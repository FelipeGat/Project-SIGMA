<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

/**
 * O nível em que um RoleAssignment vale — Tenant, Company ou Workspace,
 * ver IDENTITY_MODEL.md#relações.
 */
enum ScopeType: string
{
    case Tenant = 'tenant';
    case Company = 'company';
    case Workspace = 'workspace';
}
