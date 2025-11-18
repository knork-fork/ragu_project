<?php
declare(strict_types=1);

namespace App\Response\Interfaces;

use IteratorAggregate;

/**
 * @template TKey
 *
 * @template-covariant TValue
 *
 * @template-extends IteratorAggregate<TKey, TValue>
 */
interface CollectionInterface extends IteratorAggregate
{
    /**
     * @return mixed[]
     */
    public function getItems(): array;

    public function hasNextPage(): bool;

    public function getTotalCount(): int;
}
