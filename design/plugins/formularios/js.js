function formularios_campo(p){
	if(!p) p = {};
	
	var campos_tipos = b2make.formularios.campos_tipos;
	
	var select_lbl = $('<label for="b2make-formularios-select-tipos-'+p.count+'" class="b2make-tooltip" title="Clique para mudar o tipo de campo.">'+b2make.msgs.formulariosCamposTipo+'</label>');
	var select = $('<select class="b2make-formularios-select-tipos b2make-tooltip" id="b2make-formularios-select-tipos-'+p.count+'" title="Clique para mudar o tipo de campo."></select>');
	
	for(var i=0;i<campos_tipos.length;i++){
		select.append('<option value="'+campos_tipos[i].tipo+'"'+(campos_tipos[i].tipo == p.tipo ? ' selected="selected"' : '')+'>'+campos_tipos[i].label+'</option>');
	}
	
	var select_options_link = $('<div data-title="Formul&aacute;rios - Selecionar Op&ccedil;&otilde;es" data-back-btn="formularios" data-id="'+p.id+'" data-options-label="'+p.options_label+'" data-type="formularios-select-options" data-edit-sub-options="true" class="b2make-formularios-select-options-edit b2make-suboptions-editar-sem-styles b2make-tooltip" title="Clique para mudar as op&ccedil;&otilde;es dessa sele&ccedil;&atilde;o."'+('select' == p.tipo || 'checkbox' == p.tipo ? '' : ' style="display:none;"')+'></div>');
	
	var nome_lbl = $('<label for="b2make-formularios-nome-'+p.count+'" class="b2make-tooltip" title="Clique para mudar o nome do campo deste formul&aacute;rio.">'+b2make.msgs.formulariosCamposNome+'</label><div class="clear"></div>');
	var nome_input = $('<input type="text" id="b2make-formularios-nome-'+p.count+'" class="b2make-formularios-campo-nome b2make-tooltip" value="'+p.nome+'" title="Clique para mudar o nome do campo deste formul&aacute;rio.">');
	var campo_input = $('<div class="b2make-formularios-campo">'+p.campo+'</div>');
	
	var excluir_lbl = $('<label for="b2make-formularios-excluir-'+p.count+'" class="b2make-tooltip" title="Op&ccedil;&otilde;es dispon&iacute;veis para modificar o campo atual.">'+b2make.msgs.formulariosCamposOpcoes+'</label><div class="clear"></div>');
	var excluir = $('<div class="b2make-formularios-excluir b2make-tooltip" id="b2make-formularios-excluir-'+p.count+'" title="Clique para excluir este campo."></div>');
	var obrigatorio = $('<input type="checkbox"'+(p.obrigatorio ? ' checked="checked"' : '')+' class="b2make-formularios-obrigatorio b2make-tooltip" title="Marque nesta caixa se os campos devem ser preenchidos obrigatoriamente ou n&atilde;o."></div>');
	
	var coluna_1 = $('<div class="b2make-formularios-coluna b2make-formularios-coluna-1"></div>');
	var coluna_2 = $('<div class="b2make-formularios-coluna b2make-formularios-coluna-2"></div>');
	var coluna_3 = $('<div class="b2make-formularios-coluna"></div>');
	
	var formulario = $('<div class="b2make-formularios-campos" data-id="'+p.id+'"></div>');
	
	coluna_1.append(nome_lbl);
	coluna_1.append(nome_input);
	coluna_1.append(campo_input);
	
	coluna_2.append(select_lbl);
	coluna_2.append(select);
	coluna_2.append(select_options_link);
	
	coluna_3.append(excluir_lbl);
	coluna_3.append(excluir);
	coluna_3.append(obrigatorio);
	
	formulario.append(coluna_1);
	formulario.append(coluna_2);
	formulario.append(coluna_3);
	
	$('#b2make-formularios-adicionar').before(formulario);
	
	$('.b2make-tooltip').tooltip({
		show: {
			effect: "fade",
			delay: 400
		}
	});
}

function formularios_campos(p){
	if(!p) p = {};
	
	$('#b2make-formularios-campos').html('');
	
	if(!b2make.formularios.campos_started[b2make.formularios_atual]){
		formularios_campos_banco(p);
		return false;
	}
	
	$('#b2make-formularios-dados').show();
	
	var dados = b2make.formularios.dados[b2make.formularios_atual];
	var found;
	var count = 0;
	
	var adicionar = $('<div id="b2make-formularios-adicionar" class="b2make-tooltip b2make-btn-lightbox" title="Clique para adicionar um novo campo.">NOVO</div>');
	
	$('#b2make-formularios-campos').append(adicionar);
	
	$('.b2make-tooltip').tooltip({
		show: {
			effect: "fade",
			delay: 400
		}
	});
	
	var assunto = (b2make.formularios.dados[b2make.formularios_atual].assunto ? b2make.formularios.dados[b2make.formularios_atual].assunto : '');
	var email = (b2make.formularios.dados[b2make.formularios_atual].email ? b2make.formularios.dados[b2make.formularios_atual].email : '');
	
	$('#b2make-formularios-assunto').val(assunto);
	$('#b2make-formularios-email').val(email);
	
	var campos = b2make.formularios.campos[b2make.formularios_atual];
	
	if(campos)
	for(var i=0;i<campos.length;i++){
		count++;
		campos[i].count = count;
		campos[i].nome = campos[i].title;
		campos[i].id = campos[i].id_site_formularios_campos;
		
		formularios_campo(campos[i]);
	}
	
}

function formularios_campos_banco(p){
	if(!p) p = {};
	
	var opcao = 'formularios-campos';
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : opcao,
			id_site_formularios : (p.id_referencia ? p.id_referencia : b2make.formularios_atual)
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						var id_referencia = (p.id_referencia ? p.id_referencia : b2make.formularios_atual);
						
						b2make.formularios.campos_started[id_referencia] = true;
						b2make.formularios.campos[id_referencia] = dados.campos;
						formularios_campos(p);
					break;
					default:
						console.log('ERROR - '+opcao+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+opcao+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+opcao+' - '+txt);
		}
	});
}

function formularios_campos_order_start(obj,e){
	if(b2make.formularios_campos_mouseup)return;
	
	var top = $(obj).offset().top;
	var left = $(obj).offset().left;
	var mx = e.pageX - left;
	var my = e.pageY - top;
	
	$(obj).css('position','absolute');
	$(obj).css('zIndex','999');
	
	b2make.formularios_campos_mousemove = true;
	b2make.formularios_campos_obj = obj;
	b2make.formularios_campos_obj_x = mx;
	b2make.formularios_campos_obj_y = my;
	b2make.formularios_campos_obj_w = parseInt($(obj).outerWidth(true));
	b2make.formularios_campos_obj_h = parseInt($(obj).outerHeight(true));
	
	var mx_start = e.pageX - $('#b2make-formularios-campos').offset().left;
	var my_start = e.pageY - $('#b2make-formularios-campos').offset().top;
	
	b2make.formularios_campos_linha = Math.floor((my_start / b2make.formularios_campos_obj_h));
	
	formularios_campos_order_grid(b2make.formularios_campos_linha);
	
	mx_start = mx_start - b2make.formularios_campos_obj_x;
	my_start = my_start - b2make.formularios_campos_obj_y;
	
	$(obj).css('left',mx_start);
	$(obj).css('top',my_start);
}

function formularios_campos_order_stop(){
	b2make.formularios_campos_mousemove = false;
	
	if(!b2make.formularios_mask)return;
	
	$(b2make.formularios_mask).before(b2make.formularios_campos_obj);
	
	$(b2make.formularios_campos_obj).css('position','relative');
	$(b2make.formularios_campos_obj).css('zIndex','auto');
	$(b2make.formularios_campos_obj).css('top','auto');
	$(b2make.formularios_campos_obj).css('left','auto');
	
	b2make.formularios_mask.hide();
	
	var count = 0;
	var ids = '';
	
	$('.b2make-formularios-campos').each(function(){
		count++;
		var id = $(this).attr('data-id');
		
		ids = ids + (ids ? ';' : '') + id + ',' + count;
	});
	
	var opcao = 'formularios-campos-order';
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : opcao,
			id_formularios : b2make.formularios_atual,
			ids : ids
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						count = 0;
						var campos = b2make.formularios.campos[b2make.formularios_atual];
						var campos2 = new Array();
						
						$('.b2make-formularios-campos').each(function(){
							var id_aux = $(this).attr('data-id');
							
							for(var i=0;i<campos.length;i++){
								if(id_aux == campos[i].id){
									campos2.push(campos[i]);
									break;
								}
							}
						});
						
						b2make.formularios.campos[b2make.formularios_atual] = campos2;
						
						formularios_widgets_update({type:'change-order'});
					break;
					default:
						console.log('ERROR - '+opcao+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+opcao+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+opcao+' - '+txt);
		}
	});
}

function formularios_campos_order_grid(linha){
	var total = parseInt($('.b2make-formularios-campos').length - 1);
	
	if(!b2make.formularios_mask){
		b2make.formularios_mask = $('<div id="b2make-formularios-mask"></div>');
		b2make.formularios_mask.appendTo('#b2make-formularios-campos-hide');
		
	} else {
		b2make.formularios_mask = $('#b2make-formularios-mask');
	}
	
	var count = 0;
	b2make.formularios_mask_position = linha;
	
	if(b2make.formularios_mask_position < 0) b2make.formularios_mask_position = 0;
	if(b2make.formularios_mask_position > total) b2make.formularios_mask_position = total;
	
	b2make.formularios_mask.show();
	$('.b2make-formularios-campos').each(function(){
		var id = $(this).attr('id');
		
		if(count == b2make.formularios_mask_position){
			b2make.formularios_mask.appendTo('#b2make-formularios-campos-hide');
			
			switch(count){
				case 0:
					b2make.formularios_mask.prependTo('#b2make-formularios-campos');
				break;
				default:
					$(this).after(b2make.formularios_mask);
			}
			return false;
		}
		count++;
	});
	
	b2make.formularios_campos_linha = linha;
}

function formularios_campos_mouseup(){
	if(b2make.formularios_campos_mousedown){
		b2make.formularios_campos_mousedown = false;
		formularios_campos_order_stop();
	}
	
	b2make.formularios_campos_mousemove = false;
	b2make.formularios_campos_mouseup = true;
}

function formularios_campos_opcoes_add(){
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-formularios-campos-opcoes-add-calback',
		title: b2make.msgs.formulariosCamposOpcoesAddTitle,
		coneiner: 'b2make-formulario-formularios-campos-opcoes'
	});
}

function formularios_campos_opcoes_add_base(){
	var id_func = 'formularios-campos-opcoes-add';
	var form_id = 'b2make-formulario-formularios-campos-opcoes';
	
	if($.formulario_testar(form_id)){
		var ajaxData = { 
			ajax : 'sim',
			campo_id : b2make.formularios.campos_opcoes_atual_id,
			opcao : id_func
		};
		
		var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;}); var serialize = $('#'+form_id).serialize(); if(serialize) ajaxDataString = ajaxDataString + '&' + serialize;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: ajaxDataString,
			beforeSend: function(){
				$.carregamento_open();
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							var campos = b2make.formularios.campos[b2make.formularios_atual];
							var id = b2make.formularios.campos_opcoes_atual_id;
							
							if(campos){
								for(var i=0;i<campos.length;i++){
									if(campos[i].id == id){
										var campo_opcoes = b2make.formularios.campos[b2make.formularios_atual][i].campo_opcoes;
										
										if(!campo_opcoes) campo_opcoes = new Array();
										
										campo_opcoes.push(dados);
										
										campo_opcoes.sort(function (a, b) {
											if (a.nome > b.nome) {
												return 1;
											}
											if (a.nome < b.nome) {
												return -1;
											}
											
											return 0;
										});
										
										b2make.formularios.campos[b2make.formularios_atual][i].campo_opcoes = campo_opcoes;
										
										break;
									}
								}
								
							}
							
							formularios_campos_opcoes_html({
								prepend : true,
								id : dados.id,
								nome : dados.nome
							});
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							
							formularios_widgets_update({type:'opcoes-add'});
							
							$.dialogbox_close();
						break;
						case 'SemPermissao':
							sem_permissao_redirect();
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
				$.carregamento_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
				$.carregamento_close();
			}
		});
	}
}

function formularios_campos_opcoes_edit(id){
	$('#b2make-formulario-formularios-campos-opcoes #b2make-ffco-nome').val($('.b2make-formularios-campos-opcoes-nome[data-formularios-campos-opcoes-id="'+id+'"]').html());
	
	b2make.formularios_campos_opcoes_edit_id = id;
	
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-formularios-campos-opcoes-edit-calback',
		title: b2make.msgs.formulariosCamposOpcoesEditTitle,
		coneiner: 'b2make-formulario-formularios-campos-opcoes'
	});
}

function formularios_campos_opcoes_edit_base(){
	var id_func = 'formularios-campos-opcoes-edit';
	var form_id = 'b2make-formulario-formularios-campos-opcoes';
	var id = b2make.formularios_campos_opcoes_edit_id;
	var campo_id = b2make.formularios.campos_opcoes_atual_id;
	
	b2make.formularios_campos_opcoes_edit_id = false;
	
	if($.formulario_testar(form_id)){
		var ajaxData = { 
			ajax : 'sim',
			opcao : id_func,
			campo_id : campo_id,
			id:id
		};
		
		var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;}); var serialize = $('#'+form_id).serialize(); if(serialize) ajaxDataString = ajaxDataString + '&' + serialize;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: ajaxDataString,
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							$('.b2make-formularios-campos-opcoes-nome[data-formularios-campos-opcoes-id="'+id+'"]').html(dados.nome);
							
							var campos = b2make.formularios.campos[b2make.formularios_atual];
							
							if(campos){
								for(var i=0;i<campos.length;i++){
									if(campos[i].id == campo_id){
										var campo_opcoes = b2make.formularios.campos[b2make.formularios_atual][i].campo_opcoes;
										
										if(campo_opcoes)
										for(var j=0;j<campo_opcoes.length;j++){
											if(campo_opcoes[j].id == id){
												campo_opcoes[j].nome = dados.nome;
											}
										}
										
										campo_opcoes.sort(function (a, b) {
											if (a.nome > b.nome) {
												return 1;
											}
											if (a.nome < b.nome) {
												return -1;
											}
											
											return 0;
										});
										
										b2make.formularios.campos[b2make.formularios_atual][i].campo_opcoes = campo_opcoes;
										
										break;
									}
								}
								
							}
							
							formularios_widgets_update({type:'opcoes-edit'});
							
							$.dialogbox_close();
						break;
						case 'SemPermissao':
							sem_permissao_redirect();
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
}

function formularios_campos_opcoes_del(id){
	b2make.formularios_campos_opcoes_del_id = id;
	
	var msg = b2make.msgs.formulariosCamposOpcoesDelTitle;
	msg = msg.replace(/#opcao#/gi,$('.b2make-formularios-campos-opcoes-nome[data-formularios-campos-opcoes-id="'+id+'"]').html());
	
	$.dialogbox_open({
		confirm:true,
		calback_yes: 'b2make-formularios-campos-opcoes-del-calback',
		msg: msg
	});
}

function formularios_campos_opcoes_del_base(){
	var id_func = 'formularios-campos-opcoes-del';
	var id = b2make.formularios_campos_opcoes_del_id;
	var campo_id = b2make.formularios.campos_opcoes_atual_id;
	
	b2make.formularios_campos_opcoes_del_id = false;

	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			campo_id : campo_id,
			id:id
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						$('.b2make-formularios-campos-opcoes-delete[data-formularios-campos-opcoes-id="'+id+'"]').parent().remove();
						
						var campos = b2make.formularios.campos[b2make.formularios_atual];
						
						if(campos){
							for(var i=0;i<campos.length;i++){
								if(campos[i].id == campo_id){
									var campo_opcoes = b2make.formularios.campos[b2make.formularios_atual][i].campo_opcoes;
									var campo_opcoes_novo = new Array();
									
									if(campo_opcoes)
									for(var j=0;j<campo_opcoes.length;j++){
										if(campo_opcoes[j].id != id){
											campo_opcoes_novo.push(campo_opcoes[j]);
										}
									}
									
									b2make.formularios.campos[b2make.formularios_atual][i].campo_opcoes = campo_opcoes_novo;
									
									break;
								}
							}
							
						}
						
						formularios_widgets_update({type:'opcoes-del'});
						
						$.dialogbox_close();
					break;
					default:
						console.log('ERROR - '+id_func+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+id_func+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
}

function formularios_campos_opcoes_html(dados){
	if(!dados)dados = {};
	
	var dado = $('<div class="b2make-formularios-lista-select-options"><div class="b2make-formularios-campos-opcoes-nome" data-formularios-campos-opcoes-id="'+dados.id+'">'+dados.nome+'</div><div class="b2make-formularios-campos-opcoes-edit b2make-tooltip" data-formularios-campos-opcoes-id="'+dados.id+'" title="'+b2make.msgs.formulariosCamposOpcoesEdit+'"></div><div class="b2make-formularios-campos-opcoes-delete b2make-tooltip" data-formularios-campos-opcoes-id="'+dados.id+'" title="'+b2make.msgs.formulariosCamposOpcoesDelete+'"></div><div class="clear"></div></div>');
	
	if(dados.prepend){
		$('#b2make-formularios-lista-select-options').prepend(dado);
	} else {
		$('#b2make-formularios-lista-select-options').append(dado);
	}
}

function formularios_exceto_estes_elementos(e){
	if(
		$(e.target).hasClass('b2make-formularios-excluir') || 
		$(e.target).hasClass('b2make-formularios-select-tipos') || 
		$(e.target).hasClass('b2make-formularios-campo') || 
		$(e.target).hasClass('b2make-formularios-campo-nome') || 
		$(e.target).hasClass('b2make-formularios-select-options-edit') || 
		$(e.target).attr('for')
	){
		return false;
	} else {
		return true;
	}
	
}

function formularios_menu_html(dados){
	if(!dados)dados = {};
	$('#b2make-formularios-lista-formularios').prepend($('<div class="b2make-formularios-lista-formularios"><div class="b2make-formularios-show b2make-tooltip" title="'+b2make.msgs.formulariosShow+'" data-status="'+(dados.formularios_show ? 'show' : 'not-show')+'" data-formularios-id="'+dados.formularios_id+'"></div><div class="b2make-formularios-nome b2make-tooltip" title="'+b2make.msgs.formulariosNome+'" data-status="'+(dados.formularios_selected ? 'show' : 'not-show')+'" data-formularios-id="'+dados.formularios_id+'">'+dados.formularios_nome+'</div><div class="b2make-formularios-edit b2make-tooltip" data-formularios-id="'+dados.formularios_id+'" title="'+b2make.msgs.formulariosEdit+'"></div><div class="b2make-formularios-delete b2make-tooltip" data-formularios-id="'+dados.formularios_id+'" title="'+b2make.msgs.formulariosDelete+'"></div><div class="clear"></div></div>'));
}

function formularios_widget_create(p){
	if(!p) p = {};
	
	var obj = b2make.conteiner_child_obj;
	
	var widget_class = 'b2make-widget-formularios';
	var widget_referencia_id = b2make.formularios_atual;
	var widget_id = 'widget-formularios-'+b2make.widgets_count;
	
	var widget = '<form class="'+widget_class+'" id="'+widget_id+'" data-pub-id="'+variaveis_js.pub_id+'"></form>';
	
	widget = widget + '<div class="b2make-library-loading"></div>';
	$(obj).attr('data-referencia-id',widget_referencia_id);
	$(obj).find('.b2make-widget-out').html(widget);
	$(obj).addClass('b2make-pagina-mestre');
	$(obj).find('.b2make-widget-out').find('.'+widget_class).addClass('b2make-widget-pagina-mestre');
	
	formularios_widgets_update({id:$(obj).attr('id'),widget_add:true});
}

function formularios_widgets_update(p){
	if(!p)p = {};
	
	var plugin_id = 'formularios';
	
	$(b2make.widget).each(function(){
		switch($(this).attr('data-type')){
			case plugin_id:
				var obj_selected = b2make.conteiner_child_obj;
				var obj = this;
				var widget = $(obj).find('.b2make-widget-out').find('.b2make-widget-formularios');
				var id = $(obj).attr('id');
				var id_selected = $(obj_selected).attr('id');
				var id_referencia = $(obj).attr('data-referencia-id');
				
				if(p.id){
					if(p.id != id){
						return;
					}
				}
				
				switch(p.type){
					case 'del':
						if(p.id_referencia == id_referencia){
							widget.html('');
							widget.parent().find('.b2make-library-loading').show();
							$(obj).attr('data-referencia-id','0');
							id_referencia = '0';
						}
					case 'add':
						if((!id_referencia || id_referencia == '0') && id == id_selected){
							$(obj).attr('data-referencia-id',b2make.formularios_atual);
							id_referencia = b2make.formularios_atual;
						}
					default:
						widget.html('');
				
						if(id_referencia == '0' || !id_referencia){
							$(obj).find('.b2make-widget-out').find('.b2make-library-loading').show();
							return true;
						}
						
						var campos = b2make.formularios.campos[id_referencia];
						var label,input;
						
						if(campos)
						for(var i=0;i<campos.length;i++){
							label = $('<label for="'+campos[i].campo+'">'+(campos[i].title ? campos[i].title : campos[i].nome)+'</label>');
							
							switch(campos[i].tipo){
								case 'text': input = $('<input type="text" id="'+campos[i].campo+'" name="'+campos[i].campo+'">'); break;
								case 'textarea': input = $('<textarea id="'+campos[i].campo+'" name="'+campos[i].campo+'"></textarea>'); break;
								case 'select': 
									input = $('<select id="'+campos[i].campo+'" name="'+campos[i].campo+'"></select>');
									
									var campo_opcoes = campos[i].campo_opcoes;
									var options_label = campos[i].options_label;
									
									var option = $('<option value="-1">'+(options_label ? options_label : b2make.msgs.formulariosCamposOpcoesLabelTitle)+'</option>');
									input.append(option);
									
									if(campo_opcoes){
										for(var j=0;j<campo_opcoes.length;j++){
											var option = $('<option value="'+campo_opcoes[j].id+'">'+campo_opcoes[j].nome+'</option>');
											input.append(option);
										}
									}
								break;
								case 'checkbox': 
									input = $('<div id="'+campos[i].campo+'"></div>');
									
									var campo_opcoes = campos[i].campo_opcoes;
									
									if(campo_opcoes){
										for(var j=0;j<campo_opcoes.length;j++){
											var option = $('<div class="b2make-formularios-checkbox-cont"><input type="checkbox" value="'+campo_opcoes[j].id+'" data-campo="'+campos[i].campo+'" name="'+campos[i].campo+'_'+(j+1)+'"><label>'+campo_opcoes[j].nome+'</label></div>');
											input.append(option);
										}
									}
								break;
							}
							
							widget.append(label);
							widget.append(input);
						}
						
						var button = $('<input type="button" class="b2make-formularios-button" value="'+b2make.msgs.formulariosButtonValue+'">');
						widget.append(button);
						
						$(obj).find('.b2make-widget-out').find('.b2make-library-loading').hide();
						
						if(p.widget_add){
							return;
						}
						
						// Colors
						
						if($(obj).attr('data-caixa-color-ahex')){
							var bg = $.jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex'));
							
							$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('background-color',bg);
							$(obj).find('.b2make-widget-out').find('form').find('textarea').css('background-color',bg);
						}
						
						if($(obj).attr('data-caixa-color-2-ahex')){
							var bg = $.jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-2-ahex'));
							
							$(obj).find('.b2make-widget-out').find('form').find('input[type="button"]').css('background-color',bg);
						}
						
						if($(obj).attr('data-rotulo-color-ahex')){
							var bg = $.jpicker_ahex_2_rgba($(obj).attr('data-rotulo-color-ahex'));
							
							$(obj).find('.b2make-widget-out').find('form').find('label').css('color',bg);
						}
						
						if($(obj).attr('data-preenchimento-color-ahex')){
							var bg = $.jpicker_ahex_2_rgba($(obj).attr('data-preenchimento-color-ahex'));
							
							$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('color',bg);
							$(obj).find('.b2make-widget-out').find('form').find('textarea').css('color',bg);
						}
						
						if($(obj).attr('data-botao-color-ahex')){
							var bg = $.jpicker_ahex_2_rgba($(obj).attr('data-botao-color-ahex'));
							
							$(obj).find('.b2make-widget-out').find('form').find('input[type="button"]').css('color',bg);
						}
						
						// Bordas
						
						var target;
						var target2;
						
						target = $(obj).find('.b2make-widget-out').find('form').find('input[type="text"]');
						target2 = $(obj).find('.b2make-widget-out').find('form').find('textarea');
						
						$.bordas_update({
							borda_name : 'data-borda-caixa',
							obj : obj,
							target : target
						});
						$.bordas_update({
							borda_name : 'data-borda-caixa',
							obj : obj,
							target : target2
						});
						
						var todas = $(obj).attr('data-borda-caixa');
						var todas_saida = '';
						
						if(!todas)todas = '0;solid;rgb(0,0,0);0;000000ff';
						
						var todas_arr = todas.split(';');
						
						var p2 = parseInt(todas_arr[3]);
						var w = parseInt(todas_arr[0]) * 2 + 20 + p2;
						
						target.css('width','calc(100% - '+w+'px)');
						target2.css('width','calc(100% - '+w+'px)');
						target.css('padding',Math.floor(p2/2)+'px');
						target2.css('padding',Math.floor(p2/2)+'px');
						target2.css('height','calc(70px + '+w+'px)');
						
						target = $(obj).find('.b2make-widget-out').find('form').find('input[type="button"]');
						
						$.bordas_update({
							borda_name : 'data-borda-botao',
							obj : obj,
							target : target
						});
						
						// Font
						
						var types = new Array('rotulo','preenchimento','botao');
						var modifications = new Array('changeFontFamily','changeFontSize','changeFontAlign','changeFontItalico','changeFontNegrito');
						
						for(var i=0;i<types.length;i++){
							var target;
							var cssVar = '';
							var noSize = false;
							var type = types[i];
							
							switch(type){
								case 'rotulo': target = $(obj).find('.b2make-widget-out').find('form').find('label'); break;
								case 'preenchimento': target = $(obj).find('.b2make-widget-out').find('form').find('input[type="text"],textarea'); break;
								case 'botao': target = $(obj).find('.b2make-widget-out').find('form').find('input[type="button"]'); noSize = true; break;
							
							}
							
							for(var j=0;j<modifications.length;j++){
								switch(modifications[j]){
									case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(obj).attr('data-'+type+'-font-family')); break;
									case 'changeFontSize': cssVar = 'fontSize';  target.css(cssVar,$(obj).attr('data-'+type+'-font-size')+'px'); target.css('line-height',$(obj).attr('data-'+type+'-font-size')+'px');
										var size = parseInt($(obj).attr('data-'+type+'-font-size')); target.css('padding',Math.floor(size/2.5)+'px '+Math.floor(size/3)+'px'); 
										if(!noSize){
											target.css('width','calc(100% - '+(20 + Math.floor(size/2.5)*2)+'px)');
										}
									break;
									case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(obj).attr('data-'+type+'-font-align')); break;
									case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(obj).attr('data-'+type+'-font-italico') == 'sim' ? 'italic' : 'normal')); break;
									case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(obj).attr('data-'+type+'-font-negrito') == 'sim' ? 'bold' : 'normal')); break;
								}	
							}
						}
						
				}
				
			break;
		}
	});
}

function formularios_add(){
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-formularios-add-calback',
		title: b2make.msgs.formulariosAddTitle,
		coneiner: 'b2make-formulario-formularios'
	});
}

function formularios_add_base(){
	var id_func = 'formularios-add';
	var form_id = 'b2make-formulario-formularios';
	
	if($.formulario_testar(form_id)){
		var ajaxData = { 
			ajax : 'sim',
			opcao : id_func
		};
		
		var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;}); var serialize = $('#'+form_id).serialize(); if(serialize) ajaxDataString = ajaxDataString + '&' + serialize;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: ajaxDataString,
			beforeSend: function(){
				$.carregamento_open();
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							$('.b2make-formularios-show').each(function(){
								$(this).attr('data-status','not-show');
							});
							
							dados.formularios_show = true;
							dados.formularios_selected = true;
							formularios_menu_html(dados);
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							$.dialogbox_close();
							
							b2make.formularios_atual = dados.formularios_id;
							b2make.formularios_nome = dados.formularios_nome;
							
							$('#b2make-formularios-btn-mask').hide();
							
							if(!b2make.formularios_todos_ids)b2make.formularios_todos_ids = new Array();
							b2make.formularios_todos_ids.push(dados.formularios_id);
							
							b2make.formularios.campos[dados.formularios_id] = new Array();
							
							b2make.formularios.campos[dados.formularios_id] = dados.campos;
							
							b2make.formularios.dados[dados.formularios_id] = {
								nome : dados.formularios_nome,
								assunto : '',
								email : ''
							};
							
							formularios_campos(false);
							formularios_widgets_update({type:'add'});
						break;
						case 'SemPermissao':
							sem_permissao_redirect();
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
				$.carregamento_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
				$.carregamento_close();
			}
		});
	}
}

function formularios_edit(id){
	$('#b2make-formulario-formularios #b2make-ff-nome').val($('.b2make-formularios-nome[data-formularios-id="'+id+'"]').html());
	
	b2make.formularios_edit_id = id;
	
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-formularios-edit-calback',
		title: b2make.msgs.formulariosEditTitle,
		coneiner: 'b2make-formulario-formularios'
	});
}

function formularios_edit_base(){
	var id_func = 'formularios-edit';
	var form_id = 'b2make-formulario-formularios';
	var id = b2make.formularios_edit_id;
	
	b2make.formularios_edit_id = false;
	
	if($.formulario_testar(form_id)){
		var ajaxData = { 
			ajax : 'sim',
			opcao : id_func,
			id:id
		};
		
		var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;}); var serialize = $('#'+form_id).serialize(); if(serialize) ajaxDataString = ajaxDataString + '&' + serialize;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: ajaxDataString,
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							$('.b2make-formularios-nome[data-formularios-id="'+id+'"]').html(dados.nome);
							
							formularios_widgets_update({type:'edit',id:id,nome:dados.nome});
							$.dialogbox_close();
						break;
						case 'SemPermissao':
							sem_permissao_redirect();
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
}

function formularios_del(id){
	b2make.formularios_del_id = id;
	
	var msg = b2make.msgs.formulariosDelTitle;
	msg = msg.replace(/#formularios#/gi,$('.b2make-formularios-nome[data-formularios-id="'+id+'"]').html());
	
	$.dialogbox_open({
		confirm:true,
		calback_yes: 'b2make-formularios-del-calback',
		msg: msg
	});
}

function formularios_del_base(){
	var id_func = 'formularios-del';
	var id = b2make.formularios_del_id;
	
	b2make.formularios_del_id = false;

	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			id:id
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						$('.b2make-formularios-delete[data-formularios-id="'+id+'"]').parent().remove();
						
						if($('.b2make-formularios-nome').length == 0)$('#b2make-formularios-dados').hide();
						
						$.dialogbox_close();
						
						var id_aux = $('#b2make-formularios-lista-formularios .b2make-formularios-lista-formularios:first-child .b2make-formularios-show').attr('data-formularios-id');
						
						$('#b2make-formularios-lista-images').html('');
						
						if(id_aux){
							b2make.formularios_atual = id_aux;
							b2make.formularios_nome = $('.b2make-formularios-nome[data-formularios-id="'+id_aux+'"]').html();
							
							$('.b2make-formularios-nome').each(function(){
								$(this).attr('data-status','not-show');
							});
							
							$('.b2make-formularios-nome[data-formularios-id="'+id_aux+'"]').attr('data-status','show');
						} else {
							$('#b2make-formularios-btn-mask').show();
							b2make.formularios_atual = false;
						}
						
						$.disk_usage_diskused_del(dados.size);
						formularios_widgets_update({type:'del',id_referencia:id});
					break;
					default:
						console.log('ERROR - '+id_func+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+id_func+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
}

function formularios_start(){
	var plugin_id = 'formularios';
	
	if(!b2make.formularios) b2make.formularios = {};
	
	if(!b2make.formularios.enviar_btn) b2make.formularios.enviar_btn = 'Enviar';
	if(!b2make.msgs.formulariosEdit)b2make.msgs.formulariosEdit = 'Clique para Editar o Nome deste formul&aacute;rio';
	if(!b2make.msgs.formulariosShow)b2make.msgs.formulariosShow = 'Clique para selecionar este formul&aacute;rio no widget formul&aacute;rios.';
	if(!b2make.msgs.formulariosNome)b2make.msgs.formulariosNome = 'Clique para selecionar este formul&aacute;rio no widget formul&aacute;rios.';
	if(!b2make.msgs.formulariosDelete)b2make.msgs.formulariosDelete = 'Clique para deletar este formul&aacute;rio.';
	if(!b2make.msgs.formulariosCamposNome)b2make.msgs.formulariosCamposNome = 'Campo';
	if(!b2make.msgs.formulariosCamposTipo)b2make.msgs.formulariosCamposTipo = 'Tipos';
	if(!b2make.msgs.formulariosCamposOpcoes)b2make.msgs.formulariosCamposOpcoes = 'Op&ccedil;&otilde;es';
	if(!b2make.msgs.formulariosCamposOpcoesEdit)b2make.msgs.formulariosCamposOpcoesEdit = 'Clique para editar o nome desta op&ccedil;&atilde;o';
	if(!b2make.msgs.formulariosCamposOpcoesDelete)b2make.msgs.formulariosCamposOpcoesDelete = 'Clique para deletar o nome desta op&ccedil;&atilde;o';
	if(!b2make.msgs.formulariosAddTitle)b2make.msgs.formulariosAddTitle = 'Adicionar formul&aacute;rio';
	if(!b2make.msgs.formulariosCamposOpcoesAddTitle)b2make.msgs.formulariosCamposOpcoesAddTitle = 'Adicionar Op&ccedil;&atilde;o';
	if(!b2make.msgs.formulariosEditTitle)b2make.msgs.formulariosEditTitle = 'Editar Nome do formul&aacute;rio';
	if(!b2make.msgs.formulariosCamposOpcoesEditTitle)b2make.msgs.formulariosCamposOpcoesEditTitle = 'Editar Op&ccedil;&atilde;o';
	if(!b2make.msgs.formulariosDelTitle)b2make.msgs.formulariosDelTitle = 'Tem certeza que deseja excluir <b>#formularios#</b>?';
	if(!b2make.msgs.formulariosCamposOpcoesDelTitle)b2make.msgs.formulariosCamposOpcoesDelTitle = 'Tem certeza que deseja excluir <b>#opcao#</b>?';
	if(!b2make.msgs.formulariosButtonValue)b2make.msgs.formulariosButtonValue = 'Enviar';
	if(!b2make.msgs.formulariosCamposOpcoesLabelTitle)b2make.msgs.formulariosCamposOpcoesLabelTitle = 'Selecione...';
	
	$('#b2make-formularios-dados').hide();
	
	b2make.formularios.dados = new Array();
	b2make.formularios.campos = new Array();
	b2make.formularios.campos_started = new Array();
	b2make.formularios.campos_tipos = new Array();
	
	b2make.formularios.campos_tipos.push({tipo:'text',label:'Texto'});
	b2make.formularios.campos_tipos.push({tipo:'textarea',label:'Caixa'});
	b2make.formularios.campos_tipos.push({tipo:'select',label:'Sele&ccedil;&atilde;o'});
	b2make.formularios.campos_tipos.push({tipo:'checkbox',label:'Checagem'});
	
	$(b2make.widget).each(function(){
		switch($(this).attr('data-type')){
			case plugin_id:
				$.widgets_read_google_font({
					tipo : 2,
					types : new Array('rotulo','preenchimento','botao'),
					obj : $(this)
				});
			break;
		}
	});
	
	var id_func = plugin_id;
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						var formularios_show,formularios_selected;
						var formularios_todos_ids = new Array();
						
						for(var i=0;i<dados.resultado.length;i++){
							formularios_show = false;
							formularios_selected = false;
							
							if(i==dados.resultado.length - 1){
								b2make.formularios_atual = dados.resultado[i].id_site_formularios;
								b2make.formularios_nome = dados.resultado[i].nome;
								formularios_selected = true;
								formularios_show = true;
								$('#b2make-formularios-btn-mask').hide();
								
								$('#b2make-formularios-email').val(dados.resultado[i].email);
								$('#b2make-formularios-assunto').val(dados.resultado[i].assunto);
							}
							
							var campos = dados.resultado[i].campos;
							
							if(campos){
								b2make.formularios.campos[dados.resultado[i].id_site_formularios] = campos;
							} else {
								b2make.formularios.campos[dados.resultado[i].id_site_formularios] = new Array();
							}
							
							formularios_menu_html({
								formularios_selected:formularios_selected,
								formularios_show:formularios_show,
								formularios_id:dados.resultado[i].id_site_formularios,
								formularios_nome:dados.resultado[i].nome
							});
							
							if(!b2make.formularios_todos_ids){
								formularios_todos_ids.push(dados.resultado[i].id_site_formularios);
							}
							
							b2make.formularios.dados[dados.resultado[i].id_site_formularios] = {
								nome : dados.resultado[i].nome,
								assunto : dados.resultado[i].assunto,
								email : dados.resultado[i].email
							};
						}
						
						formularios_campos(false);
						
						if(!b2make.formularios_todos_ids){
							b2make.formularios_todos_ids = formularios_todos_ids;
						}
						
						if(b2make.formularios_widget_added)formularios_widget_create({formularios_id:b2make.formularios_atual});
						b2make.formularios_widget_added_2 = true;
						
						$('.b2make-tooltip').tooltip({
							show: {
								effect: "fade",
								delay: 400
							}
						});
						
						formularios_widgets_update({type:'start'});
					break;
					case 'Vazio':
						
					break;
					default:
						console.log('ERROR - '+id_func+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+id_func+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
	
	$('#b2make-formularios-add').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_add();
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-add-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_add_base();
	});
	
	$('#b2make-wofc-bordas-cont').on('changeBorda',function(e){
		var obj = b2make.conteiner_child_obj;
		var target;
		var target2;
		
		target = $(obj).find('.b2make-widget-out').find('form').find('input[type="text"]');
		target2 = $(obj).find('.b2make-widget-out').find('form').find('textarea');
		
		$.bordas_update({
			obj : this,
			target : target
		});
		$.bordas_update({
			obj : this,
			target : target2
		});
		
		var todas = $(this).attr('data-borda-caixa');
		var todas_saida = '';
		
		if(!todas)todas = '0;solid;rgb(0,0,0);0;000000ff';
		
		var todas_arr = todas.split(';');
		
		var p = parseInt(todas_arr[3]);
		var w = parseInt(todas_arr[0]) * 2 + 20 + p;
		
		target.css('width','calc(100% - '+w+'px)');
		target2.css('width','calc(100% - '+w+'px)');
		target.css('padding',Math.floor(p/2)+'px');
		target2.css('padding',Math.floor(p/2)+'px');
		target2.css('height','calc(70px + '+w+'px)');
		
	});
	
	$('#b2make-wofc-bordas-cont-2').on('changeBorda',function(e){
		var obj = b2make.conteiner_child_obj;
		var target;
		
		target = $(obj).find('.b2make-widget-out').find('form').find('input[type="button"]');
		
		$.bordas_update({
			obj : this,
			target : target
		});
	});
	
	$('#b2make-wofc-botao-cor-val,#b2make-wofc-preenchimento-cor-val,#b2make-wofc-rotulo-cor-val,#b2make-wofc-caixa-cor-val,#b2make-wofc-caixa-cor-val-2').on('changeColor',function(e){
		var id = $(this).attr('id');
		var bg = $(b2make.jpicker.obj).css('background-color');
		var ahex = $(b2make.jpicker.obj).attr('data-ahex');
		var obj = b2make.conteiner_child_obj;
		
		switch(id){
			case 'b2make-wofc-caixa-cor-val':
				$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('background-color',bg);
				$(obj).find('.b2make-widget-out').find('form').find('textarea').css('background-color',bg);
				$(obj).attr('data-caixa-color-ahex',ahex);
			break;
			case 'b2make-wofc-caixa-cor-val-2':
				$(obj).find('.b2make-widget-out').find('form').find('input[type="button"]').css('background-color',bg);
				$(obj).attr('data-caixa-color-2-ahex',ahex);
			break;
			case 'b2make-wofc-rotulo-cor-val':
				$(obj).find('.b2make-widget-out').find('form').find('label').css('color',bg);
				$(obj).attr('data-rotulo-color-ahex',ahex);
			break;
			case 'b2make-wofc-preenchimento-cor-val':
				$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('color',bg);
				$(obj).find('.b2make-widget-out').find('form').find('textarea').css('color',bg);
				$(obj).attr('data-preenchimento-color-ahex',ahex);
			break;
			case 'b2make-wofc-botao-cor-val':
				$(obj).find('.b2make-widget-out').find('form').find('input[type="button"]').css('color',bg);
				$(obj).attr('data-botao-color-ahex',ahex);
			break;
			
		}
	});
	
	$('#b2make-wofc-botao-text-cont,#b2make-wofc-preenchimento-text-cont,#b2make-wofc-rotulo-text-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
		var obj = b2make.conteiner_child_obj;
		var target;
		var target2 = false;
		var cssVar = '';
		var noSize = false;
		var type = $(this).attr('id')
		
		type = type.replace(/b2make-wofc-/gi,'');
		type = type.replace(/-text-cont/gi,'');
		
		switch($(this).attr('id')){
			case 'b2make-wofc-rotulo-text-cont': target = $(obj).find('.b2make-widget-out').find('form').find('label'); break;
			case 'b2make-wofc-preenchimento-text-cont': target = $(obj).find('.b2make-widget-out').find('form').find('input[type="text"],textarea'); break;
			case 'b2make-wofc-botao-text-cont': target = $(obj).find('.b2make-widget-out').find('form').find('input[type="button"]'); noSize = true; break;
		
		}
		
		switch(e.type){
			case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).attr('data-font-family')); $(obj).attr('data-'+type+'-font-family',$(this).attr('data-font-family')); break;
			case 'changeFontSize': cssVar = 'fontSize';  target.css(cssVar,$(this).attr('data-font-size')+'px'); target.css('line-height',$(this).attr('data-font-size')+'px'); $(obj).attr('data-'+type+'-font-size',$(this).attr('data-font-size')); 
				var size = parseInt($(this).attr('data-font-size')); target.css('padding',Math.floor(size/2.5)+'px '+Math.floor(size/3)+'px'); 
				if(!noSize){
					target.css('width','calc(100% - '+(20 + Math.floor(size/2.5)*2)+'px)');
				}
			break;
			case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).attr('data-font-align'));$(obj).attr('data-'+type+'-font-align',$(this).attr('data-font-align')); break;
			case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).attr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).attr('data-'+type+'-font-italico',$(this).attr('data-font-italico')); break;
			case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).attr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).attr('data-'+type+'-font-negrito',$(this).attr('data-font-negrito')); break;
		}
	});

	$(document.body).on('mousedown','.b2make-formularios-campos',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();
		
		if(formularios_exceto_estes_elementos(e)){
			var obj = this;
			
			b2make.formularios_campos_mouseup = false;
			
			setTimeout(function(){
				b2make.formularios_campos_mousedown = true;
				formularios_campos_order_start(obj,e);
			},400);
		}
	});
	
	$(document.body).on('taphold','.b2make-formularios-campos',function(e){
		e.stopPropagation();
		
		if(formularios_exceto_estes_elementos(e)){
			b2make.formularios_campos_mouseup = false;
			b2make.formularios_campos_mousedown = true;
			
			formularios_campos_order_start(this,e);
		}
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-excluir',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var obj = $(this).parent().parent();
		var id = obj.attr('data-id');
		
		var id_func = 'formularios-campos-del';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id_site_formularios : b2make.formularios_atual,
				id : id
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							var campos = b2make.formularios.campos[b2make.formularios_atual];
							var campos2 = new Array();
							
							for(var i=0;i<campos.length;i++){
								if(campos[i].id != id){
									campos2.push(campos[i]);
								}
							}
							
							b2make.formularios.campos[b2make.formularios_atual] = campos2;
							
							formularios_widgets_update({type:'del',id_referencia:id});
							
							obj.remove();
						break;
						case 'Vazio':
							
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	});
	
	$(window).on('mousemove touchmove',function(e){
		if(b2make.formularios_campos_mousemove){
			var holder = '#b2make-formularios-campos';
			var ajuste_x = 0;
			var obj = b2make.formularios_campos_obj;
			var mx = e.pageX - $(holder).offset().left;
			var my = e.pageY - $(holder).offset().top;
			
			if(mx < 0)mx = 0; if(mx > $(holder).width()) mx = $(holder).width();
			if(my < 0)my = 0; if(my > $(holder).height()) my = $(holder).height();
			
			$(obj).css('left',mx - b2make.formularios_campos_obj_x + ajuste_x);
			$(obj).css('top',my - b2make.formularios_campos_obj_y);
			
			var linha = Math.floor((my / b2make.formularios_campos_obj_h));
			
			if(
				b2make.formularios_campos_linha != linha
			)
				formularios_campos_order_grid(linha);
		}
	});
	
	$(window).on('mouseup tap',function(e){
		formularios_campos_mouseup();
	});
	
	$(document.body).on('mouseup tap','#b2make-formularios-adicionar',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var id_func = 'formularios-campos-add';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id_site_formularios : b2make.formularios_atual
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							b2make.formularios.campos[b2make.formularios_atual].push({obrigatorio : dados.obrigatorio , count : (b2make.formularios.campos[b2make.formularios_atual].length + 1), id_site_formularios_campos : dados.id_site_formularios_campos, id : dados.id , nome : dados.nome , campo : dados.campo , tipo : 'text'});
							formularios_campo({obrigatorio : dados.obrigatorio , count : (b2make.formularios.campos[b2make.formularios_atual].length + 1), id_site_formularios_campos : dados.id_site_formularios_campos, id : dados.id , nome : dados.nome , campo : dados.campo , tipo : 'text'});
							formularios_widgets_update({type:'add'});
						break;
						case 'Vazio':
							
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-show',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		if($(this).attr('data-status') == 'not-show'){
			$('.b2make-formularios-show').each(function(){
				$(this).attr('data-status','not-show');
			});
			
			$(this).attr('data-status','show');
		}
		
		$('.b2make-formularios-nome').each(function(){
			$(this).attr('data-status','not-show');
		});
		
		var nome_obj = $(this).parent().find('.b2make-formularios-nome');
		
		nome_obj.attr('data-status','show');
		
		b2make.formularios_atual = nome_obj.attr('data-formularios-id');
		b2make.formularios_nome = nome_obj.html();
		
		$('#b2make-formularios-lista-images').html('');
		formularios_campos(false);
		
		var obj = b2make.conteiner_child_obj;
		$(obj).attr('data-referencia-id',b2make.formularios_atual);
		formularios_widgets_update({id:$(obj).attr('id')});
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-nome',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();
		
		if($(this).parent().find('.b2make-formularios-show').attr('data-status') == 'not-show'){
			$('.b2make-formularios-show').each(function(){
				$(this).attr('data-status','not-show');
			});
			
			$(this).parent().find('.b2make-formularios-show').attr('data-status','show');
		}
		
		$('.b2make-formularios-nome').each(function(){
			$(this).attr('data-status','not-show');
		});
		
		$(this).attr('data-status','show');
		
		b2make.formularios_atual = $(this).attr('data-formularios-id');
		b2make.formularios_nome = $(this).html();
		
		formularios_campos(false);
		
		var obj = b2make.conteiner_child_obj;
		$(obj).attr('data-referencia-id',b2make.formularios_atual);
		formularios_widgets_update({id:$(obj).attr('id')});
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-edit',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-formularios-id');
		formularios_edit(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-edit-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_edit_base();
	});

	$(document.body).on('mouseup tap','.b2make-formularios-delete',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-formularios-id');
		formularios_del(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-del-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_del_base();
	});
	
	$(document.body).on('keyup','#b2make-formularios-email,#b2make-formularios-assunto',function(e){
		var value = $(this).val();
		var id = $(this).attr('id');
		var campo = id.replace(/b2make-formularios-/gi,'');
		
		b2make.input_delay_params = {campo:campo};
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-formularios-change',
			value:value
		});
	});
	
	$('#b2make-listener').on('b2make-formularios-change',function(e,value,p){
		if(!p) p = {};
		
		var id_func = 'formularios-vars';
		var campo = p.campo;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				id : b2make.formularios_atual,
				campo : campo,
				value : value,
				opcao : id_func
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							b2make.formularios.dados[b2make.formularios_atual][campo] = value;
						break;
						case 'Vazio':
							
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
		
	});
	
	$(document.body).on('keyup','.b2make-formularios-campo-nome',function(e){
		var value = $(this).val();
		var id = $(this).parent().parent().attr('data-id');
		
		b2make.input_delay_params = {id:id};
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-formularios-campo-change',
			value:value
		});
	});
	
	$('#b2make-listener').on('b2make-formularios-campo-change',function(e,value,p){
		if(!p) p = {};
		
		var id_func = 'formularios-campos-val';
		var id = p.id;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				id_formularios : b2make.formularios_atual,
				id_campos : id,
				value : value,
				opcao : id_func
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							var campos = b2make.formularios.campos[b2make.formularios_atual];
							
							if(campos)
							for(var i=0;i<campos.length;i++){
								if(campos[i].id == id){
									b2make.formularios.campos[b2make.formularios_atual][i].campo = dados.campo;
									b2make.formularios.campos[b2make.formularios_atual][i].title = value;
									break;
								}
							}
							
							$('.b2make-formularios-campos[data-id="'+id+'"]').find('.b2make-formularios-coluna').find('.b2make-formularios-campo').html(dados.campo);
							formularios_widgets_update({type:'change-field'});
						break;
						case 'Vazio':
							
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
		
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-obrigatorio',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).parent().parent().attr('data-id');
		var checked = 'n';
		var obrigatorio = false;
		
		if(!$(this).prop('checked')){
			checked = 's';
			obrigatorio = true;
		}
		
		var id_func = 'formularios-campos-obrigatorio';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				id_formularios : b2make.formularios_atual,
				id_campos : id,
				checked : checked,
				opcao : id_func
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							var campos = b2make.formularios.campos[b2make.formularios_atual];
							
							if(campos)
							for(var i=0;i<campos.length;i++){
								if(campos[i].id == id){
									b2make.formularios.campos[b2make.formularios_atual][i].obrigatorio = obrigatorio;
									break;
								}
							}
						break;
						case 'Vazio':
							
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	});
	
	$(document.body).on('change','.b2make-formularios-select-tipos',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).parent().parent().attr('data-id');
		var select_options = $(this).parent().find('.b2make-formularios-select-options-edit');
		var value = $(this).val();
		
		var id_func = 'formularios-campos-tipo';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				id_formularios : b2make.formularios_atual,
				id_campos : id,
				value : value,
				opcao : id_func
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							var campos = b2make.formularios.campos[b2make.formularios_atual];
							
							if(campos)
							for(var i=0;i<campos.length;i++){
								if(campos[i].id == id){
									b2make.formularios.campos[b2make.formularios_atual][i].tipo = value;
									break;
								}
							}
							
							if(value == 'select' || value == 'checkbox'){
								select_options.show();
							} else {
								select_options.hide();
							}
							
							formularios_widgets_update({type:'change-type'});
						break;
						case 'Vazio':
							
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-select-options-edit',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var campos = b2make.formularios.campos[b2make.formularios_atual];
		var id = $(this).attr('data-id');
		
		b2make.formularios.campos_opcoes_atual_id = id;
		
		if(campos)
		for(var i=0;i<campos.length;i++){
			if(campos[i].id == id){
				$('#b2make-formularios-select-options-campo-lbl').html('Campo: '+(campos[i].title ? campos[i].title : campos[i].nome));
				
				var campo_opcoes = campos[i].campo_opcoes;
				var tipo = campos[i].tipo;
				
				if(tipo == 'select'){
					var label = '';
					
					if($(this).attr('data-options-label') == "undefined"){
						label = b2make.msgs.formulariosCamposOpcoesLabelTitle;
					} else {
						label = $(this).attr('data-options-label');
					}
					
					$('#b2make-formularios-select-options-label').val(label);
					$('#b2make-formularios-select-options-label').show();
					$('#b2make-formularios-select-options-label-lbl').show();
				} else {
					$('#b2make-formularios-select-options-label').hide();
					$('#b2make-formularios-select-options-label-lbl').hide();
				}
				
				$('#b2make-formularios-lista-select-options').html('');
				
				if(campo_opcoes)
				for(var j=0;j<campo_opcoes.length;j++){
					formularios_campos_opcoes_html(campo_opcoes[j]);
				}
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				break;
			}
		}
	});
	
	$('#b2make-formularios-select-options-add').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_campos_opcoes_add();
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-campos-opcoes-add-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_campos_opcoes_add_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-campos-opcoes-edit',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-formularios-campos-opcoes-id');
		formularios_campos_opcoes_edit(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-campos-opcoes-edit-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_campos_opcoes_edit_base();
	});

	$(document.body).on('mouseup tap','.b2make-formularios-campos-opcoes-delete',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-formularios-campos-opcoes-id');
		formularios_campos_opcoes_del(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-formularios-campos-opcoes-del-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		formularios_campos_opcoes_del_base();
	});
	
	$(document.body).on('keyup','#b2make-formularios-select-options-label',function(e){
		var value = $(this).val();
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-formularios-select-options-label-change',
			value:value
		});
	});
	
	$('#b2make-listener').on('b2make-formularios-select-options-label-change',function(e,value,p){
		if(!p) p = {};
		
		var id_func = 'formularios-campos-label-change';
		var campo_id = b2make.formularios.campos_opcoes_atual_id;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				campo_id : campo_id,
				value : value,
				opcao : id_func
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							$('.b2make-formularios-select-options-edit[data-id="'+campo_id+'"]').attr('data-options-label',dados.value);
							
							var campos = b2make.formularios.campos[b2make.formularios_atual];
							
							if(campos){
								for(var i=0;i<campos.length;i++){
									if(campos[i].id == campo_id){
										b2make.formularios.campos[b2make.formularios_atual][i].options_label = dados.value;
										break;
									}
								}
								
							}
							
							formularios_widgets_update({type:'opcoes-label'});
						break;
						case 'Vazio':
							
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
		
	});
	
}

var _plugin_id = 'formularios';

window[_plugin_id] = function(){
	var id_func = 'formularios';
	var plugin_id = _plugin_id;
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/html.html',
		beforeSend: function(){
		},
		success: function(txt){
			var html = $('<div>'+txt+'</div>');
			
			var options = html.find('#b2make-widget-options-'+plugin_id).clone();
			options.appendTo('#b2make-widget-options-hide');
			var sub_options = html.find('#b2make-widget-sub-options-'+plugin_id).clone();
			sub_options.appendTo('#b2make-widget-options-hide');
			var sub_options = html.find('#b2make-widget-sub-options-formularios-select-options').clone();
			sub_options.appendTo('#b2make-widget-options-hide');
			
			if(b2make.widget_sub_options_open)$.widget_sub_options_open();
			
			var formulario = html.find('#b2make-formulario-formularios').clone();
			formulario.appendTo('#b2make-formularios');
			var formulario = html.find('#b2make-formulario-formularios-campos-opcoes').clone();
			formulario.appendTo('#b2make-formularios');
			
			$.fonts_load({obj:'#b2make-widget-options-'+plugin_id});
			$.jpicker_load({obj:'#b2make-widget-options-'+plugin_id});
			
			$.menu_conteiner_aba_load({
				id:plugin_id,
				html:html.find('#b2make-conteiner-aba-extra-'+plugin_id).clone()
			});
			
			$.fonts_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			$.jpicker_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			
			$.widget_specific_options_open();
			$.menu_conteiner_aba_extra_open();
			
			$.bordas_manual_start('#b2make-wofc-bordas-cont');
			$.bordas_manual_start('#b2make-wofc-bordas-cont-2');
			
			
			formularios_start();
		},
		error: function(txt){
			console.log('ERROR AJAX - '+plugin_id+' - html - '+txt);
		}
	});
	
	$('#b2make-'+plugin_id+'-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:plugin_id});
	});
	
	$('#b2make-'+plugin_id+'-callback').on('widget_added',function(e){
		if(b2make.formularios_widget_added_2)formularios_widget_create({formularios_id:b2make.formularios_atual});
		b2make.formularios_widget_added = true;
	});
	
	$('#b2make-'+plugin_id+'-callback').on('conteiner_child_open',function(e){
		var obj = b2make.conteiner_child_obj;
		
		var id_referencia = $(obj).attr('data-referencia-id');
		
		if(id_referencia != b2make.formularios_atual){
			$('.b2make-formularios-show').each(function(){
				$(this).attr('data-status','not-show');
			});
			
			$('.b2make-formularios-nome').each(function(){
				$(this).attr('data-status','not-show');
			});
			
			$('.b2make-formularios-lista-formularios').find('.b2make-formularios-show[data-formularios-id="'+id_referencia+'"]').attr('data-status','show');
			$('.b2make-formularios-lista-formularios').find('.b2make-formularios-nome[data-formularios-id="'+id_referencia+'"]').attr('data-status','show');
			
			b2make.formularios_atual = id_referencia;
			b2make.formularios_nome = $(obj).html();
			
			formularios_campos(false);
		}
		
		$.bordas_menu_open({
			obj : $('#b2make-wofc-bordas-cont')
		});
		
		$.bordas_menu_open({
			obj : $('#b2make-wofc-bordas-cont-2')
		});
		
		if($(obj).attr('data-caixa-color-ahex')){
			$('#b2make-wofc-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex')));
			$('#b2make-wofc-caixa-cor-val').attr('data-ahex',$(obj).attr('data-caixa-color-ahex'));
		} else {
			$('#b2make-wofc-caixa-cor-val').css('background-color','#ffffff');
			$('#b2make-wofc-caixa-cor-val').attr('data-ahex','ffffffff');
		}
		
		if($(obj).attr('data-caixa-color-2-ahex')){
			$('#b2make-wofc-caixa-cor-val-2').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-2-ahex')));
			$('#b2make-wofc-caixa-cor-val-2').attr('data-ahex',$(obj).attr('data-caixa-color-2-ahex'));
		} else {
			$('#b2make-wofc-caixa-cor-val-2').css('background-color','#D7D9DD');
			$('#b2make-wofc-caixa-cor-val-2').attr('data-ahex','d7d9ddff');
		}
		
		if($(obj).attr('data-rotulo-color-ahex')){
			$('#b2make-wofc-rotulo-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-rotulo-color-ahex')));
			$('#b2make-wofc-rotulo-cor-val').attr('data-ahex',$(obj).attr('data-rotulo-color-ahex'));
		} else {
			$('#b2make-wofc-rotulo-cor-val').css('background-color','#58585B');
			$('#b2make-wofc-rotulo-cor-val').attr('data-ahex','58585bff');
		}
		
		if($(obj).attr('data-preenchimento-color-ahex')){
			$('#b2make-wofc-preenchimento-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-preenchimento-color-ahex')));
			$('#b2make-wofc-preenchimento-cor-val').attr('data-ahex',$(obj).attr('data-preenchimento-color-ahex'));
		} else {
			$('#b2make-wofc-preenchimento-cor-val').css('background-color','#58585B');
			$('#b2make-wofc-preenchimento-cor-val').attr('data-ahex','58585bff');
		}
		
		if($(obj).attr('data-botao-color-ahex')){
			$('#b2make-wofc-botao-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-botao-color-ahex')));
			$('#b2make-wofc-botao-cor-val').attr('data-ahex',$(obj).attr('data-botao-color-ahex'));
		} else {
			$('#b2make-wofc-botao-cor-val').css('background-color','#58585B');
			$('#b2make-wofc-botao-cor-val').attr('data-ahex','58585bff');
		}
		
		var fonts = new Array('rotulo','preenchimento','botao');
		var sizes = new Array('11','11','11');
		
		for(var i=0;i<fonts.length;i++){
			var type = fonts[i];
			var size = sizes[i];
			
			if($(obj).attr('data-'+type+'-font-family')){
				$('#b2make-wofc-'+type+'-text-cont').find('.b2make-fonts-holder').css({
					'fontFamily': $(obj).attr('data-'+type+'-font-family')
				});
				$('#b2make-wofc-'+type+'-text-cont').find('.b2make-fonts-holder').html($(obj).attr('data-'+type+'-font-family'));
			} else {
				$('#b2make-wofc-'+type+'-text-cont').find('.b2make-fonts-holder').css({
					'fontFamily': b2make.font
				});
				$('#b2make-wofc-'+type+'-text-cont').find('.b2make-fonts-holder').html(b2make.font);
			}
			
			if($(obj).attr('data-'+type+'-font-size')){
				$('#b2make-wofc-'+type+'-text-cont').find('.b2make-fonts-size').val($(obj).attr('data-'+type+'-font-size'));
			} else {
				$('#b2make-wofc-'+type+'-text-cont').find('.b2make-fonts-size').val(size);
			}
		}
	});
	
	$('#b2make-'+plugin_id+'-callback').on('conteiner_child_open_finished',function(e){
		var obj = b2make.conteiner_child_obj;
		
	});
	
}

var fn = window[_plugin_id];fn();
