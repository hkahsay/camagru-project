<?php

declare(strict_types=1);

final class TestCase
{
    private int $passed = 0;
    private int $failed = 0;

    public function test(string $name, callable $callback): void
    {
        try {
            $callback($this);
            $this->passed++;
            echo '.';
        } catch (Throwable $exception) {
            $this->failed++;
            echo "\nFAIL: {$name}\n{$exception->getMessage()}\n";
        }
    }

    public function assertTrue(bool $actual, string $message = 'Expected true.'): void
    {
        if (!$actual) {
            throw new RuntimeException($message);
        }
    }

    public function assertFalse(bool $actual, string $message = 'Expected false.'): void
    {
        if ($actual) {
            throw new RuntimeException($message);
        }
    }

    public function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message ?: 'Values are not the same.');
        }
    }

    public function assertMatches(string $pattern, string $actual, string $message = ''): void
    {
        if (preg_match($pattern, $actual) !== 1) {
            throw new RuntimeException($message ?: 'Value does not match the expected pattern.');
        }
    }

    public function finish(): int
    {
        echo "\n{$this->passed} passed, {$this->failed} failed.\n";

        return $this->failed === 0 ? 0 : 1;
    }
}
