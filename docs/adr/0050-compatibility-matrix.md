# ADR-0050: Compatibility Matrix — COMPATIBILITY.md

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Com dezenas de Plugins e múltiplas versões de Kernel e SIGMA Protocol previstas no horizonte de [VISION_2030.md](../../VISION_2030.md), a pergunta "esta versão de Plugin funciona com esta versão de Kernel" precisa de uma resposta rastreável, não uma suposição.

## Decisão

Cria-se [COMPATIBILITY.md](../../COMPATIBILITY.md) — matriz de Kernel × Protocol × Plugin API × Status (✅ compatível, ⚠ compatível com aviso, ❌ incompatível). Atualizada a cada Release que introduz nova versão de Kernel, Protocol ou Plugin API. Uma combinação ❌ é rejeitada explicitamente no boot (ver [BOOTSTRAP.md](../../BOOTSTRAP.md)), nunca falha silenciosamente em runtime.

## Consequências

- A pergunta "isso é compatível" tem resposta documentada e versionada, não reconstituída manualmente quando surge um problema.
- Linhas nunca são removidas, só marcadas ❌ quando uma versão é descontinuada — histórico de compatibilidade permanece auditável.
- Começa com uma única linha (Kernel 1.0 / Protocol 1.0 / Plugin API 1.0 ✅) na Release 2 — cresce organicamente a partir daqui, nunca reconstruída retroativamente.
