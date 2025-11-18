<?php
declare(strict_types=1);

namespace App\Security;

use App\Response\Interfaces\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Class PasswordAuthenticator
 *
 * Security Guard authenticator for password authentication
 */
final class PasswordAuthenticator extends AbstractAuthenticator
{
    private const USERNAME_HEADER = 'X-API-USERNAME';
    private const PASSWORD_HEADER = 'X-API-PASSWORD';

    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function supports(Request $request): bool
    {
        $username = $request->headers->has(self::USERNAME_HEADER) ?
            $request->headers->get(self::USERNAME_HEADER) : null;
        $password = $request->headers->has(self::PASSWORD_HEADER) ?
            $request->headers->get(self::PASSWORD_HEADER) : null;

        return $username
            && $password;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->headers->get(self::USERNAME_HEADER);
        $password = $request->headers->get(self::PASSWORD_HEADER);

        if ($username === null || $password === null) {
            throw new BadCredentialsException('Invalid username or password.');
        }

        /*
         * password check will be done after
         * @see \Symfony\Component\Security\Http\EventListener\CheckCredentialsListener::checkPassport
         */

        return new Passport(new UserBadge($username), new PasswordCredentials($password));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return $this->responseFactory->createErrorResponse(
            'AUTH_FAILED',
            strtr($exception->getMessageKey(), $exception->getMessageData()),
            Response::HTTP_FORBIDDEN
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return null;
    }
}
