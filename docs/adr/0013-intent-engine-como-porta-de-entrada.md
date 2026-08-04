# ADR-0013: Intent Engine como porta de entrada única de linguagem natural

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Uma Mission pode se originar de uma frase em linguagem natural de um usuário ou de um evento estruturado de outro sistema. Se a interpretação dessa entrada acontecer misturada com o planejamento (dentro do Planner Engine) ou com a própria Mission, o sistema perde a capacidade de tratar "entender o que foi pedido" como um problema isolado, testável e melhorável independentemente de como o plano é montado depois.

## Decisão

Toda solicitação, em linguagem natural ou como evento de sistema, passa primeiro pelo **Intent Engine**, que a transforma numa `Intent` estruturada — o que está sendo pedido, sem ainda decidir como. Só então a Intent segue para o Planner Engine. Nenhum outro Engine interpreta linguagem natural diretamente.

## Consequências

- Interpretação de linguagem natural pode evoluir (melhor extração de entidades, desambiguação, suporte a múltiplos idiomas) sem tocar no Planner Engine ou no Mission Engine.
- Um pedido ambíguo ou inválido é rejeitado (ou pede esclarecimento) na porta de entrada, antes de qualquer Plan ser montado — evita que ambiguidade se propague para decisões de execução.
- Uma Intent malformada nunca deveria chegar ao Planner Engine — se chegar, é um defeito do Intent Engine, não uma condição que o Planner precisa tratar defensivamente.
- Eventos de sistema (não humanos) também passam pelo Intent Engine, para que toda Mission, independente de origem, tenha uma Intent de mesma forma — mesmo quando a "interpretação" é trivial por já vir estruturada.
