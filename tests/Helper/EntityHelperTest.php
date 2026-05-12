<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Helper\EntityHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

final class EntityHelperTest extends TestCase
{
    public function testSetCategoryAlias(): void
    {
        $slugger = $this->createMock(SluggerInterface::class);

        $slugger->expects(self::once())
            ->method('slug')
            ->with('Test Category')
            ->willReturn(new UnicodeString('test-category'));

        $helper = new EntityHelper($slugger);

        $translation = new CategoryTranslation();
        $translation->setName('Test Category');

        $category = new Category();
        $category->addTranslation($translation);

        $helper->setCategoryAlias($category);

        self::assertSame('test-category', $translation->getAlias());
    }
}
