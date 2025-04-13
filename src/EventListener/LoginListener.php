<?php
// src/EventListener/LoginListener.php

namespace App\EventListener;

use App\Entity\Utilisateur;
use App\Service\utilisateurs\LoginHistoryLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginListener implements EventSubscriberInterface
{
    private LoginHistoryLogger $loginLogger;

    public function __construct(LoginHistoryLogger $loginLogger)
    {
        $this->loginLogger = $loginLogger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        if ($user instanceof Utilisateur) {
            $this->loginLogger->logLogin($user);
        }
    }
}