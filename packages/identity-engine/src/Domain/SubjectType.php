<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * Quem recebe um Role num RoleAssignment — um User diretamente, ou um
 * Team inteiro (todo membro herda), ver IDENTITY_MODEL.md#relações.
 */
enum SubjectType: string
{
    case User = 'user';
    case Team = 'team';
}
