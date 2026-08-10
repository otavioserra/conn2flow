# Current Human Request

- **Intake ativo (Agente Atual)**: Nenhum.
- **Outros Intakes Pendentes (Outros Agentes)**: [req-108.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-108.md) — Deploy falha com HTTP 429 falso porque `api.php` carrega `banco-v2.php` (sintaxe PHP 8.5) num ambiente PHP 8.3; o `ParseError` é capturado pelo `catch (Throwable)` e vira "Rate limit excedido". Diagnóstico fechado e **rumo já decidido pelo Chefe (2026-08-10): fallback para `banco.php` quando `PHP_VERSION_ID < 80500`, mantendo as bibliotecas v2 intactas em 8.5** — elas NÃO devem ser retro-portadas. Pronto para implementação (BATCH-108); nenhum código foi alterado nesta rodada.

- **Lotes Fechados**: 
  * [req-106.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-106.md) (BATCH-106 `complete`, 2026-08-06): Painel Flutuante de Opções de Exibição, Sidebar Lateral de CSS e Barra Superior de Navegação no Editor Visual.
  * [req-107.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-107.md) (BATCH-107 `complete`, 2026-08-06): Hardening de Segurança, Mitigação de Vulnerabilidades e Saneamento do Core.
  * [req-075.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-075.md) (BATCH-075 `complete`, 2026-07-10): Site Toolbar completa, agendamento de páginas e extensões do editor.
  * [req-076.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-076.md) (BATCH-076 `complete`, 2026-07-09): Exclusão de `contents/` em deploy/sincronização.
  * [req-077.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-077.md) (BATCH-077 `complete`, 2026-07-10): Desacoplamento de JS da toolbar e mapeamento inteligente contra o DOM vivo.
  * [req-078.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-078.md) (BATCH-078 `complete`, 2026-07-10): Correções no Live Editor — trava de widgets, submenu "+" e blindagem CSS.
  * [req-079.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-079.md) (BATCH-079 `complete`, 2026-07-10): Mapeamento no Pai de Widgets de Múltiplos Elementos, Image Picker e Agrupamento/Filtro de Módulos.
  * [req-080.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-080.md) (BATCH-080 `complete`, 2026-07-10): Integração de Modelos de Sessão e Assistente IA na Editbar (barra flutuante) do Live Editor.
  * [req-081.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-081.md) (BATCH-081 `complete`, 2026-07-11): CodeMirror no Assistente IA, correção do save intermitente (blindagem do corpo AJAX), dropdowns de Usuário/Página, CRUD de prompts, Código Customizado e painel "+" em duas colunas.
  * [req-082.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-082.md) (BATCH-082 `complete`, 2026-07-13): Correção de Carregamento de Widgets, Seleção de Modelos, Restauração de Backups e Hooks de Controle Multi-usuário.
  * [req-083.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-083.md) (BATCH-083 `complete`, 2026-07-13): Correções de Homologação do Live Editor (Hover, Responsive Preview e Normalização).
  * [req-084.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-084.md) (BATCH-084 `complete`, 2026-07-13): Preservação de Datas Customizadas (data_criacao/data_modificacao) na Compilação de Recursos.
  * [req-086.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-086.md) (BATCH-086 `complete`, 2026-07-14): Preservação de data_modificacao no Sincronizador de Banco de Dados.
  * [req-087.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-087.md) (BATCH-087 `complete`, 2026-07-15): Parametrização e Resumo de Órfãos no Sincronizador de Banco de Dados.
  * [req-088.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-088.md) (BATCH-088 `complete`, 2026-07-15): Criação dos Módulos/Widgets "forms-search" (Formulários de Busca) e "pages-index" (Páginas Índice).
  * [req-089.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-089.md) (BATCH-089 `complete`, 2026-07-15): Ocultação do Dropdown de Página por Permissão no Live Editor e Ajuste de Alerta no Hook de Admin-paginas.
  * [req-090.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-090.md) (BATCH-090 `complete`, 2026-07-17): Transição do Gerenciador de Arquivos para Árvore Física no Disco e CRUD Completo de Diretórios no Admin-Arquivos.
  * [req-091.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-091.md) (BATCH-091 `complete`, 2026-07-20): Refinamentos de CRUD, Novos Modelos de Lupa e Autocomplete AJAX Otimizado no Módulo "forms-search".
  * [req-092.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-092.md) (BATCH-092 `complete`, 2026-07-20): Destaque, Sincronização de URL, Debounce, Cache e Teclado no Módulo "pages-index".
  * [req-093.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-093.md) (BATCH-093 `complete`, 2026-07-20): Renderização de Variáveis/Widgets no Editor HTML Clássico e Preview (igual à Editbar).
  * [req-094.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-094.md) (BATCH-094 `complete`, 2026-07-21): Tradução Completa dos Templates de Páginas do Módulo "publisher-index" para o Inglês.
  * [req-095.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-095.md) (BATCH-095 `complete`, 2026-07-21): Tradução Completa da Editbar, Painéis e Overlays do Editor Visual para o Inglês.
  * [req-096.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-096.md) (BATCH-096 `complete`, 2026-07-29): Mapeamento Visual de Embeds, Proteção de Eventos, Suporte Híbrido a PDF (PDF.js, Google Viewer, Object Fallback) e Modal Estruturado.
  * [req-097.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-097.md) (BATCH-097 `complete`, 2026-07-30): Opções Separadas de Edição Avançada na Toolbar, Inserção de Embeds no Painel "+" e Correções do Leitor/Modal de Embed.
  * [req-098.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/human-requests/req-098.md) (BATCH-104 `complete`, 2026-07-31): Checkout Transparente e Tokenização na Biblioteca PayPal (PHP e JS).
  * (BATCH-098 `complete`, 2026-07-30): Área de Transferência Persistente do Editor Visual (copiar numa página, colar em outra).
  * (BATCH-099 `complete`, 2026-07-30): Upload e Gestão de Pastas Liberados no Modo Picker do Admin-Arquivos.
  * (BATCH-100 `complete`, 2026-07-30): Mídia Embutida: 403 em Arquivos com Espaço, Streaming (HTTP Range) e Dimensionamento do Áudio.

- **Status**: BATCH-106 concluído e validado (Vitest 134/134 OK, PHPUnit 181/181 OK).

- **Pendências**: Nenhuma.
