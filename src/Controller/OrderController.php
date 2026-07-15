<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\MenuOrder;
use App\Entity\OrderStatusHistory;
use App\Entity\User;
use App\Form\MenuOrderType;
use App\Repository\MenuRepository;
use App\Service\EmailService;
use App\Service\OrderPricingService;
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
    // création d'une commande client
    #[Route('/menus/{slug}/commander', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        string $slug,
        Request $request,
        MenuRepository $menuRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        EmailService $emailService,
        OrderPricingService $orderPricingService,
    ): Response {
        $menu = $menuRepository->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);

        if (!$menu instanceof Menu) {
            throw $this->createNotFoundException('Menu introuvable.');
        }

        if (!$menu->isOrderable()) {
            // stock trop bas ou menu désactivé
            $message = $menu->getStockQuantity() > 0 && $menu->getStockQuantity() < $menu->getMinPeople()
                ? 'Stock insuffisant pour le minimum de ' . $menu->getMinPeople() . ' personnes.'
                : 'Ce menu n’est plus disponible.';

            $this->addFlash('danger', $message);

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
            'max_people' => $menu->getStockQuantity(),
        ]);

        $form->handleRequest($request);

        $priceDetails = $orderPricingService->calculatePriceDetails($menu, $order);

        if ($form->isSubmitted() && $form->isValid()) {
            $priceDetails = $orderPricingService->calculatePriceDetails($menu, $order);

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

            // on enlève le stock (nombre de couverts commandés)
            $menu->setStockQuantity($menu->getStockQuantity() - $order->getPeopleCount());

            $entityManager->persist($order);
            $entityManager->persist($history);
            $entityManager->flush();

            $emailService->sendOrderConfirmation($order);

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
}