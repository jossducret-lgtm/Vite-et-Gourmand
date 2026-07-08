<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\MenuOrder;
use App\Entity\OrderStatusHistory;
use App\Entity\User;
use App\Form\MenuOrderType;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('/menus/{slug}/commander', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        string $slug,
        Request $request,
        MenuRepository $menuRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $menu = $menuRepository->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);

        if (!$menu instanceof Menu) {
            throw $this->createNotFoundException('Menu introuvable.');
        }

        if ($menu->getStockQuantity() <= 0) {
            $this->addFlash('danger', 'Ce menu n’est plus disponible.');

            return $this->redirectToRoute('app_menu_show', [
                'slug' => $menu->getSlug(),
            ]);
        }

        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour commander.');
        }

        $order = new MenuOrder();
        $order->setMenu($menu);
        $order->setUser($user);
        $order->setPeopleCount($menu->getMinPeople());
        $order->setDeliveryAddress($user->getAddress() ?? '');
        $order->setDeliveryCity($user->getCity() ?? 'Bordeaux');

        $form = $this->createForm(MenuOrderType::class, $order, [
            'menu' => $menu,
        ]);

        $form->handleRequest($request);

        $priceDetails = $this->calculatePriceDetails($menu, $order);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->getPeopleCount() < $menu->getMinPeople()) {
                $this->addFlash('danger', 'Le nombre de personnes doit respecter le minimum du menu.');

                return $this->render('order/new.html.twig', [
                    'form' => $form,
                    'menu' => $menu,
                    'priceDetails' => $priceDetails,
                ]);
            }

            $priceDetails = $this->calculatePriceDetails($menu, $order);

            $order->setOrderNumber('VG-' . date('Ymd-His'));
            $order->setMenuPrice((string) $priceDetails['menuPrice']);
            $order->setDiscountAmount((string) $priceDetails['discountAmount']);
            $order->setDeliveryPrice((string) $priceDetails['deliveryPrice']);
            $order->setTotalPrice((string) $priceDetails['totalPrice']);
            $order->setStatus('EN_ATTENTE');
            $order->setMaterialLoan(false);
            $order->setMaterialReturned(false);
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setUpdatedAt(new \DateTimeImmutable());

            $history = new OrderStatusHistory();
            $history->setMenuOrder($order);
            $history->setStatus('EN_ATTENTE');
            $history->setComment('Commande créée par le client.');
            $history->setCreatedAt(new \DateTimeImmutable());

            $menu->setStockQuantity($menu->getStockQuantity() - 1);

            $entityManager->persist($order);
            $entityManager->persist($history);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commande a bien été enregistrée.');

            return $this->redirectToRoute('app_account_order_show', [
                'id' => $order->getId(),
            ]);
        }

        return $this->render('order/new.html.twig', [
            'form' => $form,
            'menu' => $menu,
            'priceDetails' => $priceDetails,
        ]);
    }

    private function calculatePriceDetails(Menu $menu, MenuOrder $order): array
    {
        $peopleCount = max(
            (int) $order->getPeopleCount(),
            (int) $menu->getMinPeople()
        );

        $menuPrice = $peopleCount * (float) $menu->getPricePerPerson();
        $discountAmount = 0;

        if ($peopleCount >= $menu->getMinPeople() + 5) {
            $discountAmount = $menuPrice * 0.10;
        }

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
}