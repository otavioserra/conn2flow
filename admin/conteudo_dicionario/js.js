$(document).ready(function(){
	sep = "../../";
	
	$('textarea.tinymce').tinymce({
		height : "400",
		extended_valid_elements : "iframe[src|width|height|name|align|frameborder]",
		document_base_url: sep,
		relative_urls: false,
		convert_urls: false,
		// Location of TinyMCE script
		script_url : sep+'includes/js/tiny_mce/tiny_mce.js',
		
		// General options
		theme : "advanced",
		plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

		// Theme options
		theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
		theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
		theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		content_css : sep+"includes/css/padrao.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",
		
		// Language
		language : 'pt',
		
		// Replace values for the template plugin
		template_replace_values : {
			username : "Some User",
			staffid : "991234"
		}
	});

	$("#form").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "palavra"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!enviar){
			alerta.html("É obrigatório preencher os campos marcados em vermelho!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
			alerta.dialog("open");
			return false;
		}
	});
	
	
});