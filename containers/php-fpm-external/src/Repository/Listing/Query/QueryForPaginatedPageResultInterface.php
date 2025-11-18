<?php
declare(strict_types=1);

namespace App\Repository\Listing\Query;

interface QueryForPaginatedPageResultInterface
{
    public function setFirstResult(int $offset): void;

    public function setMaxResults(int $limit): void;

    /**
     * @return mixed[]
     */
    public function getResults(): array;

    public function hasCount(): bool;

    public function getCount(): int;
}
