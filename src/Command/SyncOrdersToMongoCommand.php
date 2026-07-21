<?php

namespace App\Command;

use App\Service\MongoOrderSyncService;
use App\Service\MongoStatsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mongo:sync-orders',
    description: 'Synchronise les commandes MySQL vers MongoDB (stats admin)',
)]
final class SyncOrdersToMongoCommand extends Command
{
    public function __construct(
        private readonly MongoOrderSyncService $mongoOrderSyncService,
        private readonly MongoStatsService $mongoStatsService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->mongoStatsService->isAvailable()) {
            $io->error('MongoDB inaccessible. Démarrez MongoDB (voir scripts/start-mongodb.ps1) ou configurez MONGODB_URL dans .env.');
            return Command::FAILURE;
        }

        $count = $this->mongoOrderSyncService->syncAll();
        $stats = $this->mongoStatsService->getOrdersByMenu();
        $io->success(sprintf('%d commande(s) synchronisée(s). MongoDB : %d menu(x) avec stats.', $count, count($stats)));

        return Command::SUCCESS;
    }
}
