<?php
declare(strict_types=1);

namespace App\Response\Interfaces;

interface ResourceItemInterface
{
    public function getObject(): object;
}
