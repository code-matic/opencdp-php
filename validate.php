<?php

/**
 * Simple validation script to check PHP syntax
 */

echo "OpenCDP PHP SDK - Syntax Validation\n";
echo str_repeat('=', 50) . "\n\n";

$srcDir = __DIR__ . '/src';
$files = [
  'CDPClient.php',
  'CDPConfig.php',
  'DefaultLogger.php',
  'LoggerInterface.php',
  'Types.php',
  'Validators.php',
  'Exceptions/CDPException.php',
  'Exceptions/CDPEmailException.php',
  'Exceptions/CDPPushException.php',
  'Exceptions/CDPSmsException.php',
];

$errors = [];
$success = 0;

foreach ($files as $file) {
  $filepath = $srcDir . '/' . $file;
  echo "Checking {$file}... ";

  if (!file_exists($filepath)) {
    echo "MISSING\n";
    $errors[] = "{$file}: File not found";
    continue;
  }

  // Check syntax
  $output = [];
  $returnCode = 0;
  exec("php -l " . escapeshellarg($filepath) . " 2>&1", $output, $returnCode);

  if ($returnCode === 0) {
    echo "OK\n";
    $success++;
  } else {
    echo "ERROR\n";
    $errors[] = "{$file}: " . implode("\n", $output);
  }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Results: {$success} OK, " . count($errors) . " errors\n";

if (!empty($errors)) {
  echo "\nErrors:\n";
  foreach ($errors as $error) {
    echo "  - {$error}\n";
  }
  exit(1);
}

echo "\n✓ All files validated successfully!\n";
exit(0);
