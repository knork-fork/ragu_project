<?php
declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\FunctionalTestCase;
use App\Tests\Common\Request;

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

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
