# Codematic OpenCDP PHP SDK

A PHP client library for Codematic's Customer Data Platform (CDP) with optional Customer.io integration.

See the [CDP Documentation](https://docs.opencdp.io/) for more information.

## Installation

```bash
composer require codematic/opencdp-php
```

## Requirements

- PHP 8.0 or higher
- Guzzle HTTP client (automatically installed)

### Optional Dependencies

- `customerio/customerio-php` - Required for dual-write functionality to Customer.io

```bash
composer require customerio/customerio-php
```

## Features

- Send customer identification and event data to Codematic CDP
- Send transactional emails (transactional messaging and raw HTML)
- Send SMS messages
- Send push notifications
- Optional dual-write capability to Customer.io
- Comprehensive error handling and logging
- Full type safety with PHP 8.0+ features

## Quick Start

```php
<?php

use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;
use Codematic\OpenCDP\Identifiers;
use Codematic\OpenCDP\SendEmailRequest;

require 'vendor/autoload.php';

// Create a client
$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key'
);

$client = new CDPClient($config);

// Identify a user
$client->identify('user123', [
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'plan' => 'premium'
]);

// Track an event
$client->track('user123', 'purchase_completed', [
    'amount' => 99.99,
    'item_id' => 'prod-123'
]);
```

## Configuration

```php
$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key',
    cdpEndpoint: 'https://api.opencdp.io/gateway/data-gateway', // Optional custom endpoint
    timeout: 10000, // Request timeout in milliseconds (default: 10000)
    debug: false, // Enable debug logging (default: false)
    failOnException: false, // Throw exceptions on errors (default: false)
    logger: null, // Custom logger instance (LoggerInterface)
    sendToCustomerIo: false, // Enable dual-write to Customer.io (default: false)
    customerIo: [ // Required if sendToCustomerIo is true
        'siteId' => 'your-customer-io-site-id',
        'apiKey' => 'your-customer-io-api-key',
        'region' => 'us' // or 'eu' for EU data centers
    ]
);
```

## Usage Examples

### Identify a Person

```php
$client->identify('user123', [
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'plan' => 'premium',
    'created_at' => time()
]);
```

### Track an Event

```php
$client->track('user123', 'purchase_completed', [
    'amount' => 99.99,
    'currency' => 'USD',
    'item_id' => 'prod-123',
    'category' => 'electronics'
]);
```

### Register a Device

```php
use Codematic\OpenCDP\DeviceRegistrationParameters;

$params = new DeviceRegistrationParameters(
    deviceId: 'device-abc-123',
    platform: 'ios',
    fcmToken: 'fcm-token-here',
    name: 'iOS Device',
    osVersion: '15.0',
    model: 'iPhone 13',
    appVersion: '1.0.0'
);

$client->registerDevice('user123', $params);
```

### Send Transactional Email (Using Template)

```php
use Codematic\OpenCDP\SendEmailRequest;
use Codematic\OpenCDP\Identifiers;

$request = new SendEmailRequest([
    'to' => 'user@example.com',
    'identifiers' => Identifiers::withId('user123'),
    'transactional_message_id' => 'WELCOME_EMAIL',
    'message_data' => [
        'name' => 'John',
        'activation_link' => 'https://example.com/activate'
    ]
]);

$response = $client->sendEmail($request);
```

### Send Transactional Email (With Subject & Body Override)

```php
$request = new SendEmailRequest([
    'to' => 'user@example.com',
    'identifiers' => Identifiers::withEmail('user@example.com'),
    'transactional_message_id' => 'WELCOME_EMAIL',
    'subject' => 'Welcome to Our Platform!',
    'body' => '<h1>Welcome!</h1><p>Thank you for joining us.</p>',
    'message_data' => ['name' => 'John']
]);

$response = $client->sendEmail($request);
```

### Send Raw Email (Without Template)

```php
$request = new SendEmailRequest([
    'to' => 'user@example.com',
    'identifiers' => Identifiers::withEmail('user@example.com'),
    'from' => 'no-reply@example.com',
    'subject' => 'Raw Email Test',
    'body' => '<h1>This is a raw HTML email</h1>',
    'plaintext_body' => 'This is a plain text email',
    'reply_to' => 'support@example.com'
]);

$response = $client->sendEmail($request);
```

### Send Push Notification

```php
use Codematic\OpenCDP\SendPushRequest;

$request = new SendPushRequest(
    identifiers: Identifiers::withId('user123'),
    transactional_message_id: 'WELCOME_PUSH',
    title: 'Welcome!',
    body: 'Thank you for joining us!'
);

$response = $client->sendPush($request);
```

### Send Push with Message Data

```php
$request = new SendPushRequest(
    identifiers: Identifiers::withEmail('user@example.com'),
    transactional_message_id: 'ORDER_UPDATE',
    message_data: [
        'order_id' => '12345',
        'tracking_number' => 'TRK123456',
        'items' => [
            ['name' => 'Shoes', 'price' => '59.99']
        ]
    ]
);

$response = $client->sendPush($request);
```

### Send SMS

```php
use Codematic\OpenCDP\SendSmsRequest;

// Using template
$request = new SendSmsRequest(
    identifiers: Identifiers::withId('user123'),
    transactional_message_id: 'WELCOME_SMS',
    message_data: ['name' => 'John']
);

// With body override
$request = new SendSmsRequest(
    identifiers: Identifiers::withId('user123'),
    transactional_message_id: 'WELCOME_SMS',
    body: 'Thank you for joining us!'
);

// Raw SMS (without template)
$request = new SendSmsRequest(
    identifiers: Identifiers::withId('user123'),
    to: '+1234567890',
    from: '+1987654321',
    body: 'This is a raw SMS message'
);

$response = $client->sendSms($request);
```

### Dual-write to Customer.io

```php
$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key',
    sendToCustomerIo: true,
    customerIo: [
        'siteId' => 'your-customer-io-site-id',
        'apiKey' => 'your-customer-io-api-key',
        'region' => 'us' // or 'eu'
    ]
);

$client = new CDPClient($config);

// Now all identify, track, and registerDevice calls will send data to both platforms
// Note: Transactional messages (email/push/SMS) are NOT sent to Customer.io to avoid duplicates
```

## Error Handling

By default, the SDK logs errors but doesn't throw exceptions. You can enable exception throwing:

```php
$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key',
    failOnException: true // Throw exceptions on errors
);

$client = new CDPClient($config);

try {
    $client->identify('user123', ['email' => 'invalid-email']);
} catch (\InvalidArgumentException $e) {
    // Handle validation error
    echo "Validation error: " . $e->getMessage();
} catch (\Codematic\OpenCDP\Exceptions\CDPException $e) {
    // Handle CDP API error
    echo "CDP error: " . $e->getMessage();
    echo "Status: " . $e->status;
    echo "Error Code: " . $e->errorCode;
    print_r($e->summary);
}
```

**Note on Return Types:**

- Methods `identify()`, `track()`, and `registerDevice()` return `void`. When `failOnException` is `false`, errors are logged but no exception is thrown.
- Methods `sendEmail()`, `sendPush()`, and `sendSms()` return `array`. When `failOnException` is `false` and an error occurs, they return an error array with `'ok' => false` and an `'error'` key containing error details.

### Exception Types

- `CDPException` - Base exception for all CDP errors
- `CDPEmailException` - Email sending failures
- `CDPPushException` - Push notification failures
- `CDPSmsException` - SMS sending failures

## Debug Mode

Enable debug logging to see detailed information about SDK operations:

```php
$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key',
    debug: true
);

$client = new CDPClient($config);
```

This will log all requests, responses, and errors using PHP's `error_log()`.

### Custom Logger

Provide your own logger implementation:

```php
use Codematic\OpenCDP\LoggerInterface;

class MyLogger implements LoggerInterface
{
    public function debug(string $message, array $context = []): void
    {
        // Your debug logging logic
    }

    public function error(string $message, array $context = []): void
    {
        // Your error logging logic
    }

    public function warn(string $message, array $context = []): void
    {
        // Your warning logging logic
    }
}

$config = new CDPConfig(
    cdpApiKey: 'your-cdp-api-key',
    logger: new MyLogger()
);
```

## Unsupported Email Fields

Some email fields are accepted by the SDK but not yet processed by the backend. The SDK will log a warning when these are used:

- `send_at` - Scheduled send time
- `send_to_unsubscribed` - Send to unsubscribed users
- `tracked` - Email tracking
- `disable_css_preprocessing` - CSS preprocessing control
- `headers` - Custom email headers
- `disable_message_retention` - Message retention control
- `queue_draft` - Queue as draft
- `fake_bcc` - Fake BCC functionality
- `reply_to` - Reply-to address
- `preheader` - Email preheader text
- `attachments` - Email attachments

These fields are included for future compatibility but currently have no effect on email delivery.

## Testing Connection

Test your CDP connection:

```php
$client->ping(); // Throws exception only if failOnException is true
```

## Development

### Running Tests

```bash
composer test
```

### Code Quality

```bash
# PHP CodeSniffer (PSR-12 standards)
composer phpcs

# Static Analysis
composer phpstan
```

## License

MIT

## Support

For questions and support, visit [https://docs.opencdp.io/](https://docs.opencdp.io/) or contact support@codematic.io
