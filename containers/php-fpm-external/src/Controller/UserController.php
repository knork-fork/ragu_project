<?php
declare(strict_types=1);

namespace App\Controller;

use App\Context\Interfaces\LoggedInUserInterface;
use App\Response\Interfaces\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LoggedInUserInterface $loggedInUser,
    ) {
    }

    #[Route('/user', name: 'user', methods: ['GET'])]
    public function getUserInfo(): JsonResponse
    {
        $currentUser = $this->loggedInUser->getUser();

        $user = [
            'id' => $currentUser->getId(),
            'username' => $currentUser->getUsername(),
            // todo: mail and fullname are not properly implemented yet
            'email' => $currentUser->getUsername() . '@mail.com',
            'fullname' => $currentUser->getUsername(),
        ];

        return $this->responseFactory->createResponse(
            ['user' => $user],
            Response::HTTP_OK,
        );
    }
}
