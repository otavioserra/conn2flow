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
// Fun��es de Inicia��o do sistema

$_VERSAO_MODULO				=	'1.0.0';

function main(){
	global $_SYSTEM;
	global $_HTML;
	global $_HTML;
	global $_ESERVICE;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$usuario['id_loja']){
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
				$_SESSION[$_SYSTEM['ID']."alerta"] = '<p>Sua loja est� bloqueada! Favor entrar em contato com a administra��o para saber qual provid�ncias tomar: <a href="http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'contato">CONTATO</a></p>';
				header('Location: http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'] . 'e-service/');
				exit;
			}
		} else {
			// Criar Modelos de P�ginas para o usu�rio poder modificar o layout da p�gina de servi�os via Site Builder
			
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
			
			$campos = null;
			
			$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_pai"; $campo_valor = $id_site_pai; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $campo_valor = '01 - Modelos de P�ginas'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = '01-modelos-de-paginas'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html"; $campo_valor = utf8_decode($html); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			banco_insert_name
			(
				$campos,
				"site"
			);
			
			$id_site_modelo = banco_last_id();
			
			// Criar P�ginas de Servi�os para o usu�rio poder modificar o layout da p�gina de servi�os via Site Builder
			
			$html = modelo_abrir($_SYSTEM['PATH'].'e-service'.$_SYSTEM['SEPARADOR'].'pagina-servicos.html');
			
			$campos = null;
			
			$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_pai"; $campo_valor = $id_site_modelo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $campo_valor = 'P�gina de Servi�os'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = 'pagina-de-servicos'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html"; $campo_valor = utf8_decode($html); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
			
			// Cria��o da loja do usu�rio com par�metros default
			
			$campos = null;
			
			$campo_nome = "nome"; $campo_valor = $_ESERVICE['minha-loja']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
		}
		
		$_SESSION[$_SYSTEM['ID']."usuario"]['id_loja'] = $id_loja;
	}
}

main();

?>