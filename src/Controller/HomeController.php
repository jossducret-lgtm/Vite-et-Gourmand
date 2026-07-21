<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\ReviewRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        ReviewRepository $reviewRepository,
        MenuRepository $menuRepository,
        ThemeRepository $themeRepository,
    ): Response {
        // avis validés pour la page d'accueil (max 6)
        $reviews = $reviewRepository->findBy(
            ['status' => 'VALIDATED'],
            ['createdAt' => 'DESC'],
            6
        );

        return $this->render('home/index.html.twig', [
            'reviews' => $reviews,
            'featuredMenus' => $menuRepository->findLatestActive(3),
            'themes' => $themeRepository->findBy([], ['label' => 'ASC']),
        ]);
    }
}
