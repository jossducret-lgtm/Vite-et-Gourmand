<?php

namespace App\EventSubscriber;

use App\Entity\MenuOrder;
use App\Service\MongoStatsService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
final class OrderStatsSubscriber
{
    public function __construct(
        private readonly MongoStatsService $mongoStatsService,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->syncOrder($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->syncOrder($args->getObject());
    }

    private function syncOrder(object $entity): void
    {
        if (!$entity instanceof MenuOrder) {
            return;
        }

        $menu = $entity->getMenu();
        if (!$menu) {
            return;
        }

        $this->mongoStatsService->recordOrderStat(
            (int) $menu->getId(),
            (string) $menu->getTitle(),
            (string) $entity->getOrderNumber(),
            (float) $entity->getTotalPrice(),
            (string) $entity->getStatus(),
            $entity->getCreatedAt() ?? new \DateTimeImmutable(),
        );
    }
}
