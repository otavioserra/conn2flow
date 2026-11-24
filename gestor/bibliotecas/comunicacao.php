<?php
/**
 * Biblioteca de comunicação do sistema.
 *
 * Gerencia envio de emails via SMTP (PHPMailer) e impressão de páginas.
 * Suporta configurações personalizadas por host, templates HTML, anexos,
 * imagens embarcadas e múltiplos destinatários.
 *
 * REQ-304 / BATCH-313: a senha SMTP nunca vai para log; `MAIL_REDIRECT_TO` desvia todos os
 * destinatários para uma caixa de teste fora de produção; `AltBody` em texto puro derivado do
 * HTML; timeout explícito; fila/histórico com retry e contrato multi-canal em
 * `comunicacao-fila.php` (`comunicacao_enviar()` / hook `comunicacao.drivers`).
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.2.0
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-comunicacao']							=	Array(
	'versao' => '1.2.0',
);

// ===== Inclusão do PHPMailer

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once $_GESTOR['bibliotecas-path'].'PHPMailer/src/Exception.php';
require_once $_GESTOR['bibliotecas-path'].'PHPMailer/src/PHPMailer.php';
require_once $_GESTOR['bibliotecas-path'].'PHPMailer/src/SMTP.php';

// ===== Fila, histórico e drivers multi-canal (REQ-304)

require_once __DIR__.'/comunicacao-fila.php';

// Marcador do assunto redirecionado. É um marcador de protocolo (filtros de caixa de entrada e
// testes dependem do formato exato), não um texto de interface.
define('COMUNICACAO_REDIRECT_ROTULO', 'TESTE');

// ===== Funções auxiliares

/**
 * Endereço de redirecionamento de teste em vigor, ou '' quando inativo.
 *
 * Fonte única: `MAIL_REDIRECT_TO` do `.env` (`$_CONFIG['email']['redirect_to']`), o mesmo valor
 * editado em admin-environment e admin-comunicacao. Em produção (`APP_ENV=production`, ou sem
 * APP_ENV e sem DEVELOPMENT_ENV) fica TRAVADO, salvo `MAIL_REDIRECT_ALLOW_PRODUCTION=true`;
 * travado com valor preenchido, registra um aviso no log.
 *
 * @param bool $avisar Registrar o aviso de produção (uma vez por requisição).
 * @return string
 */
function comunicacao_redirecionamento_destino($avisar = true){
	$status = comunicacao_redirecionamento_status();

	if($status['bloqueado'] && $avisar){
		comunicacao_redirecionamento_avisar_producao($status['configurado']);
	}

	return $status['ativo'] ? $status['configurado'] : '';
}

/**
 * Estado do redirecionamento para o painel e para o envio.
 *
 * @return array{configurado:string,valido:bool,ativo:bool,bloqueado:bool,ambiente:string}
 */
function comunicacao_redirecionamento_status(){
	$config = comunicacao_config();
	$destino = $config['redirect_to'];
	$valido = $destino !== '' && filter_var($destino, FILTER_VALIDATE_EMAIL) !== false;
	$producao = in_array($config['ambiente'], Array('production', 'prod', 'producao'), true);
	$bloqueado = $valido && $producao && !$config['redirect_permitir_producao'];

	return Array(
		'configurado' => $destino,
		'valido' => $valido,
		'ativo' => $valido && !$bloqueado,
		'bloqueado' => $bloqueado,
		'ambiente' => $config['ambiente'],
	);
}

/** Aviso único por requisição: MAIL_REDIRECT_TO preenchido em produção foi ignorado. */
function comunicacao_redirecionamento_avisar_producao($destino){
	global $_GESTOR;

	if(!empty($_GESTOR['comunicacao-redirect-aviso'])) return;
	$_GESTOR['comunicacao-redirect-aviso'] = true;

	if(!function_exists('log_disco')) require_once __DIR__.'/log.php';
	log_disco('MAIL_REDIRECT_TO preenchido em producao foi IGNORADO (destino: '.$destino.'). Defina APP_ENV fora de producao ou MAIL_REDIRECT_ALLOW_PRODUCTION=true.', 'email');
}

/**
 * Troca TO/CC/BCC por um único destinatário de teste.
 *
 * @param array $recipients Destinatários no formato de comunicacao_email().
 * @param string $subject Assunto original.
 * @param string $destino Caixa de teste.
 * @return array{recipients:array,subject:string,originais:array,destinatario_original:string}
 */
function comunicacao_redirecionar($recipients, $subject, $destino){
	$todos = Array();
	$principais = Array();

	foreach((array)$recipients as $r){
		if(empty($r['email'])) continue;
		$email = trim((string)$r['email']);
		if(!in_array($email, $todos, true)) $todos[] = $email;
		$tipo = strtolower((string)($r['tipo'] ?? 'normal'));
		if($tipo === 'normal' && !in_array($email, $principais, true)) $principais[] = $email;
	}

	$rotulo = $principais ? $principais : $todos;
	$prefixo = '['.COMUNICACAO_REDIRECT_ROTULO.' → ';
	$subject = (string)$subject;

	// Reenvio de uma mensagem já marcada não empilha prefixos.
	if(strpos($subject, $prefixo) !== 0){
		$subject = $prefixo.implode(', ', $rotulo).'] '.$subject;
	}

	return Array(
		'recipients' => Array(Array('email' => $destino)),
		'subject' => $subject,
		'originais' => $todos,
		'destinatario_original' => implode(', ', $todos),
	);
}

/**
 * Texto puro legível a partir do HTML final do e-mail (AltBody).
 *
 * Remove head/style/script, converte quebras de bloco em linhas, mantém o endereço dos links e
 * limpa espaços repetidos.
 */
function comunicacao_html_para_texto($html){
	$texto = (string)$html;

	$texto = preg_replace('#<(head|style|script|title)\b[^>]*>.*?</\1\s*>#is', '', $texto);
	$texto = preg_replace('#<!--.*?-->#s', '', $texto);

	$texto = preg_replace_callback('#<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a\s*>#is', function($m){
		$rotulo = trim(strip_tags($m[3]));
		$href = trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		if($href === '' || stripos($href, 'mailto:') === 0 || $href[0] === '#') return $rotulo;
		if($rotulo === '' || $rotulo === $href) return $href;
		return $rotulo.' ('.$href.')';
	}, $texto);

	$texto = preg_replace('#<br\s*/?>#i', "\n", $texto);
	$texto = preg_replace('#<li\b[^>]*>#i', "\n- ", $texto);
	$texto = preg_replace('#</(p|div|h[1-6]|li|tr|table|ul|ol|blockquote|section|article|header|footer)\s*>#i', "\n", $texto);
	$texto = preg_replace('#<(p|div|h[1-6]|tr|table|ul|ol|blockquote)\b[^>]*>#i', "\n", $texto);

	$texto = strip_tags($texto);
	$texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$texto = str_replace("\xC2\xA0", ' ', $texto);

	$linhas = array_map(function($l){ return trim(preg_replace('/[ \t]+/', ' ', $l)); }, explode("\n", str_replace("\r", '', $texto)));
	$texto = implode("\n", $linhas);
	$texto = preg_replace("/\n{3,}/", "\n\n", $texto);

	return trim($texto);
}

/**
 * HTML do e-mail pronto para preview em iframe (admin-comunicacao).
 *
 * O e-mail real referencia as imagens embutidas por `src="cid:..."`, que so o cliente de e-mail
 * resolve. No iframe de preview o `cid:` quebra a imagem; aqui cada `cid:` com imagem conhecida
 * em `$embeddedImgs` vira `data:` URI (so PNG/JPEG/GIF/WebP legiveis e ate 2 MB; SVG nao entra).
 * Um `cid:` sem imagem correspondente fica intacto. O HTML enviado nao e alterado.
 *
 * @param string $html Corpo do e-mail como enviado.
 * @param array $embeddedImgs Lista de imagens (`cid`, `caminho`, `imagemTmpCaminho`).
 * @return string
 */
function comunicacao_email_preview_html($html, $embeddedImgs = Array()){
	$html = (string)$html;
	if(stripos($html, 'cid:') === false) return $html;

	$permitidos = Array('image/png', 'image/jpeg', 'image/gif', 'image/webp');
	$mapa = Array();
	foreach((array)$embeddedImgs as $img){
		if(!is_array($img) || empty($img['cid'])) continue;
		$cid = strtolower(trim(preg_replace('/^cid:/i', '', (string)$img['cid'])));
		if($cid === '') continue;
		foreach(Array('imagemTmpCaminho', 'caminho') as $campo){
			$arquivo = (string)($img[$campo] ?? '');
			if($arquivo === '' || !is_file($arquivo) || !is_readable($arquivo) || filesize($arquivo) > 2097152) continue;
			$dados = file_get_contents($arquivo);
			if($dados === false) continue;
			$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($dados);
			if(!in_array($mime, $permitidos, true)) continue;
			$mapa[$cid] = 'data:' . $mime . ';base64,' . base64_encode($dados);
			break;
		}
	}
	if(!$mapa) return $html;

	return preg_replace_callback(
		'/(\bsrc\s*=\s*)(["\']?)cid:([^"\'\s>]+)\2/i',
		static function(array $m) use ($mapa){
			$cid = strtolower(rawurldecode($m[3]));
			if(!isset($mapa[$cid])) $cid = strtolower($m[3]);
			if(!isset($mapa[$cid])) return $m[0];
			$aspas = $m[2] !== '' ? $m[2] : '"';
			return $m[1] . $aspas . $mapa[$cid] . $aspas;
		},
		$html
	) ?? $html;
}

/** Remove a senha SMTP de qualquer texto (mensagens de erro do PHPMailer, por exemplo). */
function comunicacao_texto_sem_segredo($texto, $server){
	$texto = (string)$texto;
	$senha = isset($server['pass']) ? (string)$server['pass'] : '';
	if($senha !== '' && strlen($senha) >= 3) $texto = str_replace($senha, '***', $texto);
	return $texto;
}

/** Instância do PHPMailer. Os testes injetam uma fábrica em `$_GESTOR['comunicacao-mailer-factory']`. */
function comunicacao_email_mailer(){
	global $_GESTOR;
	if(isset($_GESTOR['comunicacao-mailer-factory']) && is_callable($_GESTOR['comunicacao-mailer-factory'])){
		return call_user_func($_GESTOR['comunicacao-mailer-factory']);
	}
	return new PHPMailer(true);
}

// ===== Funções principais

/**
 * Prepara página para impressão armazenando dados na sessão.
 *
 * Armazena o conteúdo HTML e título da página que será impressa,
 * salvando em variável de sessão para posterior recuperação.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CRON Dados de execução cron.
 *
 * @param array|false $params Parâmetros da função.
 * @param string $params['pagina'] Conteúdo HTML da página a ser impressa (obrigatório).
 * @param string $params['titulo'] Título da página de impressão (opcional).
 *
 * @return void
 */
function comunicacao_impressao($params = false){
	global $_GESTOR;
	global $_CRON;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;

	// Valida parâmetro obrigatório e armazena dados de impressão
	if(isset($pagina)){
		$impressao = Array(
			'pagina' => $pagina,
		);

		if(isset($titulo)){
			$impressao['titulo'] = $titulo;
		}

		// Salva dados de impressão na sessão
		gestor_sessao_variavel('impressao',$impressao);
	}
}

/** Fallback corporativo antes de resolver imagens, preservando valores já personalizados. */
function comunicacao_email_marca($html){
	global $_GESTOR, $_CONFIG;
	$nome = trim((string)($_GESTOR['modulo-config']['nome-site'] ?? ''));
	if ($nome === '') $nome = trim((string)($_CONFIG['email']['sender']['fromName'] ?? ''));
	if ($nome === '') $nome = 'Conn2Flow';
	return str_replace(
		['#brand_name#', '#brand_logo#', '#v1brand_logo#', '#logo_url#'],
		[htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'), '@[[pagina#url-raiz]]@images/Logomarca200.png', '@[[pagina#url-raiz]]@images/Logomarca200.png', '@[[pagina#url-raiz]]@images/Logomarca200.png'],
		(string)$html
	);
}

/**
 * Servidor SMTP e remetente padrão: `.env` e, quando pedido, a personalização do host.
 *
 * Separado do envio para que o worker da fila resolva a credencial na hora de enviar: a senha
 * nunca é gravada junto da mensagem.
 *
 * @param int|null $id_hosts
 * @param bool $hostPersonalizacao
 * @return array{server:array,sender:array,htmlAssinatura:?string}
 */
function comunicacao_email_servidor($id_hosts = null, $hostPersonalizacao = false){
	global $_CONFIG;

	$server = Array(
		'debug' => false,
		'host' => 'localhost',
		'user' => 'user@localhost',
		'pass' => 'password',
		'secure' => PHPMailer::ENCRYPTION_STARTTLS,
		'port' => 587,
		'timeout' => comunicacao_config()['timeout'],
		'altBody' => 'Esta mensagem usa HTML, para ver a mesma, por favor use um programa de emails compatível com HTML!',
	);

	$sender = Array(
		'from' => 'user@localhost',
	);

	$htmlAssinatura = null;

	// ===== Variáveis de configuração padrões

	if(isset($_CONFIG['email'])){
		if(isset($_CONFIG['email']['server'])){
			if(isset($_CONFIG['email']['server']['debug'])){ $server['debug'] = $_CONFIG['email']['server']['debug']; }
			if(isset($_CONFIG['email']['server']['host'])){ $server['host'] = $_CONFIG['email']['server']['host']; }
			if(isset($_CONFIG['email']['server']['user'])){ $server['user'] = $_CONFIG['email']['server']['user']; }
			if(isset($_CONFIG['email']['server']['pass'])){ $server['pass'] = $_CONFIG['email']['server']['pass']; }
			if(isset($_CONFIG['email']['server']['port'])){ $server['port'] = $_CONFIG['email']['server']['port']; }

			if(isset($_CONFIG['email']['server']['secure'])){ $server['secure'] = PHPMailer::ENCRYPTION_SMTPS; }
		}

		if(isset($_CONFIG['email']['sender'])){
			if(isset($_CONFIG['email']['sender']['from'])){ $sender['from'] = $_CONFIG['email']['sender']['from']; }
			if(isset($_CONFIG['email']['sender']['fromName'])){ $sender['fromName'] = $_CONFIG['email']['sender']['fromName']; }
			if(isset($_CONFIG['email']['sender']['replyTo'])){ $sender['replyTo'] = $_CONFIG['email']['sender']['replyTo']; }
			if(isset($_CONFIG['email']['sender']['replyToName'])){ $sender['replyToName'] = $_CONFIG['email']['sender']['replyToName']; }
		}
	}

	// ===== Variáveis definidas pelo usuário em comunicação configurações em um host específico.

	if(isset($id_hosts) && $id_hosts !== '' && $hostPersonalizacao){
		gestor_incluir_biblioteca('configuracao');

		$comunicacaoConfiguracoes = configuracao_hosts_variaveis(Array(
			'id_hosts' => $id_hosts,
			'modulo' => 'comunicacao-configuracoes',
			'grupos' => Array(
				'host-admin',
			),
		));

		if($comunicacaoConfiguracoes)
		foreach($comunicacaoConfiguracoes as $id => $config){
			switch($id){
				case 'email-personalizado-ativo':
					$emailPersonalizadoAtivo = true;
				break;
			}
		}

		if(isset($emailPersonalizadoAtivo)){
			foreach($comunicacaoConfiguracoes as $id => $config){
				if(existe($config)){
					switch($id){
						case 'email-assinatura': $htmlAssinatura = $config; break;
						case 'servidor-host': $server['host'] = $config; break;
						case 'servidor-usuario': $server['user'] = $config; break;
						case 'servidor-senha': $server['pass'] = $config; break;
						case 'servidor-porta': $server['port'] = $config; break;
						case 'servidor-certificado-seguranca': $server['secure'] = PHPMailer::ENCRYPTION_SMTPS; break;
						case 'remetente-de': $sender['from'] = $config; break;
						case 'remetente-de-nome': $sender['fromName'] = $config; break;
						case 'remetente-responder-para': $sender['replyTo'] = $config; break;
						case 'remetente-responder-para-nome': $sender['replyToName'] = $config; break;
					}
				}
			}
		}
	}

	return Array('server' => $server, 'sender' => $sender, 'htmlAssinatura' => $htmlAssinatura);
}

/**
 * Envia emails via SMTP usando PHPMailer.
 *
 * Função completa de envio de emails com suporte a:
 * - Configurações SMTP personalizadas por host
 * - Templates HTML com variáveis substituíveis
 * - Múltiplos destinatários (TO, CC, BCC)
 * - Anexos de arquivos
 * - Imagens embarcadas (inline)
 * - Assinatura HTML automática
 * - Modo de teste com configurações em runtime
 * - REQ-304: modo híbrido com fila, rate limit e retry; redirecionamento de teste
 *
 * @global array $_GESTOR Sistema global com configurações.
 * @global array $_CONFIG Configurações do sistema.
 * @global array $_CRON Dados de execução cron.
 *
 * @param array|false $params Parâmetros da função.
 *
 * @param bool $params['hostPersonalizacao'] Permite configuração via módulo Comunicação (opcional).
 * @param int $params['id_hosts'] ID do host para comunicação (opcional).
 *
 * @param array $params['servidor'] Configurações do servidor SMTP (opcional; envio imediato, sem fila).
 * @param bool $params['servidor']['debug'] Ativar debug PHPMailer (opcional).
 * @param string $params['servidor']['hospedeiro'] Host SMTP (opcional).
 * @param string $params['servidor']['usuario'] Usuário SMTP (opcional).
 * @param string $params['servidor']['senha'] Senha SMTP (opcional).
 * @param bool $params['servidor']['seguro'] Usar SSL/TLS (opcional).
 * @param int $params['servidor']['porta'] Porta SMTP (opcional).
 *
 * @param array $params['remetente'] Dados do remetente (opcional).
 * @param string $params['remetente']['de'] Email de origem (opcional).
 * @param string $params['remetente']['deNome'] Nome do remetente (opcional).
 * @param string $params['remetente']['responderPara'] Email para respostas (opcional).
 * @param string $params['remetente']['responderParaNome'] Nome para respostas (opcional).
 *
 * @param array $params['destinatarios'] Array de destinatários (opcional).
 * @param string $params['destinatarios'][]['email'] Email do destinatário (opcional).
 * @param string $params['destinatarios'][]['nome'] Nome do destinatário (opcional).
 * @param string $params['destinatarios'][]['tipo'] Tipo: 'cc' ou 'bcc' (opcional, padrão TO).
 *
 * @param array $params['mensagem'] Conteúdo da mensagem (opcional).
 * @param string $params['mensagem']['assunto'] Assunto do email (opcional).
 * @param string $params['mensagem']['html'] Corpo HTML da mensagem (opcional).
 * @param string $params['mensagem']['altBody'] Texto puro alternativo (opcional; derivado do HTML se omitido).
 * @param bool $params['mensagem']['htmlAssinaturaAutomatica'] Incluir assinatura automática (opcional).
 * @param string $params['mensagem']['htmlLayoutID'] ID do layout HTML (opcional).
 * @param string $params['mensagem']['htmlTitulo'] Título HTML (opcional, usa assunto se omitido).
 * @param array $params['mensagem']['htmlVariaveis'] Variáveis para substituição no HTML (opcional).
 * @param array $params['mensagem']['htmlCompleto'] HTML completo com a página inteira (opcional).
 * @param array $params['mensagem']['imagens'] Imagens embarcadas (opcional).
 * @param array $params['mensagem']['anexos'] Arquivos anexos (opcional).
 *
 * @param bool $params['fila'] Enfileirar sem tentar agora (opcional).
 * @param string $params['prioridade'] 'alta', 'normal' ou 'baixa' (opcional).
 * @param string $params['chave_idempotencia'] Evita envio duplicado com a mesma chave (opcional).
 *
 * @param bool $params['EMAIL_TESTS'] Modo teste com configs em runtime (opcional; envio imediato, sem fila).
 * @param bool $params['EMAIL_DEBUG'] Debug em modo teste (opcional).
 * @param string $params['EMAIL_HOST'] Host SMTP em modo teste (opcional).
 * @param string $params['EMAIL_USER'] Usuário SMTP em modo teste (opcional).
 * @param string $params['EMAIL_PASS'] Senha SMTP em modo teste (opcional).
 * @param bool $params['EMAIL_SECURE'] SSL em modo teste (opcional).
 * @param int $params['EMAIL_PORT'] Porta em modo teste (opcional).
 * @param string $params['EMAIL_FROM'] Email origem em modo teste (opcional).
 * @param string $params['EMAIL_FROM_NAME'] Nome origem em modo teste (opcional).
 * @param string $params['EMAIL_REPLY_TO'] Email resposta em modo teste (opcional).
 * @param string $params['EMAIL_REPLY_TO_NAME'] Nome resposta em modo teste (opcional).
 *
 * @return bool True quando enviado ou aceito na fila para nova tentativa; false se o e-mail
 *              está desativado ou o envio falhou de forma definitiva.
 */
function comunicacao_email($params = false){
	$preparado = comunicacao_email_preparar($params);
	if(!$preparado) return false;

	$resultado = comunicacao_enviar('email', $preparado['payload'], $preparado['opcoes']);

	return !empty($resultado['aceito']);
}

/**
 * Monta o e-mail final (layout, assinatura, variáveis, imagens) e as opções de despacho.
 *
 * @param array|false $params Os mesmos de comunicacao_email().
 * @return array{payload:array,opcoes:array}|false false quando o e-mail está desativado.
 */
function comunicacao_email_preparar($params = false){
	global $_GESTOR;
	global $_CONFIG;
	global $_CRON;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;

	if(!(isset($_CONFIG['email']) || isset($EMAIL_TESTS))) return false;
	if(!(!empty($_CONFIG['email']['ativo']) || isset($EMAIL_TESTS))) return false;

	// ===== Definição se é ou não uma comunicação para um host.

	if(isset($_GESTOR['host-id']) && !isset($id_hosts)){
		$id_hosts = $_GESTOR['host-id'];
	}

	// ===== Servidor e remetente padrão (.env + personalização do host)

	$resolvido = comunicacao_email_servidor($id_hosts ?? null, isset($hostPersonalizacao));
	$server = $resolvido['server'];
	$sender = $resolvido['sender'];
	if($resolvido['htmlAssinatura'] !== null) $htmlAssinatura = $resolvido['htmlAssinatura'];

	// Credencial que não vem da configuração salva não pode ser persistida para retry.
	$credencialEfemera = isset($EMAIL_TESTS) || (isset($servidor) && is_array($servidor) && $servidor);

	$recipients = Array(
		Array(
			'email' => 'to@localhost',
		),
	);

	$message = Array(
		'subject' => 'Assunto Padrão',
		'body' => '<p>Corpo HTML Padrão</p>',
		'title' => 'Assunto Padrão',
	);

	// ===== Variáveis recebidas pela interface

	if(isset($servidor)){
		if(isset($servidor['debug'])){ $server['debug'] = $servidor['debug']; }
		if(isset($servidor['hospedeiro'])){ $server['host'] = $servidor['hospedeiro']; }
		if(isset($servidor['usuario'])){ $server['user'] = $servidor['usuario']; }
		if(isset($servidor['senha'])){ $server['pass'] = $servidor['senha']; }
		if(isset($servidor['porta'])){ $server['port'] = $servidor['porta']; }

		if(isset($servidor['seguro'])){ if($servidor['seguro']){$server['secure'] = PHPMailer::ENCRYPTION_SMTPS; }}
	}

	if(isset($remetente)){
		if(isset($remetente['de'])){ $sender['from'] = $remetente['de']; }
		if(isset($remetente['deNome'])){ $sender['fromName'] = $remetente['deNome']; }
		// Só sobrescreve o Reply-To padrão quando o chamador informa um valor: campo em branco
		// no cadastro do formulário significa "usar o do sistema", não "apagar o remetente".
		if(!empty($remetente['responderPara'])){ $sender['replyTo'] = $remetente['responderPara']; }
		if(!empty($remetente['responderParaNome'])){ $sender['replyToName'] = $remetente['responderParaNome']; }
	}

	if(isset($destinatarios)){
		$recipients = $destinatarios;
	}

	if(isset($mensagem)){
		if(isset($mensagem['assunto'])){ $message['subject'] = $mensagem['assunto']; }
		if(isset($mensagem['html'])){ $message['body'] = $mensagem['html']; }
		if(isset($mensagem['htmlLayoutID'])){ $message['htmlLayoutID'] = $mensagem['htmlLayoutID']; }
		if(isset($mensagem['htmlVariaveis'])){ $message['htmlVariaveis'] = $mensagem['htmlVariaveis']; }
		if(isset($mensagem['htmlTitulo'])){ $message['title'] = $mensagem['htmlTitulo']; } else if(isset($mensagem['assunto'])){ $message['title'] = $mensagem['assunto']; }
		if(isset($mensagem['imagens'])){ $message['embeddedImgs'] = $mensagem['imagens']; }
		if(isset($mensagem['anexos'])){ $message['attachments'] = $mensagem['anexos']; }
	}

	// ===== Variáveis definidas em tempo de execução, para testes.

	if(isset($EMAIL_TESTS)){
		if(isset($EMAIL_DEBUG)){$server['debug'] = $EMAIL_DEBUG;}
		if(isset($EMAIL_HOST)){$server['host'] = $EMAIL_HOST;}
		if(isset($EMAIL_USER)){$server['user'] = $EMAIL_USER;}
		if(isset($EMAIL_PASS)){$server['pass'] = $EMAIL_PASS;}
		if(isset($EMAIL_SECURE)){if($EMAIL_SECURE){$server['secure'] = PHPMailer::ENCRYPTION_SMTPS;}}
		if(isset($EMAIL_PORT)){$server['port'] = $EMAIL_PORT;}
		if(isset($EMAIL_FROM)){$sender['from'] = $EMAIL_FROM;}
		if(isset($EMAIL_FROM_NAME)){$sender['fromName'] = $EMAIL_FROM_NAME;}
		if(isset($EMAIL_REPLY_TO)){$sender['replyTo'] = $EMAIL_REPLY_TO;}
		if(isset($EMAIL_REPLY_TO_NAME)){$sender['replyToName'] = $EMAIL_REPLY_TO_NAME;}
	}

	// ===== Inserir o layout HTML caso exista.

	if(!isset($mensagem['htmlCompleto']) || !$mensagem['htmlCompleto']){
		$mailHTML = gestor_layout(Array(
			'id' => 'layout-emails',
			'return_css' => true,
		));

		$layoutHTML = $mailHTML['html'];

		if(existe($layoutHTML)){
			$mailCSS = '';
			$mailJS = '';
			$mailCorpo = '';

			$layoutCSS = $mailHTML['css'];

			if(existe($layoutCSS)){
				$layoutCSS = preg_replace("/(^|\n)/m", "    ", $layoutCSS);

				$mailCSS .= '<style>'."\n";
				$mailCSS .= "    ".$layoutCSS."\n";
				$mailCSS .= "    ".'</style>';
			}

			$layoutCSSCompiled = $mailHTML['css_compiled'];

			if(existe($layoutCSSCompiled)){
				$layoutCSSCompiled = preg_replace("/(^|\n)/m", "\n        ", $layoutCSSCompiled);

				$mailCSS .= '<style>'."\n";
				$mailCSS .= $layoutCSSCompiled."\n";
				$mailCSS .= '</style>'."\n";
			}

			$mailCorpo = $message['body'];

			// ===== Caso passe o layout ID para essa instância, aplicar o layout ao corpo do HTML do email.

			if(isset($message['htmlLayoutID'])){
				$mailBodyHTML = gestor_componente(Array(
					'id' => $message['htmlLayoutID'],
					'return_css' => true,
				));

				$layoutBodyHTML = $mailBodyHTML['html'];

				if(existe($layoutBodyHTML)){
					$layoutBodyCSS = $mailBodyHTML['css'];

					if(existe($layoutBodyCSS)){
						$layoutBodyCSS = preg_replace("/(^|\n)/m", "    ", $layoutBodyCSS);

						$mailCSS .= "\n    ".'<style>'."\n";
						$mailCSS .= "    ".$layoutBodyCSS."\n";
						$mailCSS .= "    ".'</style>';
					}

					$mailCorpo = $layoutBodyHTML;
				}
			}

			// ===== Incluir automaticamente assinatura caso a opção esteja ativada.

			if(isset($mensagem['htmlAssinaturaAutomatica'])){
				$assinaturaPadrao = false;

				if(!isset($htmlAssinatura)){
					$assinaturaPadrao = true;
				} else {
					if(!existe($htmlAssinatura)){
						$assinaturaPadrao = true;
					}
				}

				if($assinaturaPadrao){
					if(isset($id_hosts)){
						$htmlAssinatura = gestor_componente(Array(
							'id' => 'hosts-layout-emails-assinatura',
						));
					} else {
						$htmlAssinatura = gestor_componente(Array(
							'id' => 'layout-emails-assinatura',
						));
					}
				}

				// ===== Incluir assinatura no final do corpo da mensagem.

				$mailCorpo .= $htmlAssinatura;
			}

			// ===== Modificar as variáveis padrão do layout principal.

			$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#titulo -->",'<title>'.$message['title'].'</title>');
			$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#css -->",$mailCSS);
			$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#js -->",$mailJS);
			$layoutHTML = modelo_var_troca_tudo($layoutHTML,"<!-- mail#corpo -->",$mailCorpo);

			// ===== Alterar as variáveis auxiliares caso enviadas

			if(isset($message['htmlVariaveis'])){
				foreach($message['htmlVariaveis'] as $htmlVar){
					if(isset($htmlVar['variavel']) && isset($htmlVar['valor'])){
						$layoutHTML = modelo_var_troca_tudo($layoutHTML,$htmlVar['variavel'],$htmlVar['valor']);
					}
				}
			}

			// ===== Substituir variáveis globais no HTML e incluir no corpo da mensagem.

			$layoutHTML = gestor_pagina_variaveis_globais(Array('html' => $layoutHTML));

			$message['body'] = $layoutHTML;
		}
	} else {
		// ===== Caso o HTML completo tenha sido enviado, usar diretamente.

		$message['body'] = $mensagem['htmlCompleto'];
	}

	gestor_incluir_biblioteca('formulario');
	$imagensMarca = formulario_email_processar_imagens($message['body']);
	$message['body'] = $imagensMarca['html'];
	$message['embeddedImgs'] = array_merge($message['embeddedImgs'] ?? [], $imagensMarca['imagens'] ?? []);

	return comunicacao_email_montar_envio(Array(
		'server' => $server,
		'sender' => $sender,
		'recipients' => $recipients,
		'message' => $message,
		'altBody' => isset($mensagem['altBody']) ? (string)$mensagem['altBody'] : '',
		'id_hosts' => $id_hosts ?? null,
		'hostPersonalizacao' => isset($hostPersonalizacao),
		'credencial_efemera' => $credencialEfemera,
		'fila' => !empty($fila),
		'prioridade' => isset($prioridade) ? (string)$prioridade : 'normal',
		'chave_idempotencia' => isset($chave_idempotencia) ? (string)$chave_idempotencia : '',
	));
}

/**
 * Separa o que é persistível (payload, sem credenciais) do contexto efêmero de envio.
 *
 * @param array $dados Saída intermediária de comunicacao_email_preparar().
 * @return array{payload:array,opcoes:array}
 */
function comunicacao_email_montar_envio($dados){
	$message = $dados['message'];
	$tmpFiles = Array();

	foreach((array)($message['attachments'] ?? Array()) as $anexo){
		if(!empty($anexo['tmpCaminho'])) $tmpFiles[] = (string)$anexo['tmpCaminho'];
	}
	foreach((array)($message['embeddedImgs'] ?? Array()) as $img){
		if(!empty($img['imagemTmpCaminho'])) $tmpFiles[] = (string)$img['imagemTmpCaminho'];
	}

	$payload = Array(
		'id_hosts' => $dados['id_hosts'],
		'hostPersonalizacao' => (bool)$dados['hostPersonalizacao'],
		'sender' => $dados['sender'],
		'recipients' => array_values((array)$dados['recipients']),
		'message' => Array(
			'subject' => (string)$message['subject'],
			'body' => (string)$message['body'],
			'embeddedImgs' => array_values((array)($message['embeddedImgs'] ?? Array())),
			'attachments' => array_values((array)($message['attachments'] ?? Array())),
		),
		'altBody' => (string)$dados['altBody'],
		'tmp_files' => $tmpFiles,
	);

	$opcoes = Array(
		'fila' => !empty($dados['fila']),
		'prioridade' => $dados['prioridade'],
		'chave_idempotencia' => $dados['chave_idempotencia'],
		'id_hosts' => $dados['id_hosts'],
	);

	if(!empty($dados['credencial_efemera'])){
		$opcoes['somente_imediato'] = true;
		$opcoes['contexto'] = Array('server' => $dados['server']);
	} else if(!empty($dados['server']['debug'])){
		$payload['debug'] = true;
	}

	return Array('payload' => $payload, 'opcoes' => $opcoes);
}

/**
 * Transmite um e-mail já montado pelo SMTP.
 *
 * Aplica o redirecionamento de teste, o timeout e o AltBody. Não apaga arquivos temporários:
 * quem decide é o despacho, porque uma falha pode voltar para a fila.
 *
 * @param array $payload Payload de comunicacao_email_montar_envio().
 * @param array $server Servidor SMTP (com a senha, nunca persistido).
 * @return array{ok:bool,erro:string,destinatario:string,destinatario_original:?string}
 */
function comunicacao_email_transmitir($payload, $server){
	$sender = (array)($payload['sender'] ?? Array());
	$recipients = (array)($payload['recipients'] ?? Array());
	$message = (array)($payload['message'] ?? Array());
	$subject = (string)($message['subject'] ?? '');
	$body = (string)($message['body'] ?? '');

	$originais = Array();
	$destinatarioOriginal = null;
	$destinoTeste = comunicacao_redirecionamento_destino(true);
	if($destinoTeste !== ''){
		$redirecionado = comunicacao_redirecionar($recipients, $subject, $destinoTeste);
		$recipients = $redirecionado['recipients'];
		$subject = $redirecionado['subject'];
		$originais = $redirecionado['originais'];
		$destinatarioOriginal = $redirecionado['destinatario_original'];
	}

	$destinatario = comunicacao_driver_email_destinatario(Array('recipients' => $recipients));

	//Instantiation and passing `true` enables exceptions
	$mail = comunicacao_email_mailer();

	try {
		//Server settings

		if(!empty($server['debug'])) $mail->SMTPDebug = SMTP::DEBUG_SERVER;                        //Enable verbose debug output

		$mail->isSMTP();                                           	  //Send using SMTP
		$mail->Host       	= $server['host'];                   	  //Set the SMTP server to send through
		$mail->SMTPAuth   	= true;                                   //Enable SMTP authentication
		$mail->Username   	= $server['user'];                 		  //SMTP username
		$mail->Password   	= $server['pass'];                        //SMTP password
		$mail->SMTPSecure 	= $server['secure'];   				      //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
		$mail->Port       	= $server['port'];                     	  //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
		$mail->CharSet 		= PHPMailer::CHARSET_UTF8;         		  //Charset default UTF-8
		// REQ-304: sem teto, um relay lento trava o worker ou a requisição HTTP.
		$mail->Timeout		= (int)($server['timeout'] ?? comunicacao_config()['timeout']);

		//Sender

		if(isset($sender['fromName'])){ $mail->setFrom($sender['from'], $sender['fromName']); } else { $mail->setFrom($sender['from']); }
		// Reply-To é opcional: com valor vazio o PHPMailer registra "Invalid address (Reply-To)"
		// e a mensagem inteira deixa de sair. Um campo em branco no cadastro do formulário
		// não pode derrubar o envio — sem endereço, simplesmente não há Reply-To.
		if(!empty($sender['replyTo'])){
			if(!empty($sender['replyToName'])){ $mail->addReplyTo($sender['replyTo'], $sender['replyToName']); } else { $mail->addReplyTo($sender['replyTo']); }
		}

		//Recipients

		foreach($recipients as $recipient){
			$tipo = strtolower(isset($recipient['tipo']) ? $recipient['tipo'] : 'normal');

			switch($tipo){
				case 'normal':
					if(isset($recipient['nome'])){ $mail->addAddress($recipient['email'], $recipient['nome']); } else { $mail->addAddress($recipient['email']); }
				break;
				case 'cc':
					if(isset($recipient['nome'])){ $mail->addCC($recipient['email'], $recipient['nome']); } else { $mail->addCC($recipient['email']); }
				break;
				case 'bcc':
					if(isset($recipient['nome'])){ $mail->addBCC($recipient['email'], $recipient['nome']); } else { $mail->addBCC($recipient['email']); }
				break;
			}
		}

		if($originais){
			$mail->addCustomHeader('X-Original-To', implode(', ', $originais));
		}

		// Attachments

		foreach((array)($message['attachments'] ?? Array()) as $attachment){
			if(isset($attachment['caminho'])){
				$mail->AddAttachment($attachment['caminho'], (isset($attachment['nome']) ? $attachment['nome'] : '' ));
			}
		}

		// Embedded Images

		foreach((array)($message['embeddedImgs'] ?? Array()) as $img){
			$filename = $img['caminho'];
			$cid = $img['cid'];
			$name = (!empty($img['nome']) ? $img['nome'] : $img['caminho']);

			$cid = preg_replace('/'.preg_quote('cid:').'/i', '', $cid);

			$mail->AddEmbeddedImage($filename, $cid, $name);
		}

		//Content

		$mail->isHTML(true);                                  //Set email format to HTML

		$mail->Subject = $subject;
		$mail->Body    = $body;

		// REQ-304: texto puro derivado do HTML final; a frase genérica só sobra se o HTML for vazio.
		$altBody = trim((string)($payload['altBody'] ?? ''));
		if($altBody === '') $altBody = comunicacao_html_para_texto($body);
		if($altBody === '') $altBody = (string)($server['altBody'] ?? '');
		$mail->AltBody = $altBody;

		// ===== Enviar o email

		$mail->send();

		return Array('ok' => true, 'erro' => '', 'destinatario' => $destinatario, 'destinatario_original' => $destinatarioOriginal);
	} catch (Exception $e) {
		$erro = comunicacao_texto_sem_segredo($mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage(), $server);

		if(!function_exists('log_disco')) require_once __DIR__.'/log.php';

		if(!empty($server['debug']) && function_exists('log_debugar')){
			// ===== Se houve algum erro e o debug estiver ativo, incluir no histórico o erro.

			log_debugar(Array(
				'alteracoes' => Array(
					Array(
						'alteracao' => 'email-error',
						'alteracao_txt' => $erro,
					)
				),
			));
		} else {
			// ===== Se der algum problema, criar log no disco — com a senha SMTP mascarada (REQ-304).

			log_disco('Error retorno: '.$erro.' - '.comunicacao_texto_sem_segredo($e->getMessage(), $server) . ' - Server: ' . print_r(comunicacao_mascarar_segredos($server),true),'email');
		}

		return Array('ok' => false, 'erro' => $erro, 'destinatario' => $destinatario, 'destinatario_original' => $destinatarioOriginal);
	}
}

?>
