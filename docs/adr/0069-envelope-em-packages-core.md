# ADR-0069: `Envelope` vive em `packages/core`, não em `services/gateway`

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

`Envelope` (a implementação do formato definido em [SIGMA_PROTOCOL.md §1](../../SIGMA_PROTOCOL.md#1-o-envelope)) foi escrita na Release 2 dentro de `services/gateway/src/Envelope.php`, único serviço HTTP existente até então. A Release 3B introduz `services/auth`, o segundo serviço HTTP do projeto — que precisa produzir respostas no mesmo formato de Envelope. Duplicar a classe (uma cópia em `services/gateway`, outra em `services/auth`) criaria duas fontes de verdade para um formato que é, por definição, o mesmo em todo o SIGMA — divergência entre as cópias é uma questão de tempo, não de possibilidade.

## Decisão

`Envelope` muda de `services/gateway/src/Envelope.php` (`namespace Sigma\Gateway`) para `packages/core/src/Envelope.php` (`namespace Sigma\Core`) — `packages/core` já é descrito como "primitivas de domínio compartilhadas pelos Engines do SIGMA", e o formato de Envelope é exatamente esse tipo de primitiva: usado por todo serviço HTTP, não pertence a nenhum serviço específico. `services/gateway` e `services/auth` consomem a mesma classe via `sigma/core` (dependência que ambos já têm).

## Consequências

- Nenhuma duplicação do formato de Envelope entre serviços — uma mudança futura no formato (novo campo, mudança de versão) acontece num lugar só.
- `services/gateway/src/HealthEndpoints.php` e `public/index.php` atualizados para `use Sigma\Core\Envelope` — comportamento idêntico, só o namespace mudou (8 testes de `services/gateway` continuam passando sem alteração).
- `packages/core` ganha um teste direto (`EnvelopeTest.php`) que antes só existia indiretamente via os testes de `services/gateway`.
- Precedente para qualquer primitiva de protocolo futura que mais de um serviço precise (ex: um cliente HTTP interno padronizado) — o teste é sempre "isso é específico deste serviço, ou é do Protocol como um todo?".
