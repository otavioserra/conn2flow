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

function htmlParam($param,$valor){
	return ' '.$param.'="'.$valor.'"';
}

function htmlOpen(){
	return "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
}

function htmlClose(){
	return "</html>\n";
}

function htmlHeadOpen(){
	return "<head>\n";
}

function htmlHeadClose(){
	return "</head>\n";
}

function htmlTitle($title){
	return "<title>".$title."</title>\n";
}

function htmlCSS($local){
	return "<link href=\"".$local."\" rel=\"stylesheet\" type=\"text/css\">\n";
}

function htmlMeta($keywords,$description){
	return "<meta name=\"keywords\" content=\"".$keywords."\" />\n<meta name=\"description\" content=\"".$description."\" />\n";
}

function htmlBodyOpen(){
	return "<body>\n";
}

function htmlBodyClose(){
	return "</body>\n";
}

function htmlBR(){
	return "<br />\n";
}

function htmlP($value,$id,$class,$extra){
	if($id)				$id = " id=\"".$id."\"";
	if($class)			$class = " class=\"".$class."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<p".$id.$class.$extra.">".$value."</p>\n";
}

function htmlB($value,$id,$class,$extra){
	if($id)				$id = " id=\"".$id."\"";
	if($class)			$class = " class=\"".$class."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<b".$id.$class.$extra.">".$value."</b>\n";
}

function htmlH1($value,$id,$class,$extra){
	if($id)				$id = " id=\"".$id."\"";
	if($class)			$class = " class=\"".$class."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<h1".$id.$class.$extra.">".$value."</h1>\n";
}

function htmlH2($value,$id,$class,$extra){
	if($id)				$id = " id=\"".$id."\"";
	if($class)			$class = " class=\"".$class."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<h2".$id.$class.$extra.">".$value."</h2>\n";
}

function htmlH3($value,$id,$class,$extra){
	if($id)				$id = " id=\"".$id."\"";
	if($class)			$class = " class=\"".$class."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<h3".$id.$class.$extra.">".$value."</h3>\n";
}

function htmlA($url,$value,$target,$id,$extra){
	if($url)			$url = " href=\"".$url."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($target)			$target = " target=\"".$target."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<a".$url.$id.$target.$extra.">".$value."</a>\n";
}

function htmlImage($url,$width,$height,$border,$id,$extra){
	if($url)			$url = " src=\"".$url."\"";
	if($width)			$width	= " width=\"".$width."\"";
	if($height)			$height	= " height=\"".$height."\"";
	if($id)				$id = " id=\"".$id."\"";
	if($border)			$border = " border=\"".$border."\"";
	if($extra)			$extra = " ".$extra;
	
	if($border == "0")			$border = " border=\"".$border."\"";
	
	return "<img".$url.$width.$height.$id.$border.$extra." />\n";
}

function htmlFlash($local,$width,$height){	
	$version = "7,0,19,0";
	
	if($width)$width = " width=\"".$width."\"";
	if($height)$height = " height=\"".$height."\"";
	
	$flash = 
	"
	<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=".$version."\"".$width.$height.">
    <param name=\"movie\" value=\"".$local."\" />
    <param name=\"quality\" value=\"high\" />
    <embed src=\"".$local."\" quality=\"high\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\"".$width.$height."></embed>
    </object>
	";
	
	return $flash;
}

function htmlList($class,$itens){
	$saida = "<ul class=\"".$class."\">\n";
	for($i=0;$i<count($itens);$i++)
		$saida .= "<li>".$itens[$i]."</li>\n";
	$saida .= "</ul>\n";
	return $saida;
}

function htmlListOpen($class){
	return "<ul class=\"".$class."\">\n";
}

function htmlListClose(){
	return "</ul>\n";
}

function htmlListIten($iten){
	return "<li>".$iten."</li>\n";
}

function htmlSpan($value,$id,$class,$extra){
	if($id)				$id = " id=\"".$id."\"";
	if($class)			$class = " class=\"".$class."\"";
	if($extra)			$extra = " ".$extra;
	
	return "<span".$id.$class.$extra.">".$value."</span>\n";
}

function html($params){
	$attr = Array();
	$tag = 'tag';
	$val = 'val';
	$tabs = 0;
	$nl1 = true;
	$nl2 = true;
	$tab = "	";
	
	if($params)foreach($params as $var => $valor)$$var = $valor;
	
	if($attr)
	foreach($attr as $param => $valor){
		$attrs .= htmlParam($param,$valor);
	}
	
	if($tabs)
	for($i=0;$i < $tabs;$i++){
		$tabT .= $tab;
	}
	
	switch($tag){
		case 'input':
		case 'img':
			$tag_unica = true;
	}
	
	return ($nl1?"\n".$tabT:"")
	."<".$tag.$attrs.($tag_unica?" />":">"
		.$val
		.($nl2?"\n".$tabT:"")
	."</".$tag.">");
}

?>