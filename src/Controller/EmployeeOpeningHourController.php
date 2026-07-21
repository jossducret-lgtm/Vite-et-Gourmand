<?php

namespace App\Controller;

use App\Entity\OpeningHour;
use App\Form\OpeningHourType;
use App\Repository\OpeningHourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe/horaires')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeOpeningHourController extends AbstractController
{
    #[Route('', name: 'app_employee_opening_hour_index', methods: ['GET'])]
    public function index(OpeningHourRepository $openingHourRepository): Response
    {
        return $this->render('employee_opening_hour/index.html.twig', [
            'opening_hours' => $openingHourRepository->findOrderedByDay(),
        ]);
    }

    #[Route('/nouveau', name: 'app_employee_opening_hour_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $openingHour = new OpeningHour();
        $form = $this->createForm(OpeningHourType::class, $openingHour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($openingHour);
            $entityManager->flush();

            $this->addFlash('success', 'Horaire ajouté.');

            return $this->redirectToRoute('app_employee_opening_hour_index');
        }

        return $this->render('employee_opening_hour/new.html.twig', [
            'opening_hour' => $openingHour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_employee_opening_hour_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OpeningHour $openingHour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OpeningHourType::class, $openingHour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Horaire mis à jour.');

            return $this->redirectToRoute('app_employee_opening_hour_index');
        }

        return $this->render('employee_opening_hour/edit.html.twig', [
            'opening_hour' => $openingHour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_employee_opening_hour_delete', methods: ['POST'])]
    public function delete(Request $request, OpeningHour $openingHour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $openingHour->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($openingHour);
            $entityManager->flush();
            $this->addFlash('success', 'Horaire supprimé.');
        }

        return $this->redirectToRoute('app_employee_opening_hour_index');
    }
}
