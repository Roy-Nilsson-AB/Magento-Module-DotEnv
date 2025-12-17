<?php

declare(strict_types=1);

namespace Rnab\DotEnv\Console\Command;

use Magento\Framework\App\DeploymentConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to show loaded environment variables and Magento configuration
 */
class ShowEnvCommand extends Command
{
    private DeploymentConfig $deploymentConfig;

    public function __construct(
        DeploymentConfig $deploymentConfig,
        string $name = null
    ) {
        parent::__construct($name);
        $this->deploymentConfig = $deploymentConfig;
    }

    protected function configure(): void
    {
        $this->setName('dotenv:show')
            ->setDescription('Show loaded environment variables and Magento configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>DotEnv Module - Environment Status</info>');
        $output->writeln('');

        // Show APP_ENV
        $appEnv = getenv('APP_ENV') ?: $_ENV['APP_ENV'] ?? 'NOT SET';
        $output->writeln("<comment>Current Environment:</comment> <info>{$appEnv}</info>");
        $output->writeln('');

        // Show loaded files
        $output->writeln('<comment>Expected .env files loaded (in order):</comment>');
        $output->writeln('  1. .env');
        $output->writeln('  2. .env.local');
        if ($appEnv !== 'NOT SET') {
            $output->writeln("  3. .env.{$appEnv}");
            $output->writeln("  4. .env.{$appEnv}.local");
        }
        $output->writeln('');

        // Show environment variables
        $output->writeln('<comment>Environment Variables (from .env files):</comment>');
        $envVars = [];
        foreach ($_ENV as $key => $value) {
            // Filter out system variables, show only custom ones
            if (!in_array($key, ['PATH', 'HOME', 'USER', 'SHELL', 'TERM', 'PWD', 'SHLVL', '_'])) {
                $envVars[$key] = $value;
            }
        }

        if (empty($envVars)) {
            $output->writeln('  <info>No custom environment variables loaded</info>');
        } else {
            ksort($envVars);
            foreach ($envVars as $key => $value) {
                // Hide sensitive values (password, secret, key, token)
                if (preg_match('/(PASSWORD|PASS|SECRET|KEY|TOKEN)/i', $key)) {
                    $displayValue = '***HIDDEN***';
                } else {
                    $valueStr = (string)$value;
                    $displayValue = strlen($valueStr) > 80 ? substr($valueStr, 0, 77) . '...' : $valueStr;
                }
                $output->writeln("  <info>{$key}</info> = {$displayValue}");
            }
        }
        $output->writeln('');

        // Show Magento DB config (parsed from env.php)
        $output->writeln('<comment>Magento Database Configuration (parsed from env.php):</comment>');
        try {
            $dbHost = $this->deploymentConfig->get('db/connection/default/host');
            $dbName = $this->deploymentConfig->get('db/connection/default/dbname');
            $dbUser = $this->deploymentConfig->get('db/connection/default/username');

            $output->writeln("  Host: <info>{$dbHost}</info>");
            $output->writeln("  Database: <info>{$dbName}</info>");
            $output->writeln("  Username: <info>{$dbUser}</info>");
            $output->writeln('  Password: <info>***HIDDEN***</info>');
        } catch (\Exception $e) {
            $output->writeln("  <error>Error reading config: {$e->getMessage()}</error>");
        }
        $output->writeln('');

        // Test database connection
        $output->writeln('<comment>Testing Database Connection:</comment>');
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $version = $connection->fetchOne('SELECT VERSION()');
            $output->writeln("  <info>✓ Connected successfully!</info>");
            $output->writeln("  MySQL Version: <info>{$version}</info>");
        } catch (\Exception $e) {
            $output->writeln("  <error>✗ Connection failed: {$e->getMessage()}</error>");
        }

        return Command::SUCCESS;
    }
}
