<?php
/**
 * Auditoria de procedência e cobertura do CSS dos recursos (BATCH-144 / req-141 / CR-002).
 * ---------------------------------------------------------------------------------------
 * Responde duas perguntas que o sistema não sabia responder:
 *
 *  1. PROCEDÊNCIA — o CSS derivado (`css_precompiled` / `css_compiled`) corresponde à autoria
 *     (`html` / `css`) que está gravada AGORA? Assinatura ausente ou divergente = stale.
 *
 *  2. COBERTURA — quantas classes o HTML usa sem que nenhuma folha disponível as defina? É o
 *     sintoma que o operador vê como "quebrou o layout".
 *
 * O auditor NÃO escreve nada. Ele mede — porque sem número não há como saber se uma correção
 * melhorou ou piorou o acervo.
 *
 * Uso:
 *   php css-auditoria.php --gestor=<caminho> [--env=<arquivo .env>] [--limite=N] [--json]
 *
 * @version 1.0.0
 */

declare(strict_types=1);

ini_set('memory_limit', '1024M');

require_once __DIR__ . '/../../../bibliotecas/gestor.php';

// ========================= ARGUMENTOS =========================

$args = [];
foreach (($argv ?? []) as $a) {
    if (preg_match('/^--([^=]+)=(.*)$/', $a, $m)) {
        $args[$m[1]] = $m[2];
    } elseif (substr($a, 0, 2) === '--') {
        $args[substr($a, 2)] = true;
    }
}

$gestorPath = isset($args['gestor']) ? rtrim((string)$args['gestor'], '/\\') : '';
$envFile = isset($args['env']) ? (string)$args['env'] : '';
$limite = isset($args['limite']) ? max(1, (int)$args['limite']) : 10;
$comoJson = !empty($args['json']);
// Auditoria de COMPOSIÇÃO: a página final não é um recurso, é a soma de vários (página + layout +
// componentes + templates + widgets). Auditar recurso a recurso não enxerga o buraco de composição.
$urlComposta = isset($args['url']) ? (string)$args['url'] : '';

if ($gestorPath === '' || !is_dir($gestorPath)) {
    fwrite(STDERR, "ERRO: informe --gestor=<caminho do gestor>\n");
    exit(1);
}

// ========================= CREDENCIAIS =========================

/**
 * Lê as chaves de banco do `.env` ativo do gestor.
 *
 * O `.env` fica em `autenticacoes/<host>/.env`; quando o chamador não informa qual, o primeiro
 * encontrado serve — auditoria é leitura e não escolhe tenant.
 */
function auditoriaCredenciais(string $gestorPath, string $envFile): array
{
    if ($envFile === '' || !is_file($envFile)) {
        $candidatos = glob($gestorPath . DIRECTORY_SEPARATOR . 'autenticacoes'
            . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '.env') ?: [];
        $envFile = $candidatos[0] ?? '';
    }

    if ($envFile === '' || !is_file($envFile)) {
        fwrite(STDERR, "ERRO: .env do gestor nao encontrado.\n");
        exit(1);
    }

    $cfg = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $linha, 2);
        $cfg[trim($k)] = trim(trim($v), "\"'");
    }

    return [
        'host' => $cfg['DB_HOST'] ?? '127.0.0.1',
        'porta' => (int)($cfg['DB_PORT'] ?? 3306),
        'base' => $cfg['DB_DATABASE'] ?? '',
        'usuario' => $cfg['DB_USERNAME'] ?? '',
        'senha' => $cfg['DB_PASSWORD'] ?? '',
        'arquivo' => $envFile,
    ];
}

$cred = auditoriaCredenciais($gestorPath, $envFile);

// De fora do container o host `mysql` não resolve; o mapeamento local responde igual. Em PHP 8+ o
// mysqli LANÇA em vez de devolver erro, então o fallback precisa de try/catch — sem ele o script
// morre no primeiro host em vez de tentar o segundo.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$hosts = array_unique([$cred['host'], '127.0.0.1']);
$conexao = null;
foreach ($hosts as $host) {
    try {
        $conexao = new mysqli($host, $cred['usuario'], $cred['senha'], $cred['base'], $cred['porta']);
        break;
    } catch (Throwable $e) {
        $conexao = null;
    }
}

if ($conexao === null) {
    fwrite(STDERR, "ERRO: falha ao conectar em '{$cred['base']}' (env: {$cred['arquivo']}).\n");
    exit(1);
}

$conexao->set_charset('utf8mb4');

// ========================= MODO COMPOSIÇÃO (--url) =========================
//
// A página que o visitante recebe não é um recurso: é a SOMA de vários (página + layout +
// componentes incluídos pelo gestor + template escolhido em runtime + widgets). Auditar recurso a
// recurso não enxerga o buraco de composição — o caso em que o recurso contribui HTML mas o CSS
// dele não entra na resposta. Aqui a auditoria é sobre a página montada, e cada classe órfã é
// atribuída ao recurso de onde ela veio.

if ($urlComposta !== '') {
    $contexto = stream_context_create(['http' => [
        'method' => 'GET', 'timeout' => 30, 'ignore_errors' => true,
        'header' => "User-Agent: c2f-css-audit\r\n",
    ]]);

    $html = @file_get_contents($urlComposta, false, $contexto);
    if ($html === false || trim((string)$html) === '') {
        fwrite(STDERR, "ERRO: nao foi possivel buscar {$urlComposta}\n");
        exit(1);
    }

    $folhas = [];
    if (preg_match_all('#<style[^>]*>(.*?)</style>#s', (string)$html, $m)) {
        $folhas = $m[1];
    }

    // Folhas servidas por <link> do próprio gestor contam: o CSS do Quill chega por lá.
    if (preg_match_all('#<link[^>]+href="([^"]+\.css[^"]*)"#i', (string)$html, $m)) {
        $base = parse_url($urlComposta);
        foreach ($m[1] as $href) {
            if (strpos($href, '//') === 0 || preg_match('#^https?://#i', $href)) {
                continue; // CDN externo (Fomantic) não é responsabilidade deste pipeline
            }
            $absoluta = $base['scheme'].'://'.$base['host'].(isset($base['port']) ? ':'.$base['port'] : '')
                . (strpos($href, '/') === 0 ? $href : '/'.$href);
            $conteudo = @file_get_contents($absoluta, false, $contexto);
            if ($conteudo !== false) {
                $folhas[] = (string)$conteudo;
            }
        }
    }

    $cssEntregue = implode("\n", $folhas);
    $orfas = gestor_css_classes_descobertas((string)$html, $cssEntregue);

    echo "\n=== Auditoria de COMPOSIÇÃO ===\n";
    echo "URL: {$urlComposta}\n";
    printf("folhas na resposta: %d | classes usadas: %d | SEM CSS: %d\n\n",
        count($folhas), count(gestor_css_classes_usadas((string)$html)), count($orfas));

    if (!$orfas) {
        echo "Nenhuma classe órfã: a composição está coberta.\n\n";
        $conexao->close();
        exit(0);
    }

    // Atribui cada órfã ao recurso que a introduziu — é isso que diz ONDE consertar.
    $origens = [];
    foreach (['paginas', 'layouts', 'componentes', 'templates', 'widgets'] as $tabela) {
        $r = $conexao->query("SHOW TABLES LIKE '{$tabela}'");
        if (!($r instanceof mysqli_result) || !$r->num_rows) {
            continue;
        }

        // `widgets` guarda o markup em outra coluna (ou em nenhuma): projetar `html` às cegas
        // derruba a auditoria inteira com "Unknown column".
        if (!auditoriaTemColuna($conexao, $tabela, 'html')) {
            continue;
        }

        $res = $conexao->query("SELECT id, html FROM `{$tabela}` WHERE status!='D' AND html IS NOT NULL AND html<>''");
        if (!($res instanceof mysqli_result)) {
            continue;
        }

        while ($linha = $res->fetch_assoc()) {
            $classesDoRecurso = array_flip(gestor_css_classes_usadas((string)$linha['html']));
            foreach ($orfas as $classe) {
                if (isset($classesDoRecurso[$classe])) {
                    $origens[$classe][] = $tabela . ':' . $linha['id'];
                }
            }
        }
    }

    echo "Classe órfã -> recurso que a introduziu:\n";
    foreach ($orfas as $classe) {
        $de = $origens[$classe] ?? [];
        $de = array_slice(array_unique($de), 0, 3);
        printf("  %-42s %s\n", substr($classe, 0, 42), $de ? implode(', ', $de) : '(nenhum recurso do banco — vem de PHP/JS)');
    }

    echo "\n";
    $conexao->close();
    exit(0);
}


// ========================= COLETA =========================

/** A coluna existe? Acervo anterior à migração da procedência não a tem. */
function auditoriaTemColuna(mysqli $c, string $tabela, string $coluna): bool
{
    $res = $c->query("SHOW COLUMNS FROM `{$tabela}` LIKE '" . $c->real_escape_string($coluna) . "'");
    return $res instanceof mysqli_result && $res->num_rows > 0;
}

$tabelas = ['paginas', 'layouts', 'componentes', 'templates'];
$relatorio = [];
$cssLayouts = [];

// Cascata do layout: é o contexto sob o qual o CSS de uma página foi gerado.
if ($res = $conexao->query("SELECT id, language, css_precompiled FROM layouts WHERE status!='D'")) {
    while ($linha = $res->fetch_assoc()) {
        $cssLayouts[$linha['id'] . '|' . $linha['language']] = (string)($linha['css_precompiled'] ?? '');
    }
}

foreach ($tabelas as $tabela) {
    $temHash = auditoriaTemColuna($conexao, $tabela, 'css_source_hash');
    $temLayout = auditoriaTemColuna($conexao, $tabela, 'layout_id');

    $temFramework = auditoriaTemColuna($conexao, $tabela, 'framework_css');

    $campos = 'id, language, html, css, css_precompiled, css_compiled'
        . ($temHash ? ', css_source_hash' : '')
        . ($temLayout ? ', layout_id' : '')
        . ($temFramework ? ', framework_css' : '');

    $res = $conexao->query("SELECT {$campos} FROM `{$tabela}` WHERE status!='D'");
    if (!($res instanceof mysqli_result)) {
        continue;
    }

    $itens = [];
    while ($linha = $res->fetch_assoc()) {
        $html = (string)($linha['html'] ?? '');
        if (trim($html) === '') {
            continue; // sem markup não há o que estilizar nem o que auditar
        }

        // SÓ Tailwind. Recurso Fomantic recebe a folha inteira do framework por <link> de CDN, e
        // contar `accordion`/`breadcrumb` como "sem CSS" produziria um número grande e falso —
        // exatamente o tipo de métrica que faz perder tempo com o problema errado.
        $framework = $temFramework ? strtolower(trim((string)($linha['framework_css'] ?? ''))) : '';
        if ($framework !== 'tailwindcss') {
            continue;
        }

        $lang = (string)($linha['language'] ?? '');
        $layoutId = $temLayout ? (string)($linha['layout_id'] ?? '') : '';
        $baseline = $layoutId !== '' ? ($cssLayouts[$layoutId . '|' . $lang] ?? '') : '';

        $cssDisponivel = $baseline . "\n"
            . (string)($linha['css_precompiled'] ?? '') . "\n"
            . (string)($linha['css_compiled'] ?? '') . "\n"
            . (string)($linha['css'] ?? '');

        $usadas = gestor_css_classes_usadas($html);
        $descobertas = gestor_css_classes_descobertas($html, $cssDisponivel);

        $assinatura = $temHash ? (string)($linha['css_source_hash'] ?? '') : '';
        $entradas = [
            'html' => $html,
            'css' => (string)($linha['css'] ?? ''),
            'baseline' => $baseline,
        ];

        $itens[] = [
            'id' => (string)($linha['id'] ?? ''),
            'language' => $lang,
            'usadas' => count($usadas),
            'descobertas' => count($descobertas),
            'exemplos' => array_slice($descobertas, 0, 8),
            'stale' => !gestor_css_procedencia_valida($assinatura, $entradas),
            'sem_assinatura' => ($assinatura === ''),
            'sem_css_proprio' => (trim((string)($linha['css_precompiled'] ?? '')) === ''
                && trim((string)($linha['css_compiled'] ?? '')) === ''),
        ];
    }

    $relatorio[$tabela] = ['tem_coluna_hash' => $temHash, 'itens' => $itens];
}

$conexao->close();

// ========================= SAÍDA =========================

if ($comoJson) {
    echo json_encode($relatorio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
    exit(0);
}

echo "\n=== Auditoria de CSS — procedência e cobertura ===\n";
echo "Base: {$cred['base']}  (somente recursos com framework_css=tailwindcss)\n\n";

$totalGeral = 0;
$totalQuebrados = 0;

printf("%-13s %7s %8s %8s %10s %12s\n", 'tabela', 'itens', 'stale', 'sem CSS', 'classes', 'sem definir');
echo str_repeat('-', 64), "\n";

foreach ($relatorio as $tabela => $dados) {
    $itens = $dados['itens'];
    $stale = count(array_filter($itens, static fn($i) => $i['stale']));
    $semCss = count(array_filter($itens, static fn($i) => $i['sem_css_proprio']));
    $classes = array_sum(array_column($itens, 'usadas'));
    $descobertas = array_sum(array_column($itens, 'descobertas'));
    $quebrados = count(array_filter($itens, static fn($i) => $i['descobertas'] > 0));

    $totalGeral += count($itens);
    $totalQuebrados += $quebrados;

    printf("%-13s %7d %8d %8d %10d %12d\n", $tabela, count($itens), $stale, $semCss, $classes, $descobertas);

    if (!$dados['tem_coluna_hash']) {
        echo "              (sem coluna css_source_hash: rode a migração da procedência)\n";
    }
}

echo str_repeat('-', 64), "\n";
printf("Recursos com ao menos uma classe sem CSS: %d de %d\n\n", $totalQuebrados, $totalGeral);

echo "Piores casos (mais classes sem definição):\n";
$todos = [];
foreach ($relatorio as $tabela => $dados) {
    foreach ($dados['itens'] as $item) {
        $item['tabela'] = $tabela;
        $todos[] = $item;
    }
}
usort($todos, static fn($a, $b) => $b['descobertas'] <=> $a['descobertas']);

foreach (array_slice($todos, 0, $limite) as $item) {
    if ($item['descobertas'] === 0) {
        break;
    }
    printf(
        "  %-12s %-38s %3d/%-3d sem CSS%s\n",
        $item['tabela'],
        substr($item['id'], 0, 38),
        $item['descobertas'],
        $item['usadas'],
        $item['sem_css_proprio'] ? '  [sem CSS proprio]' : ''
    );
    if ($item['exemplos']) {
        echo "                 -> ", implode(', ', $item['exemplos']), "\n";
    }
}

echo "\n";

// ========================= VIOLAÇÕES DE HTML/CLASSE EM CÓDIGO =========================
//
// A norma do projeto é que PHP e JavaScript não carreguem HTML nem classe: o markup vive em
// COMPONENTES. Quando o código monta classe em runtime, o compilador não a acha pelo HTML, e a
// saída vira declarar o arquivo em `tailwind_sources` — lista MANUAL que, esquecida, derruba o
// estilo em silêncio. Esta seção mostra a dívida para que ela seja eliminada, não administrada.

$raizModulos = $gestorPath . DIRECTORY_SEPARATOR . 'modulos';
$violacoes = [];

if (is_dir($raizModulos)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raizModulos, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $arquivo) {
        if (!$arquivo->isFile()) {
            continue;
        }
        $ext = strtolower($arquivo->getExtension());
        if ($ext !== 'php' && $ext !== 'js') {
            continue;
        }

        $classes = gestor_css_classes_em_codigo((string)file_get_contents($arquivo->getPathname()));
        if ($classes) {
            $violacoes[] = [
                'arquivo' => str_replace($gestorPath . DIRECTORY_SEPARATOR, '', $arquivo->getPathname()),
                'classes' => $classes,
            ];
        }
    }
}

usort($violacoes, static fn($a, $b) => count($b['classes']) <=> count($a['classes']));

echo "HTML/classe embutidos em codigo (a norma manda usar componentes):\n";

if (!$violacoes) {
    echo "  nenhum — nenhum PHP/JS de modulo monta classe em runtime.\n";
} else {
    foreach (array_slice($violacoes, 0, $limite) as $v) {
        printf("  %-52s %3d classe(s)\n", substr($v['arquivo'], 0, 52), count($v['classes']));
        echo "                 -> ", implode(', ', array_slice($v['classes'], 0, 10)), "\n";
    }
    printf("  total de arquivos: %d\n", count($violacoes));
}

exit(0);
