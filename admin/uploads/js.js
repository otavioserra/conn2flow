$(document).ready(function(){
	sep = "../../";
	
	$(".caminho").click(function (){
		$("#caminho_texto").html($("#internet_atual").val()+$(this).attr('url'));
		$("#caminho_conteiner").css('display','block');
	});
	
	$('textarea.tinymce').tinymce({
		height : "400",
		extended_valid_elements : "iframe[src|width|height|name|align|frameborder]",
		document_base_url: '../',
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

	$('#swfupload-control').swfupload({
		upload_url: "uploadfile.php",
		file_post_name: 'uploadfile',
		file_size_limit : "10000",
		file_types : "*.*",
		file_types_description : "Upload files",
		file_upload_limit : 100,
		flash_url : "../../includes/js/swfupload/swfupload/swfupload.swf",
		button_image_url : '../../includes/js/swfupload/swfupload/wdp_buttons_upload_114x29.png',
		button_width : 114,
		button_height : 29,
		button_placeholder : $('#button_uploads')[0],
		debug: false,
		post_params: {
			'diretorio':$("#diretorio").val(),
			'usuario':$("#usuario").val(),
			'sessao':$("#sessao").val(),
		}
	})
	.bind('fileQueued', function(event, file){
		var listitem='<li id="'+file.id+'" >'+
			'Arquivo: <em>'+file.name+'</em> ('+Math.round(file.size/1024)+' KB) <span class="progressvalue" ></span>'+
			'<div class="progressbar" ><div class="progress" ></div></div>'+
			'<p class="status" >Aguardando</p>'+
			'<span class="cancel" >&nbsp;</span>'+
			'</li>';
		$('#log').append(listitem);
		$('li#'+file.id+' .cancel').bind('click', function(){
			var swfu = $.swfupload.getInstance('#swfupload-control');
			swfu.cancelUpload(file.id);
			$('li#'+file.id).slideUp('fast');
		});
		// start the upload since it's queued
		$(this).swfupload('startUpload');
	})
	.bind('fileQueueError', function(event, file, errorCode, message){
		alert('O tamanho do arquivo '+file.name+' é maior que o limite máximo');
	})
	.bind('fileDialogComplete', function(event, numFilesSelected, numFilesQueued){
		$('#queuestatus').text('Arquivos selecionados: '+numFilesSelected+' / Arquivos na Fila: '+numFilesQueued);
	})
	.bind('uploadStart', function(event, file){
		$('#log li#'+file.id).find('p.status').text('Enviando...');
		$('#log li#'+file.id).find('span.progressvalue').text('0%');
		$('#log li#'+file.id).find('span.cancel').hide();
	})
	.bind('uploadProgress', function(event, file, bytesLoaded){
		//Show Progress
		var percentage=Math.round((bytesLoaded/file.size)*100);
		$('#log li#'+file.id).find('div.progress').css('width', percentage+'%');
		$('#log li#'+file.id).find('span.progressvalue').text(percentage+'%');
	})
	.bind('uploadSuccess', function(event, file, serverData){
		var item=$('#log li#'+file.id);
		item.find('div.progress').css('width', '100%');
		item.find('span.progressvalue').text('100%');
		var pathtofile= /*'<a href="uploads/'+file.name+'" target="_blank" >view &raquo;</a>'*/'';
		item.addClass('success').find('p.status').html('Terminou!!!'+pathtofile);
	})
	.bind('uploadComplete', function(event, file){
		// upload has completed, try the next one in the queue
		$(this).swfupload('startUpload');
	});
	
	$("#form").submit(function() {
		if(!$("#nome").val()){							alert("É obrigatório preencher o Nome!");	return false;}
	});
	
	
});