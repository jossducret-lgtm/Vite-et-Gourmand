<?php

namespace App\Service;

use App\Repository\MenuOrderRepository;

// envoie les commandes MySQL vers Mongo pour les stats admin
final class MongoOrderSyncService
{
    public function __construct(
        private readonly MenuOrderRepository $menuOrderRepository,
        private readonly MongoStatsService $mongoStatsService,
    ) {
    }

    public function syncAll(): int
    {
        if (!$this->mongoStatsService->isAvailable()) {
            return 0;
        }

        $count = 0;

        foreach ($this->menuOrderRepository->findAll() as $order) {
            $menu = $order->getMenu();
            if (!$menu) {
                continue;
            }

            $this->mongoStatsService->recordOrderStat(
                (int) $menu->getId(),
                (string) $menu->getTitle(),
                (string) $order->getOrderNumber(),
                (float) $order->getTotalPrice(),
                (string) $order->getStatus(),
                $order->getCreatedAt() ?? new \DateTimeImmutable(),
            );
            ++$count;
        }

        return $count;
    }
}
