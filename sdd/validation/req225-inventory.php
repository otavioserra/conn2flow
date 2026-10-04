<?php
declare(strict_types=1);

/** Read-only source audit. Does not load the runtime or connect to SQL. */
final class Req225Inventory
{
    public static function markupViolations(string $html): array
    {
        // Comments and script bodies are not rendered markup.
        $html = preg_replace('~<!--.*?-->|<script\b[^>]*>.*?</script>~si', '', $html);
        preg_match_all('~<[a-z](?:"[^"]*"|\'[^\']*\'|[^\'">])*>~i', $html, $tags);
        $issues = [];
        foreach ($tags[0] as $tag) {
            if (preg_match('~\sclass\s*=\s*(["\'])(.*?)\1~si', $tag, $class)
                && preg_match('~^ui(?:\s|$)~', $class[2])) {
                $issues[] = 'Fomantic: ' . $tag;
            }
            if (preg_match('~^<button\b~i', $tag) && preg_match('~\stitle\s*=~i', $tag)) {
                $issues[] = 'button title: ' . $tag;
            }
        }
        return $issues;
    }

    public static function scan(string $repo, string $project): array
    {
        if (!is_dir($repo . '/gestor/modulos')) {
            throw new RuntimeException('Repository unavailable: ' . $repo);
        }
        $rows = [];
        foreach (glob($repo . '/gestor/modulos/*', GLOB_ONLYDIR) as $base) {
            $module = basename($base);
            $manifest = $base . '/' . $module . '.json';
            if (!is_file($manifest)) continue;
            $json = json_decode((string)file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
            foreach ($json['resources'] ?? [] as $lang => $resources) {
                foreach ($resources['pages'] ?? [] as $page) {
                    if (($page['layout'] ?? '') !== 'layout-administrativo-tailwind') continue;
                    $relative = 'gestor/modulos/' . $module . '/resources/' . $lang . '/pages/' . $page['id'] . '/' . $page['id'] . '.html';
                    $html = is_file($repo . '/' . $relative) ? (string)file_get_contents($repo . '/' . $relative) : '';
                    $issues = self::markupViolations($html);
                    // interface-listar can render directly from the page metadata.
                    $generated = !is_file($repo . '/' . $relative) && ($page['option'] ?? '') === 'listar';
                    if (!is_file($repo . '/' . $relative) && !$generated) $issues[] = 'HTML absent';
                    if (($page['tailwind_bundle'] ?? false) !== true) $issues[] = 'tailwind_bundle absent';
                    $counts = [];
                    foreach (['input', 'select', 'button', 'textarea'] as $tag) {
                        $counts[$tag] = preg_match_all('~<' . $tag . '\b~i', $html);
                    }
                    $rows[] = [
                        'project' => $project, 'module' => $module, 'language' => $lang,
                        'id' => $page['id'], 'path' => $page['path'] ?? '', 'source' => $relative,
                        'option' => $page['option'] ?? '', 'generated' => $generated, 'counts' => $counts,
                        'fields' => substr_count($html, 'c2fc-campo'),
                        'switches' => substr_count($html, 'c2fc-chave'),
                        'buttons' => substr_count($html, 'c2fc-botao'),
                        'actions' => substr_count($html, 'c2fc-acao'),
                        'tips' => substr_count($html, 'data-c2f-dica') + substr_count($html, 'data-tooltip'),
                        'dialogs' => substr_count($html, 'c2fc-dialogo'),
                        'bundle' => ($page['tailwind_bundle'] ?? false) === true,
                        'list' => in_array('interface-listar-tailwind', array_column($page['tailwind_dependencies'] ?? [], 'id'), true),
                        'violations' => $issues, 'runtime' => 'pending',
                    ];
                }
            }
        }
        usort($rows, static fn(array $a, array $b): int => [$a['module'], $a['id'], $a['language']] <=> [$b['module'], $b['id'], $b['language']]);
        return $rows;
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $core = $argv[2] ?? dirname(__DIR__, 2);
    $site = $argv[1] ?? dirname($core) . '/conn2flow-site';
    $rows = array_merge(Req225Inventory::scan($core, 'core'), Req225Inventory::scan($site, 'site'));
    $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $md = "# req-225 — Inventário preliminar de autoria\n\n";
    $md .= "Snapshot das árvores locais, incluindo alterações ainda sem commit. Uma linha por página/idioma. Não representa o SQL publicado.\n\n";
    $md .= "Contagens de classes são indícios, não aprovação visual. Campos, selects, chaves, botões, dicas, diálogos e listagem gerados por PHP/JS ou componentes precisam de conferência no navegador. Todas as linhas têm runtime pendente.\n\n";
    $md .= "Regenerar: `php sdd/validation/req225-inventory.php [raiz-do-site]`.\n\n";
    $md .= "| Projeto | Página/idioma | Inputs/selects/textarea | Campo/chave | Botão/ação | Dicas/diálogos | Lista/bundle | Divergências estáticas | Runtime |\n|---|---|---|---|---|---|---|---|---|\n";
    foreach ($rows as $row) {
        $c = $row['counts'];
        $md .= '| ' . $row['project'] . ' | `' . $row['id'] . '/' . $row['language'] . '` | ' . $c['input'] . '/' . $c['select'] . '/' . $c['textarea'];
        $md .= ' | ' . $row['fields'] . '/' . $row['switches'] . ' | ' . $row['buttons'] . '/' . $row['actions'];
        $md .= ' | ' . $row['tips'] . '/' . $row['dialogs'] . ' | ' . (int)$row['list'] . '/' . (int)$row['bundle'];
        $md .= ' | ' . count($row['violations']) . ' | pendente |' . "\n";
    }
    foreach (['req225-inventory.json' => $json, 'req225-inventory.md' => $md] as $file => $body) {
        if (file_put_contents(__DIR__ . '/' . $file, $body) === false) throw new RuntimeException('Cannot write ' . $file);
    }
    echo count($rows) . ' page/language rows; ' . count(array_filter($rows, static fn(array $r): bool => $r['violations'] !== [])) . " rows with static violations.\n";
}
