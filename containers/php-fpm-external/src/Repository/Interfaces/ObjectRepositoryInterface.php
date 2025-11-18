<?php
declare(strict_types=1);

namespace App\Repository\Interfaces;

use Doctrine\Persistence\ObjectRepository;

/**
 * @template T of object
 *
 * @extends ObjectRepository<T>
 */
interface ObjectRepositoryInterface extends ObjectRepository
{
    /**
     * @param T $object
     *
     * @return T
     */
    public function save(object $object): object;

    /**
     * @param T $object
     */
    public function delete(object $object): void;

    /**
     * @param T $object
     */
    public function persist(object $object): void;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return T
     */
    public function findUniqueBy(array $criteria): object;
}
