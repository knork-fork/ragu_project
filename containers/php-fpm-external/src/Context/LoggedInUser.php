<?php
declare(strict_types=1);

namespace App\Context;

use App\Context\Interfaces\LoggedInUserInterface;
use App\Entity\User;
use LogicException;

final class LoggedInUser implements LoggedInUserInterface
{
    private ?User $user = null;

    public function getUser(): User
    {
        if ($this->user === null) {
            throw new LogicException('User is not set.');
        }

        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function hasUser(): bool
    {
        return isset($this->user);
    }
}
