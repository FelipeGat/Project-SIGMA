<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/**
 * O ciclo de vida reconciliado em MISSION_LIFECYCLE.md — oito estados,
 * a partir de `Created` (o Aggregate só existe depois de um Plan já
 * aceito, ver ADR-0089). `Compensating` sempre termina em `Failed`
 * (ADR-0091), nunca em `Completed`.
 */
enum MissionStatus: string
{
    case Created = 'created';
    case PendingApproval = 'pending_approval';
    case InProgress = 'in_progress';
    case Compensating = 'compensating';
    case Validating = 'validating';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
