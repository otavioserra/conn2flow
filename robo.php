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

$_VERSAO_MODULO				=	'1.0.3';
$_INCLUDE_MAILER			=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_PROCURAR			=	true;
$_INCLUDE_CONTEUDO			=	true;
$_PUBLICO					=	true;
$_FORCAR_EXECUCAO			=	false;
$_LOCAL_ID					=	"robo";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if($_GET['force']) $_FORCAR_EXECUCAO = true;

function data_gmt($data){
	return date('r', strtotime($data));
}

function priority($path,$data){
	$path_aux = explode('/',$path);
	$data_aux = explode(' ',$data);
	$data_aux2 = explode('-',$data_aux[0]);
	
	$fator1 = date('Y') - $data_aux2[0];
	$fator2 = count($path_aux);
	
	if($fator1 > 9)$fator1 = 9;
	if($fator2 > 8)$fator2 = 8;
	
	return '0.'.((90 - $fator2*10) + (10 - $fator1));
}

function data_w3c($data){
	$data_aux = explode(' ',$data);

	return $data_aux[0].'T'.$data_aux[1].'+00:00';
}

function permisao_conteudo($id,$pai = false){
	global $_LISTA;
	
	if(!$pai){
		$permisao = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
				'no_robots',
			))
			,
			'conteudo_permissao',
			"WHERE id_conteudo='".$id."'".
			" AND tipo='C'"
		);
	} else {	
		$permisao2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
				'no_robots',
			))
			,
			'conteudo_permissao',
			"WHERE id_conteudo='".$id."'".
			" AND tipo='L'"
		);
	}
	
	if($permisao){
		return $permisao[0]['no_robots'];
	} else if($permisao2){
		return $permisao2[0]['no_robots'];
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_pai',
			))
			,
			'conteudo',
			"WHERE id_conteudo='".$id."'"
		);
		
		if($conteudo)
			return permisao_conteudo($conteudo[0]['id_conteudo_pai'],true);
		else
			return false;
	}
}

function sitemap(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_HTML;
	global $_FORCAR_EXECUCAO;
	global $_PROJETO;
	
	$changefreq = 'monthly';
	$num_conteudo_por_sitemap = 25000;
	
	if($_PROJETO['robo_sitemap']){
		$changefreq = $_PROJETO['robo_sitemap']['changefreq'];
		$num_conteudo_por_sitemap = $_PROJETO['robo_sitemap']['num_conteudo_por_sitemap'];
	}
	
	$sitemap_index_path = $sitemap_path = $_SYSTEM['PATH'] . 'files' . $_SYSTEM['SEPARADOR'] . 'sitemaps' . $_SYSTEM['SEPARADOR'];
	$url_path = "http://" . (preg_match('/www./i', $_SYSTEM['DOMINIO']) > 0 ? "" : "www.") . $_SYSTEM['DOMINIO'] . "/" . $_SYSTEM['ROOT'];
	
	$sitemap_index = 
"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
	<!-- sitemap_index < --><sitemap>
		<loc>#url#</loc>
		<lastmod>#data#</lastmod>
	</sitemap><!-- sitemap_index > -->
</sitemapindex>";

	$sitemap = 
'<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<!-- created with Ageone CMS robots -->
	<url>
		<loc>'.$url_path.'</loc>
		<changefreq>monthly</changefreq>
		<priority>1.00</priority>
	</url>
	<!-- sitemap < --><url>
		<loc>#url#</loc>
		<changefreq>'.$changefreq.'</changefreq>
		<priority>#pri#</priority>
	</url>
	<!-- sitemap > -->
</urlset>';

	$cel_nome = 'sitemap'; $cel[$cel_nome] = modelo_tag_val($sitemap,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$sitemap = modelo_tag_in($sitemap,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'sitemap_index'; $cel[$cel_nome] = modelo_tag_val($sitemap_index,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$sitemap_index = modelo_tag_in($sitemap_index,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	banco_conectar();
	
	
	$conteu_ultimo_id = banco_select
	(
		"id_conteudo"
		,
		"conteudo",
		"ORDER BY id_conteudo DESC LIMIT 1"
	);
	
	$num_total_rows = $conteu_ultimo_id[0]['id_conteudo'];
	
	if($num_total_rows > 0){
		if($num_total_rows < $num_conteudo_por_sitemap){
			$continue = true;
			$cont = 1;
			
			$limit_inferior = $limit_superior - ($num_conteudo_por_sitemap-1);
			if($limit_inferior < 0)$limit_inferior=1;
			
			$criar_sitemap = banco_select
			(
				"id_conteudo"
				,
				"conteudo",
				($_FORCAR_EXECUCAO?"":"WHERE sitemap IS NOT NULL")
			);
			
			if($criar_sitemap){
				$conteudo = banco_select
				(
					"id_conteudo"
					.",identificador"
					.",caminho_raiz"
					.",data"
					,
					"conteudo",
					"WHERE status!='D'"
				);
				banco_update
				(
					"sitemap=NULL",
					"conteudo",
					""
				);
				
				$sitemap_arquivo = $sitemap;
				
				if($conteudo)
				foreach($conteudo as $valor){
					if($valor['identificador'] && !permisao_conteudo($valor['id_conteudo'])){
						$cel_nome = 'sitemap';
						$cel_aux = $cel[$cel_nome];
						
						$url = $url_path.$valor['caminho_raiz'].$valor['identificador'] . "/";
						$pri = priority($valor['caminho_raiz'],$valor['data']);
						
						$cel_aux = modelo_var_troca($cel_aux,"#url#",$url);
						$cel_aux = modelo_var_troca($cel_aux,"#pri#",$pri);
						
						$sitemap_arquivo = modelo_var_in($sitemap_arquivo,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
				}
				
				$sitemap_arquivo = modelo_var_troca($sitemap_arquivo,'<!-- sitemap -->','');
				
				file_put_contents($sitemap_path.'sitemap.xml',$sitemap_arquivo);
			}
		
			$cont++;
		} else {
			$continue = true;
			$cont = 1;
			while($continue){
				if($cont*$num_conteudo_por_sitemap  > $num_total_rows){
					$continue = false;
					$limit_superior = $num_total_rows+1;
				} else {
					$limit_superior = $cont*$num_conteudo_por_sitemap;
				}
				
				$limit_inferior = $limit_superior - ($num_conteudo_por_sitemap-1);
				if($limit_inferior < 0)$limit_inferior=1;
				
				$criar_sitemap = banco_select
				(
					"id_conteudo"
					,
					"conteudo",
					"WHERE id_conteudo >='".$limit_inferior."'"
					." AND id_conteudo <='".$limit_superior."'"
					.($_FORCAR_EXECUCAO?"":" AND sitemap IS NOT NULL")
				);
				
				if($criar_sitemap){
					$conteudo = banco_select
					(
						"id_conteudo"
						.",identificador"
						.",caminho_raiz"
						.",data"
						,
						"conteudo",
						"WHERE id_conteudo >='".$limit_inferior."'"
						." AND id_conteudo <='".$limit_superior."'"
						." AND status!='D'"
					);
					banco_update
					(
						"sitemap=NULL",
						"conteudo",
						"WHERE id_conteudo >='".$limit_inferior."'"
						." AND id_conteudo <='".$limit_superior."'"
					);
					
					$sitemap_arquivo = $sitemap;
					
					if($conteudo)
					foreach($conteudo as $valor){
						if($valor['identificador'] && !permisao_conteudo($valor['id_conteudo'])){
							$cel_nome = 'sitemap';
							$cel_aux = $cel[$cel_nome];
							
							$url = $url_path.$valor['caminho_raiz'].$valor['identificador'] . "/";
							$data = data_w3c($valor['data']);
							
							$cel_aux = modelo_var_troca($cel_aux,"#url#",$url);
							$cel_aux = modelo_var_troca($cel_aux,"#data#",$data);
							
							$sitemap_arquivo = modelo_var_in($sitemap_arquivo,'<!-- '.$cel_nome.' -->',$cel_aux);
						}
					}
					
					$sitemap_arquivo = modelo_var_troca($sitemap_arquivo,'<!-- sitemap -->','');
					
					file_put_contents($sitemap_path.'sitemap-'.$cont.'.xml',$sitemap_arquivo);
					chmod($sitemap_path.'sitemap-'.$cont.'.xml', 0777);
				}
			
				$cont++;
			}
			
			$sitemap_index_arquivo = $sitemap_index;
			
			if(is_dir($sitemap_path)){ // verifica se realmente é uma pasta
				if($handle = opendir($sitemap_path)){
					while(false !== ($file = readdir($handle))){ // varre cada um dos arquivos da pasta
						if(($file == ".") or ($file == "..") or ($file == "sitemap.xml")){
							continue;
						}
						
						$cel_nome = 'sitemap_index';
						$cel_aux = $cel[$cel_nome];
						
						$url = $url_path.'files/sitemaps/'.$file;
						$data = data_w3c(date("Y-m-d H:i:s",filemtime($sitemap_path.$file)));
						
						$cel_aux = modelo_var_troca($cel_aux,"#url#",$url);
						$cel_aux = modelo_var_troca($cel_aux,"#data#",$data);
						
						$sitemap_index_arquivo = modelo_var_in($sitemap_index_arquivo,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
					
					$sitemap_index_arquivo = modelo_var_troca($sitemap_index_arquivo,'<!-- '.$cel_nome.' -->','');
					
					file_put_contents($sitemap_index_path.'sitemap.xml',$sitemap_index_arquivo);
				}

				// fecha a pasta aberta
				closedir($handle);
			}
		}
	}
	
	banco_fechar_conexao();
}

function rss(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_HTML;
	global $_FORCAR_EXECUCAO;
	
	$num_conteudo_rss = 100;
	$limite_texto = 500;
	
	$rss_path = $_SYSTEM['PATH'] . 'files' . $_SYSTEM['SEPARADOR'] . 'rss' . $_SYSTEM['SEPARADOR'];
	$rss_index_path = $_SYSTEM['PATH'];
	$url_path = "http://" . $_SYSTEM['DOMINIO'] . "/" . $_SYSTEM['ROOT'];
	
	$rss = 
"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<rss version=\"2.0\">
<channel>
	<title>".$_HTML['TITULO_ANTES']."</title>
	<link>".$url_path."</link>
	<description>".$_HTML['SUB_TITULO']."</description>
	<!-- rss < --><item>
		<title>#titulo#</title>
		<link>#url#</link>
		<description>#texto#</description>
		<pubDate>#data#</pubDate>
	</item>
	<!-- rss > -->
</channel>
</rss>";

	$cel_nome = 'rss'; $cel[$cel_nome] = modelo_tag_val($rss,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$rss = modelo_tag_in($rss,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	banco_conectar();
	
	
	$conteudo_ultimo_id = banco_select
	(
		"id_conteudo"
		,
		"conteudo",
		"ORDER BY id_conteudo DESC LIMIT 1"
	);
	
	$num_total_rows = $conteudo_ultimo_id[0]['id_conteudo'];
	
	if($num_total_rows > 0){
		$criar_rss = banco_select
		(
			"id_conteudo"
			,
			"conteudo",
			($_FORCAR_EXECUCAO?"":"WHERE rss IS NOT NULL")
		);
		
		if($criar_rss){
			$conteudo = banco_select
			(
				"id_conteudo"
				.",identificador"
				.",caminho_raiz"
				.",data"
				.",titulo"
				.",texto"
				,
				"conteudo",
				"WHERE status!='D'"
				." ORDER BY data DESC LIMIT 0,".$num_conteudo_rss
			);
			$conteudos_up_today = banco_select
			(
				"id_conteudo"
				,
				"conteudo",
				"WHERE status!='D'"
				." AND data > NOW()"
				." ORDER BY data DESC LIMIT 0,".$num_conteudo_rss
			);
			banco_update
			(
				"rss=NULL",
				"conteudo",
				""
			);
			
			$rss_arquivo = $rss;
			
			if($conteudo)
			foreach($conteudo as $valor){
				if($valor['identificador'] && !permisao_conteudo($valor['id_conteudo'])){
					$cel_nome = 'rss';
					$cel_aux = $cel[$cel_nome];
					
					$valor['titulo'] = preg_replace('/&/i', 'E', $valor['titulo']);
					
					$url = $url_path.$valor['caminho_raiz'].$valor['identificador'] . "/";
					$data = data_gmt($valor['data']);
					$titulo = $valor['titulo'];
					$texto = htmlspecialchars(limitar_texto_html($valor['texto'],$limite_texto));
					
					$cel_aux = modelo_var_troca($cel_aux,"#url#",$url);
					$cel_aux = modelo_var_troca($cel_aux,"#data#",$data);
					$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$titulo);
					$cel_aux = modelo_var_troca($cel_aux,"#texto#",$texto);
					
					$rss_arquivo = modelo_var_in($rss_arquivo,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
			}
			
			$rss_arquivo = modelo_var_troca($rss_arquivo,'<!-- '.$cel_nome.' -->','');
			
			file_put_contents($rss_path.'rss.xml',$rss_arquivo);
			chmod($rss_path.'rss.xml', 0777);
		}
	}
	
	banco_fechar_conexao();
}

function rss_redes(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_HTML;
	global $_FORCAR_EXECUCAO;
	
	$num_conteudo_rss = 100;
	$limite_texto = 500;
	
	$rss_path = $_SYSTEM['PATH'] . 'files' . $_SYSTEM['SEPARADOR'] . 'rss' . $_SYSTEM['SEPARADOR'];
	$rss_index_path = $_SYSTEM['PATH'];
	$url_path = "http://" . $_SYSTEM['DOMINIO'] . "/" . $_SYSTEM['ROOT'];
	
	$rss = 
"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<rss version=\"2.0\">
<channel>
	<title>".$_HTML['TITULO_ANTES']."</title>
	<link>".$url_path."</link>
	<description>".$_HTML['SUB_TITULO']."</description>
	<!-- rss < --><item>
		<title>#titulo#</title>
		<link>#url#</link>
		<description>#texto#</description>
		<pubDate>#data#</pubDate>
	</item>
	<!-- rss > -->
</channel>
</rss>";

	$cel_nome = 'rss'; $cel[$cel_nome] = modelo_tag_val($rss,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$rss = modelo_tag_in($rss,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	banco_conectar();
	
	
	$conteudo_ultimo_id = banco_select
	(
		"id_conteudo"
		,
		"conteudo",
		"ORDER BY id_conteudo DESC LIMIT 1"
	);
	
	$num_total_rows = $conteudo_ultimo_id[0]['id_conteudo'];
	
	if($num_total_rows > 0){
		$criar_rss = banco_select
		(
			"id_conteudo"
			,
			"conteudo",
			($_FORCAR_EXECUCAO?"":"WHERE rss_redes IS NOT NULL")
		);
		
		if($criar_rss){
			$conteudo = banco_select
			(
				"id_conteudo"
				.",identificador"
				.",caminho_raiz"
				.",data"
				.",titulo"
				.",texto"
				.",redes_titulo"
				.",redes_subtitulo"
				,
				"conteudo",
				"WHERE status!='D'"
				." ORDER BY data DESC LIMIT 0,".$num_conteudo_rss
			);
			$conteudos_up_today = banco_select
			(
				"id_conteudo"
				,
				"conteudo",
				"WHERE status!='D'"
				." AND data > NOW()"
				." ORDER BY data DESC LIMIT 0,".$num_conteudo_rss
			);
			banco_update
			(
				"rss_redes=NULL",
				"conteudo",
				""
			);
			
			$rss_arquivo = $rss;
			
			if($conteudo)
			foreach($conteudo as $valor){
				if($valor['identificador'] && !permisao_conteudo($valor['id_conteudo'])){
					$cel_nome = 'rss';
					$cel_aux = $cel[$cel_nome];
				
					$valor['titulo'] = preg_replace('/&/i', 'E', $valor['titulo']);
					
					$url = $url_path.$valor['caminho_raiz'].$valor['identificador'] . "/";
					$data = data_gmt($valor['data']);
					$titulo = ($valor['redes_titulo']?$valor['redes_titulo']:$valor['titulo']);
					$texto = htmlspecialchars(limitar_texto_html(($valor['redes_subtitulo']?$valor['redes_subtitulo']:$valor['texto']),$limite_texto));
					
					$cel_aux = modelo_var_troca($cel_aux,"#url#",$url);
					$cel_aux = modelo_var_troca($cel_aux,"#data#",$data);
					$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$titulo);
					$cel_aux = modelo_var_troca($cel_aux,"#texto#",$texto);
					
					$rss_arquivo = modelo_var_in($rss_arquivo,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
			}
			
			$rss_arquivo = modelo_var_troca($rss_arquivo,'<!-- '.$cel_nome.' -->','');
			
			file_put_contents($rss_path.'rss_redes.xml',$rss_arquivo);
			chmod($rss_path.'rss_redes.xml', 0777);
		}
	}
	
	banco_fechar_conexao();
}

function main(){
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	global $_FORCAR_EXECUCAO;
	
	$_SYSTEM['ROBO_INT'];
	$_SYSTEM['ROBO_ULT'];
	
	$processos_int = explode(',',$_SYSTEM['ROBO_INT']); // Processo intervalo
	$processos_ult = explode(',',$_SYSTEM['ROBO_ULT']); // Processo última execução
	
	$count = 0;
	$time = time();
	
	if($processos_int)
	foreach($processos_int as $processo_int){
		$flag = false;
		if(preg_match('/M/i', $processo_int) > 0){
			$processo_int = preg_replace('/M/i', '', $processo_int);
			$flag['minuto'] = true;
			$processo_int = (int)$processo_int;
		} else if(preg_match('/H/i', $processo_int) > 0){
			$processo_int = preg_replace('/H/i', '', $processo_int);
			$flag['hora'] = true;
			$processo_int = (int)$processo_int * 60;
		} else if(preg_match('/D/i', $processo_int) > 0){
			$processo_int = preg_replace('/D/i', '', $processo_int);
			$flag['dia'] = true;
			$processo_int = (int)$processo_int * 1440;
		}
		
		if(
			$flag['minuto'] ||
			$flag['hora'] ||
			$flag['dia']
		){
			$processo_int = $processo_int * 60;
			
			if(!$processos_ult[$count] || $processos_ult[$count] + $processo_int <= $time){
				$processos_ult[$count] = $time;
				$executa[$count] = true;
			}
		}
		
		if($processos_ult[$count]){
			$processos_ult_novo .= $processos_ult[$count];
		} else {
			$processos_ult_novo .= '0';
		}
		
		$processos_ult_novo .= ($count < count($processos_int) - 1 ? ',' : '');
		
		if($_FORCAR_EXECUCAO)$executa[$count] = true;
		
		$count++;
	}
	
	if($executa)
	foreach($executa as $proc_key => $valor){
		switch($proc_key){
			case 0: sitemap(); echo "sitemap();"; break;
			case 1: rss(); echo "rss();"; break;
			case 2: rss_redes(); echo "rss_redes();"; break;
		}
	}
	
	banco_conectar();
	banco_update
	(
		"valor='".$processos_ult_novo."'",
		"variavel_global",
		"WHERE variavel='ROBO_ULT'"
	);
	banco_fechar_conexao();
}

main();

?>