<?php

// ===== Plataforma responsável por receber solicitações do 'servidor'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-servidor-plugin';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

// =========================== Funções da Plataforma

function plataforma_servidor_cron_escalas(){
	global $_GESTOR;
	
	// ===== Identificador do Host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Verificar qual opção desta interface está sendo disparada e tratar cada caso separadamente.
	
	$opcao = $_REQUEST['opcao'];
	
	switch($opcao){
		case 'atualizar':
			// ===== Decodificar os dados em formato Array
			
			$dados = Array();
			if(isset($_REQUEST['dados'])){
				$dados = json_decode($_REQUEST['dados'],true);
			}
			
			// ===== Atualizar os escalas localmente.
			
			if(isset($dados['escalas'])){
				foreach($dados['escalas'] as $escala){
					$id_hosts_usuarios = $escala['id_hosts_usuarios'];
					$id_hosts_escalas = $escala['id_hosts_escalas'];
					
					$escalas = banco_select(Array(
						'unico' => true,
						'tabela' => 'escalas',
						'campos' => Array(
							'id_hosts_escalas',
						),
						'extra' => 
							"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
					));
					
					if($escalas){
						// ===== Atualizar escala.
						
						foreach($escala as $key => $valor){
							switch($key){
								case 'data_confirmacao':
									if(existe($valor)){
										banco_update_campo($key,$valor);
									} else {
										banco_update_campo($key,'NULL',true);
									}
								break;
								case 'versao':
									banco_update_campo($key,($valor ? $valor : '0'),true);
								break;
								default:
									banco_update_campo($key,$valor);
							}
						}
						
						banco_update_executar('escalas',"WHERE id_hosts_escalas='".$id_hosts_escalas."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
					}
				}
			}
			
			// ===== Atualizar os escalas datas localmente.
			
			if(isset($dados['escalas_datas'])){
				foreach($dados['escalas_datas'] as $escala_data){
					$id_hosts_escalas_datas = $escala_data['id_hosts_escalas_datas'];
					$id_hosts_escalas = $escala_data['id_hosts_escalas'];
					
					$escalas_datas = banco_select(Array(
						'unico' => true,
						'tabela' => 'escalas_datas',
						'campos' => Array(
							'id_hosts_escalas',
						),
						'extra' => 
							"WHERE id_hosts_escalas='".$id_hosts_escalas."'"
					));
					
					if($escalas_datas){
						// ===== Atualizar escala_data.
						
						foreach($escala_data as $key => $valor){
							switch($key){
								case 'selecionada':
								case 'selecionada_inscricao':
								case 'selecionada_confirmacao':
									banco_update_campo($key,($valor ? $valor : 'NULL'),true);
								break;
								default:
									banco_update_campo($key,$valor);
							}
						}
						
						banco_update_executar('escalas_datas',"WHERE id_hosts_escalas='".$id_hosts_escalas."' AND id_hosts_escalas_datas='".$id_hosts_escalas_datas."'");
					}
				}
			}
			
			// ===== Atualizar 'escalas_controle' caso necessário.
			
			if(isset($dados['escalas_controle'])){
				$escalas_controle = $dados['escalas_controle']['tabela'];
				$dateInicio = $dados['escalas_controle']['dateInicio'];
				$dateFim = $dados['escalas_controle']['dateFim'];
				
				$escalasControleLocal = banco_select(Array(
					'tabela' => 'escalas_controle',
					'campos' => Array(
						'id_hosts_escalas_controle',
					),
					'extra' => 
						"WHERE data >= '".$dateInicio."'"
						." AND data <= '".$dateFim."'"
				));
				
				foreach($escalas_controle as $escala_controle){
					$id_hosts_escalas_controle = $escala_controle['id_hosts_escalas_controle'];
					$foundEscalaControleLocal = false;
					
					if($escalasControleLocal){
						foreach($escalasControleLocal as $escalaControleLocal){
							if($id_hosts_escalas_controle == $escalaControleLocal['id_hosts_escalas_controle']){
								$foundEscalaControleLocal = true;
								break;
							}
						}
					}
					
					if($foundEscalaControleLocal){
						foreach($escala_controle as $key => $valor){
							switch($key){
								case 'total':
									banco_update_campo($key,($valor ? $valor : '0'),true);
								break;
								default:
									banco_update_campo($key,$valor);
							}
						}
						
						banco_update_executar('escalas_controle',"WHERE id_hosts_escalas_controle='".$id_hosts_escalas_controle."'");
					} else {
						foreach($escala_controle as $key => $valor){
							switch($key){
								case 'total':
									banco_insert_name_campo($key,($valor ? $valor : '0'),true);
								break;
								default:
									banco_insert_name_campo($key,$valor);
							}
						}
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"escalas_controle"
						);
					}
				}
			}
			
			// ===== Retornar dados.
			
			return Array(
				'status' => 'OK',
				'data' => $retornoDados,
			);
		break;
		default:
			return Array(
				'status' => 'OPTION_NOT_DEFINED',
			);
	}
}

// =========================== Funções de Acesso

function plataforma_servidor_plugin_start(){
	global $_GESTOR;
	
	// ===== Verifica a opção, executa interface caso encontrado e retorna os dados
	
	switch($_GESTOR['caminho'][1]){
		case 'cron-escalas': $dados = plataforma_servidor_cron_escalas(); break;
	}

	// ===== Caso haja dados criados por alguma opção, retornar os dados. Senão retornar NULL.
	
	if(isset($dados)){
		return $dados;
	} else {
		return NULL;
	}
}

// ===== Retornar plataforma.

return plataforma_servidor_plugin_start();

?>