# ADR-0045: System Manifest — o Bootstrap lê um único arquivo, o resto é descoberto

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Sem uma declaração explícita e centralizada do que compõe uma instalação do SIGMA, o Bootstrap precisaria inferir o que carregar a partir do conteúdo do disco (ex: tudo que existe em `packages/` e `plugins/`) — o que impede, por exemplo, um ambiente de homologação rodar deliberadamente um subconjunto de Engines/Plugins, e torna implícito algo que deveria ser uma decisão explícita e revisável.

## Decisão

Cria-se o **System Manifest** — um único arquivo YAML, lido pelo Bootstrap na etapa `discover` do [Lifecycle](../../BOOTSTRAP.md#como-o-sigma-inicia), declarando `project`, `version`, `engines`, `plugins`, `providers` e `workspace`. O Bootstrap carrega exatamente o que o Manifest lista — nunca infere a partir do que existe no monorepo. Especificação completa em [SYSTEM_MANIFEST.md](../../SYSTEM_MANIFEST.md).

## Consequências

- Diferentes ambientes (produção, homologação, uma instalação white-label futura — ver [VISION_2030.md](../../VISION_2030.md)) podem rodar subconjuntos diferentes do sistema com um único arquivo diferente, sem tocar em código.
- O que uma instalação do SIGMA de fato executa se torna uma decisão versionada e revisável em Pull Request, não um efeito colateral de o que está presente no disco.
- Um Module presente no monorepo mas ausente do Manifest simplesmente não sobe — comportamento intencional, documentado, não um bug de "por que esse Engine não carregou".
- Implementação (parser, validação) nasce com a Release 2 — SIGMA Bootstrap.
