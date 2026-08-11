<?php

declare(strict_types=1);

namespace Sigma\Kernel\Http;

use Sigma\Core\Envelope;

/**
 * As respostas de um processo do SIGMA quando o próprio boot falhou —
 * qualquer processo, não um em específico (ADR-0094).
 *
 * Achado real da Release 5.5: ao registrar `identity-engine`/
 * `mission-engine`, o gateway passou a depender de MariaDB, e o
 * `register()` desses Modules lança `SigmaException` quando o banco
 * está inalcançável — derrubando o boot inteiro. Sem esta classe, o
 * front controller respondia 503 a *tudo*, inclusive `/health/live`.
 *
 * Isso viola BOOTSTRAP.md § Health: `/health/live` responde "o
 * processo está vivo", e é o que o orquestrador usa para decidir se
 * **reinicia** o processo; `/health/ready` é o que decide se ele
 * **recebe tráfego**. Uma dependência externa fora do ar é exatamente
 * `not_ready`, nunca `unresponsive` — tratá-la como falha de liveness
 * faria o Kubernetes/Docker entrar em restart-loop de um processo
 * saudável, justamente quando a infraestrutura já está sob estresse.
 *
 * Vive no Kernel, não em `services/gateway`, porque health é
 * comportamento de Module: todo processo do SIGMA sobe pelo mesmo
 * `LifecycleManager` e enfrenta exatamente esta situação. Continua sem
 * conhecer Engine, Mission ou qualquer conceito de domínio (ADR-0040).
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
     * Toda rota de domínio enquanto o boot não completa. O código é
     * `service.unavailable`, não `gateway.unavailable`: esta classe
     * atende qualquer processo do SIGMA desde a ADR-0094, e um código
     * que nomeie um service específico seria mentira nos demais.
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function unavailable(): array
    {
        return [503, Envelope::failure('service.unavailable', 'O serviço ainda não completou o boot — tente novamente.')];
    }
}
