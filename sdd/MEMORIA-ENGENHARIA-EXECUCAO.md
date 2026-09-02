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

### 2026-09-02 — BATCH-155 (req-153 / REQ-034): transporte SSH e host no bootstrap CLI

- **O ambiente mudou**: os tenants do Gestor saíram do `conn2flow-app` para uma VM Ubuntu + HestiaCP
  em `192.168.1.108`. Antes de escrever `docker exec conn2flow-app`, confira `docker ps` — o
  container não existe mais. SSH por chave para `otavio` funciona não interativo e tem sudo sem senha.
- **`config.php` impunha `SERVER_NAME = 'localhost'` no ramo CLI**, e o estrago não era o `.env`: o
  sufixo de cookie sai de `basename($domainBase)`, então sessão gerada por CLI para outro host nasce
  com nome que o site nunca lê. O cookie chega e é IGNORADO — só um 302 para /signin, sem erro.
  Mesma família da req-032 no cron. Ao mexer em bootstrap, veja o que cada ramo SOBRESCREVE.
- **`user@host:/caminho` do rsync atravessa o Git Bash intacto** — o `MSYS_NO_PATHCONV=1` do
  `docker exec` não é necessário aqui. Confirme com `--dry-run` antes de um sync grande.
- **rsync remoto precisa de `--rsync-path="sudo rsync"` E `chown` depois**: a conta SSH não é dona do
  docroot, e sem devolver a posse os arquivos novos ficam `root:root` e o PHP-FPM do tenant não lê.
- **`ssh` concatena os argumentos e o SERVIDOR os entrega ao shell dele.** Montar a linha local como
  array não protege nada: cite cada argumento remoto com `printf %q` / `escapeshellarg()`.
- **Checksum de recurso em `<modulo>.json` é do compilador, não do autor.**
  `atualizacao-dados-recursos.php` grava `ORIGIN_UPDATE_MODULE` como histórico incremental. Zerar o
  campo (req-153) faz o CI passar, mas o próximo `project:update-all` o preenche de novo.

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
