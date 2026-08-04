# ADR-0026: Envelope de resposta padronizado para toda resposta do SIGMA

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Sem um formato único de resposta, cada Skill/Plugin, cada Agent e cada canal (Telegram, GitHub, Claude, ChatGPT) tenderia a devolver dados num formato próprio — forçando quem consome (Mission Engine, Execution Engine, interfaces) a tratar cada integração como um caso especial. Isso é exatamente o tipo de acoplamento que o SIGMA Protocol existe para eliminar.

## Decisão

Toda resposta produzida por uma Capability (ver [ADR-0027](0027-capability-unidade-de-skill.md)), por um Agent, ou por qualquer canal externo, é normalizada no envelope:

```json
{
  "success": true,
  "data": null,
  "error": null,
  "mission": "mission-id",
  "workspace": "workspace-id",
  "events": [],
  "memory": [],
  "nextActions": [],
  "logs": []
}
```

`data` e `error` foram acrescentados ao conjunto de campos original proposto pelo Product Owner — sem um campo para o resultado de negócio da chamada (`data`) e sem um campo padronizado de erro (`error`), o envelope não conseguiria carregar, por exemplo, o `Meeting` criado por uma `CreateEvent`, nem comunicar por que uma chamada falhou. Adição sinalizada explicitamente, não uma correção silenciosa — sujeita à revisão do Product Owner. Especificação completa de cada campo em [SIGMA_PROTOCOL.md](../../SIGMA_PROTOCOL.md).

## Consequências

- Qualquer consumidor (Mission Engine, Execution Engine, um dashboard) lida com um único formato, independente de qual Skill/Plugin/Agent/canal produziu a resposta.
- Uma nova integração (novo Plugin, novo Agent, novo canal) precisa apenas normalizar sua saída nativa para este envelope — o resto do sistema não muda.
- `requires_human_approval` (booleano, definido em [ADR-0017](0017-plugin-system.md)) é superado pelo campo `nextActions`, que carrega explicitamente qualquer ação pendente de decisão humana — ver também [ADR-0029](0029-autonomia-progressiva.md).
- Exige que todo Plugin/Agent implementado a partir da Release 2 em diante produza este envelope — uma integração que devolve seu formato nativo sem tradução é considerada incompleta, não uma variação aceitável.
