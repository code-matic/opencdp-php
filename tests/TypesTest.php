<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Tests;

use PHPUnit\Framework\TestCase;
use Codematic\OpenCDP\Identifiers;
use Codematic\OpenCDP\DeviceRegistrationParameters;
use Codematic\OpenCDP\SendEmailRequest;
use Codematic\OpenCDP\SendPushRequest;
use Codematic\OpenCDP\SendSmsRequest;

class TypesTest extends TestCase
{
  public function testIdentifiersWithId(): void
  {
    $identifiers = Identifiers::withId('user123');
    $this->assertEquals('user123', $identifiers->id);
    $this->assertNull($identifiers->email);
    $this->assertNull($identifiers->cdp_id);
  }

  public function testIdentifiersWithIdAsInt(): void
  {
    $identifiers = Identifiers::withId(123);
    $this->assertEquals('123', $identifiers->id);
  }

  public function testIdentifiersWithEmail(): void
  {
    $identifiers = Identifiers::withEmail('user@example.com');
    $this->assertEquals('user@example.com', $identifiers->email);
    $this->assertNull($identifiers->id);
    $this->assertNull($identifiers->cdp_id);
  }

  public function testIdentifiersWithCdpId(): void
  {
    $identifiers = Identifiers::withCdpId('cdp-123');
    $this->assertEquals('cdp-123', $identifiers->cdp_id);
    $this->assertNull($identifiers->id);
    $this->assertNull($identifiers->email);
  }

  public function testIdentifiersToArray(): void
  {
    $identifiers = Identifiers::withId('user123');
    $array = $identifiers->toArray();
    $this->assertEquals(['id' => 'user123'], $array);
  }

  public function testIdentifiersToArrayFiltersNulls(): void
  {
    $identifiers = Identifiers::withEmail('user@example.com');
    $array = $identifiers->toArray();
    $this->assertEquals(['email' => 'user@example.com'], $array);
    $this->assertArrayNotHasKey('id', $array);
    $this->assertArrayNotHasKey('cdp_id', $array);
  }

  public function testDeviceRegistrationParametersWithRequiredFields(): void
  {
    $params = new DeviceRegistrationParameters(
      deviceId: 'device-123',
      platform: 'ios',
      fcmToken: 'fcm-token-123'
    );
    $this->assertEquals('device-123', $params->deviceId);
    $this->assertEquals('ios', $params->platform);
    $this->assertEquals('fcm-token-123', $params->fcmToken);
  }

  public function testDeviceRegistrationParametersThrowsOnInvalidPlatform(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("platform must be 'android', 'ios', or 'web'");
    new DeviceRegistrationParameters(
      deviceId: 'device-123',
      platform: 'invalid',
      fcmToken: 'fcm-token-123'
    );
  }

  public function testDeviceRegistrationParametersAcceptsAndroidPlatform(): void
  {
    $params = new DeviceRegistrationParameters(
      deviceId: 'device-123',
      platform: 'android',
      fcmToken: 'fcm-token-123'
    );
    $this->assertEquals('android', $params->platform);
  }

  public function testDeviceRegistrationParametersAcceptsWebPlatform(): void
  {
    $params = new DeviceRegistrationParameters(
      deviceId: 'device-123',
      platform: 'web',
      fcmToken: 'fcm-token-123'
    );
    $this->assertEquals('web', $params->platform);
  }

  public function testDeviceRegistrationParametersToArray(): void
  {
    $params = new DeviceRegistrationParameters(
      deviceId: 'device-123',
      platform: 'ios',
      fcmToken: 'fcm-token-123',
      name: 'iPhone',
      osVersion: '15.0'
    );
    $array = $params->toArray();
    $this->assertEquals('device-123', $array['deviceId']);
    $this->assertEquals('ios', $array['platform']);
    $this->assertEquals('fcm-token-123', $array['fcmToken']);
    $this->assertEquals('iPhone', $array['name']);
    $this->assertEquals('15.0', $array['osVersion']);
  }

  public function testDeviceRegistrationParametersToArrayFiltersNulls(): void
  {
    $params = new DeviceRegistrationParameters(
      deviceId: 'device-123',
      platform: 'ios',
      fcmToken: 'fcm-token-123'
    );
    $array = $params->toArray();
    $this->assertArrayNotHasKey('name', $array);
    $this->assertArrayNotHasKey('osVersion', $array);
  }

  public function testSendEmailRequestRequiresTo(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('to is required');
    new SendEmailRequest([
      'identifiers' => Identifiers::withId('user123'),
    ]);
  }

  public function testSendEmailRequestRequiresIdentifiers(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('identifiers must be an Identifiers instance');
    new SendEmailRequest([
      'to' => 'user@example.com',
    ]);
  }

  public function testSendPushRequestWithRequiredFields(): void
  {
    $request = new SendPushRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 'WELCOME_PUSH'
    );
    $this->assertInstanceOf(Identifiers::class, $request->identifiers);
    $this->assertEquals('WELCOME_PUSH', $request->transactional_message_id);
  }

  public function testSendSmsRequestWithRequiredFields(): void
  {
    $request = new SendSmsRequest(
      identifiers: Identifiers::withId('user123'),
      transactional_message_id: 'WELCOME_SMS'
    );
    $this->assertInstanceOf(Identifiers::class, $request->identifiers);
    $this->assertEquals('WELCOME_SMS', $request->transactional_message_id);
  }
}
