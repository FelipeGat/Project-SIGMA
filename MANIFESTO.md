# Manifesto do SIGMA

Este não é um documento técnico. É o porquê. Toda decisão de arquitetura, produto ou código que entrar em conflito com o que está escrito aqui está errada, mesmo que resolva um problema imediato — o manifesto vence a conveniência.

## SIGMA nunca substituirá pessoas

SIGMA orquestra trabalho, não substitui quem o realiza. Ele existe para que uma pessoa gaste menos tempo lembrando, coordenando e repetindo, e mais tempo decidindo e criando. Se uma funcionalidade proposta tem como objetivo remover uma pessoa do processo em vez de dar a ela mais alavancagem, ela não pertence ao SIGMA.

## SIGMA aumenta produtividade

O critério de sucesso de qualquer parte do sistema é: isso fez alguém (ou algum sistema) chegar mais rápido a um resultado correto? Complexidade que não se traduz em produtividade real é peso morto, mesmo que seja arquiteturalmente elegante.

## SIGMA registra conhecimento

Toda Missão executada, toda decisão tomada, todo Playbook seguido é uma oportunidade de aprendizado. O sistema que não lembra do que já fez está condenado a resolver o mesmo problema do zero para sempre. Knowledge e Memory não são funcionalidades opcionais — são o motivo pelo qual o SIGMA fica melhor com o tempo em vez de apenas maior.

## SIGMA não pertence a uma IA. SIGMA pertence à Alfa

O valor do SIGMA está na orquestração, no conhecimento acumulado e nos processos que ele aprende — não no provedor de inteligência artificial usado num dado momento. Nenhuma decisão de produto ou arquitetura pode criar dependência irreversível de uma IA específica.

## Toda IA pode ser trocada

Claude, ChatGPT, Gemini, Manus — hoje são os quatro especialistas do SIGMA. Amanhã podem ser outros, mais, ou diferentes. Se trocar de provedor de IA por trás de um Agente exige reescrever domínio, a arquitetura falhou. Ver [ADR-0004](docs/adr/0004-tres-camadas-ia-agente-skill.md).

## Toda integração pode ser substituída

O mesmo vale para sistemas externos. Gestor.Alfa, GitHub, WhatsApp, qualquer integração — todas são Skills, substituíveis sem tocar no núcleo de orquestração. O dia em que o Gestor.Alfa for substituído por outro sistema, o SIGMA deve precisar apenas de uma nova Skill, não de uma reescrita.

## Toda decisão deve priorizar desacoplamento

Entre a solução mais rápida de implementar hoje e a solução mais desacoplada, o SIGMA escolhe a segunda. Isso já custou tempo na Fase Foundation (nenhum código foi escrito antes da arquitetura estar definida) e vai continuar custando tempo em cada épico. É um custo aceito conscientemente, não um acidente de processo.

## SIGMA é um Sistema Operacional, não uma IA

SIGMA não é um chat inteligente com plugins. É uma plataforma operacional corporativa: tem um Kernel, tem Engines com responsabilidades específicas, tem um ciclo de vida de Missão auditável. Quando a documentação, o código ou a conversa sobre o SIGMA soarem como "um assistente que responde perguntas", é sinal de que a visão está se estreitando — e isso deve ser corrigido, não seguido. Ver [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md).

---

Este manifesto é revisado raramente e por decisão explícita — não por conveniência de um épico específico. Mudanças aqui são, por definição, mudanças de rumo da empresa, não do software.
