<?php

/**
 * Script para gerar Seeders do Phinx a partir de um arquivo de backup SQL.
 * Analisa instruções INSERT, incluindo as que possuem múltiplos valores e quebras de linha.
 */

echo "Iniciando gerador de seeders a partir de gestor.sql...\n";

// --- Configuração dos Caminhos ---
$sqlFilePath = __DIR__ . '/../../../conn2flow-old/banco/backup/gestor.sql';
$seedsPath = __DIR__ . '/../db/seeds/';

// --- Validação dos Caminhos ---
if (!file_exists($sqlFilePath)) {
    die("Erro: Arquivo SQL não encontrado em: $sqlFilePath\n");
}
if (!is_dir($seedsPath)) {
    mkdir($seedsPath, 0755, true);
}

// --- Leitura do Arquivo SQL ---
// Lê o arquivo como um array de linhas, ignorando linhas vazias para otimizar
$lines = file($sqlFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (empty($lines)) {
    die("Erro: O arquivo SQL está vazio ou não pôde ser lido.\n");
}

$insertsByTable = [];
$tablesToIgnore = [
    'phinxlog', 'sessoes', 'sessoes_variaveis', 'acessos', 
    'tokens', 'plataforma_tokens', 'usuarios_tokens', 
    'hosts_tokens', 'hosts_usuarios_tokens'
];

echo "Analisando o arquivo SQL...\n";

// #############################################################################
// ### INÍCIO DA SEÇÃO REFATORADA ###
// Nova lógica de parsing, linha a linha, para montar instruções INSERT completas.
// #############################################################################

$insertCount = 0;
$totalRowsProcessed = 0;
$current_statement = '';
$in_insert_block = false;

foreach ($lines as $line) {
    $trimmed_line = trim($line);
    
    // Ignora comentários SQL e linhas de bloqueio de tabela
    if (empty($trimmed_line) || strpos($trimmed_line, '--') === 0 || stripos($trimmed_line, 'LOCK TABLES') === 0 || stripos($trimmed_line, 'UNLOCK TABLES') === 0) {
        continue;
    }

    // Detecta o início de uma nova instrução INSERT
    if (stripos($trimmed_line, 'INSERT INTO') === 0) {
        $current_statement = $trimmed_line;
        $in_insert_block = true;
    } elseif ($in_insert_block) {
        // Concatena as linhas de valores, adicionando um espaço para garantir a separação
        $current_statement .= ' ' . $trimmed_line;
    }

    // Se a instrução coletada termina com ';', ela está completa e pronta para ser processada
    if ($in_insert_block && substr($current_statement, -1) === ';') {
        $insertCount++;
        
        // Regex para extrair as partes da instrução INSERT completa
        $insertPattern = '/INSERT INTO `?(\w+)`?\s*\((.*?)\)\s*VALUES\s*(.*)/is';
        
        if (preg_match($insertPattern, $current_statement, $match)) {
            $tableName = $match[1];
            
            if (in_array($tableName, $tablesToIgnore)) {
                $in_insert_block = false;
                $current_statement = '';
                continue;
            }

            if (!isset($insertsByTable[$tableName])) {
                $insertsByTable[$tableName] = [];
            }

            $columnsStr = $match[2];
            $columns = array_map(fn($col) => trim($col, '` '), explode(',', $columnsStr));

            $valuesStr = rtrim(trim($match[3]), ';'); // Pega o bloco de valores e remove o ';' final
            
            $trimmedValues = trim($valuesStr);
            if (substr($trimmedValues, 0, 1) === '(' && substr($trimmedValues, -1) === ')') {
                 $trimmedValues = substr($trimmedValues, 1, -1);
            }

            $rowStrings = preg_split('/\)\s*,\s*\(/', $trimmedValues);

            foreach ($rowStrings as $rowContent) {
                // Usa str_getcsv para tratar corretamente valores que contêm vírgulas dentro de aspas
                $values = str_getcsv($rowContent, ',', "'");
                
                if (count($values) !== count($columns)) {
                    echo "AVISO: Contagem de colunas/valores inconsistente para a tabela '{$tableName}'. Linha ignorada.\n";
                    continue;
                }

                // Combina colunas e valores, tratando o valor literal 'NULL'
                $rowData = [];
                foreach ($columns as $index => $colName) {
                    $value = $values[$index];
                    if (is_string($value) && strtoupper(trim($value)) === 'NULL') {
                        $rowData[$colName] = null;
                    } else {
                        $rowData[$colName] = $value;
                    }
                }

                $insertsByTable[$tableName][] = $rowData;
                $totalRowsProcessed++;
            }
        }
        
        // Reseta para a próxima instrução
        $in_insert_block = false;
        $current_statement = '';
    }
}

echo "Análise concluída. Encontradas e processadas {$insertCount} instruções INSERT, com um total de {$totalRowsProcessed} registros.\n";

// #############################################################################
// ### FIM DA SEÇÃO REFATORADA ###
// #############################################################################


// --- Geração dos Arquivos de Seeder ---

if (empty($insertsByTable)) {
    echo "Nenhum dado de INSERT válido encontrado para gerar seeders.\n";
    exit(0);
}

echo "Gerando " . count($insertsByTable) . " arquivo(s) de seeder...\n";

foreach ($insertsByTable as $tableName => $data) {
    // --- ECHO DE DEBUG ADICIONADO CONFORME SUA SUGESTÃO ---
    echo "--> Processando tabela '{$tableName}' com " . count($data) . " registros para gerar seeder...\n";

    $className = toCamelCase($tableName, true) . 'Seeder';
    $fileName = $seedsPath . $className . '.php';

    $dataFormatted = formatDataForPhp($data);

    $seederContent = <<<PHP
<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class {$className} extends AbstractSeed
{
    public function run(): void
    {
        \$data = {$dataFormatted};

        if (count(\$data) > 0) {
            \$table = \$this->table('{$tableName}');
            // Esvazia a tabela antes de inserir para evitar duplicatas
            \$table->truncate(); 
            \$table->insert(\$data)->saveData();
        }
    }
}
PHP;

    file_put_contents($fileName, $seederContent);
    echo "    Seeder gerado: {$className}.php\n";
}

echo "Processo concluído com sucesso!\n";


// --- Funções Auxiliares ---

function toCamelCase(string $string, bool $capitalizeFirstCharacter = true): string
{
    $str = str_replace(['-', '_'], ' ', $string);
    $str = ucwords($str);
    $str = str_replace(' ', '', $str);

    if (!$capitalizeFirstCharacter) {
        return lcfirst($str);
    }

    return $str;
}

function formatDataForPhp(array $data): string
{
    $output = "[\n";
    foreach ($data as $row) {
        $output .= "            [\n";
        foreach ($row as $key => $value) {
            $keyEscaped = addslashes($key);
            $valueFormatted = var_export($value, true);
            $output .= "                '{$keyEscaped}' => {$valueFormatted},\n";
        }
        $output .= "            ],\n";
    }
    $output .= "        ]";
    return $output;
}

