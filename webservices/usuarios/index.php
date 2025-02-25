<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@age1.com.br
	
	B2Make
	
	Copyright (c) 2014 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/

// Funções de Iniciação do sistema B2make

$_VERSAO_MODULO				=	'1.0.1';
$_INCLUDE_MAILER			=	true;
$_PUBLICO					=	true;
$_LOCAL_ID					=	"update";
$_CAMINHO_RELATIVO_RAIZ		=	"../../";

$_WEBSERVICES['users']		=	Array(
	Array('user' => 'william','pass' => '088QaH7882k'),
);

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function main(){
	global $_SYSTEM;
	global $_HTML;
	global $_WEBSERVICES;
	
	header("Access-Control-Allow-Origin: *");
	
	if($_REQUEST['user'] && $_REQUEST['pass']){
		$permicao = false;
		$id_loja = $_REQUEST['id_loja'];
		$func = $_REQUEST['func'];
		$quant = ($_REQUEST['quant'] ? $_REQUEST['quant'] : '25');
		$start = ($_REQUEST['start'] ? $_REQUEST['start'] : '375');
		
		foreach($_WEBSERVICES['users'] as $user){
			if(
				$user['user'] == $_REQUEST['user'] &&
				$user['pass'] == $_REQUEST['pass'] 
			){
				$permicao = true;
				break;
			}
		}
		
		if($permicao){
			switch($func){
				case 'user-list':
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_usuario',
							'usuario',
							'nome',
						))
						,
						"usuario",
						"WHERE status!='D'"
						." ORDER BY nome"
						." LIMIT ".$quant." OFFSET ".$start
					);
					
					$res_quant = count($resultado);
					
					if($resultado)
					foreach($resultado as $res){
						$resultado2 = banco_select_name
						(
							banco_campos_virgulas(Array(
								'url',
								'server',
								'disklimit',
								'diskused',
								'services_list',
								'dominio_proprio',
								'ftp_site_user',
								'ftp_files_user',
								'google_analytic',
								'meta_global',
							))
							,
							"host",
							"WHERE id_usuario='".$res['id_usuario']."'"
						);
						
						$resultado3 = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_loja',
							))
							,
							"loja",
							"WHERE id_usuario='".$res['id_usuario']."'"
						);
						
						$dados[] = Array(
							'id_usuario' => $res['id_usuario'],
							'id_loja' => $resultado3[0]['id_loja'],
							'nome' => $res['nome'],
							'usuario' => $res['usuario'],
							'url' => $resultado2[0]['url'],
							'server' => $resultado2[0]['server'],
							'disklimit' => $resultado2[0]['disklimit'],
							'diskused' => $resultado2[0]['diskused'],
							'services_list' => $resultado2[0]['services_list'],
							'dominio_proprio' => $resultado2[0]['dominio_proprio'],
							'ftp_site_user' => $resultado2[0]['ftp_site_user'],
							'ftp_files_user' => $resultado2[0]['ftp_files_user'],
							'google_analytic' => $resultado2[0]['google_analytic'],
							'meta_global' => $resultado2[0]['meta_global'],
						);
					}
				break;
				case 'pedidos':
					if(!$id_loja){
						$saida = Array(
							'status' => 'IdLojaNaoInformado'
						);
						
						echo json_encode($saida);
						exit;
					}
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_pedidos',
							'valor_total',
							'codigo',
							'presente',
							'de',
							'para',
							'mensagem',
							'status',
						))
						,
						"pedidos",
						"WHERE id_loja='".$id_loja."'"
					);
					
					$res_quant = count($resultado);
					
					if($resultado){
						if($resultado)
						foreach($resultado as $res){
							$dados[] = Array(
								'id_pedidos' => $res['id_pedidos'],
								'valor_total' => $res['valor_total'],
								'codigo' => $res['codigo'],
								'presente' => $res['presente'],
								'status' => $res['status'],
								'de' => $res['de'],
								'para' => $res['para'],
								'mensagem' => $res['mensagem'],
							);
						}
					} else {
						$dados = 'Sem pedidos';
					}
				break;
			}
			
			$saida = Array(
				'status' => 'Ok',
				'quant' => $res_quant,
				'dados' => $dados,
			);
			
		} else {
			$saida = Array(
				'status' => 'UserOrPassInvalid'
			);
		}
	} else {
		$saida = Array(
			'status' => 'UserOrPassDontInformed'
		);
	}
	
	echo json_encode($saida);
}

main();

?>