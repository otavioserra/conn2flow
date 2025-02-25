(function($) {
    $.fn.changeElementType = function(newType) {
        var attrs = {};

        $.each(this[0].attributes, function(idx, attr) {
            attrs[attr.nodeName] = attr.nodeValue;
        });

        this.replaceWith(function() {
            return $("<" + newType + "/>", attrs).append($(this).contents());
        });
    };
})(jQuery);

function banners_caixa_posicao_atualizar(p){
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	var imagem;
	var layout_tipo = $(obj).attr('data-layout-tipo');
	
	switch(layout_tipo){
		case 'caixa-seta':
			if(p.proximo){
				imagem = $(p.proximo);
			} else {
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').each(function(){
					imagem = $(this);
					return false;
				});
			}
			
			var margem_seta = parseInt(($(obj).attr('data-seta-margem') ? $(obj).attr('data-seta-margem') : '20'));
			var height = parseInt(($(obj).attr('data-seta-tamanho') ? $(obj).attr('data-seta-tamanho') : '30'));
			var width = Math.floor((height * 18)/28);
			
			var padding = ($(obj).attr('data-titulo-padding') ? $(obj).attr('data-titulo-padding') : '10');
			var topo = ($(obj).attr('data-titulo-topo') ? $(obj).attr('data-titulo-topo') : '290');
			var margem = ($(obj).attr('data-titulo-margem') ? $(obj).attr('data-titulo-margem') : '20');
			var wv = parseInt((parseInt(imagem.attr('data-image-width')) * parseInt($(obj).outerHeight())) / parseInt(imagem.attr('data-image-height')));
			var tamanho = Math.floor(wv - 2*parseInt(margem));
			var left = (width+3*margem_seta);
			
			imagem.find('.b2make-banners-widget-image-cont').each(function(){
				$(this).css('top',topo+'px');
				$(this).css('padding',padding+'px');
				$(this).css('left',left+'px');
			});
			
			if(p.criar){
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').find('.b2make-banners-widget-image-cont').each(function(){
					$(this).css('top',topo+'px');
					$(this).css('padding',padding+'px');
					$(this).css('left',left+'px');
				});
			} else {
				imagem.find('.b2make-banners-widget-image-cont').each(function(){
					$(this).css('top',topo+'px');
					$(this).css('padding',padding+'px');
					$(this).css('left',left+'px');
				});
			}
			
			var seta_cont_left = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-cont-left');
			var seta_cont_right = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-cont-right');
			var seta_left = seta_cont_left.find('.b2make-banners-widget-seta-2-left');
			var seta_right = seta_cont_right.find('.b2make-banners-widget-seta-2-right');
			
			seta_left.css('width',width+'px').css('height',height+'px');
			seta_right.css('width',width+'px').css('height',height+'px');
			seta_left.find('svg').css('width',width+'px').css('height',height+'px');
			seta_right.find('svg').css('width',width+'px').css('height',height+'px');
			
			seta_cont_left.css('width',(width+2*margem_seta)+'px');
			seta_cont_right.css('width',(width+2*margem_seta)+'px');
		break;
		default:
			if(p.proximo){
				imagem = $(p.proximo);
			} else {
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').each(function(){
					imagem = $(this);
					return false;
				});
			}
			
			var padding = ($(obj).attr('data-titulo-padding') ? $(obj).attr('data-titulo-padding') : '10');
			var topo = ($(obj).attr('data-titulo-topo') ? $(obj).attr('data-titulo-topo') : '290');
			var margem = ($(obj).attr('data-titulo-margem') ? $(obj).attr('data-titulo-margem') : '20');
			var wv = parseInt((parseInt(imagem.attr('data-image-width')) * parseInt($(obj).outerHeight())) / parseInt(imagem.attr('data-image-height')));
			var tamanho = Math.floor(wv - 2*parseInt(margem));
			var left = Math.floor((parseInt($(obj).outerWidth()) - tamanho)/2 - parseInt(padding));
			
			imagem.find('.b2make-banners-widget-image-cont').each(function(){
				$(this).css('top',topo+'px');
				$(this).css('padding',padding+'px');
				$(this).css('left',left+'px');
				$(this).css('width',tamanho+'px');
			});
			
			if(p.criar){
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').find('.b2make-banners-widget-image-cont').each(function(){
					$(this).css('top',topo+'px');
					$(this).css('padding',padding+'px');
					$(this).css('left',left+'px');
					$(this).css('width',tamanho+'px');
				});
			} else {
				imagem.find('.b2make-banners-widget-image-cont').each(function(){
					$(this).css('top',topo+'px');
					$(this).css('padding',padding+'px');
					$(this).css('left',left+'px');
					$(this).css('width',tamanho+'px');
				});
			}
			
			var topo_seta = ($(obj).attr('data-seta-topo') ? $(obj).attr('data-seta-topo') : '150');
			var margem_seta = parseInt(($(obj).attr('data-seta-margem') ? $(obj).attr('data-seta-margem') : '20'));
			var tamanho_seta = parseInt(($(obj).attr('data-seta-tamanho') ? $(obj).attr('data-seta-tamanho') : '30'));
			
			var seta_left = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left');
			var seta_right = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right');
			
			seta_left.css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			seta_right.css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			seta_left.find('svg').css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			seta_right.find('svg').css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			
			seta_left.css('top',topo_seta+'px');
			seta_right.css('top',topo_seta+'px');
			
			var lateral = (parseInt($(obj).outerWidth()) - wv)/2 + margem_seta;
			
			seta_left.css('left',lateral+'px');
			seta_right.css('right',lateral+'px');
	}
}

function banners_widgets_update(p){
	if(!p)p = {};
	
	switch(p.type){
		case 'banners-imagem-uploaded':
			var id = p.id;
			var dados = p.dados;
			var url = p.dados.imagem;
			
			$('.b2make-widget[data-type="banners"][data-banners-id="'+id+'"]').each(function(){
				if($(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').length >= 2){
					$(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left').show();
					$(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right').show();
				}
				
				$(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('#b2make-banners-widget-imagem-0').remove();
				
				var layout_dentro = '<div class="b2make-banners-widget-image-cont"><div class="b2make-banners-widget-image-titulo"></div><div class="b2make-banners-widget-image-sub-titulo"></div></div>';
				
				$(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').append($('<div id="b2make-banners-widget-imagem-'+dados.id+'" class="b2make-banners-widget-image" data-image-id="'+dados.id+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" style="background-image:url('+dados.imagem+');">'+layout_dentro+'</div>'));
				
				banners_caixa_posicao_atualizar({obj:this});
			});
		break;
		case 'banners-imagem-del':
			var id = p.id;
			var id_banners = p.id_banners;
			var imagem = p.url;
			
			$('.b2make-banners-widget-image[data-image-id="'+id+'"]').each(function(){
				$(this).remove();
			});
			
			$('.b2make-widget[data-type="banners"][data-banners-id="'+id_banners+'"]').each(function(){
				if($(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').length < 2){
					$(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left').hide();
					$(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right').hide();
				}
				
				if($(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').length == 0){
					var imagem = location.href+'images/b2make-banners-sem-imagem.png?v=2';
					$(this).find('.b2make-widget-out').find('.b2make-banners-widget-holder').append($('<div id="b2make-banners-widget-imagem-0" class="b2make-banners-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
				}
				
				banners_caixa_posicao_atualizar({obj:this});
			});
		break;
		case 'banners-del':
			var id = p.id;
			
			$('div.b2make-widget[data-type="banners"][data-banners-id="'+id+'"]').each(function(){
				$(this).find('div.b2make-widget-out').html('<div class="b2make-banners-widget-holder"></div>');
			});
		break;
		case 'banners-order':
			$('div.b2make-widget[data-type="banners"][data-banners-id="'+b2make.banners_atual+'"]').each(function(){
				banners_widget_create({conteiner_child_obj:this,banners_id:b2make.banners_atual,order:true});
			});
		break;
		case 'banners-data-edit':
			var id = p.id;
			
			$('.b2make-banners-widget-image[data-image-id="'+id+'"]').each(function(){
				var titulos_cont = $(this).find('.b2make-banners-widget-image-cont');
				
				var titulo = titulos_cont.find('.b2make-banners-widget-image-titulo').html();
				var sub_titulo = titulos_cont.find('.b2make-banners-widget-image-sub-titulo').html();
				
				if(
					titulo.length == 0 ||
					sub_titulo.length == 0
				){
					titulos_cont.hide();
				} else {
					titulos_cont.show();
				}
			});
		break;
	}
}

function banners_widget_create(p){
	var plugin_id = 'banners';
	if(!p)p = {};
	
	var id_func = 'banners-images';
	var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
	var obj_id = $(obj).attr('id');
	
	$(obj).attr('data-banners-id',p.banners_id);
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			id : p.banners_id
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				var layout_tipo = $(obj).attr('data-layout-tipo');
				
				switch(layout_tipo){
					case 'caixa-seta':
						$(obj).find('.b2make-widget-out').html('<div class="b2make-banners-widget-holder"><div class="b2make-banners-widget-seta-cont-left"><div class="b2make-banners-widget-seta-2-left"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-conteiner-banners-arrow-2.svg"></div></div><div class="b2make-banners-widget-seta-cont-right"><div class="b2make-banners-widget-seta-2-right"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-conteiner-banners-arrow.svg"></div></div></div>');
					break;
					default:
						$(obj).find('.b2make-widget-out').html('<div class="b2make-banners-widget-holder"><div class="b2make-banners-widget-seta-left"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-banners-arrow-2.svg"></div><div class="b2make-banners-widget-seta-right"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-banners-arrow.svg"></div></div>');
				}
				
				jQuery('img.svg').each(function(){
					var $img = jQuery(this);
					var imgID = $img.attr('id');
					var imgClass = $img.attr('class');
					var imgStyle = $img.attr('style');
					var imgURL = $img.attr('src');

					jQuery.get(imgURL, function(data) {
						// Get the SVG tag, ignore the rest
						var $svg = jQuery(data).find('svg');

						// Add replaced image's ID to the new SVG
						if(typeof imgID !== 'undefined') {
							$svg = $svg.attr('id', imgID);
						}
						// Add replaced image's classes to the new SVG
						if(typeof imgClass !== 'undefined') {
							$svg = $svg.attr('class', imgClass+' replaced-svg');
						}

						// Add replaced image's classes to the new SVG
						if(typeof imgStyle !== 'undefined') {
							$svg = $svg.attr('style', imgStyle);
						}

						// Remove any invalid XML tags as per http://validator.w3.org
						$svg = $svg.removeAttr('xmlns:a');

						// Replace image with new SVG
						$img.replaceWith($svg);
						
						var pai = $svg.parent().parent().parent().parent();
						var cor = pai.attr('data-seta-color-ahex');
						
						if(!cor){
							cor = 'ffffffff';
						}
						
						var cor_rgba = $.jPicker.ColorMethods.hexToRgba(cor);
						
						$svg.find('path').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
						$svg.find('rect').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
						$svg.find('polyline').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
						
						banners_caixa_posicao_atualizar({});
					}, 'xml');
				});
				
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left').hide();
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right').hide();
				
				switch(dados.status){
					case 'Ok':
						if(dados.images.length >= 2){
							$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left').show();
							$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right').show();
						}
						
						if(dados.images.length == 0){
							var imagem = location.href+'images/b2make-banners-sem-imagem.png?v=2';
							$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').append($('<div id="b2make-banners-widget-imagem-0" class="b2make-banners-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
						} else {
							for(var i=0;i<dados.images.length;i++){
								var layout_dentro = '<div class="b2make-banners-widget-image-cont" style="'+(dados.images[i].titulo.length != 0 && dados.images[i].sub_titulo.length != 0 ? 'display:block;': '')+'"><div class="b2make-banners-widget-image-titulo">'+dados.images[i].titulo+'</div><div class="b2make-banners-widget-image-sub-titulo">'+dados.images[i].sub_titulo+'</div></div>';
								
								if(dados.images[i].url){
									$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').append($('<a href="'+dados.images[i].url+'" id="b2make-banners-widget-imagem-'+dados.images[i].id+'" class="b2make-banners-widget-image" data-image-id="'+dados.images[i].id+'" data-image-url="'+dados.images[i].imagem+'" data-image-width="'+dados.images[i].width+'" data-image-height="'+dados.images[i].height+'" style="background-image:url('+dados.images[i].imagem+');">'+layout_dentro+'</a>'));
								} else {
									$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').append($('<div id="b2make-banners-widget-imagem-'+dados.images[i].id+'" class="b2make-banners-widget-image" data-image-id="'+dados.images[i].id+'" data-image-url="'+dados.images[i].imagem+'" data-image-width="'+dados.images[i].width+'" data-image-height="'+dados.images[i].height+'" style="background-image:url('+dados.images[i].imagem+');">'+layout_dentro+'</div>'));
								}
							}
							
							banners_caixa_posicao_atualizar({criar:true,order:p.order});
							
							if(b2make.banners_widget_added){
								$('#b2make-'+plugin_id+'-callback').trigger('conteiner_child_open');
								b2make.banners_widget_added = false;
							}
						}
					break;
					case 'NaoExisteId':
						var imagem = location.href+'images/b2make-banners-sem-imagem.png?v=2';
						$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').append($('<div id="b2make-banners-widget-imagem-0" class="b2make-banners-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
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

function banners_animation_proximo(p){
	var obj = p.obj;
	var inverso = p.inverso;
	var interacao = b2make.animation_interacao[$(obj).attr('id')];
	var tempo_exposicao = ($(obj).attr('data-tempo-exposicao') ? parseInt($(obj).attr('data-tempo-exposicao')) : 3000);
	
	setTimeout(function(){
		if(interacao == b2make.animation_interacao[$(obj).attr('id')])
			banners_animation_start({obj:obj,inverso:inverso});
	},tempo_exposicao);
}

function banners_animation_start(p){
	var obj = p.obj;
	
	if(b2make.banners_start[$(obj).attr('id')]){
		var inverso = p.inverso;
		var atual;
		var proximo;
		var found_flag = false;
		var proxima_flag = false;
		var count = 0;
		var width = $(obj).outerWidth();
		var height = $(obj).outerHeight();
		var tempo_transicao = ($(obj).attr('data-tempo-transicao') ? parseInt($(obj).attr('data-tempo-transicao')) : 500);
		var tipo = ($(obj).attr('data-animation-type') ? $(obj).attr('data-animation-type') : 'slideRight');
		var efeito = ($(obj).attr('data-ease-type') ? $(obj).attr('data-ease-type') : 'linear');
		var cont_hide = '#b2make-banners-lista-images-hide';
		var holder = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder');
		
		if(inverso){
			holder.find('.b2make-banners-widget-image').each(function(){
				if(!atual)atual = this;
				proximo = this;
				
				count++;
			});
			
			$(cont_hide).append($(proximo));
			$(holder).prepend($(proximo));
			
			switch(tipo){
				case 'slideLeft': tipo = 'slideRight'; break;
				case 'slideRight': tipo = 'slideLeft'; break;
				case 'slideTop': tipo = 'slideDown'; break;
				case 'slideDown': tipo = 'slideTop'; break;
			}
		} else {
			holder.find('.b2make-banners-widget-image').each(function(){
				if(atual && !proximo)proximo = this;
				if(!atual)atual = this;
				
				count++;
				
				if(atual && proximo) return false;
			});
		}
		
		if(count < 2) return;
		
		$(proximo).css('position','absolute');
		$(proximo).css('zIndex','1');
		
		switch(tipo){
			case 'slideLeft':
				$(proximo).css('top','0px');
				$(proximo).css('left',width+'px');
				
				$(proximo).stop().animate({
					left: 0
				}, tempo_transicao,efeito, function() {
					
					$(proximo).css('position','relative');
					$(proximo).css('top','auto');
					$(proximo).css('left','auto');
					$(proximo).css('zIndex','auto');
					
					if(!inverso){
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}
					
					banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
					banners_animation_proximo({obj:obj,inverso:inverso});
				});
			break;
			case 'slideRight':
				$(proximo).css('top','0px');
				$(proximo).css('left',(-width)+'px');
				
				$(proximo).stop().animate({
					left: 0
				}, tempo_transicao,efeito, function() {
					
					$(proximo).css('position','relative');
					$(proximo).css('top','auto');
					$(proximo).css('left','auto');
					$(proximo).css('zIndex','auto');
					
					if(!inverso){
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}
					
					banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
					banners_animation_proximo({obj:obj,inverso:inverso});
				});
			break;
			case 'slideTop':
				$(proximo).css('top',height+'px');
				$(proximo).css('left','0px');
				
				$(proximo).stop().animate({
					top: 0
				}, tempo_transicao,efeito, function() {
					
					$(proximo).css('position','relative');
					$(proximo).css('top','auto');
					$(proximo).css('left','auto');
					$(proximo).css('zIndex','auto');
					
					if(!inverso){
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}
					
					banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
					banners_animation_proximo({obj:obj,inverso:inverso});
				});
			break;
			case 'slideDown':
				$(proximo).css('top',(-height)+'px');
				$(proximo).css('left','0px');
				
				$(proximo).stop().animate({
					top: 0
				}, tempo_transicao,efeito, function() {
					
					$(proximo).css('position','relative');
					$(proximo).css('top','auto');
					$(proximo).css('left','auto');
					$(proximo).css('zIndex','auto');
					
					if(!inverso){
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}
					
					banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
					banners_animation_proximo({obj:obj,inverso:inverso});
				});
			break;
			case 'fade':
				$(proximo).css('top','0px');
				$(proximo).css('left','0px');
				$(proximo).css('opacity',0);
				
				$(proximo).stop().animate({
					opacity: 1
				}, tempo_transicao,efeito, function() {
					
					$(proximo).css('position','relative');
					$(proximo).css('top','auto');
					$(proximo).css('left','auto');
					$(proximo).css('zIndex','auto');
					
					if(!inverso){
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}
					
					banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
					banners_animation_proximo({obj:obj,inverso:inverso});
				});
			break;
			
		}
	}
}

function banners_animation_stop(obj){
	$(obj).attr('data-animation',null);
	$(obj).find('div.b2make-widget-out').find('div.b2make-banners-widget-holder').stop();
}

function banners_images_select(){
	var obj_selected = b2make.banners_imagem_selected;
	var obj_target = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
	var image_url = obj_selected.attr('data-image-url');
	var image_id = obj_selected.attr('data-image-id');
	var image_width = obj_selected.attr('data-image-width');
	var image_height = obj_selected.attr('data-image-height');
	
	$(obj_target).find('div.b2make-widget-out')
		.find('div.b2make-banners-widget-holder')
		.find('div.b2make-banners-widget-image[id="b2make-banners-widget-imagem-'+b2make.banners_atual+'"]')
		.css('backgroundImage','url('+image_url+')')
		.attr('data-banners-imagem-id',image_id)
		.attr('data-banners-imagem-width',image_width)
		.attr('data-banners-imagem-height',image_height);
	
	var image = $(obj_target).find('div.b2make-widget-out')
		.find('div.b2make-banners-widget-holder')
		.find('div.b2make-banners-widget-image[id="b2make-banners-widget-imagem-'+b2make.banners_atual+'"]');

	var target = image.find('.b2make-banners-widget-titulo');
	var imagem_width = parseInt(image.attr('data-banners-imagem-width'));
	var imagem_height = parseInt(image.attr('data-banners-imagem-height'));
	var conteiner_width = parseInt($('#b2make-woaf-imagem-val').val());
	
	//var altura = Math.floor((conteiner_width * imagem_height) / imagem_width);
	var altura = conteiner_width;
	
	target.css('top',(b2make.banners.margin_title+altura)+'px');
}

function banners_images_html(dados){
	$('#b2make-banners-lista-images').append($('<div id="b2make-banners-imagem-holder-'+dados.id+'" class="b2make-banners-image-holder b2make-tooltip" data-image-id="'+dados.id+'" data-titulo="'+dados.titulo+'" data-sub-titulo="'+dados.sub_titulo+'" data-url="'+dados.url+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" title="'+b2make.msgs.bannersFile+': '+dados.file+'"><div class="b2make-banners-data-edit b2make-tooltip" title="'+b2make.msgs.bannersEditX+'"></div><div class="b2make-banners-image-delete b2make-tooltip" title="'+b2make.msgs.bannersDeleteX+'"></div><img src="'+dados.mini+'"></div>'));
}

function banners_images(){
	var id_func = 'banners-images';
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			id : b2make.banners_atual
		},
		beforeSend: function(){
			$('#b2make-banners-lista-images').find('.b2make-loading-box').remove();
			$('<div class="b2make-loading-box"></div>').appendTo('#b2make-banners-lista-images');
		},
		success: function(txt){
			$('#b2make-banners-lista-images').find('.b2make-loading-box').remove();
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						for(var i=0;i<dados.images.length;i++){
							banners_images_html(dados.images[i]);
						}
						
						$('.b2make-tooltip').tooltip({
							show: {
								effect: "fade",
								delay: 400
							}
						});
						
						if(b2make.banners_widget_update){
							banners_widgets_update({type:'banners-del',id:b2make.banners_widget_update_id});
							b2make.banners_widget_update_id = false;
							b2make.banners_widget_update = false;
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
			$('#b2make-banners-lista-images').find('.b2make-loading-box').remove();
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
}

function banners_imagens_delete(){
	var id = b2make.banners_imagens_delete_id;
	var id_func = 'banners-images-del';
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			id : id,
			banners : b2make.banners_atual
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
						var url = $('.b2make-banners-image-holder[data-image-id="'+id+'"]').attr('data-image-url');
						
						$('.b2make-banners-image-holder[data-image-id="'+id+'"]').remove();
						$.disk_usage_diskused_del(dados.size);
						banners_widgets_update({type:'banners-imagem-del',id:id,id_banners:b2make.banners_atual,url:url});
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

function banners_menu_html(dados){
	if(!dados)dados = {};
	$('#b2make-banners-lista-banners').prepend($('<div class="b2make-banners-lista-banners"><div class="b2make-banners-show b2make-tooltip" title="'+b2make.msgs.bannersShow+'" data-status="'+(dados.banners_show ? 'show' : 'not-show')+'" data-banners-id="'+dados.banners_id+'"></div><div class="b2make-banners-nome b2make-tooltip" title="'+b2make.msgs.bannersNome+'" data-status="'+(dados.banners_selected ? 'show' : 'not-show')+'" data-banners-id="'+dados.banners_id+'">'+dados.banners_nome+'</div><div class="b2make-banners-edit b2make-tooltip" data-banners-id="'+dados.banners_id+'" title="'+b2make.msgs.bannersEdit+'"></div><div class="b2make-banners-delete b2make-tooltip" data-banners-id="'+dados.banners_id+'" title="'+b2make.msgs.bannersDelete+'"></div><div class="clear"></div></div>'));
}

function banners_dados_edit(id){
	b2make.id_site_banners = id;
	
	$.dialogbox_open({
		width:440,
		height:270,
		message:true,
		calback_yes: 'b2make-banners-data-edit-calback',
		title: b2make.msgs.bannersEditDataTitle,
		coneiner: 'b2make-formulario-banners-dados'
	});
	
	$('#b2make-fb-titulo').val($('#b2make-banners-imagem-holder-'+id).attr('data-titulo'));
	$('#b2make-fb-sub-titulo').val($('#b2make-banners-imagem-holder-'+id).attr('data-sub-titulo'));
	$('#b2make-fb-url').val($('#b2make-banners-imagem-holder-'+id).attr('data-url'));
}

function banners_dados_edit_base(){
	$.dialogbox_close();
	
	var opcao = 'banners-data-edit';
	var id = b2make.id_site_banners;
	
	$('#b2make-banners-imagem-holder-'+id).attr('data-titulo',$('#b2make-fb-titulo').val());
	$('#b2make-banners-imagem-holder-'+id).attr('data-sub-titulo',$('#b2make-fb-sub-titulo').val());
	$('#b2make-banners-imagem-holder-'+id).attr('data-url',$('#b2make-fb-url').val());
	
	$('#b2make-banners-widget-imagem-'+id).find('.b2make-banners-widget-image-cont').find('.b2make-banners-widget-image-titulo').html($('#b2make-fb-titulo').val());
	$('#b2make-banners-widget-imagem-'+id).find('.b2make-banners-widget-image-cont').find('.b2make-banners-widget-image-sub-titulo').html($('#b2make-fb-sub-titulo').val());
	$('#b2make-banners-widget-imagem-'+id).attr('href',$('#b2make-fb-url').val());
	
	if($('#b2make-fb-url').val()){
		$('#b2make-banners-widget-imagem-'+id).changeElementType('a');
	} else {
		$('#b2make-banners-widget-imagem-'+id).changeElementType('div');
	}
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : opcao,
			titulo : $('#b2make-fb-titulo').val(),
			sub_titulo : $('#b2make-fb-sub-titulo').val(),
			url : $('#b2make-fb-url').val(),
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
						banners_widgets_update({type:'banners-data-edit',id:id});
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

function banners_add(){
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-banners-add-calback',
		title: b2make.msgs.bannersAddTitle,
		coneiner: 'b2make-formulario-banners'
	});
}

function banners_add_base(){
	var id_func = 'banners-add';
	var form_id = 'b2make-formulario-banners';
	
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
							$('.b2make-banners-show').each(function(){
								$(this).attr('data-status','not-show');
							});
							
							dados.banners_show = true;
							dados.banners_selected = true;
							banners_menu_html(dados);
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							$.dialogbox_close();
							
							b2make.banners_atual = dados.banners_id;
							b2make.banners_nome = dados.banners_nome;
							
							$('#b2make-banners-btn-mask').hide();
							$('#b2make-banners-lista-images').html('');
							
							banners_widget_create({banners_id:b2make.banners_atual});
							
							if(!b2make.banners_todos_ids)b2make.banners_todos_ids = new Array();
							b2make.banners_todos_ids.push(dados.banners_id);
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

function banners_edit(id){
	$('#b2make-formulario-banners #b2make-fb-nome').val($('.b2make-banners-nome[data-banners-id="'+id+'"]').html());
	
	b2make.banners_edit_id = id;
	
	$.dialogbox_open({
		width:350,
		height:200,
		message:true,
		calback_yes: 'b2make-banners-edit-calback',
		title: b2make.msgs.bannersEditTitle,
		coneiner: 'b2make-formulario-banners'
	});
}

function banners_edit_base(){
	var id_func = 'banners-edit';
	var form_id = 'b2make-formulario-banners';
	var id = b2make.banners_edit_id;
	
	b2make.banners_edit_id = false;
	
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
							$('.b2make-banners-nome[data-banners-id="'+id+'"]').html(dados.nome);
							
							banners_widgets_update({type:'banners-edit',id:id,nome:dados.nome});
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

function banners_del(id){
	b2make.banners_del_id = id;
	
	var msg = b2make.msgs.bannersDelTitle;
	msg = msg.replace(/#banners#/gi,$('.b2make-banners-nome[data-banners-id="'+id+'"]').html());
	
	$.dialogbox_open({
		confirm:true,
		calback_yes: 'b2make-banners-del-calback',
		msg: msg
	});
}

function banners_del_base(){
	var id_func = 'banners-del';
	var id = b2make.banners_del_id;
	
	b2make.banners_del_id = false;

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
						$('.b2make-banners-delete[data-banners-id="'+id+'"]').parent().remove();
						$.dialogbox_close();
						
						var id_aux = $('#b2make-banners-lista-banners .b2make-banners-lista-banners:first-child .b2make-banners-show').attr('data-banners-id');
						
						$('#b2make-banners-lista-images').html('');
						
						if(id_aux){
							b2make.banners_atual = id_aux;
							b2make.banners_nome = $('.b2make-banners-nome[data-banners-id="'+id_aux+'"]').html();
							
							$('.b2make-banners-nome').each(function(){
								$(this).attr('data-status','not-show');
							});
							
							$('.b2make-banners-nome[data-banners-id="'+id_aux+'"]').attr('data-status','show');
							
							banners_images();
						} else {
							$('#b2make-banners-btn-mask').show();
						}
						
						$.disk_usage_diskused_del(dados.size);
						banners_widgets_update({type:'banners-del',id:id});
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

function banners_upload_params(){
	return new Array({
		variavel : 'banners',
		valor : b2make.banners_atual,
	})
}

function banners_upload_callback(dados){
	var id_func = 'conteiner-banners';
	
	switch(dados.status){
		case 'Ok':
			banners_images_html(dados);
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			$.disk_usage_diskused_add(dados.size);
			banners_widgets_update({type:'banners-imagem-uploaded',id:b2make.banners_atual,dados:dados});
		break;
		case 'SemPermissao':
			sem_permissao_redirect();
		break;
		default:
			console.log('ERROR - '+id_func+' - '+dados.status);
		
	}
}

function banners_upload(){
	$.upload_files_start({
		url_php : 'uploadbanners.php',
		input_selector : '#b2make-banners-input',
		file_type : 'imagem',
		post_params : banners_upload_params,
		callback : banners_upload_callback
	});
}

function banners_start(){
	var plugin_id = 'banners';
	
	b2make.banners = {};
	
	b2make.banners.fator_ajuste = 0.8;
	b2make.banners.margin_title = 4;
	b2make.banners.margin_image = 0;
	
	if(!b2make.msgs.bannersDeleteX)b2make.msgs.bannersDeleteX = 'Clique para excluir esta imagem';
	if(!b2make.msgs.bannersEditX)b2make.msgs.bannersEditX = 'Clique para editar os dados desta imagem';
	if(!b2make.msgs.bannersFile)b2make.msgs.bannersFile = 'Arquivo';
	if(!b2make.msgs.bannersEdit)b2make.msgs.bannersEdit = 'Clique para Editar o Nome deste banner';
	if(!b2make.msgs.bannersNome)b2make.msgs.bannersNome = 'Clique para alterar as imagens deste banner';
	if(!b2make.msgs.bannersDelete)b2make.msgs.bannersDelete = 'Clique para deletar este banner.';
	if(!b2make.msgs.bannersShow)b2make.msgs.bannersShow = 'Clique para selecionar este banner no widget banners.';
	if(!b2make.msgs.bannersDelTitle)b2make.msgs.bannersDelTitle = 'Tem certeza que deseja excluir <b>#banners#</b>?';
	if(!b2make.msgs.bannersEditTitle)b2make.msgs.bannersEditTitle = 'Editar Nome do banners';
	if(!b2make.msgs.bannersAddTitle)b2make.msgs.bannersAddTitle = 'Adicionar banners';
	if(!b2make.msgs.bannersNaoHa)b2make.msgs.bannersNaoHa = 'N&atilde;o h&aacute; nenhum banners definido. Clique no bot&atilde;o <b>CRIAR banners</b> antes de enviar imagens.';
	if(!b2make.msgs.bannersEditDataTitle)b2make.msgs.bannersEditDataTitle = 'Editar dados da imagem';
	if(!b2make.wo_banners_titulo_max_value)b2make.wo_banners_titulo_max_value = 999;
	if(!b2make.wo_banners_titulo_min_value)b2make.wo_banners_titulo_min_value = 0;
	if(!b2make.wo_banners_animation_max_value)b2make.wo_banners_animation_max_value = 99999;
	if(!b2make.wo_banners_animation_min_value)b2make.wo_banners_animation_min_value = 0;
	
	b2make.animation_interacao = new Array();
	
	$(b2make.widget).each(function(){
		switch($(this).attr('data-type')){
			case plugin_id:
				var types = new Array('titulo','sub-titulo');
		
				for(var i=0;i<types.length;i++){
					if($(this).attr('data-google-font-'+types[i]) == 'sim'){
						$.google_fonts_wot_load({
							family : $(this).attr('data-'+types[i]+'-font-family'),
							nao_carregamento : true
						});
					}
				}
				
				var obj = this;
				
				b2make.animation_interacao[$(obj).attr('id')] = 0;
				banners_animation_proximo({obj:obj});
			break;
		}
	});
	
	banners_upload();
	
	b2make.banners_confirm_delete = true;
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
						var banners_show,banners_selected;
						var banners_todos_ids = new Array();
						
						for(var i=0;i<dados.resultado.length;i++){
							banners_show = false;
							banners_selected = false;
							
							if(i==dados.resultado.length - 1){
								b2make.banners_atual = dados.resultado[i].id_site_banners;
								b2make.banners_nome = dados.resultado[i].nome;
								banners_selected = true;
								banners_show = true;
								banners_images();
								$('#b2make-banners-btn-mask').hide();
							}
							
							banners_menu_html({
								banners_selected:banners_selected,
								banners_show:banners_show,
								banners_id:dados.resultado[i].id_site_banners,
								banners_nome:dados.resultado[i].nome
							});
							
							if(!b2make.banners_todos_ids){
								banners_todos_ids.push(dados.resultado[i].id_site_banners);
							}
						}
						
						if(!b2make.banners_todos_ids){
							b2make.banners_todos_ids = banners_todos_ids;
						}
						
						if(b2make.banners_widget_added)banners_widget_create({banners_id:b2make.banners_atual});
						b2make.banners_widget_added_2 = true;
						
						
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
	
	$(document.body).on('change','#b2make-wo-banners-layout-tipo',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		var banners_id = $(obj).attr('data-banners-id');
		
		$(obj).attr('data-layout-tipo',value);
		
		banners_widget_create({banners_id:banners_id});
	});
	
	$('#b2make-banners-btn-mask').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		$.dialogbox_open({
			msg: b2make.msgs.bannersNaoHa
		});
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-image-delete',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();
		var msg = b2make.msgs.imagemDelete;
		
		b2make.banners_imagens_delete_id = $(this).parent().attr('data-image-id');
		
		if(b2make.banners_confirm_delete){
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-banners-image-delete-yes',
				msg: msg
			});
		} else {
			banners_imagens_delete();
		}
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-image-delete-yes',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		banners_imagens_delete();
	});
	
	$('#b2make-banners-confirm-delete').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		$(this).find('input').attr("checked", !$(this).find('input').attr("checked"));
		
		if($(this).find('input').attr("checked")){
			b2make.banners_confirm_delete = true;
		} else {
			b2make.banners_confirm_delete = false;
		}
	});
	
	$('#b2make-banners-confirm-delete-input').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();

		if($(this).attr("checked")){
			b2make.banners_confirm_delete = false;
		} else {
			b2make.banners_confirm_delete = true;
		}
	});
	
	$('#b2make-banners-add').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		banners_add();
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-add-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		banners_add_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-show',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-banners-id');
		
		if($(this).attr('data-status') == 'not-show'){
			$('.b2make-banners-show').each(function(){
				$(this).attr('data-status','not-show');
			});
			
			$(this).attr('data-status','show');
			
			banners_widget_create({banners_id:id});
		}
		
		$('.b2make-banners-nome').each(function(){
			$(this).attr('data-status','not-show');
		});
		
		var nome_obj = $(this).parent().find('.b2make-banners-nome');
		
		nome_obj.attr('data-status','show');
		
		var id = nome_obj.attr('data-banners-id');
		
		b2make.banners_atual = nome_obj.attr('data-banners-id');
		b2make.banners_nome = nome_obj.html();
		
		$('#b2make-banners-lista-images').html('');
		banners_images();
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-nome',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		$('.b2make-banners-nome').each(function(){
			$(this).attr('data-status','not-show');
		});
		
		$(this).attr('data-status','show');
		
		var id = $(this).attr('data-banners-id');
		
		b2make.banners_atual = $(this).attr('data-banners-id');
		b2make.banners_nome = $(this).html();
		
		$('#b2make-banners-lista-images').html('');
		banners_images();
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-data-edit',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).parent().attr('data-image-id');
		
		banners_dados_edit(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-data-edit-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		banners_dados_edit_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-edit',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-banners-id');
		banners_edit(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-edit-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		banners_edit_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-delete',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var id = $(this).attr('data-banners-id');
		banners_del(id);
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-del-calback',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		banners_del_base();
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-image-holder',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();
		
		banners_image_mouseup();
	});
	
	$(window).on('mouseup tap',function(e){
		banners_image_mouseup();
	});
	
	$(document.body).on('mousedown','.b2make-banners-image-holder',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		e.stopPropagation();
		var obj = this;
		
		b2make.banners_image_holder_mouseup = false;
		
		setTimeout(function(){
			b2make.banners_image_holder_mousedown = true;
			banners_image_order_start(obj,e);
		},400);
	});
	
	$(document.body).on('taphold','.b2make-banners-image-holder',function(e){
		e.stopPropagation();
		
		b2make.banners_image_holder_mouseup = false;
		b2make.banners_image_holder_mousedown = true;
		
		banners_image_order_start(this,e);
	});
	
	function banners_image_order_start(obj,e){
		if(b2make.banners_image_holder_mouseup)return;
		
		var top = $(obj).offset().top;
		var left = $(obj).offset().left;
		var mx = e.pageX - left;
		var my = e.pageY - top;
		
		$(obj).css('position','absolute');
		$(obj).css('zIndex','999');
		
		b2make.banners_image_holder_mousemove = true;
		b2make.banners_image_holder_obj = obj;
		b2make.banners_image_holder_obj_x = mx;
		b2make.banners_image_holder_obj_y = my;
		b2make.banners_image_holder_obj_w = parseInt($(obj).outerWidth(true));
		b2make.banners_image_holder_obj_h = parseInt($(obj).outerHeight(true));
		
		var mx_start = e.pageX - $('#b2make-banners-lista-images').offset().left;
		var my_start = e.pageY - $('#b2make-banners-lista-images').offset().top;
		
		b2make.banners_image_holder_coluna = Math.floor((mx_start / b2make.banners_image_holder_obj_w));
		b2make.banners_image_holder_linha = Math.floor((my_start / b2make.banners_image_holder_obj_h));
		
		banners_image_order_grid(b2make.banners_image_holder_coluna,b2make.banners_image_holder_linha);
		
		mx_start = mx_start - b2make.banners_image_holder_obj_x;
		my_start = my_start - b2make.banners_image_holder_obj_y;
		
		$(obj).css('left',mx_start);
		$(obj).css('top',my_start);
	}
	
	$(window).on('mousemove touchmove',function(e){
		if(b2make.banners_image_holder_mousemove){
			var holder = '#b2make-banners-lista-images';
			var ajuste_x = 30;
			var obj = b2make.banners_image_holder_obj;
			var mx = e.pageX - $(holder).offset().left;
			var my = e.pageY - $(holder).offset().top;
			
			if(mx < 0)mx = 0; if(mx > $(holder).width()) mx = $(holder).width();
			if(my < 0)my = 0; if(my > $(holder).height()) my = $(holder).height();
			
			$(obj).css('left',mx - b2make.banners_image_holder_obj_x + ajuste_x);
			$(obj).css('top',my - b2make.banners_image_holder_obj_y);
			
			var coluna = Math.floor((mx / b2make.banners_image_holder_obj_w));
			var linha = Math.floor((my / b2make.banners_image_holder_obj_h));
			
			if(
				b2make.banners_image_holder_linha != linha ||
				b2make.banners_image_holder_coluna != coluna
			)
				banners_image_order_grid(coluna,linha);
		}
	});
	
	function banners_image_mouseup(){
		if(b2make.banners_image_holder_mousedown){
			b2make.banners_image_holder_mousedown = false;
			banners_image_order_stop();
		}
		
		b2make.banners_image_holder_mousemove = false;
		b2make.banners_image_holder_mouseup = true;
	}

	function banners_image_order_stop(){
		b2make.banners_image_holder_mousemove = false;
		
		if(!b2make.banners_mask)return;
		
		$(b2make.banners_mask).before(b2make.banners_image_holder_obj);
		
		$(b2make.banners_image_holder_obj).css('position','relative');
		$(b2make.banners_image_holder_obj).css('zIndex','auto');
		$(b2make.banners_image_holder_obj).css('top','auto');
		$(b2make.banners_image_holder_obj).css('left','auto');
		
		b2make.banners_mask.hide();
		
		var count = 0;
		var ids = '';
		
		$('.b2make-banners-image-holder').each(function(){
			count++;
			var id = $(this).attr('data-image-id');
			
			ids = ids + (ids ? ';' : '') + id + ',' + count;
		});
		
		var opcao = 'banners-order';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
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
							banners_widgets_update({type:'banners-order'});
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
	
	function banners_image_order_grid(coluna,linha){
		var colunas = 5;
		var total = parseInt($('.b2make-banners-image-holder').length - 1);
		
		if(!b2make.banners_mask){
			b2make.banners_mask = $('<div id="b2make-banners-mask"></div>');
			b2make.banners_mask.appendTo('#b2make-banners-lista-images-hide');
			
		} else {
			b2make.banners_mask = $('#b2make-banners-mask');
		}
		
		if(coluna >= colunas) coluna = colunas;
		
		var count = 0;
		b2make.banners_mask_position = linha * 5 + coluna + 1;
		
		if(b2make.banners_mask_position < 0) b2make.banners_mask_position = 0;
		if(b2make.banners_mask_position > total) b2make.banners_mask_position = total;
		
		b2make.banners_mask.show();
		$('.b2make-banners-image-holder').each(function(){
			var id = $(this).attr('id');
			var id_holder = $(b2make.banners_image_holder_obj).attr('id');
			
			if(count == b2make.banners_mask_position && id != id_holder){
				b2make.banners_mask.appendTo('#b2make-banners-lista-images-hide');
				switch(count){
					case 1:
						b2make.banners_mask.prependTo('#b2make-banners-lista-images');
					break;
					case total:
						b2make.banners_mask.appendTo('#b2make-banners-lista-images');
					break;
					default:
						$(this).before(b2make.banners_mask);
				}
				return false;
			}
			count++;
		});
		
		b2make.banners_image_holder_linha = linha;
		b2make.banners_image_holder_coluna = coluna;
	}
	
	$('#b2make-listener').on('widgets-resize',function(){
		switch(b2make.conteiner_child_type){
			case 'banners':
				banners_caixa_posicao_atualizar({});
			break;
		}
	});

	$('#b2make-wo-banners-animation-start-pause').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var obj = b2make.conteiner_child_obj;
		if(b2make.banners_start[$(obj).attr('id')]){
			$(this).css('backgroundPosition','0px 0px');
			b2make.banners_start[$(obj).attr('id')] = false;
			banners_animation_stop(obj);
		} else {
			$(this).css('backgroundPosition','-20px 0px');
			b2make.banners_start[$(obj).attr('id')] = true;
			banners_animation_start({obj:obj});
		}
	});
	
	$('#b2make-wo-banners-seta-cor-val,#b2make-wo-banners-widget-cor-val,#b2make-wo-banners-caixa-cor-val,#b2make-wo-banners-titulo-cor-val,#b2make-wo-banners-sub-titulo-cor-val').on('changeColor',function(e){
		var id = $(this).attr('id');
		var bg = $(b2make.jpicker.obj).css('background-color');
		var ahex = $(b2make.jpicker.obj).attr('data-ahex');
		var obj = b2make.conteiner_child_obj;
		var layout_tipo = $(obj).attr('data-layout-tipo');
		
		switch(id){
			case 'b2make-wo-banners-widget-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-cont-left').css('background-color',bg);
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-cont-right').css('background-color',bg);
				$(obj).attr('data-widget-color-ahex',ahex);	
			break;
			case 'b2make-wo-banners-titulo-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').find('.b2make-banners-widget-image-cont').find('.b2make-banners-widget-image-titulo').css('color',bg);
				$(obj).attr('data-titulo-color-ahex',ahex);	
			break;
			case 'b2make-wo-banners-sub-titulo-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').find('.b2make-banners-widget-image-cont').find('.b2make-banners-widget-image-sub-titulo').css('color',bg);
				$(obj).attr('data-sub-titulo-color-ahex',ahex);	
			break;
			case 'b2make-wo-banners-caixa-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').find('.b2make-banners-widget-image-cont').css('background-color',bg);
				$(obj).attr('data-caixa-color-ahex',ahex);	
			break;
			case 'b2make-wo-banners-seta-cor-val':
				$(obj).attr('data-seta-color-ahex',ahex);
				
				var cor = ahex;
				
				if(cor){
					var cor_rgba = $.jPicker.ColorMethods.hexToRgba(cor);
					
					switch(layout_tipo){
						case 'caixa-seta':
							var left = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-cont-left').find('.b2make-banners-widget-seta-2-left').find('svg');
							var right = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-cont-right').find('.b2make-banners-widget-seta-2-right').find('svg');
							
							left.find('polyline').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
							right.find('polyline').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
						break;
						default:
							var left = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left').find('svg');
							var right = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right').find('svg');
							
							left.find('path').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
							left.find('rect').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
							right.find('path').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
							right.find('rect').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
					}
				}
			break;
			
		}
	});
	
	$('#b2make-wo-banners-titulo-text-cont,#b2make-wo-banners-sub-titulo-text-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
		var obj = b2make.conteiner_child_obj;
		var target;
		var cssVar = '';
		var noSize = false;
		var id_bruto = $(this).attr('id');
		var mudar_height = false;
		var id = id_bruto.replace(/b2make-wo-banners-/gi,'');
		
		id = id.replace(/-text-cont/gi,'');
		
		switch(id_bruto){
			case 'b2make-wo-banners-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').find('.b2make-banners-widget-image-cont').find('.b2make-banners-widget-image-titulo'); mudar_height = true; break;
			case 'b2make-wo-banners-sub-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-image').find('.b2make-banners-widget-image-cont').find('.b2make-banners-widget-image-sub-titulo'); mudar_height = true; break;
		}
		
		switch(e.type){
			case 'changeFontFamily': 
				cssVar = 'fontFamily'; target.css(cssVar,$(this).attr('data-font-family')); $(obj).attr('data-'+id+'-font-family',$(this).attr('data-font-family')); 
				
				$(obj).attr('data-google-font-'+id,$(this).attr('data-google-font'));
			break;
			case 'changeFontSize': 
				cssVar = 'fontSize';  target.css(cssVar,$(this).attr('data-font-size')+'px'); target.css('line-height',$(this).attr('data-font-size')+'px'); $(obj).attr('data-'+id+'-font-size',$(this).attr('data-font-size')); 
			break;
			case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).attr('data-font-align'));$(obj).attr('data-'+id+'-font-align',$(this).attr('data-font-align')); break;
			case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).attr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).attr('data-'+id+'-font-italico',$(this).attr('data-font-italico')); break;
			case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).attr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).attr('data-'+id+'-font-negrito',$(this).attr('data-font-negrito')); break;
		}
	});
	
	$('.b2make-tooltip').tooltip({
		show: {
			effect: "fade",
			delay: 400
		}
	});
	
	$('#b2make-wo-banners-titulo-topo').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_titulo_max_value){
			this.value = b2make.wo_banners_titulo_max_value;
			value = b2make.wo_banners_titulo_max_value;
		}
		
		if(value < b2make.wo_banners_titulo_min_value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		$(obj).attr('data-titulo-topo',value);
		
		banners_caixa_posicao_atualizar({criar:true});
	});
	
	$('#b2make-wo-banners-titulo-margem').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_titulo_max_value){
			this.value = b2make.wo_banners_titulo_max_value;
			value = b2make.wo_banners_titulo_max_value;
		}
		
		if(value < b2make.wo_banners_titulo_min_value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		$(obj).attr('data-titulo-margem',value);
		
		banners_caixa_posicao_atualizar({criar:true});
	});
	
	$('#b2make-wo-banners-titulo-padding').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_titulo_max_value){
			this.value = b2make.wo_banners_titulo_max_value;
			value = b2make.wo_banners_titulo_max_value;
		}
		
		if(value < b2make.wo_banners_titulo_min_value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		$(obj).attr('data-titulo-padding',value);
		
		banners_caixa_posicao_atualizar({criar:true});
	});
	
	$('#b2make-wo-banners-seta-topo').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_titulo_max_value){
			this.value = b2make.wo_banners_titulo_max_value;
			value = b2make.wo_banners_titulo_max_value;
		}
		
		if(value < b2make.wo_banners_titulo_min_value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		$(obj).attr('data-seta-topo',value);
		
		banners_caixa_posicao_atualizar({});
	});
	
	$('#b2make-wo-banners-seta-margem').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_titulo_max_value){
			this.value = b2make.wo_banners_titulo_max_value;
			value = b2make.wo_banners_titulo_max_value;
		}
		
		if(value < b2make.wo_banners_titulo_min_value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		$(obj).attr('data-seta-margem',value);
		
		banners_caixa_posicao_atualizar({});
	});
	
	$('#b2make-wo-banners-seta-tamanho').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_titulo_max_value){
			this.value = b2make.wo_banners_titulo_max_value;
			value = b2make.wo_banners_titulo_max_value;
		}
		
		if(value < b2make.wo_banners_titulo_min_value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_titulo_min_value;
		}
		
		$(obj).attr('data-seta-tamanho',value);
		
		banners_caixa_posicao_atualizar({});
	});
	
	$('#b2make-wo-banners-seta-visivel').on('change',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		if(value == 's'){
			$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left').show();
			$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right').show();
		} else {
			$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-left').hide();
			$(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder').find('.b2make-banners-widget-seta-right').hide();
		}
		
		$(obj).attr('data-seta-visivel',value);
	});
	
	$('#b2make-wo-banners-tempo-transicao').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_animation_max_value){
			this.value = b2make.wo_banners_animation_max_value;
			value = b2make.wo_banners_animation_max_value;
		}
		
		if(value < b2make.wo_banners_animation_min_value){
			value = b2make.wo_banners_animation_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_animation_min_value;
		}
		
		$(obj).attr('data-tempo-transicao',value);
	});
	
	$('#b2make-wo-banners-tempo-exposicao').keyup(function (e) {
		var obj = b2make.conteiner_child_obj;
		var value = parseInt(this.value);
		
		if(value > b2make.wo_banners_animation_max_value){
			this.value = b2make.wo_banners_animation_max_value;
			value = b2make.wo_banners_animation_max_value;
		}
		
		if(value < b2make.wo_banners_animation_min_value){
			value = b2make.wo_banners_animation_min_value;
		}
		
		if(!value){
			value = b2make.wo_banners_animation_min_value;
		}
		
		$(obj).attr('data-tempo-exposicao',value);
	});
	
	$('#b2make-wo-banners-animation-type').on('change',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		$(obj).attr('data-animation-type',value);
	});
	
	$('#b2make-wo-banners-ease-type').on('change',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		$(obj).attr('data-ease-type',value);
	});

	$(document.body).on('mouseup tap','.b2make-banners-widget-seta-right',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var obj = $(this).parent().parent().parent().get(0);
		
		b2make.animation_interacao[$(obj).attr('id')]++;
		banners_animation_start({obj:obj});
	});
	
	$(document.body).on('mouseup tap','.b2make-banners-widget-seta-left',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var obj = $(this).parent().parent().parent().get(0);
		
		b2make.animation_interacao[$(obj).attr('id')]++;
		banners_animation_start({obj:obj,inverso:true});
	});

	$('#b2make-banners-btn').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		$(this).find('#b2make-banners-input').trigger('click');
	});	
}

var _plugin_id = 'banners';

window[_plugin_id] = function(){
	var id_func = 'banners';
	var plugin_id = _plugin_id;
	
	b2make.banners_start = new Array();
	
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
			
			var swfupload = html.find('#b2make-banners-btn-real').clone();
			swfupload.appendTo('#b2make-lightbox');
			
			var formulario = html.find('#b2make-formulario-banners').clone();
			formulario.appendTo('#b2make-formularios');
			var formulario = html.find('#b2make-formulario-banners-dados').clone();
			formulario.appendTo('#b2make-formularios');
			
			if(!b2make.swfupload_btn_real) b2make.swfupload_btn_real = new Array();
			
			b2make.swfupload_btn_real[plugin_id] = {
				obj : '#b2make-banners-btn-real'
			};
			
			$.fonts_load({obj:'#b2make-widget-options-'+plugin_id});
			$.jpicker_load({obj:'#b2make-widget-options-'+plugin_id});
			
			$.menu_conteiner_aba_load({
				id:plugin_id,
				html:html.find('#b2make-conteiner-aba-extra-'+plugin_id).clone()
			});
			
			$.fonts_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			$.jpicker_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			
			$.widget_specific_options_open();
			$.widget_sub_options_open();
			$.menu_conteiner_aba_extra_open();
			banners_start();
		},
		error: function(txt){
			console.log('ERROR AJAX - '+plugin_id+' - html - '+txt);
		}
	});
	
	$('#b2make-'+plugin_id+'-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:plugin_id});
	});
	
	$('#b2make-'+plugin_id+'-callback').on('widget_added',function(e){
		if(b2make.banners_widget_added_2)banners_widget_create({banners_id:b2make.banners_atual});
		b2make.banners_widget_added = true;
	});
	
	$('#b2make-'+plugin_id+'-callback').on('conteiner_child_open',function(e){
		var obj = b2make.conteiner_child_obj;
		
		if($(obj).attr('data-layout-tipo')){
			layout_tipo = $(obj).attr('data-layout-tipo');
			var option = $('#b2make-wo-banners-layout-tipo').find("[value='" + $(obj).attr('data-layout-tipo') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-wo-banners-layout-tipo').find(":first");
			option.prop('selected', 'selected');
		}
		
		if(b2make.banners_start[$(obj).attr('id')]){
			$('#b2make-wo-banners-animation-start-pause').css('backgroundPosition','-20px 0px');
		} else {
			$('#b2make-wo-banners-animation-start-pause').css('backgroundPosition','0px 0px');
		}
		
		if($(obj).attr('data-animation-type')){
			var option = $('#b2make-wo-banners-animation-type').find("[value='" + $(obj).attr('data-animation-type') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-wo-banners-animation-type').find(":first");
			option.prop('selected', 'selected');
		}
		
		if($(obj).attr('data-ease-type')){
			var option = $('#b2make-wo-banners-ease-type').find("[value='" + $(obj).attr('data-ease-type') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-wo-banners-ease-type').find(":first");
			option.prop('selected', 'selected');
		}
		
		if($(obj).attr('data-tempo-transicao')){
			$('#b2make-wo-banners-tempo-transicao').val($(obj).attr('data-tempo-transicao'));
		} else {
			$('#b2make-wo-banners-tempo-transicao').val('500');
		}
		
		if($(obj).attr('data-tempo-exposicao')){
			$('#b2make-wo-banners-tempo-exposicao').val($(obj).attr('data-tempo-exposicao'));
		} else {
			$('#b2make-wo-banners-tempo-exposicao').val('3000');
		}
		
		if($(obj).attr('data-titulo-topo')){
			$('#b2make-wo-banners-titulo-topo').val($(obj).attr('data-titulo-topo'));
		} else {
			$('#b2make-wo-banners-titulo-topo').val('290');
		}
		
		if($(obj).attr('data-titulo-margem')){
			$('#b2make-wo-banners-titulo-margem').val($(obj).attr('data-titulo-margem'));
		} else {
			$('#b2make-wo-banners-titulo-margem').val('20');
		}
		
		if($(obj).attr('data-titulo-padding')){
			$('#b2make-wo-banners-titulo-padding').val($(obj).attr('data-titulo-padding'));
		} else {
			$('#b2make-wo-banners-titulo-padding').val('10');
		}
		
		if($(obj).attr('data-seta-topo')){
			$('#b2make-wo-banners-seta-topo').val($(obj).attr('data-seta-topo'));
		} else {
			$('#b2make-wo-banners-seta-topo').val('150');
		}
		
		if($(obj).attr('data-seta-margem')){
			$('#b2make-wo-banners-seta-margem').val($(obj).attr('data-seta-margem'));
		} else {
			$('#b2make-wo-banners-seta-margem').val('20');
		}
		
		if($(obj).attr('data-seta-tamanho')){
			$('#b2make-wo-banners-seta-tamanho').val($(obj).attr('data-seta-tamanho'));
		} else {
			$('#b2make-wo-banners-seta-tamanho').val('30');
		}
		
		if($(obj).attr('data-seta-visivel')){
			var option = $('#b2make-wo-banners-seta-visivel').find("[value='" + $(obj).attr('data-seta-visivel') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-wo-banners-seta-visivel').find(":first");
			option.prop('selected', 'selected');
		}
		
		if($(obj).attr('data-widget-color-ahex')){
			$('#b2make-wo-banners-widget-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-widget-color-ahex')));
			$('#b2make-wo-banners-widget-cor-val').attr('data-ahex',$(obj).attr('data-widget-color-ahex'));
		} else {
			$('#b2make-wo-banners-widget-cor-val').css('background-color','rgba(255,255,255,0.4)');
			$('#b2make-wo-banners-widget-cor-val').attr('data-ahex','ffffff66');
		}
		
		if($(obj).attr('data-caixa-color-ahex')){
			$('#b2make-wo-banners-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex')));
			$('#b2make-wo-banners-caixa-cor-val').attr('data-ahex',$(obj).attr('data-caixa-color-ahex'));
		} else {
			$('#b2make-wo-banners-caixa-cor-val').css('background-color','transparent');
			$('#b2make-wo-banners-caixa-cor-val').attr('data-ahex',false);
		}
		
		if($(obj).attr('data-seta-color-ahex')){
			$('#b2make-wo-banners-seta-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-seta-color-ahex')));
			$('#b2make-wo-banners-seta-cor-val').attr('data-ahex',$(obj).attr('data-seta-color-ahex'));
		} else {
			$('#b2make-wo-banners-seta-cor-val').css('background-color','#ffffff');
			$('#b2make-wo-banners-seta-cor-val').attr('data-ahex','#ffffffff');
		}
		
		if($(obj).attr('data-titulo-color-ahex')){
			$('#b2make-wo-banners-titulo-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-titulo-color-ahex')));
			$('#b2make-wo-banners-titulo-cor-val').attr('data-ahex',$(obj).attr('data-titulo-color-ahex'));
		} else {
			$('#b2make-wo-banners-titulo-cor-val').css('background-color','#000000');
			$('#b2make-wo-banners-titulo-cor-val').attr('data-ahex','#000000ff');
		}
		
		if($(obj).attr('data-sub-titulo-color-ahex')){
			$('#b2make-wo-banners-sub-titulo-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-sub-titulo-color-ahex')));
			$('#b2make-wo-banners-sub-titulo-cor-val').attr('data-ahex',$(obj).attr('data-sub-titulo-color-ahex'));
		} else {
			$('#b2make-wo-banners-sub-titulo-cor-val').css('background-color','#000000');
			$('#b2make-wo-banners-sub-titulo-cor-val').attr('data-ahex','#000000ff');
		}
		
		var types = new Array('titulo','sub-titulo');
		
		for(var i=0;i<types.length;i++){
			var type = types[i];
			var tamanho;
			
			switch(type){
				case 'titulo': tamanho = 20; break;
				case 'sub-titulo': tamanho = 15; break;
			}
			
			if($(obj).attr('data-'+type+'-font-family')){
				$('#b2make-wo-banners-'+type+'-text-cont').find('.b2make-fonts-holder').css({
					'fontFamily': $(obj).attr('data-'+type+'-font-family')
				});
				$('#b2make-wo-banners-'+type+'-text-cont').find('.b2make-fonts-holder').html($(obj).attr('data-'+type+'-font-family'));
			} else {
				$('#b2make-wo-banners-'+type+'-text-cont').find('.b2make-fonts-holder').css({
					'fontFamily': 'Roboto Condensed'
				});
				$('#b2make-wo-banners-'+type+'-text-cont').find('.b2make-fonts-holder').html('Roboto Condensed');
			}
			
			if($(obj).attr('data-'+type+'-font-size')){
				$('#b2make-wo-banners-'+type+'-text-cont').find('.b2make-fonts-size').val($(obj).attr('data-'+type+'-font-size'));
			} else {
				$('#b2make-wo-banners-'+type+'-text-cont').find('.b2make-fonts-size').val(tamanho);
			}
		}
		
		if($(obj).attr('data-banners-id')){
			$('.b2make-banners-show').each(function(){
				if($(obj).attr('data-banners-id') == $(this).attr('data-banners-id')){
					$(this).attr('data-status','show');
				} else {
					$(this).attr('data-status','not-show');
				}
			});
			
			var id = $(obj).attr('data-banners-id');
			
			if(b2make.banners_todos_ids){
				var banners_ids =  b2make.banners_todos_ids;
				var found = false;
				
				for(var i=0;i<banners_ids.length;i++){
					if(banners_ids[i] == $(obj).attr('data-banners-id')){
						found = true;
						break;
					}
				}
				
				if(found){
					b2make.banners_atual = $(obj).attr('data-banners-id');
					b2make.banners_nome = $('.b2make-banners-nome[data-banners-id="'+id+'"]').html();
					
					$('.b2make-banners-nome').each(function(){
						$(this).attr('data-status','not-show');
					});
					
					$('.b2make-banners-nome[data-banners-id="'+id+'"]').attr('data-status','show');
					
					$('#b2make-banners-lista-images').html('');
					banners_images();
				}
			}
		} else {
			$('.b2make-galeria-imagens-show').each(function(){
				$(this).attr('data-status','not-show');
			});
		}
		
	});
	
	$('#b2make-'+plugin_id+'-callback').on('conteiner_child_open_finished',function(e){
		var obj = b2make.conteiner_child_obj;
		
	});
	
}

var fn = window[_plugin_id];fn();