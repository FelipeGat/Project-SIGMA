<?php

declare(strict_types=1);

namespace Sigma\Kernel\Contract;

/**
 * O que um Module responde quando perguntado "quem é você" — ver
 * SYSTEM_MANIFEST.md § Self-Describing Components e ADR-0046.
 */
final class ComponentDescriptor
{
    /**
     * @param string[] $capabilities
     * @param string[] $dependencies
     */
    public function __construct(
        public readonly string $id,
        public readonly ModuleKind $type,
        public readonly string $version,
        public readonly ModuleStatus $status,
        public readonly array $capabilities = [],
        public readonly array $dependencies = [],
    ) {
    }

    /**
     * @return array{id: string, type: string, version: string, status: string, capabilities: string[], dependencies: string[]}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'version' => $this->version,
            'status' => $this->status->value,
            'capabilities' => $this->capabilities,
            'dependencies' => $this->dependencies,
        ];
    }
}
