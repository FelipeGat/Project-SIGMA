<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * A unidade de contexto operacional dentro de uma Company
 * (IDENTITY_MODEL.md#workspace).
 */
final class Workspace
{
    public function __construct(
        private readonly WorkspaceId $id,
        private readonly CompanyId $companyId,
        private readonly string $name,
    ) {
    }

    public function id(): WorkspaceId
    {
        return $this->id;
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
