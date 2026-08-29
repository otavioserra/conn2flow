# Memória de Engenharia — Execução

> **Propósito**: manter contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: preservar 3 a 5 tarefas, mirar 3–4,5 KB e podar antes de 5 KB / 50 linhas.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums e tokens de assets são recalculados por `resources:sync`.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco principal.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors e mounts Docker.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.

## Tarefas recentes

### 2026-08-28 — BATCH-144 (req-141): autoria x derivado no CSS

- Runtime serve TUDO do banco (`gestor.php:2782`); disco só com `DEVELOPMENT_ENV=true`. O compilador
  offline varre `resources/` — por isso o CSS entregue vinha de um HTML que não é o entregue.
- `html`/`css` são AUTORIA; `css_precompiled`/`css_compiled` são DERIVADOS e sempre recalculáveis.
  Preservar derivado como autoria (o que `preserve_on_user_modified` fazia) cria híbrido incoerente.
- Publicação nasce no banco e nunca teve arquivo: o CLI jamais a alcança. Ela herda o CSS do template.
- Medir cobertura = classes do HTML sem regra em nenhuma folha. Filtre `framework_css=tailwindcss`
  (Fomantic vem por CDN) e ignore `group`/`peer` (marcadores sem regra própria).
- `c2f css:audit` e `c2f css:rebuild --url` (compila do HTML RENDERIZADO, sem depender de
  `tailwind_sources`). O Tailwind CLI precisa do input DENTRO da árvore com `node_modules`.
- Escape em heredoc Python: barra-b e barra-s viram BYTES DE CONTROLE na string. Use raw string
  ou chr(92), e confira o arquivo depois — assert que falha no meio deixa funcao chamada e nao definida.

### 2026-08-28 — BATCH-143 (req-140): URL sanitizada x nome físico

- `arquivo_nome_sanitizar()` troca espaço por hífen e colapsa `--`. Qualquer nome que o sistema
  GRAVA precisa satisfazer `sanitizar(n) === n`, senão a URL publicada aponta para outro arquivo.
- O controlador estático não carrega `bibliotecas/arquivo.php`: o bootstrap só inclui `banco`,
  `gestor`, `modelo` e `hooks`. Use `require_once $_GESTOR['bibliotecas-path'].'arquivo.php'`.
- A reescrita do `.htaccess` usa a flag `[B]`, então `caminho-total` chega JÁ decodificado; `%20`
  só sobrevive literal em servidor sem essa flag.
- Para casar URL com nome físico divergente, compare pelo RESULTADO da sanitização de cada entrada
  do diretório. Adivinhar a troca de hífen custa 2^n acessos a disco e ainda perde nome misto.
- POST autenticado exige CSRF mesmo com `ajax=sim` (req-107; a skill de AJAX está desatualizada):
  leia o `<meta name="csrf-token">` da página e mande em `X-CSRF-Token`. O `curl` do Git Bash não
  resolve `/tmp` em `-F @arquivo` — use caminho no formato Windows.

### 2026-08-26 — BATCH-142 (req-139): galleries denso, modal e corte vertical

- Grade compacta exige largura fracionária no card e `flex-wrap` (6×110 px e 10×65 px medidos);
  overlay sem layout shift precisa de `pointer-events:auto` no contêiner ao hover.
- `image_position` exige allowlist idêntica no CRUD, PHP e widget JS.
- Dry-run do atualizador avança checksums sem aplicar linhas: não usar `force-all` sobre tenant.

### 2026-08-29 — BATCH-146/147: cópias congeladas, alvo do CLI e assets locais

- **Widget de galeria NÃO renderiza do template**: `galleries.html` guarda uma CÓPIA tirada quando o
  operador escolheu o modelo, com `user_modified = 1` — corrigir o template do core não alcança as
  galerias existentes. Antes de "corrigir o recurso", conferir de onde o runtime realmente lê.
- **Corrigir a classe sem corrigir o CSS é meio caminho**: HTML gerado por widget só existe em
  runtime e nunca chega ao compilador Tailwind. Quem emite a classe precisa emitir a regra junto.
- **Delegar comando do CLI reaproveitando `$input` erra o alvo**: `project:update-all` recebe o
  projeto como ARGUMENTO e `css:rebuild` o lê como OPÇÃO — a etapa regenerava a base do SISTEMA
  reportando "analisados: 235 | regenerados: 0". Números plausíveis, base errada: sempre conferir a
  linha "alvo da gravação".
- **PHP CLI no Windows não tem CA bundle**: todo HTTPS falha com `unable to get local issuer
  certificate`. A saída é cair para o binário `curl` do sistema, nunca `CURLOPT_SSL_VERIFYPEER =>
  false`.
- **`vendor/` no `.gitignore` engole `gestor/assets/vendor/`**: sem exceção explícita, a migração de
  CDN vale só na máquina local e produção cai no fallback em silêncio.
- **Regex "por arquivo" apaga blocos repetidos**: ao trocar um bloco de tags por uma chamada, tratar
  cada bloco CONTÍGUO — três arquivos tinham dois blocos e o segundo sumiu sem substituto. Conferir
  paridade blocos↔chamadas depois.
- **Tela do scaffold CRUD copiada carrega ações que não pertencem a ela**: a tela `variables` oferecia
  `excluir` sobre a tabela `modulos`. Ao herdar `interface`, checar QUAL tabela os botões alcançam.

### 2026-08-29 — BATCH-148: assets locais completos, fontes e minificação

- **Migrar um CSS de terceiro sem os arquivos que ele pede quebra em silêncio**: `semantic.min.css`
  referencia as fontes por caminho RELATIVO. Do CDN resolvia; do disco passou a resolver contra
  `vendor/<lib>/<versao>/` e os ÍCONES DO GESTOR SUMIRAM. Ao vendorizar, extrair os `url()` relativos
  do próprio CSS e baixar também.
- **PHP CLI do Windows não tem CA bundle**: HTTPS falha com `unable to get local issuer certificate`.
  Cair para o binário `curl` do sistema; NUNCA `CURLOPT_SSL_VERIFYPEER => false`.
- **Google Fonts decide o formato pelo User-Agent**: sem um moderno, devolve `ttf` em vez de `woff2`.
- **`ProjectEnvironmentResolver::resolve()` devolve o ESPELHO** (`path_tests`). Comando que ESCREVE
  no projeto precisa de `config['path']` — gravar no espelho some no próximo sync, sem erro nenhum.
- **Minificar na entrega quebra quatro garantias de HTTP**: `Content-Length`, `Range`, `ETag` e
  `304` dependem de o corpo ser o arquivo em disco. Separar MINIFICAR (build) de ESCOLHER
  (resolução) entrega o mesmo ganho sem custo — medido, o `Content-Length` bate byte a byte.
- **`terser` e `esbuild` já estão em devDependencies**: não instalar minificador novo.
- **Documento de intake que se contradiz é pior que um errado**: ao corrigir uma afirmação medida
  (os 925 KB do DataTables nunca foram entregues), varrer o arquivo inteiro atrás dos trechos que
  ainda repetiam a versão antiga.

## Pendências e histórico

- Detalhes integrais vivem nos BATCHes e em `sdd/validation/`; histórico antigo está em `archive/`.
