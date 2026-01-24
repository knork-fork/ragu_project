<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture
{
    public const TEST_USER_REFERENCE = 'test_user_reference';
    public const TEST_USER_USERNAME = 'test_user';
    public const TEST_USER_PASSWORD = 'test_password';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createUser($manager);
    }

    private function createUser(ObjectManager $manager): void
    {
        $user = new User(self::TEST_USER_USERNAME);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::TEST_USER_PASSWORD));

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::TEST_USER_REFERENCE, $user);
    }
}
