<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Exceptions;

/**
 * Exception thrown when email sending fails
 */
class CDPEmailException extends CDPException
{
  public string $errorCode = 'EMAIL_SEND_FAILED';
}
