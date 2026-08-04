<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * Uma unidade de trabalho "em execução" dentro de uma Mission —
 * distinta da SubtaskCandidate do Plan (ver MISSION_MODEL.md). Vive
 * inteiramente dentro do limite do aggregate `Mission` — nunca
 * manipulada diretamente de fora dele, mesmo que seus métodos sejam
 * `public` (mesma convenção já usada por `Session` no Identity Engine).
 */
final class Subtask
{
    private SubtaskStatus $status = SubtaskStatus::Pending;

    /** @var list<RetryAttempt> */
    private array $retryAttempts = [];

    private mixed $result = null;

    public function __construct(
        private readonly SubtaskId $id,
        public readonly string $description,
        public readonly ?string $candidateAgent,
        public readonly ?string $candidateCapability,
    ) {
    }

    public static function fromCandidate(SubtaskId $id, SubtaskCandidate $candidate): self
    {
        return new self($id, $candidate->description, $candidate->candidateAgent, $candidate->candidateCapability);
    }

    /**
     * Hidratação a partir de estado já persistido (Release 5C) — nunca
     * dispara evento algum, mesmo espírito de `Mission::reconstitute()`.
     *
     * @param list<RetryAttempt> $retryAttempts
     */
    public static function reconstitute(
        SubtaskId $id,
        string $description,
        ?string $candidateAgent,
        ?string $candidateCapability,
        SubtaskStatus $status,
        array $retryAttempts,
        mixed $result,
    ): self {
        $subtask = new self($id, $description, $candidateAgent, $candidateCapability);
        $subtask->status = $status;
        $subtask->retryAttempts = $retryAttempts;
        $subtask->result = $result;

        return $subtask;
    }

    public function id(): SubtaskId
    {
        return $this->id;
    }

    public function status(): SubtaskStatus
    {
        return $this->status;
    }

    /** @return list<RetryAttempt> */
    public function retryAttempts(): array
    {
        return $this->retryAttempts;
    }

    public function result(): mixed
    {
        return $this->result;
    }

    public function assign(): void
    {
        $this->assertStatus(SubtaskStatus::Pending, 'assign');
        $this->status = SubtaskStatus::Assigned;
    }

    public function startExecution(): void
    {
        $this->assertStatus(SubtaskStatus::Assigned, 'startExecution');
        $this->status = SubtaskStatus::Executing;
    }

    public function retry(string $reason, \DateTimeImmutable $now): RetryAttempt
    {
        $this->assertStatus(SubtaskStatus::Executing, 'retry');

        $attempt = new RetryAttempt(count($this->retryAttempts) + 1, $reason, $now);
        $this->retryAttempts[] = $attempt;

        return $attempt;
    }

    public function validate(mixed $result): void
    {
        $this->assertStatus(SubtaskStatus::Executing, 'validate');
        $this->status = SubtaskStatus::Validated;
        $this->result = $result;
    }

    public function fail(): void
    {
        $this->assertStatus(SubtaskStatus::Executing, 'fail');
        $this->status = SubtaskStatus::Failed;
    }

    public function compensate(): void
    {
        // Aceita tanto `Failed` (Fluxo 2 — a própria Subtask falhou com
        // efeito já produzido) quanto `Validated` (Fluxo 4 — a Subtask
        // validou normalmente, mas a validação final da Mission a
        // apontou como a origem do efeito que precisa ser desfeito).
        // Achado real durante a Implementation, ver Decision Log da
        // Release 5B — MISSION_MODEL.md não detalha esta origem dupla.
        $this->assertStatusIn([SubtaskStatus::Failed, SubtaskStatus::Validated], 'compensate');
        $this->status = SubtaskStatus::Compensated;
    }

    public function skip(): void
    {
        $this->assertStatus(SubtaskStatus::Pending, 'skip');
        $this->status = SubtaskStatus::Skipped;
    }

    private function assertStatus(SubtaskStatus $expected, string $action): void
    {
        $this->assertStatusIn([$expected], $action);
    }

    /** @param list<SubtaskStatus> $expected */
    private function assertStatusIn(array $expected, string $action): void
    {
        if (!in_array($this->status, $expected, true)) {
            $labels = implode('" ou "', array_map(static fn (SubtaskStatus $s) => $s->value, $expected));
            throw new SigmaException(
                sprintf('Subtask precisa estar "%s" para "%s" — está "%s".', $labels, $action, $this->status->value),
                'mission.invalid_subtask_transition',
            );
        }
    }
}
