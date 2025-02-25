
$.b2make_local_callback = function(p){
	if(!p)p = {};
	
	switch(p.local){
		case 'add_conteudo':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Conteúdo criado com sucesso!',local:'add_conteudo'});
					$.b2make_enviar_requisicao({params:{opcao:'add_conteudo_static',id_conteudo:p.dados.id_conteudo},local:'add_conteudo_static'});
				break;
			}
		break;
		case 'add_conteudo_static':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Página estática criada com sucesso!',local:'add_conteudo_static'});
				break;
				case 'close':
					$.b2make_redirect();
				break;
			}
		break;
		case 'editar':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Conteúdo editado com sucesso!',local:'editar'});
					$.b2make_enviar_requisicao({params:{opcao:'editar_static',id_conteudo:p.dados.id_conteudo},local:'editar_static'});
				break;
				case 'close':
					$.b2make_redirect();
				break;
			}
		break;
		case 'editar_static':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Página estática criada com sucesso!',local:'editar_static'});
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
		case 'excluir_banco':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Conteúdo <b>excluído</b> com sucesso!',local:'excluir_banco'});
				break;
				case 'close':
					$.b2make_redirect();
				break;
			}
		break;
		case 'permissao':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({texto:'Configuração modificada com sucesso!',local:'add_conteudo'});
					$.b2make_enviar_requisicao({params:{opcao:'permissao_static',id_conteudo:p.dados.id_conteudo,tipo:p.dados.tipo,nivel:p.dados.nivel},local:'permissao_static',dont_close_console:true});
				break;
			}
		break;
		case 'permissao_static':
			switch(p.estado){
				case 'success':
					b2make.cont_permissao = {
						etapa:1,
						etapas: parseInt(p.dados.etapas),
						ids:p.dados.ids
					};
					$.b2make_console_formulario({texto:p.dados.mens,local:'permissao_static'});
					
					if(b2make.cont_permissao.etapas > 0){
						$.b2make_enviar_requisicao({
							params:{
								opcao:'permissao_static_etapas',
								id:b2make.cont_permissao.ids[b2make.cont_permissao.etapa-1].id,
								titulo:b2make.cont_permissao.ids[b2make.cont_permissao.etapa-1].titulo
							},
							local:'permissao_static_etapas',
							dont_close_console:( b2make.cont_permissao.etapas > 1 ? true : false)
						});
					} else {
						$.b2make_console_formulario({estado:'close-time',local:'permissao_static_etapas'});
					}
				break;
			}
		break;
		case 'permissao_static_etapas':
			switch(p.estado){
				case 'success':
					b2make.cont_permissao.etapa++;
					$.b2make_console_formulario({texto:p.dados.mens,local:'permissao_static_etapas'});
					
					if(b2make.cont_permissao.etapa <= b2make.cont_permissao.etapas){
						$.b2make_enviar_requisicao({
							params:{
								opcao:'permissao_static_etapas',
								id:b2make.cont_permissao.ids[b2make.cont_permissao.etapa-1].id,
								titulo:b2make.cont_permissao.ids[b2make.cont_permissao.etapa-1].titulo
							},
							local:'permissao_static_etapas',
							dont_close_console:( b2make.cont_permissao.etapa >= b2make.cont_permissao.etapas ? true : false)
						});
					} else {
						$.b2make_console_formulario({texto:'Atualização de páginas estáticas finalizada com sucesso!',local:'permissao_static_etapas'});
					}
				break;
				case 'close':
					$.b2make_redirect();
				break;
			}
		break;
		case 'modificar_caminho_raiz_novo':
			switch(p.estado){
				case 'success':
					$.b2make_console_formulario({dados:{redirect:p.dados.redirect},texto:'Raiz do conteúdo modificada com sucesso!',local:'modificar_caminho_raiz_novo'});
					$.b2make_enviar_requisicao({local:'modificar_caminho_raiz_etapas',dont_close_console:true});
					
					b2make.modificar_caminho_raiz = {
						redirect : p.dados.redirect,
						caminho_antigo : p.dados.caminho_antigo,
						caminho_novo : p.dados.caminho_novo,
						folders : new Array(p.dados.caminho_inicial)
					};
				break;
			}
		break;
		case 'modificar_caminho_raiz_etapas':
			switch(p.estado){
				case 'success':
					var caminho;
					
					if(p.dados){
						if(p.dados.mens){
							$.b2make_console_formulario({texto:p.dados.mens,local:'modificar_caminho_raiz_etapas'});
						}
						
						if(p.dados.folders){
							for(var i=0;i<p.dados.folders.length;i++){
								b2make.modificar_caminho_raiz.folders.push(p.dados.folders[i]);
							}
						}
					}
					
					if(b2make.modificar_caminho_raiz.folders.length > 0){
						caminho = b2make.modificar_caminho_raiz.folders.pop();
					}
					
					if(caminho){
						$.b2make_enviar_requisicao({
							params:{
								opcao:'modificar_caminho_raiz_etapas',
								caminho:caminho,
								caminho_antigo:b2make.modificar_caminho_raiz.caminho_antigo,
								caminho_novo:b2make.modificar_caminho_raiz.caminho_novo
							},
							local:'modificar_caminho_raiz_etapas',
							dont_close_console:true
						});
					} else {
						$.b2make_console_formulario({estado:'close-time',local:'modificar_caminho_raiz_etapas'});
					}
				break;
				case 'close-time':
					$.b2make_redirect({local:(b2make.modificar_caminho_raiz.redirect ? b2make.modificar_caminho_raiz.redirect : null)});
				break;
			}
		break;
		
	}
	
};

var excluir_conteudo = function(url,id,opcao){
	if(id){
		if(confirm("Tem certeza que deseja excluir esse item?")){
			$.b2make_enviar_requisicao({params:{opcao:'excluir',id:id},local:'excluir',dont_close_console:true});
		}
	}
};

var galeria_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#galeria_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , galeria_nome : 'sim' , galeria_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#galeria").val(dados.nome);
			$("#galeria_imagens_cont").html(dados.galeria_imagens);
			
			setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false}); }, 100);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var galeria_grupo_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#galeria_grupo_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , galeria_grupo_nome : 'sim' , galeria_grupo_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#galeria_grupo").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var produto_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#produto").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , produto_nome : 'sim' , produto : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$("#produto_txt").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var servicos_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#servico").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , servico_nome : 'sim' , servico : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$("#servico_txt").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var videos_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#videos_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , videos_nome : 'sim' , videos_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#videos").val(dados.nome);
			$("#galerias_videos_cont").html(dados.galerias_videos);
			
			setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false}); }, 100);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var videos_grupo_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#videos_grupo_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , videos_grupo_nome : 'sim' , videos_grupo_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#videos_grupo").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

$(document).ready(function(){
	sep = "../../";
	var tempo_animacao = 100;
	
	$('#menu').tabify();
	
	$("textarea[maxlength]").keypress(function(event){
		var key = event.which;
	 
		//todas as teclas incluindo enter
		if(key >= 33 || key == 13) {
			var maxLength = $(this).attr("maxlength");
			var length = this.value.length;
			if(length >= maxLength) {
				event.preventDefault();
			}
		}
	});
	
	if($('.bloquear').length){
		$(".bloquear").bind("click touchstart", function() {
			$.b2make_enviar_requisicao({params:{opcao:'bloqueio',tipo:$(this).attr('data-tipo'),id:$(this).attr('data-id'),redirect:$(this).attr('data-opcao')},local:'bloquear'});
		});
	}
	
	if($('.modificar_caminho_raiz_novo').length){
		$(".modificar_caminho_raiz_novo").bind("click touchstart", function() {
			$.b2make_enviar_requisicao({params:{opcao:'modificar_caminho_raiz_novo',id_filho:$(this).attr('data-id_filho'),id:$(this).attr('data-id'),subir_nivel:($(this).attr('data-subir_nivel')?'1':null)},local:'modificar_caminho_raiz_novo',dont_close_console:true});
		});
	}
	
	if($("a[rel^='prettyPhoto']").length){
		setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false}); }, 100);
	}
	
	$(".inteiro").numeric();
	$(".data").mask("99/99/9999");
	$(".hora").mask("99:99");
	
	$('.input_ordem').click(function(e){
		this.select();
	});
	
	$('.input_ordem').keyup(function(e){
		var code = (e.keyCode ? e.keyCode : e.which);
		var id = this.id;
		var id_a;
		var id_flag = false;
		
		if(code == 13){
			salvar_ordem();
		}
		
		if(
			(code >= 48 && code <= 57) ||
			(code >= 96 && code <= 105)
		){
			ids_unicos(this.value,this.id);
		}
		
		if(code == 38){
			$('.input_ordem').each(function(){
				var id_aux = this.id;
				
				if(id_aux == id){
					if(id_a){
						$('#'+id_a).focus();
						$('#'+id_a).select();
					} else {
						$('.input_ordem:last').focus();
						$('.input_ordem:last').select();
					}
				}
				
				id_a = id_aux;
			});
			
			ids_unicos(this.value,this.id);
		}
		
		if(code == 40){
			$('.input_ordem').each(function(){
				var id_aux = this.id;
				
				if(id_flag){
					$('#'+id_aux).focus();
					$('#'+id_aux).select();
					id_flag = false;
				}
				
				if(id_aux == id){
					id_flag = true;
				}
			});
			
			if(id_flag){
				$('.input_ordem:first').focus();
				$('.input_ordem:first').select();
			}
			
			ids_unicos(this.value,this.id);
		}
		
	});
	
	function ids_unicos(valor,id){
		var valor_aux = parseInt(valor);
		$('.input_ordem').each(function(){
			var val_aux = parseInt(this.value);
			if(valor_aux == val_aux && this.id != id && valor_aux > 0){
				this.value = val_aux + 1;
				ids_unicos(this.value,this.id);
			}
		});
	}
	
	$("#input_ordem_salvar").hover(
		function(){
			$(this).css('background-color', '#464E56');
			$(this).css('color', '#FFF');
		},
		function(){
			$(this).css('background-color', '#D7D9DD');
			$(this).css('color', '#58585B');
		}
	);
	
	$("#input_ordem_salvar").click(salvar_ordem);
	
	function salvar_ordem(){
		if(confirm('Tem certeza que deseja salvar a ordem atual?')){
			var id = '';
			
			$('.input_ordem').each(function(){
				var id_name = this.id;
				
				id_name = id_name.replace(/input_ordem_/gi,'');
				
				id = id + id_name + ',' + this.value + ';';
			});
			
			window.open(raiz+"admin/conteudo/?opcao=ordem&id="+id,"_self");
		}
	}
	
	$('.checkbox_label').click(function(e){
		if(e.target.nodeName != 'INPUT'){
			var input = $(this).find('td').find('input');
			
			if(input.attr('checked'))
				input.attr('checked', false);
			else
				input.attr('checked', true);
		}
	});
	
	$('.ordenacao').keydown(function(event){
		if(event.keyCode == '13'){
			var id = this.id.replace(/ordem/, '');
			var valor = this.value;
			var opcao = 'ordenacao';
			window.open(url_name()+"?opcao="+opcao+"&id="+id+"&valor="+valor,"_self");
		}
	});

	$('textarea.tinymce').tinymce({
		height : "400",
		width : "760",
		valid_children : "+body[style]",
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
		// content_css : raiz+"includes/css/padrao.css",

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

	$("#form").submit(function() {
		$.b2make_enviar_formulario({form_id:$(this).attr('id'),dont_close_console:true});
		
		return false;
	});
	
	$("#form2").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		
		campo = "titulo"; mens = "Preencha o Título"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		
		campo = "data_automatica2"; if($("#"+campo).is(':checked')){ 
			campo = "data"; mens = "É obrigatório definir a data!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			campo = "hora"; mens = "É obrigatório definir a hora!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			campo = "data"; mens = "Data inválida!"; if(!data_validar($("#"+campo).val())){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			campo = "hora"; mens = "Horário inválido!"; if(!hora_validar($("#"+campo).val())){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		}
		
		if(!enviar){
			return false;
		} else {
			$.b2make_enviar_formulario({form_id:$(this).attr('id'),dont_close_console:true});
		}
		
		return false;
	});
	
	function hora_validar(hora){
		var horaArr = hora.split(':');
		var horaAux = parseInt(horaArr[0]);
		var minAux = parseInt(horaArr[1]);
		
		if(horaAux > 23){
			return false;
		} else if(minAux > 59){
			return false;
		} else {
			return true;
		}
	}
	
	function data_validar(data){
		var bissexto = 0;
		var dia = data.substr(0,2);
		var mes = data.substr(3,2);
		var ano = data.substr(6,4);
		
		if((ano > 1900)||(ano < 2100)){
			switch(mes){
				case '01':
				case '03':
				case '05':
				case '07':
				case '08':
				case '10':
				case '12':
					if(dia <= 31){
						return true;
					}
					break;
				case '04':              
				case '06':
				case '09':
				case '11':
					if(dia <= 30){
						return true;
					}
					break;
				case '02':
					/* Validando ano Bissexto / fevereiro / dia */ 
					if((ano % 4 == 0) || (ano % 100 == 0) || (ano % 400 == 0)){ 
						bissexto = 1; 
					}
					
					if((bissexto == 1) && (dia <= 29)){ 
						return true;                             
					}
					
					if((bissexto != 1) && (dia <= 28)){ 
						return true; 
					}                       
					break;                                          
			}
		}
		
		return false;
	}
	
	// ======================== Tags ==================================
	
	$("#tags-add").bind("click touchstart", function() {
		$("#tags-add-cont").toggle(150);
		$("#tags-add-texto").focus();
	});
	
	$("#tags-add-button").bind("click touchstart", add_tags);
	
	$("#tags-add-texto").bind("keypress", function(e){
		var code = (e.keyCode ? e.keyCode : e.which);
		if(code == 13) {
			add_tags();
			return false;
		}
	});
	
	$(document.body).on('change',".tags-checkbox",function(e){
		$("#tags-flag").val('s');
	});
	
	$(document.body).on('click touchstart',".tags-nome",function(e){
		if(e.target.nodeName != 'INPUT'){
			var input = $(this).parent().find('input');
			
			if(input.attr('checked'))
				input.attr('checked', false);
			else
				input.attr('checked', true);
				
			$("#tags-flag").val('s');
		}
	});
	
	$(document.body).on('click touchstart',".tags-excluir",function(){
		if(confirm("Tem certeza que deseja excluir esse item?")){
			var id = $(this).parent().attr('id');
			$(this).parent().remove();
			$("#tags-add-texto").focus();
			
			$.ajax({
				type: 'POST',
				url: url_name(),
				data: { ajax : 'sim' , del_tags : 'sim' , id : id},
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$('#ajax_erro').fadeIn(tempo_animacao);
					setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
				}
			});
		}
	});
	
	function add_tags(){
		var text = $("#tags-add-texto").val();
		
		if(text.length > 0){
			if(!checkStr(text)){
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , add_tags : 'sim' , text : text},
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						var dados = eval('(' + txt + ')');
						$('#ajax_lendo').fadeOut(tempo_animacao);
						
						if(dados.erro == 1){
							if(!alerta.dialog('isOpen')){alerta.html(dados.mens); alerta.dialog('open');}
							$("#tags-add-texto").focus();
						} else {
							var cont = $('<div id="tags'+dados.id+'" class="tags-conteiner-entry"></div>');
							var input = $('<input type="checkbox" value="1" name="tags'+dados.id+'" class="tags-checkbox" checked="checked" />');
							var nome = $('<div class="tags-nome">'+text+'</div>');
							var excluir = $('<div class="tags-excluir"></div>');
							var clear = $('<div class="clear"></div>');
							
							input.appendTo(cont);
							nome.appendTo(cont);
							excluir.appendTo(cont);
							clear.appendTo(cont);
							
							$("#tags-marcador").before(cont);
							$("#tags-add-texto").val('');
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$('#ajax_erro').fadeIn(tempo_animacao);
						setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
					}
				});
			} else {
				var mens = '<p>Não é permitido caracteres especiais na formação da Tag</p><p>Caracteres Permitidos: A até Z, a até z, 0 até 9, _, - e .</p>';
				if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');}
			}
		}
	}
	
	function checkStr(pass){
		var er = new RegExp(/[^A-Za-z0-9_.\séúíóáÉÚÍÓÁèùìòàÈÙÌÒÀõãñÕÃÑêûîôâÊÛÎÔÂëÿüïöäËYÜÏÖÄ-]/);
		if(typeof(pass) == "string"){
			if(er.test(pass)){ return true; }
		}else if(typeof(pass) == "object"){
			if(er.test(pass.value)){
						return true;
					}
		}else{
			return false;
			}
	}
	
	// ======================== Galeria ==================================
	
	$("#galeria_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/galerias/popup.php');
		popup.dialog('option','title','Escolha a Galeria de Imagens');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#galeria_grupo_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/galerias/popup.php?opcao=grupos');
		popup.dialog('option','title','Escolha a Galeria de Imagens');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#videos_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/videos/popup.php');
		popup.dialog('option','title','Escolha a Galeria de Vídeos');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#videos_grupo_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/videos/popup.php?opcao=grupos');
		popup.dialog('option','title','Escolha a Galeria de Vídeos');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#servico_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/servicos/popup.php');
		popup.dialog('option','title','Escolha o Serviço');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#produto_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/produtos/popup.php');
		popup.dialog('option','title','Escolha o Produto');
		popup.dialog('open');
		popup_ativo = true;
	});
	
});