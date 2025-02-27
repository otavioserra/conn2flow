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