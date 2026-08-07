# BL-012 — Modularização das bibliotecas v2

- **Tipo:** Architecture/Maintainability
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** `gestor/bibliotecas/banco-v2.php`, `gestor/bibliotecas/interface-v2.php` e respectivos consumidores
- **Relacionados:** BL-011, BL-013, BL-014, BL-038, BL-039

## Contexto

As duas bibliotecas v2 concentram responsabilidades demais em arquivos extensos. `interface-v2.php` já possui aproximadamente 3 mil linhas e reúne enums, objetos de configuração, formatação, alertas, histórico, backup, componentes, CRUD, AJAX e fachada. O banco v2 tende ao mesmo problema à medida que ganhar o contrato definitivo de prepared statements.

Arquivos monolíticos aumentam o risco de regressão, conflito entre agentes, carga de contexto e testes pouco focados. A divisão não deve produzir herança artificial: composição, contratos pequenos e objetos imutáveis são preferíveis a uma grande árvore de subclasses.

## Resultado desejado

Transformar banco v2 e interface v2 em pacotes coesos, namespaced e carregados pelo autoload do Composer, mantendo fachadas temporárias para a migração gradual do código legado.

## Arquitetura proposta

```text
gestor/src/
  Database/
    Contract/      conexões, comandos e transações
    Driver/        MySQL/MariaDB e futuras variações
    Query/         builder, parâmetros, identificadores e paginação
    Schema/        metadados e migrações
    Exception/     taxonomia estável de falhas
    Legacy/        adaptador v1 temporário e instrumentado
  AdminInterface/
    Contract/      renderer, assets, autorização e resposta AJAX
    Config/        value objects de campos, colunas, ações e validação
    Crud/          casos de uso de listagem, inclusão, edição e exclusão
    Form/          binding, validação e normalização
    Rendering/     componentes e templates seguros
    History/       histórico e backup
    Http/          request, resposta, CSRF e sessão
    Assets/        registro e inicialização de JS/CSS
    Legacy/        fachada procedural temporária
```

Os nomes finais dependem de ADR. A separação deve acompanhar responsabilidades e testes, não uma meta arbitrária de quantidade de arquivos.

## Trabalho planejado

1. Criar ADR de namespaces, autoload PSR-4, dependências permitidas e política de compatibilidade.
2. Extrair enums e value objects sem mudar comportamento.
3. Definir interfaces pequenas para conexão, comando preparado, transação, renderer, autorização, CSRF e respostas.
4. Extrair serviços por caso de uso, injetando dependências em vez de acessar globais diretamente.
5. Manter `banco_v2()` e `interface_v2()` como fachadas de transição; marcar e medir chamadas legadas.
6. Proibir dependência inversa: banco não conhece interface; domínio da interface usa somente o contrato seguro do banco.
7. Adotar testes unitários por classe e testes de contrato para a fachada.
8. Incluir verificação arquitetural no CI para impedir ciclos, SQL cru fora da camada autorizada e novos globais.
9. Usar uma diretriz de manutenção: classes preferencialmente pequenas; arquivos acima de aproximadamente 800–1.000 linhas exigem revisão/ADR. O limite não substitui coesão.

## Critérios de aceite para futura implementação

- autoload não depende da ordem manual de `require`;
- cada pacote possui API pública documentada e internals não consumidos pelos módulos;
- testes cobrem comportamento antes e depois das extrações;
- fachadas legadas não duplicam regras de negócio;
- nenhuma extração reduz prepared statements, escaping de saída, CSRF ou autorização;
- o pacote pode ser analisado e alterado por partes sem carregar um arquivo monolítico.

## Fora de escopo desta discussão

- implementar as classes agora;
- remover as fachadas antes da migração de todos os consumidores;
- criar subclasses apenas para reduzir contagem de linhas.

## Próxima decisão

Aprovar o mapa de pacotes e promover a extração estrutural como primeiro lote da futura branch v3.0, antes da migração massiva dos módulos.

Esta modularização é a primeira fatia técnica da migração OO sistêmica do BL-038. A camada `AdminInterface/Crud` deve adotar o contrato especializado do BL-039, evitando que `InterfaceV2` permaneça uma fachada monolítica ou que a divisão gere uma segunda plataforma CRUD concorrente.
