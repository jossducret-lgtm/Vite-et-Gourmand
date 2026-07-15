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

    /** menus actifs avec filtres (page catalogue + ajax) */
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
            // ex: si le client veut 8 pers, on montre les menus avec min <= 8
            $qb->andWhere('m.minPeople <= :minPeople')
                ->setParameter('minPeople', $filters['minPeople']);
        }

        return $qb->getQuery()->getResult();
    }

    /** détail menu par slug (côté public) */
    public function findActiveBySlug(string $slug): ?Menu
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->addSelect('t')
            ->leftJoin('m.diet', 'd')
            ->addSelect('d')
            ->leftJoin('m.images', 'i')
            ->addSelect('i')
            ->leftJoin('m.dish', 'dish')
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

    /** pour afficher quelques menus sur l'accueil */
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

    /** recherche simple dans le back-office */
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
