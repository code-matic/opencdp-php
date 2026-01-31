# API Reference

## CDPClient

### Constructor

```php
public function __construct(CDPConfig $config)
```

Creates a new CDP client instance.

**Parameters:**
- `$config` (CDPConfig): Configuration object

**Example:**
```php
$config = new CDPConfig(cdpApiKey: 'your-api-key');
$client = new CDPClient($config);
```

---

### ping()

```php
public function ping(): void
```

Tests the connection to the OpenCDP API server. Throws exception only if `failOnException` is `true`.

**Example:**
```php
$client->ping();
```

---

### identify()

```php
public function identify(string $identifier, array $properties = []): void
```

Identify a person in the CDP.

**Parameters:**
- `$identifier` (string): The person identifier
- `$properties` (array): Additional properties for the person

**Throws:**
- `InvalidArgumentException`: When validation fails (if `failOnException` is true)
- `CDPException`: When API request fails (if `failOnException` is true)

**Example:**
```php
$client->identify('user123', [
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'plan' => 'premium'
]);
```

---

### track()

```php
public function track(string $identifier, string $eventName, array $properties = []): void
```

Track an event for a person.

**Parameters:**
- `$identifier` (string): The person identifier
- `$eventName` (string): The event name
- `$properties` (array): Additional properties for the event

**Throws:**
- `InvalidArgumentException`: When validation fails (if `failOnException` is true)
- `CDPException`: When API request fails (if `failOnException` is true)

**Example:**
```php
$client->track('user123', 'purchase_completed', [
    'amount' => 99.99,
    'item_id' => 'prod-123'
]);
```

---

### registerDevice()

```php
public function registerDevice(string $identifier, DeviceRegistrationParameters $params): void
```

Register a device for a person. Required for sending push notifications.

**Parameters:**
- `$identifier` (string): The person identifier
- `$params` (DeviceRegistrationParameters): Device registration parameters

**Throws:**
- `InvalidArgumentException`: When validation fails (if `failOnException` is true)
- `CDPException`: When API request fails (if `failOnException` is true)

**Example:**
```php
$params = new DeviceRegistrationParameters(
    deviceId: 'device-123',
    platform: 'ios',
    fcmToken: 'fcm-token-here'
);
$client->registerDevice('user123', $params);
```

---

### sendEmail()

```php
public function sendEmail(SendEmailRequest $request): array
```

Send a transactional email.

**Parameters:**
- `$request` (SendEmailRequest): Email request parameters

**Returns:**
- `array`: Response from the API

**Throws:**
- `InvalidArgumentException`: When validation fails (if `failOnException` is true)
- `CDPEmailException`: When email sending fails (if `failOnException` is true)

**Example:**
```php
$request = new SendEmailRequest([
    'to' => 'user@example.com',
    'identifiers' => Identifiers::withId('user123'),
    'transactional_message_id' => 'WELCOME_EMAIL'
]);
$response = $client->sendEmail($request);
```

---

### sendPush()

```php
public function sendPush(SendPushRequest $request): array
```

Send a push notification.

**Parameters:**
- `$request` (SendPushRequest): Push request parameters

**Returns:**
- `array`: Response from the API

**Throws:**
- `InvalidArgumentException`: When validation fails (if `failOnException` is true)
- `CDPPushException`: When push sending fails (if `failOnException` is true)

**Example:**
```php
$request = new SendPushRequest(
    identifiers: Identifiers::withId('user123'),
    transactional_message_id: 'WELCOME_PUSH',
    title: 'Welcome!',
    body: 'Thank you for joining us!'
);
$response = $client->sendPush($request);
```

---

### sendSms()

```php
public function sendSms(SendSmsRequest $request): array
```

Send an SMS message.

**Parameters:**
- `$request` (SendSmsRequest): SMS request parameters

**Returns:**
- `array`: Response from the API

**Throws:**
- `InvalidArgumentException`: When validation fails (if `failOnException` is true)
- `CDPSmsException`: When SMS sending fails (if `failOnException` is true)

**Example:**
```php
$request = new SendSmsRequest(
    identifiers: Identifiers::withId('user123'),
    transactional_message_id: 'WELCOME_SMS',
    body: 'Welcome to our platform!'
);
$response = $client->sendSms($request);
```

---

## Type Classes

### Identifiers

Factory methods for creating identifier objects:

```php
Identifiers::withId(string|int $id): Identifiers
Identifiers::withEmail(string $email): Identifiers
Identifiers::withCdpId(string $cdpId): Identifiers
```

**Example:**
```php
$identifiers = Identifiers::withId('user123');
$identifiers = Identifiers::withEmail('user@example.com');
$identifiers = Identifiers::withCdpId('cdp-id-123');
```

###DeviceRegistrationParameters

```php
new DeviceRegistrationParameters(
    string $deviceId,
    string $platform,        // 'android', 'ios', or 'web'
    string $fcmToken,
    ?string $name = null,
    ?string $osVersion = null,
    ?string $model = null,
    ?string $apnToken = null,
    ?string $appVersion = null,
    ?string $last_active_at = null,
    ?array $attributes = null
)
```

### SendEmailRequest

```php
new SendEmailRequest(array $params)
```

**Required Parameters:**
- `to` (string): Recipient email address
- `identifiers` (Identifiers): Person identifiers

**For Template Emails:**
- `transactional_message_id` (string|int): Template ID

**For Raw Emails:**
- `from` (string): Sender email address
- `subject` (string): Email subject
- `body` (string): Email HTML body

**Optional Parameters:**
- `message_data` (array): Template variables
- `plaintext_body` (string): Plain text version
- `bcc` (array): BCC recipients
- `cc` (array): CC recipients
- `reply_to` (string): Reply-to address
- And more...

### SendPushRequest

```php
new SendPushRequest(
    Identifiers $identifiers,
    string|int $transactional_message_id,
    ?string $title = null,
    ?string $body = null,
    ?array $message_data = null
)
```

### SendSmsRequest

```php
new SendSmsRequest(
    Identifiers $identifiers,
    ?string $transactional_message_id = null,
    ?string $to = null,
    ?string $from = null,
    ?string $body = null,
    ?array $message_data = null
)
```

---

## Exception Classes

### CDPException

Base exception class for all CDP errors.

**Properties:**
- `$summary` (array): Error details
- `$status` (int): HTTP status code
- `$errorCode` (string): Error code

### CDPEmailException

Extends `CDPException`. Thrown when email sending fails.

**Error Code:** `EMAIL_SEND_FAILED`

### CDPPushException

Extends `CDPException`. Thrown when push notification sending fails.

**Error Code:** `PUSH_SEND_FAILED`

### CDPSmsException

Extends `CDPException`. Thrown when SMS sending fails.

**Error Code:** `SMS_SEND_FAILED`

---

## Validators

Static validation methods (used internally):

```php
Validators::validateIdentifier(string|int $identifier): void
Validators::validateEventName(string $eventName): void
Validators::validateEmail(string $email): void
Validators::validatePhoneNumber(string $phone): void
Validators::validateSendEmailRequest(SendEmailRequest $request): void
Validators::validateSendPushRequest(SendPushRequest $request): void
Validators::validateSendSmsRequest(SendSmsRequest $request): void
```

**Validation Rules:**

- **Identifiers**: Must be non-empty. String identifiers cannot exceed 255 characters.
- **Event Names**: Must be non-empty and cannot exceed 255 characters.
- **Email Addresses**: Must be valid email format and cannot exceed 254 characters (per RFC 5321).
- **Phone Numbers**: Must be in E.164 international format (e.g., +1234567890).
- **Email Arrays** (`bcc`, `cc`): Must be arrays containing only string email addresses. Each email is validated individually.

---

## LoggerInterface

Custom logger interface for implementing your own logging:

```php
interface LoggerInterface
{
    public function debug(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warn(string $message, array $context = []): void;
}
```
