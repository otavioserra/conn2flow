<?php

// ===== Configuração Inicial do Gestor do Cliente

$_GESTOR										=	Array();

$_GESTOR['bibliotecas']							=	Array('banco','modelo');

require_once('config-cliente.php');

// ===== Configuração deste host

require_once('config.php');

// ===== Parâmetros passados no command line.

$args_variaveis_bool = Array(
	'',
);
$args_variaveis_valor = Array(
	'rep_dir',
);

for($i=1;$i<$argc;$i++){
	if(isset($args_variaveis_bool))
	foreach($args_variaveis_bool as $varBool){
		$_GESTOR[$varBool] = true;
	}
	
	if(isset($args_variaveis_valor))
	foreach($args_variaveis_valor as $varValor){
		if(preg_match('/'.preg_quote($varValor.'=').'/i', $argv[$i]) > 0){
			$_GESTOR[$varValor] = preg_replace('/'.preg_quote($varValor.'=').'/i', '', $argv[$i]);
		}
	}
}

// =========================== Funções Auxiliares

function existe($dado = false){
	switch(gettype($dado)){
		case 'array':
			if(count($dado) > 0){
				return true;
			} else {
				return false;
			}
		break;
		case 'string':
			if(strlen($dado) > 0){
				return true;
			} else {
				return false;
			}
		break;
		default:
			if($dado){
				return true;
			} else {
				return false;
			}
	}
}

function gestor_incluir_biblioteca($biblioteca){
	global $_GESTOR;
	
	if(isset($biblioteca)){
		switch(gettype($biblioteca)){
			case 'array':
				foreach($biblioteca as $bi){
					if(isset($_GESTOR['bibliotecas-inseridas'][$bi])){
						continue;
					}
					
					$caminhos = $_GESTOR['bibliotecas-dados'][$bi];
					
					if($caminhos){
						$_GESTOR['bibliotecas-inseridas'][$bi] = true;
						
						foreach($caminhos as $caminho){
							require_once($_GESTOR['modulos-caminho'].$caminho);
						}
					}
				}
			break;
			default:
				if(isset($_GESTOR['bibliotecas-inseridas'][$biblioteca])){
					return;
				}
				
				$caminhos = $_GESTOR['bibliotecas-dados'][$biblioteca];
				
				if($caminhos){
					$_GESTOR['bibliotecas-inseridas'][$biblioteca] = true;
					
					foreach($caminhos as $caminho){
						require_once($_GESTOR['modulos-caminho'].$caminho);
					}
				}
		}
	}
}

function aplicarCor($texto,$corNome = 'noColor'){
	// ===== referência das cores: https://stackoverflow.com/questions/5947742/how-to-change-the-output-color-of-echo-in-linux/28938235#28938235.
	
	/*
	Black        0;30     Dark Gray     1;30
	Red          0;31     Light Red     1;31
	Green        0;32     Light Green   1;32
	Brown/Orange 0;33     Yellow        1;33
	Blue         0;34     Light Blue    1;34
	Purple       0;35     Light Purple  1;35
	Cyan         0;36     Light Cyan    1;36
	Light Gray   0;37     White         1;37
	*/
	
	// ===== Array com todas as cores disponíveis.
	
	$noColor = "\033[0m";
	$colors = Array(
		'black' => '0;30',
		'red' => '0;31',
		'green' => '0;32',
		'orange' => '0;33',
		'blue' => '0;34',
		'purple' => '0;35',
		'cyan' => '0;36',
		'gray' => '0;37',
		'gray2' => '1;30',
		'red2' => '1;31',
		'green2' => '1;32',
		'yellow' => '1;33',
		'blue2' => '1;34',
		'purple2' => '1;35',
		'cyan2' => '1;36',
		'white' => '1;37',
	);
	
	// ===== Aplicar a cor se encontrado, senão aplica sem cor.
	
	if(isset($colors[$corNome])){
		$texto = "\033[".$colors[$corNome]."m" . $texto . $noColor;
	} else { 
		$texto = $noColor . $texto;
	}
	
	return $texto;
}

// =========================== Funções de Acesso

function git_start(){
	global $_GESTOR;
	
	// ===== Caso o diretório do repositório não seja informado, retornar erro. Senão continuar.
	
	if(isset($_GESTOR['rep_dir'])){
		$rep_dir = aplicarCor($_GESTOR['rep_dir'],'yellow');
		
		$rep_dir = preg_replace('/\/\.git/i', '', $rep_dir);
		
		echo 'Repositório Diretório: '.$rep_dir . "\n";
		
		// ===== API-Servidor para disparar processo de atualização do plugin.
		
		gestor_incluir_biblioteca('api-servidor');
		
		$retorno = api_servidor_interface(Array(
			'interface' => 'git',
			'opcao' => 'atualizar',
			'dados' => Array(
				'rep_dir' => $rep_dir,
			),
		));
		
		if(!$retorno['completed']){
			switch($retorno['status']){
				default:
					$alerta = (existe($retorno['error-msg']) ? $retorno['error-msg'] : $retorno['status']);
			}
			
			echo aplicarCor('Finalizado com erro: ','red') . (isset($alerta) ? aplicarCor($alerta,'orange') : aplicarCor('não definido!','orange'));
		} else {
			// ===== Dados de retorno.
			
			$dados = Array();
			if(isset($retorno['data'])){
				$dados = $retorno['data'];
			}
			
			// ===== Criar ou atualizar o plugin localmente.
			
			if(isset($dados['plugin'])){
				$plugin = $dados['plugin'];
				
				
			}
			
			echo aplicarCor('Finalizado com sucesso!','green');
		}
	} else {
		echo aplicarCor('não encontrado!','orange') . "\n";
		echo aplicarCor('Finalizado com erro!','red');
	}
}

// =========================== Iniciar Git

git_start();

?>