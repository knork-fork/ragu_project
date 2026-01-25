<?php
declare(strict_types=1);

namespace App\Tests\Common;

use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class FunctionalTestCase extends TestCase
{
    public const BASE_URL = 'http://ragu-webserver/api';

    /** @var array<string, string> */
    private static array $tokensCache = [];

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @param mixed[] $params
     * @param mixed[] $headers
     */
    protected function makeRequest(string $method, string $uri, array $params = [], array $headers = []): Response
    {
        if (!empty(getenv('XDEBUG_SESSION_START'))) {
            $uri .= !str_contains($uri, '?') ? '?' : '&';
            $uri .= 'XDEBUG_SESSION_START=' . getenv('XDEBUG_SESSION_START');
        }

        if ($method === Request::METHOD_GET && $params) {
            $uri .= !str_contains($uri, '?') ? '?' : '&';
            $uri .= http_build_query($params);
            $params = [];
        }

        // Set env override header
        $headers[] = 'X-App-Env: test';

        if (!Request::isHeaderSet('Content-Type', $headers)) {
            $headers[] = 'Content-Type: application/json';
        }
        if (!Request::isHeaderSet('Accept', $headers)) {
            $headers[] = 'Accept: application/json';
        }

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }
        // @phpstan-ignore-next-line
        curl_setopt_array($ch, [
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_URL => self::BASE_URL . $uri,
            \CURLOPT_SSL_VERIFYHOST => 0,
            \CURLOPT_SSL_VERIFYPEER => 0,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_POSTFIELDS => json_encode($params),
            \CURLOPT_HTTPHEADER => $headers,
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new RuntimeException(curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $responseHeaders = [];

        return new Response(
            (string) $result,
            $statusCode,
            $responseHeaders
        );
    }

    /**
     * @param mixed[] $params
     * @param mixed[] $headers
     */
    protected function makeRequestAsUser(string $username, string $password, string $method, string $uri, array $params = [], array $headers = []): Response
    {
        $accessToken = $this->login($username, $password);

        $requestHeaders = array_merge($headers, ['Authorization: ' . \sprintf('Bearer %s', $accessToken)]);

        return $this->makeRequest($method, $uri, $params, $requestHeaders);
    }

    /**
     * @return mixed[]
     */
    protected function decodeJsonFromResponse(Response $response, ?int $expectedStatusCode = Response::HTTP_OK): array
    {
        self::assertSame($expectedStatusCode, $response->getStatusCode(), 'Response status code invalid.');
        self::assertJson($response->getContent());

        $responseArray = json_decode($response->getContent(), true);
        self::assertIsArray($responseArray);

        return $responseArray;
    }

    /**
     * @return mixed[]
     */
    protected function decodeJsonDataFromResponse(Response $response, ?int $expectedStatusCode = Response::HTTP_OK): array
    {
        $json = $this->decodeJsonFromResponse($response, $expectedStatusCode);

        self::assertIsArray($json['data']);

        return $json['data'];
    }

    private function login(string $username, string $password): string
    {
        $cacheKey = \sprintf(
            '%s/%s',
            $username,
            $password
        );

        if (!isset(self::$tokensCache[$cacheKey])) {
            $response = $this->makeRequest(
                Request::METHOD_POST,
                '/auth/login',
                [],
                [
                    'X-API-USERNAME: ' . $username,
                    'X-API-PASSWORD: ' . $password,
                ]
            );

            $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_OK);
            self::assertArrayHasKey('token', $data);
            self::assertIsString($data['token']);

            self::$tokensCache[$cacheKey] = $data['token'];
        }

        return self::$tokensCache[$cacheKey];
    }
}
