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
        $internalResponse = file_get_contents('http://webserver:8081/status');

        $response = \sprintf(
            'php-fpm-external: OK<br>php-fpm-internal: %s',
            $internalResponse
        );

        return new Response($response, Response::HTTP_OK);
    }
}
