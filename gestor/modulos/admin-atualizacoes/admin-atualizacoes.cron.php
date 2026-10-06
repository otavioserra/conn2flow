<?php
/**
 * Rotina automática do módulo admin-atualizacoes — req-245 / BATCH-254.
 *
 * Declarada na chave `cron` do manifesto e executada pela engine de rotinas (`gestor/cron.php`) ou pelo
 * "Disparar agora" do painel `admin-cron`. Este arquivo não carrega o controlador do módulo: só as
 * bibliotecas puras da atualização. As mesmas funções servem à aba Automático da tela.
 */

/** Carrega as bibliotecas da atualização automática. */
function admin_atualizacoes_auto_bibliotecas(): void {
    global $_GESTOR;
    foreach (['atualizacoes-automatica', 'atualizacoes-execucao', 'deploy-lock'] as $biblioteca) {
        require_once $_GESTOR['bibliotecas-path'] . $biblioteca . '.php';
    }
}

/** Pasta da instalação onde moram o `.env` e a configuração da atualização automática. */
function admin_atualizacoes_auto_pasta(): string {
    global $_GESTOR;

    return (string)$_GESTOR['AUTH_PATH_SERVER'];
}

/** Domínio da instalação: o da engine de rotinas, o da requisição ou o nome da pasta do host. */
function admin_atualizacoes_auto_dominio(): string {
    global $_CRON;
    $dominio = (string)($_CRON['SERVER_NAME'] ?? ($_SERVER['SERVER_NAME'] ?? ''));

    return $dominio !== '' ? $dominio : basename(rtrim(admin_atualizacoes_auto_pasta(), '/\\'));
}

/** Choques de atualização à espera de decisão humana. */
function admin_atualizacoes_auto_choques_pendentes(): int {
    if (!function_exists('banco_query')) return 0;
    $existe = @banco_query("SHOW TABLES LIKE 'atualizacoes_choques'");
    if (!$existe || !banco_num_rows($existe)) return 0;
    $res = @banco_query("SELECT COUNT(*) AS total FROM atualizacoes_choques WHERE resolucao IS NULL OR resolucao=''");
    $linha = $res ? banco_fetch_assoc($res) : null;

    return (int)($linha['total'] ?? 0);
}

/** Há outro deploy segurando a trava deste ambiente? */
function admin_atualizacoes_auto_trava_ocupada(): bool {
    global $_GESTOR;
    $trava = deploy_lock_read(rtrim((string)$_GESTOR['ROOT_PATH'], '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'deploy.lock');

    return $trava !== null && !deploy_lock_expired($trava);
}

/**
 * Lê o resultado da execução pendente, se houver, e o aplica ao estado (grava quando muda).
 *
 * @return array A configuração já atualizada.
 */
function admin_atualizacoes_auto_resolver_pendente(array $config): array {
    global $_GESTOR;
    $pendente = $config['estado']['pendente'] ?? null;
    if (!$pendente) return $config;
    $pasta = atualizacoes_execucao_pasta((string)$_GESTOR['ROOT_PATH']);
    $id = (string)$pendente['run'];
    if (!atualizacoes_execucao_id_valido($id) || !is_file($pasta . $id . '.json')) {
        // A execução sumiu (pasta temporária limpa): não há como saber o resultado; a versão não é recusada.
        $status = 'locked';
    } else {
        $log = is_file($pasta . $id . '.log') ? (string)file_get_contents($pasta . $id . '.log') : '';
        $exit = is_file($pasta . $id . '.exit') ? (string)file_get_contents($pasta . $id . '.exit') : null;
        $status = atualizacoes_execucao_estado($log, $exit)['status'];
    }
    $novo = atualizacao_automatica_aplicar_resultado($config, $status, time());
    if ($novo !== $config) atualizacao_automatica_gravar(admin_atualizacoes_auto_pasta(), $novo);

    return $novo;
}

/**
 * Confere a versão publicada e registra a checagem no estado.
 *
 * @return array ['config' => array, 'consulta' => array]
 */
function admin_atualizacoes_auto_conferir(array $config): array {
    $consulta = atualizacao_automatica_consultar();
    $config['estado']['ultima_checagem'] = time();
    if ($consulta['ok']) $config['estado']['versao_encontrada'] = $consulta['tag'];
    $config['estado']['motivo'] = $consulta['ok'] ? null : 'consulta-falhou';
    atualizacao_automatica_gravar(admin_atualizacoes_auto_pasta(), $config);

    return ['config' => $config, 'consulta' => $consulta];
}

/**
 * Callback da tarefa `admin-atualizacoes-automatica` (frequência horária).
 *
 * A cada hora: resolve a execução pendente; na hora preferida e com a checagem vencida, confere a versão
 * publicada; havendo versão nova e nada que impeça, dispara o atualizador em segundo plano, modo completo,
 * com verificação e volta automática. O resultado é lido numa hora seguinte.
 *
 * @param array $parametros Não usados: a configuração vem do arquivo da instalação.
 * @return array ['status' => 'sucesso|aviso|erro', 'log' => string]
 */
function admin_atualizacoes_cron_automatica($parametros = Array()) {
    global $_GESTOR;
    admin_atualizacoes_auto_bibliotecas();
    $pasta = admin_atualizacoes_auto_pasta();
    $config = atualizacao_automatica_ler($pasta);
    if (empty($config['ativo'])) return Array('status' => 'sucesso', 'log' => 'Atualizacao automatica desligada.');

    $log = Array();
    if (!empty($config['estado']['pendente'])) {
        $tag = $config['estado']['pendente']['tag'];
        $config = admin_atualizacoes_auto_resolver_pendente($config);
        if (!empty($config['estado']['pendente'])) return Array('status' => 'sucesso', 'log' => 'Atualizacao para ' . $tag . ' em andamento.');
        $resultado = (string)($config['estado']['ultima_tentativa']['resultado'] ?? '?');
        $log[] = 'Resultado da atualizacao para ' . $tag . ': ' . $resultado . '.';
        if ($resultado !== 'success') return Array('status' => 'aviso', 'log' => implode(' ', $log) . ' A versao nao sera tentada de novo sem liberacao.');
    }

    $quando = atualizacao_automatica_deve_conferir($config, time());
    if (!$quando['conferir']) return Array('status' => 'sucesso', 'log' => trim(implode(' ', $log) . ' Sem checagem agora: ' . $quando['motivo'] . '.'));

    $conferida = admin_atualizacoes_auto_conferir($config);
    $config = $conferida['config'];
    // Só a checagem da rotina conta para o vencimento do período; consulta que falhou é repetida na hora seguinte.
    if ($conferida['consulta']['ok']) {
        $config['estado']['ultima_automatica'] = time();
        atualizacao_automatica_gravar($pasta, $config);
    }
    if (!$conferida['consulta']['ok']) return Array('status' => 'aviso', 'log' => 'Nao foi possivel consultar as versoes publicadas: ' . $conferida['consulta']['erro'] . '.');

    $decisao = atualizacao_automatica_decidir($config, Array(
        'instalada' => (string)($_GESTOR['versao'] ?? ''),
        'publicada' => $conferida['consulta']['tag'],
        'choques_pendentes' => admin_atualizacoes_auto_choques_pendentes(),
        'trava_ocupada' => admin_atualizacoes_auto_trava_ocupada(),
    ));
    $config['estado']['motivo'] = $decisao['motivo'];
    if (!$decisao['atualizar']) {
        atualizacao_automatica_gravar($pasta, $config);
        $impedida = in_array($decisao['motivo'], Array('choque-pendente', 'trava-ocupada', 'versao-recusada'), true);

        return Array('status' => $impedida ? 'aviso' : 'sucesso', 'log' => 'Versao publicada: ' . ($decisao['tag'] ?? 'nenhuma') . '. Nada a atualizar: ' . $decisao['motivo'] . '.');
    }

    $argv = atualizacoes_execucao_argv(atualizacao_automatica_opcoes($config, $decisao['tag']), admin_atualizacoes_auto_dominio());
    if ($argv['recusadas']) return Array('status' => 'erro', 'log' => 'Opcoes recusadas pelo atualizador: ' . implode(', ', $argv['recusadas']) . '.');
    $id = atualizacoes_execucao_novo_id();
    $php = atualizacoes_execucao_php_cli((string)($_ENV['ATUALIZACOES_PHP_CLI'] ?? ''));
    $disparo = atualizacoes_execucao_disparar((string)$_GESTOR['ROOT_PATH'], $id, $argv['argv'], Array('quem' => 'automatica', 'opcoes' => atualizacao_automatica_opcoes($config, $decisao['tag']), 'php' => $php), $php);
    if (!$disparo['ok']) {
        atualizacao_automatica_gravar($pasta, $config);

        return Array('status' => 'erro', 'log' => 'Nao foi possivel disparar a atualizacao para ' . $decisao['tag'] . ': ' . $disparo['erro'] . '.');
    }
    $config['estado']['pendente'] = Array('run' => $id, 'tag' => $decisao['tag'], 'quando' => time());
    atualizacao_automatica_gravar($pasta, $config);

    return Array('status' => 'sucesso', 'log' => 'Atualizacao para ' . $decisao['tag'] . ' disparada em segundo plano (' . $id . '), com verificacao e volta automatica.');
}
