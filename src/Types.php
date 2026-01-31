<?php

declare(strict_types=1);

namespace Codematic\OpenCDP;

/**
 * Device registration parameters
 */
class DeviceRegistrationParameters
{
  public function __construct(
    public readonly string $deviceId,
    public readonly string $platform, // 'android', 'ios', or 'web'
    public readonly string $fcmToken,
    public readonly ?string $name = null,
    public readonly ?string $osVersion = null,
    public readonly ?string $model = null,
    public readonly ?string $apnToken = null,
    public readonly ?string $appVersion = null,
    public readonly ?string $last_active_at = null,
    public readonly ?array $attributes = null
  ) {
    if (!in_array($platform, ['android', 'ios', 'web'], true)) {
      throw new \InvalidArgumentException("platform must be 'android', 'ios', or 'web'");
    }
  }

  /**
   * Convert to array for API request
   *
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return array_filter([
      'deviceId' => $this->deviceId,
      'platform' => $this->platform,
      'fcmToken' => $this->fcmToken,
      'name' => $this->name,
      'osVersion' => $this->osVersion,
      'model' => $this->model,
      'apnToken' => $this->apnToken,
      'appVersion' => $this->appVersion,
      'last_active_at' => $this->last_active_at,
      'attributes' => $this->attributes,
    ], fn($value) => $value !== null);
  }
}

/**
 * Identifiers for person lookup
 */
class Identifiers
{
  private function __construct(
    public readonly ?string $id = null,
    public readonly ?string $email = null,
    public readonly ?string $cdp_id = null
  ) {
  }

  public static function withId(string|int $id): self
  {
    return new self(id: (string) $id);
  }

  public static function withEmail(string $email): self
  {
    return new self(email: $email);
  }

  public static function withCdpId(string $cdpId): self
  {
    return new self(cdp_id: $cdpId);
  }

  /**
   * @return array<string, string>
   */
  public function toArray(): array
  {
    return array_filter([
      'id' => $this->id,
      'email' => $this->email,
      'cdp_id' => $this->cdp_id,
    ], fn($value) => $value !== null);
  }
}

/**
 * Send email request
 */
class SendEmailRequest
{
  public readonly string $to;
  public readonly Identifiers $identifiers;
  public readonly ?string $transactional_message_id;
  public readonly ?string $body;
  public readonly ?string $subject;
  public readonly ?string $from;
  public readonly ?array $message_data;
  public readonly ?int $send_at;
  public readonly ?bool $disable_message_retention;
  public readonly ?bool $send_to_unsubscribed;
  public readonly ?bool $queue_draft;
  public readonly ?array $bcc;
  public readonly ?array $cc;
  public readonly ?bool $fake_bcc;
  public readonly ?string $reply_to;
  public readonly ?string $preheader;
  public readonly ?array $headers;
  public readonly ?bool $disable_css_preprocessing;
  public readonly ?bool $tracked;
  public readonly ?string $plaintext_body;
  public readonly ?string $amp_body;
  public readonly ?string $language;
  public readonly ?array $attachments;

  /**
   * @param array<string, mixed> $params
   */
  public function __construct(array $params)
  {
    $this->to = $params['to'] ?? throw new \InvalidArgumentException('to is required');

    if (isset($params['identifiers']) && $params['identifiers'] instanceof Identifiers) {
      $this->identifiers = $params['identifiers'];
    } else {
      throw new \InvalidArgumentException('identifiers must be an Identifiers instance');
    }

    $this->transactional_message_id = $params['transactional_message_id'] ?? null;
    $this->body = $params['body'] ?? null;
    $this->subject = $params['subject'] ?? null;
    $this->from = $params['from'] ?? null;
    $this->message_data = $params['message_data'] ?? null;
    $this->send_at = $params['send_at'] ?? null;
    $this->disable_message_retention = $params['disable_message_retention'] ?? null;
    $this->send_to_unsubscribed = $params['send_to_unsubscribed'] ?? null;
    $this->queue_draft = $params['queue_draft'] ?? null;
    $this->bcc = $params['bcc'] ?? null;
    $this->cc = $params['cc'] ?? null;
    $this->fake_bcc = $params['fake_bcc'] ?? null;
    $this->reply_to = $params['reply_to'] ?? null;
    $this->preheader = $params['preheader'] ?? null;
    $this->headers = $params['headers'] ?? null;
    $this->disable_css_preprocessing = $params['disable_css_preprocessing'] ?? null;
    $this->tracked = $params['tracked'] ?? null;
    $this->plaintext_body = $params['plaintext_body'] ?? $params['body_plain'] ?? null;
    $this->amp_body = $params['amp_body'] ?? $params['body_amp'] ?? null;
    $this->language = $params['language'] ?? null;
    $this->attachments = $params['attachments'] ?? null;
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return array_filter([
      'to' => $this->to,
      'identifiers' => $this->identifiers->toArray(),
      'transactional_message_id' => $this->transactional_message_id,
      'body' => $this->body,
      'subject' => $this->subject,
      'from' => $this->from,
      'message_data' => $this->message_data,
      'send_at' => $this->send_at,
      'disable_message_retention' => $this->disable_message_retention,
      'send_to_unsubscribed' => $this->send_to_unsubscribed,
      'queue_draft' => $this->queue_draft,
      'bcc' => $this->bcc,
      'cc' => $this->cc,
      'fake_bcc' => $this->fake_bcc,
      'reply_to' => $this->reply_to,
      'preheader' => $this->preheader,
      'headers' => $this->headers,
      'disable_css_preprocessing' => $this->disable_css_preprocessing,
      'tracked' => $this->tracked,
      'body_plain' => $this->plaintext_body,
      'body_amp' => $this->amp_body,
      'language' => $this->language,
      'attachments' => $this->attachments,
    ], fn($value) => $value !== null);
  }
}

/**
 * Send push notification request
 */
class SendPushRequest
{
  public function __construct(
    public readonly Identifiers $identifiers,
    public readonly string|int $transactional_message_id,
    public readonly ?string $title = null,
    public readonly ?string $body = null,
    public readonly ?array $message_data = null
  ) {
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return array_filter([
      'identifiers' => $this->identifiers->toArray(),
      'transactional_message_id' => $this->transactional_message_id,
      'title' => $this->title,
      'body' => $this->body,
      'message_data' => $this->message_data,
    ], fn($value) => $value !== null);
  }
}

/**
 * Send SMS request
 */
class SendSmsRequest
{
  public function __construct(
    public readonly Identifiers $identifiers,
    public readonly ?string $transactional_message_id = null,
    public readonly ?string $to = null,
    public readonly ?string $from = null,
    public readonly ?string $body = null,
    public readonly ?array $message_data = null
  ) {
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return array_filter([
      'identifiers' => $this->identifiers->toArray(),
      'transactional_message_id' => $this->transactional_message_id,
      'to' => $this->to,
      'from' => $this->from,
      'body' => $this->body,
      'message_data' => $this->message_data,
    ], fn($value) => $value !== null);
  }
}
