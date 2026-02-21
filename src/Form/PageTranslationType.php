<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PageTranslation;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextEditorType;use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', HiddenType::class)
            ->add('title', null, [
                'label' => 'label.title.all',
            ])
            ->add('description', null, [
                'label' => 'label.description.all',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageTranslation::class,
        ]);
    }
}
