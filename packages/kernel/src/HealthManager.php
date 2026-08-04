<?php

declare(strict_types=1);

namespace Sigma\Kernel;

use Sigma\Kernel\Contract\IHealth;
use Sigma\Kernel\Contract\ModuleStatus;

/**
 * Implementação de referência de IHealth — agrega o estado reportado
 * por cada Module e responde às três perguntas de /health/live,
 * /health/ready, /health/startup (ver BOOTSTRAP.md e ADR-0042).
 */
final class HealthManager implements IHealth
{
    /** @var array<string, array{status: ModuleStatus, reason: ?string}> */
    private array $reports = [];

    private bool $startupComplete = false;

    public function report(string $module, ModuleStatus $status, ?string $reason = null): void
    {
        $this->reports[$module] = ['status' => $status, 'reason' => $reason];
    }

    /**
     * Marca que o boot inicial terminou — usado por /health/startup,
     * independente de o sistema já estar `ready` ou não.
     */
    public function markStartupComplete(): void
    {
        $this->startupComplete = true;
    }

    /**
     * O processo está vivo — verdadeiro a menos que nenhum Module tenha
     * sequer sido registrado ainda (processo não terminou de nascer).
     */
    public function isLive(): bool
    {
        return true;
    }

    public function isStartupComplete(): bool
    {
        return $this->startupComplete;
    }

    /**
     * Pronto para tráfego: todo Module conhecido está `Ready` — nenhum
     * `Boot`, `Start`, `Degraded` ou `Failed` pendente.
     */
    public function isReady(): bool
    {
        if ($this->reports === []) {
            return false;
        }

        foreach ($this->reports as $report) {
            if ($report['status'] !== ModuleStatus::Ready) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{status: string, reason: ?string}>
     */
    public function snapshot(): array
    {
        return array_map(
            static fn (array $report): array => [
                'status' => $report['status']->value,
                'reason' => $report['reason'],
            ],
            $this->reports,
        );
    }
}
