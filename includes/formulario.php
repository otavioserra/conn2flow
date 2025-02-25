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

// ================================ B2 Make =================================================

function formulario_xor_this($string) {
	global $_SYSTEM;
	
	if(!$_SESSION[$_SYSTEM['ID']."permissao"]){
		return false;
	}
	
	$user = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$key = ($user['senha'].session_id());
	$outText = '';

	for($i=0;$i<strlen($string);){
		for($j=0;($j<strlen($key) && $i<strlen($string));$j++,$i++){
			$outText .= $string[$i] ^ $key[$j];
		}
	}
	
	return $outText;
}

function formulario_autenticacao_encrypt(){
	global $_SYSTEM;
	
	if(!$_SESSION[$_SYSTEM['ID']."permissao"]){
		return false;
	}
	
	$user = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$usuario = urlencode(formulario_xor_this(crypt($user['usuario'])));
	$senha_sessao = urlencode(formulario_xor_this(crypt($user['senha_sessao'])));
	
	return Array(
		'usuario' => $usuario,
		'senha' => $senha_sessao,
	);
}

function formulario_autenticacao_decrypt($usuario,$senha){
	global $_SYSTEM;
	
	if(!$_SESSION[$_SYSTEM['ID']."permissao"]){
		return false;
	}
	
	$user = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$usuario = formulario_xor_this(urldecode($usuario));
	$senha = formulario_xor_this(urldecode($senha));
	
	if(
		crypt($user['usuario'],$usuario) == $usuario && 
		crypt($user['senha_sessao'],$senha) == $senha
	){
		return true;
	} else {
		return false;
	}
}

function formulario_preparar($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$auth_pass = formulario_autenticacao_encrypt();
	
	$formulario_inputs_vars = '
	<input type=hidden name="_formulario-local" id="_formulario-local" value="'.$local.'">
	<input type=hidden name="_formulario-auth" id="_formulario-auth" value="'.$auth_pass['usuario'].'">
	<input type=hidden name="_formulario-key" id="_formulario-key" value="'.$auth_pass['senha'].'">
';
	
	if($sem_conteiner){
		$pagina = $formulario_inputs_vars;
	} else {
		$pagina = preg_replace('/<\/form>/i', $formulario_inputs_vars.'</form>', $pagina);
	}
	
	return $pagina;
}

function formulario_verificar_autenticidade(){
	return formulario_autenticacao_decrypt($_REQUEST['_formulario-auth'],$_REQUEST['_formulario-key']);
}

// =================================================================================

function formOpen($url,$name,$id,$extra){
	if($url)			$url = " action=\"".$url."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($name)			$name = " name=\"".$name."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<form".$url.$id.$name.$extra." method=\"post\">\n";
}

function formOpenFile($url,$name,$id,$extra){
	if($url)			$url = " action=\"".$url."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($name)			$name = " name=\"".$name."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<form".$url.$id.$name.$extra." method=\"post\" enctype=\"multipart/form-data\">\n<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"50000000\">\n";
}

function formClose(){
	return "</form>\n";
}

function formInputText($name,$id,$value,$size,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($size)			$size = " size=\"".$size."\"";
	if($value)			$value = " value=\"".$value."\"";
	if($extra)			$extra = " ".$extra;

	return "<input type=\"text\"".$name.$id.$size.$value.$extra." />\n";
}

function formInputHidden($name,$id,$value,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($value)			$value = " value=\"".$value."\"";
	if($extra)			$extra = " ".$extra;

	return "<input type=\"hidden\"".$name.$id.$value.$extra." />\n";
}

function formInputPass($name,$id,$value,$size,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($size)			$size = " size=\"".$size."\"";
	if($value)			$value = " value=\"".$value."\"";
	if($extra)			$extra = " ".$extra;

	return "<input type=\"password\"".$name.$id.$size.$value.$extra." />\n";
}

function formInputButton($name,$id,$value,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($value)			$value = " value=\"".$value."\"";
	if($extra)			$extra = " ".$extra;

	return "<input type=\"submit\"".$name.$id.$value.$extra." />\n";
}

function formInputButton2($name,$id,$value,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($value)			$value = " value=\"".$value."\"";
	if($extra)			$extra = " ".$extra;

	return "<input type=\"button\"".$name.$id.$value.$extra." />\n";
}

function formInputFile($name,$id,$value,$size,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($size)			$size = " size=\"".$size."\"";
	if($value)			$value = " value=\"".$value."\"";
	if($extra)			$extra = " ".$extra;

	return "<input type=\"file\"".$name.$id.$size.$value.$extra." />\n";
}

function formInputCheckbox($name,$id,$value,$checked,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($value)			$value = " value=\"".$value."\"";
	if($checked)		$checked = " checked=\"checked\"";
	if($extra)			$extra = " ".$extra;

	return "<input type=\"checkbox\"".$name.$id.$value.$checked.$extra."/>\n";
}

function formSelect($name,$id,$options,$optionsValue,$optionSelected,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($extra)			$extra = " ".$extra;
	
	$saida = "<select".$name.$id.$extra.">\n";
	
	for($i=0;$i<count($options);$i++)
		if($optionSelected == $i)
			$saida .= "<option value=\"".$optionsValue[$i]."\" selected=\"selected\">".$options[$i]."</option>\n";
		else
			$saida .= "<option value=\"".$optionsValue[$i]."\">".$options[$i]."</option>\n";
			
	$saida .= "</select>\n";
	
	return $saida;
}

function formTextarea($name,$id,$value,$rows,$cols,$extra){
	if($name)			$name = " name=\"".$name."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($rows)			$rows = " rows=\"".$rows."\"";
	if($cols)			$cols = " cols=\"".$cols."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<textarea".$name.$id.$rows.$cols.$extra.">".$value."</textarea>\n";
}

?>