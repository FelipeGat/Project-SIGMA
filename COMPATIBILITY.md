# Compatibility Matrix

Quais versões de Kernel, SIGMA Protocol e Plugin API funcionam juntas. Torna-se crítico quando existirem dezenas de Plugins de versões diferentes — registrado desde o primeiro Kernel para nunca precisar ser reconstruído retroativamente. Ver [ADR-0050](docs/adr/0050-compatibility-matrix.md).

| Kernel | Protocol | Plugin API | Status |
|---|---|---|---|
| 1.0 | 1.0 | 1.0 | ✅ Compatível |

- ✅ Compatível — suportado, testado.
- ⚠ Compatível com aviso — funciona, mas alguma capacidade é degradada ou depreciada (ex: um campo do Envelope antigo ainda aceito, mas marcado para remoção).
- ❌ Incompatível — combinação rejeitada explicitamente no boot (ver [BOOTSTRAP.md](BOOTSTRAP.md) — um Module/Plugin incompatível é recusado antes de `ready`, nunca falha silenciosamente em runtime).

Esta tabela é atualizada a cada Release que introduz uma nova versão de Kernel, Protocol ou Plugin API — nunca retroativamente reconstruída. Uma linha nunca é removida, mesmo quando uma versão é descontinuada (marcar como ❌, não apagar — histórico de compatibilidade é auditável).
