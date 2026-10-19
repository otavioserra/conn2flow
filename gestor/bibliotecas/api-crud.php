<?php
/**
 * Motor canônico de CRUD da API (REQ-283).
 *
 * Atende `/_api/{modulo-id}/...` para os módulos que habilitam o CRUD no próprio manifesto
 * (`gestor/modulos/{id}/{id}.json`, seção `api.crud`), reaproveitando a seção `tabela`:
 *
 *   GET    /_api/{modulo}/              listagem paginada (page, per_page, sort, order, search, status)
 *   GET    /_api/{modulo}/{id}          leitura por id alfanumérico ou numérico
 *   POST   /_api/{modulo}/              criação
 *   PUT    /_api/{modulo}/{id}          atualização total (obrigatórios exigidos)
 *   PATCH  /_api/{modulo}/{id}          atualização parcial
 *   DELETE /_api/{modulo}/{id}          exclusão lógica (status D) ou definitiva
 *   POST   /_api/{modulo}/{id}/status   status A/I (sem corpo, alterna)
 *   POST   /_api/{modulo}/{id}/clone    clonagem
 *
 * O CRUD é opt-in: ter `tabela` não basta. As colunas graváveis e legíveis são listadas no
 * manifesto, para que nenhuma coluna sensível (senha, segredo, token) saia ou entre pela API sem
 * decisão explícita do módulo.
 *
 * Manifesto mínimo:
 *
 *   "api": { "crud": {
 *       "campos": ["nome"],              colunas graváveis (obrigatório)
 *       "obrigatorios": ["nome"],        exigidas no POST, PUT e clone
 *       "id_origem": "nome",             coluna que gera o id alfanumérico (padrão: nome)
 *       "busca": ["nome", "id"],         colunas do parâmetro search
 *       "leitura": [...],                colunas devolvidas (padrão: ids, campos e metadados)
 *       "campo_usuario": "id_usuarios",  coluna do usuário autor (opcional)
 *       "idioma": true,                  tabela com coluna language (opcional)
 *       "exclusao": "logica",            logica | definitiva
 *       "hooks": "arquivo.php"           ciclo de vida {modulo}_api_crud_{evento} (opcional)
 *   } }
 *
 * O motor não escreve na saída: devolve o mesmo contrato do hook `api` dos módulos
 * (`code`, `mensagem`, `dados` ou `erro`, `code`, `mensagem`, `detalhes`), que o controlador
 * converte no envelope `status`, `message`, `data`, `timestamp`.
 */

const API_CRUD_POR_PAGINA_PADRAO = 20;
const API_CRUD_POR_PAGINA_MAXIMO = 100;
const API_CRUD_EVENTOS = ['before_create', 'after_create', 'before_update', 'after_update', 'before_delete', 'after_delete'];

/**
 * Texto público da API no idioma corrente (variáveis globais `api-crud-*` de
 * `gestor/resources/{idioma}/variables.json`), com os marcadores `#nome#` trocados.
 *
 * Variável ausente (banco ainda não atualizado) devolve o próprio id, nunca texto fixo.
 */
function api_crud_msg(string $id, array $troca = []): string {
    $texto = (string)gestor_variaveis(['id' => $id]);
    if ($texto === '') $texto = $id;
    foreach ($troca as $marcador => $valor) {
        $texto = str_replace('#' . $marcador . '#', (string)$valor, $texto);
    }
    return $texto;
}

/**
 * Monta a resposta de erro no contrato do hook `api`.
 */
function api_crud_erro(int $code, string $mensagem, $detalhes = null): array {
    $erro = ['erro' => true, 'code' => $code, 'mensagem' => $mensagem];
    if ($detalhes !== null) $erro['detalhes'] = $detalhes;
    return $erro;
}

/**
 * Monta a resposta de sucesso no contrato do hook `api`.
 */
function api_crud_ok($dados, string $mensagem = 'OK', int $code = 200): array {
    return ['code' => $code, 'mensagem' => $mensagem, 'dados' => $dados];
}

/**
 * Nome de tabela ou coluna aceito no SQL montado pelo motor.
 */
function api_crud_identificador_valido($nome): bool {
    return is_string($nome) && preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $nome) === 1;
}

/**
 * Lê o manifesto do módulo e devolve a configuração normalizada do CRUD.
 *
 * @param string      $modulo_id Id do módulo (já saneado pelo controlador).
 * @param string|null $plugin_id Plugin dono do módulo, ou null para o core.
 *
 * @return array|null Configuração, ou null quando o módulo não habilita o CRUD
 *                    ou o manifesto é inválido.
 */
function api_crud_config(string $modulo_id, ?string $plugin_id = null): ?array {
    global $_GESTOR;

    $dir = !empty($plugin_id)
        ? ($_GESTOR['plugins-path'] ?? '') . $plugin_id . '/modules/' . $modulo_id . '/'
        : ($_GESTOR['modulos-path'] ?? '') . $modulo_id . '/';
    $arquivo = $dir . $modulo_id . '.json';
    if (!is_file($arquivo)) return null;

    $manifesto = json_decode((string)file_get_contents($arquivo), true);
    $crud = $manifesto['api']['crud'] ?? null;
    $tabela = $manifesto['tabela'] ?? null;
    if (!is_array($manifesto) || !is_array($crud) || !is_array($tabela)) return null;

    foreach (['nome', 'id', 'id_numerico', 'status', 'versao', 'data_criacao', 'data_modificacao'] as $chave) {
        if (!api_crud_identificador_valido($tabela[$chave] ?? null)) {
            error_log('api-crud [' . $modulo_id . ']: tabela.' . $chave . ' ausente ou inválida no manifesto.');
            return null;
        }
    }

    $lista = function ($valor) {
        return array_values(array_unique(array_filter((array)$valor, 'api_crud_identificador_valido')));
    };

    $campos = $lista($crud['campos'] ?? []);
    if (!$campos) {
        error_log('api-crud [' . $modulo_id . ']: api.crud.campos vazio.');
        return null;
    }

    $reservadas = [$tabela['id'], $tabela['id_numerico'], $tabela['status'], $tabela['versao'], $tabela['data_criacao'], $tabela['data_modificacao'], 'language'];
    $campo_usuario = api_crud_identificador_valido($crud['campo_usuario'] ?? null) ? $crud['campo_usuario'] : null;
    if ($campo_usuario) $reservadas[] = $campo_usuario;

    // Colunas de controle nunca são graváveis pelo cliente: o motor as mantém.
    $campos = array_values(array_diff($campos, $reservadas));

    $idioma = !empty($crud['idioma']);
    $leitura = isset($crud['leitura'])
        ? $lista($crud['leitura'])
        : array_values(array_unique(array_merge(
            [$tabela['id_numerico'], $tabela['id']],
            $campos,
            $campo_usuario ? [$campo_usuario] : [],
            $idioma ? ['language'] : [],
            [$tabela['status'], $tabela['versao'], $tabela['data_criacao'], $tabela['data_modificacao']]
        )));
    // O id numérico e o alfanumérico precisam voltar sempre: são a chave das demais rotas.
    $leitura = array_values(array_unique(array_merge([$tabela['id_numerico'], $tabela['id']], $leitura)));

    $id_origem = $crud['id_origem'] ?? 'nome';
    if (!in_array($id_origem, $campos, true)) {
        error_log('api-crud [' . $modulo_id . ']: api.crud.id_origem precisa estar em api.crud.campos.');
        return null;
    }

    $hooks = null;
    if (!empty($crud['hooks']) && is_string($crud['hooks']) && preg_match('/^[A-Za-z0-9_\-\/]+\.php$/', $crud['hooks']) && strpos($crud['hooks'], '..') === false) {
        $hooks = $dir . $crud['hooks'];
    }

    return [
        'modulo' => $modulo_id,
        'tabela' => $tabela,
        'campos' => $campos,
        'obrigatorios' => array_values(array_intersect($lista($crud['obrigatorios'] ?? []), $campos)),
        'id_origem' => $id_origem,
        'busca' => array_values(array_intersect($lista($crud['busca'] ?? [$id_origem]), $leitura)),
        'leitura' => $leitura,
        'campo_usuario' => $campo_usuario,
        'idioma' => $idioma,
        'exclusao' => (($crud['exclusao'] ?? 'logica') === 'definitiva') ? 'definitiva' : 'logica',
        'hooks' => $hooks,
    ];
}

/**
 * Localiza o módulo ativo no cadastro e devolve a configuração do CRUD.
 */
function api_crud_modulo(string $modulo_id): ?array {
    $modulo = banco_select([
        'unico' => true,
        'tabela' => 'modulos',
        'campos' => ['id', 'plugin'],
        'extra' => "WHERE id='" . banco_escape_field($modulo_id) . "' AND status='A'",
    ]);
    if (!$modulo) return null;

    return api_crud_config($modulo_id, $modulo['plugin'] ?? null);
}

/**
 * Escopo do token: leitura aceita read, write e mobile; escrita exige write ou mobile.
 * O token `mobile` representa a sessão interativa do app, equivalente à do painel.
 */
function api_crud_escopo_permite(array $usuario, bool $escrita): bool {
    $escopos = preg_split('/[\s,]+/', strtolower(trim((string)($usuario['scope'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY);
    $aceitos = $escrita ? ['write', 'mobile'] : ['read', 'write', 'mobile'];
    return (bool)array_intersect($escopos, $aceitos);
}

/**
 * Código de idioma da requisição: parâmetro `language` válido ou o idioma corrente.
 */
function api_crud_idioma(array $parametros): string {
    global $_GESTOR;

    $pedido = (string)($parametros['language'] ?? '');
    return preg_match('/^[a-z]{2}(-[a-z]{2})?$/i', $pedido) ? strtolower($pedido) : (string)($_GESTOR['linguagem-codigo'] ?? 'pt-br');
}

/**
 * Condição de idioma para tabelas com coluna language.
 */
function api_crud_where_idioma(array $cfg, string $idioma): string {
    return $cfg['idioma'] ? " AND language='" . banco_escape_field($idioma) . "'" : '';
}

/**
 * Busca um registro não excluído pelo id alfanumérico ou, sendo só dígitos, pelo numérico.
 */
function api_crud_buscar(array $cfg, string $ref, string $idioma): ?array {
    $t = $cfg['tabela'];
    $base = " AND " . $t['status'] . "!='D'" . api_crud_where_idioma($cfg, $idioma);

    $linha = banco_select([
        'unico' => true,
        'tabela' => $t['nome'],
        'campos' => $cfg['leitura'],
        'extra' => "WHERE " . $t['id'] . "='" . banco_escape_field($ref) . "'" . $base,
    ]);
    if (!$linha && ctype_digit($ref)) {
        $linha = banco_select([
            'unico' => true,
            'tabela' => $t['nome'],
            'campos' => $cfg['leitura'],
            'extra' => "WHERE " . $t['id_numerico'] . "='" . (int)$ref . "'" . $base,
        ]);
    }

    return $linha ?: null;
}

/**
 * Lê de novo um registro pelo id numérico (inclusive excluído logicamente).
 */
function api_crud_reler(array $cfg, $id_numerico): ?array {
    $t = $cfg['tabela'];
    $linha = banco_select([
        'unico' => true,
        'tabela' => $t['nome'],
        'campos' => $cfg['leitura'],
        'extra' => "WHERE " . $t['id_numerico'] . "='" . (int)$id_numerico . "'",
    ]);
    return $linha ?: null;
}

/**
 * Valida o corpo de escrita: só colunas graváveis, valores escalares e obrigatórios preenchidos.
 *
 * @param bool $exigir_obrigatorios true no POST, PUT e clone; false no PATCH.
 *
 * @return array ['dados' => array] ou erro no contrato do hook.
 */
function api_crud_validar_corpo(array $cfg, array $corpo, bool $exigir_obrigatorios): array {
    // Chaves de controle aceitas no corpo, tratadas à parte pelo motor.
    unset($corpo['language'], $corpo[$cfg['tabela']['versao']]);

    $desconhecidos = array_values(array_diff(array_keys($corpo), $cfg['campos']));
    if ($desconhecidos) {
        return api_crud_erro(400, api_crud_msg('api-crud-campos-nao-permitidos', ['campos' => implode(', ', $desconhecidos)]), ['permitidos' => $cfg['campos']]);
    }

    $dados = [];
    foreach ($corpo as $campo => $valor) {
        if (is_bool($valor)) $valor = $valor ? '1' : '0';
        if ($valor !== null && !is_scalar($valor)) {
            return api_crud_erro(400, api_crud_msg('api-crud-valor-invalido', ['campo' => $campo]));
        }
        $dados[$campo] = $valor === null ? null : (string)$valor;
    }

    if ($exigir_obrigatorios) {
        $faltando = [];
        foreach ($cfg['obrigatorios'] as $campo) {
            if (!isset($dados[$campo]) || trim($dados[$campo]) === '') $faltando[] = $campo;
        }
        if ($faltando) return api_crud_erro(400, api_crud_msg('api-crud-obrigatorios-ausentes', ['campos' => implode(', ', $faltando)]), ['obrigatorios' => $cfg['obrigatorios']]);
    }

    foreach ($cfg['obrigatorios'] as $campo) {
        if (array_key_exists($campo, $dados) && ($dados[$campo] === null || trim($dados[$campo]) === '')) {
            return api_crud_erro(400, api_crud_msg('api-crud-obrigatorio-vazio', ['campo' => $campo]));
        }
    }

    return ['dados' => $dados];
}

/**
 * Dispara um evento do ciclo de vida declarado em `api.crud.hooks`.
 *
 * A função `{modulo_com_underscores}_api_crud_{evento}` recebe o contexto. Nos eventos before_*
 * pode devolver `['erro' => true, 'code' => ..., 'mensagem' => ...]` para abortar, ou
 * `['dados' => [...]]` para substituir os dados gravados (ainda restritos às colunas graváveis).
 *
 * @return array|null Retorno do hook, ou null quando não há hook.
 */
function api_crud_ciclo(array $cfg, string $evento, array $contexto): ?array {
    if (!$cfg['hooks'] || !in_array($evento, API_CRUD_EVENTOS, true)) return null;
    if (!is_file($cfg['hooks'])) return null;

    include_once $cfg['hooks'];
    $funcao = str_replace('-', '_', $cfg['modulo']) . '_api_crud_' . $evento;
    if (preg_match('/^\d/', $funcao)) $funcao = '_' . $funcao;
    if (!function_exists($funcao)) return null;

    $retorno = $funcao($contexto + ['modulo' => $cfg['modulo'], 'evento' => $evento]);
    return is_array($retorno) ? $retorno : null;
}

/**
 * Converte o erro devolvido por um hook before_* no contrato do hook `api`.
 */
function api_crud_recusa(array $retorno): array {
    return api_crud_erro((int)($retorno['code'] ?? 400), (string)($retorno['mensagem'] ?? api_crud_msg('api-crud-operacao-recusada')), $retorno['detalhes'] ?? null);
}

/**
 * Aplica o retorno de um hook before_*: erro aborta, `dados` substitui (filtrado).
 *
 * @return array ['dados' => array] ou erro no contrato do hook.
 */
function api_crud_aplicar_before(array $cfg, ?array $retorno, array $dados): array {
    if ($retorno === null) return ['dados' => $dados];
    if (!empty($retorno['erro'])) {
        return api_crud_recusa($retorno);
    }
    if (isset($retorno['dados']) && is_array($retorno['dados'])) {
        $dados = array_intersect_key($retorno['dados'], array_flip($cfg['campos']));
    }
    return ['dados' => $dados];
}

/**
 * Lista paginada.
 */
function api_crud_listar(array $cfg, array $parametros): array {
    $t = $cfg['tabela'];
    $idioma = api_crud_idioma($parametros);

    $pagina = max(1, (int)($parametros['page'] ?? 1));
    $por_pagina = (int)($parametros['per_page'] ?? API_CRUD_POR_PAGINA_PADRAO);
    if ($por_pagina < 1 || $por_pagina > API_CRUD_POR_PAGINA_MAXIMO) {
        return api_crud_erro(400, api_crud_msg('api-crud-per-page-invalido', ['maximo' => API_CRUD_POR_PAGINA_MAXIMO]));
    }

    $ordem_campo = (string)($parametros['sort'] ?? $t['id_numerico']);
    if (!in_array($ordem_campo, $cfg['leitura'], true)) {
        return api_crud_erro(400, api_crud_msg('api-crud-sort-invalido', ['campo' => $ordem_campo]), ['permitidos' => $cfg['leitura']]);
    }
    $ordem = strtolower((string)($parametros['order'] ?? 'asc'));
    if (!in_array($ordem, ['asc', 'desc'], true)) return api_crud_erro(400, api_crud_msg('api-crud-order-invalido'));

    $where = "WHERE " . $t['status'] . "!='D'" . api_crud_where_idioma($cfg, $idioma);

    if (isset($parametros['status']) && $parametros['status'] !== '') {
        $status = strtoupper((string)$parametros['status']);
        if (!in_array($status, ['A', 'I'], true)) return api_crud_erro(400, api_crud_msg('api-crud-status-invalido'));
        $where .= " AND " . $t['status'] . "='" . $status . "'";
    }

    $busca = trim((string)($parametros['search'] ?? ''));
    if ($busca !== '' && $cfg['busca']) {
        $termo = addcslashes(banco_escape_field($busca), '%_');
        $partes = [];
        foreach ($cfg['busca'] as $coluna) $partes[] = $coluna . " LIKE '%" . $termo . "%'";
        $where .= " AND (" . implode(' OR ', $partes) . ")";
    }

    $contagem = banco_select(['unico' => true, 'tabela' => $t['nome'], 'campos' => ['COUNT(*)'], 'extra' => $where]);
    $total = (int)($contagem['COUNT(*)'] ?? 0);

    $itens = $total > 0 ? banco_select([
        'tabela' => $t['nome'],
        'campos' => $cfg['leitura'],
        'extra' => $where . " ORDER BY " . $ordem_campo . " " . strtoupper($ordem) . " LIMIT " . $por_pagina . " OFFSET " . (($pagina - 1) * $por_pagina),
    ]) : [];

    return api_crud_ok([
        'items' => $itens ?: [],
        'pagination' => [
            'page' => $pagina,
            'per_page' => $por_pagina,
            'total' => $total,
            'total_pages' => (int)ceil($total / $por_pagina),
        ],
    ]);
}

/**
 * Insere um registro com id gerado a partir de `id_origem` e devolve a linha gravada.
 */
function api_crud_inserir(array $cfg, array $dados, array $usuario, string $idioma): ?array {
    $t = $cfg['tabela'];

    $id = banco_identificador([
        'id' => banco_escape_field((string)$dados[$cfg['id_origem']]),
        'tabela' => [
            'nome' => $t['nome'],
            'campo' => $t['id'],
            'id_nome' => $t['id_numerico'],
            'status' => $t['status'],
        ] + ($cfg['idioma'] ? ['where' => "language='" . banco_escape_field($idioma) . "'"] : []),
    ]);

    $campos = [];
    foreach ($dados as $campo => $valor) {
        $campos[] = $valor === null ? [$campo, 'NULL', true] : [$campo, banco_escape_field($valor)];
    }
    $campos[] = [$t['id'], banco_escape_field($id)];
    if ($cfg['campo_usuario']) $campos[] = [$cfg['campo_usuario'], (int)$usuario['id_usuarios'], true];
    if ($cfg['idioma']) $campos[] = ['language', banco_escape_field($idioma)];
    $campos[] = [$t['status'], 'A'];
    $campos[] = [$t['versao'], '1', true];
    $campos[] = [$t['data_criacao'], 'NOW()', true];
    $campos[] = [$t['data_modificacao'], 'NOW()', true];

    // A inserção só vale comprovada: query aceita e uma linha gravada. Sem isso o último id do
    // driver pode ser de uma inserção anterior e o motor devolveria outro registro.
    if (banco_insert_name($campos, $t['nome']) === false || banco_linhas_afetadas() === 0) {
        error_log('api-crud [' . $cfg['modulo'] . ']: falha no INSERT em ' . $t['nome'] . '.');
        return null;
    }

    $id_numerico = banco_last_id();
    $linha = $id_numerico ? api_crud_reler($cfg, $id_numerico) : null;
    if ($linha && (string)$linha[$t['id']] === (string)$id) return $linha;

    return api_crud_buscar($cfg, $id, $idioma);
}

/**
 * Criação (POST na coleção).
 */
function api_crud_criar(array $cfg, array $corpo, array $usuario, string $idioma): array {
    $validacao = api_crud_validar_corpo($cfg, $corpo, true);
    if (!empty($validacao['erro'])) return $validacao;

    $antes = api_crud_aplicar_before($cfg, api_crud_ciclo($cfg, 'before_create', ['dados' => $validacao['dados'], 'usuario' => $usuario]), $validacao['dados']);
    if (!empty($antes['erro'])) return $antes;

    $registro = api_crud_inserir($cfg, $antes['dados'], $usuario, $idioma);
    if (!$registro) return api_crud_erro(500, api_crud_msg('api-crud-falha-gravacao'));

    api_crud_ciclo($cfg, 'after_create', ['registro' => $registro, 'usuario' => $usuario]);
    return api_crud_ok($registro, api_crud_msg('api-crud-registro-criado'), 201);
}

/**
 * Versão esperada pelo cliente (campo de versão no corpo ou cabeçalho If-Match), ou null.
 */
function api_crud_versao_esperada(array $cfg, array $corpo, ?string $if_match) {
    $versao = $corpo[$cfg['tabela']['versao']] ?? null;
    if (($versao === null || $versao === '') && $if_match !== null && $if_match !== '') {
        $versao = trim($if_match, " \t\"W/");
    }
    if ($versao === null || $versao === '') return null;
    return ctype_digit((string)$versao) ? (int)$versao : false;
}

/**
 * Grava colunas num registro existente, incrementa a versão e devolve a linha atualizada.
 *
 * @param array    $sets     Colunas => valor (null grava NULL).
 * @param int|null $esperada Versão exigida; null dispensa a checagem.
 *
 * O resultado é decidido pelo próprio UPDATE condicional, não por uma releitura: falha da query
 * é erro de servidor (500); zero linhas afetadas é conflito (409) — versão vencida por outra
 * escrita ou registro removido. Sem contador de linhas (modo distribuído), a versão relida decide.
 *
 * @return array ['registro' => linha atualizada] ou erro no contrato do hook.
 */
function api_crud_gravar(array $cfg, array $registro, array $sets, ?int $esperada): array {
    $t = $cfg['tabela'];

    $partes = [];
    foreach ($sets as $campo => $valor) {
        $partes[] = $campo . "=" . ($valor === null ? 'NULL' : "'" . banco_escape_field($valor) . "'");
    }
    // A versão sempre muda, então a linha encontrada sempre conta como afetada no MySQL.
    $partes[] = $t['versao'] . "=" . $t['versao'] . "+1";
    $partes[] = $t['data_modificacao'] . "=NOW()";

    $where = "WHERE " . $t['id_numerico'] . "='" . (int)$registro[$t['id_numerico']] . "'";
    if ($esperada !== null) $where .= " AND " . $t['versao'] . "='" . $esperada . "'";

    if (banco_update(implode(',', $partes), $t['nome'], $where) === false) {
        error_log('api-crud [' . $cfg['modulo'] . ']: falha no UPDATE em ' . $t['nome'] . '.');
        return api_crud_erro(500, api_crud_msg('api-crud-falha-gravacao'));
    }
    $afetadas = banco_linhas_afetadas();

    $depois = api_crud_reler($cfg, $registro[$t['id_numerico']]);
    $perdeu = $afetadas === 0 || !$depois
        || ($afetadas === null && $esperada !== null && (int)$depois[$t['versao']] !== $esperada + 1);

    if ($perdeu) {
        if ($esperada !== null) {
            return api_crud_erro(409, api_crud_msg('api-crud-conflito-versao'), $depois ? ['versao_atual' => (int)$depois[$t['versao']]] : null);
        }
        return api_crud_erro(409, api_crud_msg('api-crud-conflito-registro'));
    }

    return ['registro' => $depois];
}

/**
 * Atualização (PUT total ou PATCH parcial).
 */
function api_crud_atualizar(array $cfg, array $registro, array $corpo, array $usuario, bool $total, ?string $if_match): array {
    $t = $cfg['tabela'];

    $esperada = api_crud_versao_esperada($cfg, $corpo, $if_match);
    if ($esperada === false) return api_crud_erro(400, api_crud_msg('api-crud-versao-invalida'));
    if ($esperada !== null && (int)$registro[$t['versao']] !== $esperada) {
        return api_crud_erro(409, api_crud_msg('api-crud-conflito-versao'), ['versao_atual' => (int)$registro[$t['versao']]]);
    }

    $validacao = api_crud_validar_corpo($cfg, $corpo, $total);
    if (!empty($validacao['erro'])) return $validacao;
    if (!$validacao['dados']) return api_crud_erro(400, api_crud_msg('api-crud-nenhum-campo'), ['permitidos' => $cfg['campos']]);

    $antes = api_crud_aplicar_before($cfg, api_crud_ciclo($cfg, 'before_update', ['dados' => $validacao['dados'], 'registro' => $registro, 'usuario' => $usuario]), $validacao['dados']);
    if (!empty($antes['erro'])) return $antes;

    $gravacao = api_crud_gravar($cfg, $registro, $antes['dados'], $esperada);
    if (!empty($gravacao['erro'])) return $gravacao;

    api_crud_ciclo($cfg, 'after_update', ['registro' => $gravacao['registro'], 'anterior' => $registro, 'usuario' => $usuario]);
    return api_crud_ok($gravacao['registro'], api_crud_msg('api-crud-registro-atualizado'));
}

/**
 * Exclusão lógica (status D) ou definitiva, conforme o manifesto.
 */
function api_crud_excluir(array $cfg, array $registro, array $usuario): array {
    $t = $cfg['tabela'];

    $antes = api_crud_ciclo($cfg, 'before_delete', ['registro' => $registro, 'usuario' => $usuario]);
    if ($antes !== null && !empty($antes['erro'])) return api_crud_recusa($antes);

    if ($cfg['exclusao'] === 'definitiva') {
        if (banco_delete($t['nome'], "WHERE " . $t['id_numerico'] . "='" . (int)$registro[$t['id_numerico']] . "'") === false) {
            error_log('api-crud [' . $cfg['modulo'] . ']: falha no DELETE em ' . $t['nome'] . '.');
            return api_crud_erro(500, api_crud_msg('api-crud-falha-gravacao'));
        }
        if (banco_linhas_afetadas() === 0) return api_crud_erro(409, api_crud_msg('api-crud-conflito-registro'));
    } else {
        $gravacao = api_crud_gravar($cfg, $registro, [$t['status'] => 'D'], null);
        if (!empty($gravacao['erro'])) return $gravacao;
    }

    api_crud_ciclo($cfg, 'after_delete', ['registro' => $registro, 'usuario' => $usuario, 'exclusao' => $cfg['exclusao']]);
    return api_crud_ok([
        $t['id'] => $registro[$t['id']],
        $t['id_numerico'] => $registro[$t['id_numerico']],
        'exclusao' => $cfg['exclusao'],
    ], api_crud_msg('api-crud-registro-excluido'));
}

/**
 * Status A/I; sem `status` no corpo, alterna.
 */
function api_crud_status(array $cfg, array $registro, array $corpo, array $usuario): array {
    $t = $cfg['tabela'];

    $atual = (string)$registro[$t['status']];
    $novo = isset($corpo['status']) ? strtoupper((string)$corpo['status']) : ($atual === 'A' ? 'I' : 'A');
    if (!in_array($novo, ['A', 'I'], true)) return api_crud_erro(400, api_crud_msg('api-crud-status-invalido'));

    $antes = api_crud_ciclo($cfg, 'before_update', ['dados' => [$t['status'] => $novo], 'registro' => $registro, 'usuario' => $usuario]);
    if ($antes !== null && !empty($antes['erro'])) return api_crud_recusa($antes);

    $gravacao = api_crud_gravar($cfg, $registro, [$t['status'] => $novo], null);
    if (!empty($gravacao['erro'])) return $gravacao;

    api_crud_ciclo($cfg, 'after_update', ['registro' => $gravacao['registro'], 'anterior' => $registro, 'usuario' => $usuario]);
    return api_crud_ok($gravacao['registro'], api_crud_msg('api-crud-status-atualizado'));
}

/**
 * Clonagem: copia as colunas graváveis, sobrepõe o corpo e cria um registro novo.
 */
function api_crud_clonar(array $cfg, array $registro, array $corpo, array $usuario, string $idioma): array {
    $t = $cfg['tabela'];

    // A origem é lida com todas as colunas graváveis, inclusive as que não são devolvidas na leitura.
    $origem = banco_select([
        'unico' => true,
        'tabela' => $t['nome'],
        'campos' => $cfg['campos'],
        'extra' => "WHERE " . $t['id_numerico'] . "='" . (int)$registro[$t['id_numerico']] . "'",
    ]) ?: [];

    $validacao = api_crud_validar_corpo($cfg, $corpo, false);
    if (!empty($validacao['erro'])) return $validacao;

    $dados = array_merge($origem, $validacao['dados']);
    $validacao = api_crud_validar_corpo($cfg, $dados, true);
    if (!empty($validacao['erro'])) return $validacao;

    $antes = api_crud_aplicar_before($cfg, api_crud_ciclo($cfg, 'before_create', ['dados' => $validacao['dados'], 'origem' => $registro, 'usuario' => $usuario]), $validacao['dados']);
    if (!empty($antes['erro'])) return $antes;

    $novo = api_crud_inserir($cfg, $antes['dados'], $usuario, $idioma);
    if (!$novo) return api_crud_erro(500, api_crud_msg('api-crud-falha-gravacao'));

    api_crud_ciclo($cfg, 'after_create', ['registro' => $novo, 'origem' => $registro, 'usuario' => $usuario]);
    return api_crud_ok($novo, api_crud_msg('api-crud-registro-clonado'), 201);
}

/**
 * Ponto de entrada do motor.
 *
 * @param string      $modulo_id  Id do módulo da URL (saneado).
 * @param string      $acao       Restante do caminho depois do módulo (ex.: '', 'abc', 'abc/status').
 * @param string      $metodo     Método HTTP.
 * @param array       $parametros Query string.
 * @param array       $corpo      Corpo JSON decodificado.
 * @param array       $usuario    Retorno da validação do token (id_usuarios, id_usuarios_perfis, scope).
 * @param string|null $if_match   Cabeçalho If-Match, quando houver.
 *
 * @return array|null Resultado no contrato do hook `api`, ou null quando o módulo não habilita o CRUD.
 */
function api_crud_despachar(string $modulo_id, string $acao, string $metodo, array $parametros, array $corpo, array $usuario, ?string $if_match = null): ?array {
    $cfg = api_crud_modulo($modulo_id);
    if (!$cfg) return null;

    $metodo = strtoupper($metodo);
    $segmentos = array_values(array_filter(explode('/', $acao), 'strlen'));
    $ref = $segmentos[0] ?? null;
    $sub = $segmentos[1] ?? null;
    if (count($segmentos) > 2 || ($sub !== null && !in_array($sub, ['status', 'clone'], true))) {
        return api_crud_erro(404, api_crud_msg('api-crud-rota-nao-encontrada', ['rota' => $acao]));
    }

    // Operação no vocabulário do painel: sem operação cadastrada, vale o acesso ao módulo.
    if ($ref === null) {
        $operacoes = ['GET' => 'listar', 'POST' => 'adicionar'];
    } elseif ($sub === null) {
        $operacoes = ['GET' => 'visualizar', 'PUT' => 'editar', 'PATCH' => 'editar', 'DELETE' => 'excluir'];
    } else {
        $operacoes = ['POST' => $sub === 'status' ? 'status' : 'clonar'];
    }
    if (!isset($operacoes[$metodo])) {
        return api_crud_erro(405, api_crud_msg('api-crud-metodo-nao-permitido', ['metodos' => implode(', ', array_keys($operacoes))]));
    }
    $operacao = $operacoes[$metodo];

    if (!api_crud_escopo_permite($usuario, $metodo !== 'GET')) {
        return api_crud_erro(403, api_crud_msg('api-crud-escopo-insuficiente'), ['necessario' => $metodo === 'GET' ? 'read' : 'write']);
    }
    // O acesso base ao módulo (usuarios_perfis_modulos) é exigido à parte: gestor_acesso() com
    // operação cadastrada aceita a permissão da operação mesmo sem o vínculo do perfil ao módulo,
    // e uma permissão residual não pode sobreviver à retirada do acesso ao módulo.
    if (!gestor_acesso(false, $modulo_id, $usuario)) {
        return api_crud_erro(403, api_crud_msg('api-crud-perfil-sem-modulo', ['modulo' => $modulo_id]));
    }
    if (!gestor_acesso($operacao, $modulo_id, $usuario)) {
        return api_crud_erro(403, api_crud_msg('api-crud-perfil-sem-permissao', ['operacao' => $operacao, 'modulo' => $modulo_id]));
    }

    $idioma = api_crud_idioma($parametros + $corpo);

    if ($ref === null) {
        return $metodo === 'GET' ? api_crud_listar($cfg, $parametros) : api_crud_criar($cfg, $corpo, $usuario, $idioma);
    }

    $registro = api_crud_buscar($cfg, $ref, $idioma);
    if (!$registro) return api_crud_erro(404, api_crud_msg('api-crud-registro-nao-encontrado', ['ref' => $ref]));

    switch ($operacao) {
        case 'visualizar': return api_crud_ok($registro);
        case 'editar': return api_crud_atualizar($cfg, $registro, $corpo, $usuario, $metodo === 'PUT', $if_match);
        case 'excluir': return api_crud_excluir($cfg, $registro, $usuario);
        case 'status': return api_crud_status($cfg, $registro, $corpo, $usuario);
        default: return api_crud_clonar($cfg, $registro, $corpo, $usuario, $idioma);
    }
}
