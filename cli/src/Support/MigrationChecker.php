<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support;

/**
 * Checagem de migrações antes do deploy — req-197 / BATCH-201 (BL-028, achado 7).
 *
 * No servidor, `db/migrations` junta as migrações do core, dos plugins e do projeto numa pasta só, e o
 * Phinx recusa tudo quando duas têm a mesma versão ("Duplicate migration") ou a mesma classe. Agentes
 * em paralelo criam esse choque sem perceber (ex.: `20260929140000` duas vezes). Aqui o choque aparece
 * no repositório, antes de qualquer envio.
 *
 * Regra: agrupa por versão e por classe (convenção do Phinx: CamelCase do nome). Há choque quando o
 * mesmo grupo tem **nomes de arquivo diferentes**; o mesmo arquivo em duas origens (a cópia do core no
 * espelho do projeto) não é choque.
 */
final class MigrationChecker
{
    private const PATTERN = '/^(\d{14})_([a-z0-9_]+)\.php$/';

    /**
     * @param array<string, string> $sources rótulo => pasta de migrações (pastas inexistentes são ignoradas)
     * @return array{files: int, duplicates: list<array{kind: string, key: string, files: list<string>}>, invalid: list<string>}
     */
    public function check(array $sources): array
    {
        $byVersion = [];
        $byClass = [];
        $invalid = [];
        $count = 0;

        foreach ($sources as $label => $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (scandir($dir) ?: [] as $file) {
                if ($file === '.' || $file === '..' || !str_ends_with($file, '.php')) {
                    continue;
                }
                if (!preg_match(self::PATTERN, $file, $m)) {
                    $invalid[] = $label . ': ' . $file;
                    continue;
                }
                $count++;
                $class = str_replace(' ', '', ucwords(str_replace('_', ' ', $m[2])));
                $byVersion[$m[1]][$file][] = $label;
                $byClass[$class][$file][] = $label;
            }
        }

        $duplicates = [];
        foreach (['version' => $byVersion, 'class' => $byClass] as $kind => $groups) {
            ksort($groups);
            foreach ($groups as $key => $files) {
                if (count($files) < 2) {
                    continue;
                }
                $list = [];
                foreach ($files as $file => $labels) {
                    $list[] = $file . ' (' . implode(', ', array_unique($labels)) . ')';
                }
                $duplicates[] = ['kind' => $kind, 'key' => (string)$key, 'files' => $list];
            }
        }

        return ['files' => $count, 'duplicates' => $duplicates, 'invalid' => $invalid];
    }

    /**
     * Origens padrão: core, plugins do core e, com o projeto, as migrações e os plugins dele.
     *
     * @return array<string, string>
     */
    public static function defaultSources(string $rootPath, ?string $projectGestorPath = null): array
    {
        $sources = ['core' => $rootPath . '/gestor/db/migrations'];
        foreach (glob($rootPath . '/gestor/plugins/*/db/migrations', GLOB_ONLYDIR) ?: [] as $dir) {
            $sources['plugin:' . basename(dirname($dir, 2))] = $dir;
        }
        if ($projectGestorPath !== null && $projectGestorPath !== '') {
            $sources['projeto'] = rtrim($projectGestorPath, '/\\') . '/db/migrations';
            foreach (glob(rtrim($projectGestorPath, '/\\') . '/plugins/*/db/migrations', GLOB_ONLYDIR) ?: [] as $dir) {
                $sources['projeto-plugin:' . basename(dirname($dir, 2))] = $dir;
            }
        }
        return $sources;
    }
}
