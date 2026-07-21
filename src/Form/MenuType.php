<?php

namespace App\Form;

use App\Entity\Diet;
use App\Entity\Dish;
use App\Entity\Menu;
use App\Entity\Theme;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre du menu'])
            ->add('slug', TextType::class, ['label' => 'Slug URL', 'required' => false, 'help' => 'Généré automatiquement si vide'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 4]])
            ->add('conditionsText', TextareaType::class, [
                'label' => 'Conditions du menu',
                'required' => false,
                'attr' => ['rows' => 3],
                'help' => 'Délais, conservation, livraison...',
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'label',
                'label' => 'Thème / événement',
                'help' => 'Géré dans Thèmes / événements. Ex. : Noël, Pâques, Mariage.',
                'placeholder' => 'Choisir un thème',
            ])
            ->add('diet', EntityType::class, [
                'class' => Diet::class,
                'choice_label' => 'label',
                'label' => 'Régime alimentaire',
                'placeholder' => 'Choisir un régime',
            ])
            ->add('dish', EntityType::class, [
                'class' => Dish::class,
                'choice_label' => 'title',
                'multiple' => true,
                'expanded' => false,
                'label' => 'Plats du menu',
                'help' => 'Ctrl + clic pour en sélectionner plusieurs. Les plats se créent avant dans Plats.',
                'query_builder' => static function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('d')
                        ->andWhere('d.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('d.type', 'ASC')
                        ->addOrderBy('d.title', 'ASC');
                },
                'group_by' => static function (?Dish $dish): string {
                    return match ($dish?->getType()) {
                        'ENTREE' => 'Entrées',
                        'PLAT' => 'Plats',
                        'DESSERT' => 'Desserts',
                        default => 'Autres',
                    };
                },
                'attr' => ['class' => 'form-select', 'size' => 10],
            ])
            ->add('minPeople', IntegerType::class, ['label' => 'Minimum de personnes'])
            ->add('pricePerPerson', MoneyType::class, ['label' => 'Prix par personne', 'currency' => 'EUR'])
            ->add('stockQuantity', IntegerType::class, [
                'label' => 'Stock disponible (couverts)',
                'help' => 'Doit être >= au minimum de personnes.',
            ])
            ->add('galleryFiles', FileType::class, [
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'label' => 'Photos de la galerie',
                'help' => 'JPG, PNG ou WebP, max 2 Mo par image.',
            ])
            ->add('isActive', CheckboxType::class, ['label' => 'Publier le menu (visible sur le site)', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
