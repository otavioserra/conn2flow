<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

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

$_VERSAO_MODULO				=	'0.0.1';
$_LOCAL_ID					=	"install.host.bot";
$_PERMISSAO					=	false;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../../";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function install_host_whm(){
	global $_SYSTEM;
	
	$id_usuario = token_validar('install.host');
	
	if($id_usuario){
		ignore_user_abort(1); // run script in background 
		set_time_limit(60); // run script forever 
		
		banco_update
		(
			"installing='1'",
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS TRUE"
		);
		
		sleep(10);
		
		banco_update
		(
			"installing=NULL",
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS TRUE"
		);
		banco_update
		(
			"installed='1'",
			"host",
			"WHERE id_usuario='".$id_usuario."'"
			." AND atual IS TRUE"
		);
	}
}

function start(){
	global $_SYSTEM;
	
	install_host_whm();
}

start();

?>