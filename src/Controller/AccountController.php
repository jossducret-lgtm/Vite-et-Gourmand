<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\MenuOrder;
use App\Entity\OrderStatusHistory;
use App\Form\MenuOrderType;
use App\Form\ProfileType;
use App\Repository\MenuOrderRepository;
use App\Service\OrderPricingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    // page d'accueil espace client
    #[Route('', name: 'app_account', methods: ['GET'])]
    public function index(Security $security): Response
    {
        return $this->render('account/index.html.twig', ['user' => $security->getUser()]);
    }

    #[Route('/profil', name: 'app_account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/profile.html.twig', ['form' => $form]);
    }

    #[Route('/commandes', name: 'app_account_orders', methods: ['GET'])]
    public function orders(MenuOrderRepository $menuOrderRepository, Security $security): Response
    {
        return $this->render('account/orders.html.twig', [
            'orders' => $menuOrderRepository->findBy(['user' => $security->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/commande/{id}', name: 'app_account_order_show', methods: ['GET'])]
    public function show(int $id, MenuOrderRepository $menuOrderRepository, Security $security): Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $order = $menuOrderRepository->find($id);

        if (!$order) {
                throw $this->createNotFoundException('Commande introuvable.');
            }

            if ($order->getUser()?->getId() !== $user->getId()) {
                throw $this->createNotFoundException('Commande introuvable.');
            }
                return $this->render('account/order_show.html.twig', ['order' => $order]);
            }

    #[Route('/commande/{id}/modifier', name: 'app_account_order_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        MenuOrderRepository $menuOrderRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        OrderPricingService $orderPricingService,
    ): Response {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }


        $order = $menuOrderRepository->find($id);

        if (!$order || $order->getUser()?->getId() !== $user->getId()) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if ($order->getStatus() !== 'EN_ATTENTE') {
            $this->addFlash('danger', 'Cette commande ne peut plus être modifiée car elle a déjà été acceptée.');
            return $this->redirectToRoute('app_account_order_show', ['id' => $order->getId()]);
        }

        $menu = $order->getMenu();
        $originalPeopleCount = $order->getPeopleCount();

        $form = $this->createForm(MenuOrderType::class, $order, [
            'menu' => $menu,
            'max_people' => $menu->getStockQuantity() + $originalPeopleCount,
        ]);
        $form->handleRequest($request);
        $priceDetails = $orderPricingService->calculatePriceDetails($menu, $order);

        if ($form->isSubmitted() && $form->isValid()) {
            $stockDelta = $order->getPeopleCount() - $originalPeopleCount;
            $menu->setStockQuantity($menu->getStockQuantity() - $stockDelta);

            $priceDetails = $orderPricingService->calculatePriceDetails($menu, $order);
            $order->setMenuPrice((string) $priceDetails['menuPrice']);
            $order->setDiscountAmount((string) $priceDetails['discountAmount']);
            $order->setDeliveryPrice((string) $priceDetails['deliveryPrice']);
            $order->setTotalPrice((string) $priceDetails['totalPrice']);
            $order->setUpdatedAt(new \DateTimeImmutable());

            $history = new OrderStatusHistory();
            $history->setMenuOrder($order);
            $history->setStatus($order->getStatus());
            $history->setComment('Commande modifiée par le client.');
            $history->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($history);
            $entityManager->flush();

            $this->addFlash('success', 'Commande modifiée.');
            return $this->redirectToRoute('app_account_order_show', ['id' => $order->getId()]);
        }

        return $this->render('account/order_edit.html.twig', [
            'form' => $form,
            'order' => $order,
            'menu' => $menu,
            'priceDetails' => $priceDetails,
        ]);
    }

    #[Route('/commande/{id}/annuler', name: 'app_account_order_cancel', methods: ['POST'])]
    public function cancel(
        int $id,
        Request $request,
        MenuOrderRepository $menuOrderRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }


        $order = $menuOrderRepository->find($id);

        if (!$order || $order->getUser()?->getId() !== $user->getId()) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->isCsrfTokenValid('cancel_order_' . $order->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($order->getStatus() !== 'EN_ATTENTE') {
            $this->addFlash('danger', 'Cette commande ne peut plus être annulée car elle a déjà été acceptée.');
            return $this->redirectToRoute('app_account_order_show', ['id' => $order->getId()]);
        }

        $order->setStatus('ANNULEE');
        $order->setUpdatedAt(new \DateTimeImmutable());
        $order->getMenu()->setStockQuantity($order->getMenu()->getStockQuantity() + $order->getPeopleCount());

        $history = new OrderStatusHistory();
        $history->setMenuOrder($order);
        $history->setStatus('ANNULEE');
        $history->setComment('Commande annulée par le client.');
        $history->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($history);
        $entityManager->flush();

        $this->addFlash('success', 'Votre commande a été annulée.');
        return $this->redirectToRoute('app_account_orders');
    }
}