# Playbook: Nova Academia (onboarding AlfaGym)

Especialização de [Nova Implantação](nova-implantacao.md) para clientes que contratam o AlfaGym.

## Gatilho

Orçamento de AlfaGym aprovado para uma academia — ou Mission explícita.

## Contexto necessário

- Estrutura da academia: modalidades, planos, equipe, catraca/hardware envolvido (quando aplicável — ver `knowledge/engenharia/`).
- Se há migração de base de alunos de um sistema anterior.

## Fases esperadas

1. Provisionar ambiente da academia no AlfaGym (empresa/tenant).
2. Migrar/cadastrar base de alunos e planos, quando aplicável.
3. Configurar integrações específicas (catraca, cobrança) conforme contratado.
4. Treinamento da equipe da academia.
5. Transição para operação — suporte contínuo conforme contrato.

## Agentes e Skills tipicamente envolvidos

- Claude (Engenharia) — configuração técnica, migração de dados.
- Manus (Documentação) — checklist de implantação específico de academia.
- `GestorSkill` (escopo contratado), Skill de AlfaGym (a especificar quando o épico correspondente for aberto — hoje não documentada em [/skills](../skills/)).

## Pontos de decisão humana

Migração de base de alunos e ativação em produção exigem validação humana explícita — dado real de aluno em jogo.

## Critérios de sucesso

Academia operando no AlfaGym com base de alunos íntegra, equipe treinada, integrações contratadas funcionando.

## Conhecimento relacionado

[nova-implantacao.md](nova-implantacao.md), [knowledge/engenharia/](../knowledge/engenharia/).
