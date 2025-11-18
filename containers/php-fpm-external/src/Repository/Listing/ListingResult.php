<?php
declare(strict_types=1);

namespace App\Repository\Listing;

use App\Repository\Listing\Query\QueryForPaginatedPageResultInterface;

final class ListingResult
{
    private bool $hasNext;
    /** @var mixed[] */
    private array $results;

    public function __construct(
        private QueryForPaginatedPageResultInterface $query,
        private int $currentPage,
        private int $pageSize
    ) {
    }

    /**
     * @return mixed[]
     */
    public function fetchAll(): array
    {
        $this->executeOnce();

        return $this->results;
    }

    public function hasNext(): bool
    {
        $this->executeOnce();

        return $this->hasNext;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    private function executeOnce(): void
    {
        if (!isset($this->results)) {
            $queryBuilder = $this->query;

            $limit = $this->pageSize;
            $offset = ($this->currentPage - 1) * $limit;

            $queryBuilder->setFirstResult($offset);
            // fetch one over the limit to see if there's a next page
            $queryBuilder->setMaxResults($limit + 1);

            // fetch records from the database
            $result = $queryBuilder->getResults();

            // revert to the previous limit
            $queryBuilder->setMaxResults($limit);

            $this->hasNext = false;
            // number of results is above the limit
            if (\count($result) > $limit) {
                // there's a next page
                $this->hasNext = true;

                // remove the last result
                array_pop($result);
            }

            $this->results = $result;
        }
    }

    public function getTotalCount(): int
    {
        return $this->query->hasCount() ? $this->query->getCount() : 0;
    }
}
