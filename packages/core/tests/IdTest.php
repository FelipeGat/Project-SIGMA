<?php

declare(strict_types=1);

namespace Sigma\Core\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\Id;

final class IdTest extends TestCase
{
    public function test_generates_a_valid_uuid_v4(): void
    {
        $id = Id::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id,
        );
    }

    public function test_generates_unique_values(): void
    {
        $ids = array_map(static fn () => Id::generate(), range(1, 100));

        self::assertCount(100, array_unique($ids));
    }
}
