<?php

declare(strict_types=1);

namespace Codematic\OpenCDP;

final class GatewayUrls
{
  public const DEFAULT_PRIMARY = 'https://api.opencdp.io/gateway/data-gateway';

  public const DEFAULT_FALLBACKS = [
    'https://api.opencdp.com/gateway/data-gateway',
    'https://api.opencdp.xyz/gateway/data-gateway',
  ];

  public static function normalizeBaseUrl(string $url): string
  {
    $trimmed = trim($url);
    if ($trimmed === '') {
      return $trimmed;
    }
    return rtrim($trimmed, '/');
  }

  /**
   * @param list<string>|null $fallbackOverrides
   * @return list<string>
   */
  public static function resolveAllBaseUrls(string $primaryOverride = '', ?array $fallbackOverrides = null): array
  {
    $primary = self::normalizeBaseUrl($primaryOverride !== '' ? $primaryOverride : self::DEFAULT_PRIMARY);
    $fallbacks = $fallbackOverrides ?? self::DEFAULT_FALLBACKS;
    $seen = [];
    $ordered = [];

    $add = static function (string $url) use (&$seen, &$ordered): void {
      $normalized = self::normalizeBaseUrl($url);
      if ($normalized === '' || isset($seen[$normalized])) {
        return;
      }
      $seen[$normalized] = true;
      $ordered[] = $normalized;
    };

    $add($primary);
    foreach ($fallbacks as $fallback) {
      $add($fallback);
    }

    return $ordered;
  }
}
