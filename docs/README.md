# Documentação do Servinder Subsites — Divi

Este repositório é a fonte canônica do adaptador Divi. A documentação operacional da rede WordPress continua na IA_Central e deve ser consultada junto com estes documentos; dados de produção e instruções da rede não são duplicados aqui.

## Comece por aqui

1. [README do plugin](../README.md): propósito, requisitos, instalação e escopo.
2. [Arquitetura](architecture.md): limites entre o adaptador e o plugin core, fluxo de renderização e pontos de extensão.
3. [API](api.md): atributos Divi e contratos dos hooks públicos.
4. [Divi 5](divi-5.md): integração com as APIs oficiais, validações feitas no editor e limitações conhecidas de renderização.
5. [CHANGELOG](../CHANGELOG.md): alterações por versão.

## Referências da rede Servinder

- [Mapa central WordPress Servinder](../../../IA_Central/docs/technical/servinder-wordpress-central.md): sites, versões instaladas, estado da VPS e procedimentos operacionais. É documentação local e privada do ambiente do proprietário.
- [Fonte do plugin core Servinder Subsites](https://github.com/anarkaike/servinder-subsites): regras de visibilidade, modelo de dados e APIs compartilhadas.
- [Documentação do core](https://github.com/anarkaike/servinder-subsites/tree/main/docs): índice de IA, playbook, arquitetura, API e decisões de arquitetura.

## Evidência e limites atuais

A lógica PHP de inclusão/exclusão, o mapeamento dos selects numéricos do Divi e o escopo de consultas Loop são cobertos pelo smoke integrado. A descoberta do módulo e a gravação dos atributos foram verificadas no Divi 5. Uma página de QA salva foi renderizada corretamente por `do_blocks()` no contexto correspondente, mas sua prévia HTTP autenticada mostrou apenas o shell da página. Portanto, a saída completa em uma requisição real ainda está sob investigação. Os controles de Loop também precisam de validação ponta a ponta no Visual Builder.

Os detalhes, datas e artefatos dessa verificação estão em [Divi 5 — validação no ambiente](divi-5.md); o mapa central registra o estado do release e da instalação.
