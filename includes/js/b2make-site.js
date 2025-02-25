var getUrlParameter = function getUrlParameter(sParam) {
	var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		sURLVariables = sPageURL.split('&'),
		sParameterName,
		i;

	for (i = 0; i < sURLVariables.length; i++) {
		sParameterName = sURLVariables[i].split('=');

		if (sParameterName[0] === sParam) {
			return sParameterName[1] === undefined ? true : sParameterName[1];
		}
	}
};

if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	if(!getUrlParameter('force_http')){
		window.location = "https:" + restOfUrl;
	}
}

var b2make = {};
if(!b2make.msgs)b2make.msgs = {};

b2make.url = "platform.b2make.com";
b2make.urlBeta = "beta.b2make.com";

b2make.screen_width = window.screen.width;
b2make.mobile = {};
b2make.mobile.width = 600;

if(!b2make.plataform_not_start){
	jQuery.fn.extend({
		hasAttr: function(name){
			var attr = $(this).attr(name);

			if(typeof attr !== typeof undefined && attr !== false) {
				return true;
			} else {
				return false;
			}
		},
		myAttr: function(name,value = false){
			if(typeof value !== typeof undefined && value !== false){
				$(this).attr(name,value);
			} else {
				var attr = $(this).attr(name);

				if(typeof attr !== typeof undefined && attr !== false) {
					return attr;
				} else {
					return false;
				}
			}
		}
	});

	function b2make_site_version(){
		b2make.hostname = '//'+location.hostname+'/';
		
		var opcao = 'b2make_site_version';
		
		$.ajax({
			type: 'POST',
			url: '/platform/site-version',
			data: { 
				ajax : 'sim'
			},
			beforeSend: function(){
			},
			success: function(version){
				if(version && version != 'ERROR'){
					b2make.site_version = version;
				} else {
					//console.log('ERROR - '+opcao+' - '+version);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
			}
		});
	}

	b2make_site_version();

	function ler_scripts(scripts){
		if(scripts){
			for(var i=0;i<scripts.length;i++){
				if(!b2make.scripts_loaded[scripts[i]]){
					$.getScript(scripts[i]);
					b2make.scripts_loaded[scripts[i]] = true;
				}
			}
		}
	}

	function ler_css(stylesheets){
		if(stylesheets){
			for(var i=0;i<stylesheets.length;i++){
				if(!b2make.stylesheets_loaded[stylesheets[i]]){
					$('<link rel="stylesheet" type="text/css" href="'+stylesheets[i]+'" >').appendTo("head");
					b2make.stylesheets_loaded[stylesheets[i]] = true;
				}
			}
		}
	}

	b2make.scripts = new Array();
	b2make.stylesheets = new Array();
	b2make.scripts_loaded = new Array();
	b2make.stylesheets_loaded = new Array();

	b2make.stylesheets.push('https://'+b2make.url+'/design/jpicker/css/jPicker-1.1.6.css');
	b2make.stylesheets.push('https://'+b2make.url+'/design/jpicker/jPicker.css');

	b2make.scripts.push('https://'+b2make.url+'/design/jpicker/jpicker-1.1.6.js?v=0.9.1');

	ler_scripts(b2make.scripts);
	ler_css(b2make.stylesheets);

	$(document).ready(function(){
		function debug_box(p){
			if(!p) p = {};
			
			
			if(!b2make.debug_box){
				b2make.debug_box = {};
			}
			
			b2make.debug_box.title = '<b style="font-size:20px;">Debug</b><br><br>';
			
			if(!b2make.debug_box.cont){
				b2make.debug_box.cont = $('<div id="b2make-debug-box"></div>');
				
				b2make.debug_box.cont.css({
					'zIndex' : '99999999',
					'padding' : '10px',
					'position' : 'absolute',
					'top' : '20px',
					'left' : '20px',
					'width' : '400px',
					'height' : '300px',
					'overflow-y' : 'auto', 
					'border' : '3px #CCC solid', 
					'background-color' : '#FFF'
				});
				
				b2make.debug_box.cont.appendTo('body');
			}
			
			b2make.debug_box.cont.show();
			
			b2make.debug_box.cont.html(b2make.debug_box.title+p.msg);
		}
		
		// ==================================== Mobile =============================
		
		function getParameterByName(name, url) {
			if (!url) url = window.location.href;
			name = name.replace(/[\[\]]/g, "\\$&");
			var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
				results = regex.exec(url);
			if (!results) return null;
			if (!results[2]) return '';
			return decodeURIComponent(results[2].replace(/\+/g, " "));
		}

		function extractHostname(url) {
			var hostname;
			//find & remove protocol (http, ftp, etc.) and get hostname

			if (url.indexOf("://") > -1) {
				hostname = url.split('/')[2];
			}
			else {
				hostname = url.split('/')[0];
			}

			//find & remove port number
			hostname = hostname.split(':')[0];
			//find & remove "?"
			hostname = hostname.split('?')[0];

			return hostname;
		}
		
		function mobile_sitemap_start(p){
			b2make.mobile_sitemap = p.json.sites;
			mobile_sitemap(p.params);
		}
		
		function mobile_sitemap(p){
			if(!p) p = {};
			
			if(b2make.device != 'phone') return false;
			
			if(typeof b2make.mobile_sitemap !== typeof undefined && b2make.mobile_sitemap !== false){
				var opcao = 'mobile_sitemap';
				
				$.ajax({
					type: 'POST',
					url: '/platform/library/sitemaps',
					data: { 
						ajax : 'sim'
					},
					beforeSend: function(){
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var json = eval('(' + txt + ')');
							
							if(json){
								mobile_sitemap_start({json:json,params:p});
							} else {
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
			} else {
				if(!p.obj) return false;
				
				var sitemap = b2make.mobile_sitemap;
				var protomatch = /^(https?|ftp):\/\//;
				var obj = p.obj;
				
				switch($(obj).myAttr('data-type')){
					case 'texto':
					case 'imagem':
						if($(obj).myAttr('data-hiperlink')){
							var url = $(obj).myAttr('data-hiperlink');
							
							url = url.replace(protomatch, '');
							
							if(sitemap)
							for(var i=0;i<sitemap.length;i++){
								var url_sitemap = sitemap[i].url.replace(protomatch, '');
								
								if(url_sitemap == url){
									if(sitemap[i].url_mobile){
										$(obj).myAttr('data-hiperlink',sitemap[i].url_mobile);
										break;
									}
								}
							}
						}
					break;
					case 'services':
						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').each(function(){
							var url = $(this).find('.b2make-service-comprar').myAttr('href');
							
							url = url.replace(protomatch, '');
							
							if(sitemap)
							for(var i=0;i<sitemap.length;i++){
								var url_sitemap = sitemap[i].url.replace(protomatch, '');
								
								if(url_sitemap == url){
									if(sitemap[i].url_mobile){
										$(this).find('.b2make-service-comprar').myAttr('href',sitemap[i].url_mobile);
										break;
									}
								}
							}
						});
					break;
					case 'contents':
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').each(function(){
							var url = $(this).myAttr('data-href');
							var href = $(this).myAttr('href');
							
							url = url.replace(protomatch, '');
							
							if(sitemap)
							for(var i=0;i<sitemap.length;i++){
								var url_sitemap = sitemap[i].url.replace(protomatch, '');
								
								if(url_sitemap == url){
									if(sitemap[i].url_mobile){
										$(this).find('.b2make-content-acessar').myAttr('href',sitemap[i].url_mobile);
										if(href) $(this).myAttr('href',sitemap[i].url_mobile);
										break;
									}
								}
							}
						});
						
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').each(function(){
							var url = $(this).myAttr('data-href');
							var href = $(this).myAttr('href');
							
							url = url.replace(protomatch, '');
							
							if(sitemap)
							for(var i=0;i<sitemap.length;i++){
								var url_sitemap = sitemap[i].url.replace(protomatch, '');
								
								if(url_sitemap == url){
									if(sitemap[i].url_mobile){
										$(this).find('.b2make-content-acessar').myAttr('href',sitemap[i].url_mobile);
										$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').myAttr('href',sitemap[i].url_mobile);
										if(href) $(this).myAttr('href',sitemap[i].url_mobile);
										break;
									}
								}
							}
						});
						
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').each(function(){
							var url = $(this).myAttr('data-href');
							var href = $(this).myAttr('href');
							
							url = url.replace(protomatch, '');
							
							if(sitemap)
							for(var i=0;i<sitemap.length;i++){
								var url_sitemap = sitemap[i].url.replace(protomatch, '');
								
								if(url_sitemap == url){
									if(sitemap[i].url_mobile){
										$(this).find('.b2make-content-acessar').myAttr('href',sitemap[i].url_mobile);
										if(href) $(this).myAttr('href',sitemap[i].url_mobile);
										break;
									}
								}
							}
						});
						
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-4').each(function(){
							var href = $(this).myAttr('href');
							var url = href;
							
							url = url.replace(protomatch, '');
							
							if(sitemap)
							for(var i=0;i<sitemap.length;i++){
								var url_sitemap = sitemap[i].url.replace(protomatch, '');
								
								if(url_sitemap == url){
									if(sitemap[i].url_mobile){
										$(this).find('.b2make-content-acessar').myAttr('href',sitemap[i].url_mobile);
										if(href) $(this).myAttr('href',sitemap[i].url_mobile);
										break;
									}
								}
							}
						});
						
					break;
					
				}
			}
		}
		
		function mobile_start(json){
			var sites = json.sites;
			
			var url_page = window.location.href;
			var protomatch = /^(https?|ftp):\/\//;
			
			url_page = url_page.replace(protomatch, '');
			
			if(sites)
			for(var i=0;i<sites.length;i++){
				var url_sitemap = sites[i].url.replace(protomatch, '');
				url_sitemap = url_sitemap.replace(/^\/\//, '');
				
				if(url_sitemap == url_page || 'www.'+url_sitemap == url_page){
					if(sites[i].url_mobile){
						window.open(sites[i].url_mobile,'_self');
					}
				}
			}
		}
		
		function mobile(){
			if(!b2make.msgs.mobileLinkDesktop)b2make.msgs.mobileLinkDesktop = 'Acessar vers&atilde;o desktop';
			if(!b2make.msgs.mobileLinkMobile)b2make.msgs.mobileLinkMobile = 'Acessar vers&atilde;o mobile';
			
			b2make.device = ($('#b2make-pagina-options').myAttr('data-device') ? $('#b2make-pagina-options').myAttr('data-device') : 'desktop');
			
			if(b2make.device == 'phone'){
				b2make.desktop_domain = extractHostname($('link[rel="canonical"]').myAttr('href'));
				b2make.mobile_domain = extractHostname(location.hostname.toLowerCase());
			}
			
			var hostname = location.hostname.toLowerCase();
			
			if(!b2make.hostname){
				if(b2make.device == 'phone'){
					b2make.hostname = '//'+b2make.desktop_domain+'/';
				} else {
					b2make.hostname = '//'+extractHostname(hostname)+'/';
				}
			}
			
			if(b2make.screen_width <= b2make.mobile.width || b2make.device == 'phone'){
				b2make.mobile.active = true;
			} else {
				b2make.mobile.active = false;
			}
			
			if(getParameterByName('b2make-version-desktop')){
				localStorage.setItem('b2make.versao_desktop',true);
			}
			
			if(getParameterByName('b2make-version-mobile')){
				localStorage.setItem('b2make.versao_mobile',true);
			}
			
			if(b2make.mobile.active && !localStorage.getItem('b2make.versao_desktop') && !localStorage.getItem('b2make.versao_mobile')){
				var opcao = 'mobile';
				
				$.ajax({
					type: 'POST',
					url: '/platform/library/sitemaps',
					data: { 
						ajax : 'sim'
					},
					beforeSend: function(){
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var json = eval('(' + txt + ')');
							
							if(json){
								mobile_start(json);
							} else {
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
			
			$(document.body).on('mouseup tap','.b2make-mobile-link',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).myAttr('id');
				
				switch(id){
					case 'b2make-mobile-link-desktop':			
						var url = $('link[rel="canonical"]').myAttr('href');
						localStorage.removeItem('b2make.versao_mobile');
						window.open(url+'?b2make-version-desktop=true','_self');
					break;
					case 'b2make-mobile-link-mobile':
						var url = $('link[rel="alternate"]').myAttr('href');
						localStorage.removeItem('b2make.versao_desktop');
						window.open(url+'?b2make-version-mobile=true','_self');
					break;
				}
			});
		}
		
		// ==================================== DialogBox =============================
		
		$(window).resize(function() {
			if($('#b2make-widget-menu-holder').length > 0){
				if(widget_menu_holder){
					var widget_menu_holder = $('#b2make-widget-menu-holder');
					
					var widget_menu = widget_menu_holder.myAttr('data-menu-atual');
					
					widget_menu_holder.css('top',$('#'+widget_menu).offset().top + 'px');
					widget_menu_holder.css('left',$('#'+widget_menu).offset().left + 'px');
				}
			}
			
			images_conteiners_update();
			dialogbox_position();
		});
		
		$(window).bind('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.menu_holder){
				if(b2make.menu_holder.myAttr('data-open') == '1'){
					b2make.menu_holder.myAttr('data-open','0');
					b2make.menu_holder.hide();
				}
			}
			
			if(b2make.menu_paginas.open){
				if(!$(e.target).hasClass('b2make-menu-paginas')){
					menu_paginas_close_all();
				}
			}
		});
		
		$.dialogbox_open = function(p){
			if(!b2make.dialogbox_cont){
				
				b2make.dialogbox_cont = $('<div id="b2make-dialogbox"><div id="b2make-dialogbox-head"></div><div id="b2make-dialogbox-msg"></div><div id="b2make-dialogbox-btns"></div></div>');
				b2make.dialogbox_cont.appendTo('body');
			}
			
			if(!b2make.dialogbox){
				if(!p)p = {};
				b2make.dialogbox = true;
				
				if(!b2make.dialbox_default_width)b2make.dialbox_default_width = $("#b2make-dialogbox").width();
				if(!b2make.dialbox_default_height)b2make.dialbox_default_height = $("#b2make-dialogbox").height();
				
				if(!p.width)if(b2make.dialbox_default_width != $("#b2make-dialogbox").width())$("#b2make-dialogbox").width(b2make.dialbox_default_width);
				if(!p.height)if(b2make.dialbox_default_height != $("#b2make-dialogbox").height())$("#b2make-dialogbox").height(b2make.dialbox_default_height);
				
				if(p.width)$("#b2make-dialogbox").width(p.width);
				if(p.height)$("#b2make-dialogbox").height(p.height);
				
				$("#b2make-dialogbox-head").html((p.title?p.title:(p.confirm?b2make.msgs.confirmTitle:b2make.msgs.alertTitle)));
				if(!p.coneiner)$("#b2make-dialogbox-msg").html((p.msg?p.msg:(p.confirm?b2make.msgs.confirmMsg:b2make.msgs.alertMsg)));
				$("#b2make-dialogbox-btns").html('');
				
				if(p.coneiner){
					$("#b2make-dialogbox-msg").html('');
					$("#b2make-dialogbox-msg").append($('#'+p.coneiner));
					b2make.dialogbox_conteiner = p.coneiner;
				}
				
				if(!p.no_btn_default){
					if(p.message){
						$('<div class="b2make-dialogbox-btn b2make-dialogbox-btn-click-dont-close'+(p.calback_yes?' '+p.calback_yes:'')+'"'+(p.calback_yes_extra?' '+p.calback_yes_extra:'')+'>'+(p.message_btn_yes_title ? p.message_btn_yes_title : b2make.msgs.messageBtnYes)+'</div>').appendTo("#b2make-dialogbox-btns");
						
						if(p.more_buttons){
							var btns = p.more_buttons;
							
							for(var i=0;i<btns.length;i++){
								$('<div class="b2make-dialogbox-btn'+(btns[i].calback?' '+btns[i].calback:'')+'"'+(btns[i].calback_extra?' '+btns[i].calback_extra:'')+'>'+btns[i].title+'</div>').appendTo("#b2make-dialogbox-btns");
							}
						}
						
						$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.message_btn_no_title ? p.message_btn_no_title : b2make.msgs.messageBtnNo)+'</div>').appendTo("#b2make-dialogbox-btns");
					} else if(p.confirm){
						$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.confirm_btn_no_title ? p.confirm_btn_no_title : b2make.msgs.confirmBtnNo)+'</div>').appendTo("#b2make-dialogbox-btns");
						$('<div class="b2make-dialogbox-btn'+(p.calback_yes?' '+p.calback_yes:'')+'"'+(p.calback_yes_extra?' '+p.calback_yes_extra:'')+'>'+(p.confirm_btn_yes_title ? p.confirm_btn_yes_title : b2make.msgs.confirmBtnYes)+'</div>').appendTo("#b2make-dialogbox-btns");
					} else {
						$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_alert:'')+'"'+(p.calback_alert_extra?' '+p.calback_alert_extra:'')+'>'+(p.alert_btn_title ? p.alert_btn_title : b2make.msgs.alertBtn)+'</div>').appendTo("#b2make-dialogbox-btns");
					}
				}
				
				b2make.dialogbox_callback_yes = p.calback_yes;
				
				var top_start = -10 - $("#b2make-dialogbox").height();
				var top_stop = $(window).height()/2 - $("#b2make-dialogbox").height()/2;
				var left = $(window).width()/2 - $("#b2make-dialogbox").width()/2;
				
				$("#b2make-dialogbox").css('top',top_start);
				$("#b2make-dialogbox").css('left',left);
				$("#b2make-dialogbox").show();
				
				$("#b2make-dialogbox").animate({top:top_stop}, b2make.dialogboxAnimateTime, function(){
					if(p.coneiner){
						$('#'+p.coneiner).find('input').filter(':visible:first').focus();
						$('#'+p.coneiner).find('input').filter(':visible:first').tooltip( "close" );
					}
				});
			}
		}
		
		function dialogbox_shake(){
			$("#b2make-dialogbox").stop().effect( "shake" );
		}
		
		function dialogbox_open_after(p){
			setTimeout(function(){
				$.dialogbox_open(p);
			},b2make.dialogboxAnimateTime);
		}
		
		function dialogbox_close(){
			if(b2make.dialogbox){
				b2make.dialogbox = false;
				
				var top_stop = -10 - $("#b2make-dialogbox").height();
				
				$("#b2make-dialogbox").animate({top:top_stop}, b2make.dialogboxAnimateTime, "swing", function(){
					if(b2make.dialogbox_conteiner){
						formulario_resetar(b2make.dialogbox_conteiner);
						$('#'+b2make.dialogbox_conteiner).appendTo($('#b2make-formularios'));
						b2make.dialogbox_conteiner = false;
					}
				});
			}
		}
		
		function dialogbox_position(){
			if(b2make.dialogbox){
				$("#b2make-dialogbox").stop();
				var top = $(window).height()/2 - $("#b2make-dialogbox").height()/2;
				var left = $(window).width()/2 - $("#b2make-dialogbox").width()/2;
				
				$("#b2make-dialogbox").css('top',top);
				$("#b2make-dialogbox").css('left',left);
			} else {
				var top =  -10 - $("#b2make-dialogbox").height();;
				var left = $(window).width()/2 - $("#b2make-dialogbox").width()/2;
				
				$("#b2make-dialogbox").css('top',top);
				$("#b2make-dialogbox").css('left',left);
			}
		}
		
		function dialogbox(){
			b2make.dialogbox = false;
			if(!b2make.dialogboxAnimateTime)b2make.dialogboxAnimateTime = 250;
			if(!b2make.msgs.alertTitle)b2make.msgs.alertTitle = "Alerta";
			if(!b2make.msgs.confirmTitle)b2make.msgs.confirmTitle = "Confirma&ccedil;&atilde;o";
			if(!b2make.msgs.alertMsg)b2make.msgs.alertMsg = "Esta op&ccedil;&atilde;o n&atilde;o est&aacute; ativada";
			if(!b2make.msgs.alertBtn)b2make.msgs.alertBtn = "Ok";
			if(!b2make.msgs.confirmMsg)b2make.msgs.confirmMsg = "Tem certeza que deseja proseguir?";
			if(!b2make.msgs.confirmBtnYes)b2make.msgs.confirmBtnYes = "Sim";
			if(!b2make.msgs.confirmBtnNo)b2make.msgs.confirmBtnNo = "N&atilde;o";
			if(!b2make.msgs.messageBtnNo)b2make.msgs.messageBtnNo = "Cancelar";
			if(!b2make.msgs.messageBtnYes)b2make.msgs.messageBtnYes = "Enviar";
			
			$(".b2make-dialogbox-btn").live('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				if(!$(this).hasClass('b2make-dialogbox-btn-click-dont-close'))dialogbox_close();
			});
		}
		
		// ==================================== Start =============================
		
		function images_conteiners_update(){
			var ww = parseInt($(window).width());
			
			$('.b2make-widget').each(function(){
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'conteiner':
								var iw = $(this).myAttr('data-image-width');
								var ih = $(this).myAttr('data-image-height');
								var position_y = ($(this).myAttr('data-background-position-y') ? $(this).myAttr('data-background-position-y') : 'top');
								var position_x = ($(this).myAttr('data-background-position-x') ? $(this).myAttr('data-background-position-x') : 'left');
								var repeat = ($(this).myAttr('data-background-repeat') ? $(this).myAttr('data-background-repeat') : 'completar');
								var ch = $(this).height();
								
								if(
									position_y != 'center' &&
									position_y != 'top' &&
									position_y != 'bottom' 
								)
									position_y = position_y + 'px';
								
								if(
									position_x != 'center' &&
									position_x != 'left' &&
									position_x != 'right' 
								)
									position_x = position_x + 'px';
								
								
								if(iw && ih){
									iw = parseInt(iw);
									ih = parseInt(ih);
									ch = parseInt(ch);
									
									if(repeat == 'completar'){
										if(iw < ih){
											if(position_x != 'left')position_x = 'left';
										}
										
										var nw = Math.floor((iw * ch) / ih);
										var dw = (nw - ww) / 2;
										
										if(nw >= ww){
											$(this).css('background-size',nw+'px '+ch+'px');
											$(this).css('background-position','-'+dw+'px '+position_y);
										} else {
											var nh = Math.floor((ww * ih) / iw);
											$(this).css('background-size',ww+'px '+nh+'px');
											$(this).css('background-position',position_x+' '+position_y);
										}
									} else {
										if(ww < iw){
											if(position_x == 'center'){
												var dw = (iw - ww) / 2;
												$(this).css('background-position','-'+dw+'px '+position_y);
											}
										} else {
											$(this).css('background-position',position_x+' '+position_y);
										}
									}
								}	
						break;
						
					}
				}
			});
		}
		
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
		
		function player_widget_controls(obj,type){
			var album = false;
			
			switch(type){
				case 'player':
					var obj_id = $(obj).myAttr('id');
					var player_id = '#b2make-jplayer-'+obj_id;
					var player_control = '#b2make-player-control-'+obj_id+' ';
				break;
				case 'albummusicas':
					
					var obj_id = $(obj).parent().parent().parent().myAttr('id');
					var id_album = $(obj).myAttr('data-album-musicas-id');
					var player_id = '#b2make-jplayer-player-'+obj_id+'-'+id_album;
					var player_control = '#b2make-player-control-'+obj_id+'-'+id_album+' ';
					
					album = true;
				break;
				
			}
			
			if(!b2make.player){
				b2make.player = new Array();
			}
			
			if(!$(obj).myAttr('data-music-list')){
				return;
			}
			
			b2make.player[player_id] = {};
			
			b2make.player[player_id].lista_musicas_str = $(obj).myAttr('data-music-list');
			b2make.player[player_id].lista_musicas = new Array();
			b2make.player[player_id].lista_musicas_tit = new Array();
			b2make.player[player_id].total_musicas = 0;
			b2make.player[player_id].num_musica = 0;
			b2make.player[player_id].mudou_musicas = false;
			b2make.player[player_id].auto_play = ($(obj).myAttr('data-start-automatico') ? true : false);
			b2make.player[player_id].player_pause = true;
			if(album)b2make.player[player_id].album = true;
			
			var lm_aux = b2make.player[player_id].lista_musicas_str.split('<;>');
			var i;
			var aux;
			
			for(i=0;i<lm_aux.length;i++){
				if(lm_aux[i]){
					aux = lm_aux[i].split('<,>');
					b2make.player[player_id].lista_musicas_tit[i] = aux[0];
					b2make.player[player_id].lista_musicas[i] = aux[1];
					b2make.player[player_id].total_musicas++;
				}
			}
			
			$(player_id).jPlayer( {
				swfPath: "jplayer",
				ready: function () {
					$(player_id).jPlayer("setMedia", {
						mp3: b2make.player[player_id].lista_musicas[0] // Defines the mp3 url
					});
					
					if(b2make.player[player_id].auto_play){
						b2make.player_playing = player_id;
						$(player_id).jPlayer("play");
						b2make.player[player_id].player_pause = false;
					}
					
					$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
					$(player_control+".b2make-player-time").text($.jPlayer.convertTime(0));
					if(b2make.player[player_id].auto_play)$(player_control+".b2make-player-play").addClass("b2make-player-pause_css");
					
					var pai = $(player_id).parent().parent().parent().parent();
					var color_playing_start = (pai.myAttr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
					var color_not_start = (pai.myAttr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
					
					$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function(){
						var musica_num = parseInt($(this).myAttr('data-musica-num'));
						
						if(musica_num == b2make.player[player_id].num_musica){
							$(this).addClass('b2make-albummusicas-widget-playing');
							$(this).css('color',color_playing_start);
						} else {
							$(this).removeClass('b2make-albummusicas-widget-playing');
							$(this).css('color',color_not_start);
						}
					});
					
					$(player_control+".b2make-player-prev").on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						if(b2make.player_playing)if(b2make.player_playing != player_id){
							$(b2make.player_playing).jPlayer("pause");
							b2make.player[b2make.player_playing].player_pause = true;
							$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
						}
						b2make.player_playing = player_id;
						
						$(player_id).jPlayer("stop");
						b2make.player[player_id].num_musica--;
						
						if(b2make.player[player_id].num_musica < 0){
							b2make.player[player_id].num_musica = b2make.player[player_id].total_musicas - 1;
						}
						
						$(player_id).jPlayer("setMedia", {
							mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
						});
						$(player_id).jPlayer("play");
						b2make.player[player_id].player_pause = false;
						$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
						$(player_control+".b2make-player-play").addClass("b2make-player-pause_css");
						
						if(b2make.player[player_id].album){
							var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
							var color_not = (pai.myAttr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
							
							$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function(){
								var musica_num = parseInt($(this).myAttr('data-musica-num'));
								
								if(musica_num == b2make.player[player_id].num_musica){
									$(this).addClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_playing);
								} else {
									$(this).removeClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_not);
								}
							});
						}
					});
					
					$(player_control+".b2make-player-play").on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						if(b2make.player_playing)if(b2make.player_playing != player_id){
							$(b2make.player_playing).jPlayer("pause");
							b2make.player[b2make.player_playing].player_pause = true;
							$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
						}
						b2make.player_playing = player_id;
						
						if(b2make.player[player_id].player_pause){
							$(player_id).jPlayer("play");
							b2make.player[player_id].player_pause = false;
							$(player_control+".b2make-player-play").addClass("b2make-player-pause_css");
						} else {
							$(player_id).jPlayer("pause");
							b2make.player[player_id].player_pause = true;
							$(player_control+".b2make-player-play").removeClass("b2make-player-pause_css");
						}
					});
					
					$(player_control+".b2make-player-stop").on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						$(player_id).jPlayer("stop");
						b2make.player[player_id].player_pause = true;
						$(player_control+".b2make-player-play").addClass("b2make-player-pause_css");
						b2make.player_playing = player_id;
					});
					
					$(player_control+".b2make-player-next").on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						if(b2make.player_playing)if(b2make.player_playing != player_id){
							$(b2make.player_playing).jPlayer("pause");
							b2make.player[b2make.player_playing].player_pause = true;
							$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
						}
						b2make.player_playing = player_id;
						
						$(player_id).jPlayer("stop");
						b2make.player[player_id].num_musica++;
						
						if(b2make.player[player_id].num_musica >= b2make.player[player_id].total_musicas){
							b2make.player[player_id].num_musica = 0;
						}
						
						$(player_id).jPlayer("setMedia", {
							mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
						});
						$(player_id).jPlayer("play");
						b2make.player[player_id].player_pause = false;
						$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
						$(player_control+".b2make-player-play").addClass("b2make-player-pause_css");
						
						if(b2make.player[player_id].album){
							var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
							var color_not = (pai.myAttr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
							
							$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function(){
								var musica_num = parseInt($(this).myAttr('data-musica-num'));
								
								if(musica_num == b2make.player[player_id].num_musica){
									$(this).addClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_playing);
								} else {
									$(this).removeClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_not);
								}
							});
						}
					});
					
					$(player_id).bind($.jPlayer.event.ended + ".jp-repeat", function(event) { // Using ".jp-repeat" namespace so we can easily remove this event
						b2make.player[player_id].num_musica++;
						
						if(b2make.player[player_id].num_musica >= b2make.player[player_id].total_musicas){
							b2make.player[player_id].num_musica = 0;
						}
						
						$(player_id).jPlayer("setMedia", {
							mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
						});
						$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
						$(player_id).jPlayer("play");
						
						if(b2make.player[player_id].album){
							var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
							var color_not = (pai.myAttr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
							
							$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function(){
								var musica_num = parseInt($(this).myAttr('data-musica-num'));
								
								if(musica_num == b2make.player[player_id].num_musica){
									$(this).addClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_playing);
								} else {
									$(this).removeClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_not);
								}
							});
						}
					});
					
					$(player_id).bind($.jPlayer.event.timeupdate, function(event) {
						$(player_control+".b2make-player-time").text($.jPlayer.convertTime(event.jPlayer.status.currentTime));
						
						if(b2make.player[player_id].mudou_musicas){
							b2make.player[player_id].mudou_musicas = false;
							$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
							$(player_control+".b2make-player-play").addClass("b2make-player-pause_css");
						}
					});
					
					if(b2make.player[player_id].album){
						var parent_id = $(player_id).parent().myAttr('id');
						$('#'+parent_id+' .b2make-albummusicas-widget-list-mp3s .b2make-albummusicas-widget-mp3').live('mouseup tap',function(e){
							if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
							b2make.player[player_id].num_musica = parseInt($(this).myAttr('data-musica-num'));
							
							var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
							var color_not = (pai.myAttr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
							
							$('#'+parent_id+' .b2make-albummusicas-widget-list-mp3s .b2make-albummusicas-widget-mp3').each(function(){
								var musica_num = parseInt($(this).myAttr('data-musica-num'));
								
								if(musica_num == b2make.player[player_id].num_musica){
									$(this).addClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_playing);
									
									if(b2make.player_playing)if(b2make.player_playing != player_id){
										$(b2make.player_playing).jPlayer("pause");
										b2make.player[b2make.player_playing].player_pause = true;
										$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
									}
									b2make.player_playing = player_id;
									
									$(player_id).jPlayer("stop");
									$(player_id).jPlayer("setMedia", {
										mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
									});
									$(player_id).jPlayer("play");
									b2make.player[player_id].player_pause = false;
									$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
									$(player_control+".b2make-player-play").addClass("b2make-player-pause_css");
								} else {
									$(this).removeClass('b2make-albummusicas-widget-playing');
									$(this).css('color',color_not);
								}
							});
						});
					}
				}
			});
		}
		
		function slideshow_animation_start(obj){
			if(b2make.slideshow_start[$(obj).myAttr('id')]){
				var width = $(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image').width();
				var direction = 'left';
				var tempo = 3000;
				
				if(!$(obj).myAttr('data-animation')){
					$(obj).myAttr('data-animation',true);
				}
				if($(obj).myAttr('data-direction')){
					direction = $(obj).myAttr('data-direction');
				}
				if($(obj).myAttr('data-tempo')){
					tempo = parseInt($(obj).myAttr('data-tempo'));
				}
				
				if(direction == 'left'){
					$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').stop().animate({
						left: -width
					}, tempo,'linear', function() {
						$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image:first-child').appendTo($(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder'));
						$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').css('left',0);
						slideshow_animation_start(obj);
					});
				} else {
					$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image:last-child').prependTo($(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder'));
					$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').css('left',-width);
					$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').stop().animate({
						left: 0
					}, tempo,'linear', function() {
						slideshow_animation_start(obj);
					});
				}
			}
		}
		
		function div_colidindo(div1, div2){
			// Div 1 data
			var d1_offset             = div1.offset();
			var d1_height             = div1.outerHeight( true );
			var d1_width              = div1.outerWidth( true );
			var d1_distance_from_top  = d1_offset.top + d1_height;
			var d1_distance_from_left = d1_offset.left + d1_width;

			// Div 2 data
			var d2_offset             = div2.offset();
			var d2_height             = div2.outerHeight( true );
			var d2_width              = div2.outerWidth( true );
			var d2_distance_from_top  = d2_offset.top + d2_height;
			var d2_distance_from_left = d2_offset.left + d2_width;

			var not_colliding = ( d1_distance_from_top < d2_offset.top || d1_offset.top > d2_distance_from_top || d1_distance_from_left < d2_offset.left || d1_offset.left > d2_distance_from_left );

			// Return whether it IS colliding
			return ! not_colliding;
		}
		
		function div_abaixo(div1, div2){
			// Div 1 data
			var d1_offset             = div1.offset();
			var d1_height             = div1.outerHeight( true );
			var d1_width              = div1.outerWidth( true );
			var d1_distance_from_top  = d1_offset.top + d1_height;
			var d1_distance_from_left = d1_offset.left + d1_width;

			// Div 2 data
			var d2_offset             = div2.offset();
			var d2_height             = div2.outerHeight( true );
			var d2_width              = div2.outerWidth( true );
			var d2_distance_from_top  = d2_offset.top + d2_height;
			var d2_distance_from_left = d2_offset.left + d2_width;
			
			if(d1_distance_from_top < d2_offset.top){
				if(d2_offset.left < d1_offset.left && d2_distance_from_left > d1_offset.left){
					return true;
				}
				if(d2_offset.left < d1_distance_from_left && d2_distance_from_left > d1_distance_from_left){
					return true;
				}
				if(d2_offset.left < d1_distance_from_left && d2_distance_from_left < d1_distance_from_left){
					return true;
				}
			}

			return false;
		}
		
		function start_classes(){
			images_conteiners_update();
			if(localStorage.getItem('b2make.page_reload') == 1){
				localStorage.setItem('b2make.page_reload',null);
				location.reload(true);
			}
			
			if($('#b2make-pagina-options').myAttr('instagram_token')){
				b2make.instagram_token = $('#b2make-pagina-options').myAttr('instagram_token');
			}
			
			b2make.conteiners_height = 0;
			
			if(!b2make.formularioObrigatorioText)b2make.formularioObrigatorioText = "* Campos obrigat&oacute;rios!";
			if(!b2make.formularioEmailInvalido)b2make.formularioEmailInvalido = "* Email inv&aacute;lido!";
			if(!b2make.formularioEmailEnviado)b2make.formularioEmailEnviado = "* Contato enviado!";
			
			$('.b2make-widget[data-type="conteiner-area"]').css('cursor',"");
			$('.b2make-widget[data-type="conteiner-area"]').removeAttr('cursor');
			$('.b2make-widget[data-type="conteiner"]').css('cursor',"");
			$('.b2make-widget[data-type="conteiner"]').removeAttr('cursor');
			
			var addthis_exec;
			
			b2make.widget_start = new Array();
			b2make.areas_globais_num = 0;
			b2make.velocidade_menu_top = 3;
			var index_novo = 0;
			
			$('.b2make-widget').each(function(){
				$(this).css('cursor','default');
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'conteiner':
							b2make.conteiners_height = b2make.conteiners_height + parseInt($(this).height());
							$(this).css('width','100%');
							
							if(b2make.device != 'phone'){
								if($(this).myAttr('data-area-largura')){
									$(this).css('min-width',$(this).myAttr('data-area-largura')+'px');
								}
							} else {
								$(this).css('min-width','0px');
							}
							
							var status = $(this).myAttr('data-area-fixed');
							
							if(status == 't'){
								var fator = 14;
								var top = parseInt($(this).css('top'));
								
								$(this).css('top',(top - fator)+'px');
							}
							
							
							var flag = false;
							
							if($(this).myAttr('data-position')){
								if($(this).myAttr('data-position') == 'fixed'){
									if(!$(this).myAttr('data-position-pixels')){
										$(this).myAttr('data-position-pixels',$(this).offset().top);
									}
									var top = parseInt($(this).myAttr('data-position-pixels')) - 110;
									$(this).css('top',top);
									flag = true;
								}
							}
							
							if(!flag){
								b2make.conteiner_frames_height = b2make.conteiner_frames_height + $(this).height();
								
								if(!b2make.conteiner_frames){
									b2make.conteiner_frames = new Array();
								}
								
								b2make.conteiner_frames[index_novo] = {
									obj : $(this),
									height : parseInt($(this).height())
								};
								
								index_novo++;
							}
						break;
						case 'player':
							player_widget_controls($(this),'player');
						break;
						case 'slideshow':
							var obj = $(this).get(0);
							
							if(!b2make.slideshow_start) b2make.slideshow_start = new Array();
							
							b2make.slideshow_start[$(obj).myAttr('id')] = true;
							slideshow_animation_start(obj);
						break;
						case 'albummusicas':
							$(this).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').each(function(){
								player_widget_controls($(this),'albummusicas');
							});
						break;
						case 'imagem':
							$(this).find('img').css('cursor','default');
							if($(this).myAttr('data-hiperlink')){
								$(this).css('cursor','pointer');
								$(this).find('img').css('cursor','pointer');
								$(this).on('mouseup tap',function(e){
									if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
									
									window.open($(this).myAttr('data-hiperlink'),($(this).myAttr('data-hiperlink-target') ? $(this).myAttr('data-hiperlink-target') : '_self'));
									
									if(b2make.widget_last_hiperlink == $(this).myAttr('data-hiperlink')){
										$(window).trigger('hashsame');
									}
									
									b2make.widget_last_hiperlink = $(this).myAttr('data-hiperlink');
								});
							}
							
							if($(this).myAttr('data-marcador') == '@e-services#imagem'){
								var src = $(this).find('img').myAttr('src');
								
								$(this).css('background-image','url('+src+')');
								$(this).css('background-size','cover');
								
								$(this).find('img').remove();
							}
							
							mobile_sitemap({obj:this});
						break;
						case 'texto':
							$(this).find('.b2make-texto-table').css('cursor','default');
							$(this).find('.b2make-texto-table').find('.b2make-texto-cel').css('cursor','default');
							if($(this).myAttr('data-hiperlink')){
								$(this).css('cursor','pointer');
								$(this).find('.b2make-texto-table').css('cursor','pointer');
								$(this).find('.b2make-texto-table').find('.b2make-texto-cel').css('cursor','pointer');
								$(this).on('mouseup tap',function(e){
									if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
									
									window.open($(this).myAttr('data-hiperlink'),($(this).myAttr('data-hiperlink-target') ? $(this).myAttr('data-hiperlink-target') : '_self'));
									
									if(b2make.widget_last_hiperlink == $(this).myAttr('data-hiperlink')){
										$(window).trigger('hashsame');
									}
									
									b2make.widget_last_hiperlink = $(this).myAttr('data-hiperlink');
								});
							}
							
							mobile_sitemap({obj:this});
							
							//console.log($(this).prop('scrollHeight') + ' - ' + $(this).height());
						break;
						case 'instagram':
							var obj = $(this).get(0);
							
							instagram_verificar_recentes({
								obj : obj
							});
						break;
						case 'addthis':
							addthis_exec = true;
						break;
						case 'services':
							servicos_widget_update({obj:this});
						break;
						case 'contents':
							b2make.widget_start['contents'] = true;
						break;
						case 'posts-filter':
							b2make.widget_start['posts-filter'] = true;
						break;
						case 'form_contato':
							$(this).myAttr('data-type','formularios');
						break;
						case 'google-maps':
							if(!b2make.google_maps_load){
								b2make.google_maps_load = true;
								google_maps_load_api();
							}
						break;
						case 'redessociais':
							var rede = $(this).find('.b2make-widget-out').find('.b2make-redessociais-widget-holder').find('.b2make-redessociais-rede');
							var height = parseInt(rede.height());
							var width = Math.floor((height * 1200) / 100);
							
							rede.find('div').find('svg').css({'width':''});
							rede.find('div').find('svg').myAttr('viewBox','0 0 '+width+' '+height);
							rede.find('div').find('svg').myAttr('width',width+'px');
							rede.find('div').find('svg').myAttr('height',height+'px');
						break;
						case 'texto-complexo':
							var tc = {
								obj : this,
								id : $(this).myAttr('id'),
								scrollHeight : $(this).find('.b2make-widget-out').find('.b2make-texto-complexo')[0].scrollHeight,
								height : $(this).height()
							};
							
							var content = $('<div>'+$(this).find('.b2make-widget-out').find('.b2make-texto-complexo').html()+'</div>');
							var ajuste_height = 15;
							
							content.css('position','absolute');
							content.css('top','-99999999999999999px');
							content.css('width',$(this).width());
							content.css('height','1px');
							content.css('overflow','scroll');
							
							content.appendTo('body');
							
							setTimeout(function(){
								content.remove();
								
								tc.scrollHeight = $(tc.obj).find('.b2make-widget-out').find('.b2make-texto-complexo')[0].scrollHeight;
								
								if(tc.scrollHeight > tc.height){
									var aumento = tc.scrollHeight - tc.height;
									var obj_area;
									
									if($(tc.obj).parent().myAttr('data-type') == 'conteiner-area'){
										obj_area = $(tc.obj).parent().parent();
									} else {
										obj_area = $(tc.obj).parent();
									}
									
									var area_height = obj_area.height();
									
									$(tc.obj).parent().find('.b2make-widget').each(function(){
										var top = $(this).position().top;
										
										if(tc.id != $(this).myAttr('id')){
											if(div_colidindo($(tc.obj),$(this))){
												$(this).css('top',(top + aumento)+'px');
											}
											if(div_abaixo($(tc.obj),$(this))){
												$(this).css('top',(top + aumento)+'px');
											}
										}
									});
									
									$(tc.obj).height(tc.scrollHeight + ajuste_height);
									obj_area.height((area_height + aumento)+'px');
									
									tecnologia_posicionar();
								}
							},200);
						break;
					}
					
					$(this).find('.b2make-widget-out').find('.b2make-widget-loading').show();
				}
			});
			
			if(addthis_exec){
				var script = document.location.protocol+'//s7.addthis.com/js/300/addthis_widget.js?async=1&domready=1&pubid=ra-4dc8b14029ceaa85';
				
				$.getScript(script,function() {
					window.addthis.update('config', 'data_track_clickback', true);
					window.addthis.update('share', 'url', location.href);
					window.addthis.update('share', 'title', document.title);
					window.addthis.toolbox(".addthis_toolbox");
				});
			}
			
			$('.b2make-widget-menu').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				
				var area = $(this).parent().parent().parent();
				var this_obj = this;
				var menu_holder_append = false;
				var position = 'absolute';
				
				if(area.myAttr('data-type') == 'conteiner-area'){
					area = area.parent();
				}
				
				if(area.myAttr('data-position') == 'fixed'){
					position = 'fixed';
				}
				
				if($('#b2make-widget-menu-holder').length == 0){
					menu_holder_append = true;
					b2make.menu_holder = $('<div id="b2make-widget-menu-holder"></div>');
				} else {
					b2make.menu_holder = $('#b2make-widget-menu-holder');
					b2make.menu_holder.html('');
				}
				
				if(b2make.menu_holder.myAttr('data-open') == '1'){
					b2make.menu_holder.myAttr('data-open','0');
					b2make.menu_holder.hide();
					return;
				}
				
				var areas_ocultas = $(this).parent().parent().myAttr('data-areas');
				var areas_ocultas_arr = new Array();
				
				if(areas_ocultas)
					areas_ocultas_arr = areas_ocultas.split(',');
			
			
				if(menu_holder_append)b2make.menu_holder.appendTo('body');
				
				$('.b2make-widget').each(function(){
					if($(this).myAttr('data-type') == 'conteiner'){
						var found = false;
						
						for(var j=0;j<areas_ocultas_arr.length;j++){
							var area_oculta = areas_ocultas_arr[j];
							if(area_oculta == $(this).myAttr('id')){
								found = true;
								break;
							}
						}
						
						if(!found){
							var caixa_color = '';
							var font_color = '';
							var hover_color = '';
							var font_family = '';
							var font_size = '';
							var font_align = '';
							var font_italico = '';
							var font_negrito = '';
							var espacamento = '';
							var largura = '';
							
							if($(this_obj).myAttr('data-caixa-color-ahex')){
								caixa_color = jpicker_ahex_2_rgba($(this_obj).myAttr('data-caixa-color-ahex'));
							}
							if($(this_obj).myAttr('data-font-color-ahex')){
								font_color = jpicker_ahex_2_rgba($(this_obj).myAttr('data-font-color-ahex'));
							}
							if($(this_obj).myAttr('data-hover-color-ahex')){
								hover_color = jpicker_ahex_2_rgba($(this_obj).myAttr('data-hover-color-ahex'));
							}
							if($(this_obj).myAttr('data-font-family')){
								font_family = $(this_obj).myAttr('data-font-family');
							}
							if($(this_obj).myAttr('data-font-size')){
								font_size = $(this_obj).myAttr('data-font-size');
							}
							if($(this_obj).myAttr('data-font-align')){
								font_align = $(this_obj).myAttr('data-font-align');
							}
							if($(this_obj).myAttr('data-font-italico')){
								if($(this_obj).myAttr('data-font-italico') == 'sim')
									font_italico = $(this_obj).myAttr('data-font-italico');
							}
							if($(this_obj).myAttr('data-font-negrito')){
								if($(this_obj).myAttr('data-font-negrito') == 'sim')
									font_negrito = $(this_obj).myAttr('data-font-negrito');
							}
							if($(this_obj).myAttr('data-espacamento')){
								espacamento = $(this_obj).myAttr('data-espacamento');
							}
							if($(this_obj).myAttr('data-largura')){
								largura = $(this_obj).myAttr('data-largura');
							}
							
							var link = $('<div data-id="'+$(this).myAttr('id')+'">'+($(this).myAttr('data-name')?$(this).myAttr('data-name'):$(this).myAttr('id'))+'</div>');
							link.appendTo('#b2make-widget-menu-holder');
							
							if(caixa_color){
								link.css('background-color',caixa_color);
							}
							
							if(font_color || font_family || font_size){
								link.html('<span>'+link.html()+'</span>');
							}
							
							if(hover_color){
								link.hover(
									function () {
										$(this).css('background-color',hover_color);
									}, 
									function () {
										$(this).css('background-color',caixa_color);
									}
								);
							}
							
							if(font_color){
								link.find('span').css('color',font_color);
							}
							
							if(font_family){
								link.find('span').css('fontFamily',font_family);
							}
							
							if(font_size){
								link.find('span').css('fontSize',font_size+'px');
								link.css('line-height',font_size+'px');
							}
							
							if(font_align){
								link.css('textAlign',font_align);
							}
							
							if(font_italico){
								link.css('fontStyle','italic');
							}
							
							if(font_negrito){
								link.css('fontWeight','bold');
							}
							
							if(espacamento){
								link.css('padding',espacamento+'px');
								link.css('margin-bottom',espacamento+'px');
							}
							
							if(largura){
								link.css('width',largura+'px');
							}
							
						}
					}
				});
				
				b2make.menu_holder_position = position;
				b2make.menu_holder_obj = $(this);
				b2make.menu_holder.myAttr('data-menu-atual',$(this).myAttr('id'));
				b2make.menu_holder.css('position',position);
				
				if(position == 'fixed'){
					b2make.menu_holder.css('top',($(this).parent().parent().position().top + $(this).outerHeight()) + 'px');
					b2make.menu_holder.css('left',$(this).offset().left + 'px');
				} else {
					b2make.menu_holder.css('top',($(this).offset().top + $(this).outerHeight()) + 'px');
					b2make.menu_holder.css('left',$(this).offset().left + 'px');
				}
				b2make.menu_holder.myAttr('data-open','1');
				b2make.menu_holder.show();
				
				$('#b2make-widget-menu-holder div').on('mouseup tap',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					menu_animar_scroll({obj:$(this)});
				});
			});
			
			if($("a[rel^='prettyPhoto']").length){
				var prettyphoto_var = {animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true};
				setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto(prettyphoto_var); }, 100);
			}
			
			$('div.b2make-widget[data-type="agenda"]').each(function(){
				var excluir_eventos = $(this).myAttr('data-excluir-eventos');
				
				if(!excluir_eventos) excluir_eventos = 's';
				
				if(excluir_eventos == 's')
				$(this).find('div.b2make-widget-out').find('div.b2make-eventos-widget-holder').find('div.b2make-widget-eventos').each(function(){
					var data = $(this).find('div.b2make-widget-eventos-data').myAttr('data-date');
					var hora = $(this).find('div.b2make-widget-eventos-hora').html().split(':');
					
					var time1 = parseInt($.datepicker.formatDate('@',$.datepicker.parseDate( "dd/mm/yy", data)));
					var time2 = parseInt(hora[0]) * 1000 * 60 * 60 + parseInt(hora[1]) * 1000 * 60 ;
					var time_now = parseInt($.now());
					
					if(time1 + time2 < time_now){
						$(this).remove();
					}
				});
			});
			
			$('.b2make-gwi-prev').bind('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				
				var holder = $(this).parent().find('div.b2make-galeria-widget-holder');
				var img = holder.find('div:last-child');
				
				img.prependTo(holder);
			});
			
			$('.b2make-gwi-next').bind('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				
				var holder = $(this).parent().find('div.b2make-galeria-widget-holder');
				var img = holder.find('div:first-child');
				
				img.appendTo(holder);
			});
			
			$('.b2make-wsoae-prev').bind('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				
				var holder = $(this).parent().find('div.b2make-eventos-widget-holder');
				var img = holder.find('div.b2make-widget-eventos:last-child');
				
				img.prependTo(holder);
			});
			
			$('.b2make-wsoae-next').bind('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				
				var holder = $(this).parent().find('div.b2make-eventos-widget-holder');
				var img = holder.find('div.b2make-widget-eventos:first-child');
				
				img.appendTo(holder);
			});
			
			$('.b2make-albumfotos-widget-image').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				if($(e.target).hasClass('b2make-albumfotos-legenda-btn')){
					return true;
				}
				
				if($(this).myAttr('data-imagens-urls')){
					var imgs_arr = $(this).myAttr('data-imagens-urls').split(',');
					var imgs;
					
					if(imgs_arr.length > 0){
						imgs = new Array();
						for(var i=0;i<imgs_arr.length;i++){
							imgs.push(imgs_arr[i]);
						}
						if(!b2make.start_pretty_photo){
							$.fn.prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false});
							b2make.start_pretty_photo = true;
						}
						$.prettyPhoto.open(imgs);
					}
				}
			});
			
			$('.b2make-slideshow-widget-image').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				if(!b2make.widget_child_move){
					var imagens = $(this).parent().parent().parent().myAttr('data-imagens-urls');
					var imagem = $(this).myAttr('data-image-url');
					
					if(imagens){
						var imgs_arr = imagens.split(',');
						var imgs;
						var indice = 0;
						
						if(imgs_arr.length > 0){
							imgs = new Array();
							for(var i=0;i<imgs_arr.length;i++){
								if(imagem == imgs_arr[i]){
									indice = i;
									break;
								}
							}
							for(var i=indice;i<imgs_arr.length;i++){
								imgs.push(imgs_arr[i]);
							}
							for(var i=0;i<indice;i++){
								imgs.push(imgs_arr[i]);
							}
							if(!b2make.start_pretty_photo){
								$.fn.prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false});
								b2make.start_pretty_photo = true;
							}
							$.prettyPhoto.open(imgs);
						}
					}
				}
			});
			
			$('.b2make-galeria-imagens-widget-image,.b2make-galeria-imagens-widget-image-2').live('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var img = $(this).myAttr('data-image-url');
				var pai = $(this).parent().parent().parent();
				
				if(pai.myAttr('data-imagens-urls')){
					var imgs_arr = pai.myAttr('data-imagens-urls').split(',');
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
							$.fn.prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false});
							b2make.start_pretty_photo = true;
						}
						$.prettyPhoto.open(imgs);
					}
				}
			});
		}
		
		// ==================================== Menu Páginas =============================
		
		function menu_pagina_submenu_posicao(p){
			if(!p)p={};
			
			var obj = p.obj;
			var id = obj.myAttr('data-id');
			
			switch(obj.myAttr('data-tipo')){
				case 'esquerda':
					obj.css('width',($('#'+id).outerWidth(true)+$('#'+id).offset().left)+'px');
					obj.find('ul').find('li').find('a').css('width',($('#'+id).outerWidth(true)+$('#'+id).offset().left-30)+'px');
					obj.css('left','0px');
				break;
				case 'direita':
					obj.css('width',($(window).outerWidth(true)-$('#'+id).offset().left)+'px');
					obj.find('ul').find('li').find('a').css('width',($(window).outerWidth(true)-$('#'+id).offset().left-30)+'px');
					obj.css('left',$('#'+id).offset().left+'px');
				break;
			}
		}
		
		function menu_pagina_html_urls(p){
			if(!p)p={};
			
			var urls = p.urls;
			var sites = b2make.menu_paginas.sitemaps.sites;
			var found_pai;
			var found_pai_2;
			var obj = $('#'+p.id);
			var escolha_pontual = false;
			var num_paginas = 0;
			var ids_paginas_arr;
			
			if(obj.myAttr('data-paginas-opcao') && obj.myAttr('data-paginas-opcao') == 'escolha-pontual'){
				escolha_pontual = true;
			}
			
			if(escolha_pontual){
				if(obj.myAttr('data-ids-paginas')){
					var ids_paginas = obj.myAttr('data-ids-paginas');
					var paginas_ids = new Array();
					ids_paginas_arr = ( ids_paginas ? ids_paginas.split(',') : false);
				}
			}
			
			
			if(escolha_pontual){
				var entra_menu = false;
				var faz_parte_menu = new Array();
				
				if(ids_paginas_arr)
				for(var k=0;k<ids_paginas_arr.length;k++){
					for(var i=0;i<sites.length;i++){
						if(ids_paginas_arr[k] == sites[i].id_site){
							found_pai = false;
							found_pai_2 = false;
							for(var j=0;j<sites.length;j++){
								if(sites[i].id_site_pai == sites[j].id_site && !sites[j].raiz){
									found_pai = true;
								}
							}
							
							if(faz_parte_menu)
							for(var j=0;j<faz_parte_menu.length;j++){
								if(sites[i].id_site_pai == faz_parte_menu[j]){
									found_pai_2 = true;
								}
							}
							
							var url = (b2make.mobile.active && sites[i].url_mobile ? sites[i].url_mobile : sites[i].url);
							var li = $('<li><a href="'+url+'">'+(found_pai && found_pai_2 ? '&nbsp;&nbsp;&nbsp;':'')+(sites[i].raiz?'In&iacute;cio':sites[i].nome)+'</a></li>');
							
							faz_parte_menu.push(sites[i].id_site);
							
							if(sites[i].raiz){
								urls.prepend(li);
							} else {
								urls.append(li);
							}
							
							num_paginas++;
						}
					}
				}
			} else {
				for(var i=0;i<sites.length;i++){
					found_pai = false;
					for(var j=0;j<sites.length;j++){
						if(sites[i].id_site_pai == sites[j].id_site && !sites[j].raiz){
							found_pai = true;
						}
					}
					
					var url = (b2make.mobile.active && sites[i].url_mobile ? sites[i].url_mobile : sites[i].url);
					var li = $('<li><a href="'+url+'">'+(found_pai ? '&nbsp;&nbsp;&nbsp;':'')+(sites[i].raiz?'In&iacute;cio':sites[i].nome)+'</a></li>');
					
					if(sites[i].raiz){
						urls.prepend(li);
					} else {
						urls.append(li);
					}
					
					num_paginas++;
				}
			}
			
			return {urls:urls,num_paginas:num_paginas};
		}
		
		function menu_paginas_html(p){
			if(!p)p={};
			
			var tipo = $('#'+p.id).myAttr('data-tipo-menu');
			var color = $('#'+p.id).myAttr('data-widget-color-ahex');
			var left = $('#'+p.id).offset().left;
			var top = parseInt($('#'+p.id).offset().top) + parseInt($('#'+p.id).outerHeight(true));
			
			var cont_principal = $('<div class="b2make-menu-paginas-submenu" data-id="'+p.id+'"'+(tipo ? ' data-tipo="'+tipo+'"':'')+'></div>');
			cont_principal.hide();
			cont_principal.css('left',left+'px');
			cont_principal.css('top',top+'px');
			cont_principal.appendTo('body');
			
			if(color){
				cont_principal.css('background-color',jpicker_ahex_2_rgba(color));
			}
			
			var urls = $('<ul></ul>');
			
			var ret = menu_pagina_html_urls({urls:urls,id:p.id});
			
			urls = ret.urls;
			
			cont_principal.append(urls);
			
			if(color){
				cont_principal.find('ul').find('li').css('border-bottom','1px solid '+jpicker_ahex_2_rgba(color));
			}
			
			cont_principal.css('height',((b2make.menu_paginas.height+1)*ret.num_paginas)+'px');
			
			b2make.menu_paginas.submenu[p.id] = cont_principal;
			
			if(tipo == 'direita' || tipo == 'esquerda'){
				menu_pagina_submenu_posicao({obj:cont_principal});
			}
		}
		
		function menu_paginas_close_all(){
			if(b2make.menu_paginas.submenu){
				const keys = Object.keys(b2make.menu_paginas.submenu)
				for (const key of keys) {
					b2make.menu_paginas.submenu[key].stop().fadeToggle(b2make.menu_paginas.transicao,function(){
						var left = $('#'+key).offset().left;
						var tipo = $(this).myAttr('data-tipo');
					
						if(tipo != 'direita' && tipo != 'esquerda'){
							var top = parseInt($('#'+key).offset().top) + parseInt($('#'+key).outerHeight(true));
							
							$(this).css('left',left+'px');
							$(this).css('top',top+'px');
						}
					});
				}
			}
			
			b2make.menu_paginas.open = false;
		}
		
		function menu_paginas_close(p){
			if(!p)p={};
			
			if(b2make.menu_paginas.submenu[p.id]){
				b2make.menu_paginas.submenu[p.id].stop().fadeToggle(b2make.menu_paginas.transicao,function(){
					var left = $('#'+p.id).offset().left;
					var tipo = $(this).myAttr('data-tipo');
				
					if(tipo != 'direita' && tipo != 'esquerda'){
						var top = parseInt($('#'+p.id).offset().top) + parseInt($('#'+p.id).outerHeight(true));
						
						$(this).css('left',left+'px');
						$(this).css('top',top+'px');
					}
				});
			}
			
			b2make.menu_paginas.open = false;
		}
		
		function menu_paginas_open(p){
			if(!p)p={};
			
			if(!b2make.menu_paginas.submenu){
				b2make.menu_paginas.submenu = new Array();
			}
			
			if(!b2make.menu_paginas.submenu[p.id]){
				menu_paginas_html({id:p.id});
			}
			
			b2make.menu_paginas.submenu[p.id].stop().fadeToggle(b2make.menu_paginas.transicao,function(){
				$(this).css('opacity','1');
				var tipo = $(this).myAttr('data-tipo');
				
				if(tipo != 'direita' && tipo != 'esquerda'){
					var left = $('#'+p.id).offset().left;
					var top = parseInt($('#'+p.id).offset().top) + parseInt($('#'+p.id).outerHeight(true));
					
					$(this).css('left',left+'px');
					$(this).css('top',top+'px');
				} else {
					menu_pagina_submenu_posicao({obj:$(this)});
				}
			});
			
			b2make.menu_paginas.open = true;
		}
		
		function menu_paginas_start(p){
			var sitemaps = p.json;
			
			b2make.menu_paginas.sitemaps_loaded = true;
			b2make.menu_paginas.sitemaps = sitemaps;
			
			if(b2make.menu_paginas.menu_clicked){
				menu_paginas_open({id:b2make.menu_paginas.menu_clicked});
			}
		}
		
		function menu_paginas(){
			b2make.menu_paginas = {};
			
			b2make.menu_paginas.transicao = 300;
			b2make.menu_paginas.height = 60;
			
			if($('.b2make-menu-paginas').length > 0){
				var opcao = 'menu_paginas';
				
				$.ajax({
					type: 'POST',
					url: '/platform/library/sitemaps',
					data: { 
						ajax : 'sim'
					},
					beforeSend: function(){
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var json = eval('(' + txt + ')');
							
							if(json){
								menu_paginas_start({json:json});
							} else {
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
				
				$(document.body).on('mouseup tap','.b2make-menu-paginas',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var id = $(this).parent().parent().myAttr('id');
					
					if(b2make.menu_paginas.sitemaps_loaded){
						if(!b2make.menu_paginas.open){
							menu_paginas_open({id:id});
						} else {
							menu_paginas_close({id:id});
						}
					} else {
						b2make.menu_paginas.menu_clicked = id;
					}
				});
				
				$(window).resize(function() {
					$('.b2make-menu-paginas-submenu').each(function(){
						var id = $(this).myAttr('data-id');
						var tipo = $(this).myAttr('data-tipo');
						
						if(tipo != 'direita' && tipo != 'esquerda'){
							var left = $('#'+id).offset().left;
							var top = parseInt($('#'+id).offset().top) + parseInt($('#'+id).outerHeight(true));
							
							$(this).css('left',left+'px');
							$(this).css('top',top+'px');
						} else {
							menu_pagina_submenu_posicao({obj:$(this)});
						}
					});
				});
			}
		}
		
		// ==================================== Áreas Globais =============================
		
		function conteiner_areas_globais_change_area(p = {}){
			var obj = p.obj;
			var area_global_id = $(obj).myAttr('data-area-global-id');
			var area_local_id = $(obj).myAttr('id');
			var id_func = 'conteiner_areas_globais_change_area';
			
			$.ajax({
				cache: true,
				type: 'GET',
				crossDomain: true,
				url: b2make.hostname+'files/areas_globais/'+area_global_id+'.html?v='+b2make.site_version,
				beforeSend: function(){
				},
				success: function(txt){
					var obj_new = $(txt);
					
					obj_new.myAttr('id',area_local_id);
					
					obj_new.css('width','100%');
					
					var area_global_html = $('<div>').append(obj_new.clone()).html();
					
					$(obj).before(area_global_html);
					$(obj).remove();
					
					$("#"+area_local_id).find('.b2make-conteiner-area').myAttr('id','conteiner-area'+b2make.widgets_count);
					$("#"+area_local_id).find('.b2make-conteiner-area').parent().myAttr('data-area','conteiner-area'+b2make.widgets_count);
					
					b2make.widgets_count++;
					
					$("#"+area_local_id).find(b2make.widget).each(function(){
						if($(this).myAttr('data-type') != 'conteiner-area'){
							var type = $(this).myAttr('data-type');
							
							$(this).myAttr('id',type+b2make.widgets_count);
							
							b2make.widgets_count++;
						}
					});
					
					b2make.areas_globais_num--;
					
					if(b2make.areas_globais_num == 0){
						b2make.plataforma_manual_start = false;
						plataforma_start();
					}
				},
				error: function(txt){
					console.log('ERROR AJAX - '+id_func+' - '+txt);
				}
			});
		}
		
		// ==================================== Breadcrumbs =============================
		
		function breadcrumbs_montar_menu(p = {}){
			var pagina_arvore = p.pagina_arvore;
			var inicio = 'In&iacute;cio';
			
			var url = '<a href="'+(b2make.mobile.active && pagina_arvore.url_mobile ? pagina_arvore.url_mobile : pagina_arvore.url)+'" class="b2make-breadcrumbs-link'+(p.start? ' b2make-breadcrumbs-link-2':'')+'">'+(pagina_arvore.pai ? pagina_arvore.nome : inicio)+'</a>';
			
			if(pagina_arvore.pai){
				return breadcrumbs_montar_menu({pagina_arvore:pagina_arvore.pai}) + '<span class="b2make-breadcrumbs-link-sep"> / </span>' + url
			} else {
				return url;
			}
		}
		
		function breadcrumbs_montar_arvore(p = {}){
			if(p.sitemaps){
				var sites_unpublished = p.sitemaps.sites_unpublished;
				var sites = p.sitemaps.sites;
				var atual_id = p.atual_id;
				var pagina_arvore = false;
				
				if(sites)
				for(var i=0;i<sites.length;i++){
					if(atual_id == sites[i].id_site){
						if(sites[i].id_site_pai){
							return {
								nome : sites[i].nome,
								url : sites[i].url,
								url_mobile : sites[i].url_mobile,
								pai : breadcrumbs_montar_arvore({sitemaps:p.sitemaps,atual_id:sites[i].id_site_pai})
							};
						} else {
							return {
								nome : sites[i].nome,
								url : sites[i].url,
								url_mobile : sites[i].url_mobile,
								pai : false
							};;
						}
					}
				}
				
				if(sites_unpublished)
				for(var i=0;i<sites_unpublished.length;i++){
					if(atual_id == sites_unpublished[i].id_site){
						if(sites_unpublished[i].id_site_pai){
							return {
								nome : sites_unpublished[i].nome,
								url : sites_unpublished[i].url,
								url_mobile : sites_unpublished[i].url_mobile,
								pai : breadcrumbs_montar_arvore({sitemaps:p.sitemaps,atual_id:sites_unpublished[i].id_site_pai})
							};
						} else {
							return {
								nome : sites_unpublished[i].nome,
								url : sites_unpublished[i].url,
								url_mobile : sites_unpublished[i].url_mobile,
								pai : false
							};
						}
					}
				}
				
				return false;
			}
		}
		
		function breadcrumbs_start(p){
			var sitemaps = p.json;
			var atual_id = p.atual_id;
			var obj = p.obj;
			var cont_principal = p.cont_principal;
			
			var pagina_arvore = breadcrumbs_montar_arvore({sitemaps:sitemaps,atual_id:atual_id});
			var breadcrumbs = breadcrumbs_montar_menu({pagina_arvore:pagina_arvore,start:true});
			cont_principal.html(breadcrumbs);
			$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
		}
		
		function breadcrumbs_html_update(p = {}){
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			var cont_principal = $(obj).find('.b2make-widget-out').find('.b2make-breadcrumbs');
			var atual_id = $(obj).myAttr('data-id');
			var plugin_id = 'breadcrumbs';
			var id_func = 'pagina-arvore';
			
			cont_principal.html('');
			
			var opcao = 'breadcrumbs_html_update';
			
			$.ajax({
				type: 'POST',
				url: '/platform/library/sitemaps',
				data: { 
					ajax : 'sim'
				},
				beforeSend: function(){
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var json = eval('(' + txt + ')');
						
						if(json){
							breadcrumbs_start({json:json,atual_id:atual_id,obj:obj,cont_principal:cont_principal});
						} else {
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

		function breadcrumbs(){
			$('.b2make-widget').each(function(){
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'breadcrumbs':
							breadcrumbs_html_update({obj:this});
						break;
					}
				}
			});
		}
		
		// ==================================== Google Maps =============================
		
		function google_maps_create(p){
			if(!p) p = {};
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			var id_pai = $(obj).myAttr('id');
			var id_map = $(obj).myAttr('id')+'-map';
			var obj_pai = obj;
			
			if($(obj).myAttr('data-area')){
				obj = $(obj).find('.b2make-widget[data-type="conteiner-area"]');
			} else {
				obj = $(obj);
			}
			
			obj = obj.find('.b2make-widget-out').find('.b2make-google-maps');
			
			obj.html('');
			
			var div_map = $('<div id="'+id_map+'" class="b2make-google-maps-map"></div>');
			div_map.appendTo(obj);
			
			div_map.height($(obj_pai).height());
			
			var gmaps = false;
			
			gmaps = {};
			gmaps.map_id = id_map;
			
			gmaps.geocoder = new google.maps.Geocoder();
			gmaps.bounds = new google.maps.LatLngBounds();
			gmaps.markersArray = [];
			
			var lat = -23.5428164;
			var lng = -46.6416659;
			
			if($(obj_pai).myAttr('data-latlong')){
				var latlong_txt = $(obj_pai).myAttr('data-latlong');
				var latlong_arr = latlong_txt.split(':');
				lat = parseFloat(latlong_arr[0]);
				lng = parseFloat(latlong_arr[1]);
			}
			
			var zoom = b2make.googleMaps.zoom;
			
			if($(obj_pai).myAttr('data-zoom')){
				zoom = parseInt($(obj_pai).myAttr('data-zoom'));
			}
			
			var styleJson = [];
			
			if($(obj_pai).myAttr('data-style')){
				styleJson = JSON.parse($(obj_pai).myAttr('data-style-json'));
				styleJson = styleJson.styles;
			}
			
			var styledMapType = new google.maps.StyledMapType(styleJson,{name: b2make.msgs.googleMapsStyledButton});
			
			gmaps.opts = {
				center: new google.maps.LatLng(lat,lng),
				zoom: zoom,
				maxZoom: b2make.googleMaps.maxZoom,
				scrollwheel: false,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				disableDoubleClickZoom: true,
				mapTypeControlOptions: {
					mapTypeIds: ['styled_map', 'satellite', 'hybrid', 'terrain']
				}
			};
			
			gmaps.map = new google.maps.Map(document.getElementById(gmaps.map_id), gmaps.opts);
			
			gmaps.map.mapTypes.set('styled_map', styledMapType);
			gmaps.map.setMapTypeId('styled_map');
			
			gmaps.markersArray.push(new google.maps.Marker({
				position: {lat: lat, lng: lng},
				map: gmaps.map,
				title: b2make.msgs.googleMapsTitleMarker
			}));
			
			b2make.googleMaps.gmaps[id_pai] = gmaps;
		}

		function google_maps_load_api(){
			$.getScript('https://maps.googleapis.com/maps/api/js?key=AIzaSyDR5LYU7Spye-I-jrkQkSoHATJfpWGipGk', function(){
				google_maps_start();
			});
		}
		
		function google_maps_start(){
			b2make.googleMaps = {};
			
			b2make.googleMaps.gmaps = new Array();
			b2make.googleMaps.zoom = 12;
			b2make.googleMaps.maxZoom = 20;
			
			if(!b2make.msgs.googleMapsTitleMarker)b2make.msgs.googleMapsTitleMarker = 'Local do Estabelecimento';
			if(!b2make.msgs.googleMapsStyledButton)b2make.msgs.googleMapsStyledButton = 'Mapa';
			
			$('.b2make-widget').each(function(){
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'google-maps':
							google_maps_create({obj:this});
						break;
					}
				}
			});
		}
		
		// ==================================== Google Analytics =============================
		
		function b2make_ga(p = {}){
			if(typeof gtag != 'function'){
			   return false;
			}
			
			var evento = p.evento;
			var item_id = p.item_id;
			var item_dados = p.item_dados;
			var item_quant = p.item_quant;
			
			gtag('event', evento, {
				"items": [
					{
					"id": item_id,
					"name": item_dados.nome,
					"price": item_dados.preco,
					"quantity": item_quant,
					}
				]
			});
		}
		
		// ==================================== E-Service =============================
		
		function formatMoney2(n){
			n = parseFloat(n);
		var c = isNaN(c = Math.abs(c)) ? 2 : c, 
			d = d == undefined ? "," : d, 
			t = t == undefined ? "." : t, 
			s = n < 0 ? "-" : "", 
			i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))), 
			j = (j = i.length) > 3 ? j % 3 : 0;
		   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
		}
		
		function servicos_widget_html(p){
			if(!p)p={};
			
			if(p.link_area){			
				var service_cont = $('<a href="'+p.service.url+'" class="b2make-service-cont" data-id="'+p.service.id+'" data-quantidade="'+p.service.quantidade+'" data-validade="'+p.service.validade+'" data-validade_data="'+p.service.validade_data+'" data-validade_hora="'+p.service.validade_hora+'" data-validade_tipo="'+p.service.validade_tipo+'" data-preco="'+p.service.preco+'" data-href="'+p.service.url+'"></a>');
			} else {
				var service_cont = $('<div class="b2make-service-cont" data-id="'+p.service.id+'" data-quantidade="'+p.service.quantidade+'" data-validade="'+p.service.validade+'" data-validade_data="'+p.service.validade_data+'" data-validade_hora="'+p.service.validade_hora+'" data-validade_tipo="'+p.service.validade_tipo+'" data-preco="'+p.service.preco+'" data-href="'+p.service.url+'"></div>');
			}
			
			var imagem = $('<div class="b2make-service-imagem" style="background-image:url('+(p.service.url_imagem ? p.service.url_imagem : '//'+b2make.url+'/site/images/b2make-album-sem-imagem.png')+');"></div>');
			var name = $('<div class="b2make-service-name">'+p.service.nome+'</div>');
			var descricao = $('<div class="b2make-service-descricao">'+p.service.descricao+'</div>');
			var comprar = $('<a class="b2make-service-comprar" href="'+p.service.url+'">'+(parseInt(p.service.quantidade) > 0?b2make.msgs.servicosComprar:b2make.msgs.servicosIndisponivel)+'</a>');
			var preco = $('<div class="b2make-service-preco">R$ '+formatMoney2(p.service.preco)+'</div>');
			
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
			
			return service_cont;
		}

		function servicos_widget_update_start(p){
			var obj = p.obj;
			var servicos = p.servicos;
			
			if(!b2make.services) b2make.services = {};
			
			b2make.services.conteiner_height_lines = 3;
			
			$(obj).find('.b2make-widget-out').find('.b2make-services-list').html('');
			var services_ids = $(obj).myAttr('data-services-ids');
			var categoria_id = $(obj).myAttr('data-categoria-id');
			var found_service;
			var link_area = false;
			
			if(services_ids)services_ids = services_ids.split(',');
			
			if($(obj).myAttr('data-acao-click') == 'area'){
				link_area = true;
			}
			
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
			
			if($(obj).myAttr('data-categoria')){
				var categoria = $(obj).myAttr('data-categoria');
			} else {
				var categoria = 'escolha-pontual';
			}
			
			switch(categoria){
				case 'todos-servicos':
					if(servicos){
						var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
						switch(ordem){
							case 'alfabetica-asc':
								servicos.sort(function(a, b){
									if(a.nome < b.nome) return -1;
									if(a.nome > b.nome) return 1;
									return 0;
								});
							break;
							case 'alfabetica-desc':
								servicos.sort(function(a, b){
									if(a.nome > b.nome) return -1;
									if(a.nome < b.nome) return 1;
									return 0;
								});
							break;
							case 'data-asc':
								servicos.sort(function(a, b){
									if(!a.data_criacao)a.data_criacao = 0;
									if(!b.data_criacao)b.data_criacao = 0;
									
									if(a.data_criacao < b.data_criacao) return -1;
									if(a.data_criacao > b.data_criacao) return 1;
									return 0;
								});
							break;
							case 'data-desc':
								servicos.sort(function(a, b){
									if(!a.data_criacao)a.data_criacao = 0;
									if(!b.data_criacao)b.data_criacao = 0;
									
									if(a.data_criacao > b.data_criacao) return -1;
									if(a.data_criacao < b.data_criacao) return 1;
									return 0;
								});
							break;
							case 'data-modificacao-asc':
								servicos.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao < b.data_modificacao) return -1;
									if(a.data_modificacao > b.data_modificacao) return 1;
									return 0;
								});
							break;
							case 'data-modificacao-desc':
								servicos.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao > b.data_modificacao) return -1;
									if(a.data_modificacao < b.data_modificacao) return 1;
									return 0;
								});
							break;
						}
						
						for(var i=0;i<servicos.length;i++){
							$(obj).find('.b2make-widget-out').find('.b2make-services-list').append(servicos_widget_html({
								service:servicos[i],
								link_area:link_area,
								largura_cont:largura_cont,
								altura_cont:altura_cont,
								altura_img:altura_img
							}));
						}
					}
				break;
				case 'escolha-pontual':
					var num = 0;
					if(servicos){
						var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
						switch(ordem){
							case 'alfabetica-asc':
								servicos.sort(function(a, b){
									if(a.nome < b.nome) return -1;
									if(a.nome > b.nome) return 1;
									return 0;
								});
							break;
							case 'alfabetica-desc':
								servicos.sort(function(a, b){
									if(a.nome > b.nome) return -1;
									if(a.nome < b.nome) return 1;
									return 0;
								});
							break;
							case 'data-asc':
								servicos.sort(function(a, b){
									if(!a.data_criacao)a.data_criacao = 0;
									if(!b.data_criacao)b.data_criacao = 0;
									
									if(a.data_criacao < b.data_criacao) return -1;
									if(a.data_criacao > b.data_criacao) return 1;
									return 0;
								});
							break;
							case 'data-desc':
								servicos.sort(function(a, b){
									if(!a.data_criacao)a.data_criacao = 0;
									if(!b.data_criacao)b.data_criacao = 0;
									
									if(a.data_criacao > b.data_criacao) return -1;
									if(a.data_criacao < b.data_criacao) return 1;
									return 0;
								});
							break;
							case 'data-modificacao-asc':
								servicos.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao < b.data_modificacao) return -1;
									if(a.data_modificacao > b.data_modificacao) return 1;
									return 0;
								});
							break;
							case 'data-modificacao-desc':
								servicos.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao > b.data_modificacao) return -1;
									if(a.data_modificacao < b.data_modificacao) return 1;
									return 0;
								});
							break;
						}
						
						for(var i=0;i<servicos.length;i++){
							found_service = false;
							if(services_ids)
							for(var j=0;j<services_ids.length;j++){
								if(services_ids[j] == servicos[i].id){
									found_service = true;
								}
							}
							
							if(!found_service)continue;
							
							$(obj).find('.b2make-widget-out').find('.b2make-services-list').append(servicos_widget_html({
								service:servicos[i],
								link_area:link_area,
								largura_cont:largura_cont,
								altura_cont:altura_cont,
								altura_img:altura_img
							}));
						}
					}
				break;
				case 'categoria':
					var num = 0;
					if(servicos){
						var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
						switch(ordem){
							case 'alfabetica-asc':
								servicos.sort(function(a, b){
									if(a.nome < b.nome) return -1;
									if(a.nome > b.nome) return 1;
									return 0;
								});
							break;
							case 'alfabetica-desc':
								servicos.sort(function(a, b){
									if(a.nome > b.nome) return -1;
									if(a.nome < b.nome) return 1;
									return 0;
								});
							break;
							case 'data-asc':
								servicos.sort(function(a, b){
									if(!a.data_criacao)a.data_criacao = 0;
									if(!b.data_criacao)b.data_criacao = 0;
									
									if(a.data_criacao < b.data_criacao) return -1;
									if(a.data_criacao > b.data_criacao) return 1;
									return 0;
								});
							break;
							case 'data-desc':
								servicos.sort(function(a, b){
									if(!a.data_criacao)a.data_criacao = 0;
									if(!b.data_criacao)b.data_criacao = 0;
									
									if(a.data_criacao > b.data_criacao) return -1;
									if(a.data_criacao < b.data_criacao) return 1;
									return 0;
								});
							break;
							case 'data-modificacao-asc':
								servicos.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao < b.data_modificacao) return -1;
									if(a.data_modificacao > b.data_modificacao) return 1;
									return 0;
								});
							break;
							case 'data-modificacao-desc':
								servicos.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao > b.data_modificacao) return -1;
									if(a.data_modificacao < b.data_modificacao) return 1;
									return 0;
								});
							break;
						}
						
						for(var i=0;i<servicos.length;i++){
							found_service = false;
							
							if(categoria_id)
							if(categoria_id == servicos[i].id_servicos_categorias){
								found_service = true;
							}
							
							if(!found_service)continue;
							
							$(obj).find('.b2make-widget-out').find('.b2make-services-list').append(servicos_widget_html({
								service:servicos[i],
								link_area:link_area,
								largura_cont:largura_cont,
								altura_cont:altura_cont,
								altura_img:altura_img
							}));
						}
					}
				break;
			}
			
			mobile_sitemap({obj:obj});
			
			if($(obj).myAttr('data-widget-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-widget-color-ahex')));
			if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
			if($(obj).myAttr('data-botao-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-botao-color-ahex')));
			if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-name').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
			if($(obj).myAttr('data-descricao-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-descricao').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-descricao-text-color-ahex')));
			if($(obj).myAttr('data-preco-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-preco').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-preco-text-color-ahex')));
			if($(obj).myAttr('data-botao-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-botao-text-color-ahex')));
			
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
					
					var height = ($(obj).myAttr('data-titulo-font-size') ? parseInt($(obj).myAttr('data-titulo-font-size')) : b2make.services.conteiner_height_name) + b2make.services.conteiner_height_lines*($(obj).myAttr('data-descricao-font-size') ? parseInt($(obj).myAttr('data-descricao-font-size')) : b2make.services.conteiner_height_descricao);
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
		}
		
		function servicos_widget_update(p){
			if(!p)p={};
			
			var opcao = 'servicos_widget_update';
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			var id_servicos = $(obj).myAttr('data-services-ids');
			
			var data = { 
				ajax : 'sim'
			};
			
			if($(obj).myAttr('data-categoria')){
				var categoria = $(obj).myAttr('data-categoria');
			} else {
				var categoria = 'escolha-pontual';
			}
			
			if(categoria == 'escolha-pontual'){
				if(id_servicos) data.id_servicos = id_servicos;
			}
			
			$.ajax({
				type: 'POST',
				url: '/platform/services-list',
				data: data,
				beforeSend: function(){
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'OK':
								servicos_widget_update_start({servicos:dados.servicos,obj:obj});
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

		function services_inserir_carrinho_botao(p){
			var loja_id = p.dados.store.id;
			var loja_url_cliente = p.dados.store.loja_url_cliente;
			var servicos = p.dados.servicos;
			
			var debug = getUrlParameter('debug');
			var beta = getUrlParameter('beta');
			
			$('.b2make-service-inserir-carrinho').each(function(){
				var servico_id = $(this).myAttr('data-id');
				var servico_nome = $(this).myAttr('data-nome');
				var servico_preco = $(this).myAttr('data-preco');
				var ativar_link = false;
				
				b2make_ga({
					evento : 'view_item',
					item_id : servico_id,
					item_dados : {
						nome : servico_nome,
						preco : servico_preco
					},
					item_quant:1
				});
				
				if(servicos)
				for(var i=0;i<servicos.length;i++){
					if(servicos[i].id == servico_id && parseInt(servicos[i].quantidade) > 0){
						ativar_link = true;
					}
					
					if(servicos[i].id == servico_id && servicos[i].indisponivel){
						ativar_link = false;
					}
				}
				
				if(ativar_link){
					if(loja_url_cliente == "1"){
						$(this).myAttr('href',b2make.hostname+'cart/add/'+servico_id,'_self');
					} else {
						$(this).myAttr('href','https://'+(beta ? b2make.urlBeta : b2make.url)+'/'+(debug?'teste/':'')+'e-services/'+loja_id+'/cart/add/'+servico_id,'_self');
					}
				}
			});
		}
		
		function services(){
			b2make.services = {};
			
			if(!b2make.msgs.servicosComprar)b2make.msgs.servicosComprar = 'Comprar';
			if(!b2make.msgs.servicosIndisponivel)b2make.msgs.servicosIndisponivel = 'Servi&ccedil;o Indispon&iacute;vel';
			
			var servico_id = '';
			
			$('.b2make-service-inserir-carrinho').each(function(){
				servico_id = servico_id + (servico_id ? ',':'') + $(this).myAttr('data-id');
			});
			
			var opcao = 'services';
			
			$.ajax({
				type: 'POST',
				url: '/platform/services-config',
				data: { 
					ajax : 'sim',
					servico_id : servico_id
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
							case 'OK':
								services_inserir_carrinho_botao({dados:dados});
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
		
		// ==================================== Youtube =============================
		
		function youtube(){
			$('.b2make-youtube-cont').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var url = $(this).parent().parent().myAttr('data-url');
				var titulo = $(this).parent().parent().myAttr('data-titulo');
				
				if(!b2make.start_pretty_photo){
					$.fn.prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false});
					b2make.start_pretty_photo = true;
				}
				$.prettyPhoto.open(url,titulo);
			});
		}
		
		// ==================================== Posts Filter =============================
		
		function posts_filter_resultados_start(p={}){
			var obj = p.obj;
			var conteudos_list = p.dados.conteudos_list;
			var id_plugin = 'posts-filter';
			var selected_tags = new Array();
			
			var cont = $(obj).find('.b2make-widget-out').find('.b2make-'+id_plugin);
			var lista = cont.find('.b2make-'+id_plugin+'-lista');
			var menu = cont.find('.b2make-'+id_plugin+'-menu');
			
			lista.html('');
			
			if($(obj).myAttr('data-layout-tipo') != 'menu'){
				cont.find('.b2make-posts-filter-menu').find('.b2make-posts-filter-cont-2').each(function(){
					var id_tag = $(this).myAttr('data-id');
					var option = $(this).val();
					
					if(option == '-1'){
						selected_tags[id_tag] = false;
					} else {
						selected_tags[id_tag] = option;
					}
				});
				
				var contents_ids = $(obj).myAttr('data-contents-ids');
				var contents_conteudo_tipo_ids = $(obj).myAttr('data-contents-conteudo-tipo-ids');
				
				if(contents_ids)contents_ids = contents_ids.split(',');
				if(contents_conteudo_tipo_ids)contents_conteudo_tipo_ids = contents_conteudo_tipo_ids.split(',');
				
				switch($(obj).myAttr('data-conteudo-tipo')){
					case 'todos-posts':
						if(conteudos_list){
							var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
							switch(ordem){
								case 'alfabetica-asc':
									conteudos_list.sort(function(a, b){
										if(a.nome < b.nome) return -1;
										if(a.nome > b.nome) return 1;
										return 0;
									});
								break;
								case 'alfabetica-desc':
									conteudos_list.sort(function(a, b){
										if(a.nome > b.nome) return -1;
										if(a.nome < b.nome) return 1;
										return 0;
									});
								break;
								case 'data-asc':
									conteudos_list.sort(function(a, b){
										if(!a.data_modificacao)a.data_modificacao = 0;
										if(!b.data_modificacao)b.data_modificacao = 0;
										
										if(a.data_modificacao < b.data_modificacao) return -1;
										if(a.data_modificacao > b.data_modificacao) return 1;
										return 0;
									});
								break;
								case 'data-desc':
									conteudos_list.sort(function(a, b){
										if(!a.data_modificacao)a.data_modificacao = 0;
										if(!b.data_modificacao)b.data_modificacao = 0;
										
										if(a.data_modificacao > b.data_modificacao) return -1;
										if(a.data_modificacao < b.data_modificacao) return 1;
										return 0;
									});
								break;
							}
						}
					break;
					case 'escolha-pontual':
						var conteudos_list_new = new Array();
						
						if(conteudos_list){
							for(var i=0;i<conteudos_list.length;i++){
								found_content = false;
								if(contents_ids)
								for(var j=0;j<contents_ids.length;j++){
									if(contents_ids[j] == conteudos_list[i].id){
										found_content = true;
									}
								}
								
								if(!found_content)continue;
								
								conteudos_list_new.push(conteudos_list[i]);
							}
							
							conteudos_list = conteudos_list_new;
						}
					break;
					case 'conteudo-tipo':
						var conteudos_list_new = new Array();
						
						if(conteudos_list){
							var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
							switch(ordem){
								case 'alfabetica-asc':
									conteudos_list.sort(function(a, b){
										if(a.nome < b.nome) return -1;
										if(a.nome > b.nome) return 1;
										return 0;
									});
								break;
								case 'alfabetica-desc':
									conteudos_list.sort(function(a, b){
										if(a.nome > b.nome) return -1;
										if(a.nome < b.nome) return 1;
										return 0;
									});
								break;
								case 'data-asc':
									conteudos_list.sort(function(a, b){
										if(!a.data_modificacao)a.data_modificacao = 0;
										if(!b.data_modificacao)b.data_modificacao = 0;
										
										if(a.data_modificacao < b.data_modificacao) return -1;
										if(a.data_modificacao > b.data_modificacao) return 1;
										return 0;
									});
								break;
								case 'data-desc':
									conteudos_list.sort(function(a, b){
										if(!a.data_modificacao)a.data_modificacao = 0;
										if(!b.data_modificacao)b.data_modificacao = 0;
										
										if(a.data_modificacao > b.data_modificacao) return -1;
										if(a.data_modificacao < b.data_modificacao) return 1;
										return 0;
									});
								break;
							}
							
							for(var i=0;i<conteudos_list.length;i++){
								found_content = false;
								if(contents_conteudo_tipo_ids)
								for(var j=0;j<contents_conteudo_tipo_ids.length;j++){
									if(contents_conteudo_tipo_ids[j] == conteudos_list[i].id_site_conteudos_tipos){
										found_content = true;
									}
								}
								
								if(!found_content)continue;
								
								conteudos_list_new.push(conteudos_list[i]);
							}
							
							conteudos_list = conteudos_list_new;
						}
					break;
				}
				
				if(conteudos_list){
					var resultados_count = 0;
					
					for(var i=0;i<conteudos_list.length;i++){
						if($(obj).myAttr('data-layout-orientacao') == 'vertical'){
							var ficha_html = $('<div>').append(cont.find('.b2make-'+id_plugin+'-ficha-html-vertical-holder').find('.b2make-posts-filter-conteudo-2').clone());
						} else {
							var ficha_html = $('<div>').append(cont.find('.b2make-'+id_plugin+'-ficha-html-holder').find('.b2make-posts-filter-conteudo').clone());
						}
						
						var conteudo_list = conteudos_list[i];
						var found;
						var imprimir_ficha = true;
						
						$.each(selected_tags, function(index,tag_id){
							found = false;

							if(tag_id){
								if(conteudo_list.tags){
									var tags = conteudo_list.tags;
									
									for(var j=0;j<tags.length;j++){
										if(tags[j].id == tag_id){
											found = true;
											break;
										}
									}
								}
							} else {
								found = true;
							}
							
							if(!found){
								imprimir_ficha = false;
							}
						});
						
						if(imprimir_ficha){
							var meta = (conteudo_list._meta ? conteudo_list._meta : new Array());
							var tags = (conteudo_list.tags ? conteudo_list.tags : new Array());
							
							$.each(conteudo_list, function(campo,valor){
								switch(campo){
									case 'id':
									case 'tags':
									case '_meta':
										//
									break;
									case 'url':
										ficha_html.find('[data-marcador="@conteudo#url"]').myAttr('href',valor);
									break;
									case 'nome':
										ficha_html.find('[data-marcador="@conteudo#nome"]').html(valor);
									break;
									case 'texto':
										ficha_html.find('[data-marcador="@conteudo#texto"]').html(valor);
									break;
									case 'url_imagem_2':
										ficha_html.find('[data-marcador="@conteudo#imagem"]').css('background-image','url('+valor+')');
									break;
									default:
										for(var j=0;j<meta.length;j++){
											if(meta[j].id == campo){
												switch(meta[j].widget){
													case 'texto':
														ficha_html.find('[data-marcador="@'+campo+'#"]').html(valor);
													break;
													case 'imagem':
														if(valor){
															ficha_html.find('[data-marcador="@'+campo+'#"]').css('background-image','url('+b2make.hostname+'files/'+valor+')');
														} else {
															ficha_html.find('[data-marcador="@'+campo+'#"]').remove();
														}
													break;
												}
												
												break;
											}
										}
								}
							});
							
							for(var j=0;j<tags.length;j++){
								if(tags[j].principal){
									var ele = ficha_html.find('[data-marcador="@conteudo#tag-principal"]');
									
									ele.html(tags[j].nome);
									
									if(ele.myAttr('data-cor-fundo')){
										ele.css('background-color','#'+tags[j].cor);
									}
								}
							}
							
							lista.append(ficha_html.html());
							resultados_count++;
						}
					}
					
					if(resultados_count == 0){
						var sem_resultados_html = $('<div>').append(cont.find('.b2make-'+id_plugin+'-sem-resultados-html-holder').find('.b2make-posts-filter-sem-resultados').clone());
						
						if($(obj).myAttr('data-layout-orientacao') != 'vertical'){
							sem_resultados_html.find('.b2make-posts-filter-sem-resultados').height($(obj).height());
						}
						
						lista.html(sem_resultados_html.html());
					}
				}
				
				var widget_height = $(obj).height();
				
				if($(obj).myAttr('data-layout-orientacao') == 'vertical'){
					var menu_height = menu.outerHeight(true);
					var lista_magin = parseInt(lista.css('marginTop'));
					
					var height = Math.floor(widget_height - menu_height - lista_magin);
					
					lista.height(height);
				} else {
					lista.height(widget_height);
				}
			}
		}
		
		function posts_filter_resultados(p={}){
			var obj = p.obj;
			
			var contents_ids = $(obj).myAttr('data-contents-ids');
			var contents_conteudo_tipo_ids = $(obj).myAttr('data-contents-conteudo-tipo-ids');
			var conteudo_tipo = $(obj).myAttr('data-conteudo-tipo');
			
			var opcao = 'posts_filter_resultados';
			
			$.ajax({
				type: 'POST',
				url: '/platform/contents-list',
				data: { 
					ajax : 'sim',
					contents_conteudo_tipo_ids : contents_conteudo_tipo_ids,
					conteudo_tipo : conteudo_tipo,
					contents_ids : contents_ids
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
							case 'OK':
								posts_filter_resultados_start({obj:obj,dados:dados});
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
		
		function posts_filter_widget_menu_html(p){
			if(!p)p={};
			
			var id_plugin = 'posts-filter';
			if($(p.obj).myAttr('data-layout-tipo') == 'menu'){
				var select = $('<select class="b2make-'+id_plugin+'-cont" data-id="'+p.posts_filter.id+'"></select>');
				select.append('<option value="-1">'+p.posts_filter.nome+'</option>');
			} else {
				var posts_filter_cont = $('<div class="b2make-'+id_plugin+'-cont-2-lbl">'+p.posts_filter.nome+':</div>');
				var select = $('<select class="b2make-'+id_plugin+'-cont-2" data-id="'+p.posts_filter.id+'"></select>');
				select.append('<option value="-1">Selecione...</option>');
			}
			
			var tags = b2make.posts_filter_tags_lista;
			var selected_val = '-1';
			
			if($(p.obj).myAttr('data-layout-tipo') != 'menu'){
				var options = b2make.posts_filter.options;
				var optionsProc = new Array();
				
				if(options){
					optionsProc = JSON.parse(options);
				}
				
				for(var i=0;i<optionsProc.length;i++){
					if(p.posts_filter.id == optionsProc[i].id){
						selected_val = optionsProc[i].val;
					}
				}
			}
			
			for(var i=0;i<tags.length;i++){
				if(p.posts_filter.id == tags[i].id_pai){
					select.append('<option value="'+tags[i].id+'"'+(selected_val == tags[i].id ? ' selected="selected"':'')+'>'+tags[i].nome+'</option>');
				}
			}
			
			if($(p.obj).myAttr('data-layout-tipo') == 'menu'){
				return select;
			} else {
				return $('<div>').append(posts_filter_cont.clone()).html()+$('<div>').append(select.clone()).html();
			}
		}

		function posts_filter_widget_update(p){
			if(!p)p={};
			
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			var id_plugin = 'posts-filter';
			
			var cont = $(obj).find('.b2make-widget-out').find('.b2make-'+id_plugin);
			var menu = cont.find('.b2make-'+id_plugin+'-menu');
			var lista = cont.find('.b2make-'+id_plugin+'-lista');
			
			menu.html('');
			
			var posts_filter_ids = $(obj).myAttr('data-'+id_plugin+'-ids');
			var posts_filter_ids_arr;
			
			if(posts_filter_ids)posts_filter_ids_arr = posts_filter_ids.split(',');
			
			if($(obj).myAttr('data-botao-texto')){
				var botao_texto = $(obj).myAttr('data-botao-texto');
			} else {
				var botao_texto = b2make.posts_filter.posts_filterBotaoTexto;
			}
			
			if(b2make.posts_filter_tags_lista){
				var tags = b2make.posts_filter_tags_lista;
				
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
			
			if($(obj).myAttr('data-layout-tipo') == 'menu'){
				menu.myAttr('data-type','menu');
				menu.append('<div class="b2make-posts-filter-btn">'+botao_texto+'</div>');
			} else {
				menu.myAttr('data-type','menu-resultados');
				menu.myAttr('data-orientacao',($(obj).myAttr('data-layout-orientacao') ? $(obj).myAttr('data-layout-orientacao') : 'horizontal'));
				menu.append('<div class="b2make-posts-filter-btn-2">'+botao_texto+'</div>');
				lista.myAttr('data-orientacao',($(obj).myAttr('data-layout-orientacao') ? $(obj).myAttr('data-layout-orientacao') : 'horizontal'));
			}
			
			lista.html('');
			
			posts_filter_resultados({obj:obj});
			
			$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
		}

		function posts_filter_start(json){
			var posts_filter_tags_lista = new Array();
			var tags = (json ? json.tags : false);
			
			if(tags){
				for(var i=0;i<tags.length;i++){
					posts_filter_tags_lista.push({
						nome : tags[i].nome,
						cor : tags[i].cor,
						id : tags[i].id_site_conteudos_tags,
						id_pai : tags[i].id_site_conteudos_tags_pai
					});
				}
			}
			
			b2make.posts_filter_tags_lista = posts_filter_tags_lista;
			
			$('.b2make-widget[data-type="posts-filter"]').each(function(){
				posts_filter_widget_update({obj:this});
			});
		}
		
		function posts_filter(){
			b2make.posts_filter = {};
			
			if(localStorage.getItem('b2make.posts-filter-options')){
				b2make.posts_filter.options = localStorage.getItem('b2make.posts-filter-options');
				localStorage.removeItem('b2make.posts-filter-options');
			}
			
			if(!b2make.posts_filter.posts_filterBotaoTexto)b2make.posts_filter.posts_filterBotaoTexto = 'FILTRAR';
			
			if(b2make.widget_start['posts-filter']){
				var opcao = 'posts_filter';
				
				$.ajax({
					type: 'POST',
					url: '/platform/library/posts-filter',
					data: { 
						ajax : 'sim'
					},
					beforeSend: function(){
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var json = eval('(' + txt + ')');
							
							if(json){
								posts_filter_start(json);
							} else {
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
				
				$(document.body).on('mouseup tap','.b2make-posts-filter-btn-2',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var obj = $(this).parent().parent().parent().parent();
					posts_filter_resultados({obj:obj});
				});
				
				$(document.body).on('mouseup tap','.b2make-posts-filter-btn',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var obj = $(this).parent().parent().parent().parent();
					var url = $(obj).myAttr('data-filtrar-url');
					
					if(url){
						var options = new Array();
						
						$(obj).find('.b2make-posts-filter-cont').each(function(){
							options.push({
								id:$(this).myAttr('data-id'),
								val:$(this).val()
							});
						});
						
						localStorage.setItem('b2make.posts-filter-options', JSON.stringify(options));
						
						window.open(url,'_self');
					} else {
						console.log('ERROR: posts-filter - URLUndefined');
					}
				});
			}
		}
		
		// ==================================== Contents =============================
		
		function contents_widget_setinha_altura(p){
			if(!p)p={};
			
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			
			var next = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next');
			var previous = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous');
			var tamanho = ($(obj).myAttr('data-tamanho-seta') ? $(obj).myAttr('data-tamanho-seta') : 15);
			
			var height = $(obj).find('.b2make-widget-out').find('.b2make-contents').height();
			var top = Math.floor(parseInt(height)/2) - Math.floor(parseInt(tamanho)/2);
			
			next.css('top',top+'px');
			previous.css('top',top+'px');
		}

		function contents_widget_setinha_update(p){
			if(!p)p={};
			
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			
			var next = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next');
			var previous = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous');
			var tamanho = ($(obj).myAttr('data-tamanho-seta') ? $(obj).myAttr('data-tamanho-seta') : 15);
			
			next.css('width',tamanho+'px');
			next.css('height',tamanho+'px');
			next.css('line-height',tamanho+'px');
			next.css('font-size',tamanho+'px');
			
			previous.css('width',tamanho+'px');
			previous.css('height',tamanho+'px');
			previous.css('line-height',tamanho+'px');
			previous.css('font-size',tamanho+'px');
			
			contents_widget_setinha_altura(p);
		}

		function contents_widget_html(p){
			if(!p)p={};
			
			var content_cont;
			var mais_opcoes = true;
			
			var protomatch = /^(https?|ftp):/;
			
			if(p.content.url)p.content.url = p.content.url.replace(protomatch, '');
			if(p.content.url_imagem)p.content.url_imagem = p.content.url_imagem.replace(protomatch, '');
			
			switch(p.layout_tipo){
				case 'padrao':
					if(p.link_area){
						content_cont = $('<a href="'+p.content.url+'" class="b2make-content-cont" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></a>');
					} else {
						content_cont = $('<div class="b2make-content-cont" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></div>');
					}
					
					var imagem = $('<div class="b2make-content-imagem" style="background-image:url('+(p.content.url_imagem ? p.content.url_imagem : '//'+b2make.url+'/site/images/b2make-album-sem-imagem.png')+');"></div>');
					var name = $('<div class="b2make-content-name">'+p.content.nome+'</div>');
					var texto = $('<div class="b2make-content-texto">'+(p.content.texto ? p.content.texto : '')+'</div>');
					var acessar = $('<a class="b2make-content-acessar" href="'+p.content.url+'">'+p.botao_texto+'</a>');
					
					imagem.appendTo(content_cont);
					name.appendTo(content_cont);
					texto.appendTo(content_cont);
					acessar.appendTo(content_cont);
					
					if(p.altura_img){
						imagem.height(p.altura_img);
					}
				break;
				case 'imagem':
					content_cont = $('<a href="'+p.content.url+'" class="b2make-content-cont-2" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></a>');
					
					var imagem = $('<div class="b2make-content-imagem-mask"><div class="b2make-content-imagem-2" style="background-image:url('+(p.content.url_imagem_2 ? p.content.url_imagem_2 : '//'+b2make.url+'/site/images/b2make-album-sem-imagem.png')+');"></div></div>');
					var name = $('<div class="b2make-content-name-2">'+p.content.nome+'</div>');
					var texto = $('<div class="b2make-content-texto-2"'+(p.linhas_descricao ? ' style="max-height:'+(p.linhas_descricao * 15)+'px;"':'')+'>'+(p.content.texto ? p.content.texto : '')+'</div>');
					var texto_cont = $('<div class="b2make-content-texto-cont"></div>');
					
					imagem.appendTo(content_cont);
					name.appendTo(texto_cont);
					texto.appendTo(texto_cont);
					
					if(p.content.tags){
						var tags = p.content.tags;
						var principal;
						
						for(var i=0;i<tags.length;i++){
							if(!principal)principal = tags[0];
							
							if(tags[i].principal){
								principal = tags[i];
								break;
							}
						}
						
						var tag_cont = $('<div class="b2make-content-tag-cont" style="border-bottom:12px solid #'+principal.cor+'; color: #'+principal.cor+';">'+principal.nome+'</div>');
						
						tag_cont.appendTo(texto_cont);
					}
					
					texto_cont.appendTo(content_cont);
					
					if(p.altura_textos){
						texto_cont.height(p.altura_textos);
					}
				break;
				case 'menu':
					content_cont = $('<a href="'+p.content.url+'" class="b2make-content-cont-2" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></a>');
					
					var imagem = $('<div class="b2make-content-imagem-mask"><div class="b2make-content-imagem-2" style="background-image:url('+(p.content.url_imagem_2 ? p.content.url_imagem_2 : '//'+b2make.url+'/site/images/b2make-album-sem-imagem.png')+');"></div></div>');
					var name = $('<div class="b2make-content-name-2">'+p.content.nome+'</div>');
					var texto = $('<div class="b2make-content-texto-2"'+(p.linhas_descricao ? ' style="max-height:'+(p.linhas_descricao * 15)+'px;"':'')+'>'+(p.content.texto ? p.content.texto : '')+'</div>');
					var texto_cont = $('<div class="b2make-content-texto-cont"></div>');
					var acessar = $('<div class="b2make-content-acessar-mask"><a class="b2make-content-acessar-2" href="'+p.content.url+'">'+p.botao_texto+'</a></div>');
					
					imagem.appendTo(content_cont);
					name.appendTo(texto_cont);
					texto.appendTo(texto_cont);
					acessar.appendTo(content_cont);
					
					texto_cont.appendTo(content_cont);
					
					if(p.altura_textos){
						texto_cont.height(p.altura_textos);
					}
				break;
				case 'mosaico':
					mais_opcoes = false;
					
					if(p.num > 3) break;
					
					content_cont = $('<a href="'+p.content.url+'" class="b2make-content-cont-3" data-pos="'+(p.num+1)+'" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></a>');
					
					var imagem = $('<div class="b2make-content-imagem-mask"><div class="b2make-content-imagem-2" style="background-image:url('+(p.content.url_imagem_2 ? p.content.url_imagem_2 : '//'+b2make.url+'/site/images/b2make-album-sem-imagem.png')+');"></div></div>');
					var name = $('<div class="b2make-content-name-2">'+p.content.nome+'</div>');
					var texto = $('<div class="b2make-content-texto-2"'+(p.linhas_descricao ? ' style="max-height:'+(p.linhas_descricao * 15)+'px;"':'')+'>'+(p.content.texto ? p.content.texto : '')+'</div>');
					var texto_cont = $('<div class="b2make-content-texto-cont"></div>');
					
					imagem.appendTo(content_cont);
					name.appendTo(texto_cont);
					texto.appendTo(texto_cont);
					texto_cont.appendTo(content_cont);
					
					if(p.altura_textos){
						texto_cont.height(p.altura_textos);
					}
					
				break;
				case 'lista-texto':
					mais_opcoes = false;
					
					content_cont = $('<a class="b2make-content-cont-4" href="'+p.content.url+'">'+p.content.nome+'</a>');
					
				break;
				
			}
			
			if(mais_opcoes){
				if(p.largura_cont){
					content_cont.width(p.largura_cont);
				}
				
				if(p.altura_cont){
					content_cont.height(p.altura_cont);
				}
				
				if(p.margem){
					content_cont.css('margin',p.margem+'px');
				}
			}
			
			return content_cont;
		}
		
		function contents_widget_update_resize(p = {}){
			var obj = p.obj;
			
			if($(obj).myAttr('data-layout-tipo') == 'mosaico'){
				var width = $(obj).width();
				var height = $(obj).height();
				var margem = ($(obj).myAttr('data-margem') ? parseInt($(obj).myAttr('data-margem')) : 10);
				
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').each(function(){
					var pos = $(this).myAttr('data-pos');
					var col_width = Math.ceil((width-2*margem)/3);
					var line_height = Math.ceil((height-margem)/2);
					
					switch(pos){
						case '1':
							$(this).css('width',col_width+'px');
							$(this).css('height',height+'px');
							$(this).css('top','0px');
							$(this).css('left','0px');
						break;
						case '2':
							$(this).css('width',col_width+'px');
							$(this).css('height',line_height+'px');
							$(this).css('top','0px');
							$(this).css('left',(col_width+margem)+'px');
						break;
						case '3':
							$(this).css('width',col_width+'px');
							$(this).css('height',line_height+'px');
							$(this).css('top','0px');
							$(this).css('left',(2*(col_width+margem))+'px');
						break;
						case '4':
							$(this).css('width',((2*col_width)+margem)+'px');
							$(this).css('height',line_height+'px');
							$(this).css('top',(line_height+margem)+'px');
							$(this).css('left',(col_width+margem)+'px');
						break;
						
					}
				});
			}
		}

		function contents_widget_update_start(p){
			var conteudos_list = p.dados.conteudos_list;
			var obj = p.obj;
			var link_area;
			
			$(obj).find('.b2make-widget-out').find('.b2make-contents').html('');
			var contents_ids = $(obj).myAttr('data-contents-ids');
			var contents_conteudo_tipo_ids = $(obj).myAttr('data-contents-conteudo-tipo-ids');
			var found_content;
			
			$(obj).find('.b2make-widget-out').find('.b2make-contents').append('<div class="b2make-content-next">&#10095;</div>');
			$(obj).find('.b2make-widget-out').find('.b2make-contents').append('<div class="b2make-content-previous">&#10094;</div>');
			
			if(contents_ids)contents_ids = contents_ids.split(',');
			if(contents_conteudo_tipo_ids)contents_conteudo_tipo_ids = contents_conteudo_tipo_ids.split(',');
			
			if($(obj).myAttr('data-acao-click') == 'area'){
				link_area = true;
			}
			
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
			
			if($(obj).myAttr('data-margem-seta')){
				var margem_seta = $(obj).myAttr('data-margem-seta');
			} else {
				var margem_seta = 15;
			}
			
			if($(obj).myAttr('data-botao-texto')){
				var botao_texto = $(obj).myAttr('data-botao-texto');
			} else {
				var botao_texto = b2make.msgs.contentsBotaoTexto;
			}
			
			if($(obj).myAttr('data-layout-tipo')){
				var layout_tipo = $(obj).myAttr('data-layout-tipo');
			} else {
				var layout_tipo = 'padrao';
			}
			
			if($(obj).myAttr('data-altura-textos-cont')){
				var altura_textos = $(obj).myAttr('data-altura-textos-cont');
			} else {
				var altura_textos = false;
			}

			if($(obj).myAttr('data-linhas-descricao')){
				var linhas_descricao = parseInt($(obj).myAttr('data-linhas-descricao'));
			} else {
				var linhas_descricao = 3;
			}
			
			switch($(obj).myAttr('data-conteudo-tipo')){
				case 'todos-posts':
					if(conteudos_list){
						var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
						switch(ordem){
							case 'alfabetica-asc':
								conteudos_list.sort(function(a, b){
									if(a.nome < b.nome) return -1;
									if(a.nome > b.nome) return 1;
									return 0;
								});
							break;
							case 'alfabetica-desc':
								conteudos_list.sort(function(a, b){
									if(a.nome > b.nome) return -1;
									if(a.nome < b.nome) return 1;
									return 0;
								});
							break;
							case 'data-asc':
								conteudos_list.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao < b.data_modificacao) return -1;
									if(a.data_modificacao > b.data_modificacao) return 1;
									return 0;
								});
							break;
							case 'data-desc':
								conteudos_list.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao > b.data_modificacao) return -1;
									if(a.data_modificacao < b.data_modificacao) return 1;
									return 0;
								});
							break;
						}
						
						var num = 0;
						for(var i=0;i<conteudos_list.length;i++){
							$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
								linhas_descricao:linhas_descricao,
								num:num,
								altura_textos:altura_textos,
								layout_tipo:layout_tipo,
								margem:margem,
								botao_texto:botao_texto,
								content:conteudos_list[i],
								largura_cont:largura_cont,
								altura_cont:altura_cont,
								altura_img:altura_img,
								link_area:link_area
							}));
							num++;
						}
					}
				break;
				case 'escolha-pontual':
					var num = 0;
					if(conteudos_list){
						var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
						switch(ordem){
							case 'alfabetica-asc':
								conteudos_list.sort(function(a, b){
									if(a.nome < b.nome) return -1;
									if(a.nome > b.nome) return 1;
									return 0;
								});
							break;
							case 'alfabetica-desc':
								conteudos_list.sort(function(a, b){
									if(a.nome > b.nome) return -1;
									if(a.nome < b.nome) return 1;
									return 0;
								});
							break;
							case 'data-asc':
								conteudos_list.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao < b.data_modificacao) return -1;
									if(a.data_modificacao > b.data_modificacao) return 1;
									return 0;
								});
							break;
							case 'data-desc':
								conteudos_list.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao > b.data_modificacao) return -1;
									if(a.data_modificacao < b.data_modificacao) return 1;
									return 0;
								});
							break;
						}
						
						for(var i=0;i<conteudos_list.length;i++){
							found_content = false;
							if(contents_ids)
							for(var j=0;j<contents_ids.length;j++){
								if(contents_ids[j] == conteudos_list[i].id){
									found_content = true;
								}
							}
							
							if(!found_content)continue;
							
							$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
								linhas_descricao:linhas_descricao,
								num:num,
								altura_textos:altura_textos,
								layout_tipo:layout_tipo,
								margem:margem,
								botao_texto:botao_texto,
								content:conteudos_list[i],
								largura_cont:largura_cont,
								altura_cont:altura_cont,
								altura_img:altura_img,
								link_area:link_area
							}));
							num++;
						}
					}
				break;
				case 'conteudo-tipo':
					var num = 0;
					if(conteudos_list){
						var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
						switch(ordem){
							case 'alfabetica-asc':
								conteudos_list.sort(function(a, b){
									if(a.nome < b.nome) return -1;
									if(a.nome > b.nome) return 1;
									return 0;
								});
							break;
							case 'alfabetica-desc':
								conteudos_list.sort(function(a, b){
									if(a.nome > b.nome) return -1;
									if(a.nome < b.nome) return 1;
									return 0;
								});
							break;
							case 'data-asc':
								conteudos_list.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao < b.data_modificacao) return -1;
									if(a.data_modificacao > b.data_modificacao) return 1;
									return 0;
								});
							break;
							case 'data-desc':
								conteudos_list.sort(function(a, b){
									if(!a.data_modificacao)a.data_modificacao = 0;
									if(!b.data_modificacao)b.data_modificacao = 0;
									
									if(a.data_modificacao > b.data_modificacao) return -1;
									if(a.data_modificacao < b.data_modificacao) return 1;
									return 0;
								});
							break;
						}
						
						for(var i=0;i<conteudos_list.length;i++){
							found_content = false;
							if(contents_conteudo_tipo_ids)
							for(var j=0;j<contents_conteudo_tipo_ids.length;j++){
								if(contents_conteudo_tipo_ids[j] == conteudos_list[i].id_site_conteudos_tipos){
									found_content = true;
								}
							}
							
							if(!found_content)continue;
							
							$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
								linhas_descricao:linhas_descricao,
								num:num,
								altura_textos:altura_textos,
								layout_tipo:layout_tipo,
								margem:margem,
								botao_texto:botao_texto,
								content:conteudos_list[i],
								largura_cont:largura_cont,
								altura_cont:altura_cont,
								altura_img:altura_img,
								link_area:link_area
							}));
							num++;
						}
					}
				break;
			}
			
			mobile_sitemap({obj:obj});
			
			if(layout_tipo == 'menu'){
				if($(obj).find('.b2make-widget-out').find('.b2make-menu-tags').length == 0)$(obj).find('.b2make-widget-out').prepend('<div class="b2make-menu-tags"></div>');
				
				$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').html('');
				
				var menu_tags = $(obj).find('.b2make-widget-out').find('.b2make-menu-tags');
				var contents_tags = ($(obj).myAttr('data-contents-tags-ids') ? $(obj).myAttr('data-contents-tags-ids') : '');
				var contents_tags_arr = (contents_tags ? contents_tags.split(',') : new Array());
				var tag_id = '-1';
				
				if($(obj).myAttr('data-menu-largura-cont')){
					var menu_largura_cont = $(obj).myAttr('data-menu-largura-cont');
				} else {
					var menu_largura_cont = 200;
				}
				
				if($(obj).myAttr('data-menu-altura-cont')){
					var menu_altura_cont = $(obj).myAttr('data-menu-altura-cont');
				} else {
					var menu_altura_cont = 80;
				}
				
				if($(obj).myAttr('data-menu-margem-cont')){
					var menu_margem_cont = $(obj).myAttr('data-menu-margem-cont');
				} else {
					var menu_margem_cont = 5;
				}
				
				if($(obj).myAttr('data-menu-botao-largura-cont')){
					var menu_botao_largura_cont = $(obj).myAttr('data-menu-botao-largura-cont');
				} else {
					var menu_botao_largura_cont = 100;
				}
				
				if($(obj).myAttr('data-menu-botao-altura-cont')){
					var menu_botao_altura_cont = $(obj).myAttr('data-menu-botao-altura-cont');
				} else {
					var menu_botao_altura_cont = 40;
				}
				
				if($(obj).myAttr('data-menu-botao-margem-cont')){
					var menu_botao_margem_cont = $(obj).myAttr('data-menu-botao-margem-cont');
				} else {
					var menu_botao_margem_cont = 30;
				}
				
				if(contents_tags_arr){
					var conteudos_tags_lista = b2make.conteudos_tags_lista;
					var cor_atual = '';
					
					for(var i=0;i<contents_tags_arr.length;i++){
						for(var j=0;j<conteudos_tags_lista.length;j++){
							if(conteudos_tags_lista[j].id == contents_tags_arr[i]){
								var cor = conteudos_tags_lista[j].cor;
								var nome = conteudos_tags_lista[j].nome;
								
								if(i == (b2make.contents.tag_id_atual ? b2make.contents.tag_id_atual : 0)){
									tag_id = contents_tags_arr[i];
									b2make.contents.cor_atual = cor_atual = cor;
									menu_tags.append($('<div class="b2make-menu-tag" style="border-bottom: solid 5px #'+cor+'; color: #'+cor+'; width: '+menu_largura_cont+'px; line-height: '+menu_altura_cont+'px; margin: '+menu_margem_cont+'px;" data-id="'+contents_tags_arr[i]+'" data-cor="'+cor+'" data-atual="sim">'+nome+'</div>'));
								} else {
									menu_tags.append($('<div class="b2make-menu-tag" style="width: '+menu_largura_cont+'px; line-height: '+menu_altura_cont+'px; margin: '+menu_margem_cont+'px;" data-id="'+contents_tags_arr[i]+'" data-cor="'+cor+'">'+nome+'</div>'));
								}
							}
						}
					}
					
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').each(function(){
						var id = $(this).myAttr('data-id');
						
						if(conteudos_list)
						for(var i=0;i<conteudos_list.length;i++){
							var conteudo_list = conteudos_list[i];
							var found = false;
							
							if(id == conteudo_list.id){
								if(conteudo_list.tags){
									var tags = conteudo_list.tags;
									
									for(var j=0;j<tags.length;j++){
										if(tags[j].id == tag_id){
											found = true;
											break;
										}
									}
								}
								
								if(found){
									$(this).show();
									$(this).myAttr('data-show',true);
									$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('background-color','#'+cor_atual);
									$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').width(menu_botao_largura_cont);
									$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('line-height',menu_botao_altura_cont+'px');
									$(this).find('.b2make-content-acessar-mask').css('bottom','-'+menu_botao_margem_cont+'px');
									$(this).css('marginBottom',(parseInt(menu_botao_margem_cont)+parseInt(menu_botao_altura_cont))+'px');
								} else {
									$(this).hide();
								}
							}
						}
					});
				}
			} else {
				$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').remove();
			}
			
			$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').css('right',margem_seta+'px');
			$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').css('left',margem_seta+'px');
			
			contents_widget_setinha_update({obj:obj});
			
			if($(obj).myAttr('data-widget-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-widget-color-ahex')));
			if($(obj).myAttr('data-seta-color-ahex')){
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-seta-color-ahex')));
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-seta-color-ahex')));
			}
			
			switch(layout_tipo){
				case 'padrao':
					if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
					if($(obj).myAttr('data-botao-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-botao-color-ahex')));
					if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
					if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
					if($(obj).myAttr('data-botao-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-botao-text-color-ahex')));
				break;
				case 'imagem':
					if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
					if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
					if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
					if($(obj).myAttr('data-texto-textos-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
				break;
				case 'menu':
					if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
					if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
					if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
					if($(obj).myAttr('data-texto-textos-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
					if($(obj).myAttr('data-menu-color-ahex')){
						$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-menu-color-ahex')));
						$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('border-bottom','solid 5px '+jpicker_ahex_2_rgba($(obj).myAttr('data-menu-color-ahex')));
					}
					if($(obj).myAttr('data-menu-text-color-ahex')){
						$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-menu-text-color-ahex')));
						b2make.contents.cor_padrao = jpicker_ahex_2_rgba($(obj).myAttr('data-menu-text-color-ahex'));
					} else {
						b2make.contents.cor_padrao = '#606060';
					}
					if($(obj).myAttr('data-menu-botao-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-menu-botao-color-ahex')));
					
				break;
				case 'mosaico':
					if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
					if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
					if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
					if($(obj).myAttr('data-texto-textos-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
			
					contents_widget_update_resize({obj:obj});
				break;
				
			}
			
			var ids = new Array('titulo','texto','botao','tags','menu','menu-botao');
			var mudar_height = false;
			var target;
			
			for(var i=0;i<ids.length;i++){
				var id = ids[i];
				
				mudar_height = false;
				
				switch(layout_tipo){
					case 'padrao':
						switch(id){
							case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name'); mudar_height = true; break;
							case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto'); mudar_height = true; break;
							case 'botao': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar'); break;
						}
					break;
					case 'imagem':
						switch(id){
							case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
							case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
							case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
						}
					break;
					case 'menu':
						switch(id){
							case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
							case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
							case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
							case 'menu': target = $(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag'); break;
							case 'menu-botao': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2'); break;
						}
					break;
					case 'mosaico':
						switch(id){
							case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
							case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
							case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
						}
					break;
					case 'lista-texto':
						switch(id){
							case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name'); mudar_height = true; break;
							case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto'); mudar_height = true; break;
							case 'botao': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar'); break;
						}
					break;
				}
				
				if($(obj).myAttr('data-'+id+'-font-family'))target.css('fontFamily',$(obj).myAttr('data-'+id+'-font-family'));
				if($(obj).myAttr('data-'+id+'-font-size')){
					target.css('fontSize',$(obj).myAttr('data-'+id+'-font-size')+'px');
					
					var height = b2make.contents.conteiner_height_lines*($(obj).myAttr('data-titulo-font-size') ? parseInt($(obj).myAttr('data-titulo-font-size')) : b2make.contents.conteiner_height_name) + b2make.contents.conteiner_height_lines*($(obj).myAttr('data-texto-font-size') ? parseInt($(obj).myAttr('data-texto-font-size')) : b2make.contents.conteiner_height_texto);
					height = height + b2make.contents.conteiner_height_default;
					
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('height',height+'px');
					
					if(mudar_height){
						var line_height = parseInt($(obj).myAttr('data-'+id+'-font-size')) + b2make.contents.conteiner_height_margin;
						target.css('line-height',line_height+'px');
						
						target.css('max-height',(line_height*b2make.contents.conteiner_height_lines)+'px');
					}
				}
				if($(obj).myAttr('data-'+id+'-font-align'))target.css('textAlign',$(obj).myAttr('data-'+id+'-font-align'));
				if($(obj).myAttr('data-'+id+'-font-italico'))target.css('fontStyle',($(obj).myAttr('data-'+id+'-font-italico') == 'sim' ? 'italic' : 'normal'));
				if($(obj).myAttr('data-'+id+'-font-negrito'))target.css('fontWeight',($(obj).myAttr('data-'+id+'-font-negrito') == 'sim' ? 'bold' : 'normal'));
			}
			
			if(layout_tipo == 'mosaico' || layout_tipo == 'lista-texto'){
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').hide();
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').hide();
			} else {
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').show();
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').show();
			}
			
			$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
		}
		
		function contents_widget_update(p){
			if(!p)p={};
			
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			
			var contents_ids = $(obj).myAttr('data-contents-ids');
			var contents_conteudo_tipo_ids = $(obj).myAttr('data-contents-conteudo-tipo-ids');
			var conteudo_tipo = $(obj).myAttr('data-conteudo-tipo');
			
			var opcao = 'posts_filter_resultados';
			
			var data = { 
				ajax : 'sim'
			};
			
			if(contents_conteudo_tipo_ids) data.contents_conteudo_tipo_ids = contents_conteudo_tipo_ids;
			if(conteudo_tipo) data.conteudo_tipo = conteudo_tipo;
			if(contents_ids) data.contents_ids = contents_ids;
			
			$.ajax({
				type: 'POST',
				url: '/platform/contents-list',
				data: data,
				beforeSend: function(){
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'OK':
								contents_widget_update_start({obj:obj,dados:dados});
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
		
		function contents_start(json){
			var conteudos_tags_lista = new Array();
			var tags = (json ? json.tags : false);
			
			if(tags){
				for(var i=0;i<tags.length;i++){
					conteudos_tags_lista.push({
						nome : tags[i].nome,
						cor : tags[i].cor,
						id : tags[i].id_site_conteudos_tags,
						id_pai : tags[i].id_site_conteudos_tags_pai
					});
				}
			}
			
			b2make.conteudos_tags_lista = conteudos_tags_lista;
			
			$('.b2make-widget[data-type="contents"]').each(function(){
				$(this).find('.b2make-widget-out').find('.b2make-widget-loading').show();
				$(this).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').html('&#10095;');
				$(this).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').html('&#10094;');
				contents_widget_update({obj:this});
			});
		}
		
		function contents(){
			b2make.contents = {};
			
			if(!b2make.msgs.contentsBotaoTexto)b2make.msgs.contentsBotaoTexto = 'Leia Mais';
			
			b2make.contents.conteiner_height_default = 220;
			b2make.contents.conteiner_height_lines = 3;
			b2make.contents.conteiner_height_margin = 2;
			b2make.contents.conteiner_height_name = 18;
			b2make.contents.conteiner_height_texto = 13;
			
			if(b2make.widget_start['contents']){
				var opcao = 'contents';
				
				$.ajax({
					type: 'POST',
					url: '/platform/library/posts-filter',
					data: { 
						ajax : 'sim'
					},
					beforeSend: function(){
					},
					success: function(txt){
						if(
							txt.charAt(0) == "{" || 
							txt.charAt(0) == "["
						){
							var json = eval('(' + txt + ')');
							
							if(json){
								contents_start(json);
							} else {
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
				
				$(document.body).on('mouseup tap','.b2make-content-next',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var widget = $(this).parent().parent().parent();
					var obj = $(this).parent();
					
					switch(widget.myAttr('data-layout-tipo')){
						case 'menu':
							obj.find('.b2make-content-cont-2:visible').first().appendTo(obj);
						break;
						default:
							obj.find('.b2make-content-cont').first().appendTo(obj);
							obj.find('.b2make-content-cont-2').first().appendTo(obj);
					}
				});
				
				$(document.body).on('mouseup tap','.b2make-content-previous',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var widget = $(this).parent().parent().parent();
					var obj = $(this).parent();
					
					switch(widget.myAttr('data-layout-tipo')){
						case 'menu':
							obj.find('.b2make-content-cont-2:visible').last().prependTo(obj);
						break;
						default:
							obj.find('.b2make-content-cont').last().prependTo(obj);
							obj.find('.b2make-content-cont-2').last().prependTo(obj);
					}
				});
				
				$(document.body).on('mouseenter','.b2make-content-imagem-2',function(e){
					$(this).addClass('b2make-img-zoom-transition');
				});
				
				$(document.body).on('mouseleave','.b2make-content-imagem-2',function(e){
					$(this).removeClass('b2make-img-zoom-transition');
				});
				
				$(document.body).on('mouseenter','.b2make-menu-tag',function(e){
					if(!$(this).myAttr('data-atual')){
						var cor_padrao = b2make.contents.cor_padrao;
						var cor_atual = b2make.contents.cor_atual;
						var cor = ($(this).myAttr('data-cor') ? '#'+$(this).myAttr('data-cor') : cor_padrao);
						
						$(this).css('color',cor);
						$(this).css('border-bottom','solid 5px '+cor);
					}
				});
				
				$(document.body).on('mouseleave','.b2make-menu-tag',function(e){
					if(!$(this).myAttr('data-atual')){
						var cor_padrao = b2make.contents.cor_padrao;
						
						$(this).css('color',cor_padrao);
						$(this).css('border-bottom','solid 5px #e1e1e1');
					}
				});
				
				$(document.body).on('mouseup tap','.b2make-menu-tag',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var obj = $(this).parent().parent().parent().get(0);
					
					var contents_tags = ($(obj).myAttr('data-contents-tags-ids') ? $(obj).myAttr('data-contents-tags-ids') : '');
					var contents_tags_arr = (contents_tags ? contents_tags.split(',') : new Array());
					
					var id = $(this).myAttr('data-id');
					
					for(var i=0;i<contents_tags_arr.length;i++){
						if(id == contents_tags_arr[i]){
							b2make.contents.tag_id_atual = i;
							
							$(this).parent().find('.b2make-menu-tag').each(function(){
								$(this).removeAttr('data-atual');
							});
							$(this).myAttr('data-atual','sim');
							
							contents_widget_update({obj:obj});
							break;
						}
					}
				});
			}
		}
		
		// ==================================== Formulários =============================
		
		$.bordas_update = function(p){
			var borda_name = 'data-bordas-todas';
			
			if(p.borda_name){
				borda_name = p.borda_name;
			} else {
				if($(p.obj).myAttr('data-borda-name')){
					borda_name = $(p.obj).myAttr('data-borda-name');
				}
			}
			
			var todas = $(p.obj).myAttr(borda_name);
			
			if(!todas) todas = '0;solid;rgb(0,0,0);0;000000ff';
			
			var todas_arr = todas.split(';');
			
			p.target.css('border',todas_arr[0]+'px '+todas_arr[1]+' '+todas_arr[2]);
			p.target.css('-webkit-border-radius',todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px');
			p.target.css('border-radius',todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px');
		}
		
		function formularios_widgets_update(p){
			if(!p)p = {};
			
			var plugin_id = 'formularios';
			
			$('.b2make-widget').each(function(){
				switch($(this).myAttr('data-type')){
					case plugin_id:
						var obj = this;
						var widget = $(obj).find('.b2make-widget-out').find('.b2make-widget-formularios');
						var id = $(obj).myAttr('id');
						var id_referencia = $(obj).myAttr('data-referencia-id');
						
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
								}
							break;
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
									label = $('<label for="'+campos[i].campo+'">'+campos[i].title+'</label>');
									
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
													var option = $('<div class="b2make-formularios-checkbox-cont"><input type="checkbox" id="'+campos[i].campo+(j+1)+'" value="'+campo_opcoes[j].id+'" data-campo="'+campos[i].campo+'" name="'+campos[i].campo+'_'+(j+1)+'"><label for="'+campos[i].campo+(j+1)+'">'+campo_opcoes[j].nome+'</label></div>');
													input.append(option);
												}
											}
										break;
									}
									
									if(campos[i].obrigatorio) input.myAttr('data-obrigatorio',true);
									
									widget.append(label);
									widget.append(input);
								}
								
								input = $('<input type="hidden" id="_b2make-form-id" name="_b2make-form-id" value="'+id_referencia+'">');
								
								widget.append(input);
								
								var button = $('<input type="button" class="b2make-formularios-button" value="'+b2make.msgs.formulariosButtonValue+'">');
								widget.append(button);
								
								$(obj).find('.b2make-widget-out').find('.b2make-library-loading').hide();
								
								if(p.widget_add){
									return;
								}
								
								// Colors
								
								if($(obj).myAttr('data-caixa-color-ahex')){
									var bg = jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex'));
									
									$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('background-color',bg);
									$(obj).find('.b2make-widget-out').find('form').find('textarea').css('background-color',bg);
								}
								
								if($(obj).myAttr('data-caixa-color-2-ahex')){
									var bg = jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-2-ahex'));
									
									$(obj).find('.b2make-widget-out').find('form').find('input[type="button"]').css('background-color',bg);
								}
								
								if($(obj).myAttr('data-rotulo-color-ahex')){
									var bg = jpicker_ahex_2_rgba($(obj).myAttr('data-rotulo-color-ahex'));
									
									$(obj).find('.b2make-widget-out').find('form').find('label').css('color',bg);
								}
								
								if($(obj).myAttr('data-preenchimento-color-ahex')){
									var bg = jpicker_ahex_2_rgba($(obj).myAttr('data-preenchimento-color-ahex'));
									
									$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('color',bg);
									$(obj).find('.b2make-widget-out').find('form').find('textarea').css('color',bg);
								}
								
								if($(obj).myAttr('data-botao-color-ahex')){
									var bg = jpicker_ahex_2_rgba($(obj).myAttr('data-botao-color-ahex'));
									
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
								
								var todas = $(obj).myAttr('data-borda-caixa');
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
											case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(obj).myAttr('data-'+type+'-font-family')); break;
											case 'changeFontSize': cssVar = 'fontSize';  target.css(cssVar,$(obj).myAttr('data-'+type+'-font-size')+'px'); target.css('line-height',$(obj).myAttr('data-'+type+'-font-size')+'px');
												var size = parseInt($(obj).myAttr('data-'+type+'-font-size')); target.css('padding',Math.floor(size/2.5)+'px '+Math.floor(size/3)+'px'); 
												if(!noSize){
													target.css('width','calc(100% - '+(Math.floor(size/2.5))+'px)');
												}
											break;
											case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(obj).myAttr('data-'+type+'-font-align')); break;
											case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(obj).myAttr('data-'+type+'-font-italico') == 'sim' ? 'italic' : 'normal')); break;
											case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(obj).myAttr('data-'+type+'-font-negrito') == 'sim' ? 'bold' : 'normal')); break;
										}	
									}
								}							
								
						}
						
					break;
				}
			});
		}
		
		function formularios_widgets_update_start(p){
			var json = p.json;
			
			if(json)
			for(var i=0;i<json.length;i++){
				b2make.formularios.campos[json[i].id] = json[i].campos;
			}
			
			formularios_widgets_update(null);
		}
		
		function formularios_widgets_start(p){
			if(!p)p = {};
			
			var opcao = 'formularios_widgets_start';
			
			$.ajax({
				type: 'POST',
				url: '/platform/library/formularios',
				data: { 
					ajax : 'sim'
				},
				beforeSend: function(){
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var json = eval('(' + txt + ')');
						
						if(json){
							formularios_widgets_update_start({json:json});
						} else {
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
		
		function formularios(){
			b2make.formularios = {};
			b2make.formularios.campos = new Array();
			
			if(!b2make.msgs.formulariosButtonValue)b2make.msgs.formulariosButtonValue = 'Enviar';
			
			if($('.b2make-widget-formularios').length > 0){
				formularios_widgets_start(null);
			}
			
			$(document.body).on('mouseover','.b2make-widget-form_contato input[type="button"],.b2make-widget-formularios input[type="button"]',function(e){
				var pai = $(this).parent().parent().parent();
				var color = '#FFF';
				var bg = '#464E56';
				
				if(pai.myAttr('data-botao-color-ahex')){
					bg = jpicker_ahex_2_rgba(pai.myAttr('data-botao-color-ahex'));
				}
				if(pai.myAttr('data-caixa-color-2-ahex')){
					color = jpicker_ahex_2_rgba(pai.myAttr('data-caixa-color-2-ahex'));
				}

				$(this).css('color',color);
				$(this).css('background-color',bg);
			});
			
			$(document.body).on('mouseout','.b2make-widget-form_contato input[type="button"],.b2make-widget-formularios input[type="button"]',function(e){
				var pai = $(this).parent().parent().parent();
				var color = '#58585B';
				var bg = '#D7D9DD';
				
				if(pai.myAttr('data-botao-color-ahex')){
					color = jpicker_ahex_2_rgba(pai.myAttr('data-botao-color-ahex'));
				}
				if(pai.myAttr('data-caixa-color-2-ahex')){
					bg = jpicker_ahex_2_rgba(pai.myAttr('data-caixa-color-2-ahex'));
				}

				$(this).css('color',color);
				$(this).css('background-color',bg);
			});
			
			$(document.body).on('mouseup tap','.b2make-widget-form_contato input[type="button"]',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				var pai = $(this).parent().parent().parent();
				var opcao = 'FormContato';
				var enviar = true;
				var campo_obrigatorio = false;
				var bg_caixa;
				var color_caixa;
				var bg_erro = '#FF0000';
				var color_erro = '#FFFFFF';
				
				if(pai.myAttr('data-caixa-color-ahex')){
					bg_caixa = jpicker_ahex_2_rgba(pai.myAttr('data-caixa-color-ahex'));
				}
				if(pai.myAttr('data-preenchimento-color-ahex')){
					color_caixa = jpicker_ahex_2_rgba(pai.myAttr('data-preenchimento-color-ahex'));
				}
				
				$(this).parent().find('.b2make-formulario-obrigatorio').remove();
				$(this).parent().find('.b2make-formulario-enviado').remove();
				$(this).parent().find('input[type="text"],textarea').each(function(){
					if(bg_caixa){
						$(this).css('background-color',bg_caixa);
					}
					if(color_caixa){
						$(this).css('color',color_caixa);
					}
					
					$(this).removeClass('b2make-formulario-erro');
					if(!$(this).val()){ 
						enviar = false; 
						$(this).addClass('b2make-formulario-erro'); 
						campo_obrigatorio = true;
						
						if(bg_caixa){
							$(this).css('background-color',bg_erro);
						}
						if(color_caixa){
							$(this).css('color',color_erro);
						}
					}
					
					if($(this).myAttr('name') == 'form_contato-email'){
						if(!checkMail($(this).val())){ 
							enviar = false; 
							$(this).addClass('b2make-formulario-erro');
							
							if(bg_caixa){
								$(this).css('background-color',bg_erro);
							}
							if(color_caixa){
								$(this).css('color',color_erro);
							}
						}
					}
				});
				
				if(!enviar){
					$(this).parent().parent().parent().stop().effect( "shake" );
					$(this).before($('<div class="b2make-formulario-obrigatorio">'+(campo_obrigatorio ? b2make.formularioObrigatorioText : b2make.formularioEmailInvalido)+'</div>'));
				} else {
					var pub_id = $(this).parent().myAttr('data-pub-id');
					var url = 'https://'+b2make.url+'/webservices/formulario-contato/';
					var params = 'pub_id='+pub_id+'&'+$(this).parent().serialize();
					var obj = $(this).parent();
					
					obj.append('<div class="b2make-carregando"></div>');
					
					$.ajax({
						type: 'POST',
						url: url + '?' + params,
						data: params,
						dataType: "json",
						beforeSend: function(){
							
						},
						success: function(txt){
							var dados = txt;
							
							switch(dados.status){
								case 'Ok':
									obj.find('input[type="button"]').before($('<div class="b2make-formulario-enviado">'+b2make.formularioEmailEnviado+'</div>'));
									obj.get(0).reset();
								break;
								default:
									console.log('ERROR - '+opcao+' - '+dados.status);
								
							}
							
							obj.find('.b2make-carregando').remove();
						},
						error: function(txt){
							console.log('ERROR AJAX - '+opcao+' - '+txt);
							obj.find('.b2make-carregando').remove();
						}
					});
				}
			});
			
			$(document.body).on('mouseup tap','.b2make-formularios-button',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var form = $(this).parent();
				var pai = form.parent().parent();
				var opcao = 'FormContato';
				var enviar = true;
				var campo_obrigatorio = false;
				var bg_caixa;
				var color_caixa;
				var bg_erro = '#FF0000';
				var color_erro = '#FFFFFF';
				
				if(pai.myAttr('data-caixa-color-ahex')){
					bg_caixa = jpicker_ahex_2_rgba(pai.myAttr('data-caixa-color-ahex'));
				}
				if(pai.myAttr('data-preenchimento-color-ahex')){
					color_caixa = jpicker_ahex_2_rgba(pai.myAttr('data-preenchimento-color-ahex'));
				}
				
				form.find('.b2make-formulario-obrigatorio').remove();
				form.find('.b2make-formulario-enviado').remove();
				form.find('input[type="text"],textarea').each(function(){
					if(bg_caixa){
						$(this).css('background-color',bg_caixa);
					}
					if(color_caixa){
						$(this).css('color',color_caixa);
					}
					
					$(this).removeClass('b2make-formulario-erro');
					if(!$(this).val() && $(this).myAttr('data-obrigatorio')){ 
						enviar = false; 
						$(this).addClass('b2make-formulario-erro'); 
						campo_obrigatorio = true;
						
						if(bg_caixa){
							$(this).css('background-color',bg_erro);
						}
						if(color_caixa){
							$(this).css('color',color_erro);
						}
					}
				});
				
				if(!enviar){
					pai.stop().effect( "shake" );
					$(this).before($('<div class="b2make-formulario-obrigatorio">'+(campo_obrigatorio ? b2make.formularioObrigatorioText : b2make.formularioEmailInvalido)+'</div>'));
				} else {
					var beta = getUrlParameter('beta');
					
					var pub_id = form.myAttr('data-pub-id');
					var url = 'https://'+(beta ? b2make.urlBeta: b2make.url)+'/webservices/formulario-contato/';
					var params = 'pub_id='+pub_id+'&'+form.serialize();
					
					form.append('<div class="b2make-carregando"></div>');
					
					$.ajax({
						type: 'POST',
						url: url + '?' + params,
						data: params,
						dataType: "json",
						beforeSend: function(){
							
						},
						success: function(txt){
							var dados = txt;
							
							switch(dados.status){
								case 'Ok':
									form.find('input[type="button"]').before($('<div class="b2make-formulario-enviado">'+b2make.formularioEmailEnviado+'</div>'));
									form.get(0).reset();
								break;
								default:
									console.log('ERROR - '+opcao+' - '+dados.status);
								
							}
							
							form.find('.b2make-carregando').remove();
						},
						error: function(txt){
							console.log('ERROR AJAX - '+opcao+' - '+txt);
							console.log(txt);
							form.find('.b2make-carregando').remove();
						}
					});
				}
			});
		}
		
		// ==================================== Album de Fotos =============================
		
		function album_fotos_mudar_foto_mini(p){
			
		}
		
		function album_fotos_mudar_foto(p){
			if(b2make.album_fotos.animating) return false;
			
			var foto_id = p.id;
			var obj = p.obj;
			var imagem_cont = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont');
			var imagem_url_1 = imagem_cont.find('.b2make-albumfotos-widget-image-2').myAttr('data-imagem');
			var imagem_url_2 = imagem_cont.find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini[data-id="'+foto_id+'"]').myAttr('data-imagem');
			var album_id = imagem_cont.find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini[data-id="'+foto_id+'"]').myAttr('data-album-fotos-id');
			var holder;
			var width = imagem_cont.find('.b2make-albumfotos-widget-image-2').width();
			var tempo_transicao = b2make.album_fotos.tempo_transicao;
			var efeito = b2make.album_fotos.efeito;
			var descricao = '';
			
			if(imagem_url_1 == imagem_url_2) return false;
			
			$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry').each(function(){
				$(this).removeAttr('data-status');
			});
			
			$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry[data-album-fotos-id="'+album_id+'"]').myAttr('data-status','selected');
			
			$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini').each(function(){
				if($(this).myAttr('data-id') == foto_id){
					if($(this).myAttr('data-descricao')){
						descricao = $(this).myAttr('data-descricao');
					}
					return false;
				}
				
				$(this).appendTo($(this).parent());
			});
			
			if(!b2make.album_fotos.animation_holder[obj]){
				var height = imagem_cont.find('.b2make-albumfotos-widget-image-2').height();
				var left = imagem_cont.find('.b2make-albumfotos-widget-image-2').css('left');
				var top = imagem_cont.find('.b2make-albumfotos-widget-image-2').css('top');
				
				holder = b2make.album_fotos.animation_holder[obj] = $('<div class="b2make-albumfotos-animation-holder"></div>');
				
				var imagem1 = $('<div class="b2make-albumfotos-imagem1"></div>');
				var imagem2 = $('<div class="b2make-albumfotos-imagem2"></div>');
				
				imagem1.css('width',width+'px');
				imagem1.css('height',height+'px');
				imagem2.css('width',width+'px');
				imagem2.css('height',height+'px');
				imagem2.css('left',width+'px');
				
				imagem1.appendTo(holder);
				imagem2.appendTo(holder);
				
				holder.css('width',(2*width)+'px');
				holder.css('height',height+'px');
				holder.css('top','0px');
				holder.css('left','0px');
				
				holder.appendTo(imagem_cont);
				holder.hide();
			} else {
				holder = b2make.album_fotos.animation_holder[obj];
			}
			
			if(p.animation_option){
				b2make.album_fotos.animation_option = p.animation_option;
			}
			
			var animation_option = b2make.album_fotos.animation_option;
			
			b2make.album_fotos.animating = true;
			
			switch(animation_option){
				case 'slide-left':
					holder.find('.b2make-albumfotos-imagem1').css('background-image','url('+imagem_url_1+')');
					holder.find('.b2make-albumfotos-imagem2').css('background-image','url('+imagem_url_2+')');
					holder.show();
					imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
					holder.css('left','0px');
					
					holder.stop().animate({
						left: (-width)+'px'
					}, tempo_transicao,efeito, function() {
						imagem_cont.find('.b2make-albumfotos-widget-image-2').css('background-image','url('+imagem_url_2+')');
						imagem_cont.find('.b2make-albumfotos-widget-image-2').myAttr('data-imagem',imagem_url_2);
						imagem_cont.find('.b2make-albumfotos-widget-images-descricao').html(descricao);
						if(descricao.length > 0){
							imagem_cont.find('.b2make-albumfotos-widget-images-descricao').show();
						} else {
							imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
						}
						b2make.album_fotos.animating = false;
						holder.hide();
					});
				break;
				case 'slide-right':
					holder.find('.b2make-albumfotos-imagem1').css('background-image','url('+imagem_url_2+')');
					holder.find('.b2make-albumfotos-imagem2').css('background-image','url('+imagem_url_1+')');
					holder.show();
					imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
					holder.css('left',(-width)+'px');
					
					holder.stop().animate({
						left: '0px'
					}, tempo_transicao,efeito, function() {
						imagem_cont.find('.b2make-albumfotos-widget-image-2').css('background-image','url('+imagem_url_2+')');
						imagem_cont.find('.b2make-albumfotos-widget-image-2').myAttr('data-imagem',imagem_url_2);
						imagem_cont.find('.b2make-albumfotos-widget-images-descricao').html(descricao);
						if(descricao.length > 0){
							imagem_cont.find('.b2make-albumfotos-widget-images-descricao').show();
						} else {
							imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
						}
						b2make.album_fotos.animating = false;
						holder.hide();
					});
				break;
			}
		}
		
		function album_fotos(){
			b2make.album_fotos = {};
			
			b2make.album_fotos.animating = false;
			b2make.album_fotos.animation_holder = new Array();
			b2make.album_fotos.tempo_transicao = 500;
			b2make.album_fotos.efeito = 'linear';
			b2make.album_fotos.animation_option = 'slide-left';
			b2make.album_fotos.legenda = new Array();
			b2make.album_fotos.legenda_margin_left_tit = 12;
			
			$('.b2make-widget[data-type="albumfotos"]').each(function(){
				var found = false;
				var tipo = false;
				var id = $(this).myAttr('id');
				var ajuste_top = 5;
				
				var attr = $(this).hasAttr('data-layout-tipo');

				if(attr){
					tipo = $(this).myAttr('data-layout-tipo');
				} else {
					tipo = "padrao";
				}
				
				
				if(tipo == "padrao"){
					b2make.album_fotos.legenda[id] = {};
					
					$(this).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder').find('.b2make-albumfotos-widget-image').each(function(){
						if($(this).hasAttr('data-album-fotos-legenda')){
							found = true;
						}
					});
					
					$(this).find('.b2make-widget-out').append('<div class="b2make-albumfotos-legenda-cont"></div>');
					
					if(found){
						$(this).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder').find('.b2make-albumfotos-widget-image').each(function(){
							var width = $(this).width();
							var height_tit = $(this).find('.b2make-albumfotos-widget-titulo').outerHeight(true);
							var font_size_tit = $(this).find('.b2make-albumfotos-widget-titulo').css('font-size');
							
							if(typeof $(this).parent().parent().parent().myAttr('data-font-family') !== typeof undefined && $(this).parent().parent().parent().myAttr('data-font-family') !== false) {
								var font_family_tit = $(this).parent().parent().parent().myAttr('data-font-family');
							} else {
								var font_family_tit = 'Roboto Condensed';
							}
							
							if(typeof $(this).parent().parent().parent().myAttr('data-text-color-ahex') !== typeof undefined && $(this).parent().parent().parent().myAttr('data-text-color-ahex') !== false) {
								var font_color_tit = $(this).parent().parent().parent().myAttr('data-text-color-ahex');
							} else {
								var font_color_tit = '#ffffff';
							}
							
							if(typeof $(this).parent().parent().parent().myAttr('data-legenda-color-ahex') !== typeof undefined && $(this).parent().parent().parent().myAttr('data-legenda-color-ahex') !== false) {
								var bg_color_legenda = $(this).parent().parent().parent().myAttr('data-legenda-color-ahex');
							} else {
								var bg_color_legenda = '#ededed';
							}
							
							var margin_left_tit = b2make.album_fotos.legenda_margin_left_tit;
							
							$(this).css('marginBottom','48px');
							if($(this).hasAttr('data-album-fotos-legenda')){
								var legenda = $(this).myAttr('data-album-fotos-legenda');
								var cont = $('<div class="b2make-albumfotos-legenda-btn" data-legenda="'+legenda+'">Ver Legenda</div>');
								
								cont.css('top',width+height_tit+ajuste_top);
								cont.css('width',width-2*margin_left_tit);
								cont.css('font-size',font_size_tit);
								cont.css('font-family',font_family_tit);
								cont.css('color',jpicker_ahex_2_rgba(font_color_tit));
								cont.css('background-color',jpicker_ahex_2_rgba(bg_color_legenda));
								
								$(this).append(cont);
							}
						});
					}
				}
			});
			
			$(document.body).on('mouseup tap','.b2make-albumfotos-widget-image-mini',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).myAttr('data-id');
				var obj = $(this).parent().parent().parent().parent().parent().parent().get(0);
				
				album_fotos_mudar_foto({id:id,obj:obj});
			});
			
			$(document.body).on('mouseup tap','.b2make-albumfotos-widget-right-arrow',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var obj = $(this).parent().parent().parent().parent().parent().get(0);
				var imagem = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini:nth-child(2)');
				
				var id = imagem.myAttr('data-id');
				
				album_fotos_mudar_foto({id:id,obj:obj,animation_option:'slide-left'});
			});
			
			$(document.body).on('mouseup tap','.b2make-albumfotos-widget-left-arrow',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var obj = $(this).parent().parent().parent().parent().parent().get(0);
				var imagem = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini:last-child');
				
				var id = imagem.myAttr('data-id');
				
				album_fotos_mudar_foto({id:id,obj:obj,animation_option:'slide-right'});
			});
			
			$(document.body).on('mouseup tap','.b2make-albumfotos-widget-menu-entry',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var album_id = $(this).myAttr('data-album-fotos-id');
				var obj = $(this).parent().parent().parent().parent().get(0);
				var id = '';
				
				$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini').each(function(){
					if($(this).myAttr('data-album-fotos-id') == album_id){
						id = $(this).myAttr('data-id');
						return false;
					}
				});
				
				album_fotos_mudar_foto({id:id,obj:obj});
			});
			
			$(document.body).on('mouseup tap','.b2make-albumfotos-legenda-btn',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = $(this).parent().parent().parent().parent().myAttr('id');
				var album_id = $(this).parent().myAttr('data-album-fotos-id');
				
				if(!b2make.album_fotos.legenda[id].conteiners){
					b2make.album_fotos.legenda[id].conteiners = new Array();
				}
				
				if(!b2make.album_fotos.legenda[id].conteiners[album_id]){
					b2make.album_fotos.legenda[id].conteiners[album_id] = $('<div class="b2make-albumfotos-legenda-cont" id="b2make-albumfotos-legenda-cont-'+id+'-'+album_id+'"></div>');
					$(this).parent().parent().parent().append(b2make.album_fotos.legenda[id].conteiners[album_id]);
				}
				
				var ajuste_top = 20;
				var legenda = $(this).myAttr('data-legenda');
				var top = parseInt($(this).parent().position().top) + parseInt($(this).parent().css('marginTop')) + parseInt($(this).position().top) + parseInt($(this).parent().css('marginTop')) + ajuste_top;
				var left = parseInt($(this).parent().position().left) + parseInt($(this).parent().css('marginLeft'));
				
				var cont = b2make.album_fotos.legenda[id].conteiners[album_id];
				var margin_left_tit = b2make.album_fotos.legenda_margin_left_tit;
				var width = $(this).parent().width();
				var font_size_tit = parseInt($(this).parent().find('.b2make-albumfotos-widget-titulo').css('font-size')) - 2;
				
				if(typeof $(this).parent().parent().parent().parent().myAttr('data-font-family') !== typeof undefined && $(this).parent().parent().parent().parent().myAttr('data-font-family') !== false) {
					var font_family_tit = $(this).parent().parent().parent().parent().myAttr('data-font-family');
				} else {
					var font_family_tit = 'Roboto Condensed';
				}
				
				if(typeof $(this).parent().parent().parent().parent().myAttr('data-text-color-ahex') !== typeof undefined && $(this).parent().parent().parent().parent().myAttr('data-text-color-ahex') !== false) {
					var font_color_tit = $(this).parent().parent().parent().parent().myAttr('data-text-color-ahex');
				} else {
					var font_color_tit = '#ffffff';
				}
				
				if(typeof $(this).parent().parent().parent().parent().myAttr('data-legenda-color-ahex') !== typeof undefined && $(this).parent().parent().parent().parent().myAttr('data-legenda-color-ahex') !== false) {
					var bg_color_legenda = $(this).parent().parent().parent().parent().myAttr('data-legenda-color-ahex');
				} else {
					var bg_color_legenda = '#ededed';
				}
				
				cont.css('top',top);
				cont.css('left',left);
				cont.css('width',width-2*margin_left_tit);
				cont.css('font-size',font_size_tit);
				cont.css('font-family',font_family_tit);
				cont.css('color',jpicker_ahex_2_rgba(font_color_tit));
				cont.css('background-color',jpicker_ahex_2_rgba(bg_color_legenda));
				
				cont.html(legenda);
				
				cont.slideToggle('fast');
			});
			
		}
		
		// ==================================== Conteiner Banners =============================
		
		function conteiner_banners_caixa_posicao_atualizar(p){
			var obj = p.obj;
			var imagem;
			
			if(p.proximo){
				imagem = $(p.proximo);
			} else {
				$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').each(function(){
					imagem = $(this);
					return false;
				});
			}
			
			var margem_seta = parseInt(($(obj).myAttr('data-seta-margem') ? $(obj).myAttr('data-seta-margem') : '100'));
			var tamanho_seta = parseInt(($(obj).myAttr('data-seta-tamanho') ? $(obj).myAttr('data-seta-tamanho') : '100'));
			
			var seta_left = $(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left');
			var seta_right = $(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right');
			
			seta_left.css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			seta_right.css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			seta_left.find('svg').css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			seta_right.find('svg').css('width',tamanho_seta+'px').css('height',tamanho_seta+'px');
			
			seta_left.css('left',margem_seta+'px');
			seta_right.css('right',margem_seta+'px');
		}

		function conteiner_banners_animation_proximo(p){
			var obj = p.obj;
			var inverso = p.inverso;
			var interacao = b2make.conteiner_animation_interacao[$(obj).myAttr('id')];
			var tempo_exposicao = ($(obj).myAttr('data-tempo-exposicao') ? parseInt($(obj).myAttr('data-tempo-exposicao')) : 5000);
			var url = $(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').first().myAttr('data-url');
			
			if($(obj).find('.b2make-conteiner-area').length > 0){
				var area_centralizada = $(obj).find('.b2make-conteiner-area').first();
				
				area_centralizada.off();
				area_centralizada.css('cursor','default');
				
				if(url){
					area_centralizada.css('cursor','pointer');
					
					$(area_centralizada).on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						
						window.open(url,'_self');
					});
					
					setTimeout(function(){
						area_centralizada.css('cursor','pointer');
					},10);
				}
			} else {
				var conteiner = $(obj);
				
				conteiner.off();
				conteiner.css('cursor','default');
				
				if(url){
					conteiner.css('cursor','pointer');
					
					$(conteiner).on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						
						window.open(url,'_self');
					});
					
					setTimeout(function(){
						conteiner.css('cursor','pointer');
					},10);
				}
			}
			
			setTimeout(function(){
				if(interacao == b2make.conteiner_animation_interacao[$(obj).myAttr('id')])
					conteiner_banners_animation_start({obj:obj,inverso:inverso});
			},tempo_exposicao);
		}
		
		function conteiner_banners_animation_start(p){
			var obj = p.obj;
			var inverso = p.inverso;
			var atual;
			var proximo;
			var found_flag = false;
			var proxima_flag = false;
			var count = 0;
			var width = $(obj).outerWidth();
			var height = $(obj).outerHeight();
			var tempo_transicao = ($(obj).myAttr('data-tempo-transicao') ? parseInt($(obj).myAttr('data-tempo-transicao')) : 500);
			var tipo = ($(obj).myAttr('data-animation-type') ? $(obj).myAttr('data-animation-type') : 'slideRight');
			var efeito = ($(obj).myAttr('data-ease-type') ? $(obj).myAttr('data-ease-type') : 'linear');
			var cont_hide = '#b2make-conteiner-banners-lista-images-hide';
			var holder = $(obj).find('.b2make-conteiner-banners-holder');
			
			if(inverso){
				holder.find('.b2make-conteiner-banners-image').each(function(){
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
				holder.find('.b2make-conteiner-banners-image').each(function(){
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
						
						conteiner_banners_animation_proximo({obj:obj,inverso:inverso});
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
						
						conteiner_banners_animation_proximo({obj:obj,inverso:inverso});
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
						
						conteiner_banners_animation_proximo({obj:obj,inverso:inverso});
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
						
						conteiner_banners_animation_proximo({obj:obj,inverso:inverso});
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
						
						conteiner_banners_animation_proximo({obj:obj,inverso:inverso});
					});
				break;
				
			}
		}
		
		function conteiner_banners(){
			b2make.conteiner_animation_interacao = new Array();
			
			$('.b2make-conteiner-banners-image').each(function(){
				$(this).myAttr('data-animation-imagem-atual','nao');
				$(this).css('cursor','');
			});
			
			$('.b2make-widget[data-type="conteiner"]').each(function(){
				var obj = this;
				
				if($(obj).myAttr('data-banners-id')){
					b2make.conteiner_animation_interacao[$(obj).myAttr('id')] = 0;
					conteiner_banners_animation_proximo({obj:obj,inverso:true});
				}
			});
			
			$('.b2make-conteiner-banners-seta-right').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var obj = $(this).parent().parent().get(0);
				
				b2make.conteiner_animation_interacao[$(obj).myAttr('id')]++;
				conteiner_banners_animation_start({obj:obj,inverso:true});
				
				e.stopPropagation();
			});
			
			$('.b2make-conteiner-banners-seta-left').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var obj = $(this).parent().parent().get(0);
				
				b2make.conteiner_animation_interacao[$(obj).myAttr('id')]++;
				conteiner_banners_animation_start({obj:obj});
				
				e.stopPropagation();
			});
		}
		
		// ==================================== Banners =============================
		
		function banners_caixa_posicao_atualizar(p){
			var obj = p.obj;
			var imagem;
			
			if(p.proximo){
				imagem = $(p.proximo);
			} else {
				$(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').each(function(){
					imagem = $(this);
					return false;
				});
			}
			
			var padding = ($(obj).myAttr('data-titulo-padding') ? $(obj).myAttr('data-titulo-padding') : '10');
			var topo = ($(obj).myAttr('data-titulo-topo') ? $(obj).myAttr('data-titulo-topo') : '290');
			var margem = ($(obj).myAttr('data-titulo-margem') ? $(obj).myAttr('data-titulo-margem') : '20');
			var wv = parseInt((parseInt(imagem.myAttr('data-image-width')) * parseInt($(obj).outerHeight())) / parseInt(imagem.myAttr('data-image-height')));
			var tamanho = Math.floor(wv - 2*parseInt(margem));
			var left = Math.floor((parseInt($(obj).outerWidth()) - tamanho)/2 - parseInt(padding));
			
			imagem.find('.b2make-conteiner-banners-image-cont').each(function(){
				$(this).css('top',topo+'px');
				$(this).css('padding',padding+'px');
				$(this).css('left',left+'px');
				$(this).css('width',tamanho+'px');
			});
			
			if(p.criar){
				$(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').find('.b2make-conteiner-banners-image-cont').each(function(){
					$(this).css('top',topo+'px');
					$(this).css('padding',padding+'px');
					$(this).css('left',left+'px');
					$(this).css('width',tamanho+'px');
				});
			} else {
				imagem.find('.b2make-conteiner-banners-image-cont').each(function(){
					$(this).css('top',topo+'px');
					$(this).css('padding',padding+'px');
					$(this).css('left',left+'px');
					$(this).css('width',tamanho+'px');
				});
			}
			
			var topo_seta = ($(obj).myAttr('data-seta-topo') ? $(obj).myAttr('data-seta-topo') : '150');
			var margem_seta = parseInt(($(obj).myAttr('data-seta-margem') ? $(obj).myAttr('data-seta-margem') : '20'));
			var tamanho_seta = parseInt(($(obj).myAttr('data-seta-tamanho') ? $(obj).myAttr('data-seta-tamanho') : '30'));
			
			var seta_left = $(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left');
			var seta_right = $(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right');
			
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

		function banners_animation_proximo(p){
			var obj = p.obj;
			var inverso = p.inverso;
			var interacao = b2make.animation_interacao[$(obj).myAttr('id')];
			var tempo_exposicao = ($(obj).myAttr('data-tempo-exposicao') ? parseInt($(obj).myAttr('data-tempo-exposicao')) : 3000);
			
			setTimeout(function(){
				if(interacao == b2make.animation_interacao[$(obj).myAttr('id')])
					banners_animation_start({obj:obj,inverso:inverso});
			},tempo_exposicao);
		}
		
		function banners_animation_start(p){
			var obj = p.obj;
			var inverso = p.inverso;
			var atual;
			var proximo;
			var found_flag = false;
			var proxima_flag = false;
			var count = 0;
			var width = $(obj).outerWidth();
			var height = $(obj).outerHeight();
			var tempo_transicao = ($(obj).myAttr('data-tempo-transicao') ? parseInt($(obj).myAttr('data-tempo-transicao')) : 500);
			var tipo = ($(obj).myAttr('data-animation-type') ? $(obj).myAttr('data-animation-type') : 'slideRight');
			var efeito = ($(obj).myAttr('data-ease-type') ? $(obj).myAttr('data-ease-type') : 'linear');
			var cont_hide = '#b2make-conteiner-banners-lista-images-hide';
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
		
		function banners(){
			b2make.animation_interacao = new Array();
			
			$('.b2make-banners-widget-image').each(function(){
				$(this).myAttr('data-animation-imagem-atual','nao');
			});
			
			$('.b2make-widget[data-type="banners"]').each(function(){
				var obj = this;
				
				b2make.animation_interacao[$(obj).myAttr('id')] = 0;
				banners_animation_proximo({obj:obj});
			});
			
			$('.b2make-banners-widget-seta-right,.b2make-banners-widget-seta-2-right').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				if($(this).hasClass('b2make-banners-widget-seta-2-right')){
					var obj = $(this).parent().parent().parent().parent().get(0);
				} else {
					var obj = $(this).parent().parent().parent().get(0);
				}
				
				b2make.animation_interacao[$(obj).myAttr('id')]++;
				banners_animation_start({obj:obj});
			});
			
			$('.b2make-banners-widget-seta-left,.b2make-banners-widget-seta-2-left').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				if($(this).hasClass('b2make-banners-widget-seta-2-left')){
					var obj = $(this).parent().parent().parent().parent().get(0);
				} else {
					var obj = $(this).parent().parent().parent().get(0);
				}
				
				b2make.animation_interacao[$(obj).myAttr('id')]++;
				banners_animation_start({obj:obj,inverso:true});
			});
		}
		
		// ==================================== Botão Accordion =============================
		
		function accordion_area_animate(p = {}){
			var obj_widget = p.obj_widget;
			var conteiner_area = (obj_widget.parent().myAttr('data-type') == "conteiner-area" ? true : false);
			var obj_area = (obj_widget.parent().myAttr('data-type') == "conteiner-area" ? obj_widget.parent().parent() : obj_widget.parent());
			
			var area_margin_bottom = 20;
			var id_widget = obj_widget.myAttr('id');
			var height_text_cont = obj_widget.find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-texto-cont').outerHeight(true);
			var height_widget = obj_widget.outerHeight(true);
			var height_area = obj_area.outerHeight(true);
			var top_widget = obj_widget.position().top;
			var height_original_area = 0;
			var height_finish = 0;
			
			if(typeof obj_area.attr('data-area-height-original') !== typeof undefined && obj_area.attr('data-area-height-original') !== false) {
				height_original_area = parseInt(obj_area.myAttr('data-area-height-original'));
			} else {
				obj_area.myAttr('data-area-height-original',height_area);
				height_original_area = parseInt(height_area);
			}
			
			
			if(!b2make.accordion.widgets[id_widget].open){
				if(height_text_cont + height_widget + top_widget > height_area){
					height_finish = area_margin_bottom + height_text_cont + height_widget + top_widget;
				}
			} else {
				if(conteiner_area){
					var obj_finder = obj_area.find('.b2make-conteiner-area');
				} else {
					var obj_finder = obj_area;
				}
				
				var height_finish = height_original_area;
				
				obj_finder.find('.b2make-widget[data-type="accordion"]').each(function(){
					var id = $(this).myAttr('id');
					
					if(id != id_widget){
					
						if(b2make.accordion.widgets[id].open){
							var height_text_cont_aux = $(this).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-texto-cont').outerHeight(true);
							var height_widget_aux = $(this).outerHeight(true);
							var top_widget_aux = $(this).position().top;
							
							if(height_text_cont_aux + height_widget_aux + top_widget_aux > height_finish){
								height_finish = area_margin_bottom + height_text_cont_aux + height_widget_aux + top_widget_aux;
							}
						}
					}
				});
			}
			
			if(height_finish > 0){
				if(b2make.mobile.active){
					obj_area.height(height_finish);
					setTimeout(function(){
						tecnologia_posicionar();
					},200);
				} else {
					obj_area.stop().animate({height:height_finish}, 'fast' , 'swing', function() {
						tecnologia_posicionar();
					});
				}
			}
		}
		
		function accordion_open(p = {}){
			var obj = p.obj;
			
			var id = obj.myAttr('id');
			var cont = obj.find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-texto-cont');
			
			accordion_area_animate({obj_widget:obj});
			if(b2make.mobile.active){
				cont.toggle('fast',function(){
					tecnologia_posicionar();
				});
			} else {
				cont.slideToggle('fast');
			}
			
			b2make.accordion.widgets[id].open = true;
		}
		
		function accordion_close(p = {}){
			var obj = p.obj;
			
			var id = obj.myAttr('id');
			var cont = obj.find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-texto-cont');
			
			accordion_area_animate({obj_widget:obj});
			cont.slideToggle('fast');
			
			b2make.accordion.widgets[id].open = false;
		}
		
		function accordion_toggle(p = {}){
			var obj = p.obj;
			var id = obj.myAttr('id');
			
			if(!b2make.accordion.widgets[id]){
				b2make.accordion.widgets[id] = {};
				
				obj.css('overflow','visible');
			}
			
			if(!b2make.accordion.widgets[id].open){
				accordion_open({
					obj:obj
				});
			} else {
				accordion_close({
					obj:obj
				});
			}
		}
		
		function accordion(){
			b2make.accordion = {};
			
			b2make.accordion.widgets = new Array();
			
			$('.b2make-widget[data-type="accordion"]').hover(
				function(){
					var obj = this;
					
					if(typeof $(obj).myAttr('data-accordion-preenchimento-2-color-ahex') !== typeof undefined && $(obj).myAttr('data-accordion-preenchimento-2-color-ahex') !== false){
						$(obj).css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-accordion-preenchimento-2-color-ahex')));
					} else {
						$(obj).css('background-color','#686868');
					}
					
					var texto_cont = $(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-table').find('.b2make-accordion-cel');
					
					if(typeof $(obj).myAttr('data-accordion-texto-2-color-ahex') !== typeof undefined && $(obj).myAttr('data-accordion-texto-2-color-ahex') !== false){
						texto_cont.css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-accordion-texto-2-color-ahex')));
					} else {
						texto_cont.css('color','#ededed');
					}
				},
				function(){
					var obj = this;
					
					if(typeof $(obj).myAttr('data-accordion-preenchimento-color-ahex') !== typeof undefined && $(obj).myAttr('data-accordion-preenchimento-color-ahex') !== false){
						$(obj).css('background-color',jpicker_ahex_2_rgba($(obj).myAttr('data-accordion-preenchimento-color-ahex')));
					} else {
						$(obj).css('background-color','#434142');
					}
					
					var texto_cont = $(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-table').find('.b2make-accordion-cel');
					
					if(typeof $(obj).myAttr('data-accordion-texto-color-ahex') !== typeof undefined && $(obj).myAttr('data-accordion-texto-color-ahex') !== false){
						texto_cont.css('color',jpicker_ahex_2_rgba($(obj).myAttr('data-accordion-texto-color-ahex')));
					} else {
						texto_cont.css('color','#ffffff');
					}
				}
			);
			
			$('.b2make-accordion').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				accordion_toggle({obj:$(this).parent().parent()});
			});
			
			$('.b2make-accordion-texto-cont').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				e.stopPropagation();
			});
		}
		
		// ==================================== Widget Loja =============================
		
		function widget_loja_start(p){
			var widget_loja = p.dados.store.widget_loja;
			var loja_id = p.dados.store.id;
			var loja_url_cliente = p.dados.store.loja_url_cliente;
			
			if(widget_loja){
				var beta = getUrlParameter('beta');
				var url = (loja_url_cliente == "1" ? b2make.hostname : 'https://'+(beta ? b2make.urlBeta : b2make.url)+'/e-services/'+loja_id+'/');
				
				var widget_loja_cont = $('<div id="b2make-widget-loja"'+(b2make.mobile.active ? ' class="b2make-widget-loja-mobile"': '')+'></div>');
				
				var dados_pessoais = $('<a id="b2make-widget-loja-dados-pessoais" href="'+url+'account"'+(b2make.mobile.active ? ' class="b2make-widget-loja-dados-pessoais-mobile"': '')+'></a>');
				var carrinho = $('<a id="b2make-widget-loja-carrinho" href="'+url+'cart"'+(b2make.mobile.active ? ' class="b2make-widget-loja-carrinho-mobile"': '')+'></a>');
				
				dados_pessoais.appendTo(widget_loja_cont);
				carrinho.appendTo(widget_loja_cont);
				
				widget_loja_cont.appendTo('body');
			}
		}
		
		function widget_loja(){
			var opcao = 'widget_loja';
			
			$.ajax({
				type: 'POST',
				url: '/platform/services-config',
				data: { 
					ajax : 'sim'
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
							case 'OK':
								widget_loja_start({dados:dados});
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
		
		// ==================================== Instagram =============================
		
		function instagram_widget_update(p){
			if(!p)p={};
			
			var opcao = 'instagram_widget_update';
			
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			var count = parseInt(($(obj).myAttr('data-numero-posts') ? $(obj).myAttr('data-numero-posts') : 1));
			
			if(count < 1)count = 1;
			if(count > 16)count = 16;
			
			if(!$(obj).myAttr('data-numero-posts'))$(obj).myAttr('data-numero-posts',count);
			
			var params = 'access_token='+b2make.instagram_token+'&count='+count;
			
			$.ajax({
				type: 'POST',
				url: 'https://api.instagram.com/v1/users/self/media/recent/' + '?' + params,
				data: params,
				dataType: "jsonp",
				beforeSend: function(){
					
				},
				success: function(txt){
					var dados = txt;
					var first = true;
					
					if(dados.data){
						if(count == 1){
							instagram_post({
								url : dados.data[0].link,
								id : dados.data[0].id,
								obj : obj
							});
						} else {
							for(var i=0;i<dados.data.length;i++){
								instagram_images({
									url : dados.data[i].link,
									image : dados.data[i].images.standard_resolution.url,
									id : dados.data[i].id,
									obj : obj,
									first : first
								});
								
								first = false;
								
								if(count <= i){
									break;
								}
							}
							
							var numero = $(obj).myAttr('data-tamanho-imagens');
							
							if(numero){
								$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').find('.b2make-instagram-posts').each(function(){
									$(this).css('margin',Math.floor(((15*numero)/220))+'px');
									$(this).css('width',numero+'px');
									$(this).css('height',numero+'px');
								});
							}
						}
					} else {
						console.log('ERROR - '+opcao+' - Erro data');
					}
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
				}
			});
		}
		
		function instagram_verificar_recentes(p){
			if(!p)p={};
			
			var opcao = 'instagram_verificar_recentes';
			
			if(p.instagram_token){
				b2make.instagram_token = p.instagram_token;
			}
			
			var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
			var count = parseInt(($(obj).myAttr('data-numero-posts') ? $(obj).myAttr('data-numero-posts') : 1));
			
			if(count < 1)count = 1;
			if(count > 16)count = 16;
			
			if(!$(obj).myAttr('data-numero-posts'))$(obj).myAttr('data-numero-posts',count);
			
			var params = 'access_token='+b2make.instagram_token+'&count='+count;
			
			$.ajax({
				type: 'POST',
				url: 'https://api.instagram.com/v1/users/self/media/recent/' + '?' + params,
				data: params,
				dataType: "jsonp",
				beforeSend: function(){
					
				},
				success: function(txt){
					var dados = txt;
					var first = true;
					
					if(dados.data){
						var id_aux = $(obj).myAttr('data-instagram-id');
						var id_arr = id_aux.split(',');
						var id = id_arr[0];
						
						if(dados.data[0].id != id){
							instagram_widget_update({
								obj : obj
							});
						}
					} else {
						console.log('ERROR - '+opcao+' - Erro data');
					}
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
				}
			});
		}
		
		function instagram_post(p){
			if(!p)p={};
			
			var opcao = 'instagram_post';
			var obj = p.obj;
			
			var params = 'url='+p.url;
			
			$.ajax({
				type: 'POST',
				url: 'https://api.instagram.com/oembed/' + '?' + params,
				data: params,
				dataType: "jsonp",
				beforeSend: function(){
					
				},
				success: function(txt){
					var dados = txt;
					
					if(dados.html){
						$(obj).myAttr('data-instagram-id',p.id);
						$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').html(dados.html);
					} else {
						console.log('ERROR - '+opcao+' - Erro data');
					}
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
				}
			});
		}
		
		function instagram_images(p){
			if(!p)p={};
			var obj = p.obj;
			
			if(p.first == true){
				$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').html('');
				$(obj).myAttr('data-instagram-id','');
			}
			
			var ids = $(obj).myAttr('data-instagram-id');
			
			if(ids != '') ids = ids + ',';
			
			$(obj).myAttr('data-instagram-id',ids + p.id);
			$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').append('<a class="b2make-instagram-posts" style="background-image:url('+p.image+')" href="'+p.url+'" target="_blank"></a>');
		}
		
		// ===================== Menus
		
		function navegador_layout(){
			var layout = $('#b2make-pagina-options').myAttr('data-pagina-menu-bolinhas-layout');
			var layout_vars = layout.split('|');
			var w = parseInt(layout_vars[0]);
			
			var focus = $('._parallax-nav-btn-1');
			var normal = $('._parallax-nav-btn-2');
			
			focus.css('width',w+'px');
			normal.css('width',w+'px');
			focus.css('height',w+'px');
			normal.css('height',w+'px');
			focus.css('margin',(w/2)+'px');
			normal.css('margin',(w/2)+'px');
			
			
			if(!b2make.navegador_layout_first){
				setTimeout(function(){normal.css('background-color',jpicker_ahex_2_rgba(layout_vars[2]));},100);
				setTimeout(function(){focus.css('background-color',jpicker_ahex_2_rgba(layout_vars[4]));},100);
			} else {
				normal.css('background-color',jpicker_ahex_2_rgba(layout_vars[2]));
				focus.css('background-color',jpicker_ahex_2_rgba(layout_vars[4]));
			}
			
			b2make.navegador_layout_first = true;
			
			var todas = layout_vars[1];
			
			var todas_arr = todas.split(';');
			
			$('._parallax-nav-btn-2').css('border',todas_arr[0]+'px '+todas_arr[1]+' '+todas_arr[2]);
			$('._parallax-nav-btn-2').css('-webkit-border-radius',todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px');
			$('._parallax-nav-btn-2').css('border-radius',todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px');
			
			var todas = layout_vars[3];
			
			var todas_arr = todas.split(';');
			
			$('._parallax-nav-btn-1').css('border',todas_arr[0]+'px '+todas_arr[1]+' '+todas_arr[2]);
			$('._parallax-nav-btn-1').css('-webkit-border-radius',todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px');
			$('._parallax-nav-btn-1').css('border-radius',todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px');
		}
		
		function navegador_posicionar(){
			var frames = b2make.conteiner_frames;
			var frames_height = b2make.conteiner_frames_height;
			var areas_desativadas = '';
			
			if($('#b2make-pagina-options').myAttr('data-pagina-menu-bolinha')){
				if($('#b2make-pagina-options').myAttr('data-pagina-menu-bolinha') == 'n'){
					return;
				}
			}
			
			if($('#b2make-pagina-options').myAttr('data-pagina-menu-bolinhas-areas')){
				areas_desativadas = $('#b2make-pagina-options').myAttr('data-pagina-menu-bolinhas-areas');
			}
			
			var areas_desativadas_arr = areas_desativadas.split(',');
			
			if(!b2make.conteiner_frames_nav){
				b2make.conteiner_frames_nav = $('<div id="_parallax-nav"></div>');
				
				b2make.conteiner_frames_nav.css('zIndex','999');
				b2make.conteiner_frames_nav.css('position','fixed');
				
				b2make.conteiner_frames_nav_buttons = new Array();
				
				var height_frame = 0;
				var flag_1_nav = false;
				
				for(var i=0;i<frames.length;i++){
					var area_desativada = false;
					for(var j=0;j<areas_desativadas_arr.length;j++){
						if(areas_desativadas_arr[j] == frames[i].obj.myAttr('id')){
							area_desativada = true;
							break;
						}
					}
					
					if(area_desativada){
						continue;
					}
					
					height_frame = height_frame + frames[i].height;
					
					b2make.conteiner_frames_nav_buttons[i] = $('<div id="_parallax-nav-'+(i+1)+'" class="_parallax-nav-btn _parallax-nav-btn-2" data-id="'+(frames[i].obj.myAttr('id'))+'"></div>');
					b2make.conteiner_frames_nav_buttons[i].appendTo(b2make.conteiner_frames_nav);
					b2make.conteiner_frames_nav_buttons[i].bind('click tap',function(e){
						menu_animar_scroll({obj:$(this)});
					});
				}
				
				if(i==1){
					flag_1_nav = true;
				}
			}
			
			var layout = $('#b2make-pagina-options').myAttr('data-pagina-menu-bolinhas-layout');
			if(layout){
				var layout_vars = layout.split('|');
				var w = parseInt(layout_vars[0]);
				
				var nav_left = b2make.conteiner_frames_nav_left;
				var nav_top = ($(window).height()/2) - (2*w * frames.length) / 2;
				
				b2make.conteiner_frames_nav.css('left',nav_left);
				b2make.conteiner_frames_nav.css('top',nav_top);
				
				b2make.conteiner_frames_nav.appendTo('body');
			}
			
			var pos = $(window).scrollTop();
			var top_scroll = pos;
			
			for(var i=0;i<frames.length;i++){
				var top = frames[i].obj.offset().top;
				var height = top + frames[i].obj.height();
				
				if(top_scroll >= top -1 && top_scroll < height -1){
					$('#_parallax-nav-'+(i+1)).removeClass('_parallax-nav-btn-2');
					$('#_parallax-nav-'+(i+1)).addClass('_parallax-nav-btn-1');
				} else {
					$('#_parallax-nav-'+(i+1)).addClass('_parallax-nav-btn-2');
					$('#_parallax-nav-'+(i+1)).removeClass('_parallax-nav-btn-1');
				}
			}
			
			if(flag_1_nav){
				$('#_parallax-nav-1').hide();
			}
			
			navegador_layout();
		}
		
		function menu_animar_scroll(p){
			if(!p)p={};
			
			var id = (p.obj ? p.obj.myAttr('data-id') : p.id);
			var frames = b2make.conteiner_frames;
			var top = 0;
			var body = $("html, body");
			var scrollTop = $(window).scrollTop();
			var espaco = 0;
			var tempo = 0;
			var frames_height = b2make.conteiner_frames_height;
			
			for(var i=0;i<frames.length;i++){
				if(frames[i].obj.myAttr('id') == id){
					top = frames[i].obj.offset().top;
					break;
				}
			}
			
			var top_scroll = top;
			
			espaco = scrollTop - top_scroll
			if(espaco < 0) espaco = espaco * (-1);
			
			tempo = espaco / b2make.velocidade_menu_top;
			
			body.stop().animate({scrollTop:top_scroll}, tempo , 'swing', function() {
			   
			});
		}

		function voltar_topo_close(){
			var voltar_topo = $('#b2make-pagina-voltar-topo');
			
			voltar_topo.hide();
		}

		function voltar_topo_open(){
			var voltar_topo = $('#b2make-pagina-voltar-topo');
			
			voltar_topo.show();
		}
		
		function voltar_topo(){
			$('<div id="b2make-pagina-voltar-topo"></div>').appendTo('body');
			$('#b2make-pagina-voltar-topo').hide();
			
			$('#b2make-pagina-voltar-topo').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				var top = 0;
				var body = $("html, body");
				var scrollTop = $(window).scrollTop();
				var espaco = 0;
				var tempo = 0;
				
				espaco = top - scrollTop
				if(espaco < 0) espaco = espaco * (-1);
				
				tempo = espaco / b2make.velocidade_menu_top;
				
				body.stop().animate({scrollTop:top}, tempo , 'swing', function() {
				   
				});
			});
		}
		
		$(window).resize(function(){
			navegador_posicionar();
			tecnologia_posicionar();
			
			$('.b2make-widget').each(function(){
				$(this).css('cursor','default');
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'conteiner':
							if($(this).myAttr('data-area-fixed') && $(this).myAttr('data-area-fixed') == 'b'){
								var abaixo = parseInt($(this).myAttr('data-area-fixed-baixo'));
								$(this).css('top','auto');
								$(this).css('zIndex','900');
								$(this).css('bottom',abaixo+'px');
							}
						break;
					}
				}
			});
		});

		$(window).bind('scroll', function(){
			window.requestAnimationFrame(scrollHandler);
		});
		
		function scrollHandler(){
			var scrollTop = $(window).scrollTop();
			if(scrollTop > 200){
				voltar_topo_open();
			} else {
				voltar_topo_close();
			}
			
			var frames = b2make.conteiner_frames;
			var frames_height = b2make.conteiner_frames_height;
			var pos = $(window).scrollTop();
			
			var top_scroll = pos;
			
			if(frames){
				for(var i=0;i<frames.length;i++){
					var top = frames[i].obj.offset().top;
					var height = top + frames[i].obj.height();
					
					if(top_scroll >= top -1 && top_scroll < height -1){
						$('#_parallax-nav-'+(i+1)).removeClass('_parallax-nav-btn-2');
						$('#_parallax-nav-'+(i+1)).addClass('_parallax-nav-btn-1');
					} else {
						$('#_parallax-nav-'+(i+1)).addClass('_parallax-nav-btn-2');
						$('#_parallax-nav-'+(i+1)).removeClass('_parallax-nav-btn-1');
					}
				}
			}
			
			if(b2make.menu_holder){
				if(b2make.menu_holder.myAttr('data-open') == '1'){
					if(b2make.menu_holder_position == 'absolute'){
						b2make.menu_holder.myAttr('data-open','0');
						b2make.menu_holder.hide();
					}
				}
			}
			
			navegador_layout();
		}
		
		function jpicker_ahex_2_rgba(ahex){
			var rgba = $.jPicker.ColorMethods.hexToRgba(ahex);
			
			return 'rgba('+rgba.r+','+rgba.g+','+rgba.b+','+(rgba.a/255).toFixed(1)+')';
		}
		
		$(window).bind('mousewheel', function(e){
			$("html, body").stop();
		});
		
		function tecnologia_posicionar(){
			if(!b2make.technology)b2make.technology = $('<div id="b2make-technology-cont">'+(b2make.device == 'phone' || localStorage.getItem('b2make.versao_desktop') ? (b2make.device == 'phone' ? '<div id="b2make-mobile-link-desktop" class="b2make-mobile-link">'+b2make.msgs.mobileLinkDesktop+'</div>' : '<div id="b2make-mobile-link-mobile" class="b2make-mobile-link">'+b2make.msgs.mobileLinkMobile+'</div>') : '') +'<a href="https://'+b2make.url+'" target="_blank" id="b2make-technology-link"></a></div>');
			
			b2make.technology.css('position','relative');
			b2make.technology.appendTo('body');
		}
		
		function hash_animation_sem_parallax(id){
			var tempo;
			var top_scroll = $('#'+id).offset().top ;
			var scrollTop = $(window).scrollTop();
			var espaco;
			var body = $("html, body");
			
			espaco = scrollTop - top_scroll
			if(espaco < 0) espaco = espaco * (-1);
			
			tempo = espaco / b2make.velocidade_menu_top;
			
			body.stop().animate({scrollTop:top_scroll}, tempo , 'swing', function() {
			   
			});
		}
		
		function hash_animation(){
			var hash = location.hash.replace( /^#/, '' );
			
			if(!hash){
				return;
			}
			
			if($('.b2make-widget[data-name="'+hash+'"]').length > 0){
				var obj = $('.b2make-widget[data-name="'+hash+'"]');
				var type = obj.myAttr('data-type');
				
				hash_animation_sem_parallax(obj.myAttr('id'));
			}
		}
		
		function hash_navagation(){
			$(window).on('hashchange hashsame',function(){
				
				hash_animation();
				
				return false;
			});
			
			hash_animation();
		}
		
		function operacoes_finais(){
			tecnologia_posicionar();
			
			$('.b2make-wsoae-prev,.b2make-wsoae-next').on('mouseover',function () {
				var pai = $(this).parent().parent();
				
				var color_caixa_hover = pai.myAttr('data-seta-cor-2-ahex');
				var color_seta_hover = pai.myAttr('data-caixa-cor-ahex');
				
				if(!color_caixa_hover) color_caixa_hover = '#333333'; else color_caixa_hover = jpicker_ahex_2_rgba(color_caixa_hover);
				if(!color_seta_hover) color_seta_hover = '#ECEDEF'; else color_seta_hover = jpicker_ahex_2_rgba(color_seta_hover);
				
				$(this).css('background-color',color_caixa_hover);
				$(this).find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',color_seta_hover);
			});
			
			$('.b2make-wsoae-prev,.b2make-wsoae-next').on('mouseout',function () {
				var pai = $(this).parent().parent();
				
				var color_caixa_hover = pai.myAttr('data-caixa-cor-ahex');
				var color_seta_hover = pai.myAttr('data-seta-cor-1-ahex');
				
				if(!color_caixa_hover) color_caixa_hover = '#ECEDEF'; else color_caixa_hover = jpicker_ahex_2_rgba(color_caixa_hover);
				if(!color_seta_hover) color_seta_hover = '#333333'; else color_seta_hover = jpicker_ahex_2_rgba(color_seta_hover);
				
				$(this).css('background-color',color_caixa_hover);
				$(this).find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',color_seta_hover);
			});
			
			$('.b2make-player-prev,.b2make-player-play,.b2make-player-next').on('mouseover',function (){
				var album_musicas = $(this).parent().hasClass('b2make-albummusicas-widget-controls');
				
				if(album_musicas){
					var pai = $(this).parent().parent().parent().parent().parent();
					var color = '#DBDBDB';
				} else {
					var pai = $(this).parent().parent().parent().parent();
					var color = '#726B6D';
				}
				
				if(pai.myAttr('data-botoes-color-2-ahex')){
					var bg = jpicker_ahex_2_rgba(pai.myAttr('data-botoes-color-2-ahex'));
					
					$(this).find('svg').find('polygon').css('fill',bg);
					$(this).find('svg').find('rect').css('fill',bg);
					$(this).find('svg').find('path').css('fill',bg);
				} else {
					$(this).find('svg').find('polygon').css('fill',color);
					$(this).find('svg').find('rect').css('fill',color);
					$(this).find('svg').find('path').css('fill',color);
				}
			});
			
			$('.b2make-player-prev,.b2make-player-play,.b2make-player-next').on('mouseout',function (){
				var album_musicas = $(this).parent().hasClass('b2make-albummusicas-widget-controls');
				
				if(album_musicas){
					var pai = $(this).parent().parent().parent().parent().parent();
					var color = '#FFFFFF';
				} else {
					var pai = $(this).parent().parent().parent().parent();
					var color = '#413E3F';
				}
				
				if(pai.myAttr('data-botoes-color-1-ahex')){
					var bg = jpicker_ahex_2_rgba(pai.myAttr('data-botoes-color-1-ahex'));
					
					$(this).find('svg').find('polygon').css('fill',bg);
					$(this).find('svg').find('rect').css('fill',bg);
					$(this).find('svg').find('path').css('fill',bg);
				} else {
					$(this).find('svg').find('polygon').css('fill',color);
					$(this).find('svg').find('rect').css('fill',color);
					$(this).find('svg').find('path').css('fill',color);
				}
			});
			
			$('.b2make-widget').each(function(){
				$(this).css('cursor','default');
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'conteiner':
							if($(this).myAttr('data-area-fixed') && $(this).myAttr('data-area-fixed') == 'b'){
								var abaixo = parseInt($(this).myAttr('data-area-fixed-baixo'));
								$(this).css('top','auto');
								$(this).css('zIndex','900');
								$(this).css('bottom',abaixo+'px');
							}
						break;
					}
				}
			});
		}
		
		function plataforma_areas_globais_start(){
			b2make.widgets_count = 0;
			b2make.areas_globais_num = 0;
			
			$('.b2make-widget').each(function(){
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'conteiner':
							if($(this).myAttr('data-area-global') == 's'){
								b2make.plataforma_manual_start = true;
								b2make.areas_globais_num++;
								
								conteiner_areas_globais_change_area({
									obj:this
								});
							}
						break;
					}
				}
			});
		}
		
		function plataforma_start(){
			if(!b2make.plataforma_manual_start){
				mobile();
				dialogbox();
				start_classes();
				menu_paginas();
				breadcrumbs();
				services();			
				youtube();
				posts_filter();
				contents();
				formularios();
				album_fotos();
				conteiner_banners();
				banners();
				widget_loja();
				voltar_topo();
				navegador_posicionar();
				accordion();
				hash_navagation();
				operacoes_finais();
			}
		}
		
		plataforma_areas_globais_start();
		plataforma_start();
		
	});
}