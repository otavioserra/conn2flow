<?php
/**
 * Regeneração do CSS derivado a partir do HTML EFETIVO do banco (BATCH-144 / req-141 / CR-002).
 * ---------------------------------------------------------------------------------------------
 * O compilador offline (`resources:sync`) varre arquivos físicos em `resources/`. Mas o runtime do
 * gestor serve o HTML do BANCO (`DEVELOPMENT_ENV=false`), e todo conteúdo criado pelo editor online
 * — publicações, páginas novas, templates editados — vive só lá. Resultado medido: 1.279 de 1.410
 * recursos Tailwind com ao menos uma classe sem CSS nenhum, porque o CSS entregue foi compilado de
 * um HTML que não é o HTML entregue.
 *
 * Este script fecha o ciclo: materializa o HTML do banco num arquivo temporário, compila o Tailwind
 * contra ELE e grava o resultado junto com a assinatura de procedência. A máquina de compilação é a
 * mesma do `tailwind-recursos.php` — aqui só muda a FONTE da varredura.
 *
 * Uso:
 *   php css-regenerar.php --gestor=<caminho> [--env=<.env>] [--tipo=paginas] [--id=<id>]
 *                         [--limite=N] [--todos] [--dry-run]
 *
 * Sem `--todos`, regenera apenas o que está stale (assinatura ausente ou divergente).
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
if ($gestorPath === '' || !is_dir($gestorPath)) {
    fwrite(STDERR, "ERRO: informe --gestor=<caminho do gestor>\n");
    exit(1);
}

// ABSOLUTO obrigatoriamente: o Tailwind roda com o cwd no gestor, e um caminho relativo seria
// resolvido a partir dele — o input viraria `<gestor>/<gestor>/...` e o CLI diria que nao existe.
$gestorPath = realpath($gestorPath) ?: $gestorPath;

$envFile = isset($args['env']) ? (string)$args['env'] : '';
$soTipo = isset($args['tipo']) ? (string)$args['tipo'] : '';
$soId = isset($args['id']) ? (string)$args['id'] : '';
$limite = isset($args['limite']) ? max(1, (int)$args['limite']) : 0;
$todos = !empty($args['todos']);
// Varredura pelo HTML RENDERIZADO: dispensa declarar `tailwind_sources`, porque a resposta HTTP
// ja traz toda classe aplicada, venha ela do HTML, do PHP, de um widget ou do template expandido.
$urlBase = isset($args['url']) ? rtrim((string)$args['url'], '/') . '/' : '';
$dryRun = !empty($args['dry-run']);

// O compilador espera estes globais e uma função de progresso do script pai. `$SYSTEM_PATH` é a
// raiz do repositório: é por ele que o resolvedor acha o binário do Tailwind em `node_modules/.bin`
// — sem ele o comando sai relativo e o proc_open falha com "caminho não encontrado".
$GLOBALS['CLI_ARGS'] = $args;
$SYSTEM_PATH = realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR;
$GESTOR_DIR = $gestorPath . DIRECTORY_SEPARATOR;
$globalNodeModules = '/opt/node-v22.22.3-linux-x64/lib/node_modules';
if (is_dir($globalNodeModules)) {
    $nodePath = getenv('NODE_PATH');
    $nodePaths = is_string($nodePath) && $nodePath !== ''
        ? explode(PATH_SEPARATOR, $nodePath)
        : [];
    if (!in_array($globalNodeModules, $nodePaths, true)) {
        array_unshift($nodePaths, $globalNodeModules);
        putenv('NODE_PATH=' . implode(PATH_SEPARATOR, $nodePaths));
    }
}
$isProjectMode = is_file($GESTOR_DIR . 'contents' . DIRECTORY_SEPARATOR . 'tailwindcss'
    . DIRECTORY_SEPARATOR . 'input.css');

if (!function_exists('cliProgress')) {
    function cliProgress(string $message, bool $force = false, bool $verboseOnly = false): void
    {
        if (!$verboseOnly) {
            echo $message, "\n";
        }
    }
}

require_once __DIR__ . '/tailwind-recursos.php';

// ========================= BANCO =========================

/** Credenciais do `.env` ativo do gestor (mesma leitura do auditor). */
function regenerarCredenciais(string $gestorPath, string $envFile): array
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

$cred = regenerarCredenciais($gestorPath, $envFile);

// ========================= TRAVA DE ALVO =========================
//
// Este script ESCREVE no banco. O identificador de projeto e o `.env` que ele resolve decidem QUAL
// banco — e um engano aqui altera dados de produção. A trava existe porque o engano é fácil: em
// `environment.json` o mesmo mirror atende `transformamp` (url de produção) e `transformamp-local`,
// mudando apenas o `.env` resolvido.
//
// Regra: banco fora da máquina local exige `--confirmar-remoto` explícito.
$hostBanco = strtolower(trim((string)$cred['host']));
$ehLocal = in_array($hostBanco, ['localhost', '127.0.0.1', '::1', 'mysql', 'db', 'mariadb'], true);

echo "
";
echo "  alvo da gravação:  base '{$cred['base']}' em '{$cred['host']}'
";
echo "  .env em uso:       {$cred['arquivo']}
";

if (!$ehLocal && empty($args['confirmar-remoto'])) {
    fwrite(STDERR,
        "
RECUSADO: '{$cred['host']}' não é um banco local.
"
        . "Este comando grava CSS derivado em massa; para rodar contra um banco remoto use
"
        . "--confirmar-remoto, e confira antes se o identificador do projeto é o que você quer.

"
    );
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexao = null;
foreach (array_unique([$cred['host'], '127.0.0.1']) as $host) {
    try {
        $conexao = new mysqli($host, $cred['usuario'], $cred['senha'], $cred['base'], $cred['porta']);
        break;
    } catch (Throwable $e) {
        $conexao = null;
    }
}
if ($conexao === null) {
    fwrite(STDERR, "ERRO: falha ao conectar em '{$cred['base']}'.\n");
    exit(1);
}
$conexao->set_charset('utf8mb4');

/** A coluna existe nesta tabela? */
function regenerarTemColuna(mysqli $c, string $tabela, string $coluna): bool
{
    $res = $c->query("SHOW COLUMNS FROM `{$tabela}` LIKE '" . $c->real_escape_string($coluna) . "'");
    return $res instanceof mysqli_result && $res->num_rows > 0;
}

// ========================= COMPILADOR =========================

$comando = tailwind_recursos_resolver_command();
if ($comando === null) {
    fwrite(STDERR, "ERRO: Tailwind CLI nao encontrado (instale as dependencias de node).\n");
    exit(1);
}

$centralInput = tailwind_recursos_input_central();
if ($centralInput === null) {
    fwrite(STDERR, "ERRO: input central do Tailwind nao encontrado para este gestor.\n");
    exit(1);
}

// O temporário fica DENTRO da árvore do gestor, como no build offline (`.tailwind-build/inputs`):
// o `@import "tailwindcss/utilities.css"` é resolvido pelo Node a partir do diretório do arquivo de
// input, e fora da árvore com `node_modules` ele falha com "Can't resolve".
$tempDir = $GESTOR_DIR . '.tailwind-build' . DIRECTORY_SEPARATOR . 'regen';
if (!is_dir($tempDir)) {
    @mkdir($tempDir, 0777, true);
}

/**
 * Fontes Tailwind adicionais declaradas no metadado do recurso (`tailwind_sources`).
 *
 * Nem toda classe vive no HTML: o `perfil-usuario` declara o próprio `.php`, `.json` e `.js` porque
 * monta classes em runtime. Compilar só do HTML perderia essas utilities. Honrar a chave mantém a
 * paridade com o build offline — mas ela é uma declaração MANUAL, e quem esquecer de declarar perde
 * o estilo em silêncio; por isso `regenerarHtmlRenderizado()` existe como fonte que não depende de
 * ninguém declarar nada.
 *
 * @return list<string> Caminhos absolutos existentes.
 */
function regenerarFontesDeclaradas(string $gestorPath, string $tabela, string $id, string $lang, string $modulo): array
{
    $tipos = ['paginas' => 'pages', 'layouts' => 'layouts', 'componentes' => 'components', 'templates' => 'templates'];
    if (!isset($tipos[$tabela]) || $id === '' || $lang === '') {
        return [];
    }

    $base = $modulo !== ''
        ? $gestorPath . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . $modulo
        : $gestorPath;

    $dir = $base . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $lang
        . DIRECTORY_SEPARATOR . $tipos[$tabela] . DIRECTORY_SEPARATOR . $id;

    $metadata = null;

    // 1) metadado por recurso (`<id>.json` ao lado do HTML).
    $json = $dir . DIRECTORY_SEPARATOR . $id . '.json';
    if (is_file($json)) {
        $decodificado = json_decode((string)file_get_contents($json), true);
        if (is_array($decodificado)) {
            $metadata = $decodificado;
        }
    }

    // 2) manifesto do MÓDULO — e este é o caminho que a maioria dos recursos usa de verdade.
    //
    // O `perfil-usuario` não tem `<id>.json` nenhum: as 15 páginas dele declaram `tailwind_sources`
    // dentro de `modulos/perfil-usuario/perfil-usuario.json`, em `resources.<lang>.pages[]`. Ler só
    // o metadado por recurso fazia esta função devolver lista VAZIA para todas elas, e a regeneração
    // recompilava a partir do HTML apenas — descartando as utilities montadas em PHP/JS.
    //
    // O sintoma era a tela do perfil sem estilo: 25.276 bytes de CSS correto no disco viravam 7.980
    // no banco, e a página servia 192 das 247 classes sem regra nenhuma. Silencioso: o comando
    // reportava sucesso, porque do ponto de vista dele nada falhou.
    if ($metadata === null && $modulo !== '') {
        $manifesto = $base . DIRECTORY_SEPARATOR . $modulo . '.json';

        if (is_file($manifesto)) {
            $decodificado = json_decode((string)file_get_contents($manifesto), true);
            $entradas = $decodificado['resources'][$lang][$tipos[$tabela]] ?? null;

            if (is_array($entradas)) {
                foreach ($entradas as $entrada) {
                    if (is_array($entrada) && ($entrada['id'] ?? null) === $id) {
                        $metadata = $entrada;
                        break;
                    }
                }
            }
        }
    }

    if (!is_array($metadata)) {
        return [];  // conteúdo criado só no banco não tem metadado — e não precisa ter
    }

    $fontes = [];
    foreach ((array)($metadata['tailwind_sources'] ?? []) as $source) {
        if (!is_string($source) || trim($source) === '') {
            continue;
        }
        $candidato = realpath($dir . DIRECTORY_SEPARATOR . $source);
        // Fora da raiz do gestor a fonte é recusada, como no build offline.
        if ($candidato !== false && is_file($candidato) && strpos($candidato, $gestorPath) === 0) {
            $fontes[] = $candidato;
        }
    }

    sort($fontes, SORT_STRING);

    return array_values(array_unique($fontes));
}

/**
 * HTML como o servidor entrega, usado como fonte de varredura (req-141).
 *
 * É a fonte que NÃO exige declaração nenhuma: a resposta renderizada já contém toda classe
 * aplicada, venha ela do HTML gravado, do PHP do módulo, de um widget ou do template expandido.
 * Complementa o HTML do banco em vez de substituí-lo — um estado condicional que não apareceu nesta
 * renderização (mensagem de erro, aba fechada) continua vindo do registro.
 *
 * @return string HTML recebido, ou string vazia quando a rota não respondeu 200.
 */
function regenerarHtmlRenderizado(string $urlBase, string $caminho): string
{
    if ($urlBase === '' || trim($caminho) === '') {
        return '';
    }

    $contexto = stream_context_create(['http' => [
        'method' => 'GET',
        'timeout' => 20,
        'ignore_errors' => true,
        'header' => "User-Agent: c2f-css-rebuild\r\n",
    ]]);

    $html = @file_get_contents($urlBase . ltrim($caminho, '/'), false, $contexto);
    if ($html === false) {
        return '';
    }

    $status = 0;
    foreach (($http_response_header ?? []) as $cabecalho) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $cabecalho, $m)) {
            $status = (int)$m[1];
        }
    }

    // Só 200 serve: 404 e a tela de erro trazem classes que não pertencem a esta página.
    return $status === 200 ? (string)$html : '';
}


/**
 * Compila o Tailwind tendo como fonte o HTML vindo do BANCO.
 *
 * O `@source` do Tailwind aponta para ARQUIVOS, então o HTML do banco é materializado num
 * temporário. É a única diferença em relação ao build offline — a cascata, o contrato central e as
 * camadas continuam idênticos, para o resultado ser comparável ao que o `resources:sync` produz.
 *
 * @return array{ok:bool, css:string, erro:string}
 */
function regenerarCompilar(
    string $html,
    string $cssAutoral,
    bool $ehLayout,
    array $comando,
    string $centralInput,
    string $tempDir,
    array $fontesExtras = []
): array {
    $id = bin2hex(random_bytes(6));
    $htmlPath = $tempDir . DIRECTORY_SEPARATOR . 'fonte-' . $id . '.html';
    $inputPath = $tempDir . DIRECTORY_SEPARATOR . 'input-' . $id . '.css';
    $outputPath = $tempDir . DIRECTORY_SEPARATOR . 'output-' . $id . '.css';

    // O CSS autoral entra na varredura porque pode conter `@apply` com utilities.
    file_put_contents($htmlPath, $html . "\n<!-- css autoral -->\n" . $cssAutoral);

    $central = tailwind_recursos_css_string(tailwind_recursos_relativo($tempDir, $centralInput));
    $fonte = tailwind_recursos_css_string(tailwind_recursos_relativo($tempDir, $htmlPath));

    // Mesma regra do build offline: layout carrega theme/base/preflight; recurso isolado recebe
    // essas camadas do layout e importa apenas utilities.
    $linhas = $ehLayout
        ? ['@import "' . $central . '";']
        : ['@reference "' . $central . '";', '@import "tailwindcss/utilities.css" layer(utilities) source(none);'];
    $linhas[] = '@source "' . $fonte . '";';

    // Fontes declaradas pelo recurso (classes montadas em PHP/JS) entram junto com o HTML.
    foreach ($fontesExtras as $extra) {
        $linhas[] = '@source "' . tailwind_recursos_css_string(tailwind_recursos_relativo($tempDir, $extra)) . '";';
    }

    file_put_contents($inputPath, implode("\n", $linhas) . "\n");

    $cmd = array_merge($comando, ['--input', $inputPath, '--output', $outputPath, '--minify']);
    $resultado = tailwind_recursos_exec($cmd, $GLOBALS['GESTOR_DIR'] ?? null);

    $css = is_file($outputPath) ? (string)file_get_contents($outputPath) : '';

    @unlink($htmlPath);
    @unlink($inputPath);
    @unlink($outputPath);

    if ($resultado['code'] !== 0) {
        return ['ok' => false, 'css' => '', 'erro' => trim($resultado['stderr']) ?: 'exit ' . $resultado['code']];
    }

    return ['ok' => true, 'css' => $css, 'erro' => ''];
}

// ========================= EXECUÇÃO =========================

$tabelas = $soTipo !== '' ? [$soTipo] : ['layouts', 'componentes', 'templates', 'paginas'];
$cssLayouts = [];

if ($res = $conexao->query("SELECT id, language, css_precompiled FROM layouts WHERE status!='D'")) {
    while ($linha = $res->fetch_assoc()) {
        $cssLayouts[$linha['id'] . '|' . $linha['language']] = (string)($linha['css_precompiled'] ?? '');
    }
}

$stats = ['analisados' => 0, 'regenerados' => 0, 'ja_coerentes' => 0, 'sem_tailwind' => 0, 'erros' => 0];
$processados = 0;

echo "\n=== Regeneração de CSS a partir do banco ===\n";
echo "Base: {$cred['base']}", $dryRun ? '  [DRY-RUN: nada será gravado]' : '', "\n\n";

foreach ($tabelas as $tabela) {
    if (!regenerarTemColuna($conexao, $tabela, 'html')) {
        continue;
    }

    $temHash = regenerarTemColuna($conexao, $tabela, 'css_source_hash');
    $temLayout = regenerarTemColuna($conexao, $tabela, 'layout_id');
    $temFramework = regenerarTemColuna($conexao, $tabela, 'framework_css');

    $temModulo = regenerarTemColuna($conexao, $tabela, 'modulo');

    $campos = 'id, language, html, css, css_precompiled'
        . ($temHash ? ', css_source_hash' : '')
        . ($temLayout ? ', layout_id' : '')
        . ($temFramework ? ', framework_css' : '')
        . ($temModulo ? ', modulo' : '')
        . (regenerarTemColuna($conexao, $tabela, 'caminho') ? ', caminho' : '');

    $where = "status!='D'";
    if ($soId !== '') {
        $where .= " AND id='" . $conexao->real_escape_string($soId) . "'";
    }

    // ESCOPO: por padrão só os recursos editados ONLINE (req-141 / BATCH-149).
    //
    // O problema que o req-141 descreve existe apenas quando o HTML do banco divergiu do HTML do
    // disco — ou seja, quando alguém editou pelo gestor. Para `user_modified = 0`, o build offline
    // (`resources:sync`) já compilou o CSS a partir do MESMO HTML, com o contexto completo do
    // projeto: tema, plugins (`@tailwindcss/typography`) e as fontes declaradas em
    // `tailwind_sources`. Regenerar ali não corrige nada e ainda substitui um CSS mais completo por
    // um mais pobre.
    //
    // Medido no `transformamp`: 17 de 1.446 recursos têm `user_modified = 1`. Regenerar os 1.429
    // restantes derrubou a página `perfil-usuario` de 25.276 para 13.429 bytes de CSS e deixou a
    // tela sem estilo — em silêncio, com o comando reportando sucesso.
    //
    // `--todos` continua alcançando o acervo inteiro, para auditoria e recuperação.
    if (!$todos && regenerarTemColuna($conexao, $tabela, 'user_modified')) {
        $where .= " AND user_modified=1";
    }

    $res = $conexao->query("SELECT {$campos} FROM `{$tabela}` WHERE {$where}");
    if (!($res instanceof mysqli_result)) {
        continue;
    }

    while ($linha = $res->fetch_assoc()) {
        if ($limite > 0 && $processados >= $limite) {
            break 2;
        }

        $html = (string)($linha['html'] ?? '');
        if (trim($html) === '') {
            continue;
        }

        $framework = $temFramework ? strtolower(trim((string)($linha['framework_css'] ?? ''))) : '';
        if ($framework !== 'tailwindcss') {
            $stats['sem_tailwind']++;
            continue;
        }

        $stats['analisados']++;

        $lang = (string)($linha['language'] ?? '');
        $layoutId = $temLayout ? (string)($linha['layout_id'] ?? '') : '';
        $baseline = $layoutId !== '' ? ($cssLayouts[$layoutId . '|' . $lang] ?? '') : '';
        $cssAutoral = (string)($linha['css'] ?? '');

        // req-156: a versao do compilador entra na procedencia — derivado gerado por outra major
        // do Tailwind e stale, ainda que HTML, CSS e baseline nao tenham mudado.
        $entradas = [
            'html' => $html,
            'css' => $cssAutoral,
            'baseline' => $baseline,
            'compilador' => gestor_css_compilador_versao(),
        ];
        $assinaturaGravada = $temHash ? (string)($linha['css_source_hash'] ?? '') : '';

        if (!$todos && gestor_css_procedencia_valida($assinaturaGravada, $entradas)) {
            $stats['ja_coerentes']++;
            continue;
        }

        $processados++;

        // A resposta renderizada entra como fonte quando --url foi informado: ela dispensa qualquer
        // declaracao de `tailwind_sources`, porque ja traz as classes montadas em PHP/JS/widget.
        $htmlRender = regenerarHtmlRenderizado($urlBase, (string)($linha['caminho'] ?? ''));
        $htmlFonte = $htmlRender !== '' ? ($html . "
<!-- render -->
" . $htmlRender) : $html;

        $fontesExtras = regenerarFontesDeclaradas(
            $gestorPath,
            $tabela,
            (string)($linha['id'] ?? ''),
            $lang,
            $temModulo ? (string)($linha['modulo'] ?? '') : ''
        );

        $compilado = regenerarCompilar(
            $htmlFonte,
            $cssAutoral,
            $tabela === 'layouts',
            $comando,
            $centralInput,
            $tempDir,
            $fontesExtras
        );

        if (!$compilado['ok']) {
            $stats['erros']++;
            printf("  ERRO   %-12s %-40s %s\n", $tabela, substr((string)$linha['id'], 0, 40), $compilado['erro']);
            continue;
        }

        $descobertasAntes = count(gestor_css_classes_descobertas($html, $baseline . "\n" . (string)($linha['css_precompiled'] ?? '') . "\n" . $cssAutoral));
        $descobertasDepois = count(gestor_css_classes_descobertas($html, $baseline . "\n" . $compilado['css'] . "\n" . $cssAutoral));

        printf(
            "  %-6s %-12s %-40s %6d B  sem CSS: %d -> %d\n",
            $dryRun ? 'DRY' : 'OK',
            $tabela,
            substr((string)$linha['id'], 0, 40),
            strlen($compilado['css']),
            $descobertasAntes,
            $descobertasDepois
        );

        if ($dryRun) {
            $stats['regenerados']++;
            continue;
        }

        $assinatura = gestor_css_procedencia_assinatura($entradas);

        $sql = "UPDATE `{$tabela}` SET css_precompiled=?"
            . ($temHash ? ', css_source_hash=?' : '')
            . " WHERE id=? AND language=?";
        $st = $conexao->prepare($sql);

        if ($temHash) {
            $st->bind_param('ssss', $compilado['css'], $assinatura, $linha['id'], $linha['language']);
        } else {
            $st->bind_param('sss', $compilado['css'], $linha['id'], $linha['language']);
        }

        $st->execute();
        $stats['regenerados']++;
    }
}

// Limpeza: só os arquivos desta execução. O diretório é compartilhado com o build offline.
foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
    @unlink($f);
}

$conexao->close();

echo "\n";
printf(
    "analisados: %d | regenerados: %d | já coerentes: %d | fora do Tailwind: %d | erros: %d\n\n",
    $stats['analisados'],
    $stats['regenerados'],
    $stats['ja_coerentes'],
    $stats['sem_tailwind'],
    $stats['erros']
);

exit($stats['erros'] > 0 ? 1 : 0);
