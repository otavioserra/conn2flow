var _plugin_id = 'services';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function formatMoney(n){
	n = parseFloat(n);
var c = isNaN(c = Math.abs(c)) ? 2 : c, 
    d = d == undefined ? "," : d, 
    t = t == undefined ? "." : t, 
    s = n < 0 ? "-" : "", 
    i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))), 
    j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

function services_menu_visibilidade(){
	var obj = b2make.conteiner_child_obj;
	
	$('#b2make-services-lista-cont').hide();
	$('#b2make-services-lista-categoria-cont').hide();
	$('#b2make-services-options-cont').hide();
	
	if($(obj).myAttr('data-categoria')){
		var categoria = $(obj).myAttr('data-categoria');
	} else {
		var categoria = 'escolha-pontual';
	}
	
	switch(categoria){
		case 'todos-servicos': $('#b2make-services-options-cont').show(); break;
		case 'escolha-pontual': 
			$('#b2make-services-lista-cont').show();
			$('#b2make-services-options-cont').show();
		break;
		case 'categoria': 
			$('#b2make-services-lista-categoria-cont').show();
			$('#b2make-services-options-cont').show();
		break;
	}
	
	$('#b2make-services-options-sel option[value="'+categoria+'"]').prop('selected', true);
}

function servicos_widget_html(p){
	if(!p)p={};
	
	var service_cont = $('<div class="b2make-service-cont" data-id="'+p.service.id+'" data-quantidade="'+p.service.quantidade+'" data-validade="'+p.service.validade+'" data-preco="'+p.service.preco+'" data-href="'+p.service.url+'"></div>');
	
	var imagem = $('<div class="b2make-service-imagem" style="background-image:url('+(p.service.url_imagem ? p.service.url_imagem : '//platform.b2make.com/site/images/b2make-album-sem-imagem.png')+');"></div>');
	var name = $('<div class="b2make-service-name">'+p.service.nome+'</div>');
	var descricao = $('<div class="b2make-service-descricao">'+p.service.descricao+'</div>');
	var comprar = $('<a class="b2make-service-comprar" href="'+p.service.url+'">'+(parseInt(p.service.quantidade) > 0?b2make.msgs.servicosComprar:b2make.msgs.servicosIndisponivel)+'</a>');
	var preco = $('<div class="b2make-service-preco">R$ '+formatMoney(p.service.preco)+'</div>');
	
	imagem.appendTo(service_cont);
	name.appendTo(service_cont);
	descricao.appendTo(service_cont);
	comprar.appendTo(service_cont);
	preco.appendTo(service_cont);
	
	if(p.largura_cont){
		service_cont.width(p.largura_cont);
	}
	
	if(p.altura_cont){
		service_cont.height(p.altura_cont);
	}
	
	if(p.altura_img){
		imagem.height(p.altura_img);
	}
	
	if(p.margem){
		service_cont.css('margin',p.margem+'px');
	}
	
	return service_cont;
}

function servicos_widget_update(p){
	if(!p)p={};
	
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	var id_func = 'servicos-html-list';
	
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
						if($(obj).find('.b2make-widget-out').find('.b2make-widget-loading').length == 0)$(obj).find('.b2make-widget-out').append('<div class="b2make-widget-loading"></div>');
						
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').html('');
						
						var services_ids = $(obj).myAttr('data-services-ids');
						var categoria_id = $(obj).myAttr('data-categoria-id');
						var found_service;
						
						if(services_ids)services_ids = services_ids.split(',');
						
						if($(obj).myAttr('data-tamanho-cont')){
							var largura_cont = $(obj).myAttr('data-tamanho-cont');
						} else {
							var largura_cont = 160;
						}
						
						if($(obj).myAttr('data-tamanho-cont-2')){
							var altura_cont = $(obj).myAttr('data-tamanho-cont-2');
						} else {
							var altura_cont = 280;
						}
						
						if($(obj).myAttr('data-altura-imagem')){
							var altura_img = $(obj).myAttr('data-altura-imagem');
						} else {
							var altura_img = 160;
						}
						
						if($(obj).myAttr('data-margem')){
							var margem = $(obj).myAttr('data-margem');
						} else {
							var margem = 10;
						}
						
						if($(obj).myAttr('data-categoria')){
							var categoria = $(obj).myAttr('data-categoria');
						} else {
							var categoria = 'escolha-pontual';
						}
						
						switch(categoria){
							case 'todos-servicos':
								if(dados.services_list){
									var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
									switch(ordem){
										case 'alfabetica-asc':
											dados.services_list.sort(function(a, b){
												if(a.nome < b.nome) return -1;
												if(a.nome > b.nome) return 1;
												return 0;
											});
										break;
										case 'alfabetica-desc':
											dados.services_list.sort(function(a, b){
												if(a.nome > b.nome) return -1;
												if(a.nome < b.nome) return 1;
												return 0;
											});
										break;
										case 'data-asc':
											dados.services_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao < b.data_criacao) return -1;
												if(a.data_criacao > b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-desc':
											dados.services_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao > b.data_criacao) return -1;
												if(a.data_criacao < b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-asc':
											dados.services_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao < b.data_modificacao) return -1;
												if(a.data_modificacao > b.data_modificacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-desc':
											dados.services_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao > b.data_modificacao) return -1;
												if(a.data_modificacao < b.data_modificacao) return 1;
												return 0;
											});
										break;
									}
									
									for(var i=0;i<dados.services_list.length;i++){
										$(obj).find('.b2make-widget-out').find('.b2make-services-list').append(servicos_widget_html({
											margem:margem,
											service:dados.services_list[i],
											largura_cont:largura_cont,
											altura_cont:altura_cont,
											altura_img:altura_img
										}));
									}
								}
							break;
							case 'escolha-pontual':
								var num = 0;
								if(dados.services_list){
									var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
									switch(ordem){
										case 'alfabetica-asc':
											dados.services_list.sort(function(a, b){
												if(a.nome < b.nome) return -1;
												if(a.nome > b.nome) return 1;
												return 0;
											});
										break;
										case 'alfabetica-desc':
											dados.services_list.sort(function(a, b){
												if(a.nome > b.nome) return -1;
												if(a.nome < b.nome) return 1;
												return 0;
											});
										break;
										case 'data-asc':
											dados.services_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao < b.data_criacao) return -1;
												if(a.data_criacao > b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-desc':
											dados.services_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao > b.data_criacao) return -1;
												if(a.data_criacao < b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-asc':
											dados.services_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao < b.data_modificacao) return -1;
												if(a.data_modificacao > b.data_modificacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-desc':
											dados.services_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao > b.data_modificacao) return -1;
												if(a.data_modificacao < b.data_modificacao) return 1;
												return 0;
											});
										break;
									}
									
									for(var i=0;i<dados.services_list.length;i++){
										found_service = false;
										if(services_ids)
										for(var j=0;j<services_ids.length;j++){
											if(services_ids[j] == dados.services_list[i].id){
												found_service = true;
											}
										}
										
										if(!found_service)continue;
										
										$(obj).find('.b2make-widget-out').find('.b2make-services-list').append(servicos_widget_html({
											margem:margem,
											service:dados.services_list[i],
											largura_cont:largura_cont,
											altura_cont:altura_cont,
											altura_img:altura_img
										}));
									}
								}
							break;
							case 'categoria':
								var num = 0;
								if(dados.services_list){
									var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
									switch(ordem){
										case 'alfabetica-asc':
											dados.services_list.sort(function(a, b){
												if(a.nome < b.nome) return -1;
												if(a.nome > b.nome) return 1;
												return 0;
											});
										break;
										case 'alfabetica-desc':
											dados.services_list.sort(function(a, b){
												if(a.nome > b.nome) return -1;
												if(a.nome < b.nome) return 1;
												return 0;
											});
										break;
										case 'data-asc':
											dados.services_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao < b.data_criacao) return -1;
												if(a.data_criacao > b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-desc':
											dados.services_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao > b.data_criacao) return -1;
												if(a.data_criacao < b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-asc':
											dados.services_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao < b.data_modificacao) return -1;
												if(a.data_modificacao > b.data_modificacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-desc':
											dados.services_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao > b.data_modificacao) return -1;
												if(a.data_modificacao < b.data_modificacao) return 1;
												return 0;
											});
										break;
									}
									
									for(var i=0;i<dados.services_list.length;i++){
										found_service = false;
										
										if(categoria_id)
										if(categoria_id == dados.services_list[i].id_servicos_categorias){
											found_service = true;
										}
										
										if(!found_service)continue;
										
										$(obj).find('.b2make-widget-out').find('.b2make-services-list').append(servicos_widget_html({
											margem:margem,
											service:dados.services_list[i],
											largura_cont:largura_cont,
											altura_cont:altura_cont,
											altura_img:altura_img
										}));
									}
								}
							break;
						}
						
						if($(obj).myAttr('data-widget-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-widget-color-ahex')));
						if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
						if($(obj).myAttr('data-botao-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-color-ahex')));
						if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-name').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
						if($(obj).myAttr('data-descricao-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-descricao').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-descricao-text-color-ahex')));
						if($(obj).myAttr('data-preco-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-preco').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-preco-text-color-ahex')));
						if($(obj).myAttr('data-botao-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-text-color-ahex')));
						
						var ids = new Array('titulo','descricao','preco','botao');
						var mudar_height = false;
						var target;
						
						for(var i=0;i<ids.length;i++){
							var id = ids[i];
							
							switch(id){
								case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-name'); mudar_height = true; break;
								case 'descricao': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-descricao'); mudar_height = true; break;
								case 'preco': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-preco'); break;
								case 'botao': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar'); break;
							}
							
							if($(obj).myAttr('data-'+id+'-font-family'))target.css('fontFamily',$(obj).myAttr('data-'+id+'-font-family'));
							if($(obj).myAttr('data-'+id+'-font-size')){
								target.css('fontSize',$(obj).myAttr('data-'+id+'-font-size')+'px');
								
								var height = b2make.services.conteiner_height_lines*($(obj).myAttr('data-titulo-font-size') ? parseInt($(obj).myAttr('data-titulo-font-size')) : b2make.services.conteiner_height_name) + b2make.services.conteiner_height_lines*($(obj).myAttr('data-descricao-font-size') ? parseInt($(obj).myAttr('data-descricao-font-size')) : b2make.services.conteiner_height_descricao);
								height = height + b2make.services.conteiner_height_default;
								
								$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').css('height',height+'px');
								
								var line_height = parseInt($(obj).myAttr('data-'+id+'-font-size')) + b2make.services.conteiner_height_margin;
								target.css('line-height',line_height+'px');
								
								if(mudar_height){
									target.css('max-height',(line_height*b2make.services.conteiner_height_lines)+'px');
								}
							}
							if($(obj).myAttr('data-'+id+'-font-align'))target.css('textAlign',$(obj).myAttr('data-'+id+'-font-align'));
							if($(obj).myAttr('data-'+id+'-font-italico'))target.css('fontStyle',($(obj).myAttr('data-'+id+'-font-italico') == 'sim' ? 'italic' : 'normal'));
							if($(obj).myAttr('data-'+id+'-font-negrito'))target.css('fontWeight',($(obj).myAttr('data-'+id+'-font-negrito') == 'sim' ? 'bold' : 'normal'));
						}
						
						$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
					break;
					case 'Vazio':
						// Nada a fazer
					break;
					case 'LojaBloqueada':
						b2make.services_blocked_alerta = dados.alerta;
						b2make.services_blocked = true;
						$.conteiner_child_close();
						$.dialogbox_open({
							msg:dados.alerta
						});
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

function services_start(){
	var id_func = 'servicos';
	var id_plugin = 'services';
	
	b2make.plugin[id_plugin].started = true;
	
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
				
				if(b2make.services_added){
					return;
				} else {
					b2make.services_added = true;
				}
				
				switch(dados.status){
					case 'Ok':
						var servico_show,servico_selected;
						var servicos_lista = new Array();
						var servicos_categorias = new Array();
						
						for(var i=0;i<dados.resultado.length;i++){
							
							servico_show = true;
							servico_selected = false;
							
							if(i==dados.resultado.length - 1){
								b2make.servico_atual = dados.resultado[i].id_servicos;
								b2make.servico_nome = dados.resultado[i].nome;
								servico_selected = true;
							}
							
							servicos_lista.push({
								nome : dados.resultado[i].nome,
								descricao : dados.resultado[i].descricao,
								imagem_path : dados.resultado[i].imagem_path,
								preco : dados.resultado[i].preco,
								id : dados.resultado[i].id_servicos
							});
							
							$.lista_add_linha({
								lista_id : 'b2make-services-lista',
								data_id : dados.resultado[i].id_servicos,
								status : 'B',
								fields : {
									nome : dados.resultado[i].nome
								}
							});
						}
						
						b2make.servicos_lista = servicos_lista;
						
						if(dados.servicos_categorias){
							for(var i=0;i<dados.servicos_categorias.length;i++){
								servicos_categorias.push({
									nome : dados.servicos_categorias[i].nome,
									id : dados.servicos_categorias[i].id_servicos_categorias
								});
								
								$.lista_add_linha({
									lista_id : 'b2make-services-lista-categoria',
									data_id : dados.servicos_categorias[i].id_servicos_categorias,
									status : 'B',
									fields : {
										nome : dados.servicos_categorias[i].nome
									}
								});
							}
						}
						
						b2make.servicos_categorias_lista = servicos_categorias;
						
						b2make.servicos_prontos = true;
						
						if(b2make.services_widget_added || variaveis_js.widget_id){
							b2make.services_widget_added = false;
							$('#b2make-services-callback').trigger('widget_added');
							$.conteiner_child_open({select:true,widget_type:'services'});
						}
						
						$('.b2make-tooltip').tooltip({
							show: {
								effect: "fade",
								delay: 400
							}
						});
						
						$('#b2make-services-callback').on('edit-save',function(e,data){
							window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'e-service/services/?opcao=editar&id='+data.id+'&site=true&widget_id='+data.widget_id,'_self');
						});
						
						$('#b2make-services-lista').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							var status = $('#b2make-services-lista').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').myAttr('data-status');
			
							var services = ($(obj).myAttr('data-services-ids') ? $(obj).myAttr('data-services-ids') : '');
							var services_arr = (services ? services.split(',') : new Array());
							var services_saida = '';
							
							if(status == 'A'){
								services_saida = services + (services ? ',':'') + id
							} else {
								if(services_arr)
								for(var i=0;i<services_arr.length;i++){
									if(id != services_arr[i]){
										services_saida = services_saida + (services_saida ? ',':'') + services_arr[i]
									}
								}
							}
							
							$(obj).myAttr('data-services-ids',services_saida);
							servicos_widget_update({obj:obj});
						
						});
						
						$('#b2make-services-lista-categoria').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							
							$('#b2make-services-lista-categoria').find('.b2make-lista-option-block').each(function(){
								$(this).myAttr('data-status','B');
							});
							
							$('#b2make-services-lista-categoria').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').myAttr('data-status','A');
							
							$(obj).myAttr('data-categoria-id',id);
							servicos_widget_update({obj:obj});
						
						});
						
						$(b2make.widget).each(function(){
							if($(this).myAttr('data-type') != 'conteiner-area'){
								switch($(this).myAttr('data-type')){
									case 'services':
										$.widgets_read_google_font({
											tipo : 2,
											types : new Array('titulo','descricao','preco','botao'),
											obj : $(this)
										});
										
										servicos_widget_update({obj:this});
										
										
									break;
								}
							}
						});
						
						if(b2make.plugin[id_plugin].widget_added){
							b2make.plugin[id_plugin].widget_added = false;
							$('#b2make-contents-callback').trigger('conteiner_child_open');
							$('#b2make-contents-callback').trigger('conteiner_child_open_finished');
							$.menu_conteiner_aba_extra_open();
							$.widget_specific_options_open();
							$.widget_sub_options_open();
							services_menu_visibilidade();
						}
					break;
					case 'Vazio':
						// Nada a fazer
					break;
					case 'LojaBloqueada':
						b2make.services_blocked_alerta = dados.alerta;
						b2make.services_blocked = true;
						$.conteiner_child_close();
						$.dialogbox_open({
							msg:dados.alerta
						});
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
	
	$('#b2make-services-callback').on('conteiner_child_open',function(e){
		if(!b2make.services_blocked){
			var obj = b2make.conteiner_child_obj;
			
			if($(obj).myAttr('data-type') != 'services')return;
			
			var services = ($(obj).myAttr('data-services-ids') ? $(obj).myAttr('data-services-ids') : '');
			var services_arr = (services ? services.split(',') : new Array());
			
			$('#b2make-services-lista').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).myAttr('data-id');
				var found = false;
				
				if(services_arr)
				for(var i=0;i<services_arr.length;i++){
					if(id == services_arr[i]){
						found = true;
					}
				}
				
				if(found){
					$(this).myAttr('data-status','A');
				} else {
					$(this).myAttr('data-status','B');
				}
			});	
			
			var categoria_id = ($(obj).myAttr('data-categoria-id') ? $(obj).myAttr('data-categoria-id') : '');
			
			$('#b2make-services-lista-categoria').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).myAttr('data-id');
				
				if(id == categoria_id){
					$(this).myAttr('data-status','A');
				} else {
					$(this).myAttr('data-status','B');
				}
			});	
			
			if($(obj).myAttr('data-acao-click')){
				var option = $('#b2make-wo-acao-click').find("[value='" + $(obj).myAttr('data-acao-click') + "']");
				option.myAttr('selected', 'selected');
			} else {
				var option = $('#b2make-wo-acao-click').find(":first");
				option.myAttr('selected', 'selected');
			}			
			
			if($(obj).myAttr('data-widget-color-ahex')){
				$('#b2make-wo-services-widget-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-widget-color-ahex')));
				$('#b2make-wo-services-widget-cor-val').myAttr('data-ahex',$(obj).myAttr('data-widget-color-ahex'));
			} else {
				$('#b2make-wo-services-widget-cor-val').css('background-color','transparent');
				$('#b2make-wo-services-widget-cor-val').myAttr('data-ahex',false);
			}
			
			if($(obj).myAttr('data-caixa-color-ahex')){
				$('#b2make-wo-services-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
				$('#b2make-wo-services-caixa-cor-val').myAttr('data-ahex',$(obj).myAttr('data-caixa-color-ahex'));
			} else {
				$('#b2make-wo-services-caixa-cor-val').css('background-color','#FFFFFF');
				$('#b2make-wo-services-caixa-cor-val').myAttr('data-ahex','ffffffff');
			}
			
			if($(obj).myAttr('data-botao-color-ahex')){
				$('#b2make-wo-services-botao-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-color-ahex')));
				$('#b2make-wo-services-botao-cor-val').myAttr('data-ahex',$(obj).myAttr('data-botao-color-ahex'));
			} else {
				$('#b2make-wo-services-botao-cor-val').css('background-color','#F00000');
				$('#b2make-wo-services-botao-cor-val').myAttr('data-ahex','f00000ff');
			}
			
			if($(obj).myAttr('data-titulo-text-color-ahex')){
				$('#b2make-wo-services-titulo-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
				$('#b2make-wo-services-titulo-cor-val').myAttr('data-ahex',$(obj).myAttr('data-titulo-text-color-ahex'));
			} else {
				$('#b2make-wo-services-titulo-cor-val').css('background-color','#58585B');
				$('#b2make-wo-services-titulo-cor-val').myAttr('data-ahex','58585bff');
			}
			
			if($(obj).myAttr('data-descricao-text-color-ahex')){
				$('#b2make-wo-services-descricao-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-descricao-text-color-ahex')));
				$('#b2make-wo-services-descricao-cor-val').myAttr('data-ahex',$(obj).myAttr('data-descricao-text-color-ahex'));
			} else {
				$('#b2make-wo-services-descricao-cor-val').css('background-color','#58585B');
				$('#b2make-wo-services-descricao-cor-val').myAttr('data-ahex','58585bff');
			}
			
			if($(obj).myAttr('data-preco-text-color-ahex')){
				$('#b2make-wo-services-preco-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-preco-text-color-ahex')));
				$('#b2make-wo-services-preco-cor-val').myAttr('data-ahex',$(obj).myAttr('data-preco-text-color-ahex'));
			} else {
				$('#b2make-wo-services-preco-cor-val').css('background-color','#58585B');
				$('#b2make-wo-services-preco-cor-val').myAttr('data-ahex','58585bff');
			}
			
			if($(obj).myAttr('data-botao-text-color-ahex')){
				$('#b2make-wo-services-botao-text-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-text-color-ahex')));
				$('#b2make-wo-services-botao-text-cor-val').myAttr('data-ahex',$(obj).myAttr('data-botao-text-color-ahex'));
			} else {
				$('#b2make-wo-services-botao-text-cor-val').css('background-color','#58585B');
				$('#b2make-wo-services-botao-text-cor-val').myAttr('data-ahex','58585bff');
			}
			
			var types = new Array('titulo','descricao','preco','botao');
			
			for(var i=0;i<types.length;i++){
				var type = types[i];
				var tamanho;
				
				switch(type){
					case 'titulo': tamanho = 18; break;
					case 'descricao': tamanho = 13; break;
					case 'preco': tamanho = 16; break;
					case 'botao': tamanho = 11; break;
				}
				
				if($(obj).myAttr('data-'+type+'-font-family')){
					$('#b2make-wo-services-'+type+'-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-'+type+'-font-family')
					});
					$('#b2make-wo-services-'+type+'-text-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-'+type+'-font-family'));
				} else {
					$('#b2make-wo-services-'+type+'-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': 'Roboto Condensed'
					});
					$('#b2make-wo-services-'+type+'-text-cont').find('.b2make-fonts-holder').html('Roboto Condensed');
				}
				
				if($(obj).myAttr('data-'+type+'-font-size')){
					$('#b2make-wo-services-'+type+'-text-cont').find('.b2make-fonts-size').val($(obj).myAttr('data-'+type+'-font-size'));
				} else {
					$('#b2make-wo-services-'+type+'-text-cont').find('.b2make-fonts-size').val(tamanho);
				}
			}
			
			if($(obj).myAttr('data-tamanho-cont')){
				$('#b2make-wo-services-tamanho-cont').val($(obj).myAttr('data-tamanho-cont'));
			} else {
				$('#b2make-wo-services-tamanho-cont').val('160');
			}
			
			if($(obj).myAttr('data-tamanho-cont-2')){
				$('#b2make-wo-services-tamanho-cont-2').val($(obj).myAttr('data-tamanho-cont-2'));
			} else {
				$('#b2make-wo-services-tamanho-cont-2').val('280');
			}
			
			if($(obj).myAttr('data-altura-imagem')){
				$('#b2make-wo-services-altura-imagem').val($(obj).myAttr('data-altura-imagem'));
			} else {
				$('#b2make-wo-services-altura-imagem').val('160');
			}
			
			if($(obj).myAttr('data-margem')){
				$('#b2make-wo-services-margem-cont').val($(obj).myAttr('data-margem'));
			} else {
				$('#b2make-wo-services-margem-cont').val('10');
			}
		
			services_menu_visibilidade();
		}
	});
	
	$('#b2make-services-callback').on('conteiner_child_open_finished',function(e){
		if(!b2make.servicos_prontos) return false;
		if(b2make.services_blocked){
			$.conteiner_child_close();
			$.dialogbox_open({
				msg:b2make.services_blocked_alerta
			});
		} else {
			var obj = b2make.conteiner_child_obj;
			var ids_str = $(obj).myAttr('data-services-ids');
			var ids = new Array();
			
			if(ids_str){
				ids = ids_str.split(',');
			}
			
			if(ids){
				$('#b2make-services-lista').find('.b2make-lista-linha').each(function(){
					var show_cont = $(this);
					
					if($(this).hasClass('b2make-lista-cabecalho')){
						return true;
					}
					
					var id = show_cont.myAttr('data-id');
					var found = false;
					
					for(var i=0;i<ids.length;i++){
						if(ids[i] == id){
							found = true;							
							break;
						}
					}
					
					if(found){
						show_cont.myAttr('data-status','show');
						show_cont.find('.b2make-lista-option-block').myAttr('data-status','A');
					} else {
						show_cont.myAttr('data-status','not-show');
						show_cont.find('.b2make-lista-option-block').myAttr('data-status','B');
					}
				});
			}
			
			if(b2make.services_widget_added){
				servicos_widget_update({});
			}
		}
	});
	
	$('#b2make-services-add').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var obj = b2make.conteiner_child_obj;
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'store/services/?opcao=add&site=true&widget_id='+$(obj).myAttr('id'),'_self');
	});
	
	$('#b2make-services-lista').on('edit',function(event,params){
		var obj = b2make.conteiner_child_obj;
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'store/services/?opcao=editar&id='+params.id+'&site=true&widget_id='+$(obj).myAttr('id'),'_self');
	});
	
}

function services(){
	b2make.services = {};
	if(!b2make.msgs.servicosEdit)b2make.msgs.servicosEdit = 'Clique para Editar o este servi&ccedil;o';
	if(!b2make.msgs.servicosNome)b2make.msgs.servicosNome = 'Clique para alterar as fotos deste servi&ccedil;o';
	if(!b2make.msgs.servicosShow)b2make.msgs.servicosShow = 'Clique para que mostrar/n&atilde;o mostrar este servi&ccedil;o no widget E-Servi&ccedil;os';
	if(!b2make.msgs.servicosComprar)b2make.msgs.servicosComprar = 'Comprar';
	if(!b2make.msgs.servicosIndisponivel)b2make.msgs.servicosIndisponivel = 'Servi&ccedil;o Indispon&iacute;vel';
	
	b2make.services.conteiner_height_default = 220;
	b2make.services.conteiner_height_lines = 2;
	b2make.services.conteiner_height_margin = 2;
	b2make.services.conteiner_height_name = 18;
	b2make.services.conteiner_height_descricao = 13;
	
	var id_func = 'servicos';
	var id_plugin = 'services';
	
	// Install B2make Widget Options
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+id_plugin+'/html.html',
		beforeSend: function(){
		},
		success: function(txt){
			var html = $('<div>'+txt+'</div>');
			var options = html.find('#b2make-widget-options-'+id_plugin).clone();
			options.appendTo('#b2make-widget-options-hide');
			var sub_options = html.find('#b2make-widget-sub-options-'+id_plugin).clone();
			sub_options.appendTo('#b2make-widget-options-hide');
			
			$.fonts_load({obj:'#b2make-widget-options-'+id_plugin});
			$.jpicker_load({obj:'#b2make-widget-options-'+id_plugin});
			
			$.menu_conteiner_aba_load({
				id:id_plugin,
				html:html.find('#b2make-conteiner-aba-extra-'+id_plugin).clone()
			});
			$.fonts_load({obj:'.b2make-conteiner-aba-extra[data-type="'+id_plugin+'"]'});
			$.jpicker_load({obj:'.b2make-conteiner-aba-extra[data-type="'+id_plugin+'"]'});
			$.lista_start($('#b2make-services-lista').get(0));
			$.lista_start($('#b2make-services-lista-categoria').get(0));
			
			if(b2make.plugin[id_plugin].widget_added){
				$.menu_conteiner_aba_extra_open();
			}
			
			$(document.body).on('changeColor','#b2make-wo-services-widget-cor-val,#b2make-wo-services-botao-text-cor-val,#b2make-wo-services-preco-cor-val,#b2make-wo-services-descricao-cor-val,#b2make-wo-services-titulo-cor-val,#b2make-wo-services-caixa-cor-val,#b2make-wo-services-botao-cor-val',function(e){
				var id = $(this).myAttr('id');
				var bg = $(b2make.jpicker.obj).css('background-color');
				var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
				var obj = b2make.conteiner_child_obj;
				
				switch(id){
					case 'b2make-wo-services-widget-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').css('background-color',bg);
						$(obj).myAttr('data-widget-color-ahex',ahex);	
					break;
					case 'b2make-wo-services-caixa-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').css('background-color',bg);
						$(obj).myAttr('data-caixa-color-ahex',ahex);	
					break;
					case 'b2make-wo-services-botao-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('background-color',bg);
						$(obj).myAttr('data-botao-color-ahex',ahex);
					break;
					case 'b2make-wo-services-titulo-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-name').css('color',bg);
						$(obj).myAttr('data-titulo-text-color-ahex',ahex);
					break;
					case 'b2make-wo-services-descricao-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-descricao').css('color',bg);
						$(obj).myAttr('data-descricao-text-color-ahex',ahex);
					break;
					case 'b2make-wo-services-preco-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-preco').css('color',bg);
						$(obj).myAttr('data-preco-text-color-ahex',ahex);
					break;
					case 'b2make-wo-services-botao-text-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('color',bg);
						$(obj).myAttr('data-botao-text-color-ahex',ahex);
					break;
					
				}
			});
			
			$(document.body).on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito','#b2make-wo-services-botao-text-cont,#b2make-wo-services-preco-text-cont,#b2make-wo-services-descricao-text-cont,#b2make-wo-services-titulo-text-cont',function(e){
				var obj = b2make.conteiner_child_obj;
				var target;
				var cssVar = '';
				var noSize = false;
				var id_bruto = $(this).myAttr('id');
				var mudar_height = false;
				var id = id_bruto.replace(/b2make-wo-services-/gi,'');
				
				id = id.replace(/-text-cont/gi,'');
				
				switch(id_bruto){
					case 'b2make-wo-services-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-name'); mudar_height = true; break;
					case 'b2make-wo-services-descricao-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-descricao'); mudar_height = true; break;
					case 'b2make-wo-services-preco-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-preco'); break;
					case 'b2make-wo-services-botao-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar'); break;
				}
				
				switch(e.type){
					case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-'+id+'-font-family',$(this).myAttr('data-font-family')); break;
					case 'changeFontSize': 
						cssVar = 'fontSize';  target.css(cssVar,$(this).myAttr('data-font-size')+'px'); target.css('line-height',$(this).myAttr('data-font-size')+'px'); $(obj).myAttr('data-'+id+'-font-size',$(this).myAttr('data-font-size')); 
						
						var height = b2make.services.conteiner_height_lines*parseInt($('#b2make-wo-services-descricao-text-cont').find('.b2make-fonts-size').val()) + b2make.services.conteiner_height_lines*parseInt($('#b2make-wo-services-titulo-text-cont').find('.b2make-fonts-size').val());
						height = height + b2make.services.conteiner_height_default;
						
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').css('height',height+'px');
						
						var line_height = parseInt($(this).myAttr('data-font-size')) + b2make.services.conteiner_height_margin;
						target.css('line-height',line_height+'px');
						
						if(mudar_height){
							target.css('max-height',(line_height*b2make.services.conteiner_height_lines)+'px');
						}
					break;
					case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).myAttr('data-font-align'));$(obj).myAttr('data-'+id+'-font-align',$(this).myAttr('data-font-align')); break;
					case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).myAttr('data-'+id+'-font-italico',$(this).myAttr('data-font-italico')); break;
					case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).myAttr('data-'+id+'-font-negrito',$(this).myAttr('data-font-negrito')); break;
				}
			});
			
			$(document.body).on('change','#b2make-wo-acao-click',function(){
				var obj = b2make.conteiner_child_obj;
				var value = $(this).val();
				
				$(obj).myAttr('data-acao-click',value);
			});
			
			$(document.body).on('keyup','#b2make-wo-services-tamanho-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-services-tamanho-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-services-tamanho-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-tamanho-cont',value);
				
				servicos_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-services-tamanho-cont-2',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-services-tamanho-2-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-services-tamanho-2-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-tamanho-cont-2',value);
				
				servicos_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-services-altura-imagem',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-services-altura-imagem-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-services-altura-imagem-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-altura-imagem',value);
				
				servicos_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-services-margem-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-services-margem-cont-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-services-margem-cont-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-margem',value);
				
				servicos_widget_update({});
			});
			
			$(document.body).on('change','#b2make-services-options-sel',function(){
				var obj = b2make.conteiner_child_obj;
				var value = $(this).val();
				
				$(obj).myAttr('data-categoria',value);
				
				$('#b2make-services-lista-cont').hide();
				$('#b2make-services-lista-categoria-cont').hide();
				$('#b2make-services-options-cont').hide();
				
				switch($(obj).myAttr('data-categoria')){
					case 'todos-servicos': $('#b2make-services-options-cont').show(); break;
					case 'escolha-pontual': 
						$('#b2make-services-lista-cont').show();
						$('#b2make-services-options-cont').show();
					break;
					case 'categoria': 
						$('#b2make-services-lista-categoria-cont').show();
						$('#b2make-services-options-cont').show();
					break;
				}
				
				servicos_widget_update({});
			});
			
			$(document.body).on('change','#b2make-services-ordem-sel',function(){
				var obj = b2make.conteiner_child_obj;
				var value = $(this).val();
				
				$(obj).myAttr('data-ordem',value);
				
				servicos_widget_update({});
			});
			
			services_start();
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_plugin+' - html - '+txt);
			console.log(txt);
		}
	});
	
	$('#b2make-services-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:'services'});
	});
	
	$('#b2make-services-callback').on('widget_added',function(e){
		if(!b2make.services_blocked && !b2make.servicos_prontos){
			
		} else {
			b2make.services_widget_added = true;
			b2make.services_widget_added_2 = true;
		}
		
		if(!b2make.plugin[id_plugin].started){
			b2make.plugin[id_plugin].widget_added = true;			
		}
	});
	
}

services();