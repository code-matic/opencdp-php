<?php

/**
 * Basic usage example for OpenCDP PHP SDK
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;

// Create configuration
$apiKey = getenv('CDP_API_KEY') ?: 'your-cdp-api-key';
if ($apiKey === 'your-cdp-api-key') {
  fwrite(STDERR, "ERROR: Set CDP_API_KEY or replace the placeholder key in this script.\n");
  exit(1);
}

$config = new CDPConfig(
  cdpApiKey: $apiKey,
  debug: true // Enable debug logging
);

// Create client
$client = new CDPClient($config);

// Test connection
echo "Testing connection...\n";
$client->ping();
echo "Connection successful!\n\n";

// Identify a user
echo "Identifying user...\n";
$userId = getenv('CDP_SMOKE_USER_ID') ?: 'flutter_dev_test_001';
$client->identify($userId, [
  'email' => 'john.doe@example.com',
  'name' => 'John Doe',
  'plan' => 'premium',
  'created_at' => time()
]);
echo "User identified!\n\n";

// Track an event
echo "Tracking event...\n";
$client->track($userId, 'sdk_smoke_test', [
  'amount' => 99.99,
  'currency' => 'USD',
  'item_id' => 'prod-123',
  'category' => 'electronics'
]);
echo "Event tracked!\n\n";

echo "Done!\n";
