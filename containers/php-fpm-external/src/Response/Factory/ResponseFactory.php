<?php
declare(strict_types=1);

namespace App\Response\Factory;

use App\Context\Interfaces\LoggedInUserInterface;
use App\Response\Interfaces\CollectionInterface;
use App\Response\Interfaces\ResponseFactoryInterface;
use App\Response\Item\ResourceItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ResponseFactory implements ResponseFactoryInterface
{
    private const RESPONSE_PROPERTY_DATA = 'data';
    private const RESPONSE_PROPERTY_HAS_NEXT = 'has_next';
    private const RESPONSE_PROPERTY_TOTAL_COUNT = 'total';

    public function __construct(
        private LoggedInUserInterface $loggedInUser
    ) {
    }

    /**
     * @param mixed[] $data
     */
    public function createResponse(array $data): JsonResponse
    {
        return new JsonResponse($this->createResponseArray($data));
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function createResponseArray(array $data): array
    {
        return [
            self::RESPONSE_PROPERTY_DATA => $data,
        ];
    }

    /**
     * @return mixed[]
     */
    public function createResponseCollection(CollectionInterface $collection): array
    {
        $data = [
            self::RESPONSE_PROPERTY_HAS_NEXT => $collection->hasNextPage(),
            self::RESPONSE_PROPERTY_TOTAL_COUNT => $collection->getTotalCount(),
            self::RESPONSE_PROPERTY_DATA => $collection->getItems(),
        ];

        if ($collection->getTotalCount() === 0) {
            unset($data[self::RESPONSE_PROPERTY_TOTAL_COUNT]);
        }

        return $data;
    }

    public function createResponseItem(ResourceItem $resourceItem): array
    {
        return [
            self::RESPONSE_PROPERTY_DATA => $resourceItem->getObject(),
        ];
    }

    public function createErrorResponse(string $errorType, string $errorMessage, int $statusCode, ?string $errorDescription = null): JsonResponse
    {
        $headers = [];

        if ($statusCode === Response::HTTP_UNAUTHORIZED) {
            $headers['WWW-Authenticate'] = 'Bearer';
        }

        return new JsonResponse(
            $this->createErrorArray($errorType, $errorMessage, $errorDescription),
            $statusCode,
            $headers,
        );
    }

    public function createErrorArray(string $errorType, string $errorMessage, ?string $errorDescription = null): array
    {
        $data = [
            'response' => [
                'message' => $errorMessage,
                'detailed' => $errorDescription,
            ],
            'current_user' => $this->loggedInUser->hasUser()
                ? [
                    'username' => $this->loggedInUser->getUser()->getUsername(),
                ]
                : null,
        ];

        if ($errorDescription === null) {
            unset($data['response']['detailed']);
        }

        return [
            'status_message' => $errorType,
            self::RESPONSE_PROPERTY_DATA => $data,
        ];
    }

    public function formatArrayForToken(string $token, string $refreshToken): array
    {
        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
        ];
    }
}
