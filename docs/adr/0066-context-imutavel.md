# ADR-0066: `Context` é imutável — trocar de Workspace produz uma nova Session, nunca muta o Context vigente

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Um `Context`/`Identity` mutável no meio de uma execução (ex: trocar de Workspace enquanto uma Mission está em andamento) cria uma classe inteira de bugs de concorrência e de auditoria — qual Workspace estava ativo quando uma ação foi de fato executada deixaria de ter resposta única. O Product Owner pediu explicitamente que `Context` fosse imutável: "Entrou. Workspace Comercial. Esse Context não muda. Trocar Workspace. Nova Session. Novo Context."

## Decisão

`Context` (`packages/identity-engine/src/Domain/Context.php`) é uma classe `final readonly`-por-propriedade, sem nenhum método de mutação — uma vez construído por `Identity::resolveContext()`, não pode ser alterado. `Session::withWorkspaceSelected()` reforça a mesma regra um nível abaixo: uma `Session` só aceita selecionar um Workspace **uma vez**; uma segunda chamada lança `SigmaException` (`identity.workspace_already_selected`) — trocar de Workspace exige chamar `Identity::authenticate()` de novo, produzindo uma `Session` (e depois um `Context`) inteiramente nova, nunca reaproveitando a anterior.

Nuance de implementação: a primeira seleção de Workspace (Identity Lifecycle, passo 4→5, numa Session recém-criada sem Workspace ainda) é permitida e mantém o mesmo `SessionId` — não é uma "troca", é a continuação natural da mesma Session. É só a partir daí, com Workspace já selecionado, que qualquer nova seleção é rejeitada e exige uma Session nova. `SessionTest::test_selecting_a_workspace_a_second_time_is_rejected` cobre essa regra.

## Consequências

- Nenhum consumidor de `Context` (nenhum Engine, via Kernel) pode observar um Workspace "mudando debaixo dele" no meio de uma operação — toda ação tem uma Identity/Context imutável e rastreável do início ao fim.
- Trocar de Workspace tem um custo pequeno a mais (reautenticar, reconstruir o Context) comparado a simplesmente mutar um campo — aceito conscientemente pela redução de risco de auditoria/concorrência que isso evita.
- `Identity::selectWorkspace()` e `Identity::resolveContext()` recebem sempre uma `Session` e retornam um valor novo (`Session` ou `Context`) — nunca mutam o argumento recebido, reforçando a mesma disciplina de imutabilidade em toda a cadeia.
