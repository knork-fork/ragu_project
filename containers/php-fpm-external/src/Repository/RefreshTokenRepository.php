<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;

final class RefreshTokenRepository extends \Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository
{
    public function saveToken(RefreshToken $refreshToken, User $user): RefreshToken
    {
        $refreshToken->setUser($user);

        $this->getEntityManager()->persist($refreshToken);
        $this->getEntityManager()->flush();

        return $refreshToken;
    }
}
