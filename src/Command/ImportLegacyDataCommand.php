<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Category;
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
        #[Autowire('%kernel.project_dir%')]
        private string $rootDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('table', InputArgument::REQUIRED, 'Argument description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $table = $input->getArgument('table');
        $io->note(\sprintf('You are about to import data from %s table.', $table));

        $this->$table();

        $io->success('Data imported successfully.');

        return Command::SUCCESS;
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function category(): void
    {
        $file = new \SplFileObject($this->rootDir.'/var/category.csv');
        $file->setFlags(
            \SplFileObject::READ_CSV
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE
        );

        /** @var array<string, Category> $parents */
        $parents = [];

        // 1️⃣ Create parent categories
        foreach ($file as $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }

            $row = array_map('trim', $row);
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

            $row = array_map('trim', $row);

            [$id, $parentId] = $row;
            if ('NULL' === $parentId) {
                continue;
            }

            $parent = $parents[$parentId] ??
                throw new \RuntimeException("Parent category $parentId not found.");

            $category = $this->createCategoryFromRow($row, $parent);
            $this->entityManager->persist($category);
        }

        $this->entityManager->flush();
    }

    /**
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
}
