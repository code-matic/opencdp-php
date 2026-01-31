<?php

declare(strict_types=1);

namespace Codematic\OpenCDP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Codematic\OpenCDP\Exceptions\CDPException;
use Codematic\OpenCDP\Exceptions\CDPEmailException;
use Codematic\OpenCDP\Exceptions\CDPPushException;
use Codematic\OpenCDP\Exceptions\CDPSmsException;

/**
 * CDP Client for interacting with the Codematic Customer Data Platform
 */
class CDPClient
{
  private Client $httpClient;
  private CDPConfig $config;
  private LoggerInterface $logger;
  private ?object $customerIoClient = null;

  public function __construct(CDPConfig $config)
  {
    $this->config = $config;
    $this->logger = $config->logger;

    // Initialize Guzzle HTTP client
    $this->httpClient = new Client([
      'base_uri' => $config->cdpEndpoint,
      'timeout' => $config->timeout / 1000, // Convert ms to seconds
      'headers' => [
        'Authorization' => $config->cdpApiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
      ],
    ]);

    // Initialize Customer.io client if configured
    if ($config->sendToCustomerIo && $config->customerIo !== null) {
      $this->initializeCustomerIo();
    }
  }

  /**
   * Initialize Customer.io client if the package is available
   */
  private function initializeCustomerIo(): void
  {
    if (!class_exists('\Customerio\Client')) {
      if ($this->config->debug) {
        $this->logger->warn('Customer.io package not installed. Install customerio/customerio-php to enable dual-write.');
      }
      return;
    }

    try {
      $region = $this->config->customerIo['region'] ?? 'us';
      $this->customerIoClient = new \Customerio\Client(
        $this->config->customerIo['siteId'],
        $this->config->customerIo['apiKey'],
        ['region' => $region]
      );
    } catch (\Exception $e) {
      if ($this->config->debug) {
        $this->logger->error('[Customer.io] Initialize error', ['error' => $e->getMessage()]);
      }
    }
  }

  /**
   * Tests the connection to the OpenCDP API server.
   * 
   * Sends a ping request to verify that the configured endpoint is reachable and valid.
   * This method ensures that credentials and network access are configured correctly.
   * It does NOT establish a persistent connection.
   *
   * @throws CDPException Only when config->failOnException === true and the connection fails
   */
  public function ping(): void
  {
    $this->validateConnection();
  }

  /**
   * Internal connection validation method
   *
   * @throws CDPException
   */
  private function validateConnection(): void
  {
    try {
      $response = $this->httpClient->get('v1/health/ping');

      if ($this->config->debug) {
        $this->logger->debug('[CDP] Connection Established! Status: ' . $response->getStatusCode());
      }
    } catch (GuzzleException $e) {
      $statusCode = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
      $statusText = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getReasonPhrase() : null;

      $errorSummary = [
        'message' => $e->getMessage(),
        'statusCode' => $statusCode,
        'statusText' => $statusText,
      ];

      if ($this->config->debug) {
        $this->logger->error('[CDP] Failed to connect to CDP Server', $errorSummary);
      }

      if ($this->config->failOnException) {
        throw new CDPException('Failed to connect to CDP Server: ' . $e->getMessage(), $statusCode ?? 500);
      }
    }
  }

  /**
   * Identify a person in the CDP
   *
   * @param string $identifier The person identifier
   * @param array<string, mixed> $properties Additional properties for the person
   * @return void Returns nothing. Errors are logged if failOnException is false.
   * @throws CDPException Only when config->failOnException === true
   * @throws \InvalidArgumentException Only when config->failOnException === true
   */
  public function identify(string $identifier, array $properties = []): void
  {
    try {
      Validators::validateIdentifier($identifier);
    } catch (\InvalidArgumentException $e) {
      if ($this->config->debug) {
        $this->logger->error('[CDP] Identify validation error', ['error' => $e->getMessage()]);
      }
      if ($this->config->failOnException) {
        throw $e;
      }
      return;
    }

    $normalizedProps = Validators::validateProperties($properties);

    // Send to Customer.io if configured
    if ($this->config->sendToCustomerIo && $this->customerIoClient !== null) {
      try {
        $this->customerIoClient->identify([
          'id' => $identifier,
          'attributes' => $normalizedProps,
        ]);
        if ($this->config->debug) {
          $this->logger->debug("[Customer.io] Identified {$identifier}");
        }
      } catch (\Exception $e) {
        if ($this->config->debug) {
          $this->logger->error('[Customer.io] Identify error', ['error' => $e->getMessage()]);
        }
        if ($this->config->failOnException) {
          throw $e;
        }
      }
    }

    // Send to CDP
    try {
      $this->httpClient->post('v1/persons/identify', [
        'json' => [
          'identifier' => $identifier,
          'properties' => $normalizedProps,
        ],
      ]);

      if ($this->config->debug) {
        $this->logger->debug("[CDP] Identified {$identifier}");
      }
    } catch (GuzzleException $e) {
      if ($this->config->debug) {
        $errorSummary = [
          'message' => $e->getMessage(),
          'status' => method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
        ];
        $this->logger->error('[CDP] Identify error', ['errorSummary' => $errorSummary]);
      }

      if ($this->config->failOnException) {
        throw new CDPException(
          'Identify failed: ' . $e->getMessage(),
          method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 500
        );
      }
    }
  }

  /**
   * Track an event for a person
   *
   * @param string $identifier The person identifier
   * @param string $eventName The event name
   * @param array<string, mixed> $properties Additional properties for the event
   * @return void Returns nothing. Errors are logged if failOnException is false.
   * @throws CDPException Only when config->failOnException === true
   * @throws \InvalidArgumentException Only when config->failOnException === true
   */
  public function track(string $identifier, string $eventName, array $properties = []): void
  {
    try {
      Validators::validateIdentifier($identifier);
      Validators::validateEventName($eventName);
    } catch (\InvalidArgumentException $e) {
      if ($this->config->debug) {
        $this->logger->error('[CDP] Track validation error', ['error' => $e->getMessage()]);
      }
      if ($this->config->failOnException) {
        throw $e;
      }
      return;
    }

    $normalizedProps = Validators::validateProperties($properties);

    // Send to Customer.io if configured
    if ($this->config->sendToCustomerIo && $this->customerIoClient !== null) {
      try {
        $this->customerIoClient->track($identifier, [
          'name' => $eventName,
          'data' => $normalizedProps,
        ]);
        if ($this->config->debug) {
          $this->logger->debug("[Customer.io] Tracked event {$eventName} for {$identifier}");
        }
      } catch (\Exception $e) {
        if ($this->config->debug) {
          $this->logger->error('[Customer.io] Track error', ['error' => $e->getMessage()]);
        }
        if ($this->config->failOnException) {
          throw $e;
        }
      }
    }

    // Send to CDP
    try {
      $this->httpClient->post('v1/persons/track', [
        'json' => [
          'identifier' => $identifier,
          'eventName' => $eventName,
          'properties' => $normalizedProps,
        ],
      ]);

      if ($this->config->debug) {
        $this->logger->debug("[CDP] Tracked event {$eventName} for {$identifier}");
      }
    } catch (GuzzleException $e) {
      if ($this->config->debug) {
        $errorSummary = [
          'message' => $e->getMessage(),
          'status' => method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
        ];
        $this->logger->error('[CDP] Track error', ['errorSummary' => $errorSummary]);
      }

      if ($this->config->failOnException) {
        throw new CDPException(
          'Track failed: ' . $e->getMessage(),
          method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 500
        );
      }
    }
  }

  /**
   * Register a device for a person. A device must be registered to send push notifications.
   *
   * @param string $identifier The person identifier
   * @param DeviceRegistrationParameters $params Device registration parameters
   * @return void Returns nothing. Errors are logged if failOnException is false.
   * @throws CDPException Only when config->failOnException === true
   * @throws \InvalidArgumentException Only when config->failOnException === true
   */
  public function registerDevice(string $identifier, DeviceRegistrationParameters $params): void
  {
    try {
      Validators::validateIdentifier($identifier);
    } catch (\InvalidArgumentException $e) {
      if ($this->config->debug) {
        $this->logger->error('[CDP] Register device validation error', ['error' => $e->getMessage()]);
      }
      if ($this->config->failOnException) {
        throw $e;
      }
      return;
    }

    // Send to Customer.io if configured
    if ($this->config->sendToCustomerIo && $this->customerIoClient !== null) {
      try {
        $deviceData = $params->toArray();
        $this->customerIoClient->addDevice($identifier, $deviceData['deviceId'], $deviceData);
        if ($this->config->debug) {
          $this->logger->debug("[Customer.io] Registered device for {$identifier}");
        }
      } catch (\Exception $e) {
        if ($this->config->debug) {
          $this->logger->error('[Customer.io] Register device error', ['error' => $e->getMessage()]);
        }
        if ($this->config->failOnException) {
          throw $e;
        }
      }
    }

    // Send to CDP
    try {
      $payload = array_merge(['identifier' => $identifier], $params->toArray());

      $this->httpClient->post('v1/persons/registerDevice', [
        'json' => $payload,
      ]);

      if ($this->config->debug) {
        $this->logger->debug("[CDP] Registered device for {$identifier}");
      }
    } catch (GuzzleException $e) {
      if ($this->config->debug) {
        $errorSummary = [
          'message' => $e->getMessage(),
          'status' => method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
        ];
        $this->logger->error('[CDP] Register device error', ['errorSummary' => $errorSummary]);
      }

      if ($this->config->failOnException) {
        throw new CDPException(
          'Register device failed: ' . $e->getMessage(),
          method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 500
        );
      }
    }
  }

  /**
   * Send an email using the CDP transactional email service
   *
   * @param SendEmailRequest $request The send email request parameters
   * @return array<string, mixed> Response from the API. Returns error array with 'ok' => false when failOnException is false and an error occurs.
   * @throws CDPEmailException Only when config->failOnException === true
   * @throws \InvalidArgumentException Only when config->failOnException === true
   */
  public function sendEmail(SendEmailRequest $request): array
  {
    try {
      Validators::validateSendEmailRequest($request);
    } catch (\InvalidArgumentException $e) {
      if ($this->config->debug) {
        $this->logger->error('[CDP] Send email validation error', ['error' => $e->getMessage()]);
      }
      if ($this->config->failOnException) {
        throw $e;
      }
      return ['ok' => false, 'error' => $e->getMessage()];
    }

    // Check for unsupported fields and log warnings
    $this->warnUnsupportedFields($request);

    // Build the payload
    $payload = $request->toArray();

    // Warning about Customer.io dual-write
    if ($this->config->sendToCustomerIo && $this->customerIoClient !== null && $this->config->debug) {
      $this->logger->warn('[CDP] Warning: Transactional messaging email will NOT be sent to Customer.io to avoid sending twice. To turn this warning off set `sendToCustomerIo` to false.');
    }

    // Send to CDP
    try {
      $response = $this->httpClient->post('v1/send/email', [
        'json' => $payload,
      ]);

      $data = json_decode((string) $response->getBody(), true);

      if ($this->config->debug) {
        $this->logger->debug("[CDP] Email sent successfully to {$request->to}");
      }

      return $data ?? ['ok' => true];
    } catch (GuzzleException $e) {
      $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
      $statusCode = $response ? $response->getStatusCode() : 400;
      $responseBody = $this->extractResponseBody($response);
      $responseData = $responseBody ? json_decode($responseBody, true) : null;

      $errorSummary = [
        'message' => $e->getMessage(),
        'status' => $statusCode,
        'data' => $responseData['message'] ?? '[truncated]',
      ];

      if ($this->config->debug) {
        $this->logger->error('[CDP] Send email error', ['errorSummary' => $errorSummary]);
      }

      if ($this->config->failOnException) {
        $exception = new CDPEmailException($responseData['message'] ?? $e->getMessage(), $statusCode);
        $exception->summary = $errorSummary;
        $exception->status = $statusCode;
        throw $exception;
      }

      return ['ok' => false, 'error' => $errorSummary];
    }
  }

  /**
   * Safely extracts response body content from a response, handling both seekable and non-seekable streams
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response The HTTP response
   * @return string|null The response body content or null if response is null
   */
  private function extractResponseBody(?\Psr\Http\Message\ResponseInterface $response): ?string
  {
    if ($response === null) {
      return null;
    }

    $body = $response->getBody();
    $contents = $body->getContents();

    // Rewind if possible for potential future reads
    if ($body->isSeekable()) {
      $body->rewind();
    }

    return $contents;
  }

  /**
   * Warns about unsupported fields that are accepted but not processed by the backend
   */
  private function warnUnsupportedFields(SendEmailRequest $request): void
  {
    $unsupportedFields = [];

    if ($request->send_at !== null)
      $unsupportedFields[] = 'send_at';
    if ($request->disable_message_retention !== null)
      $unsupportedFields[] = 'disable_message_retention';
    if ($request->send_to_unsubscribed !== null)
      $unsupportedFields[] = 'send_to_unsubscribed';
    if ($request->queue_draft !== null)
      $unsupportedFields[] = 'queue_draft';
    if ($request->headers !== null)
      $unsupportedFields[] = 'headers';
    if ($request->disable_css_preprocessing !== null)
      $unsupportedFields[] = 'disable_css_preprocessing';
    if ($request->tracked !== null)
      $unsupportedFields[] = 'tracked';
    if ($request->fake_bcc !== null)
      $unsupportedFields[] = 'fake_bcc';
    if ($request->reply_to !== null)
      $unsupportedFields[] = 'reply_to';
    if ($request->preheader !== null)
      $unsupportedFields[] = 'preheader';
    if ($request->attachments !== null)
      $unsupportedFields[] = 'attachments';

    if (!empty($unsupportedFields) && $this->config->debug) {
      $this->logger->warn(
        '[CDP] Warning: The following fields are not yet supported by the backend and will be ignored: ' .
        implode(', ', $unsupportedFields) . '. ' .
        'These fields are included for future compatibility but have no effect on email delivery.'
      );
    }
  }

  /**
   * Send a push notification using the OpenCDP transactional push service
   *
   * @param SendPushRequest $request The send push request parameters
   * @return array<string, mixed> Response from the API. Returns error array with 'ok' => false when failOnException is false and an error occurs.
   * @throws CDPPushException Only when config->failOnException === true
   * @throws \InvalidArgumentException Only when config->failOnException === true
   */
  public function sendPush(SendPushRequest $request): array
  {
    try {
      Validators::validateSendPushRequest($request);
    } catch (\InvalidArgumentException $e) {
      if ($this->config->debug) {
        $this->logger->error('[CDP] Send push validation error', ['error' => $e->getMessage()]);
      }
      if ($this->config->failOnException) {
        throw $e;
      }
      return ['ok' => false, 'error' => $e->getMessage()];
    }

    // Build the payload
    $payload = $request->toArray();

    // Warning about Customer.io dual-write
    if ($this->config->sendToCustomerIo && $this->customerIoClient !== null && $this->config->debug) {
      $this->logger->warn('[CDP] Warning: Transactional messaging push will NOT be sent to Customer.io to avoid sending twice. To turn this warning off set `sendToCustomerIo` to false.');
    }

    // Send to CDP
    try {
      $response = $this->httpClient->post('v1/send/push', [
        'json' => $payload,
      ]);

      $data = json_decode((string) $response->getBody(), true);

      if ($this->config->debug) {
        $this->logger->debug('[CDP] Push notification sent successfully');
      }

      return $data ?? ['ok' => true];
    } catch (GuzzleException $e) {
      $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
      $statusCode = $response ? $response->getStatusCode() : 400;
      $responseBody = $this->extractResponseBody($response);
      $responseData = $responseBody ? json_decode($responseBody, true) : null;

      $errorSummary = [
        'message' => $e->getMessage(),
        'status' => $statusCode,
        'data' => $responseData['message'] ?? '[truncated]',
      ];

      if ($this->config->debug) {
        $this->logger->error('[CDP] Send push error', ['errorSummary' => $errorSummary]);
      }

      if ($this->config->failOnException) {
        $exception = new CDPPushException($responseData['message'] ?? $e->getMessage(), $statusCode);
        $exception->summary = $errorSummary;
        $exception->status = $statusCode;
        throw $exception;
      }

      return ['ok' => false, 'error' => $errorSummary];
    }
  }

  /**
   * Send an SMS using the OpenCDP transactional SMS service
   *
   * @param SendSmsRequest $request The send SMS request parameters
   * @return array<string, mixed> Response from the API. Returns error array with 'ok' => false when failOnException is false and an error occurs.
   * @throws CDPSmsException Only when config->failOnException === true
   * @throws \InvalidArgumentException Only when config->failOnException === true
   */
  public function sendSms(SendSmsRequest $request): array
  {
    try {
      Validators::validateSendSmsRequest($request);
    } catch (\InvalidArgumentException $e) {
      if ($this->config->debug) {
        $this->logger->error('[CDP] Send SMS validation error', ['error' => $e->getMessage()]);
      }
      if ($this->config->failOnException) {
        throw $e;
      }
      return ['ok' => false, 'error' => $e->getMessage()];
    }

    // Build the payload and convert transactional_message_id to string
    $payload = $request->toArray();
    if (isset($payload['transactional_message_id'])) {
      $payload['transactional_message_id'] = (string) $payload['transactional_message_id'];
    }

    // Warning about Customer.io dual-write
    if ($this->config->sendToCustomerIo && $this->customerIoClient !== null && $this->config->debug) {
      $this->logger->warn('[CDP] Warning: Transactional messaging SMS will NOT be sent to Customer.io to avoid sending twice. To turn this warning off set `sendToCustomerIo` to false.');
    }

    // Send to CDP
    try {
      $response = $this->httpClient->post('v1/send/sms', [
        'json' => $payload,
      ]);

      $data = json_decode((string) $response->getBody(), true);

      if ($this->config->debug) {
        $this->logger->debug('[CDP] SMS sent successfully');
      }

      return $data ?? ['ok' => true];
    } catch (GuzzleException $e) {
      $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
      $statusCode = $response ? $response->getStatusCode() : 400;
      $responseBody = $this->extractResponseBody($response);
      $responseData = $responseBody ? json_decode($responseBody, true) : null;

      $errorSummary = [
        'message' => $e->getMessage(),
        'status' => $statusCode,
        'data' => $responseData['message'] ?? '[truncated]',
      ];

      if ($this->config->debug) {
        $this->logger->error('[CDP] Send SMS error', ['errorSummary' => $errorSummary]);
      }

      if ($this->config->failOnException) {
        $exception = new CDPSmsException($responseData['message'] ?? $e->getMessage(), $statusCode);
        $exception->summary = $errorSummary;
        $exception->status = $statusCode;
        throw $exception;
      }

      return ['ok' => false, 'error' => $errorSummary];
    }
  }
}
