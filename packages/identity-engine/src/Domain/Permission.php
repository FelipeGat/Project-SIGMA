<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * Uma ação discreta e concedível, identificada por uma chave no formato
 * `<recurso>.<ação>` (ex: `mission.create`), sempre concedida através de
 * um Role — nunca diretamente a um User/Team (IDENTITY_MODEL.md#permission).
 * A mesma chave usada em `permissions` num Sigma Contract.
 */
final class Permission
{
    private function __construct(private readonly string $key)
    {
    }

    public static function fromKey(string $key): self
    {
        if (!preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $key)) {
            throw new SigmaException(
                sprintf('Chave de Permission inválida: "%s" — esperado o formato "recurso.acao".', $key),
                'identity.invalid_permission_key',
            );
        }

        return new self($key);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function equals(self $other): bool
    {
        return $this->key === $other->key;
    }
}
