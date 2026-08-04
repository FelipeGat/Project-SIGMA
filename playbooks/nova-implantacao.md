# Playbook: Nova Implantação

## Gatilho

Um cliente contrata um sistema do ecossistema Alfa (Gestor.Alfa, AlfaControl, AlfaGym...) — orçamento correspondente aprovado, ou Mission explícita ("Sigma, inicie a implantação do sistema X para o cliente Y").

## Contexto necessário

- Qual sistema está sendo implantado e para qual Company/Client.
- Escopo contratado (módulos, integrações, treinamento incluso).
- Playbook especializado aplicável, quando existir (ex: [Nova Academia](nova-academia.md), [Novo Condomínio](novo-condominio.md)).

## Fases esperadas

1. Confirmar escopo contratado a partir do orçamento de origem.
2. Provisionar/configurar o ambiente do cliente no sistema em questão (etapa específica por sistema — detalhada no Playbook especializado, quando existir).
3. Agendar treinamento/onboarding com o cliente.
4. Acompanhar até critério de "implantado" (definido por sistema) e transição para operação/suporte contínuo.

## Agentes e Skills tipicamente envolvidos

- Claude (Engenharia) — configuração técnica do ambiente, quando aplicável.
- Manus (Documentação) — checklist e registro de implantação.
- `GestorSkill` (escopo contratado), `GoogleCalendarSkill` (treinamento).

## Pontos de decisão humana

Ativação em produção do ambiente do cliente exige validação humana explícita.

## Critérios de sucesso

Cliente operando no sistema contratado dentro do escopo acordado, treinamento realizado, transição para suporte contínuo registrada.

## Conhecimento relacionado

[nova-academia.md](nova-academia.md), [novo-condominio.md](novo-condominio.md), [knowledge/engenharia/](../knowledge/engenharia/).
