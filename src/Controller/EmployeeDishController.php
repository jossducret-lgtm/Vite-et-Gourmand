<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Form\DishType;
use App\Repository\DishRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe/plats')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDishController extends AbstractController
{
    public function __construct(
        private readonly FileUploader $fileUploader,
    ) {
    }

    #[Route('', name: 'app_employee_dish_index', methods: ['GET'])]
    public function index(DishRepository $dishRepository): Response
    {
        return $this->render('employee_dish/index.html.twig', [
            'dishes' => $dishRepository->findAll(),
        ]);
    }

    #[Route('/nouveau', name: 'app_employee_dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dish = new Dish();
        $dish->setIsActive(true);
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhoto($form, $dish);
            $dish->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($dish);
            $entityManager->flush();

            $this->addFlash('success', 'Plat créé avec succès.');

            return $this->redirectToRoute('app_employee_dish_index');
        }

        return $this->render('employee_dish/new.html.twig', [
            'dish' => $dish,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_dish_show', methods: ['GET'])]
    public function show(Dish $dish): Response
    {
        return $this->render('employee_dish/show.html.twig', ['dish' => $dish]);
    }

    #[Route('/{id}/modifier', name: 'app_employee_dish_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhoto($form, $dish);
            $dish->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Plat mis à jour.');

            return $this->redirectToRoute('app_employee_dish_index');
        }

        return $this->render('employee_dish/edit.html.twig', [
            'dish' => $dish,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_employee_dish_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dish->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($dish);
            $entityManager->flush();
            $this->addFlash('success', 'Plat supprimé.');
        }

        return $this->redirectToRoute('app_employee_dish_index');
    }

    private function handlePhoto(\Symfony\Component\Form\FormInterface $form, Dish $dish): void
    {
        if ($form->has('photoFile') && $form->get('photoFile')->getData()) {
            $dish->setPhoto($this->fileUploader->upload($form->get('photoFile')->getData(), 'dish'));
        }
    }
}
