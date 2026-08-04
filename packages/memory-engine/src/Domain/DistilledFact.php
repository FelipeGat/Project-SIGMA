<?php

declare(strict_types=1);

namespace Sigma\MemoryEngine\Domain;

use Sigma\Core\SigmaException;

/**
 * Um fato já resolvido, pronto para virar `MemoryRecord` na destilação
 * de um `ContextMemory` fechado (MEMORY_LIFECYCLE.md — Fluxo 1, etapa
 * 3). O algoritmo que produz esta lista (o que extrair de `rawContent`
 * e qual `confidence` atribuir) é decisão de Implementation da Release
 * 4B — este `Domain/` só recebe o resultado já resolvido e decide a
 * transição, mesmo padrão usado para promoção (ver Proposal 4A —
 * Arquitetura).
 */
final class DistilledFact
{
    public function __construct(
        public readonly string $subjectKey,
        public readonly string $content,
        public readonly float $confidence,
    ) {
        if (trim($subjectKey) === '') {
            throw new SigmaException('subjectKey não pode ser vazio.', 'memory.invalid_subject_key');
        }

        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new SigmaException('confidence precisa estar entre 0.0 e 1.0.', 'memory.invalid_confidence');
        }
    }
}
