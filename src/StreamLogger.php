<?php

declare(strict_types=1);

namespace Waffle\Commons\Log;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Stringable;
use Waffle\Commons\Log\Enum\LogChannel;

/**
 * A strict PSR-3 logger that writes JSON-formatted logs to a stream.
 * Optimized for Docker/Kubernetes environments (stdout/stderr).
 */
final class StreamLogger extends AbstractLogger
{
    /**
     * Standard Monolog/RFC 5424 log levels.
     */
    private const LEVELS = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    /** @var resource */
    private $stream;

    /**
     * @param string $streamPath The path to the stream (e.g., 'php://stderr', 'php://stdout', '/var/log/app.log').
     * @param string $channel The log channel name (e.g. 'app', 'security').
     * @param int $permissions UNIX permissions if creating a file (default 0644).
     * @throws InvalidArgumentException If the stream cannot be opened.
     */
    public function __construct(
        private(set) readonly string $streamPath = 'php://stderr',
        private(set) readonly string $channel = LogChannel::APP,
        private(set) readonly int $permissions = 0o644,
    ) {
        // 'a' mode: Open for writing only; place the file pointer at the end of the file.
        // 'b' mode: Binary safe mode.
        // If the file does not exist, attempt to create it.
        $resource = fopen($this->streamPath, 'ab');

        if (!is_resource($resource)) {
            throw new InvalidArgumentException(sprintf('The stream "%s" could not be opened.', $this->streamPath));
        }

        // Apply permissions if it's a regular file and we just created/opened it
        // We do not chmod streams like php://stdout
        if (str_starts_with($this->streamPath, 'php://') === false && file_exists($this->streamPath)) {
            chmod($this->streamPath, $this->permissions);
        }

        $this->stream = $resource;
    }

    /**
     * Closes the resource when the logger is destroyed to prevent leaks in long-running processes.
     */
    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $timestamp = new DateTimeImmutable();

        // 1. Interpolate message with context (PSR-3 Requirement 1.2)
        $interpolatedMessage = $this->interpolate((string) $message, $context);
        $levelStr = (string) $level;

        // 2. Build Symfony/Monolog compatible payload
        // We force 'context' and 'extra' to be objects {} in JSON if empty, rather than []
        $payload = [
            'channel' => $this->channel,
            'message' => $interpolatedMessage,
            'context' => $context,
            'level' => self::LEVELS[$levelStr] ?? 0,
            'level_name' => strtoupper($levelStr),
            'datetime' => $timestamp->format(DateTimeInterface::RFC3339_EXTENDED),
            'extra' => [], // Placeholder for future extensibility
        ];

        // 3. Encode to JSON
        try {
            // JSON_UNESCAPED_SLASHES and UNICODE make logs readable for humans in the console
            // JSON_THROW_ON_ERROR allows us to catch serialization issues
            $line = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            // Fallback for non-encodable data (e.g. recursion or binary data in context)
            // We must NEVER crash the app just because logging failed.
            $line = json_encode([
                'timestamp' => $payload['datetime'],
                'level' => 'critical',
                'message' => 'Log Serialization Failed: ' . $e->getMessage(),
            ], JSON_THROW_ON_ERROR);
        }

        // 4. Write to stream (Atomic write attempt)
        if (is_resource($this->stream)) {
            fwrite($this->stream, $line . PHP_EOL);
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     * Logic strictly follows PSR-3 specification.
     */
    private function interpolate(string $message, array $context = []): string
    {
        // If context is empty, no replacement needed (Performance optimization)
        if ($context === []) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $val) {
            // Check that the value can be cast to string
            if (!(!is_array($val) && (!is_object($val) || method_exists($val, '__toString')))) {
                continue;
            }

            $replace['{' . $key . '}'] = (string) $val;
        }

        return strtr($message, $replace);
    }
}
