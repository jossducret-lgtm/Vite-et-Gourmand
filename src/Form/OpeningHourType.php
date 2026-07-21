<?php

namespace App\Form;

use App\Entity\OpeningHour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OpeningHourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dayOfWeek', ChoiceType::class, [
                'label' => 'Jour',
                'choices' => [
                    'Lundi' => 'LUNDI',
                    'Mardi' => 'MARDI',
                    'Mercredi' => 'MERCREDI',
                    'Jeudi' => 'JEUDI',
                    'Vendredi' => 'VENDREDI',
                    'Samedi' => 'SAMEDI',
                    'Dimanche' => 'DIMANCHE',
                ],
            ])
            ->add('openingTime', TimeType::class, ['label' => 'Ouverture', 'widget' => 'single_text', 'required' => false])
            ->add('closingTime', TimeType::class, ['label' => 'Fermeture', 'widget' => 'single_text', 'required' => false])
            ->add('isClosed', CheckboxType::class, ['label' => 'Fermé ce jour', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpeningHour::class,
        ]);
    }
}
