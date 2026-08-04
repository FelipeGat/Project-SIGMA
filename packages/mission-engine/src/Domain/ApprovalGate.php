<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * Um pedido de decisão humana pendente — o que bloqueia uma Mission
 * em `PendingApproval` (ADR-0090). Vive inteiramente dentro do limite
 * do aggregate `Mission`, mesma convenção de `Subtask`.
 */
final class ApprovalGate
{
    private ApprovalDecisionStatus $decision = ApprovalDecisionStatus::Pending;
    private ?\DateTimeImmutable $decidedAt = null;
    private ?string $decidedBy = null;

    public function __construct(
        private readonly ApprovalGateId $id,
        public readonly string $reason,
        public readonly \DateTimeImmutable $requestedAt,
    ) {
    }

    /**
     * Hidratação a partir de estado já persistido (Release 5C) — nunca
     * dispara evento algum, mesmo espírito de `Mission::reconstitute()`.
     */
    public static function reconstitute(
        ApprovalGateId $id,
        string $reason,
        \DateTimeImmutable $requestedAt,
        ApprovalDecisionStatus $decision,
        ?\DateTimeImmutable $decidedAt,
        ?string $decidedBy,
    ): self {
        $gate = new self($id, $reason, $requestedAt);
        $gate->decision = $decision;
        $gate->decidedAt = $decidedAt;
        $gate->decidedBy = $decidedBy;

        return $gate;
    }

    public function id(): ApprovalGateId
    {
        return $this->id;
    }

    public function decision(): ApprovalDecisionStatus
    {
        return $this->decision;
    }

    public function decidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function decidedBy(): ?string
    {
        return $this->decidedBy;
    }

    public function approve(string $decidedBy, \DateTimeImmutable $now): void
    {
        $this->assertPending();
        $this->decision = ApprovalDecisionStatus::Approved;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = $now;
    }

    public function reject(string $decidedBy, \DateTimeImmutable $now): void
    {
        $this->assertPending();
        $this->decision = ApprovalDecisionStatus::Rejected;
        $this->decidedBy = $decidedBy;
        $this->decidedAt = $now;
    }

    private function assertPending(): void
    {
        if ($this->decision !== ApprovalDecisionStatus::Pending) {
            throw new SigmaException('Este ApprovalGate já foi decidido.', 'mission.approval_gate_already_decided');
        }
    }
}
