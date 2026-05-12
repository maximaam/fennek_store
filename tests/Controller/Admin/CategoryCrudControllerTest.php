<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Helper\EntityHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CategoryCrudControllerTest extends WebTestCase
{
    use LoginUserTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private SluggerInterface $slugger;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->loginSuperAdmin($this->client);

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $this->em = $em;

        /** @var SluggerInterface $slugger */
        $slugger = self::getContainer()->get(SluggerInterface::class);
        $this->slugger = $slugger;

        $this->cleanUp();
    }

    public function testIndexPageSuccessful(): void
    {
        $this->client->request('GET', '/admin/category');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Kategorien Liste');
        self::assertSelectorExists('h1');
        self::assertSelectorTextContains('h1', 'Kategorien Liste');
        self::assertSelectorExists('#main');
        self::assertSelectorExists('.page-actions');
        self::assertSelectorExists('.table.datagrid');
    }

    public function testNewPageSuccessful(): void
    {
        $this->client->request('GET', '/admin/category/new');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Kategorie');
        self::assertSelectorExists('h1');
        self::assertSelectorTextContains('h1', 'Kategorie');
        self::assertSelectorExists('select#Category_parent');
    }

    public function testEditPageSuccessful(): void
    {
        $category = $this->createCategory(true, 'Name DE', 'Name EN');

        $this->client->request('GET', \sprintf('/admin/category/%s/edit', $category->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
        self::assertSelectorExists('select#Category_parent');
        self::assertSelectorExists('input#Category_translations_0_name');
        self::assertSelectorExists('textarea#Category_translations_0_description');
        self::assertSelectorExists('input#Category_translations_1_name');
        self::assertSelectorExists('textarea#Category_translations_1_description');

        $inputValue = $this->client->getCrawler()->filter('input#Category_translations_0_name')->attr('value');
        self::assertSame($category->getNameDe(), $inputValue);
        $inputValue = $this->client->getCrawler()->filter('input#Category_translations_1_name')->attr('value');
        self::assertSame($category->getNameEn(), $inputValue);
    }

    public function testDetailPageSuccessful(): void
    {
        $category = $this->createCategory(true, 'Name DE', 'Name EN');

        $this->client->request('GET', \sprintf('/admin/category/%s', $category->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
        self::assertSelectorExists('section#main');
        self::assertSelectorTextContains('span[title="'.$category->getNameDe().'"]', $category->getNameDe());
    }

    public function testCreateParentCategory(): void
    {
        $this->client->request('GET', '/admin/category/new');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('ea[newForm][btn]', [
            'Category[parent]' => '',
            'Category[translations][0][name]' => 'Name DE',
            'Category[translations][1][name]' => 'Name EN',
            'Category[translations][0][description]' => 'Description DE',
            'Category[translations][1][description]' => 'Description EN',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        $category = $this->em->getRepository(Category::class)->fetchOneBy(['name' => 'Name DE'], EntityHelper::LOCALE_DE);
        self::assertNotNull($category);
        self::assertNull($category->getParent());
        self::assertSame('Name EN', $category->getNameEn());
    }

    public function testCreateChildCategory(): void
    {
        // Call first, otherwise client run setup and deletes the category
        $parent = $this->createCategory(true, 'ParentName DE', 'ParentName EN');

        $this->client->request('GET', '/admin/category/new');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('ea[newForm][btn]', [
            'Category[parent]' => $parent->getId(),
            'Category[translations][0][name]' => 'Child Name DE',
            'Category[translations][1][name]' => 'Child Name EN',
            'Category[translations][0][description]' => 'Child Description DE',
            'Category[translations][1][description]' => 'Child Description EN',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        $child = $this->em
            ->getRepository(Category::class)
            ->fetchOneBy(['name' => 'Child Name DE'], EntityHelper::LOCALE_DE);
        self::assertNotNull($child);
        self::assertNotNull($child->getParent());
        self::assertSame($parent->getId(), $child->getParent()->getId());
        self::assertSame('Child Name EN', $child->getNameEn());
    }

    public function testEditParentCategory(): void
    {
        $category = $this->createCategory(true, 'Parent DE', 'Parent EN');
        self::assertNull($category->getParent());

        $this->client->request('GET', \sprintf('/admin/category/%s/edit', $category->getId()));
        self::assertResponseIsSuccessful();

        $this->client->submitForm('ea[newForm][btn]', [
            'Category[parent]' => '',
            'Category[translations][0][name]' => 'Parent DE Edited',
            'Category[translations][1][name]' => 'Parent EN Edited',
            'Category[translations][0][description]' => 'Parent Description DE Edited',
            'Category[translations][1][description]' => 'Parent Description EN Edited',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        $updated = $this->em->getRepository(Category::class)->find($category->getId());
        self::assertNotNull($updated);
        self::assertNull($updated->getParent());
        self::assertSame('Parent EN Edited', $updated->getNameEn());
    }

    public function testEditChildCategory(): void
    {
        $parent = $this->createCategory(true, 'Parent DE', 'Parent EN');
        $child = $this->createCategory(false, 'Child DE', 'Child EN', $parent);

        $this->client->request('GET', \sprintf('/admin/category/%s/edit', $child->getId()));
        self::assertResponseIsSuccessful();

        $this->client->submitForm('ea[newForm][btn]', [
            'Category[parent]' => $parent->getId(),
            'Category[translations][0][name]' => 'Child DE Edited',
            'Category[translations][1][name]' => 'Child EN Edited',
            'Category[translations][0][description]' => 'ChildDescription DE Edited',
            'Category[translations][1][description]' => 'ChildDescription EN Edited',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        $updatedChild = $this->em->getRepository(Category::class)->find($child->getId());
        self::assertNotNull($updatedChild);
        self::assertSame($parent->getId(), $updatedChild->getParent()?->getId());
        self::assertSame('Child DE Edited', $updatedChild->getNameDe());
        self::assertSame('Child EN Edited', $updatedChild->getNameEn());
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->cleanUp();

        parent::tearDown();
        $this->em->close();
        unset($this->em);
    }

    /**
     * EventSubscriber of EA does not apply in the text context,
     * so position and alias must be manually.
     */
    private function createCategory(bool $isParent, string $nameDe, string $nameEn, ?Category $parent = null): Category
    {
        $category = new Category();
        $translationDe = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_DE)
            ->setName($nameDe)
            ->setAlias($this->slugger->slug($nameDe)->toString());
        $translationEn = new CategoryTranslation()
            ->setLocale(EntityHelper::LOCALE_EN)
            ->setName($nameEn)
            ->setAlias($this->slugger->slug($nameEn)->toString());

        $category->addTranslation($translationDe);
        $category->addTranslation($translationEn);

        if (!$isParent && $parent instanceof Category) {
            $category->setParent($parent);
        }

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function cleanUp(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\CategoryTranslation ct')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Category c')->execute();
    }
}
