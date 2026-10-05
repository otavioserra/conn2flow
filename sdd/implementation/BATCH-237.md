# BATCH-237 — Topbar administrativa (req-228)

- Status: `in-review`
- Projeto: `conn2flow`
- Raiz de autoria: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`
- Worktree: `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow-req228`
- Branch: `feat/req-228`
- Intake: [req-228](../human-requests/archive/req-228.md)
- Autonomia: `autonomo_monitorado`

## Escopo e tarefas

- [x] Ler CURRENT, intake, governança e criar worktree isolada.
- [x] Mapear identidade, permissões, idioma, CSRF e âncora de segurança do perfil.
- [x] Dropdown acessível com identidade escapada, perfil, segurança, idioma e logout.
- [x] Gerenciamento e persistência SQL de atalhos por conta, com validação de permissão.
- [x] Responsividade e coexistência com editbar.
- [x] Compilação oficial, testes e evidências.

Restrição: não editar formulários nem listagens de módulos. O roteador intercepta somente as ações `admin-topbar-*`, depois da autenticação e CSRF e antes do controlador do módulo.

## Implementação

O layout referencia o componente bilíngue `admin-topbar-tailwind`; a dependência Tailwind está declarada nos dois manifests. A biblioteca registrada no `config.php` compõe o cabeçalho antes da inclusão de CSS/JS. A identidade e o estado JSON são escapados no atributo HTML, sem interpolação de dados do usuário em scripts. Textos novos são variáveis globais PT-BR/EN.

Favoritos usam a tabela `usuarios_topbar_favoritos`, com chave única `(id_usuarios, pagina_id)`, independente da sessão e do idioma. Cada adicionar/remover é uma escrita SQL independente; não regrava uma lista inteira. O servidor deriva o proprietário da sessão, valida a página ativa e o acesso ao módulo/operação, e não recebe URLs do cliente. O catálogo inclui raízes e ações de inclusão; exclui exclusão, mudança de status, identificadores dinâmicos e destinos externos. Rotas e rótulos são resolvidos no idioma atual. Preferências fora do catálogo atual permanecem no banco, sem exposição.

O cabeçalho mostra seis ícones a partir de 1280 px, quatro de 768 a 1279 px e usa o menu de atalhos abaixo de 768 px. A janela nativa de personalização tem busca, checkboxes e salvamento imediato. Falha de gravação mantém o estado anterior e mostra mensagem visível; Escape e clique fora fecham os dropdowns. A janela devolve foco ao botão de origem. Não há dependência do Fomantic no novo componente/script.

## Evidências — 2026-10-04

- `php sdd/validation/req228-topbar-test.php`: **28/28**, com doubles do runtime/banco; acesso negado, isolamento entre contas, idempotência, rejeição de GET/arrays/anônimo, falha SQL, identidade escapada e estado JSON seguro.
- PHPUnit focado: **112 testes / 530 assertions**, sem falhas; o runner informa três depreciações. Inclui layout, personalização de menu, CSRF, recursos e erros terminais do roteador. Os testes de marcação do layout foram adaptados para compor o novo recurso, preservando as assertions de Lucide e `lg:hidden`.
- Vitest: **83/83** (`admin-tailwind`, `global-csrf`, `interface-tailwind`).
- [Roteiro de navegador](../validation/req228-browser.cjs), [resultado JSON](../validation/req228-evidence/results.json): **60/60** verificações; desktop/mobile, teclado, clique fora, falha 503 simulada, persistência após reload e em segundo contexto, clique no favorito, PT-BR/EN, segurança, logout, CSRF recusado sem token e editbar real. Sem erros inesperados de página/console; os erros HTTP 403/503 provocados pelo roteiro são registrados separadamente.
- Viewports: 1366, 1024, 768, 390 e 320 px; sem overflow horizontal, dropdowns contidos na tela. Editbar exercitada em 1366/390 px com cookie de perfil no mesmo contexto autenticado, sem injeção transitória de DOM.
- Capturas versionadas: [desktop com favoritos](../validation/req228-evidence/favorites-1366.png), [perfil mobile](../validation/req228-evidence/profile-390.png), [editbar desktop](../validation/req228-evidence/editbar-1366.png), [editbar mobile](../validation/req228-evidence/editbar-390.png).
- PHP lint e `node --check`: aprovados; `git diff --check`: aprovado.

## Pipeline e limites

Compilação oficial por `resources:sync` e `project:update-all project-test --no-wait`, sequenciais, em worktree isolada. O destino `project-test` declara `local=true` e `deploy_mode=ssh`. O pipeline aplicou a migração, atualizou SQL, reconstruiu CSS e confirmou 462 arquivos de código por hash. Nenhum deploy em projeto de produção.

A publicação direta em `dist/` registrou erro de rsync no Windows (`The source and destination cannot both be remote`). A entrega pelo controlador de arquivos do Gestor foi exercitada com sucesso no navegador. Esse transporte não foi alterado neste lote. O pipeline devolveu zero e reportou essa etapa como warning; não se afirma publicação direta concluída.

O compilador produziu derivados de outras famílias do core. Seus efeitos locais foram revertidos apenas na worktree deste lote, mantendo os recursos de topbar, CSS compilado próprio, minificação do novo JS e as entradas correspondentes nos manifests. Os `*Data.json` são gerados novamente pela esteira de entrega; não foram editados manualmente nem incluídos com alterações de módulos alheios.

O roteiro registra a preferência inicial da conta de teste e restaura as entradas tocadas, encerrando a sessão ao testar logout. A preferência inicial e final do último ensaio é vazia. Modo do ambiente não foi alterado. A memória de execução foi preservada; não houve poda.

Revisão local conforme `review-current-batch`: sem achado bloqueante remanescente no escopo. Alterações isoladas na branch `feat/req-228`, sem integração em `main`. Próximo passo: revisão técnica/humana do diff. O conteúdo de formulários e listagens das req-220/222 não foi editado.

PHP lint; testes focados de autorização, isolamento por usuário, caminho seguro e idempotência; navegador desktop/mobile com teclado, click outside, persistência entre contextos, idioma, perfil e editbar; compilação e sincronização sequenciais no ambiente declarado local.
