# BL-018 — DataTables 3 como motor frontend da C2FDataGrid

- **Tipo:** Architecture/Migration/Performance/Security
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Decisão preliminar:** 2026-08-07 — DataTables 3 no frontend, protocolo e backend próprios do Conn2Flow
- **Escopo:** tabelas/listagens administrativas e contrato AJAX da interface
- **Relacionados:** BL-011, BL-013, BL-014, BL-016, BL-017, BL-021

## Decisão arquitetural preliminar

Adotar DataTables 3 como motor de tabela no navegador, sem expor seu protocolo ou suas classes aos módulos. O Conn2Flow será proprietário de:

- componente público `C2FDataGrid`;
- schema de colunas, filtros, ordenação e ações;
- controles e markup Tailwind;
- protocolo HTTP/AJAX;
- autorização, CSRF, sessão e tratamento de erros;
- consultas, paginação e contagens via banco v2;
- adapters que traduzem entre `C2FDataGrid` e DataTables.

DataTables será um detalhe interno substituível. A decisão continua em backlog até promoção humana; ela não autoriza implementação.

## Correção do diagnóstico anterior

O repositório embarca DataTables 1.10.23 com Responsive 2.2.7 e adaptação Semantic/Fomantic. Essa cópia é antiga. Entretanto, o projeto DataTables está ativo: a versão 3 foi lançada em julho de 2026 e removeu a dependência obrigatória de jQuery, modernizou o core com TypeScript/ESM e manteve compatibilidade da API pública como objetivo.

A integração oficial Tailwind ainda não está madura/completa. Portanto, a v3 do Conn2Flow não deve depender do tema Tailwind experimental do DataTables. O design system próprio controlará o visual.

## Limite de responsabilidades

```text
Módulo PHP
  -> schema C2FDataGrid (IDs lógicos, permissões e formatos)
  -> endpoint C2FDataGrid (request/response próprio)
  -> repositório banco v2 (allowlist + parâmetros preparados)

Browser
  -> C2FDataGrid (API pública, Tailwind, acessibilidade e lifecycle)
  -> adapter DataTables3 (tradução de estado/eventos)
  -> DataTables 3 (motor interno de linhas, ordenação e draw)
```

Nenhum módulo deve receber ou montar diretamente `draw`, `columns[n][data]`, `recordsFiltered`, seletores jQuery ou objetos DataTables.

## Contrato proposto da C2FDataGrid

### Request lógico

- `requestId`: correlação/controle de respostas fora de ordem;
- `offset` e `limit`, com limites impostos pelo servidor;
- `sort`: lista de `{ columnId, direction }`;
- `search`: termo normalizado;
- `filters`: IDs lógicos e valores tipados;
- `context`: ação/módulo necessários, sem nomes SQL.

### Response lógico

- `requestId`;
- `rows`;
- `total` e `filteredTotal`;
- metadados de página;
- ações autorizadas por linha quando aplicável;
- envelope uniforme de erro, autenticação expirada e redirect.

O adapter converte esse contrato para o callback esperado pelo DataTables. Nomes reais de tabela/coluna são resolvidos exclusivamente no schema confiável do servidor.

## Uso planejado do DataTables 3

- instalar/pinar por package manager e empacotar localmente; não carregar por CDN;
- usar API nativa/ESM, sem jQuery;
- desabilitar controles visuais nativos quando forem substituídos pelos componentes Tailwind da `C2FDataGrid`;
- conectar busca, paginação, tamanho de página e ordenação Tailwind à API pública do adapter;
- manter `<table>`, `caption`, cabeçalhos e semântica acessível;
- usar Responsive somente atrás do adapter; detalhes móveis devem abrir o `C2FDialog`, não modal Fomantic;
- renderizar ações por componentes Conn2Flow e autorização do servidor;
- não adotar extensões DataTables Plus/comerciais sem ADR específico;
- não depender de integrações Tailwind experimentais do fornecedor.

## Diferenças relevantes: 1.10.23 → 3.x

### Introduzidas na linha 2.x

- `layout` e features componíveis no lugar do antigo `dom`;
- busca fixa/nomeada e melhorias de busca;
- busca neutra a diacríticos;
- captions e linguagem de tipos de registro;
- data types e renderização mais extensíveis;
- `ready()` para inicialização assíncrona;
- mapeamento de propriedades server-side via `ajax.dataSrc`;
- APIs adicionais de coluna e melhor suporte a cabeçalhos/rodapés complexos;
- mais variáveis CSS e classes internas reorganizadas.

### Introduzidas na linha 3.x

- nenhuma dependência externa obrigatória; jQuery deixa de ser requisito;
- core/extensões migrados para TypeScript e ESM;
- internals e seletores modernizados;
- busca agrupada por subconjunto de colunas;
- exports ESM nomeados;
- configuração AJAX também no carregamento de idioma;
- mudanças de escopo em callbacks e remoção de internals legados.

O principal ganho para o Conn2Flow não é uma grande quantidade de widgets: é retirar jQuery, melhorar modularidade e usar uma base mantida sem abandonar o modelo server-side já conhecido.

## Trabalho de migração

1. caracterizar as listagens atuais e congelar fixtures de request/response;
2. definir `C2FDataGridRequest`, `C2FDataGridResponse`, schemas e limites;
3. implementar consulta segura no banco v2, sem SQL derivado do payload;
4. criar adapter de compatibilidade para o protocolo DataTables 1.10 durante a transição;
5. criar frontend `C2FDataGrid` Tailwind e adapter DataTables 3;
6. substituir modal responsive, popup, loading, busca e erros Fomantic;
7. validar no piloto `admin-paginas-v2`;
8. migrar as ondas do core e overlays privados;
9. remover DataTables 1.10.23, Responsive antigo, tema Semantic e adapter legado;
10. manter testes de contrato que permitam trocar DataTables no futuro.

## Prova de conceito revisada

O PoC deixa de escolher entre bibliotecas e passa a validar a decisão:

- uma listagem CRUD simples;
- uma listagem com ações/permissões;
- uma listagem com volume e filtros;
- desktop, mobile, teclado e leitor de tela;
- sessão expirada, 401/403, CSRF e falha de rede;
- requests concorrentes/cancelados;
- 100, 1.000 e grande volume server-side;
- bundle, tempo de interação, queries e memória;
- prova de que o módulo não conhece DataTables.

Tabulator, TanStack e motor próprio ficam documentados como alternativas de contingência caso o PoC revele bloqueio técnico.

## Critérios de aceite para futura implementação

- módulos consomem somente `C2FDataGrid`;
- frontend não depende de jQuery, Semantic ou Fomantic;
- backend não aceita nomes SQL vindos do navegador;
- busca, filtro e ordenação usam allowlists e prepared statements;
- visual é 100% controlado pelo design system Tailwind;
- assets são locais, pinados e reproduzíveis;
- adapter DataTables pode ser removido sem alterar os módulos;
- paridade, acessibilidade e performance do piloto são aprovadas.

## Referências

- DataTables 3: <https://datatables.net/blog/2026/datatables-3>
- Novidades da linha 2: <https://datatables.net/new/2>
- Upgrade 1.10 para 2: <https://datatables.net/upgrade/2>
- Upgrade para 3: <https://datatables.net/releases/3/upgrade>
- Tailwind technical preview: <https://datatables.net/releases/3/examples/styling/tailwind.html>

## Próxima ação

Promover um batch de contrato/PoC da `C2FDataGrid` depois que banco v2, API da interface e primitives Tailwind mínimas estiverem disponíveis.
