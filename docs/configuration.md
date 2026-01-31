# Configuration Guide

## Basic Configuration

The SDK is configured using the `CDPConfig` class:

```php
use Codematic\OpenCDP\CDPConfig;

$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key'
);
```

## Configuration Options

### Required Parameters

#### `cdpApiKey` (string)
Your OpenCDP API key for authentication.

```php
$config = new CDPConfig(cdpApiKey: 'your-api-key');
```

### Optional Parameters

#### `cdpEndpoint` (string)
Custom API endpoint URL. Default: `https://api.opencdp.io/gateway/data-gateway`

```php
$config = new CDPConfig(
    cdpApiKey: 'your-api-key',
    cdpEndpoint: 'https://custom-endpoint.example.com'
);
```

#### `timeout` (int)
Request timeout in milliseconds. Default: `10000` (10 seconds)

```php
$config = new CDPConfig(
    cdpApiKey: 'your-api-key',
    timeout: 15000 // 15 seconds
);
```

#### `debug` (bool)
Enable debug logging. Default: `false`

```php
$config = new CDPConfig(
    cdpApiKey: 'your-api-key',
    debug: true
);
```

When enabled, the SDK will log all operations using your configured logger or `error_log()`.

#### `failOnException` (bool)
Throw exceptions on errors. Default: `false`

```php
$config = new CDPConfig(
    cdpApiKey: 'your-api-key',
    failOnException: true
);
```

When `false`, errors are logged but don't throw exceptions. When `true`, validation and API errors will throw exceptions.

#### `logger` (LoggerInterface|null)
Custom logger instance. Default: `DefaultLogger`

```php
use Codematic\OpenCDP\LoggerInterface;

class MyLogger implements LoggerInterface {
    public function debug(string $message, array $context = []): void {
        // Your implementation
    }
    
    public function error(string $message, array $context = []): void {
        // Your implementation
    }
    
    public function warn(string $message, array $context = []): void {
        // Your implementation
    }
}

$config = new CDPConfig(
    cdpApiKey: 'your-api-key',
    logger: new MyLogger()
);
```

#### `sendToCustomerIo` (bool)
Enable dual-write to Customer.io. Default: `false`

```php
$config = new CDPConfig(
    cdpApiKey: 'your-api-key',
    sendToCustomerIo: true,
    customerIo: [
        'siteId' => 'your-site-id',
        'apiKey' => 'your-api-key'
    ]
);
```

#### `customerIo` (array|null)
Customer.io configuration. Required when `sendToCustomerIo` is `true`.

```php
$config = new CDPConfig(
    cdpApiKey: 'your-api-key',
    sendToCustomerIo: true,
    customerIo: [
        'siteId' => 'your-site-id',     // Required
        'apiKey' => 'your-api-key',     // Required
        'region' => 'us'                // Optional: 'us' or 'eu', default: 'us'
    ]
);
```

## Complete Example

```php
use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;

$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key',
    cdpEndpoint: 'https://api.opencdp.io/gateway/data-gateway',
    timeout: 10000,
    debug: true,
    failOnException: false,
    logger: null, // Use default logger
    sendToCustomerIo: true,
    customerIo: [
        'siteId' => 'your-customer-io-site-id',
        'apiKey' => 'your-customer-io-api-key',
        'region' => 'us'
    ]
);

$client = new CDPClient($config);
```

## Environment Variables

You can load configuration from environment variables:

```php
$config = new CDPConfig(
    cdpApiKey: $_ENV['CDP_API_KEY'],
    debug: ($_ENV['APP_ENV'] ?? 'production') === 'development',
    sendToCustomerIo: isset($_ENV['CUSTOMERIO_SITE_ID']),
    customerIo: isset($_ENV['CUSTOMERIO_SITE_ID']) ? [
        'siteId' => $_ENV['CUSTOMERIO_SITE_ID'],
        'apiKey' => $_ENV['CUSTOMERIO_API_KEY'],
        'region' => $_ENV['CUSTOMERIO_REGION'] ?? 'us'
    ] : null
);
```

## Best Practices

1. **Never hardcode API keys** - Use environment variables or secure configuration files
2. **Enable debug mode in development** - Helps troubleshoot issues
3. **Disable debug mode in production** - Reduces log verbosity and overhead
4. **Set appropriate timeouts** - Based on your network conditions
5. **Use failOnException judiciously** - Consider your error handling strategy
