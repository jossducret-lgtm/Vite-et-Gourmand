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
                'help' => 'À renseigner uniquement si la livraison est hors Bordeaux.',
            ])
            ->add('peopleCount', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'help' => $menu ? 'Minimum : ' . $menu->getMinPeople() . ' personnes' : null,
                'constraints' => [new Assert\NotBlank(), new Assert\Positive()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuOrder::class,
            'menu' => null,
        ]);
    }
}