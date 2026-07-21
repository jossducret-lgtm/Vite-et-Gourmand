<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/employe/themes')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeThemeController extends AbstractController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'app_employee_theme_index', methods: ['GET'])]
    public function index(ThemeRepository $themeRepository): Response
    {
        return $this->render('employee_theme/index.html.twig', [
            'themes' => $themeRepository->findBy([], ['label' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'app_employee_theme_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $theme = new Theme();
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->prepareTheme($theme);
            $entityManager->persist($theme);
            $entityManager->flush();

            $this->addFlash('success', 'Thème créé. Il est disponible lors de la création d\'un menu.');

            return $this->redirectToRoute('app_employee_theme_index');
        }

        return $this->render('employee_theme/new.html.twig', [
            'theme' => $theme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_employee_theme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Theme $theme, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->prepareTheme($theme);
            $entityManager->flush();

            $this->addFlash('success', 'Thème mis à jour.');

            return $this->redirectToRoute('app_employee_theme_index');
        }

        return $this->render('employee_theme/edit.html.twig', [
            'theme' => $theme,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_employee_theme_delete', methods: ['POST'])]
    public function delete(Request $request, Theme $theme, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $theme->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_employee_theme_index');
        }

        if ($theme->getMenus()->count() > 0) {
            $this->addFlash('danger', 'Impossible de supprimer un thème utilisé par au moins un menu.');

            return $this->redirectToRoute('app_employee_theme_index');
        }

        $entityManager->remove($theme);
        $entityManager->flush();

        $this->addFlash('success', 'Thème supprimé.');

        return $this->redirectToRoute('app_employee_theme_index');
    }

    private function prepareTheme(Theme $theme): void
    {
        if (!$theme->getSlug()) {
            $theme->setSlug(strtolower($this->slugger->slug($theme->getLabel() ?? 'theme')->toString()));
        }
    }
}
