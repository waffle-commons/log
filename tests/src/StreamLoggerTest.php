<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\InvalidArgumentException;
use Waffle\Commons\Log\StreamLogger;

#[CoversClass(StreamLogger::class)]
final class StreamLoggerTest extends AbstractTestCase
{
    public function testItWritesJsonLogToStream(): void
    {
        // 1. Arrange: Use a temporary file to simulate the stream
        // php://memory is tricky because once closed/destructed, content is lost,
        // and we can't easily share the resource handle with the class being tested.
        $tempFile = tempnam(sys_get_temp_dir(), 'waffle_log_test');

        try {
            $logger = new StreamLogger($tempFile);

            // 2. Act
            $logger->info('User {username} logged in', ['username' => 'waffle_bot', 'id' => 42]);

            // Force destructor (optional, but ensures flush/close) or just read file
            unset($logger);

            // 3. Assert
            $content = file_get_contents($tempFile);
            $this->assertNotEmpty($content, 'Log file should not be empty');

            $json = json_decode($content, true);

            self::assertIsArray($json, 'Log output should be valid JSON');
            self::assertSame(200, $json['level']);
            self::assertSame('INFO', $json['level_name']);
            self::assertSame('User waffle_bot logged in', $json['message']);
            self::assertSame('waffle_bot', $json['context']['username']);
            self::assertSame(42, $json['context']['id']);
            self::assertArrayHasKey('datetime', $json);
        } finally {
            // Cleanup
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testItThrowsExceptionForInvalidStream(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Use an invalid protocol to guarantee failure regardless of filesystem permissions
        new StreamLogger('invalid-protocol://stream');
    }

    public function testContextInterpolationComplex(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'waffle_log_interpolate');
        $logger = new StreamLogger($tempFile);

        // Test with int, string and object implementing __toString
        $date = new \DateTime('2026-01-01'); // Has no __toString by default? Wait, DateTime doesn't.
        // Let's use a Stringable object
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'StringableObj';
            }
        };

        $logger->error('Error: {code} on {obj}', ['code' => 500, 'obj' => $stringable]);

        $content = file_get_contents($tempFile);
        $json = json_decode($content, true);

        self::assertSame('Error: 500 on StringableObj', $json['message']);

        unlink($tempFile);
    }

    public function testSerializationFailureFallback(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'waffle_log_fail');
        $logger = new StreamLogger($tempFile);

        // Create data that fails JSON encoding (binary or infinite recursion)
        $badData = [];
        $badData['loop'] = &$badData;

        $logger->warning('Watch out', ['context_key' => $badData]);

        $content = file_get_contents($tempFile);
        $json = json_decode($content, true);

        self::assertSame('critical', $json['level']);
        self::assertStringContainsString('Log Serialization Failed', $json['message']);

        unlink($tempFile);
    }
}
