<?php

declare(strict_types=1);

namespace Codematic\OpenCDP;

/**
 * Configuration class for CDP Client
 */
class CDPConfig
{
  /** @var string API key for CDP authentication */
  public readonly string $cdpApiKey;

  /** @var string Base URL for the OpenCDP API */
  public readonly string $cdpEndpoint;

  /** @var int Request timeout in milliseconds */
  public readonly int $timeout;

  /** @var bool Enable debug logging */
  public readonly bool $debug;

  /** @var bool Throw exceptions on errors */
  public readonly bool $failOnException;

  /** @var LoggerInterface Logger instance */
  public readonly LoggerInterface $logger;

  /** @var bool Send events to Customer.io */
  public readonly bool $sendToCustomerIo;

  /** @var array{siteId: string, apiKey: string, region: string}|null Customer.io configuration */
  public readonly ?array $customerIo;

  /**
   * @param string $cdpApiKey Required API key for CDP
   * @param string $cdpEndpoint Optional custom endpoint
   * @param int $timeout Request timeout in milliseconds (default: 10000)
   * @param bool $debug Enable debug logging (default: false)
   * @param bool $failOnException Throw exceptions on errors (default: false)
   * @param LoggerInterface|null $logger Custom logger instance
   * @param bool $sendToCustomerIo Enable dual-write to Customer.io (default: false)
   * @param array{siteId: string, apiKey: string, region?: string}|null $customerIo Customer.io configuration
   */
  public function __construct(
    string $cdpApiKey,
    string $cdpEndpoint = 'https://api.opencdp.io/gateway/data-gateway/',
    int $timeout = 10000,
    bool $debug = false,
    bool $failOnException = false,
    ?LoggerInterface $logger = null,
    bool $sendToCustomerIo = false,
    ?array $customerIo = null
  ) {
    if (empty(trim($cdpApiKey))) {
      throw new \InvalidArgumentException('cdpApiKey cannot be empty');
    }

    $this->cdpApiKey = $cdpApiKey;
    $this->cdpEndpoint = $cdpEndpoint;
    $this->timeout = max(1000, $timeout); // Minimum 1 second
    $this->debug = $debug;
    $this->failOnException = $failOnException;
    $this->logger = $logger ?? new DefaultLogger();
    $this->sendToCustomerIo = $sendToCustomerIo;

    // Validate Customer.io config if dual-write is enabled
    if ($sendToCustomerIo) {
      if ($customerIo === null) {
        throw new \InvalidArgumentException('customerIo configuration is required when sendToCustomerIo is true');
      }
      if (empty($customerIo['siteId']) || empty($customerIo['apiKey'])) {
        throw new \InvalidArgumentException('customerIo.siteId and customerIo.apiKey are required');
      }
      $customerIo['region'] = $customerIo['region'] ?? 'us';
    }

    $this->customerIo = $customerIo;
  }
}
