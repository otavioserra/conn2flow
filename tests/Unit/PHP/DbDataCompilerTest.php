<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DbDataCompilerTest extends TestCase {
    public function testCompilerPublishesPartitionsReusesExistingRecordsAndReportsMetadata(): void {
        define('SDD_NO_AUTORUN', true);
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/agents/arquitetura/atualizacao-dados-recursos.php';
        $dir = sys_get_temp_dir() . '/c2f-data-compiler-' . bin2hex(random_bytes(8));
        mkdir($dir . '/resources', 0775, true);
        mkdir($dir . '/modulos');
        mkdir($dir . '/db/data', 0775, true);
        $GLOBALS['GESTOR_DIR'] = $dir . '/';
        $GLOBALS['RESOURCES_DIR'] = $dir . '/resources/';
        $GLOBALS['MODULES_DIR'] = $dir . '/modulos/';
        $GLOBALS['DB_DATA_DIR'] = $dir . '/db/data/';
        $GLOBALS['CLI_ARGS'] = ['only' => 'paginas', 'quiet' => true];
        file_put_contents($dir . '/resources/tables_config.json', json_encode(['tabelas' => ['paginas' => [
            'nome' => 'paginas', 'id' => 'id', 'id_numerico' => 'id_paginas',
            'config' => ['strategy' => 'natural_key', 'natural_key_columns' => ['language', 'modulo', 'id']],
        ]]]));
        $payload = str_repeat('x', 2 * 1024 * 1024);
        $rows = [];
        for ($i = 0; $i < 42; $i++) $rows[] = ['id' => sprintf('page-%02d', $i), 'language' => 'pt-br', 'modulo' => '', 'html' => $payload, 'versao' => 7, 'checksum' => 'fixture'];
        try {
            atualizarDados([], ['pagesData' => $rows]);
            self::assertSame($rows, db_data_read_table('paginas', $GLOBALS['DB_DATA_DIR']));
            $existing = carregarDadosExistentes();
            self::assertCount(42, $existing['paginas']);
            self::assertSame(7, $existing['paginas']['pt-br||page-41']['versao']);
            self::assertSame('fixture', $existing['paginas']['pt-br||page-41']['checksum']);
            gerarSchemaMetadata();
            $meta = json_decode(file_get_contents($dir . '/db/data/schema-metadata.json'), true);
            self::assertTrue($meta['tables']['paginas']['partitioned']);
            self::assertSame(2, $meta['tables']['paginas']['total_parts']);
            self::assertFileDoesNotExist($dir . '/db/data/PaginasData.json');
            atualizarDados([], ['pagesData' => array_slice($rows, 0, 1)]);
            gerarSchemaMetadata();
            $meta = json_decode(file_get_contents($dir . '/db/data/schema-metadata.json'), true);
            self::assertFalse($meta['tables']['paginas']['partitioned']);
            self::assertSame(1, $meta['tables']['paginas']['total_parts']);
        } finally {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            rmdir($dir);
        }
    }
}
