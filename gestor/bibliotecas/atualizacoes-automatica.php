<?php
/**
 * Atualização automática do sistema — req-245 / BATCH-254.
 *
 * Biblioteca pura (sem Gestor): configuração e estado por instalação, comparação de versões, vencimento da
 * checagem e a decisão do ciclo. Quem agenda é a tarefa do módulo `admin-atualizacoes`
 * (`admin-atualizacoes.cron.php`); quem atualiza é o mesmo atualizador do CLI e da API, disparado em segundo
 * plano por `atualizacoes-execucao.php` (trava, snapshot, verificação e volta automática).
 *
 * A configuração e o estado moram em `autenticacoes/<domínio>/atualizacao-automatica.json`: fora da área
 * pública, fora do pacote da atualização e por instalação.
 */

/** Períodos de checagem oferecidos na tela, em dias. */
const ATUALIZACAO_AUTOMATICA_PERIODOS = ['diario' => 1, 'semanal' => 7, 'mensal' => 30];

/**
 * Opções que a automação passa ao atualizador. Lista fechada no código: nada do arquivo de configuração
 * entra na linha de comando além do que está aqui (`backup` é a única escolha do administrador).
 */
const ATUALIZACAO_AUTOMATICA_OPCOES_FIXAS = ['tag', 'backup'];

/** Configuração e estado iniciais. */
function atualizacao_automatica_padrao(): array {
    return [
        'ativo' => false,
        'periodo' => 'semanal',
        'hora' => 3,
        'backup' => true,
        'estado' => [
            'ultima_checagem' => null,
            'ultima_automatica' => null,
            'versao_encontrada' => null,
            'motivo' => null,
            'pendente' => null,
            'ultima_tentativa' => null,
            'recusadas' => [],
        ],
    ];
}

/** Caminho do arquivo da instalação. */
function atualizacao_automatica_arquivo(string $pastaDoHost): string {
    return rtrim($pastaDoHost, '/\\') . DIRECTORY_SEPARATOR . 'atualizacao-automatica.json';
}

/**
 * Normaliza o que veio do arquivo ou do formulário: tipos, período conhecido, hora de 0 a 23 e lista de
 * recusadas só com tags bem formadas. Chave desconhecida é descartada.
 */
function atualizacao_automatica_normalizar(array $dados): array {
    $padrao = atualizacao_automatica_padrao();
    $estado = is_array($dados['estado'] ?? null) ? $dados['estado'] : [];
    $hora = filter_var($dados['hora'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 23]]);
    $recusadas = [];
    foreach ((array)($estado['recusadas'] ?? []) as $tag => $info) {
        if (!is_string($tag) || !atualizacao_automatica_tag_valida($tag)) continue;
        $info = is_array($info) ? $info : [];
        $recusadas[$tag] = ['quando' => (int)($info['quando'] ?? 0), 'motivo' => substr((string)($info['motivo'] ?? ''), 0, 200)];
    }
    $pendente = is_array($estado['pendente'] ?? null) ? $estado['pendente'] : null;
    if ($pendente && (!is_string($pendente['run'] ?? null) || !atualizacao_automatica_tag_valida((string)($pendente['tag'] ?? '')))) $pendente = null;
    $tentativa = is_array($estado['ultima_tentativa'] ?? null) ? $estado['ultima_tentativa'] : null;
    $encontrada = (string)($estado['versao_encontrada'] ?? '');

    return [
        'ativo' => filter_var($dados['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'periodo' => isset(ATUALIZACAO_AUTOMATICA_PERIODOS[(string)($dados['periodo'] ?? '')]) ? (string)$dados['periodo'] : $padrao['periodo'],
        'hora' => $hora === false ? $padrao['hora'] : $hora,
        'backup' => array_key_exists('backup', $dados) ? filter_var($dados['backup'], FILTER_VALIDATE_BOOLEAN) : $padrao['backup'],
        'estado' => [
            'ultima_checagem' => isset($estado['ultima_checagem']) ? (int)$estado['ultima_checagem'] : null,
            // Checagem feita pela rotina: é ela que conta para o vencimento. A manual ("Verificar agora") só informa.
            'ultima_automatica' => isset($estado['ultima_automatica']) ? (int)$estado['ultima_automatica'] : null,
            'versao_encontrada' => atualizacao_automatica_tag_valida($encontrada) ? $encontrada : null,
            'motivo' => isset($estado['motivo']) ? substr((string)$estado['motivo'], 0, 60) : null,
            'pendente' => $pendente ? ['run' => (string)$pendente['run'], 'tag' => (string)$pendente['tag'], 'quando' => (int)($pendente['quando'] ?? 0)] : null,
            'ultima_tentativa' => $tentativa ? [
                'tag' => substr((string)($tentativa['tag'] ?? ''), 0, 80),
                'quando' => (int)($tentativa['quando'] ?? 0),
                'resultado' => substr((string)($tentativa['resultado'] ?? ''), 0, 40),
            ] : null,
            'recusadas' => $recusadas,
        ],
    ];
}

/** Lê a configuração da instalação; arquivo ausente ou ilegível devolve o padrão (desligado). */
function atualizacao_automatica_ler(string $pastaDoHost): array {
    $arquivo = atualizacao_automatica_arquivo($pastaDoHost);
    $dados = is_file($arquivo) ? json_decode((string)@file_get_contents($arquivo), true) : null;

    return atualizacao_automatica_normalizar(is_array($dados) ? $dados : []);
}

/** Grava a configuração (escrita em arquivo temporário e troca, para não deixar o arquivo pela metade). */
function atualizacao_automatica_gravar(string $pastaDoHost, array $dados): bool {
    $arquivo = atualizacao_automatica_arquivo($pastaDoHost);
    $json = json_encode(atualizacao_automatica_normalizar($dados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $temporario = $arquivo . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($temporario, $json, LOCK_EX) === false) return false;
    if (!@rename($temporario, $arquivo)) { @unlink($temporario); return false; }

    return true;
}

/** Tag de release do sistema (`gestor-v1.2.3`). */
function atualizacao_automatica_tag_valida(string $tag): bool {
    return (bool)preg_match('/^gestor-v\d+(\.\d+){1,3}$/', $tag);
}

/** Número da versão a partir da tag ou do texto da versão instalada (`gestor-v2.10.13`, `v2.10.13`, `2.10.13`). */
function atualizacao_automatica_numero(string $versao): ?string {
    return preg_match('/(\d+(?:\.\d+){1,3})/', $versao, $m) ? $m[1] : null;
}

/** A tag publicada é mais nova que a versão instalada? Versão ilegível nunca é "mais nova". */
function atualizacao_automatica_mais_nova(string $tag, string $instalada): bool {
    $nova = atualizacao_automatica_numero($tag);
    $atual = atualizacao_automatica_numero($instalada);

    return $nova !== null && $atual !== null && version_compare($nova, $atual, '>');
}

/**
 * Versão estável mais recente numa resposta da API de releases do GitHub: a de maior número entre as tags
 * `gestor-v…` que não são rascunho nem pré-lançamento.
 */
function atualizacao_automatica_ultima_estavel(array $releases): ?string {
    $melhor = null;
    foreach ($releases as $release) {
        if (!is_array($release) || !empty($release['draft']) || !empty($release['prerelease'])) continue;
        $tag = (string)($release['tag_name'] ?? '');
        if (!atualizacao_automatica_tag_valida($tag)) continue;
        if ($melhor === null || version_compare((string)atualizacao_automatica_numero($tag), (string)atualizacao_automatica_numero($melhor), '>')) $melhor = $tag;
    }

    return $melhor;
}

/**
 * A checagem está vencida? Uma folga de duas horas absorve o atraso da própria tarefa: sem ela, uma checagem
 * diária feita às 03:05 nunca estaria vencida às 03:00 do dia seguinte.
 */
function atualizacao_automatica_vencida(array $config, int $agora): bool {
    $ultima = $config['estado']['ultima_automatica'] ?? null;
    if (!$ultima) return true;
    $dias = ATUALIZACAO_AUTOMATICA_PERIODOS[$config['periodo']] ?? 7;

    return ($agora - (int)$ultima) >= ($dias * 86400 - 7200);
}

/** Momento da próxima checagem (para a tela): a hora preferida, no primeiro dia em que estiver vencida. */
function atualizacao_automatica_proxima(array $config, int $agora): ?int {
    if (empty($config['ativo'])) return null;
    $ultima = $config['estado']['ultima_automatica'] ?? null;
    $dias = ATUALIZACAO_AUTOMATICA_PERIODOS[$config['periodo']] ?? 7;
    $base = $ultima ? max($agora, (int)$ultima + $dias * 86400 - 7200) : $agora;
    $alvo = mktime((int)$config['hora'], 0, 0, (int)date('n', $base), (int)date('j', $base), (int)date('Y', $base));
    if ($alvo + 3599 < $base) $alvo += 86400;

    return $alvo;
}

/**
 * Deve a tarefa conferir a versão publicada agora? Devolve o motivo quando não.
 *
 * @return array ['conferir' => bool, 'motivo' => string]
 */
function atualizacao_automatica_deve_conferir(array $config, int $agora): array {
    if (empty($config['ativo'])) return ['conferir' => false, 'motivo' => 'desligada'];
    if (!empty($config['estado']['pendente'])) return ['conferir' => false, 'motivo' => 'execucao-pendente'];
    if ((int)date('G', $agora) !== (int)$config['hora']) return ['conferir' => false, 'motivo' => 'fora-da-hora'];
    if (!atualizacao_automatica_vencida($config, $agora)) return ['conferir' => false, 'motivo' => 'em-dia'];

    return ['conferir' => true, 'motivo' => 'vencida'];
}

/**
 * Decisão do ciclo depois de conferida a versão publicada.
 *
 * @param array $contexto ['instalada' => string, 'publicada' => ?string, 'choques_pendentes' => int, 'trava_ocupada' => bool]
 * @return array ['atualizar' => bool, 'motivo' => string, 'tag' => ?string]
 */
function atualizacao_automatica_decidir(array $config, array $contexto): array {
    $tag = $contexto['publicada'] ?? null;
    $nao = function (string $motivo) use ($tag) { return ['atualizar' => false, 'motivo' => $motivo, 'tag' => $tag]; };
    if (empty($config['ativo'])) return $nao('desligada');
    if (!is_string($tag) || !atualizacao_automatica_tag_valida($tag)) return $nao('sem-versao-publicada');
    if (!atualizacao_automatica_mais_nova($tag, (string)($contexto['instalada'] ?? ''))) return $nao('ja-atualizado');
    if (isset($config['estado']['recusadas'][$tag])) return $nao('versao-recusada');
    if ((int)($contexto['choques_pendentes'] ?? 0) > 0) return $nao('choque-pendente');
    if (!empty($contexto['trava_ocupada'])) return $nao('trava-ocupada');

    return ['atualizar' => true, 'motivo' => 'versao-nova', 'tag' => $tag];
}

/**
 * Opções que a automação entrega ao atualizador para uma tag. Sempre modo completo, com verificação e volta
 * automática: só `tag` e, se pedido, `backup`.
 */
function atualizacao_automatica_opcoes(array $config, string $tag): array {
    $opcoes = ['tag' => $tag];
    if (!empty($config['backup'])) $opcoes['backup'] = true;

    return array_intersect_key($opcoes, array_flip(ATUALIZACAO_AUTOMATICA_OPCOES_FIXAS));
}

/**
 * Aplica ao estado o resultado de uma execução pendente. Sucesso encerra; volta automática ou falha põe a
 * versão nas recusadas; ainda rodando, nada muda.
 *
 * @param string $status Um dos valores de `ATUALIZACOES_EXECUCAO_CODIGOS` ou `running`.
 */
function atualizacao_automatica_aplicar_resultado(array $config, string $status, int $agora): array {
    $pendente = $config['estado']['pendente'] ?? null;
    if (!$pendente || $status === 'running') return $config;
    $config['estado']['ultima_tentativa'] = ['tag' => $pendente['tag'], 'quando' => (int)$pendente['quando'], 'resultado' => $status];
    // `locked`: a trava estava ocupada e nada foi tentado; a versão não é recusada por isso.
    if ($status !== 'success' && $status !== 'locked') {
        $config['estado']['recusadas'][$pendente['tag']] = ['quando' => $agora, 'motivo' => $status];
    }
    $config['estado']['pendente'] = null;

    return $config;
}

/** Libera uma versão recusada para nova tentativa. */
function atualizacao_automatica_liberar(array $config, string $tag): array {
    unset($config['estado']['recusadas'][$tag]);

    return $config;
}

/**
 * Consulta as releases publicadas. Com verificação de certificado: uma resposta forjada aqui decidiria qual
 * versão o site instala sozinho.
 *
 * @return array ['ok' => bool, 'tag' => ?string, 'erro' => string]
 */
function atualizacao_automatica_consultar(string $url = 'https://api.github.com/repos/otavioserra/conn2flow/releases?per_page=30'): array {
    if (!function_exists('curl_init')) return ['ok' => false, 'tag' => null, 'erro' => 'curl indisponível'];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Conn2Flow-AutoUpdate/1.0',
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
    ]);
    $resposta = curl_exec($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);
    if ($codigo !== 200 || !is_string($resposta)) return ['ok' => false, 'tag' => null, 'erro' => 'HTTP ' . $codigo . ($erro !== '' ? ' ' . $erro : '')];
    $dados = json_decode($resposta, true);
    if (!is_array($dados)) return ['ok' => false, 'tag' => null, 'erro' => 'resposta inválida'];

    return ['ok' => true, 'tag' => atualizacao_automatica_ultima_estavel($dados), 'erro' => ''];
}
