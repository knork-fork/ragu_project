<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Repository\Interfaces\UserRepositoryInterface;
use App\Repository\RefreshTokenRepository;
use App\Response\Interfaces\ResponseFactoryInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGenerator;
use Gesdinet\JWTRefreshTokenBundle\Security\Http\Authenticator\RefreshTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private JWTEncoderInterface $JWTEncoder,
        private JWTTokenManagerInterface $JWTTokenManager,
        private ResponseFactoryInterface $responseFactory,
        private int $refreshTokenTtl,
        private RefreshTokenAuthenticator $refreshTokenAuthenticator,
        private UserRepositoryInterface $userRepository,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private ManagerRegistry $managerRegistry
    ) {
    }

    /**
     * @throws Exception\JWTDecodeFailureException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function loginAction(UserInterface $userInterface, Request $request): JsonResponse
    {
        $token = $this->JWTTokenManager->create($userInterface);

        /** @var RefreshToken $refreshToken */
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($userInterface, $this->refreshTokenTtl);
        /** @var RefreshTokenRepository $refreshTokenRepository */
        $refreshTokenRepository = $this->managerRegistry->getRepository(RefreshToken::class);
        $refreshTokenRepository
            ->saveToken($refreshToken, $userInterface)
        ;

        $refreshTokenString = $refreshToken->__toString();

        return $this->processResponse(
            $request,
            $this->responseFactory->createResponse(
                $this->responseFactory->formatArrayForToken($token, $refreshTokenString)
            )
        );
    }

    // todo
    /*public function refreshAction(Request $request): JsonResponse
    {
    }*/

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
