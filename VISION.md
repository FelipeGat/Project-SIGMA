# VISÃO — Project SIGMA

## O problema

A Alfa Soluções opera hoje como um conjunto de sistemas independentes — Gestor.Alfa, AlfaControl, AlfaGym, AlfaJornada, AlfaCam, e outros que virão. Cada um resolve bem o seu domínio, mas nenhum deles enxerga o todo. Coordenar pessoas, clientes, projetos e automações entre esses sistemas hoje depende de alguém (geralmente humano) que carrega o contexto na cabeça e decide, caso a caso, o que fazer, em qual sistema, e em que ordem.

SIGMA existe para ser esse "alguém" — de forma estruturada, auditável e escalável.

## O que o SIGMA é

SIGMA é o **Sistema Operacional Corporativo** da Alfa: uma camada de orquestração que recebe intenção em linguagem natural — de uma pessoa ou de outro sistema — e a transforma em ação coordenada através de agentes especializados (IAs) e integrações (Skills) com os sistemas que já existem.

> "Sigma, participe da reunião do cliente Brenno."

Isso é uma **Missão**. O SIGMA interpreta, planeja, decompõe em subtarefas, escolhe as Skills e Agentes certos, executa, valida, registra e conclui. Toda ação relevante no ecossistema Alfa deve poder nascer de uma frase assim.

## O que o SIGMA nunca deve virar

Estas fronteiras são deliberadas e protegem a arquitetura de virar, com o tempo, exatamente aquilo que o SIGMA foi criado para evitar:

- **Não é um chatbot.** Uma Missão não é uma conversa que termina numa resposta de texto — ela termina numa ação validada e registrada.
- **Não é um sistema CRUD.** SIGMA não é onde se cadastra cliente, fatura ou obra. Isso já existe no Gestor.Alfa, no AlfaControl, no AlfaGym. SIGMA orquestra esses sistemas via API; não os substitui nem duplica seus dados.
- **Não é um assistente virtual de um único usuário.** É infraestrutura compartilhada, multiusuário, multiempresa, pensada para operar em escala — não um copiloto pessoal.
- **Não executa nada diretamente.** SIGMA nunca fala direto com o mundo. Ele decide *o quê* fazer e delega o *como* a um Agente, que age através de uma Skill. Essa indireção é o que torna o sistema auditável, substituível (trocar de provedor de IA sem reescrever o domínio) e seguro.

## Por que Missão é a entidade central

Toda funcionalidade do SIGMA — cadastro, integração, automação, relatório — existe para servir ao ciclo de vida de uma Missão: interpretar, planejar, decompor em subtarefas, escolher Skills, executar, validar, registrar, concluir. Se uma funcionalidade não se conecta a esse ciclo, ou ela pertence a outro sistema, ou o modelo de domínio ainda não está certo.

## Os quatro pilares

| Pilar | Pergunta que responde | Análogo |
|---|---|---|
| **Knowledge** | O que o sistema sabe? | Base de conhecimento, documentação, contexto de domínio |
| **Memory** | O que o sistema aprendeu? | Histórico, decisões passadas, preferências observadas |
| **Mission** | O que o sistema está executando ou já executou? | O trabalho em si |
| **Skill** | O que o sistema sabe fazer? | Capacidades concretas de ação no mundo |

## Escala como requisito de design, não como aspiração

Toda decisão arquitetural do SIGMA é tomada considerando milhares de usuários e dezenas de sistemas integrados — não porque é isso que existe hoje, mas porque retrofitar escala em cima de acoplamento é o erro mais caro que um sistema desse tipo pode cometer. Isso não significa construir tudo de uma vez: significa que cada peça construída, por menor que seja, já nasce desacoplada, orientada a eventos e substituível.

## Horizonte

SIGMA começa por um único épico validado ponta a ponta (ver [ROADMAP.md](ROADMAP.md)) e cresce por aprovação explícita, épico a épico. O objetivo de longo prazo é que qualquer pessoa na Alfa possa expressar uma intenção em linguagem natural e o SIGMA saiba — ou aprenda a saber — orquestrar os sistemas certos para realizá-la.
