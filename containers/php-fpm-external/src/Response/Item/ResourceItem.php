<?php
declare(strict_types=1);

namespace App\Response\Item;

use App\Response\Interfaces\ResourceItemInterface;

final class ResourceItem implements ResourceItemInterface
{
    public function __construct(
        private object $object
    ) {
    }

    public function getObject(): object
    {
        return $this->object;
    }
}
