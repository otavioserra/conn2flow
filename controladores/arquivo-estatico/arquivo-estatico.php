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
				if($alvo){
					if($_GESTOR['caminho'][count($_GESTOR['caminho'])-1] != $ext.'.'.$ext){
						$file = $_INDEX['sistemas-dir'].'b2make-gestor/assets/'.$_GESTOR['caminho-total'];
					} else {
						if(count($_GESTOR['caminho']) > 2){
							$file = $_GESTOR['plugins-path'].$alvo.'/local/modulos/'.$alvo2.'/'.$alvo2.'.'.$ext;
						} else {
							$file = $_INDEX['sistemas-dir'].'b2make-gestor/modulos/'.$alvo.'/'.$alvo.'.'.$ext;
						}
					}
				}
			break;
			default:
				$file = $_INDEX['sistemas-dir'].'b2make-gestor/assets/'.$_GESTOR['caminho-total'];
		}
		
		if(file_exists($file)){
			$lastModified = filemtime($file);
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
			header('Cache-Control: private');
			
			switch($ext){
				case 'js': header("Content-Type: application/javascript; charset: UTF-8"); break;
				case 'css': header("Content-Type: text/css; charset: UTF-8"); break;
				default: 
					header("Content-Type:" . mime_content_type($file)."; charset: UTF-8");
			}
			
			readfile($file);
			exit;
		}
		
		// ===== Arquivos gerenciado pelos usuários via módulo arquivos.
		
		$file = $_INDEX['sistemas-dir'].'b2make-gestor/contents/'.$_GESTOR['caminho-total'];
		
		if(file_exists($file)){
			$lastModified = filemtime($file);
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
			header('Cache-Control: private');
			header("Content-Type:" . mime_content_type($file)."; charset: UTF-8");
			
			readfile($file);
			exit;
		}
	}
	
	arquivo_estatico_404();
}

arquivo_estatico_start();

?>