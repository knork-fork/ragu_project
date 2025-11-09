<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatusController
{
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): Response
    {
        return new Response('OK', Response::HTTP_OK);
    }
}
