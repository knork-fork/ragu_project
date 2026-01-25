<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Context\Interfaces\LoggedInUserInterface;
use App\Entity\User;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SetLoggedInUserListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private LoggedInUserInterface $loggedInUser
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        if ($user instanceof User) {
            $this->loggedInUser->setUser($user);
        }
    }
}
