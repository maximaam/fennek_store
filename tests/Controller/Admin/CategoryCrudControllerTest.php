<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CategoryCrudControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private SluggerInterface $slugger;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->slugger = self::getContainer()->get(SluggerInterface::class);

        $this->cleanUp();
    }

    public function testIndexPageSuccessfull(): void
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

    public function testNewPageSuccessfull(): void
    {
        $this->client->request('GET', '/admin/category/new');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Kategorie');
        self::assertSelectorExists('h1');
        self::assertSelectorTextContains('h1', 'Kategorie');
        self::assertSelectorExists('select#Category_parent');
    }

    public function testEditPageSuccessfull(): void
    {
        $category = $this->createCategory(true, 'Name DE', 'Name EN');

        $this->client->request('GET', sprintf('/admin/category/%s/edit', $category->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
        self::assertSelectorExists('select#Category_parent');
        self::assertSelectorExists('input#Category_nameDe');

        $inputValue = $this->client->getCrawler()->filter('input#Category_nameDe')->attr('value');
        self::assertSame($category->getNameDe(), $inputValue);
    }

    public function testDetailPageSuccessfull(): void
    {
        $category = $this->createCategory(true, 'Name DE', 'Name EN');

        $this->client->request('GET', sprintf('/admin/category/%s', $category->getId()));
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
            'Category[nameDe]' => 'Parent DE',
            'Category[nameEn]' => 'Parent EN',
            'Category[descriptionDe]' => 'Description DE',
            'Category[descriptionEn]' => 'Description EN',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        $category = $this->em->getRepository(Category::class)->findOneBy(['nameDe' => 'Parent DE']);
        self::assertNotNull($category);
        self::assertNull($category->getParent());
        self::assertSame('Parent EN', $category->getNameEn());
    }

    public function testCreateChildCategory(): void
    {
        $parent = $this->createCategory(true, 'Parent DE', 'Parent EN');

        $this->client->request('GET', '/admin/category/new');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('ea[newForm][btn]', [
            'Category[parent]' => $parent->getId(),
            'Category[nameDe]' => 'Child DE',
            'Category[nameEn]' => 'Child EN',
            'Category[descriptionDe]' => 'Child DE desc',
            'Category[descriptionEn]' => 'Child EN desc',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        $child = $this->em->getRepository(Category::class)->findOneBy(['nameDe' => 'Child DE']);
        self::assertNotNull($child);
        self::assertSame($parent->getId(), $child->getParent()?->getId());
        self::assertSame('Child EN', $child->getNameEn());
    }

    public function testEditParentCategory(): void
    {
        $category = $this->createCategory(true, 'Parent DE', 'Parent EN');
        self::assertNull($category->getParent());

        $this->client->request('GET', sprintf('/admin/category/%s/edit', $category->getId()));
        self::assertResponseIsSuccessful();

        $this->client->submitForm('ea[newForm][btn]', [
            'Category[parent]' => '',
            'Category[nameDe]' => 'Parent DE Edited',
            'Category[nameEn]' => 'Parent EN Edited',
            'Category[descriptionDe]' => 'Edited DE',
            'Category[descriptionEn]' => 'Edited EN',
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

        $this->client->request('GET', sprintf('/admin/category/%s/edit', $child->getId()));
        self::assertResponseIsSuccessful();

        $this->client->submitForm('ea[newForm][btn]', [
            'Category[parent]' => $parent->getId(),
            'Category[nameDe]' => 'Child DE Edited',
            'Category[nameEn]' => 'Child EN Edited',
            'Category[descriptionDe]' => 'Edited DE',
            'Category[descriptionEn]' => 'Edited EN',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        $updatedChild = $this->em->getRepository(Category::class)->find($child->getId());
        self::assertNotNull($updatedChild);
        self::assertSame($parent->getId(), $updatedChild->getParent()?->getId());
        self::assertSame('Child DE Edited', $updatedChild->getNameDe());
        self::assertSame('Child EN Edited', $updatedChild->getNameEn());
    }

    protected function tearDown(): void
    {
        // Clean up all categories to keep DB clean for other tests
        $this->cleanUp();

        parent::tearDown();
        unset($this->em);
    }

    /**
     * EventSubscriber of EA does not apply in the text context,
     * so position and alias must be manually.
     */
    private function createCategory(bool $isParent, string $nameDe, string $nameEn, ?Category $parent = null): Category
    {
        $category = new Category();
        $category->setNameDe($nameDe)
                 ->setNameEn($nameEn)
                 ->setAliasDe($this->slugger->slug($nameDe)->toString())
                 ->setAliasEn($this->slugger->slug($nameEn)->toString());

        if (!$isParent && $parent !== null) {
            $category->setParent($parent);
        }

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function cleanUp(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Category c')->execute();        
    }
}
