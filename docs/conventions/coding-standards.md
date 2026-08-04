# Padrão de Código

Aplica-se a todo código de aplicação a partir do primeiro épico de implementação (E1). Nenhum item abaixo é opcional — um Pull Request que não atende a algum deles não está pronto para revisão.

## Todo código deve possuir

- **Documentação** — o "porquê" de decisões não óbvias, não o "o quê" (nomes bem escolhidos já dizem o quê).
- **Testes** — cobertura do comportamento, não da linha; testes de domínio isolados de infraestrutura sempre que possível.
- **Logs** — toda ação relevante de negócio (criação/transição de Mission, invocação de Skill, decisão de Agent) é logada e correlacionável.
- **Tratamento de erros** — falhas esperadas são tratadas explicitamente; falhas inesperadas não são silenciadas, são propagadas e logadas.
- **Versionamento** — mudanças incompatíveis em contrato público (API, evento de domínio) são versionadas, nunca alteradas silenciosamente.
- **Nomes padronizados** — conforme [naming-conventions.md](naming-conventions.md).
- **Alta legibilidade** — código lido é código revisado; se uma função exige um comentário para ser entendida, o primeiro passo é considerar renomear/reestruturar antes de comentar.

## Comentários

Comentários existem apenas quando o "porquê" não é óbvio a partir do código: uma restrição externa, uma decisão que parece estranha sem contexto, um workaround para um bug específico. Nunca comentar o "o quê" — isso é responsabilidade do nome do identificador.

## Princípios de design que todo módulo deve respeitar

- **SOLID** em toda classe/módulo.
- **Clean Architecture** — domínio não conhece framework; framework implementa contratos do domínio.
- **DDD** — linguagem ubíqua (ver [naming-conventions.md](naming-conventions.md)) usada de forma consistente entre código, documentação e conversa.
- **Alta coesão, baixo acoplamento** — um módulo que precisa conhecer detalhes internos de outro módulo para funcionar está mal desenhado; a comunicação correta é via evento de domínio ou contrato explícito (interface).

## Revisão

Todo Pull Request de um épico aprovado é revisado contra:

1. Os Critérios de Aceite definidos na proposta do épico.
2. Este padrão de código.
3. As convenções de nomenclatura.

PRs que não atendem a algum ponto retornam para ajuste antes de merge — não há exceção "só desta vez" para código que entra no SIGMA.
