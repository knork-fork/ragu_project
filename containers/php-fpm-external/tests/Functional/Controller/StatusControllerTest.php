<?php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\FunctionalTestCase;
use App\Tests\Common\Request;
use App\Tests\Common\Response;

/**
 * @internal
 */
final class StatusControllerTest extends FunctionalTestCase
{
    public function testStatusReturnsValidResponse(): void
    {
        $response = $this->makeRequest(
            Request::METHOD_GET,
            '/status'
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('php-fpm-external: OK<br>php-fpm-internal: OK', $response->getContent());
    }
}
