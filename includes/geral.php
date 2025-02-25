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

$_VERSAO_MODULO_INCLUDE				=	'1.2.3';

function smartstripslashes($str){
	$cd1 = substr_count($str, "\"");
	$cd2 = substr_count($str, "\\\"");
	$cs1 = substr_count($str, "'");
	$cs2 = substr_count($str, "\\'");
	$tmp = strtr($str, array("\\\"" => "", "\\'" => ""));
	$cb1 = substr_count($tmp, "\\");
	$cb2 = substr_count($tmp, "\\\\");
	
	if ($cd1 == $cd2 && $cs1 == $cs2 && $cb1 == 2 * $cb2) {
		return strtr($str, array("\\\"" => "\"", "\\'" => "'", "\\\\" => "\\"));
	}
	
	return $str;
}

function campos_antes_guardar($campos){
	global $_SYSTEM;
	
	$_SESSION[$_SYSTEM['ID']."campos_antes"] = $campos;
}

function campos_antes_recuperar(){
	global $_SYSTEM;
	
	$campos = $_SESSION[$_SYSTEM['ID']."campos_antes"];
	$_SESSION[$_SYSTEM['ID']."campos_antes"] = false;
	
	return $campos;
}

function data_mes_nome($mes,$maiusculas = false,$primeira_maiuscula = false){
	$meses = Array(
		1 => 'janeiro',
		2 => 'fevereiro',
		3 => 'março',
		4 => 'abril',
		5 => 'maio',
		6 => 'junho',
		7 => 'julho',
		8 => 'agosto',
		9 => 'setembro',
		10 => 'outubro',
		11 => 'novembro',
		12 => 'dezembro',
	);
	
	$mes_saida = $meses[(int)$mes];
	
	if($primeira_maiuscula){
		$pl = $mes_saida[0];
		$mes_saida[0] = strtoupper($pl);
	}
	
	if($maiusculas){
		$mes_saida = strtoupper($mes_saida);
	}

	return $mes_saida;
}

function data_hora_array($data_hora_padrao_datetime){
	$data_hora = explode(" ",$data_hora_padrao_datetime);
	$data_aux = explode("-",$data_hora[0]);
	$hora_aux = explode(":",$data_hora[1]);
	
	$data_hora_array = Array(
		'dia' => $data_aux[2],
		'mes' => $data_aux[1],
		'ano' => $data_aux[0],
		'hora' => $hora_aux[0],
		'min' => $hora_aux[1],
		'seg' => $hora_aux[2],
	);
	
	return $data_hora_array;
}

function data_hora_from_datetime_to_text($data_hora, $format = false){
	global $_SYSTEM;
	
	if($data_hora){
		$data_hora = explode(" ",$data_hora);
		$data_aux = explode("-",$data_hora[0]);
		
		if($format){
			$hora_aux = explode(":",$data_hora[1]);
			$format = preg_replace('/D/', $data_aux[2], $format);
			$format = preg_replace('/ME/', $data_aux[1], $format);
			$format = preg_replace('/A/', $data_aux[0], $format);
			$format = preg_replace('/H/', $hora_aux[0], $format);
			$format = preg_replace('/MI/', $hora_aux[1], $format);
			$format = preg_replace('/S/', $hora_aux[2], $format);
			
			return $format;
		} else if($_SYSTEM['DATA_FORMAT_PADRAO']){
			$format = $_SYSTEM['DATA_FORMAT_PADRAO'];
			$hora_aux = explode(":",$data_hora[1]);
			$format = preg_replace('/D/', $data_aux[2], $format);
			$format = preg_replace('/ME/', $data_aux[1], $format);
			$format = preg_replace('/A/', $data_aux[0], $format);
			$format = preg_replace('/H/', $hora_aux[0], $format);
			$format = preg_replace('/MI/', $hora_aux[1], $format);
			$format = preg_replace('/S/', $hora_aux[2], $format);
			
			return $format;
		} else {
			$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
			$hora = $data_hora[1];
			
			return $data . " " . $hora;
		}
	} else {
		return "";
	}
}

function data_padrao_date($data){
	$dataArray = explode("/",$data);
	$data = $dataArray[2]."-".$dataArray[1]."-".$dataArray[0];
	
	return $data;
}

function data_hora_padrao_datetime($dataHora){
	$dataHoraArray = explode(" ",$dataHora);
	$dataArray = explode("/",$dataHoraArray[0]);
	$datetime = $dataArray[2]."-".$dataArray[1]."-".$dataArray[0]." ".$dataHoraArray[1].":00";
	
	return $datetime;
}

function data_from_datetime_to_text($data_hora){
	$data_hora = explode(" ",$data_hora);
	$data_aux = explode("-",$data_hora[0]);
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	
	return $data;
}

function data_from_date_to_text($data){
	$data_aux = explode("-",$data);
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	
	return $data;
}

function hora_from_time_to_text($hora,$com_segundos = false){
	if(!$com_segundos){
		$hora_aux = explode(":",$hora);
		$hora = $hora_aux[0] . ":" . $hora_aux[1];
	}
	
	return $hora;
}

function mime_types($extensao){
	$mime_types = array("323" => "text/h323",
	"acx" => "application/internet-property-stream",
	"ai" => "application/postscript",
	"aif" => "audio/x-aiff",
	"aifc" => "audio/x-aiff",
	"aiff" => "audio/x-aiff",
	"asf" => "video/x-ms-asf",
	"asr" => "video/x-ms-asf",
	"asx" => "video/x-ms-asf",
	"au" => "audio/basic",
	"avi" => "video/x-msvideo",
	"axs" => "application/olescript",
	"bas" => "text/plain",
	"bcpio" => "application/x-bcpio",
	"bin" => "application/octet-stream",
	"bmp" => "image/bmp",
	"c" => "text/plain",
	"cat" => "application/vnd.ms-pkiseccat",
	"cdf" => "application/x-cdf",
	"cer" => "application/x-x509-ca-cert",
	"class" => "application/octet-stream",
	"clp" => "application/x-msclip",
	"cmx" => "image/x-cmx",
	"cod" => "image/cis-cod",
	"cpio" => "application/x-cpio",
	"crd" => "application/x-mscardfile",
	"crl" => "application/pkix-crl",
	"crt" => "application/x-x509-ca-cert",
	"csh" => "application/x-csh",
	"css" => "text/css",
	"dcr" => "application/x-director",
	"der" => "application/x-x509-ca-cert",
	"dir" => "application/x-director",
	"dll" => "application/x-msdownload",
	"dms" => "application/octet-stream",
	"doc" => "application/msword",
	"dot" => "application/msword",
	"dvi" => "application/x-dvi",
	"dxr" => "application/x-director",
	"eps" => "application/postscript",
	"etx" => "text/x-setext",
	"evy" => "application/envoy",
	"exe" => "application/octet-stream",
	"fif" => "application/fractals",
	"flr" => "x-world/x-vrml",
	"gif" => "image/gif",
	"gtar" => "application/x-gtar",
	"gz" => "application/x-gzip",
	"h" => "text/plain",
	"hdf" => "application/x-hdf",
	"hlp" => "application/winhlp",
	"hqx" => "application/mac-binhex40",
	"hta" => "application/hta",
	"htc" => "text/x-component",
	"htm" => "text/html",
	"html" => "text/html",
	"htt" => "text/webviewhtml",
	"ico" => "image/x-icon",
	"ief" => "image/ief",
	"iii" => "application/x-iphone",
	"ins" => "application/x-internet-signup",
	"isp" => "application/x-internet-signup",
	"jfif" => "image/pipeg",
	"jpe" => "image/jpeg",
	"jpeg" => "image/jpeg",
	"jpg" => "image/jpeg",
	"js" => "application/x-javascript",
	"latex" => "application/x-latex",
	"lha" => "application/octet-stream",
	"lsf" => "video/x-la-asf",
	"lsx" => "video/x-la-asf",
	"lzh" => "application/octet-stream",
	"m13" => "application/x-msmediaview",
	"m14" => "application/x-msmediaview",
	"m3u" => "audio/x-mpegurl",
	"man" => "application/x-troff-man",
	"mdb" => "application/x-msaccess",
	"me" => "application/x-troff-me",
	"mht" => "message/rfc822",
	"mhtml" => "message/rfc822",
	"mid" => "audio/mid",
	"mny" => "application/x-msmoney",
	"mov" => "video/quicktime",
	"movie" => "video/x-sgi-movie",
	"mp2" => "video/mpeg",
	"mp3" => "audio/mpeg",
	"mp3_2" => "audio/mp3",
	"mpa" => "video/mpeg",
	"mpe" => "video/mpeg",
	"mpeg" => "video/mpeg",
	"mpg" => "video/mpeg",
	"mpp" => "application/vnd.ms-project",
	"mpv2" => "video/mpeg",
	"ms" => "application/x-troff-ms",
	"mvb" => "application/x-msmediaview",
	"nws" => "message/rfc822",
	"oda" => "application/oda",
	"p10" => "application/pkcs10",
	"p12" => "application/x-pkcs12",
	"p7b" => "application/x-pkcs7-certificates",
	"p7c" => "application/x-pkcs7-mime",
	"p7m" => "application/x-pkcs7-mime",
	"p7r" => "application/x-pkcs7-certreqresp",
	"p7s" => "application/x-pkcs7-signature",
	"pbm" => "image/x-portable-bitmap",
	"pdf" => "application/pdf",
	"pfx" => "application/x-pkcs12",
	"pgm" => "image/x-portable-graymap",
	"pjpeg" => "image/pjpeg",
	"pko" => "application/ynd.ms-pkipko",
	"pma" => "application/x-perfmon",
	"pmc" => "application/x-perfmon",
	"pml" => "application/x-perfmon",
	"pmr" => "application/x-perfmon",
	"pmw" => "application/x-perfmon",
	"png" => "image/png",
	"pnm" => "image/x-portable-anymap",
	"pot" => "application/vnd.ms-powerpoint",
	"ppm" => "image/x-portable-pixmap",
	"pps" => "application/vnd.ms-powerpoint",
	"ppt" => "application/vnd.ms-powerpoint",
	"prf" => "application/pics-rules",
	"ps" => "application/postscript",
	"pub" => "application/x-mspublisher",
	"qt" => "video/quicktime",
	"ra" => "audio/x-pn-realaudio",
	"ram" => "audio/x-pn-realaudio",
	"ras" => "image/x-cmu-raster",
	"rgb" => "image/x-rgb",
	"rmi" => "audio/mid",
	"roff" => "application/x-troff",
	"rtf" => "application/rtf",
	"rtx" => "text/richtext",
	"scd" => "application/x-msschedule",
	"sct" => "text/scriptlet",
	"setpay" => "application/set-payment-initiation",
	"setreg" => "application/set-registration-initiation",
	"sh" => "application/x-sh",
	"shar" => "application/x-shar",
	"sit" => "application/x-stuffit",
	"snd" => "audio/basic",
	"spc" => "application/x-pkcs7-certificates",
	"spl" => "application/futuresplash",
	"src" => "application/x-wais-source",
	"sst" => "application/vnd.ms-pkicertstore",
	"stl" => "application/vnd.ms-pkistl",
	"stm" => "text/html",
	"svg" => "image/svg+xml",
	"sv4cpio" => "application/x-sv4cpio",
	"sv4crc" => "application/x-sv4crc",
	"t" => "application/x-troff",
	"tar" => "application/x-tar",
	"tcl" => "application/x-tcl",
	"tex" => "application/x-tex",
	"texi" => "application/x-texinfo",
	"texinfo" => "application/x-texinfo",
	"tgz" => "application/x-compressed",
	"tif" => "image/tiff",
	"tiff" => "image/tiff",
	"tr" => "application/x-troff",
	"trm" => "application/x-msterminal",
	"tsv" => "text/tab-separated-values",
	"txt" => "text/plain",
	"uls" => "text/iuls",
	"ustar" => "application/x-ustar",
	"vcf" => "text/x-vcard",
	"vrml" => "x-world/x-vrml",
	"wav" => "audio/x-wav",
	"wcm" => "application/vnd.ms-works",
	"wdb" => "application/vnd.ms-works",
	"wks" => "application/vnd.ms-works",
	"wmf" => "application/x-msmetafile",
	"wps" => "application/vnd.ms-works",
	"wri" => "application/x-mswrite",
	"wrl" => "x-world/x-vrml",
	"wrz" => "x-world/x-vrml",
	"xaf" => "x-world/x-vrml",
	"xbm" => "image/x-xbitmap",
	"xla" => "application/vnd.ms-excel",
	"xlc" => "application/vnd.ms-excel",
	"xlm" => "application/vnd.ms-excel",
	"xls" => "application/vnd.ms-excel",
	"xlt" => "application/vnd.ms-excel",
	"xlw" => "application/vnd.ms-excel",
	"xml" => "text/xml",
	"xof" => "x-world/x-vrml",
	"xpm" => "image/x-xpixmap",
	"xwd" => "image/x-xwindowdump",
	"x-png" => "image/x-png",
	"z" => "application/x-compress",
	"zip" => "application/zip");
	
	if($mime_types[$extensao])
		return $mime_types[$extensao];
	else
		return "text/plain";
}

function data_hora_from_datetime($data_hora){
	$data_hora = explode(" ",$data_hora);
	$data_aux = explode("-",$data_hora[0]);
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	$hora = $data_hora[1];
	
	$retorno[0] = $data;
	$retorno[1] = $hora;
	
	return $retorno;
}

function enviar_mail($parametros){
	global $_SYSTEM;
	global $_ERRO;
	global $_EMAILS;
	global $_DEBUG;
	global $_DEBUG_GRAVAR_LOG;
	global $_HTML;
	
	if($_SYSTEM['EMAIL'] || $_SYSTEM['EMAIL_TESTE']){
		$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
	
		if(!$parametros['smtp_host'])$parametros['smtp_host'] = ($_SYSTEM['SMTP_FORCE_HOST']?$_SYSTEM['SMTP_FORCE_HOST']:'smtp.'.$dominio_sem_www);
		if(!$parametros['smtp_porta'])$parametros['smtp_porta'] = $_SYSTEM['MAILER_PORT'];
		if(!$parametros['smtp_usuario'])$parametros['smtp_usuario'] = $_SYSTEM['SMTP_USER'].'@'.($_SYSTEM['SMTP_FORCE_HOST_EMAIL']?$_SYSTEM['SMTP_FORCE_HOST_EMAIL']:$dominio_sem_www);
		if(!$parametros['smtp_senha'])$parametros['smtp_senha'] = $_SYSTEM['SMTP_PASS'];
		
		if(!$parametros["texto"] && !$parametros["html_sem_modelo"]){
			$mensagem = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'email.html');
			
			$mensagem = modelo_var_troca($mensagem,"#email#titulo#",$parametros["from_name"]);
			$mensagem = modelo_var_troca($mensagem,"#email#css#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$_SYSTEM['PADRAO_CSS']);
			$mensagem = modelo_var_troca($mensagem,"#email#body#",$parametros["mensagem"]);
			$mensagem = modelo_var_troca($mensagem,"#email#body_style#",$parametros["body_style"]);
		} else {
			$mensagem = $parametros["mensagem"];
		}
		
		#preparação para o envio do email
		$mail = new PHPMailer\PHPMailer\PHPMailer();
		
		//$mail->SetLanguage("br");
		$mail->CharSet = 'UTF-8';
		$mail->IsSMTP(true);
		if(!$parametros["texto"]){
			$mail->IsHTML(true);
			//$mail->AltBody = "Para ver a mensagem, por favor use um programa de emails compatível com HTML!";
		}
		
		#dados da conta
		$mail->Host = $parametros["smtp_host"];
		$mail->Port = $parametros["smtp_porta"];
		$mail->SMTPAuth = true;
		$mail->Username = $parametros["smtp_usuario"];
		$mail->Password = $parametros["smtp_senha"];

		#dados do disparo
		$mail->FromName = ($parametros["from_name"] ? $parametros["from_name"] : "Sistema ".$_HTML['TITULO']);
		$mail->From = ($parametros["from"] ? $parametros["from"] : $parametros["smtp_usuario"]);
		
		if($parametros["email"] && $parametros["email_name"])
			$mail->AddAddress($parametros["email"],$parametros["email_name"]);
		else
			$mail->AddAddress($parametros["email"]);
		
		if($parametros["cc"]){
			$ccs = $parametros["cc"];
			foreach($ccs as $cc){
				$mail->AddCC($cc['email']);
			}
		}
		
		if($parametros["bcc"]){
			$bccs = $parametros["bcc"];
			debug($bccs,false,true);
			foreach($bccs as $bcc){
				debug($bcc['email']);
				$mail->AddBCC($bcc['email']);
			}
		}
		
		$mail->msgHTML($mensagem);
		
		if($parametros["embedded_imgs"]){
			$embedded_imgs = $parametros["embedded_imgs"];
			
			foreach($embedded_imgs as $imgs){
				$filename = $imgs['src'];
				$cid = $imgs['cid'];
				$tmp_image = $imgs['tmp_image'];
				$name = ($imgs['name']?$imgs['name']:$imgs['src']);
				
				$mail->AddEmbeddedImage($filename, $cid, $name);
				
				if($tmp_image){
					$tmp_images[] = $tmp_image;
				}
			}
		}
		
		$mail->Subject = $parametros["subject"];
		
		if($_DEBUG || $_DEBUG_GRAVAR_LOG){
			$mail->SMTPDebug  = 2;
			
			if($_DEBUG_GRAVAR_LOG){
				gravar_log(print_r($parametros,true));
				$mail->Debugoutput = function($str, $level){ gravar_log("debug level $level; message: $str");};
			}
		}
		
		debug($parametros,true);
		
		if(!$mail->Send()){
			$_ERRO = 'Problema com o envio do e-mail.';
			$mail->ClearAddresses();
			
			if($tmp_images)
			foreach($tmp_images as $tmp){
				unlink($tmp);
			}
			
			return false;
		} else {
			$_ERRO = 'Enviado!';
			$mail->ClearAddresses();
			
			if($tmp_images)
			foreach($tmp_images as $tmp){
				unlink($tmp);
			}
			
			return true;
		}
	} else {
		$_ERRO = 'ENVIO DE E-MAIL DESABILITADO.';
		
		debug($parametros,true);
		
		if($_DEBUG)
			return true;
		else
			return false;
	}
}

function email_enviar($params = false){
	global $_SYSTEM;
	global $_HTML;
	
	if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
	
	$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);

	$parametros['from_name'] = $_HTML['TITULO'];
	$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($from_name) $parametros['from_name'] = $from_name;
	if($from) $parametros['from'] = $from;
	
	if(!$nao_inserir_assinatura)$mensagem .= $_SYSTEM['MAILER_ASSINATURA'];
	
	$parametros['email_name'] = $email_nome;
	$parametros['email'] = $email;
	$parametros['subject'] = $assunto;
	$parametros['mensagem'] = $mensagem;
	$parametros['embedded_imgs'] = $embedded_imgs;
	$parametros['html_sem_modelo'] = $html_sem_modelo;
	
	if($strip_tags){
		$parametros['email_name'] = strip_tags($parametros['email_name']);
		$parametros['email'] = strip_tags($parametros['email']);
		$parametros['subject'] = strip_tags($parametros['subject']);
		$parametros['mensagem'] = strip_tags($parametros['mensagem']);
	}
	
	if($parametros['enviar_mail'])enviar_mail($parametros);
}

function preparar_float_4_texto($float,$sem_descimal = false){
	$float_aux = explode('e',$float);
	
	if(count($float_aux) > 1){
		$_mais = explode('+',$float_aux[1]);
		$_menos = explode('-',$float_aux[1]);
		
		if(count($_mais) > 1){$pot = (int)$_mais[1];$flag['mais']=true;}
		if(count($_menos) > 1){$pot = (int)$_menos[1];$flag['menos']=true;}
		
		$mult = '1';
		for($i=0;$i<$pot;$i++){
			$mult .= '0';
		}
		
		if($flag['mais'])
			$float = (string)((float)$float_aux[0] * (float)$mult);
		else
			$float = (string)((float)$float_aux[0] / (float)$mult);
			
		if($float < 0.01)$float = 0;
	}
	
	
	if($float < 0){
		$num_in = substr($float,1,strlen($float)-1);
		$menos = "-";
	} else
		$num_in = $float;
	
	$var = explode(".",$num_in);
	
	$num_1 = $var[0];
	$num_2 = $var[1];
	
	if($num_1)
	{
		if($num_1 != "0")
		{
			if(strlen($num_1) % 3 == 0)
				for($i=0;$i<strlen($num_1);$i++)
					if(($i+1) % 3 == 0 && $i != 0)
					{
						$sep = ".";
						
						if($i == strlen($num_1) -1)
							$sep = "";
						$num_1_final = $num_1_final . $num_1[$i] . $sep;
					}
					else 
						$num_1_final = $num_1_final . $num_1[$i];
				
			if(strlen($num_1) % 3 == 1)
				for($i=0;$i<strlen($num_1);$i++)
					if($i == 0 || $i % 3 == 0)
					{
						$sep = ".";
						
						if($i == strlen($num_1) -1)
							$sep = "";
						$num_1_final = $num_1_final . $num_1[$i] . $sep;
					}
					else 
						$num_1_final = $num_1_final . $num_1[$i];
			
			if(strlen($num_1) % 3 == 2)
				for($i=0;$i<strlen($num_1);$i++)
					if($i == 1 || ($i+2) % 3 == 0)
					{
						$sep = ".";
						
						if($i == strlen($num_1) -1)
							$sep = "";
						$num_1_final = $num_1_final . $num_1[$i] . $sep;
					}
					else 
						$num_1_final = $num_1_final . $num_1[$i];
						
		}
		else
			$num_1_final = "0";
	}
	else
		$num_1_final = "0";
				
	if($num_2){
		if(strlen($num_2) == 1)
			$num_2_final = $num_2 . "0";
		else if(strlen($num_2) > 1)
			$num_2_final = $num_2[0].$num_2[1];
	}
	else
		$num_2_final = "00";
	
	return ($menos . $num_1_final . ($sem_descimal? '' : "," . $num_2_final));
}

function preparar_texto_4_float($texto){
	// Formato 00.000,00
	
	$num_1_2 = explode(",",$texto);
	
	if($num_1_2){
		$num_aux = explode(".",$num_1_2[0]);
		
		if($num_aux){
			for($i=0;$i<count($num_aux);$i++){
				$num_1 .= $num_aux[$i];
			}
		} else
			$num_1 = $num_1_2[0];
		
		$num_2 = $num_1_2[1];
		
		return ($num_1 . "." . $num_2);
	} else
		return $texto;
}

function createthumb($name,$filename,$new_w,$new_h,$moldura = false){
	$system = explode(".",$name);
	if(preg_match("/jpg|jpeg/",$system[count($system)-1])){$src_img = imagecreatefromjpeg($name);}
	if(preg_match("/png/",$system[count($system)-1])){$src_img = imagecreatefrompng($name);}
	
	$old_x = imageSX($src_img);
	$old_y = imageSY($src_img);
	
	if($old_x > $old_y){
		$thumb_w = $new_w;
		$thumb_h = $old_y*($new_w/$old_x);
	}
	if($old_x < $old_y){
		$thumb_w = $old_x*($new_h/$old_y);
		$thumb_h = $new_h;
	}
	if($old_x == $old_y){
		if($new_w <= $new_h){
			$thumb_w = $thumb_h = $new_w;
		} else {
			$thumb_w = $thumb_h = $new_h;
		}
	}
	$dst_img = ImageCreateTrueColor($thumb_w,$thumb_h);
	imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
	
	if($moldura){
		$dst_img2 = ImageCreateTrueColor($new_w,$new_h);
		imagecopymerge($dst_img2,$dst_img,($new_w - $thumb_w)/2,($new_h - $thumb_h)/2,0,0,$thumb_w,$thumb_h,100);
		$dst_img = $dst_img2;
	}
	
	if(preg_match("/png/",$system[1])){
		imagepng($dst_img,$filename); 
	} else {
		imagejpeg($dst_img,$filename); 
	}
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
}

function senha_gerar($limite,$simples = false){
	$CaracteresAceitos = 'abcdefghijklmnopqrstuvxywzABCDEFGHIJKLMNOPQRSTUVXYWZ_';
	if(!$simples)$CaracteresAceitos .= '%#&';
	$CaracteresAceitos2 = '0123456789';
	
	$max = strlen($CaracteresAceitos)-1;
	$max2 = strlen($CaracteresAceitos2)-1;

	$password = null;

	for($i=0; $i < $limite; $i++) {
		if(mt_rand(0, 2) == 2)
			$password .= $CaracteresAceitos2[mt_rand(0, $max2)];
		else
			$password .= $CaracteresAceitos[mt_rand(0, $max)];
	}

	return $password;
}

function debug($mens = false,$print_r = false){
	global $_DEBUG;
	global $_ERRO;
	global $_MENS;
	
	if($_DEBUG)if($_ERRO){	echo "ERRO: " . $_ERRO . "<br />"; }
	if($_DEBUG)if($_MENS)if($print_r){	print_r($_MENS); }else{	echo $_MENS . "<br />"; }
	if($_DEBUG)if($mens)if($print_r){	print_r($mens); }else{	echo $mens . "<br />"; }
	
	$_ERRO = false;$_MENS = false;
}

function redirect($local = false,$sem_root = false){
	global $_SYSTEM;
	global $_AJAX_PAGE;
	global $_VARIAVEIS_JS;
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PROJETO;
	global $_REDIRECT_PAGE;
	global $_ALERTA;
	
	if($local){
		$local = ($sem_root?'':'/' . $_SYSTEM['ROOT']) . ($local == '/' ?'':$local);
	} else {
		switch($_SESSION[$_SYSTEM['ID']."permissao_id"]){
			//case '2': $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = $_CAMINHO_RELATIVO_RAIZ.$_HTML['ADMIN']; break;
			default: $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $_HTML['ADMIN'];
		}
		
		if($_PROJETO['redirecionar']){
			$permissao_id = $_SESSION[$_SYSTEM['ID']."permissao_id"];
			
			if($_PROJETO['redirecionar']['permissao_id']){
				$dados = $_PROJETO['redirecionar']['permissao_id'];
				foreach($dados as $dado){
					if($dado['id'] == $permissao_id) $_SESSION[$_SYSTEM['ID']."redirecionar_local"] = '/'.$_SYSTEM['ROOT'] . $dado['local'];
				}
			}	
		}
		
		$local = $_SESSION[$_SYSTEM['ID']."redirecionar_local"];
	}
	
	if($_AJAX_PAGE){
		if($_REDIRECT_PAGE){
			$_VARIAVEIS_JS['redirecionar'] = $local;
			$_REDIRECT_PAGE = false;
		} else {
			$_VARIAVEIS_JS['redirecionar_ajax'] = $local;
		}
		echo pagina();
		exit(0);
	} else {
		if($_ALERTA)$_SESSION[$_SYSTEM['ID']."alerta"] = $_ALERTA;
		header("Location: ".$local);
		exit(0);
	}
	
}

function retirar_tags_layout($texto){
	$tags_abertas = Array(
		'table' => Array('','','',''),
		'tbody' => Array('','','',''),
		'tr' => Array('','','',''),
		'td' => Array('','','',''),
		'div' => Array('','','',''),
		'span' => Array('','','',''),
		'a' => Array('','','',''),
		'p' => Array('',' ','',' '),
		'b' => Array('','','',''),
		'strong' => Array('','','',''),
		'body' => Array('','','',''),
		'font' => Array('','','',''),
		'iframe' => Array('','','',''),
	);
	
	$tags_fechadas = Array(
		'br' => Array(' ',' ',' '),
		'hr' => Array(' ',' ',' '),
		'img' => Array('','',''),
		'button' => Array('','',''),
	);
	
	$pattern[] = '/<p>&nbsp;<\/p>/i'; $replacement[] = '';
	$pattern[] = '/<p[[:print:]]{0,}>&nbsp;<\/p>/i'; $replacement[] = '';
	
	foreach($tags_fechadas as $tag => $mudanca){
		$pattern[] = '/<'.$tag.'>/i'; $replacement[] = $mudanca[0];
		$pattern[] = '/<'.$tag.'\/>/i'; $replacement[] = $mudanca[1];
		$pattern[] = '/<'.$tag.'[[:print:]]{0,}\/>/i'; $replacement[] = $mudanca[2];
	}
	
	foreach($tags_abertas as $tag => $mudanca){
		$pattern[] = '/<'.$tag.'>/i'; $replacement[] = $mudanca[0];
		$pattern[] = '/<\/'.$tag.'>/i'; $replacement[] = $mudanca[1];
		$pattern[] = '/<'.$tag.'[[:print:]]{0,}>/i'; $replacement[] = $mudanca[2];
		$pattern[] = '/<\/'.$tag.'[[:print:]]{0,}>/i'; $replacement[] = $mudanca[3];
	}
	
	$pattern[] = '/#[[:print:]]{0,}#/i'; $replacement[] = '';
	
	$texto = preg_replace($pattern, $replacement, $texto);
	
	return $texto;
}

function resize_image($image_source, $image_new, $new_w, $new_h, $recortar = false, $force_resize = false, $moldurar = false){
	global $_RESIZE_IMAGE_Y_ZERO;
	
	if(!$new_w && !$new_h){
		return;
	}
	
	//Check if GD extension is loaded
	if (!extension_loaded('gd') && !extension_loaded('gd2')) {
		trigger_error("GD is not loaded", E_USER_WARNING);
		return false;
	}

	//Get Image size info
	$imgInfo = getimagesize($image_source);
	
	switch ($imgInfo[2]) {
		case 1: $img = imagecreatefromgif($image_source); break;
		case 2: $img = imagecreatefromjpeg($image_source);  break;
		case 3: $img = imagecreatefrompng($image_source); break;
		default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
	}

	//If image dimension is smaller, do not resize
	$old_w = $imgInfo[0];
	$old_h = $imgInfo[1];
	$moldura_w = $old_w;
	$moldura_h = $old_h;
	
	if($new_w && !$new_h){
		$new_h = floor(($new_w * $old_h) / $old_w);
	}
	
	if(!$new_w && $new_h){
		$new_w = floor(($new_h * $old_w) / $old_h);
	}
	
	if($force_resize){
		$thumb_w = $new_w;
		$thumb_h = $new_h;
	} else if($old_w < $new_w && $old_h < $new_h){
		$thumb_w = $old_w;
		$thumb_h = $old_h;
	} else {
		if(!$recortar){
			if($moldurar){
				$res_o = $old_w / $old_h;
				$res_n = $new_w / $new_h;
				
				if($res_n > $res_o){
					$moldura_w = $old_w;
					$moldura_h = ($new_h*$old_w)/$new_w;
				} else if($res_n < $res_o){
					$moldura_w = ($new_w*$old_h)/$new_h;
					$moldura_h = $old_h;
				} else {
					$moldura_w = $old_w;
					$moldura_h = $old_h;
				}
				
				$moldura_w = round($moldura_w);
				$moldura_h = round($moldura_h);
			} else {
				$thumb_w = $new_w;
				$thumb_h = $new_h;
				
				if($new_w > $new_h){
					$thumb_w = ($new_h*$old_w)/$old_h;
				} else {
					$thumb_h = ($new_w*$old_h)/$old_w;
				}
				
				if($thumb_w > $new_w){
					$thumb_w = $new_w;
					$thumb_h = ($new_w*$old_h)/$old_w;
				}
				
				if($thumb_h > $new_h){
					$thumb_w = ($new_h*$old_w)/$old_h;
					$thumb_h = $new_h;
				}
			}
		}
	}
	
	if($moldurar || $recortar){
		$nWidth = $new_w;
		$nHeight = $new_h;
	} else {
		$nWidth = round($thumb_w);
		$nHeight = round($thumb_h);
	}
	
	$newImg = imagecreatetruecolor($nWidth, $nHeight);
	if($moldurar)$newImgMol = imagecreatetruecolor($moldura_w, $moldura_h);
	
	/* Check if this image is PNG or GIF, then set if Transparent*/  
	if(($imgInfo[2] == 1) OR ($imgInfo[2]==3)){
		imagealphablending($newImg, false);
		imagesavealpha($newImg,true);
		$transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
		imagefilledrectangle($newImg, 0, 0, $nWidth, $nHeight, $transparent);
		if($moldurar){
			imagealphablending($newImgMol, false);
			imagesavealpha($newImgMol,true);
			$transparent = imagecolorallocatealpha($newImgMol, 255, 255, 255, 127);
			imagefilledrectangle($newImgMol, 0, 0, $moldura_w, $moldura_h, $transparent);
		}
	}
	
	if($moldurar){
		$cortar_x = round(($old_w - $moldura_w)/2);
		$cortar_y = round(($old_h - $moldura_h)/2);
		
		if($_RESIZE_IMAGE_Y_ZERO){
			$cortar_y = 0;
		}
		
		imagecopyresampled($img,$img,0,0,$cortar_x,$cortar_y,$old_w,$old_h,$old_w,$old_h);
		imagecopy($newImgMol,$img,0,0,0,0,$moldura_w,$moldura_h);
		imagecopyresampled($newImg, $img, 0, 0, 0, 0, $nWidth, $nHeight, $moldura_w, $moldura_h);
	} else if($recortar){
		$cortar_x = round(($old_w - $new_w)/2);
		$cortar_y = round(($old_h - $new_h)/2);
		imagecopyresampled($img,$img,0,0,$cortar_x,$cortar_y,$old_w,$old_h,$old_w,$old_h);
		imagecopy($newImg,$img,0,0,0,0,$nWidth,$nHeight);
	} else {
		imagecopyresampled($newImg, $img, 0, 0, 0, 0, $nWidth, $nHeight, $old_w, $old_h);
	}
	
	if(!file_exists($image_new))$file_nao_existe = true;
	
	if(preg_match('/.gif|.GIF/i', $image_new) > 0){$imgInfo2 = 1;}
	if(preg_match('/.jpg|.JPG/i', $image_new) > 0){$imgInfo2 = 2;}
	if(preg_match('/.jpeg|.JPEG/i', $image_new) > 0){$imgInfo2 = 2;}
	if(preg_match('/.png|.PNG/i', $image_new) > 0){$imgInfo2 = 3;}

	switch ($imgInfo2) {
		case 1: imagegif($newImg,$image_new); break;
		case 2: imagejpeg($newImg,$image_new,90);  break;
		case 3: imagepng($newImg,$image_new,9); break;
		default:  trigger_error('Failed resize image!', E_USER_WARNING);  break;
	}
	
	if($file_nao_existe)chmod($image_new , 0777);
}

function filtrar_image($image_source, $image_new, $image_filtro, $arg1 = 0, $arg2 = 0, $arg3 = 0, $arg4 = 0){
	//Check if GD extension is loaded
	if (!extension_loaded('gd') && !extension_loaded('gd2')) {
		trigger_error("GD is not loaded", E_USER_WARNING);
		return false;
	}

	//Get Image size info
	$imgInfo = getimagesize($image_source);
	switch ($imgInfo[2]) {
		case 1: $img = imagecreatefromgif($image_source); break;
		case 2: $img = imagecreatefromjpeg($image_source);  break;
		case 3: $img = imagecreatefrompng($image_source); break;
		default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
	}

	switch($image_filtro){
		case IMG_FILTER_NEGATE: 
		case IMG_FILTER_GRAYSCALE: 
		case IMG_FILTER_EDGEDETECT: 
		case IMG_FILTER_EMBOSS: 
		case IMG_FILTER_GAUSSIAN_BLUR: 
		case IMG_FILTER_SELECTIVE_BLUR: 
		case IMG_FILTER_MEAN_REMOVAL: 
			imagefilter($img,$image_filtro);
		break;
		case IMG_FILTER_BRIGHTNESS: 
		case IMG_FILTER_CONTRAST: 
		case IMG_FILTER_SMOOTH: 
			imagefilter($img,$image_filtro,$arg1);
		break;
		case IMG_FILTER_PIXELATE: 
			imagefilter($img,$image_filtro,$arg1,$arg2);
		break;
		case IMG_FILTER_COLORIZE: 
			imagefilter($img,$image_filtro,$arg1,$arg2,$arg3,$arg4);
		break;
		
	}
	
	if(!file_exists($image_new))$file_nao_existe = true;
	
	//Generate the file, and rename it to $image_new
	switch ($imgInfo[2]) {
		case 1: imagegif($img,$image_new); break;
		case 2: imagejpeg($img,$image_new,80);  break;
		case 3: imagepng($img,$image_new,9); break;
		default:  trigger_error('Failed resize image!', E_USER_WARNING);  break;
	}
	
	if($file_nao_existe)chmod($image_new , 0777);
	imagedestroy($img);
}

function image_hover_image($image_back, $image_front){
	global $_RESIZE_IMAGE_Y_ZERO;
	
	//Check if GD extension is loaded
	if (!extension_loaded('gd') && !extension_loaded('gd2')) {
		trigger_error("GD is not loaded", E_USER_WARNING);
		return false;
	}

	//Get Image size info
	$imgInfo = getimagesize($image_back);
	switch ($imgInfo[2]) {
		case 1: $img1 = imagecreatefromgif($image_back); break;
		case 2: $img1 = imagecreatefromjpeg($image_back);  break;
		case 3: $img1 = imagecreatefrompng($image_back); break;
		default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
	}

	//Get Image size info
	$imgInfo2 = getimagesize($image_front);
	switch ($imgInfo2[2]) {
		case 1: $img2 = imagecreatefromgif($image_front); break;
		case 2: $img2 = imagecreatefromjpeg($image_front);  break;
		case 3: $img2 = imagecreatefrompng($image_front); break;
		default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
	}
	
	$x = $imgInfo[0]/2 - $imgInfo2[0]/2;
	$y = $imgInfo[1]/2 - $imgInfo2[1]/2;
	
	imagecopyresampled($img1, $img2, $x, $y, 0, 0, $imgInfo2[0], $imgInfo2[1], $imgInfo2[0], $imgInfo2[1]);
	
	if(!file_exists($image_back))$file_nao_existe = true;
	
	//Generate the file, and rename it to $image_back
	switch ($imgInfo[2]) {
		case 1: imagegif($img1,$image_back); break;
		case 2: imagejpeg($img1,$image_back,80);  break;
		case 3: imagepng($img1,$image_back,9); break;
		default:  trigger_error('Failed resize image!', E_USER_WARNING);  break;
	}
	
	if($file_nao_existe)chmod($image_back , 0777);
}

function image_mask($image_source, $image_new, $image_mask, $posicionar_mask = false, $pos_hor = 'direita', $pos_ver = 'baixo'){
	$imgInfo = getimagesize($image_source);
	switch ($imgInfo[2]) {
		case 1: $img = imagecreatefromgif($image_source); break;
		case 2: $img = imagecreatefromjpeg($image_source);  break;
		case 3: $img = imagecreatefrompng($image_source); break;
		default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
	}
	
	// Find base image size 
	$swidth = imagesx($img); 
	$sheight = imagesy($img); 
	
	// Turn on alpha blending 
	imagealphablending($img, true); 
	
	// Create overlay image 
	$image_mask = imagecreatefrompng($image_mask); 
	imagealphablending($image_mask, true); 
	
	// Get the size of overlay 
	$owidth = imagesx($image_mask); 
	$oheight = imagesy($image_mask);

	if($posicionar_mask){
		// Position

		switch($pos_hor){
			case 'esquerda': $pos_x = 0; break;
			case 'direita': $pos_x = $swidth - $owidth; break;
			case 'centro': $pos_x = floor($swidth/2 - $owidth/2); break;
			default: $pos_x = $pos_hor; break;
		}
		  
		switch($pos_ver){
			case 'topo': $pos_y = 0; break;
			case 'baixo': $pos_y = $sheight - $oheight; break;
			case 'meio': $pos_y = floor($sheight/2 - $oheight/2); break;
			default: $pos_y = $pos_ver; break;
		}
		  
		// Overlay watermark 
		imagecopy($img, $image_mask, $pos_x, $pos_y, 0, 0, $owidth, $oheight);
	} else {
		$out = imagecreatetruecolor($swidth, $sheight);
		imagecopyresampled($out, $img, 0, 0, 0, 0, $swidth, $sheight, $swidth, $sheight);
		imagecopyresampled($out, $image_mask, 0, 0, 0, 0, $swidth, $sheight, $owidth, $oheight);
		$img = $out;
	}
	
	if(preg_match('/.gif|.GIF/i', $image_new) > 0){$imgInfo2 = 1;}
	if(preg_match('/.jpg|.JPG/i', $image_new) > 0){$imgInfo2 = 2;}
	if(preg_match('/.png|.PNG/i', $image_new) > 0){$imgInfo2 = 3;}

	switch ($imgInfo2) {
		case 1: imagegif($img,$image_new); break;
		case 2: imagejpeg($img,$image_new,80);  break;
		case 3: imagesavealpha($img, true); imagepng($img,$image_new,9); break;
		default:  trigger_error('Failed resize image!', E_USER_WARNING);  break;
	}
}

function cropImage($nw, $nh, $source, $stype, $dest) {
    $size = getimagesize($source);
    $w = $size[0];
    $h = $size[1];
 
    switch($stype) {
        case 'gif':
        $simg = imagecreatefromgif($source);
        break;
        case 'jpg':
        $simg = imagecreatefromjpeg($source);
        break;
        case 'png':
        $simg = imagecreatefrompng($source);
        break;
    }
 
    $dimg = imagecreatetruecolor($nw, $nh);
 
    $wm = $w/$nw;
    $hm = $h/$nh;
 
    $h_height = $nh/2;
    $w_height = $nw/2;
 
    if($w> $h) {
 
        $adjusted_width = $w / $hm;
        $half_width = $adjusted_width / 2;
        $int_width = $half_width - $w_height;
 
        imagecopyresampled($dimg,$simg,-$int_width,0,0,0,$adjusted_width,$nh,$w,$h);
 
    } elseif(($w <$h) || ($w == $h)) {
 
        $adjusted_height = $h / $wm;
        $half_height = $adjusted_height / 2;
        $int_height = $half_height - $h_height;
 
        imagecopyresampled($dimg,$simg,0,-$int_height,0,0,$nw,$adjusted_height,$w,$h);
 
    } else {
        imagecopyresampled($dimg,$simg,0,0,0,0,$nw,$nh,$w,$h);
    }
 
    imagejpeg($dimg,$dest,100);
}

function parametros(){
	global $_CONEXAO_BANCO;
	global $_PARAMS;
	global $_PARAMS_META;
	global $_LOCAL_ID;
	
	if(!$_CONEXAO_BANCO)$connect = true;
	if($connect)banco_conectar();
	
	$resultados = banco_select_name
	(
		banco_campos_virgulas(Array(
			'grupo',
			'param',
			'valor',
			'tipo',
			'descricao',
		))
		,
		"parametros",
		"WHERE local='".$_LOCAL_ID."'"
	);
	
	if($resultados)
	foreach($resultados as $resultado){
		if($resultado['grupo']){
			$params[$resultado['grupo']][$resultado['param']] = $resultado['valor'];
			
			$params_meta[$resultado['grupo']][$resultado['param']] = Array(
				'valor' => $resultado['valor'],
				'tipo' => $resultado['tipo'],
				'descricao' => $resultado['descricao'],
			);
		} else {
			$params[$resultado['param']] = $resultado['valor'];
			
			$params_meta[$resultado['param']] = Array(
				'valor' => $resultado['valor'],
				'tipo' => $resultado['tipo'],
				'descricao' => $resultado['descricao'],
			);
		}
	}
	
	$_PARAMS = $params;
	$_PARAMS_META = $params_meta;
	
	if($connect)banco_fechar_conexao();
}

function limite_texto($texto,$limite){
	if(strlen($texto)>$limite){
		return substr($texto,0,$limite) . '...';
	} else 
		return $texto;
}

function remover_tags_vazias($string){
	$replacement = '';
	$pattern = '/<[^\/>]*>([\s]?)*<\/[^>]*>/';
	
	if(preg_match($pattern, $string) > 0){
		return remover_tags_vazias(preg_replace($pattern, $replacement, $string));
	} else {
		return $string;
	}
}

function limitar_texto_html($texto, $limit, $tags_permitidas = '<p><a><i><ul><li><br><b><strong>', $break=" ", $pad="..."){
	$texto = preg_replace('/\n/', ' ',html_entity_decode(htmlspecialchars_decode($texto),ENT_COMPAT,'UTF-8'));// Retirar todos os carcteres especiais HTML, e transformar todos os \n em espaços
	$texto = preg_replace('/\s+/', ' ',strip_tags($texto,$tags_permitidas));// retirar todas as tags, exceto as permitidas bem como o espaço do começo e do fim da string
	
	if(strlen($texto) <= $limit) return ($texto);
	
	$texto = substr($texto, 0, $limit) . $pad . $break; // cortar a string no limite definido
	if(false !== ($breakpoint = strrpos($texto, $break))) {
		$texto = substr($texto, 0, $breakpoint);
	}
	
	$doc = new DOMDocument();
	@$doc->loadHTML($texto); // Verificar se há tags html não fechadas
	$texto = $doc->saveHTML();
	
	$texto = preg_replace('/\n/', ' ',$texto); // Transformar todos os \n em espaços
	$texto = trim(preg_replace('/\s+/', ' ',strip_tags($texto,$tags_permitidas))); // retirar todas as tags, exceto as permitidas bem como o espaço do começo e do fim da string
	$texto = remover_tags_vazias($texto); // Remover tags vazias 
	
	return $texto;
}

function root_sistema(){
	global $_CAMINHO_RELATIVO_RAIZ;
	
	$path_aux = explode('../',$_CAMINHO_RELATIVO_RAIZ);
	$root_aux = explode('/',$_SERVER["SCRIPT_NAME"]);
	
	$count = 0;
	$root = '';
	
	foreach($root_aux as $dir){
		$count++;
		if($dir){
			$root .= $dir . '/';
		}
		
		if($count + count($path_aux) == count($root_aux)){
			break;
		}
	}
	
	return $root;
}

function retirar_acentos($var,$retirar_espaco = true) {
	$var = strtolower($var);
	
	$unwanted_array = array(    
		'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
		'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ª'=>'a', 'ç'=>'c',
		'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'º'=>'o',
		'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
	);
	$var = strtr( $var, $unwanted_array );
	
	$var = preg_replace("/[\.\\\\,:;<>\/:\?\|_!`~@#\$%\^&\*\"'\+=]/","",$var);	
	$var = preg_replace("/[\(\)\{\}\[\]]/","-",$var);
	if($retirar_espaco)$var = str_replace(" ","-",$var);
	$var = preg_replace('/\-+/','-', $var);
	$var = preg_replace("/[^a-z^A-Z^0-9^-]/","",$var);	
	
	return $var;
}

function retirar_acentos_apenas($var) {
	$var = preg_replace("/[áàâãª]/","a",$var);	
	$var = preg_replace("/[ÁÀÂÃ]/","A",$var);	
	$var = preg_replace("/[éèê]/","e",$var);	
	$var = preg_replace("/[ÊÉÈ]/","E",$var);	
	$var = preg_replace("/[íìî]/","i",$var);	
	$var = preg_replace("/[ÍÌÎ]/","I",$var);	
	$var = preg_replace("/[óòôõº]/","o",$var);	
	$var = preg_replace("/[ÓÒÔÕº]/","O",$var);	
	$var = preg_replace("/[úùû]/","u",$var);	
	$var = preg_replace("/[ÚÙÛ]/","U",$var);
	$var = str_replace("ç","c",$var);
	$var = str_replace("Ç","C",$var);	
	
	return $var;
}

function imagem_info($path){
	global $_SYSTEM;
	
	if($path){
		if($_SYSTEM['SEPARADOR'] == "\\"){
			$path_aux = explode('/',$path);
			
			$path = '';
			if($path_aux){
				foreach($path_aux as $path_aux2){
					$count++;
					$path .= $path_aux2.(count($path_aux) > $count ? "\\" : "");
				}
			}
		}
	
		return getimagesize($_SYSTEM['PATH'].$path);
	} else {
		return Array();
	}
}

function valid_filename($str){
	$aux = explode('.',$str);
	
	if(count($aux) > 1){
		$ext = preg_replace('/[^0-9a-z?-????\`\~\!\@\#\$\%\^\*\(\)\; \,\.\'\/\_\-]/i', ' ',retirar_acentos($aux[count($aux)-1]));
		
		$filename = preg_replace('/'.$aux[count($aux)-1].'/i', '',$str);
		$filename = preg_replace('/[^0-9a-z?-????\`\~\!\@\#\$\%\^\*\(\)\; \,\.\'\/\_\-]/i', ' ',retirar_acentos($filename));
		
		$filename .= '.'.$ext;
	} else {
		$filename = preg_replace('/[^0-9a-z?-????\`\~\!\@\#\$\%\^\*\(\)\; \,\.\'\/\_\-]/i', ' ',retirar_acentos($str));
	}
    return $filename; 
}

function removeDirectory($dir) {
	if(is_dir($dir)){
		$abreDir = opendir($dir);

		while (false !== ($file = readdir($abreDir))) {
			if ($file==".." || $file ==".") continue;
			if (is_dir($cFile=($dir."/".$file))) removeDirectory($cFile);
			elseif (is_file($cFile)) unlink($cFile);
		}

		closedir($abreDir);
		rmdir($dir);
	}
}

function recursiveChmod($path, $filePerm=0644, $dirPerm=0755){
	// Check if the path exists
	if(!file_exists($path)){
		return(FALSE);
	}
	// See whether this is a file
	if(is_file($path)){
		// Chmod the file with our given filepermissions
		chmod($path, $filePerm);
	// If this is a directory...
	} elseif(is_dir($path)){
		// Then get an array of the contents
		$foldersAndFiles = scandir($path);
		// Remove "." and ".." from the list
		$entries = array_slice($foldersAndFiles, 2);
	// Parse every result...
	foreach($entries as $entry){
		// And call this function again recursively, with the same permissions
		recursiveChmod($path."/".$entry, $filePerm, $dirPerm);
	}
		// When we are done with the contents of the directory, we chmod the directory itself
		chmod($path, $dirPerm);
	}
	// Everything seemed to work out well, return TRUE
	return(TRUE);
}

function print_r_html($arr){
	?><pre><?php
	print_r($arr);
	?></pre><?php
}

function zero_a_esquerda($num,$dig){
	$len = strlen((string)$num);
	
	if($len < $dig){
		$num2 = $num;
		
		for($i=0;$i<$dig - $len;$i++){
			$num2 = '0'.$num2;
		}
		
		return $num2;
	} else {
		return $num;
	}
}

function zero_a_esquerda_retirar($num){
	$num = (string)$num;
	$len = strlen($num);
	
	for($i=0;$i<$len;$i++){
		if($num[$i] != '0'){
			$found = true;
		}
		if($found){
			$num2 .= $num[$i];
		}
	}
	
	if($num2)
		return $num2;
	else
		return $num;
}

function path_com_versao_arquivo($filename){
	global $_SYSTEM;
	
	$filename_int = $filename;
	
	if(preg_match('/\\'.$_SYSTEM['SEPARADOR'].'/i', $filename) > 0){
		$path = $_SYSTEM['PATH'] . $filename;
	} else {
		$filename = preg_replace('/\//i', $_SYSTEM['SEPARADOR'], $filename);
		$path = $_SYSTEM['PATH'] . $filename;
	}
	
	return '/'.$_SYSTEM['ROOT'].$filename_int.'?v='.(is_file($path) ? filemtime($path) : '1');
}

function gravar_log($mens){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$hoje = date("d-m-Y");
	
	$arquivo = fopen($_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."tmp".$_SYSTEM['SEPARADOR']."log_tarefa.$hoje.txt", "ab");
	$hora = date("H:i:s T");
	fwrite($arquivo, "[".$hora."][".($usuario['usuario']?$usuario['usuario']:"anônimo")."] ".$mens.".\r\n");
	fclose($arquivo);
}

function gravar_log_host($mens){
	global $_SYSTEM;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$hoje = date("d-m-Y");
	$root = $_SERVER["DOCUMENT_ROOT"].'/b2make/';
	
	$arquivo = fopen($root."log_tarefa.$hoje.txt", "ab");
	$hora = date("H:i:s T");
	fwrite($arquivo, "[".$hora."][".($usuario['usuario']?$usuario['usuario']:"anônimo")."] ".$mens.".\r\n");
	fclose($arquivo);
}

function log_banco($params = false){
	global $_CONEXAO_BANCO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$campos = null;
	
	$campo_nome = "id_referencia"; $campo_valor = $id_referencia; 		if($id_referencia)			$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "grupo"; $campo_valor = $grupo; 						if($grupo)			$campos[] = Array($campo_nome,$campo_valor);
	$campo_nome = "valor"; $campo_valor = addslashes($valor); 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "data"; $campo_valor = "NOW()"; 			$campos[] = Array($campo_nome,$campo_valor,true);
	
	banco_insert_name
	(
		$campos,
		"log"
	);
}

function crypto_rand_secure($min, $max) {
	$range = $max - $min;
	if ($range < 0) return $min; // not so random...
	$log = log($range, 2);
	$bytes = (int) ($log / 8) + 1; // length in bytes
	$bits = (int) $log + 1; // length in bits
	$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
	do {
		$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
		$rnd = $rnd & $filter; // discard irrelevant bits
	} while ($rnd >= $range);
	return $min + $rnd;
}

function getToken($length=32){
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    for($i=0;$i<$length;$i++){
        $token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
    }
    return md5($token);
}

function hashPassword($senha,$salt,$inc = 1000){
	$pass = $salt.$senha;

	for($i=0;$i<$inc;$i++){
		$pass = hash('sha512',$pass);
	}

	return $pass;
}

function getstatus($url) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_HEADER, true);
    curl_setopt($c, CURLOPT_NOBODY, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($c, CURLOPT_URL, $url);
	$return =  curl_exec($c);
    $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    return $status;
}

function diretorio_criar_senao_existir($dir, $dirPerm=0777){
	if(!is_dir($dir)){
		mkdir($dir,$dirPerm,true);
		chmod($dir,$dirPerm);
	}
}

function token_gerar($modulo){
	global $_SYSTEM;
	
	banco_delete
	(
		"access_token",
		"WHERE data <= (NOW() - INTERVAL 120 MINUTE)"
	);
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$id_usuario = $usuario['id_usuario'];
	$token = md5(crypt(rand().$usuario['usuario']));
	$pass = md5(crypt(rand().session_id()));
	
	$campos = null;
	
	$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "token"; $campo_valor = $token; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "pass"; $campo_valor = $pass; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "modulo"; $campo_valor = $modulo; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
	$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
	
	banco_insert_name
	(
		$campos,
		"access_token"
	);
	
	return Array(
		'token' => $token,
		'pass' => $pass,
	);
}

function token_validar($modulo){
	global $_SYSTEM;
	
	banco_delete
	(
		"access_token",
		"WHERE data <= (NOW() - INTERVAL 120 MINUTE)"
	);
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_access_token',
			'id_usuario',
			'modulo',
		))
		,
		"access_token",
		"WHERE token='".$_REQUEST['token']."'"
		." AND pass='".$_REQUEST['pass']."'"
	);
	
	if($resultado){
		if($modulo == $resultado[0]['modulo']){
			$id_usuario = $resultado[0]['id_usuario'];
			
			banco_delete
			(
				"access_token",
				"WHERE id_access_token='".$resultado[0]['id_access_token']."'"
			);
		}
	}
	
	return $id_usuario;
}

function array_to_xml($array, &$xml_user_info){
	if($array)
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml_user_info->addChild("$key");
                array_to_xml($value, $subnode);
            }else{
                $subnode = $xml_user_info->addChild("item$key");
                array_to_xml($value, $subnode);
            }
        } else {
            $xml_user_info->addChild("$key",htmlspecialchars("$value"));
        }
    }
}

function formatar_xml($array){
	global $_LOCAL_ID;
	global $_XML_LOCAL;
	global $_XML_CHARSET;
	
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="'.($_XML_CHARSET ? $_XML_CHARSET : 'UTF-8').'"?>'."\n".'<'.($_XML_LOCAL ? $_XML_LOCAL : $_LOCAL_ID).' />');
	array_to_xml($array,$xml);
	
	$dom = dom_import_simplexml($xml)->ownerDocument;
	$dom->formatOutput = true;
	return $dom->saveXML();
}

function curl_post_async($url, $params = false){
	if($params){
		foreach($params as $key => &$val){
		  if(is_array($val)) $val = implode(',', $val);
			$post_params[] = $key.'='.urlencode($val);
		}
		$post_string = implode('&', $post_params);
	}
	
    $parts = parse_url($url);

    $fp = fsockopen($parts['host'],
        isset($parts['port'])?$parts['port']:80,
        $errno, $errstr, 30);

    $out = "POST ".$parts['path']." HTTP/1.1\r\n";
    $out.= "Host: ".$parts['host']."\r\n";
    $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
    $out.= "Content-Length: ".strlen($post_string)."\r\n";
    $out.= "Connection: Close\r\n\r\n";
    if(isset($post_string))$out.= $post_string;

    fwrite($fp, $out);
    fclose($fp);
}

function http_define_ssl($url,$https = false){
	global $_SYSTEM;
	
	if(!$https)$https = $_SESSION[$_SYSTEM['ID']."b2make-site"]['https'];
	
	if($https){
		return preg_replace('/http:/i','https:',$url);
	} else {
		return preg_replace('/https:/i','http:',$url);
	}
}

function contain_resolution($box_width,$box_height,$obj_width,$obj_height){
	$BW = $box_width;
	$BH = $box_height;
	$OW = $obj_width;
	$OH = $obj_height;
	
	if($OW > $OH){
		$FO = $OH / $OW;
	} else {
		$FO = $OW / $OH;
	}
	
	if($BW > $BH){
		$FB = $BH / $BW;
	} else {
		$FB = $BW / $BH;
	}
	
	if($FO > $FB){
		if($OW > $OH){
			$NW = $BH*$OW/$OH;
			$NH = $BH;
		} else {
			$NW = $BH*$OW/$OH;
			$NH = $BH;
		}
	} else {
		if($OW > $OH){
			$NW = $BW;
			$NH = $BW*$OH/$OW;
		} else {
			$NW = $BH*$OW/$OH;
			$NH = $BH;
		}
	}
	
	return Array(
		'width' => round($NW),
		'height' => round($NH),
	);
}

function randomString($n = 20) { 
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
    $randomString = ''; 
  
    for ($i = 0; $i < $n; $i++) { 
        $index = rand(0, strlen($characters) - 1); 
        $randomString .= $characters[$index]; 
    } 
  
    return $randomString; 
} 

function parcelamento($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	for($parcela=1;$parcela<=$maximo_parcelas;$parcela++){
		$valor_parcela = $valor_total / $parcela;
		if($valor_parcela < $valor_minimo){
			break;
		}
		
		$quantidade = $parcela;
		$valor = $valor_parcela;
	}
	
	return Array(
		'quantidade' => $quantidade,
		'valor' => $valor,
	);
}

?>