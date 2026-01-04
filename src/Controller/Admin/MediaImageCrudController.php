<?php

namespace App\Controller\Admin;

use App\Entity\MediaImage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MediaImage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('imageFile')
            ->setFormType(VichImageType::class)
            ->onlyOnForms();

        yield ImageField::new('imageName')
            ->setBasePath('/uploads/pages')
            ->onlyOnIndex();
    }
}

