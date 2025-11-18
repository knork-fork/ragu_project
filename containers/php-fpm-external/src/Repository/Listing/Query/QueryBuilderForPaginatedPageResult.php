<?php
declare(strict_types=1);

namespace App\Repository\Listing\Query;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

final class QueryBuilderForPaginatedPageResult implements QueryForPaginatedPageResultInterface
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        private bool $autoCount
    ) {
    }

    public function setFirstResult(int $offset): void
    {
        $this->queryBuilder->setFirstResult($offset);
    }

    public function setMaxResults(int $limit): void
    {
        $this->queryBuilder->setMaxResults($limit);
    }

    public function hasCount(): bool
    {
        return $this->autoCount;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getCount(): int
    {
        if ($this->autoCount) {
            /*
             * For auto count to work properly we need to only count rows from the root entity.
             * In order to accomplish that we do the following:
             *
             * - Count distinct rows of the root entity because joining with other entities produces multiple rows,
             *  which would give an incorrect count
             * - Reset orderBy because counting with ORDER BY clause produces an SQL error:
             * "column <column_name> must appear in the GROUP BY clause or be used in an aggregate function"
             * - Reset groupBy because counting with GROUP BY produces multiple rows (count for each group)
            */
            $qb = clone $this->queryBuilder;
            $rootAlias = $qb->getRootAliases()[0];

            $qb->select("count(DISTINCT {$rootAlias})")
                ->resetDQLParts(['orderBy', 'groupBy'])
                // we also must reset pagination (OFFSET AND LIMIT) before counting all results
                ->setFirstResult(null)
                ->setMaxResults(null)
            ;

            try {
                /* @var int */
                return $qb->getQuery()->getSingleScalarResult();
            } catch (NoResultException $e) {
                return 0;
            }
        }

        return 0;
    }

    public function getResults(): array
    {
        /* @var array<mixed> */
        return $this->queryBuilder->getQuery()->getResult();
    }
}
