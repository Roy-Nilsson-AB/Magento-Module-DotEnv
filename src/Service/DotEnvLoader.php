<?php

declare(strict_types=1);

namespace Rnab\DotEnv\Service;

use Symfony\Component\Dotenv\Dotenv;

/**
 * DotEnv Loader Service
 *
 * Loads .env files in Symfony-style cascading order:
 * 1. .env (committed, defaults for all environments)
 * 2. .env.local (NOT committed, machine-specific overrides)
 * 3. .env.{APP_ENV} (committed, environment-specific)
 * 4. .env.{APP_ENV}.local (NOT committed, machine-specific + environment-specific)
 */
class DotEnvLoader
{
    /**
     * Default environment if APP_ENV is not set
     */
    private const DEFAULT_ENV = 'prod';

    /**
     * Load .env files from the specified path
     *
     * @param string $path The base path (usually Magento root BP)
     * @return void
     */
    public static function loadFromPath(string $path): void
    {
        // Check if .env file exists - if not, nothing to do
        if (!file_exists($path . '/.env')) {
            return;
        }

        try {
            $dotenv = new Dotenv();

            // Determine APP_ENV
            $appEnv = self::determineEnvironment($path);

            // Build list of files to load in order
            $filesToLoad = self::buildFileList($path, $appEnv);

            // Load each file that exists
            foreach ($filesToLoad as $file) {
                if (file_exists($file)) {
                    $dotenv->load($file);
                }
            }
        } catch (\Throwable $e) {
            // Log error but don't break the application
            error_log(sprintf(
                'RNAB DotEnv: Failed to load .env files: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }

    /**
     * Determine the environment from APP_ENV variable
     *
     * Priority:
     * 1. $_ENV['APP_ENV'] if already set
     * 2. $_SERVER['APP_ENV'] if already set
     * 3. Read from .env.local if exists
     * 4. Default to 'prod'
     *
     * @param string $path Base path
     * @return string Environment name
     */
    private static function determineEnvironment(string $path): string
    {
        // Check if already set in environment
        if (!empty($_ENV['APP_ENV'])) {
            return $_ENV['APP_ENV'];
        }

        if (!empty($_SERVER['APP_ENV'])) {
            return $_SERVER['APP_ENV'];
        }

        // Try to read from .env.local
        $envLocalFile = $path . '/.env.local';
        if (file_exists($envLocalFile)) {
            $content = file_get_contents($envLocalFile);
            if ($content !== false && preg_match('/^APP_ENV\s*=\s*(.+)$/m', $content, $matches)) {
                $env = trim($matches[1]);
                // Remove quotes if present
                $env = trim($env, '"\'');
                if (!empty($env)) {
                    return $env;
                }
            }
        }

        return self::DEFAULT_ENV;
    }

    /**
     * Build list of .env files to load in order
     *
     * @param string $path Base path
     * @param string $appEnv Environment name
     * @return array List of absolute file paths
     */
    private static function buildFileList(string $path, string $appEnv): array
    {
        return [
            $path . '/.env',
            $path . '/.env.local',
            $path . '/.env.' . $appEnv,
            $path . '/.env.' . $appEnv . '.local',
        ];
    }
}
