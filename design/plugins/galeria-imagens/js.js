var _plugin_id = 'galeria-imagens';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function galeria_imagens_layout_tipo(p){
	if(!p)p = {};
	
	var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
	var layout_tipo = $(obj).attr('data-layout-tipo');
	
	switch(layout_tipo){
		case 'mosaico':
			$('#b2make-wo-galeria-imagens-tamanho-lbl').hide();
			$('#b2make-wo-galeria-imagens-tamanho').hide();
			$('#b2make-wo-galeria-imagens-botao-texto-lbl').show();
			$('#b2make-wo-galeria-imagens-botao-texto').show();
			$('#b2make-wo-galeria-imagens-descricao-cor-lbl').show();
			$('#b2make-wo-galeria-imagens-descricao-cor-val').show();
			$('#b2make-wo-galeria-imagens-descricao-altura-lbl').show();
			$('#b2make-wo-galeria-imagens-descricao-altura').show();
			$('#b2make-wo-galeria-imagens-descricao-posicao-lbl').show();
			$('#b2make-wo-galeria-imagens-descricao-posicao').show();
		break;
		default:
			$('#b2make-wo-galeria-imagens-tamanho-lbl').show();
			$('#b2make-wo-galeria-imagens-tamanho').show();
			$('#b2make-wo-galeria-imagens-botao-texto-lbl').hide();
			$('#b2make-wo-galeria-imagens-botao-texto').hide();
			$('#b2make-wo-galeria-imagens-descricao-cor-lbl').hide();
			$('#b2make-wo-galeria-imagens-descricao-cor-val').hide();
			$('#b2make-wo-galeria-imagens-descricao-altura-lbl').hide();
			$('#b2make-wo-galeria-imagens-descricao-altura').hide();
			$('#b2make-wo-galeria-imagens-descricao-posicao-lbl').hide();
			$('#b2make-wo-galeria-imagens-descricao-posicao').hide();
	}
}

function galeria_imagens_widgets_update(p){
		if(!p)p = {};
		
		switch(p.type){
			case 'galeria-imagens-imagem-uploaded':
				var id = p.id;
				
				$('div.b2make-widget[data-type="galeria-imagens"][data-galeria-imagens-id="'+id+'"]').each(function(){
					galeria_imagens_widget_create({conteiner_child_obj:this,galeria_imagens_id:id});
				});
			break;
			case 'galeria-imagens-imagem-del':
				var id = p.id;
				
				$('div.b2make-widget[data-type="galeria-imagens"][data-galeria-imagens-id="'+id+'"]').each(function(){
					galeria_imagens_widget_create({conteiner_child_obj:this,galeria_imagens_id:id});
				});
			break;
			case 'galeria-imagens-delete':
				var id = p.id;
				
				$('div.b2make-widget[data-type="galeria-imagens"][data-galeria-imagens-id="'+id+'"]').each(function(){
					galeria_imagens_widget_create({conteiner_child_obj:this,galeria_imagens_id:id});
				});
			break;
			case 'mudar-tamanho':
				var obj = b2make.conteiner_child_obj;
				var value = p.value;
				
				$(obj).find('.b2make-widget-out').find('.b2make-galeria-imagens-widget-holder').find('.b2make-galeria-imagens-widget-image').each(function(){
					$(this).css('width',value+'px');
					$(this).css('height',value+'px');
				});
			break;
			case 'mudar-margem':
				var obj = b2make.conteiner_child_obj;
				var value = p.value;
				var layout_tipo = ($(obj).attr('data-layout-tipo') ? $(obj).attr('data-layout-tipo') : 'padrao');
				
				switch(layout_tipo){
					case 'mosaico':
						galeria_imagens_widget_create({});
					break;
					default:
						$(obj).find('.b2make-widget-out').find('.b2make-galeria-imagens-widget-holder').find('.b2make-galeria-imagens-widget-image').each(function(){
							$(this).css('margin',value+'px');
						});
				}
			break;
			
		}
	}

function galeria_imagens_widget_create(p){
	var plugin_id = 'galeria-imagens';
	if(!p)p = {};
	
	var id_func = 'galeria-imagens-images';
	var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
	var galeria_imagens_id = (p.galeria_imagens_id ? p.galeria_imagens_id : b2make.galeria_imagens_atual);
	var layout_tipo = ($(obj).attr('data-layout-tipo') ? $(obj).attr('data-layout-tipo') : 'padrao');
	var obj_id = $(obj).attr('id');
	
	$(obj).attr('data-galeria-imagens-id',galeria_imagens_id);
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/index.php',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			id : galeria_imagens_id
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				if(p.criar){
					$('#b2make-wo-galeria-imagens-tamanho').val(200);
					$('#b2make-wo-galeria-imagens-margem').val(10);
				}
				
				var tamanho = ($(obj).attr('data-tamanho') ? $(obj).attr('data-tamanho') : $('#b2make-wo-galeria-imagens-tamanho').val());
				var margem = ($(obj).attr('data-margem') ? $(obj).attr('data-margem') : $('#b2make-wo-galeria-imagens-margem').val());
				var holder;
				
				switch(layout_tipo){
					case 'mosaico':
						$(obj).find('.b2make-widget-out').html('<div class="b2make-galeria-imagens-widget-holder-2"></div>');
						holder = $(obj).find('div.b2make-widget-out').find('div.b2make-galeria-imagens-widget-holder-2');
					break;
					default:
						$(obj).find('.b2make-widget-out').html('<div class="b2make-galeria-imagens-widget-holder"></div>');
						holder = $(obj).find('div.b2make-widget-out').find('div.b2make-galeria-imagens-widget-holder');
				}
				
				
				switch(dados.status){
					case 'Ok':
						if(dados.images.length == 0){
							var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
							holder.append($('<div id="b2make-galeria-imagens-widget-imagem-0" class="b2make-galeria-imagens-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+'); width:'+tamanho+'px; height:'+tamanho+'px; margin:'+margem+'px;"></div>'));
							$(obj).attr('data-imagens-urls',imagem);
						} else {
							switch(layout_tipo){
								case 'mosaico':
									if($(obj).attr('data-descricao-color-ahex')){
										var descricao_color = $.jpicker_ahex_2_rgba($(obj).attr('data-descricao-color-ahex'));
									} else {
										var descricao_color = '#1b4174';
									}
									
									if($(obj).attr('data-descricao-text-color-ahex')){
										var descricao_text_color = $.jpicker_ahex_2_rgba($(obj).attr('data-descricao-text-color-ahex'));
									} else {
										var descricao_text_color = '#FFFFFF';
									}
									
									if($(obj).attr('data-descricao-altura')){
										var descricao_altura = $(obj).attr('data-descricao-altura');
									} else {
										var descricao_altura = '40';
									}
									
									if($(obj).attr('data-descricao-posicao')){
										var descricao_posicao = $(obj).attr('data-descricao-posicao');
									} else {
										var descricao_posicao = 'topo';
									}
									
									if($(obj).attr('data-descricao-texto')){
										var descricao_texto = $(obj).attr('data-descricao-texto');
									} else {
										var descricao_texto = '';
									}
									
									var margens = new Array();
									
									for(var j=0;j<=100;j++){
										if(j % 4 == 3){
											margens[j] = true;
										}
									}
									
									for(var i=0;i<4;i++){
										var style = '';
										var width = '';
										var height = '';
										
										switch(i){
											case 0: 
												height = 'calc(50% - '+Math.floor((parseInt(descricao_altura)+parseInt(margem))/2)+'px)';
												style = 'width: 100%; height: '+height+';';
											break;
											case 1:
												width = 'calc(50% - '+Math.floor(parseInt(margem)/2)+'px)';
												height = 'calc(50% - '+(Math.floor((parseInt(descricao_altura)+parseInt(margem))/2))+'px)';
												style = 'width: '+width+'; height: '+height+'; margin-top: '+margem+'px;';
											break;
											case 2:
												width = 'calc(50% - '+Math.floor(parseInt(margem)/2 + (margem % 2 == 0 ? 0 : 1))+'px)';
												height = 'calc(25% - '+Math.floor(((parseInt(descricao_altura)+parseInt(margem))/2)/2 + parseInt(margem)/2)+'px)';
												style = 'width: '+width+'; height: '+height+'; margin-top: '+margem+'px; margin-left: '+margem+'px;';
											break;
											case 3:
												width = 'calc(50% - '+Math.floor(parseInt(margem)/2 + (margem % 2 == 0 ? 0 : 1))+'px)';
												height = 'calc(25% - '+Math.ceil(((parseInt(descricao_altura)+parseInt(margem))/2)/2 + parseInt(margem)/2 + (margens[parseInt(margem)] ? -1 : 0))+'px)';
												style = 'width: '+width+'; height: '+height+'; margin-top: '+margem+'px; margin-left: '+margem+'px;';
											break;
										}
										
										holder.append($('<div id="b2make-galeria-imagens-widget-imagem-'+dados.images[i].id+'" class="b2make-galeria-imagens-widget-image-2" data-image-id="'+dados.images[i].id+'" data-image-url="'+dados.images[i].imagem+'" data-image-width="'+dados.images[i].width+'" data-image-height="'+dados.images[i].height+'" style="background-image:url('+dados.images[i].imagem+'); '+style+'"></div>'));
									}
									
									var descricao_cont = $('<div class="b2make-galeria-imagens-descricao-cont" style="background-color:'+descricao_color+';color:'+descricao_text_color+'; height: '+descricao_altura+'px; line-height: '+descricao_altura+'px;">'+descricao_texto+'</div>');
									
									switch(descricao_posicao){
										case 'base':
											holder.append(descricao_cont);
										break;
										default:
											holder.prepend(descricao_cont);
									}
								break;
								default:
									for(var i=0;i<dados.images.length;i++){
										holder.append($('<div id="b2make-galeria-imagens-widget-imagem-'+dados.images[i].id+'" class="b2make-galeria-imagens-widget-image" data-image-id="'+dados.images[i].id+'" data-image-url="'+dados.images[i].imagem+'" data-image-width="'+dados.images[i].width+'" data-image-height="'+dados.images[i].height+'" style="background-image:url('+dados.images[i].imagem+'); width:'+tamanho+'px; height:'+tamanho+'px; margin:'+margem+'px;"></div>'));
									}
							}
							
							var imagens = '';
							
							for(var i=0;i<dados.images.length;i++){
								imagens = imagens + (imagens.length > 0 ? ',' : '') + dados.images[i].imagem;
							}
							
							$(obj).attr('data-imagens-urls',imagens);
						}
					break;
					case 'NaoExisteId':
						var imagem = location.href+'images/b2make-banners-sem-imagem.png?v=2';
						holder.append($('<div id="b2make-galeria-imagens-widget-imagem-0" class="b2make-galeria-imagens-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+'); width:'+tamanho+'px; height:'+tamanho+'px; margin:'+margem+'px;"></div>'));
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

function galeria_imagens_images_html(dados){
	$('#b2make-galeria-imagens-lista-images').append($('<div id="b2make-galeria-imagens-imagem-holder-'+dados.id+'" class="b2make-galeria-imagens-image-holder b2make-tooltip" data-image-id="'+dados.id+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" title="'+b2make.msgs.galeria_imagensFile+': '+dados.file+'"><div class="b2make-galeria-imagens-image-delete b2make-tooltip" title="'+b2make.msgs.galeria_imagensDeleteX+'"></div><img src="'+dados.mini+'"></div>'));
}

function galeria_imagens_images(){	
	plugin_id = 'galeria-imagens';
	var id_func = 'galeria-imagens-images';
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/index.php',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			id : b2make.galeria_imagens_atual
		},
		beforeSend: function(){
			$('#b2make-galeria-imagens-lista-images').find('.b2make-loading-box').remove();
			$('<div class="b2make-loading-box"></div>').appendTo('#b2make-galeria-imagens-lista-images');
		},
		success: function(txt){
			$('#b2make-galeria-imagens-lista-images').find('.b2make-loading-box').remove();
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						for(var i=0;i<dados.images.length;i++){
							galeria_imagens_images_html(dados.images[i]);
						}
						
						$('.b2make-tooltip').tooltip({
							show: {
								effect: "fade",
								delay: 400
							}
						});
						
						if(b2make.galeria_imagens_widget_update){
							galeria_imagens_widgets_update({type:'galeria_imagens-del',id:b2make.galeria_imagens_widget_update_id});
							b2make.galeria_imagens_widget_update_id = false;
							b2make.galeria_imagens_widget_update = false;
						}
					break;
					case 'NaoExisteId':
						// Nada a fazer
					break;
					default:
						console.log('ERROR - '+id_func+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+id_func+' - '+txt);
			}
		},
		error: function(txt){
			$('#b2make-galeria-imagens-lista-images').find('.b2make-loading-box').remove();
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
}

function galeria_imagens_imagens_delete(){	
	plugin_id = 'galeria-imagens';
	var id = b2make.galeria_imagens_imagens_delete_id;
	var id_func = 'galeria-imagens-images-del';
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/index.php',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			id : id,
			galeria_imagens : b2make.galeria_imagens_atual
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
						var url = $('.b2make-galeria-imagens-image-holder[data-image-id="'+id+'"]').attr('data-image-url');
						
						$('.b2make-galeria-imagens-image-holder[data-image-id="'+id+'"]').remove();
						$.disk_usage_diskused_del(dados.size);
						galeria_imagens_widgets_update({type:'galeria-imagens-imagem-del',id:b2make.galeria_imagens_atual});
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

function galeria_imagens_menu_html(dados){
	if(!dados)dados = {};
	$('#b2make-galeria-imagens-lista-galeria').prepend($('<div class="b2make-galeria-imagens-lista-galeria"><div class="b2make-galeria-imagens-show b2make-tooltip" title="'+b2make.msgs.galeria_imagenshow+'" data-status="'+(dados.galeria_imagens_show ? 'show' : 'not-show')+'" data-galeria-imagens-id="'+dados.galeria_imagens_id+'"></div><div class="b2make-galeria-imagens-nome b2make-tooltip" title="'+b2make.msgs.galeria_imagensNome+'" data-status="'+(dados.galeria_imagens_selected ? 'show' : 'not-show')+'" data-galeria-imagens-id="'+dados.galeria_imagens_id+'">'+dados.galeria_imagens_nome+'</div><div class="b2make-galeria-imagens-edit b2make-tooltip" data-galeria-imagens-id="'+dados.galeria_imagens_id+'" title="'+b2make.msgs.galeria_imagensEdit+'"></div><div class="b2make-galeria-imagens-delete b2make-tooltip" data-galeria-imagens-id="'+dados.galeria_imagens_id+'" title="'+b2make.msgs.galeria_imagensDelete+'"></div><div class="clear"></div></div>'));
}

function galeria_imagens_add(){
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-galeria-imagens-add-calback',
		title: b2make.msgs.galeria_imagensAddTitle,
		coneiner: 'b2make-formulario-galeria-imagens'
	});
}

function galeria_imagens_add_base(){
	plugin_id = 'galeria-imagens';
	var id_func = 'galeria-imagens-add';
	var form_id = 'b2make-formulario-galeria-imagens';
	
	if($.formulario_testar(form_id)){
		var ajaxData = { 
			ajax : 'sim',
			opcao : id_func
		};
		
		var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;}); var serialize = $('#'+form_id).serialize(); if(serialize) ajaxDataString = ajaxDataString + '&' + serialize;
		
		
		$.ajax({
			type: 'POST',
			url: 'plugins/'+plugin_id+'/index.php',
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
							$('.b2make-galeria-imagens-nome').each(function(){
								$(this).attr('data-status','not-show');
							});
							
							dados.galeria_imagens_show = true;
							dados.galeria_imagens_selected = true;
							galeria_imagens_menu_html(dados);
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							$.dialogbox_close();
							
							b2make.galeria_imagens_atual = dados.galeria_imagens_id;
							b2make.galeria_imagens_nome = dados.galeria_imagens_nome;
							
							$('#b2make-galeria-imagens-btn-mask').hide();
							$('#b2make-galeria-imagens-lista-images').html('');
							
							galeria_imagens_widget_create({
								galeria_imagens_id: dados.galeria_imagens_id,
								galeria_imagens_nome: dados.galeria_imagens_nome
							});
							
							if(!b2make.galeria_imagens_todos_ids)b2make.galeria_imagens_todos_ids = new Array();
							b2make.galeria_imagens_todos_ids.push(dados.galeria_imagens_id);
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

function galeria_imagens_edit(id){
	$('#b2make-formulario-galeria-imagens #b2make-fgi-nome').val($('.b2make-galeria-imagens-nome[data-galeria-imagens-id="'+id+'"]').html());
	
	b2make.galeria_imagens_edit_id = id;
	
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-galeria-imagens-edit-calback',
		title: b2make.msgs.galeria_imagensEditTitle,
		coneiner: 'b2make-formulario-galeria-imagens'
	});
}

function galeria_imagens_edit_base(){
	plugin_id = 'galeria-imagens';
	var id_func = 'galeria-imagens-edit';
	var form_id = 'b2make-formulario-galeria-imagens';
	var id = b2make.galeria_imagens_edit_id;
	
	b2make.galeria_imagens_edit_id = false;
	
	if($.formulario_testar(form_id)){
		var ajaxData = { 
			ajax : 'sim',
			opcao : id_func,
			id:id
		};
		
		var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;}); var serialize = $('#'+form_id).serialize(); if(serialize) ajaxDataString = ajaxDataString + '&' + serialize;
		
		$.ajax({
			type: 'POST',
			url: 'plugins/'+plugin_id+'/index.php',
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
							$('.b2make-galeria-imagens-nome[data-galeria-imagens-id="'+id+'"]').html(dados.nome);
							
							galeria_imagens_widgets_update({type:'galeria-imagens-edit',id:id,nome:dados.nome});
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

function galeria_imagens_del(id){
	b2make.galeria_imagens_del_id = id;
	
	var msg = b2make.msgs.galeria_imagensDelTitle;
	msg = msg.replace(/#galeria-imagens#/gi,$('.b2make-galeria-imagens-nome[data-galeria-imagens-id="'+id+'"]').html());
	
	$.dialogbox_open({
		confirm:true,
		calback_yes: 'b2make-galeria-imagens-del-calback',
		msg: msg
	});
}

function galeria_imagens_del_base(){
	plugin_id = 'galeria-imagens';
	var id_func = 'galeria-imagens-del';
	var id = b2make.galeria_imagens_del_id;
	
	b2make.galeria_imagens_del_id = false;

	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/index.php',
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
						$('.b2make-galeria-imagens-delete[data-galeria-imagens-id="'+id+'"]').parent().remove();
						$.dialogbox_close();
						
						var id_aux = $('#b2make-galeria-imagens-lista-galeria .b2make-galeria-imagens-lista-galeria-imagens:first-child .b2make-galeria-imagens-show').attr('data-galeria-imagens-id');
						
						$('#b2make-galeria-imagens-lista-images').html('');
						
						if(id_aux){
							b2make.galeria_imagens_atual = id_aux;
							b2make.galeria_imagens_nome = $('.b2make-galeria-imagens-nome[data-galeria-imagens-id="'+id_aux+'"]').html();
							
							$('.b2make-galeria-imagens-nome').each(function(){
								$(this).attr('data-status','not-show');
							});
							
							$('.b2make-galeria-imagens-nome[data-galeria-imagens-id="'+id_aux+'"]').attr('data-status','show');
							
							galeria_imagens_images();
						} else {
							$('#b2make-galeria-imagens-btn-mask').show();
						}
						
						$.disk_usage_diskused_del(dados.size);
						galeria_imagens_widgets_update({type:'galeria-imagens-delete',id:id});
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

function galeria_imagens_upload_params(){
	return new Array({
		variavel : 'galeria-imagens',
		valor : b2make.galeria_imagens_atual,
	})
}

function galeria_imagens_upload_callback(dados){
	var id_func = 'galeria-imagens';
	
	switch(dados.status){
		case 'Ok':
			galeria_imagens_images_html(dados);
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			$.disk_usage_diskused_add(dados.size);
			galeria_imagens_widgets_update({type:'galeria-imagens-imagem-uploaded',id:b2make.galeria_imagens_atual,url:dados.imagem});
		break;
		case 'SemPermissao':
			sem_permissao_redirect();
		break;
		default:
			console.log('ERROR - '+id_func+' - '+dados.status);
		
	}
}

function galeria_imagens_upload(){
	$.upload_files_start_buttons();
	$.upload_files_start({
		url_php : 'plugins/galeria-imagens/uploadgaleria_imagens.php',
		input_selector : '#b2make-galeria-imagens-input',
		file_type : 'imagem',
		post_params : galeria_imagens_upload_params,
		callback : galeria_imagens_upload_callback
	});
}

function galeria_imagens_start(plugin_id){
	
	b2make.galeria_imagens.fator_ajuste = 0.8;
	b2make.galeria_imagens.margin_title = 4;
	b2make.galeria_imagens.margin_image = 0;
	b2make.galeria_imagens.tamanho_max = 500;
	b2make.galeria_imagens.tamanho_min = 10;
	b2make.galeria_imagens.margem_max = 100;
	b2make.galeria_imagens.margem_min = 0;
	
	if(!b2make.msgs.galeria_imagensDeleteX)b2make.msgs.galeria_imagensDeleteX = 'Clique para excluir esta imagem';
	if(!b2make.msgs.galeria_imagensFile)b2make.msgs.galeria_imagensFile = 'Imagem';
	if(!b2make.msgs.galeria_imagensEdit)b2make.msgs.galeria_imagensEdit = 'Clique para Editar o Nome desta galeria de imagens';
	if(!b2make.msgs.galeria_imagensNome)b2make.msgs.galeria_imagensNome = 'Clique para alterar as imagens desta galeria de imagens';
	if(!b2make.msgs.galeria_imagensDelete)b2make.msgs.galeria_imagensDelete = 'Clique para deletar esta galeria de imagens';
	if(!b2make.msgs.galeria_imagenshow)b2make.msgs.galeria_imagenshow = 'Clique para que a galeria de imagens mostre/n&atilde;o mostre esta galeria de imagens no widget galeria de imagens';
	if(!b2make.msgs.galeria_imagensDelTitle)b2make.msgs.galeria_imagensDelTitle = 'Tem certeza que deseja excluir <b>#galeria-imagens#</b>?';
	if(!b2make.msgs.galeria_imagensEditTitle)b2make.msgs.galeria_imagensEditTitle = 'Editar Nome da galeria';
	if(!b2make.msgs.galeria_imagensAddTitle)b2make.msgs.galeria_imagensAddTitle = 'Adicionar galeria';
	if(!b2make.msgs.galeria_imagensDeleteX)b2make.msgs.galeria_imagensDeleteX = 'Clique para excluir esta imagem';
	if(!b2make.msgs.galeria_imagensNaoHa)b2make.msgs.galeria_imagensNaoHa = 'N&atilde;o h&aacute; nenhuma galeria de imagens definida. Clique no bot&atilde;o <b>CRIAR GALERIA</b> antes de enviar imagens.';
	
	galeria_imagens_upload();
	
	b2make.galeria_imagens_confirm_delete = true;
	var id_func = 'galeria-imagens';
	
	b2make.plugin[plugin_id].started = true;
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/index.php',
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
						var galeria_imagens_show,galeria_imagens_selected;
						var galeria_imagens_todos_ids = new Array();
						
						for(var i=0;i<dados.resultado.length;i++){
							galeria_imagens_show = true;
							galeria_imagens_selected = false;
							
							if(i==dados.resultado.length - 1){
								b2make.galeria_imagens_atual = dados.resultado[i].id_site_galeria_imagens;
								b2make.galeria_imagens_nome = dados.resultado[i].nome;
								galeria_imagens_selected = true;
								galeria_imagens_images();
								$('#b2make-galeria-imagens-btn-mask').hide();
							}
							
							galeria_imagens_menu_html({
								galeria_imagens_selected:galeria_imagens_selected,
								galeria_imagens_show:galeria_imagens_show,
								galeria_imagens_id:dados.resultado[i].id_site_galeria_imagens,
								galeria_imagens_nome:dados.resultado[i].nome
							});
							
							if(!b2make.galeria_imagens_todos_ids){
								galeria_imagens_todos_ids.push(dados.resultado[i].id_site_galeria_imagens);
							}
						}
						
						if(!b2make.galeria_imagens_todos_ids){
							b2make.galeria_imagens_todos_ids = galeria_imagens_todos_ids;
						}
						
						if(b2make.galeria_imagens_widget_added)galeria_imagens_widget_create({galeria_imagens_id:b2make.galeria_imagens_atual,criar:true});
						b2make.galeria_imagens_widget_added_2 = true;
						
						if(b2make.galeria_imagens.conteiner_child_open)$('#b2make-'+plugin_id+'-callback').trigger('conteiner_child_open');
						
						$('.b2make-tooltip').tooltip({
							show: {
								effect: "fade",
								delay: 400
							}
						});
					break;
					case 'Vazio':
						// Nada a fazer
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
			console.log(txt);
		}
	});
	
	$('#b2make-galeria-imagens-btn-mask').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		$.dialogbox_open({
			msg: b2make.msgs.galeria_imagensNaoHa
		});
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-image-delete',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();
		var msg = b2make.msgs.imagemDelete;
		
		b2make.galeria_imagens_imagens_delete_id = $(this).parent().attr('data-image-id');
		
		if(b2make.galeria_imagens_confirm_delete){
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-galeria-imagens-image-delete-yes',
				msg: msg
			});
		} else {
			galeria_imagens_imagens_delete();
		}
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-image-delete-yes',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		galeria_imagens_imagens_delete();
	});
	
	$('#b2make-galeria-imagens-confirm-delete').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		$(this).find('input').attr("checked", !$(this).find('input').attr("checked"));
		
		if($(this).find('input').attr("checked")){
			b2make.galeria_imagens_confirm_delete = true;
		} else {
			b2make.galeria_imagens_confirm_delete = false;
		}
	});
	
	$('#b2make-galeria-imagens-confirm-delete-input').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();

		if($(this).attr("checked")){
			b2make.galeria_imagens_confirm_delete = false;
		} else {
			b2make.galeria_imagens_confirm_delete = true;
		}
	});
	
	$('#b2make-galeria-imagens-add').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		galeria_imagens_add();
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-add-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		galeria_imagens_add_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-show',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-galeria-imagens-id');
		var nome = $(this).parent().find('.b2make-galeria-imagens-nome').html();
		
		if($(this).attr('data-status') == 'not-show'){
			$('.b2make-galeria-imagens-show').each(function(){
				$(this).attr('data-status','not-show');
			});
			
			$(this).attr('data-status','show');
			
			galeria_imagens_widget_create({
				galeria_imagens_id: id,
				galeria_imagens_nome: nome
			});
		}
		
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-galeria-imagens-id');
		
		if($(this).attr('data-status') == 'not-show'){
			$('.b2make-galeria-imagens-show').each(function(){
				$(this).attr('data-status','not-show');
			});
			
			$(this).attr('data-status','show');
			
			galeria_imagens_widget_create({galeria_imagens_id:id});
		}
		
		$('.b2make-galeria-imagens-nome').each(function(){
			$(this).attr('data-status','not-show');
		});
		
		var nome_obj = $(this).parent().find('.b2make-galeria-imagens-nome');
		
		nome_obj.attr('data-status','show');
		
		var id = nome_obj.attr('data-galeria-imagens-id');
		
		b2make.galeria_imagens_atual = nome_obj.attr('data-galeria-imagens-id');
		b2make.galeria_imagens_nome = nome_obj.html();
		
		$('#b2make-galeria-imagens-lista-images').html('');
		galeria_imagens_images();
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-nome',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		$('.b2make-galeria-imagens-nome').each(function(){
			$(this).attr('data-status','not-show');
		});
		
		$(this).attr('data-status','show');
		
		var id = $(this).attr('data-galeria-imagens-id');
		
		b2make.galeria_imagens_atual = $(this).attr('data-galeria-imagens-id');
		b2make.galeria_imagens_nome = $(this).html();
		
		$('#b2make-galeria-imagens-lista-images').html('');
		galeria_imagens_images();
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-edit',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-galeria-imagens-id');
		galeria_imagens_edit(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-edit-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		galeria_imagens_edit_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-delete',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-galeria-imagens-id');
		galeria_imagens_del(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-del-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		galeria_imagens_del_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-image-holder',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();
		
		$('.b2make-galeria-imagens-image-holder').removeClass('b2make-galeria-imagens-image-holder-clicked');
		$(this).addClass('b2make-galeria-imagens-image-holder-clicked');
		
		b2make.galeria_imagens_imagem_selected = $(this);
	});
	
	$('#b2make-wo-galeria-imagens-descricao-cor-val,#b2make-wo-galeria-imagens-widget-cor-val').on('changeColor',function(e){
		var id = $(this).attr('id');
		var bg = $(b2make.jpicker.obj).css('background-color');
		var ahex = $(b2make.jpicker.obj).attr('data-ahex');
		var obj = b2make.conteiner_child_obj;
		
		switch(id){
			case 'b2make-wo-galeria-imagens-widget-cor-val':
				$(obj).css('background-color',bg);
				$(obj).attr('data-widget-color-ahex',ahex);	
			break;
			case 'b2make-wo-galeria-imagens-descricao-cor-val':
				$(obj).css('background-color',bg);
				$(obj).attr('data-descricao-color-ahex',ahex);	
			break;
			
		}
	});
	
	$('#b2make-wo-galeria-imagens-tamanho').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.galeria_imagens.tamanho_max){
			this.value = b2make.galeria_imagens.tamanho_max;
			value = b2make.galeria_imagens.tamanho_max;
		}
		
		if(value < b2make.galeria_imagens.tamanho_min){
			value = b2make.galeria_imagens.tamanho_min;
		}
		
		if(!value){
			value = b2make.galeria_imagens.tamanho_min;
		}
		
		$(obj).attr('data-tamanho',value);
		
		galeria_imagens_widgets_update({type:'mudar-tamanho',value:value});
	});
	
	$('#b2make-wo-galeria-imagens-margem').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.galeria_imagens.margem_max){
			this.value = b2make.galeria_imagens.margem_max;
			value = b2make.galeria_imagens.margem_max;
		}
		
		if(value < b2make.galeria_imagens.margem_min){
			value = b2make.galeria_imagens.margem_min;
		}
		
		if(!value){
			value = b2make.galeria_imagens.margem_min;
		}
		
		$(obj).attr('data-margem',value);
		
		galeria_imagens_widgets_update({type:'mudar-margem',value:value});
	});
	
	$(document.body).on('mouseup tap','.b2make-galeria-imagens-widget-image',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var img = $(this).attr('data-image-url');
		var pai = $(this).parent().parent().parent();
		
		if(pai.attr('data-imagens-urls')){
			var imgs_arr = pai.attr('data-imagens-urls').split(',');
			var imgs;
			
			if(imgs_arr.length > 0){
				imgs = new Array();
				imgs.push(img);
				for(var i=0;i<imgs_arr.length;i++){
					if(imgs_arr[i] != img){
						imgs.push(imgs_arr[i]);
					}
				}
				if(!b2make.start_pretty_photo){
					$.fn.prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true});
					b2make.start_pretty_photo = true;
				}
				$.prettyPhoto.open(imgs);
			}
		}
	});
	
	$(document.body).on('change','#b2make-wo-galeria-imagens-layout-tipo',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		$(obj).attr('data-layout-tipo',value);
		
		galeria_imagens_layout_tipo({});
		galeria_imagens_widget_create({});
	});
	
	$(document.body).on('keyup','#b2make-wo-galeria-imagens-botao-texto',function(e){
		var value = $(this).val();
		var id = $(this).attr('id');
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-wo-galeria-imagens-botao-texto-change',
			value:value
		});
	});
	
	$(document.body).on('b2make-wo-galeria-imagens-botao-texto-change','#b2make-listener',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		
		$(obj).attr('data-descricao-texto',value);
		
		galeria_imagens_widget_create({});
	});
	
	$(document.body).on('keyup','#b2make-wo-galeria-imagens-descricao-altura',function(e){
		var value = $(this).val();
		var id = $(this).attr('id');
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-wo-galeria-imagens-descricao-altura-change',
			value:value
		});
	});
	
	$(document.body).on('b2make-wo-galeria-imagens-descricao-altura-change','#b2make-listener',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		
		$(obj).attr('data-descricao-altura',value);
		
		galeria_imagens_widget_create({});
	});
	
	$(document.body).on('change','#b2make-wo-galeria-imagens-descricao-posicao',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		$(obj).attr('data-descricao-posicao',value);
		
		galeria_imagens_widget_create({});
	});
	
}

window[_plugin_id] = function(){
	var plugin_id = _plugin_id;
	
	b2make.galeria_imagens = {};
	
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
			
			var upload = html.find('#b2make-galeria-imagens-btn-real').clone();
			upload.appendTo('#b2make-lightbox');
			
			var formulario = html.find('#b2make-formulario-galeria-imagens').clone();
			formulario.appendTo('#b2make-formularios');
			
			if(!b2make.upload_btn_real) b2make.upload_btn_real = new Array();
			
			b2make.upload_btn_real[plugin_id] = {
				obj : '#b2make-galeria-imagens-btn-real'
			};
			
			$.fonts_load({obj:'#b2make-widget-options-'+plugin_id});
			$.jpicker_load({obj:'#b2make-widget-options-'+plugin_id});
			
			$.menu_conteiner_aba_load({
				id:plugin_id,
				html:html.find('#b2make-conteiner-aba-extra-'+plugin_id).clone()
			});
			
			$.fonts_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			$.jpicker_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			
			if(b2make.plugin[plugin_id].widget_added){
				$.menu_conteiner_aba_extra_open();
				$.widget_specific_options_open();
				$.widget_sub_options_open();
			}
			
			galeria_imagens_start(plugin_id);
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
		},
		error: function(txt){
			console.log('ERROR AJAX - '+plugin_id+' - html - '+txt);
		}
	});
	
	// =========
	
	$('#b2make-'+plugin_id+'-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:plugin_id});
	});
	
	$('#b2make-'+plugin_id+'-callback').on('widget_added',function(e){
		if(b2make.galeria_imagens_widget_added_2)galeria_imagens_widget_create({galeria_imagens_id:b2make.galeria_imagens_atual,criar:true});
		b2make.galeria_imagens_widget_added = true;
		
		if(!b2make.plugin[plugin_id].started){
			b2make.plugin[plugin_id].widget_added = true;			
		}
	});
	
	$('#b2make-'+plugin_id+'-callback').on('conteiner_child_open',function(e){
		var obj = b2make.conteiner_child_obj;
		
		b2make.galeria_imagens.conteiner_child_open = true;
		
		galeria_imagens_layout_tipo({});
		
		if($(obj).attr('data-layout-tipo')){
			var option = $('#b2make-wo-galeria-imagens-layout-tipo').find("[value='" + $(obj).attr('data-layout-tipo') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-wo-galeria-imagens-layout-tipo').find(":first");
			option.prop('selected', 'selected');
		}
		
		if($(obj).attr('data-descricao-texto')){
			$('#b2make-wo-galeria-imagens-botao-texto').val($(obj).attr('data-descricao-texto'));
		} else {
			$('#b2make-wo-galeria-imagens-botao-texto').val('');
		}
		
		if($(obj).attr('data-tamanho')){
			$('#b2make-wo-galeria-imagens-tamanho').val($(obj).attr('data-tamanho'));
		} else {
			$('#b2make-wo-galeria-imagens-tamanho').val('200');
		}
		
		if($(obj).attr('data-margem')){
			$('#b2make-wo-galeria-imagens-margem').val($(obj).attr('data-margem'));
		} else {
			$('#b2make-wo-galeria-imagens-margem').val('10');
		}
		
		if($(obj).attr('data-widget-color-ahex')){
			$('#b2make-wo-galeria-imagens-widget-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-widget-color-ahex')));
			$('#b2make-wo-galeria-imagens-widget-cor-val').attr('data-ahex',$(obj).attr('data-widget-color-ahex'));
		} else {
			$('#b2make-wo-galeria-imagens-widget-cor-val').css('background-color','#ffffff');
			$('#b2make-wo-galeria-imagens-widget-cor-val').attr('data-ahex','#ffffffff');
		}
		
		if($(obj).attr('data-galeria-imagens-id')){
			$('.b2make-galeria-imagens-show').each(function(){
				if($(obj).attr('data-galeria-imagens-id') == $(this).attr('data-galeria-imagens-id')){
					$(this).attr('data-status','show');
				} else {
					$(this).attr('data-status','not-show');
				}
			});
			
			var id = $(obj).attr('data-galeria-imagens-id');
			
			if(b2make.galeria_imagens_todos_ids){
				var galeria_imagens_ids =  b2make.galeria_imagens_todos_ids;
				var found = false;
				
				for(var i=0;i<galeria_imagens_ids.length;i++){
					if(galeria_imagens_ids[i] == $(obj).attr('data-galeria-imagens-id')){
						found = true;
						break;
					}
				}
				
				if(found){
					b2make.galeria_imagens_atual = $(obj).attr('data-galeria-imagens-id');
					b2make.galeria_imagens_foto_nome = $('.b2make-galeria-imagens-nome[data-galeria-imagens-id="'+id+'"]').html();
					
					$('.b2make-galeria-imagens-nome').each(function(){
						$(this).attr('data-status','not-show');
					});
					
					$('.b2make-galeria-imagens-nome[data-galeria-imagens-id="'+id+'"]').attr('data-status','show');
					
					$('#b2make-galeria-imagens-lista-images').html('');
					galeria_imagens_images();
				}
			}
		} else {
			$('.b2make-galeria-imagens-show').each(function(){
				$(this).attr('data-status','not-show');
			});
		}
		
		if($(obj).attr('data-descricao-color-ahex')){
			$('#b2make-wo-galeria-imagens-descricao-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-descricao-color-ahex')));
			$('#b2make-wo-galeria-imagens-descricao-cor-val').attr('data-ahex',$(obj).attr('data-descricao-color-ahex'));
		} else {
			$('#b2make-wo-galeria-imagens-descricao-cor-val').css('background-color','#1b4174');
			$('#b2make-wo-galeria-imagens-descricao-cor-val').attr('data-ahex','#1b4174ff');
		}
		
		if($(obj).attr('data-descricao-altura')){
			$('#b2make-wo-galeria-imagens-descricao-altura').val($(obj).attr('data-descricao-altura'));
		} else {
			$('#b2make-wo-galeria-imagens-descricao-altura').val('40');
		}
		
		if($(obj).attr('data-descricao-posicao')){
			var option = $('#b2make-wo-galeria-imagens-descricao-posicao').find("[value='" + $(obj).attr('data-descricao-posicao') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-wo-galeria-imagens-descricao-posicao').find(":first");
			option.prop('selected', 'selected');
		}
		
	});
	
	$('#b2make-'+plugin_id+'-callback').on('conteiner_child_open_finished',function(e){
		var obj = b2make.conteiner_child_obj;
		
	});
	
}

var fn = window[_plugin_id];fn();