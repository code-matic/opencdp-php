<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Exceptions;

/**
 * Exception thrown when push notification sending fails
 */
class CDPPushException extends CDPException
{
  public string $errorCode = 'PUSH_SEND_FAILED';
}
