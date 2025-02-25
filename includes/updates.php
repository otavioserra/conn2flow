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

$_VERSAO_MODULO_INCLUDE				=	'1.0.0';

function updates_array_to_xml($array, &$xml_user_info){
	if($array)
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml_user_info->addChild("$key");
                updates_array_to_xml($value, $subnode);
            }else{
                $subnode = $xml_user_info->addChild("item$key");
                updates_array_to_xml($value, $subnode);
            }
        } else {
            $xml_user_info->addChild("$key",htmlspecialchars("$value"));
        }
    }
}

function updates_format_xml($array){
	global $_LOCAL_ID;
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'."\n".'<'.$_LOCAL_ID.' />');
	updates_array_to_xml($array,$xml);
	
	$dom = dom_import_simplexml($xml)->ownerDocument;
	$dom->formatOutput = true;
	return $dom->saveXML();
}

function updates_exit($arr){
	header('Content-Type: text/xml; charset=UTF-8;');
	echo updates_format_xml($arr);
	exit;
}

function updates_autenticar(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_USUARIO;
	
	if($_REQUEST['pass'] && $_REQUEST['pub_id']){
		$pass = $_REQUEST['pass'];
		$pub_id = $_REQUEST['pub_id'];
	
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuario',
			))
			,
			"usuario",
			"WHERE pub_id='".$pub_id."'"
		);
		
		if($resultado){
			$id_usuario = $resultado[0]['id_usuario'];
			$_USUARIO = $resultado[0];
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'token',
					'token_verificacao',
				))
				,
				"host",
				"WHERE id_usuario='".$id_usuario."'"
				." AND atual IS TRUE"
			);
			
			$token_local = $resultado[0]['token'];
			$token_verificacao = $resultado[0]['token_verificacao'];
			
			$token_verificacao_teste = md5($pass . $token_local);
			
			if($token_verificacao_teste == $token_verificacao){
				return;
			} else {
				updates_exit(Array(
					'error' => 'Token inválido',
				));
			}
		} else {
			updates_exit(Array(
				'error' => 'Pub_id não conhecido',
			));
		}
	} else {
		updates_exit(Array(
			'error' => 'User and/or Pass not defined',
		));
	}
}

function updates_config(){
	global $_BANCO;
	
	$_BANCO['UTF8']							=		true;
}

updates_config();

?>