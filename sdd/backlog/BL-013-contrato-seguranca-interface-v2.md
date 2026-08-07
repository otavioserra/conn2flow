# BL-013 — Contrato e hardening da interface v2

- **Tipo:** Architecture/Security
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** infraestrutura de interface administrativa v2
- **Relacionados:** BL-011, BL-012, BL-014, BL-015, BL-016, BL-038, BL-039

## Diagnóstico

A interface v2 já introduz objetos de configuração e uma fachada orientada a objetos, mas ainda não constitui uma plataforma v3 pronta:

- depende diretamente de jQuery, Fomantic UI e DataTables;
- contém renderização, acesso a dados, autorização, histórico, backup e transporte AJAX no mesmo arquivo;
- usa métodos legados/SQL textual do banco v2, incluindo `selectLegado`, `escape` e condições montadas como texto;
- aceita identificadores e valores vindos de requisições em fluxos dinâmicos;
- usa recursos sintáticos de PHP 8.5 e nem sequer pode ser interpretada pelo PHP 8.4 atual;
- não possui suíte dedicada de testes de contrato.

Portanto, adotar `InterfaceV2` sem revisão apenas transferiria riscos do legado para uma API nova.

## Contrato alvo

A interface administrativa deve orquestrar casos de uso; não deve construir SQL. Cada operação precisa receber:

- identidade do módulo e ação autorizada;
- esquema explícito de campos, tipos e regras;
- identificadores permitidos por allowlist;
- entrada normalizada separada da entrada bruta;
- repositório/command object baseado em prepared statements;
- renderer com escaping contextual por padrão;
- resposta HTTP/AJAX tipada e uniforme.

## Frentes de trabalho

### 1. Entrada e segurança

- centralizar leitura de request, sessão, CSRF e autenticação;
- separar autenticação expirada de erro de autorização e de validação;
- validar nome de campo, ordenação, filtros e ações contra metadados do módulo;
- tornar HTML confiável um tipo/escape hatch explícito e auditável;
- aplicar autorização no servidor a toda ação, inclusive botões escondidos na tela;
- adicionar limites de paginação, upload, busca e tamanho de payload.

### 2. Persistência

- substituir `escape`, `selectLegado`, `updateSQL` e `where` textual pelo contrato seguro definido no BL-011;
- declarar transações em alterações compostas de registro, histórico e backup;
- eliminar dependência de nomes de tabelas/campos recebidos diretamente do cliente;
- normalizar falhas de banco sem expor SQL ou credenciais.

### 3. API de módulo

- consolidar objetos de coluna, campo, botão, ação, paginação e validação;
- suportar extensões por composição e hooks tipados;
- preservar uma camada de compatibilidade procedural somente durante a transição;
- versionar o protocolo AJAX e documentar erros, redirects, dados e metadados.

### 4. Testes

- testes unitários dos value objects e validadores;
- testes de contrato de CRUD, histórico, backup, filtros, ordenação e paginação;
- testes de segurança para CSRF, IDOR, mass assignment, XSS e identificadores inválidos;
- testes de sessão expirada em rotas AJAX;
- golden tests de HTML somente para componentes estáveis, evitando snapshots gigantes.

## Critérios de aceite para futura implementação

- nenhum caso de uso novo depende de `interface.php` ou de SQL concatenado;
- entradas dinâmicas usam schema/allowlist e parâmetros vinculados;
- todas as respostas AJAX obedecem ao mesmo envelope e semântica HTTP;
- renderização escapa conteúdo por padrão;
- cobertura de contrato permite trocar o renderer ou o motor de tabelas sem alterar os módulos;
- integração com Tailwind ocorre por abstrações do design system, não por classes de um fornecedor espalhadas no PHP.

## Próxima decisão

Fechar o contrato público da interface v2 e os limites entre interface, banco, HTTP e renderer antes de reescrever o piloto `admin-paginas-v2`.

O contrato CRUD reutilizável será definido no BL-039. A interface fornece adapters, formulários e presenters; ela não deve voltar a concentrar persistência, policies, histórico e lifecycle CRUD numa única classe.
