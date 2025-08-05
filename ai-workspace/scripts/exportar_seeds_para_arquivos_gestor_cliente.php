<?php
// Função para obter o nome do módulo pelo id
function nomeModulo($modulos, $idModulo) {
    foreach ($modulos as $mod) {
        if ((isset($mod['id']) && $mod['id'] == $idModulo) || (isset($mod['id_modulos']) && $mod['id_modulos'] == $idModulo)) {
            if (isset($mod['nome'])) {
                // Normaliza o nome para ser usado como pasta
                return preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($mod['nome']));
            }
        }
    }
    return 'modulo_' . $idModulo;
}

// Script para exportar layouts, páginas e componentes do gestor-cliente/modulos baseado nos seeders e categorias

// Caminhos dos seeders (relativo à raiz do repositório)
$repoRoot = dirname(__DIR__, 2);
$categoriasSeeder = $repoRoot . '/gestor/db/seeds/CategoriasSeeder.php';
$templatesSeeder = $repoRoot . '/gestor/db/seeds/TemplatesSeeder.php';
$modulosSeeder = $repoRoot . '/gestor/db/seeds/ModulosSeeder.php';

// Função para extrair o array $data de um seeder
function extrairDataSeeder($arquivo) {
    $conteudo = file_get_contents($arquivo);
    if (preg_match('/\$data\s*=\s*(\[.*?\]);/s', $conteudo, $matches)) {
        return eval('return ' . $matches[1] . ';');
    }
    return [];
}

$categorias = extrairDataSeeder($categoriasSeeder);
$templates = extrairDataSeeder($templatesSeeder);
$modulos = extrairDataSeeder($modulosSeeder);

// Indexar categorias por módulo
$categoriasPorModulo = [];
foreach ($categorias as $cat) {
    $modulo = trim($cat['id_modulos'] ?? '');
    if ($modulo) {
        $categoriasPorModulo[$modulo][] = $cat;
    }
}

// Indexar templates por categoria
$templatesPorCategoria = [];
foreach ($templates as $tpl) {
    $idCategoria = isset($tpl['id_categorias']) ? trim($tpl['id_categorias']) : null;
    if ($idCategoria) {
        $templatesPorCategoria[$idCategoria][] = $tpl;
    }
}


$baseDir = __DIR__ . '/../../gestor-cliente/';

// Listar módulos válidos (com .php ou .js)
$modulosValidos = [];
$modulosDir = $baseDir . 'modulos/';
if (is_dir($modulosDir)) {
    foreach (scandir($modulosDir) as $mod) {
        if ($mod === '.' || $mod === '..') continue;
        $modPath = $modulosDir . $mod . '/';
        if (is_dir($modPath)) {
            if (file_exists($modPath . $mod . '.php') || file_exists($modPath . $mod . '.js')) {
                $modulosValidos[] = $mod;
            }
        }
    }
}

// Exportar recursos do admin_templates (id_modulos = 13)
foreach ($categorias as $cat) {
    $idModulo = trim($cat['id_modulos'] ?? '');
    $tipo = trim($cat['id_categorias_pai'] ?? '');
    $idCategoria = trim($cat['id_categorias']);
    $nomeModulo = trim($cat['id']); // este é o nome do módulo real do gestor-cliente
    if ($idModulo !== '13' || !isset($templatesPorCategoria[$idCategoria])) continue;
    if ($tipo !== '17' && $tipo !== '18' && $tipo !== '29') continue;

    foreach ($templatesPorCategoria[$idCategoria] as $tpl) {
        $idTemplate = trim($tpl['id']);
        $html = $tpl['html'] ?? '';
        $css = $tpl['css'] ?? '';
        if ($tipo === '17') {
            // Layouts sempre globais
            $dir = $baseDir . 'resources/layouts/' . $idTemplate . '/';
        } elseif ($tipo === '29') {
            // Componentes sempre globais
            $dir = $baseDir . 'resources/componentes/' . $idTemplate . '/';
        } elseif ($tipo === '18') {
            // Página: só exporta para módulo se for válido
            if ($nomeModulo !== '' && in_array($nomeModulo, $modulosValidos)) {
                $dir = $baseDir . 'modulos/' . $nomeModulo . '/' . $idTemplate . '/';
            } else {
                $dir = $baseDir . 'resources/paginas/' . $idTemplate . '/';
            }
        } else {
            continue;
        }
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($dir . "$idTemplate.html", $html);
        if ($css && trim($css) !== '') file_put_contents($dir . "$idTemplate.css", $css);
    }
}

// Exportar recursos de módulos
foreach ($categoriasPorModulo as $idModulo => $cats) {
    $nomeModulo = nomeModulo($modulos, $idModulo);
    foreach ($cats as $cat) {
        $tipo = trim($cat['id_categorias_pai'] ?? '');
        $idCategoria = trim($cat['id_categorias']);
        $nomeCategoria = trim($cat['id']);
        if (!isset($templatesPorCategoria[$idCategoria])) continue;
        foreach ($templatesPorCategoria[$idCategoria] as $tpl) {
            $idTemplate = trim($tpl['id']);
            $html = $tpl['html'] ?? '';
            $css = $tpl['css'] ?? '';
            // Só páginas e componentes vão para modulos/{modulo}/
            $tipoDir = '';
            if ($tipo === '18') {
                $tipoDir = 'paginas';
            } elseif ($tipo === '29') {
                $tipoDir = 'componentes';
            } else {
                continue;
            }
            $dir = $baseDir . "modulos/" . $nomeModulo . "/$tipoDir/$idTemplate/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            file_put_contents($dir . "$idTemplate.html", $html);
            if ($css && trim($css) !== '') file_put_contents($dir . "$idTemplate.css", $css);
            // Layouts de módulo não existem, layouts são sempre globais
        }
    }
}

echo "Exportação concluída para gestor-cliente/resources e gestor-cliente/modulos.\n";
// Bloco duplicado removido para corrigir erro de sintaxe
