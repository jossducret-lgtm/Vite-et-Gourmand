<?php

namespace App\Service;

use App\Entity\Menu;
use App\Entity\MenuOrder;

// calcul des prix commande (menu + réduction + livraison)
final class OrderPricingService
{
    public function calculatePriceDetails(Menu $menu, MenuOrder $order): array
    {
        $peopleCount = (int) $order->getPeopleCount();
        $minPeople = (int) $menu->getMinPeople();

        if ($peopleCount < $minPeople) {
            return $this->emptyPriceDetails();
        }

        $menuPrice = $peopleCount * (float) $menu->getPricePerPerson();
        // -10% si 5 personnes de plus que le minimum
        $discountAmount = $peopleCount >= $minPeople + 5 ? $menuPrice * 0.10 : 0;

        $city = mb_strtolower((string) $order->getDeliveryCity());

        if ($city === 'bordeaux') {
            $deliveryPrice = 0;
        } else {
            $distanceKm = $order->getDistanceKm() ?? 0;
            $deliveryPrice = 5 + ((float) $distanceKm * 0.59);
        }

        return [
            'menuPrice' => round($menuPrice, 2),
            'discountAmount' => round($discountAmount, 2),
            'deliveryPrice' => round($deliveryPrice, 2),
            'totalPrice' => round($menuPrice - $discountAmount + $deliveryPrice, 2),
        ];
    }

    private function emptyPriceDetails(): array
    {
        return [
            'menuPrice' => 0.0,
            'discountAmount' => 0.0,
            'deliveryPrice' => 0.0,
            'totalPrice' => 0.0,
        ];
    }
}
