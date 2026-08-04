# ADR-0036: "Objetivo" é o campo de propósito da Intent — não uma camada nova

- **Status**: Proposto — aguardando confirmação explícita do Product Owner
- **Data**: 2026-08-04

## Contexto

Em revisão, "Objetivo" foi apresentado como um conceito a acrescentar ao vocabulário do SIGMA, com o exemplo "Sigma, quero fechar a venda da Sea Master" gerando uma cadeia Objetivo → Missões → Tarefas → Eventos. Esse exemplo é estruturalmente idêntico ao que [ADR-0028](0028-intencao-nao-comando.md) já descreve para Intent → Mission → Subtask → Event — o mesmo tipo de frase, a mesma decomposição em múltiplas Missions relacionadas. Ao mesmo tempo, a especificação de [SGL](0032-sigma-language.md) apresentada na mesma revisão usa `objective` como um **campo dentro de um bloco `INTENT`**, não como um bloco próprio.

Essas duas leituras não são obviamente conciliáveis sem uma decisão: "Objetivo" pode ser (a) um sinônimo em português para o campo de propósito de uma Intent, ou (b) uma camada nova, acima de Intent, da qual uma ou mais Intents nasceriam.

## Decisão (proposta)

Adota-se a leitura (a): **"Objetivo" é o nome de produto, em português, para o campo `objective` de uma Intent** — a frase-resumo do estado desejado que uma Intent representa. Não se introduz uma camada nova. Esta leitura evita renomear Intent Engine, o evento `IntentDetected`, e a Release 7, já consolidados em seis ADRs, no [EVENT_MODEL.md](../../EVENT_MODEL.md) e no [ROADMAP.md](../../ROADMAP.md) — um custo de retrabalho real que a leitura (b) exigiria sem benefício estrutural claro, já que a decomposição em múltiplas Missions já é exatamente o que Intent faz desde ADR-0028.

## Consequências se confirmada

- `DOMAIN.md`, `SIGMA_PROTOCOL.md` e `SGL.md` usam "objective"/"Objetivo" como o campo de propósito de uma Intent, não como entidade própria — já aplicado nesta revisão.
- Nenhuma mudança de nome em Engine, evento ou Release.

## Se a leitura (b) for a intenção correta

Esta ADR precisa ser substituída por uma nova, introduzindo `Objective` como entidade de domínio própria, uma camada acima de `Intent` (uma Objective podendo gerar mais de uma Intent), com o remapeamento correspondente em `DOMAIN.md`, `SIGMA_PROTOCOL.md`, `ARCHITECTURE.md` e no roadmap. Não implementado nesta revisão — sinalizado para decisão explícita antes de a Release 6 (Planner) ou 6 (Intent) começarem, já que ambas dependem diretamente de qual leitura é a correta.
