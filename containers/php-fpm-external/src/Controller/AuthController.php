<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use App\Response\Interfaces\ResponseFactoryInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGenerator;
use Gesdinet\JWTRefreshTokenBundle\Security\Http\Authenticator\RefreshTokenAuthenticator;
use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthController extends AbstractController
{
    private const REFRESH_TOKEN_HEADER = 'X-API-REFRESH-TOKEN';

    public function __construct(
        private JWTTokenManagerInterface $JWTTokenManager,
        private ResponseFactoryInterface $responseFactory,
        private int $refreshTokenTtl,
        private RefreshTokenAuthenticator $refreshTokenAuthenticator,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private ManagerRegistry $managerRegistry
    ) {
    }

    public function loginAction(UserInterface $userInterface, Request $request): JsonResponse
    {
        $token = $this->JWTTokenManager->create($userInterface);

        /** @var RefreshToken $refreshToken */
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($userInterface, $this->refreshTokenTtl);
        /** @var RefreshTokenRepository $refreshTokenRepository */
        $refreshTokenRepository = $this->managerRegistry->getRepository(RefreshToken::class);
        if (!$userInterface instanceof \App\Entity\User) {
            throw new InvalidArgumentException('User must be an instance of App\Entity\User');
        }
        $refreshTokenRepository->saveToken($refreshToken, $userInterface);

        $refreshTokenString = $refreshToken->__toString();

        return $this->processResponse(
            $request,
            $this->responseFactory->createResponse(
                $this->responseFactory->formatArrayForToken($token, $refreshTokenString)
            )
        );
    }

    public function refreshAction(Request $request): JsonResponse
    {
        $refreshToken = $request->headers->has(self::REFRESH_TOKEN_HEADER) ?
            $request->headers->get(self::REFRESH_TOKEN_HEADER) : null;

        $request->attributes->set('refresh_token', $refreshToken);

        $firewallName = 'auth';
        $passport = $this->refreshTokenAuthenticator->authenticate($request);
        $token = $this->refreshTokenAuthenticator->createToken($passport, $firewallName);

        $response = $this->refreshTokenAuthenticator->onAuthenticationSuccess($request, $token, $firewallName);
        if ($response === null) {
            throw new InvalidArgumentException('Response from onAuthenticationSuccess cannot be null');
        }
        $data = json_decode((string) $response->getContent(), true);
        if (!\is_array($data)) {
            throw new InvalidArgumentException('Response content is not a valid JSON array');
        }

        $tokenString = $data['token'] ?? null;
        $refreshTokenString = $data['refresh_token'] ?? null;
        if (!\is_string($tokenString) || !\is_string($refreshTokenString)) {
            throw new InvalidArgumentException('Token or refresh token is missing or not a string');
        }

        return $this->processResponse(
            $request,
            $this->responseFactory->createResponse(
                $this->responseFactory->formatArrayForToken($tokenString, $refreshTokenString)
            )
        );
    }

    private function processResponse(Request $request, JsonResponse $response): JsonResponse
    {
        $responseContent = $response->getContent();
        /** @var array<string, array<string, mixed>> */
        $json = $responseContent !== false ? json_decode($responseContent, true) : null;

        if ($response->getStatusCode() !== Response::HTTP_OK || !isset($json['data']['token']) || !\is_string($json['data']['token'])) {
            // token not created successfully
            return $response;
        }

        // encode content
        $jsonString = json_encode($json);
        $response->setContent($jsonString !== false ? $jsonString : null);

        return $response;
    }
}
