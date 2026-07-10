<?php

namespace App\Form;

use App\Entity\Diet;
use App\Entity\Dish;
use App\Entity\Menu;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('slug')
            ->add('description')
            ->add('conditionsText')
            ->add('minPeople')
            ->add('pricePerPerson')
            ->add('stockQuantity')
            ->add('isActive')
            ->add('createdAt', null, [
                'widget' => 'single_text'
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text'
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'id',
            ])
            ->add('diet', EntityType::class, [
                'class' => Diet::class,
                'choice_label' => 'id',
            ])
            ->add('dish', EntityType::class, [
                'class' => Dish::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
