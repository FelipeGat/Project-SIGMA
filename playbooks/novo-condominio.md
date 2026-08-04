# Playbook: Novo Condomínio (onboarding AlfaControl)

Especialização de [Nova Implantação](nova-implantacao.md) para clientes que contratam o AlfaControl.

## Gatilho

Orçamento de AlfaControl aprovado para um condomínio — ou Mission explícita.

## Contexto necessário

- Estrutura do condomínio: blocos, unidades, síndico/administração responsável.
- Perfis de usuário a provisionar (síndico, morador, portaria, conselho — conforme perfis já suportados pelo AlfaControl).
- Se há migração de base de moradores/unidades de um sistema anterior.

## Fases esperadas

1. Provisionar ambiente do condomínio no AlfaControl (empresa/tenant).
2. Cadastrar estrutura de blocos/unidades e migrar base de moradores, quando aplicável.
3. Configurar perfis de acesso conforme contratado.
4. Treinamento do síndico/administração.
5. Transição para operação — suporte contínuo conforme contrato.

## Agentes e Skills tipicamente envolvidos

- Claude (Engenharia) — configuração técnica, migração de dados.
- Manus (Documentação) — checklist de implantação específico de condomínio.
- `GestorSkill` (escopo contratado), Skill de AlfaControl (a especificar quando o épico correspondente for aberto — hoje não documentada em [/skills](../skills/)).

## Pontos de decisão humana

Migração de base de moradores e ativação em produção exigem validação humana explícita — dado real de morador em jogo.

## Critérios de sucesso

Condomínio operando no AlfaControl com estrutura de unidades íntegra, síndico/administração treinados, perfis de acesso corretos.

## Conhecimento relacionado

[nova-implantacao.md](nova-implantacao.md), [knowledge/engenharia/](../knowledge/engenharia/).
