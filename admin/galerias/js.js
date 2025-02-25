
$.b2make_local_callback = function(p){
	if(!p)p = {};
	
	switch(p.local){
		case 'add':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Galeria criada com sucesso!',local:'add'});
				break;
				case 'close':
					$.b2make_redirect();
				break;
			}
		break;
		case 'editar':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Galeria editada com sucesso!',local:'editar'});
					$.b2make_enviar_requisicao({params:{opcao:'editar_static',id_galerias:p.dados.id_galerias},local:'editar_static'});
				break;
			}
		break;
		case 'editar_static':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Conteúdos com páginas estáticas alterados com sucesso!',local:'editar_static'});
				break;
				case 'close':
					$.b2make_redirect();
				break;
			}
		break;
		case 'bloquear':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({dados:{redirect:p.dados.redirect},texto:'Conteúdo <b>'+(p.dados.status == 'B' ? 'bloqueado' : 'ativado')+'</b> com sucesso!',local:'bloquear'});
				break;
				case 'close':
					$.b2make_redirect({local:(p.dados.redirect ? p.dados.redirect : null)});
				break;
			}
		break;
		case 'excluir':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({dados:{redirect:p.dados.redirect},texto:'Excluir filhos desse conteúdo!',local:'excluir_etapas'});
					$.b2make_enviar_requisicao({local:'excluir_etapas',dont_close_console:true});
					
					b2make.excluir_etapas = {
						folders : new Array(p.dados.caminho),
						id : p.dados.id
					};
				break;
			}
		break;
		case 'excluir_etapas':
			switch(p.estado){
				case 'success':
					var caminho;
					
					if(p.dados){
						if(p.dados.mens){
							$.b2make_console_formulario({texto:p.dados.mens,local:'excluir_etapas'});
						}
						
						if(p.dados.folders){
							for(var i=0;i<p.dados.folders.length;i++){
								b2make.excluir_etapas.folders.push(p.dados.folders[i]);
							}
						}
					}
					
					if(b2make.excluir_etapas.folders.length > 0){
						caminho = b2make.excluir_etapas.folders.pop();
					}
					
					if(caminho){
						$.b2make_enviar_requisicao({
							params:{
								opcao:'excluir_etapas',
								caminho:caminho
							},
							local:'excluir_etapas',
							dont_close_console:true
						});
					} else {
						$.b2make_enviar_requisicao({params:{opcao:'excluir_banco',id:b2make.excluir_etapas.id},local:'excluir_banco'});
					}
				break;
			}
		break;
	}
};

$(document).ready(function(){
	sep = "../../";
	
	if($("a[rel^='prettyPhoto']").length){
		setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, show_title: true}); }, 100);
	}
	
	var swfup_max_files = 0;

	$('#swfupload-control').swfupload({
		upload_url: "uploadimg.php",
		file_post_name: 'uploadfile',
		file_size_limit : "10000",
		file_types : "*.jpg;*.png;*.gif",
		file_types_description : "Image files",
		file_upload_limit : 100,
		flash_url : sep+"includes/js/swfupload/swfupload/swfupload.swf",
		button_image_url : sep+'includes/js/swfupload/swfupload/wdp_buttons_upload_114x29.png',
		button_width : 114,
		button_height : 29,
		button_placeholder : $('#swfupload-button')[0],
		debug: false,
		post_params: {
			'galeria':$("#galeria_id").val(),
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
		
		swfup_max_files = swfup_max_files + numFilesQueued;
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
		swfup_max_files--;
		$(this).swfupload('startUpload');
		
		if(swfup_max_files <= 0){
			swfup_max_files = 0;
			
			$.b2make_enviar_requisicao({params:{opcao:'editar_static',id_galerias:$("#galeria_id").val()},local:'editar_static'});
		}
	});
	
	$("#form").submit(function() {
		if(!submit_form("form",false)){
			return false;
		}
	});
	
	$("#form2").submit(function() {
		if(!submit_form("form2",false)){
			return false;
		}
	});
	
	$("#grupos").submit(function() {
		if(!submit_form("grupos",false)){
			return false;
		}
	});
	
	function submit_form(form_id,status){
		var enviar = true;
		var campo;
		var mens;
		
		switch(form_id){
			case "form":
				campo = "nome"; mens = "Preencha o nome"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			break;
			case "form2":
				campo = "titulo"; mens = "Preencha o título"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			break;
			case "grupos":
				campo = "grupo"; mens = "Preencha o nome do grupo"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			break;
			
		}
		
		if(!enviar){
			return false;
		} else {
			$.b2make_enviar_formulario({form_id:form_id,dont_close_console:($('#_formulario-local').val() == 'add' ? false : true)});
		}
		
		return false;
	}
	
	$(".galeria_escolher").bind("click touchstart", function() {
		parent.galeria_escolher($(this).attr('galeria'));
	});
	
	$(".galeria_grupo_escolher").bind("click touchstart", function() {
		parent.galeria_grupo_escolher($(this).attr('galeria_grupo'));
	});
	
	
});