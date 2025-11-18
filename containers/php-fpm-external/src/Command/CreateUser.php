<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a local user.',
)]
final class CreateUser extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new Question('Please insert the username: ');
        $username = $helper->ask($input, $output, $question);
        \assert(\is_string($username));

        $question = new Question('Please insert the password: ');
        $question->setHidden(true);
        $password = $helper->ask($input, $output, $question);
        \assert(\is_string($password));

        $this->createUser($username, $password);
        $output->writeln('User created successfully.');

        return Command::SUCCESS;
    }

    private function createUser(string $username, string $password): void
    {
        $user = new User($username);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        $this->em->persist($user);
        $this->em->flush();
    }
}
