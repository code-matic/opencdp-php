<?php

declare(strict_types=1);

namespace Codematic\OpenCDP;

/**
 * Logger interface for CDP operations
 */
interface LoggerInterface
{
    /**
     * Log a debug message
     *
     * @param string $message The message to log
     * @param array<string, mixed> $context Additional context data
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log an error message
     *
     * @param string $message The message to log
     * @param array<string, mixed> $context Additional context data
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log a warning message
     *
     * @param string $message The message to log
     * @param array<string, mixed> $context Additional context data
     */
    public function warn(string $message, array $context = []): void;
}
