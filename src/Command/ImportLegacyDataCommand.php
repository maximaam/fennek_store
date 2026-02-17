<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\MediaImage;
use App\Entity\Page;
use App\Entity\Product;
use App\Entity\Purchase;
use App\Enum\MediaImageOwner;
use App\Enum\PayPalStatus;
use App\Helper\EntityHelper;
use App\Helper\ProductHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:import-legacy-data',
    description: 'Add a short description for your command',
)]
class ImportLegacyDataCommand extends Command
{
    public const array LOCALES = ['de', 'en'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityHelper $entityHelper,
        private readonly TranslatorInterface $translator,
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
            $io->writeln(\sprintf('Importing category : %s', 'test'));
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
        $payments = $this->loadCsv(__FUNCTION__);
        foreach ($payments as $i => $row) {
            if ($row === [null] || !\is_array($row)) {
                continue;
            }
            $row = array_map(trim(...), $row);

            $products = $this->createPurchaseProduct($row);
            $payments = $this->createPurchasePayment($row);

            $io->writeln(\sprintf('%d - Importing payment : %s', $i, $row[5]));

            $purchase = new Purchase()
                ->setCreatedAtLegacy($row[1])
                ->setUpdatedAtLegacy($row[2])
                ->setStatus('2' === $row[3] ? PayPalStatus::COMPLETED : PayPalStatus::CREATED)
                ->setShipped('1' === $row[4])
                ->setOrderId($row[5])
                ->setProduct($products)
                ->setPayment($payments);

            $this->entityManager->persist($purchase);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * @param array<string, string> $row
     *
     * @return array<string, string>
     */
    private function createPurchaseProduct(array $row): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8">'.$row[12], \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        $items = [];
        foreach ($dom->getElementsByTagName('li') as $li) {
            $link = $li->getElementsByTagName('a')->item(0);
            $title = $link?->textContent ?? '';
            $meta = str_replace($title, '', trim(str_replace("\n", '', $li->textContent)));

            $resultMeta = [];
            foreach (explode('|', $meta) as $part) {
                if (!str_contains($part, ':')) {
                    continue;
                }

                [$key, $value] = array_map(trim(...), explode(':', $part, 2));
                $key = $this->normalizeKey($key);

                $resultMeta[$key] = $value;
            }

            $items[uniqid()] = [
                'title' => $title,
                'url' => $link?->getAttribute('href'),
                'meta' => $resultMeta,
            ];
        }
        libxml_clear_errors();

        $data = [];
        $amount = (int) bcmul((string) $row[8], '100', 0);
        $exclTax = round($amount / ((ProductHelper::VAT / 100) + 1), 2);
        $vat = round($amount - $exclTax, 2);

        $data['totals'] = [
            'vat' => $vat,
            'total' => $amount,
            'excl_tax' => $exclTax,
        ];
        $data['products'] = [];
        $productsIds = str_contains((string) $row[11], ',') ? explode(',', (string) $row[11]) : [$row[11]];

        $i = 0;
        foreach ($items as $key => $item) {
            $product = $this->entityManager->getRepository(Product::class)
                ->findOneBy(['titleDe' => $item['title']]);

            if (!isset($productsIds[$i])) {
                dd($productsIds);
            }

            $data['products'][$key] = [
                'id' => $productsIds[$i],
                'title' => $item['title'],
                'quantity' => $item['meta']['menge'] ?? null,
                'color' => isset($item['meta']['farbe']) ? $this->translator->trans('colors_list_de.'.$item['meta']['farbe']) : null,
                'size' => $item['meta']['groesse'] ?? null,
                'image' => $product instanceof Product ? $product->getImages()[0]->getImageName() : null,
                'item_url' => $item['url'],
                'item_number' => $product instanceof Product ? $product->getItemNumber() : null,
                'price' => $product instanceof Product ? $product->getPrice() : null,
                'full_price' => $product instanceof Product ? $product->getPrice() * ($item['meta']['menge'] ?? 1) : null,
            ];

            ++$i;
        }

        return $data;
    }

    /**
     * @param array<string, string> $row
     *
     * @return array<string, string>
     */
    private function createPurchasePayment(array $row): array
    {
        $paymentRow = unserialize($row[10]);

        return [
            'id' => $paymentRow['cart'],
            'links' => [],
            'status' => '2' === $row[3] ? PayPalStatus::COMPLETED->value : PayPalStatus::CREATED->value,
            'payer' => [
                'name' => [
                    'given_name' => $paymentRow['payer']['payer_info']['first_name'],
                    'surname' => $paymentRow['payer']['payer_info']['last_name'],
                ],
                'address' => [
                    'country_code' => $paymentRow['payer']['payer_info']['country_code'],
                ],
                'payer_id' => $paymentRow['payer']['payer_info']['payer_id'],
                'email_address' => $paymentRow['payer']['payer_info']['email'],
            ],
            'payment_source' => [
                'paypal' => [
                    'name' => [
                        'given_name' => $paymentRow['payer']['payer_info']['first_name'],
                        'surname' => $paymentRow['payer']['payer_info']['last_name'],
                    ],
                    'address' => [
                        'country_code' => $paymentRow['payer']['payer_info']['country_code'],
                    ],
                    'account_id' => $paymentRow['payer']['payer_info']['payer_id'],
                    'email_address' => $paymentRow['payer']['payer_info']['email'],
                    'account_status' => $paymentRow['payer']['status'],
                ],
            ],
            'purchase_units' => [
                [
                    'payments' => [
                        'captures' => [
                            [
                                'id' => $paymentRow['id'],
                                'links' => [],
                                'amount' => [
                                    'value' => $paymentRow['transactions'][0]['amount']['total'],
                                    'currency_code' => $paymentRow['transactions'][0]['amount']['currency'],
                                ],
                                'status' => '2' === $row[3] ? PayPalStatus::COMPLETED->value : PayPalStatus::CREATED->value,
                                'created_time' => $paymentRow['create_time'],
                                'update_time' => $paymentRow['create_time'],
                                'final_capture' => true,
                            ],
                        ],
                    ],
                    'shipping' => [
                        'name' => [
                            'full_name' => $paymentRow['payer']['payer_info']['shipping_address']['recipient_name'],
                        ],
                        'address' => [
                            'address_line_1' => $paymentRow['payer']['payer_info']['shipping_address']['line1'],
                            'admin_area_2' => $paymentRow['payer']['payer_info']['shipping_address']['city'],
                            'admin_area_1' => $paymentRow['payer']['payer_info']['shipping_address']['state'] ?? null,
                            'postal_code' => $paymentRow['payer']['payer_info']['shipping_address']['postal_code'],
                            'country_code' => $paymentRow['payer']['payer_info']['shipping_address']['country_code'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int, string> $row
     *
     * @throws \DateMalformedStringException
     */
    private function createCategoryFromRow(array $row, ?Category $parent): Category
    {
        $category = new Category()
            ->setParent($parent)
            ->setCreatedAtLegacy($row[2])
            ->setUpdatedAtLegacy($row[3])
            ->setPosition(null);

        $translationDe = new CategoryTranslation()
            ->setLocale('de')
            ->setName($row[4])
            ->setAlias($row[6])
            ->setDescription($row[8]);
        $translationEn = new CategoryTranslation()
            ->setLocale('en')
            ->setName($row[5])
            ->setAlias($row[7])
            ->setDescription($row[9]);

        $category->addTranslation($translationDe);
        $category->addTranslation($translationEn);

        return $category;
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

    private function normalizeKey(string $key): string
    {
        return str_replace(
            ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'],
            ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'],
            mb_strtolower($key)
        );
    }
}
