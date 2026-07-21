<?php

namespace App\Controller;

use App\Entity\OrderStatusHistory;
use App\Repository\MenuOrderRepository;
use App\Repository\ReviewRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeController extends AbstractController
{
    #[Route('', name: 'app_employee_dashboard', methods: ['GET'])]
    public function dashboard(MenuOrderRepository $menuOrderRepository, ReviewRepository $reviewRepository): Response
    {
        return $this->render('employee/index.html.twig', [
            'pendingOrdersCount' => count($menuOrderRepository->findBy(['status' => 'EN_ATTENTE'])),
            'pendingReviewsCount' => count($reviewRepository->findBy(['status' => 'PENDING'])),
        ]);
    }

    #[Route('/commandes', name: 'app_employee_orders', methods: ['GET'])]
    public function orders(Request $request, MenuOrderRepository $menuOrderRepository): Response
    {
        $filters = [
            'status' => $request->query->get('status'),
            'customer' => $request->query->get('customer'),
        ];

        return $this->render('employee/orders.html.twig', [
            'orders' => $menuOrderRepository->findForEmployeeWithFilters($filters),
            'filters' => $filters,
            'statuses' => $this->getStatuses(),
        ]);
    }

    #[Route('/commande/{id}/statut', name: 'app_employee_order_status', methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        MenuOrderRepository $menuOrderRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        EmailService $emailService
    ): Response {
        $order = $menuOrderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->isCsrfTokenValid('employee_order_status_' . $order->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $newStatus = $request->request->get('status');
        $comment = $request->request->get('comment');
        $contactMode = $request->request->get('cancellationContactMode');
        $cancellationReason = $request->request->get('cancellationReason');

        if (!in_array($newStatus, $this->getStatuses(), true)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirectToRoute('app_employee_orders');
        }

        if ($newStatus === 'ANNULEE') {
            if (!$contactMode || !$cancellationReason) {
                $this->addFlash('danger', 'Pour annuler, vous devez préciser le mode de contact et le motif.');
                return $this->redirectToRoute('app_employee_orders');
            }

            $order->setCancellationContactMode($contactMode);
            $order->setCancellationReason($cancellationReason);
            $order->getMenu()->setStockQuantity($order->getMenu()->getStockQuantity() + $order->getPeopleCount());
        }

        if ($newStatus === 'EN_ATTENTE_RETOUR_MATERIEL') {
            $order->setMaterialLoan(true);
            $emailService->sendMaterialReturnWarning($order);
        }

        if ($newStatus === 'TERMINEE') {
            if ($order->isMaterialLoan()) {
                $order->setMaterialReturned(true);
            }
            $emailService->sendOrderCompleted($order);
        }

        $order->setStatus($newStatus);
        $order->setUpdatedAt(new \DateTimeImmutable());

        $history = new OrderStatusHistory();
        $history->setMenuOrder($order);
        $history->setStatus($newStatus);
        $history->setComment($comment ?: 'Mise à jour du statut.');
        $history->setChangedBy($security->getUser());
        $history->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($history);
        $entityManager->flush();

        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('app_employee_orders');
    }

    #[Route('/avis', name: 'app_employee_reviews', methods: ['GET'])]
    public function reviews(ReviewRepository $reviewRepository): Response
    {
        return $this->render('employee/reviews.html.twig', [
            'reviews' => $reviewRepository->findBy(['status' => 'PENDING'], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/avis/{id}/moderation', name: 'app_employee_review_moderate', methods: ['POST'])]
    public function moderateReview(
        int $id,
        Request $request,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $review = $reviewRepository->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Avis introuvable.');
        }

        if (!$this->isCsrfTokenValid('moderate_review_' . $review->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $action = $request->request->get('action');

        if ($action === 'validate') {
            $review->setStatus('VALIDATED');
        } elseif ($action === 'refuse') {
            $review->setStatus('REFUSED');
        } else {
            $this->addFlash('danger', 'Action invalide.');
            return $this->redirectToRoute('app_employee_reviews');
        }

        $review->setModeratedBy($security->getUser());
        $review->setModeratedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Avis modéré.');
        return $this->redirectToRoute('app_employee_reviews');
    }

    private function getStatuses(): array
    {
        return [
            'EN_ATTENTE',
            'ACCEPTEE',
            'EN_PREPARATION',
            'EN_COURS_DE_LIVRAISON',
            'LIVREE',
            'EN_ATTENTE_RETOUR_MATERIEL',
            'TERMINEE',
            'ANNULEE',
        ];
    }
}
