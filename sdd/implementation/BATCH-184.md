# BATCH-184: Migração do Acervo de Documentação — Onda 1 (fundação) e seguintes do primeiro agente

Execução da [req-179](../human-requests/req-179.md). Os módulos estão com o segundo agente: [req-180](../human-requests/req-180.md) / [BATCH-185](BATCH-185.md).

## Progresso

| Doc (pt-br + en) | Legado removido | Achados do código |
|---|---|---|
| `reference/libraries/index.md` | — | O registro `bibliotecas-dados` tem `api-cliente` e `cpanel` sem arquivo (fatal se pedidos). `gestor_incluir_bibliotecas()` não valida nomes. |
| `reference/libraries/variaveis.md` | `BIBLIOTECA-VARIAVEIS` / `LIBRARY-VARIABLES` | Biblioteca sem chamadores; nenhuma variável `_sistema` nos dados; SQL sem escape de `grupo`/`id`; sem filtro de idioma; `atualizar` não atualiza o cache. A doc antiga a apresentava como sistema de configuração, com casos de uso inventados. |
| `reference/libraries/pagina.md` | `BIBLIOTECA-PAGINA` / `LIBRARY-PAGE` | Só `pagina_celula()` tem uso (`perfil-usuario`). O `strtolower()` de mascarar/desmascarar atua no padrão, não no nome. |
| `reference/libraries/banco.md` | `BIBLIOTECA-BANCO` / `LIBRARY-DATABASE` / `BANCO-V2-DOCS` | A `banco-v2` foi removida da linha 2.x (req-108), e os `@deprecated` são resíduo. `'unico' => false` ainda devolve uma linha só. `banco_retirar_acentos()` gera maiúsculas (`migraCOes`). `banco_identificador()` faz DELETE físico de registros `status='D'`. `banco_delete_varios()` dá TypeError. 22 funções sem chamadores. |
| `reference/libraries/gestor.md` | `BIBLIOTECA-GESTOR` / `LIBRARY-MANAGER` | `gestor_variaveis_alterar()` sem escape do valor; `gestor_variaveis_globais()` ignora o módulo; `gestor_layout()` sem escape e sem filtro de status; `gestor_componente()` sem filtro de status; sessões limpas em 1 de cada 51 requisições; `existe('0')` é true. |
| `guides/installation.md` (antecipado a pedido do Humano) | `CONN2FLOW-INSTALADOR-DETALHADO`, `CONN2FLOW-ADAPTACAO-POS-INSTALACAO` (+ en) | A doc antiga descrevia `checkSystemRequirements()`/`runMigrations()`/`runSeeds()`/`install()`, que **não existem**. O instalador não valida a versão do PHP nem as extensões. `releases/latest` do GitHub aponta para o `gestor-v*` (as duas séries usam `make_latest: true`), então o link "sempre o mais recente" usa a API filtrando `instalador-v*`: JS no template `docs-article` + comando testado. O fallback do instalador é fixo em `gestor-v2.10.1`. `CAPTCHA_PROVIDER` para Turnstile é `cloudflare-turnstile`. `getGestorPath()` tem `. DIRECTORY_SEPARATOR + 'gestor'` (TypeError, só no ramo sem `install_path`). O `PHP85-INSTALL-GUIDE` (en) fica para `guides/development-environment` (onda 4). |
| `reference/libraries/interface.md` | `BIBLIOTECA-INTERFACE` / `LIBRARY-INTERFACE` | **Segurança (req-181):** SQL injection em `interface_listar_ajax()` (busca, colunas e ordenação vindas do `$_REQUEST`); excluir/status por GET sem CSRF; nome de coluna controlável em `verificar-campo`. A regex de `email-obrigatorio` rejeita e-mail que começa com dígito, com maiúsculas ou com `+`. `texto-obrigatorio` conta bytes. `$id` indefinido em `editar`/`alteracoes_finalizar` (inócuo). O docblock de `interface_verificar_campos` estava errado. |
| `concepts/request-lifecycle.md` | `CONN2FLOW-ROTEAMENTO-DETALHADO` / `ROUTING-DETAILED` | 404 e 401 são redirecionamentos; `?hotfix` responde "Hotfix Done!"; *fingerprint* comentado; `gestor_pagina_css_incluir($css)` empilha na fila de JS; o cookie `COOKIE_AUTHPROFILE` não é assinado. |
| `concepts/global-variables.md` | `CONN2FLOW-VARIAVEIS-GLOBAIS` / `GLOBAL-VARIABLES` | Sem o `.env` do host, o `config.php` carrega o **primeiro** `.env` de `autenticacoes/` (Host desconhecido usa configuração de outro domínio). `LANGUAGES` lido sem `??` (*warning*). `ACESSOS_TEMPO_BLOQUEIO_IP`: padrão 86400 no código, 900 no exemplo. |
| `concepts/resources.md` | `CONN2FLOW-SISTEMA-RECURSOS`, `CONN2FLOW-LAYOUTS-PAGINAS-COMPONENTES` (+ en) | Checksum é **MD5**. `incrementVersionStr()` reinicia para `1.0` versões fora de `X.Y`. `resources:sync --force` não é repassado ao script. Órfãos somem do deploy sem erro. Nenhuma tela lê `system_updated`/`*_updated`. Os assets estáticos têm `asset_version` automático (SHA-256), então a regra de "subir versão à mão" do `gestor/resources/CLAUDE.md` está desatualizada. O `CORRECOES-CHECKSUM-SHA256` trata do download de plugins e fica para `concepts/plugins` (onda 5). |
| `concepts/hooks.md` | `CONN2FLOW-HOOKS`, `HOOKS-DISPATCH-BANCO-INTEGRACOES-v1`, `HOOK-APPLY-FILTERS-HTML-EDITOR-IA` (+ en) | O controller é incluído pelo **namespace**: um namespace sem entrada em `controllers` tem o callback ignorado em silêncio (o `hooks.json` do conn2flow-site tem `interface` nessa situação). `hooks_registrar_modulo()` apaga por id do módulo sem considerar o plugin. Filter sem `return` zera o valor. Nenhum módulo do core declara `hooks`. |
| `reference/libraries/modelo.md` (revisão do piloto) | `BIBLIOTECA-MODELO` / `LIBRARY-TEMPLATE` | Doc do piloto conferida contra o código; nada a corrigir. |
| `reference/libraries/{ip,lang,geral}.md` | `BIBLIOTECA-{IP,LANG,GERAL}` (+ en) | `ip_get()` atrás de CDN devolve o IP da borda (não lê `CF-Connecting-IP`). O dicionário padrão do `lang.php` (`bibliotecas/<lang>.json`) não existe; o compilador de recursos não mescla o seu `lang/` e imprime chaves cruas. |
| `reference/libraries/{widgets,plugins,plugins-consts,pdf}.md` | `BIBLIOTECA-{WIDGETS,PLUGINS,PLUGINS-CONSTS,PDF}` (+ en) | Widget que devolve vazio deixa o mockup na página publicada; widgets de plugin não são encontrados. `plugins.php` é arquivo-modelo sem uso. `pdf_voucher()` sem chamadores. |
| `reference/libraries/{host,ftp,recursos}.md` | `BIBLIOTECA-{HOST,FTP}` (+ en) | `host.php` depende de `hosts`/`hosts_variaveis`/`hosts_arquivos`, que nenhuma migração cria, e de `$_GESTOR['host-id']`, que nada define. |
| `reference/libraries/{cron,jwt,hooks}.md` | — | **A engine de cron não avalia `expressao_cron`**: cada tick roda todas as tarefas da frequência; nada cria o agendamento no servidor (guia de instalação ajustado). Sem trava contra execução sobreposta. `jwt_validate_token()` não confere `exp`; nada no core usa os tokens; rotação só manual. |
| `reference/libraries/2fa.md` | — | **Segurança (req-181 A5):** verificação do 2FA sem limite de tentativas, TOTP reutilizável na janela, reenvio de e-mail sem limite. |
| `reference/libraries/{log,formato}.md` | `BIBLIOTECA-{LOG,FORMATO}` (+ en) | `log_disco()` relê e regrava o arquivo inteiro (lento e perde linhas em concorrência). `formato_data_hora_from_datetime_to_text()` troca os códigos dentro de palavras. `formato_colocar_char_meio_numero('7')` lança `ValueError`. |
| `reference/libraries/{html,arquivo}.md` | `BIBLIOTECA-{HTML,ARQUIVO}` (+ en) | `html_finalizar()` desfaz o escape (`&lt;script&gt;` → `<script>`). **Segurança (req-181 A6):** upload de `.html`/`.svg` servido inline no domínio do site. |

## Ferramental ajustado no caminho

- `docs:audit` não trata `reference/libraries/index.md` como biblioteca.
- O extrator ignora `@param tipo $params['chave']` (chave de array), que antes sobrescrevia o tipo real do parâmetro.

## Validação

- `docs:audit --json`: 0 erros. Os avisos restantes são só `missing:` (cobertura pendente).
- PHPUnit das ferramentas de docs: 12/12.
