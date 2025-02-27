$(document).ready(function(){
	sep = "../../";
	
	$(".opcao").hover(
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-cinza.png?v=1)');
		},
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-branco.png?v=1)');
		}
	);
	
	$('textarea.tinymce').tinymce({
		height : "400",
		width : "760",
		extended_valid_elements : "iframe[src|width|height|name|align|frameborder]",
		document_base_url: raiz,
		relative_urls: false,
		convert_urls: false,
		// Location of TinyMCE script
		script_url : raiz+'includes/js/tiny_mce/tiny_mce.js',
		
		// General options
		theme : "advanced",
		plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",
		plugin_insertdate_dateFormat : "%d/%m/%Y",
        plugin_insertdate_timeFormat : "%H:%M:%S",
		
		// Theme options
		theme_advanced_buttons1 : "undo,redo,|,cut,copy,paste,pastetext,pasteword,removeformat,|,formatselect,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,outdent,indent,blockquote",
		theme_advanced_buttons2 : "fullscreen,preview,code,print,visualaid,|,link,unlink,anchor,image,media,charmap,emotions,hr,advhr,|,insertdate,inserttime,sub,sup,|,tablecontrols",
		theme_advanced_buttons3 : "",
		theme_advanced_buttons4 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		//content_css : raiz+"includes/css/padrao.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",
		file_browser_callback: filebrowser,
		
		// Language
		language : 'pt',
		
		// Replace values for the template plugin
		template_replace_values : {
			username : "Some User",
			staffid : "991234"
		}
	});
	
	function filebrowser(field_name, url, type, win) {
		fileBrowserURL = raiz+"includes/php/pdw_file_browser/index.php?filter=" + type;
		
		tinyMCE.activeEditor.windowManager.open({
			title: "PDW Explorador de Arquivos",
			url: fileBrowserURL,
			width: 950,
			height: 650,
			inline: 0,
			maximizable: 1,
			close_previous: 0
		},{
			window : win,
			input : field_name
		});
	}
	
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
	
	$("#form").submit(function() {
		if(!confirm("Será necessário reiniciar o sistema. Tem certeza que deseja GRAVAR as alterações?")){return false;}
	});
	
	
});