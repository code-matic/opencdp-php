<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Exceptions;

/**
 * Exception thrown when SMS sending fails
 */
class CDPSmsException extends CDPException
{
  public string $errorCode = 'SMS_SEND_FAILED';
}
