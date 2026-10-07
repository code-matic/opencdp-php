<?php

declare(strict_types=1);

namespace Codematic\OpenCDP;

/**
 * Validation functions for CDP requests
 */
class Validators
{
  /**
   * Validates that the identifier is not empty
   *
   * @param string|int $identifier
   * @throws \InvalidArgumentException
   */
  public static function validateIdentifier(string|int $identifier): void
  {
    if (
      $identifier === null ||
      $identifier === '' ||
      (is_string($identifier) && trim($identifier) === '')
    ) {
      throw new \InvalidArgumentException('Identifier cannot be empty');
    }

    if (is_string($identifier) && strlen($identifier) > 255) {
      throw new \InvalidArgumentException('Identifier cannot exceed 255 characters');
    }
  }

  /**
   * Validates that the event name is not empty
   *
   * @param string $eventName
   * @throws \InvalidArgumentException
   */
  public static function validateEventName(string $eventName): void
  {
    if (empty(trim($eventName))) {
      throw new \InvalidArgumentException('Event name cannot be empty');
    }

    if (strlen(trim($eventName)) > 255) {
      throw new \InvalidArgumentException('Event name cannot exceed 255 characters');
    }
  }

  /**
   * Validates properties and returns normalized array
   *
   * @param array<string, mixed>|null $properties
   * @return array<string, mixed>
   */
  public static function validateProperties(?array $properties): array
  {
    return $properties ?? [];
  }

  /**
   * Validates email address format
   *
   * @param string $email
   * @throws \InvalidArgumentException
   */
  public static function validateEmail(string $email): void
  {
    if (empty(trim($email))) {
      throw new \InvalidArgumentException('Email address cannot be empty');
    }

    if (strlen($email) > 254) {
      throw new \InvalidArgumentException('Email address cannot exceed 254 characters');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new \InvalidArgumentException('Invalid email address format');
    }
  }

  /**
   * Validates phone number format (E.164)
   *
   * @param string $phone
   * @throws \InvalidArgumentException
   */
  public static function validatePhoneNumber(string $phone): void
  {
    if (empty(trim($phone))) {
      throw new \InvalidArgumentException('Phone number cannot be empty');
    }

    // E.164 format: ^\+?[1-9]\d{1,14}$
    if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
      throw new \InvalidArgumentException('Phone number must be in international format (e.g., +1234567890)');
    }
  }

  /**
   * Validates send email request
   *
   * @param SendEmailRequest $request
   * @throws \InvalidArgumentException
   */
  public static function validateSendEmailRequest(SendEmailRequest $request): void
  {
    // Validate required field: to
    if (empty($request->to)) {
      throw new \InvalidArgumentException('to is required');
    }
    self::validateEmail($request->to);

    // Validate identifiers
    $identifiersArray = $request->identifiers->toArray();
    if (empty($identifiersArray)) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $hasId = isset($identifiersArray['id']) && $identifiersArray['id'] !== '';
    $hasEmail = isset($identifiersArray['email']) && $identifiersArray['email'] !== '';
    $hasCdpId = isset($identifiersArray['cdp_id']) && $identifiersArray['cdp_id'] !== '';

    if (!$hasId && !$hasEmail && !$hasCdpId) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $count = ($hasId ? 1 : 0) + ($hasEmail ? 1 : 0) + ($hasCdpId ? 1 : 0);
    if ($count > 1) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    // Validate email fields if provided
    if ($request->from !== null) {
      self::validateEmail($request->from);
    }

    if ($request->bcc !== null && !empty($request->bcc)) {
      if (!is_array($request->bcc)) {
        throw new \InvalidArgumentException('bcc must be an array');
      }
      foreach ($request->bcc as $email) {
        if (!is_string($email)) {
          throw new \InvalidArgumentException('bcc must contain only string email addresses');
        }
        self::validateEmail($email);
      }
    }

    if ($request->cc !== null && !empty($request->cc)) {
      if (!is_array($request->cc)) {
        throw new \InvalidArgumentException('cc must be an array');
      }
      foreach ($request->cc as $email) {
        if (!is_string($email)) {
          throw new \InvalidArgumentException('cc must contain only string email addresses');
        }
        self::validateEmail($email);
      }
    }

    if ($request->reply_to !== null) {
      self::validateEmail($request->reply_to);
    }

    // Validate send_at if provided
    if ($request->send_at !== null) {
      if (!is_int($request->send_at) || $request->send_at < 0) {
        throw new \InvalidArgumentException('send_at must be a positive integer');
      }
    }

    // Validate body fields are not empty strings
    if ($request->body !== null && trim($request->body) === '') {
      throw new \InvalidArgumentException('body cannot be empty if provided');
    }

    if ($request->amp_body !== null && trim($request->amp_body) === '') {
      throw new \InvalidArgumentException('amp_body cannot be empty if provided');
    }

    if ($request->plaintext_body !== null && trim($request->plaintext_body) === '') {
      throw new \InvalidArgumentException('plaintext_body cannot be empty if provided');
    }

    // Validate headers if provided
    if ($request->headers !== null && !is_array($request->headers)) {
      throw new \InvalidArgumentException('headers must be an array');
    }

    // Check if this is a template or raw email request
    $isTemplateRequest = $request->transactional_message_id !== null;

    if (!$isTemplateRequest) {
      // Raw email - body, subject, and from are required
      $errors = [];

      if ($request->body === null) {
        $errors[] = 'body is required when not using a template';
      }
      if ($request->subject === null) {
        $errors[] = 'subject is required when not using a template';
      }
      if ($request->from === null) {
        $errors[] = 'from is required when not using a template';
      }

      if (!empty($errors)) {
        throw new \InvalidArgumentException('When not using a template: ' . implode(', ', $errors));
      }
    }
  }

  /**
   * Validates send push request
   *
   * @param SendPushRequest $request
   * @throws \InvalidArgumentException
   */
  public static function validateSendPushRequest(SendPushRequest $request): void
  {
    // Validate identifiers
    $identifiersArray = $request->identifiers->toArray();
    if (empty($identifiersArray)) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $hasId = isset($identifiersArray['id']) && $identifiersArray['id'] !== '';
    $hasEmail = isset($identifiersArray['email']) && $identifiersArray['email'] !== '';
    $hasCdpId = isset($identifiersArray['cdp_id']) && $identifiersArray['cdp_id'] !== '';

    if (!$hasId && !$hasEmail && !$hasCdpId) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $count = ($hasId ? 1 : 0) + ($hasEmail ? 1 : 0) + ($hasCdpId ? 1 : 0);
    if ($count > 1) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    // Validate transactional_message_id is present
    if (empty($request->transactional_message_id)) {
      throw new \InvalidArgumentException('transactional_message_id is required');
    }

    // Validate body is not empty string if provided
    if ($request->body !== null && trim($request->body) === '') {
      throw new \InvalidArgumentException('body cannot be empty if provided');
    }
  }

  /**
   * Validates send SMS request
   *
   * @param SendSmsRequest $request
   * @throws \InvalidArgumentException
   */
  public static function validateSendSmsRequest(SendSmsRequest $request): void
  {
    // Validate identifiers
    $identifiersArray = $request->identifiers->toArray();
    if (empty($identifiersArray)) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $hasId = isset($identifiersArray['id']) && $identifiersArray['id'] !== '';
    $hasEmail = isset($identifiersArray['email']) && $identifiersArray['email'] !== '';
    $hasCdpId = isset($identifiersArray['cdp_id']) && $identifiersArray['cdp_id'] !== '';

    if (!$hasId && !$hasEmail && !$hasCdpId) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $count = ($hasId ? 1 : 0) + ($hasEmail ? 1 : 0) + ($hasCdpId ? 1 : 0);
    if ($count > 1) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    // Validate conditional requirement: body is required if no transactional_message_id
    $hasTemplateId = $request->transactional_message_id !== null &&
      $request->transactional_message_id !== '';

    if (!$hasTemplateId && empty($request->body)) {
      throw new \InvalidArgumentException('body is required when not using a template');
    }

    // Validate phone numbers if provided
    if ($request->to !== null) {
      self::validatePhoneNumber($request->to);
    }

    if ($request->from !== null) {
      self::validatePhoneNumber($request->from);
    }

    // Validate body is not empty string if provided
    if ($request->body !== null && trim($request->body) === '') {
      throw new \InvalidArgumentException('body cannot be empty if provided');
    }

    // Validate message_data is an array if provided
    if ($request->message_data !== null && !is_array($request->message_data)) {
      throw new \InvalidArgumentException('message_data must be an array');
    }
  }

  /**
   * Validates send WhatsApp request
   *
   * @param SendWhatsAppRequest $request
   * @throws \InvalidArgumentException
   */
  public static function validateSendWhatsAppRequest(SendWhatsAppRequest $request): void
  {
    $identifiersArray = $request->identifiers->toArray();
    if (empty($identifiersArray)) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $hasId = isset($identifiersArray['id']) && $identifiersArray['id'] !== '';
    $hasEmail = isset($identifiersArray['email']) && $identifiersArray['email'] !== '';
    $hasCdpId = isset($identifiersArray['cdp_id']) && $identifiersArray['cdp_id'] !== '';

    if (!$hasId && !$hasEmail && !$hasCdpId) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    $count = ($hasId ? 1 : 0) + ($hasEmail ? 1 : 0) + ($hasCdpId ? 1 : 0);
    if ($count > 1) {
      throw new \InvalidArgumentException('identifiers must contain exactly one of: id, email, or cdp_id');
    }

    // Not empty(): a transactional id of "0" or 0 is valid.
    if (trim((string) $request->transactional_message_id) === '') {
      throw new \InvalidArgumentException('transactional_message_id is required');
    }

    if ($request->to !== null) {
      self::validatePhoneNumber($request->to);
    }

    if ($request->template_variables !== null) {
      if (!is_array($request->template_variables)) {
        throw new \InvalidArgumentException('template_variables must be an array');
      }

      $allowedKeys = ['header', 'body', 'button'];
      foreach ($request->template_variables as $key => $value) {
        if (!in_array($key, $allowedKeys, true)) {
          throw new \InvalidArgumentException('template_variables may only contain header, body, and button');
        }
        if (!is_array($value)) {
          throw new \InvalidArgumentException("template_variables.{$key} must be an array");
        }
        // The gateway sends parameters by position and drops non-numeric button keys. This also
        // rejects PHP lists (keys 0, 1, ...), which would be encoded as JSON arrays.
        foreach (array_keys($value) as $slot) {
          if (!preg_match('/^[1-9]\d*$/', (string) $slot)) {
            throw new \InvalidArgumentException(
              "template_variables.{$key} keys must be positional slot numbers (\"1\", \"2\", ...), got \"{$slot}\""
            );
          }
        }
      }
    }

    if ($request->message_data !== null) {
      if (!is_array($request->message_data)) {
        throw new \InvalidArgumentException('message_data must be an array');
      }
      // A PHP list is encoded as a JSON array, which the gateway rejects.
      if ($request->message_data !== [] && array_keys($request->message_data) === range(0, count($request->message_data) - 1)) {
        throw new \InvalidArgumentException('message_data must be an associative array');
      }
    }
  }
}
