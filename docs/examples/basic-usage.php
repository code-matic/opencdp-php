<?php

/**
 * Basic usage example for OpenCDP PHP SDK
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;

// Create configuration
$config = new CDPConfig(
  cdpApiKey: 'your-cdp-api-key',
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
$client->identify('user123', [
  'email' => 'john.doe@example.com',
  'name' => 'John Doe',
  'plan' => 'premium',
  'created_at' => time()
]);
echo "User identified!\n\n";

// Track an event
echo "Tracking event...\n";
$client->track('user123', 'purchase_completed', [
  'amount' => 99.99,
  'currency' => 'USD',
  'item_id' => 'prod-123',
  'category' => 'electronics'
]);
echo "Event tracked!\n\n";

echo "Done!\n";
