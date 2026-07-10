<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom', 'constraints' => [new Assert\NotBlank()]])
            ->add('lastName', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank()]])
            ->add('phone', TelType::class, ['label' => 'GSM', 'constraints' => [new Assert\NotBlank()]])
            ->add('address', TextType::class, ['label' => 'Adresse postale', 'constraints' => [new Assert\NotBlank()]])
            ->add('postalCode', TextType::class, ['label' => 'Code postal', 'constraints' => [new Assert\NotBlank()]])
            ->add('city', TextType::class, ['label' => 'Ville', 'constraints' => [new Assert\NotBlank()]])
            ->add('country', TextType::class, ['label' => 'Pays', 'constraints' => [new Assert\NotBlank()]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}