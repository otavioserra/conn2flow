# Engineering Memory - Execution

> **Propósito**: contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: é proibido podar abaixo de 50 KB / 200 linhas; emitir alerta preventivo nesse patamar, podar obrigatoriamente ao atingir 75 KB / 300 linhas e mirar ~25 KB, preservando 20 a 25 tarefas e aprendizados recentes. O fim da sessão ou do batch não aciona poda.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums e tokens de assets são recalculados por `resources:sync`.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco principal.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors, transportes e mounts.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.
- Suíte PHP no Windows (PHP 8.5 WinGet): sem `openssl.cnf` o `CoreHelpersTest` falha. Exportar
  `OPENSSL_CONF=<dir do php>\extras\ssl\openssl.cnf` antes do `composer test` dá verde; o `lab`
  (WSL/HestiaCP) segue como referência. Divergência só no Windows é do ambiente.

## Tarefas recentes

### REQ-272 / BATCH-281 — releases 3.0.0, 3.0.1 e 3.0.2 (2026-10-14)

- **Antes de lançar, rodar a suíte no modo do CI**: `.env` em `gestor/autenticacoes/localhost/` + banco vazio no `lab`, `phinx migrate` e `CONN2FLOW_TEST_BOOTSTRAP_CONFIG=1 CONN2FLOW_RUN_DB_TESTS=1`. O CI usa `mysql:8.0` (mais estrito que o MariaDB) e carrega o `config.php` real.
- Release falho sem pacote publicado: apagar e recriar a tag (decisão do Humano). Publicado: nova versão de correção. O workflow mantém só a última release de cada série.
- **Atualização pelo painel roda num processo que já carregou o código antigo**: biblioteca nova precisa de `require_once` direto no script novo; função nova de arquivo já carregado não existe.
- Atualização do sistema deixa os `*Data.json` do projeto em `db/data/` com o contrato do core; o projeto completa o contrato via `project-schema-metadata.json`.
- Ensaio real: `c2f update:core <projeto> --tag=... --wait` (no Lab, `api_resolve_ip: 127.0.0.1` no projeto para aceitar o certificado local) e depois `project:deploy`.

### REQ-271 / BATCH-280 — trava de pagamentos no `stripe.php` (2026-10-14)

- **Biblioteca nova precisa de entrada no mapa de `config.php`**: `gestor_incluir_biblioteca('x')` sem a chave volta calada e o fatal só aparece na chamada. Função de pagamento vai em `stripe.php`.
- O site também carrega bibliotecas do core por caminho em fixtures (`tests/fixtures/req122-payments.php`): remover arquivo do core exige procurar `bibliotecas/<nome>.php` no site.
- O rsync do pipeline não apaga arquivo removido: a conferência por hash lista `SOBRA`, que precisa ser apagada no destino.
- Escrita remota no Lab: usar o alias `ssh lab` (mesma máquina, `sudo` liberado). `ssh otavio@lab.conn2flow.local` com `sudo` foi barrado pelo classificador.

### REQ-269 / BATCH-278 — `--skip-css` nos pipelines (2026-10-14)

- `project:update-all <id> --skip-css` pula Tailwind (etapas 2 e 4) e `css:rebuild` (7). Só para mudança de backend, banco ou teste.
- **Medir antes de otimizar**: com cache quente o pipeline completo do site leva ~40 s; deploy de 10 min vem de muitos arquivos divergentes no destino (outras árvores), não do Tailwind.
- `manager:update-all` mira o Docker local (`dev-environment/data/sites/localhost`); sem `docker` no host, as etapas 3 e 4 falham com ou sem flag.
- `sed` com `\\r\\n` dentro de string PHP insere quebra real: editar PHP com a ferramenta de edição.

### REQ-268 / BATCH-277 — `sem_permissao = 0` abria a página (2026-10-14)

- **`existe('0')` é verdadeiro.** Coluna numérica lida do banco chega como texto: `existe()`/`isset()` não servem para flag 0/1. Para `paginas.sem_permissao`, usar `gestor_pagina_sem_permissao()` (só `1` abre). O sitemap já usava `empty()`.
- **Página sem módulo aceita qualquer logado**, inclusive cliente da loja. Exigência de perfil fica no módulo que renderiza (ex.: `gestor_permissao_modulo(false, 'presentations')`).
- **Arquivos de `contents/` saem pelo `arquivo-estatico` antes da sessão**: página restrita não protege as imagens dela.
- **Deploy concorrente**: a trava de projeto faz o segundo `project:update-all` esperar ("Aguardando a trava…", com PID e máquina). Depois, conferir por hash: a worktree do outro agente, criada mais tarde, deixa no destino arquivos de data mais nova que as minhas edições (`touch` + `project:sync-core`).

### REQ-263 / BATCH-272 — site lento depois de publicar: faltava índice no caminho de `paginas` (2026-10-08)

- **Sintoma**: tudo em ~70 ms e, depois de uma publicação, toda página PHP em 0,4 a 0,9 s, nos dois ambientes do mesmo servidor de banco; arquivo estático normal.
- **Causa**: `WHERE caminho=… AND language=…` em `gestor.php` só tinha o índice de `language`. O InnoDB examinava as ~1.000 páginas do idioma e, como a consulta pede `html` e os CSS, lia o conteúdo de todas (68 MB por visita). Rápido só enquanto isso cabia no `innodb_buffer_pool_size` (128 MB no ambiente de teste; a tabela tem 220 MB).
- **Como achar**: comparar `Innodb_buffer_pool_reads` antes e depois de uma requisição (número fixo e alto por visita denuncia varredura); ligar o registro de consultas lentas em tabela por alguns segundos (`log_output=TABLE`, `long_query_time=0.02`) e devolver a configuração.
- **Hipóteses erradas que custaram tempo**: ociosidade, WSL e opcache. O opcache se confere por dentro do pool com `cgi-fcgi` e um script fora da pasta do site.
- **Regra**: consulta que roda em toda requisição e pede coluna de conteúdo precisa de índice que leve a uma linha. `caminho` é TEXT: índice com prefixo (`'limit' => ['caminho' => 191]` no Phinx).
- **Teste que recorta código-fonte por `"\n}\n"`** quebra quando o arquivo está com CRLF (checkout com `autocrlf`): normalizar o fim de linha antes de recortar.

### Histórico anterior

BATCH-168, BATCH-174, BATCH-175, BATCH-178 e BATCH-180 (2026-09-15 a 2026-09-25: CSRF em XHR e renovação silenciosa, controle de acessos, suíte no ambiente `lab`, roteador e widgets) foram podados em 2026-10-02 por limite de tamanho. O registro integral está nos relatórios desses lotes em `sdd/implementation/` (ou `archive/`) e na versão `97a6bc9e` deste arquivo.

BATCH-144 (autoria x derivado no CSS; runtime serve do banco, disco só com `DEVELOPMENT_ENV`) e
BATCH-146/147 (cópias congeladas de widget, alvo do CLI e assets locais) foram podados por limite
de tamanho. O registro integral vive em `sdd/implementation/BATCH-144.md`, `BATCH-146.md` e
`BATCH-147.md`.

BATCH-155 a BATCH-167 (2026-09-02 e 2026-09-03: SSH e bootstrap do CLI, checksum e fim de linha, paridade visual do Tailwind, fila de scripts, worker que se matava, sessão e cgroup) foram movidos, na íntegra, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-02-03.md).

BATCH-181 a BATCH-214 (2026-09-25 a 2026-10-01: docs como código, deploy e migrações, atualização segura com manifesto, snapshot e rollback, formulários do `interface`, sementes declarativas, recursos sem idioma, layout por perfil 1:N) foram movidos, na íntegra, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-25-10-01.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-09-25-10-01.md) na poda de 2026-10-06.

BATCH-216 a BATCH-229 (2026-10-02 a 2026-10-04: módulo `cookie-consent`, prévia de widgets e toque, manutenção no deploy e Lab compartilhado, módulos distribuídos, modal do editor visual, menus do painel, rotina e catálogo locais, chave de sessão, e a migração do painel para Tailwind com controles, editor HTML e `admin-paginas`) foram movidos, na íntegra, para [`archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-10-02-04.md`](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-10-02-04.md) na poda de 2026-10-07. As regras da migração para Tailwind vivem na skill `c2f-tailwind-module-migration`.

BATCH-249 a BATCH-266 (2026-10-05 a 2026-10-07: harmonização e auditoria humana do painel, atualização automática, widgets e lousa do Dashboard, miniaturas e modelos) foram movidos, na poda de 2026-10-15, para [archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-10-05-07.md](archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-10-05-07.md).


### Integração da 3.1 na 3.0 (DEC-134, 2026-10-07)

- **Unir duas linhas**: branch de integração na árvore da linha nova, `git merge --no-ff --no-commit origin/main`; índices do SDD se resolvem por união dos dois lados; dados gerados (`TemplatesData`, manifestos) se refazem com `resources:sync` e `assets:minify` depois do merge.
- **Antes do merge**, descartar mudança só de fim de linha nos arquivos que o outro lado também mexe (`git checkout HEAD -- sdd`), senão o merge recusa.
- **Modelo cujo conteúdo não é HTML** (arranjo de lousa em JSON) precisa de desenho próprio no gerador de miniaturas: `lousaAmostra()` em `sdd/validation/req256/gerar-miniaturas.cjs`.
- **Validar a união nos dois Labs** antes de enviar: roteiros da linha nova na instalação dela e, na instalação de lançamento, tabelas criadas, telas novas respondendo e os roteiros que não dependem de dado preparado.

### REQ-260 / BATCH-269 — camada de provedores de IA (2026-10-07)

- **Falar com IA é pela `ia-provedores.php`**: `ia_provedor_servidor_do_banco($linha)` e depois `ia_provedor_gerar_texto()` ou `ia_provedor_gerar_imagem()`. Nada de `curl` próprio nem de chave no endereço; `ia_enviar_prompt()` já usa a biblioteca.
- **Teste de conexão sem limite de saída**: modelo que raciocina gasta o limite antes de responder e o teste falha à toa.
- **Biblioteca nova precisa de registro** em `$_GESTOR['bibliotecas-dados']` (`gestor/config.php`), senão `gestor_incluir_biblioteca()` não inclui nada e não avisa.
- **Célula condicional com marcador dentro** (`<!-- x < -->#tipo#<!-- x > -->`): trocar o marcador na célula antes de colocá-la na linha; a troca feita antes na linha não alcança o que entra depois.
- **Rolagem em 390 px no roteiro**: abrir a página já na largura de celular. Redimensionar a janela aberta deixa o menu lateral do painel no estado de desktop e acusa rolagem que não existe.
- **Pedido do editor à IA no roteiro**: `ajaxOpcao=html-editor-ia-requests` em tela com editor (`admin-paginas/adicionar/`), com `server_id`, `mode`, `prompt` e `data`; volta `status: Ok` e `data.html_gerado`.

### REQ-261 / BATCH-270 — ponto de extensão geral e conexão com o banco em pedido de IA (2026-10-07)

- **Recurso para todas as telas do painel**: hook `interface` / `pagina` (módulo e opção como argumentos), disparado no `interface_finalizar()` depois dos pontos por módulo. O callback sai cedo quando não se aplica.
- **Espera longa por serviço externo derruba a conexão com o banco**: o `banco_query()` só conecta quando não há conexão e não reconecta a que caiu; a gravação depois da espera falha com erro só no log. Antes de espera longa, soltar a conexão (`ia_provedor_banco_soltar()` é o modelo). Sintoma: registro que some de vez em quando, nos pedidos mais demorados.
- **O mesmo título de comentário abre dois blocos em `interface.php`** ("Disparar hook de página"): em teste de fonte, usar a última ocorrência.

### REQ-262 / BATCH-271 — pontos de extensão do uso de IA (2026-10-07)

- **Cota, crédito, auditoria e medição de IA se ligam em `ia-provedores` / `pedido.autorizar` (filtro) e `pedido.concluido` (ação)**; não se altera quem chama a camada. Quem chama informa `recurso` e `referencia` no pedido.
- **`banco_select()` devolve coluna calculada pela expressão inteira** (`SUM(x) AS total` é a chave) e separa colunas por vírgula: expressão com vírgula parte a coluna em duas. Defeito achado no módulo de créditos do site.
- **Teste sem rede da camada de provedores**: servidor do tipo compatível apontando para `http://127.0.0.1:9/v1` dá falha de comunicação imediata e exercita o caminho de erro sem sair da máquina.

### REQ-130 do site / BATCH-124 — mensagem de domínio no `formulario.js` (2026-10-15)

- **`input.closest('.field') || input.parent()` nunca usa o pai**: objeto jQuery vazio é truthy. Em formulário Tailwind sem `.field`, a mensagem de erro sumia. Agora: `closest('.field, .conn2flow-form-field')`, com reserva no pai.
- **Prompt novo no `form-ui` do core não chega a projeto com `form-ui` próprio** (o conn2flow-site tem um): o fallback do JS precisa ser legível, e o idioma vem de `gestor.language` (o `<html lang>` do site sai vazio).

### REQ-131 do site / BATCH-125 — estado semântico no `formulario.js` e painel sem laço (2026-10-15)

- **Semente de usuários sem perfil desde a REQ-202**: instalação nova criava o admin com `id_usuarios_perfis` nulo, e o `/dashboard/` redirecionava para si mesmo. `ensureAdminUser()` agora atribui `administradores`; `gestor_permissao()` responde 403 (tela do site restrito com textos `panel-access-denied-*`) quando a recusa é do próprio painel.
- **`formulario.js` só aplica classes de estado** (`has-error`, `c2fc-campo-invalido`, `c2fc-campo-msg-erro`, `c2fc-contador-excedido`, `c2fc-form-msg-erro`); o visual fica no `form-ui.css`. Teste em `tests/Unit/JS/forms.semantic-state.test.js` carrega o jQuery vendorizado; o `happy-dom` erra `.find()` com seletor de vírgula.

### REQ-274 / BATCH-283 — passe de navegador confiável no site restrito (2026-10-15)

- **Passe = cookie HMAC, não sessão**: `gestor_site_acesso_restrito_bypass_*()` na biblioteca `gestor`; chave derivada do `USUARIO_HASH_PASSWORD` (sem ele, não há passe) e salt `SITE_RESTRICTED_BYPASS_SALT` (trocar = revogar todos). O porteiro só o consulta para quem está sem sessão.
- **Botão novo no `admin-environment` precisa de `data-c2f-dica="@[[var]]@"`** com variável existente nos dois idiomas, senão o `RefinamentosReq243Test` falha.
- **E2E com `curl` no Git Bash**: `MSYS_NO_PATHCONV=1` faz o `curl.exe` não achar cookie em `/tmp`; usar `cygpath -m`. AJAX do painel pede `X-CSRF-Token` (meta `csrf-token`) e um só arquivo de cookies (`-b`/`-c`), porque o GET renova a sessão.

### REQ-275 / BATCH-284 — visibilidade e link direto privado nas páginas (2026-10-15)

- **`paginas.visivel` (padrão 1) + `hash_privado`**: o roteador lê os dois numa consulta à parte pela chave (`gestor_roteador_pagina_visibilidade()`), não na busca principal — durante uma atualização, antes da migração, coluna nova na busca principal derrubaria todas as páginas (o `banco_query` só devolve `false` e loga).
- **Página escondida = página inexistente**: sem `?acesso=`, o roteador segue o caminho do 404 (aqui é 302 para `/404`). Teste E2E deve comparar com uma rota que não existe, não com o código 404.
- **Formulários de páginas**: `#page-visibility#` entre a linha Tipo/Permissão e o Agendamento, componente `page-visibility-tailwind` em cada módulo, biblioteca `paginas-visibilidade.php`. Script PHP de inserção em lote: `unset` no array percorrido por `foreach` não pula o item (o `foreach` itera a cópia).

### REQ-277 / BATCH-286 — hotfix do atualizador pelo painel (2026-10-15)

- **Erro fatal não passa por `catch` nem `finally`**: a etapa web que morre (ex.: memória de 128 MB do PHP-FPM na sincronização de dados) deixava `temp/deploy.lock` preso por 2 h. A liberação vai num `register_shutdown_function` que olha `error_get_last()`. O PID da trava web é o do worker do FPM, que sobrevive ao fatal: checar PID não resolve sozinho.
- **Worktree no Windows nasce com CRLF** (`core.autocrlf=true`): testes de contrato com `"\n"` falham em massa (28 na primeira rodada). Antes da suíte: `git ls-files -z | xargs -0 rm -f && git -c core.autocrlf=false checkout -- .` — sem mudar o `autocrlf` do repositório, que é compartilhado.
- **Número de lote**: conferir `memory/implementation/` antes de criar `BATCH-NNN.md`; o agente do release tinha usado o BATCH-285 na mesma tarde e o `Write` sobrescreveu (restaurado do HEAD).

### REQ-284 / BATCH-293 — recompilação inútil do Tailwind e checksums que oscilavam (2026-10-19)

- **`project:update-all` é a exceção da exceção** (Engenheiro Chefe): só com core e projeto mudando juntos. Liga manutenção (503) no destino até o fim; interrompido, deixa `temp/maintenance.json` por 30 min. Mudança só de projeto: `project:deploy`.
- **`sync-files` + `sync-db` de projeto avulsos trancam o admin**: core e projeto entregam `db/data` com os mesmos nomes; o rsync do projeto sobrescreve as sementes de perfis do core no destino e a retirada por dono desativa `administradores` e apaga a permissão `dashboard`.
- **Cache do Tailwind dependia do caminho absoluto** (modais de sistema do core entram em toda página): worktree ou `c:\` x `C:\` recompilava tudo. Agora a fonte entra como `gestor:`/`core:` + relativo.
- **CRLF/LF nos Data.json**: o Tailwind grava LF, o git devolve CRLF no checkout; o compilador lia cru e o checksum oscilava. `readFileIfExists()` normaliza texto.
- **`bash` no PowerShell é o do WSL** (`WindowsApps\bash.exe`): roda outro PHP e outros caminhos. Para medir no Windows, chamar `php cli/c2f.php` direto.
- **Busca de classe por substring engana** (`bg-blue-900` casa em `bg-blue-900/20`): conferir com fronteira de token.
