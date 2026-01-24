<?php
declare(strict_types=1);

namespace App\Controller;

use App\Response\Interfaces\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    #[Route('/user', name: 'user', methods: ['GET'])]
    public function getUserInfo(): JsonResponse
    {
        $user = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'testuser@mail.com',
            'fullname' => 'Test User',
        ];

        return $this->responseFactory->createResponse(
            ['user' => $user],
            Response::HTTP_OK,
        );
    }
}
