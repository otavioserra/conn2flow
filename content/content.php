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

$_VERSAO_MODULO				=	'1.0.0';

function content(){
	global $_SYSTEM;
	global $_HTML;
	global $_HTML;
	global $_ESERVICE;
	global $_LOJA_REQUIRE;
	global $_AJAX_ALERTA;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$usuario['content']){
		if($usuario['id_usuario_pai']) $usuario['id_usuario'] = $usuario['id_usuario_pai'];
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario',
			))
			,
			"host",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND content IS NOT NULL"
		);
		
		if($resultado){
			$_SESSION[$_SYSTEM['ID']."usuario"]['content'] = true;
		} else {
			// Criar Modelos de Páginas para o usuário poder modificar o layout da página de conteúdos via Site Builder
			
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
			
			$html = modelo_abrir($_SYSTEM['PATH'].'content'.$_SYSTEM['SEPARADOR'].'pagina-conteudos.html');
			$html_mobile = modelo_abrir($_SYSTEM['PATH'].'content'.$_SYSTEM['SEPARADOR'].'pagina-conteudos-mobile.html');
			
			$campos = null;
			
			$campo_nome = "id_host"; $campo_valor = $id_host; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_pai"; $campo_valor = $id_site_modelo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id_site_templates"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "nome"; $campo_valor = 'Página de Conteúdos'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "id"; $campo_valor = 'pagina-de-conteudos'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html"; $campo_valor = $html; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html_mobile"; $campo_valor = $html_mobile; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "html_mobile_saved"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "id_usuario"; $campo_valor = $usuario['id_usuario']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			$campo_nome = "data_criacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			$campo_nome = "data_modificacao"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
			
			$id_site_modelo = banco_last_id();
			
			banco_insert_name
			(
				$campos,
				"site"
			);
			
			$_SESSION[$_SYSTEM['ID']."usuario"]['content'] = true;
			$_SESSION[$_SYSTEM['ID']."b2make.site.reset-cache"] = true;
			
			banco_update
			(
				"content=1",
				"host",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
			);
		}
		
		$_SESSION[$_SYSTEM['ID']."usuario"]['id_loja'] = $id_loja;
	}
}

content();

?>