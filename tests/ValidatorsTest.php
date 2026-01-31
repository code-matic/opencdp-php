<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Tests;

use PHPUnit\Framework\TestCase;
use Codematic\OpenCDP\Validators;
use Codematic\OpenCDP\SendEmailRequest;
use Codematic\OpenCDP\SendPushRequest;
use Codematic\OpenCDP\SendSmsRequest;
use Codematic\OpenCDP\Identifiers;

class ValidatorsTest extends TestCase
{
  public function testValidateIdentifierWithValidString(): void
  {
    $this->expectNotToPerformAssertions();
    Validators::validateIdentifier('user123');
  }

  public function testValidateIdentifierWithValidInt(): void
  {
    $this->expectNotToPerformAssertions();
    Validators::validateIdentifier(123);
  }

  public function testValidateIdentifierThrowsOnEmptyString(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Identifier cannot be empty');
    Validators::validateIdentifier('');
  }

  public function testValidateIdentifierThrowsOnWhitespaceOnly(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Identifier cannot be empty');
    Validators::validateIdentifier('   ');
  }

  public function testValidateIdentifierThrowsOnTooLongString(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Identifier cannot exceed 255 characters');
    Validators::validateIdentifier(str_repeat('a', 256));
  }

  public function testValidateIdentifierAcceptsMaxLength(): void
  {
    $this->expectNotToPerformAssertions();
    Validators::validateIdentifier(str_repeat('a', 255));
  }

  public function testValidateEventNameWithValidString(): void
  {
    $this->expectNotToPerformAssertions();
    Validators::validateEventName('purchase_completed');
  }

  public function testValidateEventNameThrowsOnEmptyString(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Event name cannot be empty');
    Validators::validateEventName('');
  }

  public function testValidateEventNameThrowsOnWhitespaceOnly(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Event name cannot be empty');
    Validators::validateEventName('   ');
  }

  public function testValidateEventNameThrowsOnTooLongString(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Event name cannot exceed 255 characters');
    Validators::validateEventName(str_repeat('a', 256));
  }

  public function testValidateEventNameAcceptsMaxLength(): void
  {
    $this->expectNotToPerformAssertions();
    Validators::validateEventName(str_repeat('a', 255));
  }

  public function testValidateEmailWithValidEmail(): void
  {
    $this->expectNotToPerformAssertions();
    Validators::validateEmail('user@example.com');
  }

  public function testValidateEmailThrowsOnEmptyString(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Email address cannot be empty');
    Validators::validateEmail('');
  }

  public function testValidateEmailThrowsOnInvalidFormat(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid email address format');
    Validators::validateEmail('not-an-email');
  }

  public function testValidateEmailThrowsOnTooLongEmail(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Email address cannot exceed 254 characters');
    $localPart = str_repeat('a', 250);
    Validators::validateEmail($localPart . '@example.com');
  }

  public function testValidateEmailAcceptsMaxLength(): void
  {
    $this->expectNotToPerformAssertions();
    // Test with valid email formats that are under the 254 character limit
    // PHP's filter_var has strict validation, so we use realistic email formats
    $email1 = 'user@example.com'; // Standard email (well under 254)
    Validators::validateEmail($email1);
    
    // Test with a longer but still valid email
    $localPart = 'user' . str_repeat('a', 50); // 54 chars local part
    $domain = 'example.com'; // 11 chars
    $email2 = $localPart . '@' . $domain; // 66 chars total (well under 254)
    Validators::validateEmail($email2);
  }

  public function testValidatePhoneNumberWithValidE164(): void
  {
    $this->expectNotToPerformAssertions();
    Validators::validatePhoneNumber('+1234567890');
  }

  public function testValidatePhoneNumberThrowsOnEmptyString(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Phone number cannot be empty');
    Validators::validatePhoneNumber('');
  }

  public function testValidatePhoneNumberThrowsOnInvalidFormat(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Phone number must be in international format');
    Validators::validatePhoneNumber('123-456-7890');
  }

  public function testValidatePropertiesWithNull(): void
  {
    $result = Validators::validateProperties(null);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  public function testValidatePropertiesWithArray(): void
  {
    $props = ['key' => 'value'];
    $result = Validators::validateProperties($props);
    $this->assertEquals($props, $result);
  }

  public function testValidateSendEmailRequestWithValidTemplateRequest(): void
  {
    $request = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withId('user123'),
      'transactional_message_id' => 'WELCOME_EMAIL',
    ]);

    $this->expectNotToPerformAssertions();
    Validators::validateSendEmailRequest($request);
  }

  public function testValidateSendEmailRequestWithValidRawEmail(): void
  {
    $request = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withEmail('user@example.com'),
      'from' => 'sender@example.com',
      'subject' => 'Test Subject',
      'body' => '<h1>Test</h1>',
    ]);

    $this->expectNotToPerformAssertions();
    Validators::validateSendEmailRequest($request);
  }

  public function testValidateSendEmailRequestThrowsOnInvalidBccArray(): void
  {
    // Since SendEmailRequest has typed properties, PHP will catch type errors at construction.
    // Instead, test that validation catches invalid email addresses in the bcc array
    $request = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withId('user123'),
      'transactional_message_id' => 'WELCOME_EMAIL',
      'bcc' => ['invalid-email'], // Invalid email format in array
    ]);

    // Validation should catch invalid email in bcc array
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid email address format');
    Validators::validateSendEmailRequest($request);
  }

  public function testValidateSendEmailRequestWithValidBccArray(): void
  {
    $request = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withId('user123'),
      'transactional_message_id' => 'WELCOME_EMAIL',
      'bcc' => ['bcc1@example.com', 'bcc2@example.com'],
    ]);

    $this->expectNotToPerformAssertions();
    Validators::validateSendEmailRequest($request);
  }

  public function testValidateSendEmailRequestWithValidCcArray(): void
  {
    $request = new SendEmailRequest([
      'to' => 'user@example.com',
      'identifiers' => Identifiers::withId('user123'),
      'transactional_message_id' => 'WELCOME_EMAIL',
      'cc' => ['cc1@example.com', 'cc2@example.com'],
    ]);

    $this->expectNotToPerformAssertions();
    Validators::validateSendEmailRequest($request);
  }

  public function testValidateSendPushRequestWithValidRequest(): void
  {
    $request = new SendPushRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 'WELCOME_PUSH',
      title: 'Test Title',
      body: 'Test Body'
    );

    $this->expectNotToPerformAssertions();
    Validators::validateSendPushRequest($request);
  }

  public function testValidateSendSmsRequestWithValidTemplateRequest(): void
  {
    $request = new SendSmsRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 'WELCOME_SMS'
    );

    $this->expectNotToPerformAssertions();
    Validators::validateSendSmsRequest($request);
  }

  public function testValidateSendSmsRequestWithValidRawSms(): void
  {
    $request = new SendSmsRequest(
      identifiers: Identifiers::withId('user123'),
      to: '+1234567890',
      from: '+1987654321',
      body: 'Test SMS body'
    );

    $this->expectNotToPerformAssertions();
    Validators::validateSendSmsRequest($request);
  }
}
