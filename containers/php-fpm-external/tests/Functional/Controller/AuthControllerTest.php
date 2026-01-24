<?php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DataFixtures\UserFixture;
use App\Tests\Common\FunctionalTestCase;
use App\Tests\Common\Request;
use App\Tests\Common\Response;

/**
 * @internal
 */
final class AuthControllerTest extends FunctionalTestCase
{
    public function testLoginActionWithMissingHeadersReturns401(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/login'
        );

        $data = $this->decodeJsonFromResponse($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('Not privileged to request the resource.', $data['message']);
    }

    public function testLoginActionWithWrongPasswordReturns403(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/login',
            [],
            [
                'X-API-USERNAME: ' . UserFixture::TEST_USER_USERNAME,
                'X-API-PASSWORD: wrong-password',
            ]
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_FORBIDDEN);
        self::assertArrayHasKey('response', $data);
        $responseData = (array) $data['response'];
        self::assertArrayHasKey('message', $responseData);
        self::assertSame('Invalid credentials.', $responseData['message']);
    }

    public function testLoginActionWithInactiveUserReturns403(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/login',
            [],
            [
                'X-API-USERNAME: ' . UserFixture::INACTIVE_USER_USERNAME,
                'X-API-PASSWORD: ' . UserFixture::TEST_USER_PASSWORD,
            ]
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_FORBIDDEN);
        self::assertArrayHasKey('response', $data);
        $responseData = (array) $data['response'];
        self::assertArrayHasKey('message', $responseData);
        self::assertSame('Account is inactive.', $responseData['message']);
    }

    public function testLoginActionWithCorrectCredentialsReturnsToken(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/login',
            [],
            [
                'X-API-USERNAME: ' . UserFixture::TEST_USER_USERNAME,
                'X-API-PASSWORD: ' . UserFixture::TEST_USER_PASSWORD,
            ]
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_OK);
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('refresh_token', $data);
    }

    public function testRefreshActionWithMissingRefreshTokenReturns401(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/refresh',
            [],
            []
        );

        $data = $this->decodeJsonFromResponse($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('Missing JWT Refresh Token', $data['message']);
    }

    public function testRefreshActionWithInvalidRefreshTokenReturns401(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/refresh',
            [],
            [
                'X-API-REFRESH-TOKEN: invalid-refresh-token',
            ]
        );

        $data = $this->decodeJsonFromResponse($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('JWT Refresh Token Not Found', $data['message']);
    }

    public function testRefreshActionWithValidRefreshTokenReturnsNewToken(): void
    {
        $refreshToken = $this->createRefreshToken();

        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/refresh',
            [],
            [
                'X-API-REFRESH-TOKEN: ' . $refreshToken,
            ]
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_OK);
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('refresh_token', $data);
        self::assertSame($refreshToken, $data['refresh_token']);
    }

    private function createRefreshToken(): string
    {
        $response = $this->makeRequest(
            Request::METHOD_POST,
            '/auth/login',
            [],
            [
                'X-API-USERNAME: ' . UserFixture::TEST_USER_USERNAME,
                'X-API-PASSWORD: ' . UserFixture::TEST_USER_PASSWORD,
            ]
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_OK);
        self::assertArrayHasKey('refresh_token', $data);
        self::assertIsString($data['refresh_token']);

        return $data['refresh_token'];
    }
}
