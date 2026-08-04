# Knowledge / Engenharia

Padrões técnicos e decisões de arquitetura dos sistemas do ecossistema Alfa (Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam) que o SIGMA precisa conhecer para orquestrá-los corretamente — não a documentação de arquitetura do próprio SIGMA (isso vive em [docs/architecture/](../../docs/architecture/)).

## Formato esperado

Um arquivo por sistema (`gestor-alfa.md`, `alfacontrol.md`...) descrevendo: que API está disponível para o SIGMA consumir, particularidades e limitações conhecidas, e convenções que uma Skill que integra com aquele sistema precisa respeitar.

Este conteúdo é o que justifica e informa cada nova Skill em [/skills](../../skills/) — uma Skill não deveria ser escrita sem que o sistema que ela integra esteja, no mínimo minimamente, descrito aqui.
