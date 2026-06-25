<?php

/**
 * Recuperação de Dados de Recursos — Exportação Bruta no Servidor (Pull System)
 * ------------------------------------------------------------------------------------------
 * req-058 / BATCH-058.
 *
 * Ponto de entrada CLI alternativo, executado DIRETAMENTE no ambiente do servidor, que extrai
 * um dump bruto (raw) das tabelas do banco do gestor em arquivos <PascalCase>Data.json. É a
 * contraparte local do endpoint da API `_api/project/recover`: quando não é possível (ou não se
 * deseja) acionar a API por HTTP, este controlador faz a mesma exportação reaproveitando a função
 * reverseExport() do atualizador de banco.
 *
 * Diferente do descompilador cliente (controladores/agents/arquitetura/recuperacao-dados-recursos.php),
 * este script NÃO decompõe os campos em arquivos físicos — apenas gera os *Data.json brutos. A
 * decompilação fica a cargo do cliente local.
 *
 * Argumentos CLI:
 *   --tables=lista     : Exporta apenas as tabelas informadas (CSV). Sem isso, exporta todas as
 *                        tabelas registradas em db/data/schema-metadata.json (ou, na ausência do
 *                        contrato, as inferidas dos *Data.json existentes).
 *   --output-dir=path  : Diretório de destino dos *Data.json (default: gestor/db/data/).
 *   --db-host/--db-name/--db-user/--db-pass : Credenciais de banco (quando $_BANCO não estiver
 *                        populado pelo escopo do gestor). Fallback para as variáveis de ambiente
 *                        CONN2FLOW_DB_HOST/CONN2FLOW_DB_DATABASE/CONN2FLOW_DB_USERNAME/CONN2FLOW_DB_PASSWORD.
 *
 * Exemplos:
 *   php recuperacao-dados-recursos.php
 *   php recuperacao-dados-recursos.php --tables=menus,galleries --output-dir=/tmp/recover
 *
 * Estrutura: parse de argumentos -> bootstrap de banco -> inclusão do atualizador (SDD_NO_AUTORUN)
 * -> resolução de tabelas -> reverseExport().
 */

declare(strict_types=1);

/**
 * Parser simples de argumentos CLI no padrão --chave=valor / --flag.
 */
function recuperacao_parse_args(array $argv): array {
    $out = [];
    foreach ($argv as $a) {
        if (preg_match('/^--([^=]+)=(.*)$/', $a, $m)) { $out[$m[1]] = $m[2]; }
        elseif (substr($a, 0, 2) === '--') { $out[substr($a, 2)] = true; }
    }
    return $out;
}

/**
 * Garante que $GLOBALS['_BANCO'] esteja populado a partir dos argumentos CLI ou variáveis de
 * ambiente quando o escopo do gestor não tiver definido a conexão. Mantém valores já presentes.
 */
function recuperacao_bootstrap_banco(array $args): void {
    global $_BANCO;
    if (!isset($_BANCO) || !is_array($_BANCO)) { $_BANCO = []; }

    $resolver = function (string $argKey, string $envKey, $default = null) use ($args) {
        if (isset($args[$argKey]) && $args[$argKey] !== true && $args[$argKey] !== '') {
            return $args[$argKey];
        }
        $env = getenv($envKey);
        return ($env !== false && $env !== '') ? $env : $default;
    };

    $host = $resolver('db-host', 'CONN2FLOW_DB_HOST', $_BANCO['host'] ?? null);
    $name = $resolver('db-name', 'CONN2FLOW_DB_DATABASE', $_BANCO['nome'] ?? null);
    $user = $resolver('db-user', 'CONN2FLOW_DB_USERNAME', $_BANCO['usuario'] ?? null);
    $pass = $resolver('db-pass', 'CONN2FLOW_DB_PASSWORD', $_BANCO['senha'] ?? null);

    if ($host !== null) { $_BANCO['host'] = $host; }
    if ($name !== null) { $_BANCO['nome'] = $name; }
    if ($user !== null) { $_BANCO['usuario'] = $user; }
    if ($pass !== null) { $_BANCO['senha'] = $pass; }
}

/**
 * Executa a exportação bruta das tabelas para o diretório de saída usando reverseExport().
 * Retorna 0 em sucesso e 1 em falha. Depende de o atualizador de banco já ter sido incluído
 * (define reverseExport/db/schemaMetadata/tabelaFromDataFile e a global $DB_DATA_DIR).
 *
 * @param array       $args       Argumentos já parseados (--tables, --output-dir).
 * @param string|null $outputDir  Diretório de saída (sobrepõe --output-dir quando informado).
 */
function recuperacao_executar(array $args, ?string $outputDir = null): int {
    global $DB_DATA_DIR;

    $dataDir = $outputDir
        ?? (isset($args['output-dir']) && is_string($args['output-dir']) && $args['output-dir'] !== ''
            ? rtrim($args['output-dir'], '/\\') . DIRECTORY_SEPARATOR
            : $DB_DATA_DIR);
    if (!is_dir($dataDir)) { @mkdir($dataDir, 0775, true); }

    // Resolver tabelas: --tables (CSV) ou o contrato schema-metadata.json; fallback nos *Data.json.
    $tabelas = [];
    if (isset($args['tables']) && is_string($args['tables']) && $args['tables'] !== '') {
        $tabelas = array_map('trim', explode(',', $args['tables']));
    } else {
        $meta = schemaMetadata();
        $tabelas = array_keys($meta['tables'] ?? []);
        if (empty($tabelas)) {
            foreach (glob($DB_DATA_DIR . '*Data.json') ?: [] as $f) {
                $tabelas[] = tabelaFromDataFile($f);
            }
        }
    }
    $tabelas = array_values(array_filter(array_map(function ($t) {
        return preg_replace('/[^a-z0-9_]/', '', strtolower((string)$t));
    }, $tabelas)));

    if (empty($tabelas)) {
        fwrite(STDERR, "Nenhuma tabela para exportar.\n");
        return 1;
    }

    try {
        $pdo = db();
        reverseExport($pdo, $tabelas, $dataDir);
        return 0;
    } catch (Throwable $e) {
        fwrite(STDERR, 'Erro na recuperação: ' . $e->getMessage() . "\n");
        return 1;
    }
}

// Guard de autorun: permite incluir este arquivo em testes sem disparar a execução.
if (!defined('SDD_NO_AUTORUN')) {
    $__args = recuperacao_parse_args($argv ?? []);

    // Bootstrap de banco a partir de args/env quando o gestor não tiver definido a conexão.
    recuperacao_bootstrap_banco($__args);

    // Inclui o atualizador de banco apenas para reusar reverseExport/db/schemaMetadata.
    if (!defined('SDD_NO_AUTORUN')) { define('SDD_NO_AUTORUN', true); }
    require_once realpath(__DIR__ . '/../atualizacoes/atualizacoes-banco-de-dados.php');

    exit(recuperacao_executar($__args));
}
