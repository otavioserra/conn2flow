<?php

declare(strict_types=1);

// Gerador modernizado: orquestra a geração dos JSONs (nova arquitetura)
// e garante seeders padrão que leem esses JSONs.

echo "🌍 Gerador de Seeders Multilíngues (nova arquitetura)\n";
echo "===================================================\n\n";

$gestorDir = dirname(__DIR__);                 // .../gestor
$rootDir   = dirname($gestorDir);              // .../conn2flow
$dataDir   = $gestorDir . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data';
$seedsDir  = $gestorDir . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'seeds';

// 1) Executar o gerador de dados consolidado (lê globais/módulos/plugins e atualiza versões/checksums)
$generator = $rootDir . DIRECTORY_SEPARATOR . 'ai-workspace' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR . 'generate_seeds_data.php';
if (!file_exists($generator)) {
    fwrite(STDERR, "❌ Gerador de dados não encontrado em: $generator\n");
    exit(1);
}

echo "▶️ Executando gerador de dados (JSON)...\n";
// Incluir o script diretamente para aproveitar a execução inline e mensagens de log
require $generator; // o script escreve LayoutsData.json, PaginasData.json e ComponentesData.json

// 2) Garantir seeders padrão (não sobrescrever se já existirem)
if (!is_dir($seedsDir)) {
    mkdir($seedsDir, 0755, true);
}

function ensureSeeder(string $seedsDir, string $className, string $table, string $dataFile): void {
    $path = $seedsDir . DIRECTORY_SEPARATOR . $className . '.php';
    if (file_exists($path)) {
        echo "✅ $className já existe, mantendo arquivo.\n";
        return;
    }
    $code = "<?php\n\n"
          . "declare(strict_types=1);\n\n"
          . "use Phinx\\\\Seed\\\\AbstractSeed;\n\n"
          . "final class $className extends AbstractSeed\n{\n"
          . "    public function run(): void\n    {\n"
          . "        $data = json_decode(file_get_contents(__DIR__ . '/../data/$dataFile'), true);\n\n"
          . "        $table = $this->table('$table');\n"
          . "        $table->insert($data)->saveData();\n"
          . "    }\n"
          . "}\n";
    file_put_contents($path, $code);
    echo "🆕 $className criado.\n";
}

ensureSeeder($seedsDir, 'LayoutsSeeder', 'layouts', 'LayoutsData.json');
ensureSeeder($seedsDir, 'PaginasSeeder', 'paginas', 'PaginasData.json');
ensureSeeder($seedsDir, 'ComponentesSeeder', 'componentes', 'ComponentesData.json');

// 3) Resumo final a partir dos JSONs
function countJson(string $file): int {
    if (!file_exists($file)) return 0;
    $arr = json_decode((string)file_get_contents($file), true);
    return is_array($arr) ? count($arr) : 0;
}

$layoutsFile = $dataDir . DIRECTORY_SEPARATOR . 'LayoutsData.json';
$pagesFile   = $dataDir . DIRECTORY_SEPARATOR . 'PaginasData.json';
$compsFile   = $dataDir . DIRECTORY_SEPARATOR . 'ComponentesData.json';

$nLayouts = countJson($layoutsFile);
$nPages   = countJson($pagesFile);
$nComps   = countJson($compsFile);

echo "\n📊 RESUMO FINAL:\n";
echo "================\n";
echo "📋 Layouts: $nLayouts recursos\n";
echo "📄 Páginas: $nPages recursos\n";
echo "🧩 Componentes: $nComps recursos\n";
echo "📁 Total: " . ($nLayouts + $nPages + $nComps) . " recursos\n\n";
echo "🎉 Seeders prontos. Utilize o Phinx para executar as seeds.\n";

exit(0);

