<?php

/**
 * Script para gerar Migrations do Phinx a partir de um arquivo de backup SQL.
 * Esta versão é mais robusta, analisando tipos de texto, chaves, padrões e outros detalhes.
 */

echo "Iniciando gerador de migrações a partir de gestor.sql...\n";

$sqlFilePath = __DIR__ . '/../../../conn2flow-old/banco/backup/gestor.sql';
$migrationsPath = __DIR__ . '/../db/migrations/';

if (!file_exists($sqlFilePath)) {
    die("Erro: Arquivo SQL não encontrado em: $sqlFilePath\n");
}

$sqlContent = file_get_contents($sqlFilePath);

// ============================================================================
// CORREÇÃO: Bloco de pré-análise para ler ALTER TABLE de todo o arquivo
// ============================================================================
$tablePks = [];
$tableAis = [];

echo "Analisando definições de chaves primárias e auto-incremento...\n";
// Encontra todas as definições de Chave Primária via ALTER TABLE
if (preg_match_all('/ALTER TABLE `?(\w+)`?\s+ADD PRIMARY KEY \(`?(\w+)`?\)/is', $sqlContent, $pkMatches, PREG_SET_ORDER)) {
    foreach ($pkMatches as $match) {
        $tablePks[$match[1]] = [$match[2]]; // Armazena como array para consistência
    }
}

// Encontra todas as definições de AUTO_INCREMENT via ALTER TABLE
if (preg_match_all('/ALTER TABLE `?(\w+)`?\s+MODIFY `?(\w+)`? .*?AUTO_INCREMENT/is', $sqlContent, $aiMatches, PREG_SET_ORDER)) {
    foreach ($aiMatches as $match) {
        $tableAis[$match[1]] = $match[2];
    }
}

echo "Chaves primárias encontradas via ALTER: " . count($tablePks) . "\n";
echo "Colunas AUTO_INCREMENT encontradas via ALTER: " . count($tableAis) . "\n";
// ============================================================================

// Divide o arquivo SQL em múltiplos comandos CREATE TABLE
preg_match_all('/CREATE TABLE.+?;/is', $sqlContent, $statements);
$statements = $statements[0];

if (empty($statements) || count($statements) == 0) {
    die("Nenhuma declaração CREATE TABLE encontrada no arquivo gestor.sql.\n");
}

echo "Encontradas " . count($statements) . " tabelas para gerar.\n";

foreach ($statements as $statement) {
    // Extrai o nome da tabela
    if (!preg_match('/CREATE TABLE\s*(?:IF NOT EXISTS\s*)?`?(\w+)`?/i', $statement, $matches)) {
        continue;
    }
    $tableName = $matches[1];
    $className = toCamelCase("Create_{$tableName}_Table");
    $fileNamePart = date('YmdHis') . '_' . strtolower("create_{$tableName}_table");

    echo "Gerando migração para a tabela: {$tableName}...\n";

    // Extrai as definições de coluna
    if (!preg_match('/\((.*)\)/s', $statement, $columnMatches)) {
        continue;
    }
    $columnsContent = $columnMatches[1];

    $lines = preg_split('/,\s*[\r\n]+/', $columnsContent);
    
    $phinxColumns = [];
    $phinxIndexes = [];

    // CORREÇÃO: Usa os dados pré-analisados do arquivo SQL completo
    $primaryKeyColumns = $tablePks[$tableName] ?? [];
    $autoIncrementColumn = $tableAis[$tableName] ?? null;

    // Tenta encontrar PK e AI dentro do CREATE TABLE como fallback, caso não tenha sido definido por ALTER TABLE
    if (empty($primaryKeyColumns) && preg_match('/PRIMARY KEY\s*\((.*?)\)/i', $columnsContent, $pkMatch)) {
        preg_match_all('/`(\w+)`/', $pkMatch[1], $pkColMatches);
        if (!empty($pkColMatches[1])) {
            $primaryKeyColumns = $pkColMatches[1];
        }
    }
    if (empty($autoIncrementColumn) && preg_match('/`(\w+)`\s+.*AUTO_INCREMENT/i', $columnsContent, $aiMatch)) {
        $autoIncrementColumn = $aiMatch[1];
    }
    
    $isAutoIncrementPk = (!empty($primaryKeyColumns) && count($primaryKeyColumns) === 1 && $primaryKeyColumns[0] === $autoIncrementColumn);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Chave Primária - já foi pré-analisada, então pulamos a linha
        if (stripos(trim($line), 'PRIMARY KEY') === 0) {
            continue;
        }

        // Índices (KEY)
        if (preg_match('/(UNIQUE\s+)?KEY\s+`(\w+)`\s+\((.*)\)/i', $line, $indexMatch)) {
            $indexName = $indexMatch[2];
            $indexColsStr = $indexMatch[3];
            $indexCols = preg_match_all('/`(\w+)`/', $indexColsStr, $colMatches) ? $colMatches[1] : [];

            if (!empty($indexCols)) {
                $options = ['name' => $indexName];
                if (!empty($indexMatch[1])) { // is UNIQUE
                    $options['unique'] = true;
                }
                $colsString = "'" . implode("', '", $indexCols) . "'";
                $optionsString = var_export($options, true);
                $phinxIndexes[] = "->addIndex([{$colsString}], {$optionsString})";
            }
            continue;
        }

        // Definições de Coluna
        if (preg_match('/^`(\w+)`\s+(.+)/i', $line, $fieldMatch)) {
            $colName = $fieldMatch[1];
            $def = trim($fieldMatch[2]);

            // Se a coluna for a chave primária de auto-incremento, Phinx cuidará dela.
            if ($isAutoIncrementPk && $colName === $autoIncrementColumn) {
                continue; 
            }

            $typeMatch = preg_match('/^([a-z]+)(?:\((.*?)\))?/i', $def, $typeInfo);
            $colType = strtoupper($typeInfo[1]);
            $limit = $typeInfo[2] ?? null;

            list($phinxType, $phinxOptions) = mapSqlTypeToPhinx($colType, $limit);
            $options = [];
            $options = array_merge($options, $phinxOptions);

            if (stripos($def, 'NOT NULL') === false) {
                $options['null'] = true;
            }

            if (preg_match("/DEFAULT\s+(NULL|'[^']*'|[\d\.]+)/i", $def, $defaultMatch)) {
                $defaultValue = $defaultMatch[1];
                if (strtoupper($defaultValue) === 'NULL') {
                    $options['default'] = null;
                } elseif (is_numeric($defaultValue)) {
                    $options['default'] = $defaultValue;
                } else {
                    $options['default'] = trim($defaultValue, "'");
                }
            }

            if (stripos($def, 'unsigned') !== false) {
                $options['signed'] = false;
            }

            if (preg_match("/COMMENT\s+'(.*?)'/i", $def, $commentMatch)) {
                $options['comment'] = addslashes($commentMatch[1]);
            }

            $optionsCode = '';
            if (!empty($options)) {
                $opts = [];
                foreach ($options as $key => $value) {
                    if (is_bool($value)) {
                        $opts[] = "'$key' => " . ($value ? 'true' : 'false');
                    } else if (is_null($value)) {
                        $opts[] = "'$key' => null";
                    } else if (is_string($value) && !is_numeric($value) && !preg_match('/^Phinx\\\\Db\\\\Adapter/', $value)) {
                        $opts[] = "'$key' => '" . addslashes($value) . "'";
                    } else {
                        $opts[] = "'$key' => $value";
                    }
                }
                $optionsCode = ', [' . implode(', ', $opts) . ']';
            }

            $phinxColumns[] = "->addColumn('{$colName}', '{$phinxType}'{$optionsCode})";
        }
    }

    $phinxTableOptions = "[]";
    if (!empty($primaryKeyColumns)) {
        if ($isAutoIncrementPk) {
            // Sempre especifica o nome da coluna de ID, mesmo que seja 'id'.
            // Isso torna o código mais explícito e evita que o Phinx tente adivinhar.
            $phinxTableOptions = "['id' => '{$autoIncrementColumn}']";
        } else {
            $pkString = "'" . implode("', '", $primaryKeyColumns) . "'";
            $phinxTableOptions = "['id' => false, 'primary_key' => [{$pkString}]]";
        }
    }

    $columnsCode = implode("\n              ", $phinxColumns);
    $indexesCode = !empty($phinxIndexes) ? "\n              " . implode("\n              ", $phinxIndexes) : '';

    // Gera o conteúdo do arquivo de migração
    $migrationContent = <<<PHP
<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class {$className} extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        \$table = \$this->table('{$tableName}', {$phinxTableOptions});
        \$table{$columnsCode}{$indexesCode}
              ->create();
    }
}
PHP;

    file_put_contents($migrationsPath . $fileNamePart . '.php', $migrationContent);
    sleep(1); // Garante timestamps únicos para cada arquivo
}

function toCamelCase(string $string): string
{
    return str_replace('_', '', ucwords($string, '_'));
}

function mapSqlTypeToPhinx(string $type, ?string $limit): array
{
    $type = strtoupper($type);
    $options = [];

    switch ($type) {
        case 'TINYINT':
            if ($limit == '1') return ['boolean', $options];
            $options['limit'] = 'Phinx\Db\Adapter\MysqlAdapter::INT_TINY';
            return ['integer', $options];
        case 'INT':
        case 'INTEGER':
            return ['integer', $options];
        case 'BIGINT':
            $options['limit'] = 'Phinx\Db\Adapter\MysqlAdapter::INT_BIG';
            return ['integer', $options];
        case 'VARCHAR':
            if ($limit) $options['limit'] = $limit;
            return ['string', $options];
        case 'CHAR':
            if ($limit) $options['limit'] = $limit;
            return ['char', $options];
        case 'TEXT':
            return ['text', $options];
        case 'TINYTEXT':
            $options['limit'] = 'Phinx\Db\Adapter\MysqlAdapter::TEXT_TINY';
            return ['text', $options];
        case 'MEDIUMTEXT':
            $options['limit'] = 'Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM';
            return ['text', $options];
        case 'LONGTEXT':
            $options['limit'] = 'Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG';
            return ['text', $options];
        case 'DATETIME':
            return ['datetime', $options];
        case 'TIMESTAMP':
            return ['timestamp', $options];
        case 'DATE':
            return ['date', $options];
        case 'FLOAT':
            return ['float', $options];
        case 'DECIMAL':
        case 'DOUBLE':
            if ($limit) {
                list($precision, $scale) = explode(',', $limit);
                $options['precision'] = trim($precision);
                $options['scale'] = trim($scale);
            }
            return ['decimal', $options];
        case 'ENUM':
            $options['values'] = $limit;
            return ['enum', $options];
        default:
            return ['string', $options];
    }
}

echo "Migrações geradas com sucesso na pasta 'gestor/db/migrations/'!\n";

?>
