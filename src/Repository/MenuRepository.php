<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /**
     * Récupère les menus actifs avec filtres dynamiques.
     *
     * Utilisé pour :
     * - la page /menus
     * - la route AJAX /menus/filter
     *
     * Filtres possibles :
     * - minPrice
     * - maxPrice
     * - theme
     * - diet
     * - minPeople
     */
    public function findActiveWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->addSelect('t')
            ->leftJoin('m.diet', 'd')
            ->addSelect('d')
            ->leftJoin('m.images', 'i')
            ->addSelect('i')
            ->andWhere('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.createdAt', 'DESC');

        if (!empty($filters['minPrice']) && is_numeric($filters['minPrice'])) {
            $qb->andWhere('m.pricePerPerson >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        if (!empty($filters['maxPrice']) && is_numeric($filters['maxPrice'])) {
            $qb->andWhere('m.pricePerPerson <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (!empty($filters['theme']) && is_numeric($filters['theme'])) {
            $qb->andWhere('t.id = :theme')
                ->setParameter('theme', $filters['theme']);
        }

        if (!empty($filters['diet']) && is_numeric($filters['diet'])) {
            $qb->andWhere('d.id = :diet')
                ->setParameter('diet', $filters['diet']);
        }

        if (!empty($filters['minPeople']) && is_numeric($filters['minPeople'])) {
            /**
             * Ici on cherche les menus dont le minimum est inférieur ou égal
             * au nombre de personnes renseigné par le visiteur.
             *
             * Exemple :
             * L'utilisateur indique 8 personnes.
             * On affiche les menus possibles jusqu'à 8 personnes minimum.
             */
            $qb->andWhere('m.minPeople <= :minPeople')
                ->setParameter('minPeople', $filters['minPeople']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère un menu actif par son slug.
     *
     * Utilisé pour la page détail :
     * /menus/{slug}
     */
    public function findActiveBySlug(string $slug): ?Menu
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->addSelect('t')
            ->leftJoin('m.diet', 'd')
            ->addSelect('d')
            ->leftJoin('m.images', 'i')
            ->addSelect('i')
            ->leftJoin('m.dishes', 'dish')
            ->addSelect('dish')
            ->leftJoin('dish.allergens', 'a')
            ->addSelect('a')
            ->andWhere('m.slug = :slug')
            ->andWhere('m.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les derniers menus actifs.
     *
     * Utile pour afficher quelques menus sur l'accueil si besoin.
     */
    public function findLatestActive(int $limit = 3): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->addSelect('t')
            ->leftJoin('m.diet', 'd')
            ->addSelect('d')
            ->leftJoin('m.images', 'i')
            ->addSelect('i')
            ->andWhere('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche simple côté employé/admin.
     *
     * Utile si tu veux plus tard ajouter une barre de recherche
     * dans le CRUD des menus.
     */
    public function searchForBackOffice(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->addSelect('t')
            ->leftJoin('m.diet', 'd')
            ->addSelect('d')
            ->orderBy('m.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('m.title LIKE :search OR m.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }
}