<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Repository\Interfaces\UserRepositoryInterface;
use App\Repository\Listing\ListingResult;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends AbstractEntityRepository<User>
 */
final class UserRepository extends AbstractEntityRepository implements UserRepositoryInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getUserByUsername(?string $username): ?UserInterface
    {
        $config = [
            'LOWER(u.username)' => $username ? strtolower($username) : null,
        ];

        $result = null;
        foreach ($config as $column => $value) {
            if ($value === null) {
                continue;
            }

            $result = $this->createQueryBuilder('u')
                ->where($column . ' = :value')
                ->setParameter('value', $value)
                ->getQuery()
                ->getOneOrNullResult()
            ;

            if ($result) {
                break;
            }
        }

        /* @var User|null */
        return $result;
    }

    public function paginateGetAll(
        int $page,
        int $pageSize,
    ): ListingResult {
        $queryBuilder = $this->createQueryBuilder('u');
        $this->withActiveUnexpiredSubscriptions($queryBuilder);
        $queryBuilder->groupBy('u.id');

        return $this->getPaginatedResultsForQueryBuilder($queryBuilder, $page, $pageSize, true);
    }

    /**
     * @param string $username
     *
     * @throws NonUniqueResultException
     */
    public function loadUserByUsername($username): ?UserInterface
    {
        $username = trim($username);

        return $this->getUserByUsername($username);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }
}
