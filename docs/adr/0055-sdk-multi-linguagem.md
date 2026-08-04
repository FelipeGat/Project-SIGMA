# ADR-0055: `/sdk` multi-linguagem como diretório próprio, distinto de `packages/sdk`

- **Status**: Aceito — refina o escopo de `packages/sdk` definido em [ADR-0016](0016-monorepo-apps-packages-services.md)
- **Data**: 2026-08-04

## Contexto

`packages/sdk` foi especificado na Sprint 0.2 como "biblioteca pública para sistemas externos integrarem com o SIGMA" — mas, sendo um pacote Composer dentro do monorepo PHP, só é consumível por quem já está no ecossistema Composer/PHP. Sistemas do horizonte de integração (Gestor.Alfa, AlfaSchool, AlfaControl, AlfaGym, futuros clientes e serviços de terceiros) não são todos PHP.

## Decisão

Cria-se `/sdk` na raiz do repositório, com subpastas por linguagem (`php/`, `typescript/`, `python/`) mais `docs/` de integração comum — a distribuição pública, multi-linguagem, falando apenas [SIGMA_PROTOCOL.md](../../SIGMA_PROTOCOL.md) e os [Contracts](../../contracts/). `packages/sdk` passa a ser entendido como o pacote interno consumido dentro do próprio monorepo PHP, não a via de integração externa.

## Consequências

- Um sistema do ecossistema Alfa em qualquer linguagem tem um caminho de integração dedicado, sem precisar entender a implementação interna dos Engines.
- Criado antes da Release 5, deliberadamente vazio — reserva o espaço e a decisão de design (protocolo-primeiro, multi-linguagem) sem implementar nada ainda; nenhum cliente é publicado até `services/gateway` ter API pública estável.
- `packages/sdk/README.md` atualizado para esclarecer a distinção e apontar para `/sdk`.
