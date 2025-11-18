<?php
declare(strict_types=1);

namespace App\Repository\Listing\Query;

use Doctrine\ORM\Query;

final class QueryForPaginatedPageResult implements QueryForPaginatedPageResultInterface
{
    public function __construct(
        private Query $query,
        private int $count
    ) {
    }

    public function setFirstResult(int $offset): void
    {
        $this->query->setFirstResult($offset);
    }

    public function setMaxResults(int $limit): void
    {
        $this->query->setMaxResults($limit);
    }

    public function hasCount(): bool
    {
        return $this->count > 0;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getResults(): array
    {
        /* @var array<mixed> */
        return $this->query->getResult();
    }
}
