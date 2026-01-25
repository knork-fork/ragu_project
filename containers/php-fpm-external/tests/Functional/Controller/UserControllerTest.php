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
final class UserControllerTest extends FunctionalTestCase
{
    public function testGetUserInfoReturnsUserInfo(): void
    {
        $response = $this->makeRequestAsUser(
            UserFixture::TEST_USER_USERNAME,
            UserFixture::TEST_USER_PASSWORD,
            Request::METHOD_GET,
            '/user'
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_OK);
        self::assertArrayHasKey('user', $data);
        $userData = (array) $data['user'];
        self::assertArrayHasKey('id', $userData);
        self::assertArrayHasKey('username', $userData);
        self::assertArrayHasKey('email', $userData);
        self::assertArrayHasKey('fullname', $userData);
        self::assertSame(UserFixture::TEST_USER_USERNAME, $userData['username']);
    }
}
