<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\MenuImage;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/employe/menus')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeMenuController extends AbstractController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly FileUploader $fileUploader,
    ) {
    }

    #[Route('', name: 'app_employee_menu_index', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): Response
    {
        return $this->render('employee_menu/index.html.twig', [
            'menus' => $menuRepository->findAll(),
        ]);
    }

    #[Route('/nouveau', name: 'app_employee_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $menu = new Menu();
        $menu->setIsActive(true);
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($error = $this->validateMenu($menu)) {
                $this->addFlash('danger', $error);

                return $this->render('employee_menu/new.html.twig', [
                    'menu' => $menu,
                    'form' => $form,
                ]);
            }

            if ($menu->getStockQuantity() < $menu->getMinPeople()) {
                $this->addFlash('danger', 'Le stock doit être au moins égal au minimum de personnes.');

                return $this->render('employee_menu/new.html.twig', [
                    'menu' => $menu,
                    'form' => $form,
                ]);
            }

            $this->prepareMenu($menu);
            $this->handleGalleryUploads($form, $menu);
            $entityManager->persist($menu);
            $entityManager->flush();

            $this->addFlash('success', 'Menu créé avec succès.');

            return $this->redirectToRoute('app_employee_menu_index');
        }

        return $this->render('employee_menu/new.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_menu_show', methods: ['GET'])]
    public function show(Menu $menu): Response
    {
        return $this->render('employee_menu/show.html.twig', ['menu' => $menu]);
    }

    #[Route('/{id}/modifier', name: 'app_employee_menu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Menu $menu, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($error = $this->validateMenu($menu)) {
                $this->addFlash('danger', $error);

                return $this->render('employee_menu/edit.html.twig', [
                    'menu' => $menu,
                    'form' => $form,
                ]);
            }

            if ($menu->getStockQuantity() < $menu->getMinPeople()) {
                $this->addFlash('danger', 'Le stock doit être au moins égal au minimum de personnes.');

                return $this->render('employee_menu/edit.html.twig', [
                    'menu' => $menu,
                    'form' => $form,
                ]);
            }

            $this->prepareMenu($menu, false);
            $this->handleGalleryUploads($form, $menu);
            $menu->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Menu mis à jour.');

            return $this->redirectToRoute('app_employee_menu_index');
        }

        return $this->render('employee_menu/edit.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_employee_menu_delete', methods: ['POST'])]
    public function delete(Request $request, Menu $menu, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $menu->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($menu);
            $entityManager->flush();
            $this->addFlash('success', 'Menu supprimé.');
        }

        return $this->redirectToRoute('app_employee_menu_index');
    }

    #[Route('/{id}/image/{imageId}/supprimer', name: 'app_employee_menu_image_delete', methods: ['POST'])]
    public function deleteImage(int $id, int $imageId, Request $request, MenuRepository $menuRepository, EntityManagerInterface $entityManager): Response
    {
        $menu = $menuRepository->find($id);

        if (!$menu) {
            throw $this->createNotFoundException('Menu introuvable.');
        }

        if (!$this->isCsrfTokenValid('delete_image_' . $imageId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        foreach ($menu->getImages() as $image) {
            if ($image->getId() === $imageId) {
                $menu->removeImage($image);
                $entityManager->remove($image);
                break;
            }
        }

        $entityManager->flush();
        $this->addFlash('success', 'Image supprimée.');

        return $this->redirectToRoute('app_employee_menu_edit', ['id' => $id]);
    }

    private function prepareMenu(Menu $menu, bool $isNew = true): void
    {
        if (!$menu->getSlug()) {
            $menu->setSlug(strtolower($this->slugger->slug($menu->getTitle())->toString()));
        }

        if ($isNew) {
            $menu->setCreatedAt(new \DateTimeImmutable());
        }
    }

    private function handleGalleryUploads(FormInterface $form, Menu $menu): void
    {
        if (!$form->has('galleryFiles')) {
            return;
        }

        /** @var UploadedFile[] $files */
        $files = $form->get('galleryFiles')->getData() ?? [];

        $position = count($menu->getImages());

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $menuImage = new MenuImage();
            $menuImage->setFilename($this->fileUploader->upload($file, 'menu'));
            $menuImage->setAltText($menu->getTitle());
            $menuImage->setPosition(++$position);
            $menuImage->setCreatedAt(new \DateTimeImmutable());
            $menu->addImage($menuImage);
        }
    }

    // vérifie qu'il y a bien entrée + plat + dessert
    private function validateMenu(Menu $menu): ?string
    {
        if ($menu->getDish()->isEmpty()) {
            return 'Sélectionnez au moins un plat pour composer le menu.';
        }

        $types = [];
        foreach ($menu->getDish() as $dish) {
            $types[$dish->getType()] = true;
        }

        foreach (['ENTREE', 'PLAT', 'DESSERT'] as $required) {
            if (!isset($types[$required])) {
                return 'Un menu complet doit contenir au moins une entrée, un plat et un dessert.';
            }
        }

        return null;
    }
}
