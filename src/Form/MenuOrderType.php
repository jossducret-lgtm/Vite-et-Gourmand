<?php

namespace App\Form;

use App\Entity\MenuOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class MenuOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $menu = $options['menu'];
        $maxPeople = $options['max_people'] ?? ($menu ? $menu->getStockQuantity() : null);

        $peopleConstraints = [
            new Assert\NotBlank(),
            new Assert\Positive(),
        ];

        if ($menu) {
            $peopleConstraints[] = new Assert\GreaterThanOrEqual(
                $menu->getMinPeople(),
                message: 'Le nombre de personnes doit être au minimum de {{ compared_value }}.'
            );
        }

        if ($maxPeople !== null) {
            $peopleConstraints[] = new Assert\LessThanOrEqual(
                $maxPeople,
                message: 'Stock insuffisant : {{ compared_value }} couvert(s) disponible(s) au maximum.'
            );
        }

        $builder
            ->add('serviceDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de prestation',
                'constraints' => [new Assert\NotBlank(), new Assert\GreaterThanOrEqual('today')],
            ])
            ->add('deliveryTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure souhaitée de livraison',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('deliveryAddress', TextType::class, [
                'label' => 'Adresse de livraison',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('deliveryCity', TextType::class, [
                'label' => 'Ville de livraison',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('deliveryPlace', TextType::class, [
                'label' => 'Lieu précis',
                'required' => false,
            ])
            ->add('distanceKm', NumberType::class, [
                'label' => 'Distance en km depuis Bordeaux',
                'required' => false,
                'help' => 'Remplir seulement si livraison hors Bordeaux.',
            ])
            ->add('peopleCount', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'help' => $menu
                    ? sprintf(
                        'Minimum : %d personnes. Stock disponible : %d couvert(s).',
                        $menu->getMinPeople(),
                        $maxPeople ?? $menu->getStockQuantity()
                    )
                    : null,
                'attr' => [
                    'min' => $menu?->getMinPeople(),
                    'max' => $maxPeople,
                ],
                'constraints' => $peopleConstraints,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuOrder::class,
            'menu' => null,
            'max_people' => null,
        ]);
    }
}