# ADR-0006: Toda integração externa é modelada como Skill com contrato padronizado

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

O SIGMA vai crescer para dezenas de integrações (GitHub, Telegram, Email, Google Calendar, Docker, WhatsApp, Gestor.Alfa, e outras). Sem um contrato único, cada integração tende a ser implementada com sua própria forma de configuração, permissão e log — o que inviabiliza auditoria e reuso à medida que o número de integrações cresce.

## Decisão

Toda integração externa é uma Skill, e toda Skill implementa o mesmo contrato: Configuração, Permissões, Entrada, Saída, Eventos, Logs, Testes, Documentação (detalhado em `docs/architecture/ARCHITECTURE.md` §6). Nenhuma integração é aceita no sistema fora desse contrato.

## Consequências

- Uma nova Skill é previsível de construir, revisar e operar — o custo de adicionar a integração nº 30 é comparável ao de adicionar a nº 3.
- Permissão de uso de uma Skill é verificável por Missão/Agente de forma uniforme, sem lógica especial por integração.
- Skills mal ajustadas ao contrato (ex: uma integração que não se encaixa bem em Entrada/Saída simples) exigem esforço extra de modelagem — aceito em troca de uniformidade.
