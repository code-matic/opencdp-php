<?php

declare(strict_types=1);

namespace Codematic\OpenCDP;

/**
 * Default logger implementation using error_log
 */
class DefaultLogger implements LoggerInterface
{
  public function debug(string $message, array $context = []): void
  {
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    error_log("[CDP DEBUG] {$message}{$contextStr}");
  }

  public function error(string $message, array $context = []): void
  {
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    error_log("[CDP ERROR] {$message}{$contextStr}");
  }

  public function warn(string $message, array $context = []): void
  {
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    error_log("[CDP WARN] {$message}{$contextStr}");
  }
}
