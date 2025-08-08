<?php

echo "🌍 GERADOR DE SEEDERS MULTILÍNGUES - CONN2FLOW v1.8.4+\n";
echo "======================================================\n\n";

// Configuração base
$base_dir = dirname(__DIR__); // Um nível acima para sair da pasta resources

// Carregar mapeamento principal para descobrir idiomas disponíveis
$main_resources_map_file = __DIR__ . '/resources.map.php';
if (!file_exists($main_resources_map_file)) {
    die("❌ Arquivo de mapeamento principal não encontrado: $main_resources_map_file\n");
}

$main_resources_map = include $main_resources_map_file;
$available_languages = array_keys($main_resources_map['languages']);

echo "🌍 Idiomas detectados: " . implode(', ', $available_languages) . "\n\n";

// Função para ler conteúdo do arquivo
function getFileContent($filepath) {
    if (file_exists($filepath)) {
        return file_get_contents($filepath);
    }
    return null;
}

// Função para gerar checksum individual
function generateSimpleChecksum($content) {
    return md5($content ?? '');
}

// Função para gerar checksum combinado
function generateCombinedChecksum($html, $css) {
    return md5(($html ?? '') . ($css ?? ''));
}

// Função para gerar estrutura de checksum completa
function generateChecksumStructure($html, $css) {
    return [
        'html' => generateSimpleChecksum($html),
        'css' => generateSimpleChecksum($css),
        'combined' => generateCombinedChecksum($html, $css)
    ];
}

// Função para incrementar versão
function incrementVersion($current_version) {
    if ($current_version === '0') {
        return '1.0';
    }
    
    $parts = explode('.', $current_version);
    if (count($parts) == 2) {
        $parts[1] = (int)$parts[1] + 1;
        return implode('.', $parts);
    }
    
    return '1.0';
}

// Função para comparar checksums
function checksumsChanged($old_checksum, $new_checksum) {
    if (!isset($old_checksum['combined']) || !isset($new_checksum['combined'])) {
        return true;
    }
    return $old_checksum['combined'] !== $new_checksum['combined'];
}

// Função para atualizar recurso no mapeamento
function updateResourceInMapping(&$resource_item, $new_checksum) {
    if (checksumsChanged($resource_item['checksum'], $new_checksum)) {
        $resource_item['version'] = incrementVersion($resource_item['version']);
        $resource_item['checksum'] = $new_checksum;
        
        echo "⬆️ Versão atualizada: {$resource_item['id']} -> v{$resource_item['version']}\n";
        return true;
    }
    return false;
}

// Função para processar arquivo de módulo
function updateModuleResourceMapping($module_path, $module_id, $language, $resource_type, $resource_id, $new_checksum) {
    $module_file = $module_path . "/$module_id.php";
    if (!file_exists($module_file)) {
        return false;
    }
    
    // Ler conteúdo do arquivo do módulo
    $module_content = file_get_contents($module_file);
    
    // Procurar pela estrutura específica do recurso no módulo
    $pattern = '/(\[\s*\'name\'\s*=>\s*[^,]+,\s*\'id\'\s*=>\s*\'' . preg_quote($resource_id) . '\',.*?\'version\'\s*=>\s*\')([^\']+)(\',\s*\'checksum\'\s*=>\s*\[\s*\'html\'\s*=>\s*\')([^\']*)(\',\s*\'css\'\s*=>\s*\')([^\']*)(\',?\s*\]\s*,)/s';
    
    if (preg_match($pattern, $module_content, $matches)) {
        $old_version = $matches[2];
        $old_html_checksum = $matches[4];
        $old_css_checksum = $matches[6];
        
        // Comparar checksums
        $old_combined = md5($old_html_checksum . $old_css_checksum);
        
        if ($old_combined !== $new_checksum['combined']) {
            // Incrementar versão
            $new_version = incrementVersion($old_version);
            
            // Criar nova estrutura de checksum
            $new_resource = $matches[1] . $new_version . $matches[3] . 
                           $new_checksum['html'] . $matches[5] . 
                           $new_checksum['css'] . $matches[7];
            
            // Substituir no conteúdo
            $new_module_content = str_replace($matches[0], $new_resource, $module_content);
            
            // Salvar arquivo atualizado
            file_put_contents($module_file, $new_module_content);
            
            echo "⬆️ Módulo $module_id - Versão atualizada: $resource_id -> v$new_version\n";
            return true;
        }
    } else {
        echo "⚠️ Módulo $module_id - Padrão não encontrado para: $resource_id\n";
    }
    
    return false;
}

// Função para gerar código PHP do seeder
function generateSeederCode($className, $tableName, $data) {
    $code = "<?php\n\ndeclare(strict_types=1);\n\nuse Phinx\\Seed\\AbstractSeed;\n\n";
    $code .= "final class {$className} extends AbstractSeed\n{\n";
    $code .= "    public function run(): void\n    {\n";
    $code .= "        \$data = [\n";
    
    foreach ($data as $row) {
        $code .= "            [\n";
        foreach ($row as $key => $value) {
            if ($value === null) {
                $code .= "                '$key' => null,\n";
            } elseif (is_string($value)) {
                $escaped_value = addslashes($value);
                $code .= "                '$key' => '$escaped_value',\n";
            } else {
                $code .= "                '$key' => $value,\n";
            }
        }
        $code .= "            ],\n";
    }
    
    $code .= "        ];\n\n";
    $code .= "        \$table = \$this->table('$tableName');\n";
    $code .= "        \$table->insert(\$data)->saveData();\n";
    $code .= "    }\n";
    $code .= "}\n";
    
    return $code;
}

// Loop principal por idioma
$all_layouts_data = [];
$all_pages_data = [];
$all_components_data = [];

foreach ($available_languages as $language) {
    echo "🌍 Processando idioma: $language\n";
    echo str_repeat("-", 50) . "\n";
    
    // Carregar mapeamento específico do idioma
    $resources_map_file = __DIR__ . "/resources.map.$language.php";
    if (!file_exists($resources_map_file)) {
        echo "⚠️ Arquivo de mapeamento não encontrado para $language: $resources_map_file\n";
        continue;
    }
    
    $resources_map = include $resources_map_file;
    $map_updated = false;
    
    // 1. PROCESSAR LAYOUTS GLOBAIS
    echo "📋 Processando layouts globais...\n";
    
    if (isset($resources_map['layouts'])) {
        foreach ($resources_map['layouts'] as &$layout) {
            $layout_dir = $base_dir . "/resources/$language/layouts/" . $layout['id'];
            $html_file = $layout_dir . "/" . $layout['id'] . ".html";
            $css_file = $layout_dir . "/" . $layout['id'] . ".css";
            
            $html_content = getFileContent($html_file);
            $css_content = getFileContent($css_file);
            
            if ($html_content !== null || $css_content !== null) {
                // Gerar nova checksum
                $new_checksum = generateChecksumStructure($html_content, $css_content);
                
                // Verificar se precisa atualizar versão
                if (updateResourceInMapping($layout, $new_checksum)) {
                    $map_updated = true;
                }
                
                // Adicionar aos dados do seeder
                $all_layouts_data[] = [
                    'layout_id' => count($all_layouts_data) + 1,
                    'user_id' => 1,
                    'name' => $layout['name'],
                    'id' => $layout['id'],
                    'language' => $language,
                    'module' => null,
                    'html' => $html_content,
                    'css' => $css_content,
                    'status' => 'A',
                    'version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'user_modified' => 0,
                    'file_version' => $layout['version'],
                    'checksum' => json_encode($new_checksum)
                ];
            }
        }
    }
    
    // 2. PROCESSAR PÁGINAS GLOBAIS
    echo "📄 Processando páginas globais...\n";
    
    if (isset($resources_map['pages'])) {
        foreach ($resources_map['pages'] as &$page) {
            $page_dir = $base_dir . "/resources/$language/pages/" . $page['id'];
            $html_file = $page_dir . "/" . $page['id'] . ".html";
            $css_file = $page_dir . "/" . $page['id'] . ".css";
            
            $html_content = getFileContent($html_file);
            $css_content = getFileContent($css_file);
            
            if ($html_content !== null || $css_content !== null) {
                // Gerar nova checksum
                $new_checksum = generateChecksumStructure($html_content, $css_content);
                
                // Verificar se precisa atualizar versão
                if (updateResourceInMapping($page, $new_checksum)) {
                    $map_updated = true;
                }
                
                // Adicionar aos dados do seeder
                $all_pages_data[] = [
                    'page_id' => count($all_pages_data) + 1,
                    'user_id' => 1,
                    'layout_id' => null,
                    'name' => $page['name'],
                    'id' => $page['id'],
                    'language' => $language,
                    'path' => $page['id'] . '/',
                    'type' => isset($page['type']) ? $page['type'] : 'sistema',
                    'module' => isset($page['module']) ? $page['module'] : null,
                    'option' => isset($page['option']) ? $page['option'] : null,
                    'root' => isset($page['root']) ? (int)$page['root'] : 0,
                    'no_permission' => null,
                    'html' => $html_content,
                    'css' => $css_content,
                    'status' => 'A',
                    'version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'user_modified' => 0,
                    'file_version' => $page['version'],
                    'checksum' => json_encode($new_checksum)
                ];
            }
        }
    }
    
    // 3. PROCESSAR COMPONENTES GLOBAIS
    echo "🧩 Processando componentes globais...\n";
    
    if (isset($resources_map['components'])) {
        foreach ($resources_map['components'] as &$component) {
            $component_dir = $base_dir . "/resources/$language/components/" . $component['id'];
            $html_file = $component_dir . "/" . $component['id'] . ".html";
            $css_file = $component_dir . "/" . $component['id'] . ".css";
            
            $html_content = getFileContent($html_file);
            $css_content = getFileContent($css_file);
            
            if ($html_content !== null || $css_content !== null) {
                // Gerar nova checksum
                $new_checksum = generateChecksumStructure($html_content, $css_content);
                
                // Verificar se precisa atualizar versão
                if (updateResourceInMapping($component, $new_checksum)) {
                    $map_updated = true;
                }
                
                // Adicionar aos dados do seeder
                $all_components_data[] = [
                    'component_id' => count($all_components_data) + 1,
                    'user_id' => 1,
                    'name' => $component['name'],
                    'id' => $component['id'],
                    'language' => $language,
                    'module' => isset($component['module']) ? $component['module'] : null,
                    'html' => $html_content,
                    'css' => $css_content,
                    'status' => 'A',
                    'version' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'user_modified' => 0,
                    'file_version' => $component['version'],
                    'checksum' => json_encode($new_checksum)
                ];
            }
        }
    }
    
    // 4. PROCESSAR RECURSOS DOS MÓDULOS
    echo "📦 Processando módulos...\n";
    
    $modulos_dir = $base_dir . '/modulos';
    if (is_dir($modulos_dir)) {
        $modulos = glob($modulos_dir . '/*', GLOB_ONLYDIR);
        foreach ($modulos as $modulo_path) {
            $modulo_name = basename($modulo_path);
            echo "📦 Módulo: $modulo_name\n";
            
            // Processar layouts do módulo
            $layouts_modulo_dir = $modulo_path . "/resources/$language/layouts";
            if (is_dir($layouts_modulo_dir)) {
                $layouts_modulo = glob($layouts_modulo_dir . '/*', GLOB_ONLYDIR);
                foreach ($layouts_modulo as $layout_path) {
                    $layout_id_name = basename($layout_path);
                    $html_file = $layout_path . "/$layout_id_name.html";
                    $css_file = $layout_path . "/$layout_id_name.css";
                    
                    $html_content = getFileContent($html_file);
                    $css_content = getFileContent($css_file);
                    
                    if ($html_content !== null || $css_content !== null) {
                        $new_checksum = generateChecksumStructure($html_content, $css_content);
                        
                        // Atualizar arquivo do módulo se necessário
                        updateModuleResourceMapping($modulo_path, $modulo_name, $language, 'layouts', $layout_id_name, $new_checksum);
                        
                        $layout_name = ucwords(str_replace('-', ' ', $layout_id_name));
                        
                        $all_layouts_data[] = [
                            'layout_id' => count($all_layouts_data) + 1,
                            'user_id' => 1,
                            'name' => $layout_name,
                            'id' => $layout_id_name,
                            'language' => $language,
                            'module' => $modulo_name,
                            'html' => $html_content,
                            'css' => $css_content,
                            'status' => 'A',
                            'version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'user_modified' => 0,
                            'file_version' => '1.0',
                            'checksum' => json_encode($new_checksum)
                        ];
                    }
                }
            }
            
            // Processar páginas do módulo
            $pages_modulo_dir = $modulo_path . "/resources/$language/pages";
            if (is_dir($pages_modulo_dir)) {
                $pages_modulo = glob($pages_modulo_dir . '/*', GLOB_ONLYDIR);
                foreach ($pages_modulo as $page_path) {
                    $page_id_name = basename($page_path);
                    $html_file = $page_path . "/$page_id_name.html";
                    $css_file = $page_path . "/$page_id_name.css";
                    
                    $html_content = getFileContent($html_file);
                    $css_content = getFileContent($css_file);
                    
                    if ($html_content !== null || $css_content !== null) {
                        $new_checksum = generateChecksumStructure($html_content, $css_content);
                        
                        // Atualizar arquivo do módulo se necessário
                        updateModuleResourceMapping($modulo_path, $modulo_name, $language, 'pages', $page_id_name, $new_checksum);
                        
                        $page_name = ucwords(str_replace('-', ' ', $page_id_name));
                        
                        $all_pages_data[] = [
                            'page_id' => count($all_pages_data) + 1,
                            'user_id' => 1,
                            'layout_id' => null,
                            'name' => $page_name,
                            'id' => $page_id_name,
                            'language' => $language,
                            'path' => $page_id_name . '/',
                            'type' => 'sistema',
                            'module' => $modulo_name,
                            'option' => 'listar',
                            'root' => 1,
                            'no_permission' => null,
                            'html' => $html_content,
                            'css' => $css_content,
                            'status' => 'A',
                            'version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'user_modified' => 0,
                            'file_version' => '1.0',
                            'checksum' => json_encode($new_checksum)
                        ];
                    }
                }
            }
            
            // Processar componentes do módulo
            $components_modulo_dir = $modulo_path . "/resources/$language/components";
            if (is_dir($components_modulo_dir)) {
                $components_modulo = glob($components_modulo_dir . '/*', GLOB_ONLYDIR);
                foreach ($components_modulo as $component_path) {
                    $component_id_name = basename($component_path);
                    $html_file = $component_path . "/$component_id_name.html";
                    $css_file = $component_path . "/$component_id_name.css";
                    
                    $html_content = getFileContent($html_file);
                    $css_content = getFileContent($css_file);
                    
                    if ($html_content !== null || $css_content !== null) {
                        $new_checksum = generateChecksumStructure($html_content, $css_content);
                        
                        // Atualizar arquivo do módulo se necessário
                        updateModuleResourceMapping($modulo_path, $modulo_name, $language, 'components', $component_id_name, $new_checksum);
                        
                        $component_name = ucwords(str_replace('-', ' ', $component_id_name));
                        
                        $all_components_data[] = [
                            'component_id' => count($all_components_data) + 1,
                            'user_id' => 1,
                            'name' => $component_name,
                            'id' => $component_id_name,
                            'language' => $language,
                            'module' => $modulo_name,
                            'html' => $html_content,
                            'css' => $css_content,
                            'status' => 'A',
                            'version' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'user_modified' => 0,
                            'file_version' => '1.0',
                            'checksum' => json_encode($new_checksum)
                        ];
                    }
                }
            }
        }
    }
    
    // Salvar arquivo de mapeamento atualizado se necessário
    if ($map_updated) {
        $updated_content = "<?php\n\n/**********\n\tDescription: resources mapping: $language (apenas recursos globais).\n\tModule-specific resources migrated to respective module files.\n\tUpdated at: " . date('Y-m-d H:i:s') . "\n**********/\n\n// ===== Variable definition.\n\n\$resources = " . var_export($resources_map, true) . ";\n\n// ===== Return the variable.\n\nreturn \$resources;\n";
        file_put_contents($resources_map_file, $updated_content);
        echo "💾 Arquivo de mapeamento $language atualizado\n";
    }
    
    echo "✅ Idioma $language processado\n\n";
}

// Salvar os seeders
$seeds_dir = $base_dir . '/db/seeds';

// Layouts Seeder
$layouts_seeder = generateSeederCode('LayoutsSeeder', 'layouts', $all_layouts_data);
file_put_contents($seeds_dir . '/LayoutsSeeder.php', $layouts_seeder);
echo "✅ LayoutsSeeder.php criado com " . count($all_layouts_data) . " layouts\n";

// Pages Seeder
$pages_seeder = generateSeederCode('PagesSeeder', 'pages', $all_pages_data);
file_put_contents($seeds_dir . '/PagesSeeder.php', $pages_seeder);
echo "✅ PagesSeeder.php criado com " . count($all_pages_data) . " páginas\n";

// Components Seeder
$components_seeder = generateSeederCode('ComponentsSeeder', 'components', $all_components_data);
file_put_contents($seeds_dir . '/ComponentsSeeder.php', $components_seeder);
echo "✅ ComponentsSeeder.php criado com " . count($all_components_data) . " componentes\n";

echo "\n📊 RESUMO FINAL:\n";
echo "================\n";
echo "📋 Layouts: " . count($all_layouts_data) . " recursos\n";
echo "📄 Páginas: " . count($all_pages_data) . " recursos\n";
echo "🧩 Componentes: " . count($all_components_data) . " recursos\n";
echo "📁 Total: " . (count($all_layouts_data) + count($all_pages_data) + count($all_components_data)) . " recursos\n";
echo "🌍 Idiomas processados: " . implode(', ', $available_languages) . "\n\n";

echo "🎉 SEEDERS MULTILÍNGUES GERADOS COM SUCESSO!\n";
echo "✅ Sistema pronto para GitHub Actions e release automático\n";

?>
