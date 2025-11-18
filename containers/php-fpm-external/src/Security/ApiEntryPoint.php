<?php
declare(strict_types=1);

namespace App\Security;

use App\Response\Interfaces\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->responseFactory->createErrorResponse(
            'AUTH_REQUIRED',
            $authException ? $authException->getMessage() : '',
            Response::HTTP_UNAUTHORIZED
        );
    }
}
