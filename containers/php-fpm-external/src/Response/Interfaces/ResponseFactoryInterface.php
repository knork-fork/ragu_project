<?php
declare(strict_types=1);

namespace App\Response\Interfaces;

use App\Response\Item\ResourceItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

interface ResponseFactoryInterface
{
    /**
     * @param mixed[] $data
     */
    public function createResponse(array $data, int $statusCode = Response::HTTP_OK): JsonResponse;

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function createResponseArray(array $data): array;

    /**
     * @template TKey
     * @template TValue
     *
     * @param CollectionInterface<TKey, TValue> $collection
     *
     * @return array{
     *     has_next: bool,
     *     total?: int,
     *     data: array<TValue>
     * }
     */
    public function createResponseCollection(CollectionInterface $collection): array;

    /**
     * @return mixed[]
     */
    public function createResponseItem(ResourceItem $resourceItem): array;

    /**
     * creates API failure response object
     */
    public function createErrorResponse(string $errorType, string $errorMessage, int $statusCode, ?string $errorDescription = null): JsonResponse;

    /**
     * creates structured API failure response array
     *
     * @return mixed[]
     */
    public function createErrorArray(string $errorType, string $errorMessage, ?string $errorDescription = null): array;

    /**
     * @return string[]
     */
    public function formatArrayForToken(string $token, string $refreshToken): array;
}
