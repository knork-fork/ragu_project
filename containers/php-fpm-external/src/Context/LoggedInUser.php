<?php
declare(strict_types=1);

namespace App\Context;

use App\Context\Interfaces\LoggedInUserInterface;
use App\Entity\User;

final class LoggedInUser implements LoggedInUserInterface
{
    private User $user;

    public function getUser(): User
    {
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
