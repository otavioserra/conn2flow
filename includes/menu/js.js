b2make_menu = {};

$(document).ready(function(){
	$(window).resize(function() {
		menu_resize();
		menu_gestor_resize();
		$.url_design();
	});
	
	function url_rules(href){
		if(!href)href = location.href;
		
		var site = document.location.protocol+'//'+location.hostname+variaveis_js.site_raiz;
		
		href = href.replace(site,'');
		
		var href_aux = href;
		var href_arr;
		
		if(href_aux.match(/\./gi)){href_arr = href_aux.split('.');href = href_arr[0];href_aux = href;}
		if(href_aux.match(/\#/gi)){href_arr = href_aux.split('#');href = href_arr[0];href_aux = href;}
		if(href_aux.match(/\?/gi)){href_arr = href_aux.split('?');href = href_arr[0];}
		
		if(href.match(/\/$/gi)){href = href.replace(/\/$/, "");}
		
		href = site+href;
		
		switch(href){
			case site+'design': b2make_menu.local = 'design'; break;
		}
	};
	
	function start(){
		b2make_menu.conteiner_top = 79;
		b2make_menu.back_animation_time = 200;
		b2make_menu.margin_conteiner = 20;
		b2make_menu.margin_area = -48;
		b2make_menu.height_start_conteiner = 21;
		b2make_menu.disk_usage_saparator = 'de';
		b2make_menu.plan = 'TRIAL';
		b2make_menu.width_conteiner = $('#b2make-menu-conteiner').width();
		
		b2make_menu.open = true;
		
		if(variaveis_js.reset_cache){
			localStorage.removeItem('b2make.menu-estado');
		}
		
		var estado = localStorage['b2make.menu-estado'];
		
		switch(estado){
			case 'up':
				b2make_menu.open = false;
				$('#b2make-menu-conteiner').css('height', b2make_menu.height_start_conteiner+'px');        
				$('#b2make-menu-anchor').css('background-color','#141414');
				$('#b2make-menu-anchor-title').removeClass('b2make-menu-anchor-title-up');
				$('#b2make-menu-anchor-title').addClass('b2make-menu-anchor-title-down');
			break;
			default:
				var top = parseInt(b2make_menu.conteiner_top);

				var height2 = $(window).height() - top + b2make_menu.margin_area;
				$('#b2make-menu-area').css('height', height2+'px');  
				$('#b2make-menu-area-transicao').css('height', height2+'px');  
				$('#b2make-menu-area-animation').css('height', (height2+30)+'px');  
				$('#b2make-menu-area-mask').css('height', height2+'px');  
		}
		
		url_rules();
	}
	
	start();
	
	$.url_design = function(){
		if(b2make_menu.open){
			if(b2make_menu.local == 'store' || b2make_menu.local == 'dashboard' || b2make_menu.nao_aplicar){
				$('#b2make-site').css('width',($(window).width() - b2make_menu.width_conteiner)+'px');
				$('#b2make-site').css('left',b2make_menu.width_conteiner+'px');
				$('#b2make-site').css('margin-left','0px');
				$('.in_busca_2').css('margin-left','0px')
				$('._menu_principal').css('margin-left','0px')
			} else {
				$('#b2make-ruler-left').css('left',b2make_menu.ruler_left);
				$('#b2make-ruler-corner').css('left',b2make_menu.ruler_left);
				$('#b2make-ruler-top').css('left',b2make_menu.ruler_left+b2make_menu.ruler_width);
				
				if(b2make_menu.multi_screen_device != 'phone'){
					$('#b2make-site').css('float','right');
					$('#b2make-site').css('width',($(window).width() - b2make_menu.width_conteiner)+'px');
					$('#b2make-site').css('margin-left',b2make_menu.width_conteiner+'px');
				} else {
					$('#b2make-site').css('left',Math.floor((parseInt($(window).outerWidth(true)) - b2make_menu.multi_screen_width) / 2));
				}
			}
		} else {
			if(b2make_menu.local == 'store' || b2make_menu.local == 'dashboard' || b2make_menu.nao_aplicar){
				$('#b2make-site').css('width',($(window).width())+'px');
				$('#b2make-site').css('left','0px');
				$('#b2make-site').css('margin-left','0px');
				$('.in_busca_2').css('margin-left',b2make_menu.width_conteiner+'px')
				$('._menu_principal').css('margin-left',b2make_menu.width_conteiner+'px')
			} else {
				if(b2make_menu.multi_screen_device != 'phone'){
					$('#b2make-site').css('float','none');
					$('#b2make-site').css('width',($(window).width())+'px');
					$('#b2make-site').css('margin-left','0px');
				} else {
					$('#b2make-site').css('left',Math.floor((parseInt($(window).outerWidth(true)) - b2make_menu.ruler_width - b2make_menu.multi_screen_width) / 2));
				}
			}
		}
		
		$('.b2make-widget').each(function(){
			var type = $(this).attr('data-type');
			
			if(type == 'conteiner'){
				$(this).width($('#b2make-site').width());
			}
		});
	}

	function menu_up(){
		$('#b2make-menu-conteiner').stop().animate({
			'height': b2make_menu.height_start_conteiner+'px',          
		}, b2make_menu.back_animation_time, 'linear', function(){
			$('#b2make-menu-anchor').css('background-color','#141414');
			$('#b2make-menu-anchor-title').removeClass('b2make-menu-anchor-title-up');
			$('#b2make-menu-anchor-title').addClass('b2make-menu-anchor-title-down');
			
			$.url_design();
			
			localStorage['b2make.menu-estado'] = 'up';
		});
	}
	
	function menu_down(){
		var top = parseInt(b2make_menu.conteiner_top);
		var height = $(window).height() - top + b2make_menu.margin_conteiner;
		
		$('#b2make-menu-conteiner').stop().animate({
			'height': height+'px',          
		}, b2make_menu.back_animation_time, 'linear', function(){
			$('#b2make-menu-anchor').css('background-color','#EEEEEE');
			$('#b2make-menu-anchor-title').removeClass('b2make-menu-anchor-title-down');
			$('#b2make-menu-anchor-title').addClass('b2make-menu-anchor-title-up');
			
			$.url_design();
			
			localStorage['b2make.menu-estado'] = 'down';
		});
	}
	
	function menu_resize(){
		if(b2make_menu.open){
			var top = parseInt(b2make_menu.conteiner_top);
			var height = $(window).height() - top + b2make_menu.margin_conteiner;
			var height2 = $(window).height() - top + b2make_menu.margin_area;
			
			$('#b2make-menu-conteiner').height(height);
			$('#b2make-menu-area').css('height', height2+'px');
			$('#b2make-menu-area-mask').css('height', height2+'px');
		}
	}

	function menu(){
		$('.b2make-menu-nav li').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var pai = $(this).parent().parent();
			var local = $(this);
			var pai_id = pai.attr('data-id');
			var local_id = local.attr('data-id');
			
			if(local_id){
				if(pai_id == b2make_menu.local){
					switch(pai_id){
						case 'design':
							switch(local_id){
								case 'template':
									localStorage['b2make.menu-local-clicked'] = local_id;
									window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'management/templates','_self');
								break;
								case 'config':
									$('#b2make-page-options-holder').trigger('mouseup');
								break;
								default:
									$.widget_add({
										type : local_id
									});
							}
						break;
					}
				}
			}
		});
	
		$('#b2make-menu-anchor-title').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if(b2make_menu.open){
				b2make_menu.open = false;
				menu_up();
			} else {
				b2make_menu.open = true;
				menu_down();
			}
			
			$('#b2make-listener').trigger('b2make-menu-change');
			menu_resize();
		});

		var menu_id = (localStorage.getItem('b2make.menu_opcao_atual') ? localStorage.getItem('b2make.menu_opcao_atual') : 'design');
		
		$('.b2make-menu-local[data-id="'+menu_id+'"]').show();
		$('.b2make-menu-opcao[data-id="'+menu_id+'"]').addClass('b2make-menu-opcao-selected');
		
		$('.b2make-menu-opcao').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.b2make-menu-local').hide();
			$('.b2make-menu-opcao').removeClass('b2make-menu-opcao-selected');
			$('.b2make-menu-local[data-id="'+$(this).attr('data-id')+'"]').show();
			$('.b2make-menu-opcao[data-id="'+$(this).attr('data-id')+'"]').addClass('b2make-menu-opcao-selected');
			
			localStorage['b2make.menu_opcao_atual'] = $(this).attr('data-id');
		});
		
		menu_resize();
	}
	
	menu();
	
	function menu_gestor_resize(){
		if($('#b2make-gestor-menu-top').length > 0){
			var menu_top_height = $('#b2make-gestor-menu-top').height();
			var menu_top_top = $('#b2make-gestor-menu-top').position().top;
			
			$('#b2make-gestor-menu-dados').css('max-height',menu_top_height-menu_top_top);
		}
	}
	
	function menu_gestor(){
		menu_gestor_resize();
		
		var line_height = 40;
		
		$('.b2make-gestor-menu-pai').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if($(this).find('.b2make-gestor-menu-pai-filhos-cont').find('.b2make-gestor-menu-filho').length > 0){
				var pai_cont = $(this);
				var filhos_cont = pai_cont.find('.b2make-gestor-menu-pai-filhos-cont');
				var filhos = filhos_cont.find('.b2make-gestor-menu-filho');
				
				if(typeof pai_cont.attr('data-open') !== typeof undefined && pai_cont.attr('data-open') !== false){
					// Descomente abaixo caso queira que abra / feche o menu de filhos clicando no pai.
					
					/* filhos_cont.stop().animate({height:0,marginTop:0});
					pai_cont.stop().animate({"height":line_height});
					
					pai_cont.removeAttr('data-open'); */
				} else {
					$('.b2make-gestor-menu-pai[data-open="true"]').each(function(){
						var pai_cont_2 = $(this);
						var filhos_cont_2 = pai_cont_2.find('.b2make-gestor-menu-pai-filhos-cont');
						
						filhos_cont_2.stop().animate({height:0,marginTop:0});
						pai_cont_2.stop().animate({"height":line_height});
						
						pai_cont_2.removeAttr('data-open');
					});
					
					var height = filhos.first().height() * filhos.length;
					
					filhos_cont.stop().animate({height:height,marginTop:line_height});
					pai_cont.css('height',line_height);
					pai_cont.stop().animate({"height":(height+line_height)});
					
					pai_cont.attr('data-open','true');
				}
			} else {
				var url = variaveis_js.site_raiz + $(this).attr('data-url');
				var target = '_self';
				
				if($(this).attr('data-id') == 'design'){
					target = 'b2make-design';
				}
				
				window.open(url,target);
			}
		});
		
		$('.b2make-gestor-menu-filho').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			
			var url = variaveis_js.site_raiz + $(this).attr('data-url');
			var target = '_self';
			
			window.open(url,target);
		});
	}
	
	menu_gestor();

	function disk_usage_change_gestor(){
		var disklimit = parseInt(b2make_menu.disk_usage.disklimit.replace(/M/gi,''));
		var diskused = parseInt(b2make_menu.disk_usage.diskused.replace(/M/gi,''));
		var disk_perc = (disklimit != 0 ? diskused / disklimit : 0);
		
		$('#b2make-gestor-disk-usage-percent').html((Math.floor(disk_perc*100))+'%');
		
		if(disk_perc >= b2make_menu.disk_usage.warning_start){
			console.log('ss');
			$('#b2make-gestor-disk-usage-slide').addClass('b2make-gestor-disk-usage-warning');
			$('#b2make-gestor-disk-usage-slide').removeClass('b2make-gestor-disk-usage-normal');
		} else {
			$('#b2make-gestor-disk-usage-slide').removeClass('b2make-gestor-disk-usage-warning');
			$('#b2make-gestor-disk-usage-slide').addClass('b2make-gestor-disk-usage-normal');
		}
		
		$('#b2make-gestor-disk-usage-slide').css('width',(Math.floor(disk_perc*100))+'%');
	}
	
	function disk_usage(){
		b2make_menu.disk_usage = {};
		
		b2make_menu.disk_usage.warning_start = 0.8;
		b2make_menu.disk_usage.disklimit = (variaveis_js.disklimit ? variaveis_js.disklimit : '0M');
		b2make_menu.disk_usage.diskused = (variaveis_js.diskused ? variaveis_js.diskused : '0M');
		b2make_menu.disk_usage.plan = (variaveis_js.plan ? variaveis_js.plan : b2make_menu.plan);
		b2make_menu.disk_usage.user = (variaveis_js.user_cpanel ? variaveis_js.user_cpanel : b2make_menu.user_cpanel);
		
		disk_usage_change_gestor();
	}
	
	disk_usage();
	
	function finish(){
		localStorage['b2make.menu-local-anterior'] = b2make_menu.local;
		$.url_design();
	}
	
	finish();
});