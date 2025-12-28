<?php
declare(strict_types=1);

namespace Rnab\DotEnv\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Psr\Log\LoggerInterface;

/**
 * Plugin to prevent writes to config files based on configuration
 * 
 * Protects env.php and config.php from being overwritten by Magento commands
 * when managing configuration via .env files or version control.
 */
class PreventConfigFileWritePlugin
{
    private const CONFIG_PATH_PROTECT_ENV = 'dotenv/protection/protect_env_php';
    private const CONFIG_PATH_PROTECT_CONFIG = 'dotenv/protection/protect_config_php';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Prevent writing to config files if protection is enabled
     *
     * Silently removes protected file data from the write operation and logs a warning.
     * This allows commands like setup:upgrade to continue without errors while still
     * protecting the files from being modified.
     *
     * @param Writer $subject
     * @param array $data
     * @param bool $override
     * @param string|null $pool
     * @param array $comments
     * @param bool $lock
     * @return array|null
     */
    public function beforeSaveConfig(
        Writer $subject,
        array $data,
        $override = false,
        $pool = null,
        array $comments = [],
        bool $lock = false
    ): ?array {
        $protectEnv = $this->scopeConfig->isSetFlag(self::CONFIG_PATH_PROTECT_ENV);
        $protectConfig = $this->scopeConfig->isSetFlag(self::CONFIG_PATH_PROTECT_CONFIG);

        $modified = false;

        // Remove protected files from the data array
        if (isset($data[ConfigFilePool::APP_ENV]) && $protectEnv) {
            unset($data[ConfigFilePool::APP_ENV]);
            $modified = true;

            $this->logger->warning(
                'Prevented write to env.php. File is protected by RNAB DotEnv module. ' .
                'Configuration should be managed via .env files. ' .
                'To disable protection, go to Stores > Configuration > Advanced > Environment Configuration.'
            );
        }

        if (isset($data[ConfigFilePool::APP_CONFIG]) && $protectConfig) {
            unset($data[ConfigFilePool::APP_CONFIG]);
            $modified = true;

            $this->logger->warning(
                'Prevented write to config.php. File is protected by RNAB DotEnv module. ' .
                'To disable protection, go to Stores > Configuration > Advanced > Environment Configuration.'
            );
        }

        // Only return modified data if we actually changed something
        return $modified ? [$data, $override, $pool, $comments, $lock] : null;
    }
}
