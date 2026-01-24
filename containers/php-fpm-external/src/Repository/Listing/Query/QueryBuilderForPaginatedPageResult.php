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
            $qb = clone $this->queryBuilder;
            $rootAlias = $qb->getRootAliases()[0];
            $qb->select("count(DISTINCT {$rootAlias})")
                ->resetDQLParts(['orderBy', 'groupBy'])
                ->setFirstResult(null)
                ->setMaxResults(null)
            ;
            try {
                $result = $qb->getQuery()->getSingleScalarResult();

                return is_numeric($result) ? (int) $result : 0;
            } catch (NoResultException $e) {
                return 0;
            }
        }

        return 0;
    }

    /**
     * @return array<mixed>
     */
    public function getResults(): array
    {
        $result = $this->queryBuilder->getQuery()->getResult();

        return \is_array($result) ? $result : [];
    }
}
