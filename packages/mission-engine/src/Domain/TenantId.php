<?php

declare(strict_types=1);

namespace Sigma\MissionEngine\Domain;

/**
 * Referência opaca a um Tenant do Identity Engine — Mission Engine
 * nunca resolve um Tenant por conta própria (mesmo princípio de
 * ADR-0039), só carrega o identificador.
 */
final class TenantId extends Identifier
{
}
