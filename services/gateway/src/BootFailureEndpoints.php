<?php

declare(strict_types=1);

namespace Sigma\Gateway;

use Sigma\Core\Envelope;

/**
 * As respostas do gateway quando o próprio boot falhou.
 *
 * Achado real da Release 5.5: ao registrar `identity-engine`/
 * `mission-engine`, o gateway passou a depender de MariaDB, e
 * `IdentityEngineModule::register()` lança `SigmaException` quando o
 * banco está inalcançável — derrubando o boot inteiro. Sem esta
 * classe, o front controller respondia 503 a *tudo*, inclusive
 * `/health/live`.
 *
 * Isso viola BOOTSTRAP.md § Health: `/health/live` responde "o
 * processo está vivo", e é o que o orquestrador usa para decidir se
 * **reinicia** o processo; `/health/ready` é o que decide se ele
 * **recebe tráfego**. Um banco fora do ar é exatamente `not_ready`,
 * nunca `unresponsive` — tratá-lo como falha de liveness faria o
 * Kubernetes/Docker entrar em restart-loop de um processo saudável,
 * justamente quando a infraestrutura já está sob estresse.
 *
 * @see HealthEndpoints Para o caminho normal, com o boot bem-sucedido.
 */
final class BootFailureEndpoints
{
    public function __construct(private readonly \Throwable $failure)
    {
    }

    /**
     * O processo está vivo — o boot falhou por dependência externa, não
     * porque o processo travou. Reiniciar não resolveria.
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function live(): array
    {
        return [200, Envelope::success(['status' => 'live'])];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function ready(): array
    {
        return [503, Envelope::failure('bootstrap.failed', $this->failure->getMessage())];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function startup(): array
    {
        return [503, Envelope::failure('bootstrap.failed', $this->failure->getMessage())];
    }

    /**
     * Toda rota de domínio enquanto o boot não completa.
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function unavailable(): array
    {
        return [503, Envelope::failure('gateway.unavailable', 'O gateway ainda não completou o boot — tente novamente.')];
    }
}
