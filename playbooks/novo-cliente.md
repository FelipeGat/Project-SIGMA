# Playbook: Novo Cliente

## Gatilho

Um lead qualificado avança para cliente — tipicamente refletido por uma mudança de estágio no Funil de Vendas do Gestor.Alfa, ou por uma Mission explícita ("Sigma, cadastre o cliente X").

## Contexto necessário

- Dados básicos do cliente (razão social, contato, segmento).
- Qual(is) produto(s)/empresa(s) do grupo este cliente vai consumir — determina quais Playbooks subsequentes (Novo Orçamento, Nova Implantação, Nova Academia/Novo Condomínio) se aplicam.

## Fases esperadas

1. Confirmar/criar o cadastro do cliente no sistema de origem correto via `GestorSkill` (evitar duplicidade — buscar antes de criar).
2. Registrar contexto qualitativo inicial em `knowledge/clientes/` (decisor, origem do lead, particularidades já conhecidas).
3. Identificar, a partir do produto de interesse, se este cliente segue para Novo Orçamento e/ou Nova Implantação.
4. Notificar o Team comercial responsável.

## Agentes e Skills tipicamente envolvidos

- ChatGPT (Estratégia) — qualificação e priorização.
- Manus (Documentação) — registro em Knowledge.
- `GestorSkill`, `TelegramSkill`/`EmailSkill` (notificação).

## Pontos de decisão humana

Criação de cadastro definitivo no Gestor.Alfa e qualquer comunicação enviada ao cliente exigem confirmação humana até que o histórico de execução automatizada justifique reduzir esse gate.

## Critérios de sucesso

Cliente cadastrado sem duplicidade, contexto inicial registrado em Knowledge, e Mission(s) subsequente(s) corretamente identificadas.

## Conhecimento relacionado

[knowledge/clientes/](../knowledge/clientes/), [skills/gestor.md](../skills/gestor.md), [novo-orcamento.md](novo-orcamento.md), [nova-implantacao.md](nova-implantacao.md).
