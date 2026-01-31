<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Tests;

use PHPUnit\Framework\TestCase;
use Codematic\OpenCDP\CDPConfig;
use Codematic\OpenCDP\DefaultLogger;

class CDPConfigTest extends TestCase
{
  public function testCDPConfigWithMinimalRequiredParams(): void
  {
    $config = new CDPConfig(cdpApiKey: 'test-api-key');
    $this->assertEquals('test-api-key', $config->cdpApiKey);
    $this->assertEquals('https://api.opencdp.io/gateway/data-gateway/', $config->cdpEndpoint);
    $this->assertEquals(10000, $config->timeout);
    $this->assertFalse($config->debug);
    $this->assertFalse($config->failOnException);
    $this->assertInstanceOf(DefaultLogger::class, $config->logger);
  }

  public function testCDPConfigThrowsOnEmptyApiKey(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('cdpApiKey cannot be empty');
    new CDPConfig(cdpApiKey: '');
  }

  public function testCDPConfigThrowsOnWhitespaceOnlyApiKey(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('cdpApiKey cannot be empty');
    new CDPConfig(cdpApiKey: '   ');
  }

  public function testCDPConfigWithCustomEndpoint(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      cdpEndpoint: 'https://custom.endpoint.com'
    );
    $this->assertEquals('https://custom.endpoint.com', $config->cdpEndpoint);
  }

  public function testCDPConfigWithCustomTimeout(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      timeout: 5000
    );
    $this->assertEquals(5000, $config->timeout);
  }

  public function testCDPConfigEnforcesMinimumTimeout(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      timeout: 500 // Less than 1000ms minimum
    );
    $this->assertEquals(1000, $config->timeout);
  }

  public function testCDPConfigWithDebugEnabled(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      debug: true
    );
    $this->assertTrue($config->debug);
  }

  public function testCDPConfigWithFailOnExceptionEnabled(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      failOnException: true
    );
    $this->assertTrue($config->failOnException);
  }

  public function testCDPConfigWithCustomLogger(): void
  {
    $customLogger = new DefaultLogger();
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      logger: $customLogger
    );
    $this->assertSame($customLogger, $config->logger);
  }

  public function testCDPConfigWithCustomerIoDisabled(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      sendToCustomerIo: false
    );
    $this->assertFalse($config->sendToCustomerIo);
    $this->assertNull($config->customerIo);
  }

  public function testCDPConfigWithCustomerIoEnabled(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      sendToCustomerIo: true,
      customerIo: [
        'siteId' => 'test-site-id',
        'apiKey' => 'test-api-key',
        'region' => 'us'
      ]
    );
    $this->assertTrue($config->sendToCustomerIo);
    $this->assertNotNull($config->customerIo);
    $this->assertEquals('test-site-id', $config->customerIo['siteId']);
    $this->assertEquals('test-api-key', $config->customerIo['apiKey']);
    $this->assertEquals('us', $config->customerIo['region']);
  }

  public function testCDPConfigCustomerIoDefaultsToUsRegion(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      sendToCustomerIo: true,
      customerIo: [
        'siteId' => 'test-site-id',
        'apiKey' => 'test-api-key'
      ]
    );
    $this->assertEquals('us', $config->customerIo['region']);
  }

  public function testCDPConfigThrowsWhenCustomerIoEnabledButConfigMissing(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('customerIo configuration is required when sendToCustomerIo is true');
    new CDPConfig(
      cdpApiKey: 'test-api-key',
      sendToCustomerIo: true
    );
  }

  public function testCDPConfigThrowsWhenCustomerIoSiteIdMissing(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('customerIo.siteId and customerIo.apiKey are required');
    new CDPConfig(
      cdpApiKey: 'test-api-key',
      sendToCustomerIo: true,
      customerIo: [
        'apiKey' => 'test-api-key'
      ]
    );
  }

  public function testCDPConfigThrowsWhenCustomerIoApiKeyMissing(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('customerIo.siteId and customerIo.apiKey are required');
    new CDPConfig(
      cdpApiKey: 'test-api-key',
      sendToCustomerIo: true,
      customerIo: [
        'siteId' => 'test-site-id'
      ]
    );
  }
}
