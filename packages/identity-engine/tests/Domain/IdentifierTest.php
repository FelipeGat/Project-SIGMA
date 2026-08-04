<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\WorkspaceId;

final class IdentifierTest extends TestCase
{
    public function test_generate_produces_a_non_empty_value(): void
    {
        $id = TenantId::generate();

        self::assertNotSame('', $id->toString());
    }

    public function test_two_generated_ids_are_never_equal(): void
    {
        self::assertFalse(TenantId::generate()->equals(TenantId::generate()));
    }

    public function test_from_string_with_the_same_value_is_equal(): void
    {
        $value = TenantId::generate()->toString();

        self::assertTrue(TenantId::fromString($value)->equals(TenantId::fromString($value)));
    }

    public function test_ids_of_different_types_with_the_same_value_are_never_equal(): void
    {
        $value = TenantId::generate()->toString();

        self::assertFalse(TenantId::fromString($value)->equals(WorkspaceId::fromString($value)));
    }

    public function test_from_string_rejects_empty_value(): void
    {
        $this->expectException(SigmaException::class);

        TenantId::fromString('');
    }

    public function test_to_string_cast_returns_the_value(): void
    {
        $id = TenantId::generate();

        self::assertSame($id->toString(), (string) $id);
    }
}
