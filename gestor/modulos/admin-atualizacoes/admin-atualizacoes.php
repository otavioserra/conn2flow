<?php

/**
 * Módulo: admin-atualizacoes
 * Objetivo: Interface administrativa para orquestrar e visualizar execuções
 *           do sistema de atualização automatizada (atualizacoes-sistema.php).
 *
 * Padrões (alinhado ao template `modulo_id`):
 * - Uso de páginas declaradas em admin-atualizacoes.json com placeholders.
 * - Manipulação de HTML via substituição em $_GESTOR['pagina'] (sem HTML inline fixo).
 * - Camada futura de persistência (tabela atualizacoes_execucoes) será integrada
 *   para registrar: id, inicio, fim, modo, exit_code, log, plano, status.
 * - Estrutura de funções seguindo convenções: <modulo>_listar, <modulo>_detalhe, <modulo>_disparar.
 * - Execução de atualização isolada (sem bloqueio) a evoluir com fila/async.
 */

global $_GESTOR;

// Garantir base-path definido para evitar warnings caso módulo seja carregado cedo.
if(empty($_GESTOR['base-path'])){
    // Assume diretório raiz do gestor como base (duas pastas acima deste arquivo).
    $_GESTOR['base-path'] = dirname(__DIR__,2).'/' ;
}

$_GESTOR['modulo-id'] = 'admin-atualizacoes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__.'/admin-atualizacoes.json'), true);

// ================= Utilidades =================

function admin_atualizacoes_logs_dir(): string {
    global $_GESTOR; return $_GESTOR['base-path'].'logs/atualizacoes/';
}
function admin_atualizacoes_temp_sessions_dir(): string {
    global $_GESTOR; return $_GESTOR['base-path'].'temp/atualizacoes/sessions/';
}
// Agora omitimos o log diário (atualizacoes-sistema-YYYYMMDD.log) da UI principal;
// Exibiremos apenas logs de sessão recentes (salvos em temp/atualizacoes/sessions/<sid>.log).
function admin_atualizacoes_listar_logs_recente(int $limit = 20): array {
    $dir = admin_atualizacoes_temp_sessions_dir();
    if(!is_dir($dir)) return [];
    $files = glob($dir.'*.log');
    rsort($files, SORT_STRING);
    return array_slice($files,0,$limit);
}
function admin_atualizacoes_ultimo_plano(): ?string {
    $dir = admin_atualizacoes_logs_dir();
    if(!is_dir($dir)) return null;
    $plans = glob($dir.'plan-*.json');
    rsort($plans, SORT_STRING);
    return $plans[0] ?? null;
}

// ================= Render Helpers =================

function admin_atualizacoes_listar(): void {
    global $_GESTOR;

    // Logs de sessão (temp) – pode ser útil para acesso rápido
    $logs = admin_atualizacoes_listar_logs_recente();
    $linhas = '';
    foreach($logs as $f){
        $base = basename($f);
        $data = date('Y-m-d H:i:s', @filemtime($f));
        $linhas .= '<tr>'
            . '<td>'.htmlspecialchars($data).'</td>'
            . '<td>'.htmlspecialchars($base).'</td>'
            . '<td>'
            . '<a class="c2fc-rotulo" href="detalhe/?log='.urlencode($base).'">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-view-log-button']).'</a>'
            . '</td>'
            . '</tr>';
    }
    if($linhas==='') $linhas = '<tr><td colspan="3" class="c2fc-texto-suave">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-no-records']).'</td></tr>';

    $ultimoPlano = admin_atualizacoes_ultimo_plano();
    $planoLink = $ultimoPlano ? '<a class="c2fc-rotulo" href="detalhe/?plano='.urlencode(basename($ultimoPlano)).'">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-plan-json']).'</a>' : '';

    // Histórico (tabela)
    $historicoLinhas='';
    $ultimaExecucao = '';
    if(function_exists('banco_query')){
        // Consulta últimos 15
        $sql = "SELECT id_atualizacoes_execucoes,started_at,finished_at,release_tag,modo,status,stats_removed,stats_copied,session_log_path,plan_json_path FROM atualizacoes_execucoes ORDER BY started_at DESC LIMIT 15";
        $res = @banco_query($sql);
        if($res){
            while($row = banco_fetch_assoc($res)){
                $statusLabel = htmlspecialchars(admin_atualizacoes_status_rotulo((string)($row['status'] ?? '')), ENT_QUOTES, 'UTF-8');
                $cls = 'grey';
                if($row['status']==='running') $cls='blue'; elseif($row['status']==='success') $cls='green'; elseif($row['status']==='error') $cls='red';
                // req-240: a primeira linha é a execução mais recente e alimenta o resumo do topo.
                if($ultimaExecucao === ''){
                    $ultimaExecucao = htmlspecialchars((string)($row['started_at'] ?? ''), ENT_QUOTES, 'UTF-8')
                        .(!empty($row['release_tag']) ? ' · '.htmlspecialchars((string)$row['release_tag'], ENT_QUOTES, 'UTF-8') : '')
                        .' <span class="c2fc-rotulo c2fc-cor-'.$cls.'">'.$statusLabel.'</span>';
                }
                $acoes=[];
                if(!empty($row['session_log_path']) && file_exists($row['session_log_path'])){
                    $baseLog = basename($row['session_log_path']);
                    $acoes[]='<a class="c2fc-rotulo" href="detalhe/?log='.urlencode($baseLog).'">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-log-title']).'</a>';
                }
                if(!empty($row['plan_json_path']) && file_exists($row['plan_json_path'])){
                    $basePlan = basename($row['plan_json_path']);
                    $acoes[]='<a class="c2fc-rotulo" href="detalhe/?plano='.urlencode($basePlan).'">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-plan-json']).'</a>';
                }
                $historicoLinhas.='<tr>'
                    .'<td>'.htmlspecialchars($row['started_at']??'').'</td>'
                    .'<td>'.htmlspecialchars($row['release_tag']??'').'</td>'
                    .'<td>'.htmlspecialchars($row['modo']??'').'</td>'
                    .'<td><span class="c2fc-rotulo c2fc-cor-'.$cls.'">'.$statusLabel.'</span></td>'
                    .'<td class="hidden md:table-cell">'.htmlspecialchars($row['stats_removed']??'').'</td>'
                    .'<td class="hidden md:table-cell">'.htmlspecialchars($row['stats_copied']??'').'</td>'
                    .'<td class="hidden md:table-cell">'.htmlspecialchars($row['finished_at']??'').'</td>'
                    .'<td>'.implode(' ',$acoes).'</td>'
                .'</tr>';
            }
        }
    }
    if($historicoLinhas==='') $historicoLinhas='<tr><td colspan="8" class="c2fc-texto-suave">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-no-records']).'</td></tr>';

    $componenteId = function_exists('interface_componente_variante')
        ? interface_componente_variante('atualizacoes-lista')
        : 'atualizacoes-lista';
    $comp = gestor_componente(['id' => $componenteId]);
    $comp = modelo_var_troca_tudo($comp,'#plano-link#',$planoLink);
    $comp = modelo_var_troca_tudo($comp,'#linhas#',$linhas);
    $comp = modelo_var_troca_tudo($comp,'#historico_linhas#',$historicoLinhas);

    // req-240: resumo do topo e choques dentro da aba de registros. A variante antiga não tem os marcadores
    // e continua recebendo a seção de choques ao final (req-198).
    $v = function($id){ global $_GESTOR; return htmlspecialchars((string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>$id]), ENT_QUOTES, 'UTF-8'); };
    $pendentes = admin_atualizacoes_choques_pendentes();
    $comp = modelo_var_troca_tudo($comp,'#versao-atual#',htmlspecialchars((string)($_GESTOR['versao'] ?? '—'), ENT_QUOTES, 'UTF-8'));
    $comp = modelo_var_troca_tudo($comp,'#ultima-execucao#',$ultimaExecucao !== '' ? $ultimaExecucao : $v('updates-summary-never'));
    $comp = modelo_var_troca_tudo($comp,'#choques-pendentes#',$pendentes > 0 ? (string)$pendentes : $v('updates-summary-clashes-none'));
    $comp = modelo_var_troca_tudo($comp,'#choques-selo#',$pendentes > 0 ? ' <span class="c2fc-rotulo c2fc-cor-orange">'.$pendentes.'</span>' : '');
    if(strpos($comp,'#choques#') !== false){
        $comp = modelo_var_troca_tudo($comp,'#choques#',admin_atualizacoes_choques_html(true));
    } else {
        $comp .= admin_atualizacoes_choques_html();
    }
    // req-245: aba Automático (configuração e estado da atualização automática).
    $comp = admin_atualizacoes_auto_preencher($comp);
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#dynamic-content#',$comp);
    // Incluir JS do módulo
    if(function_exists('gestor_pagina_javascript_incluir')) gestor_pagina_javascript_incluir();
}

/**
 * req-198: choques das entregas (tabela `atualizacoes_choques`) — sobreposições do projeto/plugin que a
 * atualização do core preservou, edições no servidor e retiradas que não puderam acontecer. A decisão
 * (sobrescrever, manter, mesclar) fica no detalhe (req-199).
 */
function admin_atualizacoes_choques_html(bool $emAba = false): string {
    global $_GESTOR;
    $vazio = function() use ($emAba){
        global $_GESTOR;
        return $emAba ? '<p class="text-sm text-slate-600">'.htmlspecialchars((string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-clash-empty']), ENT_QUOTES, 'UTF-8').'</p>' : '';
    };
    if(!function_exists('banco_query')) return $vazio();
    $existe = @banco_query("SHOW TABLES LIKE 'atualizacoes_choques'");
    if(!$existe || !banco_num_rows($existe)) return $vazio();
    $v = function($id){ global $_GESTOR; return htmlspecialchars((string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>$id]), ENT_QUOTES, 'UTF-8'); };
    $e = function($t){ return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
    $linhas = '';
    $res = @banco_query("SELECT id_atualizacoes_choques,data_criacao,origem,camada,versao,caminho,motivo,camada_dona,resolucao FROM atualizacoes_choques ORDER BY id_atualizacoes_choques DESC LIMIT 50");
    if($res) while($r = banco_fetch_assoc($res)){
        $cor = in_array($r['motivo'], ['sobreposto'], true) ? 'blue' : 'orange';
        $linhas .= '<tr>'
            .'<td>'.$e($r['data_criacao']).'</td>'
            .'<td>'.$e($r['camada']).'<br><small>'.$e($r['origem']).'</small></td>'
            .'<td><code>'.$e($r['caminho']).'</code></td>'
            .'<td><span class="c2fc-rotulo c2fc-cor-'.$cor.'">'.$v('updates-clash-'.$r['motivo']).'</span></td>'
            .'<td>'.$e($r['camada_dona'] ?? '—').'</td>'
            .'<td>'.$e($r['versao'] ?? '').'</td>'
            .'<td>'.($r['resolucao'] ? $v('updates-clash-res-'.$r['resolucao']) : $v('updates-clash-pending')).'</td>'
            .'<td><a class="c2fc-rotulo" href="detalhe/?choque='.(int)$r['id_atualizacoes_choques'].'">'.$v('updates-clash-view').'</a></td>'
            .'</tr>';
    }
    if($linhas === '') $linhas = '<tr><td colspan="8" class="c2fc-texto-suave">'.$v('updates-clash-empty').'</td></tr>';
    $tabela = '<p class="text-sm text-slate-600">'.$v('updates-clash-help').'</p>'
        .'<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm [&_th]:px-3 [&_th]:py-2 [&_td]:px-3 [&_td]:py-3 [&_td]:align-top"><thead class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500 uppercase"><tr>'
        .'<th>'.$v('updates-clash-col-date').'</th><th>'.$v('updates-clash-col-layer').'</th><th>'.$v('updates-clash-col-path').'</th>'
        .'<th>'.$v('updates-clash-col-reason').'</th><th>'.$v('updates-clash-col-owner').'</th><th>'.$v('updates-clash-col-version').'</th>'
        .'<th>'.$v('updates-clash-col-resolution').'</th><th></th></tr></thead><tbody class="divide-y divide-slate-100">'.$linhas.'</tbody></table></div>';
    if($emAba) return '<div class="space-y-3" data-atualizacoes-choques>'.$tabela.'</div>';
    return '<section class="space-y-3 rounded border border-slate-200 bg-white p-4" data-atualizacoes-choques><h2 class="text-base font-semibold text-slate-900">'.$v('updates-clash-title').'</h2>'.$tabela.'</section>';
}

/** req-240: quantos choques ainda esperam decisão (0 quando a tabela não existe). */
function admin_atualizacoes_choques_pendentes(): int {
    if(!function_exists('banco_query')) return 0;
    $existe = @banco_query("SHOW TABLES LIKE 'atualizacoes_choques'");
    if(!$existe || !banco_num_rows($existe)) return 0;
    $res = @banco_query("SELECT COUNT(*) AS total FROM atualizacoes_choques WHERE resolucao IS NULL OR resolucao=''");
    $linha = $res ? banco_fetch_assoc($res) : null;
    return (int)($linha['total'] ?? 0);
}

/** req-240: rótulo traduzido do status de uma execução; status desconhecido sai como veio do banco. */
function admin_atualizacoes_status_rotulo(string $status): string {
    global $_GESTOR;
    if(!in_array($status, ['success','error','running'], true)) return $status;
    $rotulo = (string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-status-'.$status]);
    return $rotulo !== '' ? $rotulo : $status;
}

/** req-198: detalhe de um choque (diff e onde está a versão nova); req-199: a decisão. */
function admin_atualizacoes_choque_detalhe(int $id): string {
    global $_GESTOR;
    $e = function($t){ return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
    $v = function($id){ global $_GESTOR; return htmlspecialchars((string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>$id]), ENT_QUOTES, 'UTF-8'); };
    $res = @banco_query("SELECT * FROM atualizacoes_choques WHERE id_atualizacoes_choques=".(int)$id." LIMIT 1");
    $r = $res ? banco_fetch_assoc($res) : null;
    if(!$r) return '<p class="rounded border border-amber-300 bg-amber-50 p-3 text-amber-900">'.$v('updates-clash-not-found').'</p>';
    $diff = (string)($r['diff'] ?? '');
    return '<h3 class="text-base font-semibold text-slate-900">'.$v('updates-clash-title').': <code>'.$e($r['caminho']).'</code></h3>'
        .'<dl class="space-y-2 text-sm">'
        .'<div class="flex flex-wrap gap-2"><dt class="font-semibold">'.$v('updates-clash-col-reason').':</dt><dd>'.$v('updates-clash-'.$r['motivo']).'</dd></div>'
        .'<div class="flex flex-wrap gap-2"><dt class="font-semibold">'.$v('updates-clash-col-layer').':</dt><dd>'.$e($r['camada']).' ('.$e($r['origem']).'), '.$v('updates-clash-col-version').' '.$e($r['versao']).'</dd></div>'
        .'<div class="flex flex-wrap gap-2"><dt class="font-semibold">'.$v('updates-clash-col-owner').':</dt><dd>'.$e($r['camada_dona'] ?? '—').'</dd></div>'
        .'<div class="flex flex-wrap gap-2"><dt class="font-semibold">'.$v('updates-clash-copy').':</dt><dd>'.($r['copia'] ? '<code>'.$e($r['copia']).'</code>' : '—').'</dd></div>'
        .'</dl>'
        .($diff !== '' ? '<pre class="max-h-[60vh] overflow-auto whitespace-pre-wrap rounded border border-slate-200 bg-slate-950 p-3 font-mono text-xs text-slate-100">'.$e($diff).'</pre>' : '')
        .admin_atualizacoes_choque_decisao_html($r);
}

/**
 * req-199 / BATCH-205: decisão sobre o choque — os botões das ações que valem para o motivo e o editor de
 * mescla (começa com a versão que está no ar; a nova fica ao lado, só leitura). Resolvido: quem e quando.
 */
function admin_atualizacoes_choque_decisao_html(array $r): string {
    global $_GESTOR;
    $e = function($t){ return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
    $v = function($id){ global $_GESTOR; return htmlspecialchars((string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>$id]), ENT_QUOTES, 'UTF-8'); };
    if(!empty($r['resolucao'])){
        return '<div class="rounded border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900" data-choque-resolvido>'.$v('updates-clash-res-'.$r['resolucao'])
            .(!empty($r['resolvido_em']) ? ' — '.$e($r['resolvido_em']) : '').(!empty($r['resolvido_por']) ? ' ('.$e($r['resolvido_por']).')' : '').'</div>';
    }
    require_once $_GESTOR['bibliotecas-path'].'atualizacoes-choques.php';
    $acoes = instalacao_choque_acoes($r);
    $versoes = instalacao_choque_versoes($_GESTOR['ROOT_PATH'], $r);
    $botoes = '';
    foreach($acoes as $a){
        $botoes .= '<button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" data-choque-acao="'.$e($a).'">'.$v('updates-clash-act-'.$a).'</button>';
    }
    $mescla = '';
    if(in_array('mesclar', $acoes, true) && !$versoes['binario']){
        $mescla = '<div class="hidden space-y-3" data-choque-mescla>'
            .'<p>'.$v('updates-clash-merge-help').'</p>'
            .'<div class="grid grid-cols-1 gap-3 md:grid-cols-2">'
            .'<label class="block space-y-1 text-sm"><span>'.$v('updates-clash-merge-result').'</span><textarea rows="22" data-choque-conteudo class="w-full rounded border border-slate-300 p-2 font-mono">'.$e($versoes['no_ar'] ?? '').'</textarea></label>'
            .'<label class="block space-y-1 text-sm"><span>'.$v('updates-clash-merge-new').'</span><textarea rows="22" readonly class="w-full rounded border border-slate-300 p-2 font-mono">'.$e($versoes['nova'] ?? '').'</textarea></label>'
            .'</div>'
            .'<button type="button" class="rounded bg-sky-700 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-800" data-choque-mesclar-salvar>'.$v('updates-clash-merge-save').'</button>'
            .'</div>';
    }
    return '<section class="space-y-3 border-t border-slate-200 pt-4" data-choque-decisao data-choque-id="'.(int)$r['id_atualizacoes_choques'].'"'
        .' data-msg-confirmar="'.$v('updates-clash-confirm').'" data-msg-ok="'.$v('updates-clash-resolved-ok').'">'
        .'<h4 class="text-base font-semibold text-slate-900">'.$v('updates-clash-actions').'</h4>'
        .'<p class="text-sm text-slate-600">'.$v('updates-clash-actions-help-'.$r['motivo']).'</p>'
        .'<div class="flex flex-wrap gap-2">'.$botoes.'</div>'
        .$mescla
        .'<div class="hidden rounded border p-3 text-sm" data-choque-msg role="status"></div>'
        .'</section>';
}

function admin_atualizacoes_detalhe(): void {
    global $_GESTOR;
    $dirLogs = admin_atualizacoes_logs_dir();
    $dirSess = admin_atualizacoes_temp_sessions_dir();
    $log = $_GET['log'] ?? null; $plano = $_GET['plano'] ?? null;
    $conteudo = '';
    if(isset($_GET['choque']) && ctype_digit((string)$_GET['choque'])){
        $conteudo = admin_atualizacoes_choque_detalhe((int)$_GET['choque']);
    } elseif($log){
        $path = realpath($dirLogs.$log);
        if((!$path || strpos($path,$dirLogs)!==0 || !is_file($path)) && is_file($dirSess.$log)) {
            $path = realpath($dirSess.$log);
        }
        if($path && ( (strpos($path,$dirLogs)===0) || (strpos($path,$dirSess)===0) ) && is_file($path)) {
            $raw = @file_get_contents($path);
            $safe = htmlspecialchars($raw);
            $conteudo = '<h3 class="text-base font-semibold text-slate-900">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-log-title']).': '.htmlspecialchars($log, ENT_QUOTES, 'UTF-8').'</h3>'
                .'<pre class="max-h-[60vh] overflow-auto whitespace-pre-wrap rounded border border-slate-200 bg-slate-950 p-3 font-mono text-xs text-slate-100">'.$safe.'</pre>';
        } else $conteudo = '<p class="rounded border border-amber-300 bg-amber-50 p-3 text-amber-900">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-invalid-log']).'</p>';
    } elseif($plano){
        // Os planos moram na pasta de logs; a variável `$dir` usada antes não existia neste escopo.
        $path = realpath($dirLogs.$plano);
        if($path && strpos($path,$dirLogs)===0 && is_file($path)) {
            $json = @file_get_contents($path);
            $safe = htmlspecialchars($json);
            $conteudo = '<h3 class="text-base font-semibold text-slate-900">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-plan-json']).': '.htmlspecialchars($plano, ENT_QUOTES, 'UTF-8').'</h3>'
                .'<pre class="max-h-[60vh] overflow-auto whitespace-pre-wrap rounded border border-slate-200 bg-slate-950 p-3 font-mono text-xs text-slate-100">'.$safe.'</pre>';
        } else $conteudo = '<p class="rounded border border-amber-300 bg-amber-50 p-3 text-amber-900">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-invalid-plan']).'</p>';
    } else {
        $conteudo = '<p class="rounded border border-slate-200 bg-slate-50 p-3 text-slate-700">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-select-log-plan']).'</p>';
    }
    $componenteId = function_exists('interface_componente_variante')
        ? interface_componente_variante('atualizacoes-detalhe-comp')
        : 'atualizacoes-detalhe-comp';
    $comp = gestor_componente(['id' => $componenteId]);
    $comp = modelo_var_troca_tudo($comp,'#conteudo#',$conteudo);
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#dynamic-content#',$comp);
    // Incluir JS do módulo
    if(function_exists('gestor_pagina_javascript_incluir')) gestor_pagina_javascript_incluir();
}


// ==== Ajax

// --------------- AJAX Handlers ---------------
// Wrapper que encaminha chamadas ao script atualizacoes-sistema.php usando a API web (action=...)
function admin_atualizacoes_call_system(array $params): array {
    global $_GESTOR;
    $script = $_GESTOR['base-path'].'controladores/atualizacoes/atualizacoes-sistema.php';
    if(!is_file($script)) return ['error'=>'Script não encontrado'];
    // Construir query local (inclui action)
    $query = http_build_query($params);
    // Inclui script em escopo isolado capturando saída
    $_GET = $_REQUEST = [];
    parse_str($query,$_GET); $_REQUEST=$_GET; // simula requisição
    ob_start();
    include $script; // script imprime JSON
    $raw = ob_get_clean();
    $json = json_decode($raw,true);
    if($json===null) return ['error'=>'Resposta inválida','raw'=>$raw];
    return $json;
}

function admin_atualizacoes_ajax_update(){
    global $_GESTOR;
    $params = $_POST['params'] ?? $_GET['params'] ?? [];
    if(!is_array($params)) $params=[];
    $acao = $params['acao'] ?? '';
    $sid = $params['sid'] ?? '';
    $resp = [];
    try {
        switch($acao){
            // notas: o 'start' recebe flags via map abaixo (inclui agora 'wipe')
            case 'start':
                $modo = $params['modo'] ?? 'full';
                $mapModo = [];
                if($modo==='only-files') $mapModo['only_files']=1; elseif($modo==='only-db') $mapModo['only_db']=1; // flags que webStart interpreta
                // Flags extras opcionais
                $extraFlagsMap = [
                    'local'=>'local',
                    'debug'=>'debug',
                    'dry_run'=>'dry_run',
                    'no_db'=>'no_db',
                    'no_verify'=>'no_verify',
                    'download_only'=>'download_only',
                    'skip_download'=>'skip_download',
                    'force_all'=>'force_all',
                    'log_diff'=>'log_diff',
                    'backup'=>'backup',
                    'wipe'=>'wipe', // nova flag: envia --wipe ao backend
                    'clean_temp'=>'clean_temp',
                    'csrf_capable'=>'csrf_capable',
                    'no_health'=>'no_health', // req-201: verificação e volta automática no finalize
                    'no_rollback'=>'no_rollback',
                ];
                $extras=[]; foreach($extraFlagsMap as $k=>$flag){ if(!empty($params[$k])) $extras[$flag]=1; }
                if(!empty($params['tables'])) $extras['tables']=$params['tables'];
                foreach(['health_url','health_ip'] as $k){ if(!empty($params[$k])) $extras[$k]=(string)$params[$k]; }
                if(!empty($params['logs_retention_days'])) $extras['logs_retention_days']=(int)$params['logs_retention_days'];
                $domain = trim((string)($params['domain'] ?? ''));
                $resp = admin_atualizacoes_call_system(array_filter(array_merge([
                    'action'=>'start',
                    'domain'=>$domain !== '' ? $domain : null,
                    'tag'=>$params['tag'] ?? null,
                ],$mapModo,$extras)));
                break;
            case 'deploy':
                $resp = admin_atualizacoes_call_system(['action'=>'deploy','sid'=>$sid]);
                break;
            case 'db':
                $resp = admin_atualizacoes_call_system(['action'=>'db','sid'=>$sid]);
                break;
            case 'finalize':
                $resp = admin_atualizacoes_call_system(['action'=>'finalize','sid'=>$sid]);
                break;
            case 'status':
                $resp = admin_atualizacoes_call_system(['action'=>'status','sid'=>$sid]);
                break;
            case 'cancel':
                $resp = admin_atualizacoes_call_system(['action'=>'cancel','sid'=>$sid]);
                break;
            default:
                $resp = ['error'=>'Ação AJAX desconhecida'];
        }
        if(isset($resp['error'])) {
            $_GESTOR['ajax-json']=['status'=>'erro','erro'=>$resp['error'],'data'=>$resp];
        } else {
            $_GESTOR['ajax-json']=['status'=>'ok','data'=>$resp];
        }
    } catch(Throwable $e){
        $_GESTOR['ajax-json']=['status'=>'erro','erro'=>$e->getMessage()];
    }
}

/** req-199 / BATCH-205: decisão sobre um choque pelo painel (mesmo motor da API). */
function admin_atualizacoes_ajax_choque_resolver(){
    global $_GESTOR;
    require_once $_GESTOR['bibliotecas-path'].'atualizacoes-choques.php';
    $id = (int)($_POST['id'] ?? 0);
    $acao = (string)($_POST['acao'] ?? '');
    $conteudo = isset($_POST['conteudo']) ? (string)$_POST['conteudo'] : null;
    $u = function_exists('gestor_usuario') ? (array)gestor_usuario() : [];
    $quem = 'painel:'.(string)($u['email'] ?? ($u['usuario'] ?? ($_GESTOR['usuario-id'] ?? '?')));
    $r = atualizacoes_choques_resolver($_GESTOR['ROOT_PATH'], $id, $acao, $acao === 'mesclar' ? $conteudo : null, $quem);
    $_GESTOR['ajax-json'] = $r['ok']
        ? ['status' => 'Ok', 'data' => ['id' => $id, 'acao' => $r['acao'], 'resolvidos' => $r['resolvidos']]]
        : ['status' => 'Erro', 'message' => $r['erro']];
}

// ================= Interface Principal =================

// ==== Atualização automática (req-245 / BATCH-254)

/** Carrega as funções compartilhadas com a rotina agendada (o arquivo de cron não tem efeito ao ser incluído). */
function admin_atualizacoes_auto_carregar(): void {
    global $_GESTOR;
    require_once $_GESTOR['modulos-path'] . 'admin-atualizacoes/admin-atualizacoes.cron.php';
    admin_atualizacoes_auto_bibliotecas();
}

/** Texto de uma variável do módulo, escapado para HTML. */
function admin_atualizacoes_auto_txt(string $id): string {
    return htmlspecialchars((string)gestor_variaveis(['modulo' => 'admin-atualizacoes', 'id' => $id]), ENT_QUOTES, 'UTF-8');
}

/** Data e hora no formato da tela. */
function admin_atualizacoes_auto_data(?int $quando): string {
    return $quando ? date('d/m/Y H:i', $quando) : '';
}

/** Rótulo do resultado de uma tentativa: os códigos de erro do atualizador viram "Falhou". */
function admin_atualizacoes_auto_resultado(string $status): array {
    if ($status === 'success') return ['auto-result-success', 'green'];
    if ($status === 'rolled_back') return ['auto-result-rolled_back', 'orange'];
    if ($status === 'locked') return ['auto-result-locked', 'grey'];

    return ['auto-result-error', 'red'];
}

/** Bloco de estado da aba Automático (também devolvido pelas ações AJAX, para a tela se atualizar). */
function admin_atualizacoes_auto_estado_html(array $config): string {
    $t = 'admin_atualizacoes_auto_txt';
    $e = $config['estado'];
    $linha = function (string $icone, string $rotulo, string $valor): string {
        return '<div class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">'
            . '<i data-lucide="' . $icone . '" class="mt-0.5 size-4 shrink-0 text-slate-500"></i>'
            . '<div class="min-w-0"><p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">' . $rotulo . '</p>'
            . '<p class="mt-0.5 text-sm font-medium break-words text-slate-900">' . $valor . '</p></div></div>';
    };
    $selo = '<span class="c2fc-rotulo c2fc-cor-' . ($config['ativo'] ? 'green' : 'grey') . '">' . $t($config['ativo'] ? 'auto-status-on' : 'auto-status-off') . '</span>';
    $checagem = $e['ultima_checagem'] ? admin_atualizacoes_auto_data($e['ultima_checagem']) : $t('auto-state-never');
    $encontrada = $e['versao_encontrada'] ? htmlspecialchars($e['versao_encontrada'], ENT_QUOTES, 'UTF-8') : $t('auto-state-none');
    if ($e['motivo'] && $e['motivo'] !== 'desligada') {
        $motivo = (string)gestor_variaveis(['modulo' => 'admin-atualizacoes', 'id' => 'auto-reason-' . $e['motivo']]);
        if ($motivo !== '') $encontrada .= '<span class="mt-0.5 block text-xs font-normal text-slate-600">' . htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    $proxima = atualizacao_automatica_proxima($config, time());
    if ($e['pendente']) {
        $tentativa = htmlspecialchars($e['pendente']['tag'], ENT_QUOTES, 'UTF-8') . ' <span class="c2fc-rotulo c2fc-cor-blue">' . $t('auto-state-pending') . '</span>';
    } elseif ($e['ultima_tentativa']) {
        [$rotulo, $cor] = admin_atualizacoes_auto_resultado((string)$e['ultima_tentativa']['resultado']);
        $tentativa = htmlspecialchars((string)$e['ultima_tentativa']['tag'], ENT_QUOTES, 'UTF-8') . ' · ' . admin_atualizacoes_auto_data((int)$e['ultima_tentativa']['quando'])
            . ' <span class="c2fc-rotulo c2fc-cor-' . $cor . '">' . $t($rotulo) . '</span>';
    } else {
        $tentativa = $t('auto-state-none');
    }
    $recusadas = '';
    foreach ($e['recusadas'] as $tag => $info) {
        [$rotulo, $cor] = admin_atualizacoes_auto_resultado((string)$info['motivo']);
        $tagHtml = htmlspecialchars((string)$tag, ENT_QUOTES, 'UTF-8');
        $recusadas .= '<li class="flex flex-wrap items-center justify-between gap-2 py-2">'
            . '<span class="min-w-0 text-sm font-medium break-words text-slate-900">' . $tagHtml . ' · ' . admin_atualizacoes_auto_data((int)$info['quando'])
            . ' <span class="c2fc-rotulo c2fc-cor-' . $cor . '">' . $t($rotulo) . '</span></span>'
            . '<button type="button" class="c2fc-botao c2fc-botao-pequeno" data-auto-liberar="' . $tagHtml . '" data-c2f-dica="' . $t('auto-retry-tip') . '" data-c2f-dica-pos="top right">'
            . '<i data-lucide="rotate-ccw" class="size-4"></i>' . $t('auto-retry') . '</button></li>';
    }
    $recusadas = $recusadas !== ''
        ? '<ul class="divide-y divide-slate-100">' . $recusadas . '</ul>'
        : '<p class="text-sm text-slate-600">' . $t('auto-state-none') . '</p>';

    return '<div class="grid grid-cols-1 gap-3 md:grid-cols-2">'
        . $linha('power', $t('auto-state-switch'), $selo)
        . $linha('calendar-clock', $t('auto-state-next'), $proxima ? admin_atualizacoes_auto_data($proxima) : $t('auto-state-none'))
        . $linha('search-check', $t('auto-state-last-check'), $checagem)
        . $linha('package', $t('auto-state-found'), $encontrada)
        . $linha('history', $t('auto-state-last-attempt'), $tentativa)
        . '</div>'
        . '<div class="rounded-lg border border-slate-200 p-3"><p class="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">' . $t('auto-state-refused') . '</p>' . $recusadas . '</div>';
}

/** Preenche a aba Automático do componente com a configuração e o estado da instalação. */
function admin_atualizacoes_auto_preencher(string $comp): string {
    if (strpos($comp, '#auto-estado#') === false) return $comp;
    admin_atualizacoes_auto_carregar();
    $config = admin_atualizacoes_auto_resolver_pendente(atualizacao_automatica_ler(admin_atualizacoes_auto_pasta()));
    $horas = '';
    for ($h = 0; $h < 24; $h++) {
        $horas .= '<option value="' . $h . '"' . ($h === (int)$config['hora'] ? ' selected' : '') . '>' . sprintf('%02d:00', $h) . '</option>';
    }
    $comp = modelo_var_troca_tudo($comp, '#auto-ativo#', $config['ativo'] ? 'checked' : '');
    $comp = modelo_var_troca_tudo($comp, '#auto-backup#', $config['backup'] ? 'checked' : '');
    $comp = modelo_var_troca_tudo($comp, '#auto-hora-opcoes#', $horas);
    foreach (array_keys(ATUALIZACAO_AUTOMATICA_PERIODOS) as $periodo) {
        $comp = modelo_var_troca_tudo($comp, '#auto-sel-' . $periodo . '#', $config['periodo'] === $periodo ? 'true' : 'false');
    }

    return modelo_var_troca_tudo($comp, '#auto-estado#', admin_atualizacoes_auto_estado_html($config));
}

/** Liga ou desliga a rotina no `admin-cron`, criando a linha se a tarefa ainda não foi sincronizada. */
function admin_atualizacoes_auto_sincronizar_tarefa(bool $ativo): void {
    $id = 'admin-atualizacoes-automatica';
    $existe = banco_select(Array('unico' => true, 'tabela' => 'cron_tarefas', 'campos' => Array('id'), 'extra' => "WHERE id='" . $id . "'"));
    if ($existe) {
        banco_update_campo('ativo', $ativo ? 1 : 0, true, false);
        banco_update_campo('status', 'A');
        // Marcado como ajustado no painel: a sincronização dos manifestos não devolve o valor do arquivo.
        banco_update_campo('user_modified', 1, true, false);
        banco_update_campo('data_modificacao', 'NOW()', true, false);
        banco_update_executar('cron_tarefas', "WHERE id='" . $id . "'");

        return;
    }
    $campos = null;
    $campos[] = Array('id', $id, false);
    $campos[] = Array('nome', banco_escape_field((string)gestor_variaveis(['modulo' => 'admin-atualizacoes', 'id' => 'auto-cron-name'])), false);
    $campos[] = Array('descricao', banco_escape_field((string)gestor_variaveis(['modulo' => 'admin-atualizacoes', 'id' => 'auto-cron-description'])), false);
    $campos[] = Array('modulo', 'admin-atualizacoes', false);
    $campos[] = Array('frequencia', 'horario', false);
    $campos[] = Array('expressao_cron', '0 * * * *', false);
    $campos[] = Array('funcao_callback', 'admin_atualizacoes_cron_automatica', false);
    $campos[] = Array('parametros', '', false);
    $campos[] = Array('ativo', $ativo ? 1 : 0, true);
    $campos[] = Array('origem', 'modulo', false);
    $campos[] = Array('user_modified', 1, true);
    $campos[] = Array('status', 'A', false);
    $campos[] = Array('data_criacao', 'NOW()', true);
    $campos[] = Array('data_modificacao', 'NOW()', true);
    banco_insert_name($campos, 'cron_tarefas');
}

/** Ações da aba Automático: `salvar`, `verificar` (só confere a versão publicada) e `liberar` (versão recusada). */
function admin_atualizacoes_ajax_auto(): void {
    global $_GESTOR;
    $params = $_POST['params'] ?? [];
    if (!is_array($params)) $params = [];
    $acao = (string)($params['acao'] ?? '');
    try {
        admin_atualizacoes_auto_carregar();
        $pasta = admin_atualizacoes_auto_pasta();
        $config = admin_atualizacoes_auto_resolver_pendente(atualizacao_automatica_ler($pasta));
        $mensagem = '';
        switch ($acao) {
            case 'salvar':
                // Só as quatro escolhas da tela entram; o estado é o que já estava gravado.
                $config = atualizacao_automatica_normalizar([
                    'ativo' => $params['ativo'] ?? false,
                    'periodo' => $params['periodo'] ?? '',
                    'hora' => $params['hora'] ?? null,
                    'backup' => $params['backup'] ?? false,
                    'estado' => $config['estado'],
                ]);
                if (!atualizacao_automatica_gravar($pasta, $config)) throw new RuntimeException('write');
                admin_atualizacoes_auto_sincronizar_tarefa($config['ativo']);
                $mensagem = 'auto-saved';
                break;
            case 'verificar':
                $conferida = admin_atualizacoes_auto_conferir($config);
                $config = $conferida['config'];
                if ($conferida['consulta']['ok']) {
                    $nova = $conferida['consulta']['tag'] !== null && atualizacao_automatica_mais_nova($conferida['consulta']['tag'], (string)($_GESTOR['versao'] ?? ''));
                    $config['estado']['motivo'] = $conferida['consulta']['tag'] === null ? 'sem-versao-publicada' : ($nova ? null : 'ja-atualizado');
                    atualizacao_automatica_gravar($pasta, $config);
                }
                $mensagem = $conferida['consulta']['ok'] ? 'auto-checked' : 'auto-reason-consulta-falhou';
                break;
            case 'liberar':
                $tag = (string)($params['tag'] ?? '');
                if (!atualizacao_automatica_tag_valida($tag)) throw new RuntimeException('tag');
                $config = atualizacao_automatica_liberar($config, $tag);
                if (!atualizacao_automatica_gravar($pasta, $config)) throw new RuntimeException('write');
                $mensagem = 'auto-released';
                break;
            default:
                throw new RuntimeException('acao');
        }
        $_GESTOR['ajax-json'] = ['status' => 'ok', 'data' => [
            'mensagem' => (string)gestor_variaveis(['modulo' => 'admin-atualizacoes', 'id' => $mensagem]),
            'ativo' => $config['ativo'],
            'estado' => admin_atualizacoes_auto_estado_html($config),
        ]];
    } catch (Throwable $e) {
        $_GESTOR['ajax-json'] = ['status' => 'erro', 'erro' => (string)gestor_variaveis(['modulo' => 'admin-atualizacoes', 'id' => 'auto-error'])];
    }
}

function admin_atualizacoes_start(){
    global $_GESTOR;

    gestor_incluir_bibliotecas();

    if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'update': admin_atualizacoes_ajax_update(); break;
			case 'choque-resolver': admin_atualizacoes_ajax_choque_resolver(); break;
			case 'auto': admin_atualizacoes_ajax_auto(); break;
		}
		
		interface_ajax_finalizar();
    } else {
        $_GESTOR['tailwind-page-bundle'] = true;
        interface_iniciar();

        switch($_GESTOR['opcao']){
            case 'detalhe-atualizacao': admin_atualizacoes_detalhe(); break;
            case 'disparar': /* página descontinuada: redireciona para lista */ admin_atualizacoes_listar(); break;
            case 'listar-atualizacoes':
            default: admin_atualizacoes_listar(); break;
        }

        interface_finalizar();
    }
}

admin_atualizacoes_start();

?>
