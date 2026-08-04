# ADR-0075: `Workspace`/`Context` conceitualmente pertencem à `Session`, não à `Identity` — direção aprovada, implementação adiada

- **Status**: Proposto — direção aprovada pelo Product Owner, sem mudança de código nesta Release
- **Data**: 2026-08-04

## Contexto

Hoje, `Identity::selectWorkspace()`/`Identity::resolveContext()` são métodos do aggregate `Identity`, ainda que operem sobre uma `Session` específica. O Product Owner observou: "Identity é permanente. Context muda... Session deveria possuir `CurrentContext`, não Identity" — o raciocínio é que, no futuro, a mesma `Session` poderá (dentro de certas regras) refletir o Workspace corrente de forma mais explícita como algo que a própria `Session` carrega, não algo que `Identity` calcula para ela.

## Decisão

Direção aprovada, **não implementada nesta Release** (Release 3.5 é "sem mudar comportamento" por decisão explícita do Product Owner): quando o modelo de `Session`/`Context` for tocado de novo com mudança de comportamento real, avaliar mover `selectWorkspace()`/`resolveContext()` para métodos de `Session` (ou de um serviço dedicado que produz `Session` já com `Context` associado), em vez de `Identity`. Isso não contradiz [ADR-0066](0066-context-imutavel.md) (Context imutável, trocar Workspace = nova Session) — é uma reorganização de **onde** a lógica mora, não de **como** ela se comporta.

## Consequências

- Fica registrado como direção clara para a próxima vez que `Domain/` do Identity Engine for tocado com mudança de comportamento — provavelmente quando um segundo `CredentialProvider` ou um cenário de múltiplas Sessions concorrentes por Identity forem implementados.
- Nenhum código muda agora — `Identity::selectWorkspace()`/`resolveContext()` continuam exatamente como estão, cobertos pelos mesmos testes.
- Se esta direção for implementada no futuro sem revisão, é responsabilidade de quem implementar verificar se ainda faz sentido dado o que existir então — esta ADR registra a intenção no momento em que foi dada, não uma obrigação cega de executá-la sem reavaliar o contexto futuro.
