<?php

declare(strict_types=1);

namespace Sigma\Auth;

use Sigma\Core\Envelope;
use Sigma\Core\SigmaException;
use Sigma\IdentityEngine\Application\UseCase\Authenticate;
use Sigma\IdentityEngine\Application\UseCase\Logout;
use Sigma\IdentityEngine\Application\UseCase\ResolveContext;
use Sigma\IdentityEngine\Application\UseCase\SelectWorkspace;
use Sigma\IdentityEngine\Domain\SessionId;
use Sigma\IdentityEngine\Domain\TenantId;
use Sigma\IdentityEngine\Domain\WorkspaceId;
use Sigma\Kernel\Contract\IContainer;

/**
 * A lógica dos quatro endpoints de autenticação — separada do front
 * controller (`public/index.php`) para ser testável sem subir um
 * servidor HTTP, mesmo padrão de services/gateway/src/HealthEndpoints.php.
 *
 * @see public/index.php
 */
final class AuthEndpoints
{
    public function __construct(private readonly IContainer $container)
    {
    }

    /**
     * @param array<string, mixed> $body
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function login(array $body): array
    {
        return $this->guarded(function () use ($body) {
            $tenantId = TenantId::fromString((string) ($body['tenantId'] ?? ''));
            $email = (string) ($body['email'] ?? '');
            $password = (string) ($body['password'] ?? '');

            /** @var Authenticate $authenticate */
            $authenticate = $this->container->get(Authenticate::class);
            $session = $authenticate->execute($tenantId, $email, $password, new \DateTimeImmutable());

            return [200, Envelope::success([
                'sessionId' => $session->id()->toString(),
                'expiresAt' => $session->expiresAt()->format(\DATE_ATOM),
            ])];
        });
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function logout(?string $sessionToken): array
    {
        return $this->guarded(function () use ($sessionToken) {
            /** @var Logout $logout */
            $logout = $this->container->get(Logout::class);
            $logout->execute(SessionId::fromString((string) $sessionToken));

            return [200, Envelope::success(['status' => 'logged_out'])];
        });
    }

    /**
     * @param array<string, mixed> $body
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function selectWorkspace(?string $sessionToken, array $body): array
    {
        return $this->guarded(function () use ($sessionToken, $body) {
            $workspaceId = WorkspaceId::fromString((string) ($body['workspaceId'] ?? ''));

            /** @var SelectWorkspace $selectWorkspace */
            $selectWorkspace = $this->container->get(SelectWorkspace::class);
            $session = $selectWorkspace->execute(SessionId::fromString((string) $sessionToken), $workspaceId, new \DateTimeImmutable());

            return [200, Envelope::success(['sessionId' => $session->id()->toString(), 'workspaceId' => $workspaceId->toString()])];
        });
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function context(?string $sessionToken): array
    {
        return $this->guarded(function () use ($sessionToken) {
            /** @var ResolveContext $resolveContext */
            $resolveContext = $this->container->get(ResolveContext::class);
            $context = $resolveContext->execute(SessionId::fromString((string) $sessionToken), new \DateTimeImmutable());

            return [200, Envelope::success([
                'identityId' => $context->identityId->toString(),
                'userId' => $context->userId->toString(),
                'tenantId' => $context->tenantId->toString(),
                'companyId' => $context->companyId->toString(),
                'workspaceId' => $context->workspaceId->toString(),
                'permissions' => $context->grantedPermissions(),
            ])];
        });
    }

    /**
     * @param callable(): array{0: int, 1: array<string, mixed>} $action
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function guarded(callable $action): array
    {
        try {
            return $action();
        } catch (SigmaException $exception) {
            return [$this->statusFor($exception->errorCode()), Envelope::failure($exception->errorCode(), $exception->getMessage())];
        } catch (\Throwable $exception) {
            // Falha de infraestrutura (ex: Redis/MariaDB inalcançável) — nunca
            // vaza stack trace/mensagem interna ao cliente; a mensagem real
            // fica só no lado do servidor. Achado real ao validar esta
            // Release: sem isto, uma falha assim quebrava o contrato do
            // Envelope (resposta deixava de ser JSON válido). Ver Decision Log.
            error_log(sprintf('[auth] falha inesperada: %s', $exception->getMessage()));

            return [500, Envelope::failure('auth.internal_error', 'Erro interno ao processar a requisição.')];
        }
    }

    private function statusFor(string $errorCode): int
    {
        return match ($errorCode) {
            'identity.invalid_credentials', 'identity.session_expired' => 401,
            'identity.tenant_not_found', 'identity.workspace_not_found', 'identity.identity_not_found',
            'identity.session_not_found', 'identity.company_not_found', 'identity.role_not_found',
            'identity.role_assignment_not_found' => 404,
            'identity.email_already_registered', 'identity.workspace_already_selected',
            'identity.workspace_not_selected' => 409,
            default => 400,
        };
    }
}
