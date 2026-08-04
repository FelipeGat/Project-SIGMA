<?php

declare(strict_types=1);

namespace Sigma\IdentityEngine\Application\UseCase;

use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\CredentialRepository;
use Sigma\IdentityEngine\Application\IdentityRepository;
use Sigma\IdentityEngine\Application\PasswordHasher;
use Sigma\IdentityEngine\Application\SessionRepository;
use Sigma\IdentityEngine\Application\UserRepository;
use Sigma\IdentityEngine\Domain\Session;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\Kernel\Contract\IEventBus;

/**
 * Login — Identity Lifecycle etapas 2-4. O erro de e-mail inexistente
 * e o de senha errada usam o MESMO código, deliberadamente — nunca
 * revelar qual dos dois falhou.
 */
final class Authenticate
{
    private const SESSION_TTL = 'PT8H';

    public function __construct(
        private readonly UserRepository $users,
        private readonly CredentialRepository $credentials,
        private readonly IdentityRepository $identities,
        private readonly PasswordHasher $passwordHasher,
        private readonly SessionRepository $sessions,
        private readonly IEventBus $eventBus,
    ) {
    }

    public function execute(TenantId $tenantId, string $email, string $plainPassword, \DateTimeImmutable $now): Session
    {
        $user = $this->users->findByEmail($tenantId, $email);
        $hash = $user !== null ? $this->credentials->passwordHash($user->id()) : null;

        if ($user === null || $hash === null || !$this->passwordHasher->verify($plainPassword, $hash)) {
            throw new SigmaException('Credenciais inválidas.', 'identity.invalid_credentials');
        }

        $identity = $this->identities->findByUserId($user->id());
        if ($identity === null) {
            throw new SigmaException('Credenciais inválidas.', 'identity.invalid_credentials');
        }

        $session = $identity->authenticate($now, new \DateInterval(self::SESSION_TTL));
        $this->sessions->save($session);

        foreach ($identity->pullDomainEvents() as $event) {
            $this->eventBus->publish($event->name(), $event->payload());
        }

        return $session;
    }
}
