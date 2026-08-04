# ADR-0042: Health endpoints compatíveis com Kubernetes

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Um único endpoint `/health` genérico não distingue três perguntas operacionais diferentes, que qualquer orquestrador de containers moderno (Kubernetes, e equivalentes) já espera poder fazer separadamente: o processo está vivo (não travado)? Ele já terminou de inicializar? Ele está pronto para receber tráfego agora? Misturar as três numa resposta booleana força decisões operacionais grosseiras — reiniciar um processo que só está demorando para subir, ou mandar tráfego para um processo que ainda não terminou de inicializar.

## Decisão

O SIGMA expõe três endpoints, seguindo a convenção já estabelecida por Kubernetes: `/health/live`, `/health/ready`, `/health/startup` — ver [BOOTSTRAP.md § Health](../../BOOTSTRAP.md#health--compatível-com-kubernetes) para o que cada um responde.

## Consequências

- Deploys ganham controle fino: um processo lento para iniciar não é morto prematuramente (`startup` cobre esse caso); um processo travado é reiniciado (`live`); tráfego só chega quando de fato pronto (`ready`).
- `/health/ready` reflete o estado granular por Module (incluindo `degraded` — ver [ADR-0041](0041-lifecycle-estendido.md)), não um booleano único — permite que um orquestrador (ou um humano) veja exatamente qual Module está com problema.
- Todo endpoint responde no [Envelope do SIGMA Protocol](../../SIGMA_PROTOCOL.md#1-o-envelope) — consistência com o resto do sistema, mesmo para um endpoint de infraestrutura.
- `docker/docker-compose.yml` e qualquer definição de deploy futura (Release 2 em diante) já apontam para estes três endpoints, não um único `/health` genérico.
