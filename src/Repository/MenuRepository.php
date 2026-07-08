<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
final class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    public function findActiveWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')->addSelect('t')
            ->leftJoin('m.diet', 'd')->addSelect('d')
            ->leftJoin('m.images', 'i')->addSelect('i')
            ->andWhere('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.createdAt', 'DESC');

        if (!empty($filters['minPrice'])) {
            $qb->andWhere('m.pricePerPerson >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('m.pricePerPerson <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (!empty($filters['theme'])) {
            $qb->andWhere('t.id = :theme')
                ->setParameter('theme', $filters['theme']);
        }

        if (!empty($filters['diet'])) {
            $qb->andWhere('d.id = :diet')
                ->setParameter('diet', $filters['diet']);
        }

        if (!empty($filters['minPeople'])) {
            $qb->andWhere('m.minPeople <= :minPeople')
                ->setParameter('minPeople', $filters['minPeople']);
        }

        return $qb->getQuery()->getResult();
    }
}