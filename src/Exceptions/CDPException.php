<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Exceptions;

/**
 * Base exception for CDP errors
 */
class CDPException extends \Exception
{
  /** @var array<string, mixed> Error summary */
  public array $summary = [];

  /** @var int HTTP status code */
  public int $status = 400;

  /** @var string Error code */
  public string $errorCode = 'CDP_ERROR';

  /**
   * @param string $message
   * @param int $status HTTP status code
   * @param \Throwable|null $previous
   */
  public function __construct(string $message = '', int $status = 400, ?\Throwable $previous = null)
  {
    parent::__construct($message, 0, $previous);
    $this->status = $status;
  }
}
