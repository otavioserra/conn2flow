---
title: "db-data.php library"
description: "Complete JSON seed tables, 80 MiB parts and integrity-checked manifests."
section: reference
order: 100
sources:
  - gestor/bibliotecas/db-data.php
verified_at: 914c7b10
---

# db-data.php library


The default part limit is 80 MiB including JSON framing. A record larger than the limit throws; records are never split. Small tables stay monolithic. Readers validate sequence, bytes, SHA-256 and record totals before returning data. Do not deploy a partial table: synchronization, recovery and ZIP packaging share this reader. The writer preserves input order; callers can use db_data_order_rows first. It still returns all records in memory, so partitioning limits file size rather than guaranteeing constant memory.

- `db_data_base`: Validates the table name and builds the PascalCaseData prefix.
- `db_data_order_rows`: Sorts by the column list, preserving exporter-defined order.
- `db_data_locked`: Shared/exclusive directory lock released in finally.
- `db_data_json`: Validates JSON and optional record lists; invalid input throws.
- `db_data_manifest_unlocked`: Validates manifest and all parts; caller already holds the lock.
- `db_data_table_manifest`: Returns a validated manifest or null when absent.
- `db_data_read_table`: Reads all parts or monolithic/legacy format; parts without a manifest throw.
- `db_data_list_tables`: Enumerates tables from monolithic names and manifests.
- `db_data_table_files`: Returns only validated complete-table files.
- `db_data_zip_add_tables`: Adds all parts and manifests to ZIP; failed addition throws.
- `db_data_table_checksum`: Keeps the historical key; monolithic uses md5_file, parts use MD5 of metadata.
- `db_data_stage_write`: Checks bytes written; retries up to five times.
- `db_data_publish`: Publishes by rename, with up to five attempts.
- `db_data_atomic_write`: Does not rewrite identical content; uses temporary file and rename for changes.
- `db_data_write_table`: Keeps whole records, publishes parts before manifest and removes obsolete format.

## Generated function reference

<!-- c2f:extract:start -->

Reference generated from `gestor/bibliotecas/db-data.php` by `c2f docs:extract` — 15 functions. Do not edit inside this block.

- `db_data_base(string $table): string` — [line 7](../../../../../gestor/bibliotecas/db-data.php#L7)
- `db_data_order_rows(array $rows, array $columns): array` — [line 15](../../../../../gestor/bibliotecas/db-data.php#L15)
  Exporters supply natural keys or PKs; the storage writer preserves this order verbatim.
- `db_data_locked(string $dir, int $mode, callable $operation)` — [line 31](../../../../../gestor/bibliotecas/db-data.php#L31)
  A directory-wide lock also protects transitions between the two formats.
- `db_data_json(string $path, bool $list = false): array` — [line 45](../../../../../gestor/bibliotecas/db-data.php#L45)
- `db_data_manifest_unlocked(string $table, string $dir): ?array` — [line 61](../../../../../gestor/bibliotecas/db-data.php#L61)
  Internal: callers hold the directory lock. Validate every part before returning.
- `db_data_table_manifest(string $table, string $dir): ?array` — [line 101](../../../../../gestor/bibliotecas/db-data.php#L101)
- `db_data_read_table(string $table, string $dir): array` — [line 107](../../../../../gestor/bibliotecas/db-data.php#L107)
- `db_data_list_tables(string $dir): array` — [line 130](../../../../../gestor/bibliotecas/db-data.php#L130)
- `db_data_table_files(string $table, string $dir): array` — [line 143](../../../../../gestor/bibliotecas/db-data.php#L143)
  Only export the files of a validated, complete table.
- `db_data_zip_add_tables(ZipArchive $zip, string $dir): void` — [line 152](../../../../../gestor/bibliotecas/db-data.php#L152)
- `db_data_table_checksum(string $table, string $dir): string` — [line 163](../../../../../gestor/bibliotecas/db-data.php#L163)
  Per-table checksum remains keyed by the historical monolithic filename.
- `db_data_stage_write(string $path, string $json): string` — [line 174](../../../../../gestor/bibliotecas/db-data.php#L174)
- `db_data_publish(string $tmp, string $path): void` — [line 184](../../../../../gestor/bibliotecas/db-data.php#L184)
- `db_data_atomic_write(string $path, string $json): void` — [line 192](../../../../../gestor/bibliotecas/db-data.php#L192)
- `db_data_write_table(string $table, array $rows, string $dir, array $options = []): array` — [line 200](../../../../../gestor/bibliotecas/db-data.php#L200)
  Preserve the caller's stable ordering and complete records. All sizes include JSON framing.

<!-- c2f:extract:end -->
