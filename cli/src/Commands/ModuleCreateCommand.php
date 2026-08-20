<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class ModuleCreateCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'module:create';
    }

    public function getDescription(): string
    {
        return 'Scaffold a canonical CRUD module based on modulos-grupos architecture.';
    }

    public function getAliases(): array
    {
        return ['make:module', 'module:new'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f module:create <module-id> [options]\n\n" .
               "Creates a complete canonical module in gestor/modulos/<module-id>/ with:\n" .
               "  - <module-id>.php (Controller with lifecycle hooks)\n" .
               "  - <module-id>.json (Schema metadata with natural_key strategy)\n" .
               "  - <module-id>.js (Frontend script)\n" .
               "  - resources/pt-br/ & resources/en/ (Pages, templates, variables)\n\n" .
               "Arguments:\n" .
               "  module-id     Kebab-case identifier of the module (e.g. 'relatorios-gerenciais').\n\n" .
               "Options:\n" .
               "  --table=NAME  Custom database table name (default: same as module identifier with underscores).";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleId = $input->getArgument(0);

        if (!$moduleId) {
            $output->error("Module ID is required. Example: c2f module:create meu-modulo");
            return 1;
        }

        // Normalize module id to kebab-case
        $moduleId = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $moduleId), '-'));
        $moduleSnake = str_replace('-', '_', $moduleId);
        $moduleTitle = ucwords(str_replace('-', ' ', $moduleId));
        $customTable = $input->getOption('table') ?: $moduleSnake;

        $moduleDir = $this->rootPath . '/gestor/modulos/' . $moduleId;

        if (is_dir($moduleDir)) {
            $output->error("Module directory already exists at: {$moduleDir}");
            return 1;
        }

        $output->title("Scaffolding Module: {$moduleId}");
        $output->info("Creating canonical architecture under gestor/modulos/{$moduleId}/...");

        // Create directory structure
        $dirs = [
            $moduleDir,
            "{$moduleDir}/resources/pt-br/pages/{$moduleId}-adicionar",
            "{$moduleDir}/resources/pt-br/pages/{$moduleId}-editar",
            "{$moduleDir}/resources/pt-br/variables",
            "{$moduleDir}/resources/en/pages/{$moduleId}-adicionar",
            "{$moduleDir}/resources/en/pages/{$moduleId}-editar",
            "{$moduleDir}/resources/en/variables",
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // 1. Controller PHP
        $phpCode = <<<PHP
<?php

declare(strict_types=1);

global \$_GESTOR;

\$_GESTOR['modulo-id'] = '{$moduleId}';
\$_GESTOR['modulo-salvar'] = 'gestor-salvar-{$moduleId}';

function {$moduleSnake}_start(): void
{
    global \$_GESTOR;

    gestor_incluir_bibliotecas();

    if (\$_GESTOR['ajax']) {
        switch (\$_GESTOR['ajax-opcao']) {
            // Endpoints AJAX customizados aqui
        }
        return;
    }

    switch (\$_GESTOR['opcao']) {
        case 'adicionar':
            {$moduleSnake}_adicionar();
            break;
        case 'editar':
            {$moduleSnake}_editar();
            break;
        case 'clonar':
            {$moduleSnake}_clonar();
            break;
        case 'status':
            {$moduleSnake}_status();
            break;
        default:
            {$moduleSnake}_listar();
    }
}

function {$moduleSnake}_interfaces_padroes(): void
{
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#_padrao'];

    switch (\$_GESTOR['opcao']) {
        case 'listar':
            break;
    }
}

function {$moduleSnake}_adicionar(): void
{
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#_padrao'];

    // Processamento de inserção
    if (isset(\$_POST['salvar'])) {
        \$validacao = interface_validacao_campos_obrigatorios([
            'nome',
        ]);

        if (!\$validacao) {
            return;
        }

        \$campos = [
            'id' => banco_identificador(\$_POST['nome']),
            'nome' => \$_POST['nome'],
            'status' => 'A',
            'data_criacao' => 'NOW()',
            'data_modificacao' => 'NOW()',
        ];

        banco_insert_name(\$campos, \$modulo['tabela']['nome']);
        gestor_redirecionar(\$modulo['url'] . '?sucesso=adicionar');
        return;
    }

    interface_formulario_adicionar();
}

function {$moduleSnake}_editar(): void
{
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#_padrao'];
    \$id = \$_GESTOR['opcao-id'];

    banco_select_campos_antes_iniciar();
    \$registro = banco_select([
        'tabela' => \$modulo['tabela']['nome'],
        'campos' => ['id', 'nome', 'status'],
        'where' => [['campo' => \$modulo['tabela']['id'], 'valor' => \$id]],
        'unico' => true,
    ]);

    if (!\$registro) {
        gestor_redirecionar(\$modulo['url']);
        return;
    }

    if (isset(\$_POST['salvar'])) {
        \$validacao = interface_validacao_campos_obrigatorios([
            'nome',
        ]);

        if (!\$validacao) {
            return;
        }

        \$campos = [
            'nome' => \$_POST['nome'],
            'data_modificacao' => 'NOW()',
        ];

        banco_update(\$campos, \$modulo['tabela']['nome'], [['campo' => \$modulo['tabela']['id'], 'valor' => \$id]]);
        gestor_redirecionar(\$modulo['url'] . '?sucesso=editar');
        return;
    }

    interface_formulario_editar(\$registro);
}

function {$moduleSnake}_clonar(): void
{
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#_padrao'];
    \$id = \$_GESTOR['opcao-id'];

    // Lógica de clonagem canônica
    gestor_redirecionar(\$modulo['url'] . '?sucesso=clonar');
}

function {$moduleSnake}_status(): void
{
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#_padrao'];
    \$id = \$_GESTOR['opcao-id'];

    \$registro = banco_select([
        'tabela' => \$modulo['tabela']['nome'],
        'campos' => ['status'],
        'where' => [['campo' => \$modulo['tabela']['id'], 'valor' => \$id]],
        'unico' => true,
    ]);

    if (\$registro) {
        \$novoStatus = (\$registro['status'] === 'A') ? 'I' : 'A';
        banco_update(['status' => \$novoStatus], \$modulo['tabela']['nome'], [['campo' => \$modulo['tabela']['id'], 'valor' => \$id]]);
    }

    gestor_redirecionar(\$modulo['url']);
}

function {$moduleSnake}_listar(): void
{
    global \$_GESTOR;

    \$modulo = \$_GESTOR['modulo#_padrao'];

    interface_lista_padrao([
        'tabela' => \$modulo['tabela']['nome'],
        'campos' => [
            ['nome' => 'nome', 'label' => 'Nome', 'ordem' => 'asc'],
            ['nome' => 'status', 'label' => 'Status'],
        ],
    ]);
}

{$moduleSnake}_start();

PHP;
        file_put_contents("{$moduleDir}/{$moduleId}.php", $phpCode);

        // 2. Schema JSON
        $schema = [
            'modulo' => $moduleId,
            'nome' => $moduleTitle,
            'versao' => '1.0.0',
            'tabela' => [
                'nome' => $customTable,
                'id' => 'id_' . $moduleSnake,
                'strategy' => 'natural_key',
                'natural_key_columns' => ['id'],
                'data_criacao' => 'data_criacao',
                'data_modificacao' => 'data_modificacao',
            ],
            'permissoes' => [
                ['id' => 'listar', 'nome' => 'Listar'],
                ['id' => 'adicionar', 'nome' => 'Adicionar'],
                ['id' => 'editar', 'nome' => 'Editar'],
                ['id' => 'excluir', 'nome' => 'Excluir'],
                ['id' => 'status', 'nome' => 'Alterar Status'],
            ],
            'sync_resources' => true
        ];
        file_put_contents("{$moduleDir}/{$moduleId}.json", json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // 3. Frontend JS
        $jsCode = <<<JS
$(document).ready(function() {
    'use strict';

    console.log('Module {$moduleId} initialized');
});
JS;
        file_put_contents("{$moduleDir}/{$moduleId}.js", $jsCode);

        // 4. HTML Add/Edit Pages (PT-BR and EN)
        $htmlPage = <<<HTML
<div class="ui container">
    <div class="ui segment">
        <h3 class="ui header">@[[var#titulo]]@</h3>
        <form class="ui form" method="POST">
            <div class="field required">
                <label>@[[var#label_nome]]@</label>
                <input type="text" name="nome" value="#nome#" required>
            </div>
            <button class="ui primary button" type="submit" name="salvar">@[[var#botao_salvar]]@</button>
            <a class="ui button" href="#module_base_url#">@[[var#botao_cancelar]]@</a>
        </form>
    </div>
</div>
HTML;
        file_put_contents("{$moduleDir}/resources/pt-br/pages/{$moduleId}-adicionar/{$moduleId}-adicionar.html", $htmlPage);
        file_put_contents("{$moduleDir}/resources/pt-br/pages/{$moduleId}-editar/{$moduleId}-editar.html", $htmlPage);
        file_put_contents("{$moduleDir}/resources/en/pages/{$moduleId}-adicionar/{$moduleId}-adicionar.html", $htmlPage);
        file_put_contents("{$moduleDir}/resources/en/pages/{$moduleId}-editar/{$moduleId}-editar.html", $htmlPage);

        // 5. Variables JSON
        $varsPt = [
            'titulo' => $moduleTitle,
            'label_nome' => 'Nome do Registro',
            'botao_salvar' => 'Salvar',
            'botao_cancelar' => 'Cancelar'
        ];
        $varsEn = [
            'titulo' => $moduleTitle,
            'label_nome' => 'Record Name',
            'botao_salvar' => 'Save',
            'botao_cancelar' => 'Cancel'
        ];
        file_put_contents("{$moduleDir}/resources/pt-br/variables/variables.json", json_encode($varsPt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents("{$moduleDir}/resources/en/variables/variables.json", json_encode($varsEn, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $output->success("Module '{$moduleId}' successfully created with canonical architecture!");
        $output->writeln("Path: gestor/modulos/{$moduleId}/");
        $output->writeln("Next steps: run 'c2f resources:sync' to compile resources into Data.json.");

        return 0;
    }
}
