# BL-026 — Remoção de textos hardcoded e migração para recursos

- **Tipo:** Epic/I18n/Migration/Quality
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** textos voltados ao usuário em PHP, JavaScript, HTML, templates, e-mails, APIs, instalador e atualizador
- **Dependência:** BL-025; deve acompanhar BL-013 a BL-019 e BL-022

## Objetivo

Transferir todo texto destinado ao usuário para o recurso global ou para o recurso do módulo proprietário, mantendo no código apenas chaves técnicas inglesas, parâmetros e códigos de erro.

## O que deve e não deve ser traduzido

### Deve usar recurso

- labels, títulos, placeholders, ajuda e validação;
- alertas, confirmações, toast, modal e progresso;
- erros exibidos por AJAX/API;
- e-mails e notificações;
- mensagens do instalador/atualizador;
- estados vazios, acessibilidade (`aria-label`) e textos de componentes;
- mensagens de upload/listagem/paginação.

### Permanece técnico em inglês

- logs internos e mensagens de diagnóstico não expostas;
- nomes de exceção, códigos de erro, métricas e eventos;
- comentários/docblocks (tratados pelo BL-023);
- conteúdo fornecido pelo usuário ou editorial, que não é tradução de interface.

## Diagnóstico inicial

A busca heurística apontou cerca de 266 linhas candidatas no core e mostrou que automação sozinha não distingue UI, log e conteúdo. Há também casos claros no instalador:

- `installer.js` usa traduções, mas mantém várias frases portuguesas como fallback;
- respostas inválidas e erros do servidor são literais;
- `views/debug.php` possui títulos/textos em português;
- `Installer.php` mistura `__()` com exceções e uma página de sucesso HTML hardcoded;
- os catálogos do instalador têm 75 chaves em `pt-br` e 75 em `en`, com boa paridade estrutural, mas não cobrem todos os textos.

Nos manifestos do core foram observadas diferenças de IDs entre os idiomas em `contatos`, `publisher-highlights` e `publisher-pages`. Essas lacunas devem ser tratadas como dívida conhecida, não mascaradas por fallback silencioso.

## Inventário automatizado

Criar ferramenta que produza, por arquivo/módulo:

- literais em `alert`, `confirm`, modal, toast e validações;
- campos `message`, `title`, `label`, `placeholder` e equivalentes;
- HTML/texto gerado dentro de PHP/JS;
- texto visível e atributos de acessibilidade em templates;
- respostas HTTP/AJAX sem `code`/`message_key`;
- fallback literal após chamada de tradução;
- chave de recurso sem uso e uso sem definição.

O relatório é revisado, classificado e mantido como baseline. CI bloqueia apenas novas ocorrências e reduções regressivas; não deve falhar por todos os falsos positivos legados no primeiro dia.

## Regra de ownership

- bibliotecas e shell compartilhado → recurso global;
- regra/tela específica → recurso do módulo;
- overlay privado → namespace do projeto/módulo, salvo chave global oficialmente extensível;
- nunca copiar a mesma frase para dezenas de módulos apenas porque o texto coincide;
- nunca colocar regra de negócio, HTML ativo ou segredo dentro da tradução.

## Plano por ondas

### Onda 0 — Baseline e correções estruturais

- gerar relatório e classificar UI/log/editorial;
- corrigir paridade `pt-br`/`en` já conhecida;
- definir idioma de referência e política para traduções incompletas;
- adicionar validações do BL-025.

### Onda 1 — Fronteiras globais

- autenticação, sessão expirada, autorização e CSRF;
- erros HTTP/AJAX e página de login;
- shell administrativo, menus, dialog, toast, loading e formulários;
- substituir frases por `code` + `message_key` + parâmetros.

### Onda 2 — Instalador e atualizador

- eliminar fallbacks literais no JavaScript;
- migrar debug/sucesso e mensagens de progresso;
- separar log técnico de mensagem ao administrador;
- empacotar e testar catálogos antes de banco/core existirem;
- validar mudança de idioma durante o fluxo.

### Onda 3 — Pilotos v3

- `admin-paginas-v2`;
- `C2FDataGrid`/DataTables 3;
- `admin-arquivos`/`C2FUpload`/Uppy;
- componentes Tailwind necessários ao piloto.

### Onda 4 — Módulos e overlays

- migrar junto das ondas de banco/interface, não em mutirão desconectado;
- revisar e-mails, templates, publisher e conteúdo administrativo;
- validar cada composição privada nos idiomas declarados.

### Onda 5 — Encerramento

- contador zero de literais UI não permitidos;
- remover aliases de chaves e adapters antigos;
- relatório de cobertura por locale/módulo;
- revisão humana de `pt-br` e `en`, incluindo acessibilidade e placeholders.

## Testes

- troca de locale e fallback controlado;
- placeholders ausentes/extras, plural zero/um/muitos e caracteres especiais;
- escaping em HTML/atributo/JavaScript e bloqueio de rich text não declarado;
- erros AJAX em cada idioma, inclusive sessão expirada;
- catálogos incompletos e cache invalidado após atualização;
- instalação limpa e upgrade nos idiomas suportados;
- screenshot/Playwright de telas críticas para detectar chave crua ou texto misturado.

## Complexidade

- ferramenta/baseline: média;
- fronteiras globais e instalador: média/alta;
- migração completa: alta pelo volume, mas paralelizável por módulo depois do contrato estável;
- impacto por módulo tende a 10–20% adicional quando feito junto da migração v3; um mutirão separado duplicaria testes e conflitos.

## Critérios de aceite

- nenhum texto de UI novo é hardcoded;
- todos os textos migrados têm owner global ou modular claro;
- idiomas obrigatórios possuem as mesmas chaves e placeholders;
- API/AJAX não depende da frase traduzida para controle de fluxo;
- logs técnicos não são expostos como mensagem ao usuário;
- fallback ausente gera diagnóstico observável, sem esconder lacuna indefinidamente;
- core + overlays passam no validador e nos testes dos idiomas declarados.

## Próxima ação

Promover primeiro o inventário/baseline e a migração das mensagens de sessão/autorização, pois são transversais e necessárias ao contrato AJAX da interface v2.
