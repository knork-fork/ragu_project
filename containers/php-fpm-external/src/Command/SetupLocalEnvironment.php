<?php
declare(strict_types=1);

namespace App\Command;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:setup-env',
    description: 'Setup local environment, including private keys for lexik_jwt_authentication.',
)]
final class SetupLocalEnvironment extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists('./.env.local')) {
            $output->writeln('.env.local does not exist, creating it');
            file_put_contents('./.env.local', '');
        }

        $this->setupEnv();
        $this->setupJwtConfig();

        $output->writeln('Local env setup.');

        return Command::SUCCESS;
    }

    private function setupEnv(): void
    {
        $config = $this->getConfigForTag('symfony/framework-bundle');

        if (!isset($config['APP_ENV'])) {
            $config['APP_ENV'] = 'prod';
        }

        $this->saveConfigForTag('symfony/framework-bundle', $config);
    }

    /**
     * Setup JWT passphrase and generate public and private keys with it (if they don't exist already).
     */
    private function setupJwtConfig(): void
    {
        $config = $this->getConfigForTag('lexik/jwt-authentication-bundle');

        // Set JWT_PASSPHRASE if not already set
        if (!isset($config['JWT_PASSPHRASE'])) {
            $config['JWT_PASSPHRASE'] = bin2hex(random_bytes(32));
        }

        $this->saveConfigForTag('lexik/jwt-authentication-bundle', $config);

        $keyDir = __DIR__ . '/../../config/jwt';
        if (!is_dir($keyDir)) {
            mkdir($keyDir, 0o755, true);
        }

        $privateKey = $keyDir . '/private.pem';
        if (!file_exists($privateKey)) {
            $cmd = \sprintf(
                'openssl genrsa -aes256 -passout pass:%s -out %s 4096',
                escapeshellarg($config['JWT_PASSPHRASE']),
                escapeshellarg($privateKey)
            );
            $this->runCommand($cmd);
            chmod($privateKey, 0o644);
        }

        $publicKey = $keyDir . '/public.pem';
        if (!file_exists($publicKey)) {
            $cmd = \sprintf(
                'openssl rsa -pubout -passin pass:%s -in %s -out %s',
                escapeshellarg($config['JWT_PASSPHRASE']),
                escapeshellarg($privateKey),
                escapeshellarg($publicKey)
            );
            $this->runCommand($cmd);
            chmod($publicKey, 0o644);
        }
    }

    /**
     * @return array<string, string>
     */
    private function getConfigForTag(string $tag): array
    {
        $content = file_get_contents('./.env.local');
        if ($content === false) {
            throw new RuntimeException('Failed to read .env.local file.');
        }

        $openingTag = "###> {$tag} ###";
        $closingTag = "###< {$tag} ###";
        $configStart = strpos($content, $openingTag);
        $configEnd = strpos($content, $closingTag);

        if ($configStart === false || $configEnd === false) {
            // Tag does not exist in env file
            return [];
        }

        $configStart = $configStart + \strlen($openingTag) + 1;
        --$configEnd;
        $config = substr($content, $configStart, $configEnd - $configStart);

        $lines = explode(\PHP_EOL, trim($config));
        $result = [];
        foreach ($lines as $line) {
            [$key, $value] = explode('=', $line, 2);
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array<string, string> $config
     */
    private function saveConfigForTag(string $tag, array $config): void
    {
        if (empty($config)) {
            throw new InvalidArgumentException('Config array is empty.');
        }

        $content = file_get_contents('./.env.local');
        if ($content === false) {
            throw new RuntimeException('Failed to read .env.local file.');
        }

        $openingTag = "###> {$tag} ###";
        $closingTag = "###< {$tag} ###";

        // If tag does not exist append new block
        if (!str_contains($content, $openingTag) || !str_contains($content, $closingTag)) {
            $content .= \PHP_EOL . $openingTag . \PHP_EOL . $closingTag . \PHP_EOL;
        }

        $configLines = [];
        foreach ($config as $key => $value) {
            $configLines[] = "{$key}={$value}";
        }
        $newConfig = implode(\PHP_EOL, $configLines);

        $pattern = \sprintf(
            '/%s(.*?)%s/s',
            preg_quote($openingTag, '/'),
            preg_quote($closingTag, '/')
        );

        $replacement = $openingTag . \PHP_EOL . $newConfig . \PHP_EOL . $closingTag;
        $newContent = preg_replace($pattern, $replacement, $content, 1);
        $newContent = preg_replace($pattern, $replacement, $content, 1);

        if ($newContent === null) {
            throw new RuntimeException('Failed to update .env.local content. preg_last_error=' . preg_last_error());
        }

        file_put_contents('./.env.local', $newContent);
    }

    private function runCommand(string $cmd): void
    {
        exec($cmd . ' 2>&1', $output, $code);

        if ($code !== 0) {
            throw new RuntimeException("Command failed: {$cmd}\nOutput:\n" . implode("\n", $output));
        }
    }
}
