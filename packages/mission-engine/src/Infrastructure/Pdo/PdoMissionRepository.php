<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Infrastructure\Pdo;

use Sigma\MissionEngine\Application\MissionRepository;
use Sigma\MissionEngine\Domain\Actor;
use Sigma\MissionEngine\Domain\ActorType;
use Sigma\MissionEngine\Domain\ApprovalDecisionStatus;
use Sigma\MissionEngine\Domain\ApprovalGate;
use Sigma\MissionEngine\Domain\ApprovalGateId;
use Sigma\MissionEngine\Domain\Compensation;
use Sigma\MissionEngine\Domain\CompensationResult;
use Sigma\MissionEngine\Domain\CorrelationId;
use Sigma\MissionEngine\Domain\IntentId;
use Sigma\MissionEngine\Domain\Mission;
use Sigma\MissionEngine\Domain\MissionHistoryEntry;
use Sigma\MissionEngine\Domain\MissionId;
use Sigma\MissionEngine\Domain\MissionStatus;
use Sigma\MissionEngine\Domain\Plan;
use Sigma\MissionEngine\Domain\PlanSource;
use Sigma\MissionEngine\Domain\RetryAttempt;
use Sigma\MissionEngine\Domain\Subtask;
use Sigma\MissionEngine\Domain\SubtaskCandidate;
use Sigma\MissionEngine\Domain\SubtaskId;
use Sigma\MissionEngine\Domain\SubtaskStatus;
use Sigma\MissionEngine\Domain\TenantId;
use Sigma\MissionEngine\Domain\WorkspaceId;

/**
 * PDO puro (ADR-0061). `save()` faz upsert do aggregate inteiro numa
 * transação — `missions`/`subtasks`/`approval_gates`/
 * `subtask_retry_attempts` têm identidade própria (`Identifier`),
 * upsertados por essa chave; `compensations`/`mission_history` não têm
 * identidade própria no Domain, então são substituídas por completo
 * (DELETE + INSERT) a cada `save()` — mais simples e igualmente
 * correto dado o tamanho pequeno dessas listas (ver Decision Log da
 * Release 5C).
 */
final class PdoMissionRepository implements MissionRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Mission $mission): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->upsertMission($mission);

            foreach ($mission->subtasks() as $subtask) {
                $this->upsertSubtask($mission->id(), $subtask);
            }

            foreach ($mission->approvalGates() as $gate) {
                $this->upsertApprovalGate($mission->id(), $gate);
            }

            $this->replaceCompensations($mission->id(), $mission->compensations());
            $this->replaceHistory($mission->id(), $mission->history());

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    public function find(MissionId $id): ?Mission
    {
        $statement = $this->pdo->prepare('SELECT * FROM missions WHERE id = ?');
        $statement->execute([$id->toString()]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $subtasks = $this->loadSubtasks($id);
        $approvalGates = $this->loadApprovalGates($id);
        $compensations = $this->loadCompensations($id);
        $history = $this->loadHistory($id);

        $pendingGate = null;
        if ($row['pending_approval_gate_id'] !== null) {
            foreach ($approvalGates as $gate) {
                if ($gate->id()->toString() === $row['pending_approval_gate_id']) {
                    $pendingGate = $gate;

                    break;
                }
            }
        }

        return Mission::reconstitute(
            MissionId::fromString($row['id']),
            TenantId::fromString($row['tenant_id']),
            $row['workspace_id'] !== null ? WorkspaceId::fromString($row['workspace_id']) : null,
            CorrelationId::fromString($row['correlation_id']),
            $row['intent_id'] !== null ? IntentId::fromString($row['intent_id']) : null,
            $row['objective'],
            $this->decodePlan($row['plan_json']),
            new Actor(ActorType::from($row['actor_type']), $row['actor_id']),
            (int) $row['autonomy_ceiling'],
            MissionStatus::from($row['status']),
            $subtasks,
            $approvalGates,
            $pendingGate,
            $compensations,
            $history,
            new \DateTimeImmutable($row['created_at']),
            $row['started_at'] !== null ? new \DateTimeImmutable($row['started_at']) : null,
            $row['finished_at'] !== null ? new \DateTimeImmutable($row['finished_at']) : null,
        );
    }

    private function upsertMission(Mission $mission): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO missions
                (id, tenant_id, workspace_id, correlation_id, intent_id, objective, plan_json, actor_type, actor_id, autonomy_ceiling, status, pending_approval_gate_id, created_at, started_at, finished_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                pending_approval_gate_id = VALUES(pending_approval_gate_id),
                started_at = VALUES(started_at),
                finished_at = VALUES(finished_at)',
        );
        $statement->execute([
            $mission->id()->toString(),
            $mission->tenantId()->toString(),
            $mission->workspaceId()?->toString(),
            $mission->correlationId()->toString(),
            $mission->intentId()?->toString(),
            $mission->objective(),
            $this->encodePlan($mission->plan()),
            $mission->actor()->type->value,
            $mission->actor()->id,
            $mission->autonomyCeiling(),
            $mission->status()->value,
            $mission->pendingApprovalGate()?->id()->toString(),
            $mission->createdAt()->format('Y-m-d H:i:s'),
            $mission->startedAt()?->format('Y-m-d H:i:s'),
            $mission->finishedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    private function upsertSubtask(MissionId $missionId, Subtask $subtask): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO subtasks (id, mission_id, description, candidate_agent, candidate_capability, status, result_json)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), result_json = VALUES(result_json)',
        );
        $statement->execute([
            $subtask->id()->toString(),
            $missionId->toString(),
            $subtask->description,
            $subtask->candidateAgent,
            $subtask->candidateCapability,
            $subtask->status()->value,
            $subtask->result() !== null ? json_encode($subtask->result(), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) : null,
        ]);

        $retryStatement = $this->pdo->prepare(
            'INSERT INTO subtask_retry_attempts (subtask_id, attempt_number, reason, at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason)',
        );
        foreach ($subtask->retryAttempts() as $attempt) {
            $retryStatement->execute([
                $subtask->id()->toString(),
                $attempt->attemptNumber,
                $attempt->reason,
                $attempt->at->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function upsertApprovalGate(MissionId $missionId, ApprovalGate $gate): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO approval_gates (id, mission_id, reason, requested_at, decision, decided_at, decided_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE decision = VALUES(decision), decided_at = VALUES(decided_at), decided_by = VALUES(decided_by)',
        );
        $statement->execute([
            $gate->id()->toString(),
            $missionId->toString(),
            $gate->reason,
            $gate->requestedAt->format('Y-m-d H:i:s'),
            $gate->decision()->value,
            $gate->decidedAt()?->format('Y-m-d H:i:s'),
            $gate->decidedBy(),
        ]);
    }

    /** @param list<Compensation> $compensations */
    private function replaceCompensations(MissionId $missionId, array $compensations): void
    {
        $delete = $this->pdo->prepare('DELETE FROM compensations WHERE mission_id = ?');
        $delete->execute([$missionId->toString()]);

        $insert = $this->pdo->prepare(
            'INSERT INTO compensations (mission_id, subtask_id, action, at, result) VALUES (?, ?, ?, ?, ?)',
        );
        foreach ($compensations as $compensation) {
            $insert->execute([
                $missionId->toString(),
                $compensation->subtaskId->toString(),
                $compensation->action,
                $compensation->at->format('Y-m-d H:i:s'),
                $compensation->result->value,
            ]);
        }
    }

    /** @param list<MissionHistoryEntry> $history */
    private function replaceHistory(MissionId $missionId, array $history): void
    {
        $delete = $this->pdo->prepare('DELETE FROM mission_history WHERE mission_id = ?');
        $delete->execute([$missionId->toString()]);

        $insert = $this->pdo->prepare('INSERT INTO mission_history (mission_id, status, at) VALUES (?, ?, ?)');
        foreach ($history as $entry) {
            $insert->execute([$missionId->toString(), $entry->status->value, $entry->at->format('Y-m-d H:i:s')]);
        }
    }

    /** @return list<Subtask> */
    private function loadSubtasks(MissionId $missionId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM subtasks WHERE mission_id = ?');
        $statement->execute([$missionId->toString()]);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }

        $retryStatement = $this->pdo->prepare(
            'SELECT * FROM subtask_retry_attempts WHERE subtask_id = ? ORDER BY attempt_number ASC',
        );

        $subtasks = [];
        foreach ($rows as $row) {
            $retryStatement->execute([$row['id']]);
            $attempts = array_map(
                static fn (array $r) => new RetryAttempt((int) $r['attempt_number'], $r['reason'], new \DateTimeImmutable($r['at'])),
                $retryStatement->fetchAll(\PDO::FETCH_ASSOC),
            );

            $subtasks[] = Subtask::reconstitute(
                SubtaskId::fromString($row['id']),
                $row['description'],
                $row['candidate_agent'],
                $row['candidate_capability'],
                SubtaskStatus::from($row['status']),
                $attempts,
                $row['result_json'] !== null ? json_decode($row['result_json'], true) : null,
            );
        }

        return $subtasks;
    }

    /** @return list<ApprovalGate> */
    private function loadApprovalGates(MissionId $missionId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM approval_gates WHERE mission_id = ? ORDER BY requested_at ASC');
        $statement->execute([$missionId->toString()]);

        return array_map(
            static fn (array $row) => ApprovalGate::reconstitute(
                ApprovalGateId::fromString($row['id']),
                $row['reason'],
                new \DateTimeImmutable($row['requested_at']),
                ApprovalDecisionStatus::from($row['decision']),
                $row['decided_at'] !== null ? new \DateTimeImmutable($row['decided_at']) : null,
                $row['decided_by'],
            ),
            $statement->fetchAll(\PDO::FETCH_ASSOC),
        );
    }

    /** @return list<Compensation> */
    private function loadCompensations(MissionId $missionId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM compensations WHERE mission_id = ? ORDER BY id ASC');
        $statement->execute([$missionId->toString()]);

        return array_map(
            static fn (array $row) => new Compensation(
                SubtaskId::fromString($row['subtask_id']),
                $row['action'],
                new \DateTimeImmutable($row['at']),
                CompensationResult::from($row['result']),
            ),
            $statement->fetchAll(\PDO::FETCH_ASSOC),
        );
    }

    /** @return list<MissionHistoryEntry> */
    private function loadHistory(MissionId $missionId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM mission_history WHERE mission_id = ? ORDER BY id ASC');
        $statement->execute([$missionId->toString()]);

        return array_map(
            static fn (array $row) => new MissionHistoryEntry(MissionStatus::from($row['status']), new \DateTimeImmutable($row['at'])),
            $statement->fetchAll(\PDO::FETCH_ASSOC),
        );
    }

    private function encodePlan(Plan $plan): string
    {
        return json_encode(
            [
                'source' => $plan->source->value,
                'subtaskCandidates' => array_map(
                    static fn (SubtaskCandidate $c) => [
                        'description' => $c->description,
                        'candidateAgent' => $c->candidateAgent,
                        'candidateCapability' => $c->candidateCapability,
                        'requiredAutonomyLevel' => $c->requiredAutonomyLevel,
                    ],
                    $plan->subtaskCandidates,
                ),
            ],
            \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
        );
    }

    private function decodePlan(string $json): Plan
    {
        $data = json_decode($json, true);

        return new Plan(
            array_map(
                static fn (array $c) => new SubtaskCandidate(
                    $c['description'],
                    $c['candidateAgent'],
                    $c['candidateCapability'],
                    $c['requiredAutonomyLevel'],
                ),
                $data['subtaskCandidates'],
            ),
            PlanSource::from($data['source']),
        );
    }
}
