<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Tests;

use PHPUnit\Framework\TestCase;
use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;
use Codematic\OpenCDP\Identifiers;
use Codematic\OpenCDP\SendEmailRequest;
use Codematic\OpenCDP\SendPushRequest;
use Codematic\OpenCDP\SendSmsRequest;
use Codematic\OpenCDP\DeviceRegistrationParameters;
use Codematic\OpenCDP\Exceptions\CDPException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class CDPClientTest extends TestCase
{
  private function createMockClient(array $responses): CDPClient
  {
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handlerStack]);

    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      cdpEndpoint: 'https://api.test.com',
      debug: false,
      failOnException: false
    );

    $client = new CDPClient($config);
    // Use reflection to replace the HTTP client
    $reflection = new \ReflectionClass($client);
    $property = $reflection->getProperty('httpClient');
    $property->setValue($client, $guzzleClient);

    return $client;
  }

  public function testIdentifyWithValidData(): void
  {
    $client = $this->createMockClient([
      new Response(200, [], json_encode(['ok' => true])),
    ]);

    $this->expectNotToPerformAssertions();
    $client->identify('user123', ['email' => 'user@example.com']);
  }

  public function testIdentifyThrowsOnInvalidIdentifierWhenFailOnException(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      failOnException: true
    );
    $client = new CDPClient($config);

    $this->expectException(\InvalidArgumentException::class);
    $client->identify('', ['email' => 'user@example.com']);
  }

  public function testTrackWithValidData(): void
  {
    $client = $this->createMockClient([
      new Response(200, [], json_encode(['ok' => true])),
    ]);

    $this->expectNotToPerformAssertions();
    $client->track('user123', 'purchase_completed', ['amount' => 99.99]);
  }

  public function testRegisterDeviceWithValidData(): void
  {
    $client = $this->createMockClient([
      new Response(200, [], json_encode(['ok' => true])),
    ]);

    $params = new DeviceRegistrationParameters(
      deviceId: 'device-123',
      platform: 'ios',
      fcmToken: 'fcm-token-123'
    );

    $this->expectNotToPerformAssertions();
    $client->registerDevice('user123', $params);
  }

  public function testSendEmailWithValidRequest(): void
  {
    $client = $this->createMockClient([
      new Response(200, [], json_encode(['ok' => true, 'message_id' => 'msg-123'])),
    ]);

    $request = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withId('user123'),
      'transactional_message_id' => 'WELCOME_EMAIL',
    ]);

    $response = $client->sendEmail($request);
    $this->assertIsArray($response);
    $this->assertTrue($response['ok'] ?? false);
  }

  public function testSendEmailReturnsErrorArrayOnFailure(): void
  {
    $client = $this->createMockClient([
      new RequestException('Error', new GuzzleRequest('POST', '/v1/send/email'), new Response(400, [], json_encode(['message' => 'Invalid request']))),
    ]);

    $request = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withId('user123'),
      'transactional_message_id' => 'WELCOME_EMAIL',
    ]);

    $response = $client->sendEmail($request);
    $this->assertIsArray($response);
    $this->assertFalse($response['ok'] ?? true);
    $this->assertArrayHasKey('error', $response);
  }

  public function testSendPushWithValidRequest(): void
  {
    $client = $this->createMockClient([
      new Response(200, [], json_encode(['ok' => true, 'message_id' => 'msg-123'])),
    ]);

    $request = new SendPushRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 'WELCOME_PUSH',
      title: 'Test Title',
      body: 'Test Body'
    );

    $response = $client->sendPush($request);
    $this->assertIsArray($response);
    $this->assertTrue($response['ok'] ?? false);
  }

  public function testSendSmsWithValidRequest(): void
  {
    $client = $this->createMockClient([
      new Response(200, [], json_encode(['ok' => true, 'message_id' => 'msg-123'])),
    ]);

    $request = new SendSmsRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 'WELCOME_SMS'
    );

    $response = $client->sendSms($request);
    $this->assertIsArray($response);
    $this->assertTrue($response['ok'] ?? false);
  }

  public function testPingWithSuccessfulConnection(): void
  {
    $client = $this->createMockClient([
      new Response(200, [], json_encode(['status' => 'ok'])),
    ]);

    $this->expectNotToPerformAssertions();
    $client->ping();
  }

  public function testPingThrowsOnFailureWhenFailOnException(): void
  {
    $config = new CDPConfig(
      cdpApiKey: 'test-api-key',
      cdpEndpoint: 'https://api.test.com',
      failOnException: true
    );

    $mock = new MockHandler([
      new RequestException('Connection failed', new GuzzleRequest('GET', '/v1/health/ping')),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new Client(['handler' => $handlerStack]);

    $client = new CDPClient($config);
    $reflection = new \ReflectionClass($client);
    $property = $reflection->getProperty('httpClient');
    $property->setValue($client, $guzzleClient);

    $this->expectException(CDPException::class);
    $client->ping();
  }
}
