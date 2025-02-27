function _url_name(){
	var url_aux = location.pathname;
	var url_parts;
	
	url_parts = url_aux.split('/');
	
	return url_parts[url_parts.length-1];
}

function escolher_para(id){
	var window_escolher_para = window.open(_url_name()+"?opcao=escolher_para&id="+id,'window_para','top=100,left=200,width=600,height=600,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=yes');
	window_escolher_para.focus();
}

$(document).ready(function(){
	sep = "../../";
	
	$('textarea.tinymce').tinymce({
		height : "400",
		document_base_url: sep,
		relative_urls: false,
		convert_urls: false,
		// Location of TinyMCE script
		script_url : sep+'includes/js/tiny_mce/tiny_mce.js',

		// General options
		theme : "advanced",
		plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

		// Theme options
		theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
		theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
		theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		//content_css : sep+"includes/css/textos.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",

		// Replace values for the template plugin
		template_replace_values : {
			username : "Some User",
			staffid : "991234"
		},
		
		// Language
		
		language : "pt"
	});
	
	$('textarea.tinymce_mini').tinymce({
		height : "100",
		document_base_url: sep,
		relative_urls: false,
		convert_urls: false,
		// Location of TinyMCE script
		script_url : sep+'includes/js/tiny_mce/tiny_mce.js',

		// General options
		theme : "advanced",
		plugins : "paste,xhtmlxtras",
		
		// Theme options
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_buttons4 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		content_css : sep+"includes/css/textos.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",

		// Replace values for the template plugin
		template_replace_values : {
			username : "Some User",
			staffid : "991234"
		},
		
		// Language
		
		language : "pt"
	});
	
	$("#form").submit(function(){
		if(!$("#para_id").val()){					alert("É obrigatório definir o campo Para!");	return false;}
		if(!$("#assunto").val()){					alert("É obrigatório preencher o Assunto!");	return false;}
		if(!$("#mensagem").val()){					alert("É obrigatório preencher a Mensagem!");	return false;}
	});
	
	$("#form_usuario").submit(function(){
		if($("#nome_id").val() > 0){
			$('#para_opcao', window.opener.document).val('usuario');
			$('#para', window.opener.document).val($("#busca_nome2").val());
			$('#para_id', window.opener.document).val($("#nome_id").val());
			
			window.opener.focus();
			window.close();
		} else {
			alert("Escolha um usuário antes de clicar em ESCOLHER.\n\nNOTA: Selecione a caixa de texto. Digite uma parte do texto do nome do usuário que está buscando, aguarde um pouco que o sistema retorna as possibilidades em uma lista, escolha uma opção e clique no botão ESCOLHER ou então aperte a tecla ENTER do teclado.");
		}
		return false;
	});
	
	$("#form_grupo").submit(function(){
		var grupo_nomes = "";
		var grupo_id = "";
		
		for(var i=0;i<parseInt($('#grupo_num').val());i++){
			if($("#grupo_id"+i+":checked").val()){	
				if(grupo_id)		grupo_id = grupo_id + ',' + $('#grupo_id'+i).val(); 				else grupo_id = $('#grupo_id'+i).val();
				if(grupo_nomes)	grupo_nomes = grupo_nomes + ', ' + $('#grupo_nome'+i).html(); 	else grupo_nomes = $('#grupo_nome'+i).html();
			}
		}
		
		$('#para_opcao', window.opener.document).val('grupo');
		$('#para', window.opener.document).val(grupo_nomes);
		$('#para_id', window.opener.document).val(grupo_id);
		
		window.opener.focus();
		window.close();
		
		return false;
	});
	
	
});