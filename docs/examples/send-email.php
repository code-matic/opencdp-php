<?php

/**
 * Email sending examples for OpenCDP PHP SDK
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;
use Codematic\OpenCDP\Identifiers;
use Codematic\OpenCDP\SendEmailRequest;

// Create client
$config = new CDPConfig(cdpApiKey: 'your-cdp-api-key', debug: true);
$client = new CDPClient($config);

// Example 1: Template-based email (without body override)
echo "Sending template-based email...\n";
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
print_r($response);

// Example 2: Template-based email with body and subject override
echo "\nSending template email with overrides...\n";
$request = new SendEmailRequest([
  'to' => 'user@example.com',
  'identifiers' => Identifiers::withId('user123'),
  'transactional_message_id' => 'WELCOME_EMAIL',
  'subject' => 'Welcome to Our Platform!',
  'body' => '<h1>Welcome!</h1><p>Thank you for joining us.</p>',
  'message_data' => ['name' => 'John']
]);
$response = $client->sendEmail($request);
print_r($response);

// Example 3: Raw email (without template)
echo "\nSending raw email...\n";
$request = new SendEmailRequest([
  'to' => 'user@example.com',
  'identifiers' => Identifiers::withEmail('user@example.com'),
  'from' => 'no-reply@example.com',
  'subject' => 'Raw Email Test',
  'body' => '<h1>This is a raw HTML email</h1><p>Sent via OpenCDP PHP SDK</p>',
  'plaintext_body' => 'This is a plain text email',
  'reply_to' => 'support@example.com'
]);
$response = $client->sendEmail($request);
print_r($response);

echo "\nDone!\n";
