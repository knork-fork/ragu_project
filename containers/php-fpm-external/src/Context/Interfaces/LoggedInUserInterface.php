<?php
declare(strict_types=1);

namespace App\Context\Interfaces;

use App\Entity\User;

/**
 * interface representing currently logged in user
 */
interface LoggedInUserInterface
{
    public function setUser(User $user): self;

    public function getUser(): User;

    public function hasUser(): bool;
}
