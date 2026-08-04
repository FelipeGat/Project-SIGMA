# Identity Lifecycle

Como uma Identity nasce, se autentica, carrega contexto e fica pronta para ser usada pelo resto do SIGMA — o equivalente, para identidade, do que [BOOTSTRAP.md](BOOTSTRAP.md) é para o Kernel. Pré-requisito para a implementação da Release 3 — Identity Engine, ao lado de [IDENTITY_MODEL.md](IDENTITY_MODEL.md) e `contracts/Identity.contract.yaml`.

## Nota sobre vocabulário

Este documento usa **Identity** como o objeto raiz, resolvido em runtime, do qual tudo mais deriva — User, Workspace, Permissions, Context, Autonomy. É uma correção de rumo do Product Owner sobre [IDENTITY_MODEL.md](IDENTITY_MODEL.md): lá, o objeto de valor resolvido em runtime tem hoje o nome `Context`, e `Session` está ligada a `User`. A partir desta revisão, `Identity` passa a ser o objeto raiz (`Identity → User → Workspace → Permissions → Context → Autonomy`), `Session` passa a autenticar uma `Identity` (não um `User` diretamente — importante quando uma mesma pessoa puder ter sessões concorrentes em contextos diferentes, ex: Workspace Comercial e Workspace Financeiro), e `Context`, dentro dessa hierarquia, deve ser **imutável**: trocar de Workspace implica uma nova Session e um novo Context, nunca uma mutação do Context vigente.

Essas mudanças foram aprovadas como direção, mas a reconciliação formal com IDENTITY_MODEL.md (renomear a entidade hoje chamada `Context` para esse novo papel de `Identity`, mover `Session` para debaixo dela, formalizar imutabilidade) acontece via ADR durante a Implementation da Release 3 — decisão explícita do Product Owner de não reabrir a Proposal para isso agora. Este documento já escreve o fluxo com o vocabulário final, para não ter que ser reescrito quando essa reconciliação acontecer.

## O fluxo

```
Identity criada
      ↓
Autenticada
      ↓
Contexto carregado
      ↓
Session criada
      ↓
Workspace selecionado
      ↓
Permissões resolvidas
      ↓
Autonomia calculada
      ↓
Identity pronta
```

### 1. Identity criada

Um `User` existe, associado a exatamente um `Tenant` (ver [IDENTITY_MODEL.md](IDENTITY_MODEL.md#user)). Isso por si só não é uma Identity utilizável — é o registro estático de quem a pessoa é, sem nenhum contexto de uso resolvido ainda.

### 2. Autenticada

O User apresenta uma credencial válida (nesta Release: e-mail/senha, hash com Argon2id — ver Escopo da Proposal da Release 3). A autenticação prova "esta requisição é de fato deste User"; ainda não decide nada sobre Workspace, Permission ou Autonomy.

### 3. Contexto carregado

A partir do User autenticado, o Identity Engine carrega o que é estável para aquele User independente de qual Workspace ele vier a selecionar: seu Tenant, as Companies e Workspaces a que tem acesso, os Teams de que participa. Esta etapa não resolve ainda Permission/Autonomy — isso depende do Workspace, que ainda não foi escolhido.

### 4. Session criada

Uma `Session` é emitida — token, momento de emissão, expiração. A Session autentica a Identity (não diretamente um User isolado — ver "Nota sobre vocabulário" acima), e é o que perdura entre requisições. Nenhum Workspace está selecionado ainda nesta etapa; uma Session pode existir "genérica", antes de qualquer seleção.

### 5. Workspace selecionado

O User (ou o cliente, em nome do User) escolhe em qual Workspace vai operar. Esta escolha é o que decide o resto do fluxo — Permission e Autonomy são sempre relativas a um escopo (Tenant, Company ou Workspace, ver [IDENTITY_MODEL.md](IDENTITY_MODEL.md#relações)), e o Workspace é o escopo mais específico e mais comum.

### 6. Permissões resolvidas

A partir do Workspace selecionado, o Identity Engine resolve o conjunto efetivo de Permissions do User naquele escopo — via seus `RoleAssignment`s diretos e via os Teams de que participa, exatamente como modelado em [IDENTITY_MODEL.md](IDENTITY_MODEL.md#permission).

### 7. Autonomia calculada

O Autonomy Level efetivo é calculado para aquele escopo (regra do menor valor entre User/Role e o exigido pela Capability sendo chamada — [SIGMA_PROTOCOL.md §5](SIGMA_PROTOCOL.md#5-autonomia-progressiva)).

### 8. Identity pronta

O objeto `Identity` — Session + User + Workspace ativo + Permissions efetivas + Autonomy — está completo e **imutável**. É isso que o Kernel disponibiliza a todo Engine como contexto de execução. Trocar de Workspace não muta essa Identity; volta ao passo 4 (nova Session) e reconstrói o fluxo do zero para o novo escopo.

## Por que Context imutável importa

Um Context/Identity mutável no meio de uma execução (ex: trocar de Workspace enquanto uma Mission está em andamento) cria uma classe inteira de bugs de concorrência e de auditoria — qual Workspace estava ativo quando essa ação foi de fato executada deixa de ter resposta única. Tratando cada seleção de Workspace como uma Session nova, toda ação tem uma Identity imutável e rastreável associada a ela, do início ao fim.

## Onde vive

Implementado por `packages/identity-engine`/`services/auth` (Release 3 — Identity Engine), disponibilizado a todo Engine seguinte através do [Kernel](KERNEL.md), exatamente como descrito para "Context" em [WORKSPACES.md](WORKSPACES.md) e [MULTITENANCY.md](MULTITENANCY.md) — esses dois documentos continuam válidos; `Identity`, aqui, é o nome do objeto que eles descrevem como "contexto de execução resolvido pelo Kernel".
