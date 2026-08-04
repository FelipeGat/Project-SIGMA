<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Kernel\Contract\ConfigSchema;
use Sigma\Kernel\ConfigurationProvider;

final class ConfigurationProviderTest extends TestCase
{
    public function test_resolves_required_and_optional_keys(): void
    {
        $schema = new ConfigSchema(required: ['REDIS_HOST'], optional: ['REDIS_PORT'], defaults: ['REDIS_PORT' => '6379']);

        $config = new ConfigurationProvider($schema, ['REDIS_HOST' => 'localhost'], 'event-bus');

        self::assertSame('localhost', $config->require('REDIS_HOST'));
        self::assertSame('6379', $config->get('REDIS_PORT'));
    }

    public function test_fails_explicitly_when_a_required_key_is_missing(): void
    {
        $schema = new ConfigSchema(required: ['REDIS_HOST']);

        $this->expectException(SigmaException::class);
        $this->expectExceptionMessage('event-bus');

        new ConfigurationProvider($schema, [], 'event-bus');
    }

    public function test_optional_key_without_default_resolves_to_null(): void
    {
        $schema = new ConfigSchema(optional: ['REDIS_PASSWORD']);

        $config = new ConfigurationProvider($schema, [], 'event-bus');

        self::assertFalse($config->has('REDIS_PASSWORD'));
        self::assertNull($config->get('REDIS_PASSWORD'));
    }
}
