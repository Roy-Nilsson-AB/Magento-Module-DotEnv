<?php
declare(strict_types=1);

namespace Rnab\DotEnv\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\FileSystemException;
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
     * @param Writer $subject
     * @param array $data
     * @param bool $override
     * @param string|null $pool
     * @param array $comments
     * @param bool $lock
     * @return array|null
     * @throws FileSystemException
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

        foreach ($data as $fileKey => $config) {
            if ($fileKey === ConfigFilePool::APP_ENV && $protectEnv) {
                $this->logger->warning(
                    'Blocked attempt to write to env.php. File is protected by RNAB DotEnv module. ' .
                    'Configuration should be managed via .env files. ' .
                    'To disable protection, go to Stores > Configuration > Advanced > Environment Configuration.'
                );
                
                throw new FileSystemException(
                    __(
                        'env.php is protected from writes. Configuration should be managed via .env files. ' .
                        'To disable protection, go to Stores > Configuration > Advanced > Environment Configuration.'
                    )
                );
            }

            if ($fileKey === ConfigFilePool::APP_CONFIG && $protectConfig) {
                $this->logger->warning(
                    'Blocked attempt to write to config.php. File is protected by RNAB DotEnv module. ' .
                    'To disable protection, go to Stores > Configuration > Advanced > Environment Configuration.'
                );
                
                throw new FileSystemException(
                    __(
                        'config.php is protected from writes. ' .
                        'To disable protection, go to Stores > Configuration > Advanced > Environment Configuration.'
                    )
                );
            }
        }

        return null;
    }
}
