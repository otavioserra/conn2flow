<?php

// ===== Sub-controlador de Autenticação Mobile da API (BATCH-008 conn2flow-app)
//
// Rotas: api/auth/{login|logout|me|modules}
// - login: credenciais do Conn2Flow (usuario/email + senha) -> tokens OAuth2 (Bearer).
// - logout: revoga o access token (e opcionalmente o refresh token) na tabela oauth2_tokens.
// - me: revalida o token e devolve os dados atuais do usuário.
// - modules: espelha a lógica do menu web (usuarios_perfis_modulos) e devolve os
//   módulos/grupos liberados para o perfil do usuário — RBAC dinâmico do app.

global $_GESTOR;

// =========================== Helpers

/**
 * Valida o Bearer token e devolve a linha completa do usuário ativo.
 * Responde 401 diretamente quando o token é inválido.
 *
 * @return array Linha do usuário (id_usuarios, nome, email, usuario, id_usuarios_perfis, id_hosts, gestor_perfil).
 */
function api_auth_usuario() {
    global $_GESTOR;

    if (isset($_GESTOR['api-auth-usuario'])) {
        return $_GESTOR['api-auth-usuario'];
    }

    gestor_incluir_biblioteca('oauth2');

    $token_usuario = oauth2_autorizar_requisicao();

    if (!$token_usuario || !is_array($token_usuario)) {
        api_response_error('Token de autenticação inválido ou expirado', 401);
    }

    $usuarios = banco_select(Array(
        'unico' => true,
        'tabela' => 'usuarios',
        'campos' => Array(
            'id_usuarios',
            'nome',
            'email',
            'usuario',
            'id_usuarios_perfis',
            'id_hosts',
            'gestor_perfil',
        ),
        'extra' =>
            "WHERE id_usuarios='" . banco_escape_field($token_usuario['id_usuarios']) . "'"
            . " AND status='A'"
    ));

    if (!$usuarios) {
        api_response_error('Usuário inválido ou inativo', 401);
    }

    $_GESTOR['api-auth-usuario'] = $usuarios;

    return $usuarios;
}

/**
 * Extrai o Bearer token bruto do header Authorization (para revogação no logout).
 *
 * @return string|null
 */
function api_auth_bearer_token() {
    $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

    if (!$auth_header) {
        $headers = function_exists('getallheaders') ? getallheaders() : Array();
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
        }
    }

    if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Idioma dos recursos (modulos/paginas sao por language). Query param opcional
 * validado por whitelist de formato; padrão é a linguagem corrente do gestor.
 *
 * @return string
 */
function api_auth_language() {
    global $_GESTOR;

    $language = isset($_GET['language']) ? strtolower((string) $_GET['language']) : '';

    if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $language)) {
        $language = $_GESTOR['linguagem-codigo'];
    }

    return $language;
}

/**
 * Controle de força bruta do login: máximo de tentativas falhas por usuário+IP
 * numa janela deslizante. Complementa o rate limit genérico por IP da API.
 *
 * @param string $usuario Login informado.
 * @param bool   $registrar_falha Quando true, registra uma falha; quando false, apenas consulta.
 *
 * @return bool True se ainda está dentro do limite.
 */
function api_auth_login_throttle($usuario, $registrar_falha = false) {
    global $_GESTOR;

    $max_falhas = 10;
    $janela = 900; // 15 minutos

    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $chave = md5('login_throttle_' . strtolower((string) $usuario) . '_' . $ip);

    $api_logs_path = $_GESTOR['logs-path'] . 'api/';
    if (!is_dir($api_logs_path)) {
        mkdir($api_logs_path, 0755, true);
    }
    $cache_file = $api_logs_path . 'login_throttle_' . $chave . '.cache';

    $agora = time();
    $falhas = Array();

    if (file_exists($cache_file)) {
        $dados = json_decode(file_get_contents($cache_file), true);
        if ($dados && isset($dados['falhas'])) {
            $falhas = array_values(array_filter($dados['falhas'], function ($timestamp) use ($agora, $janela) {
                return ($agora - $timestamp) < $janela;
            }));
        }
    }

    if ($registrar_falha) {
        $falhas[] = $agora;
        file_put_contents($cache_file, json_encode(Array('falhas' => $falhas)));
    }

    return count($falhas) < $max_falhas;
}

/**
 * Resolve o perfil efetivo do usuário e a lista de módulos liberados,
 * espelhando exatamente a resolução do menu web (gestor_menu / gestor_permissao_modulo).
 *
 * @param array $usuario Linha do usuário (api_auth_usuario()).
 *
 * @return array ['perfil_id' => string|null, 'perfil_nome' => string|null, 'modulos_ids' => string[], 'gestor_perfil' => bool]
 */
function api_auth_resolver_perfil($usuario) {
    $modulos_ids = Array();
    $perfil_id = null;
    $perfil_nome = null;
    $usa_gestor_perfil = false;

    if (existe($usuario['id_hosts']) && existe($usuario['gestor_perfil'])) {
        // Usuário filho de host com perfil de gestor próprio.
        $usa_gestor_perfil = true;
        $perfil_id = $usuario['gestor_perfil'];

        $permissoes = banco_select(Array(
            'tabela' => 'usuarios_gestores_perfis_modulos',
            'campos' => Array('modulo'),
            'extra' =>
                "WHERE perfil='" . banco_escape_field($perfil_id) . "'"
                . " AND id_hosts='" . banco_escape_field($usuario['id_hosts']) . "'"
        ));
    } else {
        // Usuário direto (ou filho de host sem gestor_perfil: herda o perfil do dono do host).
        $id_usuarios_perfis = $usuario['id_usuarios_perfis'];

        if (existe($usuario['id_hosts']) && !existe($usuario['gestor_perfil'])) {
            $hosts = banco_select(Array(
                'unico' => true,
                'tabela' => 'hosts',
                'campos' => Array('id_usuarios'),
                'extra' => "WHERE id_hosts='" . banco_escape_field($usuario['id_hosts']) . "'"
            ));

            if ($hosts) {
                $usuario_pai = banco_select(Array(
                    'unico' => true,
                    'tabela' => 'usuarios',
                    'campos' => Array('id_usuarios_perfis'),
                    'extra' => "WHERE id_usuarios='" . banco_escape_field($hosts['id_usuarios']) . "'"
                ));

                if ($usuario_pai) {
                    $id_usuarios_perfis = $usuario_pai['id_usuarios_perfis'];
                }
            }
        }

        $usuarios_perfis = banco_select(Array(
            'unico' => true,
            'tabela' => 'usuarios_perfis',
            'campos' => Array('id', 'nome'),
            'extra' => "WHERE id_usuarios_perfis='" . banco_escape_field($id_usuarios_perfis) . "'"
        ));

        if ($usuarios_perfis) {
            $perfil_id = $usuarios_perfis['id'];
            $perfil_nome = $usuarios_perfis['nome'];

            $permissoes = banco_select(Array(
                'tabela' => 'usuarios_perfis_modulos',
                'campos' => Array('modulo'),
                'extra' => "WHERE perfil='" . banco_escape_field($perfil_id) . "'"
            ));
        } else {
            $permissoes = false;
        }
    }

    if (!empty($permissoes)) {
        foreach ($permissoes as $permissao) {
            $modulos_ids[] = $permissao['modulo'];
        }
    }

    return Array(
        'perfil_id' => $perfil_id,
        'perfil_nome' => $perfil_nome,
        'modulos_ids' => $modulos_ids,
        'gestor_perfil' => $usa_gestor_perfil,
    );
}

// =========================== Handlers

/**
 * POST api/auth/login — { "usuario": "...", "senha": "..." }
 */
function api_auth_login() {
    global $_GESTOR;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    $body = api_get_request_body();

    $usuario_login = isset($body['usuario']) ? trim((string) $body['usuario']) : '';
    $senha = isset($body['senha']) ? (string) $body['senha'] : '';

    if ($usuario_login === '' || $senha === '') {
        api_response_error('Usuário e senha são obrigatórios.', 400);
    }

    if (!api_auth_login_throttle($usuario_login)) {
        api_response_error('Muitas tentativas de login. Aguarde alguns minutos e tente novamente.', 429);
    }

    // Aceita login pelo campo usuario ou pelo e-mail; mensagem de erro única
    // (não revelar se a conta existe ou está inativa).
    $usuario_escapado = banco_escape_field($usuario_login);

    $usuarios = banco_select(Array(
        'unico' => true,
        'tabela' => 'usuarios',
        'campos' => Array(
            'id_usuarios',
            'nome',
            'email',
            'usuario',
            'senha',
            'status',
            'id_usuarios_perfis',
            'id_hosts',
            'gestor_perfil',
        ),
        'extra' =>
            "WHERE (usuario='" . $usuario_escapado . "' OR email='" . $usuario_escapado . "')"
            . " AND status!='D'"
    ));

    $credenciais_validas =
        $usuarios &&
        isset($usuarios['senha']) &&
        password_verify($senha, $usuarios['senha']) &&
        ($usuarios['status'] === 'A');

    if (!$credenciais_validas) {
        api_auth_login_throttle($usuario_login, true);
        api_response_error('Usuário ou senha inválidos.', 401);
    }

    // ===== Emitir tokens OAuth2 (mesma infraestrutura validada por api_authenticate).

    gestor_incluir_biblioteca('autenticacao');
    gestor_incluir_biblioteca('oauth2');

    $tokens = oauth2_gerar_token_client_credentials(Array(
        'id_usuarios' => (int) $usuarios['id_usuarios'],
        'grant_type' => 'client_credentials',
        'scope' => 'mobile',
    ));

    if (!$tokens) {
        api_response_error('Não foi possível iniciar a sessão. Limite de sessões ativas atingido — encerre uma sessão (logout) e tente novamente.', 429);
    }

    $_GESTOR['api-auth-usuario'] = $usuarios;
    $perfil = api_auth_resolver_perfil($usuarios);

    api_response_success(Array(
        'token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'expires_in' => $tokens['expires_in'],
        'token_type' => $tokens['token_type'],
        'user' => Array(
            'id' => (int) $usuarios['id_usuarios'],
            'usuario' => $usuarios['usuario'],
            'nome' => $usuarios['nome'],
            'email' => $usuarios['email'],
            'perfil_id' => $perfil['perfil_id'],
            'perfil_nome' => $perfil['perfil_nome'],
        ),
    ), 'Login realizado com sucesso');
}

/**
 * POST api/auth/logout — revoga o access token atual (e refresh_token opcional no body).
 */
function api_auth_logout() {
    global $_GESTOR;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_response_error('Método não permitido. Use POST.', 405);
    }

    $usuario = api_auth_usuario();

    gestor_incluir_biblioteca('autenticacao');

    // ===== Abrir chave pública para extrair o pubID dos tokens a revogar.

    $keyPublicPath = $_GESTOR['openssl-path'] . 'publica.key';
    $chavePublica = file_get_contents($keyPublicPath);

    $tokens_para_revogar = Array();

    $access_token = api_auth_bearer_token();
    if ($access_token) {
        $tokens_para_revogar[] = $access_token;
    }

    $body = api_get_request_body();
    if (!empty($body['refresh_token'])) {
        $tokens_para_revogar[] = (string) $body['refresh_token'];
    }

    $revogados = 0;

    foreach ($tokens_para_revogar as $token) {
        $payload = autenticacao_validar_jwt_chave_publica(Array(
            'token' => $token,
            'chavePublica' => $chavePublica,
            'retornarPayloadCompleto' => true,
        ));

        if ($payload && isset($payload['pubID'])) {
            banco_delete(
                "oauth2_tokens",
                "WHERE pubID='" . banco_escape_field($payload['pubID']) . "'"
                . " AND id_usuarios='" . banco_escape_field($usuario['id_usuarios']) . "'"
            );
            $revogados++;
        }
    }

    api_response_success(Array('revoked' => $revogados), 'Sessão encerrada');
}

/**
 * GET api/auth/me — dados atuais do usuário do token (cold start do app).
 */
function api_auth_me() {
    $usuario = api_auth_usuario();
    $perfil = api_auth_resolver_perfil($usuario);

    api_response_success(Array(
        'user' => Array(
            'id' => (int) $usuario['id_usuarios'],
            'usuario' => $usuario['usuario'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'perfil_id' => $perfil['perfil_id'],
            'perfil_nome' => $perfil['perfil_nome'],
        ),
    ));
}

/**
 * GET api/auth/modules — módulos e grupos liberados para o perfil do usuário.
 *
 * Espelha a montagem do menu web: modulos ativos fora de nao_menu_principal,
 * filtrados pelo perfil, com grupos ordenados e o caminho da página raiz.
 * Sem cache: mudanças de permissão no painel refletem no request seguinte.
 */
function api_auth_modules() {
    global $_GESTOR;

    $usuario = api_auth_usuario();
    $perfil = api_auth_resolver_perfil($usuario);
    $language = api_auth_language();

    // ===== Páginas raiz (rota web de cada módulo).

    $paginas = banco_select(Array(
        'tabela' => 'paginas',
        'campos' => Array('modulo', 'caminho'),
        'extra' =>
            "WHERE raiz IS NOT NULL"
            . " AND status='A'"
            . " AND language='" . banco_escape_field($language) . "'"
    ));

    $caminhos = Array();
    if ($paginas) {
        foreach ($paginas as $pagina) {
            if (!isset($caminhos[$pagina['modulo']])) {
                $caminhos[$pagina['modulo']] = $pagina['caminho'];
            }
        }
    }

    // ===== Módulos ativos do menu.

    $modulos = banco_select(Array(
        'tabela' => 'modulos',
        'campos' => Array('id', 'modulo_grupo_id', 'nome', 'titulo', 'icone', 'icone2', 'plugin'),
        'extra' =>
            "WHERE nao_menu_principal IS NULL"
            . " AND status='A'"
            . " AND language='" . banco_escape_field($language) . "'"
            . " ORDER BY nome ASC"
    ));

    // ===== Plugins habilitados do host (para usuários de host).

    $hosts_plugins = false;
    if (existe($usuario['id_hosts'])) {
        $hosts_plugins = banco_select(Array(
            'tabela' => 'hosts_plugins',
            'campos' => Array('plugin', 'habilitado'),
            'extra' => "WHERE id_hosts='" . banco_escape_field($usuario['id_hosts']) . "'"
        ));
    }

    $modules_response = Array();
    $grupos_usados = Array();

    if ($modulos) {
        foreach ($modulos as $modulo) {
            // ===== RBAC: dashboard sempre; demais precisam estar no perfil.

            $liberado = ($modulo['id'] === 'dashboard');

            if (!$liberado && in_array($modulo['id'], $perfil['modulos_ids'], true)) {
                $liberado = true;
            }

            if (!$liberado) {
                continue;
            }

            // ===== Usuário de host: módulo de plugin precisa do plugin habilitado.

            if (existe($usuario['id_hosts']) && !empty($modulo['plugin'])) {
                $habilitado = false;
                if ($hosts_plugins) {
                    foreach ($hosts_plugins as $hosts_plugin) {
                        if ($hosts_plugin['plugin'] == $modulo['plugin'] && $hosts_plugin['habilitado']) {
                            $habilitado = true;
                            break;
                        }
                    }
                }
                if (!$habilitado) {
                    continue;
                }
            }

            // ===== host-configuracao exige privilégios admin verificados em sessão web; fora do app.

            if ($modulo['id'] === 'host-configuracao') {
                continue;
            }

            // ===== Sem página raiz não há rota (mesma regra do menu web).

            if (!isset($caminhos[$modulo['id']])) {
                continue;
            }

            $modules_response[] = Array(
                'id' => $modulo['id'],
                'name' => existe($modulo['titulo']) ? $modulo['titulo'] : $modulo['nome'],
                'icon' => $modulo['icone'],
                'icon2' => $modulo['icone2'],
                'group_id' => $modulo['modulo_grupo_id'],
                'web_path' => $caminhos[$modulo['id']],
            );

            if (!empty($modulo['modulo_grupo_id'])) {
                $grupos_usados[$modulo['modulo_grupo_id']] = true;
            }
        }
    }

    // ===== Grupos de módulos (apenas os usados), na ordem do menu.

    $groups_response = Array();

    $modulos_grupos = banco_select(Array(
        'tabela' => 'modulos_grupos',
        'campos' => Array('id', 'nome', 'ordemMenu'),
        'extra' => "WHERE language='" . banco_escape_field($language) . "' ORDER BY ordemMenu ASC, nome ASC"
    ));

    if ($modulos_grupos) {
        foreach ($modulos_grupos as $modulo_grupo) {
            if (isset($grupos_usados[$modulo_grupo['id']])) {
                $groups_response[] = Array(
                    'id' => $modulo_grupo['id'],
                    'name' => $modulo_grupo['nome'],
                    'order' => (int) $modulo_grupo['ordemMenu'],
                );
            }
        }
    }

    api_response_success(Array(
        'language' => $language,
        'perfil_id' => $perfil['perfil_id'],
        'groups' => $groups_response,
        'modules' => $modules_response,
    ));
}

// =========================== Roteamento do sub-controlador

/**
 * Despacha api/auth/{acao}.
 *
 * @param string|null $sub_endpoint Ação da URL (caminho[2]).
 */
function api_auth_handle($sub_endpoint) {
    switch ($sub_endpoint) {
        case 'login':
            api_auth_login();
            break;
        case 'logout':
            api_auth_logout();
            break;
        case 'me':
            api_auth_me();
            break;
        case 'modules':
            api_auth_modules();
            break;
        default:
            api_response_error('Sub-endpoint AUTH não encontrado: ' . $sub_endpoint, 404);
    }
}
