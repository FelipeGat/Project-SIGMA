# SDK

Bibliotecas cliente, em múltiplas linguagens, para qualquer sistema integrar com o SIGMA falando apenas [SIGMA_PROTOCOL.md](../SIGMA_PROTOCOL.md) e os [Contracts](../contracts/) — sem conhecer a implementação interna dos Engines. Ver [VISION_2030.md](../VISION_2030.md) (`sigma-sdk`).

Distinto de `packages/sdk/`: aquele é o pacote Composer interno, consumido dentro do próprio monorepo PHP. Este diretório é a distribuição externa, multi-linguagem — para sistemas como Gestor.Alfa, AlfaSchool, AlfaControl, AlfaGym e futuros clientes/parceiros que não fazem parte do monorepo.

| Pasta | Linguagem/conteúdo |
|---|---|
| [php/](php/) | Cliente PHP |
| [typescript/](typescript/) | Cliente TypeScript/JavaScript |
| [python/](python/) | Cliente Python |
| [docs/](docs/) | Documentação de integração comum a todos os clientes |

Vazio na Fase Foundation — criado deliberadamente antes da Release 5 para reservar o espaço e a decisão de design (multi-linguagem, protocolo-primeiro), não para ser implementado agora. Nenhum cliente é publicado até que `services/gateway` tenha uma API pública estável.
