<?php

namespace App\Service;

use App\Entity\ContactMessage;
use App\Entity\MenuOrder;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $companyEmail = 'contact@vitegourmand.fr'
    ) {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new Email())
            ->from($this->companyEmail)
            ->to($user->getEmail())
            ->subject('Bienvenue chez Vite & Gourmand')
            ->text(sprintf("Bonjour %s,\n\nVotre compte Vite & Gourmand a bien été créé.\n\nÀ bientôt.", $user->getFirstName()));

        $this->mailer->send($email);
    }

    public function sendOrderConfirmation(MenuOrder $order): void
    {
        $email = (new Email())
            ->from($this->companyEmail)
            ->to($order->getUser()->getEmail())
            ->subject('Confirmation de votre commande ' . $order->getOrderNumber())
            ->text(sprintf(
                "Bonjour %s,\n\nVotre commande %s pour le menu %s a bien été enregistrée.\nTotal : %s €.\n\nMerci pour votre confiance.",
                $order->getUser()->getFirstName(),
                $order->getOrderNumber(),
                $order->getMenu()->getTitle(),
                $order->getTotalPrice()
            ));

        $this->mailer->send($email);
    }

    public function sendOrderCompleted(MenuOrder $order): void
    {
        $email = (new Email())
            ->from($this->companyEmail)
            ->to($order->getUser()->getEmail())
            ->subject('Votre commande est terminée')
            ->text("Votre commande est terminée. Vous pouvez maintenant vous connecter à votre compte pour donner votre avis.");

        $this->mailer->send($email);
    }

    public function sendMaterialReturnWarning(MenuOrder $order): void
    {
        $email = (new Email())
            ->from($this->companyEmail)
            ->to($order->getUser()->getEmail())
            ->subject('Retour de matériel')
            ->text("Du matériel vous a été prêté. Merci de prendre contact avec Vite & Gourmand pour sa restitution. Passé 10 jours ouvrés, des frais de 600 euros peuvent être appliqués conformément aux CGV.");

        $this->mailer->send($email);
    }

    public function sendEmployeeAccountCreated(User $employee): void
    {
        $email = (new Email())
            ->from($this->companyEmail)
            ->to($employee->getEmail())
            ->subject('Création de votre compte employé')
            ->text("Un compte employé a été créé pour vous. Le mot de passe n'est pas communiqué par email. Merci de vous rapprocher de l'administrateur.");

        $this->mailer->send($email);
    }

    public function sendContactMessage(ContactMessage $message): void
    {
        $email = (new Email())
            ->from($message->getEmail())
            ->to($this->companyEmail)
            ->subject('Demande de contact : ' . $message->getTitle())
            ->text($message->getMessage());

        $this->mailer->send($email);
    }
}