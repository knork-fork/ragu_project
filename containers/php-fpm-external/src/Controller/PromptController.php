<?php
declare(strict_types=1);

namespace App\Controller;

use App\Context\Interfaces\LoggedInUserInterface;
use App\Response\Interfaces\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PromptController
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        // private LoggedInUserInterface $loggedInUser,
    ) {
    }

    /**
     * Send a prompt to be processed in the background job queue
     * 
     * If chat_id is provided prompt is part of the existing chat, otherwise a new chat is created
     */
    #[Route('/prompt-send', name: 'prompt-send', methods: ['POST'])]
    public function sendPrompt(Request $request): JsonResponse
    {
        // $currentUserId = $this->loggedInUser->getUser()->getId();

        /** @var array<mixed> $data */
        $data = json_decode($request->getContent(), true);
        $chatId = $data['chat_id'] ?? null;

        if ($chatId === null) {
            // to-do: create new chat entity

            // temporarily just return random chat id between 1 and 1000000
            $chatId = random_int(1, 1000000);
        }

        // Hardcoded for now, replace with job queuing system later
        $promptResponse = [
            'id' => 123,
            'prompt_job_id' => 123,
            'chat_id' => $chatId,
        ];

        return $this->responseFactory->createResponse(
            ['prompt' => $promptResponse],
            Response::HTTP_CREATED,
        );
    }
}
