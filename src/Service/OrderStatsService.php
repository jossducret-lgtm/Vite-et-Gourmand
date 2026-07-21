<?php

namespace App\Service;

// couche utilisée par la page stats admin
final class OrderStatsService
{
    public function __construct(
        private readonly MongoStatsService $mongoStatsService,
        private readonly MongoOrderSyncService $mongoOrderSyncService,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->mongoStatsService->isAvailable();
    }

    public function getOrdersByMenu(?int $menuId = null, ?string $dateStart = null, ?string $dateEnd = null): array
    {
        if (!$this->mongoStatsService->isAvailable()) {
            return [];
        }

        $stats = $this->mongoStatsService->getOrdersByMenu($menuId, $dateStart, $dateEnd);

        // si mongo est vide on tente une synchro
        if ($stats === []) {
            $this->mongoOrderSyncService->syncAll();
            $stats = $this->mongoStatsService->getOrdersByMenu($menuId, $dateStart, $dateEnd);
        }

        return $stats;
    }

    public function getSource(): string
    {
        return $this->mongoStatsService->isAvailable() ? 'mongodb' : 'unavailable';
    }
}
