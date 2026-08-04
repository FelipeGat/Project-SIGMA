# ADR-0017: Skills são implementadas como Plugins carregados dinamicamente — nunca compiladas no Kernel

- **Status**: Aceito — refina [ADR-0006](0006-integracao-externa-e-sempre-uma-skill.md)
- **Data**: 2026-08-04

## Contexto

ADR-0006 já estabelece que toda integração externa é uma Skill com contrato padronizado. Não estava definido, tecnicamente, se essas Skills seriam classes compiladas e referenciadas diretamente pelo Kernel/Skill Engine (`use App\Skills\GestorSkill`), ou carregadas dinamicamente sem o núcleo conhecer a implementação concreta. A primeira abordagem, embora mais simples de implementar inicialmente, cria acoplamento de compilação entre o núcleo e cada integração — exatamente o tipo de acoplamento que o [MANIFESTO.md](../../MANIFESTO.md) trata como inaceitável ("toda integração pode ser substituída").

## Decisão

Toda Skill é implementada como um **Plugin**: um diretório em [/plugins](../../plugins/) com um `manifest.json` (schema em [plugins/manifest.schema.json](../../plugins/manifest.schema.json)) que descreve tudo que o Skill Engine precisa para carregar, autorizar e invocar aquela Skill, sem importar nenhuma classe concreta do Plugin. Detalhamento completo em [PLUGIN_SYSTEM.md](../../PLUGIN_SYSTEM.md).

## Consequências

- Adicionar, remover ou atualizar uma integração não exige alterar ou recompilar o Kernel/Skill Engine — apenas adicionar/atualizar um diretório em `plugins/`.
- Abre caminho para Plugins de terceiros no horizonte de [VISION_2030.md](../../VISION_2030.md) (`sigma-skills` como catálogo), já que o contrato de carregamento é público e não depende de acesso ao código-fonte do núcleo.
- Exige que o Skill Engine implemente um mecanismo de descoberta e validação de manifest antes de qualquer Plugin real existir — trabalho adicional no épico da camada L7, em troca do desacoplamento.
- Um manifest malformado ou um Plugin que não cumpre seu contrato declarado é um erro de carregamento detectável cedo (na descoberta), não um bug silencioso em produção.
