---
title: "Biblioteca db-data.php"
description: "Tabelas JSON completas, partes de 80 MiB e manifestos com integridade."
section: reference
order: 100
sources:
  - gestor/bibliotecas/db-data.php
verified_at: 914c7b10
---

# Biblioteca db-data.php


O limite padrão por parte é 80 MiB, incluindo a estrutura JSON. Um registro maior que o limite gera exceção; nunca é cortado. Tabelas pequenas continuam monolíticas. O leitor valida sequência, bytes, SHA-256 e totais antes de devolver dados. Não publique tabela parcial: sincronização, recuperação e ZIP usam o leitor compartilhado. O escritor preserva a ordem recebida; o chamador pode usar db_data_order_rows antes. A leitura ainda retorna todos os registros em memória: o fracionamento limita arquivos, não garante memória constante.

- `db_data_base`: Valida o nome da tabela e monta o prefixo PascalCaseData.
- `db_data_order_rows`: Ordena pela lista de colunas, preservando a ordem definida pelo exportador.
- `db_data_locked`: Trava compartilhada/exclusiva por diretório, liberada em finally.
- `db_data_json`: Valida JSON e, opcionalmente, lista de registros; inválido gera exceção.
- `db_data_manifest_unlocked`: Valida manifesto e todas as partes; chamador já segura a trava.
- `db_data_table_manifest`: Retorna manifesto validado ou null quando não existe.
- `db_data_read_table`: Lê todas as partes ou formato monolítico/legado; partes sem manifesto geram erro.
- `db_data_list_tables`: Enumera tabelas pelos nomes monolíticos e manifestos.
- `db_data_table_files`: Retorna somente arquivos de tabela inteira validada.
- `db_data_zip_add_tables`: Adiciona todas as partes e manifestos ao ZIP; falha de inclusão lança exceção.
- `db_data_table_checksum`: Mantém chave histórica; monolítico usa md5_file, partes usam MD5 dos metadados.
- `db_data_stage_write`: Confere quantidade escrita; tenta até cinco vezes.
- `db_data_publish`: Publica via rename, com até cinco tentativas.
- `db_data_atomic_write`: Não regrava conteúdo idêntico; temporário e troca para alterações.
- `db_data_write_table`: Mantém registros inteiros, publica partes antes do manifesto e remove formato obsoleto.

## Referência extraída de funções

<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/db-data.php` por `c2f docs:extract` — 15 funções. Não edite dentro deste bloco.

- `db_data_base(string $table): string` — [linha 7](../../../../../gestor/bibliotecas/db-data.php#L7)
- `db_data_order_rows(array $rows, array $columns): array` — [linha 15](../../../../../gestor/bibliotecas/db-data.php#L15)
  Exporters supply natural keys or PKs; the storage writer preserves this order verbatim.
- `db_data_locked(string $dir, int $mode, callable $operation)` — [linha 31](../../../../../gestor/bibliotecas/db-data.php#L31)
  A directory-wide lock also protects transitions between the two formats.
- `db_data_json(string $path, bool $list = false): array` — [linha 45](../../../../../gestor/bibliotecas/db-data.php#L45)
- `db_data_manifest_unlocked(string $table, string $dir): ?array` — [linha 61](../../../../../gestor/bibliotecas/db-data.php#L61)
  Internal: callers hold the directory lock. Validate every part before returning.
- `db_data_table_manifest(string $table, string $dir): ?array` — [linha 101](../../../../../gestor/bibliotecas/db-data.php#L101)
- `db_data_read_table(string $table, string $dir): array` — [linha 107](../../../../../gestor/bibliotecas/db-data.php#L107)
- `db_data_list_tables(string $dir): array` — [linha 130](../../../../../gestor/bibliotecas/db-data.php#L130)
- `db_data_table_files(string $table, string $dir): array` — [linha 143](../../../../../gestor/bibliotecas/db-data.php#L143)
  Only export the files of a validated, complete table.
- `db_data_zip_add_tables(ZipArchive $zip, string $dir): void` — [linha 152](../../../../../gestor/bibliotecas/db-data.php#L152)
- `db_data_table_checksum(string $table, string $dir): string` — [linha 163](../../../../../gestor/bibliotecas/db-data.php#L163)
  Per-table checksum remains keyed by the historical monolithic filename.
- `db_data_stage_write(string $path, string $json): string` — [linha 174](../../../../../gestor/bibliotecas/db-data.php#L174)
- `db_data_publish(string $tmp, string $path): void` — [linha 184](../../../../../gestor/bibliotecas/db-data.php#L184)
- `db_data_atomic_write(string $path, string $json): void` — [linha 192](../../../../../gestor/bibliotecas/db-data.php#L192)
- `db_data_write_table(string $table, array $rows, string $dir, array $options = []): array` — [linha 200](../../../../../gestor/bibliotecas/db-data.php#L200)
  Preserve the caller's stable ordering and complete records. All sizes include JSON framing.

<!-- c2f:extract:end -->
