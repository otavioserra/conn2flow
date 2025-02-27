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
// Funчѕes de Iniciaчуo do sistema

$_PAGINA_LOCAL				=	"unsubscribe";
$_PERMISSAO					=	false;

include("config.php");

// Funчѕes de assistъncia

function main(){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	
	if($_REQUEST['cod'] == crypt($_REQUEST['email'],$_REQUEST['cod'])){
		$_SESSION[$_SYSTEM['ID']."alerta"] = 5;
		
		banco_conectar();
		banco_update
		(
			"status='D',".
			"opt_out=NOW()",
			$_BANCO_PREFIXO."emails",
			"WHERE id_emails='".$_REQUEST['id']."' AND email='".$_REQUEST['email']."'"
		);
		banco_fechar_conexao();
	}
	
	header('Location: index.php');
}

main();

?>