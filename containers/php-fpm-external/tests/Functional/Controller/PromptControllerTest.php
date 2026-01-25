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
final class PromptControllerTest extends FunctionalTestCase
{
    public function testSendPromptWithNoChatIdCreatesNewChat(): void
    {
        $response = $this->makeRequestAsUser(
            UserFixture::TEST_USER_USERNAME,
            UserFixture::TEST_USER_PASSWORD,
            Request::METHOD_POST,
            '/prompt-send',
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_CREATED);
        self::assertArrayHasKey('prompt', $data);
        $promptData = (array) $data['prompt'];
        self::assertArrayHasKey('id', $promptData);
        self::assertArrayHasKey('prompt_job_id', $promptData);
        self::assertArrayHasKey('chat_id', $promptData);
        self::assertIsInt($promptData['chat_id']);
    }

    public function testSendPromptWithChatIdReturnsSameChatId(): void
    {
        // to-do: get from fixture
        $chatId = 1234;

        $response = $this->makeRequestAsUser(
            UserFixture::TEST_USER_USERNAME,
            UserFixture::TEST_USER_PASSWORD,
            Request::METHOD_POST,
            '/prompt-send',
            ['chat_id' => $chatId],
        );

        $data = $this->decodeJsonDataFromResponse($response, Response::HTTP_CREATED);
        self::assertArrayHasKey('prompt', $data);
        $promptData = (array) $data['prompt'];
        self::assertArrayHasKey('id', $promptData);
        self::assertArrayHasKey('prompt_job_id', $promptData);
        self::assertArrayHasKey('chat_id', $promptData);
        self::assertSame($chatId, $promptData['chat_id']);
    }
}
