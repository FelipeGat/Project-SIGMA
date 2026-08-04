# ADR-0009: Stack tecnológica de referência

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O SIGMA precisa de um backend capaz de sustentar DDD, Event-Driven, filas, WebSocket e agendamento; um frontend web instalável e funcional offline; e um app mobile que compartilhe Design System e backend com o web, sem duplicar lógica de domínio no cliente.

## Decisão

- **Backend**: Laravel 12, PHP 8.4 — DDD, Event-Driven, Redis, MariaDB, API REST, WebSocket, Queues, Scheduler, com Actions, Repositories, Services, Value Objects, DTOs, Policies, Observers, Events e Listeners como blocos padrão de cada módulo (ver `docs/architecture/ARCHITECTURE.md` §7).
- **Frontend Web**: React, TypeScript, Vite, PWA, Design System próprio, Dark Mode, suporte offline, responsivo.
- **Mobile**: React Native com Expo, consumindo o mesmo Design System e o mesmo backend do frontend web — nenhuma lógica de domínio duplicada no cliente mobile.

## Consequências

- Reaproveita a expertise já consolidada da Alfa em Laravel (Gestor.Alfa, AlfaGym-parcial) e em React/React Native (AlfaControl, AlfaJornada, alfa-mobile).
- Design System único mantido uma vez e consumido por web e mobile reduz divergência visual e retrabalho.
- PHP/Laravel para um domínio Event-Driven pesado exige disciplina adicional (filas, listeners assíncronos) que frameworks nativamente orientados a atores não exigiriam — aceito em troca de consistência com o restante do ecossistema Alfa e velocidade de contratação/onboarding de time.
- Toda mudança futura de stack é uma decisão de porte equivalente a esta e exige novo ADR, não uma substituição silenciosa.
