<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Category;
use App\Entity\MediaImage;
use App\Entity\Page;
use App\Entity\Product;
use App\Entity\Purchase;
use App\Enum\MediaImageOwner;
use App\Helper\EntityHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-legacy-data',
    description: 'Add a short description for your command',
)]
class ImportLegacyDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityHelper $entityHelper,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $rootDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('table', InputArgument::REQUIRED, 'Argument description');
    }

    /**
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $table = $input->getArgument('table');
        $io->note(\sprintf('You are about to import data from %s table.', $table));

        match ($table) {
            'product' => $this->product($io),
            'category' => $this->category($io),
            'page' => $this->page($io),
            'payment' => $this->payment($io),
            default => throw new \RuntimeException("Unknown table '$table'"),
        };

        $io->success('Data imported successfully.');

        return Command::SUCCESS;
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function category(SymfonyStyle $io): void
    {
        $file = $this->loadCsv('category');

        /** @var array<string, Category> $parents */
        $parents = [];

        // 1️⃣ Create parent categories
        foreach ($file as $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }

            $row = array_map(trim(...), $row);
            [$id, $parentId] = $row;
            if ('NULL' !== $parentId) {
                continue;
            }

            $category = $this->createCategoryFromRow($row, null);
            $this->entityManager->persist($category);
            $parents[$id] = $category;
        }
        $this->entityManager->flush();

        $file->rewind();

        // 3️⃣ Create child categories
        foreach ($file as $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }

            $row = array_map(trim(...), $row);

            [$id, $parentId] = $row;
            if ('NULL' === $parentId) {
                continue;
            }

            $parent = $parents[$parentId] ??
                throw new \RuntimeException("Parent category $parentId not found.");

            $category = $this->createCategoryFromRow($row, $parent);
            $io->writeln(\sprintf('Importing category : %s', $category->getNameDe()));
            $this->entityManager->persist($category);
        }

        $this->entityManager->flush();
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function product(SymfonyStyle $io): void
    {
        $products = $this->loadCsv('product');
        $categories = $this->loadCsv('category');

        // Map id to alias, to be able to find the product's category
        $cats = [];
        foreach ($categories as $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }

            $row = array_map(trim(...), $row);
            $cats[$row[0]] = $row[6];
        }

        foreach ($products as $i => $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }
            $row = array_map(trim(...), $row);

            $io->writeln(\sprintf('%d - Importing product : %s', $i, $row[6]));

            $category = $this->entityManager
                ->getRepository(Category::class)
                ->findOneBy(['aliasDe' => $cats[$row[1]]]) ?? throw new \RuntimeException("Category {$cats[$row[1]]} not found.");

            $colors = [];
            if (isset($row[10]) && '' !== $row[10] && 'NULL' !== $row[10]) {
                $colors = explode(',', $row[10]);
            }

            $sizes = null;
            if (isset($row[11]) && '' !== $row[11] && 'NULL' !== $row[11]) {
                $sizes = explode(',', $row[11]);
            }

            $price = trim($row[12]);
            if (!is_numeric($price)) {
                throw new \RuntimeException("Invalid price value: {$row[12]}");
            }

            $product = new Product()
                ->setCategory($category)
                ->setCreatedAtLegacy($row[2])
                ->setUpdatedAtLegacy($row[3])
                ->setItemNumber($row[4])
                ->setTitleDe($row[6])
                ->setTitleEn($row[7])
                ->setDescriptionDe($row[8])
                ->setDescriptionEn($row[9])
                ->setColors($colors)
                ->setSizes($sizes)
                ->setPrice((int) bcmul($price, '100', 0))
                ->setTopItem('1' === $row[13]);

            $this->entityHelper->setProductTitleSlug($product);

            if ('' !== $row[14]) {
                $images = trim($row[14], '-');
                foreach (explode('-', $images) as $image) {
                    $newImage = new MediaImage()
                        ->setOwner(MediaImageOwner::PRODUCT)
                        ->setCreatedAtLegacy($row[2])
                        ->setUpdatedAtLegacy($row[3])
                        ->setImageName($image)
                        ->setProduct($product);
                    $product->addImage($newImage);
                }
            }

            $this->entityManager->persist($product);

            if (0 === $i % 10) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function page(SymfonyStyle $io): void
    {
        $pages = $this->loadCsv('page');
        foreach ($pages as $i => $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }
            $row = array_map(trim(...), $row);

            $io->writeln(\sprintf('%d - Importing page : %s', $i, $row[3]));

            $page = new Page()
                ->setCreatedAtLegacy($row[1])
                ->setUpdatedAtLegacy($row[2])
                ->setTitleDe($row[3])
                ->setTitleEn($row[4])
                ->setDescriptionDe($row[5])
                ->setDescriptionEn($row[6])
                ->setAliasDe($row[7])
                ->setAliasEn($row[8]);

            $this->entityManager->persist($page);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function payment(SymfonyStyle $io): void
    {
        $payments = $this->loadCsv(__METHOD__);
        foreach ($payments as $i => $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }
            $row = array_map(trim(...), $row);

            $io->writeln(\sprintf('%d - Importing payment : %s', $i, $row[3]));

            $page = new Purchase()
                ->setCreatedAtLegacy($row[1])
                ->setUpdatedAtLegacy($row[2]);

            $this->entityManager->persist($page);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * @param array<int, string> $row
     *
     * @throws \DateMalformedStringException
     */
    private function createCategoryFromRow(array $row, ?Category $parent): Category
    {
        return new Category()
            ->setParent($parent)
            ->setCreatedAtLegacy($row[2])
            ->setUpdatedAtLegacy($row[3])
            ->setNameDe($row[4])
            ->setNameEn($row[5])
            ->setAliasDe($row[6])
            ->setAliasEn($row[7])
            ->setDescriptionDe($row[8])
            ->setDescriptionEn($row[9])
            ->setPosition(0);
    }

    private function loadCsv(string $filename): \SplFileObject
    {
        $file = new \SplFileObject($this->rootDir.'/var/'.$filename.'.csv');
        $file->setFlags(
            \SplFileObject::READ_CSV
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE
        );

        return $file;
    }
}
