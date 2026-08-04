<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

/**
 * As quatro entidades de negócio com Digital Twin (DIGITAL_TWIN.md).
 * `User` é populado desde a Release 4 (ADR-0079); os outros três têm
 * o mecanismo pronto, mas ficam sem instância até a Release 8.
 */
enum TwinSubjectType: string
{
    case Client = 'client';
    case Project = 'project';
    case Company = 'company';
    case User = 'user';
}
