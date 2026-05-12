<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\DataFixtures\CategoryFixtures;
use App\Entity\Category;
use App\Helper\EntityHelper;
use App\Tests\Trait\DoctrineManagerTrait;
use App\Tests\Trait\LiipDatabaseToolTrait;
use App\Tests\Trait\LoginUserTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CategoryCrudControllerTest extends WebTestCase
{
    use DoctrineManagerTrait;
    use LiipDatabaseToolTrait;
    use LoginUserTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $this->loginSuperAdmin($this->client);
        $this->initDoctrineManager();
        $this->initDatabaseTool([CategoryFixtures::class]);
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
        $category = $this->getCategory();

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
        $category = $this->getCategory();

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

        $category = $this->em->getRepository(Category::class)
            ->fetchOneBy(['name' => 'Name DE'], EntityHelper::LOCALE_DE);
        self::assertNotNull($category);
        self::assertNull($category->getParent());
        self::assertSame('Name EN', $category->getNameEn());
    }

    public function testCreateChildCategory(): void
    {
        $parent = $this->getCategory(true);

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
        $category = $this->getCategory(true);
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
        $parent = $this->getCategory(true);
        $child = $this->getCategory();

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

    private function getCategory(bool $parent = false): Category
    {
        $qb = $this->em
            ->getRepository(Category::class)
            ->createQueryBuilder('c');
        if ($parent) {
            $qb->where('c.parent IS NULL');
        } else {
            $qb->where('c.parent IS NOT NULL');
        }

        $qb->setMaxResults(1);

        if (null !== $result = $qb->getQuery()->getOneOrNullResult()) {
            return $result;
        }

        throw new \RuntimeException('No category found');
    }
}
