<?php

namespace App\Repository;

use App\Entity\OpeningHour;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpeningHour>
 */
class OpeningHourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpeningHour::class);
    }

    private const DAY_ORDER = [
        'LUNDI' => 1,
        'MARDI' => 2,
        'MERCREDI' => 3,
        'JEUDI' => 4,
        'VENDREDI' => 5,
        'SAMEDI' => 6,
        'DIMANCHE' => 7,
    ];

    /**
     * @return OpeningHour[]
     */
    public function findOrderedByDay(): array
    {
        $hours = $this->findAll();

        usort($hours, static function (OpeningHour $a, OpeningHour $b): int {
            $orderA = self::DAY_ORDER[$a->getDayOfWeek() ?? ''] ?? 99;
            $orderB = self::DAY_ORDER[$b->getDayOfWeek() ?? ''] ?? 99;

            return $orderA <=> $orderB;
        });

        return $hours;
    }
}
