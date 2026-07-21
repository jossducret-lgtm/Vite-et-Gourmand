<?php

namespace App\Form;

use App\Entity\Theme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Nom du thème / événement',
                'help' => 'Ex : Noël, Mariage, Séminaire...',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug URL',
                'required' => false,
                'help' => 'Généré automatiquement si vide (ex. : noel, mariage).',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Theme::class]);
    }
}
