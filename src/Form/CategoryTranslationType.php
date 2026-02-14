<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CategoryTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', HiddenType::class)
            ->add('name', null, [
                'label' => 'label.name.all',
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
            'data_class' => CategoryTranslation::class,
        ]);
    }

    /*
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) {
            $translation = $event->getData();
            $event->getForm()
                ->add('locale', HiddenType::class)
                ->add('name', null, [
                    'label' => \sprintf('label.name.all', $translation?->getLocale()),
                ])
                ->add('description', null, [
                    'label' => \sprintf('label.description.all', $translation?->getLocale()),
                    'required' => false,
                    'attr' => [
                        'rows' => 5,
                    ],
                ]);
        });
    }
    */
}
