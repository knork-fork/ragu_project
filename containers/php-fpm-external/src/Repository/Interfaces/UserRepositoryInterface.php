<?php
declare(strict_types=1);

namespace App\Repository\Interfaces;

use App\Entity\User;
use App\Repository\Listing\ListingResult;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ObjectRepositoryInterface<User>
 */
interface UserRepositoryInterface extends ObjectRepositoryInterface
{
    public function getUserByUsername(?string $username): ?UserInterface;

    public function loadUserByIdentifier(string $identifier): ?UserInterface;

    public function paginateGetAll(
        int $page,
        int $pageSize,
    ): ListingResult;
}
