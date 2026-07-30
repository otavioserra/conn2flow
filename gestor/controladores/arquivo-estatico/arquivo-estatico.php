<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'arquivo-estatico';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

function arquivo_estatico_404(){
	http_response_code(404);
	
	echo '<html>'."\n";
	echo '<head>'."\n";
	echo '	<title>404</title>'."\n";
	echo '</head>'."\n";
	echo '<body>'."\n";
	echo '	<h1>404</h1>'."\n";
	echo '	<h2>Arquivo não encontrado</h2>'."\n";
	echo '</body>'."\n";
	echo '</html>'."\n";
	
	exit;
}

/**
 * Content-Type do arquivo servido (BATCH-100).
 *
 * `charset` só faz sentido em formatos de TEXTO; anexá-lo a vídeo/áudio/imagem/PDF é inválido e
 * alguns players tratam o tipo como desconhecido.
 *
 * @param string $ext Extensão requisitada.
 * @param string $file Caminho físico (usado para detectar o mime quando a extensão não é conhecida).
 * @return string
 */
function arquivo_estatico_content_type($ext, $file = ''){
	$textuais = Array(
		'js' => 'application/javascript',
		'css' => 'text/css',
		'svg' => 'image/svg+xml',
		'json' => 'application/json',
		'html' => 'text/html',
		'txt' => 'text/plain',
		'xml' => 'application/xml',
	);

	$ext = strtolower((string)$ext);

	if(isset($textuais[$ext])) return $textuais[$ext].'; charset=UTF-8';

	$mime = ($file && function_exists('mime_content_type') && file_exists($file)) ? @mime_content_type($file) : '';

	return $mime ? $mime : 'application/octet-stream';
}

/**
 * Interpreta o cabeçalho `Range` de uma requisição (BATCH-100).
 *
 * Reprodução de mídia depende disso: o navegador pede faixas do arquivo para iniciar rápido e para
 * permitir o "arrastar" da linha do tempo — e o Safari/iOS simplesmente NÃO toca um `<video>`/`<audio>`
 * servido sem resposta parcial (206).
 *
 * Função PURA (testável): não lê arquivo nem envia cabeçalho.
 *
 * @param string|null $header Valor bruto do cabeçalho (ex.: "bytes=0-1023", "bytes=500-", "bytes=-500").
 * @param int $tamanho Tamanho total do arquivo em bytes.
 * @return array|null|false [inicio, fim] quando válido; null quando não há Range; false quando inválido (416).
 */
function arquivo_estatico_range($header, $tamanho){
	$header = trim((string)$header);

	if($header === '') return null;
	if($tamanho <= 0) return false;
	if(!preg_match('/^bytes=(\d*)-(\d*)$/i', $header, $m)) return false;

	$inicioBruto = $m[1];
	$fimBruto = $m[2];

	if($inicioBruto === '' && $fimBruto === '') return false;

	if($inicioBruto === ''){
		// Sufixo: últimos N bytes.
		$quantidade = (int)$fimBruto;
		if($quantidade <= 0) return false;
		if($quantidade > $tamanho) $quantidade = $tamanho;
		$inicio = $tamanho - $quantidade;
		$fim = $tamanho - 1;
	} else {
		$inicio = (int)$inicioBruto;
		$fim = ($fimBruto === '') ? ($tamanho - 1) : (int)$fimBruto;
		if($fim > $tamanho - 1) $fim = $tamanho - 1;
	}

	if($inicio > $fim || $inicio < 0 || $inicio > $tamanho - 1) return false;

	return Array($inicio, $fim);
}

/**
 * Envia o arquivo ao cliente com suporte a requisição parcial (BATCH-100).
 *
 * Antes o envio era um `readfile()` cru: sem `Content-Length` (resposta chunked), sem `Accept-Ranges`
 * e ignorando o `Range` pedido — o que impede seek em vídeo/áudio e quebra a reprodução no iOS.
 *
 * @param string $file Caminho físico já validado.
 * @param string $ext Extensão requisitada.
 */
function arquivo_estatico_enviar($file, $ext){
	$tamanho = filesize($file);
	$lastModified = filemtime($file);

	// Buffers abertos corrompem binário e invalidam o Content-Length.
	while(ob_get_level() > 0){ ob_end_clean(); }

	header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastModified).' GMT');
	header('Cache-Control: private');
	header('Content-Type: '.arquivo_estatico_content_type($ext, $file));
	header('Accept-Ranges: bytes');

	$range = arquivo_estatico_range(isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : '', $tamanho);

	if($range === false){
		http_response_code(416);
		header('Content-Range: bytes */'.$tamanho);
		exit;
	}

	if($range === null){
		header('Content-Length: '.$tamanho);
		readfile($file);
		exit;
	}

	list($inicio, $fim) = $range;
	$comprimento = $fim - $inicio + 1;

	http_response_code(206);
	header('Content-Range: bytes '.$inicio.'-'.$fim.'/'.$tamanho);
	header('Content-Length: '.$comprimento);

	$handle = fopen($file, 'rb');
	if($handle === false) exit;

	fseek($handle, $inicio);
	$restante = $comprimento;
	$bloco = 8192;

	while($restante > 0 && !feof($handle)){
		$ler = ($restante > $bloco) ? $bloco : $restante;
		$dados = fread($handle, $ler);
		if($dados === false) break;
		echo $dados;
		flush();
		$restante -= strlen($dados);
	}

	fclose($handle);
	exit;
}

function arquivo_estatico_start(){
	global $_GESTOR;
	global $_INDEX;
	
	if(isset($_GESTOR['arquivo-estatico'])){
		$ext = ($_GESTOR['arquivo-estatico']['ext'] ? $_GESTOR['arquivo-estatico']['ext'] : null);
		$alvo = ($_GESTOR['arquivo-estatico']['alvo'] ? $_GESTOR['arquivo-estatico']['alvo'] : null);
		$alvo2 = ($_GESTOR['arquivo-estatico']['alvo2'] ? $_GESTOR['arquivo-estatico']['alvo2'] : null);
		$file = '';
		
		// ===== Arquivos do gestor e módulos
		
		switch($ext){
			case 'js':
			case 'css':
				$alvo2_sem_ext = str_replace('.'.$ext, '', $alvo2);
				switch($alvo2_sem_ext){
					case 'widget':
						if($alvo){
							if($_GESTOR['caminho'][count($_GESTOR['caminho'])-1] != $alvo2_sem_ext.'.'.$ext){
								$file = $_GESTOR['assets-path'].$_GESTOR['caminho-total'];
							} else {
								if(count($_GESTOR['caminho']) == 2){
									$file = $_GESTOR['modulos-path'].$alvo.'/'.$alvo.'.'.$alvo2_sem_ext.'.'.$ext;
								}
							}
						}
					break;
					default:
						if($alvo){
							$arquivo_requisitado = $_GESTOR['caminho'][count($_GESTOR['caminho'])-1];
							$padrao_opcao = '/^[A-Za-z0-9-]+$/';
							
							if($arquivo_requisitado == $ext.'.'.$ext){
								if(count($_GESTOR['caminho']) == 2){
									$file = $_GESTOR['modulos-path'].$alvo.'/'.$alvo.'.'.$ext;
								}
							} else if($arquivo_requisitado == $alvo2_sem_ext.'.'.$ext && preg_match($padrao_opcao, $alvo2_sem_ext)){
								if(count($_GESTOR['caminho']) == 2){
									$opcao = '.'.$alvo2_sem_ext;
									$file = $_GESTOR['modulos-path'].$alvo.'/'.$alvo.$opcao.'.'.$ext;
								}
							}
							
							if(!file_exists($file)){
								$file = $_GESTOR['assets-path'].$_GESTOR['caminho-total'];
							}
						}
				}
			break;
			default:
				$file = $_GESTOR['assets-path'].$_GESTOR['caminho-total'];
		}
		
		if(file_exists($file)){
			arquivo_estatico_enviar($file, $ext);
		}

		// ===== Arquivos gerenciado pelos usuários via módulo arquivos.

		$file = $_GESTOR['contents-path'].$_GESTOR['caminho-total'];

		if(file_exists($file)){
			arquivo_estatico_enviar($file, $ext);
		}
	}
	
	arquivo_estatico_404();
}

// O guard permite incluir este controlador em teste (as funções puras de Range/Content-Type) sem
// disparar o envio do arquivo — padrão já usado nos controladores de arquitetura.
if(!defined('SDD_NO_AUTORUN')) arquivo_estatico_start();

?>