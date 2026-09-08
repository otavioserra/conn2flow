<?php
/**
 * Despacho de tarefas do painel Admin Cron (REQ-039 / BATCH-166).
 *
 * Arquivo SEM efeito colateral: só define funções. `admin-cron.php` termina em
 * `admin_cron_start()`, que abre a interface — carregá-lo para chegar a estas funções dispararia
 * a renderização do painel. Foi o mesmo motivo que levou o Host Manager a extrair o domínio dele
 * no BATCH-028, e é o que permite testá-las e reutilizá-las fora do ciclo de vida do módulo.
 *
 * O que vive aqui: decidir se uma tarefa precisa rodar fora do processo web e colocá-la para
 * rodar como processo CLI independente do pool PHP-FPM.
 *
 * @package Conn2Flow
 * @subpackage Admin Cron
 */

/**
 * Le uma configuracao do despacho, aceitando `.env` e `$_GESTOR['config']`.
 *
 * O nucleo NAO popula `$_GESTOR['config']` — essa chave e uma convencao que o config-loader do
 * Host Manager cria para si. Ler so dali deixaria todas as opcoes abaixo inertes, sem erro
 * visivel: a estrategia por SSH nunca seria configuravel e o binario do PHP nunca poderia ser
 * apontado a mao (REQ-040).
 *
 * `$_ENV` antes de `getenv()` porque o Dotenv do Gestor e carregado em modo imutavel e, conforme
 * os adaptadores ativos, pode preencher apenas a superglobal.
 *
 * @param string $chave Chave em `$_GESTOR['config']`.
 * @param string $env   Variavel de ambiente equivalente.
 * @return string Valor normalizado, ou string vazia.
 */
function admin_cron_config($chave, $env){
	global $_GESTOR;

	$valor = isset($_ENV[$env]) ? $_ENV[$env] : getenv($env);

	if($valor === false || $valor === null || trim((string)$valor) === ''){
		$valor = isset($_GESTOR['config'][$chave]) ? $_GESTOR['config'][$chave] : '';
	}

	return is_scalar($valor) ? trim((string)$valor) : '';
}

/**
 * Registra, sem interromper o disparo, por que o caminho desacoplado nao pode ser usado.
 *
 * @param string $id
 * @param string $erro
 * @return void
 */
function cron_log_admin_fallback($id, $erro){
	if(function_exists('gestor_log')){
		gestor_log('[admin-cron] disparo desacoplado indisponivel para "'.$id.'": '.$erro);
	}
}

/**
 * Diz se a tarefa precisa rodar fora do processo web.
 *
 * A declaracao vive na propria tarefa (`parametros.execucao = "desacoplada"`), e nao numa lista
 * fixa dentro do nucleo: quem sabe que uma rotina reinicia servico e o modulo dono dela. O
 * nucleo nao deve conhecer o `host-manager` pelo nome. A chave de configuracao
 * `cron_tarefas_desacopladas` fica como escape do operador para uma tarefa ja publicada.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return bool
 */
function admin_cron_tarefa_desacoplada($tarefa){
	global $_GESTOR;

	$parametros = Array();
	if(!empty($tarefa['parametros'])){
		$decodificado = json_decode((string)$tarefa['parametros'], true);
		if(is_array($decodificado)) $parametros = $decodificado;
	}

	if(admin_cron_parametros_pedem_desacoplamento($parametros)) return true;

	// O `parametros` do banco pode estar DESATUALIZADO: a sincronizacao so o reescreve quando
	// `user_modified` esta vazio, e basta o operador ter pausado a tarefa uma vez para congelar
	// a versao antiga. Como a declaracao aqui e de SEGURANCA (rotina que reinicia servico nao
	// pode rodar no worker web), o manifesto do modulo tambem vale como fonte.
	if(admin_cron_parametros_pedem_desacoplamento(admin_cron_parametros_do_manifesto($tarefa))) return true;

	$lista = admin_cron_config('cron_tarefas_desacopladas', 'CRON_TAREFAS_DESACOPLADAS');
	$lista = array_filter(array_map('trim', explode(',', $lista)));

	return in_array((string)$tarefa['id'], $lista, true);
}

/**
 * Le a declaracao de desacoplamento de um conjunto de parametros.
 *
 * @param array $parametros
 * @return bool
 */
function admin_cron_parametros_pedem_desacoplamento($parametros){
	if(!is_array($parametros)) return false;
	if(isset($parametros['execucao']) && strtolower(trim((string)$parametros['execucao'])) === 'desacoplada') return true;
	return !empty($parametros['background']);
}

/**
 * Recupera os parametros DECLARADOS da tarefa no manifesto do modulo dono.
 *
 * Leitura pontual pelo campo `modulo` da propria linha — nao varre `modulos/`, ao contrario da
 * sincronizacao, porque aqui interessa uma tarefa so.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return array Parametros declarados; vazio quando o manifesto nao traz a tarefa.
 */
function admin_cron_parametros_do_manifesto($tarefa){
	global $_GESTOR;

	$modulo = isset($tarefa['modulo']) ? trim((string)$tarefa['modulo']) : '';
	if($modulo === '' || !preg_match('/^[a-z0-9][a-z0-9-]*$/i', $modulo)) return Array();
	if(!isset($_GESTOR['modulos-path'])) return Array();

	$arquivo = $_GESTOR['modulos-path'].$modulo.DIRECTORY_SEPARATOR.$modulo.'.json';
	if(!file_exists($arquivo)) return Array();

	$dados = json_decode((string)file_get_contents($arquivo), true);
	if(!is_array($dados) || !isset($dados['cron']) || !is_array($dados['cron'])) return Array();

	$id = (string)$tarefa['id'];

	foreach($dados['cron'] as $declarada){
		if(!is_array($declarada)) continue;
		if(trim((string)(isset($declarada['id']) ? $declarada['id'] : '')) !== $id) continue;

		return (isset($declarada['parametros']) && is_array($declarada['parametros']))
			? $declarada['parametros']
			: Array();
	}

	return Array();
}

/**
 * Resolve o binario do PHP CLI para o disparo desacoplado.
 *
 * `PHP_BINARY` NAO serve: sob PHP-FPM ele aponta para o binario do pool (`php-fpm`), e o cron
 * seria executado sob o SAPI errado.
 *
 * @return string
 */
function admin_cron_php_binario(){
	$configurado = admin_cron_config('cron_php_binary', 'CRON_PHP_BINARY');
	if($configurado !== '') return $configurado;

	if(defined('PHP_BINDIR')){
		$candidato = PHP_BINDIR . '/php';
		if(@is_executable($candidato)) return $candidato;
	}

	if(PHP_SAPI === 'cli' && defined('PHP_BINARY') && PHP_BINARY !== '') return PHP_BINARY;

	return 'php';
}

/**
 * Dispara a tarefa como processo CLI independente do worker web.
 *
 * `setsid` e a peca central: sem uma sessao nova o filho continua no grupo de processos do pool
 * PHP-FPM e o `systemctl restart php8.5-fpm` o mata junto com o pai — exatamente o que este
 * caminho existe para evitar. O `&` faz o `sh -c` retornar de imediato, entao `proc_close()` nao
 * bloqueia a resposta ao navegador.
 *
 * @param array $tarefa Linha de cron_tarefas.
 * @return array{ok: bool, erro: string, comando: string}
 */
function admin_cron_disparar_em_background($tarefa){
	global $_GESTOR;

	if(DIRECTORY_SEPARATOR !== '/'){
		return Array('ok' => false, 'erro' => 'Disparo desacoplado disponivel apenas em ambientes POSIX.', 'comando' => '', 'estrategia' => '', 'isolado' => false);
	}

	if(!function_exists('proc_open')){
		return Array('ok' => false, 'erro' => 'proc_open indisponivel neste pool PHP.', 'comando' => '', 'estrategia' => '', 'isolado' => false);
	}

	$raiz = isset($_GESTOR['ROOT_PATH']) ? (string)$_GESTOR['ROOT_PATH'] : '';
	$script = $raiz . 'cron.php';

	if($raiz === '' || !file_exists($script)){
		return Array('ok' => false, 'erro' => 'Entrada cron.php nao encontrada em "'.$raiz.'".', 'comando' => '', 'estrategia' => '', 'isolado' => false);
	}

	$argumentos = Array(admin_cron_php_binario(), $script, 'tarefa='.$tarefa['id']);

	// `cron.php` detecta o host sozinho quando ha UMA instalacao; com varias ele aborta pedindo
	// server=. Informar o dominio da requisicao corrente resolve os dois casos.
	$servidor = isset($_SERVER['SERVER_NAME']) ? trim((string)$_SERVER['SERVER_NAME']) : '';
	if($servidor !== '') $argumentos[] = 'server='.$servidor;

	if(!empty($_GESTOR['plataforma-id'])) $argumentos[] = 'plataforma='.$_GESTOR['plataforma-id'];

	$comando = implode(' ', array_map('escapeshellarg', $argumentos));

	$estrategia = admin_cron_disparo_estrategia();
	$linha = admin_cron_disparo_montar_linha($estrategia, $comando);

	if($linha === ''){
		return Array('ok' => false, 'erro' => 'Nenhuma estrategia de disparo disponivel.', 'comando' => '', 'estrategia' => '', 'isolado' => false);
	}

	$descritores = Array(
		0 => Array('file', '/dev/null', 'r'),
		1 => Array('file', '/dev/null', 'w'),
		2 => Array('file', '/dev/null', 'w'),
	);

	$pipes = Array();
	$processo = @proc_open($linha, $descritores, $pipes);

	if(!is_resource($processo)){
		return Array('ok' => false, 'erro' => 'Nao foi possivel iniciar o processo CLI.', 'comando' => $linha, 'estrategia' => $estrategia, 'isolado' => false);
	}

	proc_close($processo);

	return Array(
		'ok' => true,
		'erro' => '',
		'comando' => $linha,
		'estrategia' => $estrategia,
		'isolado' => admin_cron_disparo_isola_cgroup($estrategia),
	);
}

/**
 * Estrategias de disparo, da mais isolada para a menos.
 *
 * @return string[]
 */
function admin_cron_disparo_estrategias(){
	return Array('systemd-run', 'ssh', 'setsid');
}

/**
 * Diz se a estrategia coloca o processo FORA do cgroup do pool PHP-FPM.
 *
 * `setsid` NAO isola: ele cria uma sessao nova, e sessao nao e cgroup. O processo continua em
 * `php8.5-fpm.service`, e `systemctl restart php8.5-fpm` mata todo o cgroup — foi o que
 * interrompeu o instalador no meio de `v-add-web-domain` e deixou a conta congelada em
 * `provisioning` (REQ-040). Ele fica na lista como ultimo recurso, e o chamador precisa saber
 * que escolheu um caminho sem protecao.
 *
 * @param string $estrategia
 * @return bool
 */
function admin_cron_disparo_isola_cgroup($estrategia){
	return in_array($estrategia, Array('systemd-run', 'ssh'), true);
}

/**
 * Resolve qual estrategia usar, SONDANDO o ambiente em vez de supor.
 *
 * A diferenca importa: `systemd-run --scope` depende de autorizacao do systemd, e o pool roda
 * como usuario sem privilegio — anunciar isolamento sem verificar produziria exatamente a falha
 * silenciosa que este lote existe para eliminar. Cada candidata e exercitada com um comando
 * trivial antes de ser adotada.
 *
 * O resultado e memorizado por requisicao: a sondagem custa alguns milissegundos e nao muda no
 * meio de um disparo.
 *
 * @return string Nome da estrategia escolhida; string vazia quando nenhuma serve.
 */
function admin_cron_disparo_estrategia(){
	global $_GESTOR;

	// A escolha forcada e consultada ANTES do cache: memorizar uma sondagem faz sentido (o
	// ambiente nao muda no meio do disparo), mas mascarar a configuracao do operador com um
	// resultado anterior nao.
	$forcada = admin_cron_config('cron_dispatch_strategy', 'CRON_DISPATCH_STRATEGY');

	if($forcada !== '' && in_array($forcada, admin_cron_disparo_estrategias(), true)){
		return $forcada;
	}

	static $escolhida = null;
	if($escolhida !== null) return $escolhida;

	foreach(admin_cron_disparo_estrategias() as $candidata){
		if(admin_cron_disparo_sondar($candidata)){
			return $escolhida = $candidata;
		}
	}

	return $escolhida = '';
}

/**
 * Exercita uma estrategia com um comando trivial e devolve se ela funciona neste host.
 *
 * @param string $estrategia
 * @return bool
 */
function admin_cron_disparo_sondar($estrategia){
	switch($estrategia){
		case 'systemd-run':
			$prefixo = admin_cron_disparo_prefixo_systemd();
			if($prefixo === '') return false;
			// Sonda com o MESMO prefixo do disparo real: um `--slice` recusado pelo systemd
			// precisa reprovar aqui, nao adiante e em silencio.
			return admin_cron_exec_sincrono($prefixo . ' /bin/true')['codigo'] === 0;

		case 'ssh':
			$prefixo = admin_cron_disparo_prefixo_ssh();
			if($prefixo === '') return false;
			return admin_cron_exec_sincrono($prefixo . ' true')['codigo'] === 0;

		case 'setsid':
			return admin_cron_exec_sincrono('command -v setsid')['codigo'] === 0;
	}

	return false;
}

/**
 * Prefixo do `systemd-run`, ou string vazia quando o binario nao existe.
 *
 * @return string
 */
function admin_cron_disparo_prefixo_systemd(){
	if(admin_cron_exec_sincrono('command -v systemd-run')['codigo'] !== 0) return '';

	return admin_cron_disparo_systemd_montar();
}

/**
 * Monta o prefixo do `systemd-run` a partir da configuracao, sem consultar o ambiente.
 *
 * Construcao e disponibilidade sao perguntas diferentes: manter a montagem pura permite
 * verifica-la em teste num host que nao tem systemd, e deixa a sondagem com uma
 * responsabilidade so.
 *
 * @return string
 */
function admin_cron_disparo_systemd_montar(){
	$slice = admin_cron_config('cron_dispatch_slice', 'CRON_DISPATCH_SLICE');
	if($slice === '') $slice = 'system-cron.slice';

	$prefixo = 'systemd-run --scope --quiet';

	// `--slice` e recusado em algumas politicas; quando declarado, entra na sonda tambem.
	if($slice !== '' && preg_match('/\A[A-Za-z0-9@_.\-]+\.slice\z/', $slice)){
		$prefixo .= ' --slice=' . escapeshellarg($slice);
	}

	return $prefixo;
}

/**
 * Prefixo do `ssh` para o proprio host, ou string vazia quando nao configurado.
 *
 * Um `ssh` para `127.0.0.1` parece redundante e nao e: o processo remoto nasce sob o cgroup de
 * sessao do `sshd`, nao sob o do pool PHP-FPM. E a rota de escape que nao pede privilegio de
 * systemd — so uma chave publica ja instalada.
 *
 * @return string
 */
function admin_cron_disparo_prefixo_ssh(){
	if(admin_cron_disparo_ssh_montar() === '') return '';
	if(admin_cron_exec_sincrono('command -v ssh')['codigo'] !== 0) return '';

	return admin_cron_disparo_ssh_montar();
}

/**
 * Monta o prefixo do `ssh` a partir da configuracao, sem consultar o ambiente.
 *
 * @return string Prefixo pronto, ou string vazia quando o host nao esta configurado ou e invalido.
 */
function admin_cron_disparo_ssh_montar(){
	$host = admin_cron_config('cron_dispatch_ssh_host', 'CRON_DISPATCH_SSH_HOST');

	if($host === '' || !preg_match('/\A[A-Za-z0-9._\-]+\z/', $host)) return '';

	$usuario = admin_cron_config('cron_dispatch_ssh_user', 'CRON_DISPATCH_SSH_USER');
	$porta = (int)admin_cron_config('cron_dispatch_ssh_port', 'CRON_DISPATCH_SSH_PORT');
	$identidade = admin_cron_config('cron_dispatch_ssh_identity', 'CRON_DISPATCH_SSH_IDENTITY');

	// `BatchMode` e o que impede a sonda de travar num prompt de senha dentro do worker web.
	$prefixo = 'ssh -o BatchMode=yes -o ConnectTimeout=3 -o StrictHostKeyChecking=accept-new';

	if($porta > 0 && $porta <= 65535) $prefixo .= ' -p ' . $porta;
	if($identidade !== '') $prefixo .= ' -i ' . escapeshellarg($identidade);

	$alvo = ($usuario !== '' ? $usuario . '@' : '') . $host;

	return $prefixo . ' ' . escapeshellarg($alvo);
}

/**
 * Monta a linha de comando final para a estrategia escolhida.
 *
 * @param string $estrategia
 * @param string $comando    Comando ja escapado (binario + script + argumentos).
 * @return string Linha para o `sh -c`; string vazia quando a estrategia nao se aplica.
 */
function admin_cron_disparo_montar_linha($estrategia, $comando){
	switch($estrategia){
		case 'systemd-run':
			// Construtores PUROS aqui: a disponibilidade do binario ja foi decidida por
			// `admin_cron_disparo_estrategia()`, que sondou antes de escolher.
			$prefixo = admin_cron_disparo_systemd_montar();
			if($prefixo === '') return '';
			// `--scope` executa em primeiro plano; o `&` devolve o worker web de imediato, e o
			// processo segue vivo no cgroup do systemd.
			return 'setsid ' . $prefixo . ' ' . $comando . ' < /dev/null > /dev/null 2>&1 &';

		case 'ssh':
			$prefixo = admin_cron_disparo_ssh_montar();
			if($prefixo === '') return '';
			// `-f` devolve o `ssh` local; o `nohup ... &` do lado remoto solta o processo da
			// sessao do `sshd` para que o fechamento do canal nao o derrube.
			$remoto = 'nohup ' . $comando . ' < /dev/null > /dev/null 2>&1 &';
			return $prefixo . ' -f ' . escapeshellarg($remoto);

		case 'setsid':
			return 'setsid ' . $comando . ' < /dev/null > /dev/null 2>&1 &';
	}

	return '';
}

/**
 * Executa um comando curto e devolve codigo de saida e saida combinada.
 *
 * Usado apenas pelas sondagens: nada aqui pode bloquear a requisicao, por isso as candidatas
 * carregam seus proprios limites de tempo (`ConnectTimeout` no ssh) e os comandos sondados sao
 * triviais (`/bin/true`, `command -v`).
 *
 * @param string $linha
 * @return array{codigo: int, saida: string}
 */
function admin_cron_exec_sincrono($linha){
	if(!function_exists('proc_open')) return Array('codigo' => 127, 'saida' => '');

	$descritores = Array(
		0 => Array('file', '/dev/null', 'r'),
		1 => Array('pipe', 'w'),
		2 => Array('pipe', 'w'),
	);

	$pipes = Array();
	$processo = @proc_open($linha, $descritores, $pipes);

	if(!is_resource($processo)) return Array('codigo' => 127, 'saida' => '');

	$saida = (string)stream_get_contents($pipes[1]) . (string)stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	return Array('codigo' => (int)proc_close($processo), 'saida' => trim($saida));
}
