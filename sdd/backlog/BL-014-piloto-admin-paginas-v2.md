# BL-014 — Recuperação e cutover do piloto admin-paginas-v2

- **Tipo:** Epic/Architecture/Migration
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** `gestor/modulos/admin-paginas`, `gestor/modulos/admin-paginas-v2` e sua fronteira de publicação com `publisher-pages`
- **Relacionados:** BL-011, BL-012, BL-013, BL-015, BL-016, BL-018, BL-038, BL-039, BL-041, BL-042, BL-043, BL-044

## Diagnóstico

O piloto v2 não é uma versão atualizada do módulo vigente:

- seu PHP e manifesto estão vários meses atrás do `admin-paginas` atual;
- ainda contém chamadas de banco v1 e nove chamadas de interface v1;
- depende de sintaxe PHP 8.5 e falha no lint do PHP 8.4;
- não incorpora integralmente configurações atuais de chave natural, preservação de campos alterados pelo usuário, recursos e integrações de IA;
- não possui testes dedicados.

Os dois módulos usam a mesma tabela `paginas`. Logo, não existe uma cópia geral de registros de negócio para uma tabela nova. O problema real é sincronizar implementação e metadados e fazer o cutover sem duplicar rotas, menus, permissões e recursos.

O recorte não pode considerar `admin-paginas` o único dono das mutações: `publisher-pages`, interface genérica, plugins e sincronizadores também escrevem em `paginas`. A matriz do piloto deve caracterizar essas fronteiras e encaminhá-las ao domínio central do BL-041.

## Estratégia proposta

1. Congelar o piloto atual como referência, sem promovê-lo diretamente.
2. Construir matriz de paridade entre PHP, manifesto, dados, recursos, permissões, hooks e fluxos do módulo vigente e do piloto.
3. Reaplicar a experiência funcional atual sobre os contratos seguros de banco/interface v2.
4. Reimplementar o fluxo procedural como módulo/casos de uso OO sobre a plataforma CRUD do BL-039, sem fazer de `admin-paginas` a superclasse implícita de todos os módulos.
5. Delegar publicação, agendamento, caminho, visibilidade e indexabilidade aos casos de uso do BL-041; o módulo administrativo não gera sitemap nem chama indexadores diretamente.
6. Preservar a tabela `paginas`, seus IDs canônicos e alterações do usuário.
7. Migrar os metadados do módulo v2 para os identificadores canônicos de `admin-paginas`, evitando manter duas telas concorrentes.
8. Executar cutover por feature flag/release v3 com caminho de rollback.

## Matriz mínima de paridade

- listar, buscar, ordenar e paginar;
- criar, editar, clonar e excluir;
- layout, caminho, framework, módulo, idioma e permissão;
- HTML, CSS e JavaScript associados;
- variáveis, histórico, backup e restauração;
- draft/publicação/despublicação, agendamento, expiração, visibilidade e indexabilidade;
- integração de `publisher-pages` sem duplicar validação, 301, histórico ou eventos;
- canonical, idiomas e metadados exigidos pelo contrato de descoberta;
- recursos de IA, prompts e modos atuais;
- chave natural `[language, modulo, id]`;
- `preserve_on_user_modified` para nome, layout, caminho, framework, permissão, HTML e CSS;
- instalação, atualização e desinstalação do manifesto;
- permissões, menu, breadcrumbs, redirects e links profundos.

## Migração de dados e metadados

- inventariar registros paralelos `admin-paginas`/`admin-paginas-v2` em páginas, módulos, menus, permissões, variáveis e recursos;
- definir resolução determinística para duplicidades;
- preservar conteúdo marcado como modificado pelo usuário;
- tornar a transformação idempotente e gerar relatório antes/depois;
- não sobrescrever dados de produção para alcançar paridade do manifesto;
- retirar o identificador experimental somente após comprovar que não há referências órfãs.

## Validação

- caracterização automatizada do módulo atual antes da reescrita;
- comparação funcional lado a lado com conjunto de dados realista;
- testes de acesso negado, CSRF, sessão expirada e IDOR;
- testes de atualização de uma instância 2.x com conteúdo personalizado;
- teste de rollback sem reversão destrutiva da tabela `paginas`;
- auditoria visual e de acessibilidade no renderer Tailwind.

## Critérios de aceite para futura implementação

- paridade funcional documentada e aprovada;
- zero chamadas de banco/interface v1 no módulo novo;
- uma única rota e identidade canônica para administração de páginas;
- nenhuma perda de páginas ou customizações do usuário;
- manifesto compatível com merge/update e chaves naturais atuais;
- piloto serve como teste de referência para migrar os demais módulos.
- o módulo não acessa superglobais, banco, sessão ou renderer por variável global e não duplica o pipeline CRUD comum.
- `admin-paginas` e `publisher-pages` não escrevem diretamente em `paginas` nos fluxos migrados e não executam side effects externos dentro da transação.
- eventos de publicação alimentam a projeção do sitemap sem anunciar páginas protegidas, noindex, draft ou fora da janela.

## Próxima decisão

Promover primeiro um batch de caracterização/paridade; a reescrita só começa depois dos contratos de BL-011, BL-012 e BL-013.
