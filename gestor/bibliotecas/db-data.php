<?php
/** Canonical, fail-fast storage for complete seed tables (REQ-234). */
declare(strict_types=1);

const DB_DATA_PART_SIZE_LIMIT = 80 * 1024 * 1024;

function db_data_base(string $table): string {
    if (!preg_match('/^[a-z][a-z0-9_]*$/D', $table)) {
        throw new RuntimeException('DB_DATA_TABLE_INVALID: ' . $table);
    }
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $table))) . 'Data';
}

/** Exporters supply natural keys or PKs; the storage writer preserves this order verbatim. */
function db_data_order_rows(array $rows, array $columns): array {
    usort($rows, static function(array $left, array $right) use ($columns): int {
        foreach ($columns as $column) {
            $a = $left[$column] ?? null; $b = $right[$column] ?? null;
            if ($a === $b) continue;
            if ($a === null) return -1;
            if ($b === null) return 1;
            $diff = is_int($a) && is_int($b) ? $a <=> $b : strcmp((string)$a, (string)$b);
            if ($diff !== 0) return $diff;
        }
        return 0;
    });
    return $rows;
}

/** A directory-wide lock also protects transitions between the two formats. */
function db_data_locked(string $dir, int $mode, callable $operation) {
    $path = realpath($dir);
    if ($path === false) throw new RuntimeException('DB_DATA_DIRECTORY_MISSING: ' . $dir);
    $lock = fopen(sys_get_temp_dir() . '/c2f-db-data-' . hash('sha256', $path) . '.lock', 'c');
    if ($lock === false) throw new RuntimeException('DB_DATA_LOCK_OPEN: ' . $dir);
    try {
        if (!flock($lock, $mode)) throw new RuntimeException('DB_DATA_LOCK_FAILED: ' . $dir);
        return $operation();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function db_data_json(string $path, bool $list = false): array {
    $raw = file_get_contents($path);
    if ($raw === false) throw new RuntimeException('DB_DATA_READ_FAILED: ' . $path);
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    try { $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
    catch (JsonException $e) { throw new RuntimeException('DB_DATA_JSON_INVALID: ' . $path, 0, $e); }
    if (!is_array($data) || ($list && (!array_is_list($data) || !str_starts_with(ltrim($raw), '[')))) {
        throw new RuntimeException('DB_DATA_STRUCTURE_INVALID: ' . $path);
    }
    if ($list) foreach ($data as $row) {
        if (!is_array($row)) throw new RuntimeException('DB_DATA_RECORD_INVALID: ' . $path);
    }
    return $data;
}

/** Internal: callers hold the directory lock. Validate every part before returning. */
function db_data_manifest_unlocked(string $table, string $dir): ?array {
    $base = db_data_base($table);
    $path = $dir . '/' . $base . '.manifest.json';
    if (!is_file($path)) return null;
    $m = db_data_json($path);
    if (($m['schema_version'] ?? null) !== 1 || ($m['table'] ?? null) !== $table
        || ($m['data_file_base'] ?? null) !== $base || !is_array($m['parts'] ?? null)
        || !array_is_list($m['parts']) || !is_int($m['total_parts'] ?? null)
        || $m['total_parts'] < 1 || $m['total_parts'] !== count($m['parts'])
        || !is_int($m['total_records'] ?? null) || !is_int($m['total_bytes'] ?? null)
        || !is_string($m['generated_at'] ?? null)) {
        throw new RuntimeException('DB_DATA_MANIFEST_INVALID: ' . $path);
    }
    $records = $bytes = 0;
    foreach ($m['parts'] as $i => $part) {
        $file = sprintf('%s.part-%03d.json', $base, $i + 1);
        if (!is_array($part) || ($part['index'] ?? null) !== $i + 1 || ($part['file'] ?? null) !== $file
            || !is_int($part['records'] ?? null) || $part['records'] < 1
            || !is_int($part['size_bytes'] ?? null) || $part['size_bytes'] < 2
            || !is_string($part['sha256'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $part['sha256'])) {
            throw new RuntimeException('DB_DATA_PART_METADATA_INVALID: ' . $path);
        }
        $partPath = $dir . '/' . $file;
        if (!is_file($partPath)) throw new RuntimeException('DB_DATA_PART_MISSING: ' . $partPath);
        clearstatcache(true, $partPath);
        if (filesize($partPath) !== $part['size_bytes'] || hash_file('sha256', $partPath) !== $part['sha256']) {
            throw new RuntimeException('DB_DATA_PART_INTEGRITY: ' . $partPath);
        }
        if (count(db_data_json($partPath, true)) !== $part['records']) {
            throw new RuntimeException('DB_DATA_PART_RECORD_COUNT: ' . $partPath);
        }
        $records += $part['records'];
        $bytes += $part['size_bytes'];
    }
    if ($records !== $m['total_records'] || $bytes !== $m['total_bytes']) {
        throw new RuntimeException('DB_DATA_MANIFEST_TOTALS: ' . $path);
    }
    return $m;
}

function db_data_table_manifest(string $table, string $dir): ?array {
    db_data_base($table);
    if (!is_dir($dir)) return null;
    return db_data_locked($dir, LOCK_SH, fn() => db_data_manifest_unlocked($table, $dir));
}

function db_data_read_table(string $table, string $dir): array {
    $base = db_data_base($table);
    if (!is_dir($dir)) return [];
    return db_data_locked($dir, LOCK_SH, function() use ($table, $dir, $base) {
        $m = db_data_manifest_unlocked($table, $dir);
        if ($m !== null) {
            $rows = [];
            foreach ($m['parts'] as $part) {
                foreach (db_data_json($dir . '/' . $part['file'], true) as $row) $rows[] = $row;
            }
            return $rows;
        }
        $path = $dir . '/' . $base . '.json';
        // Legacy snake_case filenames remain readable.
        if (!is_file($path)) $path = $dir . '/' . $table . 'Data.json';
        if (is_file($path)) return db_data_json($path, true);
        if (glob($dir . '/' . $base . '.part-*.json')) {
            throw new RuntimeException('DB_DATA_MANIFEST_MISSING: ' . $base);
        }
        return [];
    });
}

function db_data_list_tables(string $dir): array {
    $tables = [];
    foreach (glob(rtrim($dir, '/\\') . '/*Data*.json') ?: [] as $path) {
        if (!preg_match('/^([A-Za-z][A-Za-z0-9_]*)Data(?:\.manifest)?\.json$/D', basename($path), $m)) continue;
        $table = strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $m[1]));
        if (str_contains($m[1], '_')) $table = strtolower($m[1]);
        $tables[$table] = true;
    }
    ksort($tables);
    return array_keys($tables);
}

/** Only export the files of a validated, complete table. */
function db_data_table_files(string $table, string $dir): array {
    $m = db_data_table_manifest($table, $dir);
    if ($m !== null) return array_merge([db_data_base($table) . '.manifest.json'], array_column($m['parts'], 'file'));
    db_data_read_table($table, $dir);
    $file = db_data_base($table) . '.json';
    if (is_file($dir . '/' . $file)) return [$file];
    return is_file($dir . '/' . $table . 'Data.json') ? [$table . 'Data.json'] : [];
}

function db_data_zip_add_tables(ZipArchive $zip, string $dir): void {
    foreach (db_data_list_tables($dir) as $table) {
        foreach (db_data_table_files($table, $dir) as $file) {
            if (!$zip->addFile($dir . '/' . $file, $file)) {
                throw new RuntimeException('DB_DATA_ZIP_ADD_FAILED: ' . $file);
            }
        }
    }
}

/** Per-table checksum remains keyed by the historical monolithic filename. */
function db_data_table_checksum(string $table, string $dir): string {
    return db_data_locked($dir, LOCK_SH, function() use ($table, $dir) {
        $m = db_data_manifest_unlocked($table, $dir);
        if ($m !== null) return md5(json_encode($m['parts'], JSON_THROW_ON_ERROR));
        $path = $dir . '/' . db_data_base($table) . '.json';
        if (!is_file($path)) $path = $dir . '/' . $table . 'Data.json';
        db_data_json($path, true);
        return md5_file($path);
    });
}

function db_data_stage_write(string $path, string $json): string {
    $tmp = $path . '.tmp';
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        if (@file_put_contents($tmp, $json) === strlen($json)) return $tmp;
        usleep(100000 * $attempt);
    }
    @unlink($tmp);
    throw new RuntimeException('DB_DATA_WRITE_FAILED: ' . $path);
}

function db_data_publish(string $tmp, string $path): void {
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        if (@rename($tmp, $path)) return;
        usleep(100000 * $attempt);
    }
    throw new RuntimeException('DB_DATA_RENAME_FAILED: ' . $path);
}

function db_data_atomic_write(string $path, string $json): void {
    if (is_file($path) && file_get_contents($path) === $json) return;
    $tmp = db_data_stage_write($path, $json);
    try { db_data_publish($tmp, $path); }
    finally { if (is_file($tmp)) unlink($tmp); }
}

/** Preserve the caller's stable ordering and complete records. All sizes include JSON framing. */
function db_data_write_table(string $table, array $rows, string $dir, array $options = []): array {
    $base = db_data_base($table);
    $limit = $options['part_size_limit'] ?? DB_DATA_PART_SIZE_LIMIT;
    if (!is_int($limit) || $limit < 3 || !array_is_list($rows)) throw new RuntimeException('DB_DATA_OPTIONS_INVALID');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('DB_DATA_DIRECTORY_CREATE: ' . $dir);
    // Serialize one record at a time; avoid another complete, 100+ MiB JSON copy.
    $chunks = []; $chunk = ''; $count = 0; $counts = [];
    foreach ($rows as $row) {
        if (!is_array($row)) throw new RuntimeException('DB_DATA_RECORD_INVALID: ' . $table);
        try { $json = json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR); }
        catch (JsonException $e) { throw new RuntimeException('DB_DATA_ENCODE_FAILED: ' . $table, 0, $e); }
        $json = '    ' . str_replace("\n", "\n    ", $json);
        if (strlen($json) + 4 > $limit) throw new RuntimeException('DB_DATA_RECORD_TOO_LARGE: ' . $table);
        if ($count > 0 && strlen($chunk) + strlen($json) + 6 > $limit) {
            $chunks[] = "[\n" . $chunk . "\n]"; $counts[] = $count; $chunk = ''; $count = 0;
        }
        $chunk .= ($count ? ",\n" : '') . $json; $count++;
    }
    $chunks[] = $count ? "[\n" . $chunk . "\n]" : '[]'; $counts[] = $count;
    return db_data_locked($dir, LOCK_EX, function() use ($table, $dir, $base, $chunks, $counts, $limit) {
        $partitioned = count($chunks) > 1 || strlen($chunks[0]) >= $limit;
        $parts = [];
        if ($partitioned) {
            $staged = [];
            try {
            foreach ($chunks as $i => $json) {
                $file = sprintf('%s.part-%03d.json', $base, $i + 1);
                $path = $dir . '/' . $file;
                if (!is_file($path) || file_get_contents($path) !== $json) {
                    $staged[$path] = db_data_stage_write($path, $json);
                }
                $parts[] = ['file' => $file, 'index' => $i + 1, 'records' => $counts[$i], 'size_bytes' => strlen($json), 'sha256' => hash('sha256', $json)];
            }
            // No final part is replaced until every temporary part has been written.
            foreach ($staged as $path => $tmp) db_data_publish($tmp, $path);
            } finally {
                foreach ($staged as $tmp) if (is_file($tmp)) unlink($tmp);
            }
            $m = ['schema_version' => 1, 'table' => $table, 'data_file_base' => $base, 'total_parts' => count($parts),
                'total_records' => array_sum($counts), 'total_bytes' => array_sum(array_column($parts, 'size_bytes')),
                'generated_at' => gmdate('Y-m-d\TH:i:s\Z'), 'parts' => $parts];
            $manifestPath = $dir . '/' . $base . '.manifest.json';
            if (is_file($manifestPath)) {
                $previous = db_data_json($manifestPath);
                if (($previous['parts'] ?? null) === $parts && is_string($previous['generated_at'] ?? null)) {
                    $m['generated_at'] = $previous['generated_at'];
                }
            }
            db_data_atomic_write($manifestPath, json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            db_data_manifest_unlocked($table, $dir);
        } else {
            db_data_atomic_write($dir . '/' . $base . '.json', $chunks[0]);
            $parts = [];
        }
        $keep = array_column($parts, 'file');
        $obsolete = glob($dir . '/' . $base . '.part-*.json') ?: [];
        $obsolete[] = $dir . '/' . $base . ($partitioned ? '.json' : '.manifest.json');
        foreach ($obsolete as $path) {
            if (!in_array(basename($path), $keep, true) && is_file($path) && !unlink($path)) {
                throw new RuntimeException('DB_DATA_CLEANUP_FAILED: ' . $path);
            }
        }
        return ['partitioned' => $partitioned, 'total_parts' => count($chunks), 'total_records' => array_sum($counts), 'total_bytes' => array_sum(array_map('strlen', $chunks))];
    });
}
