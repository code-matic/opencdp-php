<?php

declare(strict_types=1);

namespace Codematic\OpenCDP\Tests;

use PHPUnit\Framework\TestCase;
use Codematic\OpenCDP\Exceptions\CDPException;
use Codematic\OpenCDP\Exceptions\CDPEmailException;
use Codematic\OpenCDP\Exceptions\CDPPushException;
use Codematic\OpenCDP\Exceptions\CDPSmsException;

class ExceptionTest extends TestCase
{
  public function testCDPExceptionHasErrorCodeProperty(): void
  {
    $exception = new CDPException('Test message', 400);
    $this->assertEquals('CDP_ERROR', $exception->errorCode);
    $this->assertIsString($exception->errorCode);
  }

  public function testCDPExceptionHasSummaryProperty(): void
  {
    $exception = new CDPException('Test message', 400);
    $this->assertIsArray($exception->summary);
    $this->assertEmpty($exception->summary);
  }

  public function testCDPExceptionHasStatusProperty(): void
  {
    $exception = new CDPException('Test message', 500);
    // The constructor now accepts status as second parameter
    $this->assertEquals(500, $exception->status);
    $this->assertIsInt($exception->status);
  }

  public function testCDPExceptionCanSetSummary(): void
  {
    $exception = new CDPException('Test message', 400);
    $exception->summary = ['key' => 'value'];
    $this->assertEquals(['key' => 'value'], $exception->summary);
  }

  public function testCDPEmailExceptionHasCorrectErrorCode(): void
  {
    $exception = new CDPEmailException('Email failed', 400);
    $this->assertEquals('EMAIL_SEND_FAILED', $exception->errorCode);
  }

  public function testCDPEmailExceptionInheritsFromCDPException(): void
  {
    $exception = new CDPEmailException('Email failed', 400);
    $this->assertInstanceOf(CDPException::class, $exception);
  }

  public function testCDPPushExceptionHasCorrectErrorCode(): void
  {
    $exception = new CDPPushException('Push failed', 400);
    $this->assertEquals('PUSH_SEND_FAILED', $exception->errorCode);
  }

  public function testCDPPushExceptionInheritsFromCDPException(): void
  {
    $exception = new CDPPushException('Push failed', 400);
    $this->assertInstanceOf(CDPException::class, $exception);
  }

  public function testCDPSmsExceptionHasCorrectErrorCode(): void
  {
    $exception = new CDPSmsException('SMS failed', 400);
    $this->assertEquals('SMS_SEND_FAILED', $exception->errorCode);
  }

  public function testCDPSmsExceptionInheritsFromCDPException(): void
  {
    $exception = new CDPSmsException('SMS failed', 400);
    $this->assertInstanceOf(CDPException::class, $exception);
  }

  public function testExceptionCanBeThrownAndCaught(): void
  {
    $this->expectException(CDPException::class);
    $this->expectExceptionMessage('Test error');
    throw new CDPException('Test error', 400);
  }
}
