---
title: "Biblioteca modulo-distribuido.php"
label: "Módulos distribuídos"
description: "Arquitetura central/distribuído: um módulo roda no Conn2Flow central e grava os dados no banco da instalação do cliente por um canal HMAC, com login e permissão decididos pelo central."
section: reference
order: 340
sources:
  - gestor/bibliotecas/modulo-distribuido.php
  - gestor/controladores/api/api-module-distributed.php
  - gestor/controladores/api/api-module-central.php
  - gestor/config.php
verified_at: 900cbe77
---

# Biblioteca `modulo-distribuido.php`

Permite que um módulo tenha duas metades:
- **central** (`"scope": "central-module"` no `<modulo>.json`): roda na instalação central (a do fornecedor), onde ficam o código, os usuários e as permissões;
- **distribuído** (`"scope": "distributed-module"`): roda no site do cliente e guarda os **dados** no banco do cliente.

O código do módulo central continua usando `banco_select()`, `banco_insert()`… normalmente; dentro do canal distribuído, `banco_query()` empacota a SQL e a executa na instalação do cliente. O cliente vê o módulo como uma tela embutida (iframe) do central, liberada só depois de o central confirmar login e permissão.

## Configuração

No `.env` ([variáveis globais](../../concepts/global-variables.md)): `MODULO_DISTRIBUIDO_SECRET` (segredo HMAC compartilhado), `MODULO_DISTRIBUIDO_CENTRAL_URL` (no distribuído: a URL do central) e `MODULO_DISTRIBUIDO_ENDPOINT` (no central: a URL da API do distribuído). Por módulo, `$_CONFIG['modulo-distribuido']['secrets'][<slug>]` e `['endpoints'][<slug>]` sobrepõem os valores gerais (`modulo_distribuido_config_get('modulo-distribuido.secrets.<slug>')`).

`modulo_distribuido_scope($manifesto)`, `modulo_distribuido_scope_central()`, `modulo_distribuido_scope_distribuido()` e `modulo_distribuido_resolver_manifesto()` leem o escopo do manifesto (constantes `C2F_SCOPE_CENTRAL` e `C2F_SCOPE_DISTRIBUIDO`).

## O canal de dados

```php
// No módulo central, envolvendo só as operações de dados do cliente:
// o manifesto do módulo central declara "distributed": {"target-slug": "meu-modulo"}
$config = modulo_distribuido_canal_central($_GESTOR['modulo#'.$_GESTOR['modulo-id']]);
$pedidos = modulo_distribuido_com_canal(function () {
    return banco_select(['tabela' => 'pedidos', 'campos' => ['id', 'total']]);
}, $config);
// ou, à mão: banco_distribuido_iniciar($config); … banco_distribuido_finalizar();
```

1. `banco_distribuido_ativo()` faz `banco_query()` desviar para `banco_distribuido_query()`.
2. `modulo_distribuido_montar_payload()` empacota a SQL (`operacao`, `modulo`, `linguagem`, `timestamp`, `nonce`), e `modulo_distribuido_enviar()` a manda por `modulo_distribuido_http_post()` para `<endpoint>/modulo-distribuido/<slug>/db`, com `X-C2F-Signature` = HMAC-SHA256 do corpo (`modulo_distribuido_assinar()`).
3. No distribuído, a API confere a assinatura (`modulo_distribuido_verificar_assinatura()`, tempo constante) e `modulo_distribuido_executar_local()` roda a SQL via PDO: `SELECT` devolve colunas e linhas; escrita devolve `affected_rows` e `insert_id`.
4. `modulo_distribuido_resposta_para_resultado()` converte a resposta num `BancoResultadoRemoto`, que imita o `mysqli_result` para as funções da [banco.php](banco.md).

Autenticação, variáveis e o resto da requisição continuam no banco local do central: só o que estiver dentro do canal vai para o cliente. `modulo_distribuido_canal_central($manifesto, $overrides)` monta a configuração do lado central a partir de `distributed.target-slug` (e, opcionalmente, `endpoint-config`/`secret-config`, caminhos de chaves em `$_CONFIG`) do manifesto; `modulo_distribuido_canal_distribuido()` faz o mesmo no distribuído, para falar com o central; `modulo_distribuido_detectar_operacao()` e `modulo_distribuido_operacao_leitura()` classificam a SQL; `modulo_distribuido_parse_rota()` lê `{slug}/{acao}` da URL.

> [!WARNING]
> O distribuído aceita **qualquer SQL de uma instrução** (`modulo_distribuido_sql_segura()` só recusa `;` fora de strings), em qualquer tabela, e não confere o `timestamp` nem o `nonce`: uma requisição assinada pode ser reenviada. Trate o segredo como uma senha de administrador do banco do cliente (req-181, item A11).

## Login, permissão e tela

O distribuído não decide acesso: pergunta ao central.
- `modulo_distribuido_signin()` manda usuário e senha ao central e recebe os tokens (OAuth2 do central); `modulo_distribuido_token_sessao()` e `modulo_distribuido_persistir_token()` guardam o token na sessão.
- `modulo_distribuido_guardiao()` (no distribuído) consulta o central, onde `modulo_distribuido_middleware_central()` valida o token e a permissão do usuário no módulo e responde `nao-autenticado`, `sem-permissao` ou autorizado. Qualquer falha resolve para o estado mais restritivo.
- `modulo_distribuido_estado_renderizacao()` traduz isso em `login`, `sem-permissao` ou `iframe`; `modulo_distribuido_montar_url_iframe()` monta a URL da tela do central.
- `modulo_distribuido_app()` faz tudo numa chamada: token, guardião e a tela do estado, trocando `#modulo-distribuido-app#` na página pelo componente `modulo-distribuido-app` (`modulo_distribuido_render_estado()`, `modulo_distribuido_render_componente()`, `modulo_distribuido_textos()`).

As variantes `modulo_distribuido_estado_por_token_ativo()`, `modulo_distribuido_estado_por_permissao_central()` e `modulo_distribuido_middleware_permissao()` são as peças puras (testáveis) desse fluxo.

## Funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/modulo-distribuido.php` por `c2f docs:extract` — 37 funções. Não edite dentro deste bloco.

- `modulo_distribuido_scope(array|string $modulo, string|null $slug = null): string|null` — [linha 140](../../../../../gestor/bibliotecas/modulo-distribuido.php#L140)
  Lê o valor da chave "scope" do manifesto <slug>.json de um módulo.
  Parâmetros:
  - `$modulo`: Manifesto decodificado, caminho do .json ou do diretório do módulo.
  - `$slug`: Slug do módulo (necessário quando $modulo é um diretório).
  Retorno: Valor de scope ('central-module' / 'distributed-module') ou null.
- `modulo_distribuido_scope_central($scope): bool` — [linha 151](../../../../../gestor/bibliotecas/modulo-distribuido.php#L151)
  Indica se o scope informado é o de um módulo central.
- `modulo_distribuido_scope_distribuido($scope): bool` — [linha 158](../../../../../gestor/bibliotecas/modulo-distribuido.php#L158)
  Indica se o scope informado é o de um módulo distribuído.
- `modulo_distribuido_resolver_manifesto(array|string $modulo, string|null $slug = null): array|null` — [linha 170](../../../../../gestor/bibliotecas/modulo-distribuido.php#L170)
  Resolve o manifesto (array) de um módulo a partir de array, caminho de .json ou diretório.
  Parâmetros:
  - `$modulo`: Manifesto decodificado, caminho do .json ou do diretório do módulo.
  - `$slug`: Slug do módulo (usado para achar <slug>.json quando $modulo é diretório).
  Retorno: Manifesto decodificado ou null.
- `modulo_distribuido_assinar(string $corpo, string $secret, string $algo = 'sha256'): string` — [linha 209](../../../../../gestor/bibliotecas/modulo-distribuido.php#L209)
  Gera a assinatura HMAC de um corpo de requisição.
  Parâmetros:
  - `$corpo`: Corpo cru (string JSON) a ser assinado.
  - `$secret`: Segredo compartilhado entre central e distribuído.
  - `$algo`: Algoritmo de hash (padrão sha256).
  Retorno: Assinatura em hexadecimal.
- `modulo_distribuido_verificar_assinatura(string $corpo, string $assinatura, string $secret, string $algo = 'sha256'): bool` — [linha 223](../../../../../gestor/bibliotecas/modulo-distribuido.php#L223)
  Verifica, de forma resistente a ataques de timing, a assinatura HMAC de um corpo.
  Parâmetros:
  - `$corpo`: Corpo cru recebido.
  - `$assinatura`: Assinatura recebida (header X-C2F-Signature).
  - `$secret`: Segredo compartilhado.
  - `$algo`: Algoritmo de hash (padrão sha256).
  Retorno: true se a assinatura confere.
- `modulo_distribuido_detectar_operacao(string $sql): string` — [linha 240](../../../../../gestor/bibliotecas/modulo-distribuido.php#L240)
  Detecta o tipo de operação SQL a partir da instrução.
  Parâmetros:
  - `$sql`: Instrução SQL.
  Retorno: Um de: 'select', 'insert', 'update', 'delete', 'outro'.
- `modulo_distribuido_operacao_leitura($operacao): bool` — [linha 254](../../../../../gestor/bibliotecas/modulo-distribuido.php#L254)
  Indica se a operação detectada é de leitura (SELECT).
- `modulo_distribuido_montar_payload(string $sql, array $opcoes = []): array` — [linha 266](../../../../../gestor/bibliotecas/modulo-distribuido.php#L266)
  Monta o payload (array) que empacota a instrução SQL para trânsito via API.
  Parâmetros:
  - `$sql`: Instrução SQL já montada.
  - `$opcoes`: Opções: 'modulo' (slug), 'linguagem'.
  Retorno: Payload com sql, operacao, modulo, timestamp e nonce.
- `modulo_distribuido_enviar(array $payload, array $config): array` — [linha 297](../../../../../gestor/bibliotecas/modulo-distribuido.php#L297)
  Envia um payload de banco distribuído para a instalação remota e decodifica a resposta.
  Parâmetros:
  - `$payload`: Payload montado por modulo_distribuido_montar_payload().
  - `$config`: Configuração do canal distribuído.
  Retorno: Resposta decodificada. Em erro retorna ['status' => 'error', 'message' => ...].
- `modulo_distribuido_http_post($url, $corpo, array $headers, $timeout = 15): string|false` — [linha 344](../../../../../gestor/bibliotecas/modulo-distribuido.php#L344)
  POST HTTP simples via cURL. Isolado para permitir mock nos testes.
  Retorno: Corpo da resposta ou false em falha de transporte.
- `modulo_distribuido_resposta_para_resultado(array $resposta): BancoResultadoRemoto|bool` — [linha 375](../../../../../gestor/bibliotecas/modulo-distribuido.php#L375)
  Converte a resposta remota em um resultado consumível por banco.php.
  Parâmetros:
  - `$resposta`: Resposta decodificada da instalação distribuída.
- `modulo_distribuido_executar_local(array $payload, PDO $pdo): array` — [linha 413](../../../../../gestor/bibliotecas/modulo-distribuido.php#L413)
  Executa localmente, no banco do cliente, a instrução SQL recebida da central.
  Parâmetros:
  - `$payload`: Payload recebido (deve conter 'sql').
  - `$pdo`: Conexão PDO com o banco local do cliente.
  Retorno: Resposta pronta para json_encode: {status, tipo, fields, rows, ...}.
- `modulo_distribuido_sql_segura(string $sql): bool` — [linha 475](../../../../../gestor/bibliotecas/modulo-distribuido.php#L475)
  Guard básico contra múltiplas instruções empilhadas.
  Parâmetros:
  - `$sql`: Instrução SQL.
  Retorno: true se aparenta ser uma instrução única e segura.
- `modulo_distribuido_parse_rota(array $caminho): array|null` — [linha 497](../../../../../gestor/bibliotecas/modulo-distribuido.php#L497)
  Faz o parse da rota da API distribuída: api/v1/modulo-distribuido/{slug}/{acao}.
  Parâmetros:
  - `$caminho`: Segmentos do caminho da URL.
  Retorno: ['slug' => ..., 'acao' => ..., 'resto' => [...]] ou null se não casar.
- `modulo_distribuido_estado_por_token_ativo(bool $token_ativo): string` — [linha 548](../../../../../gestor/bibliotecas/modulo-distribuido.php#L548)
  Decide o estado de renderização do módulo distribuído a partir da validade do token.
  Parâmetros:
  - `$token_ativo`: Resultado de autenticacao_distribuido_token_ativo().
  Retorno: 'iframe' ou 'login'.
- `modulo_distribuido_estado_renderizacao(bool $token_ativo, bool $tem_permissao): string` — [linha 567](../../../../../gestor/bibliotecas/modulo-distribuido.php#L567)
  Decide o estado de renderização considerando autenticação E permissão por módulo.
  Parâmetros:
  - `$token_ativo`: Usuário autenticado (token válido).
  - `$tem_permissao`: Usuário autorizado ao módulo alvo.
  Retorno: 'login' | 'sem-permissao' | 'iframe'.
- `modulo_distribuido_estado_por_permissao_central(string $estado_central): string` — [linha 585](../../../../../gestor/bibliotecas/modulo-distribuido.php#L585)
  Traduz o 'estado' devolvido pelo middleware central (endpoint 'permissao') no estado de renderização do ambiente distribuído.
  Parâmetros:
  - `$estado_central`: 'nao-autenticado' | 'sem-permissao' | 'permitido'.
  Retorno: 'login' | 'sem-permissao' | 'iframe'.
- `modulo_distribuido_middleware_permissao(array $config, string $token, string|null $slug = null): array` — [linha 609](../../../../../gestor/bibliotecas/modulo-distribuido.php#L609)
  Middleware de permissão (lado distribuído): consulta o central e devolve o estado.
  Parâmetros:
  - `$config`: Config do canal (endpoint, slug, secret, transporte).
  - `$token`: Access token do usuário (obtido no signin).
  - `$slug`: Slug do módulo alvo (default: $config['slug']).
  Retorno: ['estado' => 'login'|'sem-permissao'|'iframe', 'resposta' => array].
- `modulo_distribuido_guardiao(array $config, array $opcoes = []): array` — [linha 658](../../../../../gestor/bibliotecas/modulo-distribuido.php#L658)
  Guardião de módulo distribuído — fachada obrigatória de controle de acesso.
  Parâmetros:
  - `$config`: Config do canal (endpoint, slug, secret, transporte, central-url).
  - `$opcoes`: 'token', 'central-url', 'opcao', 'params-iframe', 'slug'.
  Retorno: {
- `modulo_distribuido_signin(array $config, string $usuario, string $senha): array|false` — [linha 697](../../../../../gestor/bibliotecas/modulo-distribuido.php#L697)
  Autentica (ativa) o usuário no central e retorna os tokens — fluxo de login distribuído.
  Parâmetros:
  - `$config`: Config do canal para o central (endpoint, secret, slug, transporte).
  - `$usuario`: Login do usuário.
  - `$senha`: Senha em texto plano.
  Retorno: Dados dos tokens (access_token, refresh_token, ...) ou false.
- `modulo_distribuido_middleware_central(string $token, string $slug, array $opcoes = []): array` — [linha 737](../../../../../gestor/bibliotecas/modulo-distribuido.php#L737)
  Middleware central — avalia autenticação e permissão como AUTORIDADE do sistema.
  Parâmetros:
  - `$token`: Access token do usuário.
  - `$slug`: Slug do módulo alvo.
  - `$opcoes`: 'resolver_usuario' (callable(token):?int), 'verificar_permissao' (callable(id,slug):bool).
  Retorno: ['estado' => 'nao-autenticado'|'sem-permissao'|'permitido', 'id_usuarios' => int|null, 'modulo' => string].
- `modulo_distribuido_montar_url_iframe(string $endpoint_central, string $slug, array $opcoes = []): string` — [linha 777](../../../../../gestor/bibliotecas/modulo-distribuido.php#L777)
  Monta a URL administrativa do módulo no central para renderização em Iframe.
  Parâmetros:
  - `$endpoint_central`: URL base do central (ex.: https://conn2flow.com/).
  - `$slug`: Slug do módulo (central) a ser embutido.
  - `$opcoes`: 'modulo' (override do slug na URL), 'opcao', 'token', 'params' (extra).
  Retorno: URL absoluta para o src do Iframe.
- `banco_distribuido_iniciar(array $config): void` — [linha 809](../../../../../gestor/bibliotecas/modulo-distribuido.php#L809)
  Ativa o modo distribuído do banco para as próximas operações.
  Parâmetros:
  - `$config`: Configuração do canal (endpoint, slug, secret, token, ...).
- `banco_distribuido_finalizar(): void` — [linha 819](../../../../../gestor/bibliotecas/modulo-distribuido.php#L819)
  Desativa o modo distribuído do banco, retornando às operações locais.
- `banco_distribuido_ativo(): bool` — [linha 829](../../../../../gestor/bibliotecas/modulo-distribuido.php#L829)
  Indica se o modo distribuído do banco está ativo no contexto atual.
- `banco_distribuido_query(string $sql): BancoResultadoRemoto|bool` — [linha 845](../../../../../gestor/bibliotecas/modulo-distribuido.php#L845)
  Executa uma query no modo distribuído: empacota, envia e converte a resposta.
  Parâmetros:
  - `$sql`: Instrução SQL montada pelas funções de banco.php.
- `modulo_distribuido_config_get(string $path, mixed $default = null): mixed` — [linha 875](../../../../../gestor/bibliotecas/modulo-distribuido.php#L875)
  Lê um valor de $_CONFIG por caminho em dot-notation (ex.: 'modulo-distribuido.secret').
  Parâmetros:
  - `$path`: Caminho em dot-notation.
  - `$default`: Valor padrão quando o caminho não existe.
- `modulo_distribuido_canal_central(array $modulo_config, array $overrides = []): array` — [linha 904](../../../../../gestor/bibliotecas/modulo-distribuido.php#L904)
  Resolve a config do canal para um MÓDULO CENTRAL (que delega banco ao distribuído).
  Parâmetros:
  - `$modulo_config`: Manifesto do módulo central.
  - `$overrides`: endpoint, secret, slug, token, timeout, transporte.
- `modulo_distribuido_canal_distribuido(array $modulo_config, array $overrides = []): array` — [linha 944](../../../../../gestor/bibliotecas/modulo-distribuido.php#L944)
  Resolve a config do canal para um MÓDULO DISTRIBUÍDO (que consulta o central).
  Parâmetros:
  - `$modulo_config`: Manifesto do módulo distribuído.
  - `$overrides`: central-url, secret, slug, transporte.
  Retorno: Inclui 'endpoint' (central-url/_api) e 'central-url'.
- `modulo_distribuido_com_canal(callable $operacao, array $config): mixed` — [linha 977](../../../../../gestor/bibliotecas/modulo-distribuido.php#L977)
  Executa uma operação de dados dentro do canal distribuído, garantindo o fechamento.
  Parâmetros:
  - `$operacao`: Função com as chamadas de banco (delegadas ao distribuído).
  - `$config`: Config do canal (de modulo_distribuido_canal_central()).
  Retorno: Retorno de $operacao.
- `modulo_distribuido_token_sessao(string $chave): string` — [linha 993](../../../../../gestor/bibliotecas/modulo-distribuido.php#L993)
  Lê, da sessão, o token de acesso ao central guardado por um módulo distribuído.
  Parâmetros:
  - `$chave`: Chave da variável de sessão.
  Retorno: Token ou string vazia.
- `modulo_distribuido_persistir_token(string $chave, array $tokens, callable|null $persistir = null): void` — [linha 1015](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1015)
  Persiste os tokens obtidos no login/ativação distribuído.
  Parâmetros:
  - `$chave`: Chave base da variável de sessão do token.
  - `$tokens`: Tokens retornados pelo central (access_token, refresh_token, ...).
  - `$persistir`: Persistência customizada: function($chave, array $tokens): void.
- `modulo_distribuido_textos(string|null $lang = null, array $overrides = []): array` — [linha 1041](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1041)
  Dicionário de textos (i18n) do componente global de estados distribuídos.
  Parâmetros:
  - `$lang`: Idioma (default: $_GESTOR['linguagem-codigo']).
  - `$overrides`: Sobrescritas de texto por chave (#c2f-md-*# sem as cercas).
  Retorno: Mapa chave => texto.
- `modulo_distribuido_render_componente(string $html, string $estado, string|null $iframe_url = null, array $textos = []): string` — [linha 1096](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1096)
  Processa o HTML do componente de estados segundo o estado de acesso (função pura).
  Parâmetros:
  - `$html`: HTML do componente (de gestor_componente()).
  - `$estado`: 'login' | 'sem-permissao' | 'iframe'.
  - `$iframe_url`: URL do Iframe (quando 'iframe').
  - `$textos`: Mapa de textos (de modulo_distribuido_textos()).
  Retorno: HTML final pronto para injeção.
- `modulo_distribuido_render_estado(string $estado, string|null $iframe_url = null): string` — [linha 1140](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1140)
  Aplica a renderização por estado diretamente sobre a página do módulo ($_GESTOR['pagina']).
  Parâmetros:
  - `$estado`: 'login' | 'sem-permissao' | 'iframe'.
  - `$iframe_url`: URL do Iframe (quando 'iframe').
  Retorno: O próprio estado.
- `modulo_distribuido_app(array $config, array $opcoes = []): array` — [linha 1175](../../../../../gestor/bibliotecas/modulo-distribuido.php#L1175)
  Orquestração completa do "app" de um módulo distribuído (fachada de alto nível).
  Parâmetros:
  - `$config`: Config do canal (de modulo_distribuido_canal_distribuido()).
  - `$opcoes`: 'token', 'token-chave', 'opcao', 'params-iframe', 'central-url',
  Retorno: Resultado do guardião + 'html' (componente renderizado).

<!-- c2f:extract:end -->
