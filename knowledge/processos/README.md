# Knowledge / Processos

Descrição de como a Alfa executa, hoje, os processos que já domina — antes de qualquer automação. É a base a partir da qual um [Playbook](../../playbooks/) é escrito e, mais adiante, um `Process` executável é modelado (ver [DOMAIN.md](../../DOMAIN.md)).

## Formato esperado

Um arquivo por processo (`nome-do-processo.md`): gatilho, etapas na ordem em que acontecem hoje (mesmo que manualmente), quem participa, e onde o processo costuma falhar ou atrasar — esse último ponto é o que mais orienta o desenho de uma Mission automatizada depois.

Diferença em relação a Playbook: aqui descreve-se o processo como ele é praticado; o Playbook já é escrito pensando em como o SIGMA vai planejar e executar Missions a partir dele.
