<?php

namespace App\Service;

use MongoDB\Client;
use MongoDB\Collection;
use Psr\Log\LoggerInterface;

final class MongoStatsService
{
    private ?Collection $collection = null;

    public function __construct(
        private readonly string $mongodbUrl,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function recordOrderStat(
        int $menuId,
        string $menuTitle,
        string $orderNumber,
        float $totalPrice,
        string $status,
        \DateTimeInterface $createdAt
    ): void {
        try {
            $this->getCollection()->updateOne(
                ['orderNumber' => $orderNumber],
                ['$set' => [
                    'menuId' => $menuId,
                    'menuTitle' => $menuTitle,
                    'orderNumber' => $orderNumber,
                    'totalPrice' => $totalPrice,
                    'status' => $status,
                    'createdAt' => $createdAt->format('Y-m-d H:i:s'),
                    'year' => (int) $createdAt->format('Y'),
                    'month' => (int) $createdAt->format('m'),
                ]],
                ['upsert' => true]
            );
        } catch (\Throwable $exception) {
            $this->logger->error('MongoDB stats sync failed: ' . $exception->getMessage());
        }
    }

    public function isAvailable(): bool
    {
        try {
            $client = new Client($this->mongodbUrl);
            $client->selectDatabase('vite_gourmand')->command(['ping' => 1]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<array{menuId: int, menuTitle: string, ordersCount: int, revenue: float}>
     */
    public function getOrdersByMenu(?int $menuId = null, ?string $dateStart = null, ?string $dateEnd = null): array
    {
        try {
            $match = ['status' => ['$ne' => 'ANNULEE']];

            if ($menuId) {
                $match['menuId'] = $menuId;
            }

            if ($dateStart) {
                $match['createdAt']['$gte'] = $dateStart . ' 00:00:00';
            }

            if ($dateEnd) {
                $match['createdAt']['$lte'] = $dateEnd . ' 23:59:59';
            }

            $cursor = $this->getCollection()->aggregate([
                ['$match' => $match],
                [
                    '$group' => [
                        '_id' => ['menuId' => '$menuId', 'menuTitle' => '$menuTitle'],
                        'ordersCount' => ['$sum' => 1],
                        'revenue' => ['$sum' => '$totalPrice'],
                    ],
                ],
                ['$sort' => ['ordersCount' => -1]],
            ]);

            $results = [];
            foreach ($cursor as $document) {
                $results[] = [
                    'menuId' => (int) $document['_id']['menuId'],
                    'menuTitle' => (string) $document['_id']['menuTitle'],
                    'ordersCount' => (int) $document['ordersCount'],
                    'revenue' => round((float) $document['revenue'], 2),
                ];
            }

            return $results;
        } catch (\Throwable $exception) {
            $this->logger->error('MongoDB stats read failed: ' . $exception->getMessage());

            return [];
        }
    }

    private function getCollection(): Collection
    {
        if ($this->collection === null) {
            $client = new Client($this->mongodbUrl);
            $this->collection = $client->selectCollection('vite_gourmand', 'order_stats');
        }

        return $this->collection;
    }
}
