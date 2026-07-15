<?php

namespace App\Service;

use App\Entity\ContactMessage;
use App\Entity\MenuOrder;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $companyEmail = 'contact@vitegourmand.fr',
    ) {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->companyEmail)
            ->to($user->getEmail())
            ->subject('Bienvenue chez Vite & Gourmand')
            ->htmlTemplate('email/welcome.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    public function sendOrderConfirmation(MenuOrder $order): void
    {
        $email = (new TemplatedEmail())
            ->from($this->companyEmail)
            ->to($order->getUser()->getEmail())
            ->subject('Confirmation de votre commande ' . $order->getOrderNumber())
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }

    public function sendOrderCompleted(MenuOrder $order): void
    {
        $email = (new TemplatedEmail())
            ->from($this->companyEmail)
            ->to($order->getUser()->getEmail())
            ->subject('Votre commande est terminée')
            ->htmlTemplate('email/order_completed.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }

    public function sendMaterialReturnWarning(MenuOrder $order): void
    {
        $email = (new TemplatedEmail())
            ->from($this->companyEmail)
            ->to($order->getUser()->getEmail())
            ->subject('Retour de matériel')
            ->htmlTemplate('email/material_return.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }

    public function sendEmployeeAccountCreated(User $employee): void
    {
        $email = (new TemplatedEmail())
            ->from($this->companyEmail)
            ->to($employee->getEmail())
            ->subject('Création de votre compte employé')
            ->htmlTemplate('email/employee_created.html.twig')
            ->context(['employee' => $employee]);

        $this->mailer->send($email);
    }

    public function sendContactMessage(ContactMessage $message): void
    {
        $email = (new TemplatedEmail())
            ->from($message->getEmail())
            ->to($this->companyEmail)
            ->subject('Demande de contact : ' . $message->getTitle())
            ->htmlTemplate('email/contact.html.twig')
            ->context(['message' => $message]);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(User $user, string $resetToken): void
    {
        $resetUrl = $this->urlGenerator->generate('app_reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from($this->companyEmail)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('email/reset_password.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }
}