<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Tests;

use PHPUnit\Framework\TestCase;
use Codematic\OpenCDP\CDPClient;
use Codematic\OpenCDP\CDPConfig;
use Codematic\OpenCDP\Identifiers;
use Codematic\OpenCDP\SendEmailRequest;
use Codematic\OpenCDP\Validators;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class EmailAttachmentsTest extends TestCase
{
  private const PDF_CONTENT = '%PDF-1.4 test';

  /** @var array<int, array<string, mixed>> */
  private array $history = [];

  private function createClient(bool $failOnException): CDPClient
  {
    $this->history = [];
    $handlerStack = HandlerStack::create(new MockHandler([new Response(200, [], '{"ok":true}')]));
    $handlerStack->push(Middleware::history($this->history));

    $client = new CDPClient(new CDPConfig(
      cdpApiKey: 'test-api-key',
      cdpEndpoint: 'https://api.test.com',
      cdpFallbackEndpoints: [],
      debug: false,
      failOnException: $failOnException
    ));
    $reflection = new \ReflectionClass($client);
    $reflection->getProperty('httpClient')->setValue($client, new Client(['handler' => $handlerStack]));
    $reflection->getProperty('baseUrls')->setValue($client, ['https://api.test.com']);

    return $client;
  }

  /**
   * @param array<mixed, mixed>|null $attachments
   */
  private function emailRequest(?array $attachments = null): SendEmailRequest
  {
    return new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withId('user123'),
      'transactional_message_id' => 'INVOICE_EMAIL',
      'attachments' => $attachments,
    ]);
  }

  public function testSendEmailIncludesAttachmentsInPayload(): void
  {
    $client = $this->createClient(true);
    $pdfBase64 = base64_encode(self::PDF_CONTENT);

    $response = $client->sendEmail($this->emailRequest(['invoice.pdf' => $pdfBase64]));

    $this->assertTrue($response['ok'] ?? false);
    $this->assertCount(1, $this->history);
    $body = json_decode((string) $this->history[0]['request']->getBody(), true);
    $this->assertSame(['invoice.pdf' => $pdfBase64], $body['attachments']);
  }

  public function testSendEmailDoesNotSendInvalidAttachmentsWhenNotFailingOnException(): void
  {
    $client = $this->createClient(false);
    $attachments = [];
    for ($i = 0; $i < 6; $i++) {
      $attachments["file{$i}.txt"] = base64_encode(self::PDF_CONTENT);
    }

    $response = $client->sendEmail($this->emailRequest($attachments));

    $this->assertFalse($response['ok']);
    $this->assertSame('attachments may contain at most 5 files', $response['error']);
    $this->assertCount(0, $this->history);
  }

  /**
   * @return array<string, array{0: array<mixed, mixed>, 1: string}>
   */
  public static function invalidAttachmentsProvider(): array
  {
    $pdf = base64_encode(self::PDF_CONTENT);
    $sixFiles = [];
    for ($i = 0; $i < 6; $i++) {
      $sixFiles["file{$i}.txt"] = $pdf;
    }

    return [
      'too many files' => [$sixFiles, 'attachments may contain at most 5 files'],
      'over 2 MB decoded' => [
        ['big.bin' => base64_encode(str_repeat("\0", 2 * 1024 * 1024 + 1))],
        'attachments decoded size exceeds 2097152 bytes (2 MB)',
      ],
      'path traversal' => [['../secret.pdf' => $pdf], 'invalid attachment filename: ../secret.pdf'],
      'forward slash' => [['dir/file.pdf' => $pdf], 'invalid attachment filename: dir/file.pdf'],
      'backslash' => [['dir\\file.pdf' => $pdf], 'invalid attachment filename: dir\\file.pdf'],
      'empty filename' => [['' => $pdf], 'invalid attachment filename: (empty)'],
      'empty content' => [['a.pdf' => ''], 'attachment "a.pdf" must be a non-empty base64 string'],
      'non-string content' => [['a.pdf' => 123], 'attachment "a.pdf" must be a non-empty base64 string'],
      'not base64' => [['a.pdf' => '!!!'], 'attachment "a.pdf" must be a valid base64 string'],
    ];
  }

  /**
   * @dataProvider invalidAttachmentsProvider
   * @param array<mixed, mixed> $attachments
   */
  public function testValidateAttachmentsRejectsInvalidInput(array $attachments, string $message): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);

    Validators::validateSendEmailRequest($this->emailRequest($attachments));
  }

  public function testValidateAttachmentsAcceptsUnpaddedAndUrlSafeBase64(): void
  {
    $this->expectNotToPerformAssertions();

    Validators::validateAttachments([
      'unpadded.txt' => 'aGVsbG8',
      'urlsafe.bin' => '-_8=',
      'wrapped.txt' => "aGVs\nbG8=",
    ]);
  }

  public function testWithAttachmentEncodesByDefaultAndKeepsOriginalUnchanged(): void
  {
    $original = $this->emailRequest();

    $withAttachment = $original->withAttachment('notes.txt', 'hello');

    $this->assertNull($original->attachments);
    $this->assertSame(['notes.txt' => 'aGVsbG8='], $withAttachment->attachments);
    $this->assertSame('INVOICE_EMAIL', $withAttachment->transactional_message_id);
    $this->assertSame('user123', $withAttachment->identifiers->id);
  }

  public function testWithAttachmentKeepsContentAsIsWhenEncodeIsFalse(): void
  {
    $request = $this->emailRequest()->withAttachment('notes.txt', 'aGVsbG8=', false);

    $this->assertSame(['notes.txt' => 'aGVsbG8='], $request->attachments);
  }

  public function testWithAttachmentMergesWithExistingAttachments(): void
  {
    $request = $this->emailRequest(['a.txt' => 'YQ=='])->withAttachment('b.txt', 'b');

    $this->assertSame(['a.txt' => 'YQ==', 'b.txt' => 'Yg=='], $request->attachments);
  }

  public function testWithAttachmentPreservesOtherFields(): void
  {
    $original = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withEmail('user@example.com'),
      'from' => 'sender@example.com',
      'subject' => 'Invoice',
      'body' => '<h1>Invoice</h1>',
      'plaintext_body' => 'Invoice',
      'amp_body' => '<amp>Invoice</amp>',
      'cc' => ['cc@example.com'],
    ]);

    $copy = $original->withAttachment('invoice.pdf', self::PDF_CONTENT);

    $expected = $original->toArray();
    $expected['attachments'] = ['invoice.pdf' => base64_encode(self::PDF_CONTENT)];
    $this->assertSame($expected, $copy->toArray());
  }

  public function testWithAttachmentFileReadsAndEncodesFile(): void
  {
    $path = sys_get_temp_dir() . '/opencdp-test-invoice-' . uniqid() . '.pdf';
    file_put_contents($path, self::PDF_CONTENT);

    try {
      $request = $this->emailRequest()
        ->withAttachmentFile($path)
        ->withAttachmentFile($path, 'copy.pdf');
    } finally {
      unlink($path);
    }

    $this->assertSame(
      [basename($path) => base64_encode(self::PDF_CONTENT), 'copy.pdf' => base64_encode(self::PDF_CONTENT)],
      $request->attachments
    );
  }

  public function testWithAttachmentFileThrowsWhenFileIsMissing(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot read attachment file');

    $this->emailRequest()->withAttachmentFile(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.pdf');
  }
}
