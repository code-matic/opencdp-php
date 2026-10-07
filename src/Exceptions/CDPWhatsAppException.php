<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Exceptions;

/**
 * Exception thrown when WhatsApp sending fails
 */
class CDPWhatsAppException extends CDPException
{
  public string $errorCode = 'WHATSAPP_SEND_FAILED';
}
