<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Tests;

use PHPUnit\Framework\TestCase;
use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;
use Codematic\OpenCDP\Identifiers;
use Codematic\OpenCDP\SendWhatsAppRequest;
use Codematic\OpenCDP\Exceptions\CDPWhatsAppException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;

class CDPClientWhatsAppTest extends TestCase
{
  /** @var array<int, array<string, mixed>> */
  private array $history = [];

  private function createClient(array $responses, bool $failOnException = true): CDPClient
  {
    $handlerStack = HandlerStack::create(new MockHandler($responses));
    $handlerStack->push(Middleware::history($this->history));

    $client = new CDPClient(new CDPConfig(
      cdpApiKey: 'test-api-key',
      cdpEndpoint: 'https://primary.test.com',
      cdpFallbackEndpoints: [],
      failOnException: $failOnException
    ));
    $reflection = new \ReflectionClass($client);
    $reflection->getProperty('httpClient')->setValue($client, new Client(['handler' => $handlerStack]));
    $reflection->getProperty('baseUrls')->setValue($client, ['https://primary.test.com', 'https://fallback.test.com']);

    return $client;
  }

  private function request(): SendWhatsAppRequest
  {
    return new SendWhatsAppRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 'ORDER_WHATSAPP'
    );
  }

  /** @return string[] */
  private function requestedHosts(): array
  {
    return array_map(fn($entry) => $entry['request']->getUri()->getHost(), $this->history);
  }

  public function testEncodesEmptyArraysAsObjectsAndIdAsString(): void
  {
    $client = $this->createClient([new Response(200, [], json_encode(['data' => ['id' => 'trx_1']]))]);

    $response = $client->sendWhatsApp(new SendWhatsAppRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 0,
      template_variables: ['body' => []],
      message_data: []
    ));

    $this->assertSame('trx_1', $response['data']['id']);
    $this->assertSame(
      '{"identifiers":{"id":"user123"},"transactional_message_id":"0","template_variables":{"body":{}},"message_data":{}}',
      (string) $this->history[0]['request']->getBody()
    );
  }

  public function testDoesNotFailOverOnClientError(): void
  {
    $client = $this->createClient([
      new Response(400, [], json_encode(['message' => 'Transactional message with id X not found'])),
      new Response(200),
    ]);

    try {
      $client->sendWhatsApp($this->request());
      $this->fail('Expected CDPWhatsAppException');
    } catch (CDPWhatsAppException $e) {
      $this->assertSame(400, $e->status);
      $this->assertSame('Transactional message with id X not found', $e->getMessage());
    }
    $this->assertSame(['primary.test.com'], $this->requestedHosts());
  }

  public function testFailsOverOn503(): void
  {
    $client = $this->createClient([new Response(503), new Response(200, [], '{}')]);

    $client->sendWhatsApp($this->request());

    $this->assertSame(['primary.test.com', 'fallback.test.com'], $this->requestedHosts());
  }

  public function testFailsOverWhenConnectionIsRefused(): void
  {
    $client = $this->createClient([
      new ConnectException('Failed to connect', new GuzzleRequest('POST', '/'), null, ['errno' => 7]),
      new Response(200, [], '{}'),
    ]);

    $client->sendWhatsApp($this->request());

    $this->assertSame(['primary.test.com', 'fallback.test.com'], $this->requestedHosts());
  }

  public function testDoesNotFailOverAfterTimeoutAndReportsStatusZero(): void
  {
    $client = $this->createClient([
      new ConnectException('Operation timed out', new GuzzleRequest('POST', '/'), null, ['errno' => 28]),
      new Response(200, [], '{}'),
    ]);

    try {
      $client->sendWhatsApp($this->request());
      $this->fail('Expected CDPWhatsAppException');
    } catch (CDPWhatsAppException $e) {
      $this->assertSame(0, $e->status);
      $this->assertSame('Operation timed out', $e->getMessage());
    }
  }

  public function testReturnsErrorArrayWhenFailOnExceptionIsFalse(): void
  {
    $client = $this->createClient([new Response(500, [], json_encode(['message' => 'boom']))], false);

    $response = $client->sendWhatsApp($this->request());

    $this->assertFalse($response['ok']);
    $this->assertSame(500, $response['error']['status']);
  }

  /**
   * @dataProvider invalidRequests
   */
  public function testRejectsInvalidRequestsBeforeSending(SendWhatsAppRequest $request, string $message): void
  {
    $client = $this->createClient([]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    $client->sendWhatsApp($request);
  }

  public static function invalidRequests(): array
  {
    $id = Identifiers::withId('user123');
    return [
      'list as body slots' => [
        new SendWhatsAppRequest($id, 'T', template_variables: ['body' => ['Jane', 'ORD-1']]),
        'template_variables.body keys must be positional slot numbers',
      ],
      'named slot' => [
        new SendWhatsAppRequest($id, 'T', template_variables: ['header' => ['name' => 'Jane']]),
        'template_variables.header keys must be positional slot numbers',
      ],
      'list as message_data' => [
        new SendWhatsAppRequest($id, 'T', message_data: ['a', 'b']),
        'message_data must be an associative array',
      ],
      'blank transactional id' => [
        new SendWhatsAppRequest($id, '  '),
        'transactional_message_id is required',
      ],
    ];
  }
}
