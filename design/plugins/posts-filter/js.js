function posts_filter_conteudo_tipo(){
	var obj = b2make.conteiner_child_obj;
	
	$('#b2make-posts_filter-contents-lista-cont').hide();
	$('#b2make-posts_filter-contents-lista-conteudo-tipo-cont').hide();
	$('#b2make-posts_filter-contents-options-cont').hide();
	
	switch($(obj).attr('data-conteudo-tipo')){
		case 'todos-posts': $('#b2make-posts_filter-contents-options-cont').show(); break;
		case 'escolha-pontual': $('#b2make-posts_filter-contents-lista-cont').show(); break;
		case 'conteudo-tipo': 
			$('#b2make-posts_filter-contents-lista-conteudo-tipo-cont').show();
			$('#b2make-posts_filter-contents-options-cont').show();
		break;
	}
}

function posts_filter_widget_menu_html(p){
	if(!p)p={};
	
	var id_plugin = 'posts-filter';
	
	if($(p.obj).attr('data-layout-tipo') == 'menu'){
		var posts_filter_cont = $('<div class="b2make-'+id_plugin+'-cont" data-id="'+p.posts_filter.id+'">'+p.posts_filter.nome+'</div>');
	} else {
		var posts_filter_cont = $('<div class="b2make-'+id_plugin+'-cont-2-lbl">'+p.posts_filter.nome+':</div><div class="b2make-'+id_plugin+'-cont-2" data-id="'+p.posts_filter.id+'">Selecione...</div>');
	}
	
	return posts_filter_cont;
}

function posts_filter_layout_tipo(){
	var obj = b2make.conteiner_child_obj;
	
	if($(obj).attr('data-layout-tipo') == 'menu'){
		$('#b2make-posts_filter-botao-filtrar-url-lbl').show();
		$('#b2make-posts_filter-botao-filtrar-url').show();
		$('#b2make-wo-posts_filter-sem-resultados-html-lbl').hide();
		$('#b2make-wo-posts_filter-sem-resultados-html-btn').hide();
		$('#b2make-wo-posts_filter-ficha-html-lbl').hide();
		$('#b2make-wo-posts_filter-ficha-html-btn').hide();
		$('#b2make-posts_filter-contents-lbl').hide();
		$('#b2make-posts_filter-contents-editar-btn').hide();
		$('#b2make-wo-posts_filter-layout-orientacao-lbl').hide();
		$('#b2make-wo-posts_filter-layout-orientacao').hide();
	} else {
		$('#b2make-posts_filter-botao-filtrar-url-lbl').hide();
		$('#b2make-posts_filter-botao-filtrar-url').hide();
		$('#b2make-wo-posts_filter-sem-resultados-html-lbl').show();
		$('#b2make-wo-posts_filter-sem-resultados-html-btn').show();
		$('#b2make-wo-posts_filter-ficha-html-lbl').show();
		$('#b2make-wo-posts_filter-ficha-html-btn').show();
		$('#b2make-posts_filter-contents-lbl').show();
		$('#b2make-posts_filter-contents-editar-btn').show();
		$('#b2make-wo-posts_filter-layout-orientacao-lbl').show();
		$('#b2make-wo-posts_filter-layout-orientacao').show();
	}
	
}

function posts_filter_widget_update(p){
	if(!p)p={};
	
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	var id_plugin = 'posts-filter';
	
	var cont = $(obj).find('.b2make-widget-out').find('.b2make-'+id_plugin);
	var menu = cont.find('.b2make-'+id_plugin+'-menu');
	var lista = cont.find('.b2make-'+id_plugin+'-lista');
	var ficha_html = cont.find('.b2make-'+id_plugin+'-ficha-html-holder');
	var ficha_html_vertical = cont.find('.b2make-'+id_plugin+'-ficha-html-vertical-holder');
	var sem_resultados_html = cont.find('.b2make-'+id_plugin+'-sem-resultados-html-holder');
	
	if(ficha_html_vertical.length == 0){
		cont.append('<div class="b2make-posts-filter-ficha-html-vertical-holder"></div>');
		ficha_html_vertical = cont.find('.b2make-'+id_plugin+'-ficha-html-vertical-holder');
	}
	
	if(sem_resultados_html.length == 0){
		cont.append('<div class="b2make-posts-filter-sem-resultados-html-holder"></div>');
		sem_resultados_html = cont.find('.b2make-'+id_plugin+'-sem-resultados-html-holder');
	}
	
	if(ficha_html.html().length == 0){
		ficha_html.html(b2make.posts_filter_ficha_html);
	}
	
	if(ficha_html_vertical.length > 0)
	if(ficha_html_vertical.html().length == 0){
		ficha_html_vertical.html(b2make.posts_filter_ficha_html_vertical);
	}
	
	if(sem_resultados_html.length > 0)
	if(sem_resultados_html.html().length == 0){
		sem_resultados_html.html(b2make.posts_filter_sem_resultados_html);
	}
	
	menu.html('');
	
	var posts_filter_ids = $(obj).attr('data-'+id_plugin+'-ids');
	var posts_filter_ids_arr;
	
	if(posts_filter_ids)posts_filter_ids_arr = posts_filter_ids.split(',');
	
	if($(obj).attr('data-botao-texto')){
		var botao_texto = $(obj).attr('data-botao-texto');
	} else {
		var botao_texto = b2make.msgs.posts_filterBotaoTexto;
	}
	
	if(b2make.posts_filter_lista){
		var tags = b2make.posts_filter_lista;
		
		if(posts_filter_ids_arr)
		for(var j=0;j<posts_filter_ids_arr.length;j++){
			for(var i=0;i<tags.length;i++){
				if(posts_filter_ids_arr[j] == tags[i].id){
					menu.append(posts_filter_widget_menu_html({obj:obj,botao_texto:botao_texto,posts_filter:tags[i]}));
					break;
				}
			}
		}
	}
	
	if($(obj).attr('data-layout-tipo') == 'menu'){
		menu.attr('data-type','menu');
		menu.append('<div class="b2make-posts-filter-btn">'+botao_texto+'</div>');
		lista.html('');
	} else {
		menu.attr('data-type','menu-resultados');
		menu.attr('data-orientacao',($(obj).attr('data-layout-orientacao') ? $(obj).attr('data-layout-orientacao') : 'horizontal'));
		menu.append('<div class="b2make-posts-filter-btn-2">'+botao_texto+'</div>');
		lista.html('');
		lista.attr('data-orientacao',($(obj).attr('data-layout-orientacao') ? $(obj).attr('data-layout-orientacao') : 'horizontal'));
		
		var widget_height = $(obj).height();
		
		if($(obj).attr('data-layout-orientacao') == 'vertical'){
			lista.append(ficha_html_vertical.html());
			lista.append(ficha_html_vertical.html());
			
			var menu_height = menu.outerHeight(true);
			var lista_magin = parseInt(lista.css('marginTop'));
			
			var height = Math.floor(widget_height - menu_height - lista_magin);
			
			lista.height(height);
		} else {
			lista.append(ficha_html.html());
			lista.append(ficha_html.html());
			
			lista.height(widget_height);
		}
	}
	
	$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
}

function posts_filter_start(){
	var id_func = 'posts-filter';
	var id_plugin = 'posts-filter';
	var url_path = 'content/tags/';
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func
		},
		beforeSend: function(){
			if(b2make.posts_filter_added){
				return false;
			} else {
				b2make.posts_filter_added = true;
			}
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				$.lista_start($('#b2make-'+id_plugin+'-lista').get(0));
				
				switch(dados.status){
					case 'Ok':
						var posts_filter_show,posts_filter_selected;
						var posts_filter_lista = new Array();
						
						for(var i=0;i<dados.resultado.length;i++){
							posts_filter_show = true;
							posts_filter_selected = false;
							
							if(i==dados.resultado.length - 1){
								b2make.posts_filter_atual = dados.resultado[i].id_site_conteudos_tags;
								b2make.posts_filter_nome = dados.resultado[i].nome;
								posts_filter_selected = true;
							}
							
							posts_filter_lista.push({
								nome : dados.resultado[i].nome,
								cor : dados.resultado[i].cor,
								id : dados.resultado[i].id_site_conteudos_tags
							});
							
							$.lista_add_linha({
								lista_id : 'b2make-'+id_plugin+'-lista',
								data_id : dados.resultado[i].id_site_conteudos_tags,
								status : 'B',
								fields : {
									nome : dados.resultado[i].nome
								}
							});
						}
						
						b2make.posts_filter_lista = posts_filter_lista;
						b2make.posts_filter_ficha_html = dados.ficha_html;
						b2make.posts_filter_ficha_html_vertical = dados.ficha_html_vertical;
						b2make.posts_filter_sem_resultados_html = dados.sem_resultados_html;
						
						var conteudos_lista = new Array();
						
						for(var i=0;i<dados.site_conteudos.length;i++){
							conteudos_lista.push({
								nome : dados.site_conteudos[i].nome,
								id : dados.site_conteudos[i].id_site_conteudos
							});
							
							$.lista_add_linha({
								lista_id : 'b2make-posts_filter-contents-lista',
								data_id : dados.site_conteudos[i].id_site_conteudos,
								status : 'B',
								fields : {
									nome : dados.site_conteudos[i].nome
								}
							});
						}
						
						b2make.posts_filter_conteudos_lista = conteudos_lista;
						
						var conteudos_tipos_lista = new Array();
						
						if(dados.conteudos_tipos)
						for(var i=0;i<dados.conteudos_tipos.length;i++){
							conteudos_tipos_lista.push({
								nome : dados.conteudos_tipos[i].nome,
								id : dados.conteudos_tipos[i].id_site_conteudos_tipos
							});
							
							$.lista_add_linha({
								lista_id : 'b2make-posts_filter-contents-lista-conteudo-tipo',
								data_id : dados.conteudos_tipos[i].id_site_conteudos_tipos,
								status : 'B',
								fields : {
									nome : dados.conteudos_tipos[i].nome
								}
							});
						}
						
						b2make.posts_filter_conteudos_tipos_lista = conteudos_tipos_lista;
						
						b2make.posts_filter_prontos = true;
						
						if(b2make.posts_filter_widget_added){
							b2make.posts_filter_widget_added = false;
							$('#b2make-'+id_plugin+'-callback').trigger('widget_added');
							$.conteiner_child_open({select:true,widget_type:id_plugin});
						}
						
						$('.b2make-tooltip').tooltip({
							show: {
								effect: "fade",
								delay: 400
							}
						});
						
						$('#b2make-'+id_plugin+'-lista').on('edit',function(event,params){
							var obj = b2make.conteiner_child_obj;
							
							$.script_trigger({
								id : id_plugin,
								callback : 'edit-save',
								operacao : 'save-ajax-call',
								params : [{id:params.id,widget_id:$(obj).attr('id')}]
							});
							$.save();
						});
						
						$('#b2make-'+id_plugin+'-callback').on('edit-save',function(e,data){
							window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+url_path+'?opcao=editar&id='+data.id+'&site=true&widget_id='+data.widget_id,'_self');
						});
						
						$('#b2make-'+id_plugin+'-lista').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							var status = $('#b2make-'+id_plugin+'-lista').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').attr('data-status');
			
							var posts_filter = ($(obj).attr('data-'+id_plugin+'-ids') ? $(obj).attr('data-'+id_plugin+'-ids') : '');
							var posts_filter_arr = (posts_filter ? posts_filter.split(',') : new Array());
							var posts_filter_saida = '';
							
							if(status == 'A'){
								posts_filter_saida = posts_filter + (posts_filter ? ',':'') + id
							} else {
								if(posts_filter_arr)
								for(var i=0;i<posts_filter_arr.length;i++){
									if(id != posts_filter_arr[i]){
										posts_filter_saida = posts_filter_saida + (posts_filter_saida ? ',':'') + posts_filter_arr[i]
									}
								}
							}
							
							$(obj).attr('data-'+id_plugin+'-ids',posts_filter_saida);
							posts_filter_widget_update({obj:obj});
						});
						
						$('#b2make-posts_filter-contents-lista').on('edit',function(event,params){
							var obj = b2make.conteiner_child_obj;
							window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'content/?opcao=editar&id='+params.id+'&site=true&widget_id='+$(obj).attr('id'),'_self');
						});
						
						$('#b2make-posts_filter-contents-lista').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							var status = $('#b2make-posts_filter-contents-lista').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').attr('data-status');
			
							var contents = ($(obj).attr('data-contents-ids') ? $(obj).attr('data-contents-ids') : '');
							var contents_arr = (contents ? contents.split(',') : new Array());
							var contents_saida = '';
							
							if(status == 'A'){
								contents_saida = contents + (contents ? ',':'') + id
							} else {
								if(contents_arr)
								for(var i=0;i<contents_arr.length;i++){
									if(id != contents_arr[i]){
										contents_saida = contents_saida + (contents_saida ? ',':'') + contents_arr[i]
									}
								}
							}
							
							$(obj).attr('data-contents-ids',contents_saida);
						});
						
						$('#b2make-posts_filter-contents-lista-conteudo-tipo').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							var status = $('#b2make-posts_filter-contents-lista-conteudo-tipo').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').attr('data-status');
			
							var contents_tipos = ($(obj).attr('data-contents-conteudo-tipo-ids') ? $(obj).attr('data-contents-conteudo-tipo-ids') : '');
							var contents_tipos_arr = (contents_tipos ? contents_tipos.split(',') : new Array());
							var contents_tipos_saida = '';
							
							if(status == 'A'){
								contents_tipos_saida = contents_tipos + (contents_tipos ? ',':'') + id
							} else {
								if(contents_tipos_arr)
								for(var i=0;i<contents_tipos_arr.length;i++){
									if(id != contents_tipos_arr[i]){
										contents_tipos_saida = contents_tipos_saida + (contents_tipos_saida ? ',':'') + contents_tipos_arr[i]
									}
								}
							}
							
							$(obj).attr('data-contents-conteudo-tipo-ids',contents_tipos_saida);
						});
						
						$(b2make.widget).each(function(){
							if($(this).attr('data-type') != 'conteiner-area'){
								switch($(this).attr('data-type')){
									case id_plugin:
										posts_filter_widget_update({obj:this});
									break;
								}
							}
						});
						
						$(document.body).on('change','#b2make-wo-posts_filter-layout-tipo',function(){
							var obj = b2make.conteiner_child_obj;
							var value = $(this).val();
							
							$(obj).attr('data-layout-tipo',value);
							
							posts_filter_layout_tipo();
							posts_filter_widget_update({});
						});
						
						$(document.body).on('change','#b2make-wo-posts_filter-layout-orientacao',function(){
							var obj = b2make.conteiner_child_obj;
							var value = $(this).val();
							
							$(obj).attr('data-layout-orientacao',value);
							
							if(value == 'vertical'){
								var html2 = $(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-ficha-html-vertical-holder').html();
								
								if(html2){
									$('#b2make-wo-posts_filter-ficha-html').val(html2);
								} else {
									$('#b2make-wo-posts_filter-ficha-html').val(b2make.posts_filter_ficha_html_vertical);
								}
							} else {
								var html = $(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-ficha-html-holder').html();
								
								if(html){
									$('#b2make-wo-posts_filter-ficha-html').val(html);
								} else {
									$('#b2make-wo-posts_filter-ficha-html').val(b2make.posts_filter_ficha_html);
								}
							}
							
							posts_filter_layout_tipo();
							posts_filter_widget_update({});
						});
						
						$(document.body).on('keyup','#b2make-posts_filter-botao-filtrar-url',function(e){
							var value = $(this).val();
							var id = $(this).attr('id');
							
							$.input_delay_to_change({
								trigger_selector:'#b2make-listener',
								trigger_event:'b2make-posts_filter-botao-filtrar-url-change',
								value:value
							});
						});
						
						$(document.body).on('b2make-posts_filter-botao-filtrar-url-change','#b2make-listener',function(e,value,p){
							if(!p) p = {};
							
							var obj = b2make.conteiner_child_obj;
							
							$(obj).attr('data-filtrar-url',value);
							
							posts_filter_widget_update({});
						});
						
						$(document.body).on('keyup','#b2make-wo-posts_filter-ficha-html',function(e){
							var value = $(this).val();
							var id = $(this).attr('id');
							
							$.input_delay_to_change({
								trigger_selector:'#b2make-listener',
								trigger_event:'b2make-wo-posts_filter-ficha-html-change',
								value:value
							});
						});
						
						$(document.body).on('b2make-wo-posts_filter-ficha-html-change','#b2make-listener',function(e,value,p){
							if(!p) p = {};
							
							var obj = b2make.conteiner_child_obj;
							
							if($(obj).attr('data-layout-orientacao') && $(obj).attr('data-layout-orientacao') == 'vertical'){
								$(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-ficha-html-vertical-holder').html(value);
							} else {
								$(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-ficha-html-holder').html(value);
							}
							
							posts_filter_widget_update({});
						});
						
						$(document.body).on('keyup','#b2make-wo-posts_filter-sem-resultados-html',function(e){
							var value = $(this).val();
							var id = $(this).attr('id');
							
							$.input_delay_to_change({
								trigger_selector:'#b2make-listener',
								trigger_event:'b2make-wo-posts_filter-sem-resultados-html-change',
								value:value
							});
						});
						
						$(document.body).on('b2make-wo-posts_filter-sem-resultados-html-change','#b2make-listener',function(e,value,p){
							if(!p) p = {};
							
							var obj = b2make.conteiner_child_obj;
							
							$(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-sem-resultados-html-holder').html(value);
							
							posts_filter_widget_update({});
						});
					break;
					case 'Vazio':
						if(b2make.posts_filter_widget_added){
							b2make.posts_filter_widget_added = false;
							$('#b2make-'+id_plugin+'-callback').trigger('widget_added');
							$.conteiner_child_open({select:true,widget_type:id_plugin});
						}
					break;
					case 'LojaBloqueada':
						b2make.posts_filter_blocked_alerta = dados.alerta;
						b2make.posts_filter_blocked = true;
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
	
	$('#b2make-'+id_plugin+'-callback').on('conteiner_child_open',function(e){
		if(!b2make.posts_filter_blocked){
			var obj = b2make.conteiner_child_obj;
			
			if($(obj).attr('data-type') != id_plugin)return;
			
			if(!$(obj).attr('data-conteudo-tipo')) $(obj).attr('data-conteudo-tipo','todos-posts');
			
			posts_filter_conteudo_tipo();
			
			var posts_filter = ($(obj).attr('data-'+id_plugin+'-ids') ? $(obj).attr('data-'+id_plugin+'-ids') : '');
			var posts_filter_arr = (posts_filter ? posts_filter.split(',') : new Array());
			
			$('#b2make-'+id_plugin+'-lista').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).attr('data-id');
				var found = false;
				
				if(posts_filter_arr)
				for(var i=0;i<posts_filter_arr.length;i++){
					if(id == posts_filter_arr[i]){
						found = true;
					}
				}
				
				if(found){
					$(this).attr('data-status','A');
				} else {
					$(this).attr('data-status','B');
				}
			});	
			
			posts_filter_layout_tipo();
			
			var contents = ($(obj).attr('data-contents-ids') ? $(obj).attr('data-contents-ids') : '');
			var contents_arr = (contents ? contents.split(',') : new Array());
			
			$('#b2make-posts_filter-contents-lista').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).attr('data-id');
				var found = false;
				
				if(contents_arr)
				for(var i=0;i<contents_arr.length;i++){
					if(id == contents_arr[i]){
						found = true;
					}
				}
				
				if(found){
					$(this).attr('data-status','A');
				} else {
					$(this).attr('data-status','B');
				}
			});	
			
			var contents_tipos = ($(obj).attr('data-contents-conteudo-tipo-ids') ? $(obj).attr('data-contents-conteudo-tipo-ids') : '');
			var contents_tipos_arr = (contents_tipos ? contents_tipos.split(',') : new Array());
			
			$('#b2make-posts_filter-contents-lista-conteudo-tipo').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).attr('data-id');
				var found = false;
				
				if(contents_tipos_arr)
				for(var i=0;i<contents_tipos_arr.length;i++){
					if(id == contents_tipos_arr[i]){
						found = true;
					}
				}
				
				if(found){
					$(this).attr('data-status','A');
				} else {
					$(this).attr('data-status','B');
				}
			});
			
			if($(obj).attr('data-layout-tipo')){
				layout_tipo = $(obj).attr('data-layout-tipo');
				var option = $('#b2make-wo-posts_filter-layout-tipo').find("[value='" + $(obj).attr('data-layout-tipo') + "']");
				option.prop('selected', 'selected');
			} else {
				var option = $('#b2make-wo-posts_filter-layout-tipo').find(":first");
				option.prop('selected', 'selected');
			}
			
			if($(obj).attr('data-layout-orientacao')){
				layout_tipo = $(obj).attr('data-layout-orientacao');
				var option = $('#b2make-wo-posts_filter-layout-orientacao').find("[value='" + $(obj).attr('data-layout-orientacao') + "']");
				option.prop('selected', 'selected');
			} else {
				var option = $('#b2make-wo-posts_filter-layout-orientacao').find(":first");
				option.prop('selected', 'selected');
			}
			
			if($(obj).attr('data-filtrar-url')){
				$('#b2make-posts_filter-botao-filtrar-url').val($(obj).attr('data-filtrar-url'));
			} else {
				$('#b2make-posts_filter-botao-filtrar-url').val('');
			}
			
			if($(obj).attr('data-layout-orientacao') && $(obj).attr('data-layout-orientacao') == 'vertical'){
				var html2 = $(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-ficha-html-vertical-holder').html();
				
				if(html2){
					$('#b2make-wo-posts_filter-ficha-html').val(html2);
				} else {
					$('#b2make-wo-posts_filter-ficha-html').val(b2make.posts_filter_ficha_html_vertical);
				}
			} else {
				var html = $(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-ficha-html-holder').html();
				
				if(html){
					$('#b2make-wo-posts_filter-ficha-html').val(html);
				} else {
					$('#b2make-wo-posts_filter-ficha-html').val(b2make.posts_filter_ficha_html);
				}
			}
			
			var html3 = $(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-posts-filter-sem-resultados-html-holder').html();
			
			if(html3){
				$('#b2make-wo-posts_filter-sem-resultados-html').val(html3);
			} else {
				$('#b2make-wo-posts_filter-sem-resultados-html').val(b2make.posts_filter_sem_resultados_html);
			}
			
			if($(obj).attr('data-conteudo-tipo')){
				var option = $('#b2make-posts_filter-contents-options-sel').find("[value='" + $(obj).attr('data-conteudo-tipo') + "']");
				option.prop('selected', 'selected');
			} else {
				var option = $('#b2make-posts_filter-contents-options-sel').find(":first");
				option.prop('selected', 'selected');
			}
		}
	});
	
	$('#b2make-posts-filter-callback').on('conteiner_child_open_finished',function(e){
		if(!b2make.posts_filter_prontos) return false;
		if(b2make.posts_filter_blocked){
			$.conteiner_child_close();
			$.dialogbox_open({
				msg:b2make.posts_filter_blocked_alerta
			});
		} else {
			var obj = b2make.conteiner_child_obj;
			var ids_str = $(obj).attr('data-'+id_plugin+'-ids');
			var ids = new Array();
			
			if(ids_str){
				ids = ids_str.split(',');
			}
			
			if(ids){
				$('#b2make-'+id_plugin+'-lista').find('.b2make-lista-linha').each(function(){
					var show_cont = $(this);
					
					if($(this).hasClass('b2make-lista-cabecalho')){
						return true;
					}
					
					var id = show_cont.attr('data-id');
					var found = false;
					
					for(var i=0;i<ids.length;i++){
						if(ids[i] == id){
							found = true;							
							break;
						}
					}
					
					if(found){
						show_cont.attr('data-status','show');
						show_cont.find('.b2make-lista-option-block').attr('data-status','A');
					} else {
						show_cont.attr('data-status','not-show');
						show_cont.find('.b2make-lista-option-block').attr('data-status','B');
					}
				});
			}
			
			if(b2make.posts_filter_widget_added){
				posts_filter_widget_update({});
			}
		}
	});
	
	$('#b2make-posts-filter-add').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var obj = b2make.conteiner_child_obj;
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+url_path+'?opcao=add&site=true&widget_id='+$(obj).attr('id'),'_self');
	});
	
	$(document.body).on('change','#b2make-posts_filter-contents-options-sel',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		$(obj).attr('data-conteudo-tipo',value);
		
		$('#b2make-posts_filter-contents-lista-cont').hide();
		$('#b2make-posts_filter-contents-lista-conteudo-tipo-cont').hide();
		$('#b2make-posts_filter-contents-options-cont').hide();
		
		switch($(obj).attr('data-conteudo-tipo')){
			case 'todos-posts': $('#b2make-posts_filter-contents-options-cont').show(); break;
			case 'escolha-pontual': $('#b2make-posts_filter-contents-lista-cont').show(); break;
			case 'conteudo-tipo': 
				$('#b2make-posts_filter-contents-lista-conteudo-tipo-cont').show();
				$('#b2make-posts_filter-contents-options-cont').show();
			break;
		}
		
		posts_filter_widget_update({});
	});
}

function posts_filter(){
	b2make.posts_filter = {};
	
	if(!b2make.msgs.posts_filterBotaoTexto)b2make.msgs.posts_filterBotaoTexto = 'FILTRAR';
	
	var id_func = 'posts-filter';
	var id_plugin = 'posts-filter';
	
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
			var sub_options2 = html.find('#b2make-widget-sub-options-posts-filter-ficha-html').clone();
			sub_options2.appendTo('#b2make-widget-options-hide');
			var sub_options3 = html.find('#b2make-widget-sub-options-posts-filter-conteudos').clone();
			sub_options3.appendTo('#b2make-widget-options-hide');
			var sub_options4 = html.find('#b2make-widget-sub-options-posts-filter-sem-resultados-html').clone();
			sub_options4.appendTo('#b2make-widget-options-hide');
			
			$.lista_start($('#b2make-posts_filter-contents-lista').get(0));
			$.lista_start($('#b2make-posts_filter-contents-lista-conteudo-tipo').get(0));
			
			posts_filter_start();
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_plugin+' - html - '+txt);
			console.log(txt);
		}
	});
	
	$('#b2make-'+id_plugin+'-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:id_plugin});
	});
	
	$('#b2make-'+id_plugin+'-callback').on('widget_added',function(e){
		if(!b2make.posts_filter_blocked && !b2make.posts_filter_prontos){
			b2make.posts_filter_widget_added = true;
		} else {
			b2make.posts_filter_widget_added_2 = true;
		}
		
	});
	
	$('#b2make-listener').on('publish-page',function(e,type,obj){
		if(type == id_plugin){
			$(obj).find('.b2make-widget-out').find('.b2make-posts-filter').find('.b2make-widget-loading').show();
		}
	});
	
	$('#b2make-listener').on('widgets-resize',function(){
		var obj = b2make.conteiner_child_obj;
		var type = $(obj).attr('data-type');
		
		if(type == id_plugin){
			if($(obj).attr('data-layout-orientacao') == 'vertical'){
				var cont = $(obj).find('.b2make-widget-out').find('.b2make-'+id_plugin);
				var menu = cont.find('.b2make-'+id_plugin+'-menu');
				var lista = cont.find('.b2make-'+id_plugin+'-lista');
				
				var widget_height = $(obj).height();
				var menu_height = menu.outerHeight(true);
				var lista_magin = parseInt(lista.css('marginTop'));
				
				var height = Math.floor(widget_height - menu_height - lista_magin);
				
				lista.height(height);
			}
		}
	});
}

posts_filter();