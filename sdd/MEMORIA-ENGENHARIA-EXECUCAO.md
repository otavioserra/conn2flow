# MemÃ³ria de Engenharia â€” ExecuÃ§Ã£o

> **PropÃ³sito**: manter contexto operacional recente. Regras consolidadas vivem em `.claude/skills/`, `.cursor/skills/`, `.github/skills/` e `.gemini/skills/` e sÃ£o carregadas sob demanda.
>
> **PolÃ­tica**: preservar 3 a 5 tarefas recentes, mirar ~4.5 KB a 5 KB e podar antes de 10 KB. A memÃ³ria de Chefia permanece somente leitura.

## Skills Core destiladas

- `c2f-json-resources-sync`: versÃµes/checksums de recursos sÃ£o recalculados pelo deploy.
- `c2f-widget-development`: recursos desduplicados, contrato AJAX e tokens `item#var`.
- `c2f-gd-image-safety`: suporte opcional a formatos GD e captura de `\Throwable`.
- `c2f-database-testing`: SQLite em memÃ³ria ou MySQL isolado `conn2flow_test`.
- `c2f-mysql-utf8-emoji-encoding`: JSON ASCII-safe para MySQL `utf8` de 3 bytes.
- `c2f-variables-system`: proibiÃ§Ã£o estrita de strings/textos literais hardcoded em PHP/HTML/JS.
- `c2f-module-crud-scaffolding`: scaffolding canÃ´nico baseado em `gestor/modulos/modulos-grupos/`.

## Tarefas recentes

### 2026-08-18 â€” BATCH-123: HTML Ã© recurso, classe Ã© variÃ¡vel, .env Ã© outra coisa

- **Todo HTML pertence a `resources/`**: `$html .= '<div class="â€¦">'` no PHP nÃ£o passa pelo pipeline, nÃ£o vira `*Data.json`, nÃ£o chega ao banco e nÃ£o Ã© editÃ¡vel por instalaÃ§Ã£o.
- **Classe utilitÃ¡ria Ã© VARIÃVEL do sistema, nÃ£o constante PHP**: VariÃ¡vel Ã© dado de banco (versionada, editÃ¡vel por instalaÃ§Ã£o, sem deploy).
- **`.env`/`config.php` Ã© para constante global ou dado sensÃ­vel** (token, credencial).
- **Extractor do Tailwind enxerga classes dentro do JSON de variÃ¡veis**: declarar `<modulo>.json` em `tailwind_sources`.
- **Componente de mÃ³dulo montado em runtime PRECISA entrar em `tailwind_dependencies` da pÃ¡gina**.
- **PadrÃ£o de componente com variantes**: um componente guarda TODOS os estados da tela em blocos nomeados e o PHP escolhe qual entra (`modelo_tag_val`, `modelo_tag_in`, `modelo_var_in`).
- SuÃ­te apÃ³s o batch: PHPUnit **520/520**, Vitest **328/328**.

### 2026-08-18 â€” BATCH-122: degradaÃ§Ã£o graciosa quando a migraÃ§Ã£o nÃ£o rodou

- **O risco real nÃ£o Ã© a migraÃ§Ã£o ser esquecida, Ã© cÃ³digo e schema chegarem por canais diferentes**.
- **PadrÃ£o Ã© gate de schema que falha FECHADO**: `gestor_schema_tabela_existe()` / `gestor_schema_campo_existe()`, memoizados por requisiÃ§Ã£o. `SHOW TABLES` cobre todas as checagens com 1 query.
- **Escolha o que degrada**: PAT some inteiro (funcionalidade nova); 2FA ativa normalmente sem a coluna de recovery codes.
- Gerador puro e gate no chamador: `usuario_recovery_codes_gerar()` continua testÃ¡vel e desacoplado.
- SuÃ­tes: PHPUnit **508/508**, Vitest **328/328**.

### 2026-08-18 â€” req-119 / BATCH-120 e req-120 / BATCH-121: PAT, recovery codes e telas pÃºblicas

- **Segredo Ãºnico dita o comportamento da interface**: PAT e recovery codes sÃ£o devolvidos em claro uma Ãºnica vez; evitar recargas de pÃ¡gina que destruam os dados antes de anotaÃ§Ã£o.
- **Dois formatos de credencial no mesmo `Authorization: Bearer`**: prefixo `c2f_pat_` desempata antes da validaÃ§Ã£o JWT.
- **SHA-256 sem sal para tokens de API** (busca pelo hash); senha segue com bcrypt/sal.
- SuÃ­tes: PHPUnit **490/490**, Vitest **320/320**.

### 2026-08-18 â€” req-118 / BATCH-119: base administrativa Tailwind

- **MigraÃ§Ã£o de framework foca no RUNTIME**: desacoplamento de chamadas Fomantic em `interface.js`.
- **Contrato de dados portÃ¡vel**: `interface_formulario_validacao()` com validaÃ§Ãµes agnÃ³sticas.
- **ResoluÃ§Ã£o de framework**: `gestor_framework_css_resolver()` com suporte a modo hÃ­brido.
- SuÃ­tes: PHPUnit **353/353**, Vitest **309/309**.

## PendÃªncias e HistÃ³rico

- Testes que executam o compilador de recursos podem regenerar data files/checksums. Conferir `git status`.
- Detalhes anteriores ao BATCH-119 permanecem recuperÃ¡veis no histÃ³rico Git e nos arquivos em `sdd/validation/archive/` e `sdd/implementation/archive/`.
