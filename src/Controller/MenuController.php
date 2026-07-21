<?php

namespace App\Controller;

use App\Repository\DietRepository;
use App\Repository\MenuRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/menus')]
final class MenuController extends AbstractController
{
    #[Route('', name: 'app_menu_index', methods: ['GET'])]
    public function index(
        Request $request,
        MenuRepository $menuRepository,
        ThemeRepository $themeRepository,
        DietRepository $dietRepository
    ): Response {
        $filters = $this->getFilters($request);

        return $this->render('menu/index.html.twig', [
            'menus' => $menuRepository->findActiveWithFilters($filters),
            'themes' => $themeRepository->findBy([], ['label' => 'ASC']),
            'diets' => $dietRepository->findBy([], ['label' => 'ASC']),
        ]);
    }

    // appelé en ajax quand on change les filtres
    #[Route('/filter', name: 'app_menu_filter', methods: ['GET'])]
    public function filter(Request $request, MenuRepository $menuRepository): Response
    {
        return $this->render('menu/_cards.html.twig', [
            'menus' => $menuRepository->findActiveWithFilters($this->getFilters($request)),
        ]);
    }

    #[Route('/{slug}', name: 'app_menu_show', methods: ['GET'])]
    public function show(string $slug, MenuRepository $menuRepository): Response
    {
        $menu = $menuRepository->findActiveBySlug($slug);

        if (!$menu) {
            throw $this->createNotFoundException('Menu introuvable.');
        }

        return $this->render('menu/show.html.twig', ['menu' => $menu]);
    }

    // paramètres GET du formulaire de filtres
    private function getFilters(Request $request): array
    {
        return [
            'minPrice' => $request->query->get('minPrice'),
            'maxPrice' => $request->query->get('maxPrice'),
            'theme' => $request->query->get('theme'),
            'diet' => $request->query->get('diet'),
            'minPeople' => $request->query->get('minPeople'),
        ];
    }
}
