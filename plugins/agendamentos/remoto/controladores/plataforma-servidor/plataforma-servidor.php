<?php

// ===== Plataforma responsável por receber solicitações do 'servidor'.

global $_GESTOR;

$_GESTOR['modulo-id']							=	'plataforma-servidor-plugin';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
);

// =========================== Funções Auxiliares

// =========================== Funções da Plataforma

function plataforma_servidor_cron_agendamentos(){
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
			
			// ===== Caso houve atualização do agendamentos datas, alterar os dados localmente.
			
			if(isset($dados['agendamentos_datas'])){
				$id_hosts_agendamentos_datas = $dados['agendamentos_datas']['id_hosts_agendamentos_datas'];
				$total = $dados['agendamentos_datas']['total'];
				$data = $dados['agendamentos_datas']['data'];
				$status = $dados['agendamentos_datas']['status'];
				
				$agendamentos_datas = banco_select(Array(
					'unico' => true,
					'tabela' => 'agendamentos_datas',
					'campos' => Array(
						'id_hosts_agendamentos_datas',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'"
				));
				
				if($agendamentos_datas){
					banco_update_campo('total',$total);
					
					banco_update_executar('agendamentos_datas',"WHERE id_hosts_agendamentos_datas='".$id_hosts_agendamentos_datas."'");
				} else {
					banco_insert_name_campo('id_hosts_agendamentos_datas',$id_hosts_agendamentos_datas);
					banco_insert_name_campo('data',$data);
					banco_insert_name_campo('total',$total);
					banco_insert_name_campo('status',$status);
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"agendamentos_datas"
					);
				}
			}
			
			// ===== Atualizar os agendamentos localmente.
			
			if(isset($dados['agendamentos'])){
				foreach($dados['agendamentos'] as $agendamento){
					$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
					$id_hosts_agendamentos = $agendamento['id_hosts_agendamentos'];
					
					$agendamentos = banco_select(Array(
						'unico' => true,
						'tabela' => 'agendamentos',
						'campos' => Array(
							'id_hosts_agendamentos',
						),
						'extra' => 
							"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
					));
					
					if($agendamentos){
						// ===== Atualizar agendamento.
						
						if(isset($dados['agendamentos'])){
							$agendamentos = $dados['agendamentos'];
							
							foreach($agendamentos as $key => $valor){
								switch($key){
									case 'acompanhantes':
									case 'versao':
										banco_update_campo($key,($valor ? $valor : '0'),true);
									break;
									default:
										banco_update_campo($key,$valor);
								}
							}
							
							banco_update_executar('agendamentos',"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."' AND id_hosts_usuarios='".$id_hosts_usuarios."'");
						}
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
		case 'cron-agendamentos': $dados = plataforma_servidor_cron_agendamentos(); break;
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