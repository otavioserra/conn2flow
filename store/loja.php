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
// Funções de Iniciação do sistema

$_VERSAO_MODULO				=	'1.0.1';

function loja_main(){
	global $_SYSTEM;
	global $_HTML;
	global $_HTML;
	global $_ESERVICE;
	global $_LOJA_REQUIRE;
	global $_AJAX_ALERTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$usuario['id_loja']){
		if($usuario['id_usuario_pai']) $usuario['id_usuario'] = $usuario['id_usuario_pai'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_loja',
				'status',
			))
			,
			"loja",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
		);
		
		if($resultado){
			if($resultado[0]['status'] == 'A'){
				$id_loja = $resultado[0]['id_loja'];
			} else {
				if($_LOJA_REQUIRE){
					$_AJAX_ALERTA = '<p>Sua loja está bloqueada! Favor entrar em contato com a administração para saber qual providências tomar: <a href="http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'contato">CONTATO</a></p>';
					return;
				} else {
					$_SESSION[$_SYSTEM['ID']."alerta"] = '<p>Sua loja está bloqueada! Favor entrar em contato com a administração para saber qual providências tomar: <a href="http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'contato">CONTATO</a></p>';
					header('Location: http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'] . 'store/');
					exit;
				}
			}
		} else {
			// Criação da loja do usuário com parâmetros default
			
			$campos = null;
			
			$campo_nome = "nome"; $campo_valor = $_ESERVICE['minha-loja']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "email_assunto"; $campo_valor = 'Seu pedido #codigo# foi atualizado'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "email_assinatura"; $campo_valor = '<p>Atenciosamente.</p><h3>#loja-nome#</h3>'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			banco_insert_name
			(
				$campos,
				"loja"
			);
			
			$id_loja = banco_last_id();
			
			banco_update
			(
				"id='".$_ESERVICE['minha-loja-id']."-".$id_loja."'",
				"loja",
				"WHERE id_loja='".$id_loja."'"
			);

			// Criar Modelos de Páginas para o usuário poder modificar o layout da página de serviços via Site Builder
			
			$html = '';
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_host',
					'id_site',
				))
				,
				"site",
				"WHERE id_site_pai IS NULL"
				." AND id_usuario='".$usuario['id_usuario']."'"
			);
			
			$id_host = $resultado[0]['id_host'];
			$id_site_pai = $resultado[0]['id_site'];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_site',
				))
				,
				"site",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND id='01-modelos-de-paginas'"
			);
			
			if(!$resultado){
				$campos = null;
				
				$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_site_pai"; $campo_valor = $id_site_pai; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "nome"; $campo_valor = '01 - Modelos de Páginas'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id"; $campo_valor = '01-modelos-de-paginas'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
				
				banco_insert_name
				(
					$campos,
					"site"
				);
				
				$id_site_modelo = banco_last_id();
			} else {
				$id_site_modelo = $resultado[0]['id_site'];
			}
			
			// Criar Páginas de Serviços para o usuário poder modificar o layout da página de serviços via Site Builder
			
			$html = modelo_abrir($_SYSTEM['PATH'].'store'.$_SYSTEM['SEPARADOR'].'pagina-servicos.html');
			$html_mobile = modelo_abrir($_SYSTEM['PATH'].'store'.$_SYSTEM['SEPARADOR'].'pagina-servicos-mobile.html');
			
			$campos = null;
			
			$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_pai"; $campo_valor = $id_site_modelo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $campo_valor = 'Página de Serviços'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = 'pagina-de-servicos'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html_mobile"; $campo_valor = $html_mobile; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html_mobile_saved"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "google_fontes"; $campo_valor = 'Open+Sans'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "google_fontes_mobile"; $campo_valor = 'Open+Sans'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			$id_site_modelo = banco_last_id();
			
			banco_insert_name
			(
				$campos,
				"site"
			);
			
			$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
			
			$campos = null;
			
			$campo_nome = "id_loja"; $campo_valor = $id_loja; 									$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "status"; $campo_valor = $usuario['status']; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			
			//if($usuario['nome']){$campo_nome = "nome"; $campo_valor = $usuario['nome']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['ultimo_nome']){$campo_nome = "ultimo_nome"; $campo_valor = $usuario['ultimo_nome']; 													$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['email']){$campo_nome = "email"; $campo_valor = $usuario['email']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['senha']){$campo_nome = "senha"; $campo_valor = $usuario['senha']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['telefone']){$campo_nome = "telefone"; $campo_valor = $usuario['telefone']; 															$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['cpf']){$campo_nome = "cpf"; $campo_valor = $usuario['cpf']; 																			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['cnpj']){$campo_nome = "cnpj"; $campo_valor = $usuario['cnpj']; 																		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['versao_voucher']){$campo_nome = "versao_voucher"; $campo_valor = $usuario['versao_voucher']; 											$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['data_cadastro']){$campo_nome = "data_cadastro"; $campo_valor = $usuario['data_cadastro']; 												$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['data_login']){$campo_nome = "data_login"; $campo_valor = $usuario['data_login']; 														$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['ppp_remembered_card_hash']){$campo_nome = "ppp_remembered_card_hash"; $campo_valor = $usuario['ppp_remembered_card_hash']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
			if($usuario['cnpj_selecionado']){$campo_nome = "cnpj_selecionado"; $campo_valor = '1'; 																$campos[] = Array($campo_nome,$campo_valor,true);}
			
			banco_insert_name
			(
				$campos,
				"loja_usuarios"
			);
			
			$id_loja_usuarios = banco_last_id();
			
			banco_update
			(
				"id_loja_usuarios='".$id_loja_usuarios."'",
				"usuario",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
			);
			
			$_SESSION[$_SYSTEM['ID']."usuario"]['id_loja_usuarios'] = $id_loja_usuarios;
			
			if($id_loja_usuarios){
				$_SESSION[$_SYSTEM['ID']."loja_usuarios_senha"] = $_SESSION[$_SYSTEM['ID']."usuario_senha"];
				
				$loja_usuarios = banco_select_name
				(
					"*"
					,
					"loja_usuarios",
					"WHERE id_loja_usuarios='" . $id_loja_usuarios . "'"
				);
				
				$senha_sessao = sha1(crypt($loja_usuarios[0]['senha']).mt_rand());
				$loja_usuarios[0]['senha_sessao'] = $senha_sessao;
				
				banco_update
				(
					"senha_sessao='".$senha_sessao."',".
					"data_login=NOW()",
					"loja_usuarios",
					"WHERE id_loja_usuarios='".$id_loja_usuarios."'"
				);
				
				$_SESSION[$_SYSTEM['ID']."loja_usuarios"] = $loja_usuarios[0];
				
				$_SESSION[$_SYSTEM['ID']."loja-permissao"] = true;
			}
		}
		
		$_SESSION[$_SYSTEM['ID']."usuario"]['id_loja'] = $id_loja;
	}
}

loja_main();
$_LOJA_REQUIRE = false;

?>