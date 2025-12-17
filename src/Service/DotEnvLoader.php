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

            // Also populate putenv() so getenv() works (needed for Magento's #env() syntax)
            foreach ($_ENV as $key => $value) {
                if (is_string($value)) {
                    putenv("$key=$value");
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
     * 4. Return null if not found (no environment-specific files will be loaded)
     *
     * @param string $path Base path
     * @return string|null Environment name or null if not set
     */
    private static function determineEnvironment(string $path): ?string
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

        // No APP_ENV found - only load .env and .env.local
        return null;
    }

    /**
     * Build list of .env files to load in order
     *
     * @param string $path Base path
     * @param string|null $appEnv Environment name (null if not specified)
     * @return array List of absolute file paths
     */
    private static function buildFileList(string $path, ?string $appEnv): array
    {
        $files = [
            $path . '/.env',
            $path . '/.env.local',
        ];

        // Only add environment-specific files if APP_ENV is set
        if ($appEnv !== null) {
            $files[] = $path . '/.env.' . $appEnv;
            $files[] = $path . '/.env.' . $appEnv . '.local';
        }

        return $files;
    }
}
