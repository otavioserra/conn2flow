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

function componentes_select($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes/componentes.html');
	$pagina = modelo_tag_val($modelo,'<!-- select < -->','<!-- select > -->');
	$option_cel = modelo_tag_val($modelo,'<!-- option < -->','<!-- option > -->');
	
	$pagina = modelo_var_troca($pagina,"#cont-class-extra#",$cont_class_extra);
	$pagina = modelo_var_troca($pagina,"#cont-params-extra#",$cont_params_extra.($cont_callback ? ' data-callback="'.$cont_callback.'"' : '' ));
	$pagina = modelo_var_troca($pagina,"#holder-class-extra#",$holder_class_extra);
	$pagina = modelo_var_troca($pagina,"#holder-params-extra#",$holder_params_extra);
	$pagina = modelo_var_troca($pagina,"#holder-2-class-extra#",$holder_2_class_extra);
	$pagina = modelo_var_troca($pagina,"#holder-2-params-extra#",$holder_2_params_extra);
	$pagina = modelo_var_troca($pagina,"#select-class-extra#",$select_class_extra);
	$pagina = modelo_var_troca($pagina,"#select-params-extra#",$select_params_extra);
	$pagina = modelo_var_troca($pagina,"#options-class-extra#",$options_class_extra);
	$pagina = modelo_var_troca($pagina,"#options-params-extra#",$options_params_extra);
	$pagina = modelo_var_troca($pagina,"#selected_text#",($selected_text ? $selected_text : $unselected_text));
	$pagina = modelo_var_troca($pagina,"#selected_value#",($selected_value ? $selected_value : $unselected_value));
	$pagina = modelo_var_troca($pagina,"#unselected_text#",$unselected_text);
	$pagina = modelo_var_troca($pagina,"#unselected_value#",$unselected_value);
	$pagina = modelo_var_troca($pagina,"#input_name#",$input_name);
	$pagina = modelo_var_troca($pagina,"#input_value#",($selected_value ? $selected_value : $unselected_value));
	$pagina = modelo_var_troca($pagina,"#input-params-extra#",$input_params_extra);
	
	if($options)
	foreach($options as $opt){
		$cel_aux = $option_cel;
		
		$cel_aux = modelo_var_troca($cel_aux,"#option-class-extra#",$opt['options-class-extra']);
		$cel_aux = modelo_var_troca($cel_aux,"#option-params-extra#",$opt['options-params-extra']);
		$cel_aux = modelo_var_troca($cel_aux,"#value#",$opt['value']);
		$cel_aux = modelo_var_troca($cel_aux,"#text#",$opt['text']);
		
		$options_ready .= $cel_aux;
	}
	
	$pagina = modelo_var_troca($pagina,"#options#",$options_ready);
	
	return $pagina;
}

function componentes_datepicker($params = false){
	global $_SYSTEM;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes/componentes.html');
	$pagina = modelo_tag_val($modelo,'<!-- datepicker < -->','<!-- datepicker > -->');
	
	$pagina = modelo_var_troca($pagina,"#cont-class-extra#",$cont_class_extra);
	$pagina = modelo_var_troca($pagina,"#cont-params-extra#",$cont_params_extra);
	$pagina = modelo_var_troca($pagina,"#holder-class-extra#",$holder_class_extra);
	$pagina = modelo_var_troca($pagina,"#holder-params-extra#",$holder_params_extra);
	$pagina = modelo_var_troca($pagina,"#calendar-class-extra#",$calendar_class_extra);
	$pagina = modelo_var_troca($pagina,"#calendar-params-extra#",$calendar_params_extra);
	$pagina = modelo_var_troca($pagina,"#datepicker_value#",$datepicker_value);
	$pagina = modelo_var_troca($pagina,"#input_name#",$input_name);
	$pagina = modelo_var_troca($pagina,"#input_value#",$datepicker_value);
	$pagina = modelo_var_troca($pagina,"#input_calendar_value#",$datepicker_value);
	$pagina = modelo_var_troca($pagina,"#input-params-extra#",$input_params_extra);
	$pagina = modelo_var_troca($pagina,"#input-class-extra#",$input_class_extra);
	
	return $pagina;
}

?>