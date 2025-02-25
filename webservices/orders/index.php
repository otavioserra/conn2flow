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

$_VERSAO_MODULO				=	'1.0.0';
$_INCLUDE_MAILER			=	true;
$_PUBLICO					=	true;
$_LOCAL_ID					=	"ws-orders";
$_CAMINHO_RELATIVO_RAIZ		=	"../../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function main(){
	global $_SYSTEM;
	global $_HTML;
	global $_WEBSERVICES;
	global $_REMOTE_ADDR;
	
	header("Access-Control-Allow-Origin: *");
	
	if($_REQUEST['user'] && $_REQUEST['token']){
		$permicao = false;
		
		$user = $_REQUEST['user'];
		$token = $_REQUEST['token'];
		$id_pedidos = $_REQUEST['id_pedidos'];
		
		$usuarios = banco_select_name(
			"*",
			"usuario",
			"WHERE usuario='".$user."' AND status!='D'"
		);
		
		$usuario_mobile = $usuarios[0];
		
		if($usuario_mobile){
			if(
				$usuario_mobile['usuario'] == $user &&
				$usuario_mobile['sessao_mobile'] == $token
			){
				$permicao = true;
			}
		}
		
		if($permicao){
			$loja = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_loja',
				))
				,
				"loja",
				"WHERE id_usuario='".($usuario_mobile['id_usuario_pai'] ? $usuario_mobile['id_usuario_pai'] : $usuario_mobile['id_usuario'])."'"
			);
			
			$id_loja = $loja[0]['id_loja'];
			
			if(!$id_pedidos){
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
					." ORDER BY id_pedidos DESC"
				);
				
				if($resultado){
					if($resultado)
					foreach($resultado as $res){
						$usuario_pedidos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_usuario',
							))
							,
							"usuario_pedidos",
							"WHERE id_pedidos='".$res['id_pedidos']."'"
						);
						
						$usuario = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_usuario',
								'usuario',
								'email',
								'nome',
								'sobrenome',
								'cep',
								'endereco',
								'numero',
								'complemento',
								'bairro',
								'cidade',
								'uf',
								'telefone',
								'celular',
								'data_cadastro',
								'data_login',
								'avatar',
								'pub_id',
								'cpf',
								'cnpj',
								'nascimento',
								'ddd',
							))
							,
							"usuario",
							"WHERE id_usuario='".$usuario_pedidos[0]['id_usuario']."'"
						);
						
						$dados[] = Array(
							'id_pedidos' => $res['id_pedidos'],
							'valor_total' => $res['valor_total'],
							'codigo' => $res['codigo'],
							'presente' => $res['presente'],
							'status' => $res['status'],
							'de' => $res['de'],
							'para' => $res['para'],
							'mensagem' => $res['mensagem'],
							'usuario_pedido' => Array(
								'id_usuario' => $usuario[0]['id_usuario'],
								'usuario' => $usuario[0]['usuario'],
								'email' => $usuario[0]['email'],
								'nome' => $usuario[0]['nome'],
								'sobrenome' => $usuario[0]['sobrenome'],
								'cep' => $usuario[0]['cep'],
								'endereco' => $usuario[0]['endereco'],
								'numero' => $usuario[0]['numero'],
								'complemento' => $usuario[0]['complemento'],
								'bairro' => $usuario[0]['bairro'],
								'cidade' => $usuario[0]['cidade'],
								'uf' => $usuario[0]['uf'],
								'telefone' => $usuario[0]['telefone'],
								'celular' => $usuario[0]['celular'],
								'data_cadastro' => $usuario[0]['data_cadastro'],
								'data_login' => $usuario[0]['data_login'],
								'avatar' => $usuario[0]['avatar'],
								'pub_id' => $usuario[0]['pub_id'],
								'cpf' => $usuario[0]['cpf'],
								'cnpj' => $usuario[0]['cnpj'],
								'nascimento' => $usuario[0]['nascimento'],
								'ddd' => $usuario[0]['ddd'],
							),
						);
					}
				} else {
					$dados = 'Sem pedidos';
				}
				
				$saida = Array(
					'status' => 'Ok',
					'dados' => $dados,
				);
			} else {				
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
					." AND id_pedidos='".$id_pedidos."'"
				);
				
				$res_quant = count($resultado);
				
				if($resultado){
					$pedidos_servicos = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_servicos',
							'codigo',
							'quantidade',
							'sub_total',
							'valor_original',
							'desconto',
							'protocolo_baixa',
							'id_usuario_baixa',
							'data_baixa',
							'observacao_baixa',
							'validade',
							'status',
							'voucher_por_servico',
							'de',
							'para',
							'mensagem',
						))
						,
						"pedidos_servicos",
						"WHERE id_pedidos='".$id_pedidos."'"
					);
					
					if($pedidos_servicos){
						foreach($pedidos_servicos as $ps){
							$servicos = banco_select_name
							(
								banco_campos_virgulas(Array(
									'nome',
									'descricao',
									'imagem_path',
									'imagem_path_mini',
									'versao',
								))
								,
								"servicos",
								"WHERE id_servicos='".$ps['id_servicos']."'"
							);
							
							$dados[] = Array(
								'id_servicos' => $ps['id_servicos'],
								'nome' => $servicos[0]['nome'],
								'descricao' => $servicos[0]['descricao'],
								'imagem_path' => $servicos[0]['imagem_path'],
								'imagem_path_mini' => $servicos[0]['imagem_path_mini'],
								'versao' => $servicos[0]['versao'],
								'codigo' => $ps['codigo'],
								'sub_total' => $ps['sub_total'],
								'valor_original' => $ps['valor_original'],
								'desconto' => $ps['desconto'],
								'protocolo_baixa' => $ps['protocolo_baixa'],
								'id_usuario_baixa' => $ps['id_usuario_baixa'],
								'data_baixa' => $ps['data_baixa'],
								'observacao_baixa' => $ps['observacao_baixa'],
								'validade' => $ps['validade'],
								'status' => $ps['status'],
								'voucher_por_servico' => $ps['voucher_por_servico'],
								'de' => $ps['de'],
								'para' => $ps['para'],
								'mensagem' => $ps['mensagem'],
							);
						}
					} else {
						$dados = 'Sem serviços';
					}
					
					$saida = Array(
						'status' => 'Ok',
						'dados' => $dados,
					);
				} else {
					$saida = Array(
						'status' => 'IdPedidosNaoPertenceALojaDoUsuario',
						'message' => 'Este pedido de identificador: '.$id_pedidos.' não pertence a esse usuário. É necessário informar um identificador de pedido do usuário para proseguir.',
					);
				}
			}
		} else {
			$saida = Array(
				'status' => 'UserDontPermited',
				'message' => 'Usuário não tem acesso ao sistema. É obrigatório informar ambos os campos de forma correta.',
			);
		}
	} else {
		$saida = Array(
			'status' => 'UserOrTokenDontInformed',
			'message' => 'Usuário e/ou Token não informados. É obrigatório informar ambos os campos de forma correta.',
		);
	}
	
	echo json_encode($saida);
}

main();

?>