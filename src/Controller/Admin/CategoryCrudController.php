<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {}

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    // Override to set the position
    /*
    public function createEntity(string $entityFqcn)
    {
        $category = new Category();
        $lastParent = $this->em
            ->getRepository(Category::class)
            ->findLastCreatedParent();

        $nextPosition = ($lastParent?->getPosition() ?? 0) + 1;
        $category->setPosition($nextPosition);

        return $category;
    }
    */

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('category.create_new')
            // ->setEntityLabelInPlural('category.plural')
            ->setPageTitle(Crud::PAGE_INDEX, 'category.list')
            ->setPageTitle(Crud::PAGE_NEW, 'category.singular')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(25)
            ->setPageTitle(Crud::PAGE_EDIT, fn (Category $category) => sprintf('Edit "%s"', $category->getNameDe()))
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Category $category) => sprintf('Details for "%s"', $category->getNameDe()))
        ;
        
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            $parentCategory = AssociationField::new('parent')
                ->setLabel('category.parent')
                ->renderAsNativeWidget()
                ->setFormTypeOptions([
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->andWhere('c.parent IS NULL')
                            ->orderBy('c.nameDe', 'ASC');
                    },
                ]);

            if ($pageName === Crud::PAGE_EDIT) {
                $parentCategory->setRequired(true);
            }

            yield FormField::addColumn(6);
            yield $parentCategory;

            yield FormField::addColumn(6);
            if (Crud::PAGE_EDIT === $pageName) {
                $category = $this->getContext()?->getEntity()?->getInstance();
                if (null === $category?->getParent()) {
                    yield IntegerField::new('position')
                        ->setHelp('Nur bei TOP Kategorien');
                }
            }

            yield FormField::addColumn(6);
            yield FormField::addFieldset('');
            yield TextField::new('nameDe', 'category.name.de');
            yield TextareaField::new('descriptionDe', 'category.description.de');
            // yield FormField::addFieldset();

            yield FormField::addColumn(6);
            yield FormField::addFieldset();
            yield TextField::new('nameEn', 'category.name.en');
            yield TextareaField::new('descriptionEn', 'category.description.en');

        }

        if ($pageName === Crud::PAGE_INDEX) {
            yield AssociationField::new('parent')->setLabel('category.parent');
            yield TextField::new('nameDe', 'Name');
            yield IntegerField::new('position');
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            yield FormField::addColumn(6);
            yield TextField::new('nameDe');
            yield TextField::new('aliasDe');
            yield TextField::new('descriptionDe');

            yield FormField::addColumn(6);
            yield TextField::new('nameEn');
            yield TextField::new('aliasEn');
            yield TextField::new('descriptionEn');

            yield FormField::addColumn(12);
            yield IntegerField::new('position');
        }
    }
}
