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
			$lastModified = filemtime($file);
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
			header('Cache-Control: private');
			
			switch($ext){
				case 'js': header("Content-Type: application/javascript; charset=UTF-8"); break;
				case 'css': header("Content-Type: text/css; charset=UTF-8"); break;
				default: 
					header("Content-Type: " . mime_content_type($file)."; charset=UTF-8");
			}
			
			readfile($file);
			exit;
		}
		
		// ===== Arquivos gerenciado pelos usuários via módulo arquivos.
		
		$file = $_GESTOR['contents-path'].$_GESTOR['caminho-total'];
		
		if(file_exists($file)){
			$lastModified = filemtime($file);
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
			header('Cache-Control: private');
			
			switch($ext){
				case 'js': header("Content-Type: application/javascript; charset=UTF-8"); break;
				case 'css': header("Content-Type: text/css; charset=UTF-8"); break;
				case 'svg': header("Content-Type: image/svg+xml; charset=UTF-8"); break;
				default: 
					header("Content-Type: " . mime_content_type($file)."; charset=UTF-8");
			}
			
			readfile($file);
			exit;
		}
	}
	
	arquivo_estatico_404();
}

arquivo_estatico_start();

?>