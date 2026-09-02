# Memória de Engenharia — Execução

> **Propósito**: contexto operacional recente; regras consolidadas vivem nas skills.
> **Política**: preservar 3 a 5 tarefas, mirar 3–4,5 KB e podar antes de 5 KB / 50 linhas.

## Skills Core destiladas

- `c2f-json-resources-sync`: versões/checksums e tokens de assets são recalculados por `resources:sync`.
- `c2f-database-testing`: SQLite em memória ou MySQL `conn2flow_test`; nunca o banco principal.
- `c2f-environment-configuration`: `.env` ativo vive em `autenticacoes/<host>/.env`.
- `c2f-projects-system`: `environment.json` é autoridade para fontes, mirrors, transportes e mounts.
- `c2f-variables-system`: textos de produto não nascem como literais hardcoded em PHP/HTML/JS.

## Tarefas recentes

### 2026-09-02 — BATCH-156 (req-154): templates Tailwind no preview

- Os 72 modelos por idioma, 36 Tailwind, sidecars, `TemplatesData.json` e banco estavam íntegros.
  A causa era a cascata do iframe: Tailwind pré-compilado em `@layer` seguido por Fomantic sem camada;
  CSS não estratificado vence utilities estratificadas. A/B: `py-20` 80→70px, `gap-12` 48→42px e
  CTA branco→transparente. Em preview Tailwind, preserve scripts legados, mas não `semantic.min.css`.
- Ao inserir seção, nunca substitua o baseline da página pelo sidecar do fragmento: concatene ambos.
  Substituição integral de página pode trocar o baseline. `resources:sync --force` recompilou 237/237.
- Guarda atual: 72 HTMLs/thumbnails + 36 sidecars por idioma e presença no CSS de cada utility usada
  das famílias padding, margin, gap, spacing, background, rounded e shadow.

### 2026-09-02 — BATCH-155 (req-153 / REQ-034): transporte SSH e bootstrap CLI

- Tenants saíram do `conn2flow-app` para VM/HestiaCP. Verifique `environment.json`/`docker ps`; SSH
  remoto exige destino declarativo, `BatchMode`, argumentos citados para o shell remoto,
  `sudo rsync` e devolução de posse por `chown`.
- `config.php` não pode impor `SERVER_NAME=localhost` no CLI: o sufixo do cookie fica incorreto e a
  sessão é ignorada com 302 silencioso. Checksum de `<modulo>.json` pertence ao compilador;
  `ORIGIN_UPDATE_MODULE` o repõe, então a guarda aceita vazio ou md5 coincidente com o arquivo.

### 2026-08-28 — BATCH-144 (req-141): autoria x derivado no CSS

- Runtime serve banco; disco apenas em desenvolvimento. `html`/`css` são autoria;
  `css_precompiled`/`css_compiled` são derivados recalculáveis. Publicação criada no banco herda CSS
  do template; o CLI não a encontra no disco.
- Meça cobertura por classes do HTML sem regra entregue, filtrando Tailwind e ignorando `group`/`peer`.
  `css:rebuild --url` usa HTML renderizado; confirme sempre o alvo de gravação exibido pelo comando.

### 2026-08-29 — BATCH-146/147: cópias congeladas e assets locais

- Widgets podem persistir cópia com `user_modified=1`; corrigir o template não altera instâncias.
  Classe gerada só em runtime precisa de regra emitida junto ou fonte adicional declarada.
- PHP CLI/Windows sem CA bundle deve cair para `curl` do sistema, jamais desligar validação TLS.
  A exceção do `.gitignore` para `gestor/assets/vendor/` é necessária para assets locais chegarem à produção.
- Substituições repetidas devem operar por bloco contíguo; depois confira paridade blocos↔chamadas.
