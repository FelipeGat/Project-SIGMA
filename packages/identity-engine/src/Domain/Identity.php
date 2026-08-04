<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Domain;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Domain\Event\IdentityActivated;
use Sigma\IdentityEngine\Domain\Event\IdentityCreated;
use Sigma\IdentityEngine\Domain\Event\IdentityDisabled;
use Sigma\IdentityEngine\Domain\Event\SessionEnded;
use Sigma\IdentityEngine\Domain\Event\SessionStarted;
use Sigma\IdentityEngine\Domain\Event\WorkspaceSelected;

/**
 * O objeto raiz de identidade (ADR-0064): Identity → User → Workspace
 * → Permissions → Context → Autonomy. Implementa o fluxo de
 * IDENTITY_LIFECYCLE.md como uma sequência de métodos, cada um
 * produzindo o evento de domínio correspondente (ver DOMAIN_EVENTS.md)
 * como valor de retorno — nunca publicado sozinho (ADR-0062).
 */
final class Identity
{
    use RecordsDomainEvents;

    private bool $active = false;

    private function __construct(
        private readonly IdentityId $id,
        private readonly User $user,
        private readonly Tenant $tenant,
    ) {
    }

    public static function create(User $user, Tenant $tenant): self
    {
        if (!$user->tenantId()->equals($tenant->id())) {
            throw new SigmaException('User pertence a um Tenant diferente do informado.', 'identity.tenant_mismatch');
        }

        $identity = new self(IdentityId::generate(), $user, $tenant);
        $identity->record(new IdentityCreated($identity->id, $user->id(), $tenant->id()));

        return $identity;
    }

    /**
     * Reidrata uma Identity já existente a partir de dados persistidos
     * (Infrastructure, Release 3B) — nunca dispara eventos de domínio,
     * ao contrário de create()/activate()/disable(). Usado apenas por
     * repositórios ao carregar do banco.
     */
    public static function reconstitute(IdentityId $id, User $user, Tenant $tenant, bool $active): self
    {
        $identity = new self($id, $user, $tenant);
        $identity->active = $active;

        return $identity;
    }

    public function id(): IdentityId
    {
        return $this->id;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function tenant(): Tenant
    {
        return $this->tenant;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): void
    {
        if ($this->active) {
            throw new SigmaException('Identity já está ativa.', 'identity.already_active');
        }

        $this->active = true;
        $this->record(new IdentityActivated($this->id));
    }

    public function disable(string $reason): void
    {
        if (!$this->active) {
            throw new SigmaException('Identity já está desativada.', 'identity.already_disabled');
        }

        $this->active = false;
        $this->record(new IdentityDisabled($this->id, $reason));
    }

    /** Etapas 2-4 de IDENTITY_LIFECYCLE.md — autenticada, Session criada. */
    public function authenticate(\DateTimeImmutable $now, \DateInterval $ttl): Session
    {
        if (!$this->active) {
            throw new SigmaException('Identity inativa não pode autenticar.', 'identity.not_active');
        }

        $session = Session::start($this->id, $now, $ttl);
        $this->record(new SessionStarted($session->id(), $this->id, $session->issuedAt(), $session->expiresAt()));

        return $session;
    }

    /** Etapa 5 de IDENTITY_LIFECYCLE.md — Workspace selecionado. */
    public function selectWorkspace(Session $session, Workspace $workspace, \DateTimeImmutable $now): Session
    {
        $this->assertSessionBelongsToIdentity($session);
        $this->assertSessionNotExpired($session, $now);

        $updated = $session->withWorkspaceSelected($workspace->id());
        $this->record(new WorkspaceSelected($updated->id(), $this->id, $workspace->id()));

        return $updated;
    }

    /**
     * Etapas 6-8 de IDENTITY_LIFECYCLE.md — Permissions/Autonomy
     * resolvidas, Identity pronta. Puro: nenhum evento próprio (não
     * catalogado em DOMAIN_EVENTS.md) — o resultado É o Context.
     *
     * @param list<RoleAssignment> $roleAssignments candidatos a se aplicarem (Application filtra por Tenant antes de chamar)
     * @param list<Team> $teams Teams da Company do Workspace, para resolver participação
     */
    public function resolveContext(
        Session $session,
        Workspace $workspace,
        Company $company,
        array $roleAssignments,
        array $teams,
        \DateTimeImmutable $now,
    ): Context {
        $this->assertSessionBelongsToIdentity($session);
        $this->assertSessionNotExpired($session, $now);

        if (!$session->hasWorkspaceSelected() || !$session->workspaceId()->equals($workspace->id())) {
            throw new SigmaException('Esta Session não tem este Workspace selecionado.', 'identity.workspace_not_selected');
        }

        if (!$company->id()->equals($workspace->companyId())) {
            throw new SigmaException('Company informada não corresponde ao Workspace.', 'identity.company_mismatch');
        }

        $memberTeamIds = [];
        foreach ($teams as $team) {
            if ($team->hasMember($this->user->id())) {
                $memberTeamIds[] = $team->id();
            }
        }

        $acceptableScopes = [
            Scope::workspace($workspace->id()),
            Scope::company($company->id()),
            Scope::tenant($this->tenant->id()),
        ];

        $permissions = [];
        $autonomyCapabilities = [];

        foreach ($roleAssignments as $assignment) {
            if ($assignment->isRevoked()) {
                continue;
            }

            if (!$this->assignmentAppliesToSubject($assignment, $memberTeamIds)) {
                continue;
            }

            if (!$this->assignmentAppliesToAnyScope($assignment, $acceptableScopes)) {
                continue;
            }

            foreach ($assignment->role()->permissions() as $permission) {
                $permissions[$permission->key()] = true;
            }

            foreach ($assignment->role()->autonomyCapabilities() as $capability => $granted) {
                $autonomyCapabilities[$capability] = ($autonomyCapabilities[$capability] ?? false) || $granted;
            }
        }

        return new Context(
            identityId: $this->id,
            userId: $this->user->id(),
            tenantId: $this->tenant->id(),
            companyId: $company->id(),
            workspaceId: $workspace->id(),
            permissions: $permissions,
            autonomyCapabilities: $autonomyCapabilities,
        );
    }

    public function endSession(Session $session, string $reason): void
    {
        $this->assertSessionBelongsToIdentity($session);
        $this->record(new SessionEnded($session->id(), $this->id, $reason));
    }

    private function assertSessionBelongsToIdentity(Session $session): void
    {
        if (!$session->identityId()->equals($this->id)) {
            throw new SigmaException('Session não pertence a esta Identity.', 'identity.session_identity_mismatch');
        }
    }

    private function assertSessionNotExpired(Session $session, \DateTimeImmutable $now): void
    {
        if ($session->isExpired($now)) {
            throw new SigmaException('Session expirada.', 'identity.session_expired');
        }
    }

    /** @param list<TeamId> $memberTeamIds */
    private function assignmentAppliesToSubject(RoleAssignment $assignment, array $memberTeamIds): bool
    {
        if ($assignment->subjectType() === SubjectType::User) {
            return $assignment->subjectId()->equals($this->user->id());
        }

        foreach ($memberTeamIds as $teamId) {
            if ($assignment->subjectId()->equals($teamId)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<Scope> $acceptableScopes */
    private function assignmentAppliesToAnyScope(RoleAssignment $assignment, array $acceptableScopes): bool
    {
        foreach ($acceptableScopes as $scope) {
            if ($assignment->appliesToScope($scope)) {
                return true;
            }
        }

        return false;
    }
}
