# Roadmap — SIGMA

O SIGMA avança **um épico por vez**. Cada épico só entra em desenvolvimento após ser apresentado no formato Objetivo / Escopo / Arquitetura / Dependências / Riscos / Entrega / Testes / Critérios de Aceite e **aprovado explicitamente**. Este roadmap é a visão macro; o detalhamento formal de cada épico é produzido logo antes de sua execução, não com meses de antecedência — desenhar em detalhe um épico distante hoje só gera retrabalho quando o contexto mudar.

## Status

| Épico | Nome | Status |
|---|---|---|
| Sprint 0 | Foundation — documentação, arquitetura, estrutura | 🔵 Em andamento |
| E1 | Mission Engine — núcleo | ⚪ Não iniciado |
| E2 | Identidade & Acesso | ⚪ Não iniciado |
| E3 | Skill Registry + primeira Skill real | ⚪ Não iniciado |
| E4 | Orquestração de Agentes (1º provedor de IA real) | ⚪ Não iniciado |
| E5 | Knowledge & Memory | ⚪ Não iniciado |
| E6 | Painel Web (React PWA) | ⚪ Não iniciado |
| E7 | App Mobile (React Native + Expo) | ⚪ Não iniciado |
| E8 | Automation Engine | ⚪ Não iniciado |
| E9 | Expansão de Skills | ⚪ Não iniciado |

## Sprint 0 — Foundation (atual)

Entrega apenas documentação e estrutura de projeto. Nenhum código de aplicação. Escopo:

- README.md, VISION.md, ARCHITECTURE.md, ROADMAP.md
- Estrutura inicial de diretórios (backend/, frontend-web/, frontend-mobile/, docs/)
- Convenções de nomenclatura
- ADR-0001 a ADR-0010
- CONTRIBUTING.md, CODE_OF_CONDUCT.md, LICENSE
- docs/ organizada

Critério de saída: aprovação explícita do responsável pelo projeto antes de qualquer linha de código de aplicação.

## E1 — Mission Engine (núcleo)

Entidade Mission, máquina de estados do ciclo de vida, persistência, API REST mínima (criar Missão, consultar status), eventos de domínio publicados no Event Bus. Sem Agentes reais ainda — a escolha de Skill/Agente é simulada/mockada para provar o fluxo de estados ponta a ponta.

## E2 — Identidade & Acesso

User, Team, Company. Autenticação, autorização (Policies), RBAC. Toda Missão passa a ter um autor e um contexto de empresa/time.

## E3 — Skill Registry + primeira Skill real

Entidade Skill, SkillRegistry, contrato de Skill (Config/Permissions/Input/Output/Events/Logs/Tests/Docs) implementado de fato através de uma primeira Skill ponta a ponta (candidata: `GestorSkill`, por já existir integração e caso de uso real no ecossistema Alfa).

## E4 — Orquestração de Agentes

IA (provedor), Agent (persona), AgentPort (contrato de invocação). Primeira integração real com um provedor de IA (Claude, para o Agente de Engenharia). Uma Missão passa a ser de fato interpretada e executada por um Agente real usando a Skill do E3.

## E5 — Knowledge & Memory

Modelagem e primeira persistência/consulta de Knowledge e Memory, alimentadas pelos eventos de domínio já publicados desde o E1.

## E6 — Painel Web

React + TypeScript + Vite (PWA), Design System próprio, dark mode. Dashboard de Missões em tempo real via WebSocket, consumindo a API do E1–E4.

## E7 — App Mobile

React Native + Expo, mesmo Design System e mesmo backend do E6. Paridade essencial do painel.

## E8 — Automation Engine

Motor de automação declarativa reagindo a eventos de domínio (ex: "quando Missão X concluir, disparar Missão Y").

## E9 — Expansão de Skills

GitHubSkill, TelegramSkill, EmailSkill, GoogleCalendarSkill, DockerSkill, WhatsAppSkill, e demais integrações do ecossistema Alfa, seguindo o contrato validado no E3.

---

Este roadmap é revisado ao final de cada épico concluído — a ordem e o escopo dos épicos seguintes podem mudar com base no que for aprendido.
