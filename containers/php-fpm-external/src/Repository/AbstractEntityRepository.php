<?php
declare(strict_types=1);

namespace App\Repository;

use App\Repository\Listing\ListingResult;
use App\Repository\Listing\Query\QueryBuilderForPaginatedPageResult;
use App\Repository\Listing\Query\QueryForPaginatedPageResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;

/**
 * @template T of object
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractEntityRepository extends ServiceEntityRepository
{
    /**
     * @param T $object
     */
    public function persist(object $object): void
    {
        $this->getEntityManager()->persist($object);
    }

    /**
     * @param T $object
     *
     * @return T
     */
    public function save(object $object): object
    {
        $this->getEntityManager()->persist($object);
        $this->getEntityManager()->flush();

        return $object;
    }

    /**
     * @param T $object
     */
    public function delete(object $object): void
    {
        $this->getEntityManager()->remove($object);
        $this->getEntityManager()->flush();
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return T
     *
     * @throws Exception
     */
    public function findUniqueBy(array $criteria): object
    {
        $objects = $this->findBy($criteria);

        return match (\count($objects)) {
            0 => throw new Exception(static::class . ': 0 results found on given criteria.'),
            1 => $objects[0],
            default => throw new Exception(static::class . ': multiple results found on given criteria.'),
        };
    }

    protected function getConnection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    protected function getTableName(): string
    {
        return $this->getClassMetadata()->getTableName();
    }

    protected function getPaginatedResultsForQueryBuilder(QueryBuilder $queryBuilder, int $page, int $pageSize, bool $autoCount = false): ListingResult
    {
        return new ListingResult(new QueryBuilderForPaginatedPageResult($queryBuilder, $autoCount), $page, $pageSize);
    }

    protected function getPaginatedResultsForQuery(Query $query, int $page, int $pageSize, int $count = 0): ListingResult
    {
        return new ListingResult(new QueryForPaginatedPageResult($query, $count), $page, $pageSize);
    }
}
