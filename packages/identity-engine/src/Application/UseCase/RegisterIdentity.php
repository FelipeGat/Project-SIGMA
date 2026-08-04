<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\CredentialRepository;
use Sigma\IdentityEngine\Application\IdentityRepository;
use Sigma\IdentityEngine\Application\CredentialProvider;
use Sigma\IdentityEngine\Application\TenantRepository;
use Sigma\IdentityEngine\Application\UserRepository;
use Sigma\IdentityEngine\Domain\Identity;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\User;
use Sigma\IdentityEngine\Domain\UserId;
use Sigma\Kernel\Contract\IEventBus;

/**
 * Cria User + Identity e ativa (esta Release não tem fluxo de
 * verificação de e-mail — Identity nasce ativa; ver Decision Log).
 */
final class RegisterIdentity
{
    public function __construct(
        private readonly TenantRepository $tenants,
        private readonly UserRepository $users,
        private readonly CredentialRepository $credentials,
        private readonly IdentityRepository $identities,
        private readonly CredentialProvider $credentialProvider,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(TenantId $tenantId, string $name, string $email, string $plainPassword): Identity
    {
        $tenant = $this->tenants->find($tenantId);
        if ($tenant === null) {
            throw new SigmaException('Tenant não encontrado.', 'identity.tenant_not_found');
        }

        if ($this->users->findByEmail($tenantId, $email) !== null) {
            throw new SigmaException('Já existe um User com este e-mail neste Tenant.', 'identity.email_already_registered');
        }

        $user = new User(UserId::generate(), $tenantId, $name, $email);
        $this->users->save($user);
        $this->credentials->setPasswordHash($user->id(), $this->credentialProvider->hash($plainPassword));

        $identity = Identity::create($user, $tenant);
        $identity->activate();
        $this->identities->save($identity);

        foreach ($identity->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $identity;
    }
}
