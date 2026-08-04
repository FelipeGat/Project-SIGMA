<?php

declare(strict_types=1);

namespace Sigma\Core\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\Envelope;

final class EnvelopeTest extends TestCase
{
    public function test_success_carries_the_given_data_and_success_true(): void
    {
        $envelope = Envelope::success(['status' => 'ready']);

        self::assertTrue($envelope['success']);
        self::assertSame(['status' => 'ready'], $envelope['data']);
        self::assertNull($envelope['error']);
    }

    public function test_failure_carries_the_error_code_and_message(): void
    {
        $envelope = Envelope::failure('route.not_found', 'Rota inexistente.');

        self::assertFalse($envelope['success']);
        self::assertSame(['code' => 'route.not_found', 'message' => 'Rota inexistente.'], $envelope['error']);
    }

    public function test_correlation_id_and_request_id_are_unique_per_call(): void
    {
        $first = Envelope::success();
        $second = Envelope::success();

        self::assertNotSame($first['correlationId'], $second['correlationId']);
        self::assertNotSame($first['requestId'], $second['requestId']);
    }

    public function test_protocol_version_is_always_present(): void
    {
        self::assertSame('1.0', Envelope::success()['protocolVersion']);
    }
}
