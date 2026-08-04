<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Infrastructure\Pdo;

use Sigma\MemoryEngine\Application\DigitalTwinRepository;
use Sigma\MemoryEngine\Domain\DigitalTwin;
use Sigma\MemoryEngine\Domain\DigitalTwinId;
use Sigma\MemoryEngine\Domain\TenantId;
use Sigma\MemoryEngine\Domain\TwinSubjectType;

final class PdoDigitalTwinRepository implements DigitalTwinRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(DigitalTwin $twin): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO digital_twins (id, subject_type, external_ref, state, last_synced_at, tenant_id)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE state = VALUES(state), last_synced_at = VALUES(last_synced_at)',
        );
        $statement->execute([
            $twin->id()->toString(),
            $twin->subjectType()->value,
            $twin->externalRef(),
            json_encode($twin->state(), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
            $twin->lastSyncedAt()->format('Y-m-d H:i:s'),
            $twin->tenantId()->toString(),
        ]);
    }

    public function findBySubjectTypeAndExternalRef(TwinSubjectType $subjectType, string $externalRef): ?DigitalTwin
    {
        $statement = $this->pdo->prepare('SELECT * FROM digital_twins WHERE subject_type = ? AND external_ref = ?');
        $statement->execute([$subjectType->value, $externalRef]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return DigitalTwin::reconstitute(
            DigitalTwinId::fromString($row['id']),
            TwinSubjectType::from($row['subject_type']),
            $row['external_ref'],
            json_decode($row['state'], true) ?? [],
            new \DateTimeImmutable($row['last_synced_at']),
            TenantId::fromString($row['tenant_id']),
        );
    }
}
