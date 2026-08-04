<?php

declare(strict_types=1);

namespace Sigma\Kernel\Tests;

use PHPUnit\Framework\TestCase;
use Sigma\Core\SigmaException;
use Sigma\Kernel\Container;

final class ContainerTest extends TestCase
{
    public function test_resolves_a_pre_built_instance(): void
    {
        $container = new Container();
        $instance = new \stdClass();

        $container->bind('thing', $instance);

        self::assertSame($instance, $container->get('thing'));
    }

    public function test_resolves_a_closure_factory_lazily_and_only_once(): void
    {
        $container = new Container();
        $calls = 0;

        $container->bind('thing', function () use (&$calls) {
            $calls++;

            return new \stdClass();
        });

        self::assertSame(0, $calls);

        $first = $container->get('thing');
        $second = $container->get('thing');

        self::assertSame(1, $calls);
        self::assertSame($first, $second);
    }

    public function test_has_reports_whether_a_binding_exists(): void
    {
        $container = new Container();

        self::assertFalse($container->has('missing'));

        $container->bind('present', new \stdClass());

        self::assertTrue($container->has('present'));
    }

    public function test_throws_when_resolving_an_unknown_binding(): void
    {
        $container = new Container();

        $this->expectException(SigmaException::class);

        $container->get('missing');
    }
}
