<?php

declare(strict_types=1);

namespace Rnab\DotEnv\Console\Command;

use Magento\Framework\App\DeploymentConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to show environment configuration status
 */
class ShowEnvCommand extends Command
{
    private const ENV_DIR = 'env';
    private const ENV_FILE = '.environment';

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
            ->setDescription('Show environment configuration status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Environment Configuration Status</info>');
        $output->writeln('');

        $envDir = BP . '/app/etc/' . self::ENV_DIR;

        // Show current environment
        $environment = $this->readEnvironment($envDir);
        $output->writeln("<comment>Current Environment:</comment> <info>{$environment}</info>");
        $output->writeln('');

        // Show config files being loaded
        $output->writeln('<comment>Config files loaded (in order):</comment>');
        $files = [
            'base.php' => 'base (required)',
            $environment . '.php' => 'environment-specific',
            'local.php' => 'local overrides',
        ];

        $index = 1;
        foreach ($files as $file => $description) {
            $fullPath = $envDir . '/' . $file;
            $exists = file_exists($fullPath);
            $status = $exists ? '<info>exists</info>' : '<comment>not found</comment>';
            $output->writeln("  {$index}. {$file} ({$description}) - {$status}");
            $index++;
        }
        $output->writeln('');

        // Show Magento mode
        $mageMode = $this->deploymentConfig->get('MAGE_MODE') ?? 'not set';
        $output->writeln("<comment>Magento Mode:</comment> <info>{$mageMode}</info>");
        $output->writeln('');

        // Show Magento DB config
        $output->writeln('<comment>Database Configuration:</comment>');
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
        $output->writeln('<comment>Database Connection Test:</comment>');
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $version = $connection->fetchOne('SELECT VERSION()');
            $output->writeln("  <info>Connected successfully</info>");
            $output->writeln("  MySQL Version: <info>{$version}</info>");
        } catch (\Exception $e) {
            $output->writeln("  <error>Connection failed: {$e->getMessage()}</error>");
        }

        return Command::SUCCESS;
    }

    private function readEnvironment(string $envDir): string
    {
        $envFile = $envDir . '/' . self::ENV_FILE;

        if (!file_exists($envFile)) {
            return 'NOT CONFIGURED (missing .environment file)';
        }

        $environment = trim(file_get_contents($envFile));

        if ($environment === '') {
            return 'EMPTY (.environment file is empty)';
        }

        return $environment;
    }
}
