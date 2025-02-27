function _url_name(){
	var url_aux = location.pathname;
	var url_parts;
	
	url_parts = url_aux.split('/');
	
	return url_parts[url_parts.length-1];
}

function enviar(id){
	var start = true;
	
	if(id){
		if(confirm("Esse processo dará início ao envio de e-mails. Deseja continuar ?")){
			if(start)
			start = "&email_start=sim";
			
			window.open(_url_name()+"?opcao=email_loop&id="+id+start,'email_markenting_sender','top=200,left=200,width=500,height=420,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=no');
		}
	}
}

function escolher_grupos(id){
	window.open(_url_name()+"?opcao=escolher_grupos&id="+id,'email_markenting_sender','top=200,left=200,width=500,height=420,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=yes');
}

$(document).ready(function(){
	sep = "../../";

	if($('#opcao').val() == 'envios'){
		setTimeout(function(){ window.open("?opcao=envios","_self"); }, 5000);
	}
	
	$("#form").submit(function() {
		if(!$("#assunto").val()){							alert("É obrigatório preencher o Assunto!");	return false;}
	});
	
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
	
	$(".link_hover").hover(
		function(){
			$('body').css('cursor', 'pointer');
		},
		function(){
			$('body').css('cursor', 'default'); 
		}
	);
	
	$(".enviar_mail").click(function() {
		var enviar = false;
		var id = this.id;
		var enviando = $('#enviando').val();
		
		id = id.replace(/enviar_mail_/gi,'');
		
		if(enviando){
			if(confirm("JÁ EXISTE UMA NEWSLETTER SENDO ENVIADA. Deseja parar o envio atual e iniciar o envio dessa newsletter selecionada?")){
				enviar = true;			
			}
		} else if(confirm("Esse processo dará início ao envio de e-mails. Deseja continuar ?")){
			enviar = true;
		}
		
		if(enviar){
			$.ajax({
				url: 'index.php',
				data: { ajax : 'sim', id : id , enviar_mail : 'sim' }
			});
			
			if(confirm("Deseja visualizar a tela de status de envio?")){
				window.open("?opcao=envios","_self");
			}			
		}
	});
	
	$(".check_all").click(function() {
		var check_name = $("#check_name").val();
		var campos_num = parseInt($("#campos_num").val());
		
		for(var i=0;i<campos_num;i++){
			$("#"+check_name+i).attr('checked', true);
		}
	});
	
	$(".uncheck_all").click(function() {
		var check_name = $("#check_name").val();
		var campos_num = parseInt($("#campos_num").val());
		
		for(var i=0;i<campos_num;i++){
			$("#"+check_name+i).attr('checked', false);
		}
	});
	
	function checkMail(mail){
		var er = new RegExp(/^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/);
		if(typeof(mail) == "string"){
			if(er.test(mail)){ return true; }
		}else if(typeof(mail) == "object"){
			if(er.test(mail.value)){
						return true;
					}
		}else{
			return false;
			}
	}
	
	function load_popup(){
		var id = $("#id").val();
		
		window.open(url_name()+"?opcao=email_loop&id="+id,'email_markenting_sender','top=200,left=200,width=500,height=420,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=no');
	}
	
});