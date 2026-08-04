<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * Um agrupamento de Users, escopado a uma Company — tipado como System
 * Team (CTO/Developer/Support) ou Business Team (Comercial/Financeiro/
 * Obras), ver ADR-0064 (IDENTITY_MODEL.md#team).
 */
final class Team
{
    /** @var list<UserId> */
    private array $memberIds;

    /** @param list<UserId> $memberIds */
    public function __construct(
        private readonly TeamId $id,
        private readonly CompanyId $companyId,
        private readonly string $name,
        private readonly TeamType $type,
        array $memberIds = [],
    ) {
        $this->memberIds = $memberIds;
    }

    public function id(): TeamId
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

    /** @return list<UserId> */
    public function members(): array
    {
        return $this->memberIds;
    }

    public function type(): TeamType
    {
        return $this->type;
    }

    public function addMember(UserId $userId): void
    {
        if ($this->hasMember($userId)) {
            return;
        }

        $this->memberIds[] = $userId;
    }

    public function removeMember(UserId $userId): void
    {
        $this->memberIds = array_values(array_filter(
            $this->memberIds,
            static fn (UserId $id): bool => !$id->equals($userId),
        ));
    }

    public function hasMember(UserId $userId): bool
    {
        foreach ($this->memberIds as $memberId) {
            if ($memberId->equals($userId)) {
                return true;
            }
        }

        return false;
    }
}
