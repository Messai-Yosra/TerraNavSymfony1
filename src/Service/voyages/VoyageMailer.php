<?php
// src/Service/VoyageMailer.php

namespace App\Service\voyages;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Voyage;

class VoyageMailer
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendVoyageNotification(string $to, Voyage $voyage): void
    {
        $subject = 'Nouveau voyage: ' . $voyage->getTitre();

        $textContent = sprintf(
            "Un nouveau voyage a été ajouté:\n\n".
            "Titre: %s\n".
            "Destination: %s\n".
            "Départ: %s\n".
            "Retour: %s\n".
            "Prix: %.2f €\n".
            "Places: %d\n\n".
            "Description:\n%s",
            $voyage->getTitre(),
            $voyage->getDestination(),
            $voyage->getDateDepart()->format('d/m/Y H:i'),
            $voyage->getDateRetour() ? $voyage->getDateRetour()->format('d/m/Y H:i') : 'Non spécifié',
            $voyage->getPrix(),
            $voyage->getNbPlacesD(),
            $voyage->getDescription()
        );

        try {
            $email = (new Email())
                ->from('rayen.khlifi@esprit.tn') // Utilisez un domaine que vous contrôlez
                ->to($to)
                ->subject($subject)
                ->text($textContent);

            $this->mailer->send($email);

        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }
}