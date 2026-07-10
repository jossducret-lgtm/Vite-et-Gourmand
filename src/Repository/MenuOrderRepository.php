<?php

namespace App\Repository;

use App\Entity\MenuOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuOrder>
 */
final class MenuOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuOrder::class);
    }

    public function findForEmployeeWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')->addSelect('u')
            ->leftJoin('o.menu', 'm')->addSelect('m')
            ->orderBy('o.createdAt', 'DESC');

        if (!empty($filters['status'])) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['customer'])) {
            $qb->andWhere('u.email LIKE :customer OR u.firstName LIKE :customer OR u.lastName LIKE :customer')
                ->setParameter('customer', '%' . $filters['customer'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function getStatsByMenu(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('m.id AS menuId, m.title AS menuTitle, COUNT(o.id) AS ordersCount, SUM(o.totalPrice) AS revenue')
            ->join('o.menu', 'm')
            ->andWhere('o.status != :cancelled')
            ->setParameter('cancelled', 'ANNULEE')
            ->groupBy('m.id')
            ->addGroupBy('m.title')
            ->orderBy('ordersCount', 'DESC');

        if (!empty($filters['dateStart'])) {
            $qb->andWhere('o.createdAt >= :dateStart')
                ->setParameter('dateStart', new \DateTimeImmutable($filters['dateStart'] . ' 00:00:00'));
        }

        if (!empty($filters['dateEnd'])) {
            $qb->andWhere('o.createdAt <= :dateEnd')
                ->setParameter('dateEnd', new \DateTimeImmutable($filters['dateEnd'] . ' 23:59:59'));
        }

        return $qb->getQuery()->getArrayResult();
    }
}