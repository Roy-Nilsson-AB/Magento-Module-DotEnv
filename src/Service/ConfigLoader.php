<?php

declare(strict_types=1);

namespace Rnab\DotEnv\Service;

/**
 * Loads and merges environment-specific PHP configuration files.
 *
 * Loading order:
 * 1. base.php (required)
 * 2. {environment}.php (optional)
 * 3. local.php (optional)
 *
 * Environment is determined by the .environment file in the config directory.
 */
class ConfigLoader
{
    private const ENV_FILE = '.environment';

    /**
     * Load and merge configuration files from the specified directory.
     *
     * @param string $configDir Path to the config directory (e.g., app/etc/env)
     * @return array Merged configuration
     * @throws \RuntimeException If environment or base config is missing
     */
    public static function load(string $configDir): array
    {
        $environment = self::readEnvironment($configDir);

        // Load base config (required)
        $config = self::loadRequiredFile($configDir . '/base.php');

        // Merge environment-specific config (optional)
        $envFile = $configDir . '/' . $environment . '.php';
        if (file_exists($envFile)) {
            $config = array_replace_recursive($config, require $envFile);
        }

        // Merge local overrides (optional)
        $localFile = $configDir . '/local.php';
        if (file_exists($localFile)) {
            $config = array_replace_recursive($config, require $localFile);
        }

        return $config;
    }

    /**
     * Read the environment name from .environment file.
     *
     * @param string $configDir
     * @return string
     * @throws \RuntimeException If file is missing or empty
     */
    private static function readEnvironment(string $configDir): string
    {
        $envFile = $configDir . '/' . self::ENV_FILE;

        if (!file_exists($envFile)) {
            throw new \RuntimeException(
                'Environment not configured. Create ' . $configDir . '/.environment'
            );
        }

        $environment = trim(file_get_contents($envFile));

        if ($environment === '') {
            throw new \RuntimeException(
                'Environment file is empty: ' . $configDir . '/.environment'
            );
        }

        return $environment;
    }

    /**
     * Load a required configuration file.
     *
     * @param string $path
     * @return array
     * @throws \RuntimeException If file is missing
     */
    private static function loadRequiredFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('Required config missing: ' . $path);
        }

        return require $path;
    }
}
