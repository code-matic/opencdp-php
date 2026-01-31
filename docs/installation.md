# Installation Guide

## Requirements

- PHP 8.0 or higher
- Composer for dependency management

## Installation via Composer

The recommended way to install the OpenCDP PHP SDK is through Composer:

```bash
composer require codematic/opencdp-php
```

This will automatically install the required dependencies:
- `guzzlehttp/guzzle` - HTTP client library

## Optional Dependencies

### Customer.io Integration

If you want to use the dual-write functionality to Customer.io:

```bash
composer require customerio/customerio-php
```

## Manual Installation

If you prefer not to use Composer, you can download the source code and include it manually:

1. Download the latest release from the repository
2. Extract to your project directory
3. Include the autoloader:

```php
require_once '/path/to/opencdp-php/vendor/autoload.php';
```

**Note:** Manual installation is not recommended as you'll need to manage dependencies yourself.

## Verifying Installation

After installation, verify everything is working:

```php
<?php

require 'vendor/autoload.php';

use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;

// Create a test configuration
$config = new CDPConfig(cdpApiKey: 'test-key');
$client = new CDPClient($config);

echo "OpenCDP PHP SDK installed successfully!\n";
```

## Next Steps

- Read the [Configuration Guide](configuration.md)
- Check out [Usage Examples](../examples/)
- View the [API Reference](api-reference.md)
