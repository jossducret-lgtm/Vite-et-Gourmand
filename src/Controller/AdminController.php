<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EmployeeType;
use App\Repository\ContactMessageRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\OrderStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        ContactMessageRepository $contactMessageRepository,
        OrderStatsService $orderStatsService,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'employeesCount' => count($userRepository->findEmployees()),
            'pendingMessagesCount' => count($contactMessageRepository->findBy(['isProcessed' => false])),
            'statsPreview' => array_slice($orderStatsService->getOrdersByMenu(), 0, 5),
        ]);
    }

    #[Route('/employes', name: 'app_admin_employees', methods: ['GET'])]
    public function employees(UserRepository $userRepository): Response
    {
        return $this->render('admin/employees.html.twig', [
            'employees' => $userRepository->findEmployees(),
        ]);
    }

    #[Route('/employes/nouveau', name: 'app_admin_employee_new', methods: ['GET', 'POST'])]
    public function newEmployee(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        EmailService $emailService,
    ): Response {
        $employee = new User();
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $employee->setRoles(['ROLE_EMPLOYEE']);
            $employee->setIsActive(true);
            $employee->setFirstName('Employé');
            $employee->setLastName('Vite & Gourmand');
            $employee->setPhone('0600000000');
            $employee->setAddress('12 Quai des Chartrons');
            $employee->setPostalCode('33000');
            $employee->setCity('Bordeaux');
            $employee->setCountry('France');
            $employee->setCreatedAt(new \DateTimeImmutable());
            $employee->setPassword($passwordHasher->hashPassword($employee, $plainPassword));

            $entityManager->persist($employee);
            $entityManager->flush();

            $emailService->sendEmployeeAccountCreated($employee);

            $this->addFlash('success', 'Compte employé créé. Le mot de passe doit être transmis manuellement à l\'employé.');

            return $this->redirectToRoute('app_admin_employees');
        }

        return $this->render('admin/employee_new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/employes/{id}/toggle', name: 'app_admin_employee_toggle', methods: ['POST'])]
    public function toggleEmployee(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $employee = $userRepository->find($id);

        if (!$employee || !in_array('ROLE_EMPLOYEE', $employee->getRoles(), true)) {
            throw $this->createNotFoundException('Employé introuvable.');
        }

        if (!$this->isCsrfTokenValid('toggle_employee_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $employee->setIsActive(!$employee->isActive());
        $employee->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', $employee->isActive() ? 'Compte employé réactivé.' : 'Compte employé désactivé.');

        return $this->redirectToRoute('app_admin_employees');
    }

    #[Route('/statistiques', name: 'app_admin_stats', methods: ['GET'])]
    public function stats(Request $request, OrderStatsService $orderStatsService): Response
    {
        $menuId = $request->query->getInt('menu') ?: null;
        $dateStart = $request->query->get('dateStart');
        $dateEnd = $request->query->get('dateEnd');

        $stats = $orderStatsService->getOrdersByMenu($menuId, $dateStart ?: null, $dateEnd ?: null);

        return $this->render('admin/stats.html.twig', [
            'stats' => $stats,
            'statsSource' => $orderStatsService->getSource(),
            'mongoAvailable' => $orderStatsService->isAvailable(),
            'filters' => [
                'menu' => $menuId,
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
            ],
        ]);
    }

    #[Route('/messages', name: 'app_admin_messages', methods: ['GET'])]
    public function messages(ContactMessageRepository $contactMessageRepository): Response
    {
        return $this->render('admin/messages.html.twig', [
            'messages' => $contactMessageRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/messages/{id}/traiter', name: 'app_admin_message_process', methods: ['POST'])]
    public function processMessage(int $id, Request $request, ContactMessageRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $message = $repository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message introuvable.');
        }

        if (!$this->isCsrfTokenValid('process_message_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $message->setIsProcessed(true);
        $entityManager->flush();

        $this->addFlash('success', 'Message marqué comme traité.');

        return $this->redirectToRoute('app_admin_messages');
    }
}
