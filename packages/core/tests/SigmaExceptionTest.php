<?php

declare(strict_types=1);

namespace Sigma\Core\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;

final class SigmaExceptionTest extends TestCase
{
    public function test_carries_an_error_code_for_the_envelope(): void
    {
        $exception = new SigmaException('Falha ao carregar Module', 'module.load_failed');

        self::assertSame('Falha ao carregar Module', $exception->getMessage());
        self::assertSame('module.load_failed', $exception->errorCode());
    }

    public function test_preserves_the_previous_exception(): void
    {
        $previous = new \InvalidArgumentException('causa raiz');
        $exception = new SigmaException('falha', 'x.y', $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
