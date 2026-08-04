# Skill: GitHubSkill

Integração com o GitHub — usada principalmente pelo Agent de Engenharia (Claude) para Subtasks técnicas.

## Configuração

- Token de acesso da organização/conta de serviço do SIGMA no GitHub.
- Repositórios aos quais esta instância da Skill tem acesso (allowlist explícita — não acesso irrestrito à organização).

## Permissões

- `github.issues.ler`, `github.pull_requests.ler` — leitura, baixo risco.
- `github.issues.criar`, `github.pull_requests.criar`, `github.commits.criar` — escrita, exige Mission com origem rastreável.
- Nunca concedida por padrão: merge em branch protegida, force-push, exclusão de branch/repositório — essas operações, se um dia necessárias, exigem permissão nomeada e explícita, não incluída na permissão genérica de escrita.

## Entrada

Contrato específico por operação (ex: `CriarPullRequestInput{repositorio, branch, titulo, descricao}`) — detalhado no épico que implementar esta Skill.

## Saída

Dados normalizados (ex: URL e identificador do Pull Request criado, status de um Issue).

## Eventos

- `github_skill.invoked`
- `github_skill.succeeded` / `github_skill.failed`

## Logs

Toda invocação registrada com Mission, Agent, operação, repositório afetado e resultado, correlacionável no Audit Engine.

## Testes

Contrato coberto por testes automatizados contra um repositório de teste antes de qualquer uso em repositórios reais da organização.

## Documentação

Prevista para o Épico E9 — Expansão de Skills (ver [ROADMAP.md](../ROADMAP.md)), salvo se um caso de uso do E1–E4 (ex: o próprio SIGMA documentando seu progresso) antecipar sua necessidade.
