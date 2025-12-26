# RNAB DotEnv Module for Magento 2

A Magento 2 module that provides `.env` file support using Symfony's DotEnv component. This module loads environment variables early in the Magento bootstrap process, making them available to Magento's native configuration system.

## Features

- 🔄 **Symfony-style cascading .env files** - Load environment-specific configurations
- ⚡ **Early loading** - Variables available before Magento's DeploymentConfig reads env.php
- 🎯 **Zero configuration** - Works immediately after installation
- 🔒 **Secure** - Supports `.env.local` files for secrets (excluded from version control)
- 🌍 **Environment-agnostic** - Support any environment name (prod, dev, staging, mickey-mouse, etc.)

## Installation

### Via Composer

```bash
composer require rnab/module-dotenv
```

### Manual Installation

1. Clone this repository to `vendor/rnab/module-dotenv`
2. Add to your project's `composer.json`:

```json
{
  "require": {
    "rnab/module-dotenv": "dev-master"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Roy-Nilsson-AB/Magento-Module-DotEnv"
    }
  ]
}
```

3. Run `composer install`

## Usage

### File Loading Order

The module loads `.env` files in the following order (later files override earlier ones):

1. **`.env`** - Committed to version control, contains defaults for all environments
2. **`.env.local`** - NOT committed, machine-specific overrides for all environments
3. **`.env.{APP_ENV}`** - Committed, environment-specific settings (e.g., `.env.prod`, `.env.dev`)
4. **`.env.{APP_ENV}.local`** - NOT committed, machine-specific overrides for specific environment

### Setting the Environment

The `APP_ENV` variable determines which environment-specific files are loaded:

**Option 1: Set in `.env.local`** (recommended)
```bash
# .env.local
APP_ENV=dev
```

**Option 2: Set as system environment variable**
```bash
export APP_ENV=production
```

**Default**: If `APP_ENV` is not set, it defaults to `prod`.

### .gitignore Configuration

Add the following to your `.gitignore`:

```gitignore
# Environment files - local overrides should not be committed
/.env.local
/.env.*.local
```

**DO commit** these files:
- `.env`
- `.env.prod`
- `.env.dev`
- `.env.staging`
- etc.

## Magento Configuration Integration

### Using with Magento's CONFIG__ System

Magento reads environment variables with the `CONFIG__` prefix. You can set these in your `.env` files:

**Example `.env` file:**
```bash
# Database configuration
CONFIG__DEFAULT__DB__CONNECTION__DEFAULT__HOST=localhost
CONFIG__DEFAULT__DB__CONNECTION__DEFAULT__DBNAME=magento
CONFIG__DEFAULT__DB__CONNECTION__DEFAULT__USERNAME=magento_user

# Cache configuration
CONFIG__DEFAULT__CACHE__FRONTEND__DEFAULT__BACKEND=Cm_Cache_Backend_Redis
CONFIG__DEFAULT__CACHE__FRONTEND__DEFAULT__BACKEND_OPTIONS__SERVER=redis
CONFIG__DEFAULT__CACHE__FRONTEND__DEFAULT__BACKEND_OPTIONS__PORT=6379

# Admin URL
CONFIG__DEFAULT__ADMIN__URL__CUSTOM=https://example.com/admin

# Email configuration
CONFIG__DEFAULT__SYSTEM__SMTP__HOST=smtp.example.com
CONFIG__DEFAULT__SYSTEM__SMTP__PORT=587
```

### Using with Magento's #env() Syntax

Magento's `app/etc/env.php` supports the `#env()` syntax for reading environment variables:

**app/etc/env.php:**
```php
<?php
return [
    'db' => [
        'connection' => [
            'default' => [
                'host' => '#env(DB_HOST, "localhost")',
                'dbname' => '#env(DB_NAME, "magento")',
                'username' => '#env(DB_USER, "root")',
                'password' => '#env(DB_PASS, "")',
            ],
        ],
    ],
];
```

**Your `.env` file:**
```bash
DB_HOST=localhost
DB_NAME=magento_production
DB_USER=magento_user
DB_PASS=secret_password
```

## Example Configuration

### Example: Multi-environment Setup

**.env** (committed, defaults for all environments)
```bash
# Application
APP_NAME="My Magento Store"
APP_DEBUG=false

# Database defaults
DB_HOST=localhost
DB_NAME=magento
DB_USER=magento_user

# Cache
REDIS_HOST=redis
REDIS_PORT=6379
```

**.env.local** (NOT committed, machine-specific)
```bash
# Set environment for this machine
APP_ENV=dev

# Local database password
DB_PASS=local_dev_password
```

**.env.dev** (committed, development environment)
```bash
# Enable debug mode in development
APP_DEBUG=true
CONFIG__DEFAULT__DEV__DEBUG__TEMPLATE_HINTS_STOREFRONT=1
```

**.env.prod** (committed, production environment)
```bash
# Production-specific settings
APP_DEBUG=false
CONFIG__DEFAULT__SYSTEM__FULL_PAGE_CACHE__CACHING_APPLICATION=2
```

**.env.prod.local** (NOT committed, production machine secrets)
```bash
# Production database password
DB_PASS=super_secret_production_password

# Production API keys
PAYMENT_GATEWAY_API_KEY=live_key_xyz
```

## How It Works

### Bootstrap Integration

The module uses Composer's `autoload.files` feature to inject a prepend file that runs very early in Magento's bootstrap process:

1. **app/autoload.php** defines the `BP` constant (Magento root path)
2. **app/autoload.php** includes `vendor/autoload.php` ← **Module's prepend.php runs here**
3. **app/bootstrap.php** continues Magento bootstrap
4. **DeploymentConfig** reads `app/etc/env.php` and environment variables

This ensures `.env` files are loaded before Magento reads any configuration.

### Technical Details

- Uses Symfony's DotEnv component for parsing
- Loads files into `$_ENV` and `$_SERVER` superglobals
- Gracefully handles missing files (optional by design)
- Does not overwrite existing environment variables
- Errors are logged to PHP error log, but won't break the application

## Requirements

- Magento 2.4.6 or later
- PHP 8.1 or later
- Symfony DotEnv ^6.0 or ^7.0

## Troubleshooting

### Variables not being loaded

1. Check that `.env` file exists in Magento root directory
2. Verify file permissions (files must be readable)
3. Check PHP error log for DotEnv errors: `var/log/system.log`
4. Ensure `APP_ENV` is set correctly if using environment-specific files

### Variables not available in Magento

1. Clear Magento cache: `bin/magento cache:clean`
2. Verify variable naming follows Magento conventions (e.g., `CONFIG__DEFAULT__*`)
3. Check that variables are set before Magento reads them (module loads early, but some CLI commands may bypass this)


## Configuration File Protection

The module includes protection for `env.php` and `config.php` to prevent accidental overwrites by Magento commands.

### Protection Settings

Protection is configured via **Stores > Configuration > Advanced > Environment Configuration**:

- **Protect env.php from writes** (enabled by default)
  - Prevents commands like `config:set --lock` from writing to env.php
  - Configuration should be managed via `.env` files instead
  
- **Protect config.php from writes** (disabled by default)
  - Prevents module enable/disable and config dump commands from writing to config.php
  - Useful when config.php is managed via version control

### Why Protection?

When managing configuration via `.env` files or version control, you don't want Magento commands to accidentally overwrite your carefully managed files:

**Without Protection:**
```bash
# Whoops! This will overwrite your env.php
bin/magento config:set --lock web/unsecure/base_url https://example.com
```

**With Protection (default):**
```bash
# Safely blocked with helpful error message
bin/magento config:set --lock web/unsecure/base_url https://example.com
# Error: env.php is protected from writes. Configuration should be managed via .env files.
```

### When to Enable/Disable Protection

**env.php Protection (enabled by default):**
- ✅ Keep enabled when using `.env` files for configuration
- ❌ Disable only for emergency fixes or migration tasks

**config.php Protection (disabled by default):**
- ✅ Enable when config.php is managed via version control and you want to prevent module changes
- ❌ Keep disabled when developers need to enable/disable modules or run `app:config:dump`

### Disabling Protection

If you need to temporarily disable protection (e.g., for data migration or emergencies):

1. Go to **Stores > Configuration > Advanced > Environment Configuration**
2. Set "Protect env.php from writes" to "No"
3. Run your command
4. Re-enable protection immediately after

## License

Proprietary

## Author

Roy Nilsson - RNAB
