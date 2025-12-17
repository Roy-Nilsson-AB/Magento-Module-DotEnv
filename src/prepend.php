<?php

/**
 * DotEnv Prepend File
 *
 * This file is loaded by Composer's autoload mechanism BEFORE Magento's bootstrap.
 * It loads .env files early enough that environment variables are available
 * to Magento's DeploymentConfig system.
 *
 * Loading order in Magento:
 * 1. app/autoload.php defines BP constant
 * 2. app/autoload.php includes vendor/autoload.php <- THIS FILE RUNS HERE
 * 3. app/bootstrap.php continues Magento bootstrap
 * 4. DeploymentConfig reads env.php and environment variables
 */

declare(strict_types=1);

// BP constant is defined in app/autoload.php (line 16) before vendor/autoload.php is loaded
if (defined('BP')) {
    // Load our DotEnvLoader class manually (before autoload is fully ready)
    require_once __DIR__ . '/Service/DotEnvLoader.php';

    // Load .env files from Magento root
    \Rnab\DotEnv\Service\DotEnvLoader::loadFromPath(BP);
}
