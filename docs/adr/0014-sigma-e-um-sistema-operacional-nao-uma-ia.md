# ADR-0014: SIGMA é um Sistema Operacional, não uma IA

- **Status**: Aceito
- **Data**: 2026-08-04

## Contexto

Em revisão da documentação da Fase Foundation, notou-se um risco de enquadramento: ao falar tanto de Agentes e IAs, a documentação e — por consequência, futuras decisões de produto e arquitetura — corriam o risco de tratar o SIGMA como "um chat inteligente com plugins", exatamente o que [VISION.md](../../VISION.md) e [MANIFESTO.md](../../MANIFESTO.md) explicitamente dizem que ele não é. Nomenclatura não é só estética — molda como quem lê o código e a documentação pensa sobre o sistema.

## Decisão

Toda documentação e, a partir do primeiro código, toda nomenclatura de código reforça que o SIGMA é uma plataforma operacional: possui um **Kernel** e **Engines** com responsabilidade única (ver [ADR-0011](0011-arquitetura-em-camadas-de-engines.md)), não "uma IA que faz coisas". O termo "IA" continua correto e necessário para descrever os provedores de inteligência artificial (Claude, ChatGPT, Gemini, Manus) que os Agents usam — o que muda é que o SIGMA, como um todo, nunca é descrito ou tratado como se ele mesmo fosse uma IA.

## Consequências

- Toda proposta de funcionalidade nova é avaliada também por essa lente: ela reforça o SIGMA como plataforma operacional, ou o reduz a "assistente que responde"? A segunda leitura é motivo para revisar o desenho, não só a redação.
- Onboarding de qualquer pessoa nova ao projeto (humana ou Agent) deve partir de [MANIFESTO.md](../../MANIFESTO.md) antes de qualquer detalhe técnico, para fixar esse enquadramento primeiro.
- Não há mudança de escopo técnico nesta ADR — é uma decisão de enquadramento e nomenclatura, mas com peso deliberadamente igual a uma decisão técnica, porque a forma como o time (e as IAs que trabalham nele) pensa sobre o sistema afeta toda decisão futura.
