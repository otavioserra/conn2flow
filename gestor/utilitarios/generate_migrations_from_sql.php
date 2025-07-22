<?php

echo "Iniciando gerador de migrações a partir de gestor.sql...\n";

$sqlFilePath = __DIR__ . '/../banco/gestor.sql';
$migrationsPath = __DIR__ . '/../db/migrations/';

if (!file_exists($sqlFilePath)) {
    die("Erro: Arquivo gestor.sql não encontrado em 'gestor/banco/gestor.sql'.\n");
}

$sqlContent = file_get_contents($sqlFilePath);

// Divide o arquivo SQL em múltiplos comandos CREATE TABLE
$statements = preg_split('/CREATE TABLE/i', $sqlContent, -1, PREG_SPLIT_NO_EMPTY);

if (empty($statements)) {
    die("Nenhuma declaração CREATE TABLE encontrada no arquivo gestor.sql.\n");
}

echo "Encontradas " . count($statements) . " tabelas para gerar.\n";

foreach ($statements as $statement) {
    $statement = 'CREATE TABLE ' . $statement;

    // Extrai o nome da tabela
    if (!preg_match('/`(\w+)`\.`(\w+)`/', $statement, $matches)) {
        continue;
    }
    $tableName = $matches[2];
    $baseName = "Create_{$tableName}_Table";
    $className = toCamelCase($baseName); // Para o nome da classe: CreateUsuariosTable
    $fileNamePart = strtolower($baseName); // Para o nome do arquivo: create_usuarios_table

    echo "Gerando migração para a tabela: {$tableName} (Arquivo: {$fileNamePart}.php)...\n";

    // Extrai as definições de coluna
    preg_match('/\((.*)\)/s', $statement, $columnMatches);
    $columnsContent = trim($columnMatches[1]);
    $columnLines = preg_split('/,\s*[\r\n]+/', $columnsContent);

    $phinxColumns = [];
    $primaryKey = '';

    foreach ($columnLines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Identifica a chave primária
        if (preg_match('/PRIMARY KEY\s*\(`(\w+)`\)/i', $line, $pkMatch)) {
            $primaryKey = $pkMatch[1];
            continue;
        }

        // Extrai nome da coluna, tipo e opções
        if (preg_match('/`(\w+)`\s+([A-Z]+)(?:\((\d+)\))?(.*)/i', $line, $fieldMatch)) {
            $colName = $fieldMatch[1];
            $colType = strtoupper($fieldMatch[2]);
            $limit = $fieldMatch[3] ?? null;
            $optionsStr = $fieldMatch[4];

            $options = [];
            if (strpos($optionsStr, 'NULL') !== false && strpos($optionsStr, 'NOT NULL') === false) {
                $options['null'] = 'true';
            }

            if ($limit) {
                $options['limit'] = $limit;
            }

            $phinxType = mapSqlTypeToPhinx($colType);

            if ($colName === $primaryKey && strpos($optionsStr, 'AUTO_INCREMENT') !== false) {
                $options['identity'] = 'true';
            }

            $optionsCode = empty($options) ? '' : ', [' . implode(', ', array_map(fn($k, $v) => "'$k' => $v", array_keys($options), $options)) . ']';
            $phinxColumns[] = "->addColumn('{$colName}', '{$phinxType}'{$optionsCode})";
        }
    }

    $phinxTableOptions = $primaryKey ? "['id' => false, 'primary_key' => ['{$primaryKey}']]" : '';
    $columnsCode = implode("\n              ", $phinxColumns);

    // Gera o conteúdo do arquivo de migração
    $migrationContent = <<<PHP
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class {$className} extends AbstractMigration
{
    public function change(): void
    {
        \$table = \$this->table('{$tableName}', {$phinxTableOptions});
        \$table{$columnsCode}
              ->create();
    }
}
PHP;

    $fileName = date('YmdHis') . '_' . $fileNamePart . '.php';
    file_put_contents($migrationsPath . $fileName, $migrationContent);
    sleep(1); // Garante timestamps únicos para cada arquivo
}

function toCamelCase($string) {
    return str_replace('_', '', ucwords($string, '_'));
}

function mapSqlTypeToPhinx($type) {
    $map = ['INT' => 'integer', 'VARCHAR' => 'string', 'DATETIME' => 'datetime', 'CHAR' => 'char', 'TINYINT' => 'boolean', 'TEXT' => 'text', 'DECIMAL' => 'decimal', 'FLOAT' => 'float', 'DATE' => 'date'];
    return $map[$type] ?? 'string';
}

echo "Migrações geradas com sucesso na pasta 'gestor/db/migrations/'!\n";

?>