<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\MenuOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
final class ReviewController extends AbstractController
{
    #[Route('/commande/{id}/avis', name: 'app_review_new', methods: ['GET', 'POST'])]
    public function new(
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

        if (!$order->canReceiveReview()) {
            $this->addFlash('danger', 'Vous pourrez donner un avis une fois la commande livrée.');
            return $this->redirectToRoute('app_account_order_show', ['id' => $order->getId()]);
        }

        if ($order->getReview()) {
            $this->addFlash('info', 'Vous avez déjà donné un avis pour cette commande.');
            return $this->redirectToRoute('app_account_order_show', ['id' => $order->getId()]);
        }

        $review = new Review();
        $review->setUser($security->getUser());
        $review->setMenuOrder($order);
        $review->setStatus('PENDING');
        $review->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();
            $this->addFlash('success', 'Votre avis a été envoyé. Il sera visible après validation.');
            return $this->redirectToRoute('app_account_order_show', ['id' => $order->getId()]);
        }

        return $this->render('review/new.html.twig', ['form' => $form, 'order' => $order]);
    }
}