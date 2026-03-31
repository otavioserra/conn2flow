<?php
/**
 * Biblioteca Hooks do Conn2Flow
 * 
 * Sistema de hooks estilo WordPress que permite módulos interceptarem
 * e reagirem a eventos de outros módulos sem modificar o código-fonte.
 * 
 * Registro: exclusivamente via JSON de módulos/projeto, sincronizado
 * na tabela `hooks` pelo pipeline de atualização.
 * 
 * Runtime: HookManager singleton com lazy loading por namespace+evento.
 * Apenas 4 funções globais de execução/consulta.
 *
 * @package Conn2Flow
 * @subpackage Hooks
 * @version 1.0.0
 */

// ===========================================================================================
// Classe HookManager (Singleton)
// ===========================================================================================

class HookManager {

    private static ?HookManager $instance = null;

    /**
     * Eventos já carregados do banco nesta requisição.
     * Formato: [namespace][evento] = true
     */
    private array $loaded = [];

    /**
     * Actions em memória.
     * Formato: [namespace][evento][] = ['callback' => string, 'prioridade' => int]
     */
    private array $actions = [];

    /**
     * Filters em memória.
     * Formato: [namespace][evento][] = ['callback' => string, 'prioridade' => int]
     */
    private array $filters = [];

    /**
     * Controllers já incluídos via require_once (evita duplicação).
     * Formato: [caminho_absoluto] = true
     */
    private array $controllersIncluded = [];

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ===========================================================================================
    // Métodos públicos de execução
    // ===========================================================================================

    /**
     * Executa todos os callbacks de action para um namespace+evento.
     * Actions são side-effects — não retornam valores.
     */
    public function doAction(string $ns, string $evt, mixed ...$args): void {
        $this->ensureLoaded($ns, $evt);

        $callbacks = $this->getCallbacks('action', $ns, $evt);

        foreach ($callbacks as $cb) {
            try {
                if (is_callable($cb['callback'])) {
                    $argsToCall = $args;

                    try {
                        $ref = is_array($cb['callback'])
                            ? new \ReflectionMethod($cb['callback'][0], $cb['callback'][1])
                            : new \ReflectionFunction($cb['callback']);

                        $needed = $ref->getNumberOfRequiredParameters();
                        if (count($argsToCall) < $needed) {
                            $argsToCall = array_pad($argsToCall, $needed, null);
                        }
                    } catch (\ReflectionException $e) {
                        // continuar com args originais
                    }

                    call_user_func_array($cb['callback'], $argsToCall);
                }
            } catch (\Throwable $e) {
                $this->logError($ns, $evt, $cb['callback'], $e);
            }
        }
    }

    /**
     * Aplica todos os callbacks de filter para um namespace+evento.
     * Filters recebem um valor, o transformam e retornam.
     */
    public function applyFilters(string $ns, string $evt, mixed $value, mixed ...$args): mixed {
        $this->ensureLoaded($ns, $evt);

        $callbacks = $this->getCallbacks('filter', $ns, $evt);

        foreach ($callbacks as $cb) {
            try {
                if (is_callable($cb['callback'])) {
                    $argsToCall = array_merge([$value], $args);

                    try {
                        $ref = is_array($cb['callback'])
                            ? new \ReflectionMethod($cb['callback'][0], $cb['callback'][1])
                            : new \ReflectionFunction($cb['callback']);

                        $needed = $ref->getNumberOfRequiredParameters();
                        if (count($argsToCall) < $needed) {
                            $argsToCall = array_pad($argsToCall, $needed, null);
                        }
                    } catch (\ReflectionException $e) {
                        // continue
                    }

                    $value = call_user_func_array($cb['callback'], $argsToCall);
                }
            } catch (\Throwable $e) {
                $this->logError($ns, $evt, $cb['callback'], $e);
            }
        }

        return $value;
    }

    /**
     * Verifica se existem actions registradas para um namespace+evento.
     */
    public function hasActions(string $ns, string $evt): bool {
        $this->ensureLoaded($ns, $evt);
        return !empty($this->actions[$ns][$evt]) || !empty($this->actions['*'][$evt]);
    }

    /**
     * Verifica se existem filters registrados para um namespace+evento.
     */
    public function hasFilters(string $ns, string $evt): bool {
        $this->ensureLoaded($ns, $evt);
        return !empty($this->filters[$ns][$evt]) || !empty($this->filters['*'][$evt]);
    }

    // ===========================================================================================
    // Lazy loading (privado)
    // ===========================================================================================

    /**
     * Carrega hooks do banco para um namespace+evento se ainda não carregado.
     * Também carrega hooks do namespace wildcard '*' para o mesmo evento.
     */
    private function ensureLoaded(string $ns, string $evt): void {
        // Verificar namespace específico
        if (!isset($this->loaded[$ns][$evt])) {
            $this->loadFromDb($ns, $evt);
            $this->loaded[$ns][$evt] = true;
        }

        // Verificar namespace wildcard '*'
        if ($ns !== '*' && !isset($this->loaded['*'][$evt])) {
            $this->loadFromDb('*', $evt);
            $this->loaded['*'][$evt] = true;
        }
    }

    /**
     * Busca hooks na tabela `hooks` e registra em memória.
     */
    private function loadFromDb(string $ns, string $evt): void {
        $nsEscaped  = banco_escape_field($ns);
        $evtEscaped = banco_escape_field($evt);

        $rows = banco_select_name(
            'id_hooks,modulo,plugin,namespace,evento,callback,tipo,prioridade,projeto',
            'hooks',
            "WHERE namespace='" . $nsEscaped . "'"
            . " AND evento='" . $evtEscaped . "'"
            . " AND status='A'"
            . " AND habilitado=1"
            . " ORDER BY prioridade ASC, id_hooks ASC"
        );

        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            $controllerFile = $this->resolveControllerFile($row);

            if ($controllerFile !== null) {
                $this->includeController($controllerFile);
            }

            $entry = [
                'callback'   => $row['callback'],
                'prioridade' => (int) $row['prioridade'],
            ];

            $tipo = $row['tipo'] === 'filter' ? 'filters' : 'actions';
            $this->{$tipo}[$row['namespace']][$row['evento']][] = $entry;
        }
    }

    /**
     * Resolve o caminho do arquivo controller para um hook.
     * Busca no JSON do módulo quais controllers estão mapeados para o namespace.
     */
    private function resolveControllerFile(array $row): ?string {
        global $_GESTOR;

        $modulo   = $row['modulo'];
        $plugin   = $row['plugin'] ?? null;
        $ns       = $row['namespace'];
        $projeto  = $row['projeto'] ?? null;

        // Hook de projeto
        if ($projeto) {
            return $this->resolveProjectController($ns);
        }

        // Hook de módulo
        if (!$modulo) {
            return null;
        }

        return $this->resolveModuleController($modulo, $plugin, $ns);
    }

    /**
     * Resolve controller de projeto: project/hooks/controllers/<arquivo>
     */
    private function resolveProjectController(string $ns): ?string {
        global $_GESTOR;

        $hooksJsonPath = $_GESTOR['ROOT_PATH'] . 'project/hooks/hooks.json';
        if (!file_exists($hooksJsonPath)) {
            return null;
        }

        $json = @json_decode(file_get_contents($hooksJsonPath), true);
        if (!$json || !isset($json['controllers'][$ns])) {
            return null;
        }

        $arquivo = $json['controllers'][$ns];
        $fullPath = $_GESTOR['ROOT_PATH'] . 'project/hooks/controllers/' . $arquivo;

        return file_exists($fullPath) ? $fullPath : null;
    }

    /**
     * Resolve controller de módulo.
     * 
     * Caminhos:
     * - Módulo sem plugin: modulos-path/<modulo>/<arquivo>
     * - Módulo com plugin: plugins-path/<plugin>/modules/<modulo>/<arquivo>
     */
    private function resolveModuleController(string $modulo, ?string $plugin, string $ns): ?string {
        global $_GESTOR;

        // Ler JSON do módulo para pegar o controller mapeado para o namespace
        if ($plugin) {
            $jsonPath = $_GESTOR['plugins-path'] . $plugin . '/modules/' . $modulo . '/' . $modulo . '.json';
        } else {
            $jsonPath = $_GESTOR['modulos-path'] . $modulo . '/' . $modulo . '.json';
        }

        if (!file_exists($jsonPath)) {
            return null;
        }

        $json = @json_decode(file_get_contents($jsonPath), true);
        if (!$json || !isset($json['hooks']['controllers'][$ns])) {
            return null;
        }

        $arquivo = $json['hooks']['controllers'][$ns];

        if ($plugin) {
            $fullPath = $_GESTOR['plugins-path'] . $plugin . '/modules/' . $modulo . '/' . $arquivo;
        } else {
            $fullPath = $_GESTOR['modulos-path'] . $modulo . '/' . $arquivo;
        }

        return file_exists($fullPath) ? $fullPath : null;
    }

    /**
     * Inclui controller PHP via require_once (sem duplicação).
     */
    private function includeController(string $path): void {
        if (isset($this->controllersIncluded[$path])) {
            return;
        }
        $this->controllersIncluded[$path] = true;
        require_once $path;
    }

    /**
     * Retorna callbacks ordenados por prioridade para um tipo+namespace+evento.
     * Combina callbacks do namespace específico com os do wildcard '*'.
     */
    private function getCallbacks(string $tipo, string $ns, string $evt): array {
        $prop = $tipo === 'filter' ? 'filters' : 'actions';

        $specific = $this->{$prop}[$ns][$evt] ?? [];
        $wildcard = ($ns !== '*') ? ($this->{$prop}['*'][$evt] ?? []) : [];

        $all = array_merge($specific, $wildcard);

        // Ordenar por prioridade (menor primeiro), estável
        usort($all, function ($a, $b) {
            return $a['prioridade'] <=> $b['prioridade'];
        });

        return $all;
    }

    /**
     * Registra erro de execução de hook no log.
     */
    private function logError(string $ns, string $evt, string $callback, \Throwable $e): void {
        global $_GESTOR;

        $logPath = ($_GESTOR['logs-path'] ?? __DIR__ . '/../logs/') . 'hooks-errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $message = "[{$timestamp}] ERROR {$ns}.{$evt} => {$callback}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}" . PHP_EOL;

        @file_put_contents($logPath, $message, FILE_APPEND | LOCK_EX);

        // Em ambiente de desenvolvimento, logar também no error_log
        if (!empty($_GESTOR['development-env'])) {
            error_log("Hook Error [{$ns}.{$evt}]: {$callback} - {$e->getMessage()}");
        }
    }
}

// ===========================================================================================
// Funções Globais de API (apenas execução e consulta)
// ===========================================================================================

/**
 * Executa todos os callbacks de action para um namespace+evento.
 *
 * @param string $namespace Namespace alvo (ex: 'paginas', 'global')
 * @param string $evento Evento específico (ex: 'editar', 'adicionar')
 * @param mixed ...$args Argumentos passados para os callbacks
 */
function hook_do_action(string $namespace, string $evento, mixed ...$args): void {
    HookManager::getInstance()->doAction($namespace, $evento, ...$args);
}

/**
 * Aplica todos os filters para um namespace+evento, retornando o valor transformado.
 *
 * @param string $namespace Namespace alvo
 * @param string $evento Evento específico
 * @param mixed $value Valor a ser filtrado
 * @param mixed ...$args Argumentos adicionais
 * @return mixed Valor após aplicação de todos os filters
 */
function hook_apply_filters(string $namespace, string $evento, mixed $value, mixed ...$args): mixed {
    return HookManager::getInstance()->applyFilters($namespace, $evento, $value, ...$args);
}

/**
 * Verifica se existem actions registradas para um namespace+evento.
 */
function hook_has_actions(string $namespace, string $evento): bool {
    return HookManager::getInstance()->hasActions($namespace, $evento);
}

/**
 * Verifica se existem filters registrados para um namespace+evento.
 */
function hook_has_filters(string $namespace, string $evento): bool {
    return HookManager::getInstance()->hasFilters($namespace, $evento);
}

// ===========================================================================================
// Funções de Registro (usadas pelo pipeline de atualização)
// ===========================================================================================

/**
 * Registra/atualiza os hooks de um módulo na tabela hooks.
 * Remove hooks antigos do módulo que não estão mais no JSON.
 * Fonte de verdade: arquivo JSON do módulo.
 *
 * @param string $modulo ID do módulo
 * @param string|null $plugin ID do plugin (null se não for de plugin)
 * @param array $hooks_config Seção "hooks" do JSON do módulo
 */
function hooks_registrar_modulo(string $modulo, ?string $plugin, array $hooks_config): void {
    // 1. Limpar hooks antigos deste módulo (não de projeto)
    $moduloEscaped = banco_escape_field($modulo);
    banco_delete('hooks', "WHERE modulo='" . $moduloEscaped . "' AND projeto IS NULL");

    // 2. Processar actions
    if (isset($hooks_config['actions']) && is_array($hooks_config['actions'])) {
        foreach ($hooks_config['actions'] as $namespace => $eventos) {
            if (!is_array($eventos)) continue;
            foreach ($eventos as $evento => $callbackDef) {
                hooks_inserir_callbacks($modulo, $plugin, $namespace, $evento, $callbackDef, 'action', null);
            }
        }
    }

    // 3. Processar filters
    if (isset($hooks_config['filters']) && is_array($hooks_config['filters'])) {
        foreach ($hooks_config['filters'] as $namespace => $eventos) {
            if (!is_array($eventos)) continue;
            foreach ($eventos as $evento => $callbackDef) {
                hooks_inserir_callbacks($modulo, $plugin, $namespace, $evento, $callbackDef, 'filter', null);
            }
        }
    }
}

/**
 * Registra/atualiza os hooks do projeto (project/hooks/hooks.json).
 * Remove hooks de projeto antigos e re-insere os do JSON atual.
 */
function hooks_registrar_projeto(): void {
    global $_GESTOR;

    $hooksJsonPath = $_GESTOR['ROOT_PATH'] . 'project/hooks/hooks.json';

    if (!file_exists($hooksJsonPath)) {
        return;
    }

    $json = @json_decode(file_get_contents($hooksJsonPath), true);
    if (!$json) {
        return;
    }

    // 1. Limpar hooks de projeto antigos
    banco_delete('hooks', "WHERE projeto=1");

    // 2. Processar actions
    if (isset($json['actions']) && is_array($json['actions'])) {
        foreach ($json['actions'] as $namespace => $eventos) {
            if (!is_array($eventos)) continue;
            foreach ($eventos as $evento => $callbackDef) {
                hooks_inserir_callbacks(null, null, $namespace, $evento, $callbackDef, 'action', 1);
            }
        }
    }

    // 3. Processar filters
    if (isset($json['filters']) && is_array($json['filters'])) {
        foreach ($json['filters'] as $namespace => $eventos) {
            if (!is_array($eventos)) continue;
            foreach ($eventos as $evento => $callbackDef) {
                hooks_inserir_callbacks(null, null, $namespace, $evento, $callbackDef, 'filter', 1);
            }
        }
    }
}

/**
 * Insere callback(s) na tabela hooks.
 * Suporta: string simples, array de strings, ou objeto {callback, prioridade, habilitado}.
 *
 * @param string|null $modulo
 * @param string|null $plugin
 * @param string $namespace
 * @param string $evento
 * @param mixed $callbackDef Definição do callback (string, array, ou assoc array)
 * @param string $tipo 'action' ou 'filter'
 * @param int|null $projeto 1 se de projeto, null se de módulo
 */
function hooks_inserir_callbacks(?string $modulo, ?string $plugin, string $namespace, string $evento, mixed $callbackDef, string $tipo, ?int $projeto): void {
    $callbacks = [];

    if (is_string($callbackDef)) {
        // String simples: "nome_funcao"
        $callbacks[] = [
            'callback' => $callbackDef,
            'prioridade' => 10,
            'habilitado' => 1,
        ];
    } elseif (is_array($callbackDef)) {
        if (isset($callbackDef['callback'])) {
            // Objeto com callback e prioridade/habilitado
            $callbacks[] = [
                'callback' => $callbackDef['callback'],
                'prioridade' => isset($callbackDef['prioridade']) ? (int) $callbackDef['prioridade'] : 10,
                'habilitado' => isset($callbackDef['habilitado']) ? (int) $callbackDef['habilitado'] : 1,
            ];
        } else {
            // Array de callbacks (sem / com objetos)
            foreach ($callbackDef as $item) {
                if (is_string($item)) {
                    $callbacks[] = [
                        'callback' => $item,
                        'prioridade' => 10,
                        'habilitado' => 1,
                    ];
                } elseif (is_array($item) && isset($item['callback'])) {
                    $callbacks[] = [
                        'callback' => $item['callback'],
                        'prioridade' => isset($item['prioridade']) ? (int) $item['prioridade'] : 10,
                        'habilitado' => isset($item['habilitado']) ? (int) $item['habilitado'] : 1,
                    ];
                }
            }
        }
    }

    foreach ($callbacks as $cb) {
        $campos = [];
        $sem_aspas = true;

        if ($modulo !== null) {
            $campos[] = ['modulo', banco_escape_field($modulo)];
        }
        if ($plugin !== null) {
            $campos[] = ['plugin', banco_escape_field($plugin)];
        }

        $campos[] = ['namespace', banco_escape_field($namespace)];
        $campos[] = ['evento', banco_escape_field($evento)];
        $campos[] = ['callback', banco_escape_field($cb['callback'])];
        $campos[] = ['tipo', banco_escape_field($tipo)];
        $campos[] = ['prioridade', (string) $cb['prioridade'], $sem_aspas];
        $campos[] = ['habilitado', (string) ($cb['habilitado'] ? 1 : 0), $sem_aspas];

        if ($projeto !== null) {
            $campos[] = ['projeto', '1', $sem_aspas];
        }

        $campos[] = ['status', 'A'];
        $campos[] = ['data_criacao', 'NOW()', $sem_aspas];
        $campos[] = ['data_modificacao', 'NOW()', $sem_aspas];

        banco_insert_name($campos, 'hooks');
    }
}