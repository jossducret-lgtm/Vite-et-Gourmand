<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Form\ContactMessageType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        $contactMessage = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $contactMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactMessage->setIsProcessed(false);
            $contactMessage->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($contactMessage);
            $entityManager->flush();

            // mail envoyé à l'entreprise
            $emailService->sendContactMessage($contactMessage);
            $this->addFlash('success', 'Message envoyé, nous vous répondrons rapidement.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', ['form' => $form]);
    }
}
