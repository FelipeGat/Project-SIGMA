<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * Autentica uma Identity (não diretamente um User — ver ADR-0065),
 * prepara múltiplas Sessions concorrentes por pessoa, uma por
 * contexto. Workspace é selecionado uma única vez por Session — trocar
 * de Workspace exige uma nova Session (Context imutável, ADR-0066).
 */
final class Session
{
    private function __construct(
        private readonly SessionId $id,
        private readonly IdentityId $identityId,
        private readonly \DateTimeImmutable $issuedAt,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly ?WorkspaceId $workspaceId,
    ) {
    }

    public static function start(IdentityId $identityId, \DateTimeImmutable $now, \DateInterval $ttl): self
    {
        return new self(SessionId::generate(), $identityId, $now, $now->add($ttl), workspaceId: null);
    }

    /** Reidrata uma Session já existente a partir de dados persistidos (Infrastructure, Release 3B). */
    public static function reconstitute(
        SessionId $id,
        IdentityId $identityId,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
        ?WorkspaceId $workspaceId,
    ): self {
        return new self($id, $identityId, $issuedAt, $expiresAt, $workspaceId);
    }

    public function id(): SessionId
    {
        return $this->id;
    }

    public function identityId(): IdentityId
    {
        return $this->identityId;
    }

    public function issuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function workspaceId(): ?WorkspaceId
    {
        return $this->workspaceId;
    }

    public function hasWorkspaceSelected(): bool
    {
        return $this->workspaceId !== null;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    /**
     * Retorna uma NOVA Session com o Workspace selecionado — nunca
     * muta esta instância. Só pode ser chamado uma vez por Session;
     * selecionar outro Workspace depois exige uma Session nova
     * (ver Session::start()), não uma segunda chamada aqui.
     */
    public function withWorkspaceSelected(WorkspaceId $workspaceId): self
    {
        if ($this->workspaceId !== null) {
            throw new SigmaException(
                'Esta Session já tem um Workspace selecionado — trocar de Workspace exige uma nova Session.',
                'identity.workspace_already_selected',
            );
        }

        return new self($this->id, $this->identityId, $this->issuedAt, $this->expiresAt, $workspaceId);
    }
}
