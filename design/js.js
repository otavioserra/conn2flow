if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

$.getScript('caret/jquery.caret-1.5.2.min.js');
$.extend({
    getManyCss: function(urls, callback, nocache){
        if (typeof nocache=='undefined') nocache=false; // default don't refresh
        $.when(
            $.each(urls, function(i, url){
                if (nocache) url += '?_ts=' + new Date().getTime(); // refresh? 
                $.get(url, function(){
                    $('<link>', {rel:'stylesheet', type:'text/css', 'href':url}).appendTo('head');
                });
            })
        ).then(function(){
            if (typeof callback=='function') callback();
        });
    },
	getScript: function(url, callback) {
		var head = document.getElementsByTagName("head")[0];
		var script = document.createElement("script");
		script.src = url;

		// Handle Script loading
		{
		 var done = false;

		 // Attach handlers for all browsers
		 script.onload = script.onreadystatechange = function(){
			if ( !done && (!this.readyState ||
				  this.readyState == "loaded" || this.readyState == "complete") ) {
			   done = true;
			   if (callback)
				  callback();

			   // Handle memory leak in IE
			   script.onload = script.onreadystatechange = null;
			}
		 };
		}

		head.appendChild(script);

		// We handle everything using the script element injection
		return undefined;
	}
});

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

var b2make = {};

$(document).ready(function(){
	$.log = function(val){
		console.log('>'+val);
	}
	
	function sem_permissao_redirect(){
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'signin','_self');
	}
	
	function imagem_resize(img){
		if(img){
			var image_width = parseInt(img.myAttr('data-image-width'));
			var image_height = parseInt(img.myAttr('data-image-height'));
			var cont_width = parseInt($(b2make.woc_width).val());
			var cont_height = parseInt($(b2make.woc_height).val());
			var thumb_w,thumb_h;
			
			if(b2make.imagem_resize_width){ cont_width = b2make.imagem_resize_width; b2make.imagem_resize_width = false; }
			if(b2make.imagem_resize_height){ cont_height = b2make.imagem_resize_height; b2make.imagem_resize_height = false; }
			
			thumb_w = cont_width;
			thumb_h = cont_height;
			
			if(cont_width > cont_height){
				thumb_w = (cont_height*image_width)/image_height;
			} else {
				thumb_h = (cont_width*image_height)/image_width;
			}
			
			if(thumb_w > cont_width){
				thumb_w = cont_width;
				thumb_h = (cont_width*image_height)/image_width;
			}
			
			if(thumb_h > cont_height){
				thumb_w = (cont_height*image_width)/image_height;
				thumb_h = cont_height;
			}
			
			thumb_w = Math.round(thumb_w);
			thumb_h = Math.round(thumb_h);
			
			img.width(thumb_w);
			img.height(thumb_h);
		}
	}
	
	function dynamic_variables(){
		// Atualização dos textos de acordo com um dicionário. Para multilinguas.
		// Parâmetros opcionais conforme condição dada pela plataforma
		
		sep = "../";
		
		if(location.href.match(/#_=_/) == '#_=_'){
			location.href = location.href.replace(/#_=_/gi,'');
		}
		
		b2make.msgs = {};
		b2make.path = 'design';
		b2make.font = 'Roboto Condensed';
	}
	
	dynamic_variables();
	
	function debug_console_start(){
		b2make.debug_console = $('#b2make-debug-console');
		
		if(b2make.debug_console.length == 0){
			b2make.debug_console = $('<div id="b2make-debug-console"><h2>B2make Debug Console</h2></div>');
			b2make.debug_console.appendTo('body');
		}
	}
	
	$.debug_console = function(p){
		debug_console_start();
		
		if(p.cache)localStorage['b2make.debug_console_startup'] = p.valor;
		
		b2make.debug_console.append('<p>>>> '+p.valor+'</p>');
	}
	
	if(localStorage['b2make.debug_console_startup']){
		$.debug_console({valor:localStorage['b2make.debug_console_startup']});
		localStorage.removeItem('b2make.debug_console_startup');
	}
	
	$.dialogbox_open = function(p){
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
					
					$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.message_btn_no_title ? p.message_btn_no_title : b2make.msgs.messageBtnNo)+'</div>').appendTo("#b2make-dialogbox-btns");
				} else if(p.confirm){
					$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.confirm_btn_no_title ? p.confirm_btn_no_title : b2make.msgs.confirmBtnNo)+'</div>').appendTo("#b2make-dialogbox-btns");
					$('<div class="b2make-dialogbox-btn'+(p.calback_yes?' '+p.calback_yes:'')+'"'+(p.calback_yes_extra?' '+p.calback_yes_extra:'')+'>'+(p.confirm_btn_yes_title ? p.confirm_btn_yes_title : b2make.msgs.confirmBtnYes)+'</div>').appendTo("#b2make-dialogbox-btns");
				} else {
					$('<div class="b2make-dialogbox-btn'+(p.calback_no?' '+p.calback_alert:'')+'"'+(p.calback_alert_extra?' '+p.calback_alert_extra:'')+'>'+(p.alert_btn_title ? p.alert_btn_title : b2make.msgs.alertBtn)+'</div>').appendTo("#b2make-dialogbox-btns");
				}
				
				if(p.more_buttons){
					var btns = p.more_buttons;
					
					for(var i=0;i<btns.length;i++){
						if(btns[i].before){
							$('<div class="b2make-dialogbox-btn'+(btns[i].dont_close?' b2make-dialogbox-btn-click-dont-close':'')+(btns[i].calback?' '+btns[i].calback:'')+'"'+(btns[i].calback_extra?' '+btns[i].calback_extra:'')+'>'+btns[i].title+'</div>').appendTo("#b2make-dialogbox-btns");
						} else {
							$('<div class="b2make-dialogbox-btn'+(btns[i].dont_close?' b2make-dialogbox-btn-click-dont-close':'')+(btns[i].calback?' '+btns[i].calback:'')+'"'+(btns[i].calback_extra?' '+btns[i].calback_extra:'')+'>'+btns[i].title+'</div>').prependTo("#b2make-dialogbox-btns");
						}
					}
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
	
	$.dialogbox_close = function(){
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
		
		$(document.body).on('mouseup tap',".b2make-dialogbox-btn",function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(!$(this).hasClass('b2make-dialogbox-btn-click-dont-close'))$.dialogbox_close();
		});
	}
	
	dialogbox();
	
	function ruler_active(){
		b2make.ruler.active = true;
		b2make.ruler.top.show();
		b2make.ruler.left.show();
		b2make.ruler.corner.show();
		$('.b2make-ruler-top-fixed').show();
		$('.b2make-ruler-left-fixed').show();
	}
	
	function ruler_desactive(){
		b2make.ruler.active = false;
		b2make.ruler.top.hide();
		b2make.ruler.left.hide();
		b2make.ruler.corner.hide();
		$('.b2make-ruler-top-fixed').hide();
		$('.b2make-ruler-left-fixed').hide();
	}
	
	function ruler_position(){
		var w = $(window).width();
		var h = $(window).height();
		
		b2make.ruler.top.css({top:b2make.ruler.start_top+'px',left:(b2make.ruler.width+b2make.ruler.start_left)+'px',width:(w-(b2make.ruler.width+b2make.ruler.start_left))+'px'});
		b2make.ruler.top_guide.css({top:b2make.ruler.start_top+'px',left:(b2make.ruler.width+b2make.ruler.start_left)+'px',height:(h-b2make.ruler.start_top)+'px'});
		$('.b2make-ruler-top-fixed').css({height:(h-b2make.ruler.start_top)+'px'});
		b2make.ruler.left.css({top:(b2make.ruler.height+b2make.ruler.start_top)+'px',left:b2make.ruler.start_left+'px',height:(h-(b2make.ruler.height+b2make.ruler.start_top))+'px'});
		b2make.ruler.left_guide.css({top:(b2make.ruler.height+b2make.ruler.start_top)+'px',left:b2make.ruler.start_left+'px',width:(w-b2make.ruler.start_left)+'px'});
		$('.b2make-ruler-left-fixed').css({width:(w-b2make.ruler.start_left)+'px'});
		b2make.ruler.corner.css({top:b2make.ruler.start_top+'px',left:b2make.ruler.start_left+'px'});
		
		b2make.ruler.top.html('');
		b2make.ruler.left.html('');
		b2make.ruler.corner.html('');
		
		for(var i=0;i<w;i=i+10){
			var grade_1 = $('<div class="b2make-ruler-grade-top-1"></div>');
			var grade_2 = $('<div class="b2make-ruler-grade-top-2"></div>');
			var grade_3 = $('<div class="b2make-ruler-grade-top-3"></div>');
			var grade_atual;
			
			if(i % 20 == 0){
				if(i % 100 == 0){
					grade_atual = grade_3;
					grade_atual.html(i);
				} else {
					grade_atual = grade_2;
				}
			} else {
				grade_atual = grade_1;
			}
			
			grade_atual.css('left',i+'px');
			b2make.ruler.top.append(grade_atual);
		}
		
		for(var i=0;i<h;i=i+10){
			var grade_1 = $('<div class="b2make-ruler-grade-left-1"></div>');
			var grade_2 = $('<div class="b2make-ruler-grade-left-2"></div>');
			var grade_3 = $('<div class="b2make-ruler-grade-left-3"></div>');
			var grade_atual;
			
			if(i % 20 == 0){
				if(i % 100 == 0){
					grade_atual = grade_3;
					grade_atual.html(i);
				} else {
					grade_atual = grade_2;
				}
			} else {
				grade_atual = grade_1;
			}
			
			grade_atual.css('top',i+'px');
			b2make.ruler.left.append(grade_atual);
		}
		
		var grade_1 = $('<div class="b2make-ruler-grade-top-1"></div>');
		var grade_2 = $('<div class="b2make-ruler-grade-top-2"></div>');
		
		grade_2.css('left',(b2make.ruler.width-10)+'px');
		grade_1.css('left',(b2make.ruler.width-20)+'px');
		b2make.ruler.corner.append(grade_1);
		b2make.ruler.corner.append(grade_2);
		
		var grade_1 = $('<div class="b2make-ruler-grade-left-1"></div>');
		var grade_2 = $('<div class="b2make-ruler-grade-left-2"></div>');
		
		grade_2.css('top',(b2make.ruler.height-10)+'px');
		grade_1.css('top',(b2make.ruler.height-20)+'px');
		b2make.ruler.corner.append(grade_1);
		b2make.ruler.corner.append(grade_2);
	}
	
	function ruler(){
		b2make.ruler = {};
		
		var start_left = 250;
		
		if(b2make_menu.open){
			b2make.ruler.start_left = start_left;
		} else {
			b2make.ruler.start_left = 0;
		}
		
		b2make.ruler.start_top = $('#b2make-menu').outerHeight();
		
		b2make.ruler.top = $('<div id="b2make-ruler-top"></div>').appendTo('body');
		b2make.ruler.left = $('<div id="b2make-ruler-left"></div>').appendTo('body');
		b2make.ruler.corner = $('<div id="b2make-ruler-corner"></div>').appendTo('body');
		b2make.ruler.top_guide = $('<div id="b2make-ruler-top-guide"><div id="b2make-ruler-top-coordanate"></div></div>').appendTo('body');
		b2make.ruler.left_guide = $('<div id="b2make-ruler-left-guide"><div id="b2make-ruler-left-coordanate"></div></div>').appendTo('body');
		
		b2make.ruler.top_guide.hide();
		b2make.ruler.left_guide.hide();
		
		b2make.ruler.height = b2make.ruler.corner.height();
		b2make.ruler.width = b2make.ruler.corner.width();
		
		b2make_menu.ruler_left = start_left;
		b2make_menu.ruler_width = b2make.ruler.width;
		
		ruler_position();
		
		$('#b2make-ruler-top,#b2make-ruler-top-guide').on('mouseenter',function(e){
			b2make.ruler.top.mouseenter = true;
			b2make.ruler.top_guide.show();
		});
		
		$('#b2make-ruler-top,#b2make-ruler-top-guide').on('mouseleave',function(e){
			b2make.ruler.top.mouseenter = false;
			b2make.ruler.top_guide.hide();
		});
		
		$('#b2make-ruler-left,#b2make-ruler-left-guide').on('mouseenter',function(e){
			b2make.ruler.left.mouseenter = true;
			b2make.ruler.left_guide.show();
		});
		
		$('#b2make-ruler-left,#b2make-ruler-left-guide').on('mouseleave',function(e){
			b2make.ruler.left.mouseenter = false;
			b2make.ruler.left_guide.hide();
		});
		
		$('#b2make-ruler-top,#b2make-ruler-left,#b2make-ruler-left-guide,#b2make-ruler-top-guide').bind('mousemove touchmove',function(e){
			e.stopPropagation();
			
			if(b2make.ruler.top.mouseenter){
				b2make.ruler.top_guide.css('left',e.pageX+'px');
				$('#b2make-ruler-top-coordanate').html((e.pageX - b2make.ruler.top.height() - b2make.ruler.start_left)+'px');
			}
			if(b2make.ruler.left.mouseenter){
				b2make.ruler.left_guide.css('top',(e.pageY - $(window).scrollTop())+'px');
				$('#b2make-ruler-left-coordanate').html((e.pageY - $(window).scrollTop() - b2make.ruler.start_top - b2make.ruler.left.width())+'px');
			}
		});
		
		$('#b2make-ruler-top,#b2make-ruler-left,#b2make-ruler-left-guide,#b2make-ruler-top-guide').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).myAttr('id');
			var w = $(window).width();
			var h = $(window).height();
			
			switch(id){
				case 'b2make-ruler-top':
				case 'b2make-ruler-top-guide':
					var fixed = $('<div class="b2make-ruler-top-fixed"></div>').appendTo('body');
					var close_btn = $('<div class="b2make-ruler-top-close">x</div>').appendTo(fixed);
					
					fixed.css({top:b2make.ruler.start_top+'px',left:e.pageX+'px',height:(h-b2make.ruler.start_top)+'px'});
				break;
				case 'b2make-ruler-left':
				case 'b2make-ruler-left-guide':
					var fixed = $('<div class="b2make-ruler-left-fixed"></div>').appendTo('body');
					var close_btn = $('<div class="b2make-ruler-left-close">x</div>').appendTo(fixed);
					
					fixed.css({top:(e.pageY - $(window).scrollTop())+'px',left:b2make.ruler.start_left+'px',width:(w-b2make.ruler.start_left)+'px'});
				break;
			}
			
		});
		
		$(document.body).on('mouseup tap','.b2make-ruler-top-close,.b2make-ruler-left-close',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().remove();
		});
		
		$('#b2make-listener').on('b2make-conteiner-close',function(e){
			if(!b2make.ruler.ajuste_top){
				b2make.ruler.ajuste_top = true;
				b2make.ruler.start_top = $('#b2make-menu').outerHeight();
				ruler_position();
			}
		});
		
		$('#b2make-listener').on('b2make-menu-change',function(e){
			var start_left = $('#b2make-menu-area').width();
			
			if(b2make_menu.open){
				b2make.ruler.start_left = start_left;
				$('.b2make-ruler-top-fixed').each(function(){
					var left = $(this).offset().left;
					$(this).css('left',(left+start_left));
				});
				
				$('.b2make-widget[data-type="conteiner"]').each(function(){
					if($(this).myAttr('data-area-fixed') == 's'){
						$(this).css('left',b2make_menu.width_conteiner+'px');
					}
				});
				
				$('#b2make-widget-conteiner-mask').css('width',($(window).width()-b2make_menu.width_conteiner)+'px');
			} else {
				b2make.ruler.start_left = 0;
				$('.b2make-ruler-top-fixed').each(function(){
					var left = $(this).offset().left;
					$(this).css('left',(left-start_left));
				});
				
				$('.b2make-widget[data-type="conteiner"]').each(function(){
					if($(this).myAttr('data-area-fixed') == 's'){
						$(this).css('left','auto');
					}
				});
				
				$('#b2make-widget-conteiner-mask').css('width',($(window).width())+'px');
			}
			
			$('.b2make-ruler-left-fixed').css('left',b2make.ruler.start_left+'px');
			
			ruler_position();
		});
	}
	
	ruler();
	
	function duplicate(){
		b2make.duplicate = {};
		
		b2make.duplicate.top_margin = 10;
		b2make.duplicate.left_margin = 50;
		
		$('.b2make-duplicate-conteiner').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = b2make.conteiner_obj;
			
			conteiner_close();
			
			var clone = $(obj).clone();
			$(obj).after(clone);
			
			clone.myAttr('id','area'+b2make.widgets_count);
			b2make.widgets_count++;
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : 'conteiner'
			});
			
			var area = clone.find('.b2make-widget[data-type="conteiner-area"]');
			
			if(area.length){
				area.myAttr('id','conteiner-area'+b2make.widgets_count);
				clone.myAttr('data-area','conteiner-area'+b2make.widgets_count);
				b2make.widgets_count++;
				
				area.find('.b2make-widget').each(function(){
					var type = $(this).myAttr('data-type');
					$(this).myAttr('id',type+b2make.widgets_count);
					b2make.widgets_count++;
				});
			} else {
				clone.find('.b2make-widget').each(function(){
					var type = $(this).myAttr('data-type');
					$(this).myAttr('id',type+b2make.widgets_count);
					b2make.widgets_count++;
				});
			}
			
			b2make.conteiner_total++;
			b2make.conteiner_obj = clone.get(0);
			
			conteiner_open();
		});
		
		$('.b2make-duplicate-widget').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = b2make.conteiner_child_obj;
			var pai = b2make.conteiner_obj;
			var type = $(obj).myAttr('data-type');
			var id = $(obj).myAttr('id');
			var width = $(obj).width();
			var height = $(obj).height();
			var pai_width = ($(pai).myAttr('data-area') ? parseInt($(pai).myAttr('data-area-largura')) : $(window).width());
			var pai_height = $(pai).height();
			
			$.conteiner_child_close();
			
			var clone = $(obj).clone();
			$(obj).after(clone);
			
			clone.myAttr('id',type+b2make.widgets_count);
			b2make.widgets_count++;
			
			if(!b2make.duplicate.history){
				b2make.duplicate.history = new Array();
			}
			
			if(!b2make.duplicate.history[id]){
				b2make.duplicate.history[id] = {
					top: $(obj).position().top,
					left: $(obj).position().left
				};
			}
			
			b2make.duplicate.history[id].top = b2make.duplicate.history[id].top + b2make.duplicate.top_margin;
			b2make.duplicate.history[id].left = b2make.duplicate.history[id].left + b2make.duplicate.left_margin;
			
			if((b2make.duplicate.history[id].top + height > pai_height) || (b2make.duplicate.history[id].left + width > pai_width)){
				if(!b2make.duplicate.history[id].ajuste){
					b2make.duplicate.history[id].ajuste = 0;
				} else if(b2make.duplicate.history[id].ajuste + height > pai_height){
					b2make.duplicate.history[id].ajuste = 0;
				}
				
				b2make.duplicate.history[id].top = b2make.duplicate.top_margin + b2make.duplicate.history[id].ajuste;
				b2make.duplicate.history[id].left = b2make.duplicate.left_margin;
				b2make.duplicate.history[id].ajuste = b2make.duplicate.history[id].ajuste + b2make.duplicate.top_margin;
			}
			
			clone.css({top:b2make.duplicate.history[id].top,left:b2make.duplicate.history[id].left});
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : type
			});
			
			b2make.conteiner_child_obj = clone.get(0);
			$.conteiner_child_open({widget_type:type});
		});
	}
	
	duplicate();
	
	function copy_paste(){
		b2make.copy_paste = {};
		
		b2make.copy_paste.top_margin = 10;
		b2make.copy_paste.left_margin = 50;
		
		if(local_storage_get('copy-paste')){
			b2make.copy_paste.atual = local_storage_get('copy-paste-atual');
			
			b2make.copy_paste.load_fonts = true;
			
			switch(b2make.copy_paste.atual){
				case 'conteiner':
					b2make.copy_paste.conteiner = local_storage_get('copy-paste');
				break;
				case 'widget':
					b2make.copy_paste.widget = local_storage_get('copy-paste');
					b2make.copy_paste.widget_id = local_storage_get('copy-paste-widget_id');
					b2make.copy_paste.widget_width = local_storage_get('copy-paste-widget_width');
					b2make.copy_paste.widget_height = local_storage_get('copy-paste-widget_height');
					
					b2make.copy_paste.history = new Array();
					
					b2make.copy_paste.history[b2make.copy_paste.widget_id] = {
						top : parseInt(local_storage_get('copy-paste-widget_top')),
						left : parseInt(local_storage_get('copy-paste-widget_left'))
					};
					
				break;
			}
		}
		
		$(document.body).on('focus','input,textarea',function(e){
			b2make.copy_paste.inativo = true;
		});
		
		$(document.body).on('blur','input,textarea',function(e){
			b2make.copy_paste.inativo = false;
		});
		
		$('.b2make-copy-conteiner').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = b2make.conteiner_obj;
			var outer_html = $('<div>').append($(obj).clone()).html();
			
			b2make.copy_paste.conteiner = outer_html;
			b2make.copy_paste.atual = 'conteiner';
			
			local_storage_set('copy-paste',outer_html);
			local_storage_set('copy-paste-atual','conteiner');
			
			b2make.copy_paste.load_fonts = false;
		});
		
		$('.b2make-copy-widget').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = b2make.conteiner_child_obj;
			var outer_html = $('<div>').append($(obj).clone()).html();
			var id = $(obj).myAttr('id');
			
			if(!b2make.copy_paste.history){
				b2make.copy_paste.history = new Array();
			}
			
			b2make.copy_paste.history[id] = {
				top: $('#b2make-selecionador-objetos').position().top,
				left: $('#b2make-selecionador-objetos').position().left
			};

			b2make.copy_paste.widget = outer_html;
			b2make.copy_paste.widget_id = id;
			b2make.copy_paste.widget_width = $('#b2make-woc-width-value').val();
			b2make.copy_paste.widget_height = $('#b2make-woc-height-value').val();
			b2make.copy_paste.atual = 'widget';
			
			local_storage_set('copy-paste',outer_html);
			local_storage_set('copy-paste-atual','widget');
			local_storage_set('copy-paste-widget_id',b2make.copy_paste.widget_id);
			local_storage_set('copy-paste-widget_width',b2make.copy_paste.widget_width);
			local_storage_set('copy-paste-widget_height',b2make.copy_paste.widget_height);
			local_storage_set('copy-paste-widget_top',$(obj).position().top);
			local_storage_set('copy-paste-widget_left',$(obj).position().left);
			
			b2make.copy_paste.load_fonts = false;
		});
		
		$('.b2make-paste-conteiner').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var copy;
			
			if(b2make.copy_paste.conteiner){
				var obj_aux = $(b2make.copy_paste.conteiner);
				var obj = obj_aux.get(0);
				
				copy = $(obj).clone();
				
				b2make.copy_paste.conteiner = $(copy).clone();
				
				var obj = b2make.conteiner_obj;

				if(obj){
					conteiner_close();
					$(obj).after(copy);
				} else {
					copy.appendTo(b2make.site_conteiner);
				}
				
				copy.myAttr('id','area'+b2make.widgets_count);
				b2make.widgets_count++;
				
				b2make.widgets.push({
					id : b2make.widgets_count,
					type : 'conteiner'
				});
				
				var area = copy.find('.b2make-widget[data-type="conteiner-area"]');
				
				if(area.length > 0){
					area.myAttr('id','conteiner-area'+b2make.widgets_count);
					copy.myAttr('data-area','conteiner-area'+b2make.widgets_count);
					b2make.widgets_count++;
					
					area.find('.b2make-widget').each(function(){
						var type = $(this).myAttr('data-type');
						$(this).myAttr('id',type+b2make.widgets_count);
						b2make.widgets_count++;
					});
				} else {
					copy.find('.b2make-widget').each(function(){
						var type = $(this).myAttr('data-type');
						$(this).myAttr('id',type+b2make.widgets_count);
						b2make.widgets_count++;
					});
				}
				
				b2make.conteiner_total++;
				b2make.conteiner_obj = $(copy).get(0);
				
				conteiner_open();
				
				if(b2make.copy_paste.load_fonts){
					var obj = b2make.conteiner_obj;
					
					if($(obj).myAttr('data-area')){
						obj = $(obj).find('.b2make-widget[data-type="conteiner-area"]');
					} else {
						obj = $(obj);
					}
					
					obj.find('.b2make-widget').each(function(){
						$.widgets_read_google_font({
							tipo : 1,
							obj : $(this)
						});
					});
				}
			}
		});
		
		$('.b2make-paste-widget').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var copy;
			
			if(b2make.copy_paste.widget && b2make.conteiner_obj){
				copy = $(b2make.copy_paste.widget).clone();
				b2make.copy_paste.widget = $(copy).clone();
				
				var obj = b2make.conteiner_child_obj;
				var pai = b2make.conteiner_obj;
				var type = $(copy).myAttr('data-type');
				var id = b2make.copy_paste.widget_id;
				var width = $(copy).width();
				var height = $(copy).height();
				var pai_width = ($(pai).myAttr('data-area') ? parseInt($(pai).myAttr('data-area-largura')) : $(window).width());
				var pai_height = $(pai).height();
				
				if(obj){
					$.conteiner_child_close();
					$(obj).after(copy);
				} else {
					if(b2make.conteiner_obj_area) copy.appendTo(b2make.conteiner_obj_area); else copy.appendTo(b2make.conteiner_obj);
				}
				
				copy.myAttr('id',type+b2make.widgets_count);
				b2make.widgets_count++;
				
				b2make.copy_paste.history[id].top = b2make.copy_paste.history[id].top + b2make.copy_paste.top_margin;
				b2make.copy_paste.history[id].left = b2make.copy_paste.history[id].left + b2make.copy_paste.left_margin;
				
				if((b2make.copy_paste.history[id].top + height > pai_height) || (b2make.copy_paste.history[id].left + width > pai_width)){
					if(!b2make.copy_paste.history[id].ajuste){
						b2make.copy_paste.history[id].ajuste = 0;
					} else if(b2make.copy_paste.history[id].ajuste + height > pai_height){
						b2make.copy_paste.history[id].ajuste = 0;
					}
					
					b2make.copy_paste.history[id].top = b2make.copy_paste.top_margin + b2make.copy_paste.history[id].ajuste;
					b2make.copy_paste.history[id].left = b2make.copy_paste.left_margin;
					b2make.copy_paste.history[id].ajuste = b2make.copy_paste.history[id].ajuste + b2make.copy_paste.top_margin;
				}
				
				copy.css({top:b2make.copy_paste.history[id].top,left:b2make.copy_paste.history[id].left,width:b2make.copy_paste.widget_width+'px',height:b2make.copy_paste.widget_height+'px'});
				
				b2make.widgets.push({
					id : b2make.widgets_count,
					type : type
				});
				
				b2make.conteiner_child_obj = $(copy).get(0);
				$.conteiner_child_open({widget_type:type});
				
				if(b2make.copy_paste.load_fonts){
					$.widgets_read_google_font({
						tipo : 1,
						obj : $(b2make.conteiner_child_obj)
					});
				}
			}
		});
	}
	
	copy_paste();
	
	function undo_redo_back(){
		var length = b2make.undo_redo.site_back.length;
		
		if(length > 0){
			var site = b2make.undo_redo.site_back[length-1];
			close_all();
			b2make.undo_redo.site_back.pop();
			$('#b2make-site').html(site);
			//b2make.undo_redo.site_forward.push(site);
		}
	}
	
	function undo_redo_add(){
		b2make.undo_redo.site_back.push($('#b2make-site').html());
		
		if(b2make.undo_redo.site_back.length > b2make.undo_redo.limite){
			b2make.undo_redo.site_back.shift();
		}
	}
	
	function undo_redo_loop(){
		setTimeout(function(){
			if(b2make.undo_redo.acao_user){
				b2make.undo_redo.acao_user = false;
				undo_redo_add();
			}
			
			undo_redo_loop();
		},b2make.undo_redo.time);
	}
	
	function undo_redo_acao_user(){
		if(!b2make.undo_redo.start){
			b2make.undo_redo.start = true;
			undo_redo_loop();
		}
		b2make.undo_redo.acao_user = true;
	}
	
	function undo_redo(){
		b2make.undo_redo = {};
		
		b2make.undo_redo.start = false;
		b2make.undo_redo.time = 1000;
		b2make.undo_redo.quantidade = 0;
		b2make.undo_redo.limite = 50;
		b2make.undo_redo.site_back = new Array();
		b2make.undo_redo.site_forward = new Array();
		
		var pai = b2make.conteiner_obj;
		var obj = b2make.conteiner_child_obj;
		
		$(window).on('mouseup tap',function(e){
			//undo_redo_acao_user();
		});
	}
	
	undo_redo();
	
	function multi_select_area_open(){
		b2make.multiselect.selecionador.cont.show();
		$('#b2make-widget-options-multiselect').show();
		$('#b2make-widget-options').hide();
		b2make.multiselect.selecionador.open = true;
	}
	
	function multi_select_area_close(){
		if(b2make.multiselect.selecionador.open){
			b2make.multiselect.selecionador.cont.hide();
			$('#b2make-widget-options-multiselect').hide();
			if(!b2make.multiselect.firstAccess){
				$('#b2make-widget-options').show();
			}
			
			b2make.multiselect.firstAccess = false;
			b2make.multiselect.selecionador.open = false;
		}
	}
	
	function multi_select_area(p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_obj;
		var AT1 = p.top - $(obj).position().top;
		var AT2 = p.top + p.height;
		var AL1 = p.left;
		var AL2 = p.left + p.width;
		var ajuste_left = 0;
		var first_interact = true;
		
		var selecionador = b2make.multiselect.selecionador;
		
		selecionador.top = 0;
		selecionador.left = 0;
		selecionador.width = 0;
		selecionador.height = 0;
		
		var ids = new Array();
		var obj_pai = obj
		
		if($(obj).myAttr('data-area')){
			ajuste_left = Math.floor(($(obj).width() - parseInt($(obj).myAttr('data-area-largura')))/2);
			obj = $(obj).find('.b2make-widget[data-type="conteiner-area"]');
		} else {
			obj = $(obj);
		}
		
		obj.find('.b2make-widget').each(function(){
			var OT1 = $(this).position().top;
			var OT2 = $(this).position().top + $(this).height();
			var OL1 = $(this).position().left + ajuste_left;
			var OL2 = $(this).position().left + $(this).width() + ajuste_left;
			var id = $(this).myAttr('id');
			var found = false;
			
			if(OT1 > AT1 && OT2 < AT2){
				if(OL1 > AL1 && OL2 < AL2){
					found = true;
				}
				if(OL2 > AL1 && OL1 < AL2){
					found = true;
				}
			}
			
			if(OT2 > AT1 && OT1 < AT2){
				if(OL2 > AL1 && OL1 < AL2){
					found = true;
				}
				if(OL1 < AL2 && OL2 > AL1){
					found = true;
				}
				if(OL1 > AL1 && OL2 < AL2){
					found = true;
				}
			}
			
			if(found){
				ids.push(id);
				
				if(first_interact){
					selecionador.top = OT1;
					selecionador.left = OL1;
					selecionador.width = $(this).width();
					selecionador.height = $(this).height();
				} else {
					if(selecionador.top > OT1){
						selecionador.top = OT1;
					}
					if(selecionador.left > OL1){
						selecionador.left = OL1;
					}
					if(selecionador.width < OL2 - selecionador.left){
						selecionador.width = OL2 - selecionador.left;
					}
					if(selecionador.height < OT2 - selecionador.top){
						selecionador.height = OT2 - selecionador.top;
					}
				}
				
				first_interact = false;
			}
			
		});
		
		if(ids.length > 0){
			selecionador.cont.css('top',selecionador.top + $('#b2make-site').offset().top + b2make.multiselect.ajusteBorda + $(obj_pai).position().top);
			selecionador.cont.css('left',selecionador.left + $('#b2make-site').offset().left + b2make.multiselect.ajusteBorda);
			selecionador.cont.css('width',selecionador.width - b2make.multiselect.ajusteBorda);
			selecionador.cont.css('height',selecionador.height - b2make.multiselect.ajusteBorda);
			
			selecionador.ids = ids;
			
			b2make.multiselect.selecionador = selecionador;
			
			multi_select_area_open();
		} else {
			multi_select_area_close();
		}
	}
	
	function multi_select_resize(){
		b2make.multiselect.cont.css('width',$('#b2make-site').width());
		b2make.multiselect.cont.css('height',$('#b2make-site').height());
		b2make.multiselect.cont.css('left',$('#b2make-site').offset().left);
		b2make.multiselect.cont.css('top',$('#b2make-site').offset().top);
	}
	
	function multi_select_open(){
		multi_select_resize();
		$('body').addClass('b2make-multiselect-ativo');
		b2make.multiselect.ativo = true;
		b2make.multiselect.cont.show();
	}
	
	function multi_select_close(){
		$('body').removeClass('b2make-multiselect-ativo');
		b2make.multiselect.ativo = false;
		b2make.multiselect.cont.hide();
	}
	
	function multi_select(){
		b2make.multiselect = {};
		
		b2make.multiselect.cont = $('<div id="b2make-multiselect-cont"></div>');
		b2make.multiselect.cont.appendTo('body');
		b2make.multiselect.dragCont = $('<div id="b2make-multiselect-area"></div>');
		b2make.multiselect.dragCont.appendTo('#b2make-multiselect-cont');
		b2make.multiselect.ajusteBorda = 1;
		b2make.multiselect.firstAccess = true;
		
		b2make.multiselect.selecionador = {};
		
		b2make.multiselect.selecionador.cont = $('<div id="b2make-multiselect-selecionador"></div>');
		b2make.multiselect.selecionador.cont.appendTo('body');
		multi_select_area_close();
		
		$(window).bind('mouseup touchend',function(e){
			if(b2make.multiselect.selecionador.open){
				var id = $(e.target).myAttr('id');
				
				if($(e.target).hasClass('b2make-woms-btns')){
					id = 'b2make-woms-btns';
				}
				
				switch(id){
					case 'b2make-woms-btns':
					case 'b2make-widget-options-multiselect':
					
					break;
					default:
						multi_select_area_close();
				}
			}
		});
		
		$('#b2make-widget-options-multiselect-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if(b2make.multiselect.ativo){
				multi_select_close();
			} else {
				multi_select_open();
			}
		});
		
		$('#b2make-multiselect-cont').on('mousedown touchstart',function(e){
			e.stopPropagation();
			
			b2make.multiselect.mouseX = (e.pageX - $(this).offset().left);
			b2make.multiselect.mouseY = (e.pageY - $(this).offset().top);
			b2make.multiselect.dragCont.width(0);
			b2make.multiselect.dragCont.height(0);
			
			b2make.multiselect.dragCont.css('left',(e.pageX - $(this).offset().left));
			b2make.multiselect.dragCont.css('top',(e.pageY - $(this).offset().top));

			b2make.multiselect.dragCont.show();
			
			b2make.multiselect.drag = true;
		});
		
		$('#b2make-multiselect-cont').on('mouseup touchend',function(e){
			e.stopPropagation();
			
			multi_select_area({
				top:b2make.multiselect.dragCont.position().top,
				left:b2make.multiselect.dragCont.position().left,
				width:b2make.multiselect.dragCont.width(),
				height:b2make.multiselect.dragCont.height()
			});
			
			b2make.multiselect.dragCont.hide();
			b2make.multiselect.drag = false;
			
			multi_select_close();
		});
		
		$('#b2make-multiselect-cont').on('mousemove touchmove',function(e){
			e.stopPropagation();
			
			if(b2make.multiselect.drag){
				var dragCont = b2make.multiselect.dragCont;
				var originalX = b2make.multiselect.mouseX;
				var originalY = b2make.multiselect.mouseY;
				var mX = (e.pageX - $(this).offset().left);
				var mY = (e.pageY - $(this).offset().top);
				
				var width = originalX - mX;
				var height = originalY - mY;
				
				if(width < 0) width = (-1)*width;
				if(height < 0) height = (-1)*height;
				
				if(originalX > mX){
					if(originalY > mY){ // cima-esquerda
						dragCont.css('top',mY);
						dragCont.css('left',mX);
					} else { // baixo-esquerda
						dragCont.css('top',originalY);
						dragCont.css('left',(originalX - width));
					}
				} else {
					if(originalY > mY){ // cima-direita
						dragCont.css('top',(originalY - height));
						dragCont.css('left',originalX);
					} else { // baixo-direita'
						dragCont.css('top',originalY);
						dragCont.css('left',originalX);
					}
					
				}
				
				dragCont.width(width);
				dragCont.height(height);
			}
		});
		
		$('.b2make-woms-btns').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id_cont = $(this).myAttr('id');
			var selecionador = b2make.multiselect.selecionador;
			var ids = selecionador.ids;
			
			var opcao = id_cont.replace(/b2make-woms-align-/gi,'');
			var opcao_arr = opcao.split('-');
			
			var obj_pai = b2make.conteiner_obj;
			
			if($(obj_pai).myAttr('data-area')){
				obj_pai = $(obj_pai).find('.b2make-widget[data-type="conteiner-area"]');
			} else {
				obj_pai = $(obj_pai);
			}
			
			var left = selecionador.left - $(obj_pai).offset().left + $('#b2make-site').offset().left;
			var top = selecionador.top - $(obj_pai).offset().top + $('#b2make-site').offset().top + b2make.multiselect.ajusteBorda;
			var right = left + selecionador.width;
			var bottom = top + selecionador.height;
			
			switch(opcao_arr[0]){
				case 'horizontal':
					switch(opcao_arr[1]){
						case 'center':
							var center = Math.floor(selecionador.height / 2);
							
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								$('#'+id).css('top',((top+center)-($('#'+id).height()/2)));
							}
						break;
						case 'top':
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								$('#'+id).css('top',top);
							}
						break;
						case 'bottom':
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								$('#'+id).css('top',(bottom-$('#'+id).height()));
							}
						break;
						case 'espacamento':
							var esp = selecionador.height;
							var ids_new = new Array();
							
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								esp = esp - $('#'+id).height();
								
								ids_new.push({
									id : id,
									top : $('#'+id).offset().top
								});
							}
							
							ids_new.sort(function(a, b) {
								return a.top - b.top;
							});
							
							esp = Math.floor(esp / ids_new.length);
							var top_esp = top;
							
							for(var key in ids_new){
								var id = ids_new[key].id;
								
								$('#'+id).css('top',top_esp);
								top_esp = top_esp + $('#'+id).height() + esp;
							}
						break;
					}
				break;
				case 'vertical':
					switch(opcao_arr[1]){
						case 'center':
							var center = Math.floor(selecionador.width / 2);
							
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								$('#'+id).css('left',((left+center)-($('#'+id).width()/2)));
							}
						break;
						case 'left':
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								$('#'+id).css('left',left);
							}
						break;
						case 'right':
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								$('#'+id).css('left',(right-$('#'+id).width()));
							}
						break;
						case 'espacamento':
							var esp = selecionador.width;
							var ids_new = new Array();
							
							for(var i=0;i<ids.length;i++){
								var id = ids[i];
								
								esp = esp - $('#'+id).width();
								
								ids_new.push({
									id : id,
									left : $('#'+id).offset().left
								});
							}
							
							ids_new.sort(function(a, b) {
								return a.left - b.left;
							});
							
							esp = Math.floor(esp / ids_new.length);
							var left_esp = left;
							
							for(var key in ids_new){
								var id = ids_new[key].id;
								
								$('#'+id).css('left',left_esp);
								left_esp = left_esp + $('#'+id).width() + esp;
							}
						break;
					}
				break;
			}
		});
	}
	
	multi_select();
	
	function lightbox_open(p){
		if(!b2make.lightbox){
			if(!p)p = {};
			b2make.lightbox = true;
			
			if(!b2make.lightbox_default_width)b2make.lightbox_default_width = $("#b2make-lightbox").width();
			if(!b2make.lightbox_default_height)b2make.lightbox_default_height = $("#b2make-lightbox").height();
			
			if(!p.width)if(b2make.lightbox_default_width != $("#b2make-lightbox").width())$("#b2make-lightbox").width(b2make.lightbox_default_width);
			if(!p.height)if(b2make.lightbox_default_height != $("#b2make-lightbox").height())$("#b2make-lightbox").height(b2make.lightbox_default_height);
			
			if(p.width)$("#b2make-lightbox").width(p.width);
			if(p.height)$("#b2make-lightbox").height(p.height);
			
			$("#b2make-lightbox-head").html((p.title?p.title:(p.confirm?b2make.msgs.confirmTitle:b2make.msgs.alertTitle)));
			$("#b2make-lightbox-btns").html('');
			
			if(p.coneiner){
				$("#b2make-lightbox-msg").html('');
				$("#b2make-lightbox-msg").append($('#'+p.coneiner));
				b2make.lightbox_conteiner = p.coneiner;
			}
			
			if(!p.no_btn_default){
				if(p.specific_buttons){
					var btns = p.specific_buttons;
					
					for(var i=0;i<btns.length;i++){
						var dont_close = '';
						
						if(p.specific_buttons_dont_close){
							var dont_close_arr = p.specific_buttons_dont_close;
							
							for(var j=0;j<dont_close_arr.length;j++){
								if(btns[i].title == dont_close_arr[j]){
									dont_close = ' b2make-lightbox-btn-click-dont-close';
									break;
								}
							}
						}
						
						$('<div class="b2make-lightbox-btn'+dont_close+(btns[i].calback?' '+btns[i].calback:'')+'"'+(btns[i].calback_extra?' '+btns[i].calback_extra:'')+'>'+btns[i].title+'</div>').appendTo("#b2make-lightbox-btns");
					}
				} else if(p.message){
					$('<div class="b2make-lightbox-btn b2make-lightbox-btn-click-dont-close'+(p.calback_yes?' '+p.calback_yes:'')+'"'+(p.calback_yes_extra?' '+p.calback_yes_extra:'')+'>'+(p.message_btn_yes_title ? p.message_btn_yes_title : b2make.msgs.messageBtnYes)+'</div>').appendTo("#b2make-lightbox-btns");
					
					if(p.more_buttons){
						var btns = p.more_buttons;
						
						for(var i=0;i<btns.length;i++){
							$('<div class="b2make-lightbox-btn'+(btns[i].calback?' '+btns[i].calback:'')+'"'+(btns[i].calback_extra?' '+btns[i].calback_extra:'')+'>'+btns[i].title+'</div>').appendTo("#b2make-lightbox-btns");
						}
					}
					
					$('<div class="b2make-lightbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.message_btn_no_title ? p.message_btn_no_title : b2make.msgs.messageBtnNo)+'</div>').appendTo("#b2make-lightbox-btns");
				} else if(p.confirm){
					$('<div class="b2make-lightbox-btn'+(p.calback_no?' '+p.calback_no:'')+'"'+(p.calback_no_extra?' '+p.calback_no_extra:'')+'>'+(p.confirm_btn_no_title ? p.confirm_btn_no_title : b2make.msgs.confirmBtnNo)+'</div>').appendTo("#b2make-lightbox-btns");
					$('<div class="b2make-lightbox-btn'+(p.calback_yes?' '+p.calback_yes:'')+'"'+(p.calback_yes_extra?' '+p.calback_yes_extra:'')+'>'+(p.confirm_btn_yes_title ? p.confirm_btn_yes_title : b2make.msgs.confirmBtnYes)+'</div>').appendTo("#b2make-lightbox-btns");
				} else {
					$('<div class="b2make-lightbox-btn'+(p.calback_no?' '+p.calback_alert:'')+'"'+(p.calback_alert_extra?' '+p.calback_alert_extra:'')+'>'+(p.alert_btn_title ? p.alert_btn_title : b2make.msgs.alertBtn)+'</div>').appendTo("#b2make-lightbox-btns");
				}
				
				$('<div class="b2make-lightbox-btn-2 b2make-lightbox-btn-click-back">'+b2make.msgs.backBtn+'</div>').appendTo("#b2make-lightbox-btns");
			}
			
			
			b2make.lightbox_callback_yes = p.calback_yes;
			
			var top_start = -10 - $("#b2make-lightbox").height();
			var top_stop = $(window).height()/2 - $("#b2make-lightbox").height()/2;
			var left = $(window).width()/2 - $("#b2make-lightbox").width()/2;
			
			$("#b2make-lightbox").css('top',top_start);
			$("#b2make-lightbox").css('left',left);
			
			$("#b2make-lightbox").animate({top:top_stop}, b2make.lightboxAnimateTime, function(){
				if(p.coneiner){
					$('#'+p.coneiner).find('input').filter(':visible:first').focus();
					$('#'+p.coneiner).find('input').filter(':visible:first').tooltip( "close" );
				}
				
				$('#b2make-listener').trigger('b2make-lightbox-opened');
			});
		} else {
			$("#b2make-lightbox-head").html((p.title?p.title:(p.confirm?b2make.msgs.confirmTitle:b2make.msgs.alertTitle)));
			
			if(!p.width)if(b2make.lightbox_default_width != $("#b2make-lightbox").width())$("#b2make-lightbox").width(b2make.lightbox_default_width);
			if(!p.height)if(b2make.lightbox_default_height != $("#b2make-lightbox").height())$("#b2make-lightbox").height(b2make.lightbox_default_height);
			
			if(p.width)$("#b2make-lightbox").width(p.width);
			if(p.height)$("#b2make-lightbox").height(p.height);
			
			var top = $(window).height()/2 - $("#b2make-lightbox").height()/2;
			var left = $(window).width()/2 - $("#b2make-lightbox").width()/2;
			
			$("#b2make-lightbox").css('top',top);
			$("#b2make-lightbox").css('left',left);
		}
		
		if(p.lightbox_back_btn){
			$('.b2make-lightbox-btn-click-back').show();
			$('.b2make-lightbox-btn-click-back').myAttr('data-type',p.lightbox_back_btn);
		} else {
			$('.b2make-lightbox-btn-click-back').hide();
		}
	}
	
	function lightbox_shake(){
		$("#b2make-lightbox").stop().effect( "shake" );
	}
	
	function lightbox_open_after(p){
		setTimeout(function(){
			lightbox_open(p);
		},b2make.lightboxAnimateTime);
	}
	
	function lightbox_close(){
		if(b2make.lightbox){
			b2make.lightbox = false;
			b2make.widget_sub_options_back = false;
			var top_stop = -10 - $("#b2make-lightbox").height();
			
			$("#b2make-lightbox").animate({top:top_stop}, b2make.lightboxAnimateTime, "swing", function(){
				if(b2make.lightbox_conteiner){
					formulario_resetar(b2make.lightbox_conteiner);
					$('#'+b2make.lightbox_conteiner).appendTo($('#b2make-formularios'));
					b2make.lightbox_conteiner = false;
					
				}
				
				$('#b2make-listener').trigger('b2make-lightbox-closed');
			});
		}
	}
	
	function lightbox_position(){
		if(b2make.lightbox){
			$("#b2make-lightbox").stop();
			var top = $(window).height()/2 - $("#b2make-lightbox").height()/2;
			var left = $(window).width()/2 - $("#b2make-lightbox").width()/2;
			
			$("#b2make-lightbox").css('top',top);
			$("#b2make-lightbox").css('left',left);
		} else {
			var top =  -10 - $("#b2make-lightbox").height();;
			var left = $(window).width()/2 - $("#b2make-lightbox").width()/2;
			
			$("#b2make-lightbox").css('top',top);
			$("#b2make-lightbox").css('left',left);
		}
	}
	
	function lightbox(){
		b2make.lightbox = false;
		if(!b2make.lightboxAnimateTime)b2make.lightboxAnimateTime = 250;
		if(!b2make.msgs.alertTitle)b2make.msgs.alertTitle = "Alerta";
		if(!b2make.msgs.confirmTitle)b2make.msgs.confirmTitle = "Confirma&ccedil;&atilde;o";
		if(!b2make.msgs.alertMsg)b2make.msgs.alertMsg = "Esta op&ccedil;&atilde;o n&atilde;o est&aacute; ativada";
		if(!b2make.msgs.alertBtn)b2make.msgs.alertBtn = "Ok";
		if(!b2make.msgs.backBtn)b2make.msgs.backBtn = "Voltar";
		if(!b2make.msgs.confirmMsg)b2make.msgs.confirmMsg = "Tem certeza que deseja proseguir?";
		if(!b2make.msgs.confirmBtnYes)b2make.msgs.confirmBtnYes = "Sim";
		if(!b2make.msgs.confirmBtnNo)b2make.msgs.confirmBtnNo = "N&atilde;o";
		if(!b2make.msgs.messageBtnNo)b2make.msgs.messageBtnNo = "Cancelar";
		if(!b2make.msgs.messageBtnYes)b2make.msgs.messageBtnYes = "Enviar";
		
		$(document.body).on('mouseup tap',".b2make-lightbox-btn",function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if(b2make.widget_sub_options_back){
				b2make.widget_sub_options_back = false;
				b2make.widget_edit_sub_options_open = true;
				$.widget_sub_options_open();
				return true;
			}
			if(!$(this).hasClass('b2make-lightbox-btn-click-dont-close'))lightbox_close();
			
			if(b2make.template_reopen){
				foto_perfil_close();
			}
			
			b2make.widget_sub_options_button_open = false;
		});
		
		$(document.body).on('mouseup tap',".b2make-lightbox-btn-2",function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			b2make.widget_edit_sub_options_open = true;
			b2make.widget_sub_options_type = $(this).myAttr('data-type');
			$.widget_sub_options_open();
		});
	}
	
	lightbox();
	
	$.statusbox_upload_dialbox_close = function(){
		var uploads_queueds = b2make.uploads_queueds;
		var pelo_menos_um = false;
		
		for(var i=0;i<uploads_queueds.length;i++){
			if(uploads_queueds[i]){
				pelo_menos_um = true;
			}
		}
		
		if(!pelo_menos_um){
			statusbox_close();
		} else {
			setTimeout($.statusbox_upload_dialbox_close,1000);
		}
	}
	
	$.statusbox_open = function(p){
		if(!b2make.statusbox){
			if(!p)p = {};
			b2make.statusbox = true;
			
			$("#b2make-statusbox").animate({bottom:0}, b2make.statusboxAnimateTime);
		}
	}
	
	function statusbox_close(){
		if(b2make.statusbox){
			b2make.statusbox = false;
			
			$("#b2make-statusbox").animate({bottom:-($('#b2make-statusbox').height() + 10)}, b2make.statusboxAnimateTime);
		}
	}
	
	function statusbox_remove_item_uploaded(id){
		setTimeout(function(){
			$('#b2make-statusbox-log li#'+id).fadeOut(b2make.statusboxAnimateTime);
		},b2make.statusboxRemoveItemUploadedTimeout);
	}
	
	function statusbox(){
		if(!b2make.statusboxAnimateTime)b2make.statusboxAnimateTime = 250;
		if(!b2make.statusboxRemoveItemUploadedTimeout)b2make.statusboxRemoveItemUploadedTimeout = 1000;
		
		b2make.uploads_queueds_num = 0;
		b2make.uploads_queueds = new Array();
		b2make.upload_clicked = new Array();
		
		var height = $('#b2make-statusbox').height() + 10;
		$('#b2make-statusbox').css('bottom',-height);
	}
	
	statusbox();
	
	$.script_trigger = function(p){
		if(!p)p={};
		
		b2make.script_callback.push({
			id : p.id,
			callback : p.callback,
			operacao : p.operacao,
			params : p.params
		});
	}
	
	function script_callback(p){
		if(!p)p={};
		
		if(b2make.script_callback.length > 0){
			for(var i = 0; i<b2make.script_callback.length ; i++){
				if(p.operacao == b2make.script_callback[i].operacao){
					$('#b2make-'+b2make.script_callback[i].id+'-callback').trigger(b2make.script_callback[i].callback,b2make.script_callback[i].params);
					b2make.script_callback.splice(i,1);
					break;
				}
			}
		}
	}
	
	function script_ler(p){
		if(!p)p={};
		
		var id = p.id;
		var not_callback = p.not_callback;
		
		if(b2make.dynamic_scripts_loaded[id]){
			if(b2make.dynamic_scripts_callback[id])$('#b2make-'+id+'-callback').trigger('callback');
			return;
		}
		
		var scripts = b2make.dynamic_scripts;
		var path = b2make.dynamic_scripts_path;
		var script;
		var cssFiles;
		var carregando;
		
		for(var i=0;i<scripts.length;i++){
			if(id == scripts[i].id){
				if(scripts[i].callback){
					b2make.dynamic_scripts_callback[id] = true;
				}
				if(scripts[i].carregando){
					carregando = true;
				}
				if(scripts[i].css){
					cssFiles = [(path + id + '/css.css' + (variaveis_js.ler_scripts_force_reload ? '?v=' + new Date().getTime() : '?v='+variaveis_js.b2make_version))];
				}
				
				script = path+id+'/js.js' + (variaveis_js.ler_scripts_force_reload ? '?v=' + new Date().getTime() : '?v='+variaveis_js.b2make_version);
				break;
			}
		}
		
		if(script){
			var callback_start = $('<div id="b2make-'+id+'-callback"></div>');callback_start.appendTo('body');callback_start.hide();
			if(cssFiles){
				if(carregando)$.carregamento_open();
				$.getManyCss(cssFiles, function(){
					$.getScript(script, function(){
						b2make.dynamic_scripts_loaded[id] = true;
						if(carregando)$.carregamento_close();
						if(b2make.dynamic_scripts_callback[id] && !not_callback)$('#b2make-'+id+'-callback').trigger('callback');
					});
				});
			} else {
				$.getScript(script, function(){
					b2make.dynamic_scripts_loaded[id] = true;
					if(carregando)$.carregamento_close();
					if(b2make.dynamic_scripts_callback[id] && !not_callback)$('#b2make-'+id+'-callback').trigger('callback');
				});
			}
		}
	}
	
	function script(){
		b2make.script_callback = new Array();
		b2make.dynamic_scripts = new Array();
		b2make.dynamic_scripts_path = variaveis_js.site_raiz + b2make.path + '/plugins/';
		
		var path = b2make.dynamic_scripts_path;
		
		b2make.dynamic_scripts = variaveis_js.b2make_plugins;
		
		b2make.dynamic_scripts_loaded = new Array();
		b2make.dynamic_scripts_callback = new Array();
	}
	
	script();
	
	$.upload_files_start = function(p = {}){
		var url = p.url_php;
		var input = p.input_selector;
		var file_type = p.file_type;
		var uploads_queueds_num = b2make.uploads_queueds_num;
		var max_files = 0;
		
		var acceptFileTypes = undefined;
		
		switch(file_type){
			case 'imagem': acceptFileTypes = /\.(gif|jpg|jpeg|png)$/i ; acceptFileAlert = 'S&oacute; s&atilde;o aceitos arquivos do tipo imagem (gif|jpg|jpeg|png).' ; break;
			case 'audio': acceptFileTypes = /\.(mp3)$/i ; acceptFileAlert = 'S&oacute; s&atilde;o aceitos arquivos do tipo &aacute;udio (mp3).' ; break;
		}
		
		$(input).fileupload({
			url: url,
			dropZone: null,
			autoUpload: true,
			dataType: 'json',
		}).prop('disabled', !$.support.fileInput)
		.parent().addClass($.support.fileInput ? undefined : 'disabled');
		
		$(input).bind('fileuploadadd', function (e, data){
			$.upload_files_mask_close();
			
			var goUpload = true;
			var uploadFile = data.files[0];
			
			if(acceptFileTypes)
			if(!(acceptFileTypes).test(uploadFile.name)){
				$.dialogbox_open({
					msg: acceptFileAlert
				});
				goUpload = false;
			}
			
			if(goUpload){
				b2make.uploadFiles.ids++;
				var id = b2make.uploadFiles.ids;
				
				max_files++;
				
				var listitem='<li id="'+id+'">'+
					data.files[0].name+' ('+Math.round(data.files[0].size/1024)+' KB)'+
					'<div class="progressbar" ><div class="progress" style="width:0%"></div></div>'+
					'<span class="status" >Aguardando</span><span class="progressvalue" ></span>'+
					'</li>';
				$('#b2make-statusbox-log').append(listitem);
				
				b2make.uploads_queueds[uploads_queueds_num] = true;
				$.statusbox_open(false);
				setTimeout($.statusbox_upload_dialbox_close,1000);
				
				if (data.autoUpload || (data.autoUpload !== false && $(this).fileupload('option', 'autoUpload'))){
					data.process().done(function () {
						data.submit();
					});
				}
			}
		});
		
		$(input).bind('fileuploadsubmit', function (e, data){
			var id = b2make.uploadFiles.ids;
			
			data.formData = {
				id_upload: id,
				name: data.files[0].name,
				lastModified: data.files[0].lastModified,
				'user':variaveis_js.library_user,
				'session_id':variaveis_js.library_id
			};
			
			if(p.post_params){
				var postVars = p.post_params();
				
				for(var i=0;i<postVars.length;i++){
					data.formData[postVars[i].variavel] = postVars[i].valor;
				}
			}
		});
		
		$(input).bind('fileuploadsend', function (e, data){
			var id = data.formData.id_upload;
			var status_log = $('#b2make-statusbox-log');
			
			$('#b2make-statusbox-log li#'+id).find('span.status').text('Enviando...');
			$('#b2make-statusbox-log li#'+id).find('span.progressvalue').text('0%');
		});
		
		$(input).bind('fileuploadprogress', function (e, data){
			var id = data.formData.id_upload;
			
			if(id){
				var progress = parseInt(data.loaded / data.total * 100, 10);
				
				$('#b2make-statusbox-log li#'+id).find('div.progress').css('width', progress+'%');
				$('#b2make-statusbox-log li#'+id).find('span.progressvalue').text(progress+'%');
				$('#b2make-statusbox-log').scrollTop(
					$('#b2make-statusbox-log li#'+id).offset().top - $('#b2make-statusbox-log').offset().top + $('#b2make-statusbox-log').scrollTop()
				);
				
				if(progress >= 100){
					$('#b2make-statusbox-log li#'+id).find('span.status').html('Processando...');
					$('#b2make-statusbox-log li#'+id).find('span.progressvalue').text('');
				}
			}
		});
		
		$(input).bind('fileuploaddone', function (e, data){
			var dados = data.result;
			var id = dados.id_upload;
			
			var item=$('#b2make-statusbox-log li#'+id);
			item.find('div.progress').css('width', '100%');
			item.find('span.progressvalue').text('');
			item.addClass('success').find('span.status').html('Terminou!!!');
			
			if(p.callback)p.callback(dados);
			
			max_files--;
			
			if(max_files <= 0){
				max_files = 0;
				b2make.uploads_queueds[uploads_queueds_num] = false;
			}
		});
		
		b2make.uploads_queueds_num++;
	}
	
	function upload_files_mask_size(){
		if(b2make.uploadFiles.mask){
			b2make.uploadFiles.mask.css('width','100%');
			b2make.uploadFiles.mask.css('height',$(window).height()+'px');
		}
	}
	
	$.upload_files_mask_close = function(){
		if(b2make.uploadFiles.buttonClicked){
			setTimeout(function(){
				b2make.uploadFiles.mask.hide();
				b2make.uploadFiles.buttonClicked = false;
			},200);
		}
	}
	
	$.upload_files_mask_open = function(){
		if(!b2make.uploadFiles.mask){
			b2make.uploadFiles.mask = $('<div></div>');
			
			b2make.uploadFiles.mask.css('zIndex',9999);
			b2make.uploadFiles.mask.css('position','fixed');
			b2make.uploadFiles.mask.css('top','0px');
			b2make.uploadFiles.mask.css('left','0px');
			upload_files_mask_size();
			
			b2make.uploadFiles.mask.appendTo('body');
		}
		
		b2make.uploadFiles.mask.show();
	}
	
	$.upload_files_start_buttons = function(){
		$('.b2make-uploads-btn').on('mousedown touchstart',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			b2make.uploadFiles.buttonClickedSelf = true;
			b2make.uploadFiles.buttonClicked = true;
			$.upload_files_mask_open();
			
			$(this).find('.b2make-uploads-input').click();
		});
	}
	
	function upload_files(){
		b2make.uploadFiles = {};
		
		b2make.uploadFiles.buttonClicked = false;
		b2make.uploadFiles.ids = 0;
		
		$.upload_files_start_buttons();
		
		document.body.onfocus = function(){ 
			if(b2make.uploadFiles.buttonClicked){
				$.upload_files_mask_close();
			}
		}
	}
	
	upload_files();
	
	function start_classes(){
		b2make.menu_conteiner_aba_extra_list = new Array();
		
		if(variaveis_js.define_host){
			$('#b2make-menus-holders').hide();
			$('#b2make-menu-publish-2').hide();
		}
		
		if($("a[rel^='prettyPhoto']").length){
			var prettyphoto_var = {animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true};
			setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto(prettyphoto_var); }, 100);
		}
		
		$(document.body).on('selectstart dragstart','.b2make-noselect', function(evt){ evt.preventDefault(); return false; });
		$(document.body).on('selectstart dragstart','.b2make-select', function(evt){ evt.preventDefault(); return true; });
		
		$('.b2make-player-play').removeClass('b2make-player-pause_css');
		
		
		if(variaveis_js.host_installed){
			b2make.host_installed = true;
		}
		
		/* if(!b2make.host_installed){
			script_ler({id:'install.host'});
		} */
		
		if(variaveis_js.instagram_token){
			b2make.instagram_token = variaveis_js.instagram_token;
			pagina_options_change('instagram_token',b2make.instagram_token);
		}
		
		if(variaveis_js.reset_cache){
			localStorage['b2make.menu-paginas-results'] = '';
		}
	}
	
	start_classes();
	
	$.input_delay_to_change = function(p){
		if(!b2make.input_delay){
			b2make.input_delay = new Array();
			b2make.input_delay_count = 0;
		}
		
		b2make.input_delay_count++;
		
		var valor = b2make.input_delay_count;
		
		b2make.input_delay.push(valor);
		b2make.input_value = p.value;
		
		setTimeout(function(){
			if(b2make.input_delay[b2make.input_delay.length - 1] == valor){
				input_change_after_delay({value:b2make.input_value,trigger_selector:p.trigger_selector,trigger_event:p.trigger_event});
			}
		},b2make.input_delay_timeout);
	}
	
	function input_change_after_delay(p){
		$(p.trigger_selector).trigger(p.trigger_event,[p.value,b2make.input_delay_params]);
		
		b2make.input_delay = false;
	}
	
	function input_delay(){
		if(!b2make.input_delay_timeout) b2make.input_delay_timeout = 400;
		
	}
	
	input_delay();
	
	$.disk_usage_diskused_add = function(valor){
		valor = parseInt(valor);
		if(!b2make.disk_usage.diskused_val){
			b2make.disk_usage.diskused_val = parseInt(b2make.disk_usage.diskused.replace(/M/gi,''))*1000000;
			b2make.disk_usage.disklimit_val = parseInt(b2make.disk_usage.disklimit.replace(/M/gi,''))*1000000;
		}
		
		b2make.disk_usage.diskused_val = b2make.disk_usage.diskused_val + valor;
		if(b2make.disk_usage.diskused_val > b2make.disk_usage.disklimit_val) b2make.disk_usage.diskused_val = b2make.disk_usage.disklimit_val;
		
		var diskused = Math.floor((b2make.disk_usage.diskused_val / 1000000)) + 'M';
		
		if(diskused != b2make.disk_usage.diskused){
			b2make.disk_usage.diskused = diskused;
			disk_usage_change();
		}
	}
	
	$.disk_usage_diskused_del = function(valor){
		valor = parseInt(valor);
		if(!b2make.disk_usage.diskused_val){
			b2make.disk_usage.diskused_val = parseInt(b2make.disk_usage.diskused.replace(/M/gi,''))*1000000;
			b2make.disk_usage.disklimit_val = parseInt(b2make.disk_usage.disklimit.replace(/M/gi,''))*1000000;
		}
		
		b2make.disk_usage.diskused_val = b2make.disk_usage.diskused_val - valor;
		
		if(b2make.disk_usage.diskused_val < 0) b2make.disk_usage.diskused_val = 0;
		
		var diskused = Math.floor((b2make.disk_usage.diskused_val / 1000000)) + 'M';
		
		if(diskused != b2make.disk_usage.diskused){
			b2make.disk_usage.diskused = diskused;
			disk_usage_change();
		}
	}
	
	function disk_usage_change(){
		var disklimit = parseInt(b2make.disk_usage.disklimit.replace(/M/gi,''));
		var diskused = parseInt(b2make.disk_usage.diskused.replace(/M/gi,''));
		var disk_perc = (disklimit != 0 ? diskused / disklimit : 0);
		
		$('#b2make-disk-usage-title span').html((Math.floor(disk_perc*100))+'%');
		$('#b2make-disk-usage-title-2 span').html(b2make.disk_usage.diskused+'b '+b2make.disk_usage_saparator+' '+b2make.disk_usage.disklimit+'b');
		
		if(disk_perc >= b2make.disk_usage.warning_start){
			$('#b2make-disk-usage-slide').addClass('b2make-disk-usage-warning');
			$('#b2make-disk-usage-slide').removeClass('b2make-disk-usage-normal');
			$('#b2make-disk-usage-title span').addClass('b2make-disk-usage-warning');
			$('#b2make-disk-usage-title span').removeClass('b2make-disk-usage-normal');
		} else {
			$('#b2make-disk-usage-slide').removeClass('b2make-disk-usage-warning');
			$('#b2make-disk-usage-slide').addClass('b2make-disk-usage-normal');
			$('#b2make-disk-usage-title span').removeClass('b2make-disk-usage-warning');
			$('#b2make-disk-usage-title span').addClass('b2make-disk-usage-normal');
		}
		
		$('#b2make-disk-usage-slide').css('width',(Math.floor(disk_perc*100))+'%');
	}
	
	function disk_usage(){
		b2make.disk_usage = b2make_menu.disk_usage;
		
		b2make.disk_usage_saparator = b2make_menu.disk_usage_saparator;
	}
	
	disk_usage();
	
	function multi_screen_widgets_verify(){
		var conteiners = new Array();
		var screen_width = b2make_menu.multi_screen_width;
		
		if(variaveis_js.multi_screen_widgets_verify){
			$('.b2make-widget').each(function(){
				switch($(this).myAttr('data-type')){
					case 'conteiner':
						var height = parseInt($(this).height());
						
						$(this).css('min-width','0px');
						
						var percentage = screen_width / parseInt($(this).myAttr('data-area-largura'));
						
						var new_height = Math.floor(percentage*height);
						$(this).height(new_height+'px');
					break;
					case 'conteiner-area':
						conteiner_area_remove({obj:$(this).parent().get(0)});
					break;
					default:
						var conteiner = ($(this).parent().myAttr('data-type') == 'conteiner-area' ? $(this).parent().parent() : $(this).parent());
						var conteiner_id = conteiner.prop('id');
						var margin = b2make_menu.multi_screen_margin;
						var id = $(this).prop('id');
						var left = parseInt($(this).position().left);
						var top = parseInt($(this).position().top);
						var width = parseInt($(this).width());
						var height = parseInt($(this).height());
						
						var percentage = screen_width / parseInt(conteiner.myAttr('data-area-largura'));
						
						var new_width = Math.floor(percentage*width);
						var new_height = Math.floor(percentage*height);
						var new_left = Math.floor(percentage*left);
						var new_top = Math.floor(percentage*top);
						
						$(this).width(new_width+'px');
						$(this).height(new_height+'px');
						$(this).css('left',new_left+'px');
						$(this).css('top',new_top+'px');
						
						switch($(this).myAttr('data-type')){
							case 'texto':
								var font_size = $(this).myAttr('data-font-size');
								
								var new_font_size = Math.floor(percentage*font_size);
								
								$(this).css('font-size',new_font_size+'px');
							break;
							case 'imagem':
								b2make.imagem_resize_width = new_width;
								b2make.imagem_resize_height = new_height;
								imagem_resize($(this).find('.b2make-imagem'));
							break;
							case 'galeria':
							case 'agenda':
							case 'slideshow':
								b2make.conteiner_child_type = $(this).myAttr('data-type');
								b2make.conteiner_child_obj_custom = this;
								widgets_resize();
							break;
						}
				}
			});
		}
	}
	
	function multi_screen_mobile_position(){
		if(b2make.multi_screen.device == 'phone'){
			var site = $('#b2make-site');
			var width = parseInt($(window).outerWidth(true)) - (b2make_menu.open ? b2make_menu.width_conteiner : 0);
			var left = Math.floor((width - b2make.multi_screen.width) / 2);
			
			site.css('left',left);
		}
	}
	
	function multi_screen_mobile(){
		var site = $('#b2make-site');
		
		site.width(b2make.multi_screen.width);
		multi_screen_mobile_position();
		multi_screen_widgets_verify();
	}
	
	function multi_screen_change(p){
		if(!p) p = {};
		
		var id = p.id;
		var change = false;
		
		switch(id){
			case 'desktop':
				if(b2make.multi_screen.device != 'desktop'){
					b2make.multi_screen.device = 'desktop';
					change = true;
				}
			break;
			case 'phone':
				if(b2make.multi_screen.device != 'phone'){
					b2make.multi_screen.device = 'phone';
					change = true;
				}
			break;
		}
		
		if(change){
			var opcao = 'multi-screen-change';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					device : b2make.multi_screen.device
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
								window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'design','_self');
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
	}
	
	function multi_screen_reset_mobile(){
		var opcao = 'multi-screen-reset-mobile';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao
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
							window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'design','_self');
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
	
	function multi_screen(){
		b2make.multi_screen = {};
		
		b2make_menu.multi_screen_width = b2make.multi_screen.width = 600;
		b2make_menu.multi_screen_device = b2make.multi_screen.device = variaveis_js.multi_screen_device;
		b2make_menu.multi_screen_margin = 10;
		
		if(!b2make.msgs.multiScreenResetButton) b2make.msgs.multiScreenResetButton = 'Tem certeza que deseja resetar a p&aacute;gina mobile?';
		
		if(!variaveis_js.host_mobile){
			$('#b2make-screens-change-cont').hide();
		}
		
		if(b2make.multi_screen.device == 'phone'){
			multi_screen_mobile();
			$('#b2make-mobile-reset').show();
		}
		
		$('.b2make-screen-change').on('mouseup tap',function(e){
			console.log('1');
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			console.log('2');
			
			var id = $(this).prop('id');
			id = id.replace(/b2make-screen-change-/gi,'');
			
			multi_screen_change({id:id});
		});
		
		$('#b2make-listener').on('menu',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		});
		
		$('#b2make-mobile-reset').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var msg = b2make.msgs.multiScreenResetButton;
			
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-mobile-reset-yes',
				msg: msg
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-mobile-reset-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			multi_screen_reset_mobile();
		});
	}
	
	multi_screen();
	
	function importar_pagina_b2make(){
		$('#b2make-wso-ich-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var codigo_html = $('#b2make-wso-ich-textarea').val();
			
			var opcao = 'importar-pagina-b2make';
			var id = 'id';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					codigo_html : codigo_html
				},
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
								window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path,'_self');
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					
					$.carregamento_close();
				},
				error: function(txt){
					$.carregamento_close();
					console.log('ERROR AJAX - '+opcao+' - '+txt);
				}
			});
		});
	}
	
	importar_pagina_b2make();
	
	function limites_str(str,l1,l2){
		if(str.length >= l1 && str.length <= l2 )	
			return true;
		else
			return false;
	}
	
	function checkStr(pass){
		var er = new RegExp(/[^A-Za-z0-9_.\u00C0-\u00FF\s]/);
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
	
	$(window).bind('keydown',function(e) {
		var texto = ($(e.target).myAttr('id') == 'b2make-textarea') || $(e.target).myAttr('id') == 'b2make-iframe-textarea' || $(e.target).hasClass('b2make-input-select');
		
		if(e.keyCode == 13){ // enter
			if(texto){
				var str = $(e.target).val();
				var caret = $(e.target).caret();
				str = str.substr(0, caret) + "\n" + str.substr(caret, str.length - 1);
				$(e.target).val(str)
				$(e.target).range(caret+1,caret+1);
			}
		}
		
		if(e.keyCode == 37){ // left arrow
			if(b2make.conteiner_child_show){
				if(!texto){
					var conteiner = b2make.selecionador_objetos.conteiner;
					e.preventDefault();
					var value = $(conteiner).position().left;
					$(conteiner).css('left',value-b2make.arrow_fator);
					$(b2make.woc_position_left).val(value-b2make.arrow_fator);
				}
			}
		}
		
		if(e.keyCode == 38){ // up  arrow
			if(b2make.conteiner_child_show){
				if(!texto){
					var conteiner = b2make.selecionador_objetos.conteiner;
					e.preventDefault();
					var value = $(conteiner).position().top;
					$(conteiner).css('top',value-b2make.arrow_fator);
					$(b2make.woc_position_top).val(value-b2make.arrow_fator);
				}
			}
		}
		
		if(e.keyCode == 39){ // right  arrow
			if(b2make.conteiner_child_show){
				if(!texto){
					var conteiner = b2make.selecionador_objetos.conteiner;
					e.preventDefault();
					var value = $(conteiner).position().left;
					$(conteiner).css('left',value+b2make.arrow_fator);
					$(b2make.woc_position_left).val(value-b2make.arrow_fator);
				}
			}
		}
		
		if(e.keyCode == 40){ // down  arrow
			if(b2make.conteiner_child_show){
				if(!texto){
					var conteiner = b2make.selecionador_objetos.conteiner;
					e.preventDefault();
					var value = $(conteiner).position().top;
					$(conteiner).css('top',value+b2make.arrow_fator);
					$(b2make.woc_position_top).val(value-b2make.arrow_fator);
				}
			}
		}
		
		if(e.keyCode == 46){ // delete
			if(b2make.conteiner_child_show){
				if(!texto && !$(e.target).is('input') && !$(e.target).is('textarea')){
					var msg = b2make.msgs.conteinerDelete;
					msg = msg.replace(/#name#/gi,($("#"+b2make.conteiner_child_show).myAttr('data-name') ? $("#"+b2make.conteiner_child_show).myAttr('data-name') : $("#"+b2make.conteiner_child_show).myAttr('id')));
					
					$.dialogbox_open({
						confirm:true,
						calback_yes: 'b2make-woc-delete-yes',
						msg: msg
					});
				}
			}
			
			if(b2make.conteiner_show && !b2make.conteiner_child_show){
				var msg = b2make.msgs.conteinerDelete;
				msg = msg.replace(/#name#/gi,($("#"+b2make.conteiner_show).myAttr('data-name') ? $("#"+b2make.conteiner_show).myAttr('data-name') : $("#"+b2make.conteiner_show).myAttr('id')));
				
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-wom-delete-yes',
					msg: msg
				});
			}
		}
		
		/* if(e.which === 116 && (navigator.appVersion.indexOf("Win")!=-1) && !e.ctrlKey){ // F5
			b2make.reload = true;
			$.save();
			e.preventDefault();
			return false;
		} */
		
		if(e.ctrlKey || e.metaKey){
			b2make.keydown_action_ctrl_or_command = true;
			
			if(e.which === 83){ // CTRL + S
				if(e.shiftKey){
					b2make.save_publish = true;
					$.save();
					return false;
				} else {
					$('#b2make-menu-save').trigger('mouseup');
					e.preventDefault();
					return false;
				}
			}
			
			if(e.which === 67){ // CTRL + C
				if(b2make.copy_paste.inativo){
					b2make.copy_paste.widget = false;
					b2make.copy_paste.conteiner = false;
					b2make.copy_paste.atual = false;
					return;
				}
				
				var pai = b2make.conteiner_obj;
				var obj = b2make.conteiner_child_obj;
				
				if(obj){
					b2make.copy_paste.widget = false;
					$('.b2make-copy-widget').trigger('mouseup');
				} else {
					if(pai){
						b2make.copy_paste.conteiner = false;
						$('.b2make-copy-conteiner').trigger('mouseup');
					}
				}
				
				e.preventDefault();
				return false;
			}
			
			if(e.which === 86){ // CTRL + V
				if(b2make.copy_paste.inativo)return;
				
				var pai = b2make.conteiner_obj;
				var obj = b2make.conteiner_child_obj;
				
				switch(b2make.copy_paste.atual){
					case 'widget': $('.b2make-paste-widget').trigger('mouseup'); break;
					case 'conteiner': $('.b2make-paste-conteiner').trigger('mouseup'); break;
				}
				
				e.preventDefault();
				return false;
			}
			
			if(e.which === 68){ // CTRL + D
				var pai = b2make.conteiner_obj;
				var obj = b2make.conteiner_child_obj;
				
				if(obj){
					$('.b2make-duplicate-widget').trigger('mouseup');
				} else {
					if(pai){
						$('.b2make-duplicate-conteiner').trigger('mouseup');
					}
				}
				
				e.preventDefault();
				return false;
			}
			
			/* if(e.which === 82){ // CTRL + R 
				b2make.reload = true;
				$.save();
				e.preventDefault();
				return false;
			} */
			
			if(e.which === 90){ // CTRL + Z
				undo_redo_back();
			}
			
			if(e.which === 66){ // CTRL + B
				b2make.save_publish = true;
				$.save();
			}
		}
		
	});
	
	$(window).bind('keyup',function(e) {
		if(e.keyCode == 27){ // ESC
			if(
				b2make.perfil_foto_image_select || 
				b2make.template_foto_image_select ||
				b2make.segmento_foto_image_select
			){
				foto_perfil_close();
			}
			
			if(b2make.widget_child_move){
				b2make.widget_child_move = false;
			}
			
			if(b2make.widget_mask_hide){
				widget_mask_show();
			} else if(b2make.texto_for_textarea){
				textarea_for_texto();
			} else if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			} else {
				close_all();
			}
		}
		
		if(e.keyCode == 13){ // enter
			if(b2make.dialogbox){
				if(!b2make.dialogbox_dont_close_on_enter){
					$('.'+b2make.dialogbox_callback_yes).trigger('mouseup');
				} else {
					b2make.dialogbox_dont_close_on_enter = false;
				}
			}
		}
	
		if(b2make.widget_mask_hide)widget_mask_show();
		
		if(e.ctrlKey || e.metaKey){
			b2make.keydown_action_ctrl_or_command = false;
		}
	});
	
	$(window).resize(function() {
		if(b2make.widget_mask_hide)widget_mask_show();
		
		if(b2make.conteiner_show){
			conteiner_window_change();
		}
		
		holder_menus_positions();
		holders_sub_options();
		conteiners_update();
		dialogbox_position();
		lightbox_position();
		carregando_position();
		menu_paginas_resize();
		biblioteca_imagens_conteiners_update();
		ruler_position();
		multi_screen_mobile_position();
	});
	
	$(window).bind('mouseenter',function(e){
		var type = $(e.target).myAttr('data-type');
		var type_out = $(e.target).myAttr('data-type-out');
		var id = $(e.target).myAttr('id');
		var widget = $(e.target).hasClass('b2make-widget') || $(e.target).hasClass('b2make-imagem') || $(e.target).hasClass('b2make-texto-table') || $(e.target).hasClass('b2make-texto-cel') || $(e.target).hasClass('b2make-widget-mask');
		
		if(type_out) type = type_out;
		
		if(b2make.widget_mask_hide)widget_mask_show();
		
		if(widget)switch(type){
			case 'conteiner':
			case 'conteiner-area':
				if(!b2make.conteiner_child_show){
					if(b2make.conteiner_show){
						$(this).css('cursor','move');
					} else {
						$(this).css('cursor','default');
					}
				} else {
					$(this).css('cursor','default');
				}
			break;
			case 'texto':
			case 'imagem':
			case 'widget-out':
				if(b2make.conteiner_show && b2make.conteiner_child_show){
					$(this).css('cursor','move');
				} else {
					$(this).css('cursor','default');
				}
			break;
		}
	});
	
	$(window).bind('mouseleave',function(e){
		var type = $(e.target).myAttr('data-type');
		var type_out = $(e.target).myAttr('data-type-out');
		var widget = $(e.target).hasClass('b2make-widget') || $(e.target).hasClass('b2make-imagem') || $(e.target).hasClass('b2make-texto-table') || $(e.target).hasClass('b2make-texto-cel') || $(e.target).hasClass('b2make-widget-mask');
		
		if(type_out) type = type_out;
		
		if(b2make.widget_mask_hide)widget_mask_show();
		
		if(widget)switch(type){
			case 'conteiner-area':
			case 'conteiner':
			case 'texto':
			case 'imagem':
			case 'widget-out':
				$(this).css('cursor','default');
			break;
			
		}
	});
	
	$(window).bind('mousedown touchstart',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var type = $(e.target).myAttr('data-type');
		var obj_target = $(e.target).get(0);
		var type_out = $(e.target).myAttr('data-type-out');
		var conteiner_banner = $(e.target).hasClass('b2make-conteiner-banners-image') || $(e.target).hasClass('b2make-conteiner-banners-image-cont') || $(e.target).hasClass('b2make-conteiner-banners-image-titulo') || $(e.target).hasClass('b2make-conteiner-banners-image-sub-titulo');
		var widget = $(e.target).hasClass('b2make-widget') || $(e.target).hasClass('b2make-imagem') || $(e.target).hasClass('b2make-texto-table') || $(e.target).hasClass('b2make-texto-cel') || $(e.target).hasClass('b2make-widget-mask');
		var dont_move = false;
		var imagem = $(e.target).hasClass('b2make-imagem');
		var widget_out = $(e.target).hasClass('b2make-widget-mask');
		var texto_table = $(e.target).hasClass('b2make-texto-table');
		var texto_cel = $(e.target).hasClass('b2make-texto-cel');
		var selecionador_box = ($(e.target).hasClass('b2make-selecionador-objetos-box-mini') | $(e.target).hasClass('b2make-selecionador-objetos-box'));
		var selecionador_rotate = ($(e.target).hasClass('b2make-selecionar-objetos-rotate-mask') | $(e.target).hasClass('b2make-selecionar-objetos-rotate'));
		var id = $(e.target).myAttr('id');
		var selecionador = (id == 'b2make-selecionador-objetos-mask' ? true : false);
		var imagem_padrao = $(e.target).hasClass('b2make-biblioteca-imagens-default');
		
		if(b2make.multiselect.ativo){
			if(id != 'b2make-multiselect-cont' && id != 'b2make-widget-options-multiselect'){
				multi_select_close();
			}
		}
		
		$('body').addClass('b2make-noselect');
		$('body').removeClass('b2make-select');
		
		if(type_out) type = type_out;
		
		var uploadButtonClicked = false;
		if(b2make.uploadFiles.buttonClicked && !b2make.uploadFiles.buttonClickedSelf){
			uploadButtonClicked = true;
		}
		
		b2make.uploadFiles.buttonClickedSelf = false;
		
		if(
			!uploadButtonClicked
		){
			if(
				texto_cel ||
				texto_table ||
				widget_out ||
				imagem ||
				imagem_padrao 
			){
				e.stopPropagation();
				
				if((texto_cel && b2make.conteiner_child_show != $(obj_target).parent().parent().myAttr('id')) || (!texto_cel && b2make.conteiner_child_show != $(obj_target).parent().myAttr('id'))){
					dont_move = true;
				}
			}
		}
		
		if(selecionador_box){
			if($(e.target).hasClass('b2make-selecionador-objetos-box-mini')){
				id = $(e.target).parent().myAttr('id');
			}
			
			var conteiner = b2make.selecionador_objetos.conteiner;
			var top = $(conteiner).offset().top;
			var left = $(conteiner).offset().left;
			var height = $(conteiner).height();
			var width = $(conteiner).width();
			
			b2make.widget_child_start_x = left;
			b2make.widget_child_start_y = top;
			b2make.widget_child_start_height = height;
			b2make.widget_child_start_width = width;
			
			switch(id){
				case 'b2make-selecionador-objetos-top-left':
					b2make.selecionador_n_resize = true;
					b2make.selecionador_w_resize = true;
				break;
				case 'b2make-selecionador-objetos-top-right':
					b2make.selecionador_n_resize = true;
					b2make.selecionador_e_resize = true;
				break;
				case 'b2make-selecionador-objetos-bottom-left':
					b2make.selecionador_s_resize = true;
					b2make.selecionador_w_resize = true;
				break;
				case 'b2make-selecionador-objetos-bottom-right':
					b2make.selecionador_s_resize = true;
					b2make.selecionador_e_resize = true;
				break;
			}
		}
		
		if(selecionador){
			var conteiner = b2make.selecionador_objetos.conteiner;
			
			top = $(conteiner).offset().top;
			left = $(conteiner).offset().left;
			height = $(conteiner).height();
			width = $(conteiner).width();
			
			b2make.selecionador_move_x = width/2 - (e.pageX - left);
			b2make.selecionador_move_y = height/2 - (e.pageY - top);
			
			e.stopPropagation();
			b2make.selecionador_move = true;
		}
		
		if(selecionador_rotate){
			e.stopPropagation();
			b2make.selecionador_rotate_move = true;
		}
		
		if(widget || conteiner_banner){
			if(conteiner_banner) type = 'conteiner';
			switch(type){
				case 'conteiner-area':
					if(b2make.conteiner_show){
						
						b2make.widget_move = true;
						var obj_pai = b2make.conteiner_obj;
						var obj_area = b2make.conteiner_obj_area;
						
						$(b2make.shadow).fadeOut(b2make.fade_time);
						
						var top = $(obj_pai).offset().top - parseInt($('#b2make-site').css('top'));
						var left = $(obj_pai).offset().left;
						var left_area = $(obj_area).offset().left;
						var height = $(obj_pai).height();
						var width = $(obj_area).width();
						var margim = b2make.widget_margim_correcao;
						
						b2make.widget_n_resize = false;
						b2make.widget_s_resize = false;
						b2make.widget_w_resize = false;
						b2make.widget_e_resize = false;
						
						if($(obj_pai).offset().top + margim >= e.pageY){
							b2make.widget_n_resize = true;
						} else if($(obj_pai).offset().top + height - margim <= e.pageY){
							b2make.widget_s_resize = true;
						} else if(left_area + b2make.widget_border + margim >= e.pageX){
							b2make.widget_w_resize = true;
						} else if(left + b2make.widget_border + width - margim <= e.pageX){
							b2make.widget_e_resize = true;
						}
						
						b2make.widget_start_top = top;
						
						b2make.widget_start_x = e.pageX;
						b2make.widget_start_y = e.pageY - top;
						b2make.widget_start_height = height;
						
						$(obj_pai).css('position','absolute');
						$(obj_pai).css('top',top);
						
						if($(obj_pai).myAttr('data-area-fixed') && $(obj_pai).myAttr('data-area-fixed') != 'n'){
							if(b2make_menu.open){
								$(obj_pai).css('left','0px');
								$(obj_pai).css('width',$(window).width()-b2make_menu.width_conteiner+16);
							} else {
								$(obj_pai).css('left','0px');
								$(obj_pai).css('width',$(window).width()-4);
							}
						}
						
						conteiner_before_after();
					}
				break;
				case 'conteiner':
					if(b2make.conteiner_show){
						b2make.widget_move = true;
						var obj = b2make.conteiner_obj;
						
						$(b2make.shadow).fadeOut(b2make.fade_time);
						
						var top = $(obj).offset().top - parseInt($('#b2make-site').css('top'));
						var left = $(obj).offset().left;
						var height = $(obj).height();
						var margim = b2make.widget_margim_correcao;
						
						b2make.widget_n_resize = false;
						b2make.widget_s_resize = false;
						b2make.widget_w_resize = false;
						b2make.widget_e_resize = false;
						
						if($(obj).offset().top + margim >= e.pageY){
							b2make.widget_n_resize = true;
						} else if($(obj).offset().top + height - margim <= e.pageY){
							b2make.widget_s_resize = true;
						}
						
						b2make.widget_start_top = top;
						
						b2make.widget_start_x = e.pageX;
						b2make.widget_start_y = e.pageY - top;
						b2make.widget_start_height = height;
						
						$(obj).css('position','absolute');
						$(obj).css('top',top);
						
						if($(obj).myAttr('data-area-fixed') && $(obj).myAttr('data-area-fixed') != 'n'){
							if(b2make_menu.open){
								$(obj).css('left','0px');
								$(obj).css('width',$(window).width()-b2make_menu.width_conteiner+16);
							} else {
								$(obj).css('left','0px');
								$(obj).css('width',$(window).width()-4);
							}
						}
						
						conteiner_before_after();
					}
				break;
			}
		} else {
			switch(type){
				case 'sub_menu':
					if(b2make.conteiner_show){
						b2make.widget_sub_menu_move = true;
						
						e.preventDefault();
						obj = $(b2make.widget_sub_options).get(0);
						
						top = $(obj).offset().top;
						left = $(obj).offset().left;
						height = $(obj).height();
						margim = b2make.widget_margim_correcao;
						
						if(top + b2make.widget_border + height - margim <= e.pageY && b2make.widget_sub_options_open){
							b2make.widget_sub_menu_s_resize = true;
						}
					}
				break;
			}
		}
	});
	
	$(window).bind('mouseup touchend',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var type = $(e.target).myAttr('data-type');
		var id = $(e.target).myAttr('id');
		var conteiner_banner = $(e.target).hasClass('b2make-conteiner-banners-image') || $(e.target).hasClass('b2make-conteiner-banners-image-cont') || $(e.target).hasClass('b2make-conteiner-banners-image-titulo') || $(e.target).hasClass('b2make-conteiner-banners-image-sub-titulo');
		var obj_target = $(e.target).get(0);
		var obj;
		var widget = $(e.target).hasClass('b2make-widget');
		var imagem = $(e.target).hasClass('b2make-imagem');
		var widget_out = $(e.target).hasClass('b2make-widget-mask');
		var texto_table = $(e.target).hasClass('b2make-texto-table');
		var texto_cel = $(e.target).hasClass('b2make-texto-cel');
		var imagem_padrao = $(e.target).hasClass('b2make-biblioteca-imagens-default');

		$('body').removeClass('b2make-noselect');
		$('body').addClass('b2make-select');
		
		if(b2make.menu_paginas_w_resize){
			b2make.menu_paginas_w_resize = false;
			return;
		}
		
		if(b2make.selecionador_move){
			b2make.selecionador_move = false;
			return;
		}
		
		if(b2make.selecionador_rotate_move){
			b2make.selecionador_rotate_move = false;
			return;
		}
		
		if(
			b2make.selecionador_n_resize || 
			b2make.selecionador_s_resize || 
			b2make.selecionador_e_resize || 
			b2make.selecionador_w_resize
		){
			obj = b2make.conteiner_child_obj;
			var type2 = $(obj).myAttr('data-type');
			
			$(obj).find('.b2make-widget-out').find('iframe').show();
			
			switch(type2){
				case 'facebook':
					facebook_resize();
				break;
				case 'soundcloud':
					sound_cloud_resize();
				break;
				case 'youtube':
					youtube_resize();
				break;
				default:
					$('#b2make-listener').trigger('widgets-resize-finish');
			}
			
			b2make.selecionador_n_resize = false;
			b2make.selecionador_s_resize = false;
			b2make.selecionador_e_resize = false;
			b2make.selecionador_w_resize = false;
			
			return;
		}
		
		if($('#b2make-widget-menu-holder').length > 0){
			var widget_menu_holder = $('#b2make-widget-menu-holder');
			
			if(widget_menu_holder.myAttr('data-open') == '1'){
				widget_menu_holder.myAttr('data-open','0');
				widget_menu_holder.hide();
				close_all();
			}
		}
		
		var upload_clicked = false;
		if(b2make.upload_clicked){
			for(var i=0;i<b2make.upload_clicked.length;i++){
				if(b2make.upload_clicked[i]){
					upload_clicked = true;
				}
			}
		}
		
		if(
			!upload_clicked
		){
			if(widget || conteiner_banner){
				if(conteiner_banner) type = 'conteiner';
				switch(type){
					case 'conteiner-area':
						if(!b2make.conteiner_show){
							b2make.conteiner_obj = $(obj_target).parent().get(0);
							$(obj_target).css('cursor','move');
							conteiner_open();
						} else {
							if(b2make.conteiner_child_show){
								if(
									!b2make.widget_child_n_resize &&
									!b2make.widget_child_s_resize &&
									!b2make.widget_child_e_resize &&
									!b2make.widget_child_w_resize
								){
									$.conteiner_child_close();
									$(b2make.widget_options_childreen).hide();
								}
							}
						}
					break;
					case 'conteiner':
						if(!b2make.conteiner_show){
							if(conteiner_banner){
								if($(e.target).hasClass('b2make-conteiner-banners-image')) b2make.conteiner_obj = $(obj_target).parent().parent().get(0);
								if($(e.target).hasClass('b2make-conteiner-banners-image-cont')) b2make.conteiner_obj = $(obj_target).parent().parent().parent().get(0);
								if($(e.target).hasClass('b2make-conteiner-banners-image-titulo')) b2make.conteiner_obj = $(obj_target).parent().parent().parent().parent().get(0);
								if($(e.target).hasClass('b2make-conteiner-banners-image-sub-titulo')) b2make.conteiner_obj = $(obj_target).parent().parent().parent().parent().get(0);
							} else {
								b2make.conteiner_obj = obj_target;
							}
							
							$(obj_target).css('cursor','move');
							conteiner_open();
						} else {
							if(b2make.conteiner_child_show){
								if(
									!b2make.widget_child_n_resize &&
									!b2make.widget_child_s_resize &&
									!b2make.widget_child_e_resize &&
									!b2make.widget_child_w_resize
								){
									$.conteiner_child_close();
									$(b2make.widget_options_childreen).hide();
								}
							}
						}
					break;
					case 'texto':
					case 'imagem':
						e.stopPropagation();
						
						if(!b2make.conteiner_child_show){
							if(!b2make.conteiner_show){
								b2make.conteiner_obj = $(obj_target).parent().get(0);
								
								if($(b2make.conteiner_obj).myAttr('data-type') == 'conteiner-area') b2make.conteiner_obj = $(b2make.conteiner_obj).parent().get(0);
								conteiner_open();
							}
							
							b2make.conteiner_child_obj = $(obj_target).get(0);
							
							$(obj_target).css('cursor','move');
							$.conteiner_child_open({select:true,widget_type:type});
						
						} else if(b2make.conteiner_child_show != $(obj_target).parent().myAttr('id')){
							b2make.conteiner_child_other = true;
							$.conteiner_child_close();
							
							b2make.conteiner_child_obj = $(obj_target).get(0);
							
							$(obj_target).css('cursor','move');
							$.conteiner_child_open({select:true,widget_type:type});
						}
					break;
				} 
			} else if(
				texto_cel ||
				texto_table ||
				widget_out ||
				imagem ||
				imagem_padrao 
			){
				e.stopPropagation();
				
				if(imagem_padrao)type = 'imagem';
				
				if(!b2make.conteiner_child_show){
					if(!b2make.conteiner_show){
						if(texto_cel){
							b2make.conteiner_obj = $(obj_target).parent().parent().parent().get(0);
						} else {
							b2make.conteiner_obj = $(obj_target).parent().parent().get(0);
						}
						
						if($(b2make.conteiner_obj).myAttr('data-type') == 'conteiner-area') b2make.conteiner_obj = $(b2make.conteiner_obj).parent().get(0);
						conteiner_open();
					} else {
						if(b2make.widget_sub_options_open){
							widget_sub_options_close();							
						}
					}
					
					if(texto_cel){
						b2make.conteiner_child_obj = $(obj_target).parent().parent().get(0);
					} else {
						b2make.conteiner_child_obj = $(obj_target).parent().get(0);
					}
					
					$(obj_target).css('cursor','move');
					$.conteiner_child_open({select:true,widget_type:type});
				
				} else if((texto_cel && b2make.conteiner_child_show != $(obj_target).parent().parent().myAttr('id')) || (!texto_cel && b2make.conteiner_child_show != $(obj_target).parent().myAttr('id'))){
					if(imagem)b2make.conteiner_child_other = true;
					$.conteiner_child_close();
					
					if(texto_cel){
						b2make.conteiner_child_obj = $(obj_target).parent().parent().get(0);
					} else {
						b2make.conteiner_child_obj = $(obj_target).parent().get(0);
					}
					
					$(obj_target).css('cursor','move');
					$.conteiner_child_open({select:true,widget_type:type});
				}
			}
			
			if(b2make.widget_move){
				obj = b2make.conteiner_obj;
				
				var position = 'relative';
				
				if($(obj).myAttr('data-position')) position = $(obj).myAttr('data-position');
				
				b2make.widget_move = false;
				
				if($(b2make.shadow).length == 0) $('<div id="b2make-shadow"></div>').appendTo('#b2make-site');
				$(b2make.shadow).fadeIn(b2make.fade_time);
				
				if(position == 'relative'){
					$(obj).css('top',parseInt(b2make.widget_conteiner_mask.offset().top) - parseInt($('#b2make-site').css('top')));
					b2make.widget_conteiner_mask.height($(obj).height());
				} else {
					if(
						!b2make.widget_w_resize &&
						!b2make.widget_e_resize &&
						!b2make.widget_n_resize &&
						!b2make.widget_s_resize
					){
						var widget_start_y = b2make.widget_start_y;
						var pos_y = parseInt(e.pageY) - parseInt($(window).scrollTop() + parseInt(b2make.widget_start_y) - parseInt($('#b2make-site').css('top'))) + 1;
						
						var status = $(obj).myAttr('data-area-fixed');
						
						if(status == 'b'){
							var bottom = $(window).height() - pos_y - $(obj).outerHeight(true);
							
							$(obj).css('top','auto');
							$(obj).css('bottom',bottom+'px');
						} else {
							$(obj).css('top',pos_y);
							$(obj).css('bottom','auto');
						}
						
						$(obj).css('position','fixed');
					}
				}
				
				if($(obj).myAttr('data-area-fixed') && $(obj).myAttr('data-area-fixed') != 'n'){
					if(b2make_menu.open){
						if(
							!b2make.widget_w_resize &&
							!b2make.widget_e_resize &&
							!b2make.widget_n_resize &&
							!b2make.widget_s_resize
						){
							$(obj).css('left',b2make_menu.width_conteiner+'px');
							$(obj).css('width',$(window).width()-b2make_menu.width_conteiner+16);
						} else {
							$(obj).css('left','0px');
							$(obj).css('width',$(window).width()-b2make_menu.width_conteiner+16);
						}
					} else {
						$(obj).css('left','0px');
						$(obj).css('width',$(window).width()-4);
					}
					
					if($(obj).myAttr('data-area-fixed') == 'b'){
						var baixo = parseInt($(window).scrollTop()) + parseInt($(window).height() - $(obj).offset().top - $(obj).outerHeight(true));
						$(obj).myAttr('data-area-fixed-baixo',baixo);
					}
				}
			}
			
			if(b2make.widget_sub_menu_move){
				b2make.widget_sub_menu_move = false;
			}
		}
		
		if(b2make.upload_clicked){
			for(var i=0;i<b2make.upload_clicked.length;i++){
				if(b2make.upload_clicked[i] && id != 'SWFUpload_'+i){
					b2make.upload_clicked[i] = false;
				}
			}
		}
		
		if(b2make.wot_fontes_open){
			if(!$(e.target).parent().is('#b2make-wot-fontes') && !$(e.target).parent().is('.b2make-wot-google-fontes') && !$(e.target).is('#b2make-wot-google-fontes') && !$(e.target).is('#b2make-wot-count-teste') && !$(e.target).is('#b2make-wot-fontes')){
				text_fontes_close();
			}
		}
		
		if(b2make.fonts_open){
			if(!$(e.target).parent().is('.b2make-fonts-count') && !$(e.target).parent().is('.b2make-wot-google-fontes') && !$(e.target).is('.b2make-fonts-google-fontes') && !$(e.target).is('.b2make-fonts-count-teste') && !$(e.target).is('.b2make-fonts-fontes')){
				fonts_close();
			}
			if($(e.target).is('.b2make-fonts-count-ok')){
				fonts_close();
			}
		}
		
	});
	
	$(window).bind('mousemove touchmove',function(e){
		var id = $(e.target).myAttr('id');
		var type = $(e.target).myAttr('data-type');
		var type_out = $(e.target).myAttr('data-type-out');
		var top;
		var left;
		var height;
		var width;
		var margim;
		var obj;
		var obj_pai;
		var widget = $(e.target).hasClass('b2make-widget') || $(e.target).hasClass('b2make-imagem') || $(e.target).hasClass('b2make-texto-table') || $(e.target).hasClass('b2make-texto-cel') || $(e.target).hasClass('b2make-widget-mask');
		
		
		if(
			b2make.selecionador_n_resize || 
			b2make.selecionador_s_resize || 
			b2make.selecionador_e_resize || 
			b2make.selecionador_w_resize
		){
			var conteiner = b2make.selecionador_objetos.conteiner;
			var left_pai = (b2make.multi_screen.device == 'phone' ? $('#b2make-site').offset().left : 0);
			
			if(b2make.conteiner_obj_area){
				obj_pai = b2make.conteiner_obj_area;
				left_pai = $(obj_pai).offset().left;
			} else {
				obj_pai = b2make.conteiner_obj;
			}
			
			top = $(conteiner).offset().top;
			top_pai = $(obj_pai).offset().top;
			left = $(conteiner).offset().left;
			height = $(conteiner).height();
			width = $(conteiner).width();
			margim = ($(conteiner).outerWidth() - $(conteiner).width());
			
			if(b2make.selecionador_n_resize){
				if(e.pageY < top + height - 20){
					if(e.pageY >= b2make.widget_child_start_y){
						height = b2make.widget_child_start_height - (e.pageY - b2make.widget_child_start_y);
					} else {
						height = b2make.widget_child_start_height + (b2make.widget_child_start_y - e.pageY);
					}
					
					$(conteiner).css('top',e.pageY - top_pai + b2make.selecionador_ajuste_n);
					$(conteiner).height(height);
					$(b2make.woc_height).val(parseInt(height));
					b2make.imagem_resize_height = height;
				}
				
				if(b2make.selecionador_w_resize){
					if(e.pageX < left + width - 20){
						if(e.pageX >= b2make.widget_child_start_x){
							width = b2make.widget_child_start_width - (e.pageX - b2make.widget_child_start_x);
						} else {
							width = b2make.widget_child_start_width + (b2make.widget_child_start_x - e.pageX);
						}
						
						$(conteiner).css('left',e.pageX - left_pai + b2make.selecionador_ajuste_nw);
						$(conteiner).width(width);
						$(b2make.woc_width).val(parseInt(width));
						b2make.imagem_resize_width = width;
					}
				} else if(b2make.selecionador_e_resize){
					if(e.pageX - left > 20){
						left = e.pageX - left - b2make.selecionador_ajuste_ne - margim; 
						$(conteiner).width(left);
						$(b2make.woc_width).val(parseInt(left));
						b2make.imagem_resize_width = left;
					}
				}
			} else if(b2make.selecionador_s_resize){
				if(e.pageY - top > 20){
					top = e.pageY - top - b2make.selecionador_ajuste_s - margim; 
					$(conteiner).height(top);
					$(b2make.woc_height).val(parseInt(top));
					b2make.imagem_resize_height = top;
				}
				
				if(b2make.selecionador_w_resize){
					if(e.pageX < left + width - 20){
						if(e.pageX >= b2make.widget_child_start_x){
							width = b2make.widget_child_start_width - (e.pageX - b2make.widget_child_start_x);
						} else {
							width = b2make.widget_child_start_width + (b2make.widget_child_start_x - e.pageX);
						}
						
						$(conteiner).css('left',e.pageX - left_pai + b2make.selecionador_ajuste_sw);
						$(conteiner).width(width);
						$(b2make.woc_width).val(parseInt(width));
						b2make.imagem_resize_width = width;
					}
				} else if(b2make.selecionador_e_resize){
					if(e.pageX - left > 20){
						left = e.pageX - left - b2make.selecionador_ajuste_se - margim; 
						$(conteiner).width(left);
						$(b2make.woc_width).val(parseInt(left));
						b2make.imagem_resize_width = left;
					}
				}
			} else if(b2make.selecionador_w_resize){
				if(e.pageX < left + width - 20){
					if(e.pageX >= b2make.widget_child_start_x){
						width = b2make.widget_child_start_width - (e.pageX - b2make.widget_child_start_x);
					} else {
						width = b2make.widget_child_start_width + (b2make.widget_child_start_x - e.pageX);
					}
					
					$(conteiner).css('left',e.pageX - left_pai);
					$(conteiner).width(width);
					$(b2make.woc_width).val(parseInt(width));
					b2make.imagem_resize_width = width;
				}
			} else if(b2make.selecionador_e_resize){
				if(e.pageX - left > 20){
					left = e.pageX - left; 
					$(conteiner).width(left);
					$(b2make.woc_width).val(parseInt(left));
					b2make.imagem_resize_width = left;
				}
			}
			
			obj = b2make.conteiner_child_obj;
			
			textarea_resize($(conteiner).outerWidth(),$(conteiner).outerHeight());
			textarea_iframe_resize($(conteiner).outerWidth(),$(conteiner).outerHeight());
			imagem_resize($(obj).find('.b2make-imagem'));
			iframe_resize();
			widgets_resize();
			
			$(b2make.woc_position_top).val(parseInt($(conteiner).css('top')));
			$(b2make.woc_position_left).val(parseInt($(conteiner).css('left')));
		}
		
		if(b2make.selecionador_move){
			var conteiner = b2make.selecionador_objetos.conteiner;
			var left_pai =  (b2make.multi_screen.device == 'phone' ? $('#b2make-site').offset().left : 0);
			
			if(b2make.conteiner_obj_area){
				obj_pai = b2make.conteiner_obj_area;
			} else {
				obj_pai = b2make.conteiner_obj;
			}
			
			left_pai = $(obj_pai).offset().left;
			
			top = $(conteiner).position().top;
			top_pai = $(obj_pai).offset().top;
			left = $(conteiner).position().left;
			height = $(conteiner).height();
			width = $(conteiner).width();
			margim = b2make.widget_margim_correcao;
			
			top = e.pageY - top_pai - height/2 + b2make.selecionador_move_y;
			left = e.pageX - left_pai  - width/2 + b2make.selecionador_move_x;
			
			$(conteiner).css('top',top);
			$(conteiner).css('left',left);

			$(b2make.woc_position_top).val(parseInt($(conteiner).css('top')));
			$(b2make.woc_position_left).val(parseInt($(conteiner).css('left')));
		}
		
		if(b2make.selecionador_rotate_move){
			var conteiner = b2make.selecionador_objetos.conteiner;
			var left_pai = 0;
			var angulo = 0;
			var seno_val = 0;
			var x1 = 0;
			var y1 = 0;
			var x2 = 0;
			var y2 = 0;
			var cat_o = 0;
			var cat_a = 0;
			var hip = 0;
			
			if(b2make.conteiner_obj_area){
				obj_pai = b2make.conteiner_obj_area;
				left_pai = $(obj_pai).offset().left;
			} else {
				obj_pai = b2make.conteiner_obj;
			}
			
			top = $(conteiner).offset().top;
			top_pai = $(obj_pai).offset().top;
			left = $(conteiner).offset().left;
			height = parseInt($(conteiner).height());
			width = parseInt($(conteiner).width());
			margim = ($(conteiner).outerWidth() - $(conteiner).width());;
			
			x1 = x2 = left + width / 2 + margim / 2;
			y1 = e.pageY;
			y2 = top_pai + top + height/2;
			
			cat_o = e.pageX - x1;
			cat_a = y2-y1;
			hip = Math.sqrt(Math.pow(cat_a,2)+Math.pow(cat_o,2));
			
			if(hip != 0){
				if(cat_o == 0){
					if(e.pageY > y2){
						angulo = 180;
					} else {
						angulo = 0;
					}
				} else {
					var radians = Math.atan(cat_o/cat_a);
					var pi = 4 * Math.atan(1);
					angulo = radians * (180/pi);
					
					angulo = Math.floor(angulo);
					
					if(e.pageX > x1){
						if(e.pageY > y2){
							angulo = angulo + 180;
						} else {
							
						}
					} else {
						if(e.pageY > y2){
							angulo = angulo + 180;
						} else {
							angulo = angulo + 360;
						}
					}
				}
			}
			
			$('#b2make-woc-rotate-value').val(angulo);
			
			$(conteiner).myAttr('data-angulo',angulo);
			$(conteiner).css('-moz-transform','rotate('+angulo+'deg)');
			$(conteiner).css('-webkit-transform','rotate('+angulo+'deg)');
			$(conteiner).css('-webkit-backface-visibility','hidden');
			$(conteiner).css('-o-transform','rotate('+angulo+'deg)');
			$(conteiner).css('-ms-transform','rotate('+angulo+'deg)');
			$(conteiner).css('transform','rotate('+angulo+'deg)');
		}
		
		if(type_out) type = type_out;
		
		if(widget){
			if(b2make.widget_mask_hide)widget_mask_show();
			switch(type){
				case 'conteiner-area':
					if(b2make.conteiner_show){
						e.preventDefault();
						var top_pai;
						var right;
						
						obj_pai = b2make.conteiner_obj;
						var obj_area = b2make.conteiner_obj_area;
						
						if(!b2make.conteiner_child_show){
							top_pai = $(obj_pai).offset().top;
							left = $(obj_area).offset().left;
							height = $(obj_pai).height();
							width = $(obj_area).width();
							margim = b2make.widget_margim_correcao;
							
							if(top_pai + margim >= e.pageY){
								$(obj_area).css('cursor','n-resize');
							} else if(top_pai + height - margim <= e.pageY){
								$(obj_area).css('cursor','s-resize');
							} else if(left + b2make.widget_border + margim >= e.pageX){
								$(obj_area).css('cursor','w-resize');
							} else if(left + b2make.widget_border + width - margim <= e.pageX){
								$(obj_area).css('cursor','e-resize');
							} else {
								$(obj_area).css('cursor','move');
								$(obj_pai).css('cursor','move');
							}
						} else {
							$(obj_area).css('cursor','default');
							$(obj_pai).css('cursor','default');
						}
					}
				break;
				case 'conteiner':
					if(b2make.conteiner_show){
						e.preventDefault();
						obj = b2make.conteiner_obj;
						
						if(!b2make.conteiner_child_show){
							top = $(obj).offset().top;
							left = $(obj).offset().left;
							height = $(obj).height();
							margim = b2make.widget_margim_correcao;
							
							if(top + margim >= e.pageY){
								$(obj).css('cursor','n-resize');
							} else if(top + height - margim <= e.pageY){
								$(obj).css('cursor','s-resize');
							} else {
								$(obj).css('cursor','move');
							}
						} else {
							$(obj).css('cursor','default');
						}
					}
				break;
			}
		} else {
			switch(type){
				case 'sub_menu':
					if(b2make.conteiner_show){
						e.preventDefault();
						obj = $(b2make.widget_sub_options).get(0);
						
						top = $(obj).offset().top;
						left = $(obj).offset().left;
						height = $(obj).height();
						margim = b2make.widget_margim_correcao;
						
						if(top + b2make.widget_border + height - margim <= e.pageY && b2make.widget_sub_options_open){
							$(obj).css('cursor','s-resize');
						} else {
							$(obj).css('cursor','default');
						}
					}
				break;
				default:
					if(id == 'b2make-widget-sub-options' || id == 'b2make-widget-sub-options-holder' || id == 'b2make-biblioteca-imagens-lista'){
						obj = $(b2make.widget_sub_options).get(0);
						$(obj).css('cursor','default');
					}
			}
		}
		
		if(b2make.widget_move && !b2make.conteiner_child_show){
			obj = b2make.conteiner_obj;
			var obj_area = b2make.conteiner_obj_area;
			var widget_start_y = b2make.widget_start_y;
			
			top = $(obj).offset().top;
			if(obj_area) left = $(obj_area).offset().left;
			height = $(obj).height();
			if(obj_area) width = $(obj_area).width();
			if(obj_area) var width_pai = $(obj).width();
			margim = b2make.widget_margim_correcao;
			var position = 0;
			
			if($(obj).myAttr('data-position')) position = $(obj).myAttr('data-position');
		
			if(position == 'fixed'){
				top = top - $(window).scrollTop();
			}
			
			if(b2make.widget_w_resize){
				if(e.pageX < width_pai/2 - 20){
					var ajuste_menu = 0;
					
					if(b2make_menu.open) ajuste_menu = b2make_menu.width_conteiner;
					
					width = Math.floor(width_pai - e.pageX*2) + 2*(ajuste_menu-5);
					$(obj_area).width(width);
					$('#b2make-conteiner-area-largura').val(width);
					$(obj).myAttr('data-area-largura',width);
				}
			} else if(b2make.widget_e_resize){
				if(e.pageX - left > 20){
					width = Math.floor(e.pageX - left); 
					$(obj_area).width(width);
					$('#b2make-conteiner-area-largura').val(width);
					$(obj).myAttr('data-area-largura',width);
				}
			} else if(b2make.widget_n_resize){
				var start_height = b2make.widget_start_height;
				var start_top = b2make.widget_start_top;
				var top_max = start_top + start_height + parseInt($('#b2make-menu').height()) + parseInt($('#b2make-ruler-top').height());
				var top_proc = e.pageY;
				
				height = top_max - e.pageY;
				
				if(height < 20){
					height = 20;
					top_proc = top_max;
				}
				
				$(obj).css('top',top_proc - parseInt($('#b2make-site').css('top')));
				$(obj).height(height);
				$(b2make.won_height).val(height);
				conteiner_principal_site_update();
			} else if(b2make.widget_s_resize){
				if(e.pageY - top > 20){
					if(position == 'fixed'){
						top = e.pageY - top - $(window).scrollTop(); 
					} else {
						top = e.pageY - top; 
					}
					
					$(obj).height(top);
					$(b2make.won_height).val(top);
					conteiner_principal_site_update();
				}
			} else {
				top = e.pageY - widget_start_y;
				
				$(obj).css('top',top);
				
				if($(obj).myAttr('data-area-fixed') == 's'){
					if(b2make_menu.open){
						$(obj).css('left','0px');
						$(obj).css('width',($(window).width()-b2make_menu.width_conteiner+16)+'px');
					} else {
						$(obj).css('left','0px');
						$(obj).css('width',($(window).width()-4)+'px');
					}
				}
				
				var top_before;
				var top_after;
				
				if(b2make.widget_before) top_before = $('#'+b2make.widget_before).offset().top;
				if(b2make.widget_after) top_after = $('#'+b2make.widget_after).offset().top;
				
				if(top_before){
					if(top_before + b2make.widget_move_pos_ajuste >= top){
						$('#'+b2make.widget_before).before($(obj));
						$('#'+b2make.widget_before).before(b2make.widget_conteiner_mask);
						$("#"+b2make.menu_widgets+' li[data-id="'+b2make.widget_before+'"]').before($("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"]'));
						conteiner_before_after();
					}
				}
				
				if(top_after){
					if(top_after - b2make.widget_move_pos_ajuste <= top){
						$('#'+b2make.widget_after).after($(obj));
						$('#'+b2make.widget_after).after(b2make.widget_conteiner_mask);
						$("#"+b2make.menu_widgets+' li[data-id="'+b2make.widget_after+'"]').after($("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"]'));
						conteiner_before_after();
					}
				}
				
				conteiner_position();
			}
		}
		
		if(b2make.widget_sub_menu_move){
			obj = $(b2make.widget_sub_options).get(0);
			
			top = $(obj).offset().top;
			left = $(obj).offset().left;
			height = $(obj).height();
			margim = b2make.widget_margim_correcao;
			
			if(b2make.widget_sub_menu_s_resize && b2make.widget_sub_options_open){
				if(e.pageY - top > 150){
					top = e.pageY - top; 
					$(obj).height(top);
					$(b2make.widget_sub_options).height(e.pageY - 110);
					$(b2make.widget_sub_options_holder).height($(b2make.widget_sub_options).height() - 2*$(b2make.widget_sub_options_up).outerHeight(true) - 3);
					$(b2make.widget_options_childreen).height(e.pageY + $(b2make.widget_sub_options_up).outerHeight(true) + 5);
					$(b2make.menu).height(e.pageY + $(b2make.widget_sub_options_up).outerHeight(true) + 5);
					$(b2make.menu_mask).height(e.pageY + $(b2make.widget_sub_options_up).outerHeight(true) + 5);
					$('#b2make-widget-options-main').height(e.pageY + $(b2make.widget_sub_options_up).outerHeight(true) + 5);
					$('#b2make-other-options-main').height(e.pageY + $(b2make.widget_sub_options_up).outerHeight(true) + 5);
					$(b2make.conteiner_obj).css('top',e.pageY + $(b2make.widget_sub_options_up).outerHeight(true));
					
					b2make.widget_sub_options_holder_height_user = top - $(b2make.widget_sub_options_up).outerHeight(true);
				}
			}
		}
	});
	
	function local_storage_set(name,val){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		localStorage.setItem(name,val);
	}
	
	function local_storage_get(name){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		return localStorage.getItem(name);
	}
	
	$.local_storage_del = function(name){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		localStorage.removeItem(name);
	}
	
	function local_storage_restart_array(name){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		var _name = localStorage.getItem(name);
		var obj = [];
		
		localStorage.setItem(name,JSON.stringify(obj));
	}
	
	function local_storage_remove_item_array(name,val){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		var _name = localStorage.getItem(name);
		var obj = [];
		if(_name){
			obj= JSON.parse(_name);  
		}
		
		var index = obj.indexOf(val);
		
		if(index > -1){
			obj.splice(index, 1);
		}

		localStorage.setItem(name,JSON.stringify(obj));
	}
	
	function local_storage_set_array(name,val){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		var _name = localStorage.getItem(name);
		var obj = [];
		if(_name){
			obj= JSON.parse(_name);  
		}
		obj.push(val);
		localStorage.setItem(name,JSON.stringify(obj));
	}
	
	function local_storage_change_array(name,val){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		localStorage.setItem(name,JSON.stringify(val));
	}
	
	$.local_storage_get_array = function(name){
		name = 'b2make-'+variaveis_js.pub_id+'-'+name;
		
		return JSON.parse(localStorage.getItem(name));
	}
	
	function menu_paginas_resize(){
		var status = $('#b2make-menu-paginas').myAttr('data-status');
		
		if(!status)status = 'open';
		
		if(status == 'open'){
			var height = $(window).height() - parseInt($('#b2make-menu-paginas').offset().top) - b2make.menu_paginas.ajuste_botton;
			
			$('#b2make-menu-paginas').height(height);
			$('#b2make-menu-paginas-conteiner').css('max-height',height - 120);
		}
	}
	
	function menu_paginas_add(){
		var id_func = 'pagina-add';
		var form_id = 'b2make-formulario-paginas';
		var nome = $('#b2make-fp-nome').val();
		var tipo = $('.b2make-fp-tipo:checked').val();
		var nivel = $('.b2make-fp-nivel:checked').val();
		
		if($.formulario_testar(form_id)){
			var ajaxData = { 
				ajax : 'sim',
				pai_id : b2make.menu_paginas.pai_id,
				atual_id : b2make.menu_paginas.atual_id,
				nivel : nivel,
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
								switch(tipo){
									case 'em_branco':
									case 'duplicar':
									case 'duplicar-raiz':
										window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path,'_self');
									break;
									case 'modelo':
										window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'management/templates/','_self');
									break;
								}
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
					$.dialogbox_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+id_func+' - '+txt);
					$.carregamento_close();
				}
			});
		}
	}
	
	function menu_paginas_mudar_site(id_site){
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : 'pagina-mudar',
				id : id_site
			},
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
							window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path,'_self');
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
				$.dialogbox_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
				$.carregamento_close();
			}
		});
	}
	
	function menu_paginas_edit(){
		var id_func = 'pagina-edit';
		var form_id = 'b2make-formulario-paginas';
		var nome = $('#b2make-fp-nome').val();
		
		if($.formulario_testar(form_id)){
			var ajaxData = { 
				ajax : 'sim',
				id : b2make.menu_paginas.edit_id,
				opcao : id_func
			};
			
			var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;}); var serialize = $('#'+form_id).serialize(); if(serialize) ajaxDataString = ajaxDataString + '&' + serialize;
			
			$.dialogbox_close();
			
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
								menu_paginas_start({restart:true});
							break;
							case 'NaoPodeEditar':
								$.dialogbox_open({
									msg: b2make.msgs.naoEPossivelEditarPaginaSistema
								});
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
	
	function menu_paginas_delete(){
		var id_func = 'pagina-delete';
		
		$.dialogbox_close();
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				id : b2make.menu_paginas.del_id,
				opcao : id_func
			},
			beforeSend: function(){
				$.carregamento_open();
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					if(dados.debug)$.debug_console({valor:dados.debug,cache:true});
					
					switch(dados.status){
						case 'Ok':
							menu_paginas_start({restart:true,mudar_site:true});
						break;
						case 'NaoPodeDeletar':
							$.dialogbox_open({
								msg: b2make.msgs.naoEPossivelDeletePaginaSistema
							});
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
	
	function menu_paginas_montar_filhos(paginas,id_pai,cont,nivel){
		for(var key in paginas[id_pai]){
			var dados = paginas[id_pai][key];
			
			var menu = $('<ul class="b2make-menu-paginas-nivel"></ul>');
		
			var menu_entrada = $('<li class="b2make-menu-paginas-entrada'+(b2make.menu_paginas.atual_id == dados.id ? ' b2make-menu-paginas-atual' : '')+'" data-id="'+dados.id+'"></li>');
			
			var menu_entrada_mask = $('<div class="b2make-menu-paginas-entrada-mask"></div>');
			
			if(dados.nome.match(/01 \- Modelos de P/) == '01 - Modelos de P'){
				dados.nome = 'Layouts Mestres';
			}
			
			if(b2make.multi_screen.device == 'desktop'){
				var url = dados.url;
			} else {
				var url = dados.url_mobile;
			}
			
			var menu_nome = $('<div class="b2make-menu-paginas-nome">'+dados.nome+'</div>');
			var menu_url = $('<a class="b2make-menu-paginas-url b2make-tooltip" href="'+url+'" target="b2make_page" title="'+b2make.msgs.menuPaginasUrlTitle+'"></a>');
			var menu_duplicate = $('<div class="b2make-menu-paginas-duplicate b2make-tooltip" title="'+b2make.msgs.menuPaginasDuplacateTitle+'"></div>');
			var menu_edit = $('<div class="b2make-menu-paginas-edit b2make-tooltip" title="'+b2make.msgs.menuPaginasEditTitle+'"></div>');
			var menu_del = $('<div class="b2make-menu-paginas-del b2make-tooltip" title="'+b2make.msgs.menuPaginasDeleteTitle+'"></div>');
			
			menu_entrada_mask.append(menu_nome);
			menu_entrada_mask.append(menu_url);
			menu_entrada_mask.append(menu_duplicate);
			menu_entrada_mask.append(menu_edit);
			menu_entrada_mask.append(menu_del);
			
			if(b2make.menu_paginas.atual_id == dados.id){
				menu_url.css('display','block');
				menu_duplicate.css('display','block');
				menu_edit.css('display','block');
				menu_del.css('display','block');
			}
			
			menu_entrada_mask.css('marginLeft',(nivel+1)*b2make.menu_paginas.margin);
			
			menu_entrada.append(menu_entrada_mask);
			menu.append(menu_entrada);
			
			cont.after(menu);
			
			menu_paginas_montar_filhos(paginas,dados.id,menu,nivel+1);
		}
	}
	
	function menu_paginas_montar(){
		var paginas_arvore = $.local_storage_get_array('paginas-arvore');paginas_arvore = paginas_arvore[0];
		var menu_conteiner = $('#b2make-menu-paginas-conteiner');
		
		menu_conteiner.css('background-image','none');
		menu_conteiner.html('');
		
		var menu = $('<ul class="b2make-menu-paginas-nivel"></ul>');
		
		var menu_entrada = $('<li class="b2make-menu-paginas-entrada'+(b2make.menu_paginas.atual_id == paginas_arvore['0'].id ? ' b2make-menu-paginas-atual' : '')+'" data-id="'+paginas_arvore['0'].id+'"></li>');
		
		var menu_entrada_mask = $('<div class="b2make-menu-paginas-entrada-mask"></div>');
		
		if(b2make.multi_screen.device == 'desktop'){
			var url = paginas_arvore['0'].url;
		} else {
			var url = paginas_arvore['0'].url_mobile;
		}
		
		var menu_nome = $('<div class="b2make-menu-paginas-nome">'+paginas_arvore['0'].nome+'</div>');
		var menu_url = $('<a class="b2make-menu-paginas-url b2make-tooltip" href="'+url+'" target="b2make_page" title="'+b2make.msgs.menuPaginasUrlTitle+'"></a>');
		var menu_duplicate = $('<div class="b2make-menu-paginas-duplicate b2make-tooltip" title="'+b2make.msgs.menuPaginasDuplacateTitle+'"></div>');
		
		menu_entrada_mask.append(menu_nome);
		menu_entrada_mask.append(menu_url);
		menu_entrada_mask.append(menu_duplicate);
		
		if(b2make.menu_paginas.atual_id == paginas_arvore['0'].id){
			menu_url.css('display','block');
			menu_duplicate.css('display','block');
		}
		
		menu_entrada_mask.css('marginLeft',b2make.menu_paginas.margin);
		
		menu_entrada.append(menu_entrada_mask);
		menu.append(menu_entrada);
		
		menu_conteiner.append(menu);
		
		menu_paginas_montar_filhos(paginas_arvore,paginas_arvore['0'].id,menu,1);
		
		$('.b2make-tooltip').tooltip({
			show: {
				effect: "fade",
				delay: 400
			}
		});
		
		menu_paginas_resize();
	}
	
	function menu_paginas_start(p){
		if(!p) p = {};
		
		var reload_menu = true;
		
		if(!local_storage_get('cache-version')){
			local_storage_set('cache-version',variaveis_js.cache_version);
			reload_menu = true;
		} else if(local_storage_get('cache-version') != variaveis_js.cache_version){
			local_storage_set('cache-version',variaveis_js.cache_version);
			reload_menu = true;
		} else if(p.restart){
			reload_menu = true;
		}
		
		if(reload_menu){
			var opcao = 'menu-paginas-start';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao
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
								local_storage_restart_array('paginas-arvore');
								local_storage_set_array('paginas-arvore',dados.paginas);
								menu_paginas_montar();
								
								if(p.callback)$('#b2make-listener').trigger(p.callback);
								if(p.mudar_site)menu_paginas_mudar_site(b2make.menu_paginas.pai_id);
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
		} else {
			menu_paginas_montar();
		}
	}
	
	function menu_paginas_menu_conteudos(p){
		if(!p) p = {};
		
		var type = p.type;
		
		var resultado = b2make.menu_paginas.conteudos_prontos[type];
		
		$('#b2make-wsopm-conteudos').html('');
		
		if(resultado)if(resultado.length > 0) resultado.forEach(function(e,i){
			var input = $('<input type="checkbox" id="b2make-wsopm-conteudos-input-'+e.id+'" class="b2make-wsopm-conteudos-input" value="'+e.id+'">');
			var label = $('<label type="checkbox" for="b2make-wsopm-conteudos-input-'+e.id+'" class="b2make-wsopm-conteudos-label">'+e.nome+'</label>');
			
			var conteiner = $('<div class="b2make-wsopm-conteudos-cont"></div>');
			
			input.appendTo(conteiner);
			label.appendTo(conteiner);
			
			conteiner.appendTo('#b2make-wsopm-conteudos');
		});
	}
	
	function menu_paginas_ler_conteudos(p){
		if(!p) p = {};
		
		var type = p.type;
		
		if(!b2make.menu_paginas.conteudos_prontos[type]){
			var opcao = 'menu-paginas-ler-conteudos';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					type : type
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
								b2make.menu_paginas.conteudos_prontos[type] = dados.resultado;
								$('#b2make-wsopm-conteudos').css('background-image','none');
								menu_paginas_menu_conteudos({type:type});
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
	}
	
	function menu_paginas(){
		if(!b2make.msgs.naoEPossivelDeletePaginaSistema)b2make.msgs.naoEPossivelDeletePaginaSistema = 'N&atilde;o &eacute; poss&iacute;vel Ecluir esta p&aacute;gina pois a mesma &eacute; uma p&aacute;gina do sistema.';
		if(!b2make.msgs.naoEPossivelEditarPaginaSistema)b2make.msgs.naoEPossivelEditarPaginaSistema = 'N&atilde;o &eacute; poss&iacute;vel Editar esta p&aacute;gina pois a mesma &eacute; uma p&aacute;gina do sistema.';
		if(!b2make.msgs.naoEPossivelEditarPaginaInicial)b2make.msgs.naoEPossivelDeletePaginaInicial = 'N&atilde;o &eacute; poss&iacute;vel Ecluir a P&aacute;gina Inicial.';
		if(!b2make.msgs.naoEPossivelEditarPaginaInicial)b2make.msgs.naoEPossivelEditarPaginaInicial = 'N&atilde;o &eacute; poss&iacute;vel Editar dados da P&aacute;gina Inicial.';
		if(!b2make.msgs.naoEPossivelListarPaginaInicial)b2make.msgs.naoEPossivelListarPaginaInicial = 'As p&aacute;ginas filhas da P&aacute;gina Inicial j&aacute; est&atilde;o listadas no meu a direita de forma autom&aacute;tica.';
		if(!b2make.msgs.menuPaginasDelete)b2make.msgs.menuPaginasDelete = 'Tem certeza que voc&ecirc; quer excluir a p&aacute;gina <b style="color:red;">"#pagina-nome#"</b> e as p&aacute;ginas internas se houverem?';
		if(!b2make.msgs.menuPaginasEditTitle)b2make.msgs.menuPaginasEditTitle = 'Clique para editar esta p&aacute;gina';
		if(!b2make.msgs.menuPaginasAddTitle)b2make.msgs.menuPaginasAddTitle = 'Adicionar P&aacute;gina';
		if(!b2make.msgs.menuPaginasDeleteTitle)b2make.msgs.menuPaginasDeleteTitle = 'Clique para deletar esta p&aacute;gina';
		if(!b2make.msgs.menuPaginasDuplacateTitle)b2make.msgs.menuPaginasDuplacateTitle = 'Clique para duplicar esta p&aacute;gina';
		if(!b2make.msgs.menuPaginasUrlTitle)b2make.msgs.menuPaginasUrlTitle = 'Clique para acessar esta p&aacute;gina';
		
		b2make.menu_paginas = {};
		
		b2make.menu_paginas.id_raiz = variaveis_js.menu_paginas.id_raiz;
		b2make.menu_paginas.pai_id = variaveis_js.menu_paginas.id_site_pai;
		b2make.menu_paginas.nome_pai = variaveis_js.menu_paginas.nome_pai;
		b2make.menu_paginas.atual_id = variaveis_js.menu_paginas.id_site_atual;
		b2make.menu_paginas.identificador_atual = variaveis_js.menu_paginas.identificador_atual;
		b2make.menu_paginas.total_paginas = variaveis_js.menu_paginas.total_paginas;
		b2make.menu_paginas.animation_speed = 200;
		b2make.menu_paginas.animation_open_height = 28;
		b2make.menu_paginas.ajuste_botton = 80;
		b2make.menu_paginas.margin = 10;
		
		b2make.menu_paginas.conteudos_prontos = new Array();
		b2make.menu_paginas.paginas_mestres = new Array();
		
		b2make.menu_paginas.paginas_mestres.push({
			identificador : 'pagina-de-servicos',
			type : 'servicos'
		});
		b2make.menu_paginas.paginas_mestres.push({
			identificador : 'pagina-de-conteudos',
			type : 'conteudos'
		});
		
		if(variaveis_js.pagina_mestre){
			b2make.menu_paginas.paginas_mestres.push({
				identificador : variaveis_js.menu_paginas.identificador_atual,
				type : 'conteudos-mais'
			});
		}
		
		$('#b2make-menu-paginas-pai').myAttr('data-id',b2make.menu_paginas.pai_id);

		menu_paginas_resize();
		
		b2make.sites_pagina = 1;
		
		var found_page = false;
		
		b2make.menu_paginas.paginas_mestres.forEach(function(e,i){
			if(!found_page){
				if(b2make.menu_paginas.identificador_atual == e.identificador){
					$('#b2make-menu-paginas-menu-pagina-mestre').show();
					$('.b2make-menu-paginas-publicar-pagina-mestre').myAttr('data-pagina-mestre',e.type);
					found_page = true;
				} else {
					$('#b2make-menu-paginas-menu-pagina-mestre').hide();
				}
			}
		});
		
		$('#b2make-listener').on('b2make-menu-nav-change',function(e,id){
			if(id == 'pages'){
				menu_paginas_start(null);
			}
		});
		
		$('#b2make-listener').on('b2make-menu-paginas-start',function(e,callback){
			menu_paginas_start({callback:callback});
		});
		
		if(localStorage.getItem('b2make.menu_opcao_atual') == 'pages'){
			b2make.menu_paginas.started = true;
			menu_paginas_start(null);
		}
		
		$('.b2make-menu-opcao[data-id="pages"]').on('mouseup tap',function(e){
			if(!b2make.menu_paginas.started){
				b2make.menu_paginas.started = true;
				menu_paginas_start(null);
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-menu-paginas-entrada',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.b2make-menu-paginas-entrada').removeClass('b2make-menu-paginas-atual');
			
			$(this).addClass('b2make-menu-paginas-atual');
			
			local_storage_set('menu-paginas-start-open',true);
			
			menu_paginas_mudar_site($(this).myAttr('data-id'));
		});
		
		$('#b2make-menu-paginas-adicionar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			local_storage_set('menu-paginas-start-open',true);
			
			$('#b2make-fp-cont').show();
			$('#b2make-fp-cont-2').show();
			$('#b2make-fp-nome-lbl').show();
			$('#b2make-fp-nome').show();
			
			$.dialogbox_open({
				width:350,
				height:300,
				message:true,
				calback_yes: 'b2make-formulario-paginas-add-calback',
				title: b2make.msgs.menuPaginasAddTitle,
				coneiner: 'b2make-formulario-paginas'
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-formulario-paginas-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			menu_paginas_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-menu-paginas-duplicate',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			
			local_storage_set('menu-paginas-start-open',true);
			
			$('#b2make-fp-cont').show();
			$('#b2make-fp-tipo-duplicar').myAttr('checked',true);
			$('#b2make-fp-nome-lbl').show();
			$('#b2make-fp-nome').show();
			
			$('#b2make-fp-nome').val($(this).parent().find('.b2make-menu-paginas-nome').html());
			
			$.dialogbox_open({
				width:350,
				height:300,
				message:true,
				calback_yes: 'b2make-formulario-paginas-duplicate-calback',
				title: b2make.msgs.menuPaginasAddTitle,
				coneiner: 'b2make-formulario-paginas'
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-menu-paginas-url',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
		});
		
		$(document.body).on('mouseup tap','.b2make-formulario-paginas-duplicate-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			menu_paginas_add();
		});
		
		$(document.body).on('change','.b2make-fp-tipo',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var value = $(this).val();
			
			if(value == 'duplicar-raiz'){
				$('#b2make-fp-nome-lbl').hide();
				$('#b2make-fp-nome').hide();
				$('#b2make-fp-cont-2').hide();
			} else {
				$('#b2make-fp-nome-lbl').show();
				$('#b2make-fp-nome').show();
				$('#b2make-fp-cont-2').show();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-menu-paginas-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			
			var id = $(this).parent().parent().myAttr('data-id');
			
			b2make.menu_paginas.edit_id = id;
			
			$('#b2make-fp-cont').hide();
			$('#b2make-fp-cont-2').hide();
			$('#b2make-fp-nome').val($(this).parent().find('.b2make-menu-paginas-nome').html());

			$.dialogbox_open({
				width:350,
				height:200,
				message:true,
				calback_yes: 'b2make-formulario-paginas-edit-calback',
				title: b2make.msgs.menuPaginasEditTitle,
				coneiner: 'b2make-formulario-paginas'
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-formulario-paginas-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			menu_paginas_edit();
		});
		
		$(document.body).on('mouseup tap','.b2make-menu-paginas-del',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			
			var id = $(this).parent().parent().myAttr('data-id');
			
			b2make.menu_paginas.del_id = id;
			
			local_storage_set('menu-paginas-start-open',true);
			
			$.dialogbox_open({
				confirm:true,
				msg:b2make.msgs.menuPaginasDelete.replace(/#pagina-nome#/gi,$(this).parent().find('.b2make-menu-paginas-nome').html()),
				calback_yes: 'b2make-formulario-paginas-delete-calback',
				title: b2make.msgs.menuPaginasDeleteTitle,
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-formulario-paginas-delete-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			menu_paginas_delete();
		});
	
		$(document.body).on('mouseenter','.b2make-menu-paginas-entrada',function(e){
			var id = $(this).myAttr('data-id');
			
			if(id != b2make.menu_paginas.atual_id){
				$(this).find('.b2make-menu-paginas-url').css('display','block');
				$(this).find('.b2make-menu-paginas-duplicate').css('display','block');
				$(this).find('.b2make-menu-paginas-edit').css('display','block');
				$(this).find('.b2make-menu-paginas-del').css('display','block');
			}
		});
		
		$(document.body).on('mouseleave','.b2make-menu-paginas-entrada',function(e){
			var id = $(this).myAttr('data-id');
			
			if(id != b2make.menu_paginas.atual_id){
				$(this).find('.b2make-menu-paginas-url').css('display','none');
				$(this).find('.b2make-menu-paginas-duplicate').css('display','none');
				$(this).find('.b2make-menu-paginas-edit').css('display','none');
				$(this).find('.b2make-menu-paginas-del').css('display','none');
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-suboptions-editar-sem-styles',function(e){
			if($(this).myAttr('data-type') == 'pagina-mestre'){
				var type = $(this).myAttr('data-pagina-mestre');
				
				b2make.menu_paginas.pagina_mestre_type = type;
				menu_paginas_ler_conteudos({type:type});
			}
		});
		
		$('#b2make-wsopm-selecionar-todos').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.b2make-wsopm-conteudos-cont').find('input').prop('checked','checked');
		});
		
		$('#b2make-wsopm-deselecionar-todos').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.b2make-wsopm-conteudos-cont').find('input').prop('checked',false);
		});
		
		$(document.body).on('mouseup tap','.b2make-wsopm-publicar',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var opcao = 'menu-paginas-publicar-conteudos';
			var conteudos_ids = '';
			
			$('.b2make-wsopm-conteudos-cont').each(function(){
				var input = $(this).find('input');
				
				if(input.prop('checked')){
					conteudos_ids = conteudos_ids + (conteudos_ids ? ',' : '') + input.val();
				}
			});
			
			$.save();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					type : b2make.menu_paginas.pagina_mestre_type,
					conteudos_ids : conteudos_ids,
				},
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
								lightbox_close();
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		});
	}
	
	menu_paginas();
	
	function segmento_add(){
		b2make.segmento_open = false;
		
		if(b2make.template_open)template_close();
		
		$('#b2make-sto-segmento-name').val(b2make.msgs.segmentoDefineName);
		$('#b2make-sto-segmento-descricao').val('');
		$('#b2make-sto-segmento-imagem').css('backgroundImage','url(images/b2make-segmentos-templates-default.png)');
		$('#b2make-segmento-atual-cont').show();
	}
	
	function segmento_add_server(){
		var opcao = 'segmento-add';
		var nome = $('#b2make-sto-segmento-name').val();
		var descricao = $('#b2make-sto-segmento-descricao').val();
		var flag = true;
		var msg;
		var image_id = b2make.imagem_templates_id;
		
		if(nome.length == 0){
			msg = b2make.msgs.segmentoNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(!limites_str(nome,3,100)){
			msg = b2make.msgs.segmentoNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(checkStr(nome)){
			msg = b2make.msgs.segmentoNomeCaracterIvalido;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		b2make.imagem_templates_id = null;
		
		if(flag){
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					nome : nome,
					image_id : image_id,
					descricao : descricao
				},
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
								var op;
								
								op = $('<option value="'+dados.id+'">'+nome+'</option>');
								op.appendTo($('#b2make-sto-segmentos'));
								
								segmento_open(dados.id);
								
								if(image_id){
									var type = 'segmentos';
									
									if(!localStorage.getItem('b2make.'+type+'_version')){
										localStorage.setItem('b2make.'+type+'_version',1);
									} else {
										var version = parseInt(localStorage.getItem('b2make.'+type+'_version'));
										
										version++;
										localStorage.setItem('b2make.'+type+'_version',version);
									}
								}
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		}
	}
	
	function segmento_edit_server(){
		var id = b2make.segmento_open;
		var opcao = 'segmento-edit';
		var nome = $('#b2make-sto-segmento-name').val();
		var descricao = $('#b2make-sto-segmento-descricao').val();
		var flag = true;
		var msg;
		var image_id = b2make.imagem_templates_id;
		
		if(nome.length == 0){
			msg = b2make.msgs.segmentoNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(!limites_str(nome,3,100)){
			msg = b2make.msgs.segmentoNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(checkStr(nome)){
			msg = b2make.msgs.segmentoNomeCaracterIvalido;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		b2make.imagem_templates_id = null;
		
		if(flag){
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					id : id,
					nome : nome,
					image_id : image_id,
					descricao : descricao
				},
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
								$('#b2make-sto-segmentos').find('option[value="'+id+'"]').text(nome);
								
								if(image_id){
									var type = 'segmentos';
									
									if(!localStorage.getItem('b2make.'+type+'_version')){
										localStorage.setItem('b2make.'+type+'_version',1);
									} else {
										var version = parseInt(localStorage.getItem('b2make.'+type+'_version'));
										
										version++;
										localStorage.setItem('b2make.'+type+'_version',version);
									}
								}
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		}
	}
	
	function segmento_del(){
		if(b2make.segmento_open){
			var opcao = 'segmento-del';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					id : b2make.segmento_open
				},
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
								$('#b2make-sto-segmentos').find('option[value="'+b2make.segmento_open+'"]').remove();
								segmento_add();
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		} else {
			segmento_add();
		}
	}
	
	function segmento_open(id){
		b2make.segmento_open = id;
		
		template_close();
		
		var opcao = 'segmento-dados';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				id : id
			},
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
							$('#b2make-sto-segmento-name').val(dados.segmento.nome);
							$('#b2make-sto-segmento-descricao').val(dados.segmento.descricao);
							
							if(dados.segmento.imagem)$('#b2make-sto-segmento-imagem').css('backgroundImage','url('+dados.segmento.imagem+'?v='+dados.segmento.imagem_versao+')');
							else $('#b2make-sto-segmento-imagem').css('backgroundImage','url(images/b2make-segmentos-templates-default.png)');
							
							$('#b2make-segmento-atual-cont').show();
							
							templates_list();
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
				}
				$.carregamento_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
				$.carregamento_close();
			}
		});
	}
	
	function segmento_close(){
		$('#b2make-segmento-atual-cont').hide();
		b2make.segmento_open = false;
	}
	
	function templates_list(){
		if(b2make.segmento_open){
			var opcao = 'templates';
			var id = b2make.segmento_open;
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					id : id
				},
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
								var op;
								
								for(var i=0;i<dados.templates.length;i++){
									op = $('<option value="'+dados.templates[i].id_site_templates+'">'+dados.templates[i].nome+'</option>');
									op.appendTo($('#b2make-sto-templates'));
								}
								
								b2make.templates_defined = true;
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							case 'NaoExisteId':
							
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		}
	}
	
	function template_add(){
		b2make.template_open = false;
		
		$('#b2make-sto-template-name').val(b2make.msgs.templateDefineName);
		$('#b2make-sto-template-descricao').val('');
		$('#b2make-sto-template-imagem').css('backgroundImage','url(images/b2make-segmentos-templates-default.png)');
		$('#b2make-template-atual-cont').show();
	}
	
	function template_add_server(){
		var opcao = 'template-add';
		var nome = $('#b2make-sto-template-name').val();
		var descricao = $('#b2make-sto-template-descricao').val();
		var flag = true;
		var msg;
		var image_id = b2make.imagem_templates_id;
		
		if(nome.length == 0){
			msg = b2make.msgs.templateNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(!limites_str(nome,3,100)){
			msg = b2make.msgs.templateNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(checkStr(nome)){
			msg = b2make.msgs.templateNomeCaracterIvalido;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		b2make.imagem_templates_id = null;
		
		if(flag){
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					nome : nome,
					image_id : image_id,
					descricao : descricao,
					id_site_segmentos : b2make.segmento_open
				},
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
								var op;
								
								op = $('<option value="'+dados.id+'">'+nome+'</option>');
								op.appendTo($('#b2make-sto-templates'));
								
								template_open(dados.id);
								
								if(image_id){
									var type = 'templates';
									
									if(!localStorage.getItem('b2make.'+type+'_version')){
										localStorage.setItem('b2make.'+type+'_version',1);
									} else {
										var version = parseInt(localStorage.getItem('b2make.'+type+'_version'));
										
										version++;
										localStorage.setItem('b2make.'+type+'_version',version);
									}
								}
								
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		}
	}
	
	function template_edit_server(){
		var id = b2make.template_open;
		var opcao = 'template-edit';
		var nome = $('#b2make-sto-template-name').val();
		var descricao = $('#b2make-sto-template-descricao').val();
		var flag = true;
		var msg;
		var image_id = b2make.imagem_templates_id;
		
		if(nome.length == 0){
			msg = b2make.msgs.templateNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(!limites_str(nome,3,100)){
			msg = b2make.msgs.templateNomeLimite;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		if(checkStr(nome)){
			msg = b2make.msgs.templateNomeCaracterIvalido;
			
			$.dialogbox_open({
				msg: msg
			});
			
			flag = false;
			
			return;
		}
		
		b2make.imagem_templates_id = null;
		
		if(flag){
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					id : id,
					nome : nome,
					image_id : image_id,
					descricao : descricao
				},
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
								$('#b2make-sto-templates').find('option[value="'+id+'"]').text(nome);
								
								if(image_id){
									var type = 'templates';
									
									if(!localStorage.getItem('b2make.'+type+'_version')){
										localStorage.setItem('b2make.'+type+'_version',1);
									} else {
										var version = parseInt(localStorage.getItem('b2make.'+type+'_version'));
										
										version++;
										localStorage.setItem('b2make.'+type+'_version',version);
									}
								}
								
								if(b2make.template_save){
									b2make.template_save = false;
									holder_template_close();
									save_template();
								}
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		}
	}
	
	function template_del(){
		if(b2make.template_open){
			var opcao = 'template-del';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					id : b2make.template_open
				},
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
								$('#b2make-sto-templates').find('option[value="'+b2make.template_open+'"]').remove();
								template_add();
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		} else {
			template_add();
		}
	}
	
	function template_open(id){
		if(b2make.segmento_open){
			b2make.template_open = id;
			
			var opcao = 'template-dados';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					id : id
				},
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
								$('#b2make-sto-template-name').val(dados.template.nome);
								$('#b2make-sto-template-descricao').val(dados.template.descricao);
								
								if(dados.template.imagem)$('#b2make-sto-template-imagem').css('backgroundImage','url('+dados.template.imagem+'?v='+dados.template.imagem_versao+')');
								else $('#b2make-sto-template-imagem').css('backgroundImage','url(images/b2make-segmentos-templates-default.png)');
								
								$('#b2make-template-atual-cont').show();
								
								if(b2make.template_save){
									b2make.template_save = false;
									holder_template_close();
									save_template();
								}
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		} else {
			var msg = b2make.msgs.segmentoNotDefined;
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function template_close(){
		var op = $('#b2make-sto-templates').find('option:first-child');
		
		$('#b2make-sto-templates').html('');
		op.appendTo($('#b2make-sto-templates'));
		
		$('#b2make-template-atual-cont').hide();
		b2make.template_open = false;
	}
	
	function holders_sub_options(){
		//$(b2make.widget_sub_options).width($(window).width() - $("#b2make-menu-holder").width() - $("#b2make-menu-holder").offset().left - 2*b2make.holder_margin);
	}
	
	function holders_close(){
		if(b2make.holder_events_visible){
			for(var i=0;i<b2make.holder_events.length;i++){
				if(b2make.holder_events[i].visible){
					$("#"+b2make.holder_events[i].target).hide();
					holders_update({
						visible : false,
						id : b2make.holder_events[i].id
					});
				}
			}
		}
	}
	
	function holders_update(p){
		for(var i=0;i<b2make.holder_events.length;i++){
			if(b2make.holder_events[i].id == p.id){
				b2make.holder_events[i].visible = p.visible;
			}
		}
		
		var visible = false;
		
		for(i=0;i<b2make.holder_events.length;i++){
			if(b2make.holder_events[i].visible){
				visible = true;
			}
		}
		
		b2make.holder_events_visible = visible;
	}
	
	function holder_vars(id,type){
		for(var i=0;i<b2make.holder_events.length;i++){
			switch(type){
				case 'holder':
					if(id == b2make.holder_events[i].holder){
						return b2make.holder_events[i];
					}
				break;
				case 'target':
					if(id == b2make.holder_events[i].target){
						return b2make.holder_events[i];
					}
				break;
				
			}
		}
		
		return false;
	}
	
	function holder_widget_start_operacao(widget){
		if(widget){
			var li = $('<li data-id="'+widget.id+'">'+($("#"+widget.id).myAttr('data-name')?$("#"+widget.id).myAttr('data-name'):widget.id)+'</li>');
			
			if(widget.id_pai){
				var ul;
				
				$("#"+b2make.menu_widgets+" li").each(function(){
					var id_local = $(this).myAttr('data-id');
					
					if(id_local == widget.id_pai){
						if($(this).find("ul").length == 0){
							ul = $('<ul></ul>');
							ul.appendTo($(this));
						} else {
							ul = $(this).find("ul");
						}
						
						li.appendTo(ul);
						
						return false;
					}
				});
			} else {
				li.appendTo("#"+b2make.menu_widgets);
			}
			
			li.on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var param = {};
				var type = $(this).myAttr('data-type');
				var id = $(this).myAttr('data-id');
				var vars = holder_vars(b2make.menu_widgets,'target');
				
				if($(this).parent().myAttr('id') != b2make.menu_widgets)param.nao_conteiner = true;
				param.id = id;
				
				widget_open(e,param);
				
				$("#"+vars.target).hide();
				
				holders_update({
					visible : false,
					id : vars.id
				});
			});
		}
	}
	
	function holder_widget_start(){
		var widgets = b2make.widgets;
		
		var conteiners = new Array();
		var conteiners_child = new Array();
		
		for(var i=0;i<widgets.length;i++){
			if(widgets[i].type == 'conteiner'){
				conteiners.push(widgets[i]);
			} else {
				conteiners_child.push(widgets[i]);
			}
		}
		
		for(i=0;i<conteiners.length;i++){
			holder_widget_start_operacao(conteiners[i]);
		}
		
		for(i=0;i<conteiners_child.length;i++){
			holder_widget_start_operacao(conteiners_child[i]);
		}
		
	}
	
	function holder_widget_update(id){
		var widget;
		
		if(id){
			$("#"+b2make.menu_widgets+" li").each(function(){
				var id_local = $(this).myAttr('data-id');
				var nao_existe_id_pai = true;
				
				if(id == id_local){
					$(this).remove();
					var widgets = new Array();
					
					for(var i=0;i<b2make.widgets.length;i++){
						if(id != b2make.widgets[i].id){
							widgets.push(b2make.widgets[i]);
						} else if(b2make.widgets[i].id_pai){
							nao_existe_id_pai = false;
						}
					}
					
					b2make.widgets = widgets;
					
					if(nao_existe_id_pai){
						widgets = new Array();
						
						for(i=0;i<b2make.widgets.length;i++){
							if(id != b2make.widgets[i].id_pai){
								widgets.push(b2make.widgets[i]);
							}
						}
					}
					
					b2make.widgets = widgets;
					
					return false;
				}
			});
		} else {
			widget = b2make.widgets[b2make.widgets.length - 1];
			
			if(widget){
				var li = $('<li data-id="'+widget.type+widget.id+'">'+($("#"+widget.type+widget.id).myAttr('data-name')?$("#"+widget.type+widget.id).myAttr('data-name'):widget.type+widget.id)+'</li>');
				
				if(widget.id_pai){
					var ul;
					
					$("#"+b2make.menu_widgets+" li").each(function(){
						var id_local = $(this).myAttr('data-id');
						
						if(id_local == widget.id_pai){
							if($(this).find("ul").length == 0){
								ul = $('<ul></ul>');
								ul.appendTo($(this));
							} else {
								ul = $(this).find("ul");
							}
							
							li.appendTo(ul);
							
							return false;
						}
					});
				} else {
					li.appendTo("#"+b2make.menu_widgets);
				}
				
				li.on('mouseup tap',function(e){
					if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
					
					var param = {};
					var type = $(this).myAttr('data-type');
					var id = $(this).myAttr('data-id');
					var vars = holder_vars(b2make.menu_widgets,'target');
					
					if($(this).parent().myAttr('id') != b2make.menu_widgets)param.nao_conteiner = true;
					param.id = id;
					
					widget_open(e,param);
					
					$("#"+vars.target).hide();
					
					holders_update({
						visible : false,
						id : vars.id
					});
				});
			}
		}
	}
	
	function holder_template_open(){
		close_all();
		if($(b2make.shadow).length == 0) $('<div id="b2make-shadow"></div>').appendTo('#b2make-site');
		$(b2make.shadow).fadeIn(b2make.fade_time);
		
		$('#b2make-page-options').hide();
		$('#b2make-menu-start').hide();
		
		if(!b2make.segmentos_defined){
			var opcao = 'segmentos';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao
				},
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
								var op;
								
								for(var i=0;i<dados.segmentos.length;i++){
									op = $('<option value="'+dados.segmentos[i].id_site_segmentos+'">'+dados.segmentos[i].nome+'</option>');
									op.appendTo($('#b2make-sto-segmentos'));
								}
								
								$('#b2make-save-templates-options').show();
			
								b2make.holder_template_open = true;
								b2make.segmentos_defined = true;
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					$.carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.carregamento_close();
				}
			});
		} else {
			$('#b2make-save-templates-options').show();

			b2make.holder_template_open = true;
		}
	}
	
	function holder_template_close(){
		$(b2make.shadow).fadeOut(b2make.fade_time);
		$('#b2make-save-templates-options').hide();
		$('#b2make-menu-start').show();
		b2make.holder_template_open = false;
	}
	
	function save_template_ajax_call(html){
		var opcao = 'template-save';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				html : html,
				id : b2make.template_open
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
							// Código
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
				}
				
				$.carregamento_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
				$.carregamento_close();
			}
		});
	}
	
	function save_template(){
		b2make.save_template = true;
		
		$.save();
	}
	
	function save_ajax_call(html){
		var opcao = 'save';
		
		var google_fontes = '';
		
		if(b2make.google_fonts_loaded){
			for(var i=0;i<b2make.google_fonts_loaded.length;i++){
				google_fontes = google_fontes + (google_fontes ? '|' : '') + b2make.google_fonts_loaded[i].replace(/ /gi,'+');
			}
		}
		
		var areas_globais_change = b2make.areas_globais_change;
		
		b2make.areas_globais_change = false;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				id : b2make.menu_paginas.atual_id,
				html : html,
				area_global : JSON.stringify(b2make.areas_globais_save),
				area_global_change : (areas_globais_change ? 's' : ''),
				google_fontes : google_fontes
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
							if(b2make.save_publish){
								b2make.save_publish = false;
								save_publish_call();
							} else {
								if(b2make.reload)window.location.reload(true);
								$.carregamento_close();
							}
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
							$.carregamento_close();
						
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
					$.carregamento_close();
				}
				
				script_callback({operacao:'save-ajax-call'});
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
				$.carregamento_close();
			}
		});
	}

	function save_publish_call(){
		var opcao = 'publish-page';
		
		var html_clone = $(b2make.site_conteiner).clone();
		html_clone.html(html_clone.html().replace(/script/g, "__script"));
		
		html_clone.find(b2make.widget).each(function(){
			if($(this).myAttr('data-type') != 'conteiner-area'){
				switch($(this).myAttr('data-type')){
					case 'iframe':
						$(this).find('.b2make-widget-out').html(decodeURIComponent($(this).myAttr('data-iframe-code')));
						$(this).myAttr('data-iframe-code',false);
					break;
					case 'conteiner':
						$(this).css('border','none');
						$(this).css('width','100%');
						
						if(b2make.multi_screen.device == 'phone'){
							$(this).css('min-width','0px');
						} else {
							if($(this).myAttr('data-area-largura')){
								$(this).css('min-width',$(this).myAttr('data-area-largura')+'px');
							}
						}
						
						if($(this).myAttr('data-banners-id')){
							$(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').each(function(){
								if($(this).myAttr('data-url')){
									var url = $(this).myAttr('data-url');
									$(this).myAttr('href',url);
									$(this).myAttr('target','_self');
									$(this).changeElementType('a');
								}
							});
						}
						
						if($(this).myAttr('data-area-global') == 's'){
							if($(this).myAttr('data-area-global-id')){
								var area_local_id = $(this).myAttr('id');
								var area_global_id = $(this).myAttr('data-area-global-id');
								var area_mask = $('<div class="b2make-widget b2make-loading-area" data-type="conteiner" data-area-global="s" data-area-global-id="'+area_global_id+'"></div>');
								
								$(this).after(area_mask);
								$(this).remove();
								
								area_mask.myAttr('id',area_local_id);
							}
						}
					break;
					case 'addthis':
						$(this).find('.b2make-widget-out').html(addthis_html($(this)));
					break;
					default:
						$('#b2make-listener').trigger('publish-page',[$(this).myAttr('data-type'),this]);
				}
				
				if($(this).hasClass('b2make-pagina-mestre')){
					$(this).find('.b2make-widget-out').find('.b2make-library-loading').show();
					$(this).find('.b2make-widget-out').find('.b2make-widget-pagina-mestre').html('');
				}
			}
		});
		
		html_clone.find('#b2make-pagina-options').myAttr('data-device',b2make.multi_screen.device);
		
		var google_fontes = '';
		
		if(b2make.google_fonts_loaded){
			for(var i=0;i<b2make.google_fonts_loaded.length;i++){
				google_fontes = google_fontes + (google_fontes ? '|' : '') + b2make.google_fonts_loaded[i].replace(/ /gi,'+');
			}
		}
		
		var fonts_installed = b2make.google_fonts_installed;
		var fonts_installed_before = variaveis_js.google_fonts_installed;
		var found = false;
		
		if(fonts_installed){
			for(var j=0;j<fonts_installed.length;j++){
				found = false;
				if(b2make.google_fonts_loaded){
					for(var i=0;i<b2make.google_fonts_loaded.length;i++){
						if(b2make.google_fonts_loaded[i] == fonts_installed[j].family){
							found = true;
						}
					}
				}
				
				if(!found){
					google_fontes = google_fontes + (google_fontes ? '|' : '') + fonts_installed[j].family.replace(/ /gi,'+');
				}
			}
		} else if(fonts_installed_before){
			var fonts_arr = fonts_installed_before.split('|');
			
			for(var j=0;j<fonts_arr.length;j++){
				found = false;
				if(b2make.google_fonts_loaded){
					for(var i=0;i<b2make.google_fonts_loaded.length;i++){
						if(b2make.google_fonts_loaded[i] == fonts_arr[j].replace(/\+/gi,' ')){
							found = true;
						}
					}
				}
				
				if(!found){
					google_fontes = google_fontes + (google_fontes ? '|' : '') + fonts_arr[j];
				}
			}
		}
		
		var html = $('<div>').text(html_clone.html().replace(/__script/g, "script")).html();
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				html : html,
				google_fontes : google_fontes
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
							if(dados.block_time){
								var time = dados.block_time;
								var link = dados.link;
								var link2 = dados.link2;
								
								var msg = b2make.msgs.publishBlockTimeAlert;
								
								msg = msg.replace(/\#time\#/gi,time);
								msg = msg.replace(/\#link\#/gi,link);
								msg = msg.replace(/\#link2\#/gi,link2);
								
								b2make.save_publish_open_page_url = dados.url;
								
								$.dialogbox_open({
									confirm:true,
									title:'Acessar a p&aacute;gina?',
									calback_yes: 'b2make-publish-block-time-open-url',
									width:'600px',
									height:'550px',
									msg: msg
								});
							} else {
								save_publish_open_page(dados.url);
							}
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
				}
				$.carregamento_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
				$.carregamento_close();
			}
		});
	}
	
	function save_publish_open_page(url){
		if(b2make.window_page)b2make.window_page.close();
		localStorage.setItem('b2make.page_reload',1);
		b2make.window_page = window.open(url,'b2make_page');
		if(b2make.window_page){
			b2make.window_page.focus();
		} else {
			$.dialogbox_open({
				msg: b2make.msgs.publishPagePopupBlock + ' <a href="'+url+'" target="_blank">'+url+'</a>'
			});
		}
	}
	
	$.save = function(){
		$.carregamento_open();
		if(!b2make.save){
			b2make.save = $('<div id="b2make-save"></div>');
			
			b2make.save.appendTo($('body'));
		}
		
		close_all();
		$(b2make.shadow).hide();
		
		b2make.areas_globais_save = new Array();
		b2make.areas_globais_save_ids = new Array();
		
		var html = $(b2make.site_conteiner).html();
		
		$('#b2make-save').html(html);
		
		$('#b2make-save').find('#b2make-shadow').remove();
		$('#b2make-save').find('.b2make-widget[data-type="conteiner"]').css('width',"");
		
		$('#b2make-save').find('*').css('cursor',"");
		$('#b2make-save').find('*').removeAttr('cursor');
		$('#b2make-save').find('.b2make-widget-loading').show();
		
		$('#b2make-save').find('#b2make-pagina-options').myAttr('data-device',b2make.multi_screen.device);
		
		if(b2make.areas_globais_change){
			$('#b2make-save').find(b2make.widget).each(function(){
				if($(this).myAttr('data-type') != 'conteiner-area'){
					switch($(this).myAttr('data-type')){
						case 'conteiner':
							if($(this).myAttr('data-area-global') == 's'){
								if($(this).myAttr('data-area-global-id')){
									var area_global_id = $(this).myAttr('data-area-global-id');
									var found = false;
									var areas_ids = b2make.areas_globais_save_ids;
									var area_local_id = $(this).myAttr('id');
									
									if(b2make.areas_globais_save_ids.length > 0){
										for(var i=0;i<areas_ids.length;i++){
											if(areas_ids[i] == area_global_id){
												found = true;
											}
										}
									}
									
									if(!found){
										var area_global_html = $('<div>').append($(this).clone()).html();
										
										b2make.areas_globais_save.push({
											area_global_id : area_global_id,
											area_global_html : area_global_html
										});
									}
									
									var area_mask = $('<div class="b2make-widget b2make-loading-area" data-type="conteiner" data-area-global="s" data-area-global-id="'+area_global_id+'"></div>');
									
									$(this).after(area_mask);
									$(this).remove();
									
									area_mask.myAttr('id',area_local_id);
								}
							}
						break;
					}
				}
			});
		}
		
		if(b2make.save_template){
			b2make.save_template = false;
			save_template_ajax_call($('#b2make-save').html());
		} else {
			save_ajax_call($('#b2make-save').html());
		}
		
		$('#b2make-save').html('');
	}
	
	function holder_menus_positions(){
		$('#b2make-menu-widgets').css({left:parseInt($('#b2make-menu-holder-widgets').offset().left)});
		$('#b2make-widget-sub-options-down').css({left:($(window).width() > 1000 ? $(window).width() : 1000)/2 - $('#b2make-widget-sub-options-down').width()/2});
		$('#b2make-widget-sub-options-up').css({left:($(window).width() > 1000 ? $(window).width() : 1000)/2 - $('#b2make-widget-sub-options-up').width()/2});
	}
	
	function foto_perfil_close(){
		$('#b2make-menu-start').show();
		$('#b2make-other-options').hide(); b2make.perfil_foto_image_select = false; b2make.segmento_foto_image_select = false; b2make.template_foto_image_select = false;
		$('#b2make-page-options').hide();
		widget_sub_options_close();
		widget_sub_options_close_button();
		b2make.other_options_open = false;
		
		if(b2make.template_reopen){
			if($(b2make.shadow).length == 0) $('<div id="b2make-shadow"></div>').appendTo('#b2make-site');
			$(b2make.shadow).fadeIn(b2make.fade_time);
			$('#b2make-save-templates-options').show();
			$('#b2make-menu-start').hide();
			b2make.holder_template_open = true;
			b2make.template_reopen = false;
		}
	}
	
	function holders(){
		b2make.holder_events = new Array();
		b2make.holder_events_visible = false;
		b2make.menu_widgets = "b2make-menu-widgets";
		if(!b2make.msgs.redesSociaisPergunta)b2make.msgs.redesSociaisPergunta = "Clique na imagem abaixo para selecionar uma imagem para a Rede Social";
		if(!b2make.msgs.fotoPerfilPergunta)b2make.msgs.fotoPerfilPergunta = "Clique na imagem abaixo para selecionar uma imagem para o seu perfil";
		if(!b2make.msgs.fotoSegmentoPergunta)b2make.msgs.fotoSegmentoPergunta = "Clique na imagem abaixo para selecionar uma imagem para o segmento atual";
		if(!b2make.msgs.fotoTemplatePergunta)b2make.msgs.fotoTemplatePergunta = "Clique na imagem abaixo para selecionar uma imagem para o modelo atual";
		if(!b2make.msgs.publishPage)b2make.msgs.publishPage = "Tem certeza que voc&ecirc; quer publicar est&aacute; p&aacute;gina no ambiente real?";
		if(!b2make.msgs.publishPageFavicon)b2make.msgs.publishPageFavicon = "Para que o seu favicon seja instalado em seu site &eacute; necess&aacute;rio publicar seu site no ambiente real. Tem certeza que voc&ecirc; quer publicar est&aacute; p&aacute;gina atual no ambiente real?";
		if(!b2make.msgs.publishPagePopupBlock)b2make.msgs.publishPagePopupBlock = "O seu navegador de internet bloqueou o acesso ao seu site de forma autom&aacute;tica bloqueando o POP-UP. <br><br>Favor configurar seu navegador para permitir POP-UPS de forma automatizada ou ent&atilde;o acesse o link da sua p&aacute;gina aqui:";
		if(!b2make.msgs.templateNomeLimite)b2make.msgs.templateNomeLimite = "O nome do template tem que ter mais do que 3 e menos do que 100 carctares.";
		if(!b2make.msgs.templateNomeCaracterIvalido)b2make.msgs.templateNomeCaracterIvalido = "Caracteres inv&aacute;lidos! S&oacute; &eacute; permitido os seguintes caracteres no nome: A-Za-z0-9_. ";
		if(!b2make.msgs.segmentoNomeLimite)b2make.msgs.segmentoNomeLimite = "O nome do segmento tem que ter mais do que 3 e menos do que 100 carctares.";
		if(!b2make.msgs.segmentoNomeCaracterIvalido)b2make.msgs.segmentoNomeCaracterIvalido = "Caracteres inv&aacute;lidos! S&oacute; &eacute; permitido os seguintes caracteres no nome: A-Za-z0-9_. ";
		if(!b2make.msgs.templateDefineName)b2make.msgs.templateDefineName = "Defina o nome aqui";
		if(!b2make.msgs.segmentoDefineName)b2make.msgs.segmentoDefineName = "Defina o nome aqui";
		if(!b2make.msgs.segmentoNotDefined)b2make.msgs.segmentoNotDefined = "<p>N&atilde;o foi definido o segmento. &Eacute; necess&aacute;rio escolher o segmento antes de escolher o template.</p>";
		if(!b2make.msgs.conteinerDontExist)b2make.msgs.conteinerDontExist = "<p>N&atilde;o existe nenhum conteiner criado. &Eacute; necess&aacute;rio cri&aacute;-los antes de selecion&aacute;-los.</p>";
		if(!b2make.holder_margin)b2make.holder_margin = 10;
		if(!b2make.msgs.templateChange)b2make.msgs.templateChange = 'Tem certeza que deseja trocar o modelo atual?';
		if(!b2make.msgs.templateDelete)b2make.msgs.templateDelete = 'Tem certeza que deseja excluir o modelo atual?';
		if(!b2make.msgs.segmentoDelete)b2make.msgs.segmentoDelete = 'Tem certeza que deseja excluir o segmento atual e todos os seus modelos?';
		if(!b2make.msgs.emailInvalido)b2make.msgs.emailInvalido = 'Email inv&aacute;lido, favor entrar com um endere&ccedil;o de email v&aacute;lido';
		if(!b2make.msgs.publishBlockTimeAlert)b2make.msgs.publishBlockTimeAlert = '<p><span style="color:#06F; font-weight:bold;">P&aacute;gina publicada com sucesso!</span></p><p>Sua conta passou por uma altera&ccedil;&atilde;o de configura&ccedil;&atilde;o e pode apresentar algum problema para visualizar a p&aacute;gina. Deseja acessar a p&aacute;gina mesmo assim?</p><p><span style="color:#06F; font-weight:bold;">N&atilde;o se preocupe!</span> Este alerta &eacute; meramente informativo para te ajudar caso ocorra algum erro na visualiza&ccedil;&atilde;o da p&aacute;gina! Problemas podem ocorrer de forma moment&acirc;nea nos pr&oacute;ximos <b>#time#</b> segundos. Atente para as seguintes poss&iacute;veis ocorr&ecirc;ncias para te auxiliar:</p><ul>	<li>Nova Conta - &eacute; poss&iacute;vel que os servidores de DNS ainda n&atilde;o apontaram corretamente seu dom&iacute;nio para este site. Neste caso &eacute; necess&aacute;rio aguardar o tempo informado. </li>	<li>Altera&ccedil;&atilde;o de dom&iacute;nio - &eacute; poss&iacute;vel que o sistema n&atilde;o tenha assinado o certificado SSL do dom&iacute;nio at&eacute; o momento, procedimento leva em torno de 10 minutos para assinar automaticamente. Assim, acesse a vers&atilde;o sem certificado SSL usando esse link: <b><a href="#link#" target="b2make_page">acesse aqui</a></b> , ou ent&atilde;o selecione a op&ccedil;&atilde;o de acessar o site usando as op&ccedil;&otilde;es que seu navegador oferecer.</li>	<li>Altera&ccedil;&atilde;o de dom&iacute;nio - &eacute; poss&iacute;vel que o dom&iacute;nio alterado n&atilde;o esteja corretamente apontado na autoridade do dom&iacute;nio de forma correta. Desta forma, &eacute; necess&aacute;rio informar os DNSs corretos do B2make que s&atilde;o : ns1.b2make.com e ns2.b2make.com . Aguardar o per&iacute;odo necess&aacute;rio de propaga&ccedil;&atilde;o DNS e tentar novamente. Neste caso pode-se usar o dom&iacute;nio provis&oacute;rio neste link: <b><a href="#link2#" target="b2make_page">acesse aqui</a></b> .</li></ul>';
		
		if(variaveis_js.modelo_site){
			$('#b2make-menu-save-template').show();
		} else {
			$('#b2make-menu-save-template').hide();
		}
		
		$('#b2make-menu-start').show();
		$('#b2make-page-options').hide();
		$('#b2make-other-options').hide(); b2make.perfil_foto_image_select = false; b2make.segmento_foto_image_select = false; b2make.template_foto_image_select = false;
		
		b2make.holder_events.push({
			id : "widgets",
			holder : "b2make-menu-holder-widgets",
			target : "b2make-menu-widgets",
			visible : false
		});
		
		b2make.holder_events.push({
			id : "ordenacao-paginas",
			holder : "b2make-menu-paginas-ordenacao-holder",
			target : "b2make-menu-paginas-ordenacao",
			visible : false
		});
		
		for(var i=0;i<b2make.holder_events.length;i++){
			$("#"+b2make.holder_events[i].holder).on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				holders_close();
				
				if(b2make.holder_template_open){
					holder_template_close();
				}
				
				var vars = holder_vars($(this).myAttr('id'),'holder');
				var visible = false;
				
				if($("#"+vars.target+" li").length != 0){
					if($("#"+vars.target).is(":visible")){
						$("#"+vars.target).hide();
					} else {
						$("#"+vars.target).show();
						visible = true;
					}
					
					holders_update({
						visible : visible,
						id : vars.id
					});
				} else {
					var msg = b2make.msgs.conteinerDontExist;
					
					$.dialogbox_open({
						msg: msg
					});
				}
			});
			
			$("#"+b2make.holder_events[i].target).on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
			});
			
			$("#"+b2make.holder_events[i].target+" li").on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				var type = $(this).myAttr('data-type');
				var vars = holder_vars($(this).parent().myAttr('id'),'target');
				var id = $(this).myAttr('data-id');
				
				switch(vars.id){
					case 'widgets':
						widget_open(e,{nao_conteiner : false,id:id});
					break;
					
				}
				
				$("#"+vars.target).hide();
				
				holders_update({
					visible : false,
					id : vars.id
				});
			});
		}
		
		$("html").on('mouseup tap',holders_close);
		
		$('#b2make-menu-save').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$.save();
		});
		
		$('#b2make-menu-save-template').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			holder_template_open();
		});
		
		$(document.body).on('mouseup tap','.b2make-publish-block-time-open-url',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			save_publish_open_page(b2make.save_publish_open_page_url);
		});
		
		$('#b2make-sto-template-save').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.template_save = true;
			
			if(b2make.template_open){
				template_edit_server();
			} else {
				template_add_server();
			}
		});
		
		$('#b2make-sto-segmentos').on('change',function(e){
			if($(this).val() != '0'){
				segmento_open($(this).val());
			}
			$(this).prop('selectedIndex',0);
		});
		
		$('#b2make-sto-templates').on('change',function(e){
			if($(this).val() != '0'){
				template_open($(this).val());
			}
			$(this).prop('selectedIndex',0);
		});
		
		$('#b2make-sto-segmento-new').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			segmento_add();
		});
		
		$('#b2make-sto-segmento-del').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var msg = b2make.msgs.segmentoDelete;
			
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-sto-segmento-del-yes',
				msg: msg
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-sto-segmento-del-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			segmento_del();
		});
		
		$('#b2make-sto-segmento-gravar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.segmento_open){
				segmento_edit_server();
			} else {
				segmento_add_server();
			}
		});
		
		$('#b2make-sto-template-new').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			template_add();
		});
		
		$('#b2make-sto-template-del').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var msg = b2make.msgs.templateDelete;
			
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-sto-template-del-yes',
				msg: msg
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-sto-template-del-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			template_del();
		});
		
		$('#b2make-sto-template-gravar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.template_open){
				template_edit_server();
			} else {
				template_add_server();
			}
		});
	
		$('#b2make-menu-publish,#b2make-menu-publish-2').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var msg = b2make.msgs.publishPage;
			
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-menu-publish-yes',
				msg: msg
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-menu-publish-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.save_publish = true;
			$.save();
		});
		
		$('.b2make-input-delay-to-change').keyup(function (e) {
			input_delay_to_change({
				id : $(this).myAttr('id'),
				callback_id : '#'+$(this).myAttr('id'),
				callback_event : 'changed'
			});
		});
		
		$('.b2make-input-delay-to-change').on('changed',function(e){
			var value = this.value;
			var opcao = 'pagina-vars';
			var variavel = $(this).myAttr('data-variavel');
			var bg_color = $(this).myAttr('data-bg-color-default');
			var color = $(this).myAttr('data-color-default');
			var regras = ($(this).myAttr('data-regras') ? $(this).myAttr('data-regras').split(',') : new Array() );
			var flag = true;
			var msg;
			
			for(var i=0;i<regras.length;i++){
				switch(regras[i]){
					case 'email':
						if(!checkMail(value)){
							flag = false;
							msg = b2make.msgs.emailInvalido;
						}
					break;
				}
			}
			
			if(!bg_color){
				$(this).myAttr('data-bg-color-default',$(this).css('backgroundColor'));
				$(this).myAttr('data-color-default',$(this).css('color'));
			}
			
			if(msg){
				$(this).css('backgroundColor','red');
				$(this).css('color','white');
			} else {
				$(this).css('backgroundColor',bg_color);
				$(this).css('color',color);
			}
			
			if(flag)
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					variavel : variavel,
					value : value
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
								// Código
							break;
							case 'SemPermissao':
								sem_permissao_redirect();
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
			
		});
		
		input_start_values();
		
		$('#b2make-page-options-holder').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_close_all();
			if(!b2make.msgs['configTitle'])b2make.msgs['configTitle'] = 'Configura&ccedil;&otilde;es';
			b2make.widget_edit_sub_options_open = true;
			b2make.widget_sub_options_type = 'config';
			$.widget_sub_options_open();
		});
		
		$('.b2make-other-options').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_close_all();
			$('#b2make-menu-start').hide();
			$('#b2make-other-options').show(); 
			$('#b2make-page-options').hide();
			
			var data_type = $(this).myAttr('data-type');
			
			if(b2make.mudar_foto_perfil_flag){
				data_type = 'foto-perfil';
			}
			
			switch(data_type){
				case 'foto-perfil': 
					if($(this).myAttr('data-obj'))b2make.perfil_foto_image_select = '#'+$(this).myAttr('data-obj'); else b2make.perfil_foto_image_select = false;
					
					$('#b2make-other-options').find('.b2make-widget-options-title').html($(this).myAttr('data-title'));
					$('#b2make-other-options').find('.b2make-other-options-conteiner').html('<div class="b2make-foto-perfil-titulo">'+b2make.msgs.fotoPerfilPergunta+'</div>');
					
					b2make.widget_sub_options_type = 'foto-perfil';
					b2make.widget_edit_sub_options_open = true;
					$.widget_sub_options_open(); 
				break;	
				case 'foto-segmento': 
					if($(this).myAttr('data-obj'))b2make.segmento_foto_image_select = '#'+$(this).myAttr('data-obj'); else b2make.segmento_foto_image_select = false;
					
					$('#b2make-other-options').find('.b2make-widget-options-title').html($(this).myAttr('data-title'));
					$('#b2make-other-options').find('.b2make-other-options-conteiner').html('<div class="b2make-foto-perfil-titulo">'+b2make.msgs.fotoSegmentoPergunta+'</div>');
					
					b2make.widget_sub_options_type = 'foto-segmento';
					b2make.widget_edit_sub_options_open = true;
					b2make.template_reopen = true;
					$.widget_sub_options_open(); 
				break;
				case 'foto-template': 
					if($(this).myAttr('data-obj'))b2make.template_foto_image_select = '#'+$(this).myAttr('data-obj'); else b2make.template_foto_image_select = false;
					
					$('#b2make-other-options').find('.b2make-widget-options-title').html($(this).myAttr('data-title'));
					$('#b2make-other-options').find('.b2make-other-options-conteiner').html('<div class="b2make-foto-perfil-titulo">'+b2make.msgs.fotoTemplatePergunta+'</div>');
					
					b2make.widget_sub_options_type = 'foto-template';
					b2make.widget_edit_sub_options_open = true;
					b2make.template_reopen = true;
					$.widget_sub_options_open(); 
				break;				
			}
			
			b2make.other_options_open = true;
		});
		
		$('#b2make-other-options-close').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			foto_perfil_close();
		});
	}
	
	holders();
	
	$.menu_conteiner_aba_extra_open = function(){
		var type = b2make.conteiner_child_type;
		
		b2make.menu_conteiner_aba_extra_list['menu'] = true;
		b2make.menu_conteiner_aba_extra_list['agenda'] = true;
		b2make.menu_conteiner_aba_extra_list['player'] = true;
		b2make.menu_conteiner_aba_extra_list['albummusicas'] = true;
		b2make.menu_conteiner_aba_extra_list['albumfotos'] = true;
		b2make.menu_conteiner_aba_extra_list['download'] = true;
		
		if(b2make.menu_conteiner_aba_extra_list[type]){
			b2make.menu_conteiner_aba_extra = true;
		}
	
		if(b2make.menu_conteiner_aba_extra){
			var start_width = 31;
			var margin = 1;
			var width_total = 0;
			var count = b2make.menu_conteiner_aba.count;
			var count2 = 0;
			var id = b2make.menu_conteiner_aba.ids[b2make.conteiner_child_show];
			
			$('.b2make-woc-menu-aba[data-posicao="'+1+'"]').css('left',(start_width+width_total));
			width_total = width_total + $('.b2make-woc-menu-aba[data-posicao="'+1+'"]').outerWidth() + margin;
			
			$('.b2make-woc-menu-aba').each(function(){
				var this_type = $(this).myAttr('data-type');
				
				if(this_type == type){
					$(this).show();
					count2++;
				}
			});
			
			for(var i=1;i<=count2;i++){
				$('.b2make-woc-menu-aba[data-type="'+type+'"][data-posicao-extra="'+i+'"]').css('left',(start_width+width_total));
				width_total = width_total + $('.b2make-woc-menu-aba[data-type="'+type+'"][data-posicao-extra="'+i+'"]').outerWidth() + margin;
			}
			
			for(var i=2;i<=count;i++){
				$('.b2make-woc-menu-aba[data-posicao="'+i+'"]').css('left',(start_width+width_total));
				width_total = width_total + $('.b2make-woc-menu-aba[data-posicao="'+i+'"]').outerWidth() + margin;
			}
			
			$('.b2make-conteiner-aba-extra[data-type="'+type+'"]').each(function(){
				if(id == $(this).myAttr('data-id')){
					$(this).addClass('b2make-conteiner-aba-active');
					$(this).show();
				} else {
					$(this).removeClass('b2make-conteiner-aba-active');
					$(this).hide();
				}
			});	
		}
	}
	
	$.menu_conteiner_aba_extra_close = function(){
		if(b2make.menu_conteiner_aba_extra){
			var start_width = 31;
			var margin = 1;
			var width_total = 0;
			var count = b2make.menu_conteiner_aba.count;
			
			for(var i=1;i<=count;i++){
				$('.b2make-woc-menu-aba[data-posicao="'+i+'"]').css('left',(start_width+width_total));
				width_total = width_total + $('.b2make-woc-menu-aba[data-posicao="'+i+'"]').outerWidth() + margin;
			}
			
			$('.b2make-woc-menu-aba').each(function(){
				var type = $(this).myAttr('data-type');
				
				if(type){
					$(this).hide();
				}
			});
			
			$('.b2make-conteiner-aba-extra').each(function(){
				$(this).hide();
			});
		}
		
		b2make.menu_conteiner_aba_extra = false;
	}
	
	function menu_conteiner_aba_start(){
		var id_atual = b2make.menu_conteiner_aba.ids[b2make.conteiner_child_show];
		var id = (id_atual ? id_atual : 'principal');
		
		$('.b2make-woc-menu-aba').each(function(){
			$(this).removeClass('b2make-woc-menu-aba-active');
			
		});
		
		$('.b2make-woc-menu-aba[data-id="'+id+'"]').addClass('b2make-woc-menu-aba-active');
		
		$('.b2make-conteiner-aba').each(function(){
			
			if(id == $(this).myAttr('data-id')){
				$(this).addClass('b2make-conteiner-aba-active');
				$(this).show();
			} else {
				$(this).removeClass('b2make-conteiner-aba-active');
				$(this).hide();
			}
		});		
	}
	
	$.menu_conteiner_aba_load = function(p){
		b2make.menu_conteiner_aba_extra_list[p.id] = true;
		
		$('#b2make-woc-comum').append(p.html);
		
		$('.b2make-conteiner-aba-extra').each(function(){
			$(this).hide();
		});
		
		$('.b2make-woc-menu-aba').each(function(){
			var type = $(this).myAttr('data-type');
			
			if(type){
				$(this).hide();
			}
		});
	}
	
	function menu_conteiner_aba(){
		b2make.menu_conteiner_aba = {};
		
		b2make.menu_conteiner_aba.ids = new Array();
		var start_width = 31;
		var margin = 1;
		var width_total = 0;
		var count = 0;
		
		$('.b2make-conteiner-aba-extra').each(function(){
			$(this).hide();
		});
		
		$('.b2make-conteiner-aba').each(function(){
			if($(this).hasClass('b2make-conteiner-aba-active')){
				$(this).show();
			} else {
				$(this).hide();
			}
			
			count++;
		});
		
		b2make.menu_conteiner_aba.count = count;
		
		for(var i=1;i<=count;i++){
			$('.b2make-woc-menu-aba[data-posicao="'+i+'"]').css('left',(start_width+width_total));
			width_total = width_total + $('.b2make-woc-menu-aba[data-posicao="'+i+'"]').outerWidth() + margin;
		}
		
		$('.b2make-woc-menu-aba').each(function(){
			var type = $(this).myAttr('data-type');
			
			if(type){
				$(this).hide();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-woc-menu-aba',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-id');
			var type = $(this).myAttr('data-type');
			
			$('.b2make-woc-menu-aba').each(function(){
				$(this).removeClass('b2make-woc-menu-aba-active');
				
			});
			
			$(this).addClass('b2make-woc-menu-aba-active');
			
			$('.b2make-conteiner-aba').each(function(){
				
				if(id == $(this).myAttr('data-id')){
					$(this).addClass('b2make-conteiner-aba-active');
					$(this).show();
				} else {
					$(this).removeClass('b2make-conteiner-aba-active');
					$(this).hide();
				}
			});
			
			$('.b2make-conteiner-aba-extra').each(function(){
				if(id == $(this).myAttr('data-id') && type == $(this).myAttr('data-type')){
					$(this).addClass('b2make-conteiner-aba-active');
					$(this).show();
				} else {
					$(this).removeClass('b2make-conteiner-aba-active');
					$(this).hide();
				}
			});
			
			b2make.menu_conteiner_aba.ids[b2make.conteiner_child_show] = id;
		});
	}
	
	menu_conteiner_aba();
	
	function selecionador_objetos_update(){
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		
		conteiner.css('padding-left',parseInt($(obj).css('padding-left')) + parseInt($(obj).css('border-left-width')));
		conteiner.css('padding-right',parseInt($(obj).css('padding-right')) + parseInt($(obj).css('border-right-width')));
		conteiner.css('padding-top',parseInt($(obj).css('padding-top')) + parseInt($(obj).css('border-top-width')));
		conteiner.css('padding-bottom',parseInt($(obj).css('padding-bottom')) + parseInt($(obj).css('border-bottom-width')));
		
		$('#b2make-selecionar-objetos-rotate-mask').css('padding','0px '+conteiner.css('paddingLeft'));
		$('#b2make-selecionador-objetos-widget').css('padding',$(obj).css('padding'));
		$('#b2make-selecionador-objetos-mask').css('padding',$(obj).css('padding'));
	}
	
	function selecionador_objetos_open(){
		selecionador_objetos();
	
		var obj_area = b2make.conteiner_obj;
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		var widget = conteiner.find('#b2make-selecionador-objetos-widget');
		var angulo = 0;
		var type = $(obj).myAttr('data-type');
		
		if($(obj_area).find('.b2make-widget[data-type="conteiner-area"]').length > 0){
			obj_area = $(obj_area).find('.b2make-widget[data-type="conteiner-area"]').get(0);
		}
		
		if($(obj).myAttr('data-angulo')){
			angulo = $(obj).myAttr('data-angulo');
			conteiner.myAttr('data-angulo',angulo);
		} else {
			conteiner.myAttr('data-angulo',null);
		}
		
		if(angulo > 0){
			$(obj).css('-moz-transform','rotate(0deg)');
			$(obj).css('-webkit-transform','rotate(0deg)');
			$(obj).css('-webkit-backface-visibility','auto');
			$(obj).css('-o-transform','rotate(0deg)');
			$(obj).css('-ms-transform','rotate(0deg)');
			$(obj).css('transform','rotate(0deg)');
		}
		
		conteiner.css('width',(type == 'banners' || type == 'texto' ? $(obj).width() : $(obj).outerWidth(true)));
		conteiner.css('height',$(obj).height());
		
		selecionador_objetos_update();
		
		conteiner.css('top',parseInt($(obj).position().top)-b2make.selecionador_borda_w);
		conteiner.css('left',parseInt($(obj).position().left)-b2make.selecionador_borda_w);
		conteiner.css('-moz-transform','rotate('+angulo+'deg)');
		conteiner.css('-webkit-transform','rotate('+angulo+'deg)');
		conteiner.css('-webkit-backface-visibility','hidden');
		conteiner.css('-o-transform','rotate('+angulo+'deg)');
		conteiner.css('-ms-transform','rotate('+angulo+'deg)');
		conteiner.css('transform','rotate('+angulo+'deg)');
		
		$(obj).css('width','inherit');
		$(obj).css('height','inherit');
		$(obj).css('top','0px');
		$(obj).css('left','0px');
		
		$(obj).after('<div id="b2make-selecionador-objetos-mark"></div>');
		$('#b2make-selecionador-objetos-mask').show();
		
		$(obj).appendTo(widget);
		$(obj_area).append(conteiner);
		conteiner.show();
	}
	
	function selecionador_objetos_close(){
		var obj_area = b2make.conteiner_obj;
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		var angulo = 0;
		var area_central = false;
		
		if(b2make.player_playing){
			$(b2make.player_playing).jPlayer("pause");
			b2make.player[b2make.player_playing].player_pause = true;
			$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
		}
		
		if($(obj_area).find('.b2make-widget[data-type="conteiner-area"]').length > 0){
			obj_area = $(obj_area).find('.b2make-widget[data-type="conteiner-area"]').get(0);
			area_central = true;
		}
		
		angulo = conteiner.myAttr('data-angulo');
		
		conteiner.css('-moz-transform','');
		conteiner.css('-webkit-transform','');
		conteiner.css('-webkit-backface-visibility','auto');
		conteiner.css('-o-transform','');
		conteiner.css('-ms-transform','');
		conteiner.css('transform','');
		
		var ajuste_top = 0;
		var ajuste_left = 0;
		
		if(b2make.conteiner_close_full && area_central){
			ajuste_left = 2;
		}
		
		if(b2make.conteiner_close_full && b2make.multi_screen.device == 'phone'){
			ajuste_left = 2;
			ajuste_top = 2;
		}
		
		$(obj).css('width',conteiner.css('width'));
		$(obj).css('height',conteiner.css('height'));
		$(obj).css('top',parseInt(conteiner.position().top)+b2make.selecionador_borda_w+ajuste_top);
		$(obj).css('left',parseInt(conteiner.position().left)+b2make.selecionador_borda_w+ajuste_left);
		
		if(angulo > 0){
			$(obj).myAttr('data-angulo',angulo);
			
			$(obj).css('-moz-transform','rotate('+angulo+'deg)');
			$(obj).css('-webkit-transform','rotate('+angulo+'deg)');
			$(obj).css('-webkit-backface-visibility','hidden');
			$(obj).css('-o-transform','rotate('+angulo+'deg)');
			$(obj).css('-ms-transform','rotate('+angulo+'deg)');
			$(obj).css('transform','rotate('+angulo+'deg)');
		} else {
			if($(obj).myAttr('data-angulo')){
				$(obj).css('-moz-transform','');
				$(obj).css('-webkit-transform','');
				$(obj).css('-webkit-backface-visibility','auto');
				$(obj).css('-o-transform','');
				$(obj).css('-ms-transform','');
				$(obj).css('transform','');
				
				$(obj).myAttr('data-angulo',null);
			}
		}
		
		conteiner.css('width','auto');
		conteiner.css('height','auto');
		conteiner.css('padding','auto');
		conteiner.css('top','auto');
		conteiner.css('left','auto');
		
		$('#b2make-selecionar-objetos-rotate-mask').css('padding','auto');
		$('#b2make-selecionador-objetos-widget').css('padding','auto');
		$('#b2make-selecionador-objetos-mask').css('padding','auto');
		
		$('#b2make-selecionador-objetos-mark').after($(obj));
		$('#b2make-selecionador-objetos-mark').remove();
		
		$('body').append(conteiner);
		conteiner.hide();
	}
	
	function selecionador_objetos(){
		b2make.selecionador_objetos = {};
		
		b2make.selecionador_borda_w = 1;
		b2make.selecionador_ajuste_nw = 8;
		b2make.selecionador_ajuste_ne = 10;
		b2make.selecionador_ajuste_sw = 8;
		b2make.selecionador_ajuste_se = 10;
		b2make.selecionador_ajuste_n = 9;
		b2make.selecionador_ajuste_s = 10;
		
		b2make.selecionador_objetos.conteiner = $('<div id="b2make-selecionador-objetos" class="b2make-selecionador-objetos"><div id="b2make-selecionador-objetos-widget"></div><div id="b2make-selecionador-objetos-mask"></div></div>');
		b2make.selecionador_objetos.top_left = $('<div id="b2make-selecionador-objetos-top-left" class="b2make-selecionador-objetos-box"><div class="b2make-selecionador-objetos-box-mini"></div></div>');
		b2make.selecionador_objetos.top_right = $('<div id="b2make-selecionador-objetos-top-right" class="b2make-selecionador-objetos-box"><div class="b2make-selecionador-objetos-box-mini"></div></div>');
		b2make.selecionador_objetos.bottom_left = $('<div id="b2make-selecionador-objetos-bottom-left" class="b2make-selecionador-objetos-box"><div class="b2make-selecionador-objetos-box-mini"></div></div>');
		b2make.selecionador_objetos.bottom_right = $('<div id="b2make-selecionador-objetos-bottom-right" class="b2make-selecionador-objetos-box"><div class="b2make-selecionador-objetos-box-mini"></div></div>');
		b2make.selecionador_objetos.rotate = $('<div id="b2make-selecionar-objetos-rotate-mask" class="b2make-selecionar-objetos-rotate-mask"><div id="b2make-selecionar-objetos-rotate" class="b2make-selecionar-objetos-rotate"></div></div>');
		
		b2make.selecionador_objetos.top_left.appendTo(b2make.selecionador_objetos.conteiner);
		b2make.selecionador_objetos.top_right.appendTo(b2make.selecionador_objetos.conteiner);
		b2make.selecionador_objetos.bottom_left.appendTo(b2make.selecionador_objetos.conteiner);
		b2make.selecionador_objetos.bottom_right.appendTo(b2make.selecionador_objetos.conteiner);
		b2make.selecionador_objetos.rotate.appendTo(b2make.selecionador_objetos.conteiner);
		
		b2make.selecionador_objetos.conteiner.appendTo('body');
	}
	
	function close_all(){
		if(b2make.widgets_holder_events_visible){
			widgets_holders_close();
		}
		
		if(b2make.holder_events_visible){
			holders_close();
		}
		
		if(b2make.conteiner_show){
			conteiner_close_all();
		}
		
		if(b2make.dialogbox){
			$.dialogbox_close();
		}
		
		if(b2make.lightbox){
			lightbox_close();
		}
		
		if(b2make.widget_move){
			b2make.widget_move = false;
		}
		
		if(b2make.widget_child_move){
			b2make.widget_child_move = false;
		}
		
		if(b2make.widget_sub_options_open){
			widget_sub_options_close();
		}
		
		if(b2make.holder_template_open){
			holder_template_close();
		}
		
		if(b2make.other_options_open){
			foto_perfil_close();
		}
		
		if(b2make.multiselect.ativo){
			multi_select_close();
			multi_select_area_close();
		}
		
		$('#b2make-listener').trigger('close-all');
	}
	
	function texto_add(){
		var widget_type = 'texto';
		
		history_add({local:'conteiner_child_add'});
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			$(b2make.menu).height(b2make.menu_height);
			$(b2make.menu_mask).height(b2make.menu_height);
			
			b2make.texto_for_textarea_select = true;
			
			var p = {};
			var cont = $('<div class="b2make-widget b2make-texto"><div class="b2make-texto-table" data-type="texto"><div class="b2make-texto-cel" data-type="texto">'+b2make.texto.value+'</div></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			
			cont.css('position','absolute');
			
			cont.css('fontSize',b2make.texto.fontSize);
			cont.css('color',b2make.texto.color);
			//cont.css('backgroundColor',b2make.texto.backgroundColor);
			cont.css('top',b2make.texto.top);
			cont.css('left',b2make.texto.left);
			cont.css('width',b2make.texto.width);
			cont.css('height',b2make.texto.height);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			p.add = true;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
			$('.b2make-image-holder').removeClass('b2make-image-holder-clicked');
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function texto_for_textarea(){
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		
		if(!b2make.texto_textarea){
			b2make.texto_textarea = $('<textarea id="b2make-textarea"></textarea>');
			
			b2make.texto_textarea.myAttr('data-type','texto');
			
			b2make.texto_textarea.css('position','absolute');
			
			b2make.texto_textarea.css('fontSize',b2make.texto.fontSize);
			b2make.texto_textarea.css('color',b2make.texto.color);
			b2make.texto_textarea.css('backgroundColor',b2make.texto.backgroundColor);
			b2make.texto_textarea.css('width',b2make.texto.width);
			b2make.texto_textarea.css('height',b2make.texto.height);
			b2make.texto_textarea.css('zIndex','3');
			b2make.texto_textarea.css('border','none');
			b2make.texto_textarea.css('top','0px');
			b2make.texto_textarea.css('left','0px');
		}
		
		b2make.copy_paste.inativo = true;
		
		var texto = $(obj).find('div').find('div').html();
		texto = texto.replace(/<br>/gi,"\n");
		texto = texto.replace(/&nbsp;/gi," ");
		b2make.texto_textarea.val(texto);
		b2make.texto_for_textarea = true;
		$(obj).find('div').find('div').html('');
		$(conteiner).append(b2make.texto_textarea);
		textarea_resize($(obj).outerWidth(),$(obj).outerHeight());
		if(b2make.texto_for_textarea_select){b2make.texto_textarea.select();b2make.texto_for_textarea_select = false;}
		b2make.texto_textarea.focus();
	}
	
	function textarea_for_texto(){
		var obj = b2make.conteiner_child_obj;
		
		b2make.texto_textarea.remove();
		var texto = b2make.texto_textarea.val();
		texto = texto.replace(/\r\n|\r|\n/g,"<br>");
		texto = texto.replace(/  /g,"&nbsp;&nbsp;");
		b2make.texto_for_textarea = false;
		
		$(obj).find('div').find('div').html(texto);
		b2make.copy_paste.inativo = false;
	}
	
	function textarea_resize(w,h){
		if(b2make.texto_for_textarea)
		if(w && h){
			b2make.texto_textarea.outerWidth(w);
			b2make.texto_textarea.outerHeight(h);
		}
	}
	
	function imagem_add(){
		var widget_type = 'imagem';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var cont = $('<div class="b2make-widget">'+b2make.imagem.value+'</div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			
			cont.css('position','absolute');

			cont.css('fontSize',b2make.imagem.fontSize);
			cont.css('color',b2make.imagem.color);
			cont.css('top',b2make.imagem.top);
			cont.css('left',b2make.imagem.left);
			cont.css('width',b2make.imagem.width);
			cont.css('height',b2make.imagem.height);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				b2make.conteiner_child_other = true;
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
			$('.b2make-image-holder').removeClass('b2make-image-holder-clicked');
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function iframe_add(){
		var widget_type = 'iframe';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<iframe style="width: '+b2make.iframe.width+'; height: '+b2make.iframe.height+';" allowtransparency="true" frameborder="0" scrolling="no"></iframe>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.iframe.top);
			cont.css('left',b2make.iframe.left);
			cont.css('width',b2make.iframe.width);
			cont.css('height',b2make.iframe.height);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.add = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function iframe_resize(){
		if(b2make.widget_specific_type == 'iframe'){
			var obj = b2make.conteiner_child_obj;
			var iframe = $(obj).find('div').find('iframe');
			
			if(obj && iframe){
				iframe.width($(obj).outerWidth());
				iframe.height($(obj).outerHeight());
			}
		}
	}
	
	function iframe_for_textarea(){
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		
		if(!b2make.iframe_textarea){
			b2make.iframe_textarea = $('<textarea id="b2make-iframe-textarea"></textarea>');
			
			b2make.iframe_textarea.myAttr('data-type','iframe');
			
			b2make.iframe_textarea.css('position','absolute');
			
			b2make.iframe_textarea.css('fontSize',b2make.iframe.fontSize);
			b2make.iframe_textarea.css('color',b2make.iframe.color);
			b2make.iframe_textarea.css('backgroundColor',b2make.iframe.backgroundColor);
			b2make.iframe_textarea.css('width',b2make.iframe.width);
			b2make.iframe_textarea.css('height',b2make.iframe.height);
			b2make.iframe_textarea.css('zIndex','3');
			b2make.iframe_textarea.css('border','none');
			b2make.iframe_textarea.css('top','0px');
			b2make.iframe_textarea.css('left','0px');
		}
		
		$(obj).find('div.b2make-widget-out').find('iframe').hide();
		
		var texto = ($(obj).myAttr('data-iframe-code') ? decodeURIComponent($(obj).myAttr('data-iframe-code')) : b2make.msgs.iframeTextAdd);
		b2make.iframe_textarea.val(texto);
		b2make.iframe_for_textarea = true;
		$(conteiner).append(b2make.iframe_textarea);
		textarea_iframe_resize($(obj).outerWidth(),$(obj).outerHeight());
		b2make.iframe_textarea.select();
		b2make.iframe_textarea.focus();
	}
	
	function textarea_for_iframe(){
		var obj = b2make.conteiner_child_obj;
		var widget;
		var id;
		
		b2make.iframe_textarea.remove();
		var texto = b2make.iframe_textarea.val();
		b2make.iframe_for_textarea = false;
		widget = texto;
		
		if(!$(obj).myAttr('data-iframe-id')){
			if(!b2make.iframes) b2make.iframes = 0;
			b2make.iframes++;
			
			id = b2make.iframes;
			
			$(obj).myAttr('data-iframe-id',id);
			$(obj).myAttr('data-iframe-code',encodeURIComponent(widget));
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : 'widget-iframe-add',
					widget:widget,
					id:id
				},
				beforeSend: function(){
				},
				success: function(txt){
					$(obj).find('div.b2make-widget-out').find('iframe').html('');
					$(obj).find('div.b2make-widget-out').find('iframe').myAttr('src','.?ajax=sim&opcao=widget-iframe&id='+id);
					$(obj).find('div.b2make-widget-out').find('iframe').show();
				},
				error: function(txt){
					//
				}
			});
		} else {
			id = $(obj).myAttr('data-iframe-id');
			$(obj).myAttr('data-iframe-code',encodeURIComponent(widget));
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : 'widget-iframe-edit',
					widget:widget,
					id:id
				},
				beforeSend: function(){
				},
				success: function(txt){
					$(obj).find('div.b2make-widget-out').find('iframe').html('');
					$(obj).find('div.b2make-widget-out').find('iframe').myAttr('src','.?ajax=sim&opcao=widget-iframe&id='+id);
					$(obj).find('div.b2make-widget-out').find('iframe').show();
				},
				error: function(txt){
					//
				}
			});
		}
	}
	
	function textarea_iframe_resize(w,h){
		if(b2make.iframe_for_textarea)
		if(w && h){
			b2make.iframe_textarea.outerWidth(w);
			b2make.iframe_textarea.outerHeight(h);
		}
	}
	
	function twitter_add(){
		var widget_type = 'twitter';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<iframe src="//platform.twitter.com/widgets/follow_button.html?screen_name='+b2make.twitter.user+'&show_count=true&show_screen_name=true" style="width: '+b2make.twitter.width+'px; height: '+b2make.twitter.height+'px;" allowtransparency="true" frameborder="0" scrolling="no"></iframe>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.twitter.top);
			cont.css('left',b2make.twitter.left);
			cont.css('width',b2make.twitter.width+'px');
			cont.css('height',b2make.twitter.height+'px');
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function facebook_add(){
		var widget_type = 'facebook';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<iframe src="https://www.facebook.com/plugins/likebox.php?href='+b2make.facebook.href+'&width='+b2make.facebook.width+'&height='+b2make.facebook.height+'&show_faces=true&colorscheme=light&stream=false&show_border=false&header=false&appId=358146730957925" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'+b2make.facebook.width+'px; height:'+b2make.facebook.height+'px;" allowTransparency="true"></iframe>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.facebook.top);
			cont.css('left',b2make.facebook.left);
			cont.css('backgroundColor',b2make.facebook.backgroundColor);
			cont.css('width',b2make.facebook.width+'px');
			cont.css('height',b2make.facebook.height+'px');
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function facebook_resize(){
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		var width = $(conteiner).width();
		var height = $(conteiner).height();
		
		var widget = '<iframe src="https://www.facebook.com/plugins/likebox.php?href='+($(obj).myAttr('data-href')?$(obj).myAttr('data-href'):b2make.facebook.href)+'&width='+width+'&height='+height+'&show_faces=true&colorscheme=light&stream=false&show_border=false&header=false&appId=358146730957925" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'+width+'px; height:'+height+'px;" allowTransparency="true"></iframe>';
		
		$(obj).find('div.b2make-widget-out').find('iframe').remove();
		$(obj).find('div.b2make-widget-out').append(widget);
	}
	
	function sound_cloud_add(){
		var widget_type = 'soundcloud';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<iframe width="'+b2make.soundcloud.width+'" height="'+b2make.soundcloud.height+'" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/users/'+b2make.soundcloud.user+'&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false&amp;visual=true"></iframe>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.soundcloud.top);
			cont.css('left',b2make.soundcloud.left);
			cont.css('backgroundColor',b2make.soundcloud.backgroundColor);
			cont.css('width',b2make.soundcloud.width+'px');
			cont.css('height',b2make.soundcloud.height+'px');
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function sound_cloud_resize(){
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		var width = $(conteiner).width();
		var height = $(conteiner).height();
		
		var widget = '<iframe width="'+width+'" height="'+height+'" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/users/'+($(obj).myAttr('data-user')?$(obj).myAttr('data-user'):b2make.soundcloud.user)+'&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false&amp;visual=true"></iframe>';
		
		$(obj).find('div.b2make-widget-out').find('iframe').remove();
		$(obj).find('div.b2make-widget-out').append(widget);
	}
	
	function galeria_widget_add(){
		var widget_type = 'galeria';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-galeria-add-text">'+b2make.msgs.galeriaTextAdd+'</div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.galeria_widget.top);
			cont.css('left',b2make.galeria_widget.left);
			cont.css('backgroundColor',b2make.galeria_widget.backgroundColor);
			cont.css('width',b2make.galeria_widget.width+'px');
			cont.css('height',b2make.galeria_widget.height+'px');
			cont.css('font-size',b2make.galeria_widget.fontSize);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			galeria_widget_create({
				galeria_id : b2make.galerias_atual
			});
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function galeria_widget_create(p){
		if(!p)p = {};
		
		var id_func = 'galeria-images';
		var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
		var obj_id = $(obj).myAttr('id');
		
		$(obj).myAttr('data-galeria-id',p.galeria_id);
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : p.galeria_id
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
							if(dados.images.length == 0){
								$(obj).find('div.b2make-widget-out').html('<div class="b2make-galeria-add-text">'+b2make.msgs.galeriaTextImagesEmpty+'</div>');
							} else {
								$(obj).find('div.b2make-widget-out').html('<div class="b2make-gwi-prev"><div class="b2make-gwi-table"><div class="b2make-gwi-cel"> << </div></div></div><div class="b2make-galeria-widget-holder"></div><div class="b2make-gwi-next"><div class="b2make-gwi-table"><div class="b2make-gwi-cel"> >> </div></div></div>');
								
								for(var i=0;i<dados.images.length;i++){
									$(obj).find('div.b2make-widget-out').find('div.b2make-galeria-widget-holder').append($('<div id="b2make-galeria-widget-imagem-'+dados.images[i].id+'" class="b2make-galeria-widget-image" data-image-id="'+dados.images[i].id+'" data-image-url="'+dados.images[i].imagem+'" data-image-width="'+dados.images[i].width+'" data-image-height="'+dados.images[i].height+'"><a href="'+dados.images[i].imagem+'" rel="prettyPhoto['+obj_id+']"><img src="'+dados.images[i].mini+'"></a></div>'));
								}
								
								var prettyphoto_var = {animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true};
								setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto(prettyphoto_var); }, 100);
								
								widgets_resize();
							}
						break;
						case 'NaoExisteId':
							$(obj).find('div.b2make-widget-out').html('<div class="b2make-galeria-add-text">'+b2make.msgs.galeriaTextImagesEmpty+'</div>');
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
	
	function slideshow_widget_add(){
		var widget_type = 'slideshow';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-slideshow-add-text"></div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.galeria_widget.top);
			cont.css('left',b2make.galeria_widget.left);
			cont.css('width',b2make.galeria_widget.width+'px');
			cont.css('height',b2make.galeria_widget.height+'px');
			cont.css('font-size',b2make.galeria_widget.fontSize);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			
			if(b2make.slide_show_atual){
				$('.b2make-slide-show-show').each(function(){
					if(b2make.slide_show_atual == $(this).myAttr('data-slide-show-id')){
						$(this).myAttr('data-status','show');
					}
				});
				
				slideshow_widget_create({
					slideshow_id : b2make.slide_show_atual
				});
			}
			
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function slideshow_widget_create(p){
		if(!p)p = {};
		
		var id_func = 'slide-show-images';
		var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
		var obj_id = $(obj).myAttr('id');
		
		$(obj).myAttr('data-slide-show-id',p.slideshow_id);
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : p.slideshow_id
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					$(obj).find('div.b2make-widget-out').html('<div class="b2make-slideshow-widget-holder"></div>');
					
					switch(dados.status){
						case 'Ok':
							if(dados.images.length == 0){
								var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
								$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').append($('<div id="b2make-slideshow-widget-imagem-0" class="b2make-slideshow-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
								$(obj).myAttr('data-imagens-urls',imagem);
								widgets_resize();
							} else {
								for(var i=0;i<dados.images.length;i++){
									$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').append($('<div id="b2make-slideshow-widget-imagem-'+dados.images[i].id+'" class="b2make-slideshow-widget-image" data-image-id="'+dados.images[i].id+'" data-image-url="'+dados.images[i].imagem+'" data-image-width="'+dados.images[i].width+'" data-image-height="'+dados.images[i].height+'" style="background-image:url('+dados.images[i].imagem+');"></div>'));
								}
								
								var imagens = '';
								
								for(var i=0;i<dados.images.length;i++){
									imagens = imagens + (imagens.length > 0 ? ',' : '') + dados.images[i].imagem;
								}
								
								$(obj).myAttr('data-imagens-urls',imagens);
								
								widgets_resize();
							}
						break;
						case 'NaoExisteId':
							var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
							$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').append($('<div id="b2make-slideshow-widget-imagem-0" class="b2make-slideshow-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
							$(obj).myAttr('data-imagens-urls',imagem);
							widgets_resize();
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
	
	function slideshow_animation_stop(obj){
		$(obj).myAttr('data-animation',null);
		$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').stop();
	}
	
	function albumfotos_widget_add(){
		var widget_type = 'albumfotos';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-slideshow-add-text"></div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.albumfotos_widget.top);
			cont.css('left',b2make.albumfotos_widget.left);
			//cont.css('backgroundColor',b2make.albumfotos_widget.backgroundColor);
			cont.css('width',b2make.albumfotos_widget.width+'px');
			cont.css('height',b2make.albumfotos_widget.height+'px');
			cont.css('font-size',b2make.albumfotos_widget.fontSize);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function albumfotos_widget_albuns(p = {}){
		$('.b2make-album-fotos-lista-album').each(function(){
			if($(this).find('.b2make-album-fotos-show').myAttr('data-status') == 'show'){
				var id = $(this).find('.b2make-album-fotos-show').myAttr('data-album-fotos-id');
				var nome = $(this).find('.b2make-album-fotos-nome').html();
				
				if(p.obj){
					albumfotos_widget_album_add({
						albumfotos_id : id,
						albumfotos_nome : nome,
						conteiner_child_obj : p.obj
					});
				} else {
					albumfotos_widget_album_add({
						albumfotos_id : id,
						albumfotos_nome : nome
					});
				}
			}
		});
	}
	
	function albumfotos_widget_caixa_posicao_atualizar(p){
		var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
		var obj_height = $(obj).height();
		var obj_width = $(obj).width();
		
		var margem_seta = parseInt(($(obj).myAttr('data-seta-margem') ? $(obj).myAttr('data-seta-margem') : '20'));
		var height = parseInt(($(obj).myAttr('data-seta-tamanho') ? $(obj).myAttr('data-seta-tamanho') : '45'));
		var width = Math.floor((height * 27)/45);
		
		var seta_cont_left = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-left-arrow-cont');
		var seta_cont_right = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-right-arrow-cont');
		var images_cont = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont');
		var seta_left = seta_cont_left.find('.b2make-albumfotos-widget-left-arrow');
		var seta_right = seta_cont_right.find('.b2make-albumfotos-widget-right-arrow');
		
		seta_left.css('width',width+'px').css('height',height+'px');
		seta_right.css('width',width+'px').css('height',height+'px');
		seta_left.find('svg').css('width',width+'px').css('height',height+'px');
		seta_right.find('svg').css('width',width+'px').css('height',height+'px');
		
		seta_cont_left.css('width',(width+2*margem_seta)+'px');
		seta_cont_right.css('width',(width+2*margem_seta)+'px');
		
		images_cont.css('left',(width+2*margem_seta)+'px');
		images_cont.css('width',(obj_width - 2*(width+2*margem_seta))+'px');
		
		var menu_height = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-menu').outerHeight(true);
		
		$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').height(obj_height - menu_height);
	}
	
	function albumfotos_widget_album_add(p){
		if(!p)p = {};
		
		var id_func = 'albuns-fotos-images';
		var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
		var obj_id = $(obj).myAttr('id');
		
		p.albumfotos_legenda = ($('.b2make-album-fotos-nome[data-album-fotos-id="'+p.albumfotos_id+'"]').hasAttr('data-album-fotos-legenda') ? $('.b2make-album-fotos-nome[data-album-fotos-id="'+p.albumfotos_id+'"]').myAttr('data-album-fotos-legenda') : null);
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : p.albumfotos_id
			},
			beforeSend: function(){
				$.carregamento_open();
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					var layout_tipo = $(obj).myAttr('data-layout-tipo');
					
					switch(layout_tipo){
						case 'menu':
							if($(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').length == 0){
								$(obj).find('.b2make-widget-out').html('<div class="b2make-albumfotos-widget-holder-2"></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').append('<div class="b2make-albumfotos-widget-menu"></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').append('<div class="b2make-albumfotos-widget-content"></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').append('<div class="b2make-albumfotos-widget-left-arrow-cont"><div class="b2make-albumfotos-widget-left-arrow"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-conteiner-banners-arrow-2.svg"></div></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').append('<div class="b2make-albumfotos-widget-images-cont"></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').append('<div class="b2make-albumfotos-widget-image-2"></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').append('<div class="b2make-albumfotos-widget-images-descricao"></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').append('<div class="b2make-albumfotos-widget-images-mini"></div>');
								$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').append('<div class="b2make-albumfotos-widget-right-arrow-cont"><div class="b2make-albumfotos-widget-right-arrow"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-conteiner-banners-arrow.svg"></div></div>');
								
								jQuery('img.svg').each(function(){
									var $img = jQuery(this);
									var imgID = $img.myAttr('id');
									var imgClass = $img.myAttr('class');
									var imgStyle = $img.myAttr('style');
									var imgURL = $img.myAttr('src');

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
										var cor = pai.myAttr('data-seta-color-ahex');
										
										if(!cor){
											cor = '1B4174ff';
										}
										
										var cor_rgba = $.jPicker.ColorMethods.hexToRgba(cor);
										
										$svg.find('path').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
										$svg.find('rect').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
										$svg.find('polyline').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
										
										albumfotos_widget_caixa_posicao_atualizar({});
									}, 'xml');
								});
							}
							
							var selected = false;
							
							if($(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry').length == 0){
								selected = true;
							}
							
							$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-menu').append('<div class="b2make-albumfotos-widget-menu-entry"'+(selected ? ' data-status="selected"':'')+' data-album-fotos-id="'+p.albumfotos_id+'">'+p.albumfotos_nome+'</div>');
							
							var imagem_cont = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-image-2');
							var imagem_descricao_cont = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-descricao');
							var imagem_mini_cont = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini');
							
							switch(dados.status){
								case 'Ok':
									if(dados.images.length == 0){
										if(selected){
											var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
											imagem_cont.css('background-image','url('+imagem+')');
											imagem_cont.myAttr('data-imagem',imagem);
											imagem_descricao_cont.hide();
										}
									} else {
										if(selected){
											//var indice = Math.floor(Math.random() * ((dados.images.length - 1) + 1));
											var indice = 0;
											var imagem = dados.images[indice].imagem;
											var id = dados.images[indice].id;
											var descricao = dados.images[indice].descricao;
											
											imagem_cont.css('background-image','url('+imagem+')');
											imagem_cont.myAttr('data-imagem',imagem);
											imagem_descricao_cont.myAttr('data-id',id);
											imagem_descricao_cont.html(descricao);
											imagem_descricao_cont.show();
										}
										
										for(var i=0;i<dados.images.length;i++){
											imagem_mini_cont.append('<div class="b2make-albumfotos-widget-image-mini" data-id="'+dados.images[i].id+'" data-imagem="'+dados.images[i].imagem+'" data-descricao="'+dados.images[i].descricao+'" data-album-fotos-id="'+p.albumfotos_id+'" style="background-image:url('+dados.images[i].mini+');"></div>');
										}
									}
									
									atualizar = true;
								break;
								case 'NaoExisteId':
									if(selected){
										var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
										imagem_cont.css('background-image','url('+imagem+')');
										imagem_cont.myAttr('data-imagem',imagem);
										imagem_descricao_cont.hide();
									}
									
									atualizar = true;
								break;
								default:
									console.log('ERROR - '+id_func+' - '+dados.status);
								
							}
						break;
						default:
							var titulo_nao_mostrar = ($(obj).hasAttr('data-nao-mostrar-titulo') ? true : false);
							if($(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').length == 0)$(obj).find('div.b2make-widget-out').html('<div class="b2make-albumfotos-widget-holder"></div>');
							$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').find('div.b2make-albumfotos-widget-image[id="b2make-albumfotos-widget-imagem-'+p.albumfotos_id+'"]').remove();
							
							var atualizar = false;
							
							switch(dados.status){
								case 'Ok':
									if(dados.images.length == 0){
										var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
										$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').prepend($('<div id="b2make-albumfotos-widget-imagem-'+p.albumfotos_id+'" class="b2make-albumfotos-widget-image" data-album-fotos-id="'+p.albumfotos_id+'"'+( p.albumfotos_legenda ? ' data-album-fotos-legenda="'+p.albumfotos_legenda+'"' : '')+' style="background-image:url('+imagem+');" data-album-fotos-imagem-width="159" data-album-fotos-imagem-height="159">'+(titulo_nao_mostrar ? '':'<div class="b2make-albumfotos-widget-titulo">'+p.albumfotos_nome+'</div>')+'</div>'));
									} else {
										var imagens = '';
										var indice = Math.floor(Math.random() * ((dados.images.length - 1) + 1));
										var imagem = dados.images[indice].imagem;
										var id = dados.images[indice].id;
										var width = dados.images[indice].width;
										var height = dados.images[indice].height;
										
										for(var i=0;i<dados.images.length;i++){
											imagens = imagens + (imagens.length > 0 ? ',' : '') + dados.images[i].imagem;
										}
										
										$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').prepend($('<div id="b2make-albumfotos-widget-imagem-'+p.albumfotos_id+'" class="b2make-albumfotos-widget-image" data-album-fotos-id="'+p.albumfotos_id+'"'+( p.albumfotos_legenda ? ' data-album-fotos-legenda="'+p.albumfotos_legenda+'"' : '')+' data-album-fotos-imagem-id="'+id+'" data-album-fotos-imagem-width="'+width+'" data-album-fotos-imagem-height="'+height+'" data-imagens-urls="'+imagens+'" style="background-image:url('+imagem+');">'+(titulo_nao_mostrar ? '':'<div class="b2make-albumfotos-widget-titulo">'+p.albumfotos_nome+'</div>')+'</div>'));
									}
									
									atualizar = true;
								break;
								case 'NaoExisteId':
									var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
									$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').prepend($('<div id="b2make-albumfotos-widget-imagem-'+p.albumfotos_id+'" class="b2make-albumfotos-widget-image" data-album-fotos-id="'+p.albumfotos_id+'"'+( p.albumfotos_legenda ? ' data-album-fotos-legenda="'+p.albumfotos_legenda+'"' : '')+' style="background-image:url('+imagem+');" data-album-fotos-imagem-width="159" data-album-fotos-imagem-height="159">'+(titulo_nao_mostrar ? '':'<div class="b2make-albumfotos-widget-titulo">'+p.albumfotos_nome+'</div>')+'</div>'));
									
									atualizar = true;
								break;
								default:
									console.log('ERROR - '+id_func+' - '+dados.status);
								
							}
							
							if(atualizar){
								var album_fotos_cont = $('#b2make-albumfotos-widget-imagem-'+p.albumfotos_id+'');
								
								if($(obj).myAttr('data-text-color-ahex')){
									var color = $.jpicker_ahex_2_rgba($(obj).myAttr('data-text-color-ahex'));
									album_fotos_cont.find('.b2make-albumfotos-widget-titulo').css('color',color);
								}
								
								var tamanho = 159;
								if($(obj).myAttr('data-tamanho-imagem')){
									tamanho = parseInt($(obj).myAttr('data-tamanho-imagem'));
								}
								
								album_fotos_cont.css('width',tamanho+'px');
								
								var target = album_fotos_cont.find('.b2make-albumfotos-widget-titulo');
								
								if($(obj).myAttr('data-font-family')){cssVar = 'fontFamily'; target.css(cssVar,$(obj).myAttr('data-font-family'));}
								if($(obj).myAttr('data-font-size')){
									cssVar = 'fontSize'; 
									target.css(cssVar,$(obj).myAttr('data-font-size')+'px');
									var size = parseInt($(obj).myAttr('data-font-size'));
									target.css('line-height',size+'px');
									//target.css('bottom','-'+(b2make.albumfotos.margin_title+size+20)+'px');
									album_fotos_cont.css('margin','18px 18px '+(2*size+b2make.albumfotos.margin_title)+'px 18px');
									album_fotos_cont.css('height',(2*size+b2make.albumfotos.margin_image+tamanho)+'px');
								} else {
									album_fotos_cont.css('height',tamanho+'px');
								}
								
								album_fotos_cont.css('background-size','auto '+tamanho+'px');
								
								if(album_fotos_cont.myAttr('data-album-fotos-imagem-height')){
									var imagem_width = parseInt(album_fotos_cont.myAttr('data-album-fotos-imagem-width'));
									var imagem_height = parseInt(album_fotos_cont.myAttr('data-album-fotos-imagem-height'));
									
									//var altura = Math.floor((conteiner_width * imagem_height) / imagem_width);
									var altura = tamanho;
									
									target.css('top',(b2make.albumfotos.margin_title+altura)+'px');
								}
								
								if($(obj).myAttr('data-font-align')){cssVar = 'textAlign'; target.css(cssVar,$(obj).myAttr('data-font-align'));}
								if($(obj).myAttr('data-font-italico')){cssVar = 'fontStyle'; target.css(cssVar,($(obj).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal'));}
								if($(obj).myAttr('data-font-negrito')){cssVar = 'fontWeight'; target.css(cssVar,($(obj).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal'));}
								
							}
							
							$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').find('div.b2make-albumfotos-widget-image').each(function(){
								var albuns_ids = b2make.album_fotos_todos_ids;
								var found = false;
								
								if(albuns_ids)
								for(i=0;i<albuns_ids.length;i++){
									if($(this).myAttr('data-album-fotos-id') == albuns_ids[i]){
										found = true;
										break;
									}
								}
								
								if(!found){
									$(this).remove();
								}
							});
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
	
	function albumfotos_widget_album_update(p = {}){
		var obj = (p.obj?p.obj:b2make.conteiner_child_obj);
		var layout_tipo = $(obj).myAttr('data-layout-tipo');
		
		$(obj).find('.b2make-widget-out').html('');
		albumfotos_widget_albuns({obj:obj});
	}
	
	function player_widget_add(){
		var widget_type = 'player';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-player-add-text"></div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			cont.myAttr('data-bordas-todas','1;solid;rgb(149, 148, 153);0;959499ff');
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.player_widget.top);
			cont.css('left',b2make.player_widget.left);
			cont.css('width',b2make.player_widget.width+'px');
			cont.css('height',b2make.player_widget.height+'px');
			cont.css('font-size',b2make.player_widget.fontSize);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			
			if(b2make.player_musicas_atual){
				player_widget_create({player_id:b2make.player_musicas_atual});
			}
			
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function player_widget_create(p){
		if(!p)p = {};
		
		var id_func = 'player-musicas-mp3s';
		var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
		var obj_id = $(obj).myAttr('id');
		
		$(obj).myAttr('data-player-musicas-id',p.player_id);
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : p.player_id
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
							if(dados.mp3s.length == 0){
								$(obj).find('div.b2make-widget-out').html('<div class="b2make-galeria-add-text">'+b2make.msgs.playerTextImagesEmpty+'</div>');
								$(obj).css('backgroundColor',b2make.player_widget.backgroundColor);
								$(obj).myAttr('data-music-list',null);
							} else {
								$(obj).find('div.b2make-widget-out').html('<div id="b2make-jplayer-'+obj_id+'"></div><div id="b2make-player-control-'+obj_id+'" class="b2make-player-control"><div class="b2make-player-controls"><div class="link_hover b2make-player-prev"></div><div class="link_hover b2make-player-play"></div><div class="link_hover b2make-player-next"></div><marquee behavior="scroll" direction="left" scrollamount="2" class="b2make-player-tit"></marquee><div class="b2make-player-time"></div></div></div>');
								var musicas = '';
								for(var i=0;i<dados.mp3s.length;i++){
									musicas = musicas + (musicas.length > 0 ? '<;>' : '') + dados.mp3s[i].nome_original + '<,>' + dados.mp3s[i].mp3;
								}
								
								$(obj).myAttr('data-music-list',musicas);
								$(obj).css('backgroundColor','transparent');
								
								player_widget_controls(b2make.conteiner_child_obj,'player');
								
								var svg = '<img style="height:17px;width:auto;position:absolute;top:-4px;left:-3px;" class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-icones-player.svg">';
								$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-prev').append(svg);
								var svg = '<div><img style="height:17px;width:auto;position:absolute;top:-2px;left:-21px;" class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-icones-player.svg"><div>';
								$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-play').append(svg);
								var svg = '<img style="height:17px;width:auto;position:absolute;top:-4px;left:-52px;" class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-icones-player.svg">';
								$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-next').append(svg);
								
								jQuery('img.svg').each(function(){
									var $img = jQuery(this);
									var imgID = $img.myAttr('id');
									var imgClass = $img.myAttr('class');
									var imgStyle = $img.myAttr('style');
									var imgURL = $img.myAttr('src');

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
										
										if($(obj).myAttr('data-botoes-color-1-ahex')){
											var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-botoes-color-1-ahex'));
											$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('polygon').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('rect').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('polygon').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('rect').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('polygon').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('rect').css('fill',bg);
										}

									}, 'xml');

								});
								
								if($(obj).myAttr('data-color-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-color-ahex'));
									$(obj).css('background-color',bg);
								}
								
								if($(obj).myAttr('data-text-color-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-text-color-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-tit').css('color',bg);
									$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-time').css('color',bg);
								}
								
								if($(obj).myAttr('data-font-family')){
									var target;
									var target2;
									var cssVar = '';
									var e_type = 'changeFontFamily';
									
									target = $(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-tit');
									target2 = $(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-time');
									
									switch(e_type){
										case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(obj).myAttr('data-font-family')); target2.css(cssVar,$(obj).myAttr('data-font-family')); break;
									}
								}
							}
						break;
						case 'NaoExisteId':
							$(obj).find('div.b2make-widget-out').html('<div class="b2make-galeria-add-text">'+b2make.msgs.playerTextImagesEmpty+'</div>');
							$(obj).css('backgroundColor',b2make.player_widget.backgroundColor);
							$(obj).myAttr('data-music-list',null);
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
		
		var site_raiz = variaveis_js.site_raiz;
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
			swfPath: site_raiz+b2make.path+"/jplayer",
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
				var color_playing_start = (pai.myAttr('data-lista-color-1-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
				var color_not_start = (pai.myAttr('data-lista-color-2-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
				
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
						var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.myAttr('data-lista-color-2-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
						
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
						var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.myAttr('data-lista-color-2-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
						
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
						var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.myAttr('data-lista-color-2-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
						
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
					$(document.body).on('mouseup tap','#'+parent_id+' .b2make-albummusicas-widget-list-mp3s .b2make-albummusicas-widget-mp3',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						b2make.player[player_id].num_musica = parseInt($(this).myAttr('data-musica-num'));
						
						var color_playing = (pai.myAttr('data-lista-color-1-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.myAttr('data-lista-color-2-ahex') ? $.jpicker_ahex_2_rgba(pai.myAttr('data-lista-color-2-ahex')) : '#000000');
						
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
	
	function albummusicas_widget_add(){
		var widget_type = 'albummusicas';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-albummusicas-widget-holder"></div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.albummusicas_widget.top);
			cont.css('left',b2make.albummusicas_widget.left);
			cont.css('width',b2make.albummusicas_widget.width+'px');
			cont.css('height',b2make.albummusicas_widget.height+'px');
			cont.css('font-size',b2make.albummusicas_widget.fontSize);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			
			albummusicas_widget_albuns();
			
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function albummusicas_widget_albuns(){
		$('.b2make-album-musicas-lista-album').each(function(){
			var id = $(this).find('.b2make-album-musicas-show').myAttr('data-album-musicas-id');
			var nome = $(this).find('.b2make-album-musicas-nome').html();
			albummusicas_widget_album_add({
				albummusicas_id : id,
				albummusicas_nome : nome
			});
		});
	}
	
	function albummusicas_widget_album_add(p){
		if(!p)p = {};
		
		var id_func = 'albuns-musicas-mp3s';
		var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
		var obj_id = $(obj).myAttr('id');
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : p.albummusicas_id
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					if($(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').length == 0)$(obj).find('div.b2make-widget-out').html('<div class="b2make-albummusicas-widget-holder"></div>');
					$(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').find('div.b2make-albummusicas-widget-album[id="b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id+'"]').remove();
					
					switch(dados.status){
						case 'Ok':
							if(dados.mp3s.length == 0){
								$(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').prepend($('<div id="b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id+'" class="b2make-albummusicas-widget-album" data-album-musicas-id="'+p.albummusicas_id+'"><div class="b2make-albummusicas-widget-titulo">'+p.albummusicas_nome+'</div><div class="b2make-albummusicas-widget-controls"></div><div class="b2make-albummusicas-widget-sem-mp3">'+b2make.msgs.albumMusicasSemMp3+'</div></div>'));
							} else {
								var mp3s = '';
								var list_mp3 = '';
								
								for(var i=0;i<dados.mp3s.length;i++){
									mp3s = mp3s + (mp3s.length > 0 ? '<;>' : '') + (i < 9 ? '0' : '') + (i+1) + ' - ' + dados.mp3s[i].nome_original + '<,>' + dados.mp3s[i].mp3;
									list_mp3 = list_mp3 + '<div class="b2make-albummusicas-widget-mp3'+(i == 0 ? ' b2make-albummusicas-widget-playing' : '')+'" data-album-musicas-id="'+p.albummusicas_id+'" data-musica-num="'+i+'">'+ (i < 9 ? '0' : '') + (i+1) + ' - ' +dados.mp3s[i].nome_original+'</div>';
								}
								
								$(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').prepend($('<div id="b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id+'" class="b2make-albummusicas-widget-album" data-album-musicas-id="'+p.albummusicas_id+'" data-music-list="'+mp3s+'"><div class="b2make-albummusicas-widget-titulo">'+p.albummusicas_nome+'</div><div id="b2make-jplayer-player-'+obj_id+'-'+p.albummusicas_id+'" class="b2make-albummusicas-widget-player"></div><div id="b2make-player-control-'+obj_id+'-'+p.albummusicas_id+'" class="b2make-albummusicas-widget-controls b2make-player-controls"><div class="link_hover b2make-player-prev"></div><div class="link_hover b2make-player-play"></div><div class="link_hover b2make-player-next"></div><marquee behavior="scroll" direction="left" scrollamount="2" class="b2make-player-tit"></marquee><div class="b2make-player-time"></div></div><div class="b2make-albummusicas-widget-list-mp3s">'+list_mp3+'</div></div>'));
								
								var album_widget = $(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').find('#b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id);
								player_widget_controls(album_widget,'albummusicas');
								
								var svg = '<img style="height:30px;width:auto;position:absolute;top:-8px;left:-5px;" class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-icones-album-musicas.svg">';
								$(obj).find('.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').find('#b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id).find('.b2make-player-controls').find('.b2make-player-prev').append(svg);
								var svg = '<div><img style="height:30px;width:auto;position:absolute;top:-1px;left:-28px;" class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-icones-album-musicas.svg"><div>';
								$(obj).find('.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').find('#b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id).find('.b2make-player-controls').find('.b2make-player-play').append(svg);
								var svg = '<img style="height:30px;width:auto;position:absolute;top:-8px;left:-90px;" class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-icones-album-musicas.svg">';
								$(obj).find('.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').find('#b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id).find('.b2make-player-controls').find('.b2make-player-next').append(svg);
								
								jQuery('img.svg').each(function(){
									var $img = jQuery(this);
									var imgID = $img.myAttr('id');
									var imgClass = $img.myAttr('class');
									var imgStyle = $img.myAttr('style');
									var imgURL = $img.myAttr('src');

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
										
										if($(obj).myAttr('data-botoes-color-1-ahex')){
											var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-botoes-color-1-ahex'));
											$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('polygon').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('rect').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('path').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('rect').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('polygon').css('fill',bg);
											$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('rect').css('fill',bg);
										}

									}, 'xml');

								});
								
								if($(obj).myAttr('data-color-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-color-ahex'));
									$(obj).css('background-color',bg);
								}
								
								if($(obj).myAttr('data-preenchimento-color-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-preenchimento-color-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').css('background-color',bg);
								}
								
								if($(obj).myAttr('data-faixas-color-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-faixas-color-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').css('background-color',bg);
								}
								
								if($(obj).myAttr('data-titulo-color-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-color-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-titulo').css('color',bg);
								}
								
								if($(obj).myAttr('data-player-color-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-player-color-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-tit').css('color',bg);
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-time').css('color',bg);
								}
								
								if($(obj).myAttr('data-lista-color-1-ahex')){
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-lista-color-1-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-playing').css('color',bg);
								}
								
								if($(obj).myAttr('data-lista-color-2-ahex')){
									var color_playing = ($(obj).myAttr('data-lista-color-1-ahex') ? $.jpicker_ahex_2_rgba($(obj).myAttr('data-lista-color-1-ahex')) : '#A1BC31');
									var bg = $.jpicker_ahex_2_rgba($(obj).myAttr('data-lista-color-2-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-mp3').css('color',bg);
									$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-playing').css('color',color_playing);
								}
								
								var ids_fonts = new Array('b2make-woam-titulo-cont','b2make-woam-player-cont','b2make-woam-lista-cont');
								
								for(var i=0;i<ids_fonts.length;i++){
									var target = false;
									var target2 = false;
									var cssVar = '';
									var id = ids_fonts[i];
									var type = '';
									var e_type = 'changeFontFamily';
									
									type = id.replace(/b2make-woam-/gi,'');
									type = type.replace(/-cont/gi,'');
									
									switch(id){
										case 'b2make-woam-titulo-cont':
											target = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-titulo');
										break;
										case 'b2make-woam-player-cont':
											target = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-tit');
											target2 = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-time');
										break;
										case 'b2make-woam-lista-cont':
											target = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-mp3');
										break;
										
									}
									
									switch(e_type){
										case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(obj).myAttr('data-'+type+'-font-family')); if(target2)target2.css(cssVar,$(obj).myAttr('data-'+type+'-font-family')); break;
									}
								}
							}
						break;
						case 'NaoExisteId':
							$(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').prepend($('<div id="b2make-albummusicas-widget-mp3-'+obj_id+'-'+p.albummusicas_id+'" class="b2make-albummusicas-widget-album" data-album-musicas-id="'+p.albummusicas_id+'"><div class="b2make-albummusicas-widget-titulo">'+p.albummusicas_nome+'</div><div class="b2make-albummusicas-widget-controls"></div><div class="b2make-albummusicas-widget-sem-mp3">'+b2make.msgs.albumMusicasSemMp3+'</div></div>'));
						break;
						default:
							console.log('ERROR - '+id_func+' - '+dados.status);
						
					}
					
					$(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').find('div.b2make-albummusicas-widget-album').each(function(){
						var albuns_ids = b2make.album_musicas_todos_ids;
						var found = false;
						
						if(albuns_ids)
						for(i=0;i<albuns_ids.length;i++){
							if($(this).myAttr('data-album-musicas-id') == albuns_ids[i]){
								found = true;
								break;
							}
						}
						
						if(!found){
							$(this).remove();
						}
					});
				} else {
					console.log('ERROR - '+id_func+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function redessociais_widget_add(){
		var widget_type = 'redessociais';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-redessociais-widget-holder"></div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			cont.myAttr('data-enderecos','facebook,https://www.facebook.com/b2make;twitter,https://twitter.com/b2_make;instagram,https://instagram.com/b2make/;email,mailto:b2make@b2make.com');
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.redessociais_widget.top);
			cont.css('left',b2make.redessociais_widget.left);
			cont.css('width',b2make.redessociais_widget.width+'px');
			cont.css('height',b2make.redessociais_widget.height+'px');
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			
			redessociais_widget_update();
			
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function redessociais_widget_update(){
		var obj = b2make.conteiner_child_obj;
		var margin = $('#b2make-wors-margin').val();
		var tamanho = $('#b2make-wors-tamanho').val();
		var holder = $(obj).find('.b2make-widget-out').find('.b2make-redessociais-widget-holder');
		
		holder.html('');
		
		var cores = $(obj).myAttr('data-cores-ahex');
		var cores_arr = new Array();
		
		if(cores)
			cores_arr = cores.split(';');
		
		var enderecos = $(obj).myAttr('data-enderecos');
		var enderecos_arr = new Array();
		
		if(enderecos)
			enderecos_arr = enderecos.split(';');
		
		var images = $(obj).myAttr('data-images');
		var images_arr = new Array();
		
		if(images)
			images_arr = images.split(';');
		
		for(var i=0;i<b2make.redessociais.length;i++){
			var title = b2make.redessociais[i].title;
			var id = b2make.redessociais[i].id;
			var pos = b2make.redessociais[i].pos;
			var cor = '000000ff';
			var endereco = '';
			var image_url = '';
			var image_width = '';
			var image_height = '';
			var found = false;
			
			for(var j=0;j<cores_arr.length;j++){
				var cor_arr = cores_arr[j].split(',');
				if(cor_arr[0] == id){
					cor = cor_arr[1];
					break;
				}
			}
			
			for(var j=0;j<enderecos_arr.length;j++){
				var endereco_arr = enderecos_arr[j].split(',');
				if(endereco_arr[0] == id){
					endereco = endereco_arr[1];
					break;
				}
			}
			
			for(var j=0;j<images_arr.length;j++){
				var image_arr = images_arr[j].split(',');
				if(image_arr[0] == id){
					image_url = image_arr[1];
					image_width = image_arr[2];
					image_height = image_arr[3];
					break;
				}
			}
			
			if(endereco){
				if(image_url){
					var div_rede = $('<a target="_blank" class="b2make-redessociais-rede b2make-tooltip" href="'+endereco+'" style="width:'+tamanho+'px;height:'+tamanho+'px;margin-bottom:'+margin+'px;margin-right:'+margin+'px;background-image: '+image_url+';'+(parseInt(image_width) < parseInt(image_height) ? 'background-size:100% auto;' : 'background-size:auto 100%;')+'"></a>');
				} else {
					var div_rede = $('<a target="_blank" class="b2make-redessociais-rede b2make-tooltip" href="'+endereco+'" style="width:'+tamanho+'px;height:'+tamanho+'px;margin-bottom:'+margin+'px;margin-right:'+margin+'px;" data-color="'+cor+'"><div><img style="height:'+tamanho+'px;width:auto;position:absolute;left:'+Math.floor(tamanho*pos)+'px;" class="svg social-link b2make-redessociais-options-snapshot-img" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-icones-sociais.svg"></div></a>');
				}
				
				holder.append(div_rede);
			}
		}
		
		jQuery('img.svg').each(function(){
			var $img = jQuery(this);
			var imgID = $img.myAttr('id');
			var imgClass = $img.myAttr('class');
			var imgStyle = $img.myAttr('style');
			var imgURL = $img.myAttr('src');

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
				
				var pai = $svg.parent().parent();
				var cor = pai.attr('data-color');
				
				if(cor){
					var cor_rgba = $.jPicker.ColorMethods.hexToRgba(cor);
					
					$svg.find('path').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
					$svg.find('rect').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
				}

			}, 'xml');

		});
	}
	
	function agenda_widget_add(){
		var widget_type = 'agenda';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-agenda-add-text">'+b2make.msgs.agendaTextAdd+'</div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.agenda.top);
			cont.css('left',b2make.agenda.left);
			cont.css('backgroundColor',b2make.agenda.backgroundColor);
			cont.css('width',b2make.agenda.width+'px');
			cont.css('height',b2make.agenda.height+'px');
			cont.css('font-size',b2make.agenda.fontSize);
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
			agenda_widget_create({agenda_id:b2make.agenda_atual});
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function agenda_widget_create(p){
		if(!p)p = {};
		
		var id_func = 'agenda-eventos';
		var obj = (p.conteiner_child_obj ? p.conteiner_child_obj : b2make.conteiner_child_obj);
		var obj_id = $(obj).myAttr('id');
		
		$(obj).myAttr('data-agenda-id',p.agenda_id);
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : p.agenda_id
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
							if(dados.eventos.length == 0){
								$(obj).find('div.b2make-widget-out').html('<div class="b2make-galeria-add-text">'+b2make.msgs.agendaTextImagesEmpty+'</div>');
								$(obj).css('backgroundColor',b2make.agenda.backgroundColor);
							} else {
								data_mes = new Array(); data_mes['01'] = 'JAN';  data_mes['02'] = 'FEV';  data_mes['03'] = 'MAR';  data_mes['04'] = 'ABR';  data_mes['05'] = 'MAI';  data_mes['06'] = 'JUN';  data_mes['07'] = 'JUL';  data_mes['08'] = 'AGO';  data_mes['09'] = 'SET';  data_mes['10'] = 'OUT';  data_mes['11'] = 'NOV';  data_mes['12'] = 'DEZ'; 
								
								$(obj).find('div.b2make-widget-out').html('<div class="b2make-wsoae-prev"><div class="b2make-wsoae-table"><div class="b2make-wsoae-cel"> << </div></div></div><div class="b2make-eventos-widget-holder"></div><div class="b2make-wsoae-next"><div class="b2make-wsoae-table"><div class="b2make-wsoae-cel"> >> </div></div></div>');
								
								var ordem = ($(obj).myAttr('data-ordem-eventos') ? $(obj).myAttr('data-ordem-eventos') : 'c');

								for(var i=0;i<dados.eventos.length;i++){
									if(dados.eventos[i].status == 'A'){
										var data_arr = dados.eventos[i].data.split('/');
										var data_txt = data_arr[0];
										var data_mes_txt = data_mes[data_arr[1]];
										if(ordem == 'c'){
											$(obj).find('div.b2make-widget-out').find('div.b2make-eventos-widget-holder').append($('<div data-id="'+dados.eventos[i].id+'" class="b2make-widget-eventos"><div class="b2make-widget-eventos-data" data-date="'+dados.eventos[i].data+'">'+data_txt+'</div><div class="b2make-widget-eventos-mes">'+data_mes_txt+'</div><div class="b2make-widget-eventos-hora">'+dados.eventos[i].hora+'</div><div class="b2make-widget-eventos-titulo">'+dados.eventos[i].nome_original+'</div><div class="b2make-widget-eventos-descricao">'+dados.eventos[i].descricao+'</div></div>'));
										} else {
											$(obj).find('div.b2make-widget-out').find('div.b2make-eventos-widget-holder').prepend($('<div data-id="'+dados.eventos[i].id+'" class="b2make-widget-eventos"><div class="b2make-widget-eventos-data" data-date="'+dados.eventos[i].data+'">'+data_txt+'</div><div class="b2make-widget-eventos-mes">'+data_mes_txt+'</div><div class="b2make-widget-eventos-hora">'+dados.eventos[i].hora+'</div><div class="b2make-widget-eventos-titulo">'+dados.eventos[i].nome_original+'</div><div class="b2make-widget-eventos-descricao">'+dados.eventos[i].descricao+'</div></div>'));
										}
									}
								}
								
								var excluir_eventos = $(obj).myAttr('data-excluir-eventos');
								
								if(!excluir_eventos) excluir_eventos = 'n';
								
								if(excluir_eventos == 's')
								$(obj).each(function(){
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
								
								if($(obj).myAttr('data-caixa-cor-ahex')){
									var val = $.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-cor-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-wsoae-prev').css('background-color',val);
									$(obj).find('.b2make-widget-out').find('.b2make-wsoae-next').css('background-color',val);
									$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').css('background-color',val);
								}
								
								if($(obj).myAttr('b2make-woa-seta-cor-1-val')){
									var val = $.jpicker_ahex_2_rgba($(obj).myAttr('b2make-woa-seta-cor-1-val'));
									$(obj).find('.b2make-widget-out').find('.b2make-wsoae-prev').find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',val);
									$(obj).find('.b2make-widget-out').find('.b2make-wsoae-next').find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',val);
								}
								
								if($(obj).myAttr('data-dia-cor-ahex')){
									var val = $.jpicker_ahex_2_rgba($(obj).myAttr('data-dia-cor-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-data').css('color',val);
								}
								
								if($(obj).myAttr('data-mes-cor-ahex')){
									var val = $.jpicker_ahex_2_rgba($(obj).myAttr('data-mes-cor-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-mes').css('color',val);
								}
								
								if($(obj).myAttr('data-titulo-cor-ahex')){
									var val = $.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-cor-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-titulo').css('color',val);
								}
								
								if($(obj).myAttr('data-cidade-cor-ahex')){
									var val = $.jpicker_ahex_2_rgba($(obj).myAttr('data-cidade-cor-ahex'));
									$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-descricao').css('color',val);
								}
								
								var types = new Array('dia','mes','titulo','cidade');
								
								for(var i=0;i<types.length;i++){
									var type = types[i];
									var target;
									var cssVar;
									
									switch(type){
										case 'dia': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-data'); break;
										case 'mes': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-mes'); break;
										case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-titulo'); break;
										case 'cidade': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-descricao'); break;
									
									}
									
									if($(obj).myAttr('data-'+type+'-font-family')){cssVar = 'fontFamily'; target.css(cssVar,$(obj).myAttr('data-'+type+'-font-family'));}
									if($(obj).myAttr('data-'+type+'-font-size')){cssVar = 'fontSize'; target.css(cssVar,$(obj).myAttr('data-'+type+'-font-size')+'px'); target.css('line-height',$(obj).myAttr('data-'+type+'-font-size')+'px');}
									if($(obj).myAttr('data-'+type+'-font-align')){cssVar = 'textAlign'; target.css(cssVar,$(obj).myAttr('data-'+type+'-font-align'));}
									if($(obj).myAttr('data-'+type+'-font-italico')){cssVar = 'fontStyle'; target.css(cssVar,($(obj).myAttr('data-'+type+'-font-italico') == 'sim' ? 'italic' : 'normal'));}
									if($(obj).myAttr('data-'+type+'-font-negrito')){cssVar = 'fontWeight'; target.css(cssVar,($(obj).myAttr('data-'+type+'-font-negrito') == 'sim' ? 'bold' : 'normal'));}
								}
								
								widgets_resize();
							}
						break;
						case 'NaoExisteId':
							$(obj).find('div.b2make-widget-out').html('<div class="b2make-galeria-add-text">'+b2make.msgs.agendaTextImagesEmpty+'</div>');
							$(obj).css('backgroundColor',b2make.agenda.backgroundColor);
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
	
	function menu_widget_add(){
		var widget_type = 'menu';
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = '<div class="b2make-widget-menu" id="widget-menu-'+b2make.widgets_count+'"><div class="b2make-widget-menu-barra"></div><div class="b2make-widget-menu-barra"></div><div class="b2make-widget-menu-barra"></div></div>';
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			
			cont.css('position','absolute');
			
			cont.css('top',b2make.widget_menu.top);
			cont.css('left',b2make.widget_menu.left);
			cont.css('width',b2make.widget_menu.width+'px');
			cont.css('height',b2make.widget_menu.height+'px');
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	function menu_widget_areas_update(){
		pagina_menu_bolinhas_areas_update();
		
		var holder = $('#b2make-widget-sub-options-menu');
		
		holder.html('<div id="b2make-wsom-title">'+b2make.msgs.menuNavegacaoTitle+'</div><div id="b2make-wsom-cont"></div>');
		
		holder = holder.find('#b2make-wsom-cont');
		
		$(b2make.widget).each(function(){
			if($(this).myAttr('data-type') == 'conteiner'){
				var menu_opcoes = $('<div class="b2make-wsom-conteiner"></div>');
				var input = $('<input class="b2make-wsom-input" type="checkbox" value="'+$(this).myAttr('id')+'">');
				var lbl = $('<div class="b2make-wsom-lbl">'+($(this).myAttr('data-name') ? $(this).myAttr('data-name') : $(this).myAttr('id'))+'</div>');
				
				input.appendTo(menu_opcoes);
				lbl.appendTo(menu_opcoes);
				menu_opcoes.appendTo(holder);
			}
		});
	}
	
	function menu_widget_areas_check(input){
		var obj = b2make.conteiner_child_obj;
		var id = input.val();
		var checked = (input.prop("checked") ? true : false);
		var areas = $(obj).myAttr('data-areas');
		var areas_arr = new Array();
		var areas_saida = '';
		
		if(areas)
			areas_arr = areas.split(',');

		var found = false;
		
		for(var j=0;j<areas_arr.length;j++){
			var area = areas_arr[j];
			if(area == id){
				found = true;
				if(!checked){
					areas_saida = areas_saida + (areas_saida?',':'') + area;
				}
			} else {
				areas_saida = areas_saida + (areas_saida?',':'') + area;
			}
		}
		
		if(!found && !checked){
			areas_saida = areas_saida + (areas_saida?',':'') + id;
		}
		
		if(areas_saida)
			$(obj).myAttr('data-areas',areas_saida);
		else
			$(obj).myAttr('data-areas',null);
	}
	
	function instagram_autorizar(){
		if(!b2make.instagram_token){
			var width = '800px';
			var height = '600px';
			var top = '150px';
			var left = '150px';
			var trocar_conta;
			
			if(b2make.instagram_trocar_conta){
				trocar_conta = '?trocar_conta=sim';
			}
			
			var WREF = window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'instagram-authorization'+(trocar_conta ? trocar_conta : ''),'instagram_autorizar', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
			WREF.opener = window;
		} else {
			$.instagram_widget_update(null);
		}
	}
	
	function instagram_delay_to_change(value){
		if(!b2make.instagram_delay){
			b2make.instagram_delay = new Array();
			b2make.instagram_delay_count = 0;
		}
		
		b2make.instagram_delay_count++;
		
		var valor = b2make.instagram_delay_count;
		
		b2make.instagram_delay.push(valor);
		b2make.instagram_value = value;
		
		setTimeout(function(){
			if(b2make.instagram_delay[b2make.instagram_delay.length - 1] == valor){
				instagram_change(b2make.instagram_value);
			}
		},b2make.facebook.delay_timeout);
	}
	
	function instagram_change(value){
		if(value){
			$.instagram_widget_update({});
		}
		
		b2make.instagram_delay = false;
	}
	
	$.instagram_widget_update = function(p){
		if(!p)p={};
		
		var opcao = 'instagram_widget_update';
		
		if(b2make.instagram_trocar_conta){
			b2make.instagram_trocar_conta = false;
			
			if(p.outra_conta == 'nao'){
				$.dialogbox_open({
					msg: b2make.msgs.instagramOutraContaNao,
					height : '255px',
					width : '400px'
				});
			}
		}
		
		if(p.instagram_token){
			b2make.instagram_token = p.instagram_token;
			pagina_options_change('instagram_token',b2make.instagram_token);
		}
		
		if(!b2make.instagram_token){
			if(!b2make.instagram_trocar_conta_evitar_looping){
				b2make.instagram_trocar_conta_evitar_looping = true;
				b2make.instagram_trocar_conta = true;
				instagram_autorizar();
			} else {
				b2make.instagram_trocar_conta_evitar_looping = false;
			}
			
			return false;
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
						
						if($(obj).myAttr('data-tamanho-imagens')){
							var numero = parseInt($(obj).myAttr('data-tamanho-imagens'));
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
	
	window.instagram_widget_update = $.instagram_widget_update;
	
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
						$.instagram_widget_update({
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
	
	function addthis_html(obj){
		var buttons = new Array();
		var buttons_html = '';
		var mostrar = obj.myAttr('data-mostrar');
		
		buttons['facebook'] = '<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>';
		buttons['tweets'] = '<a class="addthis_button_tweet"></a>';
		buttons['googleplus'] = '<a class="addthis_button_google_plusone" g:plusone:size="medium"></a>';
		
		if(mostrar && mostrar != ''){
			var mostrar_arr = mostrar.split(',');
			
			for(var i=0;i<mostrar_arr.length;i++){
				buttons_html = buttons_html + buttons[mostrar_arr[i]];
			}
			
			var widget = '<div class="addthis_toolbox addthis_default_style">'+buttons_html+'</div>';
			
			return widget;
		} else {
			return '';
		}
	}
	
	function addthis_update(){
		var obj = b2make.conteiner_child_obj;
		
		var widget = addthis_html($(obj));
		
		$(obj).find('.b2make-widget-out').html(widget);
		
		if(widget)addthis_exec();
	}
	
	function addthis_exec(){
		var script = document.location.protocol+'//s7.addthis.com/js/300/addthis_widget.js?async=1&domready=1&pubid=ra-4dc8b14029ceaa85';
		
		$.getScript(script,function() {
			window.addthis.update('config', 'data_track_clickback', true);
			window.addthis.update('share', 'url', location.href);
			window.addthis.update('share', 'title', document.title);
			window.addthis.toolbox(".addthis_toolbox");
		});
	}
	
	function youtube_resize(){
		var obj = b2make.conteiner_child_obj;
		var conteiner = b2make.selecionador_objetos.conteiner;
		var width = $(conteiner).width();
		var height = $(conteiner).height();
		
		$(obj).find('div.b2make-widget-out').find('iframe').myAttr('width',width);
		$(obj).find('div.b2make-widget-out').find('iframe').myAttr('height',height);
	}
	
	function youtube_widget_update(){
		var obj = b2make.conteiner_child_obj;
		
		var url = $(obj).myAttr('data-url');
		var width = $(obj).width();
		var height = $(obj).height();
		
		if($(obj).myAttr('data-layout-tipo') == 'padrao'){
			var videoid = url.match(/(?:https?:\/{2})?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)(?:\/watch\?v=|\/)([^\s&]+)/);
			
			if(videoid != null) {
				$(obj).find('.b2make-widget-out').html('<iframe width="'+width+'" height="'+height+'" src="https://www.youtube.com/embed/'+videoid[1]+'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
			}
		} else {
			var image_url = $(obj).myAttr('data-type-image-background');
			var titulo = $(obj).myAttr('data-titulo');
			$(obj).find('.b2make-widget-out').html('<div class="b2make-youtube-cont"'+(image_url ? ' style="background-image: url('+image_url+');"' : '')+'><div class="b2make-youtube-play"></div><div class="b2make-youtube-texto"'+(titulo ? '' : ' style="display:none;"')+'>'+(titulo ? titulo : '')+'</div></div>');
			
			if($(obj).myAttr('data-tamanho')){
				$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-play').css('height',$(obj).myAttr('data-tamanho')+'px');
				$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-play').css('width',$(obj).myAttr('data-tamanho')+'px');
			}
			if($(obj).myAttr('data-caixa-altura'))$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto').css('height',$(obj).myAttr('data-caixa-altura')+'px');
			if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
			if($(obj).myAttr('data-caixa-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-text-color-ahex')));
			
		}
	}
	
	function youtube_layout_tipo(){
		var obj = b2make.conteiner_child_obj;
		
		if($(obj).myAttr('data-layout-tipo') == 'imagem'){
			$('#b2make-wso-youtube-imagem-cont').show();
		} else {
			$('#b2make-wso-youtube-imagem-cont').hide();
		}
	}
	
	function youtube(){
		$('#b2make-wso-youtube-url-val').on('keyup change',function (e) {
			e.stopPropagation();
			
			var url = this.value;
			var videoid = url.match(/(?:https?:\/{2})?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)(?:\/watch\?v=|\/)([^\s&]+)/);
			
			if(videoid != null) {
				var obj = b2make.conteiner_child_obj;
				var conteiner = b2make.selecionador_objetos.conteiner;
				var width = $(conteiner).width();
				var height = $(conteiner).height();
				
				$(obj).myAttr('data-url',url);
				$(obj).find('.b2make-widget-out').html('<iframe width="'+width+'" height="'+height+'" src="https://www.youtube.com/embed/'+videoid[1]+'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
				
			}
		});
		
		$('#b2make-wso-youtube-titulo-val').on('keyup change',function (e) {
			e.stopPropagation();
			
			var titulo = this.value;
			var obj = b2make.conteiner_child_obj;
			
			$(obj).myAttr('data-titulo',titulo);
			
			var caixa = $(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto');
			
			if(titulo){
				caixa.html(titulo);
				caixa.show();
			} else {
				caixa.hide();
			}
		});
		
		$(document.body).on('change','#b2make-wso-youtube-layout-tipo',function(){
			var obj = b2make.conteiner_child_obj;
			var value = $(this).val();
			
			$(obj).myAttr('data-layout-tipo',value);
			
			youtube_layout_tipo();
			
			youtube_widget_update({});
		});
		
		$('#b2make-wso-youtube-bg-image-picker').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			b2make.widget_sub_options_up_clicked = false;
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = 'youtube';
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open();
		});
		
		$(document.body).on('keyup','#b2make-wso-youtube-tamanho',function(e){
			var value = $(this).val();
			var id = $(this).myAttr('id');
			
			$.input_delay_to_change({
				trigger_selector:'#b2make-listener',
				trigger_event:'b2make-wso-youtube-tamanho-2-change',
				value:value
			});
		});
		
		$(document.body).on('b2make-wso-youtube-tamanho-2-change','#b2make-listener',function(e,value,p){
			if(!p) p = {};
			
			var obj = b2make.conteiner_child_obj;
			
			$(obj).myAttr('data-tamanho',value);
			$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-play').css('width',value+'px');
			$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-play').css('height',value+'px');
		});
		
		$(document.body).on('keyup','#b2make-wso-youtube-caixa-altura',function(e){
			var value = $(this).val();
			var obj = b2make.conteiner_child_obj;
			
			$(obj).myAttr('data-caixa-altura',value);
			$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto').css('height',value+'px');
		});
		
		$(document.body).on('changeColor','#b2make-wso-youtube-caixa-cor-val,#b2make-wso-youtube-caixa-texto-cor-val',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			var obj = b2make.conteiner_child_obj;
			
			switch(id){
				case 'b2make-wso-youtube-caixa-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto').css('background-color',bg);
					$(obj).myAttr('data-caixa-color-ahex',ahex);
				break;
				case 'b2make-wso-youtube-caixa-texto-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto').css('color',bg);
					$(obj).myAttr('data-caixa-text-color-ahex',ahex);
				break;
				
			}
		});
		
		$(document.body).on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito','#b2make-wso-youtube-caixa-texto-text-cont',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			var cssVar = '';
			var noSize = false;
			var nao_mudar_line_height = false;
			var id_bruto = $(this).myAttr('id');
			var mudar_height = false;
			var id = id_bruto.replace(/b2make-wso-youtube-/gi,'');
			var layout_tipo = ($(obj).myAttr('data-layout-tipo') ? $(obj).myAttr('data-layout-tipo') : 'padrao');
			
			id = id.replace(/-text-cont/gi,'');
			
			switch(id_bruto){
				case 'b2make-wso-youtube-caixa-texto-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').find('.b2make-youtube-texto'); mudar_height = true; break;
			}
			
			switch(e.type){
				case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-'+id+'-font-family',$(this).myAttr('data-font-family')); break;
				case 'changeFontSize': 
					cssVar = 'fontSize';  target.css(cssVar,$(this).myAttr('data-font-size')+'px'); if(!nao_mudar_line_height) target.css('line-height',$(this).myAttr('data-font-size')+'px'); $(obj).myAttr('data-'+id+'-font-size',$(this).myAttr('data-font-size')); 
					
					if(!nao_mudar_line_height){
						var height = b2make.contents.conteiner_height_lines*parseInt($('#b2make-wo-contents-texto-text-cont').find('.b2make-fonts-size').val()) + b2make.contents.conteiner_height_lines*parseInt($('#b2make-wo-contents-titulo-text-cont').find('.b2make-fonts-size').val());
						height = height + b2make.contents.conteiner_height_default;
						
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('height',height+'px');
						
						var line_height = parseInt($(this).myAttr('data-font-size')) + b2make.contents.conteiner_height_margin;
						target.css('line-height',line_height+'px');
					}
					
					if(mudar_height){
						target.css('max-height',(line_height*b2make.contents.conteiner_height_lines)+'px');
					}
				break;
				case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).myAttr('data-font-align'));$(obj).myAttr('data-'+id+'-font-align',$(this).myAttr('data-font-align')); break;
				case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).myAttr('data-'+id+'-font-italico',$(this).myAttr('data-font-italico')); break;
				case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).myAttr('data-'+id+'-font-negrito',$(this).myAttr('data-font-negrito')); break;
			}
		});
		
	}
	
	youtube();
	
	$.conteiner_child_open = function(p){
		var obj = b2make.conteiner_child_obj;
		var sub_menu;
		var specific;
		var ler_script;
		var title;
		
		$('#b2make-menu-abas-principais').hide();
		$('#b2make-menu-abas-widgets').show();
		$('#b2make-menu-abas').show();
		
		switch(p.widget_type){
			case 'services': ler_script = true; sub_menu = true; specific = true; title = b2make.msgs[p.widget_type+'Title']; break;
		}
		
		if($(obj).myAttr('data-ler-script')){
			if(!$(obj).myAttr('data-sub-menu-disable')) sub_menu = true;
			if(!$(obj).myAttr('data-menu-specific-disable')) specific = true;
			if(!$(obj).myAttr('data-widget-title')){
				function capitalizeFirstLetter(string) {
					return string.charAt(0).toUpperCase() + string.slice(1);
				}
				
				title = capitalizeFirstLetter(p.widget_type);
			} else {
				title = $(obj).myAttr('data-widget-title');
			}
			
			ler_script = true;
		}
		
		if(ler_script){
			if(p.widget_added){
				b2make.widget_added = true;
			}
			
			if(!b2make.dynamic_scripts_loaded){
				script_ler({id:p.widget_type});
				return;
			}
			
			if(b2make.dynamic_scripts_loaded)
			if(!b2make.dynamic_scripts_loaded[p.widget_type]){
				script_ler({id:p.widget_type});
				return;
			}
			
			if(b2make.widget_added){
				b2make.widget_added = false;
				$('#b2make-'+p.widget_type+'-callback').trigger('widget_added');
			}
			
			$('#b2make-'+p.widget_type+'-callback').trigger('conteiner_child_open');
		}
		
		$(b2make.widget_sub_options_up).hide();
		$(b2make.widget_sub_options_down).hide();
		b2make.conteiner_child_show = $(obj).myAttr('id');
		
		selecionador_objetos_open();
		menu_conteiner_aba_start();
		
		if(p.select)$(obj).select();
		
		// ===================== Opções
		
		//$('#b2make-widget-options-title').hide();
		$('#b2make-widget-options-main').hide();
		
		b2make.conteiner_child_type = p.widget_type;
		
		$('#b2make-woc-help').myAttr('data-type',p.widget_type);
		
		switch(p.widget_type){
			case 'texto': 
				title = b2make.msgs.textTitle;
				if(p.add){
					texto_for_textarea();
				}
				
				specific = true;
				if($(obj).myAttr('data-text-ahex')){
					$.jPicker.List[0].color.active.val('ahex',$(obj).myAttr('data-text-ahex'));
				} else {
					$.jPicker.List[0].color.active.val('ahex','000000ff');
				}
				
				if($(obj).myAttr('data-bg-ahex')){
					$.jPicker.List[1].color.active.val('ahex',$(obj).myAttr('data-bg-ahex'));
				} else {
					$.jPicker.List[1].color.active.val('all',null);
				}
				
				if($(obj).myAttr('data-padding')){
					$('#b2make-wot-padding').val($(obj).myAttr('data-padding'));
				} else {
					$('#b2make-wot-padding').val('0');
				}
				
				var textoFontFamily = b2make.font;
				
				if($(obj).myAttr('data-font-family')){
					textoFontFamily = $(obj).myAttr('data-font-family');
					$('#b2make-wot-fontes-holder,#b2make-wot-count-teste').css({
						'fontFamily': $(obj).myAttr('data-font-family')
					});
					$('#b2make-wot-fontes-holder').html($(obj).myAttr('data-font-family'));
				} else {
					$('#b2make-wot-fontes-holder,#b2make-wot-count-teste').css({
						'fontFamily': b2make.font
					});
					$('#b2make-wot-fontes-holder').html(b2make.font);
				}
				
				$('#b2make-wot-fontes li').each(function(){
					if(textoFontFamily == $(this).css('fontFamily')){
						$(this).addClass('b2make-wot-fonte-clicked');
					} else {
						$(this).removeClass('b2make-wot-fonte-clicked');
					}
				});
				
				if($(obj).myAttr('data-font-size')){
					$('#b2make-wot-font-size').val($(obj).myAttr('data-font-size'));
				} else {
					$('#b2make-wot-font-size').val('20');
				}
				
				if($(obj).myAttr('data-type-image-background')){
					var image_url = $(obj).myAttr('data-type-image-background');

					$('#b2make-wot-bg-image').css('background-size','25px auto');
					$('#b2make-wot-bg-image').css('backgroundImage','url('+image_url+')');
				} else {
					$(obj).css('backgroundSize','100% auto');
					$('#b2make-wot-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
					$('#b2make-wot-bg-image').css('background-size','auto auto');
				}
				
				if($(obj).myAttr('data-background-repeat')){
					var option = $('#b2make-wotbi-repeat').find("[value='" + $(obj).myAttr('data-background-repeat') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-wotbi-repeat').find(":first");
					option.prop('selected', 'selected');
				}
				
				if($(obj).myAttr('data-background-position-x')){
					var pos = $(obj).myAttr('data-background-position-x');
					var ok;
					var pos_val = 0;
					
					switch(pos){
						case 'left':
						case 'right':
						case 'center':
							ok = true;
						break;
						default: pos_val = pos; pos = 'value';
					}
					
					var option = $('#b2make-wotbi-position-x').find("[value='" + pos + "']");
					option.prop('selected', 'selected');
					
					if(ok){
						$('#b2make-wotbi-position-x-value').hide();
					} else {
						$('#b2make-wotbi-position-x-value').show();
						$('#b2make-wotbi-position-x-value').val(pos_val);
					}
				} else {
					var option = $('#b2make-wotbi-position-x').find(":first");
					option.prop('selected', 'selected');
					$('#b2make-wotbi-position-x-value').hide();
					$('#b2make-wotbi-position-x-value').val('0');
				}
				
				if($(obj).myAttr('data-background-position-y')){
					var pos = $(obj).myAttr('data-background-position-y');
					var ok;
					var pos_val = 0;
					
					switch(pos){
						case 'top':
						case 'bottom':
						case 'center':
							ok = true;
						break;
						default: pos_val = pos; pos = 'value';
					}
					
					var option = $('#b2make-wotbi-position-y').find("[value='" + pos + "']");
					option.prop('selected', 'selected');
					
					if(ok){
						$('#b2make-wotbi-position-y-value').hide();
					} else {
						$('#b2make-wotbi-position-y-value').show();
						$('#b2make-wotbi-position-y-value').val(pos_val);
					}
				} else {
					var option = $('#b2make-wotbi-position-y').find(":first");
					option.prop('selected', 'selected');
					$('#b2make-wotbi-position-y-value').hide();
					$('#b2make-wotbi-position-y-value').val('0');
				}
				
				if($(obj).myAttr('data-hiperlink')){
					$('#b2make-wot-hiperlink-value').val($(obj).myAttr('data-hiperlink'));
				} else {
					$('#b2make-wot-hiperlink-value').val('');
				}
				
				if($(obj).myAttr('data-hiperlink-target')){
					if($(obj).myAttr('data-hiperlink-target') == '_self'){
						var option = $('#b2make-wot-target-value').find(":first");
					} else {
						var option = $('#b2make-wot-target-value').find(":last");
					}
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-wot-target-value').find(":first");
					option.prop('selected', 'selected');
				}
				
			break;
			case 'facebook': 
				title = b2make.msgs.facebookTitle; 
				specific = true;
				
				if($(obj).myAttr('data-href')){
					$('#b2make-wof-href').val(decodeURIComponent($(obj).myAttr('data-href')));
				} else {
					$('#b2make-wof-href').val(decodeURIComponent(b2make.facebook.href));
				}
			break;
			case 'twitter': 
				title = b2make.msgs.twitterTitle; 
				specific = true;
				
				if($(obj).myAttr('data-user')){
					$('#b2make-wot-user').val($(obj).myAttr('data-user'));
				} else {
					$('#b2make-wot-user').val(b2make.twitter.user);
				}
			break;
			case 'soundcloud': 
				title = b2make.msgs.soundcloudTitle; 
				specific = true;
				
				if($(obj).myAttr('data-user')){
					$('#b2make-wos-user').val($(obj).myAttr('data-user'));
				} else {
					$('#b2make-wos-user').val(b2make.soundcloud.user);
				}
			break;
			case 'iframe': 
				title = b2make.msgs.iframeTitle;
				specific = true;
				
				if(p.add){
					iframe_for_textarea();
				}
			break;
			case 'galeria': 
				title = b2make.msgs.galeriaTitle;
				sub_menu = true;
				specific = true;
			break;
			case 'imagem': 
				title = b2make.msgs.imagemTitle; 
				sub_menu = true; 
				specific = true; 
				
				if($(obj).myAttr('data-hiperlink')){
					$('#b2make-woi-hiperlink-value').val($(obj).myAttr('data-hiperlink'));
				} else {
					$('#b2make-woi-hiperlink-value').val('');
				}
				
				if($(obj).myAttr('data-hiperlink-target')){
					if($(obj).myAttr('data-hiperlink-target') == '_self'){
						var option = $('#b2make-woi-target-value').find(":first");
					} else {
						var option = $('#b2make-woi-target-value').find(":last");
					}
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-woi-target-value').find(":first");
					option.prop('selected', 'selected');
				}
			break;
			case 'player': 
				title = b2make.msgs.playerTitle;
				sub_menu = true;
				specific = true;
				
				if($(obj).myAttr('data-start-automatico')){
					$('#b2make-widget-player-auto-start').myAttr('data-checked',true);
				} else {
					$('#b2make-widget-player-auto-start').myAttr('data-checked',null);
				}
				
				if($(obj).myAttr('data-player-musicas-id')){
					$('.b2make-player-musicas-show').each(function(){
						if($(obj).myAttr('data-player-musicas-id') == $(this).myAttr('data-player-musicas-id')){
							$(this).myAttr('data-status','show');
						} else {
							$(this).myAttr('data-status','not-show');
						}
					});
					
					var id = $(obj).myAttr('data-player-musicas-id');
					
					b2make.player_musicas_atual = $(obj).myAttr('data-player-musicas-id');
					b2make.player_musicas_nome = $('.b2make-player-musicas-nome[data-player-musicas-id="'+id+'"]').html();
					
					$('.b2make-player-musicas-nome').each(function(){
						$(this).myAttr('data-status','not-show');
					});
					
					$('.b2make-player-musicas-nome[data-player-musicas-id="'+id+'"]').myAttr('data-status','show');
					
					$('#b2make-player-musicas-lista-mp3s').html('');
					player_musicas_mp3s();
				} else {
					$('.b2make-player-musicas-show').each(function(){
						$(this).myAttr('data-status','not-show');
					});
				}
				
				if($(obj).myAttr('data-font-family')){
					$('#b2make-wop-fonte-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-font-family')
					});
					$('#b2make-wop-fonte-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-font-family'));
				} else {
					$('#b2make-wop-fonte-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-wop-fonte-cont').find('.b2make-fonts-holder').html(b2make.font);
				}
				
				if($(obj).myAttr('data-text-color-ahex')){
					$('#b2make-wop-fonte-cor').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-text-color-ahex')));
					$('#b2make-wop-fonte-cor').myAttr('data-ahex',$(obj).myAttr('data-text-color-ahex'));
				} else {
					$('#b2make-wop-fonte-cor').css('background-color','#333333');
					$('#b2make-wop-fonte-cor').myAttr('data-ahex','333333ff');
				}
				
				if($(obj).myAttr('data-color-ahex')){
					$('#b2make-wop-preenchimento-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-color-ahex')));
					$('#b2make-wop-preenchimento-cor-val').myAttr('data-ahex',$(obj).myAttr('data-color-ahex'));
				} else {
					$('#b2make-wop-preenchimento-cor-val').css('background-color','');
					$('#b2make-wop-preenchimento-cor-val').myAttr('data-ahex','');
				}
				
				if($(obj).myAttr('data-botoes-color-1-ahex')){
					$('#b2make-wop-botoes-cor-1-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botoes-color-1-ahex')));
					$('#b2make-wop-botoes-cor-1-val').myAttr('data-ahex',$(obj).myAttr('data-botoes-color-1-ahex'));
				} else {
					$('#b2make-wop-botoes-cor-1-val').css('background-color','#413E3F');
					$('#b2make-wop-botoes-cor-1-val').myAttr('data-ahex','413e3fff');
				}
				
				if($(obj).myAttr('data-botoes-color-2-ahex')){
					$('#b2make-wop-botoes-cor-2-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botoes-color-2-ahex')));
					$('#b2make-wop-botoes-cor-2-val').myAttr('data-ahex',$(obj).myAttr('data-botoes-color-2-ahex'));
				} else {
					$('#b2make-wop-botoes-cor-2-val').css('background-color','#726B6D');
					$('#b2make-wop-botoes-cor-2-val').myAttr('data-ahex','726b6dff');
				}
			break;
			case 'agenda': 
				title = b2make.msgs.agendaTitle;
				specific = true;
				sub_menu = true;

				if($(obj).myAttr('data-excluir-eventos')){
					var option = $('#b2make-woa-excluir').find("[value='" + $(obj).myAttr('data-excluir-eventos') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-woa-excluir').find(":first");
					option.prop('selected', 'selected');
				}

				if($(obj).myAttr('data-ordem-eventos')){
					var option = $('#b2make-woa-ordem').find("[value='" + $(obj).myAttr('data-ordem-eventos') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-woa-ordem').find(":first");
					option.prop('selected', 'selected');
				}

				if($(obj).myAttr('data-caixa-cor-ahex')){
					$('#b2make-woa-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-cor-ahex')));
					$('#b2make-woa-caixa-cor-val').myAttr('data-ahex',$(obj).myAttr('data-caixa-cor-ahex'));
				} else {
					$('#b2make-woa-caixa-cor-val').css('background-color','#ECEDEF');
					$('#b2make-woa-caixa-cor-val').myAttr('data-ahex','ecedefff');
				}

				if($(obj).myAttr('data-seta-cor-1-ahex')){
					$('#b2make-woa-seta-cor-1-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-seta-cor-1-ahex')));
					$('#b2make-woa-seta-cor-1-val').myAttr('data-ahex',$(obj).myAttr('data-seta-cor-1-ahex'));
				} else {
					$('#b2make-woa-seta-cor-1-val').css('background-color','#333333');
					$('#b2make-woa-seta-cor-1-val').myAttr('data-ahex','333333ff');
				}

				if($(obj).myAttr('data-seta-cor-2-ahex')){
					$('#b2make-woa-seta-cor-2-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-seta-cor-2-ahex')));
					$('#b2make-woa-seta-cor-2-val').myAttr('data-ahex',$(obj).myAttr('data-seta-cor-2-ahex'));
				} else {
					$('#b2make-woa-seta-cor-2-val').css('background-color','#333333');
					$('#b2make-woa-seta-cor-2-val').myAttr('data-ahex','333333ff');
				}
				
				if($(obj).myAttr('data-dia-cor-ahex')){
					$('#b2make-woa-dia-cor').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-dia-cor-ahex')));
					$('#b2make-woa-dia-cor').myAttr('data-ahex',$(obj).myAttr('data-dia-cor-ahex'));
				} else {
					$('#b2make-woa-dia-cor').css('background-color','#a1bc31');
					$('#b2make-woa-dia-cor').myAttr('data-ahex','a1bc31ff');
				}
				
				if($(obj).myAttr('data-mes-cor-ahex')){
					$('#b2make-woa-mes-cor').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-mes-cor-ahex')));
					$('#b2make-woa-mes-cor').myAttr('data-ahex',$(obj).myAttr('data-mes-cor-ahex'));
				} else {
					$('#b2make-woa-mes-cor').css('background-color','#a1bc31');
					$('#b2make-woa-mes-cor').myAttr('data-ahex','a1bc31ff');
				}
				
				if($(obj).myAttr('data-titulo-cor-ahex')){
					$('#b2make-woa-titulo-cor').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-cor-ahex')));
					$('#b2make-woa-titulo-cor').myAttr('data-ahex',$(obj).myAttr('data-titulo-cor-ahex'));
				} else {
					$('#b2make-woa-titulo-cor').css('background-color','#000000');
					$('#b2make-woa-titulo-cor').myAttr('data-ahex','000000ff');
				}
				
				if($(obj).myAttr('data-cidade-cor-ahex')){
					$('#b2make-woa-cidade-cor').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-cidade-cor-ahex')));
					$('#b2make-woa-cidade-cor').myAttr('data-ahex',$(obj).myAttr('data-cidade-cor-ahex'));
				} else {
					$('#b2make-woa-cidade-cor').css('background-color','#000000');
					$('#b2make-woa-cidade-cor').myAttr('data-ahex','000000ff');
				}
				
				var types = new Array('dia','mes','titulo','cidade');
				
				for(var i=0;i<types.length;i++){
					var type = types[i];
					var tamanho;
					
					switch(type){
						case 'dia': tamanho = 72; break;
						case 'mes': tamanho = 46; break;
						case 'titulo': tamanho = 16; break;
						case 'cidade': tamanho = 13; break;
					}
					
					if($(obj).myAttr('data-'+type+'-font-family')){
						$('#b2make-woa-'+type+'-fonts-cont').find('.b2make-fonts-holder').css({
							'fontFamily': $(obj).myAttr('data-'+type+'-font-family')
						});
						$('#b2make-woa-'+type+'-fonts-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-'+type+'-font-family'));
					} else {
						$('#b2make-woa-'+type+'-fonts-cont').find('.b2make-fonts-holder').css({
							'fontFamily': b2make.font
						});
						$('#b2make-woa-'+type+'-fonts-cont').find('.b2make-fonts-holder').html(b2make.font);
					}
					
					if($(obj).myAttr('data-'+type+'-font-size')){
						$('#b2make-woa-'+type+'-fonts-cont').find('.b2make-fonts-size').val($(obj).myAttr('data-'+type+'-font-size'));
					} else {
						$('#b2make-woa-'+type+'-fonts-cont').find('.b2make-fonts-size').val(tamanho);
					}
				}
			break;
			case 'menu': 
				title = b2make.msgs.menuTitle;
				sub_menu = true; 
				specific = true;
				
				var areas = $(obj).myAttr('data-areas');
				var areas_arr = new Array();
				var areas_saida = '';
				
				if(areas)
					areas_arr = areas.split(',');
				
				$('.b2make-wsom-conteiner').each(function(){
					var input = $(this).find('.b2make-wsom-input');
					var checked = input.prop("checked");
					var id = input.val();
					var found = false;
					
					for(var j=0;j<areas_arr.length;j++){
						var area = areas_arr[j];
						if(area == id){
							input.prop("checked",false);
							found = true;
							break;
						}
					}
					
					if(!found){
						input.prop("checked",true);
					}
				});
				
				var menu_holder = $(obj).find('.b2make-widget-out').find('.b2make-widget-menu');
				
				if(menu_holder.myAttr('data-color-ahex')){
					$('#b2make-womn-esquema-cor-1-val').css('background-color',$.jpicker_ahex_2_rgba(menu_holder.myAttr('data-color-ahex')));
					$('#b2make-womn-esquema-cor-1-val').myAttr('data-ahex',menu_holder.myAttr('data-color-ahex'));
				} else {
					$('#b2make-womn-esquema-cor-1-val').css('background-color','transparent');
					$('#b2make-womn-esquema-cor-1-val').myAttr('data-ahex','00000000');
				}
				
				if(menu_holder.find('.b2make-widget-menu-barra').myAttr('data-color-ahex')){
					$('#b2make-womn-esquema-cor-2-val').css('background-color',$.jpicker_ahex_2_rgba(menu_holder.find('.b2make-widget-menu-barra').myAttr('data-color-ahex')));
					$('#b2make-womn-esquema-cor-2-val').myAttr('data-ahex',menu_holder.find('.b2make-widget-menu-barra').myAttr('data-color-ahex'));
				} else {
					$('#b2make-womn-esquema-cor-2-val').css('background-color','#000000');
					$('#b2make-womn-esquema-cor-2-val').myAttr('data-ahex','000000ff');
				}
				
				if(menu_holder.myAttr('data-caixa-color-ahex')){
					$('#b2make-womn-esquema-cor-3-val').css('background-color',$.jpicker_ahex_2_rgba(menu_holder.myAttr('data-caixa-color-ahex')));
					$('#b2make-womn-esquema-cor-3-val').myAttr('data-ahex',menu_holder.myAttr('data-caixa-color-ahex'));
				} else {
					$('#b2make-womn-esquema-cor-3-val').css('background-color','#000000');
					$('#b2make-womn-esquema-cor-3-val').myAttr('data-ahex','000000ff');
				}
				
				if(menu_holder.myAttr('data-font-color-ahex')){
					$('#b2make-womn-esquema-cor-4-val').css('background-color',$.jpicker_ahex_2_rgba(menu_holder.myAttr('data-font-color-ahex')));
					$('#b2make-womn-esquema-cor-4-val').myAttr('data-ahex',menu_holder.myAttr('data-font-color-ahex'));
				} else {
					$('#b2make-womn-esquema-cor-4-val').css('background-color','#FFF');
					$('#b2make-womn-esquema-cor-4-val').myAttr('data-ahex','ffffffff');
				}
				
				if(menu_holder.myAttr('data-hover-color-ahex')){
					$('#b2make-womn-esquema-cor-5-val').css('background-color',$.jpicker_ahex_2_rgba(menu_holder.myAttr('data-hover-color-ahex')));
					$('#b2make-womn-esquema-cor-5-val').myAttr('data-ahex',menu_holder.myAttr('data-hover-color-ahex'));
				} else {
					$('#b2make-womn-esquema-cor-5-val').css('background-color','#999');
					$('#b2make-womn-esquema-cor-5-val').myAttr('data-ahex','999999ff');
				}
				
				if(menu_holder.myAttr('data-font-family')){
					$('#b2make-womn-fonts-cont').find('.b2make-fonts-holder').css({
						'fontFamily': menu_holder.myAttr('data-font-family')
					});
					$('#b2make-womn-fonts-cont').find('.b2make-fonts-holder').html(menu_holder.myAttr('data-font-family'));
				} else {
					$('#b2make-womn-fonts-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-womn-fonts-cont').find('.b2make-fonts-holder').html(b2make.font);
				}
				
				if(menu_holder.myAttr('data-font-size')){
					$('#b2make-womn-fonts-cont').find('.b2make-fonts-size').val(menu_holder.myAttr('data-font-size'));
				} else {
					$('#b2make-womn-fonts-cont').find('.b2make-fonts-size').val(18);
				}
				
				if(menu_holder.myAttr('data-espacamento')){
					$('#b2make-womn-espacamento-val').val(menu_holder.myAttr('data-espacamento'));
				} else {
					$('#b2make-womn-espacamento-val').val(5);
				}
				
				if(menu_holder.myAttr('data-largura')){
					$('#b2make-womn-largura-val').val(menu_holder.myAttr('data-largura'));
				} else {
					$('#b2make-womn-largura-val').val(165);
				}
				
				if(menu_holder.myAttr('data-cantos-arredondados')){
					if(menu_holder.myAttr('data-cantos-arredondados') == 's'){
						var option = $('#b2make-womn-cantos-arredondados-val').find(":first");
					} else {
						var option = $('#b2make-womn-cantos-arredondados-val').find(":last");
					}
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-womn-cantos-arredondados-val').find(":first");
					option.prop('selected', 'selected');
				}
			break;
			case 'slideshow': 
				title = b2make.msgs.slideshowTitle;
				sub_menu = true;
				specific = true;
				
				if(!b2make.slideshow_start) b2make.slideshow_start = new Array();
				
				if($(obj).myAttr('data-tempo')){
					$('#b2make-woss-tempo').val($(obj).myAttr('data-tempo'));
				} else {
					$('#b2make-woss-tempo').val('3000');
				}
				
				if($(obj).myAttr('data-direction')){
					var option = $('#b2make-woss-direction').find("[value='" + $(obj).myAttr('data-direction') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-woss-direction').find(":first");
					option.prop('selected', 'selected');
				}
				
				if($(obj).myAttr('data-animation')){
					$('#b2make-woss-start-pause').css('backgroundPosition','-20px 0px');
					b2make.slideshow_start[$(obj).myAttr('id')] = true;
				} else {
					$('#b2make-woss-start-pause').css('backgroundPosition','0px 0px');
					b2make.slideshow_start[$(obj).myAttr('id')] = false;
				}
				
				if($(obj).myAttr('data-slide-show-id')){
					$('.b2make-slide-show-show').each(function(){
						if($(obj).myAttr('data-slide-show-id') == $(this).myAttr('data-slide-show-id')){
							$(this).myAttr('data-status','show');
						} else {
							$(this).myAttr('data-status','not-show');
						}
					});
					
					$('.b2make-slide-show-show').trigger('mouseup');
					
					var id = $(obj).myAttr('data-slide-show-id');
					
					if(b2make.slide_show_todos_ids){
						var slide_ids =  b2make.slide_show_todos_ids;
						var found = false;
						
						for(var i=0;i<slide_ids.length;i++){
							if(slide_ids[i] == $(obj).myAttr('data-slide-show-id')){
								found = true;
								break;
							}
						}
						
						if(found){
							b2make.slide_show_atual = $(obj).myAttr('data-slide-show-id');
							b2make.slide_foto_nome = $('.b2make-slide-show-nome[data-slide-show-id="'+id+'"]').html();
							
							$('.b2make-slide-show-nome').each(function(){
								$(this).myAttr('data-status','not-show');
							});
							
							$('.b2make-slide-show-nome[data-slide-show-id="'+id+'"]').myAttr('data-status','show');
							
							$('#b2make-slide-show-lista-images').html('');
							slide_show_images();
						}
					}
				} else {
					$('.b2make-slide-show-show').each(function(){
						$(this).myAttr('data-status','not-show');
					});
				}
			break;
			case 'albumfotos': 
				title = b2make.msgs.albumfotosTitle;
				sub_menu = true;
				specific = true;
				
				if($(obj).myAttr('data-layout-tipo')){
					var option = $('#b2make-woaf-layout-tipo').find("[value='" + $(obj).myAttr('data-layout-tipo') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-woaf-layout-tipo').find(":first");
					option.prop('selected', 'selected');
				}
				
				$('.b2make-album-fotos-image-holder').each(function(){
					$(this).removeClass('b2make-album-fotos-image-holder-clicked');
				});
				
				if(!$(obj).myAttr('data-modelo-verificado')){
					if(b2make.album_fotos_todos_ids){
						var albuns_ids = b2make.album_fotos_todos_ids;

						for(i=0;i<albuns_ids.length;i++){
							var albuns_not_show = $(obj).myAttr('data-albuns-not-show');
							var found = false;
							var id = albuns_ids[i];
							
							$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').find('div.b2make-albumfotos-widget-image').each(function(){
								if($(this).myAttr('data-album-fotos-id') == id){
									found = true;
									return false;
								}
							});
							
							if(!found){
								if(albuns_not_show){
									$(obj).myAttr('data-albuns-not-show',albuns_not_show+','+id);
								} else {
									$(obj).myAttr('data-albuns-not-show',id);
								}
							}
						}
						
						$(obj).myAttr('data-modelo-verificado','sim');
					}
				}
				
				if($(obj).myAttr('data-albuns-not-show')){
					var albuns_not_show = $(obj).myAttr('data-albuns-not-show');
					
					$('.b2make-album-fotos-lista-album').each(function(){
						var id = $(this).find('.b2make-album-fotos-show').myAttr('data-album-fotos-id');
						var ans_arr = albuns_not_show.split(',');
						var found = false;
						
						for(var i=0;i<ans_arr.length;i++){
							if(ans_arr[i] == id){
								found = true;
								break;
							}
						}
						
						if(found){
							$(this).find('.b2make-album-fotos-show').myAttr('data-status','not-show');
						} else {
							$(this).find('.b2make-album-fotos-show').myAttr('data-status','show');
						}
					});
				} else {
					$('.b2make-album-fotos-lista-album').each(function(){
						$(this).find('.b2make-album-fotos-show').myAttr('data-status','show');
					});
				}
				
				if($(obj).myAttr('data-tamanho-imagem')){
					$('#b2make-woaf-imagem-val').val($(obj).myAttr('data-tamanho-imagem'));
				} else {
					$('#b2make-woaf-imagem-val').val(159);
				}
				
				if($(obj).myAttr('data-preenchimento-color-ahex')){
					$('#b2make-woaf-preenchimento-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-preenchimento-color-ahex')));
					$('#b2make-woaf-preenchimento-cor-val').myAttr('data-ahex',$(obj).myAttr('data-preenchimento-color-ahex'));
				} else {
					$('#b2make-woaf-preenchimento-cor-val').css('background-color','');
					$('#b2make-woaf-preenchimento-cor-val').myAttr('data-ahex','');
				}
				
				if($(obj).myAttr('data-legenda-color-ahex')){
					$('#b2make-woaf-legenda-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-legenda-color-ahex')));
					$('#b2make-woaf-legenda-cor-val').myAttr('data-ahex',$(obj).myAttr('data-legenda-color-ahex'));
				} else {
					$('#b2make-woaf-legenda-cor-val').css('background-color','');
					$('#b2make-woaf-legenda-cor-val').myAttr('data-ahex','#ededed');
				}
				
				if($(obj).myAttr('data-text-color-ahex')){
					$('#b2make-woaf-text-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-text-color-ahex')));
					$('#b2make-woaf-text-cor-val').myAttr('data-ahex',$(obj).myAttr('data-text-color-ahex'));
				} else {
					$('#b2make-woaf-text-cor-val').css('background-color','#ffffff');
					$('#b2make-woaf-text-cor-val').myAttr('data-ahex','ffffffff');
				}
				
				if($(obj).myAttr('data-font-family')){
					$('#b2make-woaf-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-font-family')
					});
					$('#b2make-woaf-text-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-font-family'));
				} else {
					$('#b2make-woaf-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-woaf-text-cont').find('.b2make-fonts-holder').html(b2make.font);
				}
				
				if($(obj).myAttr('data-font-size')){
					$('#b2make-woaf-text-cont').find('.b2make-fonts-size').val($(obj).myAttr('data-font-size'));
				} else {
					$('#b2make-woaf-text-cont').find('.b2make-fonts-size').val(17);
				}
				
				if($(obj).myAttr('data-nao-mostrar-titulo')){
					$('#b2make-woaf-mostrar-titulo-input').prop("checked",false);
				} else {
					$('#b2make-woaf-mostrar-titulo-input').prop("checked",true);
				}
			break;
			case 'albummusicas': 
				title = b2make.msgs.albummusicasTitle;
				sub_menu = true;
				specific = true;
				
				if(!$(obj).myAttr('data-modelo-verificado')){
					if(b2make.album_musicas_todos_ids){
						var albuns_ids = b2make.album_musicas_todos_ids;

						for(i=0;i<albuns_ids.length;i++){
							var albuns_not_show = $(obj).myAttr('data-albuns-not-show');
							var found = false;
							var id = albuns_ids[i];
							
							$(obj).find('div.b2make-widget-out').find('div.b2make-albummusicas-widget-holder').find('div.b2make-albummusicas-widget-album').each(function(){
								if($(this).myAttr('data-album-musicas-id') == id){
									found = true;
									return false;
								}
							});
							
							if(!found){
								if(albuns_not_show){
									$(obj).myAttr('data-albuns-not-show',albuns_not_show+','+id);
								} else {
									$(obj).myAttr('data-albuns-not-show',id);
								}
							}
						}
						
						$(obj).myAttr('data-modelo-verificado','sim');
					}
				}
				
				if($(obj).myAttr('data-albuns-not-show')){
					var albuns_not_show = $(obj).myAttr('data-albuns-not-show');
					
					$('.b2make-album-musicas-lista-album').each(function(){
						var id = $(this).find('.b2make-album-musicas-show').myAttr('data-album-musicas-id');
						var ans_arr = albuns_not_show.split(',');
						var found = false;
						
						for(var i=0;i<ans_arr.length;i++){
							if(ans_arr[i] == id){
								found = true;
								break;
							}
						}
						
						if(found){
							$(this).find('.b2make-album-musicas-show').myAttr('data-status','not-show');
						} else {
							$(this).find('.b2make-album-musicas-show').myAttr('data-status','show');
						}
					});
				} else {
					$('.b2make-album-musicas-lista-album').each(function(){
						$(this).find('.b2make-album-musicas-show').myAttr('data-status','show');
					});
				}
				
				if($(obj).myAttr('data-color-ahex')){
					$('#b2make-woam-area-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-color-ahex')));
					$('#b2make-woam-area-cor-val').myAttr('data-ahex',$(obj).myAttr('data-color-ahex'));
				} else {
					$('#b2make-woam-area-cor-val').css('background-color','');
					$('#b2make-woam-area-cor-val').myAttr('data-ahex','');
				}
				
				if($(obj).myAttr('data-preenchimento-color-ahex')){
					$('#b2make-woam-preenchimento-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-preenchimento-color-ahex')));
					$('#b2make-woam-preenchimento-cor-val').myAttr('data-ahex',$(obj).myAttr('data-preenchimento-color-ahex'));
				} else {
					$('#b2make-woam-preenchimento-cor-val').css('background-color','#ffffff');
					$('#b2make-woam-preenchimento-cor-val').myAttr('data-ahex','ffffffff');
				}
				
				if($(obj).myAttr('data-faixas-color-ahex')){
					$('#b2make-woam-faixas-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-faixas-color-ahex')));
					$('#b2make-woam-faixas-cor-val').myAttr('data-ahex',$(obj).myAttr('data-faixas-color-ahex'));
				} else {
					$('#b2make-woam-faixas-cor-val').css('background-color','#4A72B0');
					$('#b2make-woam-faixas-cor-val').myAttr('data-ahex','4a72b0ff');
				}
				
				if($(obj).myAttr('data-titulo-color-ahex')){
					$('#b2make-woam-titulo-cor').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-color-ahex')));
					$('#b2make-woam-titulo-cor').myAttr('data-ahex',$(obj).myAttr('data-titulo-color-ahex'));
				} else {
					$('#b2make-woam-titulo-cor').css('background-color','#58585B');
					$('#b2make-woam-titulo-cor').myAttr('data-ahex','58585Bff');
				}
				
				if($(obj).myAttr('data-player-color-ahex')){
					$('#b2make-woam-player-cor').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-player-color-ahex')));
					$('#b2make-woam-player-cor').myAttr('data-ahex',$(obj).myAttr('data-player-color-ahex'));
				} else {
					$('#b2make-woam-player-cor').css('background-color','#ffffff');
					$('#b2make-woam-player-cor').myAttr('data-ahex','ffffffff');
				}
				
				if($(obj).myAttr('data-lista-color-1-ahex')){
					$('#b2make-woam-lista-cor-1').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-lista-color-1-ahex')));
					$('#b2make-woam-lista-cor-1').myAttr('data-ahex',$(obj).myAttr('data-lista-color-1-ahex'));
				} else {
					$('#b2make-woam-lista-cor-1').css('background-color','#A1BC31');
					$('#b2make-woam-lista-cor-1').myAttr('data-ahex','a1bc31ff');
				}
				
				if($(obj).myAttr('data-lista-color-2-ahex')){
					$('#b2make-woam-lista-cor-2').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-lista-color-2-ahex')));
					$('#b2make-woam-lista-cor-2').myAttr('data-ahex',$(obj).myAttr('data-lista-color-2-ahex'));
				} else {
					$('#b2make-woam-lista-cor-2').css('background-color','#58585B');
					$('#b2make-woam-lista-cor-2').myAttr('data-ahex','58585Bff');
				}
				
				if($(obj).myAttr('data-botoes-color-1-ahex')){
					$('#b2make-woam-botoes-cor-1-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botoes-color-1-ahex')));
					$('#b2make-woam-botoes-cor-1-val').myAttr('data-ahex',$(obj).myAttr('data-botoes-color-1-ahex'));
				} else {
					$('#b2make-woam-botoes-cor-1-val').css('background-color','#ffffff');
					$('#b2make-woam-botoes-cor-1-val').myAttr('data-ahex','ffffffff');
				}
				
				if($(obj).myAttr('data-botoes-color-2-ahex')){
					$('#b2make-woam-botoes-cor-2-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botoes-color-2-ahex')));
					$('#b2make-woam-botoes-cor-2-val').myAttr('data-ahex',$(obj).myAttr('data-botoes-color-2-ahex'));
				} else {
					$('#b2make-woam-botoes-cor-2-val').css('background-color','#dbdbdb');
					$('#b2make-woam-botoes-cor-2-val').myAttr('data-ahex','dbdbdbff');
				}
				
				if($(obj).myAttr('data-titulo-font-family')){
					$('#b2make-woam-titulo-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-titulo-font-family')
					});
					$('#b2make-woam-titulo-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-titulo-font-family'));
				} else {
					$('#b2make-woam-titulo-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-woam-titulo-cont').find('.b2make-fonts-holder').html(b2make.font);
				}
				
				if($(obj).myAttr('data-player-font-family')){
					$('#b2make-woam-player-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-player-font-family')
					});
					$('#b2make-woam-player-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-player-font-family'));
				} else {
					$('#b2make-woam-player-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-woam-player-cont').find('.b2make-fonts-holder').html(b2make.font);
				}
				
				if($(obj).myAttr('data-lista-font-family')){
					$('#b2make-woam-lista-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-lista-font-family')
					});
					$('#b2make-woam-lista-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-lista-font-family'));
				} else {
					$('#b2make-woam-lista-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-woam-lista-cont').find('.b2make-fonts-holder').html(b2make.font);
				}
			break;
			case 'redessociais': 
				title = b2make.msgs.redessociaisTitle;
				sub_menu = true;
				specific = true;
				
				if($(obj).myAttr('data-margin')){
					$('#b2make-wors-margin').val($(obj).myAttr('data-margin'));
				} else {
					$('#b2make-wors-margin').val($('#b2make-wors-margin').get(0).defaultValue);
				}
				
				if($(obj).myAttr('data-tamanho')){
					$('#b2make-wors-tamanho').val($(obj).myAttr('data-tamanho'));
				} else {
					$('#b2make-wors-tamanho').val($('#b2make-wors-tamanho').get(0).defaultValue);
				}
				
				var cores = $(obj).myAttr('data-cores-ahex');
				var cores_arr = new Array();
				
				if(cores)
					cores_arr = cores.split(';');
				
				var enderecos = $(obj).myAttr('data-enderecos');
				var enderecos_arr = new Array();
				
				if(enderecos)
					enderecos_arr = enderecos.split(';');
				
				var images = $(obj).myAttr('data-images');
				var images_arr = new Array();
				
				if(images)
					images_arr = images.split(';');
				
				for(var i=0;i<b2make.redessociais.length;i++){
					var id = b2make.redessociais[i].id;
					
					var found = false;
					
					for(var j=0;j<cores_arr.length;j++){
						var cor_arr = cores_arr[j].split(',');
						if(cor_arr[0] == id){
							found = cor_arr[1];
							break;
						}
					}
					
					if(found){
						$.jPicker.List[(i+3)].color.active.val('ahex',found);
					} else {
						$.jPicker.List[(i+3)].color.active.val('all',null);
					}
					
					found = false;
					
					for(var j=0;j<enderecos_arr.length;j++){
						var endereco_arr = enderecos_arr[j].split(',');
						if(endereco_arr[0] == id){
							found = endereco_arr[1];
							break;
						}
					}
					
					if(found){
						$('.b2make-redessociais-options-endereco[data-id="'+id+'"]').val(found);
					} else {
						$('.b2make-redessociais-options-endereco[data-id="'+id+'"]').val('');
					}
					
					var image_url = '';
					var image_width = '';
					var image_height = '';
					
					for(var j=0;j<images_arr.length;j++){
						var image_arr = images_arr[j].split(',');
						if(image_arr[0] == id){
							image_url = image_arr[1];
							image_width = image_arr[2];
							image_height = image_arr[3];
							break;
						}
					}
					
					var obj_target = '.b2make-redessociais-options-snapshot[data-id="'+id+'"]';
					
					if(image_url){
						$(obj_target).find('div').html('');
						$(obj_target).find('div').css('left','0px');
						$(obj_target).find('div').css('backgroundImage',image_url);
						if(parseInt(image_width) < parseInt(image_height)){
							$(obj_target).find('div').css('background-size','100% auto');
						} else {
							$(obj_target).find('div').css('background-size','auto 100%');
						}
					} else {
						var img = $('<img class="svg social-link b2make-redessociais-options-snapshot-img" src="images/b2make-icones-sociais.svg">');
						
						$(obj_target).find('div').css('backgroundImage','none');
						img.appendTo($(obj_target).find('div'));
						
						$(obj_target).find('div').css('left','-'+(i*b2make.redessociais_fator)+'px');
					}
				}
				
				jQuery('img.svg').each(function(){
					var $img = jQuery(this);
					var imgID = $img.myAttr('id');
					var imgClass = $img.myAttr('class');
					var imgURL = $img.myAttr('src');

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

						// Remove any invalid XML tags as per http://validator.w3.org
						$svg = $svg.removeAttr('xmlns:a');

						// Replace image with new SVG
						$img.replaceWith($svg);

					}, 'xml');

				});
			break;
			case 'instagram': 
				title = b2make.msgs.instagramTitle;
				specific = true;
				
				var numero = parseInt($(obj).myAttr('data-numero-posts'));
				var tamanho_imagens = parseInt($(obj).myAttr('data-tamanho-imagens'));
				
				$('#b2make-wsoi-tamanho-imagens-val').val((tamanho_imagens > 0 ? tamanho_imagens : '220'));
			
				if(numero > 1){
					$('#b2make-wsoi-tamanho-imagens-lbl').show();
					$('#b2make-wsoi-tamanho-imagens-val').show();
				} else {
					$('#b2make-wsoi-tamanho-imagens-lbl').hide();
					$('#b2make-wsoi-tamanho-imagens-val').hide();
				}
				
				$('#b2make-wsoi-numero-posts-val').val(numero);
			break;
			case 'addthis': 
				title = b2make.msgs.addthisTitle;
				specific = true;
				
				$('#b2make-wso-addthis-facebook').prop("checked",false);
				$('#b2make-wso-addthis-tweets').prop("checked",false);
				$('#b2make-wso-addthis-googleplus').prop("checked",false);
				
				var mostrar = $(obj).myAttr('data-mostrar');
				
				if(mostrar && mostrar != ''){
					var mostrar_arr = mostrar.split(',');
					
					for(var i=0;i<mostrar_arr.length;i++){
						$('#b2make-wso-addthis-'+mostrar_arr[i]).prop("checked",true);
					}
				}
			break;
			case 'youtube': 
				title = b2make.msgs.youtubeTitle;
				specific = true;
				
				youtube_layout_tipo();
				
				$('#b2make-wso-youtube-url-val').val($(obj).myAttr('data-url'));
				
				if($(obj).myAttr('data-layout-tipo')){
					var option = $('#b2make-wso-youtube-layout-tipo').find("[value='" + $(obj).myAttr('data-layout-tipo') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-wso-youtube-layout-tipo').find(":first");
					option.prop('selected', 'selected');
				}
				
				if($(obj).myAttr('data-type-image-background')){
					var image_url = $(obj).myAttr('data-type-image-background');

					$('#b2make-wso-youtube-bg-image').css('background-size','25px auto');
					$('#b2make-wso-youtube-bg-image').css('backgroundImage','url('+image_url+')');
				} else {
					$('#b2make-wso-youtube-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
					$('#b2make-wso-youtube-bg-image').css('background-size','auto auto');
				}
				
				if($(obj).myAttr('data-tamanho')){
					$('#b2make-wso-youtube-tamanho').val($(obj).myAttr('data-tamanho'));
				} else {
					$('#b2make-wso-youtube-tamanho').val(60);
				}
				
				if($(obj).myAttr('data-titulo')){
					$('#b2make-wso-youtube-titulo-val').val($(obj).myAttr('data-titulo'));
				} else {
					$('#b2make-wso-youtube-titulo-val').val('');
				}
				
				if($(obj).myAttr('data-caixa-altura')){
					$('#b2make-wso-youtube-caixa-altura').val($(obj).myAttr('data-caixa-altura'));
				} else {
					$('#b2make-wso-youtube-caixa-altura').val('50');
				}
				
				if($(obj).myAttr('data-caixa-color-ahex')){
					$('#b2make-wso-youtube-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
					$('#b2make-wso-youtube-caixa-cor-val').myAttr('data-ahex',$(obj).myAttr('data-caixa-color-ahex'));
				} else {
					$('#b2make-wso-youtube-caixa-cor-val').css('background-color','#C8C8C8');
					$('#b2make-wso-youtube-caixa-cor-val').myAttr('data-ahex','c8c8c8ff');
				}
				
				if($(obj).myAttr('data-caixa-text-color-ahex')){
					$('#b2make-wso-youtube-caixa-texto-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-text-color-ahex')));
					$('#b2make-wso-youtube-caixa-texto-cor-val').myAttr('data-ahex',$(obj).myAttr('data-caixa-text-color-ahex'));
				} else {
					$('#b2make-wso-youtube-caixa-texto-cor-val').css('background-color','#ffffff');
					$('#b2make-wso-youtube-caixa-texto-cor-val').myAttr('data-ahex','ffffffff');
				}
				
				var types = new Array('caixa-texto');
				
				for(var i=0;i<types.length;i++){
					var type = types[i];
					var tamanho;
					
					switch(type){
						case 'caixa-texto': tamanho = 20; break;
					}
					
					if($(obj).myAttr('data-'+type+'-font-family')){
						$('#b2make-wso-youtube-'+type+'-text-cont').find('.b2make-fonts-holder').css({
							'fontFamily': $(obj).myAttr('data-'+type+'-font-family')
						});
						$('#b2make-wso-youtube-'+type+'-text-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-'+type+'-font-family'));
					} else {
						$('#b2make-wso-youtube-'+type+'-text-cont').find('.b2make-fonts-holder').css({
							'fontFamily': 'Roboto Condensed'
						});
						$('#b2make-wso-youtube-'+type+'-text-cont').find('.b2make-fonts-holder').html('Roboto Condensed');
					}
					
					if($(obj).myAttr('data-'+type+'-font-size')){
						$('#b2make-wso-youtube-'+type+'-text-cont').find('.b2make-fonts-size').val($(obj).myAttr('data-'+type+'-font-size'));
					} else {
						$('#b2make-wso-youtube-'+type+'-text-cont').find('.b2make-fonts-size').val(tamanho);
					}
				}
			break;
			case 'download': 
				title = b2make.msgs.downloadTitle; 
				sub_menu = true; 
				specific = true; 
				
				$('#b2make-wso-download-texto-val').val($(obj).find('.b2make-widget-out').find('.b2make-download-widget-link').find('.b2make-download-widget-holder').find('.b2make-texto-cel').html());
				
				if($(obj).myAttr('data-gradiente')){
					var option = $('#b2make-wod-caixa-gradiente-val').find("[value='" + $(obj).myAttr('data-gradiente') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-wod-caixa-gradiente-val').find(":last");
					option.prop('selected', 'selected');
				}
				
				if($(obj).myAttr('data-caixa-color-ahex')){
					$('#b2make-wod-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
					$('#b2make-wod-caixa-cor-val').myAttr('data-ahex',$(obj).myAttr('data-caixa-color-ahex'));
				} else {
					$('#b2make-wod-caixa-cor-val').css('background-color','#000000');
					$('#b2make-wod-caixa-cor-val').myAttr('data-ahex','000000ff');
				}
				
				if($(obj).myAttr('data-text-color-ahex')){
					$('#b2make-wod-texto-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-text-color-ahex')));
					$('#b2make-wod-texto-cor-val').myAttr('data-ahex',$(obj).myAttr('data-text-color-ahex'));
				} else {
					$('#b2make-wod-texto-cor-val').css('background-color','#FFFFFF');
					$('#b2make-wod-texto-cor-val').myAttr('data-ahex','ffffffff');
				}
				
				if($(obj).myAttr('data-font-family')){
					$('#b2make-wod-texto-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-font-family')
					});
					$('#b2make-wod-texto-text-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-font-family'));
				} else {
					$('#b2make-wod-texto-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-wod-text-cont').find('.b2make-fonts-holder').html(b2make.font);
				}
				
				if($(obj).myAttr('data-font-size')){
					$('#b2make-wod-texto-text-cont').find('.b2make-fonts-size').val($(obj).myAttr('data-font-size'));
				} else {
					$('#b2make-wod-texto-text-cont').find('.b2make-fonts-size').val(16);
				}
				
				if($(obj).myAttr('data-font-negrito')){
					$('#b2make-wod-texto-text-cont').myAttr('data-font-negrito',$(obj).myAttr('data-font-negrito'));
				}
				
				if($(obj).myAttr('data-font-italico')){
					$('#b2make-wod-texto-text-cont').myAttr('data-font-italico',$(obj).myAttr('data-font-italico'));
				}
			break;
			
		}
		
		if($(obj).myAttr('data-sombra')){
			var sombra = $(obj).myAttr('data-sombra');
			var sombra_arr = sombra.split(';');
			
			$('#b2make-wcas-deslocamento-x-val').val(sombra_arr[0]);
			$('#b2make-wcas-deslocamento-y-val').val(sombra_arr[1]);
			$('#b2make-wcas-desfoque-val').val(sombra_arr[2]);
			$('#b2make-wcas-tamanho-val').val(sombra_arr[3]);
			$.jPicker.List[17].color.active.val('ahex',sombra_arr[5]);
		} else {
			$('#b2make-wcas-deslocamento-x-val').val('0');
			$('#b2make-wcas-deslocamento-y-val').val('0');
			$('#b2make-wcas-desfoque-val').val('0');
			$('#b2make-wcas-tamanho-val').val('0');
			$.jPicker.List[17].color.active.val('ahex','000000ff');
		}
		
		if($(obj).myAttr('data-bordas-atual')){
			$('#b2make-wcb-conjuto option[value="'+$(obj).myAttr('data-bordas-atual')+'"]').prop('selected', 'selected');
		} else {
			$('#b2make-wcb-conjuto option[value="todas"]').prop('selected', 'selected');
		}
		
		if($(obj).myAttr('data-classes')){
			$('#b2make-woc-class').val($(obj).myAttr('data-classes'));
		} else {
			$('#b2make-woc-class').val('');
		}
		
		$('#b2make-wcb-conjuto').trigger('change');
		
		if($(obj).myAttr('data-bordas-todas')){
			var todas = $(obj).myAttr('data-bordas-todas');
			var todas_arr = todas.split(';');
			
			$('#b2make-wcb-todas-espessura-val').val(todas_arr[0]);
			$('#b2make-wcb-todas-estilo-val option[value="'+todas_arr[1]+'"]').prop('selected', 'selected');
			$('#b2make-wcb-todas-cor-val').css('background-color',todas_arr[2]);
			$('#b2make-wcb-todas-raio-val').val(todas_arr[3]);
			$('#b2make-wcb-todas-cor-val').myAttr('data-ahex',todas_arr[4]);
		} else {
			$('#b2make-wcb-todas-espessura-val').val('0');
			$('#b2make-wcb-todas-estilo-val option[value="solid"]').prop('selected', 'selected');
			$('#b2make-wcb-todas-cor-val').css('background-color','rgb(0,0,0)');
			$('#b2make-wcb-todas-raio-val').val('0');
			$('#b2make-wcb-todas-cor-val').myAttr('data-ahex','000000ff');
		}
		
		if($(obj).myAttr('data-bordas-individual')){
			var individual = $(obj).myAttr('data-bordas-individual');
			var individual_arr = individual.split(':');
			var todas_arr = individual_arr[0].split(';');
			
			$('#b2make-wcb-cima-espessura-val').val(todas_arr[0]);
			$('#b2make-wcb-cima-estilo-val option[value="'+todas_arr[1]+'"]').prop('selected', 'selected');
			$('#b2make-wcb-cima-cor-val').css('background-color',todas_arr[2]);
			$('#b2make-wcb-individual-raio-topleft-val').val(todas_arr[3]);
			$('#b2make-wcb-cima-cor-val').myAttr('data-ahex',todas_arr[4]);
			
			todas_arr = individual_arr[1].split(';');
			
			$('#b2make-wcb-baixo-espessura-val').val(todas_arr[0]);
			$('#b2make-wcb-baixo-estilo-val option[value="'+todas_arr[1]+'"]').prop('selected', 'selected');
			$('#b2make-wcb-baixo-cor-val').css('background-color',todas_arr[2]);
			$('#b2make-wcb-individual-raio-topright-val').val(todas_arr[3]);
			$('#b2make-wcb-baixo-cor-val').myAttr('data-ahex',todas_arr[4]);
			
			todas_arr = individual_arr[2].split(';');
			
			$('#b2make-wcb-esquerda-espessura-val').val(todas_arr[0]);
			$('#b2make-wcb-esquerda-estilo-val option[value="'+todas_arr[1]+'"]').prop('selected', 'selected');
			$('#b2make-wcb-esquerda-cor-val').css('background-color',todas_arr[2]);
			$('#b2make-wcb-individual-raio-bottomleft-val').val(todas_arr[3]);
			$('#b2make-wcb-esquerda-cor-val').myAttr('data-ahex',todas_arr[4]);
			
			todas_arr = individual_arr[3].split(';');
			
			$('#b2make-wcb-direita-espessura-val').val(todas_arr[0]);
			$('#b2make-wcb-direita-estilo-val option[value="'+todas_arr[1]+'"]').prop('selected', 'selected');
			$('#b2make-wcb-direita-cor-val').css('background-color',todas_arr[2]);
			$('#b2make-wcb-individual-raio-bottomright-val').val(todas_arr[3]);
			$('#b2make-wcb-direita-cor-val').myAttr('data-ahex',todas_arr[4]);
		} else {
			$('#b2make-wcb-cima-espessura-val').val('0');
			$('#b2make-wcb-cima-estilo-val option[value="solid"]').prop('selected', 'selected');
			$('#b2make-wcb-cima-cor-val').css('background-color','rgb(0,0,0)');
			$('#b2make-wcb-individual-raio-topleft-val').val('0');
			$('#b2make-wcb-cima-cor-val').myAttr('data-ahex','000000ff');

			$('#b2make-wcb-baixo-espessura-val').val('0');
			$('#b2make-wcb-baixo-estilo-val option[value="solid"]').prop('selected', 'selected');
			$('#b2make-wcb-baixo-cor-val').css('background-color','rgb(0,0,0)');
			$('#b2make-wcb-individual-raio-topright-val').val('0');
			$('#b2make-wcb-baixo-cor-val').myAttr('data-ahex','000000ff');
			
			$('#b2make-wcb-esquerda-espessura-val').val('0');
			$('#b2make-wcb-esquerda-estilo-val option[value="solid"]').prop('selected', 'selected');
			$('#b2make-wcb-esquerda-cor-val').css('background-color','rgb(0,0,0)');
			$('#b2make-wcb-individual-raio-bottomleft-val').val('0');
			$('#b2make-wcb-esquerda-cor-val').myAttr('data-ahex','000000ff');
			
			$('#b2make-wcb-direita-espessura-val').val('0');
			$('#b2make-wcb-direita-estilo-val option[value="solid"]').prop('selected', 'selected');
			$('#b2make-wcb-direita-cor-val').css('background-color','rgb(0,0,0)');
			$('#b2make-wcb-individual-raio-bottomright-val').val('0');
			$('#b2make-wcb-direita-cor-val').myAttr('data-ahex','000000ff');
		}
		
		var conteiner = b2make.selecionador_objetos.conteiner;
		$("#b2make-woc-title").html(title);
		$("#b2make-woc-name").val($(obj).myAttr('data-name')?$(obj).myAttr('data-name'):$(obj).myAttr('id'));
		$("#b2make-woc-marcador").val($(obj).myAttr('data-marcador')?$(obj).myAttr('data-marcador'):'');
		$("#b2make-woc-rotate-value").val($(obj).myAttr('data-angulo')?$(obj).myAttr('data-angulo'):'0');
		$(b2make.woc_height).val($(obj).height());
		$(b2make.woc_width).val($(obj).width());
		$(b2make.woc_position_top).val(parseInt($(conteiner).css('top')));
		$(b2make.woc_position_left).val(parseInt($(conteiner).css('left')));
		$(b2make.widget_options_childreen).show();
		$('#b2make-widget-options-more').hide();
		b2make.widget_sub_options_type = '';
		b2make.widget_specific_type = '';
		
		if(sub_menu){
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = p.widget_type;
			
			if(!$('.b2make-menu-nav li[data-id="'+p.widget_type+'"]').myAttr('data-nao-abrir-sub-options-auto'))$.widget_sub_options_open();
		}
		
		if(specific){
			b2make.widget_specific_type = p.widget_type;
			$.widget_specific_options_open();
		}
		
		$.menu_conteiner_aba_extra_open();
		
		$(obj).focus();
		b2make.conteiner_child_other = false;
		b2make.widget_sub_options_button_open = false;
		
		switch(p.widget_type){
			case 'services':
				$('#b2make-'+p.widget_type+'-callback').trigger('conteiner_child_open_finished');
			break;
			
		}
		
		$('#b2make-listener').trigger('b2make-conteiner-child-open');
	}
	
	$.conteiner_child_close = function(){
		var obj = b2make.conteiner_child_obj;
		var type = $(obj).myAttr('data-type');
		var widget_sub_options_have;
		
		$('#b2make-menu-abas-principais').show();
		$('#b2make-menu-abas-widgets').hide();
		$('#b2make-menu-abas').show();
		
		$('#b2make-widget-options-main').show();
		
		if(obj){
			$(b2make.menu).height(b2make.menu_height);
			$(b2make.menu_mask).height(b2make.menu_height);
			
			b2make.conteiner_show_after = b2make.conteiner_show;
			b2make.conteiner_child_show = false;
			
			if(b2make.widget_sub_options_have){
				b2make.widget_sub_options_have = false;
				widget_sub_options_have = true;
			}
			
			switch(type){
				case 'texto':
					if(b2make.texto_for_textarea)textarea_for_texto();
					text_fontes_close();
					jpicker_cancel();
				break;
				case 'iframe':
					if(b2make.iframe_for_textarea)textarea_for_iframe();
				break;
				
			}
			
			if(b2make.widget_sub_options_open){
				if(!b2make.conteiner_child_other){
					b2make.sub_options_conteiner_close = true;
					widget_sub_options_close();
				} else {
					b2make.conteiner_child_other = false;
				}
			} else if(widget_sub_options_have){
				widget_sub_options_close_button();
			}
			
			if(b2make.widget_specific_options_open){
				widget_specific_options_close();
			}
			
			selecionador_objetos_close();
			$.menu_conteiner_aba_extra_close();
			
			$(b2make.widget_options_childreen).hide();
			$.dialogbox_close();
			$('#b2make-widget-options-more').show();
			$(b2make.widget_sub_options_down).hide();
			
			$('#b2make-listener').trigger('b2make-conteiner-child-close');
			
			b2make.conteiner_child_obj = false;
			b2make.redes_sociais_image_select = false;
			b2make.conteiner_child_type = false;
		}
	}
	
	function conteiner_principal_site_update(){
		var height = 0;
		
		$(b2make.widget).each(function(){
			var type = $(this).myAttr('data-type');
			
			if(type == 'conteiner'){
				height += $(this).height();
			}
		});
		
		$('#b2make-site').height(height+(2*b2make.widget_border));
	}
	
	function conteiner_add(){
		history_add({local:'conteiner_add',vars:{conteiner_total:b2make.conteiner_total}});
		
		var cont = $('<div class="b2make-widget"></div>');
		var bg_color = '';
		
		cont.myAttr('id','area'+b2make.widgets_count);
		cont.myAttr('data-type','conteiner');
		
		if(!b2make.conteiner_colors)bg_color = 'rgb('+Math.floor(255*Math.random())+','+Math.floor(255*Math.random())+','+Math.floor(255*Math.random())+')';
		else {
			arr_colors = b2make.conteiner_colors;
			if(!b2make.conteiner_colors_num)b2make.conteiner_colors_num = 0;
			
			bg_color = b2make.conteiner_colors[b2make.conteiner_colors_num % b2make.conteiner_colors.length];
			
			b2make.conteiner_colors_num++;
		}
		
		cont.css('backgroundColor',bg_color);
		cont.css('position','relative');
		cont.css('overflow','hidden');
		
		if(b2make.multi_screen.device != 'desktop') cont.css('min-width','0px');
		
		cont.css('height',b2make.conteiner_height);
		
		cont.appendTo(b2make.site_conteiner);
		
		b2make.widgets.push({
			id : b2make.widgets_count,
			type : 'conteiner'
		});
		
		b2make.conteiner_total++;
		
		if(b2make.widget_sub_options_open){
			widget_sub_options_close();
		}
		
		if(b2make.conteiner_show){
			conteiner_close_all();
		}
		
		b2make.conteiner_obj = document.getElementById('area'+b2make.widgets_count);

		if(b2make.multi_screen.device != 'phone') conteiner_area_add();
		
		conteiner_open();
		
		b2make.widgets_count++;
		$('.b2make-image-holder').removeClass('b2make-image-holder-clicked');
		menu_widget_areas_update();
		
		$.url_design();
	}
	
	function conteiner_open(){
		var obj = b2make.conteiner_obj;
		
		$('#b2make-menu-abas-principais').show();
		$('#b2make-menu-abas-widgets').hide();
		$('#b2make-menu-abas').show();
		
		$(b2make.widget_sub_options_up).hide();
		$(b2make.widget_sub_options_down).hide();
		
		if($(obj).myAttr('data-area')){
			b2make.conteiner_obj_area = document.getElementById($(obj).myAttr('data-area'));
			
			$(b2make.conteiner_obj_area).css('border-left',b2make.conteiner_border_style);
			$(b2make.conteiner_obj_area).css('border-right',b2make.conteiner_border_style);
			//$(b2make.conteiner_obj_area).css('backgroundColor','rgba(0,0,0,0.3);');
			
			$('#b2make-conteiner-area-largura-lbl').show();
			$('#b2make-conteiner-area-largura').show();
			
			$('#b2make-conteiner-area-largura').val($(obj).myAttr('data-area-largura'));
			
			var option = $('#b2make-conteiner-area-status').find("[value='s']");
			option.prop('selected', 'selected');
		} else {
			b2make.conteiner_obj_area = null;
			$('#b2make-conteiner-area-largura-lbl').hide();
			$('#b2make-conteiner-area-largura').hide();
			
			var option = $('#b2make-conteiner-area-status').find("[value='n']");
			option.prop('selected', 'selected');
		}
		
		if(b2make.multi_screen.device == 'phone'){
			$('#b2make-conteiner-area-largura-lbl').hide();
			$('#b2make-conteiner-area-largura').hide();
			$('#b2make-conteiner-area-lbl').hide();
			$('#b2make-conteiner-area-status').hide();
		}
		
		if($(obj).myAttr('data-area-fixed')){
			var option = $('#b2make-conteiner-fixed-status').find("[value='"+$(obj).myAttr('data-area-fixed')+"']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-conteiner-fixed-status').find("[value='n']");
			option.prop('selected', 'selected');
		}
		
		$('#b2make-wom-help').myAttr('data-type','conteiner');
		
		if(!b2make.widget_conteiner_mask){
			b2make.widget_conteiner_mask = $('<div id="b2make-widget-conteiner-mask"></div>');
		}
		
		b2make.widget_conteiner_mask.css('backgroundColor','#FFF');
		b2make.widget_conteiner_mask.css('width',$(obj).css('width'));
		b2make.widget_conteiner_mask.css('height',$(obj).css('height'));
		
		$(obj).after(b2make.widget_conteiner_mask);
		
		if($(b2make.shadow).length == 0) $('<div id="b2make-shadow"></div>').appendTo('#b2make-site');
		$(b2make.shadow).fadeIn(b2make.fade_time);
		
		b2make.conteiner_show = $(obj).myAttr('id');
		
		b2make.conteiner_show_data = {
			width : ($(obj).css('width')?$(obj).css('width'):'100%'),
			border : ($(obj).css('border')?$(obj).css('border'):'auto'),
			left : 'auto',
			top : 'auto',
			position : ($(obj).css('position')?$(obj).css('position'):'auto'),
			zIndex : ($(obj).css('zIndex')?$(obj).css('zIndex'):'auto')
		};
		
		var top = $(obj).offset().top;
		
		var position = 'relative';

		if($(obj).myAttr('data-position')) position = $(obj).myAttr('data-position');
		
		if(position == 'fixed'){
			top = top - $(window).scrollTop();
			if(b2make_menu.open){
				$(obj).css('left',b2make_menu.width_conteiner+'px');
			} else {
				$(obj).css('left','0px');
			}

			$(obj).css('width',$(window).width()-b2make_menu.width_conteiner+16);
		} else {
			position = 'absolute';
			
			top = top - parseInt($('#b2make-site').css('top')); 
			$(obj).css('left',$('#b2make-menu').css('left'));
			$(obj).css('width',$('#b2make-site').width() - 2*b2make.widget_border);
		}
		
		b2make.conteiner_not_first_access = true;
		
		$(obj).css('top',top);
		$(obj).css('zIndex',9);
		$(obj).css('position',position);
		$(obj).css('border',b2make.conteiner_border_style);
		
		// ===================== Opções
		
		$(b2make.wom_name).val($(obj).myAttr('data-name')?$(obj).myAttr('data-name'):$(obj).myAttr('id'));
		$(b2make.won_height).val($(obj).height());
		$(b2make.widget_options).show();
		$(b2make.widget_options_childreen).hide();
		conteiner_position();
		$('#b2make-widget-options-more').show();
		$('#b2make-widget-options-main').show();
		$('#b2make-menu-start').hide();
		$('#b2make-page-options').hide();
		$('#b2make-other-options').hide(); b2make.perfil_foto_image_select = false; b2make.segmento_foto_image_select = false; b2make.template_foto_image_select = false;
		if(b2make.page_options_menu_height){$('#b2make-menu').height(b2make.page_options_menu_height); b2make.page_options_menu_height = false;}
		
		if(b2make.widget_sub_options_open){
			widget_sub_options_close();
		}
		
		// Conteiner Banners
		
		if(b2make.conteiner_banners_start[$(obj).myAttr('id')]){
			$('#b2make-woc-banners-animation-start-pause').css('backgroundPosition','-20px 0px');
		} else {
			$('#b2make-woc-banners-animation-start-pause').css('backgroundPosition','0px 0px');
		}
		
		if($(obj).myAttr('data-animation-type')){
			var option = $('#b2make-woc-banners-animation-type').find("[value='" + $(obj).myAttr('data-animation-type') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-woc-banners-animation-type').find(":first");
			option.prop('selected', 'selected');
		}
		
		if($(obj).myAttr('data-ease-type')){
			var option = $('#b2make-woc-banners-ease-type').find("[value='" + $(obj).myAttr('data-ease-type') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-woc-banners-ease-type').find(":first");
			option.prop('selected', 'selected');
		}
		
		if($(obj).myAttr('data-tempo-transicao')){
			$('#b2make-woc-banners-tempo-transicao').val($(obj).myAttr('data-tempo-transicao'));
		} else {
			$('#b2make-woc-banners-tempo-transicao').val('500');
		}
		
		if($(obj).myAttr('data-tempo-exposicao')){
			$('#b2make-woc-banners-tempo-exposicao').val($(obj).myAttr('data-tempo-exposicao'));
		} else {
			$('#b2make-woc-banners-tempo-exposicao').val('3000');
		}
		
		if($(obj).myAttr('data-seta-margem')){
			$('#b2make-woc-banners-seta-margem').val($(obj).myAttr('data-seta-margem'));
		} else {
			$('#b2make-woc-banners-seta-margem').val('100');
		}
		
		if($(obj).myAttr('data-seta-tamanho')){
			$('#b2make-woc-banners-seta-tamanho').val($(obj).myAttr('data-seta-tamanho'));
		} else {
			$('#b2make-woc-banners-seta-tamanho').val('100');
		}
		
		if($(obj).myAttr('data-seta-visivel')){
			var option = $('#b2make-woc-banners-seta-visivel').find("[value='" + $(obj).myAttr('data-seta-visivel') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-woc-banners-seta-visivel').find(":first");
			option.prop('selected', 'selected');
		}
		
		if($(obj).myAttr('data-seta-color-ahex')){
			$('#b2make-woc-banners-seta-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-seta-color-ahex')));
			$('#b2make-woc-banners-seta-cor-val').myAttr('data-ahex',$(obj).myAttr('data-seta-color-ahex'));
		} else {
			$('#b2make-woc-banners-seta-cor-val').css('background-color','#ffffff');
			$('#b2make-woc-banners-seta-cor-val').myAttr('data-ahex','#ffffffff');
		}
		
		if($(obj).myAttr('data-banners-id')){
			$('.b2make-conteiner-banners-show').each(function(){
				if($(obj).myAttr('data-banners-id') == $(this).myAttr('data-banners-id')){
					$(this).myAttr('data-status','show');
				} else {
					$(this).myAttr('data-status','not-show');
				}
			});
			
			var id = $(obj).myAttr('data-banners-id');
			
			if(b2make.conteiner_banners_todos_ids){
				var conteiner_banners_ids =  b2make.conteiner_banners_todos_ids;
				var found = false;
				
				for(var i=0;i<conteiner_banners_ids.length;i++){
					if(conteiner_banners_ids[i] == $(obj).myAttr('data-banners-id')){
						found = true;
						break;
					}
				}
				
				if(found){
					b2make.conteiner_banners_atual = $(obj).myAttr('data-banners-id');
					b2make.conteiner_banners_nome = $('.b2make-conteiner-banners-nome[data-banners-id="'+id+'"]').html();
					
					$('.b2make-conteiner-banners-nome').each(function(){
						$(this).myAttr('data-status','not-show');
					});
					
					$('.b2make-conteiner-banners-nome[data-banners-id="'+id+'"]').myAttr('data-status','show');
					
					$('#b2make-conteiner-banners-lista-images').html('');
					
					conteiner_banners_images();
				}
			}
		} else {
			$('.b2make-conteiner-banners-show').each(function(){
				$(this).myAttr('data-status','not-show');
			});
		}
		
		// Áreas Globais
		
		if($(obj).myAttr('data-area-global') && $(obj).myAttr('data-area-global-id')){
			b2make.areas_globais_change = true;
		}
		
		if($(obj).myAttr('data-area-global')){
			var option = $('#b2make-conteiner-area-global').find("[value='"+$(obj).myAttr('data-area-global')+"']");
			option.prop('selected', 'selected');
		} else {			
			var option = $('#b2make-conteiner-area-global').find("[value='n']");
			option.prop('selected', 'selected');
		}
		
		if($(obj).myAttr('data-area-global-id')){
			var area_global_id = $(obj).myAttr('data-area-global-id');
			var nome_obj = {};
			
			$('.b2make-conteiner-areas-globais-show').each(function(){
				if(area_global_id == $(this).myAttr('data-areas-globais-id')){
					$(this).myAttr('data-status','show');
				} else {
					$(this).myAttr('data-status','not-show');
				}
			});
			
			$('.b2make-conteiner-areas-globais-nome').each(function(){
				if(area_global_id == $(this).myAttr('data-areas-globais-id')){
					$(this).myAttr('data-status','show');
					nome_obj = $(this);
				} else {
					$(this).myAttr('data-status','not-show');
				}
			});
			
			var id = nome_obj.myAttr('data-areas-globais-id');
			
			b2make.conteiner_areas_globais_atual = nome_obj.myAttr('data-areas-globais-id');
			b2make.conteiner_areas_globais_nome = nome_obj.html();
			b2make.conteiner_areas_globais_menu_atual = id;
		} else {
			$('.b2make-conteiner-areas-globais-show').each(function(){
				$(this).myAttr('data-status','not-show');
			});
			
			$('.b2make-conteiner-areas-globais-nome').each(function(){
				$(this).myAttr('data-status','not-show');
			});
		}
		
		// Opções Mais
		
		if($(obj).myAttr('data-bg-ahex')){
			$.jPicker.List[2].color.active.val('ahex',$(obj).myAttr('data-bg-ahex'));
		} else {
			if($(obj).css('backgroundColor')){
				var hexDigits = new Array("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
				
				function rgb2hex(rgb){
					if(rgb){
						rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
						return hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
					} else {
						return hex('255') + hex('255') + hex('255');
					}
				}

				function hex(x) {
					return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
				}
				
				$.jPicker.List[2].color.active.val('hex',rgb2hex($(obj).css('backgroundColor')));
			} else {
				$.jPicker.List[2].color.active.val('all',null);
			}
		}
		
		if($(obj).myAttr('data-type-image-background')){
			var image_url = $(obj).myAttr('data-type-image-background');

			$('#b2make-conteiner-bg-image').css('background-size','25px auto');
			$('#b2make-conteiner-bg-image').css('backgroundImage','url('+image_url+')');
		} else {
			$('#b2make-conteiner-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
			$('#b2make-conteiner-bg-image').css('background-size','auto auto');
		}
		
		if($(obj).myAttr('data-background-repeat')){
			var option = $('#b2make-conteiner-bi-repeat').find("[value='" + $(obj).myAttr('data-background-repeat') + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-conteiner-bi-repeat').find(":first");
			$(obj).css('backgroundSize','100% auto');
			option.prop('selected', 'selected');
		}
		
		if($(obj).myAttr('data-background-position-x')){
			var pos = $(obj).myAttr('data-background-position-x');
			var ok;
			var pos_val = 0;
			
			switch(pos){
				case 'left':
				case 'right':
				case 'center':
					ok = true;
				break;
				default: pos_val = pos; pos = 'value';
			}
			
			var option = $('#b2make-conteiner-bi-position-x').find("[value='" + pos + "']");
			option.prop('selected', 'selected');
			
			if(ok){
				$('#b2make-conteiner-bi-position-x-value').hide();
			} else {
				$('#b2make-conteiner-bi-position-x-value').show();
				$('#b2make-conteiner-bi-position-x-value').val(pos_val);
			}
		} else {
			var option = $('#b2make-conteiner-bi-position-x').find(":first");
			option.prop('selected', 'selected');
			$('#b2make-conteiner-bi-position-x-value').hide();
			$('#b2make-conteiner-bi-position-x-value').val('0');
		}
		
		if($(obj).myAttr('data-background-position-y')){
			var pos = $(obj).myAttr('data-background-position-y');
			var ok;
			var pos_val = 0;
			
			switch(pos){
				case 'top':
				case 'bottom':
				case 'center':
					ok = true;
				break;
				default: pos_val = pos; pos = 'value';
			}
			
			var option = $('#b2make-conteiner-bi-position-y').find("[value='" + pos + "']");
			option.prop('selected', 'selected');
			
			if(ok){
				$('#b2make-conteiner-bi-position-y-value').hide();
			} else {
				$('#b2make-conteiner-bi-position-y-value').show();
				$('#b2make-conteiner-bi-position-y-value').val(pos_val);
			}
		} else {
			var option = $('#b2make-conteiner-bi-position-y').find(":first");
			option.prop('selected', 'selected');
			$('#b2make-conteiner-bi-position-y-value').hide();
			$('#b2make-conteiner-bi-position-y-value').val('0');
		}
		
		conteiner_principal_site_update();
		biblioteca_imagens_conteiners_update();
	}
	
	$.conteiner_nao_existe_regra = function(){
		if(!b2make.conteiner_show){
			if(b2make.conteiner_total < 3){
				conteiner_add();
			} 
		}
	}
	
	function conteiner_close(){
		var obj = b2make.conteiner_obj;
		
		if(obj){
			$('#b2make-menu-abas-principais').hide();
			$('#b2make-menu-abas-widgets').hide();
			$('#b2make-menu-abas').hide();
			
			if($(obj).myAttr('data-area')){
				$(b2make.conteiner_obj_area).css('border-left','none');
				$(b2make.conteiner_obj_area).css('border-right','none');
				$(b2make.conteiner_obj_area).css('backgroundColor','transparent');
			}
			
			b2make.widget_conteiner_mask.remove();
			
			b2make.conteiner_show = false;
			
			var position = 'relative';

			if($(obj).myAttr('data-position')) position = $(obj).myAttr('data-position');
			
			var top;
			
			if(position == 'relative'){
				top = b2make.conteiner_show_data.top;
				$(obj).css('zIndex','auto');
			} else {
				top = $(obj).offset().top;
				top = top - $(window).scrollTop();
				$(obj).css('zIndex','3');
			}
			
			$(obj).css('position',position);
			
			var status = $(obj).myAttr('data-area-fixed');
			
			if(status == 'b'){
				var bottom = $(window).height() - top - $(obj).outerHeight(true) + 4;
				
				$(obj).css('top','auto');
				$(obj).css('bottom',bottom+'px');
			} else {
				$(obj).css('top',top);
				$(obj).css('bottom','auto');
			}
			
			$(obj).css('border','none');
			$(obj).css('left',b2make.conteiner_show_data.left);
			$(obj).css('width',b2make.conteiner_show_data.width);
			$(obj).css('cursor','default');
			
			if($(obj).myAttr('data-type') == 'conteiner'){
				conteiners_update();
			}
			
			$(b2make.shadow).fadeOut(b2make.fade_time);
			$(b2make.widget_options).hide();
			$('#b2make-widget-options-more').hide();
			$('#b2make-widget-options-main').hide();
			$('#b2make-widget-sub-options').hide();
			$('#b2make-menu-start').show();
			$('#b2make-other-options').hide(); b2make.perfil_foto_image_select = false; b2make.segmento_foto_image_select = false; b2make.template_foto_image_select = false;
			if(b2make.page_options_menu_height){$('#b2make-menu').height(b2make.page_options_menu_height); b2make.page_options_menu_height = false;}
			$.dialogbox_close();
			
			if(b2make.widget_sub_options_open){
				b2make.sub_options_conteiner_close = true;
				widget_sub_options_close();
			}
			
			$(b2make.widget_sub_options_down).hide();
			//$(b2make.menu).height(b2make.menu_height);
			$(b2make.menu_mask).height(b2make.menu_height);
			
			b2make.conteiner_close_full = false;
		}
		
		$('#b2make-listener').trigger('b2make-conteiner-close');
	}
	
	function conteiner_close_all(){
		b2make.conteiner_close_full = true;
		if(b2make.holder_template_open)holder_template_close();
		$.conteiner_child_close();
		conteiner_close();
		conteiner_banners_image_preview_close();
	}
	
	function conteiner_before_after(){
		var obj = b2make.conteiner_obj;
		
		var id = $(obj).myAttr('id');
		var flag = false;
		
		b2make.widget_before = false;
		b2make.widget_after = false;
		
		$(b2make.widget).each(function(){
			var type = $(this).myAttr('data-type');
			var id2 = $(this).myAttr('id');
			
			if(type == 'conteiner'){
				if(
					!b2make.widget_after &&
					flag
				){
					b2make.widget_after = id2;
				}
				
				if(id == id2){
					flag = true;
				}
				
				if(
					!flag &&
					id != id2
				){
					b2make.widget_before = id2;
				}
			}
		});
	}
	
	function conteiner_window_change(){
		$(b2make.conteiner_obj).css('width',$(window).width() - 2*b2make.widget_border);
	}
	
	function conteiners_update(){
		$(b2make.widget).each(function(){
			var type = $(this).myAttr('data-type');
			
			if(type == 'conteiner'){
				$(this).width($('#b2make-site').width());
			}
		});
		
		conteiner_banners_image_preview_update();
	}
	
	function conteiner_position(){
		if($("#"+b2make.menu_widgets+">li").length > 1){
			var cont = 0;
			
			$(b2make.won_position).show();
			$(b2make.won_position_up).show();
			$(b2make.won_position_down).show();
			
			$("#"+b2make.menu_widgets+">li").each(function(){
				var id = $(this).myAttr('data-id');
				
				if(id == b2make.conteiner_show){
					if(cont == 0){
						$(b2make.won_position_up).hide();
					}
					if(cont == $("#"+b2make.menu_widgets+">li").length - 1){
						$(b2make.won_position_down).hide();
					}
					return false;
				}
			
				cont++;
			});
		} else {
			$(b2make.won_position).hide();
		}
	}
	
	function conteiner_area_add(){
		var obj = b2make.conteiner_obj;
		var cont = $('<div class="b2make-widget"></div>');
		
		cont.myAttr('id','conteiner-area'+b2make.widgets_count);
		cont.myAttr('data-type','conteiner-area');
		
		cont.addClass('b2make-conteiner-area');
		cont.css('border-left',b2make.conteiner_border_style);
		cont.css('border-right',b2make.conteiner_border_style);
		//cont.css('backgroundColor','rgba(0,0,0,0.3);');
		
		$(obj).children().appendTo(cont);
		cont.appendTo($(obj));
		
		b2make.conteiner_obj_area = document.getElementById('conteiner-area'+b2make.widgets_count);
		
		$(obj).myAttr('data-area','conteiner-area'+b2make.widgets_count);
		$(obj).myAttr('data-area-largura',b2make.conteiner_area_width);
		
		cont.width(b2make.conteiner_area_width+'px');
		
		$('#b2make-conteiner-area-largura-lbl').show();
		$('#b2make-conteiner-area-largura').show();
		
		b2make.widgets_count++;
	}
	
	function conteiner_area_remove(p){
		if(!p) p = {};
		
		var obj = (p.obj ? p.obj : b2make.conteiner_obj);
		
		if($(obj).myAttr('data-area')){
			var id = $(obj).myAttr('data-area');
			
			$('#'+id).children().appendTo($(obj));
			$('#'+id).remove();
			
			$('#b2make-conteiner-area-largura-lbl').hide();
			$('#b2make-conteiner-area-largura').hide();
			
			$(obj).myAttr('data-area',null);
			
			b2make.conteiner_obj_area = false;
		}
	}
	
	// =========================== Conteiner Banners ====================
	
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
	
	function conteiner_banners_imagem_caixa_atualizar(p){
		var id = (p.id ? p.id : b2make.conteiner_banners_id_image);
		var obj_pai = (p.obj_pai ? p.obj_pai : b2make.conteiner_obj);
		var target = (p.target ? p.target.find('.b2make-conteiner-banners-image-cont').get(0) : $(obj_pai).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id).find('.b2make-conteiner-banners-image-cont').get(0));
		var obj_img = $(obj_pai).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id);
		
		if($(obj_img).myAttr('data-caixa-color-ahex')){
			$(target).css('background-color',$.jpicker_ahex_2_rgba($(obj_img).myAttr('data-caixa-color-ahex')));
		} else {
			$(target).css('background-color',$.jpicker_ahex_2_rgba('#0000009b'));
		}
		
		if($(obj_img).myAttr('data-titulo-color-ahex')){
			$(target).find('.b2make-conteiner-banners-image-titulo').css('color',$.jpicker_ahex_2_rgba($(obj_img).myAttr('data-titulo-color-ahex')));
		} else {
			$(target).find('.b2make-conteiner-banners-image-titulo').css('color',$.jpicker_ahex_2_rgba('#ffffffff'));
		}
		
		if($(obj_img).myAttr('data-sub-titulo-color-ahex')){
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('color',$.jpicker_ahex_2_rgba($(obj_img).myAttr('data-sub-titulo-color-ahex')));
		} else {
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('color',$.jpicker_ahex_2_rgba('#ffffffff'));
		}
		
		if($(obj_img).myAttr('data-titulo-topo')){
			$(target).css('top',$(obj_img).myAttr('data-titulo-topo')+'px');
		} else {
			$(target).css('top','100px');
		}
		
		if($(obj_img).myAttr('data-titulo-esquerda')){
			$(target).css('left',$(obj_img).myAttr('data-titulo-esquerda')+'px');
		} else {
			$(target).css('left','100px');
		}
		
		if($(obj_img).myAttr('data-titulo-padding')){
			$(target).css('padding',$(obj_img).myAttr('data-titulo-padding')+'px');
		} else {
			$(target).css('padding','15px');
		}
		
		if($(obj_img).myAttr('data-titulo-tamanho')){
			$(target).css('width',$(obj_img).myAttr('data-titulo-tamanho')+'px');
		} else {
			$(target).css('width','300px');
		}
		
		if($(obj_img).myAttr('data-titulo-font-family')){
			$.google_fonts_wot_load({
				family : $(obj_img).myAttr('data-titulo-font-family')
			});
			
			$(target).find('.b2make-conteiner-banners-image-titulo').css('fontFamily',$(obj_img).myAttr('data-titulo-font-family'));
		}
		
		if($(obj_img).myAttr('data-sub-titulo-font-family')){
			$.google_fonts_wot_load({
				family : $(obj_img).myAttr('data-sub-titulo-font-family')
			});
			
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('fontFamily',$(obj_img).myAttr('data-sub-titulo-font-family'));
		}
		
		if($(obj_img).myAttr('data-titulo-font-size')){
			$(target).find('.b2make-conteiner-banners-image-titulo').css('fontSize',$(obj_img).myAttr('data-titulo-font-size')+'px');
		} else {
			$(target).find('.b2make-conteiner-banners-image-titulo').css('fontSize','20px');
		}
		
		if($(obj_img).myAttr('data-sub-titulo-font-size')){
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('fontSize',$(obj_img).myAttr('data-sub-titulo-font-size')+'px');
		} else {
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('fontSize','15px');
		}
		
		if($(obj_img).myAttr('data-titulo-font-align')){
			$(target).find('.b2make-conteiner-banners-image-titulo').css('textAlign',$(obj_img).myAttr('data-titulo-font-align'));
		}
		
		if($(obj_img).myAttr('data-sub-titulo-font-align')){
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('textAlign',$(obj_img).myAttr('data-sub-titulo-font-align'));
		}
		
		if($(obj_img).myAttr('data-titulo-font-italico')){
			$(target).find('.b2make-conteiner-banners-image-titulo').css('fontStyle',($(obj_img).myAttr('data-titulo-font-italico') == 'sim' ? 'italic' : 'normal'));
		}
		
		if($(obj_img).myAttr('data-sub-titulo-font-italico')){
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('fontStyle',($(obj_img).myAttr('data-sub-titulo-font-italico') == 'sim' ? 'italic' : 'normal'));
		}
		
		if($(obj_img).myAttr('data-titulo-font-negrito')){
			$(target).find('.b2make-conteiner-banners-image-titulo').css('fontWeight',($(obj_img).myAttr('data-titulo-font-negrito') == 'sim' ? 'bold' : 'normal'));
		}
		
		if($(obj_img).myAttr('data-sub-titulo-font-negrito')){
			$(target).find('.b2make-conteiner-banners-image-sub-titulo').css('fontWeight',($(obj_img).myAttr('data-sub-titulo-font-negrito') == 'sim' ? 'bold' : 'normal'));
		}
		
	}
	
	function conteiner_banners_caixa_posicao_atualizar(p){
		var obj = (p.obj ? p.obj : b2make.conteiner_obj);
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

	function conteiner_banners_update(p){
		if(!p)p = {};
		
		switch(p.type){
			case 'banners-imagem-uploaded':
				var id = p.id;
				var dados = p.dados;
				var url = p.dados.imagem;
				
				$('.b2make-widget[data-type="conteiner"][data-banners-id="'+id+'"]').each(function(){
					if($(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').length >= 2 && (!$(this).myAttr('data-seta-visivel') || $(this).myAttr('data-seta-visivel') == 's')){
						$(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left').show();
						$(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right').show();
					}
					
					$(this).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-0').remove();
					
					var layout_dentro = '<div class="b2make-conteiner-banners-image-cont"><div class="b2make-conteiner-banners-image-titulo"></div><div class="b2make-conteiner-banners-image-sub-titulo"></div></div>';
					
					$(this).find('.b2make-conteiner-banners-holder').append($('<div id="b2make-conteiner-banners-imagem-'+dados.id+'" class="b2make-conteiner-banners-image" data-image-id="'+dados.id+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" style="background-image:url('+dados.imagem+');">'+layout_dentro+'</div>'));
					
					conteiner_banners_caixa_posicao_atualizar({obj:this});
				});
			break;
			case 'banners-imagem-del':
				var id = p.id;
				var id_banners = p.id_banners;
				var imagem = p.url;
				
				$('.b2make-conteiner-banners-image[data-image-id="'+id+'"]').each(function(){
					$(this).remove();
				});
				
				$('.b2make-widget[data-type="conteiner"][data-banners-id="'+id_banners+'"]').each(function(){
					if($(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').length < 2){
						$(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left').hide();
						$(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right').hide();
					}
					
					if($(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').length == 0){
						var imagem = location.href+'images/b2make-conteiner-banners-sem-imagem.png?v=2';
						$(this).find('.b2make-conteiner-banners-holder').append($('<div id="b2make-conteiner-banners-imagem-0" class="b2make-conteiner-banners-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
					}
					
					conteiner_banners_caixa_posicao_atualizar({obj:this});
				});
			break;
			case 'banners-del':
				var id = p.id;
				
				$('div.b2make-widget[data-type="conteiner"][data-banners-id="'+id+'"]').each(function(){
					conteiner_banners_destroy({conteiner_obj:this});
				});
			break;
			case 'banners-order':
				$('div.b2make-widget[data-type="conteiner"][data-banners-id="'+b2make.conteiner_banners_atual+'"]').each(function(){
					conteiner_banners_create({conteiner_child_obj:this,conteiner_banners_id:b2make.conteiner_banners_atual,order:true});
				});
			break;
			case 'banners-data-edit':
				var id = p.id;
				var url = p.url;
				
				$('.b2make-conteiner-banners-image[data-image-id="'+id+'"]').each(function(){
					var titulos_cont = $(this).find('.b2make-conteiner-banners-image-cont');
					
					var titulo = titulos_cont.find('.b2make-conteiner-banners-image-titulo').html();
					var sub_titulo = titulos_cont.find('.b2make-conteiner-banners-image-sub-titulo').html();
					
					if(
						titulo.length == 0 ||
						sub_titulo.length == 0
					){
						titulos_cont.hide();
					} else {
						titulos_cont.show();
					}
					
					if(url){
						$(this).myAttr('data-url',url);
					} else {
						$(this).removeAttr('data-url');
					}
				});
			break;
		}
	}

	function conteiner_banners_create(p){
		var plugin_id = 'conteiner-banners';
		if(!p)p = {};
		
		var id_func = 'conteiner-banners-images';
		var obj = (p.conteiner_obj ? p.conteiner_obj : b2make.conteiner_obj);
		var obj_id = $(obj).myAttr('id');
		
		$(obj).myAttr('data-banners-id',p.conteiner_banners_id);
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : p.conteiner_banners_id
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					var image_data = new Array();
					
					$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').each(function(){
						var img_id = $(this).myAttr('data-image-id');
						var img_arr = new Array();
						var data_ids = new Array(
							'data-sub-titulo-font-negrito',
							'data-titulo-font-negrito',
							'data-sub-titulo-font-italico',
							'data-titulo-font-italico',
							'data-sub-titulo-font-align',
							'data-titulo-font-align',
							'data-sub-titulo-font-size',
							'data-titulo-font-size',
							'data-sub-titulo-font-family',
							'data-titulo-font-family',
							'data-titulo-tamanho',
							'data-titulo-padding',
							'data-titulo-esquerda',
							'data-titulo-topo',
							'data-sub-titulo-color-ahex',
							'data-titulo-color-ahex',
							'data-caixa-color-ahex'
						);
						
						for(var i=0;i<data_ids.length;i++){
							if($(this).myAttr(data_ids[i])){
								img_arr.push({
									id:data_ids[i],
									valor:$(this).myAttr(data_ids[i])
								});
							}
						}
						
						image_data[img_id] = img_arr;
					});
					
					$(obj).find('.b2make-conteiner-banners-holder').remove();
					$(obj).append('<div class="b2make-conteiner-banners-holder"><div class="b2make-conteiner-banners-seta-left"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-conteiner-banners-arrow-2.svg?v=1"></div><div class="b2make-conteiner-banners-seta-right"><img class="svg" src="'+document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+b2make.path+'/images/b2make-conteiner-banners-arrow.svg?v=1"></div></div>');
					
					jQuery('img.svg').each(function(){
						var $img = jQuery(this);
						var imgID = $img.myAttr('id');
						var imgClass = $img.myAttr('class');
						var imgStyle = $img.myAttr('style');
						var imgURL = $img.myAttr('src');

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
							
							var pai = $svg.parent().parent().parent();
							var cor = pai.myAttr('data-seta-color-ahex');
							
							if(!cor){
								cor = 'ffffffff';
							}
							
							var cor_rgba = $.jPicker.ColorMethods.hexToRgba(cor);
							
							$svg.find('polyline').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
							conteiner_banners_caixa_posicao_atualizar({});
						}, 'xml');
					});
					
					$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left').hide();
					$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right').hide();
					
					switch(dados.status){
						case 'Ok':
							if(dados.images.length >= 2){
								$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left').show();
								$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right').show();
							}
							
							if(dados.images.length == 0){
								var imagem = location.href+'images/b2make-conteiner-banners-sem-imagem.png?v=2';
								$(obj).find('.b2make-conteiner-banners-holder').append($('<div id="b2make-conteiner-banners-imagem-0" class="b2make-conteiner-banners-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
							} else {
								for(var i=0;i<dados.images.length;i++){
									var layout_dentro = '<div class="b2make-conteiner-banners-image-cont" style="'+(dados.images[i].titulo.length != 0 && dados.images[i].sub_titulo.length != 0 ? 'display:block;': '')+'"><div class="b2make-conteiner-banners-image-titulo">'+dados.images[i].titulo+'</div><div class="b2make-conteiner-banners-image-sub-titulo">'+conteiner_banners_textarea_to_texto(dados.images[i].sub_titulo)+'</div></div>';
									
									$(obj).find('.b2make-conteiner-banners-holder').append($('<div'+(dados.images[i].url ?' data-url="'+dados.images[i].url+'"':'')+' id="b2make-conteiner-banners-imagem-'+dados.images[i].id+'" class="b2make-conteiner-banners-image" data-image-id="'+dados.images[i].id+'" data-image-url="'+dados.images[i].imagem+'" data-image-width="'+dados.images[i].width+'" data-image-height="'+dados.images[i].height+'" style="background-image:url('+dados.images[i].imagem+');">'+layout_dentro+'</div>'));
									
									if(image_data){
										if(image_data[dados.images[i].id]){
											var img_atual = $(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+dados.images[i].id);
											var img_arr_data = image_data[dados.images[i].id];
											
											for(var j=0;j<img_arr_data.length;j++){
												img_atual.myAttr(img_arr_data[j].id,img_arr_data[j].valor);
											}
										}
									}
									
									conteiner_banners_imagem_caixa_atualizar({
										id: dados.images[i].id,
										obj_pai: obj
									});
								}
								
								conteiner_banners_caixa_posicao_atualizar({criar:true,order:p.order});
								
								if(b2make.conteiner_banners_widget_added){
									$('#b2make-'+plugin_id+'-callback').trigger('conteiner_child_open');
									b2make.conteiner_banners_widget_added = false;
								}
								
								b2make.conteiner_banners_images_atual = dados.images;
							}
						break;
						case 'NaoExisteId':
							var imagem = location.href+'images/b2make-conteiner-banners-sem-imagem.png?v=2';
							$(obj).find('.b2make-conteiner-banners-holder').append($('<div id="b2make-conteiner-banners-imagem-0" class="b2make-conteiner-banners-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
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
	
	function conteiner_banners_destroy(p){
		if(!p)p = {};
		
		var obj = (p.conteiner_obj ? p.conteiner_obj : b2make.conteiner_obj);
		
		$(obj).find('.b2make-conteiner-banners-holder').remove();
		$(obj).removeAttr('data-banners-id');
	}
	
	function conteiner_banners_animation_proximo(p){
		var obj = p.obj;
		var inverso = p.inverso;
		var interacao = b2make.conteiner_animation_interacao[$(obj).myAttr('id')];
		var tempo_exposicao = ($(obj).myAttr('data-tempo-exposicao') ? parseInt($(obj).myAttr('data-tempo-exposicao')) : 3000);
		
		setTimeout(function(){
			if(interacao == b2make.conteiner_animation_interacao[$(obj).myAttr('id')])
				conteiner_banners_animation_start({obj:obj,inverso:inverso});
		},tempo_exposicao);
	}

	function conteiner_banners_animation_start(p){
		var obj = p.obj;
		
		if(b2make.conteiner_banners_start[$(obj).myAttr('id')]){
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
						
						conteiner_banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
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
						
						conteiner_banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
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
						
						conteiner_banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
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
						
						conteiner_banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
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
						
						conteiner_banners_caixa_posicao_atualizar({proximo:proximo,obj:obj});
						conteiner_banners_animation_proximo({obj:obj,inverso:inverso});
					});
				break;
				
			}
		}
	}

	function conteiner_banners_animation_stop(obj){
		$(obj).myAttr('data-animation',null);
		$(obj).find('div.b2make-widget-out').find('div.b2make-conteiner-banners-holder').stop();
	}

	function conteiner_banners_images_select(){
		var obj_selected = b2make.conteiner_banners_imagem_selected;
		var obj_target = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
		var image_url = obj_selected.myAttr('data-image-url');
		var image_id = obj_selected.myAttr('data-image-id');
		var image_width = obj_selected.myAttr('data-image-width');
		var image_height = obj_selected.myAttr('data-image-height');
		
		$(obj_target).find('div.b2make-widget-out')
			.find('div.b2make-conteiner-banners-holder')
			.find('div.b2make-conteiner-banners-image[id="b2make-conteiner-banners-imagem-'+b2make.conteiner_banners_atual+'"]')
			.css('backgroundImage','url('+image_url+')')
			.myAttr('data-banners-imagem-id',image_id)
			.myAttr('data-banners-imagem-width',image_width)
			.myAttr('data-banners-imagem-height',image_height);
		
		var image = $(obj_target).find('div.b2make-widget-out')
			.find('div.b2make-conteiner-banners-holder')
			.find('div.b2make-conteiner-banners-image[id="b2make-conteiner-banners-imagem-'+b2make.conteiner_banners_atual+'"]');

		var target = image.find('.b2make-conteiner-banners-widget-titulo');
		var imagem_width = parseInt(image.myAttr('data-banners-imagem-width'));
		var imagem_height = parseInt(image.myAttr('data-banners-imagem-height'));
		var conteiner_width = parseInt($('#b2make-woaf-imagem-val').val());
		
		//var altura = Math.floor((conteiner_width * imagem_height) / imagem_width);
		var altura = conteiner_width;
		
		target.css('top',(b2make.conteiner_banners.margin_title+altura)+'px');
	}

	function conteiner_banners_images_html(dados){
		$('#b2make-conteiner-banners-lista-images').append($('<div id="b2make-conteiner-banners-imagem-holder-'+dados.id+'" class="b2make-conteiner-banners-image-holder b2make-tooltip" style="background-image:url('+dados.mini+');" data-image-id="'+dados.id+'" data-titulo="'+(dados.titulo ? dados.titulo : '')+'" data-sub-titulo="'+(dados.sub_titulo ? dados.sub_titulo : '')+'" data-url="'+(dados.url ? dados.url : '')+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" title="'+b2make.msgs.conteinerBannersFile+': '+dados.file+'"><div class="b2make-conteiner-banners-data-edit b2make-tooltip" title="'+b2make.msgs.conteinerBannersEditX+'"></div><div class="b2make-conteiner-banners-image-delete b2make-tooltip" title="'+b2make.msgs.conteinerBannersDeleteX+'"></div></div>'));
	}

	function conteiner_banners_images(){
		var id_func = 'conteiner-banners-images';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.conteiner_banners_atual
			},
			beforeSend: function(){
				$('#b2make-conteiner-banners-lista-images').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-conteiner-banners-lista-images');
				b2make.conteiner_banners_mask = false;
			},
			success: function(txt){
				$('#b2make-conteiner-banners-lista-images').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							for(var i=0;i<dados.images.length;i++){
								conteiner_banners_images_html(dados.images[i]);
							}
							
							b2make.conteiner_banners_images_atual = dados.images;
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							
							if(b2make.conteiner_banners_widget_update){
								conteiner_banners_update({type:'banners-del',id:b2make.conteiner_banners_widget_update_id});
								b2make.conteiner_banners_widget_update_id = false;
								b2make.conteiner_banners_widget_update = false;
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
				$('#b2make-conteiner-banners-lista-images').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}

	function conteiner_banners_imagens_delete(){
		var id = b2make.conteiner_banners_imagens_delete_id;
		var id_func = 'conteiner-banners-images-del';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : id,
				banners : b2make.conteiner_banners_atual
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
							var url = $('.b2make-conteiner-banners-image-holder[data-image-id="'+id+'"]').myAttr('data-image-url');
							
							$('.b2make-conteiner-banners-image-holder[data-image-id="'+id+'"]').remove();
							$.disk_usage_diskused_del(dados.size);
							conteiner_banners_update({type:'banners-imagem-del',id:id,id_banners:b2make.conteiner_banners_atual,url:url});
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

	function conteiner_banners_menu_html(dados){
		if(!dados)dados = {};
		$('#b2make-conteiner-banners-lista-banners').prepend($('<div class="b2make-conteiner-banners-lista-banners"><div class="b2make-conteiner-banners-show b2make-tooltip" title="'+b2make.msgs.conteinerBannersShow+'" data-status="'+(dados.conteiner_banners_show ? 'show' : 'not-show')+'" data-banners-id="'+dados.conteiner_banners_id+'"></div><div class="b2make-conteiner-banners-nome b2make-tooltip" title="'+b2make.msgs.conteinerBannersNome+'" data-status="'+(dados.conteiner_banners_selected ? 'show' : 'not-show')+'" data-banners-id="'+dados.conteiner_banners_id+'">'+dados.conteiner_banners_nome+'</div><div class="b2make-conteiner-banners-edit b2make-tooltip" data-banners-id="'+dados.conteiner_banners_id+'" title="'+b2make.msgs.conteinerBannersEdit+'"></div><div class="b2make-conteiner-banners-delete b2make-tooltip" data-banners-id="'+dados.conteiner_banners_id+'" title="'+b2make.msgs.conteinerBannersDelete+'"></div><div class="clear"></div></div>'));
	}

	function conteiner_banners_image_preview_open(){
		if(b2make.conteiner_banners_preview_cont)b2make.conteiner_banners_preview_cont.fadeIn(150);
	}
	
	function conteiner_banners_image_preview_close(){
		if(b2make.conteiner_banners_preview_cont)b2make.conteiner_banners_preview_cont.fadeOut(150);
	}
	
	function conteiner_banners_image_preview_update(){
		if(b2make.conteiner_banners_preview_cont){
			var border_width = 4;
			var conteiner_obj = $(b2make.conteiner_obj);
			var obj = b2make.conteiner_banners_preview_cont;
			
			var top = ($(window).height() - conteiner_obj.height())/2; if(top < 0) top = 0;
			var left = ($(window).width() - conteiner_obj.width())/2; if(left < 0) left = 0;
			
			obj.width(conteiner_obj.width() - 2*border_width);
			obj.height(conteiner_obj.height() - 2*border_width);
			obj.css('top',top);
			obj.css('left',left);
		}
	}
	
	function conteiner_banners_textarea_to_texto(valor){
		if(valor){
			valor = valor.replace(/\r\n|\r|\n/g,"<br>");
			valor = valor.replace(/  /g,"&nbsp;&nbsp;");
			return valor;
		} else {
			return '';
		}
	}
	
	function conteiner_banners_texto_to_textarea(valor){
		if(valor){
			valor = valor.replace(/<br>/gi,"\n");
			valor = valor.replace(/&nbsp;/gi," ");
			return valor;
		} else {
			return '';
		}
	}
	
	function conteiner_banners_image_preview(){
		var images = b2make.conteiner_banners_images_atual;
		var id = b2make.conteiner_banners_id_image;
		
		if(!b2make.conteiner_banners_preview_cont){
			b2make.conteiner_banners_preview_cont = $('<div id="b2make-conteiner-banners-preview-cont"><div id="b2make-conteiner-banners-preview-close"></div></div>');
			b2make.conteiner_banners_preview_cont.appendTo('body');
			b2make.conteiner_banners_preview_cont.hide();
		}
		
		conteiner_banners_image_preview_update();
		conteiner_banners_image_preview_open();
		var obj = b2make.conteiner_banners_preview_cont;
		
		obj.find('.b2make-conteiner-banners-image').remove();
	
		for(var i=0;i<images.length;i++){
			if(id == images[i].id){
				var layout_dentro = '<div class="b2make-conteiner-banners-image-cont" style="'+($('#b2make-fcb-titulo').val() || $('#b2make-fcb-sub-titulo').val() ? 'display:block;': '')+'"><div class="b2make-conteiner-banners-image-titulo">'+$('#b2make-fcb-titulo').val()+'</div><div class="b2make-conteiner-banners-image-sub-titulo">'+conteiner_banners_textarea_to_texto($('#b2make-fcb-sub-titulo').val())+'</div></div>';
				
				obj.append($('<div id="b2make-conteiner-banners-imagem-'+images[i].id+'" class="b2make-conteiner-banners-image" data-image-id="'+images[i].id+'" data-image-url="'+images[i].imagem+'" data-image-width="'+images[i].width+'" data-image-height="'+images[i].height+'" style="background-image:url('+images[i].imagem+');">'+layout_dentro+'</div>'));
			
				conteiner_banners_imagem_caixa_atualizar({target:obj.find('#b2make-conteiner-banners-imagem-'+images[i].id)});
			}
		}
		
	}
	
	function conteiner_banners_dados_edit(id){
		b2make.id_site_banners = b2make.conteiner_banners_id_image = id;
		
		$.dialogbox_open({
			width:460,
			height:400,
			message:true,
			more_buttons: new Array({
				calback: 'b2make-conteiner-banners-image-edit-calback',
				title: 'Preview',
				before: true,
				dont_close: true
			}),
			calback_yes: 'b2make-conteiner-banners-data-edit-calback',
			title: b2make.msgs.conteinerBannersEditDataTitle,
			coneiner: 'b2make-formulario-conteiner-banners-dados'
		});
		
		$('#b2make-fcb-titulo').val($('#b2make-conteiner-banners-imagem-holder-'+id).myAttr('data-titulo'));
		$('#b2make-fcb-sub-titulo').val(conteiner_banners_texto_to_textarea($('#b2make-conteiner-banners-imagem-holder-'+id).myAttr('data-sub-titulo')));
		$('#b2make-fcb-url').val($('#b2make-conteiner-banners-imagem-holder-'+id).myAttr('data-url'));
		
		
		var obj_pai = b2make.conteiner_obj;
		var obj = $(obj_pai).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id).get(0);
		
		if($(obj).myAttr('data-titulo-topo')){
			$('#b2make-woc-banners-titulo-topo').val($(obj).myAttr('data-titulo-topo'));
		} else {
			$('#b2make-woc-banners-titulo-topo').val('100');
		}
		
		if($(obj).myAttr('data-titulo-esquerda')){
			$('#b2make-woc-banners-titulo-esquerda').val($(obj).myAttr('data-titulo-esquerda'));
		} else {
			$('#b2make-woc-banners-titulo-esquerda').val('100');
		}
		
		if($(obj).myAttr('data-titulo-tamanho')){
			$('#b2make-woc-banners-titulo-tamanho').val($(obj).myAttr('data-titulo-tamanho'));
		} else {
			$('#b2make-woc-banners-titulo-tamanho').val('200');
		}
		
		if($(obj).myAttr('data-titulo-padding')){
			$('#b2make-woc-banners-titulo-padding').val($(obj).myAttr('data-titulo-padding'));
		} else {
			$('#b2make-woc-banners-titulo-padding').val('15');
		}
		
		if($(obj).myAttr('data-caixa-color-ahex')){
			$('#b2make-woc-banners-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
			$('#b2make-woc-banners-caixa-cor-val').myAttr('data-ahex',$(obj).myAttr('data-caixa-color-ahex'));
		} else {
			$('#b2make-woc-banners-caixa-cor-val').css('background-color','transparent');
			$('#b2make-woc-banners-caixa-cor-val').myAttr('data-ahex',false);
		}
		
		if($(obj).myAttr('data-titulo-color-ahex')){
			$('#b2make-woc-banners-titulo-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-color-ahex')));
			$('#b2make-woc-banners-titulo-cor-val').myAttr('data-ahex',$(obj).myAttr('data-titulo-color-ahex'));
		} else {
			$('#b2make-woc-banners-titulo-cor-val').css('background-color','#000000');
			$('#b2make-woc-banners-titulo-cor-val').myAttr('data-ahex','#000000ff');
		}
		
		if($(obj).myAttr('data-sub-titulo-color-ahex')){
			$('#b2make-woc-banners-sub-titulo-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-sub-titulo-color-ahex')));
			$('#b2make-woc-banners-sub-titulo-cor-val').myAttr('data-ahex',$(obj).myAttr('data-sub-titulo-color-ahex'));
		} else {
			$('#b2make-woc-banners-sub-titulo-cor-val').css('background-color','#000000');
			$('#b2make-woc-banners-sub-titulo-cor-val').myAttr('data-ahex','#000000ff');
		}
		
		var types = new Array('titulo','sub-titulo');
		
		for(var i=0;i<types.length;i++){
			var type = types[i];
			var tamanho;
			
			switch(type){
				case 'titulo': tamanho = 20; break;
				case 'sub-titulo': tamanho = 15; break;
			}
			
			if($(obj).myAttr('data-'+type+'-font-family')){
				$('#b2make-woc-banners-'+type+'-text-cont').find('.b2make-fonts-holder').css({
					'fontFamily': $(obj).myAttr('data-'+type+'-font-family')
				});
				$('#b2make-woc-banners-'+type+'-text-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-'+type+'-font-family'));
			} else {
				$('#b2make-woc-banners-'+type+'-text-cont').find('.b2make-fonts-holder').css({
					'fontFamily': 'Roboto Condensed'
				});
				$('#b2make-woc-banners-'+type+'-text-cont').find('.b2make-fonts-holder').html('Roboto Condensed');
			}
			
			if($(obj).myAttr('data-'+type+'-font-size')){
				$('#b2make-woc-banners-'+type+'-text-cont').find('.b2make-fonts-size').val($(obj).myAttr('data-'+type+'-font-size'));
			} else {
				$('#b2make-woc-banners-'+type+'-text-cont').find('.b2make-fonts-size').val(tamanho);
			}
		}
	}
	
	function conteiner_banners_dados_edit_base(){
		$.dialogbox_close();
		
		var opcao = 'conteiner-banners-data-edit';
		var id = b2make.id_site_banners;
		
		$('#b2make-conteiner-banners-imagem-holder-'+id).myAttr('data-titulo',$('#b2make-fcb-titulo').val());
		$('#b2make-conteiner-banners-imagem-holder-'+id).myAttr('data-sub-titulo',conteiner_banners_textarea_to_texto($('#b2make-fcb-sub-titulo').val()));
		$('#b2make-conteiner-banners-imagem-holder-'+id).myAttr('data-url',$('#b2make-fcb-url').val());
		
		$('.b2make-conteiner-banners-image').each(function(){
			if($(this).myAttr('data-image-id') == id){
				$(this).find('.b2make-conteiner-banners-image-cont').find('.b2make-conteiner-banners-image-titulo').html($('#b2make-fcb-titulo').val());
				$(this).find('.b2make-conteiner-banners-image-cont').find('.b2make-conteiner-banners-image-sub-titulo').html(conteiner_banners_textarea_to_texto($('#b2make-fcb-sub-titulo').val()));
			}
		});
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				titulo : $('#b2make-fcb-titulo').val(),
				sub_titulo : $('#b2make-fcb-sub-titulo').val(),
				url : $('#b2make-fcb-url').val(),
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
							conteiner_banners_update({type:'banners-data-edit',id:id,url:$('#b2make-fcb-url').val()});
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

	function conteiner_banners_add(){
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-conteiner-banners-add-calback',
			title: b2make.msgs.conteinerBannersAddTitle,
			coneiner: 'b2make-formulario-conteiner-banners'
		});
	}

	function conteiner_banners_add_base(){
		var id_func = 'conteiner-banners-add';
		var form_id = 'b2make-formulario-conteiner-banners';
		
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
								$('.b2make-conteiner-banners-show').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								dados.conteiner_banners_show = true;
								dados.conteiner_banners_selected = true;
								conteiner_banners_menu_html(dados);
								$('.b2make-tooltip').tooltip({
									show: {
										effect: "fade",
										delay: 400
									}
								});
								$.dialogbox_close();
								
								b2make.conteiner_banners_atual = dados.conteiner_banners_id;
								b2make.conteiner_banners_nome = dados.conteiner_banners_nome;
								
								$('#b2make-conteiner-banners-btn').show();
								$('#b2make-conteiner-banners-lista-images').html('');
								
								conteiner_banners_create({conteiner_banners_id:b2make.conteiner_banners_atual});
								
								if(!b2make.conteiner_banners_todos_ids)b2make.conteiner_banners_todos_ids = new Array();
								b2make.conteiner_banners_todos_ids.push(dados.conteiner_banners_id);
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

	function conteiner_banners_edit(id){
		$('#b2make-formulario-conteiner-banners #b2make-fcb-nome').val($('.b2make-conteiner-banners-nome[data-banners-id="'+id+'"]').html());
		
		b2make.conteiner_banners_edit_id = id;
		
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-conteiner-banners-edit-calback',
			title: b2make.msgs.conteinerBannersEditTitle,
			coneiner: 'b2make-formulario-conteiner-banners'
		});
	}

	function conteiner_banners_edit_base(){
		var id_func = 'conteiner-banners-edit';
		var form_id = 'b2make-formulario-conteiner-banners';
		var id = b2make.conteiner_banners_edit_id;
		
		b2make.conteiner_banners_edit_id = false;
		
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
								$('.b2make-conteiner-banners-nome[data-banners-id="'+id+'"]').html(dados.nome);
								
								conteiner_banners_update({type:'banners-edit',id:id,nome:dados.nome});
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

	function conteiner_banners_del(id){
		b2make.conteiner_banners_del_id = id;
		
		var msg = b2make.msgs.conteinerBannersDelTitle;
		msg = msg.replace(/#banners#/gi,$('.b2make-conteiner-banners-nome[data-banners-id="'+id+'"]').html());
		
		$.dialogbox_open({
			confirm:true,
			calback_yes: 'b2make-conteiner-banners-del-calback',
			msg: msg
		});
	}

	function conteiner_banners_del_base(){
		var id_func = 'conteiner-banners-del';
		var id = b2make.conteiner_banners_del_id;
		
		b2make.conteiner_banners_del_id = false;

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
							$('.b2make-conteiner-banners-delete[data-banners-id="'+id+'"]').parent().remove();
							$.dialogbox_close();
							
							var id_aux = $('#b2make-conteiner-banners-lista-banners .b2make-conteiner-banners-lista-banners:first-child .b2make-conteiner-banners-show').myAttr('data-banners-id');
							
							$('#b2make-conteiner-banners-lista-images').html('');
							
							if(id_aux){
								b2make.conteiner_banners_atual = id_aux;
								b2make.conteiner_banners_nome = $('.b2make-conteiner-banners-nome[data-banners-id="'+id_aux+'"]').html();
								
								$('.b2make-conteiner-banners-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								$('.b2make-conteiner-banners-nome[data-banners-id="'+id_aux+'"]').myAttr('data-status','show');
								
								conteiner_banners_images();
								$('#b2make-conteiner-banners-btn').show();
							} else {
								$('#b2make-conteiner-banners-btn').hide();
							}
							
							$.disk_usage_diskused_del(dados.size);
							conteiner_banners_update({type:'banners-del',id:id});
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
	
	function conteiner_banners_upload_params(){
		return new Array({
			variavel : 'banners',
			valor : b2make.conteiner_banners_atual,
		})
	}
	
	function conteiner_banners_upload_callback(dados){
		var id_func = 'conteiner-banners';
		
		switch(dados.status){
			case 'Ok':
				conteiner_banners_images_html(dados);
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				$.disk_usage_diskused_add(dados.size);
				conteiner_banners_update({type:'banners-imagem-uploaded',id:b2make.conteiner_banners_atual,dados:dados});
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function conteiner_banners_upload(){
		$.upload_files_start({
			url_php : 'uploadbanners.php',
			input_selector : '#b2make-conteiner-banners-input',
			file_type : 'imagem',
			post_params : conteiner_banners_upload_params,
			callback : conteiner_banners_upload_callback
		});
	}
	
	function conteiner_banners(){
		b2make.conteiner_banners_start = new Array();

		var plugin_id = 'conteiner-banners';
		
		b2make.conteiner_banners = {};
		
		b2make.conteiner_banners.fator_ajuste = 0.8;
		b2make.conteiner_banners.margin_title = 4;
		b2make.conteiner_banners.margin_image = 0;
		
		if(!b2make.msgs.conteinerBannersDeleteX)b2make.msgs.conteinerBannersDeleteX = 'Clique para excluir esta imagem';
		if(!b2make.msgs.conteinerBannersEditX)b2make.msgs.conteinerBannersEditX = 'Clique para editar os dados desta imagem';
		if(!b2make.msgs.conteinerBannersFile)b2make.msgs.conteinerBannersFile = 'Arquivo';
		if(!b2make.msgs.conteinerBannersEdit)b2make.msgs.conteinerBannersEdit = 'Clique para Editar o Nome deste banner';
		if(!b2make.msgs.conteinerBannersNome)b2make.msgs.conteinerBannersNome = 'Clique para alterar as imagens deste banner';
		if(!b2make.msgs.conteinerBannersDelete)b2make.msgs.conteinerBannersDelete = 'Clique para deletar este banner.';
		if(!b2make.msgs.conteinerBannersShow)b2make.msgs.conteinerBannersShow = 'Clique para selecionar este banner no widget banners.';
		if(!b2make.msgs.conteinerBannersDelTitle)b2make.msgs.conteinerBannersDelTitle = 'Tem certeza que deseja excluir <b>#banners#</b>?';
		if(!b2make.msgs.conteinerBannersEditTitle)b2make.msgs.conteinerBannersEditTitle = 'Editar Nome do banners';
		if(!b2make.msgs.conteinerBannersAddTitle)b2make.msgs.conteinerBannersAddTitle = 'Adicionar banners';
		if(!b2make.msgs.conteinerBannersEditDataTitle)b2make.msgs.conteinerBannersEditDataTitle = 'Editar dados da imagem';
		if(!b2make.wo_conteiner_banners_titulo_max_value)b2make.wo_conteiner_banners_titulo_max_value = 999;
		if(!b2make.wo_conteiner_banners_titulo_min_value)b2make.wo_conteiner_banners_titulo_min_value = 0;
		if(!b2make.wo_conteiner_banners_animation_max_value)b2make.wo_conteiner_banners_animation_max_value = 99999;
		if(!b2make.wo_conteiner_banners_animation_min_value)b2make.wo_conteiner_banners_animation_min_value = 0;
		
		b2make.conteiner_animation_interacao = new Array();
		
		$('.b2make-widget[data-type="conteiner"]').each(function(){
			var types = new Array('titulo','sub-titulo');
	
			for(var i=0;i<types.length;i++){
				if($(this).myAttr('data-google-font-'+types[i]) == 'sim'){
					$.google_fonts_wot_load({
						family : $(this).myAttr('data-'+types[i]+'-font-family'),
						nao_carregamento : true
					});
				}
			}
			
			var obj = this;
			
			b2make.conteiner_animation_interacao[$(obj).myAttr('id')] = 0;
			conteiner_banners_animation_proximo({obj:obj});
		});
		
		$('#b2make-conteiner-banners-btn').hide();
		
		conteiner_banners_upload();
		
		b2make.conteiner_banners_confirm_delete = true;
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
							var conteiner_banners_show,conteiner_banners_selected;
							var conteiner_banners_todos_ids = new Array();
							
							for(var i=0;i<dados.resultado.length;i++){
								conteiner_banners_show = false;
								conteiner_banners_selected = false;
								
								if(i==dados.resultado.length - 1){
									b2make.conteiner_banners_atual = dados.resultado[i].id_site_banners;
									b2make.conteiner_banners_nome = dados.resultado[i].nome;
									conteiner_banners_selected = true;
									conteiner_banners_show = true;
									conteiner_banners_images();
									$('#b2make-conteiner-banners-btn').show();
								}
								
								conteiner_banners_menu_html({
									conteiner_banners_selected:conteiner_banners_selected,
									conteiner_banners_show:conteiner_banners_show,
									conteiner_banners_id:dados.resultado[i].id_site_banners,
									conteiner_banners_nome:dados.resultado[i].nome
								});
								
								if(!b2make.conteiner_banners_todos_ids){
									conteiner_banners_todos_ids.push(dados.resultado[i].id_site_banners);
								}
							}
							
							if(!b2make.conteiner_banners_todos_ids){
								b2make.conteiner_banners_todos_ids = conteiner_banners_todos_ids;
							}
							
							if(b2make.conteiner_banners_widget_added)conteiner_banners_create({conteiner_banners_id:b2make.conteiner_banners_atual});
							b2make.conteiner_banners_widget_added_2 = true;
							
							
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
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-image-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var msg = b2make.msgs.imagemDelete;
			
			b2make.conteiner_banners_imagens_delete_id = $(this).parent().myAttr('data-image-id');
			
			if(b2make.conteiner_banners_confirm_delete){
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-conteiner-banners-image-delete-yes',
					msg: msg
				});
			} else {
				conteiner_banners_imagens_delete();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-image-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_banners_imagens_delete();
		});
		
		$('#b2make-conteiner-banners-confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').myAttr("checked", !$(this).find('input').myAttr("checked"));
			
			if($(this).find('input').myAttr("checked")){
				b2make.conteiner_banners_confirm_delete = true;
			} else {
				b2make.conteiner_banners_confirm_delete = false;
			}
		});
		
		$('#b2make-conteiner-banners-confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).myAttr("checked")){
				b2make.conteiner_banners_confirm_delete = false;
			} else {
				b2make.conteiner_banners_confirm_delete = true;
			}
		});
		
		$('#b2make-conteiner-banners-add').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_banners_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_banners_add_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-show',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-banners-id');
			
			b2make.conteiner_banners_mask = false;
			
			if($(this).myAttr('data-status') == 'not-show'){
				$('.b2make-conteiner-banners-show').each(function(){
					$(this).myAttr('data-status','not-show');
				});
				
				$(this).myAttr('data-status','show');
				
				conteiner_banners_create({conteiner_banners_id:id});
				
				$('.b2make-conteiner-banners-nome').each(function(){
					$(this).myAttr('data-status','not-show');
				});
				
				var nome_obj = $(this).parent().find('.b2make-conteiner-banners-nome');
				
				nome_obj.myAttr('data-status','show');
				
				var id = nome_obj.myAttr('data-banners-id');
				
				b2make.conteiner_banners_atual = nome_obj.myAttr('data-banners-id');
				b2make.conteiner_banners_nome = nome_obj.html();
				
				$('#b2make-conteiner-banners-lista-images').html('');
				conteiner_banners_images();
				
				b2make.conteiner_banners_menu_atual = id;
			} else {
				$(this).myAttr('data-status','not-show');
				
				conteiner_banners_destroy({});
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-nome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$('.b2make-conteiner-banners-nome').each(function(){
				$(this).myAttr('data-status','not-show');
			});
			
			$(this).myAttr('data-status','show');
			
			var id = $(this).myAttr('data-banners-id');
			
			b2make.conteiner_banners_atual = $(this).myAttr('data-banners-id');
			b2make.conteiner_banners_nome = $(this).html();
			
			$('#b2make-conteiner-banners-lista-images').html('');
			conteiner_banners_images();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-data-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).parent().myAttr('data-image-id');
			
			conteiner_banners_dados_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-data-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			conteiner_banners_dados_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-image-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			conteiner_banners_image_preview();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-banners-id');
			conteiner_banners_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_banners_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-banners-id');
			conteiner_banners_del(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-del-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_banners_del_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-banners-image-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			conteiner_banners_image_mouseup();
		});
		
		$(window).on('mouseup tap',function(e){
			conteiner_banners_image_mouseup();
		});
		
		$(document.body).on('mousedown','.b2make-conteiner-banners-image-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if($(e.target).hasClass('b2make-conteiner-banners-image-delete') || $(e.target).hasClass('b2make-conteiner-banners-data-edit')) return false;
			e.stopPropagation();
			var obj = this;
			
			b2make.conteiner_banners_image_holder_mouseup = false;
			
			b2make.conteiner_banners_image_holder_mousedown = true;
			conteiner_banners_image_order_start(obj,e);
		});
		
		$(document.body).on('taphold','.b2make-conteiner-banners-image-holder',function(e){
			e.stopPropagation();
			
			b2make.conteiner_banners_image_holder_mouseup = false;
			b2make.conteiner_banners_image_holder_mousedown = true;
			
			conteiner_banners_image_order_start(this,e);
		});
		
		function conteiner_banners_image_order_start(obj,e){
			if(b2make.conteiner_banners_image_holder_mouseup)return;
			
			var top = $(obj).offset().top;
			var left = $(obj).offset().left;
			var mx = e.pageX - left;
			var my = e.pageY - top;
			
			$(obj).css('position','absolute');
			$(obj).css('zIndex','999');
			
			b2make.conteiner_banners_image_holder_mousemove = true;
			b2make.conteiner_banners_image_holder_obj = obj;
			b2make.conteiner_banners_image_holder_obj_x = mx;
			b2make.conteiner_banners_image_holder_obj_y = my;
			b2make.conteiner_banners_image_holder_obj_w = parseInt($(obj).outerWidth(true));
			b2make.conteiner_banners_image_holder_obj_h = parseInt($(obj).outerHeight(true));
			
			var mx_start = e.pageX - $('#b2make-conteiner-banners-lista-images').offset().left;
			var my_start = e.pageY - $('#b2make-conteiner-banners-lista-images').offset().top;
			
			b2make.conteiner_banners_image_holder_coluna = Math.floor((mx_start / b2make.conteiner_banners_image_holder_obj_w));
			b2make.conteiner_banners_image_holder_linha = Math.floor((my_start / b2make.conteiner_banners_image_holder_obj_h));
			
			conteiner_banners_image_order_grid(b2make.conteiner_banners_image_holder_coluna,b2make.conteiner_banners_image_holder_linha);
			
			mx_start = mx_start - b2make.conteiner_banners_image_holder_obj_x;
			my_start = my_start - b2make.conteiner_banners_image_holder_obj_y;
			
			$(obj).css('left',mx_start);
			$(obj).css('top',my_start);
		}
		
		$(window).on('mousemove touchmove',function(e){
			if(b2make.conteiner_banners_image_holder_mousemove){
				var holder = '#b2make-conteiner-banners-lista-images';
				var ajuste_x = 0;
				var obj = b2make.conteiner_banners_image_holder_obj;
				var mx = e.pageX - $(holder).offset().left;
				var my = e.pageY - $(holder).offset().top;
				
				if(mx < 0)mx = 0; if(mx > $(holder).width()) mx = $(holder).width();
				if(my < 0)my = 0; if(my > $(holder).height()) my = $(holder).height();
				
				$(obj).css('left',mx - b2make.conteiner_banners_image_holder_obj_x + ajuste_x);
				$(obj).css('top',my - b2make.conteiner_banners_image_holder_obj_y);
				
				var coluna = Math.floor((mx / b2make.conteiner_banners_image_holder_obj_w));
				var linha = Math.floor((my / b2make.conteiner_banners_image_holder_obj_h));
				
				if(
					b2make.conteiner_banners_image_holder_linha != linha ||
					b2make.conteiner_banners_image_holder_coluna != coluna
				)
					conteiner_banners_image_order_grid(coluna,linha);
			}
		});
		
		function conteiner_banners_image_mouseup(){
			if(b2make.conteiner_banners_image_holder_mousedown){
				b2make.conteiner_banners_image_holder_mousedown = false;
				conteiner_banners_image_order_stop();
			}
			
			b2make.conteiner_banners_image_holder_mousemove = false;
			b2make.conteiner_banners_image_holder_mouseup = true;
		}

		function conteiner_banners_image_order_stop(){
			b2make.conteiner_banners_image_holder_mousemove = false;
			
			if(!b2make.conteiner_banners_mask)return;
			
			$(b2make.conteiner_banners_mask).before(b2make.conteiner_banners_image_holder_obj);
			
			$(b2make.conteiner_banners_image_holder_obj).css('position','relative');
			$(b2make.conteiner_banners_image_holder_obj).css('zIndex','auto');
			$(b2make.conteiner_banners_image_holder_obj).css('top','auto');
			$(b2make.conteiner_banners_image_holder_obj).css('left','auto');
			
			b2make.conteiner_banners_mask.hide();
			
			var count = 0;
			var ids = '';
			
			$('.b2make-conteiner-banners-image-holder').each(function(){
				count++;
				var id = $(this).myAttr('data-image-id');
				
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
								conteiner_banners_update({type:'banners-order'});
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
		
		function conteiner_banners_image_order_grid(coluna,linha){
			var colunas = 5;
			var total = parseInt($('.b2make-conteiner-banners-image-holder').length);
			
			if(!b2make.conteiner_banners_mask){
				b2make.conteiner_banners_mask = $('<div id="b2make-conteiner-banners-mask"></div>');
				b2make.conteiner_banners_mask.appendTo('#b2make-conteiner-banners-lista-images-hide');
			} else {
				b2make.conteiner_banners_mask = $('#b2make-conteiner-banners-mask');
			}
			
			
			if(coluna >= colunas) coluna = colunas;
			
			var count = 1;
			b2make.conteiner_banners_mask_position = linha * 5 + coluna + 1;
			
			if(b2make.conteiner_banners_mask_position < 0) b2make.conteiner_banners_mask_position = 0;
			if(b2make.conteiner_banners_mask_position > total) b2make.conteiner_banners_mask_position = total;
			
			b2make.conteiner_banners_mask.show();
			
			$('.b2make-conteiner-banners-image-holder').each(function(){
				var id = $(this).myAttr('id');
				var id_holder = $(b2make.conteiner_banners_image_holder_obj).myAttr('id');
				
				if(count == b2make.conteiner_banners_mask_position && id != id_holder){
					switch(count){
						case 1:
							b2make.conteiner_banners_mask.prependTo('#b2make-conteiner-banners-lista-images');
						break;
						case total:
							b2make.conteiner_banners_mask.appendTo('#b2make-conteiner-banners-lista-images');
						break;
						default:
							$(this).before(b2make.conteiner_banners_mask);
					}
					return false;
				}
				count++;
			});
			
			b2make.conteiner_banners_image_holder_linha = linha;
			b2make.conteiner_banners_image_holder_coluna = coluna;
		}
		
		$('#b2make-listener').on('widgets-resize',function(){
			switch(b2make.conteiner_child_type){
				case 'conteiner_banners':
					conteiner_banners_caixa_posicao_atualizar({});
				break;
			}
		});

		$('#b2make-woc-banners-animation-start-pause').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = b2make.conteiner_obj;
			if(b2make.conteiner_banners_start[$(obj).myAttr('id')]){
				$(this).css('backgroundPosition','0px 0px');
				b2make.conteiner_banners_start[$(obj).myAttr('id')] = false;
				conteiner_banners_animation_stop(obj);
			} else {
				$(this).css('backgroundPosition','-20px 0px');
				b2make.conteiner_banners_start[$(obj).myAttr('id')] = true;
				conteiner_banners_animation_start({obj:obj});
			}
		});
		
		$('#b2make-woc-banners-seta-cor-val,#b2make-woc-banners-caixa-cor-val,#b2make-woc-banners-titulo-cor-val,#b2make-woc-banners-sub-titulo-cor-val').on('changeColor',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			var obj = b2make.conteiner_obj;
			var id_image = b2make.conteiner_banners_id_image;
			
			switch(id){
				case 'b2make-woc-banners-titulo-cor-val':
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).find('.b2make-conteiner-banners-image-cont').find('.b2make-conteiner-banners-image-titulo').css('color',bg);
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-titulo-color-ahex',ahex);	
				break;
				case 'b2make-woc-banners-sub-titulo-cor-val':
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).find('.b2make-conteiner-banners-image-cont').find('.b2make-conteiner-banners-image-sub-titulo').css('color',bg);
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-sub-titulo-color-ahex',ahex);	
				break;
				case 'b2make-woc-banners-caixa-cor-val':
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).find('.b2make-conteiner-banners-image-cont').css('background-color',bg);
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-caixa-color-ahex',ahex);	
				break;
				case 'b2make-woc-banners-seta-cor-val':
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-seta-color-ahex',ahex);
					
					var cor = ahex;
					
					if(cor){
						var cor_rgba = $.jPicker.ColorMethods.hexToRgba(cor);
						
						var left = $(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left').find('svg');
						var right = $(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right').find('svg');
						
						left.find('polyline').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
						right.find('polyline').css('fill','rgba('+cor_rgba.r+','+cor_rgba.g+','+cor_rgba.b+','+(cor_rgba.a/255).toFixed(1)+')');
					}
				break;
				
			}
		});
		
		$('#b2make-woc-banners-titulo-text-cont,#b2make-woc-banners-sub-titulo-text-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
			var obj = b2make.conteiner_obj;
			var id_image = b2make.conteiner_banners_id_image;
			var target;
			var cssVar = '';
			var noSize = false;
			var id_bruto = $(this).myAttr('id');
			var mudar_height = false;
			var id = id_bruto.replace(/b2make-woc-banners-/gi,'');
			
			id = id.replace(/-text-cont/gi,'');
			
			switch(id_bruto){
				case 'b2make-woc-banners-titulo-text-cont': target = $(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).find('.b2make-conteiner-banners-image-cont').find('.b2make-conteiner-banners-image-titulo'); mudar_height = true; break;
				case 'b2make-woc-banners-sub-titulo-text-cont': target = $(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).find('.b2make-conteiner-banners-image-cont').find('.b2make-conteiner-banners-image-sub-titulo'); mudar_height = true; break;
			}
			
			switch(e.type){
				case 'changeFontFamily': 
					cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); 
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-'+id+'-font-family',$(this).myAttr('data-font-family'));
					$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-google-font-'+id,$(this).myAttr('data-google-font'));
				break;
				case 'changeFontSize': 
					cssVar = 'fontSize';  target.css(cssVar,$(this).myAttr('data-font-size')+'px'); target.css('line-height',$(this).myAttr('data-font-size')+'px'); $(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-'+id+'-font-size',$(this).myAttr('data-font-size')); 
				break;
				case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).myAttr('data-font-align'));$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-'+id+'-font-align',$(this).myAttr('data-font-align')); break;
				case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-'+id+'-font-italico',$(this).myAttr('data-font-italico')); break;
				case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+id_image).myAttr('data-'+id+'-font-negrito',$(this).myAttr('data-font-negrito')); break;
			}
		});
		
		$('.b2make-tooltip').tooltip({
			show: {
				effect: "fade",
				delay: 400
			}
		});
		
		$('#b2make-woc-banners-titulo-topo').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_titulo_max_value){
				this.value = b2make.wo_conteiner_banners_titulo_max_value;
				value = b2make.wo_conteiner_banners_titulo_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_titulo_min_value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+b2make.conteiner_banners_id_image).myAttr('data-titulo-topo',value);
			conteiner_banners_imagem_caixa_atualizar({});
		});
		
		$('#b2make-woc-banners-titulo-esquerda').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_titulo_max_value){
				this.value = b2make.wo_conteiner_banners_titulo_max_value;
				value = b2make.wo_conteiner_banners_titulo_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_titulo_min_value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+b2make.conteiner_banners_id_image).myAttr('data-titulo-esquerda',value);
			conteiner_banners_imagem_caixa_atualizar({});
		});
		
		$('#b2make-woc-banners-titulo-padding').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_titulo_max_value){
				this.value = b2make.wo_conteiner_banners_titulo_max_value;
				value = b2make.wo_conteiner_banners_titulo_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_titulo_min_value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+b2make.conteiner_banners_id_image).myAttr('data-titulo-padding',value);
			conteiner_banners_imagem_caixa_atualizar({});
		});
		
		$('#b2make-woc-banners-titulo-tamanho').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_titulo_max_value){
				this.value = b2make.wo_conteiner_banners_titulo_max_value;
				value = b2make.wo_conteiner_banners_titulo_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_titulo_min_value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			$(obj).find('.b2make-conteiner-banners-holder').find('#b2make-conteiner-banners-imagem-'+b2make.conteiner_banners_id_image).myAttr('data-titulo-tamanho',value);
			conteiner_banners_imagem_caixa_atualizar({});
		});
		
		$('#b2make-woc-banners-seta-margem').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_titulo_max_value){
				this.value = b2make.wo_conteiner_banners_titulo_max_value;
				value = b2make.wo_conteiner_banners_titulo_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_titulo_min_value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			$(obj).myAttr('data-seta-margem',value);
			
			conteiner_banners_caixa_posicao_atualizar({});
		});
		
		$('#b2make-woc-banners-seta-tamanho').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_titulo_max_value){
				this.value = b2make.wo_conteiner_banners_titulo_max_value;
				value = b2make.wo_conteiner_banners_titulo_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_titulo_min_value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_titulo_min_value;
			}
			
			$(obj).myAttr('data-seta-tamanho',value);
			
			conteiner_banners_caixa_posicao_atualizar({});
		});
		
		$('#b2make-woc-banners-seta-visivel').on('change',function(){
			var obj = b2make.conteiner_obj;
			var value = $(this).val();
			
			if(value == 's'){
				$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left').show();
				$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right').show();
			} else {
				$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left').hide();
				$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right').hide();
			}
			
			$(obj).myAttr('data-seta-visivel',value);
		});
		
		$('#b2make-woc-banners-tempo-transicao').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_animation_max_value){
				this.value = b2make.wo_conteiner_banners_animation_max_value;
				value = b2make.wo_conteiner_banners_animation_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_animation_min_value){
				value = b2make.wo_conteiner_banners_animation_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_animation_min_value;
			}
			
			$(obj).myAttr('data-tempo-transicao',value);
		});
		
		$('#b2make-woc-banners-tempo-exposicao').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			if(value > b2make.wo_conteiner_banners_animation_max_value){
				this.value = b2make.wo_conteiner_banners_animation_max_value;
				value = b2make.wo_conteiner_banners_animation_max_value;
			}
			
			if(value < b2make.wo_conteiner_banners_animation_min_value){
				value = b2make.wo_conteiner_banners_animation_min_value;
			}
			
			if(!value){
				value = b2make.wo_conteiner_banners_animation_min_value;
			}
			
			$(obj).myAttr('data-tempo-exposicao',value);
		});
		
		$('#b2make-woc-banners-animation-type').on('change',function(){
			var obj = b2make.conteiner_obj;
			var value = $(this).val();
			
			$(obj).myAttr('data-animation-type',value);
		});
		
		$('#b2make-woc-banners-ease-type').on('change',function(){
			var obj = b2make.conteiner_obj;
			var value = $(this).val();
			
			$(obj).myAttr('data-ease-type',value);
		});

		$(document.body).on('mouseup tap','.b2make-woc-banners-seta-right',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this).parent().parent().get(0);
			
			if(!b2make.conteiner_animation_interacao[$(obj).myAttr('id')])b2make.conteiner_animation_interacao[$(obj).myAttr('id')] = 0;
			
			b2make.conteiner_animation_interacao[$(obj).myAttr('id')]++;
			conteiner_banners_animation_start({obj:obj});
		});
		
		$(document.body).on('mouseup tap','.b2make-woc-banners-seta-left',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this).parent().parent().get(0);
			
			if(!b2make.conteiner_animation_interacao[$(obj).myAttr('id')])b2make.conteiner_animation_interacao[$(obj).myAttr('id')] = 0;
			
			b2make.conteiner_animation_interacao[$(obj).myAttr('id')]++;
			conteiner_banners_animation_start({obj:obj,inverso:true});
		});
		
		$(document.body).on('mouseup tap','#b2make-conteiner-banners-preview-close',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_banners_image_preview_close();
		});
		
		$('#b2make-fcb-sub-titulo').keyup(function (e) {
			b2make.dialogbox_dont_close_on_enter = true;
		});
	
		$('.b2make-conteiner-banners-seta-right').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this).parent().parent().get(0);
			
			b2make.conteiner_animation_interacao[$(obj).myAttr('id')]++;
			conteiner_banners_animation_start({obj:obj,inverso:true});
		});
		
		$('.b2make-conteiner-banners-seta-left').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this).parent().parent().get(0);
			
			b2make.conteiner_animation_interacao[$(obj).myAttr('id')]++;
			conteiner_banners_animation_start({obj:obj});
		});
	}
	
	conteiner_banners();
	
	function conteiner_areas_globais_change_area(p = {}){
		var obj = p.obj;
		var change = p.change;
		var area_global_id = p.area_global_id;
		var area_local_id = p.area_local_id;
		var padrao = p.padrao;
		
		if(padrao){
			$(obj).removeClass('b2make-loading-area');
		} else {
			var cache = conteiner_areas_globais_cache_get({area_global_id:area_global_id});
			
			if(cache.html.length > 0){
				var obj_new = $(cache.html);
				
				obj_new.myAttr('id',area_local_id);
				
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
				
				if(change){
					b2make.conteiner_obj = $("#"+area_local_id).get(0);
					$("#"+area_local_id).css('cursor','move');
					conteiner_open();
				}
			}
		}
	}
	
	function conteiner_areas_globais_cache_change(p = {}){
		var area_global_id = p.area_global_id;
		var versao = p.versao;
		var html = p.html;
		var cache = $.local_storage_get_array('areas-globais-html');
		var found = false;
		
		if(cache){
			for(var i=0;i<cache.length;i++){
				if(cache[i].id == area_global_id){
					cache[i].versao = versao;
					cache[i].html = html;
					found = true;
					break;
				}
			}
		}
		
		if(!found){
			area_global = {
				id : area_global_id,
				versao : versao,
				html : html
			};
			
			if(!cache){
				local_storage_restart_array('areas-globais-html');
			}
			
			local_storage_set_array('areas-globais-html',area_global);
		} else {
			local_storage_change_array('areas-globais-html',cache);
		}
	}
	
	function conteiner_areas_globais_cache_get(p = {}){
		var area_global_id = p.area_global_id;
		var cache = $.local_storage_get_array('areas-globais-html');
		
		if(cache){
			for(var i=0;i<cache.length;i++){
				if(cache[i].id == area_global_id){
					return cache[i];
				}
			}
		}
		
		return false;
	}
	
	function conteiner_areas_globais_cache_verify(p = {}){
		var id = p.id;
		var cache = $.local_storage_get_array('areas-globais-html');
		
		if(cache){
			for(var i=0;i<cache.length;i++){
				if(cache[i].id == id){
					return cache[i].versao;
				}
			}
		}
		
		return false;
	}
	
	function conteiner_areas_globais_load(p = {}){
		var obj = p.obj;
		var change = p.change;
		var area_global_id = $(obj).myAttr('data-area-global-id');
		var area_local_id = $(obj).myAttr('id');
		
		if(area_global_id){
			var id_func = 'conteiner-areas-globais-load';
			var versao = conteiner_areas_globais_cache_verify({id:area_global_id});
			var versao_atual = '';
			
			if(variaveis_js.areas_globais_html)
			for(var i=0;i<variaveis_js.areas_globais_html.length;i++){
				if(variaveis_js.areas_globais_html[i].id == area_global_id){
					versao_atual = variaveis_js.areas_globais_html[i].versao;
				}
			}
			
			if(versao && versao == versao_atual){
				conteiner_areas_globais_change_area({
					change : change,
					obj : obj,
					area_global_id : area_global_id,
					area_local_id : area_local_id
				});
			} else {
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						opcao : id_func,
						id:area_global_id
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
									if(dados.found){
										conteiner_areas_globais_cache_change({
											area_global_id : area_global_id,
											versao : dados.versao,
											html : dados.html
										});
										
										conteiner_areas_globais_change_area({
											change : change,
											obj : obj,
											area_global_id : area_global_id,
											area_local_id : area_local_id
										});
									} else {
										conteiner_areas_globais_change_area({
											padrao : true,
											change : change,
											obj : obj,
											area_global_id : area_global_id,
											area_local_id : area_local_id
										});
									}
								break;
								case 'NaoExisteId':
									conteiner_areas_globais_change_area({
										padrao : true,
										change : change,
										obj : obj,
										area_global_id : area_global_id,
										area_local_id : area_local_id
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
		}
		
	}
	
	function conteiner_areas_globais_menu_html(dados){
		if(!dados)dados = {};
		$('#b2make-conteiner-area-global-lista').prepend($('<div class="b2make-conteiner-area-global-lista"><div class="b2make-conteiner-areas-globais-show b2make-tooltip" title="'+b2make.msgs.conteinerAreasGlobaisShow+'" data-status="'+(dados.conteiner_areas_globais_show ? 'show' : 'not-show')+'" data-areas-globais-id="'+dados.conteiner_areas_globais_id+'"></div><div class="b2make-conteiner-areas-globais-nome b2make-tooltip" title="'+b2make.msgs.conteinerAreasGlobaisNome+'" data-status="'+(dados.conteiner_areas_globais_selected ? 'show' : 'not-show')+'" data-areas-globais-id="'+dados.conteiner_areas_globais_id+'">'+dados.conteiner_areas_globais_nome+'</div><div class="b2make-conteiner-areas-globais-edit b2make-tooltip" data-areas-globais-id="'+dados.conteiner_areas_globais_id+'" title="'+b2make.msgs.conteinerAreasGlobaisEdit+'"></div><div class="b2make-conteiner-areas-globais-delete b2make-tooltip" data-areas-globais-id="'+dados.conteiner_areas_globais_id+'" title="'+b2make.msgs.conteinerAreasGlobaisDelete+'"></div><div class="clear"></div></div>'));
	}
	
	function conteiner_areas_globais_add(){
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-conteiner-areas-globais-add-calback',
			title: b2make.msgs.conteinerAreasGlobaisAddTitle,
			coneiner: 'b2make-formulario-conteiner-areas-globais'
		});
	}
	
	function conteiner_areas_globais_add_base(){
		var id_func = 'conteiner-areas-globais-add';
		var form_id = 'b2make-formulario-conteiner-areas-globais';
		
		if($.formulario_testar(form_id)){
			var ajaxData = { 
				ajax : 'sim',
				mobile : (b2make.multi_screen.device == 'desktop' ? 'n' : 's'),
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
								$('.b2make-conteiner-areas-globais-show').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								$('.b2make-conteiner-areas-globais-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								dados.conteiner_areas_globais_show = true;
								dados.conteiner_areas_globais_selected = true;
								conteiner_areas_globais_menu_html(dados);
								$('.b2make-tooltip').tooltip({
									show: {
										effect: "fade",
										delay: 400
									}
								});
								$.dialogbox_close();
								
								b2make.conteiner_areas_globais_atual = dados.conteiner_areas_globais_id;
								b2make.conteiner_areas_globais_nome = dados.conteiner_areas_globais_nome;
								
								$('#b2make-conteiner-areas-globais-btn').show();
								
								if(!b2make.conteiner_areas_globais_todos_ids)b2make.conteiner_areas_globais_todos_ids = new Array();
								b2make.conteiner_areas_globais_todos_ids.push(dados.conteiner_areas_globais_id);
								
								var obj = b2make.conteiner_obj;
								$(obj).myAttr('data-area-global-id',dados.conteiner_areas_globais_id);
								
								b2make.areas_globais_change = true;
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

	function conteiner_areas_globais_edit(id){
		$('#b2make-formulario-conteiner-areas-globais #b2make-fag-nome').val($('.b2make-conteiner-areas-globais-nome[data-areas-globais-id="'+id+'"]').html());
		
		b2make.conteiner_areas_globais_edit_id = id;
		
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-conteiner-areas-globais-edit-calback',
			title: b2make.msgs.conteinerAreasGlobaisEditTitle,
			coneiner: 'b2make-formulario-conteiner-areas-globais'
		});
	}

	function conteiner_areas_globais_edit_base(){
		var id_func = 'conteiner-areas-globais-edit';
		var form_id = 'b2make-formulario-conteiner-areas-globais';
		var id = b2make.conteiner_areas_globais_edit_id;
		
		b2make.conteiner_areas_globais_edit_id = false;
		
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
								$('.b2make-conteiner-areas-globais-nome[data-areas-globais-id="'+id+'"]').html(dados.nome);
								
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

	function conteiner_areas_globais_del(id){
		b2make.conteiner_areas_globais_del_id = id;
		
		var msg = b2make.msgs.conteinerAreasGlobaisDelTitle;
		msg = msg.replace(/#areas-globais#/gi,$('.b2make-conteiner-areas-globais-nome[data-areas-globais-id="'+id+'"]').html());
		
		$.dialogbox_open({
			confirm:true,
			calback_yes: 'b2make-conteiner-areas-globais-del-calback',
			msg: msg
		});
	}

	function conteiner_areas_globais_del_base(){
		var id_func = 'conteiner-areas-globais-del';
		var id = b2make.conteiner_areas_globais_del_id;
		
		b2make.conteiner_areas_globais_del_id = false;

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
							$('.b2make-conteiner-areas-globais-delete[data-areas-globais-id="'+id+'"]').parent().remove();
							$.dialogbox_close();
							
							var obj = b2make.conteiner_obj;
							
							if($(obj).myAttr('data-area-global-id') == id){
								$(obj).removeAttr('data-area-global-id');
							}
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
	
	function conteiner_areas_globais(){
		if(!b2make.msgs.conteinerAreasGlobaisShow)b2make.msgs.conteinerAreasGlobaisShow = 'Clique para selecionar esta &aacute;rea global na &aacute;rea atual.';
		if(!b2make.msgs.conteinerAreasGlobaisNome)b2make.msgs.conteinerAreasGlobaisNome = 'Clique para selecionar esta &aacute;rea global na &aacute;rea atual.';
		if(!b2make.msgs.conteinerAreasGlobaisEdit)b2make.msgs.conteinerAreasGlobaisEdit = 'Clique para Editar o Nome desta &aacute;rea global.';
		if(!b2make.msgs.conteinerAreasGlobaisDelete)b2make.msgs.conteinerAreasGlobaisDelete = 'Clique para deletar esta &aacute;rea global.';
		if(!b2make.msgs.conteinerAreasGlobaisAddTitle)b2make.msgs.conteinerAreasGlobaisAddTitle = 'Adicionar &Aacute;rea Global';
		if(!b2make.msgs.conteinerAreasGlobaisEditTitle)b2make.msgs.conteinerAreasGlobaisEditTitle = 'Editar Nome da &Aacute;rea Global';
		if(!b2make.msgs.conteinerAreasGlobaisDelTitle)b2make.msgs.conteinerAreasGlobaisDelTitle = 'Tem certeza que deseja excluir <b>#areas-globais#</b>?';
		
		var conteiner_areas_globais_show,conteiner_areas_globais_selected;
		var conteiner_areas_globais_todos_ids = new Array();
		var areas_globais = variaveis_js.areas_globais;
		var mobile = false;
		
		if(b2make.multi_screen.device == 'desktop'){
			mobile = false;
		} else {
			mobile = true;
		}
		
		if(areas_globais)
		for(var i=0;i<areas_globais.length;i++){
			if(
				(mobile && areas_globais[i].mobile) ||
				(!mobile && !areas_globais[i].mobile) 
			){
				conteiner_areas_globais_show = false;
				conteiner_areas_globais_selected = false;
				
				if(i==areas_globais.length - 1){
					b2make.conteiner_areas_globais_atual = areas_globais[i].id;
					b2make.conteiner_areas_globais_nome = areas_globais[i].nome;
					conteiner_areas_globais_selected = true;
					conteiner_areas_globais_show = true;
				}
				
				conteiner_areas_globais_menu_html({
					conteiner_areas_globais_selected:conteiner_areas_globais_selected,
					conteiner_areas_globais_show:conteiner_areas_globais_show,
					conteiner_areas_globais_id:areas_globais[i].id,
					conteiner_areas_globais_nome:areas_globais[i].nome
				});
				
				if(!b2make.conteiner_areas_globais_todos_ids){
					conteiner_areas_globais_todos_ids.push(areas_globais[i].id);
				}
			}
		}
		
		if(!b2make.conteiner_areas_globais_todos_ids){
			b2make.conteiner_areas_globais_todos_ids = conteiner_areas_globais_todos_ids;
		}
		
		$('#b2make-conteiner-area-global-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			b2make.widget_sub_options_up_clicked = false;
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = 'conteiner-global';
			b2make.widget_sub_options_lightbox_height = '445';
			b2make.widget_sub_options_holder_width_user = '330';
			b2make.widget_sub_options_lightbox_width = '370';
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open();
		});
		
		
		$('#b2make-conteiner-area-global').on('change',function(e){
			var obj = b2make.conteiner_obj;
			var status = $(this).val();
			
			if(status == 'n'){
				$(obj).removeAttr('data-area-global-id');
				
				$('.b2make-conteiner-areas-globais-show').each(function(){
					$(this).myAttr('data-status','not-show');
				});
				
				$('.b2make-conteiner-areas-globais-nome').each(function(){
					$(this).myAttr('data-status','not-show');
				});
			} else {
				$('#b2make-conteiner-area-global-btn').trigger('mouseup');
			}
			
			$(obj).myAttr('data-area-global',$(this).val());
		});
		
		$('#b2make-conteiner-area-global-add').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_areas_globais_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-areas-globais-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_areas_globais_add_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-areas-globais-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-areas-globais-id');
			conteiner_areas_globais_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-areas-globais-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_areas_globais_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-areas-globais-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-areas-globais-id');
			conteiner_areas_globais_del(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-areas-globais-del-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_areas_globais_del_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-conteiner-areas-globais-show,.b2make-conteiner-areas-globais-nome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = b2make.conteiner_obj;
			
			var id = $(this).myAttr('data-areas-globais-id');
			
			b2make.areas_globais_change = true;
			
			if($(this).myAttr('data-status') == 'not-show'){
				$('.b2make-conteiner-areas-globais-show').each(function(){
					$(this).myAttr('data-status','not-show');
				});
				
				$(this).parent().find('.b2make-conteiner-areas-globais-show').myAttr('data-status','show');
				
				$('.b2make-conteiner-areas-globais-nome').each(function(){
					$(this).myAttr('data-status','not-show');
				});
				
				var nome_obj = $(this).parent().find('.b2make-conteiner-areas-globais-nome');
				
				nome_obj.myAttr('data-status','show');
				
				var id = nome_obj.myAttr('data-areas-globais-id');
				
				b2make.conteiner_areas_globais_atual = nome_obj.myAttr('data-areas-globais-id');
				b2make.conteiner_areas_globais_nome = nome_obj.html();			
				b2make.conteiner_areas_globais_menu_atual = id;
				
				$(obj).myAttr('data-area-global-id',id);
				
				conteiner_areas_globais_load({obj:obj,change:true});
			} else {
				$(this).myAttr('data-status','not-show');
				$(obj).removeAttr('data-area-global-id');
			}
		});
	}
	
	conteiner_areas_globais();
	
	function facebook_href_delay_to_change(value){
		if(!b2make.facebook_delay){
			b2make.facebook_delay = new Array();
			b2make.facebook_delay_count = 0;
		}
		
		b2make.facebook_delay_count++;
		
		var valor = b2make.facebook_delay_count;
		
		b2make.facebook_delay.push(valor);
		b2make.facebook_value = value;
		
		setTimeout(function(){
			if(b2make.facebook_delay[b2make.facebook_delay.length - 1] == valor){
				facebook_href_change(b2make.facebook_value);
			}
		},b2make.facebook.delay_timeout);
	}
	
	function facebook_href_change(value){
		if(value){
			if(value.match(/facebook.com/) == 'facebook.com'){
				if(/^([a-z]([a-z]|\d|\+|-|\.)*):(\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?((\[(|(v[\da-f]{1,}\.(([a-z]|\d|-|\.|_|~)|[!\$&'\(\)\*\+,;=]|:)+))\])|((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=])*)(:\d*)?)(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*|(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)|((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)|((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)){0})(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value)) {
					var obj = b2make.conteiner_child_obj;
					var width = $(obj).width();
					var height = $(obj).height();

					$(obj).myAttr('data-href',encodeURIComponent(value));
					
					var widget = '<iframe src="https://www.facebook.com/plugins/likebox.php?href='+encodeURIComponent(value)+'&width='+width+'&height='+height+'&show_faces=true&colorscheme=light&stream=false&show_border=false&header=false&appId=358146730957925" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'+width+'px; height:'+height+'px;" allowTransparency="true"></iframe>';
					
					$(obj).find('div.b2make-widget-out').find('iframe').remove();
					$(obj).find('div.b2make-widget-out').append(widget);
					
				}
			}
		}
		
		b2make.facebook_delay = false;
	}
	
	function twitter_user_delay_to_change(value){
		if(!b2make.twitter_delay){
			b2make.twitter_delay = new Array();
			b2make.twitter_delay_count = 0;
		}
		
		b2make.twitter_delay_count++;
		
		var valor = b2make.twitter_delay_count;
		
		b2make.twitter_delay.push(valor);
		b2make.twitter_value = value;
		
		setTimeout(function(){
			if(b2make.twitter_delay[b2make.twitter_delay.length - 1] == valor){
				twitter_user_change(b2make.twitter_value);
			}
		},b2make.twitter.delay_timeout);
	}
	
	function soundcloud_user_delay_to_change(value){
		if(!b2make.soundcloud_delay){
			b2make.soundcloud_delay = new Array();
			b2make.soundcloud_delay_count = 0;
		}
		
		b2make.soundcloud_delay_count++;
		
		var valor = b2make.soundcloud_delay_count;
		
		b2make.soundcloud_delay.push(valor);
		b2make.soundcloud_value = value;
		
		setTimeout(function(){
			if(b2make.soundcloud_delay[b2make.soundcloud_delay.length - 1] == valor){
				soundcloud_user_change(b2make.soundcloud_value);
			}
		},b2make.soundcloud.delay_timeout);
	}
	
	function twitter_user_change(value){
		if(value){
			if(value.length > 2){
				var obj = b2make.conteiner_child_obj;
				var width = $(obj).width();
				var height = $(obj).height();

				$(obj).myAttr('data-user',value);
				
				var widget = '<iframe src="//platform.twitter.com/widgets/follow_button.html?screen_name='+value+'&show_count=true&show_screen_name=true" style="width: '+width+'px; height: '+height+'px;" allowtransparency="true" frameborder="0" scrolling="no"></iframe>';
	
				$(obj).find('div.b2make-widget-out').find('iframe').remove();
				$(obj).find('div.b2make-widget-out').append(widget);
			}
		}
		
		b2make.twitter_delay = false;
	}
	
	function soundcloud_user_change(value){
		if(value){
			if(value.length > 2){
				var obj = b2make.conteiner_child_obj;
				var width = $(obj).width();
				var height = $(obj).height();

				$(obj).myAttr('data-user',value);
				
				var widget = '<iframe width="'+width+'" height="'+height+'" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/users/'+value+'&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false&amp;visual=true"></iframe>';
	
				$(obj).find('div.b2make-widget-out').find('iframe').remove();
				$(obj).find('div.b2make-widget-out').append(widget);
			}
		}
		
		b2make.twitter_delay = false;
	}
	
	function widget_generic_add(params){
		if(!params) params = {};
		if(!params.position) params.position = {};
		
		var widget_type = params.type;
		var widget_out = 'widget-out';
		
		$.conteiner_nao_existe_regra();
		
		if(b2make.conteiner_show){
			var p = {};
			var widget = params.widget;
			var cont = $('<div class="b2make-widget"><div class="b2make-widget-out">'+widget+'</div><div class="b2make-widget-mask" data-type="'+widget_type+'" data-type-out="'+widget_out+'"></div></div>');
			
			cont.myAttr('id',widget_type+b2make.widgets_count);
			cont.myAttr('data-type',widget_type);
			cont.myAttr('data-type-out',widget_out);
			if(params.ler_script)cont.myAttr('data-ler-script','true');
			if(params.sub_menu_disable)cont.myAttr('data-sub-menu-disable','true');
			if(params.menu_specific_disable)cont.myAttr('data-menu-specific-disable','true');
			
			if(params.data){
				for(var i=0;i<params.data.length;i++){
					cont.myAttr(params.data[i].name,params.data[i].value);
				}
			}
			
			cont.css('position','absolute');
			
			cont.css('top',(params.position.top ? params.position.top : '30px'));
			cont.css('left',(params.position.left ? params.position.left : '30px'));
			cont.css('width',(params.position.width ? params.position.width : '300px'));
			cont.css('height',(params.position.height ? params.position.height : '200px'));
			
			if(b2make.conteiner_obj_area) cont.appendTo(b2make.conteiner_obj_area); else cont.appendTo(b2make.conteiner_obj);
			
			b2make.widgets.push({
				id : b2make.widgets_count,
				type : widget_type,
				id_pai : b2make.conteiner_show
			});
			
			if(b2make.conteiner_child_show){
				$.conteiner_child_close();
			}
			
			b2make.conteiner_child_obj = document.getElementById(widget_type+b2make.widgets_count);
			
			p.select = true;
			p.widget_type = widget_type;
			p.widget_added = true;
			
			if(params.callback){
				params.callback();
			}
			
			$.conteiner_child_open(p);
			
			b2make.widgets_count++;
		} else {
			var msg = b2make.msgs.conteinerNotSelected;
			msg = msg.replace(/#widget#/gi,widget_type);
			
			$.dialogbox_open({
				msg: msg
			});
		}
	}
	
	$.widget_add = function(p){
		b2make.widget_sub_options_up_clicked = false;
		
		switch(p.type){
			case 'conteiner':
				conteiner_add();
			break;
			case 'facebook':
				facebook_add();
			break;
			case 'twitter':
				twitter_add();
			break;
			case 'twitter-tweets':
				twitter_tweets_add();
			break;
			case 'texto':
				texto_add();
			break;
			case 'imagem':
				b2make.widget_add_sub_options_open = true;
				imagem_add();
			break;
			case 'soundcloud':
				sound_cloud_add();
			break;
			case 'iframe':
				iframe_add();
			break;
			case 'galeria':
				b2make.widget_add_sub_options_open = true;
				galeria_widget_add();
			break;
			case 'player':
				b2make.widget_add_sub_options_open = true;
				player_widget_add();
			break;
			case 'agenda':
				b2make.widget_add_sub_options_open = true;
				agenda_widget_add();
			break;
			case 'menu':
				b2make.widget_add_sub_options_open = true;
				menu_widget_add();
			break;
			case 'slideshow':
				b2make.widget_add_sub_options_open = true;
				slideshow_widget_add();
			break;
			case 'albumfotos':
				b2make.widget_add_sub_options_open = true;
				albumfotos_widget_add();
			break;
			case 'albummusicas':
				b2make.widget_add_sub_options_open = true;
				albummusicas_widget_add();
			break;
			case 'redessociais':
				b2make.widget_add_sub_options_open = true;
				redessociais_widget_add();
			break;
			case 'instagram':
				widget_generic_add({
					'type' : 'instagram',
					'widget' : '<div class="b2make-instagram-widget-holder"></div>',
					data: new Array({name:'data-numero-posts',value:'1'}),
					'callback' : instagram_autorizar
				});
			break;
			case 'addthis':
				widget_generic_add({
					'type' : 'addthis',
					'widget' : '<div class="addthis_toolbox addthis_default_style"><a class="addthis_button_facebook_like" fb:like:layout="button_count"></a><a class="addthis_button_tweet"></a><a class="addthis_button_google_plusone" g:plusone:size="medium"></a></div>',
					'callback' : addthis_exec,
					data: new Array({name:'data-mostrar',value:'facebook,tweets,googleplus'}),
					position: {
						'width' : '300px',
						'height' : '30px'
					}
				});
			break;
			case 'youtube':
				widget_generic_add({
					'type' : 'youtube',
					'widget' : '<iframe width="560" height="315" src="https://www.youtube.com/embed/XIp4HCCNjA0" frameborder="0" allowfullscreen></iframe>',
					'callback' : addthis_exec,
					data: new Array({name:'data-url',value:'https://www.youtube.com/watch?v=XIp4HCCNjA0'}),
					position: {
						'width' : '560px',
						'height' : '315px'
					}
				});
			break;
			case 'download':
				b2make.widget_add_sub_options_open = true;
				widget_generic_add({
					'type' : 'download',
					'widget' : '<a target="_blank" href="'+location.href+'b2make-arquivo-download.zip" class="b2make-download-widget-link"><div class="b2make-download-widget-holder b2make-texto-table"><div class="b2make-texto-cel" style="vertical-align: middle;">'+b2make.msgs.downloadButtonTitle+'</div><div class="b2make-download-widget-gradient"></div></div></a>',
					data: new Array({name:'data-font-negrito',value:'sim'}),
					position: {
						'width' : '150px',
						'height' : '30px'
					}
				});
				
				var obj = b2make.conteiner_child_obj;
				
				var gradiente = $(obj).find('.b2make-widget-out').find('.b2make-download-widget-link').find('.b2make-download-widget-holder').find('.b2make-download-widget-gradient');
				
				$(obj).myAttr('data-gradiente','n');
				gradiente.hide();
			break;
			case 'services':
				b2make.widget_add_sub_options_open = true;
				widget_generic_add({
					'type' : 'services',
					'widget' : '<div class="b2make-services-list"></div><div class="b2make-widget-loading"></div>',
					position: {
						'width' : '450px',
						'height' : '180px'
					}
				});
			break;
			default:
				$('.b2make-menu-local[data-id="design"]').find('ul.b2make-menu-nav').find('li').each(function(){
					if(p.type == $(this).myAttr('data-id')){
						var atributos = {
							'type' : p.type,
							'widget' : '<div class="b2make-'+p.type+'"></div>',
						};
						var data;
						var position;
						var sub_widget_conts = new Array();
						var sub_widget_flag;
						var loading_new;
						
						$.each(this.attributes, function(i, attrib){
							var name = attrib.name;
							var value = attrib.value;
							switch(name){
								case 'data-ler-script': atributos.ler_script = true; break;
								case 'data-sub-options': b2make.widget_add_sub_options_open = true; break;
								case 'data-sub-menu-disable': atributos.sub_menu_disable = true; break;
								case 'data-menu-specific-disable': atributos.menu_specific_disable = true; break;
								case 'data-position-width': if(!position)position = {}; position.width = value; break;
								case 'data-position-height': if(!position)position = {}; position.height = value; break;
								case 'data-position-top': if(!position)position = {}; position.top = value; break;
								case 'data-position-left': if(!position)position = {}; position.left = value; break;
								case 'data-widget-loading': sub_widget_conts.push('<div class="b2make-widget-loading"></div>'); sub_widget_flag = true; break;
								case 'data-widget-loading-new': sub_widget_flag = true; loading_new = true; break;
								case 'data-widget-more': sub_widget_conts.push($('#'+value).html()); sub_widget_flag = true; break;
								default:
									if(name.match(/data-attr-/) == 'data-attr-'){
										var data_name = name.replace(/data-attr-/gi,'');
										
										if(!data) data = new Array();
										
										data.push({
											name : 'data-'+data_name,
											value : value
										});
									}
							}
						});
						
						if(sub_widget_flag){
							var widgets_more = '';
							
							for(var i=0;i<sub_widget_conts.length;i++){
								widgets_more = widgets_more + sub_widget_conts[i];
							}
							
							atributos['widget'] = '<div class="b2make-'+p.type+'">'+widgets_more+'</div>'+(loading_new ? '<div class="b2make-widget-loading"></div>' : '');
						}
						
						if(data) atributos.data = data;
						if(position) atributos.position = position;
						
						widget_generic_add(atributos);
						
						return;
					}
				});
		}
		
		holder_widget_update(false);
		if(p.type == 'conteiner')conteiner_position();
	}
	
	function widget_open(e,p){
		e.stopPropagation();
		
		if(b2make.conteiner_show){
			conteiner_close_all();
		}
		
		if(p.nao_conteiner){
			b2make.conteiner_obj = $("#"+p.id).parent().get(0);
			
			if($(b2make.conteiner_obj).myAttr('data-type') == 'conteiner-area') b2make.conteiner_obj = $(b2make.conteiner_obj).parent().get(0);
			
			conteiner_open();
			
			b2make.conteiner_child_obj = $("#"+p.id).get(0);
			$("#"+p.id).css('cursor','move');
			$.conteiner_child_open({select:true});
		} else {
			b2make.conteiner_obj = $("#"+p.id).get(0);
			$("#"+p.id).css('cursor','move');
			conteiner_open();
		}
	}
	
	$.widget_specific_options_open = function(){
		var type = b2make.widget_specific_type;
		
		if(b2make.widget_specific_options_active)$(b2make.widget_specific_options_active).appendTo(b2make.widget_options_hide);
		
		switch(type){
			case 'texto': $(b2make.widget_options_hide).find(b2make.woc_specific_texto).appendTo(b2make.woc_specific); b2make.widget_specific_options_active = b2make.woc_specific_texto; break;
			case 'facebook': $(b2make.widget_options_hide).find('#b2make-widget-options-facebook').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-facebook'; break;
			case 'imagem': $(b2make.widget_options_hide).find('#b2make-widget-options-imagem').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-imagem'; break;
			case 'twitter': $(b2make.widget_options_hide).find('#b2make-widget-options-twitter').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-twitter'; break;
			case 'soundcloud': $(b2make.widget_options_hide).find('#b2make-widget-options-soundcloud').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-soundcloud'; break;
			case 'iframe': $(b2make.widget_options_hide).find('#b2make-widget-options-iframe').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-iframe'; break;
			case 'galeria': $(b2make.widget_options_hide).find('#b2make-widget-options-galeria').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-galeria'; break;
			case 'player': $(b2make.widget_options_hide).find('#b2make-widget-options-player').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-player'; break;
			case 'agenda': $(b2make.widget_options_hide).find('#b2make-widget-options-agenda').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-agenda'; break;
			case 'slideshow': $(b2make.widget_options_hide).find('#b2make-widget-options-slideshow').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-slideshow'; break;
			case 'albumfotos': $(b2make.widget_options_hide).find('#b2make-widget-options-albumfotos').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-albumfotos'; break;
			case 'albummusicas': $(b2make.widget_options_hide).find('#b2make-widget-options-albummusicas').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-albummusicas'; break;
			case 'redessociais': $(b2make.widget_options_hide).find('#b2make-widget-options-redessociais').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-redessociais'; break;
			case 'menu': $(b2make.widget_options_hide).find('#b2make-widget-options-menu').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-menu'; break;
			case 'instagram': $(b2make.widget_options_hide).find('#b2make-widget-options-instagram').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-instagram'; break;
			case 'addthis': $(b2make.widget_options_hide).find('#b2make-widget-options-addthis').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-addthis'; break;
			case 'youtube': $(b2make.widget_options_hide).find('#b2make-widget-options-youtube').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-youtube'; break;
			case 'download': $(b2make.widget_options_hide).find('#b2make-widget-options-download').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-download'; break;
			case 'services': $(b2make.widget_options_hide).find('#b2make-widget-options-services').appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-services'; break;
			default: $(b2make.widget_options_hide).find('#b2make-widget-options-'+type).appendTo(b2make.woc_specific); b2make.widget_specific_options_active = '#b2make-widget-options-'+type;
		}
		
		$(b2make.woc_specific).show();
		b2make.widget_specific_options_open = true;
	}
	
	function widget_specific_options_close(){
		$(b2make.woc_specific).hide();
		b2make.widget_specific_options_open = false;
	}
	
	$.widget_sub_options_open = function(){
		var height_holder = (b2make.widget_sub_options_holder_height_user ? b2make.widget_sub_options_holder_height_user : b2make.widget_sub_options_height);
		var width_holder = parseInt(b2make.widget_sub_options_holder_width_user ? b2make.widget_sub_options_holder_width_user : b2make.widget_sub_options_width);
		var type = b2make.widget_sub_options_type;
		var lightbox_back_btn = (b2make.widget_sub_options_back_button ? b2make.widget_sub_options_back_button : false);
		var title_user = (b2make.widget_sub_options_title_user ? b2make.widget_sub_options_title_user : false);
		var holder_obj = b2make.widget_sub_options_obj;
		
		if(!b2make.widget_sub_options_border) b2make.widget_sub_options_border = $(b2make.widget_sub_options).css('borderBottom');
		if(b2make.widget_sub_options_active)$(b2make.widget_sub_options_active).appendTo(b2make.widget_options_hide);
		
		switch(type){
			case 'texto':
			case 'conteiner':
			case 'youtube':
			case 'redessociaisimg':
			case 'foto-perfil':
			case 'foto-segmento':
			case 'foto-template':
			case 'imagem':			
				$(b2make.widget_options_hide).find(b2make.widget_sub_options_biblioteca_imagens).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_biblioteca_imagens;
			break;
			case 'favicon':$(b2make.widget_options_hide).find(b2make.widget_sub_options_biblioteca_imagens).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_biblioteca_imagens; lightbox_back_btn = 'config'; break;
			case 'galeria': $(b2make.widget_options_hide).find(b2make.widget_sub_options_imagem).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_imagem; break;
			case 'slideshow': $(b2make.widget_options_hide).find(b2make.widget_sub_options_galeria).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_galeria; break;
			case 'albumfotos': $(b2make.widget_options_hide).find(b2make.widget_sub_options_album_fotos).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_album_fotos; break;
			case 'player': $(b2make.widget_options_hide).find(b2make.widget_sub_options_player).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_player; break;
			case 'agenda': $(b2make.widget_options_hide).find(b2make.widget_sub_options_agenda).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_agenda; break;
			case 'albummusicas': $(b2make.widget_options_hide).find(b2make.widget_sub_options_album_musicas).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_album_musicas; break;
			case 'redessociais': $(b2make.widget_options_hide).find(b2make.widget_sub_options_redessociais).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_redessociais; break;
			case 'menu': $(b2make.widget_options_hide).find(b2make.widget_sub_options_menu).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = b2make.widget_sub_options_menu; break;
			case 'menu-bolinha-layout': $(b2make.widget_options_hide).find('#b2make-widget-sub-options-menu-bolinhas-layout').appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-widget-sub-options-menu-bolinhas-layout'; lightbox_back_btn = 'config'; break;
			case 'menu-bolinha-areas': $(b2make.widget_options_hide).find('#b2make-widget-sub-options-menu-bolinhas-areas').appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-widget-sub-options-menu-bolinhas-areas'; pagina_menu_bolinhas_areas_open(); lightbox_back_btn = 'config'; break;
			case 'importar-codigo-html': $(b2make.widget_options_hide).find('#b2make-widget-sub-options-importar-codigo-html').appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-widget-sub-options-importar-codigo-html'; lightbox_back_btn = 'config'; break;
			case 'download': $(b2make.widget_options_hide).find('#b2make-widget-sub-options-arquivos').appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-widget-sub-options-arquivos'; break;
			case 'conteiner-banner': $(b2make.widget_options_hide).find('#b2make-conteiner-banners').appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-conteiner-banners'; break;
			case 'conteiner-global': $(b2make.widget_options_hide).find('#b2make-conteiner-global').appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-conteiner-global'; break;
			case 'conteiner-banner-config': $(b2make.widget_options_hide).find('#b2make-conteiner-banners-config').appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-conteiner-banners-config'; break;
			case 'sub-options-custom': $(b2make.widget_options_hide).find($(holder_obj).myAttr('data-sub-options-cont')).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = $(holder_obj).myAttr('data-sub-options-cont'); break;
			default: $(b2make.widget_options_hide).find('#b2make-widget-sub-options-'+type).appendTo(b2make.widget_sub_options_holder); b2make.widget_sub_options_active = '#b2make-widget-sub-options-'+type;
		}
		
		if(b2make.widget_sub_options_active == b2make.widget_sub_options_biblioteca_imagens){
			var obj = b2make.conteiner_obj;
			var obj_filho = b2make.conteiner_child_obj;
			
			$('#b2make-biblioteca-imagens-lista .b2make-image-holder').each(function(){
				if($(this).hasClass('b2make-image-holder-clicked')){
					$(this).removeClass('b2make-image-holder-clicked');
				}
			});
			
			if(obj_filho){
				switch(b2make.conteiner_child_type){
					case 'texto':
						if($(obj_filho).myAttr('data-image-id')){
							$('.b2make-image-holder[data-image-id="'+$(obj_filho).myAttr('data-image-id')+'"]').addClass('b2make-image-holder-clicked');
						}
					break;
					case 'imagem':
						if($(obj_filho).myAttr('data-image-id')){
							$('.b2make-image-holder[data-image-id="'+$(obj_filho).myAttr('data-image-id')+'"]').addClass('b2make-image-holder-clicked');
						}
					break;
					
				}
			} else {
				if($(obj).myAttr('data-image-id')){
					$('.b2make-image-holder[data-image-id="'+$(obj).myAttr('data-image-id')+'"]').addClass('b2make-image-holder-clicked');
				}
			}
		}
		
		if(b2make.widget_sub_options_active == '#b2make-widget-sub-options-arquivos'){
			var obj_filho = b2make.conteiner_child_obj;
			
			$('#b2make-arquivos-lista .b2make-arquivo-holder').each(function(){
				if($(this).hasClass('b2make-arquivo-holder-clicked')){
					$(this).removeClass('b2make-arquivo-holder-clicked');
				}
			});
			
			switch(b2make.conteiner_child_type){
				case 'download':
					if($(obj_filho).myAttr('data-arquivo-id')){
						$('.b2make-arquivo-holder[data-arquivo-id="'+$(obj_filho).myAttr('data-arquivo-id')+'"]').addClass('b2make-arquivo-holder-clicked');
					}
				break;
			}
		}

		$(b2make.widget_sub_options_holder).show();
		
		$(b2make.widget_sub_options_holder).height(height_holder+200);
		$(b2make.widget_sub_options_holder).width(width_holder);
		
		b2make.widget_sub_options_open = true;
		
		if(b2make.widget_add_sub_options_open || b2make.widget_edit_sub_options_open){
			var title = '';
			
			switch(type){
				case 'texto': 
				case 'conteiner': 
				case 'youtube': 
				case 'favicon': 
				case 'foto-perfil': 
				case 'foto-segmento': 
				case 'foto-template': 
				case 'imagem': 
				case 'galeria': 
					title = b2make.msgs.imagemTitle;
				break;
				case 'albumfotos':
					title = b2make.msgs.albumfotosTitle;
				break;
				case 'slideshow': 
					title = b2make.msgs.slideshowTitle;
				break;
				case 'player': 
					title = b2make.msgs.playerTitle;
				break;
				case 'albummusicas': 
					title = b2make.msgs.albummusicasTitle;
				break;
				case 'redessociaisimg': 
					title = b2make.msgs.redessociaisimgTitle;
				break;
				case 'redessociais': 
					title = b2make.msgs.redessociaisTitle;
				break;
				case 'menu': 
					title = b2make.msgs.areaTitle;
				break;
				case 'menu-bolinha-areas': 
					title = b2make.msgs.menuBolinhaAreaTitle;
				break;
				case 'menu-bolinha-layout': 
					title = b2make.msgs.menuBolinhaLayoutTitle;
				break;
				case 'importar-codigo-html': 
					title = b2make.msgs.importarPaginaB2makeTitle;
				break;
				case 'download': 
					title = b2make.msgs.downloadLayoutTitle;
				break;
				case 'conteiner-banner': 
					title = b2make.msgs.conteinerBannerEditarTitle;
				break;
				case 'conteiner-global': 
					title = b2make.msgs.conteinerGlobalTitle;
				break;
				case 'conteiner-banner-config': 
					title = b2make.msgs.conteinerBannerConfigTitle;
				break;
				case 'sub-options-custom': 
					title = $(holder_obj).myAttr('data-sub-options-title');
				break;
				default:
					var obj = b2make.conteiner_child_obj;
					
					if(title_user){
						title = title_user;
					} else if($(obj).myAttr('data-ler-script')){
						if(!$(obj).myAttr('data-widget-title')){
							function capitalizeFirstLetter(string) {
								string = string.replace(/-/gi,' ');
								return string.charAt(0).toUpperCase() + string.slice(1);
							}
							
							title = capitalizeFirstLetter(type);
						} else {
							title = $(obj).myAttr('data-widget-title');
						}
					} else {
						title = b2make.msgs[type+'Title'];
					}
			}
			
			var lightbox_obj = {
				title: title,
				lightbox_back_btn: lightbox_back_btn,
			};
			
			if(b2make.widget_sub_options_lightbox_width){
				lightbox_obj.width = b2make.widget_sub_options_lightbox_width;
				b2make.widget_sub_options_lightbox_width = false;
			}
			
			if(b2make.widget_sub_options_lightbox_height){
				lightbox_obj.height = b2make.widget_sub_options_lightbox_height;
				b2make.widget_sub_options_lightbox_height = false;
			}
			
			if(holder_obj)
			if($(holder_obj).myAttr('data-lightbox-btns-dont-close')){
				var btns = $(holder_obj).myAttr('data-lightbox-btns-dont-close');
				var btns_arr = btns.split(';');
				var specific_buttons_dont_close = new Array();
				
				for(var i=0;i<btns_arr.length;i++){
					specific_buttons_dont_close.push(btns_arr[i]);
				}
				
				lightbox_obj.specific_buttons_dont_close = specific_buttons_dont_close;
			}
			
			if(holder_obj)
			if($(holder_obj).myAttr('data-lightbox-btns')){
				var btns = $(holder_obj).myAttr('data-lightbox-btns');
				var btns_arr = btns.split(';');
				var specific_buttons = new Array();
				
				for(var i=0;i<btns_arr.length;i++){
					var btn = btns_arr[i].split('=>');
					
					specific_buttons.push({
						title : btn[0],
						calback : btn[1]
					});
				}
				
				lightbox_obj.specific_buttons = specific_buttons;
			}
			
			lightbox_open(lightbox_obj);

			b2make.widget_add_sub_options_open = false;
			b2make.widget_edit_sub_options_open = false;
			
			b2make.widget_sub_options_holder_height_user = false;
			b2make.widget_sub_options_holder_width_user = false;		
			b2make.widget_sub_options_back_button = false;		
			b2make.widget_sub_options_title_user = false;		
		}

	}
	
	function widget_sub_options_close(){
		lightbox_close();
		
		b2make.widget_sub_options_open = false;
		b2make.conteiner_show_after = false;
		b2make.widget_sub_options_up_clicked_2 = false;
		b2make.sub_options_conteiner_close = false;
	}
	
	function widget_sub_options_close_button(){
		$(b2make.widget_sub_options).hide();
		$(b2make.widget_sub_options_down).hide();
	}
	
	function widget_mask_show(){
		if(!b2make.selecionador_objetos_mask_force_hide){
			$('.b2make-widget-mask').show();
			$('#b2make-selecionador-objetos-mask').show();
			b2make.widget_mask_hide = false;
			b2make.selecionador_objetos_mask_force_hide = false;
		}
	}
	
	function widgets_resize(){
		switch(b2make.conteiner_child_type){
			case 'galeria':
				var obj = b2make.conteiner_child_obj;
				
				if(b2make.conteiner_child_obj_custom){obj = b2make.conteiner_child_obj_custom; b2make.conteiner_child_obj_custom = false;}
				
				var holder = $(obj).find('div.b2make-widget-out').find('div.b2make-galeria-widget-holder');
				var prev = $(obj).find('div.b2make-widget-out').find('div.b2make-gwi-prev');
				var next = $(obj).find('div.b2make-widget-out').find('div.b2make-gwi-next');
				var img = holder.find('div:first-child');
				var num_imgs = holder.find('div').length;
				var obj_width = parseInt($(obj).outerWidth());
				var obj_height = parseInt($(obj).outerHeight());
				var prev_width = parseInt(prev.css('width'));
				var next_width = parseInt(next.css('width'));
				var img_width = parseInt(img.outerWidth(true));
				var img_height = parseInt(img.outerHeight(true));
				var area_imgs = img_width * img_height * num_imgs + prev_width * obj_height + next_width * obj_height;
				var area_holder = obj_width * obj_height;
				
				if(area_imgs < area_holder){
					prev.hide();
					next.hide();
					holder.css('width','100%');
				} else {
					prev.show();
					next.show();
					holder.css('width','calc(100% - 73px)');
				}
			break;
			case 'agenda':
				var obj = b2make.conteiner_child_obj;
				
				if(b2make.conteiner_child_obj_custom){obj = b2make.conteiner_child_obj_custom; b2make.conteiner_child_obj_custom = false;}
				
				var holder = $(obj).find('div.b2make-widget-out').find('div.b2make-eventos-widget-holder');
				var prev = $(obj).find('div.b2make-widget-out').find('div.b2make-wsoae-prev');
				var next = $(obj).find('div.b2make-widget-out').find('div.b2make-wsoae-next');
				var img = holder.find('div.b2make-widget-eventos:first-child');
				if(img.length > 0){
					var num_imgs = holder.find('div.b2make-widget-eventos').length;
					var obj_width = parseInt($(obj).outerWidth());
					var obj_height = parseInt($(obj).outerHeight());
					var prev_width = parseInt(prev.css('width'));
					var next_width = parseInt(next.css('width'));
					var img_width = parseInt(img.outerWidth(true));
					var img_height = parseInt(img.outerHeight(true));
					var area_imgs = img_width * img_height * num_imgs + prev_width * obj_height + next_width * obj_height;
					var area_holder = obj_width * obj_height;
					
					if(area_imgs < area_holder){
						prev.hide();
						next.hide();
						holder.css('width','100%');
					} else {
						prev.show();
						next.show();
						holder.css('width','calc(100% - 73px)');
					}
				} else {
					prev.hide();
						next.hide();
						holder.css('width','100%');
				}
			break;
			case 'slideshow':
				var obj = b2make.conteiner_child_obj;
				
				if(b2make.conteiner_child_obj_custom){obj = b2make.conteiner_child_obj_custom; b2make.conteiner_child_obj_custom = false;}
				
				var obj_height = parseInt($(obj).outerHeight());
				var width_total = 0;
				
				$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image').each(function(){
					var width = parseInt($(this).myAttr('data-image-width'));
					var height = parseInt($(this).myAttr('data-image-height'));
					var obj_width = Math.floor((obj_height * width) / height);
					
					$(this).width(obj_width);
					$(this).height(obj_height);
					
					width_total = width_total + obj_width;
				});
				
				$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').width(width_total);
			
			break;
			default:
				var obj = b2make.conteiner_child_obj;
				
				if(b2make.conteiner_child_obj_custom){obj = b2make.conteiner_child_obj_custom; b2make.conteiner_child_obj_custom = false;}
				
				if($(obj).hasClass('b2make-pagina-mestre')){
					var obj_height = parseInt($(obj).outerHeight());
					$(obj).find('.b2make-widget-out').find('.b2make-library-loading').height(obj_height);
				} else {
					$('#b2make-listener').trigger('widgets-resize');
				}
		}
	}
	
	function widgets_update(p){
		if(!p)p = {};
		
		switch(p.type){
			case 'eventos':
				$('div.b2make-widget[data-type="agenda"]').each(function(){
					var id_local = $(this).myAttr('id');
					var agenda_id = $(this).myAttr('data-agenda-id');
					agenda_widget_create({
						agenda_id : agenda_id,
						conteiner_child_obj : '#'+id_local
					});
				});
			break;
			case 'imagem-del':
				var id = p.id;
				var child_obj = b2make.conteiner_child_obj;
				var obj = b2make.conteiner_obj;
				
				$('div.b2make-widget[data-image-id="'+id+'"]').each(function(){
					var type = $(this).myAttr('data-type');
					
					switch(type){
						case 'texto':
						case 'conteiner':
							$(this).css('backgroundImage','none');
							$(this).css('backgroundSize','100% auto');
							$(this).myAttr('data-image-id',null);
							$(this).myAttr('data-galeria-id',null);
							$(this).myAttr('data-type-image-background',null);
							
							if(type == 'texto' && $(this).myAttr('id') == $(child_obj).myAttr('id')){
								$('#b2make-wot-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
								$('#b2make-wot-bg-image').css('background-size','auto auto');
							}
							if(type == 'conteiner' && $(this).myAttr('id') == $(obj).myAttr('id')){
								$('#b2make-conteiner-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
								$('#b2make-conteiner-bg-image').css('background-size','auto auto');
							}
							
						break;
						case 'imagem':
							$(this).myAttr('data-image-id',null);
							$(this).myAttr('data-galeria-id',null);
							$(this).html(b2make.imagem.value);
							$(this).css('backgroundColor',b2make.imagem.backgroundColor);
						break;
					}
				});
				
				$('div.b2make-galeria-widget-image[data-image-id="'+id+'"]').each(function(){
					var id_local = $(this).parent().parent().parent().myAttr('id');
					var galeria_id = $(this).parent().parent().parent().myAttr('data-galeria-id');
					galeria_widget_create({
						galeria_id : galeria_id,
						conteiner_child_obj : '#'+id_local
					});
				});
			break;
			case 'arquivo-del':
				var id = p.id;
				var child_obj = b2make.conteiner_child_obj;
				
				$('div.b2make-widget[data-arquivo-id="'+id+'"]').each(function(){
					var type = $(this).myAttr('data-type');
					
					switch(type){
						case 'download':
							$(this).find('.b2make-widget-out').find('.b2make-download-widget-link').myAttr('href',location.href+'b2make-arquivo-download.zip');
							$(this).myAttr('data-arquivo-id',null);
						break;
					}
				});
			break;
			case 'galeria-del':
				var id = p.id;
				
				$('div.b2make-widget[data-galeria-id="'+id+'"]').each(function(){
					var type = $(this).myAttr('data-type');
					
					switch(type){
						case 'texto':
						case 'conteiner':
							$(this).css('backgroundImage','none');
							$(this).css('backgroundSize','100% auto');
							$(this).myAttr('data-image-id',null);
							$(this).myAttr('data-galeria-id',null);
							$(this).myAttr('data-type-image-background',null);
						break;
						case 'imagem':
							$(this).myAttr('data-image-id',null);
							$(this).myAttr('data-galeria-id',null);
							$(this).html(b2make.imagem.value);
							$(this).css('backgroundColor',b2make.imagem.backgroundColor);
						break;
						case 'galeria':
							var id_local = $(this).myAttr('id');
							var galeria_id = b2make.galerias_atual;
							galeria_widget_create({
								galeria_id : galeria_id,
								conteiner_child_obj : '#'+id_local
							});
						break;
					}
				});
			break;
			case 'galeria-add':
				var id = p.id;
				
				switch(b2make.conteiner_child_type){
					case 'galeria':
						var galeria_id = b2make.galerias_atual;
						galeria_widget_create({
							galeria_id : galeria_id,
						});
					break;
				}
			break;
			case 'imagem-uploaded':
				var id = p.id;
				
				$('div.b2make-widget[data-galeria-id="'+id+'"]').each(function(){
					var type = $(this).myAttr('data-type');
					
					switch(type){
						case 'galeria':
							var id_local = $(this).myAttr('id');
							var galeria_id = id;
							galeria_widget_create({
								galeria_id : galeria_id,
								conteiner_child_obj : '#'+id_local
							});
						break;
					}
				});
			break;
			case 'album-fotos-imagem-uploaded':
				var id = p.id;
				var url = p.url;
				var dados = p.dados;
				
				$('div.b2make-albumfotos-widget-menu-entry[data-album-fotos-id="'+id+'"]').each(function(){
					var holder = $(this).parent().parent().find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini');
					var found = false;
					var found2 = false;
					
					holder.find('.b2make-albumfotos-widget-image-mini').each(function(){
						if($(this).myAttr('data-album-fotos-id') == id){
							found = true;
						}
						if($(this).myAttr('data-album-fotos-id') != id){
							found2 = true;
						}
						
						if(found && found2){
							$(this).before('<div class="b2make-albumfotos-widget-image-mini" data-id="'+dados.id+'" data-imagem="'+dados.imagem+'" data-album-fotos-id="'+id+'" style="background-image:url('+dados.mini+');"></div>');
							return false;
						}
					});
				});
				
				$('div.b2make-albumfotos-widget-image[data-album-fotos-id="'+id+'"]').each(function(){
					var urls = $(this).myAttr('data-imagens-urls');
					
					if(urls){
						$(this).myAttr('data-imagens-urls',urls+','+url);
					} else {
						$(this).myAttr('data-imagens-urls',url);
						$(this).css('backgroundImage','url('+url+')');
					}
				});
			break;
			case 'album-fotos-imagem-del':
				var id = p.id;
				var id_album = p.id_album;
				var url_image = p.url;
				
				var url,id_new;
				var img_url = $('div#b2make-album-fotos-lista-images div.b2make-album-fotos-image-holder:first-child').myAttr('data-image-url');
				var img = $('div#b2make-album-fotos-lista-images div.b2make-album-fotos-image-holder:first-child');
				
				if(img_url){
					url_new = img.myAttr('data-image-url');
					id_new = img.myAttr('data-image-id');
				} else {
					url_new = location.href+'images/b2make-album-sem-imagem.png?v=2';
				}
				
				$('div.b2make-albumfotos-widget-image[data-album-fotos-imagem-id="'+id+'"]').each(function(){
					$(this).css('backgroundImage','url('+url_new+')');
					
					if(id_new){
						$(this).myAttr('data-album-fotos-imagem-id',id_new);
					} else {
						$(this).myAttr('data-album-fotos-imagem-id',null);
					}
				});
				
				$('div.b2make-albumfotos-widget-image[data-album-fotos-id="'+id_album+'"]').each(function(){
					var urls = $(this).myAttr('data-imagens-urls');
					
					if(urls){
						var url_arr = urls.split(',');
						var url_final = '';
						
						for(var i=0;i<url_arr.length;i++){
							if(url_arr[i] != url_image){
								url_final = url_final + (url_final.length > 0 ? ',' : '') + url_arr[i];
							}
						}
						
						$(this).myAttr('data-imagens-urls',url_final);
					}
				});
				
				$('div.b2make-albumfotos-widget-menu-entry[data-album-fotos-id="'+id_album+'"]').each(function(){
					var holder = $(this).parent().parent().find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini');
					
					if(holder.find('.b2make-albumfotos-widget-image-mini[data-id="'+id+'"]').length > 0){
						albumfotos_widget_album_update({obj:$(this).parent().parent().parent().parent().get(0)});
					}
				});
			break;
			case 'album-fotos-delete':
				var id = p.id;
				
				$('div.b2make-albumfotos-widget-image[data-album-fotos-id="'+id+'"]').each(function(){
					$(this).remove();
				});
				
				$('div.b2make-albumfotos-widget-menu-entry[data-album-fotos-id="'+id+'"]').each(function(){
					albumfotos_widget_album_update({obj:$(this).parent().parent().parent().parent().get(0)});
				});
			break;
			case 'album-fotos-edit':
				var id = p.id;
				var nome = p.nome;
				
				$('div.b2make-albumfotos-widget-image[data-album-fotos-id="'+id+'"]').each(function(){
					$(this).find('.b2make-albumfotos-widget-titulo').html(nome);
				});
				
				$('div.b2make-albumfotos-widget-menu-entry[data-album-fotos-id="'+id+'"]').each(function(){
					albumfotos_widget_album_update({obj:$(this).parent().parent().parent().parent().get(0)});
				});
			break;
			case 'album-fotos-legenda-edit':
				var id = p.id;
				var legenda = p.legenda;
				
				$('div.b2make-albumfotos-widget-image[data-album-fotos-id="'+id+'"]').each(function(){
					$(this).myAttr('data-album-fotos-legenda',legenda);
				});
			break;
			case 'slide-show-imagem-uploaded':
				
				var id = p.id;
				var dados = p.dados;
				var url = p.dados.imagem;
				
				$('div.b2make-widget[data-type="slideshow"][data-slide-show-id="'+id+'"]').each(function(){
					var urls = $(this).myAttr('data-imagens-urls');
					
					if(urls){
						if(urls == location.href+'images/b2make-album-sem-imagem.png?v=2'){
							$(this).myAttr('data-imagens-urls',url);
						} else {
							$(this).myAttr('data-imagens-urls',urls+','+url);
						}
					} else {
						$(this).myAttr('data-imagens-urls',url);
					}
					
					$(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('#b2make-slideshow-widget-imagem-0').remove();
					$(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').append($('<div id="b2make-slideshow-widget-imagem-'+dados.id+'" class="b2make-slideshow-widget-image" data-image-id="'+dados.id+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" style="background-image:url('+dados.imagem+');"></div>'));
					
					var obj_height = parseInt($(this).outerHeight());
					var count = 0;
					
					$(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image').each(function(){
						$(this).width(obj_height);
						$(this).height(obj_height);
						count++;
					});
					
					$(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').width(count*obj_height);
				
				});
			break;
			case 'slide-show-imagem-del':
				var id = p.id;
				var id_slide = p.id_slide;
				var url_image = p.url;
				
				var url,id_new;
				var img_url = $('div#b2make-album-fotos-lista-images div.b2make-album-fotos-image-holder:first-child').myAttr('data-image-url');
				var img = $('div#b2make-album-fotos-lista-images div.b2make-album-fotos-image-holder:first-child');
				
				if(img_url){
					url_new = img.myAttr('data-image-url');
					id_new = img.myAttr('data-image-id');
				} else {
					url_new = location.href+'images/b2make-album-sem-imagem.png?v=2';
				}
				
				$('div.b2make-slideshow-widget-image[data-image-id="'+id+'"]').each(function(){
					$(this).remove();
				});
				
				$('div.b2make-widget[data-type="slideshow"][data-slide-show-id="'+id_slide+'"]').each(function(){
					var urls = $(this).myAttr('data-imagens-urls');
					
					if(urls){
						var url_arr = urls.split(',');
						var url_final = '';
						
						for(var i=0;i<url_arr.length;i++){
							if(url_arr[i] != url_image){
								url_final = url_final + (url_final.length > 0 ? ',' : '') + url_arr[i];
							}
						}
						
						$(this).myAttr('data-imagens-urls',url_final);
					}
					
					if($(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image').length == 0){
						var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
						$(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').append($('<div id="b2make-slideshow-widget-imagem-0" class="b2make-slideshow-widget-image" data-image-id="0" data-image-url="'+imagem+'" data-image-width="159" data-image-height="159" style="background-image:url('+imagem+');"></div>'));
						$(this).myAttr('data-imagens-urls',imagem);
					}
					
					var obj_height = parseInt($(this).outerHeight());
					var count = 0;
					
					$(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image').each(function(){
						$(this).width(obj_height);
						$(this).height(obj_height);
						count++;
					});
					
					$(this).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').width(count*obj_height);
				});
			break;
			case 'slide-show-delete':
				var id = p.id;
				
				$('div.b2make-widget[data-type="slideshow"][data-slide-show-id="'+id+'"]').each(function(){
					$(this).find('div.b2make-widget-out').html('<div class="b2make-slideshow-widget-holder"></div>');
				});
			break;
			case 'player-musicas-mp3-uploaded':
				var id = p.id;
				var dados = p.dados;
				
				$('div.b2make-widget[data-type="player"][data-player-musicas-id="'+id+'"]').each(function(){
					var list = $(this).myAttr('data-music-list');
					var player_id = '#b2make-jplayer-'+$(this).myAttr('id');
					
					if(list){
						$(this).myAttr('data-music-list',list+'<;>'+dados.nome_original+'<,>'+dados.mp3);
						
						b2make.player[player_id].lista_musicas.push(dados.mp3);
						b2make.player[player_id].lista_musicas_tit.push(dados.nome_original);
						b2make.player[player_id].total_musicas++;
					} else {
						player_widget_create({
							player_id:b2make.player_musicas_atual,
							conteiner_child_obj:this
						});
					}
				
				});
			break;
			case 'player-musicas-mp3-del':
				var id = p.id;
				var id_player = p.id_player;
				var url_mp3 = p.url;
				
				$('div.b2make-widget[data-type="player"][data-player-musicas-id="'+id_player+'"]').each(function(){
					var list = $(this).myAttr('data-music-list');
					var player_id = '#b2make-jplayer-'+$(this).myAttr('id');
					var player_control = '#b2make-player-control-'+$(this).myAttr('id')+' ';
					
					if(list){
						var music_arr = list.split('<;>');
						var list_final = '';
						var change_music = false;
						
						if(music_arr.length > 1){
							var lista_musicas_tit = new Array();
							var lista_musicas = new Array();
							var musica_num = 0;
							
							for(var i=0;i<music_arr.length;i++){
								var music_parts = music_arr[i].split('<,>');
								if(music_parts[1] != url_mp3){
									list_final = list_final + (list_final.length > 0 ? '<;>' : '') + music_arr[i];
									lista_musicas_tit.push(music_parts[0]);
									lista_musicas.push(music_parts[1]);
								} else {
									musica_num = i;
									if(i == b2make.player[player_id].num_musica){
										change_music = true;
									}
								}
							}
							
							b2make.player[player_id].total_musicas--;
							
							b2make.player[player_id].lista_musicas_tit = lista_musicas_tit;
							b2make.player[player_id].lista_musicas = lista_musicas;
							
							$(this).myAttr('data-music-list',list_final);
							
							if(change_music){
								if(b2make.player_playing == player_id && !b2make.player[player_id].player_pause){
									$(player_id).jPlayer("stop");
								}
							
								$(player_id).jPlayer("setMedia", {
									mp3: b2make.player[player_id].lista_musicas[0]
								});
								
								if(b2make.player_playing == player_id && !b2make.player[player_id].player_pause){
									$(player_id).jPlayer("play");
								}
								
								$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[0]);
								$(player_control+".b2make-player-time").text($.jPlayer.convertTime(0));
							} else {
								if(b2make.player[player_id].num_musica > musica_num){
									b2make.player[player_id].num_musica--;
								}
							}
						} else {
							$(this).myAttr('data-music-list',null);
							$(player_id).jPlayer("stop");
							
							player_widget_create({
								player_id:b2make.player_musicas_atual,
								conteiner_child_obj:this
							});
						}
					}
				
				});
			break;
			case 'player-musicas-delete':
				var id = p.id;
			
				$('div.b2make-widget[data-type="player"][data-player-musicas-id="'+id+'"]').each(function(){
					var player_id = '#b2make-jplayer-'+$(this).myAttr('id');
					
					$(this).myAttr('data-music-list',null);
					$(player_id).jPlayer("stop");
					
					player_widget_create({
						player_id:b2make.player_musicas_atual,
						conteiner_child_obj:this
					});
				});
			break;
			case 'album-musicas-mp3-uploaded':
				var id = p.id;
				var dados = p.dados;
				
				$('div.b2make-albummusicas-widget-album[data-album-musicas-id="'+id+'"]').each(function(){
					var list = $(this).myAttr('data-music-list');
					var obj = $(this).parent().parent().parent().get(0);
					var obj_id = $(obj).myAttr('id');
					var player_id = '#b2make-jplayer-player-'+obj_id+'-'+id;
					
					if(list){
						var num_musica = b2make.player[player_id].total_musicas;
						
						b2make.player[player_id].lista_musicas.push(dados.mp3);
						b2make.player[player_id].lista_musicas_tit.push(musica_tit);
						b2make.player[player_id].total_musicas++;
						
						var musica_tit = (num_musica < 9 ? '0' : '') + (num_musica+1) + ' - ' + dados.nome_original;
						var musica_link = '<div class="b2make-albummusicas-widget-mp3" data-album-musicas-id="'+id+'" data-musica-num="'+num_musica+'">'+musica_tit+'</div>';
						
						$(this).myAttr('data-music-list',list+'<;>'+musica_tit+'<,>'+dados.mp3);
						$(this).find('div.b2make-albummusicas-widget-list-mp3s').append(musica_link);
					} else {					
						var nome = $(this).find('div.b2make-albummusicas-widget-titulo').html();
						
						albummusicas_widget_album_add({
							albummusicas_id: id,
							albummusicas_nome: nome,
							conteiner_child_obj: obj
						});
					}
				
				});
			break;
			case 'album-musicas-mp3-del':
				var id = p.id;
				var id_album = p.id_album;
				var url_mp3 = p.url;
				
				$('div.b2make-albummusicas-widget-album[data-album-musicas-id="'+id_album+'"]').each(function(){
					var list = $(this).myAttr('data-music-list');
					var obj = $(this).parent().parent().parent().get(0);
					var obj_id = $(obj).myAttr('id');
					var player_id = '#b2make-jplayer-player-'+obj_id+'-'+id_album;
					var player_control = '#b2make-player-control-'+obj_id+'-'+id_album+' ';
					
					if(list){
						var music_arr = list.split('<;>');
						var list_final = '';
						var change_music = false;
						
						if(music_arr.length > 1){
							var lista_musicas_tit = new Array();
							var lista_musicas = new Array();
							var musica_num = 0;
							var count = 0;
							
							for(var i=0;i<music_arr.length;i++){
								var music_parts = music_arr[i].split('<,>');
								if(music_parts[1] != url_mp3){
									list_final = list_final + (list_final.length > 0 ? '<;>' : '') + music_arr[i];
									lista_musicas_tit.push(music_parts[0]);
									lista_musicas.push(music_parts[1]);
									$(this).find('div.b2make-albummusicas-widget-list-mp3s').find('div.b2make-albummusicas-widget-mp3[data-musica-num="'+i+'"]').myAttr('data-musica-num',count);
									count++;
								} else {
									$(this).find('div.b2make-albummusicas-widget-list-mp3s').find('div.b2make-albummusicas-widget-mp3[data-musica-num="'+i+'"]').remove();
									musica_num = i;
									if(i == b2make.player[player_id].num_musica){
										change_music = true;
									}
								}
							}
							
							b2make.player[player_id].total_musicas--;
							
							b2make.player[player_id].lista_musicas_tit = lista_musicas_tit;
							b2make.player[player_id].lista_musicas = lista_musicas;
							
							$(this).myAttr('data-music-list',list_final);
							
							if(change_music){
								if(b2make.player_playing == player_id && !b2make.player[player_id].player_pause){
									$(player_id).jPlayer("stop");
								}
							
								$(player_id).jPlayer("setMedia", {
									mp3: b2make.player[player_id].lista_musicas[0]
								});
								
								if(b2make.player_playing == player_id && !b2make.player[player_id].player_pause){
									$(player_id).jPlayer("play");
								}
								
								$(player_control+".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[0]);
								$(player_control+".b2make-player-time").text($.jPlayer.convertTime(0));
								$(this).find('div.b2make-albummusicas-widget-list-mp3s').find('div.b2make-albummusicas-widget-mp3[data-musica-num="0"]').addClass('b2make-albummusicas-widget-playing');
							} else {
								if(b2make.player[player_id].num_musica > musica_num){
									b2make.player[player_id].num_musica--;
								}
							}
							
						} else {
							$(this).myAttr('data-music-list',null);
							$(player_id).jPlayer("stop");
							var nome = $(this).find('div.b2make-albummusicas-widget-titulo').html();
							
							albummusicas_widget_album_add({
								albummusicas_id: id_album,
								albummusicas_nome: nome,
								conteiner_child_obj: obj
							});
						}
					}
				
				});
			break;
			case 'album-musicas-delete':
				var id = p.id;
			
				$('div.b2make-albummusicas-widget-album[data-album-musicas-id="'+id+'"]').each(function(){
					var obj = $(this).parent().parent().parent().get(0);
					var obj_id = $(obj).myAttr('id');
					var player_id = '#b2make-jplayer-player-'+obj_id+'-'+id_album;
					
					$(this).myAttr('data-music-list',null);
					$(player_id).jPlayer("stop");
					
					$(this).remove();
				});
			break;
			case 'album-musicas-edit':
				var id = p.id;
				var nome = p.nome;
				
				$('div.b2make-albummusicas-widget-album[data-album-musicas-id="'+id+'"]').each(function(){
					$(this).find('.b2make-albummusicas-widget-titulo').html(nome);
				});
			break;
		}
	}
	
	$.widgets_read_google_font = function(p){
		switch(p.tipo){
			case 1:
				if(p.obj.myAttr('data-font-family')){
					var font_family = p.obj.myAttr('data-font-family');
					var found = false;
					$('.b2make-fonts-list li').each(function(){
						if($(this).html() == font_family || '"'+$(this).html()+'"' == font_family){
							found = true;
						}
					});
					
					if(!found){
						$.google_fonts_wot_load({
							family : font_family,
							nao_carregamento : true
						});
					}
				}
			break;
			case 2:
				var types = p.types;
				
				for(var i=0;i<types.length;i++){
					var type = types[i];
					
					
					if(p.obj.myAttr('data-'+type+'-font-family')){
						var font_family = p.obj.myAttr('data-'+type+'-font-family');
						var found = false;
						$('.b2make-fonts-list li').each(function(){
							if($(this).html() == font_family || '"'+$(this).html()+'"' == font_family){
								found = true;
							}
						});
						
						if(!found){
							$.google_fonts_wot_load({
								family : font_family,
								nao_carregamento : true
							});
						}
					}
				}
			break;
		}
	}
	
	$.google_fonts_wot_load = function(p){
		var found = false;
		
		if(!b2make.google_fonts_loaded){
			b2make.google_fonts_loaded = new Array();
		}
		
		for(var i=0;i<b2make.google_fonts_loaded.length;i++){
			if(b2make.google_fonts_loaded[i] == p.family){
				found = true;
				break;
			}
		}
		
		if(!found){
			b2make.google_fonts_loaded.push(p.family);
			WebFont.load({
				google: {
					families: [p.family]
				},
				loading: function() {if(!p.nao_carregamento)$.carregamento_open();},
				active: function() {if(!p.nao_carregamento)$.carregamento_close();},
				inactive: function() {
					$.dialogbox_open({
						msg: b2make.msgs.googleFontsInative
					});
					
					if(!p.nao_carregamento)$.carregamento_close();
				},
				fontloading: function(familyName, fvd) {},
				fontactive: function(familyName, fvd) {},
				fontinactive: function(familyName, fvd) {}
			});
		}
	}
	
	function widgets(){
		b2make.widgets = new Array();
		b2make.widgets_count = 0;
		b2make.arrow_fator = 1;
		if(!b2make.conteiner_colors)b2make.conteiner_colors = Array('rgb(232,233,235)','rgb(211,212,216)','rgb(190,191,196)','rgb(169,168,174)');
		if(!b2make.input_delay_timeout)b2make.input_delay_timeout = 400;
		if(!b2make.wom_name_max_lenght)b2make.wom_name_max_lenght = 100;
		if(!b2make.woc_name_max_lenght)b2make.woc_name_max_lenght = 100;
		if(!b2make.menu)b2make.menu = '#b2make-menu';
		if(!b2make.menu_mask)b2make.menu_mask = '#b2make-menu-mask';
		if(!b2make.site_conteiner)b2make.site_conteiner = '#b2make-site';
		if(!b2make.widget)b2make.widget = '.b2make-widget';
		if(!b2make.shadow)b2make.shadow = '#b2make-shadow';
		if(!b2make.widget_options)b2make.widget_options = '#b2make-widget-options';
		if(!b2make.widget_options_hide)b2make.widget_options_hide = '#b2make-widget-options-hide';
		if(!b2make.widget_sub_options)b2make.widget_sub_options = '#b2make-widget-sub-options';
		if(!b2make.widget_sub_options_up)b2make.widget_sub_options_up = '#b2make-widget-sub-options-up';
		if(!b2make.widget_sub_options_down)b2make.widget_sub_options_down = '#b2make-widget-sub-options-down';
		if(!b2make.widget_sub_options_holder)b2make.widget_sub_options_holder = '#b2make-widget-sub-options-holder';
		if(!b2make.widget_sub_options_biblioteca_imagens)b2make.widget_sub_options_biblioteca_imagens = '#b2make-widget-sub-options-biblioteca-imagens';
		if(!b2make.widget_sub_options_galeria)b2make.widget_sub_options_galeria = '#b2make-widget-sub-options-slide-show';
		if(!b2make.widget_sub_options_album_fotos)b2make.widget_sub_options_album_fotos = '#b2make-widget-sub-options-album-fotos';
		if(!b2make.widget_sub_options_imagem)b2make.widget_sub_options_imagem = '#b2make-widget-sub-options-imagem';
		if(!b2make.widget_sub_options_player)b2make.widget_sub_options_player = '#b2make-widget-sub-options-player';
		if(!b2make.widget_sub_options_album_musicas)b2make.widget_sub_options_album_musicas = '#b2make-widget-sub-options-album-musicas';
		if(!b2make.widget_sub_options_redessociais)b2make.widget_sub_options_redessociais = '#b2make-widget-sub-options-redessociais';
		if(!b2make.widget_sub_options_menu)b2make.widget_sub_options_menu = '#b2make-widget-sub-options-menu';
		if(!b2make.widget_sub_options_agenda)b2make.widget_sub_options_agenda = '#b2make-widget-sub-options-agenda';
		if(!b2make.widget_sub_options_time)b2make.widget_sub_options_time = 350;
		if(!b2make.widget_sub_options_margim)b2make.widget_sub_options_margim = 17;
		if(!b2make.wom_name)b2make.wom_name = '#b2make-wom-name';
		if(!b2make.woc_name)b2make.woc_name = '#b2make-woc-name';
		if(!b2make.woc_close)b2make.woc_close = '#b2make-woc-close';
		if(!b2make.woc_delete)b2make.woc_delete = '#b2make-woc-delete';
		if(!b2make.woc_delete_yes)b2make.woc_delete_yes = '.b2make-woc-delete-yes';
		if(!b2make.won_close)b2make.won_close = '#b2make-wom-close';
		if(!b2make.won_delete)b2make.won_delete = '#b2make-wom-delete';
		if(!b2make.won_delete_yes)b2make.won_delete_yes = '.b2make-wom-delete-yes';
		if(!b2make.won_position)b2make.won_position = '#b2make-wom-position';
		if(!b2make.won_position_up)b2make.won_position_up = '#b2make-wom-position-up';
		if(!b2make.won_position_down)b2make.won_position_down = '#b2make-wom-position-down';
		if(!b2make.woc_position_top)b2make.woc_position_top = '#b2make-woc-position-top-value';
		if(!b2make.woc_position_left)b2make.woc_position_left = '#b2make-woc-position-left-value';
		if(!b2make.won_height)b2make.won_height = '#b2make-wom-height-value';
		if(!b2make.woc_height)b2make.woc_height = '#b2make-woc-height-value';
		if(!b2make.woc_width)b2make.woc_width = '#b2make-woc-width-value';
		if(!b2make.woc_zindex_up)b2make.woc_zindex_up = '#b2make-woc-zindex-up';
		if(!b2make.woc_specific)b2make.woc_specific = '#b2make-woc-specific';
		if(!b2make.woc_specific_texto)b2make.woc_specific_texto = '#b2make-widget-options-text';
		if(!b2make.won_height_min_value)b2make.won_height_min_value = 20;
		if(!b2make.won_height_max_value)b2make.won_height_max_value = 9999;
		if(!b2make.woc_height_min_value)b2make.woc_height_min_value = 20;
		if(!b2make.woc_height_max_value)b2make.woc_height_max_value = 9999;
		if(!b2make.woc_width_min_value)b2make.woc_width_min_value = 20;
		if(!b2make.woc_width_max_value)b2make.woc_width_max_value = 2000;
		if(!b2make.widget_options_childreen)b2make.widget_options_childreen = '#b2make-widget-options-childreen';
		if(!b2make.fade_time)b2make.fade_time = 200;
		if(!b2make.widget_border)b2make.widget_border = 2;
		if(!b2make.conteiner_border_style)b2make.conteiner_border_style = b2make.widget_border+'px dotted #434142';
		if(!b2make.widget_margim_correcao)b2make.widget_margim_correcao = 10;
		if(!b2make.widget_move_pos_ajuste)b2make.widget_move_pos_ajuste = 50;
		if(!b2make.conteiner_height)b2make.conteiner_height = '300px';
		if(!b2make.msgs.textTitle)b2make.msgs.textTitle = 'Texto';
		if(!b2make.msgs.imagemTitle)b2make.msgs.imagemTitle = 'Biblioteca de Imagens';
		if(!b2make.msgs.facebookTitle)b2make.msgs.facebookTitle = 'Facebook Like';
		if(!b2make.msgs.twitterTitle)b2make.msgs.twitterTitle = 'Twitter';
		if(!b2make.msgs.soundcloudTitle)b2make.msgs.soundcloudTitle = 'SoundCloud';
		if(!b2make.msgs.iframeTitle)b2make.msgs.iframeTitle = 'Iframe';
		if(!b2make.msgs.iframeTextAdd)b2make.msgs.iframeTextAdd = 'Insira o valor aqui!';
		if(!b2make.msgs.slideshowTitle)b2make.msgs.slideshowTitle = 'Slide Show';
		if(!b2make.msgs.albumfotosTitle)b2make.msgs.albumfotosTitle = '&Aacute;lbum de Foto';
		if(!b2make.msgs.albummusicasTitle)b2make.msgs.albummusicasTitle = '&Aacute;lbum de M&uacute;sica';
		if(!b2make.msgs.redessociaisTitle)b2make.msgs.redessociaisTitle = 'Selo Social';
		if(!b2make.msgs.instagramTitle)b2make.msgs.instagramTitle = 'Instagram';
		if(!b2make.msgs.addthisTitle)b2make.msgs.addthisTitle = 'Compartilhar';
		if(!b2make.msgs.youtubeTitle)b2make.msgs.youtubeTitle = 'Youtube';
		if(!b2make.msgs.downloadButtonTitle)b2make.msgs.downloadButtonTitle = 'Download';
		if(!b2make.msgs.downloadTitle)b2make.msgs.downloadTitle = 'Download';
		if(!b2make.msgs.servicesTitle)b2make.msgs.servicesTitle = 'E-Servi&ccedil;os';
		if(!b2make.msgs.areaTitle)b2make.msgs.areaTitle = '&Aacute;rea do Menu de Navega&ccedil;&atilde;o';
		if(!b2make.msgs.menuBolinhaAreaTitle)b2make.msgs.menuBolinhaAreaTitle = 'Indicador Lateral &Agrave;reas';
		if(!b2make.msgs.menuBolinhaLayoutTitle)b2make.msgs.menuBolinhaLayoutTitle = 'Indicador Lateral Layout';
		if(!b2make.msgs.importarPaginaB2makeTitle)b2make.msgs.importarPaginaB2makeTitle = 'Importar P&aacute;gina B2make';
		if(!b2make.msgs.downloadLayoutTitle)b2make.msgs.downloadLayoutTitle = 'Arquivos';
		if(!b2make.msgs.conteinerBannerEditarTitle)b2make.msgs.conteinerBannerEditarTitle = 'Full Banners';
		if(!b2make.msgs.conteinerGlobalTitle)b2make.msgs.conteinerGlobalTitle = '&Aacute;rea Global';
		if(!b2make.msgs.conteinerBannerConfigTitle)b2make.msgs.conteinerBannerConfigTitle = 'Full Banners Configura&ccedil;&atilde;o';
		if(!b2make.msgs.servicesLayoutTitle)b2make.msgs.servicesLayoutTitle = 'Servi&ccedil;os';
		if(!b2make.msgs.redessociaisimgTitle)b2make.msgs.redessociaisimgTitle = 'Selo Social Imagem';
		if(!b2make.msgs.galeriaTitle)b2make.msgs.galeriaTitle = 'Galeria';
		if(!b2make.msgs.agendaTitle)b2make.msgs.agendaTitle = 'Agenda';
		if(!b2make.msgs.formContatoTitle)b2make.msgs.formContatoTitle = 'Form Contato';
		if(!b2make.msgs.menuTitle)b2make.msgs.menuTitle = 'Menu Navega&ccedil;&atilde;o';
		if(!b2make.msgs.playerTitle)b2make.msgs.playerTitle = 'Player List';
		if(!b2make.msgs.galeriaTextAdd)b2make.msgs.galeriaTextAdd = 'Selecione a galeria acima!';
		if(!b2make.msgs.albumfotosTextAdd)b2make.msgs.albumfotosTextAdd = 'Selecione uma galeria acima!';
		if(!b2make.msgs.playerTextAdd)b2make.msgs.playerTextAdd = 'Selecione a player list acima!';
		if(!b2make.msgs.agendaTextAdd)b2make.msgs.agendaTextAdd = 'Selecione a agenda acima!';
		if(!b2make.msgs.menuNavegacaoTitle)b2make.msgs.menuNavegacaoTitle = 'Modifique abaixo as &aacute;reas do Menu de Navega&ccedil;&atilde;o. Se quiser que uma &aacute;rea fa&ccedil;a parte deste menu favor deixar selecionado, sen&atilde;o desmarque as &aacute;reas desejadas.';
		if(!b2make.msgs.galeriaTextImagesEmpty)b2make.msgs.galeriaTextImagesEmpty = 'N&atilde;o h&aacute; imagens nessa galeria, adicione algumas acima!';
		if(!b2make.msgs.albumfotosTextImagesEmpty)b2make.msgs.albumfotosTextImagesEmpty = 'N&atilde;o h&aacute; imagens nessa galeria, adicione algumas acima!';
		if(!b2make.msgs.playerTextImagesEmpty)b2make.msgs.playerTextImagesEmpty = 'N&atilde;o h&aacute; m&uacute;sicas nesse player list, clique em UPLOAD para enviar algumas!';
		if(!b2make.msgs.agendaTextImagesEmpty)b2make.msgs.agendaTextImagesEmpty = 'N&atilde;o h&aacute; eventos nessa agenda, clique em ADICIONAR para adicionar alguns!';
		if(!b2make.msgs.conteinerDelete)b2make.msgs.conteinerDelete = 'Tem certeza que deseja realmente excluir <b>#name#</b>?';
		if(!b2make.msgs.conteinerNotSelected)b2make.msgs.conteinerNotSelected = '<p>Para poder adicionar <strong>#widget#</strong> &eacute; necess&aacute;rio selecionar uma <b>&aacute;rea</b>. </p><p><strong>Dica:</strong> Crie uma nova &aacute;rea clicando no menu a esquerda.</p>';
		if(!b2make.msgs.albumMusicasSemMp3)b2make.msgs.albumMusicasSemMp3 = 'Sem M&uacute;sicas definidas neste &Aacute;lbum';
		if(!b2make.msgs.paginaMenuBolinhasTitle) b2make.msgs.paginaMenuBolinhasTitle = 'Modifique abaixo as &aacute;reas do Indicador Lateral. Se quiser que uma &aacute;rea fa&ccedil;a parte deste menu favor deixar selecionado, sen&atilde;o desmarque as &aacute;reas desejadas.';
		if(!b2make.msgs.instagramOutraContaNao) b2make.msgs.instagramOutraContaNao = '<p>Voc&ecirc; j&aacute; trocou a sua conta atual do Instagram no nosso sistema. Portanto, para trocar a sua conta do Instagram por uma outra, &eacute; necess&aacute;rio voc&ecirc; acessar o Instagram, sair da sua conta atual, entrar com a conta deseja e por fim retornar aqui e tentar novamente a op&ccedil;&atilde;o <strong>Trocar Conta Instagram</strong>. D&uacute;vidas de como proceder isso no Instagram acesse clicando <a href="https://www.facebook.com/help/instagram/623835647655355" target="_blank">aqui</a>.</p>';
		if(!b2make.texto)b2make.texto = {};
		if(!b2make.texto.color)b2make.texto.color = '#000';
		if(!b2make.texto.backgroundColor)b2make.texto.backgroundColor = '#FFF';
		if(!b2make.texto.fontSize)b2make.texto.fontSize = '20px';
		if(!b2make.texto.top)b2make.texto.top = '30px';
		if(!b2make.texto.left)b2make.texto.left = '30px';
		if(!b2make.texto.width)b2make.texto.width = '300px';
		if(!b2make.texto.height)b2make.texto.height = '150px';
		if(!b2make.texto.value)b2make.texto.value = 'Digite o texto aqui!';
		if(!b2make.iframe)b2make.iframe = {};
		if(!b2make.iframe.color)b2make.iframe.color = '#000';
		if(!b2make.iframe.backgroundColor)b2make.iframe.backgroundColor = '#FFF';
		if(!b2make.iframe.fontSize)b2make.iframe.fontSize = '10px';
		if(!b2make.iframe.top)b2make.iframe.top = '30px';
		if(!b2make.iframe.left)b2make.iframe.left = '30px';
		if(!b2make.iframe.width)b2make.iframe.width = '300px';
		if(!b2make.iframe.height)b2make.iframe.height = '150px';
		if(!b2make.imagem)b2make.imagem = {};
		if(!b2make.imagem.color)b2make.imagem.color = '#000';
		if(!b2make.imagem.backgroundColor)b2make.imagem.backgroundColor = '#FFF';
		if(!b2make.imagem.fontSize)b2make.imagem.fontSize = '16px';
		if(!b2make.imagem.top)b2make.imagem.top = '30px';
		if(!b2make.imagem.left)b2make.imagem.left = '30px';
		if(!b2make.imagem.width)b2make.imagem.width = '200px';
		if(!b2make.imagem.height)b2make.imagem.height = '200px';
		if(!b2make.imagem.value)b2make.imagem.value = '<img class="b2make-biblioteca-imagens-default" src="//platform.b2make.com/design/images/b2make-album-sem-imagem.png" style="width:100%">';
		if(!b2make.facebook)b2make.facebook = {};
		if(!b2make.facebook.top)b2make.facebook.top = '30px';
		if(!b2make.facebook.left)b2make.facebook.left = '30px';
		if(!b2make.facebook.href)b2make.facebook.href = 'https%3A%2F%2Fwww.facebook.com%2Fb2make';
		if(!b2make.facebook.width)b2make.facebook.width = 245;
		if(!b2make.facebook.height)b2make.facebook.height = 240;
		if(!b2make.facebook.delay_timeout)b2make.facebook.delay_timeout = 400;
		if(!b2make.facebook.backgroundColor)b2make.facebook.backgroundColor = '#FFF';
		//if(!b2make.facebook.appId)b2make.facebook.appId = '868155289871842';
		if(!b2make.facebook.appId)b2make.facebook.appId = '358146730957925';
		if(!b2make.twitter)b2make.twitter = {};
		if(!b2make.twitter.top)b2make.twitter.top = '30px';
		if(!b2make.twitter.left)b2make.twitter.left = '30px';
		if(!b2make.twitter.user)b2make.twitter.user = 'b2_make';
		if(!b2make.twitter.width)b2make.twitter.width = 300;
		if(!b2make.twitter.height)b2make.twitter.height = 20;
		if(!b2make.twitter.delay_timeout)b2make.twitter.delay_timeout = 400;
		if(!b2make.soundcloud)b2make.soundcloud = {};
		if(!b2make.soundcloud.top)b2make.soundcloud.top = '30px';
		if(!b2make.soundcloud.left)b2make.soundcloud.left = '30px';
		if(!b2make.soundcloud.user)b2make.soundcloud.user = '62667683';
		if(!b2make.soundcloud.width)b2make.soundcloud.width = 300;
		if(!b2make.soundcloud.height)b2make.soundcloud.height = 250;
		if(!b2make.soundcloud.delay_timeout)b2make.soundcloud.delay_timeout = 400;
		if(!b2make.galeria_widget)b2make.galeria_widget = {};
		if(!b2make.galeria_widget.top)b2make.galeria_widget.top = '30px';
		if(!b2make.galeria_widget.left)b2make.galeria_widget.left = '30px';
		if(!b2make.galeria_widget.width)b2make.galeria_widget.width = 600;
		if(!b2make.galeria_widget.height)b2make.galeria_widget.height = 250;
		if(!b2make.galeria_widget.backgroundColor)b2make.galeria_widget.backgroundColor = '#FFF';
		if(!b2make.galeria_widget.delay_timeout)b2make.galeria_widget.delay_timeout = 400;
		if(!b2make.galeria_widget.fontSize)b2make.galeria_widget.fontSize = '20px';
		if(!b2make.albumfotos_widget)b2make.albumfotos_widget = {};
		if(!b2make.albumfotos_widget.top)b2make.albumfotos_widget.top = '30px';
		if(!b2make.albumfotos_widget.left)b2make.albumfotos_widget.left = '30px';
		if(!b2make.albumfotos_widget.width)b2make.albumfotos_widget.width = 930;
		if(!b2make.albumfotos_widget.height)b2make.albumfotos_widget.height = 250;
		if(!b2make.albumfotos_widget.backgroundColor)b2make.albumfotos_widget.backgroundColor = '#FFF';
		if(!b2make.player_widget)b2make.player_widget = {};
		if(!b2make.player_widget.top)b2make.player_widget.top = '30px';
		if(!b2make.player_widget.left)b2make.player_widget.left = '30px';
		if(!b2make.player_widget.width)b2make.player_widget.width = 170;
		if(!b2make.player_widget.height)b2make.player_widget.height = 30;
		if(!b2make.player_widget.backgroundColor)b2make.player_widget.backgroundColor = '#FFF';
		if(!b2make.player_widget.delay_timeout)b2make.player_widget.delay_timeout = 400;
		if(!b2make.player_widget.fontSize)b2make.player_widget.fontSize = '20px';
		if(!b2make.albummusicas_widget)b2make.albummusicas_widget = {};
		if(!b2make.albummusicas_widget.top)b2make.albummusicas_widget.top = '30px';
		if(!b2make.albummusicas_widget.left)b2make.albummusicas_widget.left = '30px';
		if(!b2make.albummusicas_widget.width)b2make.albummusicas_widget.width = 670;
		if(!b2make.albummusicas_widget.height)b2make.albummusicas_widget.height = 190;
		if(!b2make.albummusicas_widget.backgroundColor)b2make.albummusicas_widget.backgroundColor = '#FFF';
		if(!b2make.albummusicas_widget.delay_timeout)b2make.albummusicas_widget.delay_timeout = 400;
		if(!b2make.albummusicas_widget.fontSize)b2make.albummusicas_widget.fontSize = '20px';
		if(!b2make.redessociais_widget)b2make.redessociais_widget = {};
		if(!b2make.redessociais_widget.top)b2make.redessociais_widget.top = '30px';
		if(!b2make.redessociais_widget.left)b2make.redessociais_widget.left = '30px';
		if(!b2make.redessociais_widget.width)b2make.redessociais_widget.width = 250;
		if(!b2make.redessociais_widget.height)b2make.redessociais_widget.height = 70;
		if(!b2make.agenda)b2make.agenda = {};
		if(!b2make.agenda.top)b2make.agenda.top = '30px';
		if(!b2make.agenda.left)b2make.agenda.left = '30px';
		if(!b2make.agenda.width)b2make.agenda.width = 400;
		if(!b2make.agenda.height)b2make.agenda.height = 220;
		if(!b2make.agenda.backgroundColor)b2make.agenda.backgroundColor = 'transparent';
		if(!b2make.agenda.delay_timeout)b2make.agenda.delay_timeout = 400;
		if(!b2make.agenda.fontSize)b2make.agenda.fontSize = '20px';
		if(!b2make.widget_menu)b2make.widget_menu = {};
		if(!b2make.widget_menu.top)b2make.widget_menu.top = '30px';
		if(!b2make.widget_menu.left)b2make.widget_menu.left = '30px';
		if(!b2make.widget_menu.width)b2make.widget_menu.width = 50;
		if(!b2make.widget_menu.height)b2make.widget_menu.height = 47;
		if(!b2make.conteiner_area_width)b2make.conteiner_area_width = 1200;
		if(!b2make.msgs['pagina-mestreTitle'])b2make.msgs['pagina-mestreTitle'] = 'P&aacute;gina Mestre';
		
		$('<div id="b2make-shadow"></div>').appendTo('#b2make-site');
		$(b2make.shadow).hide();
		b2make.menu_height = $(b2make.menu).outerHeight(true);
		b2make.lightbox_width = parseInt($('#b2make-lightbox').css('width'));
		b2make.widget_sub_options_width = parseInt($(b2make.widget_sub_options).css('width')) + 60;
		b2make.widget_sub_options_height = parseInt($(b2make.widget_sub_options).css('height'));
		b2make.conteiner_total = 0;
		
		$(b2make.widget_sub_options_holder).appendTo('#b2make-lightbox-msg');
		
		var addthis_start;
		
		$(b2make.widget).each(function(){
			if($(this).myAttr('data-type') != 'conteiner-area'){
				b2make.widgets.push({
					id : $(this).myAttr('id'),
					type : $(this).myAttr('data-type'),
					id_pai : ($(this).myAttr('data-type') != 'conteiner' ? ($(this).parent().myAttr('data-type') == 'conteiner' ? $(this).parent().myAttr('id') : $(this).parent().parent().myAttr('id')) : false)
				});
				
				var id = parseInt($(this).myAttr('id').replace(($(this).myAttr('data-type') != 'conteiner' ? $(this).myAttr('data-type') : 'area'),''));

				if(id >= b2make.widgets_count) b2make.widgets_count = id + 1;
				
				if(!$(this).myAttr('data-zindex')){
					$(this).myAttr('data-zindex','1');
				}
				
				switch($(this).myAttr('data-type')){
					case 'conteiner':
						b2make.conteiner_total++;
						
						if($(this).myAttr('data-area-global') == 's'){
							conteiner_areas_globais_load({
								obj:this
							});
						}
					break;
					case 'slideshow':
						var obj = $(this).get(0);
						
						if($(obj).myAttr('data-animation')){
							if(!b2make.slideshow_start) b2make.slideshow_start = new Array();
							
							b2make.slideshow_start[$(obj).myAttr('id')] = true;
							slideshow_animation_start(obj);
						}
					break;
					case 'albummusicas':
						$(this).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').each(function(){
							player_widget_controls($(this),'albummusicas');
						});
						
						$.widgets_read_google_font({
							tipo : 2,
							types : new Array('titulo','player','lista'),
							obj : $(this)
						});
					break;
					case 'iframe':
						var obj = $(this).get(0);
						var widget;
						var id;
						
						if(!b2make.iframes) b2make.iframes = 0;
						b2make.iframes++;
						
						id = b2make.iframes;
						
						$(obj).myAttr('data-iframe-id',id);
						
						widget = decodeURIComponent($(obj).myAttr('data-iframe-code'));
						
						$.ajax({
							type: 'POST',
							url: '.',
							data: { 
								ajax : 'sim',
								opcao : 'widget-iframe-add',
								widget:widget,
								id:id
							},
							beforeSend: function(){
							},
							success: function(txt){
								$(obj).find('div.b2make-widget-out').find('iframe').html('');
								$(obj).find('div.b2make-widget-out').find('iframe').myAttr('src','.?ajax=sim&opcao=widget-iframe&id='+id);
								$(obj).find('div.b2make-widget-out').find('iframe').show();
							},
							error: function(txt){
								//
							}
						});
					break;
					case 'instagram':
						if(b2make.instagram_token){
							var obj = $(this).get(0);
							
							instagram_verificar_recentes({
								obj : obj
							});
						}
					break;
					case 'addthis':
						$(this).find('.b2make-widget-out').html(addthis_html($(this)));
						addthis_start = true;
					break;
					case 'texto':
						if($(this).myAttr('data-google-font') == 'sim'){
							$.google_fonts_wot_load({
								family : $(this).myAttr('data-font-family'),
								nao_carregamento : true
							});
						}
					break;
					case 'player':
						player_widget_controls($(this),'player');
						
						$.widgets_read_google_font({
							tipo : 1,
							obj : $(this)
						});
					break;
					case 'agenda':
						$.widgets_read_google_font({
							tipo : 2,
							types : new Array('dia','mes','titulo','cidade'),
							obj : $(this)
						});
					break;
					case 'menu':
						$.widgets_read_google_font({
							tipo : 1,
							obj : $(this).find('.b2make-widget-out').find('.b2make-widget-menu')
						});
					break;
					case 'form_contato':
						$(this).myAttr('data-type','formularios');
					break;
					case 'albumfotos':
						$.widgets_read_google_font({
							tipo : 1,
							obj : $(this)
						});
					break;
					case 'download':
						$.widgets_read_google_font({
							tipo : 1,
							obj : $(this)
						});
					break;
				}
			}
		});
		
		if(addthis_start){
			addthis_exec();
		}
		
		menu_widget_areas_update();
		holder_widget_start();
		conteiners_update();
		
		$(b2make.wom_name).on('blur',function(e){
			if(this.value.length == 0){
				this.value = b2make.conteiner_show;
			} else if(this.value.length > b2make.wom_name_max_lenght){
				this.value = this.value.substr(0, b2make.wom_name_max_lenght) + "...";
			}
			menu_widget_areas_update();
		});
		
		$(b2make.wom_name).keyup(function (e) {
			if(this.value.length > b2make.wom_name_max_lenght){
				this.value = this.value.substr(0, b2make.wom_name_max_lenght) + "...";
			}
			
			$("#"+b2make.conteiner_show).myAttr('data-name',this.value);
			
			var div_aux;
			if($("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"] ul').length > 0){
				div_aux = $('<div></div>');
				$("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"] ul').appendTo(div_aux);
			}
			
			$("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"]').html(this.value);
			if(div_aux) div_aux.find('ul').appendTo($("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"]'));
		});
		
		$(b2make.woc_name).on('blur',function(e){
			if(this.value.length == 0){
				this.value = b2make.conteiner_child_show;
			} else if(this.value.length > b2make.woc_name_max_lenght){
				this.value = this.value.substr(0, b2make.woc_name_max_lenght) + "...";
			}
		});
		
		$(b2make.woc_name).keyup(function (e) {
			if(this.value.length > b2make.woc_name_max_lenght){
				this.value = this.value.substr(0, b2make.woc_name_max_lenght) + "...";
			}
			
			$("#"+b2make.conteiner_child_show).myAttr('data-name',this.value);
			$("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_child_show+'"]').html(this.value);
		});
		
		$('#b2make-woc-marcador').on('blur',function(e){
			if(this.value.length == 0){
				this.value = '';
			} else if(this.value.length > b2make.woc_name_max_lenght){
				this.value = this.value.substr(0, b2make.woc_name_max_lenght) + "...";
			}
		});
		
		$('#b2make-woc-marcador').keyup(function (e) {
			if(this.value.length > b2make.woc_name_max_lenght){
				this.value = this.value.substr(0, b2make.woc_name_max_lenght) + "...";
			}
			
			$("#"+b2make.conteiner_child_show).myAttr('data-marcador',this.value);
		});
		
		$(document.body).on('keyup','#b2make-woc-class',function(e){
			var value = $(this).val();
			var id = $(this).myAttr('id');
			
			$.input_delay_to_change({
				trigger_selector:'#b2make-listener',
				trigger_event:'b2make-woc-class-change',
				value:value
			});
		});
		
		$(document.body).on('b2make-woc-class-change','#b2make-listener',function(e,value,p){
			if(!p) p = {};
			
			var obj = b2make.conteiner_child_obj;
			var classes = $(obj).myAttr('class');
			var data_classes = ($(obj).myAttr('data-classes') ? $(obj).myAttr('data-classes') : '');
			
			value = value.replace(/[^a-z0-9_-\s]/gi,'');
			value = value.trim();
			
			$('#b2make-woc-class').val(value);
			
			$(obj).myAttr('data-classes',value);
			
			if(data_classes.length > 0){
				classes = classes.replace(data_classes, value);
			} else {
				classes = classes + ' ' + value;
			}
			
			$(obj).myAttr('class',classes.trim());
		});
		
		$(b2make.won_close).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			conteiner_close_all();
		});
		
		$(b2make.won_delete).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var msg = b2make.msgs.conteinerDelete;
			msg = msg.replace(/#name#/gi,($("#"+b2make.conteiner_show).myAttr('data-name') ? $("#"+b2make.conteiner_show).myAttr('data-name') : $("#"+b2make.conteiner_show).myAttr('id')));
			
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-wom-delete-yes',
				msg: msg
			});
		});
		
		$(document.body).on('mouseup tap',b2make.won_delete_yes,function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			history_add({local:'conteiner_del',vars:{conteiner_total:b2make.conteiner_total}});
			
			var widget_show = b2make.conteiner_show;
			var widgets = b2make.widgets;
			var widgets_novo = new Array();
			var id = widget_show.replace($("#"+widget_show).myAttr('data-type'),'');
			
			conteiner_close_all();
			
			for(var i=0;i<widgets.length;i++){
				if(id != widgets[i].id){
					widgets_novo.push(widgets[i]);
				}
			}
			
			b2make.widgets = widgets_novo;
			
			$("#"+widget_show).remove();
			b2make.conteiner_total--;
			holder_widget_update(widget_show);
			menu_widget_areas_update();
		});
		
		$(b2make.woc_close).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$.conteiner_child_close();
		});
		
		$(b2make.woc_delete).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var msg = b2make.msgs.conteinerDelete;
			msg = msg.replace(/#name#/gi,($("#"+b2make.conteiner_child_show).myAttr('data-name') ? $("#"+b2make.conteiner_child_show).myAttr('data-name') : $("#"+b2make.conteiner_child_show).myAttr('id')));
			
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-woc-delete-yes',
				msg: msg
			});
		});
		
		$(document.body).on('mouseup tap',b2make.woc_delete_yes,function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var widget_show = b2make.conteiner_child_show;
			var widgets = b2make.widgets;
			var widgets_novo = new Array();
			var id = widget_show.replace($("#"+widget_show).myAttr('data-type'),'');
			
			history_add({local:'conteiner_child_del'});
			
			$.conteiner_child_close();
			
			for(var i=0;i<widgets.length;i++){
				if(id != widgets[i].id){
					widgets_novo.push(widgets[i]);
				}
			}
			
			b2make.widgets = widgets_novo;
			
			$("#"+widget_show).remove();
			
			holder_widget_update(widget_show);
		});
		
		$(b2make.woc_zindex_up).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj_area = b2make.conteiner_obj;
			var zIndexDemais = 1;
			
			$(obj_area).find('.b2make-widget').each(function(){
				if($(this).myAttr('data-zindex')){
					var zi = parseInt($(this).myAttr('data-zindex'));
				
					if(zi > zIndexDemais){
						zIndexDemais = zi;
					}
				}
			});
			
			var widget_show = b2make.conteiner_child_show;
			var zIndex = parseInt($("#"+widget_show).myAttr('data-zindex'));
			
			if(zIndex && zIndex > 1){
				if(zIndex < zIndexDemais){
					zIndexDemais++;
					$("#"+widget_show).myAttr('data-zindex',zIndexDemais);
					$("#"+widget_show).css('zIndex',zIndexDemais);
				}
			} else {
				zIndexDemais++;
				$("#"+widget_show).myAttr('data-zindex',zIndexDemais);
				$("#"+widget_show).css('zIndex',zIndexDemais);
			}
		});
		
		$(document.body).on('mouseup tap',b2make.shadow,function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var upload_clicked = false;
			if(b2make.upload_clicked){
				for(var i=0;i<b2make.upload_clicked.length;i++){
					if(b2make.upload_clicked[i]){
						upload_clicked = true;
					}
				}
			}
			
			if(
				!upload_clicked
			){
				if(b2make.conteiner_show){
					
					e.stopPropagation();
					if(!b2make.widget_move){
						conteiner_close_all();
					}
				} else if(b2make.holder_template_open){
					holder_template_close();
				}
			}
			
			multi_select_area_close();
		});
		
		$(b2make.won_position_up).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			conteiner_before_after();
			
			var obj = b2make.conteiner_obj;
			
			$('#'+b2make.widget_before).before($(obj));
			$('#'+b2make.widget_before).before(b2make.widget_conteiner_mask);
			$("#"+b2make.menu_widgets+' li[data-id="'+b2make.widget_before+'"]').before($("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"]'));
			
			conteiner_before_after();
			conteiner_position();
		});
		
		$(b2make.won_position_down).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			conteiner_before_after();
			
			var obj = b2make.conteiner_obj;
			
			$('#'+b2make.widget_after).after($(obj));
			$('#'+b2make.widget_after).after(b2make.widget_conteiner_mask);
			$("#"+b2make.menu_widgets+' li[data-id="'+b2make.widget_after+'"]').after($("#"+b2make.menu_widgets+' li[data-id="'+b2make.conteiner_show+'"]'));

			conteiner_before_after();
			conteiner_position();
		});
		
		$(b2make.widget_sub_options_up).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			b2make.conteiner_show_after = b2make.conteiner_show;
			b2make.widget_sub_options_up_clicked = true;
			widget_sub_options_close();
		});
		
		$(b2make.widget_sub_options_down).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			if(b2make.widget_sub_options_up_clicked){
				b2make.widget_sub_options_up_clicked = false;
			}
			
			$.widget_sub_options_open();
		});
		
		$(b2make.won_height).keyup(function (e) {
			var value = parseInt(this.value);
			
			if(value > b2make.won_height_max_value){
				this.value = b2make.won_height_max_value;
				value = b2make.won_height_max_value;
			}
			
			if(value < b2make.won_height_min_value){
				value = b2make.won_height_min_value;
			}
			
			if(!value){
				value = b2make.won_height_min_value;
			}
			
			$(b2make.conteiner_obj).height(value);
			conteiner_principal_site_update();
		});
		
		$(b2make.woc_height).keyup(function (e) {
			var conteiner = b2make.selecionador_objetos.conteiner;
			var value = parseInt(this.value);
			
			if(value > b2make.woc_height_max_value){
				this.value = b2make.woc_height_max_value;
				value = b2make.woc_height_max_value;
			}
			
			if(value < b2make.woc_height_min_value){
				value = b2make.woc_height_min_value;
			}
			
			if(!value){
				value = b2make.woc_height_min_value;
			}
			
			$(conteiner).height(value);
			textarea_resize($(b2make.conteiner_child_obj).outerWidth(),$(b2make.conteiner_child_obj).outerHeight());
			
			var type = $(b2make.conteiner_child_obj).myAttr('data-type');
			
			switch(type){
				case 'facebook':
					facebook_resize();
				break;
				case 'soundcloud':
					sound_cloud_resize();
				break;
				
			}
			
			$('#b2make-listener').trigger('widgets-change-height');
		});
		
		$(b2make.woc_width).keyup(function (e) {
			var conteiner = b2make.selecionador_objetos.conteiner;
			var value = parseInt(this.value);
			
			if(value > b2make.woc_width_max_value){
				this.value = b2make.woc_width_max_value;
				value = b2make.woc_width_max_value;
			}
			
			if(value < b2make.woc_width_min_value){
				value = b2make.woc_width_min_value;
			}
			
			if(!value){
				value = b2make.woc_width_min_value;
			}
			
			$(conteiner).width(value);
			textarea_resize($(b2make.conteiner_child_obj).outerWidth(),$(b2make.conteiner_child_obj).outerHeight());
			
			var type = $(b2make.conteiner_child_obj).myAttr('data-type');
			
			switch(type){
				case 'facebook':
					facebook_resize();
				break;
				case 'soundcloud':
					sound_cloud_resize();
				break;
				
			}
			
			$('#b2make-listener').trigger('widgets-change-width');
		});
		
		$(document.body).on('mouseup tap','.b2make-suboptions-editar,.b2make-suboptions-editar-sem-styles',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			b2make.widget_sub_options_obj = this;
			
			if($(this).myAttr('data-type')){
				b2make.widget_sub_options_type = $(this).myAttr('data-type');
			} else {
				b2make.widget_sub_options_type = (b2make.conteiner_child_obj ? $(b2make.conteiner_child_obj).myAttr('data-type') : 'conteiner');
			}
			
			if($(this).myAttr('data-back-btn')){
				b2make.widget_sub_options_back_button = $(this).myAttr('data-back-btn');
			}
			
			if($(this).myAttr('data-title')){
				b2make.widget_sub_options_title_user = $(this).myAttr('data-title');
			}
			
			if($(this).myAttr('data-width')) b2make.widget_sub_options_holder_width_user = $(this).myAttr('data-width');
			
			b2make.widget_edit_sub_options_open = true;
			
			switch(b2make.widget_sub_options_type){
				case 'menu-bolinha-layout':
				case 'menu-bolinha-areas':
				case 'importar-codigo-html':
					$.widget_sub_options_open();
					return false;
				break;
			}
			
			if($(this).myAttr('data-edit-sub-options')){
				$.widget_sub_options_open();
				return false;
			}
			
			if(!b2make.widget_sub_options_button_open){
				b2make.widget_sub_options_button_open = true;
				$.widget_sub_options_open();
			} else {
				b2make.widget_sub_options_button_open = false;
				widget_sub_options_close();
			}
		});
		
		$(b2make.woc_position_top).keyup(function (e) {
			var conteiner = b2make.selecionador_objetos.conteiner;
			var value = parseInt(this.value);
			
			if(!value){
				value = '0';
			}
			
			$(conteiner).css('top',value);
		});
		
		$(b2make.woc_position_left).keyup(function (e) {
			var conteiner = b2make.selecionador_objetos.conteiner;
			var value = parseInt(this.value);
			
			if(!value){
				value = '0';
			}
			
			$(conteiner).css('left',value);
		});
		
		$(document.body).on('dblclick','#b2make-selecionador-objetos-mask',function(e){
			var obj = b2make.conteiner_child_obj;
			var type = $(obj).myAttr('data-type');
			var mask = $(obj).find('.b2make-widget-mask').length;
			
			switch(type){
				case 'imagem':
					b2make.widget_edit_sub_options_open = true;
					$.widget_sub_options_open();
				break;
				case 'texto':
					if(!b2make.texto_for_textarea)texto_for_textarea();
				break;
				default:
					$('#b2make-listener').trigger('selecionador-objetos-dblclick');
			}
			
			
			if(mask > 0){
				b2make.widget_mask_hide = true;
				$(obj).find('.b2make-widget-mask').hide();
				$(this).hide();
			}
		});
		
		$('#b2make-wof-href').keyup(function (e) {
			var value = this.value;
			
			facebook_href_delay_to_change(value);
		});
		
		$('#b2make-wot-user').keyup(function (e) {
			var value = this.value;
			
			twitter_user_delay_to_change(value);
		});
		
		$('#b2make-wos-user').keyup(function (e) {
			var value = this.value;
			
			soundcloud_user_delay_to_change(value);
		});
		
		$(document.body).on('mouseup tap','.b2make-gwi-prev',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			var obj = b2make.conteiner_child_obj;
			var holder = $(obj).find('div.b2make-widget-out').find('div.b2make-galeria-widget-holder');
			var img = holder.find('div:last-child');
			
			img.prependTo(holder);
		});
		
		$(document.body).on('mouseup tap','.b2make-gwi-next',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			var obj = b2make.conteiner_child_obj;
			var holder = $(obj).find('div.b2make-widget-out').find('div.b2make-galeria-widget-holder');
			var img = holder.find('div:first-child');
			
			img.appendTo(holder);
		});
		
		$(document.body).on('mouseup tap','.b2make-widget-menu',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var this_obj = this;
			var menu_holder;
			var menu_holder_append = false;
			if($('#b2make-widget-menu-holder').length == 0){
				menu_holder_append = true;
				menu_holder = $('<div id="b2make-widget-menu-holder"></div>');
			} else {
				menu_holder = $('#b2make-widget-menu-holder');
				menu_holder.html('');
			}
			
			if(menu_holder.myAttr('data-open') == '1'){
				menu_holder.myAttr('data-open','0');
				menu_holder.hide();
				return;
			}
			
			var areas_ocultas = $(this).parent().parent().myAttr('data-areas');
			var areas_ocultas_arr = new Array();
			
			if(areas_ocultas)
				areas_ocultas_arr = areas_ocultas.split(',');
			
			if(menu_holder_append)menu_holder.appendTo('#b2make-site');
			
			$(b2make.widget).each(function(){
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
							caixa_color = $.jpicker_ahex_2_rgba($(this_obj).myAttr('data-caixa-color-ahex'));
						}
						if($(this_obj).myAttr('data-font-color-ahex')){
							font_color = $.jpicker_ahex_2_rgba($(this_obj).myAttr('data-font-color-ahex'));
						}
						if($(this_obj).myAttr('data-hover-color-ahex')){
							hover_color = $.jpicker_ahex_2_rgba($(this_obj).myAttr('data-hover-color-ahex'));
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
						
						var link = $('<a href="#'+$(this).myAttr('id')+'">'+($(this).myAttr('data-name')?$(this).myAttr('data-name'):$(this).myAttr('id'))+'</a>');
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
			
			menu_holder.myAttr('data-menu-atual',$(this).myAttr('id'));
			menu_holder.css('top',($(this).offset().top + $(this).outerHeight()) + 'px');
			menu_holder.css('left',$(this).offset().left + 'px');
			menu_holder.myAttr('data-open','1');
			menu_holder.show();
		});
		
		$('#b2make-woss-start-pause').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = b2make.conteiner_child_obj;
			if(b2make.slideshow_start[$(obj).myAttr('id')]){
				$(this).css('backgroundPosition','0px 0px');
				b2make.slideshow_start[$(obj).myAttr('id')] = false;
				slideshow_animation_stop(obj);
			} else {
				$(this).css('backgroundPosition','-20px 0px');
				b2make.slideshow_start[$(obj).myAttr('id')] = true;
				slideshow_animation_start(obj);
			}
		});
		
		$('#b2make-woss-tempo').keyup(function (e) {
			var value = parseInt(this.value);
			
			if(value > 99999) value = 99999;
			if(value < 300) value = 300;
			
			var obj = b2make.conteiner_child_obj;
			$(obj).myAttr('data-tempo',value);
			b2make.slideshow_start[$(obj).myAttr('id')] = true;
			slideshow_animation_start(obj);
		});
		
		$('#b2make-woss-direction').on('change',function(e){
			var obj = b2make.conteiner_child_obj;
			$(obj).myAttr('data-direction',$(this).val());
			b2make.slideshow_start[$(obj).myAttr('id')] = true;
			slideshow_animation_start(obj);
		});
		
		$(document.body).on('mouseup tap','.b2make-albumfotos-widget-image',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if($(this).myAttr('data-imagens-urls')){
				var imgs_arr = $(this).myAttr('data-imagens-urls').split(',');
				var imgs;
				
				if(imgs_arr.length > 0){
					imgs = new Array();
					for(var i=0;i<imgs_arr.length;i++){
						imgs.push(imgs_arr[i]);
					}
					if(!b2make.start_pretty_photo){
						$.fn.prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true});
						b2make.start_pretty_photo = true;
					}
					$.prettyPhoto.open(imgs);
				}
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-slideshow-widget-image',function(e){
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
							$.fn.prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true});
							b2make.start_pretty_photo = true;
						}
						$.prettyPhoto.open(imgs);
					}
				}
			}
		});
		
		$('#b2make-woaf-exibicao').on('change',function(e){
			var obj = b2make.conteiner_child_obj;
			
			if($(this).val() == 's'){
				$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').find('div.b2make-albumfotos-widget-image[data-galeria-id="'+b2make.galerias_atual+'"]').show();
			} else {
				$(obj).find('div.b2make-widget-out').find('div.b2make-albumfotos-widget-holder').find('div.b2make-albumfotos-widget-image[data-galeria-id="'+b2make.galerias_atual+'"]').hide();
			}
		});
		
		$('.b2make-widget-hiperlink').on('blur',function(e){
			var value = $(this).val();
			var obj = b2make.conteiner_child_obj;
			
			$(obj).myAttr('data-hiperlink',value);
		});
		
		$('.b2make-widget-hiperlink-target').on('change',function(e){
			var value = $(this).val();
			var obj = b2make.conteiner_child_obj;
			
			$(obj).myAttr('data-hiperlink-target',value);
		});
		
		$(document.body).on('mouseup tap','.b2make-wsom-lbl',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var input = $(this).parent().find('.b2make-wsom-input');
			
			input.prop("checked", !input.prop("checked"));
			
			menu_widget_areas_check(input);
		});
		
		$(document.body).on('change','.b2make-wsom-input',function(e){
			menu_widget_areas_check($(this));
		});
		
		$('#b2make-woc-rotate-value').keyup(function (e) {
			var conteiner = b2make.selecionador_objetos.conteiner;
			var angulo = parseInt(this.value);
			
			if(angulo > 360){
				this.value = 360;
				angulo = 360;
			}
			
			if(angulo < 0){
				this.value = '';
				angulo = '0';
			}
			
			if(!angulo){
				angulo = '0';
			}
			
			$(conteiner).myAttr('data-angulo',angulo);
			$(conteiner).css('-moz-transform','rotate('+angulo+'deg)');
			$(conteiner).css('-webkit-transform','rotate('+angulo+'deg)');
			$(conteiner).css('-o-transform','rotate('+angulo+'deg)');
			$(conteiner).css('-ms-transform','rotate('+angulo+'deg)');
			$(conteiner).css('transform','rotate('+angulo+'deg)');
		});
	
		$('#b2make-womn-esquema-cor-1-val,#b2make-womn-esquema-cor-2-val,#b2make-womn-esquema-cor-3-val,#b2make-womn-esquema-cor-4-val,#b2make-womn-esquema-cor-5-val').on('changeColor',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			var obj = b2make.conteiner_child_obj;
			
			switch(id){
				case 'b2make-womn-esquema-cor-1-val':
					$(obj).find('.b2make-widget-out').find('.b2make-widget-menu').css('background-color',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-widget-menu').myAttr('data-color-ahex',ahex);
				break;
				case 'b2make-womn-esquema-cor-2-val':
					$(obj).find('.b2make-widget-out').find('.b2make-widget-menu').find('.b2make-widget-menu-barra').css('background-color',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-widget-menu').find('.b2make-widget-menu-barra').myAttr('data-color-ahex',ahex);
				break;
				case 'b2make-womn-esquema-cor-3-val':
					$(obj).find('.b2make-widget-out').find('.b2make-widget-menu').myAttr('data-caixa-color-ahex',ahex);
				break;
				case 'b2make-womn-esquema-cor-4-val':
					$(obj).find('.b2make-widget-out').find('.b2make-widget-menu').myAttr('data-font-color-ahex',ahex);
				break;
				case 'b2make-womn-esquema-cor-5-val':
					$(obj).find('.b2make-widget-out').find('.b2make-widget-menu').myAttr('data-hover-color-ahex',ahex);
				break;
				
			}
		});
		
		$('#b2make-womn-fonts-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
			var obj = b2make.conteiner_child_obj;
			var menu = $(obj).find('.b2make-widget-out').find('.b2make-widget-menu');
			
			switch(e.type){
				case 'changeFontFamily': menu.myAttr('data-font-family',$(this).myAttr('data-font-family')); break;
				case 'changeFontSize': menu.myAttr('data-font-size',$(this).myAttr('data-font-size')); break;
				case 'changeFontAlign': menu.myAttr('data-font-align',$(this).myAttr('data-font-align')); break;
				case 'changeFontItalico': menu.myAttr('data-font-italico',$(this).myAttr('data-font-italico')); break;
				case 'changeFontNegrito': menu.myAttr('data-font-negrito',$(this).myAttr('data-font-negrito')); break;
			}
		});
		
		$('#b2make-womn-cantos-arredondados-val').on('change',function(e){
			var obj = b2make.conteiner_child_obj;
			var menu = $(obj).find('.b2make-widget-out').find('.b2make-widget-menu');
			var barras = menu.find('.b2make-widget-menu-barra');
			
			menu.myAttr('data-cantos-arredondados',$(this).val());
			
			if($(this).val() == 's'){
				barras.css('-webkit-border-radius','20px');
				barras.css('-moz-border-radius','20px');
				barras.css('border-radius','20px');
			} else {
				barras.css('-webkit-border-radius','0px');
				barras.css('-moz-border-radius','0px');
				barras.css('border-radius','0px');
			}
		});
		
		$('#b2make-womn-espacamento-val').keyup(function (e) {
			var obj = b2make.conteiner_child_obj;
			var menu = $(obj).find('.b2make-widget-out').find('.b2make-widget-menu');
			var espacamento = parseInt(this.value);
			
			if(espacamento > 100){
				this.value = 100;
				espacamento = 100;
			}
			
			if(espacamento < 0){
				this.value = '';
				espacamento = '0';
			}
			
			if(!espacamento){
				espacamento = '0';
			}
			
			menu.myAttr('data-espacamento',espacamento);
		});
		
		$('#b2make-womn-largura-val').keyup(function (e) {
			var obj = b2make.conteiner_child_obj;
			var menu = $(obj).find('.b2make-widget-out').find('.b2make-widget-menu');
			var largura = parseInt(this.value);
			
			if(largura > 1000){
				this.value = 1000;
				largura = 1000;
			}
			
			if(largura < 0){
				this.value = '';
				largura = '0';
			}
			
			if(!largura){
				largura = '0';
			}
			
			menu.myAttr('data-largura',largura);
		});
		
		$('#b2make-woa-excluir').on('change',function(e){
			var obj = b2make.conteiner_child_obj;
			var value = $(this).val();
			
			$(obj).myAttr('data-excluir-eventos',value);
			
			agenda_widget_create({agenda_id:$(obj).myAttr('data-agenda-id')});
		});
		
		$('#b2make-woa-ordem').on('change',function(e){
			var obj = b2make.conteiner_child_obj;
			var value = $(this).val();
			
			$(obj).myAttr('data-ordem-eventos',value);
			
			agenda_widget_create({agenda_id:$(obj).myAttr('data-agenda-id')});
		});
		
		$('#b2make-woa-caixa-cor-val,#b2make-woa-seta-cor-1-val,#b2make-woa-seta-cor-2-val,#b2make-woa-dia-cor,#b2make-woa-mes-cor,#b2make-woa-titulo-cor,#b2make-woa-cidade-cor').on('changeColor',function (e) {
			var obj = b2make.conteiner_child_obj;
			var id_campo = 2;
			var val = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			
			switch($(this).myAttr('id')){
				case 'b2make-woa-caixa-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-wsoae-prev').css('background-color',val);
					$(obj).find('.b2make-widget-out').find('.b2make-wsoae-next').css('background-color',val);
					$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').css('background-color',val);
					$(obj).myAttr('data-caixa-cor-ahex',ahex);
				break;
				case 'b2make-woa-seta-cor-1-val':
					$(obj).find('.b2make-widget-out').find('.b2make-wsoae-prev').find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',val);
					$(obj).find('.b2make-widget-out').find('.b2make-wsoae-next').find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',val);
					$(obj).myAttr('data-seta-cor-1-ahex',ahex);
				break;
				case 'b2make-woa-seta-cor-2-val':
					$(obj).myAttr('data-seta-cor-2-ahex',ahex);
				break;
				case 'b2make-woa-dia-cor':
					$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-data').css('color',val);
					$(obj).myAttr('data-dia-cor-ahex',ahex);
				break;
				case 'b2make-woa-mes-cor':
					$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-mes').css('color',val);
					$(obj).myAttr('data-mes-cor-ahex',ahex);
				break;
				case 'b2make-woa-titulo-cor':
					$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-titulo').css('color',val);
					$(obj).myAttr('data-titulo-cor-ahex',ahex);
				break;
				case 'b2make-woa-cidade-cor':
					$(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-descricao').css('color',val);
					$(obj).myAttr('data-cidade-cor-ahex',ahex);
				break;
				
			}
		});
		
		$('#b2make-woa-dia-fonts-cont,#b2make-woa-mes-fonts-cont,#b2make-woa-titulo-fonts-cont,#b2make-woa-cidade-fonts-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			var cssVar = '';
			var type = $(this).myAttr('id')
			
			type = type.replace(/b2make-woa-/gi,'');
			type = type.replace(/-fonts-cont/gi,'');
			
			switch($(this).myAttr('id')){
				case 'b2make-woa-dia-fonts-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-data'); break;
				case 'b2make-woa-mes-fonts-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-mes'); break;
				case 'b2make-woa-titulo-fonts-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-titulo'); break;
				case 'b2make-woa-cidade-fonts-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-eventos-widget-holder').find('.b2make-widget-eventos').find('.b2make-widget-eventos-descricao'); break;
			
			}
			
			switch(e.type){
				case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-'+type+'-font-family',$(this).myAttr('data-font-family')); break;
				case 'changeFontSize': cssVar = 'fontSize'; target.css(cssVar,$(this).myAttr('data-font-size')+'px'); target.css('line-height',$(this).myAttr('data-font-size')+'px'); $(obj).myAttr('data-'+type+'-font-size',$(this).myAttr('data-font-size')); break;
				case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).myAttr('data-font-align'));$(obj).myAttr('data-'+type+'-font-align',$(this).myAttr('data-font-align')); break;
				case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).myAttr('data-'+type+'-font-italico',$(this).myAttr('data-font-italico')); break;
				case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).myAttr('data-'+type+'-font-negrito',$(this).myAttr('data-font-negrito')); break;
			}
		});
		
		$('#b2make-wop-preenchimento-cor-val,#b2make-wop-fonte-cor,#b2make-wop-botoes-cor-1-val,#b2make-wop-botoes-cor-2-val').on('changeColor',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			var obj = b2make.conteiner_child_obj;
			
			switch(id){
				case 'b2make-wop-preenchimento-cor-val':
					$(obj).css('background-color',bg);
					$(obj).myAttr('data-color-ahex',ahex);
				break;
				case 'b2make-wop-fonte-cor':
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-tit').css('color',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-time').css('color',bg);
					$(obj).myAttr('data-text-color-ahex',ahex);
				break;
				case 'b2make-wop-botoes-cor-1-val':
					$(obj).myAttr('data-botoes-color-1-ahex',ahex);
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('polygon').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('rect').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('polygon').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('rect').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('polygon').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('rect').css('fill',bg);
				break;
				case 'b2make-wop-botoes-cor-2-val':
					$(obj).myAttr('data-botoes-color-2-ahex',ahex);
				break;
				
			}
		});
		
		$('#b2make-wop-fonte-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			var target2;
			var cssVar = '';
			
			target = $(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-tit');
			target2 = $(obj).find('.b2make-widget-out').find('.b2make-player-control').find('.b2make-player-controls').find('.b2make-player-time');
			
			switch(e.type){
				case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); target2.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-font-family',$(this).myAttr('data-font-family')); break;
			}
		});
		
		$('#b2make-woam-lista-cor-1,#b2make-woam-lista-cor-2,#b2make-woam-player-cor,#b2make-woam-titulo-cor,#b2make-woam-faixas-cor-val,#b2make-woam-area-cor-val,#b2make-woam-preenchimento-cor-val,#b2make-woam-fonte-cor,#b2make-woam-botoes-cor-1-val,#b2make-woam-botoes-cor-2-val').on('changeColor',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			var obj = b2make.conteiner_child_obj;
			
			switch(id){
				case 'b2make-woam-area-cor-val':
					$(obj).css('background-color',bg);
					$(obj).myAttr('data-color-ahex',ahex);
				break;
				case 'b2make-woam-preenchimento-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').css('background-color',bg);
					$(obj).myAttr('data-preenchimento-color-ahex',ahex);
				break;
				case 'b2make-woam-faixas-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').css('background-color',bg);
					$(obj).myAttr('data-faixas-color-ahex',ahex);
				break;
				case 'b2make-woam-titulo-cor':
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-titulo').css('color',bg);
					$(obj).myAttr('data-titulo-color-ahex',ahex);
				break;
				case 'b2make-woam-player-cor':
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-tit').css('color',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-time').css('color',bg);
					$(obj).myAttr('data-player-color-ahex',ahex);
				break;
				case 'b2make-woam-lista-cor-1':
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-playing').css('color',bg);
					$(obj).myAttr('data-lista-color-1-ahex',ahex);
				break;
				case 'b2make-woam-lista-cor-2':
					var color_playing = ($(obj).myAttr('data-lista-color-1-ahex') ? $.jpicker_ahex_2_rgba($(obj).myAttr('data-lista-color-1-ahex')) : '#A1BC31');
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-mp3').css('color',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-playing').css('color',color_playing);
					$(obj).myAttr('data-lista-color-2-ahex',ahex);
				break;
				case 'b2make-woam-botoes-cor-1-val':
					$(obj).myAttr('data-botoes-color-1-ahex',ahex);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('polygon').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-prev').find('svg').find('rect').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('path').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-play').find('svg').find('rect').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('polygon').css('fill',bg);
					$(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-next').find('svg').find('rect').css('fill',bg);
				break;
				case 'b2make-woam-botoes-cor-2-val':
					$(obj).myAttr('data-botoes-color-2-ahex',ahex);
				break;
				
			}
		});
		
		$('#b2make-woam-titulo-cont,#b2make-woam-player-cont,#b2make-woam-lista-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			var target2;
			var cssVar = '';
			var id = $(this).myAttr('id');
			var type = '';
			
			type = id.replace(/b2make-woam-/gi,'');
			type = type.replace(/-cont/gi,'');
			
			switch(id){
				case 'b2make-woam-titulo-cont':
					target = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-titulo');
				break;
				case 'b2make-woam-player-cont':
					target = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-tit');
					target2 = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-player-controls').find('.b2make-player-time');
				break;
				case 'b2make-woam-lista-cont':
					target = $(obj).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').find('.b2make-albummusicas-widget-mp3');
				break;
				
			}
			
			switch(e.type){
				case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); if(target2)target2.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-'+type+'-font-family',$(this).myAttr('data-font-family')); break;
			}
		});
		
		$('#b2make-woaf-preenchimento-cor-val,#b2make-woaf-text-cor-val,#b2make-woaf-legenda-cor-val').on('changeColor',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			var obj = b2make.conteiner_child_obj;
			
			switch(id){
				case 'b2make-woaf-preenchimento-cor-val':
					$(obj).css('background-color',bg);
					$(obj).myAttr('data-preenchimento-color-ahex',ahex);
				break;
				case 'b2make-woaf-text-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder').find('.b2make-albumfotos-widget-image').find('.b2make-albumfotos-widget-titulo').css('color',bg);
					$(obj).myAttr('data-text-color-ahex',ahex);
				break;
				case 'b2make-woaf-legenda-cor-val':
					$(obj).myAttr('data-legenda-color-ahex',ahex);
				break;
				
			}
		});
		
		$('#b2make-woaf-text-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			var pai;
			var target2 = false;
			var cssVar = '';
			var noSize = false;
			var type = $(this).myAttr('id')
			
			target = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder').find('.b2make-albumfotos-widget-image').find('.b2make-albumfotos-widget-titulo');
			pai = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder').find('.b2make-albumfotos-widget-image');
			
			switch(e.type){
				case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-font-family',$(this).myAttr('data-font-family')); break;
				case 'changeFontSize': cssVar = 'fontSize';  target.css(cssVar,$(this).myAttr('data-font-size')+'px'); target.css('line-height',$(this).myAttr('data-font-size')+'px'); $(obj).myAttr('data-font-size',$(this).myAttr('data-font-size')); 
					var size = parseInt($(this).myAttr('data-font-size'));
					var conteiner_width = parseInt($('#b2make-woaf-imagem-val').val());
					
					var fator_ajuste = b2make.albumfotos.fator_ajuste;
					var margin_ajuste = Math.floor(size * fator_ajuste);
					
					target.css('max-height',(2*size)+'px');
					target.css('line-height',size+'px');
					pai.css('margin','18px 18px '+(size-margin_ajuste)+'px 18px');
					pai.css('height',(2*size+b2make.albumfotos.margin_image+conteiner_width)+'px');
				break;
				case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).myAttr('data-font-align'));$(obj).myAttr('data-font-align',$(this).myAttr('data-font-align')); break;
				case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).myAttr('data-font-italico',$(this).myAttr('data-font-italico')); break;
				case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).myAttr('data-font-negrito',$(this).myAttr('data-font-negrito')); break;
			}
		});
		
		$('#b2make-woaf-imagem-val').keyup(function (e) {
			var obj = b2make.conteiner_child_obj;
			var image = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder').find('.b2make-albumfotos-widget-image');
			var tamanho = parseInt(this.value);
			
			if(tamanho > 500){
				this.value = 500;
				tamanho = 500;
			}
			
			if(tamanho < 0){
				this.value = '';
				tamanho = '0';
			}
			
			if(!tamanho){
				tamanho = '0';
			}
			
			$(obj).myAttr('data-tamanho-imagem',tamanho);
			image.css('width',tamanho+'px');
			
			if($(obj).myAttr('data-font-size')){
				var size = parseInt($(obj).myAttr('data-font-size'));
				image.css('height',(2*size+b2make.albumfotos.margin_image+tamanho)+'px');
			} else {
				image.css('height',tamanho+'px');
			}
			
			image.css('background-size','auto '+tamanho+'px');
			image.find('.b2make-albumfotos-widget-titulo').css('top',tamanho+'px');
			
			image.each(function(){
				var this_image = $(this);
				var target = this_image.find('.b2make-albumfotos-widget-titulo');
				var imagem_width = parseInt(this_image.myAttr('data-album-fotos-imagem-width'));
				var imagem_height = parseInt(this_image.myAttr('data-album-fotos-imagem-height'));
				tamanho = parseInt(tamanho);
				
				//var altura = Math.floor((tamanho * imagem_height) / imagem_width);
				var altura = tamanho;
				
				target.css('top',(b2make.albumfotos.margin_title+altura)+'px');
			});
		});
		
		$(document.body).on('change','#b2make-woaf-layout-tipo',function(){
			var obj = b2make.conteiner_child_obj;
			var value = $(this).val();
			
			$(obj).myAttr('data-layout-tipo',value);
			
			albumfotos_widget_album_update();
		});
		
		$('#b2make-listener').on('widgets-resize',function(){
			switch(b2make.conteiner_child_type){
				case 'albumfotos':
					albumfotos_widget_caixa_posicao_atualizar({});
				break;
			}
		});
		
		$('#b2make-wsoi-numero-posts-val').keyup(function (e) {
			var obj = b2make.conteiner_child_obj;
			var numero = parseInt(this.value);
			
			if(numero > 16){
				this.value = 16;
				numero = 16;
			}
			
			if(numero < 0){
				this.value = '';
				numero = '0';
			}
			
			if(!numero){
				numero = '0';
			}
			
			if(numero > 1){
				$('#b2make-wsoi-tamanho-imagens-lbl').show();
				$('#b2make-wsoi-tamanho-imagens-val').show();
			} else {
				$('#b2make-wsoi-tamanho-imagens-lbl').hide();
				$('#b2make-wsoi-tamanho-imagens-val').hide();
			}
			
			$(obj).myAttr('data-numero-posts',numero);
			instagram_delay_to_change(numero);
		});
		
		$('#b2make-wsoi-tamanho-imagens-val').keyup(function (e) {
			var obj = b2make.conteiner_child_obj;
			var numero = parseInt(this.value);
			
			if(numero > 600){
				this.value = 600;
				numero = 600;
			}
			
			if(numero < 0){
				this.value = '';
				numero = '0';
			}
			
			if(!numero){
				numero = '0';
			}
			
			$(obj).myAttr('data-tamanho-imagens',numero);
			$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').find('.b2make-instagram-posts').each(function(){
				$(this).css('margin',Math.floor(((15*numero)/220))+'px');
				$(this).css('width',numero+'px');
				$(this).css('height',numero+'px');
			});
		});
		
		$('.b2make-wso-addthis-redes').on('mouseup touchend change',function(e){
			e.stopPropagation();
			var obj = b2make.conteiner_child_obj;
			var id = $(this).myAttr('id');
			var mostrar = $(obj).myAttr('data-mostrar');
			id = id.replace(/b2make-wso-addthis-/gi,'');
			
			if($(this).prop("checked")){
				$(obj).myAttr('data-mostrar',id+(mostrar ? ','+mostrar : ''));
			} else {
				var mostrar_arr = mostrar.split(',');;
				var mostrar_novo = '';
				
				for(var i=0;i<mostrar_arr.length;i++){
					if(id != mostrar_arr[i]){
						mostrar_novo = mostrar_novo + (mostrar_novo ? ',' : '') + mostrar_arr[i];
					}
				}
				
				$(obj).myAttr('data-mostrar',mostrar_novo);
			}
			
			addthis_update();
		});
		
		$('#b2make-wsoi-sua-conta-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			b2make.instagram_token = false;
			b2make.instagram_trocar_conta = true;
			instagram_autorizar();
		});
		
		$('#b2make-wso-download-texto-val').on('keyup change',function (e) {
			var value = this.value;
			var obj = b2make.conteiner_child_obj;
			
			$(obj).find('.b2make-widget-out').find('.b2make-download-widget-link').find('.b2make-download-widget-holder').find('.b2make-texto-cel').html(value);
		});
		
		$('#b2make-wso-download-arquivo-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open();
		});
		
		$('#b2make-wod-caixa-gradiente-val').on('change',function(e){
			var value = this.value;
			var obj = b2make.conteiner_child_obj;
			
			var gradiente = $(obj).find('.b2make-widget-out').find('.b2make-download-widget-link').find('.b2make-download-widget-holder').find('.b2make-download-widget-gradient');
			
			$(obj).myAttr('data-gradiente',value);
			
			if(value == 's'){
				gradiente.show();
			} else {
				gradiente.hide();
			}
		});
		
		$('#b2make-wod-caixa-cor-val,#b2make-wod-texto-cor-val').on('changeColor',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			var obj = b2make.conteiner_child_obj;
			
			switch(id){
				case 'b2make-wod-caixa-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-download-widget-link').find('.b2make-download-widget-holder').css('background-color',bg);
					$(obj).myAttr('data-caixa-color-ahex',ahex);
				break;
				case 'b2make-wod-texto-cor-val':
					$(obj).find('.b2make-widget-out').find('.b2make-download-widget-link').find('.b2make-download-widget-holder').css('color',bg);
					$(obj).myAttr('data-text-color-ahex',ahex);
				break;
				
			}
		});
		
		$('#b2make-wod-texto-text-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			var cssVar = '';
			var noSize = false;
			var type = $(this).myAttr('id')
			
			target = $(obj).find('.b2make-widget-out').find('.b2make-download-widget-link').find('.b2make-download-widget-holder');
			
			switch(e.type){
				case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-font-family',$(this).myAttr('data-font-family')); break;
				case 'changeFontSize': cssVar = 'fontSize';  target.css(cssVar,$(this).myAttr('data-font-size')+'px'); target.css('line-height',$(this).myAttr('data-font-size')+'px'); $(obj).myAttr('data-font-size',$(this).myAttr('data-font-size')); break;
				case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).myAttr('data-font-align'));$(obj).myAttr('data-font-align',$(this).myAttr('data-font-align')); break;
				case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).myAttr('data-font-italico',$(this).myAttr('data-font-italico')); break;
				case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).myAttr('data-font-negrito',$(this).myAttr('data-font-negrito')); break;
			}
		});
		
	}
	
	widgets();
	
	$.formulario_testar = function(form_id){
		var validar = true;
		var msg = '';
		var nao_validados = new Array();
		
		$('.b2make-formulario-obrigatorio').remove();
		$('form#'+form_id+' input').each(function(){
			var validar_param = $(this).myAttr('data-validate');
			
			$(this).removeClass('b2make-formulario-erro');
			
			switch(validar_param){
				case 'text':
					msg = '';
					if(!$(this).val()){ validar = false; nao_validados.push({ msg : msg , obj : $(this) }) };
				break;
			}
		});
		
		if(!validar){
			$("#b2make-dialogbox-btns").append($('<div class="b2make-formulario-obrigatorio">'+b2make.formularioObrigatorioText+'</div>'));
			for(var i=0;i<nao_validados.length;i++){
				nao_validados[i].obj.addClass('b2make-formulario-erro');
			}
			dialogbox_shake();
		}

		return validar;
	}
	
	function formulario_resetar(form_id){
		var validar = true;
		var msg = '';
		var nao_validados = new Array();
		
		if($('form#'+form_id).length > 0){
			$('.b2make-formulario-obrigatorio').remove();
			$('form#'+form_id+' input').each(function(){
				$(this).removeClass('b2make-formulario-erro');
			});
			$('form#'+form_id)[0].reset();
			$(".b2make-data").datepicker('setDate', new Date());
		}
	}
	
	$.formulario_edit = function(p){
		if(!p)p = {};
		
		$('form#'+p.form_id+' input').each(function(){
			var obj_pai = $(this);
			
			$('.b2make-lista-linha[data-id="'+p.data_id+'"] .b2make-lista-coluna').each(function(){
				if(!$(this).hasClass('b2make-lista-options')){
					if($(this).myAttr('data-field') == obj_pai.myAttr('name')){
						obj_pai.val($(this).html());
						return false;
					}
				}
			})
		});
	}
	
	function formulario(){
		if(!b2make.formularioObrigatorioText)b2make.formularioObrigatorioText = "* Campos obrigat&oacute;rios!";
		
		$(document.body).on('mouseup tap','.b2make-formulario-erro',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).removeClass('b2make-formulario-erro');
		});
	}
	
	formulario();
	
	function biblioteca_imagens_html(dados){
		if(!dados)dados = {};
		$('#b2make-biblioteca-imagens-lista').prepend($('<div id="b2make-imagem-holder-'+dados.id+'" class="b2make-image-holder b2make-tooltip" data-image-id="'+dados.id+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" title="'+b2make.msgs.bibliotecaImagensFile+': '+dados.file+'"><div class="b2make-image-delete b2make-tooltip" title="'+b2make.msgs.bibliotecaImagensDeleteX+'"></div><img src="'+dados.mini+'"></div>'));
	}
	
	function biblioteca_imagens_lista(){
		var id_func = 'biblioteca_imagens_lista';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func
			},
			beforeSend: function(){
				$('#b2make-biblioteca-imagens-lista').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-biblioteca-imagens-lista');
			},
			success: function(txt){
				$('#b2make-biblioteca-imagens-lista').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					switch(dados.status){
						case 'Ok':
							for(var i=0;i<dados.images.length;i++){
								biblioteca_imagens_html(dados.images[i]);
							}
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
						break;
						case 'NaoHaImagens':
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
				$('#b2make-biblioteca-imagens-lista').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function biblioteca_imagens_delete(){
		var id_str = b2make.biblioteca_imagens_delete_id;
		var id = id_str.replace(/b2make-imagem-holder-/gi,'');
		
		var id_func = 'biblioteca_imagens_delete';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
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
							$('#'+id_str).remove();
							
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'imagem-del',id:id});
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
	
	function biblioteca_imagens_select(){
		var obj_selected = b2make.imagem_selected;
		var obj_target = (!b2make.perfil_foto_image_select ? (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj) : b2make.perfil_foto_image_select );
		var type = (!b2make.perfil_foto_image_select ? $(obj_target).myAttr('data-type') : 'foto-perfil' );
		var image_id = obj_selected.myAttr('data-image-id');
		var galeria_id = b2make.galerias_atual;
		var image_url = obj_selected.myAttr('data-image-url');
		var image_width = obj_selected.myAttr('data-image-width');
		var image_height = obj_selected.myAttr('data-image-height');
		var img;
		
		b2make.imagem_count++;
		
		if(b2make.redes_sociais_image_select){
			obj_target = '.b2make-redessociais-options-snapshot[data-id="'+b2make.redes_sociais_image_select+'"]';
			type = 'redessociaisimg';
		}
		
		if(b2make.widget_sub_options_type == 'favicon'){
			type = 'favicon';
		}
		
		if(b2make.segmento_foto_image_select){
			obj_target = b2make.segmento_foto_image_select;
			type = 'foto-segmento';
		}
		
		if(b2make.template_foto_image_select){
			obj_target = b2make.template_foto_image_select;
			type = 'foto-template';
		}
		
		switch(type){
			case 'redessociaisimg':
				$(obj_target).find('div').html('');
				$(obj_target).find('div').css('left','0px');
				$(obj_target).find('div').css('backgroundImage','url('+image_url+')');
				if(parseInt(image_width) < parseInt(image_height)){
					$(obj_target).find('div').css('background-size','100% auto');
				} else {
					$(obj_target).find('div').css('background-size','auto 100%');
				}
				
				var obj = b2make.conteiner_child_obj;
				var id = b2make.redes_sociais_image_select;
				var value = 'url('+image_url+'),'+image_width+','+image_height;
				var images = $(obj).myAttr('data-images');
				var images_arr = new Array();
				
				if(images)
					images_arr = images.split(';');
				
				var images_saida = '';
				var found;
				
				for(var i=0;i<images_arr.length;i++){
					var image_arr = images_arr[i].split(',');
					
					if(image_arr[0] == id){
						images_saida = images_saida + (images_saida ? ';' : '') + id + ',' + value;
						found = true;
					} else {
						images_saida = images_saida + (images_saida ? ';' : '') + images_arr[i];
					}
				}
				
				if(!found){
					images_saida = images_saida + (images_saida ? ';' : '') + id + ',' + value;
				}

				
				if(images_saida)
					$(obj).myAttr('data-images',images_saida);
				
				redessociais_widget_update();
				
				b2make.widget_sub_options_back = false;
				$.widget_sub_options_open();
			break;
			case 'foto-perfil':
				$(obj_target).css('backgroundImage','url('+image_url+')');
				if(parseInt(image_width) < parseInt(image_height)){
					$(obj_target).css('background-size','100% auto');
				} else {
					$(obj_target).css('background-size','auto 100%');
				}
				
				var opcao = 'foto-perfil';
				var id = image_id;
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						opcao : opcao,
						id : id
					},
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
									if(!localStorage.getItem('b2make.avatar_version')){
										localStorage.setItem('b2make.avatar_version',1);
									} else {
										var avatar_version = parseInt(localStorage.getItem('b2make.avatar_version'));
										
										avatar_version++;
										localStorage.setItem('b2make.avatar_version',avatar_version);
									}
									
									foto_perfil_close();
								break;
								default:
									console.log('ERROR - '+opcao+' - '+dados.status);
								
							}
						} else {
							console.log('ERROR - '+opcao+' - '+txt);
						}
						
						$.carregamento_close();
					},
					error: function(txt){
						console.log('ERROR AJAX - '+opcao+' - '+txt);
						$.carregamento_close();
					}
				});
				
			break;
			case 'foto-segmento':
			case 'foto-template':
				$(obj_target).css('backgroundImage','url('+image_url+')');
				if(parseInt(image_width) < parseInt(image_height)){
					$(obj_target).css('background-size','100% auto');
				} else {
					$(obj_target).css('background-size','auto 100%');
				}
				
				b2make.imagem_templates_id = image_id;
				
				if(type == 'foto-segmento'){
					if(b2make.segmento_open){
						segmento_edit_server();
					} else {
						segmento_add_server();
					}
				}
				
				if(type == 'foto-template'){
					if(b2make.template_open){
						template_edit_server();
					} else {
						template_add_server();
					}
				}
				
				foto_perfil_close();
			break;
			case 'favicon':
				var opcao = 'favicon';
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						opcao : opcao,
						image_url : image_url
					},
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
									var fav = $('#b2make-page-options-favicon');
									fav.css('backgroundImage','url('+dados.favicon+(dados.version ? '?v='+dados.version : '')+')');
									
									var msg = b2make.msgs.publishPageFavicon;
									
									$.dialogbox_open({
										confirm:true,
										calback_yes: 'b2make-menu-publish-yes',
										msg: msg
									});
								break;
								default:
									console.log('ERROR - '+opcao+' - '+dados.status);
								
							}
						} else {
							console.log('ERROR - '+opcao+' - '+txt);
						}
						
						$.carregamento_close();
					},
					error: function(txt){
						console.log('ERROR AJAX - '+opcao+' - '+txt);
						$.carregamento_close();
					}
				});
				
			break;
			case 'imagem':
				$(obj_target).html('');
				$(obj_target).myAttr('data-image-id',image_id);
				$(obj_target).myAttr('data-galeria-id',galeria_id);
				$(obj_target).css('backgroundColor','transparent');
				img = $('<img src="'+image_url+'" id="b2make-imagem-'+b2make.imagem_count+'" class="b2make-imagem" data-type="imagem" data-image-id="'+image_id+'" data-image-galeria="'+b2make.galerias_atual+'" data-image-width="'+image_width+'" data-image-height="'+image_height+'">');
				img.appendTo($(obj_target));
				
				imagem_resize(img);					
			break;
			case 'texto':
				$(obj_target).css('backgroundImage','url('+image_url+')');
				$(obj_target).myAttr('data-type-image-background',image_url);
				$(obj_target).myAttr('data-image-id',image_id);
				$(obj_target).myAttr('data-galeria-id',galeria_id);
				$('#b2make-wot-bg-image').css('background-size','25px auto');
				$('#b2make-wot-bg-image').css('backgroundImage','url('+image_url+')');
			break;
			case 'conteiner':
				$(obj_target).css('backgroundImage','url('+image_url+')');
				$(obj_target).myAttr('data-type-image-background',image_url);
				$(obj_target).myAttr('data-image-id',image_id);
				$(obj_target).myAttr('data-galeria-id',galeria_id);
				$(obj_target).myAttr('data-image-width',image_width);
				$(obj_target).myAttr('data-image-height',image_height);
				$('#b2make-conteiner-bg-image').css('background-size','25px auto');
				$('#b2make-conteiner-bg-image').css('backgroundImage','url('+image_url+')');
				
				biblioteca_imagens_conteiners_update();
			break;
			case 'youtube':
				$(obj_target).find('.b2make-widget-out').find('.b2make-youtube-cont').css('backgroundImage','url('+image_url+')');
				$(obj_target).myAttr('data-type-image-background',image_url);
				$(obj_target).myAttr('data-image-id',image_id);
				$(obj_target).myAttr('data-galeria-id',galeria_id);
				$(obj_target).myAttr('data-image-width',image_width);
				$(obj_target).myAttr('data-image-height',image_height);
				$('#b2make-wso-youtube-bg-image').css('background-size','25px auto');
				$('#b2make-wso-youtube-bg-image').css('backgroundImage','url('+image_url+')');
			break;
		}
	}
	
	function biblioteca_imagens_conteiners_update(){
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
									
									$(this).css('background-size','cover');
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
	
	function biblioteca_imagens_upload_callback(dados){
		var id_func = 'imgUpload';
		
		switch(dados.status){
			case 'Ok':
				biblioteca_imagens_html(dados);
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				$.disk_usage_diskused_add(dados.size);
				widgets_update({type:'imagem-uploaded',id:b2make.galerias_atual});
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function biblioteca_imagens_upload(){
		$.upload_files_start({
			url_php : 'uploadimg.php',
			input_selector : '#b2make-biblioteca-imagens-input',
			file_type : 'imagem',
			callback : biblioteca_imagens_upload_callback
		});
	}
	
	function biblioteca_imagens(){
		if(!b2make.msgs.bibliotecaImagensDeleteX)b2make.msgs.bibliotecaImagensDeleteX = 'Clique para excluir esta imagem';
		if(!b2make.msgs.bibliotecaImagensFile)b2make.msgs.bibliotecaImagensFile = 'Arquivo';
		
		biblioteca_imagens_upload();
		biblioteca_imagens_lista();
		
		b2make.biblioteca_imagens_confirm_delete = true;
		
		$('#b2make-biblioteca-imagens-excluir-selecao').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = b2make.conteiner_obj;
			var obj_filho = b2make.conteiner_child_obj;
			
			$('#b2make-biblioteca-imagens-lista .b2make-image-holder').each(function(){
				if($(this).hasClass('b2make-image-holder-clicked')){
					$(this).removeClass('b2make-image-holder-clicked');
				}
			});
			
			if(obj_filho){
				switch(b2make.conteiner_child_type){
					case 'texto':
						$(obj_filho).myAttr('data-type-image-background',null);
						$(obj_filho).myAttr('data-image-id',null);
						$(obj_filho).css('backgroundImage','none');
						$('#b2make-wot-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
						$('#b2make-wot-bg-image').css('background-size','auto auto');
					break;
					case 'imagem':
						$(obj_filho).html(b2make.imagem.value);
						$(obj_filho).myAttr('data-image-id',null);
						$(obj_filho).css('backgroundColor',b2make.imagem.backgroundColor);
					break;
					case 'redessociais':
						if(b2make.redes_sociais_image_select){
							var obj_target = '.b2make-redessociais-options-snapshot[data-id="'+b2make.redes_sociais_image_select+'"]';
							
							var obj = b2make.conteiner_child_obj;
							var id = b2make.redes_sociais_image_select;
							var images = $(obj).myAttr('data-images');
							var images_arr = new Array();
							
							if(images)
								images_arr = images.split(';');
							
							var images_saida = '';
							var found;
							
							for(var i=0;i<images_arr.length;i++){
								var image_arr = images_arr[i].split(',');
								
								if(image_arr[0] == id){
									found = true;
								} else {
									images_saida = images_saida + (images_saida ? ';' : '') + images_arr[i];
								}
							}
							
							if(images_saida)
								$(obj).myAttr('data-images',images_saida);
							else 
								$(obj).myAttr('data-images',null);
							
							for(var i=0;i<b2make.redessociais.length;i++){
								var id = b2make.redessociais[i].id;
								
								if(id == b2make.redes_sociais_image_select){
									var img = $('<img class="svg social-link b2make-redessociais-options-snapshot-img" src="images/b2make-icones-sociais.svg">');
									
									$(obj_target).find('div').css('backgroundImage','none');
									img.appendTo($(obj_target).find('div'));
									
									$(obj_target).find('div').css('left','-'+(i*b2make.redessociais_fator)+'px');
									
									jQuery('img.svg').each(function(){
										var $img = jQuery(this);
										var imgID = $img.myAttr('id');
										var imgClass = $img.myAttr('class');
										var imgURL = $img.myAttr('src');

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

											// Remove any invalid XML tags as per http://validator.w3.org
											$svg = $svg.removeAttr('xmlns:a');

											// Replace image with new SVG
											$img.replaceWith($svg);

										}, 'xml');

									});
									
									break;
								}
								
							}
							
							redessociais_widget_update();
							
							b2make.widget_sub_options_back = false;
							$.widget_sub_options_open();
						}
					break;
					case 'youtube':
						$(obj).find('.b2make-widget-out').find('.b2make-youtube-cont').css('backgroundImage','none');
						$(obj).myAttr('data-type-image-background',null);
						$(obj).myAttr('data-image-id',null);
						$('#b2make-wso-youtube-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
						$('#b2make-wso-youtube-bg-image').css('background-size','auto auto');
					break;
				}
			} else {
				$(obj).css('backgroundImage','none');
				$(obj).myAttr('data-type-image-background',null);
				$(obj).myAttr('data-image-id',null);
				$('#b2make-conteiner-bg-image').css('backgroundImage','url(jpicker/images/bar-opacity.png)');
				$('#b2make-conteiner-bg-image').css('background-size','auto auto');
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-image-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			$('.b2make-image-holder').removeClass('b2make-image-holder-clicked');
			$(this).addClass('b2make-image-holder-clicked');
			
			b2make.imagem_selected = $(this);
			biblioteca_imagens_select();
		});
		
		$(document.body).on('mouseup tap','.b2make-image-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var msg = b2make.msgs.imagemDelete;
			
			b2make.biblioteca_imagens_delete_id = $(this).parent().myAttr('id');
			
			if(b2make.biblioteca_imagens_confirm_delete){
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-image-delete-yes',
					msg: msg
				});
			} else {
				biblioteca_imagens_delete();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-image-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			biblioteca_imagens_delete();
		});
		
		$('#b2make-biblioteca-imagens-confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
			
			if($(this).find('input').prop("checked")){
				b2make.biblioteca_imagens_confirm_delete = true;
			} else {
				b2make.biblioteca_imagens_confirm_delete = false;
			}
		});
		
		$('#b2make-biblioteca-imagens-confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).prop("checked")){
				b2make.biblioteca_imagens_confirm_delete = false;
			} else {
				b2make.biblioteca_imagens_confirm_delete = true;
			}
		});
	}
	
	biblioteca_imagens();
	
	function arquivos_html(dados){
		if(!dados)dados = {};
		$('#b2make-arquivos-lista').prepend($('<div id="b2make-arquivo-holder-'+dados.id+'" class="b2make-arquivo-holder b2make-tooltip" data-arquivo-id="'+dados.id+'" data-arquivo-url="'+dados.arquivo+'" title="'+b2make.msgs.arquivoFile+': '+dados.file+'"><div class="b2make-arquivo-delete b2make-tooltip" title="'+b2make.msgs.arquivoDeleteX+'"></div><img src="'+location.href+'images/b2make-arquivo.png"></div>'));
	}
	
	function arquivos_lista(){
		var id_func = 'arquivos_lista';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func
			},
			beforeSend: function(){
				$('#b2make-arquivos-lista').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-arquivos-lista');
			},
			success: function(txt){
				$('#b2make-arquivos-lista').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					switch(dados.status){
						case 'Ok':
							for(var i=0;i<dados.arquivos.length;i++){
								arquivos_html(dados.arquivos[i]);
							}
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
						break;
						case 'NaoHaImagens':
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
				$('#b2make-arquivos-lista').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function arquivos_delete(){
		var id_str = b2make.arquivos_delete_id;
		var id = id_str.replace(/b2make-arquivo-holder-/gi,'');
		
		var id_func = 'arquivos_delete';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
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
							$('#'+id_str).remove();
							
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'arquivo-del',id:id});
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
	
	function arquivos_select(){
		var obj_selected = b2make.arquivo_selected;
		var obj_target = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
		var type = $(obj_target).myAttr('data-type');
		var arquivo_id = obj_selected.myAttr('data-arquivo-id');
		var arquivo_url = obj_selected.myAttr('data-arquivo-url');
		
		b2make.arquivo_count++;
		
		switch(type){
			case 'download':
				$(obj_target).find('.b2make-widget-out').find('.b2make-download-widget-link').myAttr('href',arquivo_url);
				$(obj_target).myAttr('data-arquivo-id',arquivo_id);				
			break;
		}
	}
	
	function arquivos_upload_callback(dados){
		var id_func = 'arquivos';
		
		switch(dados.status){
			case 'Ok':
				arquivos_html(dados);
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				$.disk_usage_diskused_add(dados.size);
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function arquivos_upload(){
		$.upload_files_start({
			url_php : 'uploadfile.php',
			input_selector : '#b2make-arquivos-input',
			callback : arquivos_upload_callback
		});
	}
	
	function arquivos(){
		if(!b2make.msgs.arquivoDeleteX)b2make.msgs.arquivoDeleteX = 'Clique para excluir este arquivo';
		if(!b2make.msgs.arquivoFile)b2make.msgs.arquivoFile = 'Arquivo';
		
		arquivos_upload();
		arquivos_lista();
		
		b2make.arquivos_confirm_delete = true;
		
		$('#b2make-arquivos-excluir-selecao').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj_filho = b2make.conteiner_child_obj;
			
			$('#b2make-arquivos-lista .b2make-arquivo-holder').each(function(){
				if($(this).hasClass('b2make-arquivo-holder-clicked')){
					$(this).removeClass('b2make-arquivo-holder-clicked');
				}
			});
			
			switch(b2make.conteiner_child_type){
				case 'download':
					$(obj_filho).find('.b2make-widget-out').find('.b2make-download-widget-link').myAttr('href',location.href+'b2make-arquivo-download.zip');
					$(obj_filho).myAttr('data-arquivo-id',null);
				break;
			}
			
		});
		
		$(document.body).on('mouseup tap','.b2make-arquivo-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			$('.b2make-arquivo-holder').removeClass('b2make-arquivo-holder-clicked');
			$(this).addClass('b2make-arquivo-holder-clicked');
			
			b2make.arquivo_selected = $(this);
			arquivos_select();
		});
		
		$(document.body).on('mouseup tap','.b2make-arquivo-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var msg = b2make.msgs.arquivoDelete;
			
			b2make.arquivos_delete_id = $(this).parent().myAttr('id');
			
			if(b2make.arquivos_confirm_delete){
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-arquivo-delete-yes',
					msg: msg
				});
			} else {
				arquivos_delete();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-arquivo-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			arquivos_delete();
		});
		
		$('#b2make-arquivos-confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
			
			if($(this).find('input').prop("checked")){
				b2make.arquivos_confirm_delete = true;
			} else {
				b2make.arquivos_confirm_delete = false;
			}
		});
		
		$('#b2make-arquivos-confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).prop("checked")){
				b2make.arquivos_confirm_delete = false;
			} else {
				b2make.arquivos_confirm_delete = true;
			}
		});
	}
	
	arquivos();
	
	function album_fotos_dados_edit(id){
		b2make.albumfotos.id_image = id;
		
		$.dialogbox_open({
			width:440,
			height:270,
			message:true,
			calback_yes: 'b2make-album-fotos-data-edit-calback',
			title: b2make.msgs.albumFotosEditDataTitle,
			coneiner: 'b2make-formulario-album-fotos-dados'
		});
		
		$('#b2make-faf-descricao').val($('#b2make-album-fotos-imagem-holder-'+id).myAttr('data-descricao'));
	}

	function album_fotos_dados_edit_base(){
		$.dialogbox_close();
		
		var opcao = 'album-fotos-data-edit';
		var id = b2make.albumfotos.id_image;
		
		$('.b2make-albumfotos-widget-image-mini[data-id="'+id+'"]').myAttr('data-descricao',$('#b2make-faf-descricao').val());
		$('.b2make-albumfotos-widget-images-descricao[data-id="'+id+'"]').myAttr('data-descricao',$('#b2make-faf-descricao').val());
		$('#b2make-album-fotos-imagem-holder-'+id).myAttr('data-descricao',$('#b2make-faf-descricao').val());
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				descricao : $('#b2make-faf-descricao').val(),
				id_image : id,
				id_album : b2make.album_foto_atual
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
							albumfotos_widget_album_update({});
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

	function album_fotos_images_select(){
		var obj_selected = b2make.album_fotos_imagem_selected;
		var obj_target = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
		var image_url = obj_selected.myAttr('data-image-url');
		var image_id = obj_selected.myAttr('data-image-id');
		var image_width = obj_selected.myAttr('data-image-width');
		var image_height = obj_selected.myAttr('data-image-height');
		
		$(obj_target).find('div.b2make-widget-out')
			.find('div.b2make-albumfotos-widget-holder')
			.find('div.b2make-albumfotos-widget-image[id="b2make-albumfotos-widget-imagem-'+b2make.album_foto_atual+'"]')
			.css('backgroundImage','url('+image_url+')')
			.myAttr('data-album-fotos-imagem-id',image_id)
			.myAttr('data-album-fotos-imagem-width',image_width)
			.myAttr('data-album-fotos-imagem-height',image_height);
		
		var image = $(obj_target).find('div.b2make-widget-out')
			.find('div.b2make-albumfotos-widget-holder')
			.find('div.b2make-albumfotos-widget-image[id="b2make-albumfotos-widget-imagem-'+b2make.album_foto_atual+'"]');
	
		var target = image.find('.b2make-albumfotos-widget-titulo');
		var imagem_width = parseInt(image.myAttr('data-album-fotos-imagem-width'));
		var imagem_height = parseInt(image.myAttr('data-album-fotos-imagem-height'));
		var conteiner_width = parseInt($('#b2make-woaf-imagem-val').val());
		
		//var altura = Math.floor((conteiner_width * imagem_height) / imagem_width);
		var altura = conteiner_width;
		
		target.css('top',(b2make.albumfotos.margin_title+altura)+'px');
	}
	
	function album_fotos_images_html(dados){
		$('#b2make-album-fotos-lista-images').append($('<div id="b2make-album-fotos-imagem-holder-'+dados.id+'" class="b2make-album-fotos-image-holder b2make-tooltip" data-image-id="'+dados.id+'" data-image-url="'+dados.imagem+'" data-descricao="'+dados.descricao+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" title="'+b2make.msgs.albumFotosFile+': '+dados.file+'"><div class="b2make-album-fotos-data-edit b2make-tooltip" title="'+b2make.msgs.albumFotosEditX+'"></div><div class="b2make-album-fotos-image-delete b2make-tooltip" title="'+b2make.msgs.albumFotosDeleteX+'"></div><img src="'+dados.mini+'"></div>'));
	}
	
	function album_fotos_images(){
		var id_func = 'albuns-fotos-images';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.album_foto_atual
			},
			beforeSend: function(){
				$('#b2make-album-fotos-lista-images').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-album-fotos-lista-images');
			},
			success: function(txt){
				$('#b2make-album-fotos-lista-images').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							for(var i=0;i<dados.images.length;i++){
								album_fotos_images_html(dados.images[i]);
							}
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							
							if(b2make.album_fotos_widget_update){
								widgets_update({type:'album_fotos-del',id:b2make.album_fotos_widget_update_id});
								b2make.album_fotos_widget_update_id = false;
								b2make.album_fotos_widget_update = false;
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
				$('#b2make-album-fotos-lista-images').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function album_fotos_imagens_delete(){
		var id = b2make.album_fotos_imagens_delete_id;
		var id_func = 'album-fotos-images-del';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : id,
				album : b2make.album_foto_atual
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
							var url = $('.b2make-album-fotos-image-holder[data-image-id="'+id+'"]').myAttr('data-image-url');
							
							$('.b2make-album-fotos-image-holder[data-image-id="'+id+'"]').remove();
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'album-fotos-imagem-del',id:id,id_album:b2make.album_foto_atual,url:url});
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
	
	function album_fotos_menu_html(dados){
		if(!dados)dados = {};
		$('#b2make-album-fotos-lista-albuns').prepend($('<div class="b2make-album-fotos-lista-album"><div class="b2make-album-fotos-show b2make-tooltip" title="'+b2make.msgs.albumFotosShow+'" data-status="'+(dados.album_show ? 'show' : 'not-show')+'" data-album-fotos-id="'+dados.album_fotos_id+'"></div><div class="b2make-album-fotos-nome b2make-tooltip" title="'+b2make.msgs.albumFotosNome+'" data-album-fotos-legenda="'+dados.album_fotos_legenda+'" data-status="'+(dados.album_selected ? 'show' : 'not-show')+'" data-album-fotos-id="'+dados.album_fotos_id+'">'+dados.album_fotos_nome+'</div><div class="b2make-album-fotos-edit b2make-tooltip" data-album-fotos-id="'+dados.album_fotos_id+'" title="'+b2make.msgs.albumFotosEdit+'"></div><div class="b2make-album-fotos-delete b2make-tooltip" data-album-fotos-id="'+dados.album_fotos_id+'" title="'+b2make.msgs.albumFotosDelete+'"></div><div class="clear"></div></div>'));
	}
	
	function album_fotos_add(){
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-album-fotos-add-calback',
			title: b2make.msgs.albumFotosAddTitle,
			coneiner: 'b2make-formulario-album-fotos'
		});
	}
	
	function album_fotos_add_base(){
		var id_func = 'album-fotos-add';
		var form_id = 'b2make-formulario-album-fotos';
		
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
								$('.b2make-album-fotos-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								dados.album_show = true;
								dados.album_selected = true;
								album_fotos_menu_html(dados);
								$('.b2make-tooltip').tooltip({
									show: {
										effect: "fade",
										delay: 400
									}
								});
								$.dialogbox_close();
								
								b2make.album_foto_atual = dados.album_fotos_id;
								b2make.album_foto_nome = dados.album_fotos_nome;
								
								$('#b2make-album-fotos-btn').show();
								$('#b2make-album-fotos-lista-images').html('');
								
								albumfotos_widget_album_add({
									albumfotos_id: dados.album_fotos_id,
									albumfotos_nome: dados.album_fotos_nome
								});
								
								if(!b2make.album_fotos_todos_ids)b2make.album_fotos_todos_ids = new Array();
								b2make.album_fotos_todos_ids.push(dados.album_fotos_id);
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
	
	function album_fotos_edit(id){
		$('#b2make-formulario-album-fotos #b2make-faf-nome').val($('.b2make-album-fotos-nome[data-album-fotos-id="'+id+'"]').html());
		
		b2make.album_fotos_edit_id = id;
		
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-album-fotos-edit-calback',
			title: b2make.msgs.albumFotosEditTitle,
			coneiner: 'b2make-formulario-album-fotos'
		});
	}
	
	function album_fotos_edit_base(){
		var id_func = 'album-fotos-edit';
		var form_id = 'b2make-formulario-album-fotos';
		var id = b2make.album_fotos_edit_id;
		
		b2make.album_fotos_edit_id = false;
		
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
								$('.b2make-album-fotos-nome[data-album-fotos-id="'+id+'"]').html(dados.nome);
								
								widgets_update({type:'album-fotos-edit',id:id,nome:dados.nome});
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
	
	function album_fotos_del(id){
		b2make.album_fotos_del_id = id;
		
		var msg = b2make.msgs.albumFotosDelTitle;
		msg = msg.replace(/#album#/gi,$('.b2make-album-fotos-nome[data-album-fotos-id="'+id+'"]').html());
		
		$.dialogbox_open({
			confirm:true,
			calback_yes: 'b2make-album-fotos-del-calback',
			msg: msg
		});
	}
	
	function album_fotos_del_base(){
		var id_func = 'album-fotos-del';
		var id = b2make.album_fotos_del_id;
		
		b2make.album_fotos_del_id = false;
	
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
							$('.b2make-album-fotos-delete[data-album-fotos-id="'+id+'"]').parent().remove();
							$.dialogbox_close();
							
							var id_aux = $('#b2make-album-fotos-lista-albuns .b2make-album-fotos-lista-album:first-child .b2make-album-fotos-show').myAttr('data-album-fotos-id');
							
							$('#b2make-album-fotos-lista-images').html('');
							
							if(id_aux){
								b2make.album_foto_atual = id_aux;
								b2make.album_foto_nome = $('.b2make-album-fotos-nome[data-album-fotos-id="'+id_aux+'"]').html();
								
								$('.b2make-album-fotos-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								$('.b2make-album-fotos-nome[data-album-fotos-id="'+id_aux+'"]').myAttr('data-status','show');
								
								album_fotos_images();
								$('#b2make-album-fotos-btn').show();
							} else {
								$('#b2make-album-fotos-btn').hide();
							}
							
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'album-fotos-delete',id:id});
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
	
	function album_fotos_upload_params(){
		return new Array({
			variavel : 'album',
			valor : b2make.album_foto_atual,
		})
	}
	
	function album_fotos_upload_callback(dados){
		var id_func = 'albuns-fotos';
		
		switch(dados.status){
			case 'Ok':
				album_fotos_images_html(dados);
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				$.disk_usage_diskused_add(dados.size);
				widgets_update({type:'album-fotos-imagem-uploaded',id:b2make.album_foto_atual,url:dados.imagem,dados:dados});
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function album_fotos_upload(){
		$.upload_files_start({
			url_php : 'uploadalbumfotos.php',
			input_selector : '#b2make-album-fotos-input',
			file_type : 'imagem',
			post_params : album_fotos_upload_params,
			callback : album_fotos_upload_callback
		});
	}
	
	function album_fotos(){
		b2make.albumfotos = {};
		
		b2make.albumfotos.fator_ajuste = 0.8;
		b2make.albumfotos.margin_title = 4;
		b2make.albumfotos.margin_image = 0;
		
		
		if(!b2make.msgs.albumFotosEditDataTitle)b2make.msgs.albumFotosEditDataTitle = 'Editar dados da imagem';
		if(!b2make.msgs.albumFotosEditX)b2make.msgs.albumFotosEditX = 'Clique para editar os dados desta imagem';
		if(!b2make.msgs.albumFotosDeleteX)b2make.msgs.albumFotosDeleteX = 'Clique para excluir esta imagem';
		if(!b2make.msgs.albumFotosFile)b2make.msgs.albumFotosFile = 'Arquivo';
		if(!b2make.msgs.albumFotosEdit)b2make.msgs.albumFotosEdit = 'Clique para Editar o Nome deste &aacute;lbum de fotos';
		if(!b2make.msgs.albumFotosNome)b2make.msgs.albumFotosNome = 'Clique para alterar as fotos deste &aacute;lbum de fotos';
		if(!b2make.msgs.albumFotosDelete)b2make.msgs.albumFotosDelete = 'Clique para deletar este &aacute;lbum de fotos';
		if(!b2make.msgs.albumFotosShow)b2make.msgs.albumFotosShow = 'Clique para que o &aacute;lbum de fotos mostrar/n&atilde;o mostrar este &aacute;lbum de fotos no widget &aacute;lbum de fotos';
		if(!b2make.msgs.albumFotosDelTitle)b2make.msgs.albumFotosDelTitle = 'Tem certeza que deseja excluir <b>#album#</b>?';
		if(!b2make.msgs.albumFotosEditTitle)b2make.msgs.albumFotosEditTitle = 'Editar Nome do &Aacute;lbum';
		if(!b2make.msgs.albumFotosAddTitle)b2make.msgs.albumFotosAddTitle = 'Adicionar &Aacute;lbum';
		if(!b2make.msgs.albumFotosDeleteX)b2make.msgs.albumFotosDeleteX = 'Clique para excluir esta imagem';
		
		$('#b2make-album-fotos-btn').hide();
		
		album_fotos_upload();
		
		b2make.album_fotos_confirm_delete = true;
		var id_func = 'albuns-fotos';
		
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
							var album_show,album_selected;
							var album_fotos_todos_ids = new Array();
							
							for(var i=0;i<dados.resultado.length;i++){
								album_show = true;
								album_selected = false;
								
								if(i==dados.resultado.length - 1){
									b2make.album_foto_atual = dados.resultado[i].id_site_album_fotos;
									b2make.album_foto_nome = dados.resultado[i].nome;
									album_selected = true;
									album_fotos_images();
									$('#b2make-album-fotos-btn').show();
								}
								
								album_fotos_menu_html({
									album_selected:album_selected,
									album_show:album_show,
									album_fotos_id:dados.resultado[i].id_site_album_fotos,
									album_fotos_nome:dados.resultado[i].nome,
									album_fotos_legenda:dados.resultado[i].legenda
								});
								
								if(!b2make.album_fotos_todos_ids){
									album_fotos_todos_ids.push(dados.resultado[i].id_site_album_fotos);
								}
							}
							
							if(!b2make.album_fotos_todos_ids){
								b2make.album_fotos_todos_ids = album_fotos_todos_ids;
							}
							
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
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-data-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).parent().myAttr('data-image-id');
			e.stopPropagation();
			album_fotos_dados_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-data-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			album_fotos_dados_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-image-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var msg = b2make.msgs.imagemDelete;
			
			b2make.album_fotos_imagens_delete_id = $(this).parent().myAttr('data-image-id');
			
			if(b2make.album_fotos_confirm_delete){
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-album-fotos-image-delete-yes',
					msg: msg
				});
			} else {
				album_fotos_imagens_delete();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-image-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_fotos_imagens_delete();
		});
		
		$('#b2make-album-fotos-confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
			
			if($(this).find('input').prop("checked")){
				b2make.album_fotos_confirm_delete = true;
			} else {
				b2make.album_fotos_confirm_delete = false;
			}
		});
		
		$('#b2make-album-fotos-confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).prop("checked")){
				b2make.album_fotos_confirm_delete = false;
			} else {
				b2make.album_fotos_confirm_delete = true;
			}
		});
		
		$('#b2make-album-fotos-add').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_fotos_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_fotos_add_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-show',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-album-fotos-id');
			var nome = $(this).parent().find('.b2make-album-fotos-nome').html();
			var obj = b2make.conteiner_child_obj;
			var albuns_not_show = $(obj).myAttr('data-albuns-not-show');
			var layout_tipo = $(obj).myAttr('data-layout-tipo');
			
			if($(this).myAttr('data-status') == 'show'){
				$(this).myAttr('data-status','not-show');
				switch(layout_tipo){
					case 'menu':
						var holder = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2');
						
						holder.find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry[data-album-fotos-id="'+id+'"]').remove();
						holder.find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini[data-album-fotos-id="'+id+'"]').each(function(){
							$(this).remove();
						});
						
						if(holder.find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry').length == 1){
							holder.find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry').myAttr('data-status','selected');
						}
						
						if(holder.find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry').length == 0){
							var imagem = location.href+'images/b2make-album-sem-imagem.png?v=2';
							holder.find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-image-2').css('background-image','url('+imagem+')');
						}
					break;
					default:
						$(obj)
							.find('.b2make-widget-out')
							.find('.b2make-albumfotos-widget-holder')
							.find('.b2make-albumfotos-widget-image[data-album-fotos-id="'+id+'"]')
							.remove();
				}
				
				if(albuns_not_show){
					$(obj).myAttr('data-albuns-not-show',albuns_not_show+','+id);
				} else {
					$(obj).myAttr('data-albuns-not-show',id);
				}
			} else {
				$(this).myAttr('data-status','show');
				albumfotos_widget_album_add({
					albumfotos_id: id,
					albumfotos_nome: nome
				});
				
				if(albuns_not_show){
					var ans_arr = albuns_not_show.split(',');
					var ans_final = '';
					
					for(var i=0;i<ans_arr.length;i++){
						if(ans_arr[i] != id){
							ans_final = ans_final + (ans_final.length > 0 ? ',' : '') + ans_arr[i];
						}
					}
					$(obj).myAttr('data-albuns-not-show',(ans_final.length > 0 ? ans_final : null));
				}
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-nome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$('.b2make-album-fotos-nome').each(function(){
				$(this).myAttr('data-status','not-show');
			});
			
			$(this).myAttr('data-status','show');
			
			var id = $(this).myAttr('data-album-fotos-id');
			
			b2make.album_foto_atual = $(this).myAttr('data-album-fotos-id');
			b2make.album_foto_nome = $(this).html();
			
			$('#b2make-album-fotos-legenda').val($(this).myAttr('data-album-fotos-legenda'));
			$('#b2make-album-fotos-lista-images').html('');
			album_fotos_images();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-album-fotos-id');
			album_fotos_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_fotos_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-album-fotos-id');
			album_fotos_del(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-del-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_fotos_del_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-fotos-image-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			$('.b2make-album-fotos-image-holder').removeClass('b2make-album-fotos-image-holder-clicked');
			$(this).addClass('b2make-album-fotos-image-holder-clicked');
			
			b2make.album_fotos_imagem_selected = $(this);
			album_fotos_images_select();
		});
		
		$(document.body).on('keyup','#b2make-album-fotos-legenda',function(e){
			var value = $(this).val();
			
			$.input_delay_to_change({
				trigger_selector:'#b2make-listener',
				trigger_event:'b2make-album-fotos-legenda-change',
				value:value
			});
		});
		
		$('#b2make-listener').on('b2make-album-fotos-legenda-change',function(e,value,p){
			if(!p) p = {};
			
			var id_func = 'album-fotos-legenda-edit';
			var id = b2make.album_foto_atual;
			
			var ajaxData = { 
				ajax : 'sim',
				opcao : id_func,
				legenda : value,
				id:id
			};
			
			var ajaxDataString = ''; $.each(ajaxData, function(key, value) {ajaxDataString = ajaxDataString + (ajaxDataString.length > 0 ? '&' : '') + key + '=' + value;});
			
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
								$('.b2make-album-fotos-nome[data-album-fotos-id="'+id+'"]').myAttr('data-album-fotos-legenda',dados.legenda);
								
								widgets_update({type:'album-fotos-legenda-edit',id:id,legenda:dados.legenda});
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
			
		});
		
		$(document.body).on('change','#b2make-woaf-mostrar-titulo-input',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = b2make.conteiner_child_obj;
			
			if($(this).is(':checked')){
				$(obj).removeAttr('data-nao-mostrar-titulo');
			} else {
				$(obj).myAttr('data-nao-mostrar-titulo','sim');
			}
			
			albumfotos_widget_album_update({});
		});
	}
	
	album_fotos();
	
	function slide_show_images_select(){
		/* var obj_selected = b2make.slide_show_imagem_selected;
		var obj_target = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
		var image_url = obj_selected.myAttr('data-image-url');
		var image_id = obj_selected.myAttr('data-image-id');
		
		$(obj_target).find('div.b2make-widget-out')
			.find('div.b2make-slideshow-widget-holder')
			.find('div.b2make-slideshow-widget-image[id="b2make-slideshow-widget-imagem-'+b2make.slide_show_atual+'"]')
			.css('backgroundImage','url('+image_url+')')
			.myAttr('data-slide-show-imagem-id',image_id); */
			
	}
	
	function slide_show_images_html(dados){
		$('#b2make-slide-show-lista-images').append($('<div id="b2make-slide-show-imagem-holder-'+dados.id+'" class="b2make-slide-show-image-holder b2make-tooltip" data-image-id="'+dados.id+'" data-image-url="'+dados.imagem+'" data-image-width="'+dados.width+'" data-image-height="'+dados.height+'" title="'+b2make.msgs.slideShowFile+': '+dados.file+'"><div class="b2make-slide-show-image-delete b2make-tooltip" title="'+b2make.msgs.slideShowDeleteX+'"></div><img src="'+dados.mini+'"></div>'));
	}
	
	function slide_show_images(){
		var id_func = 'slide-show-images';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.slide_show_atual
			},
			beforeSend: function(){
				$('#b2make-slide-show-lista-images').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-slide-show-lista-images');
			},
			success: function(txt){
				$('#b2make-slide-show-lista-images').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							for(var i=0;i<dados.images.length;i++){
								slide_show_images_html(dados.images[i]);
							}
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							
							if(b2make.slide_show_widget_update){
								widgets_update({type:'slide_show-del',id:b2make.slide_show_widget_update_id});
								b2make.slide_show_widget_update_id = false;
								b2make.slide_show_widget_update = false;
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
				$('#b2make-slide-show-lista-images').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function slide_show_imagens_delete(){
		var id = b2make.slide_show_imagens_delete_id;
		var id_func = 'slide-show-images-del';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : id,
				slide : b2make.slide_show_atual
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
							var url = $('.b2make-slide-show-image-holder[data-image-id="'+id+'"]').myAttr('data-image-url');
							
							$('.b2make-slide-show-image-holder[data-image-id="'+id+'"]').remove();
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'slide-show-imagem-del',id:id,id_slide:b2make.slide_show_atual,url:url});
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
	
	function slide_show_menu_html(dados){
		if(!dados)dados = {};
		$('#b2make-slide-show-lista-slides').prepend($('<div class="b2make-slide-show-lista-slide"><div class="b2make-slide-show-show b2make-tooltip" title="'+b2make.msgs.slideShowShow+'" data-status="'+(dados.slide_show ? 'show' : 'not-show')+'" data-slide-show-id="'+dados.slide_show_id+'"></div><div class="b2make-slide-show-nome b2make-tooltip" title="'+b2make.msgs.slideShowNome+'" data-status="'+(dados.slide_selected ? 'show' : 'not-show')+'" data-slide-show-id="'+dados.slide_show_id+'">'+dados.slide_show_nome+'</div><div class="b2make-slide-show-edit b2make-tooltip" data-slide-show-id="'+dados.slide_show_id+'" title="'+b2make.msgs.slideShowEdit+'"></div><div class="b2make-slide-show-delete b2make-tooltip" data-slide-show-id="'+dados.slide_show_id+'" title="'+b2make.msgs.slideShowDelete+'"></div></div>'));
	}
	
	function slide_show_add(){
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-slide-show-add-calback',
			title: b2make.msgs.slideShowAddTitle,
			coneiner: 'b2make-formulario-slide-show'
		});
	}
	
	function slide_show_add_base(){
		var id_func = 'slide-show-add';
		var form_id = 'b2make-formulario-slide-show';
		
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
								$('.b2make-slide-show-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								var obj = b2make.conteiner_child_obj;
								
								if(!$(obj).myAttr('data-slide-show-id')){
									dados.slide_show = true;
								}
								
								dados.slide_selected = true;
								slide_show_menu_html(dados);
								$('.b2make-tooltip').tooltip({
									show: {
										effect: "fade",
										delay: 400
									}
								});
								$.dialogbox_close();
								
								b2make.slide_show_atual = dados.slide_show_id;
								b2make.slide_foto_nome = dados.slide_show_nome;
								
								$('#b2make-slide-show-btn').show();
								$('#b2make-slide-show-lista-images').html('');
								
								var obj = b2make.conteiner_child_obj;
								
								if(!$(obj).myAttr('data-slide-show-id')){
									$(obj).myAttr('data-slide-show-id',dados.slide_show_id)
									
									slideshow_widget_create({
										slideshow_id: dados.slide_show_id,
										slideshow_nome: dados.slide_show_nome
									});
								}
								
								if(!b2make.slide_show_todos_ids) b2make.slide_show_todos_ids = new Array();
								
								b2make.slide_show_todos_ids.push(dados.slide_show_id);
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
	
	function slide_show_edit(id){
		$('#b2make-formulario-slide-show #b2make-fss-nome').val($('.b2make-slide-show-nome[data-slide-show-id="'+id+'"]').html());
		
		b2make.slide_show_edit_id = id;
		
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-slide-show-edit-calback',
			title: b2make.msgs.slideShowEditTitle,
			coneiner: 'b2make-formulario-slide-show'
		});
	}
	
	function slide_show_edit_base(){
		var id_func = 'slide-show-edit';
		var form_id = 'b2make-formulario-slide-show';
		var id = b2make.slide_show_edit_id;
		
		b2make.slide_show_edit_id = false;
		
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
								$('.b2make-slide-show-nome[data-slide-show-id="'+id+'"]').html(dados.nome);
								
								widgets_update({type:'slide-show-edit',id:id,nome:dados.nome});
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
	
	function slide_show_del(id){
		b2make.slide_show_del_id = id;
		
		var msg = b2make.msgs.slideShowDelTitle;
		msg = msg.replace(/#slide#/gi,$('.b2make-slide-show-nome[data-slide-show-id="'+id+'"]').html());
		
		$.dialogbox_open({
			confirm:true,
			calback_yes: 'b2make-slide-show-del-calback',
			msg: msg
		});
	}
	
	function slide_show_del_base(){
		var id_func = 'slide-show-del';
		var id = b2make.slide_show_del_id;
		
		b2make.slide_show_del_id = false;
	
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
							$('.b2make-slide-show-delete[data-slide-show-id="'+id+'"]').parent().remove();
							$.dialogbox_close();
							
							var id_aux = $('#b2make-slide-show-lista-slides .b2make-slide-show-lista-slide:first-child .b2make-slide-show-show').myAttr('data-slide-show-id');
							
							$('#b2make-slide-show-lista-images').html('');
							
							if(id_aux){
								b2make.slide_show_atual = id_aux;
								b2make.slide_foto_nome = $('.b2make-slide-show-nome[data-slide-show-id="'+id_aux+'"]').html();
								
								$('.b2make-slide-show-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								$('.b2make-slide-show-nome[data-slide-show-id="'+id_aux+'"]').myAttr('data-status','show');
								
								slide_show_images();
								$('#b2make-slide-show-btn').show();
							} else {
								$('#b2make-slide-show-btn').hide();
							}
							
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'slide-show-delete',id:id});
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
	
	function slide_show_upload_params(){
		return new Array({
			variavel : 'slide',
			valor : b2make.slide_show_atual,
		})
	}
	
	function slide_show_upload_callback(dados){
		var id_func = 'slide-show';
		
		switch(dados.status){
			case 'Ok':
				slide_show_images_html(dados);
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				$.disk_usage_diskused_add(dados.size);
				widgets_update({type:'slide-show-imagem-uploaded',id:b2make.slide_show_atual,dados:dados});
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function slide_show_upload(){
		$.upload_files_start({
			url_php : 'uploadslideshow.php',
			input_selector : '#b2make-slide-show-input',
			file_type : 'imagem',
			post_params : slide_show_upload_params,
			callback : slide_show_upload_callback
		});
	}
	
	function slide_show(){
		if(!b2make.msgs.slideShowFile)b2make.msgs.slideShowFile = 'Imagem';
		if(!b2make.msgs.slideShowDeleteX)b2make.msgs.slideShowDeleteX = 'Clique para excluir esta imagem';
		if(!b2make.msgs.slideShowEdit)b2make.msgs.slideShowEdit = 'Clique para Editar o Nome deste Slide Show';
		if(!b2make.msgs.slideShowNome)b2make.msgs.slideShowNome = 'Clique para alterar as fotos deste Slide Show';
		if(!b2make.msgs.slideShowDelete)b2make.msgs.slideShowDelete = 'Clique para deletar este Slide Show';
		if(!b2make.msgs.slideShowShow)b2make.msgs.slideShowShow = 'Clique para que o Slide Show mostrar/n&atilde;o mostrar este Slide Show no widget Slide Show';
		if(!b2make.msgs.slideShowDelTitle)b2make.msgs.slideShowDelTitle = 'Tem certeza que deseja excluir <b>#slide#</b>?';
		if(!b2make.msgs.slideShowEditTitle)b2make.msgs.slideShowEditTitle = 'Editar Nome do Slide Show';
		if(!b2make.msgs.slideShowAddTitle)b2make.msgs.slideShowAddTitle = 'Adicionar Slide Show';
		if(!b2make.msgs.slideShowDeleteX)b2make.msgs.slideShowDeleteX = 'Clique para excluir esta imagem';
		
		$('#b2make-slide-show-btn').hide();
		
		slide_show_upload();
		
		b2make.slide_show_confirm_delete = true;
		var id_func = 'slides-show';
		
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
							var slide_show,slide_selected;
							var slide_show_todos_ids = new Array();
							
							for(var i=0;i<dados.resultado.length;i++){
								slide_show = true;
								slide_selected = false;
								
								if(i==dados.resultado.length - 1){
									b2make.slide_show_atual = dados.resultado[i].id_site_slide_show;
									b2make.slide_foto_nome = dados.resultado[i].nome;
									slide_selected = true;
									slide_show_images();
									$('#b2make-slide-show-btn').show();
								}
								
								slide_show_menu_html({
									slide_selected:slide_selected,
									slide_show:slide_show,
									slide_show_id:dados.resultado[i].id_site_slide_show,
									slide_show_nome:dados.resultado[i].nome
								});
								
								if(!b2make.slide_show_todos_ids){
									slide_show_todos_ids.push(dados.resultado[i].id_site_slide_show);
								}
							}
							
							if(!b2make.slide_show_todos_ids){
								b2make.slide_show_todos_ids = slide_show_todos_ids;
							}
							
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
		
		$(document.body).on('mouseup tap','.b2make-slide-show-image-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var msg = b2make.msgs.imagemDelete;
			
			b2make.slide_show_imagens_delete_id = $(this).parent().myAttr('data-image-id');
			
			if(b2make.slide_show_confirm_delete){
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-slide-show-image-delete-yes',
					msg: msg
				});
			} else {
				slide_show_imagens_delete();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-image-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			slide_show_imagens_delete();
		});
		
		$('#b2make-slide-show-confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
			
			if($(this).find('input').prop("checked")){
				b2make.slide_show_confirm_delete = true;
			} else {
				b2make.slide_show_confirm_delete = false;
			}
		});
		
		$('#b2make-slide-show-confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).prop("checked")){
				b2make.slide_show_confirm_delete = false;
			} else {
				b2make.slide_show_confirm_delete = true;
			}
		});
		
		$('#b2make-slide-show-add').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			slide_show_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			slide_show_add_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-show',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-slide-show-id');
			var nome = $(this).parent().find('.b2make-slide-show-nome').html();
			
			if($(this).myAttr('data-status') == 'not-show'){
				$('.b2make-slide-show-show').each(function(){
					$(this).myAttr('data-status','not-show');
				});
				
				$(this).myAttr('data-status','show');
				
				slideshow_widget_create({
					slideshow_id: id,
					slideshow_nome: nome
				});
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-nome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$('.b2make-slide-show-nome').each(function(){
				$(this).myAttr('data-status','not-show');
			});
			
			$(this).myAttr('data-status','show');
			
			var id = $(this).myAttr('data-slide-show-id');
			
			b2make.slide_show_atual = $(this).myAttr('data-slide-show-id');
			b2make.slide_foto_nome = $(this).html();
			
			$('#b2make-slide-show-lista-images').html('');
			slide_show_images();
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-slide-show-id');
			slide_show_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			slide_show_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-slide-show-id');
			slide_show_del(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-del-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			slide_show_del_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-slide-show-image-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			$('.b2make-slide-show-image-holder').removeClass('b2make-slide-show-image-holder-clicked');
			$(this).addClass('b2make-slide-show-image-holder-clicked');
			
			b2make.slide_show_imagem_selected = $(this);
			slide_show_images_select();
		});
	}
	
	slide_show();
	
	function player_musicas_mp3s_select(){
		/* var obj_selected = b2make.player_musicas_mp3_selected;
		var obj_target = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
		var mp3_url = obj_selected.myAttr('data-mp3-url');
		var mp3_id = obj_selected.myAttr('data-mp3-id');
		
		$(obj_target).find('div.b2make-widget-out')
			.find('div.b2make-playermusicas-widget-holder')
			.find('div.b2make-playermusicas-widget-mp3[id="b2make-playermusicas-widget-mp3-'+b2make.player_musicas_atual+'"]')
			.css('backgroundImage','url('+mp3_url+')')
			.myAttr('data-player-musicas-mp3-id',mp3_id); */
			
	}
	
	function player_musicas_mp3s_html(dados){
		$('#b2make-player-musicas-lista-mp3s').append($('<div id="b2make-player-musicas-mp3-holder-'+dados.id+'" class="b2make-player-musicas-mp3-holder" data-mp3-id="'+dados.id+'" data-mp3-url="'+dados.mp3+'"><div class="b2make-player-musicas-mp3-delete b2make-tooltip" title="'+b2make.msgs.playerMusicasDeleteX+'"></div>'+dados.nome_original+'</div>'));
	}
	
	function player_musicas_mp3s(){
		var id_func = 'player-musicas-mp3s';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.player_musicas_atual
			},
			beforeSend: function(){
				$('#b2make-player-musicas-lista-mp3s').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-player-musicas-lista-mp3s');
			},
			success: function(txt){
				$('#b2make-player-musicas-lista-mp3s').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							for(var i=0;i<dados.mp3s.length;i++){
								player_musicas_mp3s_html(dados.mp3s[i]);
							}
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							
							if(b2make.player_musicas_widget_update){
								widgets_update({type:'player_musicas-del',id:b2make.player_musicas_widget_update_id});
								b2make.player_musicas_widget_update_id = false;
								b2make.player_musicas_widget_update = false;
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
				$('#b2make-player-musicas-lista-mp3s').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function player_musicas_mp3s_delete(){
		var id = b2make.player_musicas_mp3s_delete_id;
		var id_func = 'player-musicas-mp3s-del';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : id,
				player : b2make.player_musicas_atual
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
							var url = $('.b2make-player-musicas-mp3-holder[data-mp3-id="'+id+'"]').myAttr('data-mp3-url');
							
							$('.b2make-player-musicas-mp3-holder[data-mp3-id="'+id+'"]').remove();
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'player-musicas-mp3-del',id:id,id_player:b2make.player_musicas_atual,url:url});
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
	
	function player_musicas_menu_html(dados){
		if(!dados)dados = {};
		$('#b2make-player-musicas-lista-players').prepend($('<div class="b2make-player-musicas-lista-player"><div class="b2make-player-musicas-show b2make-tooltip" title="'+b2make.msgs.playerMusicasShow+'" data-status="'+(dados.player_musicas ? 'show' : 'not-show')+'" data-player-musicas-id="'+dados.player_musicas_id+'"></div><div class="b2make-player-musicas-nome b2make-tooltip" title="'+b2make.msgs.playerMusicasNome+'" data-status="'+(dados.player_selected ? 'show' : 'not-show')+'" data-player-musicas-id="'+dados.player_musicas_id+'">'+dados.player_musicas_nome+'</div><div class="b2make-player-musicas-edit b2make-tooltip" data-player-musicas-id="'+dados.player_musicas_id+'" title="'+b2make.msgs.playerMusicasEdit+'"></div><div class="b2make-player-musicas-delete b2make-tooltip" data-player-musicas-id="'+dados.player_musicas_id+'" title="'+b2make.msgs.playerMusicasDelete+'"></div></div>'));
	}
	
	function player_musicas_add(){
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-player-musicas-add-calback',
			title: b2make.msgs.playerMusicasAddTitle,
			coneiner: 'b2make-formulario-player-musicas'
		});
	}
	
	function player_musicas_add_base(){
		var id_func = 'player-musicas-add';
		var form_id = 'b2make-formulario-player-musicas';
		
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
								$('.b2make-player-musicas-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								var obj = b2make.conteiner_child_obj;
								
								if(!$(obj).myAttr('data-player-musicas-id')){
									dados.player_musicas = true;
								}
								
								dados.player_selected = true;
								player_musicas_menu_html(dados);
								$('.b2make-tooltip').tooltip({
									show: {
										effect: "fade",
										delay: 400
									}
								});
								$.dialogbox_close();
								
								b2make.player_musicas_atual = dados.player_musicas_id;
								b2make.player_musicas_nome = dados.player_musicas_nome;
								
								$('#b2make-player-musicas-btn').show();
								$('#b2make-player-musicas-lista-mp3s').html('');
								
								var obj = b2make.conteiner_child_obj;
								
								if(!$(obj).myAttr('data-player-musicas-id')){
									$(obj).myAttr('data-player-musicas-id',dados.player_musicas_id)
									
									player_widget_create({player_id:dados.player_musicas_id});
								}
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
	
	function player_musicas_edit(id){
		$('#b2make-formulario-player-musicas #b2make-fpm-nome').val($('.b2make-player-musicas-nome[data-player-musicas-id="'+id+'"]').html());
		
		b2make.player_musicas_edit_id = id;
		
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-player-musicas-edit-calback',
			title: b2make.msgs.playerMusicasEditTitle,
			coneiner: 'b2make-formulario-player-musicas'
		});
	}
	
	function player_musicas_edit_base(){
		var id_func = 'player-musicas-edit';
		var form_id = 'b2make-formulario-player-musicas';
		var id = b2make.player_musicas_edit_id;
		
		b2make.player_musicas_edit_id = false;
		
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
								$('.b2make-player-musicas-nome[data-player-musicas-id="'+id+'"]').html(dados.nome);
								
								widgets_update({type:'player-musicas-edit',id:id,nome:dados.nome});
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
	
	function player_musicas_del(id){
		b2make.player_musicas_del_id = id;
		
		var msg = b2make.msgs.playerMusicasDelTitle;
		msg = msg.replace(/#player#/gi,$('.b2make-player-musicas-nome[data-player-musicas-id="'+id+'"]').html());
		
		$.dialogbox_open({
			confirm:true,
			calback_yes: 'b2make-player-musicas-del-calback',
			msg: msg
		});
	}
	
	function player_musicas_del_base(){
		var id_func = 'player-musicas-del';
		var id = b2make.player_musicas_del_id;
		
		b2make.player_musicas_del_id = false;
	
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
							$('.b2make-player-musicas-delete[data-player-musicas-id="'+id+'"]').parent().remove();
							$.dialogbox_close();
							
							var id_aux = $('#b2make-player-musicas-lista-players .b2make-player-musicas-lista-player:first-child .b2make-player-musicas-show').myAttr('data-player-musicas-id');
							
							$('#b2make-player-musicas-lista-mp3s').html('');
							
							if(id_aux){
								b2make.player_musicas_atual = id_aux;
								b2make.player_musicas_nome = $('.b2make-player-musicas-nome[data-player-musicas-id="'+id_aux+'"]').html();
								
								$('.b2make-player-musicas-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								$('.b2make-player-musicas-nome[data-player-musicas-id="'+id_aux+'"]').myAttr('data-status','show');
								
								player_musicas_mp3s();
								$('#b2make-player-musicas-btn').show();
							} else {
								$('#b2make-player-musicas-btn').hide();
							}
							
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'player-musicas-delete',id:id});
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
	
	function player_musicas_upload_params(){
		return new Array({
			variavel : 'player',
			valor : b2make.player_musicas_atual,
		})
	}
	
	function player_musicas_upload_callback(dados){
		var id_func = 'player-musicas';
		
		switch(dados.status){
			case 'Ok':
				player_musicas_mp3s_html(dados);
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				$.disk_usage_diskused_add(dados.size);
				widgets_update({type:'player-musicas-mp3-uploaded',id:b2make.player_musicas_atual,dados:dados});
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function player_musicas_upload(){
		$.upload_files_start({
			url_php : 'uploadplayermusicas.php',
			input_selector : '#b2make-player-musicas-input',
			file_type : 'audio',
			post_params : player_musicas_upload_params,
			callback : player_musicas_upload_callback
		});
	}
	
	function player_musicas(){
		if(!b2make.msgs.playerMusicasDeleteX)b2make.msgs.playerMusicasDeleteX = 'Clique para excluir esta mp3';
		if(!b2make.msgs.playerMusicasEdit)b2make.msgs.playerMusicasEdit = 'Clique para Editar o Nome deste Player de M&uacute;sicas';
		if(!b2make.msgs.playerMusicasNome)b2make.msgs.playerMusicasNome = 'Clique para alterar as fotos deste Player de M&uacute;sicas';
		if(!b2make.msgs.playerMusicasDelete)b2make.msgs.playerMusicasDelete = 'Clique para deletar este Player de M&uacute;sicas';
		if(!b2make.msgs.playerMusicasShow)b2make.msgs.playerMusicasShow = 'Clique para que o Player de M&uacute;sicas mostrar/n&atilde;o mostrar este Player de M&uacute;sicas no widget Player de M&uacute;sicas';
		if(!b2make.msgs.playerMusicasDelTitle)b2make.msgs.playerMusicasDelTitle = 'Tem certeza que deseja excluir <b>#player#</b>?';
		if(!b2make.msgs.playerMusicasEditTitle)b2make.msgs.playerMusicasEditTitle = 'Editar Nome do Player de M&uacute;sicas';
		if(!b2make.msgs.playerMusicasAddTitle)b2make.msgs.playerMusicasAddTitle = 'Adicionar Player de M&uacute;sicas';
		if(!b2make.msgs.playerMusicasDeleteX)b2make.msgs.playerMusicasDeleteX = 'Clique para excluir esta mp3';
		
		$('#b2make-player-musicas-btn').hide();
		
		player_musicas_upload();
		
		b2make.player_musicas_confirm_delete = true;
		var id_func = 'players-musicas';
		
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
							var player_musicas,player_selected;
							
							for(var i=0;i<dados.resultado.length;i++){
								player_musicas = true;
								player_selected = false;
								
								if(i==dados.resultado.length - 1){
									b2make.player_musicas_atual = dados.resultado[i].id_site_player_musicas;
									b2make.player_musicas_nome = dados.resultado[i].nome;
									player_selected = true;
									player_musicas_mp3s();
									$('#b2make-player-musicas-btn').show();
								}
								
								player_musicas_menu_html({
									player_selected:player_selected,
									player_musicas:player_musicas,
									player_musicas_id:dados.resultado[i].id_site_player_musicas,
									player_musicas_nome:dados.resultado[i].nome
								});
							}
							
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
		
		$('#b2make-widget-player-auto-start').on('options',function(e){
			var obj = b2make.conteiner_child_obj;
			
			if($(this).myAttr('data-checked')){
				$(obj).myAttr('data-start-automatico',true);
			} else {
				$(obj).myAttr('data-start-automatico',null);
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-mp3-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var msg = b2make.msgs.mp3Delete;
			
			b2make.player_musicas_mp3s_delete_id = $(this).parent().myAttr('data-mp3-id');
			
			if(b2make.player_musicas_confirm_delete){
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-player-musicas-mp3-delete-yes',
					msg: msg
				});
			} else {
				player_musicas_mp3s_delete();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-mp3-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			player_musicas_mp3s_delete();
		});
		
		$('#b2make-player-musicas-confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
			
			if($(this).find('input').prop("checked")){
				b2make.player_musicas_confirm_delete = true;
			} else {
				b2make.player_musicas_confirm_delete = false;
			}
		});
		
		$('#b2make-player-musicas-confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).prop("checked")){
				b2make.player_musicas_confirm_delete = false;
			} else {
				b2make.player_musicas_confirm_delete = true;
			}
		});
		
		$('#b2make-player-musicas-add').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			player_musicas_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			player_musicas_add_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-show',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-player-musicas-id');
			var nome = $(this).parent().find('.b2make-player-musicas-nome').html();
			
			if($(this).myAttr('data-status') == 'not-show'){
				$('.b2make-player-musicas-show').each(function(){
					$(this).myAttr('data-status','not-show');
				});
				
				$(this).myAttr('data-status','show');
				
				player_widget_create({player_id:id});
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-nome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$('.b2make-player-musicas-nome').each(function(){
				$(this).myAttr('data-status','not-show');
			});
			
			$(this).myAttr('data-status','show');
			
			var id = $(this).myAttr('data-player-musicas-id');
			
			b2make.player_musicas_atual = $(this).myAttr('data-player-musicas-id');
			b2make.player_musicas_nome = $(this).html();
			
			$('#b2make-player-musicas-lista-mp3s').html('');
			player_musicas_mp3s();
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-player-musicas-id');
			player_musicas_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			player_musicas_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-player-musicas-id');
			player_musicas_del(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-del-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			player_musicas_del_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-player-musicas-mp3-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			$('.b2make-player-musicas-mp3-holder').removeClass('b2make-player-musicas-mp3-holder-clicked');
			$(this).addClass('b2make-player-musicas-mp3-holder-clicked');
			
			b2make.player_musicas_mp3_selected = $(this);
			player_musicas_mp3s_select();
		});
	}
	
	player_musicas();
	
	function album_musicas_mp3s_select(){
		/* var obj_selected = b2make.album_musicas_mp3_selected;
		var obj_target = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
		var mp3_url = obj_selected.myAttr('data-mp3-url');
		var mp3_id = obj_selected.myAttr('data-mp3-id');
		
		$(obj_target).find('div.b2make-widget-out')
			.find('div.b2make-albummusicas-widget-holder')
			.find('div.b2make-albummusicas-widget-mp3[id="b2make-albummusicas-widget-mp3-'+b2make.album_musicas_atual+'"]')
			.css('backgroundImage','url('+mp3_url+')')
			.myAttr('data-album-musicas-mp3-id',mp3_id); */
			
	}
	
	function album_musicas_mp3s_html(dados){
		$('#b2make-album-musicas-lista-mp3s').append($('<div id="b2make-album-musicas-mp3-holder-'+dados.id+'" class="b2make-album-musicas-mp3-holder" data-mp3-id="'+dados.id+'" data-mp3-url="'+dados.mp3+'"><div class="b2make-album-musicas-mp3-delete b2make-tooltip" title="'+b2make.msgs.albumMusicasDeleteX+'"></div>'+dados.nome_original+'</div>'));
	}
	
	function album_musicas_mp3s(){
		var id_func = 'albuns-musicas-mp3s';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.album_musicas_atual
			},
			beforeSend: function(){
				$('#b2make-album-musicas-lista-mp3s').find('.b2make-loading-box').remove();
				$('<div class="b2make-loading-box"></div>').appendTo('#b2make-album-musicas-lista-mp3s');
			},
			success: function(txt){
				$('#b2make-album-musicas-lista-mp3s').find('.b2make-loading-box').remove();
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							for(var i=0;i<dados.mp3s.length;i++){
								album_musicas_mp3s_html(dados.mp3s[i]);
							}
							
							$('.b2make-tooltip').tooltip({
								show: {
									effect: "fade",
									delay: 400
								}
							});
							
							if(b2make.album_musicas_widget_update){
								widgets_update({type:'album_musicas-del',id:b2make.album_musicas_widget_update_id});
								b2make.album_musicas_widget_update_id = false;
								b2make.album_musicas_widget_update = false;
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
				$('#b2make-album-musicas-lista-mp3s').find('.b2make-loading-box').remove();
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function album_musicas_mp3s_delete(){
		var id = b2make.album_musicas_mp3s_delete_id;
		var id_func = 'album-musicas-mp3s-del';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : id,
				album : b2make.album_musicas_atual
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
							var url = $('.b2make-album-musicas-mp3-holder[data-mp3-id="'+id+'"]').myAttr('data-mp3-url');
							
							$('.b2make-album-musicas-mp3-holder[data-mp3-id="'+id+'"]').remove();
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'album-musicas-mp3-del',id:id,id_album:b2make.album_musicas_atual,url:url});
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
	
	function album_musicas_menu_html(dados){
		if(!dados)dados = {};
		$('#b2make-album-musicas-lista-albuns').prepend($('<div class="b2make-album-musicas-lista-album"><div class="b2make-album-musicas-show b2make-tooltip" title="'+b2make.msgs.albumMusicasShow+'" data-status="'+(dados.album_musicas ? 'show' : 'not-show')+'" data-album-musicas-id="'+dados.album_musicas_id+'"></div><div class="b2make-album-musicas-nome b2make-tooltip" title="'+b2make.msgs.albumMusicasNome+'" data-status="'+(dados.album_selected ? 'show' : 'not-show')+'" data-album-musicas-id="'+dados.album_musicas_id+'">'+dados.album_musicas_nome+'</div><div class="b2make-album-musicas-edit b2make-tooltip" data-album-musicas-id="'+dados.album_musicas_id+'" title="'+b2make.msgs.albumMusicasEdit+'"></div><div class="b2make-album-musicas-delete b2make-tooltip" data-album-musicas-id="'+dados.album_musicas_id+'" title="'+b2make.msgs.albumMusicasDelete+'"></div></div>'));
	}
	
	function album_musicas_add(){
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-album-musicas-add-calback',
			title: b2make.msgs.albumMusicasAddTitle,
			coneiner: 'b2make-formulario-album-musicas'
		});
	}
	
	function album_musicas_add_base(){
		var id_func = 'album-musicas-add';
		var form_id = 'b2make-formulario-album-musicas';
		
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
								$('.b2make-album-musicas-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								var obj = b2make.conteiner_child_obj;
								
								if(!$(obj).myAttr('data-album-musicas-id')){
									dados.album_musicas = true;
								}
								
								dados.album_selected = true;
								album_musicas_menu_html(dados);
								$('.b2make-tooltip').tooltip({
									show: {
										effect: "fade",
										delay: 400
									}
								});
								$.dialogbox_close();
								
								b2make.album_musicas_atual = dados.album_musicas_id;
								b2make.album_musicas_nome = dados.album_musicas_nome;
								
								$('#b2make-album-musicas-btn').show();
								$('#b2make-album-musicas-lista-mp3s').html('');
								
								var obj = b2make.conteiner_child_obj;
								
								albummusicas_widget_album_add({
									albummusicas_id: dados.album_musicas_id,
									albummusicas_nome: dados.album_musicas_nome
								});
								
								if(!b2make.album_musicas_todos_ids) b2make.album_musicas_todos_ids = new Array();
								
								b2make.album_musicas_todos_ids.push(dados.album_musicas_id);
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
	
	function album_musicas_edit(id){
		$('#b2make-formulario-album-musicas #b2make-fam-nome').val($('.b2make-album-musicas-nome[data-album-musicas-id="'+id+'"]').html());
		
		b2make.album_musicas_edit_id = id;
		
		$.dialogbox_open({
			width:350,
			height:200,
			message:true,
			calback_yes: 'b2make-album-musicas-edit-calback',
			title: b2make.msgs.albumMusicasEditTitle,
			coneiner: 'b2make-formulario-album-musicas'
		});
	}
	
	function album_musicas_edit_base(){
		var id_func = 'album-musicas-edit';
		var form_id = 'b2make-formulario-album-musicas';
		var id = b2make.album_musicas_edit_id;
		
		b2make.album_musicas_edit_id = false;
		
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
								$('.b2make-album-musicas-nome[data-album-musicas-id="'+id+'"]').html(dados.nome);
								
								widgets_update({type:'album-musicas-edit',id:id,nome:dados.nome});
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
	
	function album_musicas_del(id){
		b2make.album_musicas_del_id = id;
		
		var msg = b2make.msgs.albumMusicasDelTitle;
		msg = msg.replace(/#album#/gi,$('.b2make-album-musicas-nome[data-album-musicas-id="'+id+'"]').html());
		
		$.dialogbox_open({
			confirm:true,
			calback_yes: 'b2make-album-musicas-del-calback',
			msg: msg
		});
	}
	
	function album_musicas_del_base(){
		var id_func = 'album-musicas-del';
		var id = b2make.album_musicas_del_id;
		
		b2make.album_musicas_del_id = false;
	
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
							$('.b2make-album-musicas-delete[data-album-musicas-id="'+id+'"]').parent().remove();
							$.dialogbox_close();
							
							var id_aux = $('#b2make-album-musicas-lista-albuns .b2make-album-musicas-lista-album:first-child .b2make-album-musicas-show').myAttr('data-album-musicas-id');
							
							$('#b2make-album-musicas-lista-mp3s').html('');
							
							if(id_aux){
								b2make.album_musicas_atual = id_aux;
								b2make.album_musicas_nome = $('.b2make-album-musicas-nome[data-album-musicas-id="'+id_aux+'"]').html();
								
								$('.b2make-album-musicas-nome').each(function(){
									$(this).myAttr('data-status','not-show');
								});
								
								$('.b2make-album-musicas-nome[data-album-musicas-id="'+id_aux+'"]').myAttr('data-status','show');
								
								album_musicas_mp3s();
								$('#b2make-album-musicas-btn').show();
							} else {
								$('#b2make-album-musicas-btn').hide();
							}
							
							$.disk_usage_diskused_del(dados.size);
							widgets_update({type:'album-musicas-delete',id:id});
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
	
	function album_musicas_upload_params(){
		return new Array({
			variavel : 'album',
			valor : b2make.album_musicas_atual,
		})
	}
	
	function album_musicas_upload_callback(dados){
		var id_func = 'albuns-musicas';
		
		switch(dados.status){
			case 'Ok':
				album_musicas_mp3s_html(dados);
				
				$('.b2make-tooltip').tooltip({
					show: {
						effect: "fade",
						delay: 400
					}
				});
				
				$.disk_usage_diskused_add(dados.size);
				widgets_update({type:'album-musicas-mp3-uploaded',id:b2make.album_musicas_atual,dados:dados});
			break;
			case 'SemPermissao':
				sem_permissao_redirect();
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function album_musicas_upload(){
		$.upload_files_start({
			url_php : 'uploadalbummusicas.php',
			input_selector : '#b2make-album-musicas-input',
			file_type : 'audio',
			post_params : album_musicas_upload_params,
			callback : album_musicas_upload_callback
		});
	}
	
	function album_musicas(){
		if(!b2make.msgs.albumMusicasDeleteX)b2make.msgs.albumMusicasDeleteX = 'Clique para excluir esta mp3';
		if(!b2make.msgs.albumMusicasEdit)b2make.msgs.albumMusicasEdit = 'Clique para Editar o Nome deste &Aacute;lbum de M&uacute;sicas';
		if(!b2make.msgs.albumMusicasNome)b2make.msgs.albumMusicasNome = 'Clique para alterar as fotos deste &Aacute;lbum de M&uacute;sicas';
		if(!b2make.msgs.albumMusicasDelete)b2make.msgs.albumMusicasDelete = 'Clique para deletar este &Aacute;lbum de M&uacute;sicas';
		if(!b2make.msgs.albumMusicasShow)b2make.msgs.albumMusicasShow = 'Clique para que o &Aacute;lbum de M&uacute;sicas mostrar/n&atilde;o mostrar este &Aacute;lbum de M&uacute;sicas no widget &Aacute;lbum de M&uacute;sicas';
		if(!b2make.msgs.albumMusicasDelTitle)b2make.msgs.albumMusicasDelTitle = 'Tem certeza que deseja excluir <b>#album#</b>?';
		if(!b2make.msgs.albumMusicasEditTitle)b2make.msgs.albumMusicasEditTitle = 'Editar Nome do &Aacute;lbum de M&uacute;sicas';
		if(!b2make.msgs.albumMusicasAddTitle)b2make.msgs.albumMusicasAddTitle = 'Adicionar &Aacute;lbum de M&uacute;sicas';
		if(!b2make.msgs.albumMusicasDeleteX)b2make.msgs.albumMusicasDeleteX = 'Clique para excluir esta mp3';
		
		$('#b2make-album-musicas-btn').hide();
		
		album_musicas_upload();
		
		b2make.album_musicas_confirm_delete = true;
		var id_func = 'albuns-musicas';
		
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
							var album_musicas,album_selected;
							var album_musicas_todos_ids = new Array();
							
							for(var i=0;i<dados.resultado.length;i++){
								album_musicas = true;
								album_selected = false;
								
								if(i==dados.resultado.length - 1){
									b2make.album_musicas_atual = dados.resultado[i].id_site_album_musicas;
									b2make.album_musicas_nome = dados.resultado[i].nome;
									album_selected = true;
									album_musicas_mp3s();
									$('#b2make-album-musicas-btn').show();
								}
								
								album_musicas_menu_html({
									album_selected:album_selected,
									album_musicas:album_musicas,
									album_musicas_id:dados.resultado[i].id_site_album_musicas,
									album_musicas_nome:dados.resultado[i].nome
								});
								
								if(!b2make.album_musicas_todos_ids){
									album_musicas_todos_ids.push(dados.resultado[i].id_site_album_musicas);
								}
							}
							
							if(!b2make.album_musicas_todos_ids){
								b2make.album_musicas_todos_ids = album_musicas_todos_ids;
							}
							
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
		
		$('#b2make-widget-album-auto-start').on('options',function(e){
			var obj = b2make.conteiner_child_obj;
			
			if($(this).myAttr('data-checked')){
				$(obj).myAttr('data-start-automatico',true);
			} else {
				$(obj).myAttr('data-start-automatico',null);
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-mp3-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			var msg = b2make.msgs.mp3Delete;
			
			b2make.album_musicas_mp3s_delete_id = $(this).parent().myAttr('data-mp3-id');
			
			if(b2make.album_musicas_confirm_delete){
				$.dialogbox_open({
					confirm:true,
					calback_yes: 'b2make-album-musicas-mp3-delete-yes',
					msg: msg
				});
			} else {
				album_musicas_mp3s_delete();
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-mp3-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_musicas_mp3s_delete();
		});
		
		$('#b2make-album-musicas-confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
			
			if($(this).find('input').prop("checked")){
				b2make.album_musicas_confirm_delete = true;
			} else {
				b2make.album_musicas_confirm_delete = false;
			}
		});
		
		$('#b2make-album-musicas-confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).prop("checked")){
				b2make.album_musicas_confirm_delete = false;
			} else {
				b2make.album_musicas_confirm_delete = true;
			}
		});
		
		$('#b2make-album-musicas-add').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_musicas_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_musicas_add_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-show',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-album-musicas-id');
			var nome = $(this).parent().find('.b2make-album-musicas-nome').html();
			var obj = b2make.conteiner_child_obj;
			var albuns_not_show = $(obj).myAttr('data-albuns-not-show');
			
			if($(this).myAttr('data-status') == 'show'){
				$(this).myAttr('data-status','not-show');
				$(obj)
					.find('.b2make-widget-out')
					.find('.b2make-albummusicas-widget-holder')
					.find('.b2make-albummusicas-widget-album[data-album-musicas-id="'+id+'"]')
					.remove();
				
				if(albuns_not_show){
					$(obj).myAttr('data-albuns-not-show',albuns_not_show+','+id);
				} else {
					$(obj).myAttr('data-albuns-not-show',id);
				}
			} else {
				$(this).myAttr('data-status','show');
				albummusicas_widget_album_add({
					albummusicas_id: id,
					albummusicas_nome: nome
				});
				
				if(albuns_not_show){
					var ans_arr = albuns_not_show.split(',');
					var ans_final = '';
					
					for(var i=0;i<ans_arr.length;i++){
						if(ans_arr[i] != id){
							ans_final = ans_final + (ans_final.length > 0 ? ',' : '') + ans_arr[i];
						}
					}
					$(obj).myAttr('data-albuns-not-show',(ans_final.length > 0 ? ans_final : null));
				}
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-nome',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$('.b2make-album-musicas-nome').each(function(){
				$(this).myAttr('data-status','not-show');
			});
			
			$(this).myAttr('data-status','show');
			
			var id = $(this).myAttr('data-album-musicas-id');
			
			b2make.album_musicas_atual = $(this).myAttr('data-album-musicas-id');
			b2make.album_musicas_nome = $(this).html();
			
			$('#b2make-album-musicas-lista-mp3s').html('');
			album_musicas_mp3s();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-edit',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-album-musicas-id');
			album_musicas_edit(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_musicas_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-delete',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-album-musicas-id');
			album_musicas_del(id);
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-del-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			album_musicas_del_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-album-musicas-mp3-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			$('.b2make-album-musicas-mp3-holder').removeClass('b2make-album-musicas-mp3-holder-clicked');
			$(this).addClass('b2make-album-musicas-mp3-holder-clicked');
			
			b2make.album_musicas_mp3_selected = $(this);
			album_musicas_mp3s_select();
		});
	}
	
	album_musicas();
	
	function agenda_open(agenda_id){
		for(var i=0;i<b2make.agendas.length;i++){
			if(agenda_id == b2make.agendas[i].id){
				$('#b2make-wsoa-value').val(b2make.agendas[i].nome);
				b2make.agenda_atual = b2make.agendas[i].id;
				agenda_eventos();
			}
		}
	}
	
	function agenda_delete(){
		var id_func = 'agenda-delete';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.agenda_atual
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
							$('#b2make-wsoa-select option[value="'+b2make.agenda_atual+'"]').remove();
							$('#b2make-woa-select option[value="'+b2make.agenda_atual+'"]').remove();
							
							var agendas = new Array();
							
							for(var i=0;i<b2make.agendas.length;i++){
								if(b2make.agendas[i].id != b2make.agenda_atual){
									agendas.push(b2make.agendas[i]);
								}
							}
							
							b2make.agendas = agendas;
							
							if(b2make.agendas.length > 0){
								$('#b2make-wsoa-value').val(b2make.agendas[0].nome);
								b2make.agenda_atual = b2make.agendas[0].id;
								agenda_eventos();
							} else {
								agenda_create();
							}
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
	
	function agenda_name(){
		var id_func = 'agenda-name';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.agenda_atual,
				name : $('#b2make-wsoa-value').val()
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
							$('#b2make-wsoa-select option[value="'+b2make.agenda_atual+'"]').html($('#b2make-wsoa-value').val());
							$('#b2make-woa-select option[value="'+b2make.agenda_atual+'"]').html($('#b2make-wsoa-value').val());
							for(var i=0;i<b2make.agendas.length;i++){
								if(b2make.agenda_atual == b2make.agendas[i].id){
									b2make.agendas[i].nome = $('#b2make-wsoa-value').val();
								}
							}
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
	
	function agenda_eventos(){
		var id_func = 'agenda-eventos';
		
		if(b2make.eventos_reset){
			lista_reset('b2make-wsoa-lista');
			b2make.eventos_reset = true;
		}
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : id_func,
				id : b2make.agenda_atual
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
							if(dados.eventos.length > 0){
								for(var i=0;i<dados.eventos.length;i++){
									$.lista_add_linha({
										lista_id : 'b2make-wsoa-lista',
										data_id : dados.eventos[i].id,
										status : dados.eventos[i].status,
										fields : {
											nome_original : dados.eventos[i].nome_original,
											descricao : dados.eventos[i].descricao,
											data : dados.eventos[i].data,
											hora : dados.eventos[i].hora
										}
									});
								}
								
								if(b2make.eventos_open){
									$('.b2make-eventos-holder[data-eventos-id="'+b2make.eventos_open.eventos_id+'"]').addClass('b2make-eventos-holder-clicked');
									b2make.eventos_open = false;
								}
							} else {
								
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
				console.log('ERROR AJAX - '+id_func+' - '+txt);
			}
		});
	}
	
	function eventos_add(){
		$.dialogbox_open({
			width:500,
			height:370,
			message:true,
			calback_yes: 'b2make-eventos-add-calback',
			title: b2make.msgs.eventosAddTitle,
			coneiner: 'b2make-formulario-eventos'
		});
	}
	
	function eventos_add_base(){
		var id_func = 'eventos-add';
		var form_id = 'b2make-formulario-eventos';
		
		if($.formulario_testar(form_id)){
			var ajaxData = { 
				ajax : 'sim',
				opcao : id_func,
				agenda : b2make.agenda_atual
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
								$.lista_add_linha({
									lista_id : 'b2make-wsoa-lista',
									data_id : dados.id,
									fields : {
										nome_original : $('#b2make-fe-titulo').val(),
										descricao : $('#b2make-fe-descricao').val(),
										data : $('#b2make-fe-data').val(),
										hora : $('#b2make-fe-hora').val()
									}
								});
								$.dialogbox_close();
								widgets_update({type:'eventos'});
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
	
	function eventos_edit(id){
		$.formulario_edit({
			form_id : 'b2make-formulario-eventos',
			data_id : id
		});
		
		b2make.id_site_eventos = id;
		
		$.dialogbox_open({
			width:500,
			height:370,
			message:true,
			calback_yes: 'b2make-eventos-edit-calback',
			title: b2make.msgs.eventosEditTitle,
			coneiner: 'b2make-formulario-eventos'
		});
	}
	
	function eventos_edit_base(){
		var id_func = 'eventos-edit';
		var form_id = 'b2make-formulario-eventos';
		var data_id = b2make.id_site_eventos;
		
		b2make.id_site_eventos = false;
		
		if($.formulario_testar(form_id)){
			var ajaxData = { 
				ajax : 'sim',
				opcao : id_func,
				agenda : b2make.agenda_atual,
				id_site_eventos : data_id
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
								lista_editar_linha({
									form_id : form_id,
									data_id : data_id,
								});
								$.dialogbox_close();
								widgets_update({type:'eventos'});
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
	
	function agenda_create(){
		var id_func = 'agenda-add';
		
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
							$('#b2make-wsoa-value').val(dados.agenda_nome);
							$('<option value="'+dados.agenda_id+'">'+dados.agenda_nome+'</option>').appendTo($('#b2make-wsoa-select'));
							$('<option value="'+dados.agenda_id+'">'+dados.agenda_nome+'</option>').appendTo($('#b2make-woa-select'));
							
							b2make.agendas.push({
								'id' : dados.agenda_id,
								'nome' : dados.agenda_nome
							});
							
							b2make.agenda_atual = dados.agenda_id;
							agenda_eventos();
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
	
	function agenda_update_modelo(){
		$(b2make.widget).each(function(){
			switch($(this).myAttr('data-type')){
				case 'agenda':
					if(b2make.agenda_atual != $(this).myAttr('data-agenda-id')){
						$(this).myAttr('data-agenda-id',b2make.agenda_atual);
					}
				break;
			}
		});
	}
	
	function agenda(){
		var id_func = 'agendas';
		var sep = '../';
		
		b2make.agendas = new Array();
		if(!b2make.msgs.agendaDeleteX)b2make.msgs.agendaDeleteX = 'Clique para excluir este evento';
		if(!b2make.msgs.agendaDelete)b2make.msgs.agendaDelete = 'Tem certeza que deseja realmente excluir <b>#name#</b>?';
		if(!b2make.msgs.eventosDelete)b2make.msgs.eventosDelete = 'Tem certeza que deseja realmente excluir este evento?';
		if(!b2make.msgs.eventosAddTitle)b2make.msgs.eventosAddTitle = 'Adicionar Evento';
		if(!b2make.msgs.eventosEditTitle)b2make.msgs.eventosEditTitle = 'Editar Evento';
		
		b2make.eventos_confirm_delete = true;
		b2make.eventos_count = 0;
		
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
							for(var i=0;i<dados.resultado.length;i++){
								if(i==0){
									$('#b2make-wsoa-value').val(dados.resultado[0].nome);
									b2make.agenda_atual = dados.resultado[0].id_site_agenda;
									agenda_eventos();
								}
								
								$('<option value="'+dados.resultado[i].id_site_agenda+'">'+dados.resultado[i].nome+'</option>').appendTo($('#b2make-wsoa-select'));
								$('<option value="'+dados.resultado[i].id_site_agenda+'">'+dados.resultado[i].nome+'</option>').appendTo($('#b2make-woa-select'));
								
								b2make.agendas.push({
									'id' : dados.resultado[i].id_site_agenda,
									'nome' : dados.resultado[i].nome
								});
								
								agenda_update_modelo();
							}
						break;
						case 'Vazio':
							agenda_create();
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
		
		$('#b2make-wsoa-new').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			agenda_create();
		});

		$('#b2make-wsoa-value').on('blur',function(e){
			agenda_name();
		});
		
		$('#b2make-wsoa-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var msg = b2make.msgs.agendaDelete;
			msg = msg.replace(/#name#/gi,$('#b2make-wsoa-value').val());
			
			$.dialogbox_open({
				confirm:true,
				calback_yes: 'b2make-wsoa-delete-yes',
				msg: msg
			});
		});
		
		$(document.body).on('mouseup tap','.b2make-wsoa-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			agenda_delete();
		});
		
		$('#b2make-wsoa-select').on('change',function(e){
			if($(this).val() != '0'){
				agenda_open($(this).val());
			}
			$(this).prop('selectedIndex',0);
		});
		
		$('#b2make-woa-select').on('change',function(e){
			if($(this).val() != '0'){
				agenda_widget_create({agenda_id:$(this).val()});
			}
			$(this).prop('selectedIndex',0);
		});
		
		$('#b2make-woa-new').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = 'agenda';
			b2make.widget_add_sub_options_open = true;
			$.widget_sub_options_open();
			agenda_create();
		});
		
		$('#b2make-woa-edit').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = 'agenda';
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open();
		});
		
		$('#b2make-wsoa-eventos-new').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			eventos_add();
		});
		
		$(document.body).on('mouseup tap','.b2make-eventos-add-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			eventos_add_base();
		});
		
		$('#b2make-wsoa-lista').on('edit',function(event,params){
			eventos_edit(params.id);
		});
		
		$('#b2make-wsoa-lista').on('block',function(event,params){
			widgets_update({type:'eventos'});
		});
		
		$('#b2make-wsoa-lista').on('del',function(event,params){
			widgets_update({type:'eventos'});
		});
		
		$(document.body).on('mouseup tap','.b2make-eventos-edit-calback',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			eventos_edit_base();
		});
		
		$(document.body).on('mouseup tap','.b2make-wsoae-prev',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			var obj = b2make.conteiner_child_obj;
			var holder = $(obj).find('div.b2make-widget-out').find('div.b2make-eventos-widget-holder');
			var img = holder.find('div.b2make-widget-eventos:last-child');
			
			img.prependTo(holder);
		});
		
		$(document.body).on('mouseup tap','.b2make-wsoae-next',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			var obj = b2make.conteiner_child_obj;
			var holder = $(obj).find('div.b2make-widget-out').find('div.b2make-eventos-widget-holder');
			var img = holder.find('div.b2make-widget-eventos:first-child');
			
			img.appendTo(holder);
		});
	}
	
	agenda();
	
	$.lista_add_linha = function(params){
		if(!params)params = {};
		var obj = '#'+params.lista_id;
		
		var data_id = params.data_id;
		var data_fields = $(obj).myAttr('data-fields').split(';');
		var data_field_id = $(obj).myAttr('data-field-id');
		var data_options = $(obj).myAttr('data-options').split(',');
		var fields;
		var coluna;
		var options = '';
		var status = (params.status ? params.status : 'A');
		var option;
		
		var linha = $('<div class="b2make-lista-linha"></div>');
		
		if(data_id) linha.myAttr('data-id',data_id);
		
		for(var i=0;i<data_options.length;i++){
			option = '';
			switch(data_options[i]){
				case 'del':
					option = '<div class="b2make-lista-option b2make-lista-option-del" data-option="del"'+(data_id ? ' data-id="'+data_id+'"' : '')+' title="'+b2make.msgs.listaOptionDelTitle+'"></div>';
				break;
				case 'edit':
					option = '<div class="b2make-lista-option b2make-lista-option-edit" data-option="edit"'+(data_id ? ' data-id="'+data_id+'"' : '')+' title="'+b2make.msgs.listaOptionEditTitle+'"></div>';
				break;
				case 'block':
					option = '<div class="b2make-lista-option b2make-lista-option-block" data-option="block" data-status="'+status+'"'+(data_id ? ' data-id="'+data_id+'"' : '')+' title="'+b2make.msgs.listaOptionBlockTitle+'"></div>';
				break;
			}
			
			options = options + option;
		}
		
		coluna = $('<div class="b2make-lista-coluna b2make-lista-options" data-options="true" style="text-align:center;">'+options+'</div>');
		coluna.appendTo(linha);
		
		for(i=0;i<data_fields.length;i++){
			fields = data_fields[i].split(',');
			
			coluna = $('<div class="b2make-lista-coluna'+(fields[2]?' '+fields[2]:'')+'" data-field="'+fields[0]+'" style="width:'+fields[3]+'px;text-align:'+fields[4]+';">'+params.fields[fields[0]]+'</div>');
			coluna.appendTo(linha);
		}
		
		$(obj).find('.b2make-lista-cabecalho').after(linha);
		
		$('.b2make-lista-option').tooltip({
			show: {
				effect: "fade",
				delay: 400
			}
		});
	}
	
	function lista_editar_linha(params){
		if(!params)params = {};
		var obj = '#'+params.lista_id;
		
		$('form#'+params.form_id+' input').each(function(){
			var obj_pai = $(this);
			
			$('.b2make-lista-linha[data-id="'+params.data_id+'"] .b2make-lista-coluna').each(function(){
				if(!$(this).hasClass('b2make-lista-options')){
					if($(this).myAttr('data-field') == obj_pai.myAttr('name')){
						$(this).html(obj_pai.val());
						return false;
					}
				}
			})
		});
	}
	
	$.lista_start = function(obj){
		var id_raw = $(obj).myAttr('id');
		var id = id_raw;
		var data_fields = $(obj).myAttr('data-fields').split(';');
		var data_field_id = $(obj).myAttr('data-field-id');
		var data_options = $(obj).myAttr('data-options').split(',');
		var fields;
		var coluna;
		
		id = id.replace(/lista/gi,'');
		
		var linha = $('<div class="b2make-lista-linha b2make-lista-cabecalho"></div>');
		
		coluna = $('<div class="b2make-lista-coluna" data-cabecalho="true" data-options="true" style="width:'+(data_options.length*(b2make.lista_options_width+2*b2make.lista_options_margin))+'px;text-align:center;">'+b2make.msgs.listaOptionsTitle+'</div>');
		coluna.appendTo(linha);
		
		for(var i=0;i<data_fields.length;i++){
			fields = data_fields[i].split(',');
			
			coluna = $('<div class="b2make-lista-coluna'+(fields[2]?' '+fields[2]:'')+'" data-cabecalho="true" data-field="'+fields[0]+'" style="width:'+fields[3]+'px;text-align:'+fields[4]+';">'+fields[1]+'</div>');
			coluna.appendTo(linha);
		}
		
		linha.appendTo($(obj));
		
		if($('#'+id+'confirm-delete').find('input').prop("checked")){
			b2make.lista_confirm_delete[id_raw] = true;
		} else {
			b2make.lista_confirm_delete[id_raw] = false;
		}
		
		$('#'+id+'confirm-delete').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
			
			if($(this).find('input').prop("checked")){
				b2make.lista_confirm_delete[id_raw] = true;
			} else {
				b2make.lista_confirm_delete[id_raw] = false;
			}
		});
		
		$('#'+id+'confirm-delete-input').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			if($(this).prop("checked")){
				b2make.lista_confirm_delete[id_raw] = false;
			} else {
				b2make.lista_confirm_delete[id_raw] = true;
			}
		});
	}
	
	function lista_reset(obj_id){
		$("#"+obj_id).html('');
		$.lista_start($("#"+obj_id).get(0));
	}
	
	function lista_option_del(){
		var opcao = b2make.lista_delete_opcao;
		var linha = b2make.lista_delete_linha;
		var id = b2make.lista_delete_id;
		var pai = linha.parent();
		
		b2make.lista_delete_id = false;
		b2make.lista_delete_linha = false;
		b2make.lista_delete_opcao = false;
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
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
							pai.trigger('del',{
								id : id,
								ajax_option : pai.myAttr('data-ajax-option')
							});
							linha.remove();
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
	
	function lista(){
		if(!b2make.msgs.listaOptionDelTitle)b2make.msgs.listaOptionDelTitle = 'Clique para excluir este item';
		if(!b2make.msgs.listaOptionEditTitle)b2make.msgs.listaOptionEditTitle = 'Clique para editar este item';
		if(!b2make.msgs.listaOptionBlockTitle)b2make.msgs.listaOptionBlockTitle = 'Clique para ativar/desativar este item';
		if(!b2make.msgs.listaItemDelete)b2make.msgs.listaItemDelete = 'Tem certeza que voc&ecirc; deseja deletar este item?';
		if(!b2make.msgs.listaOptionsTitle)b2make.msgs.listaOptionsTitle = 'A&Ccedil;&Atilde;O';
		if(!b2make.lista_options_width)b2make.lista_options_width = 25;
		if(!b2make.lista_options_margin)b2make.lista_options_margin = 2;
		
		b2make.lista_confirm_delete = new Array();
		
		$(".b2make-lista-dados").each(function(){
			$.lista_start(this);
		});
		
		$(document.body).on('mouseup tap','.b2make-lista-option',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			var pai = $(this).parent().parent().parent();
			var pai_id = pai.myAttr('id');
			var ajax_option = pai.myAttr('data-ajax-option');
			var ajax_no = pai.myAttr('data-ajax-no');
			var id = $(this).myAttr('data-id');
			var option = $(this).myAttr('data-option');
			var opcao = ajax_option+'-'+option;
			
			switch(option){
				case 'edit':
					pai.trigger('edit',{
						id : id,
						ajax_option : ajax_option
					});
				break;
				case 'block':
					var status = $(this).myAttr('data-status');
					
					if(status == 'A'){
						$(this).myAttr('data-status','B');
					} else {
						$(this).myAttr('data-status','A');
					}
					
					if(ajax_no){
						pai.trigger('block',{
							id : id,
							ajax_option : ajax_option
						});
						return;
					}
					
					$.ajax({
						type: 'POST',
						url: '.',
						data: { 
							ajax : 'sim',
							opcao : opcao,
							id : id,
							status : $(this).myAttr('data-status')
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
										pai.trigger('block',{
											id : id,
											ajax_option : ajax_option
										});
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
				break;
				case 'del':
					var msg = b2make.msgs.listaItemDelete;
					
					b2make.lista_delete_id = id;
					b2make.lista_delete_linha = $(this).parent().parent();
					b2make.lista_delete_opcao = opcao;
					
					if(b2make.lista_confirm_delete[pai_id]){
						$.dialogbox_open({
							confirm:true,
							calback_yes: 'b2make-lista-delete-yes',
							msg: msg
						});
					} else {
						lista_option_del();
					}
				break;
				
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-lista-delete-yes',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			lista_option_del();
		});
	}
	
	lista();
	
	function text_fontes_close(){
		if(b2make.wot_fontes_open){
			b2make.wot_fontes_open = false;
			$('#b2make-wot-count').hide();
		}
	}
	
	function jpicker_cancel(){
		//$('input.Cancel').trigger('click');
		
		b2make.text_jpicker = false;
		b2make.bg_jpicker = false;
		b2make.bg_cont_jpicker = false;
		b2make.bg_redes_jpicker = false;
	}
	
	function specific_options(){
		if(!b2make.conteiner_area_largura_max_value)b2make.conteiner_area_largura_max_value = 2000;
		if(!b2make.conteiner_area_largura_min_value)b2make.conteiner_area_largura_min_value = 20;
		if(!b2make.conteiner_bi_positon_max_value)b2make.conteiner_bi_positon_max_value = 2000;
		if(!b2make.conteiner_bi_positon_min_value)b2make.conteiner_bi_positon_min_value = 0;
		if(!b2make.wotbi_positon_max_value)b2make.wotbi_positon_max_value = 2000;
		if(!b2make.wotbi_positon_min_value)b2make.wotbi_positon_min_value = 0;
		if(!b2make.wot_font_max_value)b2make.wot_font_max_value = 90;
		if(!b2make.wot_font_min_value)b2make.wot_font_min_value = 6;
		if(!b2make.wot_padding_max_value)b2make.wot_padding_max_value = 50;
		if(!b2make.wot_padding_min_value)b2make.wot_padding_min_value = 0;
		if(!b2make.wors_margin_max_value)b2make.wors_margin_max_value = 99;
		if(!b2make.wors_margin_min_value)b2make.wors_margin_min_value = 0;
		if(!b2make.wors_tamanho_max_value)b2make.wors_tamanho_max_value = 300;
		if(!b2make.wors_tamanho_min_value)b2make.wors_tamanho_min_value = 0;
		if(!b2make.msgs.colorTitle)b2make.msgs.colorTitle = 'Clique para mudar a ';
		if(!b2make.msgs.textColorTitle)b2make.msgs.textColorTitle = 'Cor do Texto';
		if(!b2make.msgs.bgColorTitle)b2make.msgs.bgColorTitle = 'Cor da Caixa';
		if(!b2make.msgs.jpickerLocalization)b2make.msgs.jpickerLocalization = {
			text:
			{
				title: 'Clique nas marcas para escolher uma cor',
				newColor: 'nova',
				currentColor: 'atual',
				ok: 'OK',
				cancel: 'Cancelar'
			},
			tooltips:
			{
				picker_open:'Clique para abrir o Color Picker'
				,
				colors:
				{
					newColor: 'Nova Cor - Pressione &quot;OK&quot; para Criar',
					currentColor: 'Clique para reverter para cor original'
				},
				buttons:
				{
					ok: 'Clique para selecionar esta cor',
					cancel: 'Cancelar e reverter para cor original'
				},
				hue:
				{
					radio: 'Mudar para o modo de cor &quot;Hue&quot;',
					textbox: 'Entre um valor &quot;Hue&quot; (0-360&ordm;)'
				},
				saturation:
				{
					radio: 'Mudar para o modo de cor &quot;Satura&ccedil;&atilde;o&quot;',
					textbox: 'Entre um valor &quot;Satura&ccedil;&atilde;o&quot; (0-100%)'
				},
				value:
				{
					radio: 'Mudar para o modo de cor &quot;Valor&quot;',
					textbox: 'Entre um valor &quot;Valor&quot; (0-100%)'
				},
				red:
				{
					radio: 'Mudar para o modo de cor &quot;Red&quot;',
					textbox: 'Entre um valor Red (0-255)'
				},
				green:
				{
					radio: 'Mudar para o modo de cor &quot;Green&quot;',
					textbox: 'Entre um valor &quot;Green&quot; (0-255)'
				},
				blue:
				{
					radio: 'Mudar para o modo de cor &quot;Blue&quot; Color Mode',
					textbox: 'Entre um valor &quot;Blue&quot; (0-255)'
				},
				alpha:
				{
					radio: 'Mudar para o modo de cor &quot;Alpha&quot; Color Mode',
					textbox: 'Entre um valor &quot;Alpha&quot; (0-100)'
				},
				hex:
				{
					textbox: 'Entre um valor &quot;Hex&quot; (#000000-#ffffff)',
					alpha: 'Entre um valor &quot;Alpha&quot; (#00-#ff)'
				}
			}
		};
		
		$('#b2make-woi-editar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(!b2make.iframe_for_textarea){
				iframe_for_textarea();
			} else {
				textarea_for_iframe();
			}
		});
		
		$('#b2make-wot-text-editar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(!b2make.texto_for_textarea){
				texto_for_textarea();
			} else {
				textarea_for_texto();
			}
		});
		
		$.fn.jPicker.defaults.images.clientPath='jpicker/images/';
		
		var conts_left = 0;
		var left_text_color = $('#b2make-wot-text-color').offset().left+'px';
		var left_bg_color = $('#b2make-wot-bg-color').offset().left+'px';
		var left_bg_cont_color = $('#b2make-wot-bg-color').offset().left+80+'px';
		
		b2make.msgs.jpickerLocalization.tooltips.picker_open = b2make.msgs.colorTitle + b2make.msgs.textColorTitle;
		$('#b2make-wot-text-color-jpicker').jPicker({window:{element:'#b2make-wot-text-cores',zIndex:120,effects:{type:"fade",speed:{show:b2make.fade_time,hide:b2make.fade_time}},position:{x:left_text_color,y:'28px'},expandable:true,title:b2make.msgs.textColorTitle,alphaSupport:true},color:{active:new $.jPicker.Color({ hex: '000000', a:255 })},localization:b2make.msgs.jpickerLocalization});
		b2make.msgs.jpickerLocalization.tooltips.picker_open = b2make.msgs.colorTitle + b2make.msgs.bgColorTitle;
		$('#b2make-wot-bg-color-jpicker').jPicker({window:{element:'#b2make-wot-text-cores',zIndex:120,effects:{type:"fade",speed:{show:b2make.fade_time,hide:b2make.fade_time}},position:{x:left_bg_color,y:'28px'},expandable:true,title:b2make.msgs.bgColorTitle,alphaSupport:true},color:{active:new $.jPicker.Color(null)},localization:b2make.msgs.jpickerLocalization});
		b2make.msgs.jpickerLocalization.tooltips.picker_open = b2make.msgs.colorTitle + b2make.msgs.bgColorTitle;
		$('#b2make-conteiner-bg-color-jpicker').jPicker({window:{element:'#b2make-conteiner-cores',zIndex:120,effects:{type:"fade",speed:{show:b2make.fade_time,hide:b2make.fade_time}},position:{x:left_bg_cont_color,y:'28px'},expandable:true,title:b2make.msgs.bgColorTitle,alphaSupport:true},color:{active:new $.jPicker.Color(null)},localization:b2make.msgs.jpickerLocalization});

		$('#popup').hide();
		
		$('.Icon').tooltip({
			show: {
				effect: "fade",
				delay: 250
			}
		});
		
		$(document.body).on('mouseup tap','#b2make-wot-text-color .jPicker .Icon',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			jpicker_cancel();
			$('#b2make-wot-text-cores .Container').css('top','0px');
			b2make.text_jpicker = true;
			if(b2make.texto_for_textarea)textarea_for_texto();
		});
		
		$(document.body).on('mouseup tap','#b2make-wot-bg-color .jPicker .Icon',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			jpicker_cancel();
			$('#b2make-wot-text-cores .Container').css('top','0px');
			b2make.bg_jpicker = true;
			if(b2make.texto_for_textarea)textarea_for_texto();
		});
		
		$(document.body).on('mouseup tap','#b2make-conteiner-cores .jPicker .Icon',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			jpicker_cancel();
			$('#b2make-conteiner-cores .jPicker').css('top','28px');
			$('#b2make-conteiner-cores .jPicker').show();
			b2make.bg_cont_jpicker = true;
		});
		
		$(document.body).on('mouseup tap','.b2make-redessociais-options-jpicker .jPicker .Icon',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var num = $(this).parent().parent().myAttr('data-num');
			
			jpicker_cancel();
			b2make.bg_redes_jpicker = true;
			b2make.bg_redes_jpicker_num = parseInt(num);
		});
		
		$(document.body).on('mouseup tap','#b2make-wcas-cor-cont .jPicker .Icon',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var num = $(this).parent().parent().myAttr('data-num');
			
			$('#b2make-conteiner-cores .jPicker').css('top','0px');
			jpicker_cancel();
			b2make.sombra_jpicker = true;
		});
		
		$(document.body).on('mouseup tap','input.Ok',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = (b2make.conteiner_child_show ? b2make.conteiner_child_obj : b2make.conteiner_obj);
			var all;
			
			if(b2make.texto_for_textarea)textarea_for_texto();
			
			if(b2make.text_jpicker){
				all = $.jPicker.List[0].color.active.val('all');
				
				if(all){
					$(obj).css({
						'color': 'rgba('+all.r+','+all.g+','+all.b+','+(all.a/255).toFixed(1)+')'
					});
					
					$(obj).myAttr('data-text-ahex',all.ahex);
				} else {
					$(obj).css({
						'color': 'rgb(0,0,0)'
					});
					
					$(obj).myAttr('data-text-ahex',false);
				}
				
				b2make.text_jpicker = false;
			}
			
			if(b2make.bg_jpicker){
				all = $.jPicker.List[1].color.active.val('all');
				
				if(all){
					$(obj).css({
						'backgroundColor': 'rgba('+all.r+','+all.g+','+all.b+','+(all.a/255).toFixed(1)+')'
					});
					
					$(obj).myAttr('data-bg-ahex',all.ahex);
				} else {
					$(obj).css({
						'backgroundColor': 'transparent'
					});
					
					$(obj).myAttr('data-bg-ahex',false);
				}
				
				b2make.bg_jpicker = false;
			}
			
			if(b2make.bg_cont_jpicker){
				all = $.jPicker.List[2].color.active.val('all');
				
				if(all){
					$(obj).css({
						'backgroundColor': 'rgba('+all.r+','+all.g+','+all.b+','+(all.a/255).toFixed(1)+')'
					});
					
					$(obj).myAttr('data-bg-ahex',all.ahex);
				} else {
					$(obj).css({
						'backgroundColor': 'transparent'
					});
					
					$(obj).myAttr('data-bg-ahex',false);
				}
				
				b2make.bg_cont_jpicker = false;
			}
			
			if(b2make.sombra_jpicker){
				all = $.jPicker.List[17].color.active.val('all');
				
				if(all){
					widget_aba_sombra_mudar_atibuto({value:'rgba('+all.r+','+all.g+','+all.b+','+(all.a/255).toFixed(1)+')',ahex:all.ahex},4);
				} else {
					widget_aba_sombra_mudar_atibuto({value:'rgb(0,0,0)',ahex:all.ahex},4);
				}
				
				b2make.sombra_jpicker = false;
			}
			
			if(b2make.bg_redes_jpicker){
				var num = b2make.bg_redes_jpicker_num;
				var id = b2make.redessociais[num].id;
				var cores = $(obj).myAttr('data-cores-ahex');
				var cores_arr = new Array();
				
				if(cores)
					cores_arr = cores.split(';');
				
				all = $.jPicker.List[(num+3)].color.active.val('all');
				
				var cores_saida = '';
				var found;
				
				if(all){
					for(var i=0;i<cores_arr.length;i++){
						var cor_arr = cores_arr[i].split(',');
						
						if(cor_arr[0] == id){
							cores_saida = cores_saida + (cores_saida ? ';' : '') + id + ',' + all.ahex;
							found = true;
						} else {
							cores_saida = cores_saida + (cores_saida ? ';' : '') + cores_arr[i];
						}
					}
					
					if(!found){
						cores_saida = cores_saida + (cores_saida ? ';' : '') + id + ',' + all.ahex;
					}
				} else {					
					for(var i=0;i<cores_arr.length;i++){
						var cor_arr = cores_arr[i].split(',');
						
						if(cor_arr[0] == id){
							found = true;
						} else {
							cores_saida = cores_saida + (cores_saida ? ';' : '') + cores_arr[i];
						}
					}
				}
				
				if(cores_saida)
					$(obj).myAttr('data-cores-ahex',cores_saida);
				else 
					$(obj).myAttr('data-cores-ahex',null);
				
				redessociais_widget_update();
				
				b2make.bg_redes_jpicker = false;
			}
			
			
		});
		
		$(document.body).on('mouseup tap','input.Cancel',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.texto_for_textarea && b2make.conteiner_child_show)textarea_for_texto();
		});
		
		$('.b2make-wot-align').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = b2make.conteiner_child_obj;
			var id = $(this).myAttr('id');
			var pos;
			
			if(b2make.texto_for_textarea)textarea_for_texto();
			
			if(id.match(/b2make-wot-align-/) == 'b2make-wot-align-'){
				pos = id.replace('b2make-wot-align-','');
				$(obj).css({
					'textAlign' : pos
				});
			} else if(id.match(/b2make-wot-valign-/) == 'b2make-wot-valign-'){
				pos = id.replace('b2make-wot-valign-','');
				$(obj).find('div').find('div').css({
					'verticalAlign' : pos
				});
			}
		});
		
		$('#b2make-wot-padding').keyup(function (e) {
			var conteiner = b2make.selecionador_objetos.conteiner;
			if(b2make.texto_for_textarea)textarea_for_texto();
			var value = parseInt(this.value);
			
			if(value > b2make.wot_padding_max_value){
				this.value = b2make.wot_padding_max_value;
				value = b2make.wot_padding_max_value;
			}
			
			if(value < b2make.wot_padding_min_value){
				value = b2make.wot_padding_min_value;
			}
			
			$(b2make.conteiner_child_obj).css('padding',value+'px');
			$(conteiner).css('padding',value+'px');
			$('.b2make-selecionar-objetos-rotate-mask').css('padding','0px '+value+'px');
			$(b2make.conteiner_child_obj).myAttr('data-padding',value);
		});
		
		$('#b2make-wot-fontes-holder').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			var fonte = $(this).html();
			
			if(!b2make.wot_fontes_open){
				google_fonts();
				$('#b2make-wot-count').show();
				b2make.wot_fontes_open = true;
				
				if(b2make.google_fonts){
					$('#b2make-wot-fontes li,.b2make-wot-google-fontes li').each(function(){
						if(fonte == $(this).html()){
							$(this).addClass('b2make-wot-fonte-clicked');
						} else {
							$(this).removeClass('b2make-wot-fonte-clicked');
						}
					});
				} else {
					b2make.google_font_first_font = fonte;
				}
				
				$('#b2make-fonts-count-teste').css({
					'fontFamily': $(this).css('fontFamily')
				});
			} else {
				$('#b2make-wot-count').hide();
				b2make.wot_fontes_open = false;
			}
		});
		
		$('#b2make-wot-fontes li').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			e.stopPropagation();
			
			$('#b2make-wot-fontes li,.b2make-wot-google-fontes li').each(function(){
				$(this).removeClass('b2make-wot-fonte-clicked');
			});
			
			$(this).addClass('b2make-wot-fonte-clicked');
			
			$('#b2make-wot-fontes-holder,#b2make-wot-count-teste').css({
				'fontFamily': $(this).css('fontFamily')
			});
			$('#b2make-wot-fontes-holder').html($(this).css('fontFamily').replace(/'/gi,''));
			
			$(obj).myAttr('data-font-family',$(this).css('fontFamily').replace(/'/gi,''));
			$(obj).myAttr('data-google-font','nao');
			$(obj).css('fontFamily',$(this).css('fontFamily'));
		});
		
		$(document.body).on('mouseup tap','#b2make-wot-count .b2make-wot-google-fontes li',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			e.stopPropagation();
			
			$('#b2make-wot-fontes li,.b2make-wot-google-fontes li').each(function(){
				$(this).removeClass('b2make-wot-fonte-clicked');
			});
			
			$(this).addClass('b2make-wot-fonte-clicked');
			
			$('#b2make-wot-fontes-holder,#b2make-wot-count-teste').css({
				'fontFamily': $(this).myAttr('data-font-family')
			});
			$('#b2make-wot-fontes-holder').html($(this).myAttr('data-font-family').replace(/'/gi,''));
			
			$(obj).myAttr('data-font-family',$(this).myAttr('data-font-family').replace(/'/gi,''));
			$(obj).myAttr('data-google-font','sim');
			$(obj).css('fontFamily',$(this).myAttr('data-font-family'));
			
			$.google_fonts_wot_load({
				family : $(this).myAttr('data-font-family')
			});
		});
		
		$('#b2make-wot-font-size').keyup(function (e) {
			if(b2make.texto_for_textarea)textarea_for_texto();
			var value = parseInt(this.value);
			
			if(value > b2make.wot_font_max_value){
				this.value = b2make.wot_font_max_value;
				value = b2make.wot_font_max_value;
			}
			
			if(value < b2make.wot_font_min_value){
				value = b2make.wot_font_min_value;
			}
			
			$(b2make.conteiner_child_obj).css('fontSize',value+'px');
			$(b2make.conteiner_child_obj).myAttr('data-font-size',value);
		});
		
		$('#b2make-wot-bg-image-picker').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			e.stopPropagation();
			
			//if(!b2make.widget_sub_options_open){
				b2make.widget_sub_options_up_clicked = false;
				b2make.widget_sub_options_have = true;
				b2make.widget_sub_options_type = 'texto';
				b2make.widget_edit_sub_options_open = true;
				$.widget_sub_options_open();
				
				b2make.widget_specific_type = 'texto';
				$.widget_specific_options_open();
			/* } else {
				b2make.widget_sub_options_up_clicked_2 = true;
				widget_sub_options_close();
			} */
		});
		
		if($('#b2make-wotbi-position-x').val() != 'value') $('#b2make-wotbi-position-x-value').hide();
		if($('#b2make-wotbi-position-y').val() != 'value') $('#b2make-wotbi-position-y-value').hide();
		
		$('#b2make-wotbi-repeat').on('change',function(e){
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			
			if($(this).val() == 'completar'){
				$(obj).myAttr('data-background-repeat','completar');
				$(obj).css('backgroundRepeat','no-repeat');
				$(obj).css('backgroundSize','100% auto');
			} else {
				$(obj).myAttr('data-background-repeat',$(this).val());
				$(obj).css('backgroundRepeat',$(this).val());
				$(obj).css('backgroundSize','auto auto');
			}
		});
		
		$('#b2make-wotbi-position-x').on('change',function(e){
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			
			var pos = $(obj).myAttr('data-background-position-y');
			
			if(pos){
				switch(pos){
					case 'top':
					case 'bottom':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if($(this).val() != 'value'){
				$('#b2make-wotbi-position-x-value').hide();
				
				$(obj).myAttr('data-background-position-x',$(this).val());
				$(obj).css('backgroundPosition',$(this).val() + ' ' + pos);
			} else {
				$('#b2make-wotbi-position-x-value').show();
				
				$(obj).myAttr('data-background-position-x','0');
				$(obj).css('backgroundPosition','0px' + ' ' + pos);
			}
			
		});
		
		$('#b2make-wotbi-position-y').on('change',function(e){
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			
			var pos = $(obj).myAttr('data-background-position-x');
			
			if(pos){
				switch(pos){
					case 'left':
					case 'right':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if($(this).val() != 'value'){
				$('#b2make-wotbi-position-y-value').hide();
				
				$(obj).myAttr('data-background-position-y',$(this).val());
				$(obj).css('backgroundPosition',pos + ' ' + $(this).val());
			} else {
				$('#b2make-wotbi-position-y-value').show();
				
				$(obj).myAttr('data-background-position-y','0');
				$(obj).css('backgroundPosition',pos + ' ' + '0px');
			}
			
		});
		
		$('#b2make-wotbi-position-x-value').keyup(function (e) {
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			var value = parseInt(this.value);
			
			var pos = $(obj).myAttr('data-background-position-y');
			
			if(pos){
				switch(pos){
					case 'top':
					case 'bottom':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if(value > b2make.wotbi_positon_max_value){
				this.value = b2make.wotbi_positon_max_value;
				value = b2make.wotbi_positon_max_value;
			}
			
			if(value < b2make.wotbi_positon_min_value){
				value = b2make.wotbi_positon_min_value;
			}
			
			$(obj).myAttr('data-background-position-x',value);
			$(obj).css('backgroundPosition',value+'px' + ' ' + pos);
		});
		
		$('#b2make-wotbi-position-y-value').keyup(function (e) {
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			var value = parseInt(this.value);
			
			var pos = $(obj).myAttr('data-background-position-x');
			
			if(pos){
				switch(pos){
					case 'left':
					case 'right':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if(value > b2make.wotbi_positon_max_value){
				this.value = b2make.wotbi_positon_max_value;
				value = b2make.wotbi_positon_max_value;
			}
			
			if(value < b2make.wotbi_positon_min_value){
				value = b2make.wotbi_positon_min_value;
			}
			
			$(obj).myAttr('data-background-position-y',value);
			$(obj).css('backgroundPosition',pos + ' ' + value+'px');
		});
		
		$('#b2make-wot-negrito').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			e.stopPropagation();
			e.preventDefault();
			
			if($(obj).myAttr('data-texto-bold') == '1'){
				$(obj).myAttr('data-texto-bold','0');
				$(obj).css('fontWeight','normal');
			} else {
				$(obj).myAttr('data-texto-bold','1');
				$(obj).css('fontWeight','bold');
			}
		});
		
		$('#b2make-wot-italico').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if(b2make.texto_for_textarea)textarea_for_texto();
			var obj = b2make.conteiner_child_obj;
			e.stopPropagation();
			e.preventDefault();
			
			if($(obj).myAttr('data-texto-italico') == '1'){
				$(obj).myAttr('data-texto-italico','0');
				$(obj).css('fontStyle','normal');
			} else {
				$(obj).myAttr('data-texto-italico','1');
				$(obj).css('fontStyle','italic');
			}
		});
		
		// ========================= Conteiner
		
		$('#b2make-conteiner-bg-image-picker').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			b2make.widget_sub_options_up_clicked = false;
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = 'conteiner';
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open();
			
			b2make.widget_specific_type = 'conteiner';
			$.widget_specific_options_open();
		});
		
		$('#b2make-conteiner-banners-editar-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			b2make.widget_sub_options_up_clicked = false;
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = 'conteiner-banner';
			b2make.widget_sub_options_lightbox_height = '445';
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open();
		});
		
		$('#b2make-conteiner-banners-config-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();
			
			b2make.widget_sub_options_up_clicked = false;
			b2make.widget_sub_options_have = true;
			b2make.widget_sub_options_type = 'conteiner-banner-config';
			b2make.widget_sub_options_lightbox_width = '600';
			b2make.widget_sub_options_lightbox_height = '290';
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open();
		});
		
		if($('#b2make-conteiner-bi-position-x').val() != 'value') $('#b2make-conteiner-bi-position-x-value').hide();
		if($('#b2make-conteiner-bi-position-y').val() != 'value') $('#b2make-conteiner-bi-position-y-value').hide();
		
		$('#b2make-conteiner-bi-repeat').on('change',function(e){
			var obj = b2make.conteiner_obj;
			
			if($(this).val() == 'completar'){
				$(obj).myAttr('data-background-repeat','completar');
				$(obj).css('backgroundRepeat','no-repeat');
				$(obj).css('backgroundSize','100% auto');
			} else {
				$(obj).myAttr('data-background-repeat',$(this).val());
				$(obj).css('backgroundRepeat',$(this).val());
				$(obj).css('backgroundSize','auto auto');
			}
		});
		
		$('#b2make-conteiner-bi-position-x').on('change',function(e){
			var obj = b2make.conteiner_obj;
			
			var pos = $(obj).myAttr('data-background-position-y');
			
			if(pos){
				switch(pos){
					case 'top':
					case 'bottom':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if($(this).val() != 'value'){
				$('#b2make-conteiner-bi-position-x-value').hide();
				
				$(obj).myAttr('data-background-position-x',$(this).val());
				$(obj).css('backgroundPosition',$(this).val() + ' ' + pos);
			} else {
				$('#b2make-conteiner-bi-position-x-value').show();
				
				$(obj).myAttr('data-background-position-x','0');
				$(obj).css('backgroundPosition','0px' + ' ' + pos);
			}
			
		});
		
		$('#b2make-conteiner-bi-position-y').on('change',function(e){
			var obj = b2make.conteiner_obj;
			
			var pos = $(obj).myAttr('data-background-position-x');
			
			if(pos){
				switch(pos){
					case 'left':
					case 'right':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if($(this).val() != 'value'){
				$('#b2make-conteiner-bi-position-y-value').hide();
				
				$(obj).myAttr('data-background-position-y',$(this).val());
				$(obj).css('backgroundPosition',pos + ' ' + $(this).val());
			} else {
				$('#b2make-conteiner-bi-position-y-value').show();
				
				$(obj).myAttr('data-background-position-y','0');
				$(obj).css('backgroundPosition',pos + ' ' + '0px');
			}
			
		});
		
		$('#b2make-conteiner-bi-position-x-value').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			var pos = $(obj).myAttr('data-background-position-y');
			
			if(pos){
				switch(pos){
					case 'top':
					case 'bottom':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if(value > b2make.conteiner_bi_positon_max_value){
				this.value = b2make.conteiner_bi_positon_max_value;
				value = b2make.conteiner_bi_positon_max_value;
			}
			
			if(value < b2make.conteiner_bi_positon_min_value){
				value = b2make.conteiner_bi_positon_min_value;
			}
			
			$(obj).myAttr('data-background-position-x',value);
			$(obj).css('backgroundPosition',value+'px' + ' ' + pos);
		});
		
		$('#b2make-conteiner-bi-position-y-value').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var value = parseInt(this.value);
			
			var pos = $(obj).myAttr('data-background-position-x');
			
			if(pos){
				switch(pos){
					case 'left':
					case 'right':
					case 'center':
						var ok;
					break;
					default: pos = pos + 'px';
				}
			} else {
				pos = 'top';
			}
			
			if(value > b2make.conteiner_bi_positon_max_value){
				this.value = b2make.conteiner_bi_positon_max_value;
				value = b2make.conteiner_bi_positon_max_value;
			}
			
			if(value < b2make.conteiner_bi_positon_min_value){
				value = b2make.conteiner_bi_positon_min_value;
			}
			
			$(obj).myAttr('data-background-position-y',value);
			$(obj).css('backgroundPosition',pos + ' ' + value+'px');
		});
		
		$('#b2make-conteiner-area-largura-lbl').hide();
		$('#b2make-conteiner-area-largura').hide();
		
		$('#b2make-conteiner-area-status').on('change',function(e){
			var status = $(this).val();
			
			if(status == 'n'){
				conteiner_area_remove({});
			} else {
				conteiner_area_add();
			}
		});
		
		$('#b2make-conteiner-fixed-status').on('change',function(e){
			var status = $(this).val();
			var obj = b2make.conteiner_obj;
			
			if(status == 'n'){
				$(obj).myAttr('data-position','relative');
				$(obj).css('position','relative');
				$(obj).css('top','auto');
				$(obj).css('left','auto');
				$(obj).css('bottom','auto');
				$(window).scrollTop($(obj).offset().top - 126);
				$(obj).css('zIndex','auto');
			} else {
				if(status == 't'){
					//var top = parseInt($(obj).offset().top) - parseInt($(window).scrollTop());
					var top = '124px';
					$(obj).myAttr('data-position','fixed');
					$(obj).css('position','fixed');
					$(obj).css('top',top);
					$(obj).css('bottom','auto');
					if(b2make_menu.open){
						$(obj).css('left',b2make_menu.width_conteiner+'px');
					} else {
						$(obj).css('left','0px');
					}
					
					$(obj).css('zIndex',50);
				} else {
					//var bottom = parseInt($(window).height() - $(obj).offset().top - $(obj).outerHeight(true)) - parseInt($(window).scrollTop());
					var bottom = '0px';
					$(obj).myAttr('data-position','fixed');
					$(obj).css('position','fixed');
					$(obj).css('top','auto');
					$(obj).css('bottom',bottom);
					if(b2make_menu.open){
						$(obj).css('left',b2make_menu.width_conteiner+'px');
					} else {
						$(obj).css('left','0px');
					}
					
					$(obj).css('zIndex',50);
				}
			}
			
			$(obj).myAttr('data-area-fixed',status);
		});
		
		$('#b2make-conteiner-area-largura').keyup(function (e) {
			var obj = b2make.conteiner_obj;
			var obj_area = b2make.conteiner_obj_area;
			var value = parseInt(this.value);
			
			if(value > b2make.conteiner_area_largura_max_value){
				this.value = b2make.conteiner_area_largura_max_value;
				value = b2make.conteiner_area_largura_max_value;
			}
			
			if(value < b2make.conteiner_area_largura_min_value){
				value = b2make.conteiner_area_largura_min_value;
			}
			
			$(obj).myAttr('data-area-largura',value);
			$(obj_area).css('width',value+'px');
		});
		
		$('#b2make-wors-margin').keyup(function (e) {
			var value = parseInt(this.value);
			
			if(value > b2make.wors_margin_max_value){
				this.value = b2make.wors_margin_max_value;
				value = b2make.wors_margin_max_value;
			}
			
			if(value < b2make.wors_margin_min_value){
				value = b2make.wors_margin_min_value;
			}
			
			$(b2make.conteiner_child_obj).myAttr('data-margin',value);
			redessociais_widget_update();
		});
		
		$('#b2make-wors-tamanho').keyup(function (e) {
			var value = parseInt(this.value);
			
			if(value > b2make.wors_tamanho_max_value){
				this.value = b2make.wors_tamanho_max_value;
				value = b2make.wors_tamanho_max_value;
			}
			
			if(value < b2make.wors_tamanho_min_value){
				value = b2make.wors_tamanho_min_value;
			}
			
			$(b2make.conteiner_child_obj).myAttr('data-tamanho',value);
			redessociais_widget_update();
		});
		
		b2make.redessociais = new Array();
		
		b2make.redessociais.push({title:'Facebook',id:'facebook',pos:0.2});
		b2make.redessociais.push({title:'Twitter',id:'twitter',pos:-0.75});
		b2make.redessociais.push({title:'Instagram',id:'instagram',pos:-1.6});
		b2make.redessociais.push({title:'E-mail',id:'email',pos:-2.427});
		b2make.redessociais.push({title:'Youtube',id:'youtube',pos:-3.3});
		b2make.redessociais.push({title:'Flicker',id:'flicker',pos:-4.18});
		b2make.redessociais.push({title:'Linkedin',id:'linkedin',pos:-5.0});
		b2make.redessociais.push({title:'Vimeo',id:'vimeo',pos:-5.9});
		b2make.redessociais.push({title:'Tumblr',id:'tumblr',pos:-6.74});
		b2make.redessociais.push({title:'Skype',id:'skype',pos:-7.57});
		b2make.redessociais.push({title:'Foursquare',id:'foursquare',pos:-8.45});
		b2make.redessociais.push({title:'Google Plus',id:'googleplus',pos:-9.3});
		b2make.redessociais.push({title:'Pinterest',id:'pinterest',pos:-10.15});
		b2make.redessociais.push({title:'Picasa',id:'picasa',pos:-11.03});
		
		b2make.redessociais_fator = 17;
		
		for(var i=0;i<b2make.redessociais.length;i++){
			var title = b2make.redessociais[i].title;
			var id = b2make.redessociais[i].id;
			
			var div = $('<div class="b2make-redessociais-options-cont"></div>');
			var div_title = $('<div class="b2make-redessociais-options-title">'+title+':</div>');
			var input = $('<input data-id="'+id+'" class="b2make-redessociais-options-endereco b2make-input-select b2make-tooltip" title="Clique para mudar o endere&ccedil;o da p&aacute;gina do '+title+'">');
			var div_snapshot = $('<div class="b2make-redessociais-options-snapshot b2make-tooltip b2make-other-options-2" data-id="'+id+'" data-type="redessociaisimg" data-title="Selo Social" title="Clique para alterar a imagem desta rede social"><div></div></div>');
			var div_jpicker = $('<div class="b2make-redessociais-options-jpicker" data-num="'+i+'"><div class="b2make-redessociais-options-jpicker-call"></div></div>');
			
			div_title.appendTo(div);
			input.appendTo(div);
			div_snapshot.appendTo(div);
			div_jpicker.appendTo(div);
			
			div.appendTo('#b2make-widget-sub-options-redessociais');
			
			div_snapshot.find('div').css('position','absolute');
			
		}
		
		b2make.msgs.jpickerLocalization.tooltips.picker_open = b2make.msgs.colorTitle + b2make.msgs.bgColorTitle;
		$('.b2make-redessociais-options-jpicker-call').jPicker({window:{element:'#b2make-lightbox',zIndex:120,effects:{type:"fade",speed:{show:b2make.fade_time,hide:b2make.fade_time}},position:{x:'28px',y:'28px'},expandable:true,title:b2make.msgs.bgColorTitle,alphaSupport:true},color:{active:new $.jPicker.Color(null)},localization:b2make.msgs.jpickerLocalization});
		
		$('.b2make-tooltip').tooltip({
			show: {
				effect: "fade",
				delay: 400
			}
		});
		
		/* $('.b2make-input-select').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).select();
		}); */
		
		$('.b2make-redessociais-options-endereco').on('blur',function(e){
			var obj = b2make.conteiner_child_obj;
			var id = $(this).myAttr('data-id');
			var value = $(this).val();
			var enderecos = $(obj).myAttr('data-enderecos');
			var enderecos_arr = new Array();
			
			if(enderecos)
				enderecos_arr = enderecos.split(';');
			
			var enderecos_saida = '';
			var found;
			
			if(value){
				for(var i=0;i<enderecos_arr.length;i++){
					var endereco_arr = enderecos_arr[i].split(',');
					
					if(endereco_arr[0] == id){
						enderecos_saida = enderecos_saida + (enderecos_saida ? ';' : '') + id + ',' + value;
						found = true;
					} else {
						enderecos_saida = enderecos_saida + (enderecos_saida ? ';' : '') + enderecos_arr[i];
					}
				}
				
				if(!found){
					enderecos_saida = enderecos_saida + (enderecos_saida ? ';' : '') + id + ',' + value;
				}
			} else {					
				for(var i=0;i<enderecos_arr.length;i++){
					var endereco_arr = enderecos_arr[i].split(',');
					
					if(endereco_arr[0] == id){
						found = true;
					} else {
						enderecos_saida = enderecos_saida + (enderecos_saida ? ';' : '') + enderecos_arr[i];
					}
				}
			}
			
			if(enderecos_saida)
				$(obj).myAttr('data-enderecos',enderecos_saida);
			else 
				$(obj).myAttr('data-enderecos',null);
			
			redessociais_widget_update();
		});
		
		$('.b2make-other-options-2').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			switch($(this).myAttr('data-type')){
				case 'redessociaisimg': 
					if($(this).myAttr('data-id'))b2make.redes_sociais_image_select = $(this).myAttr('data-id'); else b2make.redes_sociais_image_select = false;
					
					b2make.widget_sub_options_type = 'redessociaisimg';
					b2make.widget_edit_sub_options_open = true;
					$.widget_sub_options_open();
					b2make.widget_sub_options_back = true;
					b2make.widget_sub_options_type = 'redessociais';
				break;
				
			}
		});
	}

	specific_options();
	
	function widget_aba_sombra_mudar_atibuto(input,id){
		if(input.value != '-'){
			var obj = b2make.conteiner_child_obj;
			var sombra = $(obj).myAttr('data-sombra');if(!sombra){sombra = '0;0;0;0;rgba(1,1,1,1);000000ff';}
			var sombra_arr = sombra.split(';');
			var value = (id != 4 ? parseInt(input.value) : input.value);
			
			if(!value)value = 0;
			
			if(id != 4){
				if(value > 300) value = 300;
				if(value < -300) value = -300;
				if(value != 0)input.value = value; else input.value = '';
			}
			
			var sombra_saida = '';
			
			for(var i=0;i<5;i++){
				if(i == id){
					sombra_saida = sombra_saida + (sombra_saida ? ';':'') + value;
				} else {
					sombra_saida = sombra_saida + (sombra_saida ? ';':'') + sombra_arr[i];
				}
			}
			
			if(input.ahex){
				sombra_saida = sombra_saida + (sombra_saida ? ';':'') + input.ahex;
			} else {
				sombra_saida = sombra_saida + (sombra_saida ? ';':'') + sombra_arr[5];
			}
			
			$(obj).myAttr('data-sombra',sombra_saida);
			widget_aba_sombra_update();
		}
	}
	
	function widget_aba_sombra_update(){
		var obj = b2make.conteiner_child_obj;
		var sombra = $(obj).myAttr('data-sombra');
		
		if(sombra){
			var sombra_arr = sombra.split(';');
		
			var deslocamento_x = sombra_arr[0];
			var deslocamento_y = sombra_arr[1];
			var desfoque = sombra_arr[2];
			var tamanho = sombra_arr[3];
			var cor = sombra_arr[4];
			
			var css = deslocamento_x+'px '+deslocamento_y+'px '+desfoque+'px '+tamanho+'px '+cor;
			
			$(obj).css('-webkit-box-shadow',css);
			$(obj).css('box-shadow',css);
		}
	}
	
	function widget_aba_sombra(){
		$('#b2make-wcas-deslocamento-x-val').keyup(function (e) {
			widget_aba_sombra_mudar_atibuto(this,0);
		});
		
		$('#b2make-wcas-deslocamento-y-val').keyup(function (e) {
			widget_aba_sombra_mudar_atibuto(this,1);
		});
		
		$('#b2make-wcas-desfoque-val').keyup(function (e) {
			widget_aba_sombra_mudar_atibuto(this,2);
		});
		
		$('#b2make-wcas-tamanho-val').keyup(function (e) {
			widget_aba_sombra_mudar_atibuto(this,3);
		});
		
		var left_sombra_cont_color = ($('#b2make-wcas-cor-cont').offset().left-20)+'px';
		
		b2make.msgs.jpickerLocalization.tooltips.picker_open = b2make.msgs.colorTitle + b2make.msgs.bgColorTitle;
		$('#b2make-wcas-cor-jpicker').jPicker({window:{element:'#b2make-wcas-cor-cont',zIndex:120,effects:{type:"fade",speed:{show:b2make.fade_time,hide:b2make.fade_time}},position:{x:left_sombra_cont_color,y:'0px'},expandable:true,title:b2make.msgs.bgColorTitle,alphaSupport:true},color:{active:new $.jPicker.Color(null)},localization:b2make.msgs.jpickerLocalization});
		
	}
	
	widget_aba_sombra();
	
	function widget_aba_borda_update(){
		var obj = b2make.conteiner_child_obj;
		var atual = ($(obj).myAttr('data-bordas-atual') ? $(obj).myAttr('data-bordas-atual') : 'todas');
		
		switch(atual){
			case 'todas':
				var todas = $(obj).myAttr('data-bordas-todas');
				
				if(!todas) todas = '0;solid;rgb(0,0,0);0;000000ff';
				
				var todas_arr = todas.split(';');
				
				$(obj).css('border-top-width',null);
				$(obj).css('border-top-style',null);
				$(obj).css('border-top-color',null);
				$(obj).css('border-bottom-width',null);
				$(obj).css('border-bottom-style',null);
				$(obj).css('border-bottom-color',null);
				$(obj).css('border-left-width',null);
				$(obj).css('border-left-style',null);
				$(obj).css('border-left-color',null);
				$(obj).css('border-right-width',null);
				$(obj).css('border-right-style',null);
				$(obj).css('border-right-color',null);
				
				
				if($(obj).css('zIndex') == 'auto')$(obj).css('zIndex','1');
				
				$(obj).css('border',todas_arr[0]+'px '+todas_arr[1]+' '+todas_arr[2]);
				$(obj).css({
					'-webkit-border-radius' : todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px',
					'-moz-border-radius' : todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px',
					'border-radius' : todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px '+todas_arr[3]+'px'
				});
			break;
			case 'individual':
				var individual = $(obj).myAttr('data-bordas-individual');
				
				if(!individual)individual = '0;solid;rgb(0,0,0);0;000000ff:0;solid;rgb(0,0,0);0;000000ff:0;solid;rgb(0,0,0);0;000000ff:0;solid;rgb(0,0,0);0;000000ff';
				
				var individual_arr = individual.split(':');
				
				var todas_arr = individual_arr[0].split(';');
				var border = new Array();
				
				border[0] = todas_arr[3];
				
				$(obj).css('border',null);
				
				$(obj).css('border-top-width',todas_arr[0]+'px');
				$(obj).css('border-top-style',todas_arr[1]);
				$(obj).css('border-top-color',todas_arr[2]);
				
				todas_arr = individual_arr[1].split(';');
				border[1] = todas_arr[3];
				
				$(obj).css('border-bottom-width',todas_arr[0]+'px');
				$(obj).css('border-bottom-style',todas_arr[1]);
				$(obj).css('border-bottom-color',todas_arr[2]);
				
				todas_arr = individual_arr[2].split(';');
				border[2] = todas_arr[3];
				
				$(obj).css('border-left-width',todas_arr[0]+'px');
				$(obj).css('border-left-style',todas_arr[1]);
				$(obj).css('border-left-color',todas_arr[2]);
				
				todas_arr = individual_arr[3].split(';');
				border[3] = todas_arr[3];
				
				$(obj).css('border-right-width',todas_arr[0]+'px');
				$(obj).css('border-right-style',todas_arr[1]);
				$(obj).css('border-right-color',todas_arr[2]);
				
				$(obj).css('-webkit-border-radius',border[0]+'px '+border[1]+'px '+border[2]+'px '+border[3]+'px');
				$(obj).css('-moz-border-radius',border[0]+'px '+border[1]+'px '+border[2]+'px '+border[3]+'px');
				$(obj).css('border-radius',border[0]+'px '+border[1]+'px '+border[2]+'px '+border[3]+'px');
				if($(obj).css('zIndex') == 'auto')$(obj).css('zIndex','1');
			break;
		}
		
		selecionador_objetos_update();
	}
	
	function widget_aba_borda_individual(p){
		var individual = $(p.obj).myAttr('data-bordas-individual');
		var individual_saida = '';
		
		if(!individual)individual = '0;solid;rgb(0,0,0);0;000000ff:0;solid;rgb(0,0,0);0;000000ff:0;solid;rgb(0,0,0);0;000000ff:0;solid;rgb(0,0,0);0;000000ff';
		
		var individual_arr = individual.split(':');
		var individual_entrada_arr = new Array();
		var id = 0;
		
		switch(p.obj_id){
			case 'b2make-wcb-cima-espessura-val': id = 0; break;
			case 'b2make-wcb-baixo-espessura-val': id = 1; break;
			case 'b2make-wcb-esquerda-espessura-val': id = 2; break;
			case 'b2make-wcb-direita-espessura-val': id = 3; break;
			case 'b2make-wcb-cima-estilo-val': id = 0; break;
			case 'b2make-wcb-baixo-estilo-val': id = 1; break;
			case 'b2make-wcb-esquerda-estilo-val': id = 2; break;
			case 'b2make-wcb-direita-estilo-val': id = 3; break;
			case 'b2make-wcb-cima-cor-val': id = 0; break;
			case 'b2make-wcb-baixo-cor-val': id = 1; break;
			case 'b2make-wcb-esquerda-cor-val': id = 2; break;
			case 'b2make-wcb-direita-cor-val': id = 3; break;
			case 'b2make-wcb-individual-raio-topleft-val': id = 0; break;
			case 'b2make-wcb-individual-raio-topright-val': id = 1; break;
			case 'b2make-wcb-individual-raio-bottomleft-val': id = 2; break;
			case 'b2make-wcb-individual-raio-bottomright-val': id = 3; break;
		}
		
		if(individual_arr[id]){
			individual_entrada_arr = individual_arr[id].split(';');
		}
		
		for(var i=0;i<4;i++){
			var padrao = '';
			
			switch(i){
				case 0: padrao = '0'; break;
				case 1: padrao = 'solid'; break;
				case 2: padrao = 'rgb(0,0,0)'; break;
				case 3: padrao = '0'; break;
			}
			
			if(i == p.id_campo){
				individual_entrada_arr[i] = p.val;
			} else {
				individual_entrada_arr[i] = (individual_entrada_arr[i] ? individual_entrada_arr[i] : padrao);
			}
		}
		
		if(p.ahex){
			individual_entrada_arr[4] = p.ahex;
		} else {
			individual_entrada_arr[4] = (individual_entrada_arr[4] ? individual_entrada_arr[4] : '000000ff');
		}
		
		individual_saida = individual_entrada_arr[0]+';'+individual_entrada_arr[1]+';'+individual_entrada_arr[2]+';'+individual_entrada_arr[3]+';'+individual_entrada_arr[4];
		
		for(var i=0;i<4;i++){
			if(i == id){
				individual_arr[i] = individual_saida;
			} else {
				individual_arr[i] = (individual_arr[i] ? individual_arr[i] : '0;solid;rgb(0,0,0);0');
			}
		}
		
		individual_saida = individual_arr[0]+':'+individual_arr[1]+':'+individual_arr[2]+':'+individual_arr[3];
		
		$(p.obj).myAttr('data-bordas-individual',individual_saida);
	}
	
	function widget_aba_borda_todas(p){
		var todas = $(p.obj).myAttr('data-bordas-todas');
		var todas_saida = '';
		
		if(!todas)todas = '0;solid;rgb(0,0,0);0;000000ff';
		
		var todas_arr = todas.split(';');
		
		for(var i=0;i<4;i++){
			var padrao = '';
			
			switch(i){
				case 0: padrao = '0'; break;
				case 1: padrao = 'solid'; break;
				case 2: padrao = 'rgb(0,0,0)'; break;
				case 3: padrao = '0'; break;
			}
			
			if(i == p.id_campo){
				todas_arr[i] = p.val;
			} else {
				todas_arr[i] = (todas_arr[i] ? todas_arr[i] : padrao);
			}
		}
		
		if(p.ahex){
			todas_arr[4] = p.ahex;
		} else {
			todas_arr[4] = (todas_arr[4] ? todas_arr[4] : '000000ff');
		}
		
		todas_saida = todas_arr[0]+';'+todas_arr[1]+';'+todas_arr[2]+';'+todas_arr[3]+';'+todas_arr[4];
		
		$(p.obj).myAttr('data-bordas-todas',todas_saida);
	}
	
	function widget_aba_borda(){
		$('#b2make-wcb-conjuto').on('change',function(){
			var obj = b2make.conteiner_child_obj;
			var type = $(this).val();
			
			$(obj).myAttr('data-bordas-atual',type);
			
			switch(type){
				case 'todas':
					$('#b2make-wcb-conjuto-todas').show();
					$('#b2make-wcb-conjuto-individual').hide();
				break;
				case 'individual':
					$('#b2make-wcb-conjuto-todas').hide();
					$('#b2make-wcb-conjuto-individual').show();
				break;
			}
			
			widget_aba_borda_update();
		});
		
		$('.b2make-wcb-todas-espessura-val').on('keyup',function (e) {
			var obj = b2make.conteiner_child_obj;
			var id_campo = 0;
			var val = $(this).val();
			
			if(!val) val = 0;
			
			if(val > 300) val = 300;
			if(val < 0) val = 0;
			
			if(val != 0)$(this).val(val); else $(this).val('');
			
			switch($(this).myAttr('id')){
				case 'b2make-wcb-todas-espessura-val':
					widget_aba_borda_todas({
						val : val,
						id_campo : id_campo,
						obj : obj
					});
				break;
				case 'b2make-wcb-cima-espessura-val':
				case 'b2make-wcb-baixo-espessura-val':
				case 'b2make-wcb-esquerda-espessura-val':
				case 'b2make-wcb-direita-espessura-val':
					widget_aba_borda_individual({
						id_campo : id_campo,
						val : val,
						obj : obj,
						obj_id : $(this).myAttr('id')
					});
				break;
			}
			
			widget_aba_borda_update();
		});
		
		$('.b2make-wcb-todas-estilo-val').on('change',function (e) {
			var obj = b2make.conteiner_child_obj;
			var id_campo = 1;
			
			switch($(this).myAttr('id')){
				case 'b2make-wcb-todas-estilo-val':
					widget_aba_borda_todas({
						val : $(this).val(),
						id_campo : id_campo,
						obj : obj
					});
				break;
				case 'b2make-wcb-cima-estilo-val':
				case 'b2make-wcb-baixo-estilo-val':
				case 'b2make-wcb-esquerda-estilo-val':
				case 'b2make-wcb-direita-estilo-val':
					widget_aba_borda_individual({
						id_campo : id_campo,
						val : $(this).val(),
						obj : obj,
						obj_id : $(this).myAttr('id')
					});
				break;
			}
			
			widget_aba_borda_update();
		});
		
		$('#b2make-wcb-todas-cor-val,#b2make-wcb-cima-cor-val,#b2make-wcb-baixo-cor-val,#b2make-wcb-esquerda-cor-val,#b2make-wcb-direita-cor-val').on('changeColor',function (e) {
			var obj = b2make.conteiner_child_obj;
			var id_campo = 2;
			var val = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			
			switch($(this).myAttr('id')){
				case 'b2make-wcb-todas-cor-val':
					widget_aba_borda_todas({
						val : val,
						id_campo : id_campo,
						ahex : ahex,
						obj : obj
					});
				break;
				case 'b2make-wcb-cima-cor-val':
				case 'b2make-wcb-baixo-cor-val':
				case 'b2make-wcb-esquerda-cor-val':
				case 'b2make-wcb-direita-cor-val':
					widget_aba_borda_individual({
						id_campo : id_campo,
						val : val,
						ahex : ahex,
						obj : obj,
						obj_id : $(this).myAttr('id')
					});
				break;
			}
			
			widget_aba_borda_update();
		});
		
		$('.b2make-wcb-todas-raio-val').on('keyup',function (e) {
			var obj = b2make.conteiner_child_obj;
			var id_campo = 3;
			var val = $(this).val();
			
			if(!val) val = 0;
			
			if(val > 300) val = 300;
			if(val < 0) val = 0;
			
			if(val != 0)$(this).val(val); else $(this).val('');
			
			switch($(this).myAttr('id')){
				case 'b2make-wcb-todas-raio-val':
					widget_aba_borda_todas({
						val : val,
						id_campo : id_campo,
						obj : obj
					});
				break;
				case 'b2make-wcb-individual-raio-topleft-val':
				case 'b2make-wcb-individual-raio-topright-val':
				case 'b2make-wcb-individual-raio-bottomleft-val':
				case 'b2make-wcb-individual-raio-bottomright-val':
					widget_aba_borda_individual({
						id_campo : id_campo,
						val : val,
						obj : obj,
						obj_id : $(this).myAttr('id')
					});
				break;
			}
			
			widget_aba_borda_update();
		});
	}
	
	widget_aba_borda();
	
	function google_fonts(){
		if(!b2make.google_fonts){
			$.ajax({
				dataType: "json",
				url: 'webfonts/webfonts.js?v=2',
				data: { 
					
				},
				beforeSend: function(){
					$.carregamento_open();
				},
				success: function(txt){
					var ul = $('<ul class="b2make-wot-google-fontes"></ul>');
					var value_por_coluna = Math.floor(txt.length/3);
					
					b2make.google_fonts_collection = txt;
					
					for(var i=0;i<txt.length;i++){
						var variants = '';
						for(var j=0;j<txt[i].variants.length;j++){
							variants = (variants ? ',' : '') + txt[i].variants[j];
						}
						
						var li = $('<li data-font-family="'+txt[i].family+'" data-font-variants="'+variants+'">'+txt[i].family+'</li>');
						li.appendTo(ul);
						
						if(i>0 && i%value_por_coluna == 0){
							ul.clone().appendTo('#b2make-wot-google-fontes');
							ul.appendTo('.b2make-fonts-google-fontes');
							ul = $('<ul class="b2make-wot-google-fontes"></ul>')
						}
					}
					
					if(ul.find('li').length > 0){
						ul.clone().appendTo('#b2make-wot-google-fontes');
						ul.appendTo('.b2make-fonts-google-fontes');
					}
					
					$('#b2make-wot-fontes li,.b2make-fonts-list li,.b2make-wot-google-fontes li').each(function(){
						if(b2make.google_font_first_font == $(this).html()){
							$(this).addClass('b2make-wot-fonte-clicked');
						} else {
							$(this).removeClass('b2make-wot-fonte-clicked');
						}
					});
					
					b2make.google_fonts = true;
					$.carregamento_close();
				},
				error: function(txt){
					console.log(txt.responseText);
					$.carregamento_close();
				}
			});
		}
	}
	
	function fonts_close(){
		if(b2make.fonts_open){
			b2make.fonts_open = false;
			$('.b2make-fonts-count').hide();
		}
	}
	
	$.fonts_load = function(p){
		$(p.obj).find('.b2make-fonts-instance').each(function(){
			
			var options = $(this).myAttr('data-options');
			
			if(options){
				var options_arr = options.split(',');
				
				for(var i=0;i<options_arr.length;i++){
					switch(options_arr[i]){
						case 'font-select':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-holder').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-count').clone().appendTo($(this));
						break;
						case 'font-size':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-size').clone().appendTo($(this));
						break;
						case 'font-negrito':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-negrito').clone().appendTo($(this));
						break;
						case 'font-italico':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-italico').clone().appendTo($(this));
						break;
						case 'font-align':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-left').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-center').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-right').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-justify').clone().appendTo($(this));
						break;
						
					}
				}
			} else {
				$(this).html($('#b2make-fonts-conteiner').html());
			}
		});
	}
	
	function fonts(){
		if(!b2make.msgs.googleFontsInative)b2make.msgs.googleFontsInative = 'Esta fonte est&aacute; inativa, escolha outra!';
		
		$('.b2make-fonts-instance').each(function(){
			var options = $(this).myAttr('data-options');
			
			if(options){
				var options_arr = options.split(',');
				
				for(var i=0;i<options_arr.length;i++){
					switch(options_arr[i]){
						case 'font-select':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-holder').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-count').clone().appendTo($(this));
						break;
						case 'font-size':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-size').clone().appendTo($(this));
						break;
						case 'font-negrito':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-negrito').clone().appendTo($(this));
						break;
						case 'font-italico':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-italico').clone().appendTo($(this));
						break;
						case 'font-align':
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-left').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-center').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-right').clone().appendTo($(this));
							$('#b2make-fonts-conteiner').find('.b2make-fonts-align-justify').clone().appendTo($(this));
						break;
						
					}
				}
			} else {
				$(this).html($('#b2make-fonts-conteiner').html());
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-holder',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			
			var pai = $(this).parent();
			var fonte = $(this).html();
			
			if(pai.myAttr('id') != b2make.fonts_pai_atual){
				b2make.fonts_open = false;
				$('.b2make-fonts-count').hide();
			}
			
			b2make.fonts_pai_atual = pai.myAttr('id');
			
			if(!b2make.fonts_open){
				google_fonts();
				pai.find('.b2make-fonts-count').show();
				b2make.fonts_open = true;
				
				if(b2make.google_fonts){
					$('.b2make-fonts-list li,.b2make-wot-google-fontes li').each(function(){
						if(fonte == $(this).html()){
							$(this).addClass('b2make-wot-fonte-clicked');
						} else {
							$(this).removeClass('b2make-wot-fonte-clicked');
						}
					});
				} else {
					b2make.google_font_first_font = fonte;
				}
				
				pai.find('.b2make-fonts-count-teste').css({
					'fontFamily': $(this).css('fontFamily')
				});
			} else {
				pai.find('.b2make-fonts-count').hide();
				b2make.fonts_open = false;
			}
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-list li',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this).parent().parent().parent();
			e.stopPropagation();
			
			$('.b2make-fonts-list li,.b2make-wot-google-fontes li').each(function(){
				$(this).removeClass('b2make-wot-fonte-clicked');
			});
			
			$(this).addClass('b2make-wot-fonte-clicked');
			
			obj.find('.b2make-fonts-holder,.b2make-fonts-count-teste').css({
				'fontFamily': $(this).css('fontFamily')
			});
			obj.find('.b2make-fonts-holder').html($(this).css('fontFamily').replace(/'/gi,''));
			
			obj.myAttr('data-font-family',$(this).css('fontFamily').replace(/'/gi,''));
			obj.myAttr('data-google-font','nao');
			obj.trigger('changeFontFamily');
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-count .b2make-wot-google-fontes li',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this).parent().parent().parent().parent();
			e.stopPropagation();
			
			$('.b2make-fonts-list li,.b2make-wot-google-fontes li').each(function(){
				$(this).removeClass('b2make-wot-fonte-clicked');
			});
			
			$(this).addClass('b2make-wot-fonte-clicked');
			
			obj.find('.b2make-fonts-holder').css({
				'fontFamily': $(this).myAttr('data-font-family')
			});
			obj.find('.b2make-fonts-count').find('.b2make-fonts-count-teste').css({
				'fontFamily': $(this).myAttr('data-font-family')
			});
			obj.find('.b2make-fonts-holder').html($(this).myAttr('data-font-family').replace(/'/gi,''));
			
			obj.myAttr('data-font-family',$(this).myAttr('data-font-family').replace(/'/gi,''));
			obj.myAttr('data-google-font','sim');
			obj.trigger('changeFontFamily');
			
			$.google_fonts_wot_load({
				family : $(this).myAttr('data-font-family')
			});
		});
		
		$(document.body).on('keyup','.b2make-fonts-size',function(e) {
			var obj = $(this).parent();
			var value = parseInt(this.value);
			
			if(value > b2make.wot_font_max_value){
				this.value = b2make.wot_font_max_value;
				value = b2make.wot_font_max_value;
			}
			
			if(value < b2make.wot_font_min_value){
				value = b2make.wot_font_min_value;
			}
			
			obj.myAttr('data-font-size',value);
			obj.trigger('changeFontSize');
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-negrito',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = $(this).parent();
			
			if(obj.myAttr('data-font-negrito') == 'sim'){
				obj.myAttr('data-font-negrito','nao');
			} else {
				obj.myAttr('data-font-negrito','sim');
			}
			
			obj.trigger('changeFontNegrito');
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-italico',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = $(this).parent();
			
			if(obj.myAttr('data-font-italico') == 'sim'){
				obj.myAttr('data-font-italico','nao');
			} else {
				obj.myAttr('data-font-italico','sim');
			}
			
			obj.trigger('changeFontItalico');
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-align',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = $(this).parent();
			var pos = $(this).myAttr('data-id');
			
			obj.myAttr('data-font-align',pos);
			obj.trigger('changeFontAlign');
		});
		
	}
	
	fonts();
	
	$.bordas_menu_open = function(p){
		var obj = (p.target ? p.target : b2make.conteiner_child_obj);
		var borda_name = 'data-bordas-todas';
		
		if($(p.obj).myAttr('data-borda-name')){
			borda_name = $(p.obj).myAttr('data-borda-name');
		}
		
		var todas = $(obj).myAttr(borda_name);
		var todas_saida = '';
		
		if(!todas)todas = '0;solid;rgb(0,0,0);0;000000ff';
		
		var todas_arr = todas.split(';');
		
		for(var i=0;i<4;i++){
			var padrao = '';
			
			switch(i){
				case 0: padrao = '0'; break;
				case 1: padrao = 'solid'; break;
				case 2: padrao = 'rgb(0,0,0)'; break;
				case 3: padrao = '0'; break;
			}
			
			if(i == p.id_campo){
				todas_arr[i] = p.val;
			} else {
				todas_arr[i] = (todas_arr[i] ? todas_arr[i] : padrao);
			}
		}
		
		if(p.ahex){
			todas_arr[4] = p.ahex;
		} else {
			todas_arr[4] = (todas_arr[4] ? todas_arr[4] : '000000ff');
		}
		
		$(p.obj).find('.b2make-bordas-espessura-val').val(todas_arr[0]);
		
		var option = $(p.obj).find('.b2make-bordas-estilo-val').find("[value='" + todas_arr[1] + "']");
		option.prop('selected', 'selected');
		
		$(p.obj).find('.b2make-bordas-cor-val').css('background-color',todas_arr[2]);
		$(p.obj).find('.b2make-bordas-cor-val').myAttr('data-ahex',todas_arr[4]);
		
		$(p.obj).find('.b2make-bordas-raio-val').val(todas_arr[3]);
		
		todas_saida = todas_arr[0]+';'+todas_arr[1]+';'+todas_arr[2]+';'+todas_arr[3]+';'+todas_arr[4];
		
		$(p.obj).myAttr(borda_name,todas_saida);
	}
	
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
	
	function bordas_valores(p){
		var obj = b2make.conteiner_child_obj;
		var borda_name = 'data-bordas-todas';
		
		if($(p.obj).myAttr('data-borda-name')){
			borda_name = $(p.obj).myAttr('data-borda-name');
		}
		
		var todas = $(p.obj).myAttr(borda_name);
		var todas_saida = '';
		
		if(!todas)todas = '0;solid;rgb(0,0,0);0;000000ff';
		
		var todas_arr = todas.split(';');
		
		for(var i=0;i<4;i++){
			var padrao = '';
			
			switch(i){
				case 0: padrao = '0'; break;
				case 1: padrao = 'solid'; break;
				case 2: padrao = 'rgb(0,0,0)'; break;
				case 3: padrao = '0'; break;
			}
			
			if(i == p.id_campo){
				todas_arr[i] = p.val;
			} else {
				todas_arr[i] = (todas_arr[i] ? todas_arr[i] : padrao);
			}
		}
		
		if(p.ahex){
			todas_arr[4] = p.ahex;
		} else {
			todas_arr[4] = (todas_arr[4] ? todas_arr[4] : '000000ff');
		}
		
		todas_saida = todas_arr[0]+';'+todas_arr[1]+';'+todas_arr[2]+';'+todas_arr[3]+';'+todas_arr[4];
		
		$(p.obj).myAttr(borda_name,todas_saida);
		$(obj).myAttr(borda_name,todas_saida);
	}
	
	$.bordas_manual_start = function(selector){
		var obj = $(selector);
		
		var options = obj.myAttr('data-options');
		
		if(options){
			var options_arr = options.split(',');
			
			for(var i=0;i<options_arr.length;i++){
				switch(options_arr[i]){
					case 'espessura':
						$('#b2make-bordas-conteiner').find('.b2make-bordas-espessura-lbl').clone().appendTo(obj);
						$('#b2make-bordas-conteiner').find('.b2make-bordas-espessura-val').clone().appendTo(obj);
					break;
					case 'estilo':
						$('#b2make-bordas-conteiner').find('.b2make-bordas-estilo-lbl').clone().appendTo(obj);
						$('#b2make-bordas-conteiner').find('.b2make-bordas-estilo-val').clone().appendTo(obj);
					break;
					case 'cor':
						$('#b2make-bordas-conteiner').find('.b2make-bordas-cor-lbl').clone().appendTo(obj);
						$('#b2make-bordas-conteiner').find('.b2make-bordas-cor-val').clone().appendTo(obj);
					break;
					case 'raio':
						$('#b2make-bordas-conteiner').find('.b2make-bordas-raio-lbl').clone().appendTo(obj);
						$('#b2make-bordas-conteiner').find('.b2make-bordas-raio-val').clone().appendTo(obj);
					break;
					
				}
			}
		} else {
			obj.html($('#b2make-bordas-conteiner').html());
		}
	}
	
	function bordas(){
		$('.b2make-bordas-instance').each(function(){
			var options = $(this).myAttr('data-options');
			
			if(options){
				var options_arr = options.split(',');
				
				for(var i=0;i<options_arr.length;i++){
					switch(options_arr[i]){
						case 'espessura':
							$('#b2make-bordas-conteiner').find('.b2make-bordas-espessura-lbl').clone().appendTo($(this));
							$('#b2make-bordas-conteiner').find('.b2make-bordas-espessura-val').clone().appendTo($(this));
						break;
						case 'estilo':
							$('#b2make-bordas-conteiner').find('.b2make-bordas-estilo-lbl').clone().appendTo($(this));
							$('#b2make-bordas-conteiner').find('.b2make-bordas-estilo-val').clone().appendTo($(this));
						break;
						case 'cor':
							$('#b2make-bordas-conteiner').find('.b2make-bordas-cor-lbl').clone().appendTo($(this));
							$('#b2make-bordas-conteiner').find('.b2make-bordas-cor-val').clone().appendTo($(this));
						break;
						case 'raio':
							$('#b2make-bordas-conteiner').find('.b2make-bordas-raio-lbl').clone().appendTo($(this));
							$('#b2make-bordas-conteiner').find('.b2make-bordas-raio-val').clone().appendTo($(this));
						break;
						
					}
				}
			} else {
				$(this).html($('#b2make-bordas-conteiner').html());
			}
		});
		
		$(document.body).on('keyup','.b2make-bordas-espessura-val',function (e) {
			var obj = $(this).parent();
			var id_campo = 0;
			var val = $(this).val();
			
			if(!val) val = 0;
			
			if(val > 300) val = 300;
			if(val < 0) val = 0;
			
			$(this).val(val);
			
			bordas_valores({
				val : val,
				id_campo : id_campo,
				obj : obj
			});
			
			obj.trigger('changeBorda');
		});
		
		$(document.body).on('change','.b2make-bordas-estilo-val',function (e) {
			var obj = $(this).parent();
			var val = $(this).val();
			var id_campo = 1;
			
			bordas_valores({
				val : val,
				id_campo : id_campo,
				obj : obj
			});
			
			obj.trigger('changeBorda');
		});
		
		$(document.body).on('changeColor','.b2make-bordas-cor-val',function (e) {
			var obj = $(this).parent();
			var id_campo = 2;
			var val = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			
			bordas_valores({
				val : val,
				id_campo : id_campo,
				ahex : ahex,
				obj : obj
			});
			
			obj.trigger('changeBorda');
		});
		
		$(document.body).on('keyup','.b2make-bordas-raio-val',function (e) {
			var obj = $(this).parent();
			var id_campo = 3;
			var val = $(this).val();
			
			if(!val) val = 0;
			
			if(val > 300) val = 300;
			if(val < 0) val = 0;
			
			$(this).val(val);
			
			bordas_valores({
				val : val,
				id_campo : id_campo,
				obj : obj
			});
			
			obj.trigger('changeBorda');
		});
	}
	
	bordas();
	
	$.jpicker_ahex_2_rgba = function(ahex){
		var rgba = $.jPicker.ColorMethods.hexToRgba(ahex);
		
		return 'rgba('+rgba.r+','+rgba.g+','+rgba.b+','+(rgba.a/255).toFixed(1)+')';
	}
	
	function jpicker_open(obj){
		var position = $(obj).myAttr('data-position');
		var obj_target = $(obj).myAttr('data-obj-target');
		var obj_holder = $(obj).myAttr('data-obj-holder');
		var obj_callback = $(obj).myAttr('data-obj-callback');
		var obj_parent_callback = $(obj).myAttr('data-parent-callback');
		var css_property = $(obj).myAttr('data-css-property');
		var position_specific = $(obj).myAttr('data-position-specific');
		var ahex = $(obj).myAttr('data-ahex');
		var left = 0;
		
		b2make.jpicker_clicked = true;
		b2make.jpicker = {
			obj : obj,
			obj_parent_callback : obj_parent_callback,
			css_property : css_property,
			obj_callback : obj_callback,
			obj_holder : obj_holder,
			obj_target : obj_target
		};
		
		switch(position){
			case 'middle':
				left = $('#b2make-jpicker-conteiner div.jPicker').width() / 2 - $(obj).width() / 2;
			break;
			case 'right':
				left = $('#b2make-jpicker-conteiner div.jPicker').width() - $(obj).width();
			break;
		}
		
		if(position_specific){
			left = position_specific;
		}
		
		if(ahex){
			$.jPicker.List[18].color.active.val('ahex',ahex);
		} else {
			$.jPicker.List[18].color.active.val('ahex','000000ff');
		}
		
		$('#b2make-jpicker-conteiner div.jPicker').css('top',($(obj).offset().top ));
		$('#b2make-jpicker-conteiner div.jPicker').css('left',($(obj).offset().left));
		$('#b2make-jpicker-conteiner').find('span.jPicker').find('span.Icon').find('span.Image:first').trigger('click');
	}
	
	$.jpicker_load = function(p){
		$(p.obj).find('.b2make-jpicker').each(function(){
			$(this).addClass('b2make-tooltip');
			if(!$(this).myAttr('title'))$(this).myAttr('title',b2make.msgs.jpickerTitle);
		});
	}
	
	function jpicker(){
		if(!b2make.msgs.jpickerTitle)b2make.msgs.jpickerTitle = 'Clique para mudar a cor do objeto desejado';
		if(!b2make.msgs.jpickerWindowTitle)b2make.msgs.jpickerWindowTitle = 'Selecione a Cor Desejada';
		
		b2make.jpicker = {};
		
		$('.b2make-jpicker').each(function(){
			$(this).addClass('b2make-tooltip');
			if(!$(this).myAttr('title'))$(this).myAttr('title',b2make.msgs.jpickerTitle);
		});
		
		var left_jpicker = $('#b2make-jpicker-conteiner').offset().left+'px';
		
		b2make.msgs.jpickerLocalization.tooltips.picker_open = b2make.msgs.jpickerTitle;
		$('#b2make-jpicker-widget').jPicker({window:{element:'#b2make-jpicker-conteiner',zIndex:120,effects:{type:"fade",speed:{show:b2make.fade_time,hide:b2make.fade_time}},position:{x:left_jpicker,y:'28px'},expandable:true,title:b2make.msgs.jpickerWindowTitle,alphaSupport:true},color:{active:new $.jPicker.Color({ hex: '000000', a:255 })},localization:b2make.msgs.jpickerLocalization});
		
		$(document.body).on('mouseup tap','.b2make-jpicker',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).addClass('b2make-tooltip');
			jpicker_open(this);
		});
		
		$(document.body).on('mouseup tap','input.Ok',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var all;
			var css_property = (b2make.jpicker.css_property ? b2make.jpicker.css_property : 'background-color');
			
			if(b2make.jpicker_clicked){
				all = $.jPicker.List[18].color.active.val('all');
				
				if(all){
					$(b2make.jpicker.obj).css({'background-color' : 'rgba('+all.r+','+all.g+','+all.b+','+(all.a/255).toFixed(1)+')'});
				} else {
					$(b2make.jpicker.obj).css({'background-color' : 'rgb(0,0,0)'});
				}
				
				if(all){
					$(b2make.jpicker.obj).myAttr('data-ahex',all.ahex);
				} else {
					$(b2make.jpicker.obj).myAttr('data-ahex','000000ff');
				}
				
				if(b2make.jpicker.obj_target){
					if(all){
						$(b2make.jpicker.obj_target).css({css_property : 'rgba('+all.r+','+all.g+','+all.b+','+(all.a/255).toFixed(1)+')'});
					} else {
						$(b2make.jpicker.obj_target).css({css_property : 'rgb(0,0,0)'});
					}
				}
				
				if(b2make.jpicker.obj_holder){
					if(all){
						$(b2make.jpicker.obj_holder).myAttr('data-color-ahex',all.ahex);
					} else {
						$(b2make.jpicker.obj_holder).myAttr('data-color-ahex',false);
					}
				}
				
				if(b2make.jpicker.obj_callback){
					$(b2make.jpicker.obj_callback).trigger('changeColor');
				}
				
				if(b2make.jpicker.obj_parent_callback){
					$(b2make.jpicker.obj).trigger('changeColor');
				}
				
				b2make.jpicker_clicked = false;
			}
		});
	}
	
	jpicker();
	
	function pagina_menu_bolinhas_data_update(id,valor){
		var layout = pagina_options_valor('data-pagina-menu-bolinhas-layout');
		var layout_vars = layout.split('|');
		var layout_saida = '';
		var aux = '';
		
		for(var i=0;i<layout_vars.length;i++){
			if(id == i){
				aux = valor;
			} else {
				aux = layout_vars[i];
			}
			
			layout_saida = layout_saida + (layout_saida ? '|' : '') + aux;
		}
		
		pagina_options_change('data-pagina-menu-bolinhas-layout',layout_saida);
		
		var val = layout_saida;
		var opcao = 'pagina-menu-bolinhas-layout';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				val : val
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
	
	function pagina_menu_bolinhas_areas_update(){
		var holder = $('#b2make-widget-sub-options-menu-bolinhas-areas');
		
		holder.html('<div id="b2make-wsomb-title">'+b2make.msgs.paginaMenuBolinhasTitle+'</div><div id="b2make-wsomba-cont"></div>');
		
		holder = holder.find('#b2make-wsomba-cont');
		
		$(b2make.widget).each(function(){
			if($(this).myAttr('data-type') == 'conteiner'){
				var menu_opcoes = $('<div class="b2make-wsomb-conteiner"></div>');
				var input = $('<input class="b2make-wsomb-input" type="checkbox" value="'+$(this).myAttr('id')+'">');
				var lbl = $('<div class="b2make-wsomb-lbl">'+($(this).myAttr('data-name') ? $(this).myAttr('data-name') : $(this).myAttr('id'))+'</div>');
				
				input.appendTo(menu_opcoes);
				lbl.appendTo(menu_opcoes);
				menu_opcoes.appendTo(holder);
			}
		});
	}
	
	function pagina_menu_bolinhas_areas_open(){
		var areas = pagina_options_valor('data-pagina-menu-bolinhas-areas');
		var areas_arr = new Array();
		var areas_saida = '';
		
		if(areas)
			areas_arr = areas.split(',');
		
		$('.b2make-wsomb-conteiner').each(function(){
			var input = $(this).find('.b2make-wsomb-input');
			var checked = input.prop("checked");
			var id = input.val();
			var found = false;
			
			for(var j=0;j<areas_arr.length;j++){
				var area = areas_arr[j];
				if(area == id){
					input.prop("checked",false);
					found = true;
					break;
				}
			}
			
			if(!found){
				input.prop("checked",true);
			}
		});
	}
	
	function pagina_menu_bolinhas_areas_check(input){
		var id = input.val();
		var checked = input.prop("checked");
		var areas = pagina_options_valor('data-pagina-menu-bolinhas-areas');
		var areas_arr = new Array();
		var areas_saida = '';
		
		if(areas)
			areas_arr = areas.split(',');

		var found = false;
		
		for(var j=0;j<areas_arr.length;j++){
			var area = areas_arr[j];
			if(area == id){
				found = true;
				if(!checked){
					areas_saida = areas_saida + (areas_saida?',':'') + area;
				}
			} else {
				areas_saida = areas_saida + (areas_saida?',':'') + area;
			}
		}
		
		if(!found && !checked){
			areas_saida = areas_saida + (areas_saida?',':'') + id;
		}
		
		if(areas_saida)
			pagina_options_change('data-pagina-menu-bolinhas-areas',areas_saida);
		else
			pagina_options_change('data-pagina-menu-bolinhas-areas',null);
		
		var val = areas_saida;
		var opcao = 'pagina-menu-bolinhas-areas';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				val : val
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
	
	function favicon(){
		var fav = $('#b2make-page-options-favicon');
		var pagina_favicon = variaveis_js.pagina_favicon;
		var pagina_favicon_version = variaveis_js.pagina_favicon_version;
		
		if(pagina_favicon){
			fav.css('background-image','url("'+pagina_favicon+(pagina_favicon_version ? '?v='+pagina_favicon_version : '')+'")');
		} else {
			fav.css('background-image','url("images/b2make-favicon-original.jpg")');
		}
		
		fav.on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.widget_sub_options_type = 'favicon';
			b2make.widget_edit_sub_options_open = true;
			$.widget_sub_options_open(); 
		});
	}
	
	function pagina_options_change(variavel,valor){
		$('#b2make-pagina-options').myAttr(variavel,valor);
	}
	
	function pagina_options_valor(variavel){
		return ($('#b2make-pagina-options').myAttr(variavel) ? $('#b2make-pagina-options').myAttr(variavel) : false);
	}
	
	function pagina_options(){
		favicon();
		
		if($('#b2make-pagina-options').length == 0){
			var pagina_options = $('<div id="b2make-pagina-options"></div>');
			pagina_options.appendTo('#b2make-site');
		}
		
		var grid = localStorage.getItem('b2make-grade');
		
		if(grid){
			var option = $('#b2make-page-options-grid').find("[value='" + grid + "']");
			option.prop('selected', 'selected');
		}
		
		if(variaveis_js.pagina_parallax){
			pagina_options_change('data-pagina-parallax',variaveis_js.pagina_parallax);
			var option = $('#b2make-page-options-parallax').find("[value='" + variaveis_js.pagina_parallax + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-page-options-parallax').find(":first");
			option.prop('selected', 'selected');
		}
		
		if(variaveis_js.pagina_menu_bolinhas){
			pagina_options_change('data-pagina-menu-bolinha',variaveis_js.pagina_menu_bolinhas);
			var option = $('#b2make-page-options-menu-bolinha').find("[value='" + variaveis_js.pagina_menu_bolinhas + "']");
			option.prop('selected', 'selected');
		} else {
			var option = $('#b2make-page-options-menu-bolinha').find(":first");
			option.prop('selected', 'selected');
		}
		
		if(variaveis_js.pagina_menu_bolinhas_areas){
			pagina_options_change('data-pagina-menu-bolinhas-areas',variaveis_js.pagina_menu_bolinhas_areas);
		}
		
		if(variaveis_js.pagina_menu_bolinhas_layout){
			pagina_options_change('data-pagina-menu-bolinhas-layout',variaveis_js.pagina_menu_bolinhas_layout);
		} else {
			var layout_start = '14|1;solid;rgb(153,152,157);14;99989dff|ffffffff|1;solid;rgb(0,0,0);14;000000ff|000000ff';
			pagina_options_change('data-pagina-menu-bolinhas-layout',layout_start);
		}
		
		var layout_inicial = pagina_options_valor('data-pagina-menu-bolinhas-layout');
		
		var layout_vars = layout_inicial.split('|');
		
		$('#b2make-wsombl-tamanho-val').val(layout_vars[0]);
		
		$('#b2make-wsombl-bordas-cont').myAttr('data-borda-caixa',layout_vars[1]);
		
		$.bordas_menu_open({
			target : $('#b2make-wsombl-bordas-cont'),
			obj : $('#b2make-wsombl-bordas-cont')
		});
		
		$('#b2make-wsombl-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba(layout_vars[2]));
		$('#b2make-wsombl-caixa-cor-val').myAttr('data-ahex',layout_vars[2]);
		
		$('#b2make-wsombl-bordas-foco-cont').myAttr('data-borda-foco-caixa',layout_vars[3]);
		
		$.bordas_menu_open({
			target : $('#b2make-wsombl-bordas-foco-cont'),
			obj : $('#b2make-wsombl-bordas-foco-cont')
		});
		
		$('#b2make-wsombl-caixa-cor-foco-val').css('background-color',$.jpicker_ahex_2_rgba(layout_vars[4]));
		$('#b2make-wsombl-caixa-cor-foco-val').myAttr('data-ahex',layout_vars[4]);
		
		var layout = pagina_options_valor('data-pagina-menu-bolinhas-layout');
		var focus = $('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha-focus');
		var normal = $('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha');
		
		var layout_vars = layout.split('|');
		var w = layout_vars[0];
		
		focus.css('width',w+'px');
		normal.css('width',w+'px');
		focus.css('height',w+'px');
		normal.css('height',w+'px');
		focus.css('margin',(w/2)+'px');
		normal.css('margin',(w/2)+'px');
		
		normal.css('background-color',$.jpicker_ahex_2_rgba(layout_vars[2]));
		focus.css('background-color',$.jpicker_ahex_2_rgba(layout_vars[4]));
		
		$.bordas_update({
			obj : $('#b2make-wsombl-bordas-cont'),
			target : normal
		});
		$.bordas_update({
			obj : $('#b2make-wsombl-bordas-foco-cont'),
			target : focus
		});
		
		$('#b2make-page-options-parallax').on('change',function(){
			var val = $(this).val();
			var opcao = 'pagina-parallax';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					val : val
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
								pagina_options_change('data-'+opcao,val);
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
		});
		
		$('#b2make-page-options-grid').on('change',function(){
			var val = $(this).val();
			
			if(val == 's'){
				$('#b2make-grade').show();
			} else {
				$('#b2make-grade').hide();
			}
			
			localStorage.setItem('b2make-grade',val);
		});
		
		$('#b2make-page-options-menu-bolinha').on('change',function(){
			var val = $(this).val();
			var opcao = 'pagina-menu-bolinha';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					val : val
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
								pagina_options_change('data-'+opcao,val);
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
		});
		
		$(document.body).on('mouseup tap','.b2make-wsomb-lbl',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var input = $(this).parent().find('.b2make-wsomb-input');
			
			input.prop("checked", !input.prop("checked"));
			
			pagina_menu_bolinhas_areas_check(input);
		});
		
		$(document.body).on('change','.b2make-wsomb-input',function(e){
			pagina_menu_bolinhas_areas_check($(this));
		});
		
		$('#b2make-wsombl-bordas-cont').on('changeBorda',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			
			target = $('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha');
			
			$.bordas_update({
				obj : this,
				target : target
			});
			
			pagina_menu_bolinhas_data_update(1,$(this).myAttr('data-borda-caixa'));
		});
		
		$('#b2make-wsombl-bordas-foco-cont').on('changeBorda',function(e){
			var obj = b2make.conteiner_child_obj;
			var target;
			
			target = $('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha-focus');
			
			$.bordas_update({
				obj : this,
				target : target
			});
			
			pagina_menu_bolinhas_data_update(3,$(this).myAttr('data-borda-foco-caixa'));
		});
		
		$('#b2make-wsombl-caixa-cor-val,#b2make-wsombl-caixa-cor-foco-val').on('changeColor',function(e){
			var id = $(this).myAttr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
			
			switch(id){
				case 'b2make-wsombl-caixa-cor-val':
					$('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha').css('background-color',bg);
					pagina_menu_bolinhas_data_update(2,ahex);
				break;
				case 'b2make-wsombl-caixa-cor-foco-val':
					$('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha-focus').css('background-color',bg);
					pagina_menu_bolinhas_data_update(4,ahex);
				break;
				
			}
		});
		
		$('#b2make-wsombl-tamanho-val').keyup(function (e) {
			var tamanho = parseInt(this.value);
			
			if(tamanho > 100){
				this.value = 100;
				tamanho = 100;
			}
			
			if(tamanho < 0){
				this.value = '';
				tamanho = '0';
			}
			
			if(!tamanho){
				tamanho = '0';
			}
			
			var focus = $('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha-focus');
			var normal = $('#b2make-wsombl-preview').find('.b2make-wsombl-preview-bolinha');
			
			var w = tamanho;
			
			focus.css('width',w+'px');
			normal.css('width',w+'px');
			focus.css('height',w+'px');
			normal.css('height',w+'px');
			focus.css('margin',(w/2)+'px');
			normal.css('margin',(w/2)+'px');
			
			pagina_menu_bolinhas_data_update(0,tamanho);
		});
	}
	
	pagina_options();
	
	function history_forward(){
		b2make.history.count++;
		if(b2make.history.back)b2make.history.count++;
		
		if(b2make.history.count > b2make.history.max){
			b2make.history.count = 0;
		}
		
		if(b2make.history.dados[b2make.history.count]){
			history_change_page(b2make.history.count);
		} else {
			b2make.history.count--;
		}
		
		b2make.history.forward = true;
		b2make.history.back = false;
	}
	
	function history_back(){
		if(!b2make.history.back)history_add(null);
		if(b2make.history.forward)b2make.history.count--;
		
		history_change_page(b2make.history.count);
		
		b2make.history.count--;
		
		if(b2make.history.count < 0){
			b2make.history.count = 0;
		}
		
		b2make.history.forward = false;
		b2make.history.back = true;
	}
	
	function history_change_page(num){
		close_all();
		
		if(b2make.history.dados[num]){
			var page = b2make.history.dados[num].page;
			var local = b2make.history.dados[num].local;
			var vars = b2make.history.dados[num].vars;
			
			var history = $('#b2make-history');
			
			history.html(page);
			
			history.find('#b2make-widget-conteiner-mask').remove();
			history.find('#b2make-shadow').remove();
			
			history.find('.b2make-widget[data-type="conteiner"]').css('border','none');
			history.find('.b2make-widget[data-type="conteiner-area"]').css('border','none');
			
			history.find('.b2make-widget[data-type="conteiner"]').each(function(){
				$(this).css('border','none');
				
				var position = 'relative'; if($(this).myAttr('data-position')) position = $(obj).myAttr('data-position');
				
				if(position == 'relative'){
					$(this).css('top','0px');
					$(this).css('left','0px');
					$(this).css('width','100%');
				}
				
				$(this).css('position',position);
			});
			
			$('#b2make-site').html(history.html());
			
			switch(local){
				case 'conteiner_add':
				case 'conteiner_del':
					b2make.conteiner_total = vars.conteiner_total;
				break;
			}
			
			console.log(b2make.conteiner_total);
		}
	}
	
	function history_add(p){
		if(!p)p = {};
		return;
		/* b2make.history.count++;
		
		if(b2make.history.count > b2make.history.max){
			b2make.history.count = 0;
		}
		
		var history = $('#b2make-history');
		
		if(history.length == 0){
			history = $('<div id="b2make-history"></div>');
			history.appendTo('body');
			history.hide();
		}
		
		history.html($('#b2make-site').html());
		
		b2make.history.dados[b2make.history.count] = {
			page : history.html(),
			local : p.local,
			vars : p.vars
		}; */
	}
	
	function history(){
		b2make.history = {};
		
		b2make.history.widgets_count = b2make.widgets_count;
		b2make.history.count = 0;
		b2make.history.max = 100;
		b2make.history.dados = new Array();
		b2make.history.delay_time = 200;
		
		/* 
		$(window).bind('keydown',function(e) {
			if(e.ctrlKey || e.metaKey){
				if(e.which === 90){ // CTRL + Z
					history_back();
					e.preventDefault();
					return false;
				}
			}
			if(e.ctrlKey || e.metaKey){
				if(e.which === 89){ // CTRL + Y
					history_forward();
					e.preventDefault();
					return false;
				}
			}
			
		}); */
		
		// seleciona o nó alvo
		var target = document.querySelector('#b2make-site');
		
		// cria uma nova instância de observador
		var observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				switch(mutation.type){
					case 'childList':
						if(mutation.addedNodes.length > 0){
							var node = mutation.addedNodes[0];
							
							if($(node).hasClass('b2make-library-loading')){
								$(node).height($(node).parent().height());
								
							}
							
						}
						/* if(mutation.addedNodes.length > 0){
							var node = mutation.addedNodes[0];
							
							if($(node).hasClass('b2make-widget')){
								if(b2make.widgets_count != b2make.history.widgets_count){
									b2make.history.widgets_count = b2make.widgets_count;
									history_add(null);
								}
							}
							
							b2make.history.widgets_delete_next = false;
						}
						
						if(mutation.removedNodes.length > 0){
							var node = mutation.removedNodes[0];
							
							if($(node).hasClass('b2make-widget')){
								var id = $(node).myAttr('id');
								if(!$('#'+id).get(0)){
									history_add(null);
								}
							}
						} */
					break;
					/* case 'attributes':
						switch($(mutation.target).myAttr('id')){
							case 'b2make-selecionador-objetos':
								if(!b2make.history.delay){
									b2make.history.delay = true;
									
									console.log($(mutation.target).myAttr('style'));
									
									setTimeout(function(){b2make.history.delay = false;},b2make.history.delay_time);
								}
							break;
						}
						
						if($(mutation.target).hasClass('b2make-widget'))
						switch($(mutation.target).myAttr('data-type')){
							case 'conteiner':							
								if(!b2make.history.delay){
									b2make.history.delay = true;
									
									console.log($(mutation.target).myAttr('style'));
									
									setTimeout(function(){b2make.history.delay = false;},b2make.history.delay_time);
								}
							break;
							case 'conteiner-area':							
								if(!b2make.history.delay){
									b2make.history.delay = true;
									
									console.log($(mutation.target).myAttr('style'));
									
									setTimeout(function(){b2make.history.delay = false;},b2make.history.delay_time);
								}
							break;
							
						}
						
						//console.log($(mutation.target).myAttr('id'));
					break; */
				}
			});
		});
		
		// configuração do observador:
		var config = { subtree: true, attributes: true, childList: true, characterData: true };
		
		// passar o nó alvo, bem como as opções de observação
		observer.observe(target, config);
		//history_add(null);
	}
	
	history();
	
	function close_save(){
		if(!b2make.save){
			b2make.save = $('<div id="b2make-save"></div>');
			
			b2make.save.appendTo($('body'));
		}
		
		close_all();
		$(b2make.shadow).hide();
		
		var html = $(b2make.site_conteiner).html();
		
		$('#b2make-save').html(html);
		
		$('#b2make-save').find('#b2make-shadow').remove();
		$('#b2make-save').find('.b2make-widget[data-type="conteiner"]').css('width',"");
		
		$('#b2make-save').find('*').css('cursor',"");
		$('#b2make-save').find('*').removeAttr('cursor');
		$('#b2make-save').find('.b2make-widget-loading').show();
		
		var opcao = 'save';
		
		$.ajax({
			type: 'POST',
			url: '.',
			async: false,
			data: { 
				ajax : 'sim',
				opcao : opcao,
				html : $('#b2make-save').html()
			},
		});
		
		$('#b2make-save').html('');
		script_callback({operacao:'save-ajax-call'});
	}
	
	function close(){
		$(window).on('unload',function(e){
			close_save();
		});
	}
	
	close();
	
	function input_start_values(){
		var variaveis = new Array();
		
		$('.b2make-input-start-value').each(function(){
			var id = $(this).myAttr('id');
			var variavel = $(this).myAttr('data-variavel');
			
			variaveis.push({
				id : id,
				variavel : variavel
			});
		});
		
		var str_json = JSON.stringify({variaveis:variaveis});
		var opcao = 'input-start-values';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				str_json : str_json
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
							if(dados.valores){
								for(var i=0;i<dados.valores.length;i++){
									var id = dados.valores[i].id;
									var val = dados.valores[i].val;
									
									$('#'+id).val(val);
								}
							}
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
	
	function input_delay_to_change(p){
		if(!p)p = {};
		
		var id = p.id;
		
		if(!b2make.input_delay) b2make.input_delay = new Array();
		if(!b2make.input_delay_count) b2make.input_delay_count = new Array();
		if(!b2make.input_delay_parms) b2make.input_delay_parms = new Array();
		
		if(!b2make.input_delay[id]){
			b2make.input_delay[id] = new Array();
			b2make.input_delay_count[id] = 0;
		}
		
		b2make.input_delay_count[id]++;
		
		var valor = b2make.input_delay_count[id];
		
		b2make.input_delay[id].push(valor);
		b2make.input_delay_parms[id] = p;
		
		setTimeout(function(){
			if(b2make.input_delay[id][b2make.input_delay[id].length - 1] == valor){
				input_change(b2make.input_delay_parms[id]);
			}
		},b2make.input_delay_timeout);
	}
	
	function input_change(p){
		if(!p)p = {};
		
		$(p.callback_id).trigger(p.callback_event);
		b2make.input_delay[p.id] = false;
	}

	holder_menus_positions();
	
	$.carregamento_open = function(){
		if(!b2make.carregando_conteiner){
			b2make.carregando_conteiner = $('<div id="b2make-carregamento-conteiner"><div id="b2make-carregamento-texto">'+b2make.msgs.carregando+'</div></div>');
			b2make.carregando_conteiner.appendTo('body');
			carregando_position();
		}
		
		b2make.carregando_conteiner.fadeIn(b2make.carregando.animation);
	}
	
	$.carregamento_close = function(){
		if(b2make.carregando_conteiner){
			b2make.carregando_conteiner.fadeOut(b2make.carregando.animation);
		}
	}
	
	function carregando_position(){
		$('#b2make-carregamento-texto').css({top:$(window).height()/2 - $('#b2make-carregamento-texto').height()/2});	
		$('#b2make-carregamento-texto').css({left:$(window).width()/2 - $('#b2make-carregamento-texto').width()/2});	
	}
	
	function carregando(){
		b2make.carregando = {};
		
		b2make.carregando.animation = 150;
		
		if(!b2make.msgs.carregando)b2make.msgs.carregando = 'Carregando';
	}
	
	carregando();
	
	function help_open(id){
		$.dialogbox_open({
			title: b2make.msgs.help_title+' - '+b2make.help_text[id].titulo,
			height: b2make.help.dialogbox_height,
			width: b2make.help.dialogbox_width,
			msg: b2make.help_text[id].texto
		});
	}
	
	function help(){
		b2make.help = {};
		
		if(!b2make.msgs.help_title)b2make.msgs.help_title = 'Ajuda';
		if(!b2make.help.dialogbox_height)b2make.help.dialogbox_height = 400;
		if(!b2make.help.dialogbox_width)b2make.help.dialogbox_width = 540;
		
		$('#b2make-woc-help,#b2make-wom-help').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id = $(this).myAttr('data-type');
			
			if(!b2make.help_text){
				b2make.help_text = Array();
			}
			
			if(!b2make.help_text[id]){
				var opcao = 'help-texto';
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						opcao : opcao,
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
									b2make.help_text[id] = {
										texto : dados.texto,
										titulo : dados.titulo
									};
									help_open(id);
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
			} else {
				help_open(id);
			}
		});
	}
	
	help();
	
	function operacoes_finais(){
		var ler_scripts = false;
		var ler_scripts_list = false;
		
		var grid = localStorage.getItem('b2make-grade');
		
		if(grid){
			if(grid == 'n'){
				$('#b2make-grade').hide();
			}
		}
		
		$(b2make.widget).each(function(){
			if($(this).myAttr('data-type') != 'conteiner-area'){
				
				if(b2make.dynamic_scripts)
				for(var i=0;i<b2make.dynamic_scripts.length;i++){
					if($(this).myAttr('data-type') == b2make.dynamic_scripts[i].id){
						if(!ler_scripts){
							ler_scripts = new Array();
							ler_scripts_list = new Array();
						}
						
						if(!ler_scripts[$(this).myAttr('data-type')]){
							ler_scripts[$(this).myAttr('data-type')] = true;
							ler_scripts_list.push($(this).myAttr('data-type'));
						}
					}
				}
			}
		});
		
		if(ler_scripts_list){
			for(var i=0;i<ler_scripts_list.length;i++){
				if(!b2make.dynamic_scripts_loaded){
					script_ler({id:ler_scripts_list[i],not_callback:true});
					continue;
				}
				
				if(b2make.dynamic_scripts_loaded)
				if(!b2make.dynamic_scripts_loaded[ler_scripts_list[i]]){
					script_ler({id:ler_scripts_list[i],not_callback:true});
				}
			}
		}
		
		if(localStorage.getItem('b2make.mudar_foto_perfil'))
		if(localStorage.getItem('b2make.mudar_foto_perfil') == '1'){
			b2make.mudar_foto_perfil_flag = true;
			$('.b2make-other-options').trigger('mouseup');
			localStorage.setItem('b2make.mudar_foto_perfil',null);
		}
		
		$('.b2make-tooltip').tooltip({
			show: {
				effect: "fade",
				delay: 400
			}
		});
		
		/* $('.b2make-input-select').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			$(this).select();
		}); */
		
		$(document.body).on('keydown','.b2make-input-only-number',function (e) {
			// Allow: backspace, delete, tab, escape, enter and
			if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
			// Allow: Ctrl+A
			(e.keyCode == 65 && e.ctrlKey === true) || 
			// Allow: home, end, left, right, down, up
			(e.keyCode >= 35 && e.keyCode <= 40)) {
				// let it happen, don't do anything
				return;
			}
			// Ensure that it is a number and stop the keypress
			if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
				e.preventDefault();
			}
		});
		
		$('.b2make-input-only-number-negative').keydown(function (e) {
			// Allow: backspace, delete, tab, escape, enter and
			if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
			// Allow: Ctrl+A
			(e.keyCode == 65 && e.ctrlKey === true) || 
			// Allow: home, end, left, right, down, up
			(e.keyCode >= 35 && e.keyCode <= 40)) {
				// let it happen, don't do anything
				return;
			}
			
			// Ensure that it is a number and stop the keypress
			if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
				var str = $(this).val();
				var found = true;
				
				if((e.keyCode == 109 || e.keyCode == 189)){
					found = false;
				}
				
				if($(this).caret().start != 0){
					found = true;
				}
				
				if(found)e.preventDefault();
			}
		});
		
		$(".b2make-data").datepicker({
			nextText: 'Próximo',
			prevText: 'Anterior',
			dateFormat: 'dd/mm/yy',
			dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
			dayNamesMin: ['Do', 'Se', 'Te', 'Qa', 'Qi', 'Se', 'Sa'],
			monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
			monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']
		});
		
		$(".b2make-data").datepicker('setDate', new Date());
		
		$(".b2make-hora").inputmask("99:99",{ "clearIncomplete": true });
		$(".b2make-data").inputmask("99/99/9999",{ "clearIncomplete": true });
		
		
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
		
		$('.b2make-checklist-label').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id_chklist = $(this).myAttr('id').replace(/-lbl/gi,'');
			
			$('#'+id_chklist).trigger('mouseup');
		});
		
		$('.b2make-checklist').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var id_chklist = $(this).myAttr('id')
			
			if($(this).myAttr('data-checked')){
				$(this).myAttr('data-checked',null);
			} else {
				$(this).myAttr('data-checked',true);
			}
			
			$('#'+id_chklist).trigger('options');
		});
		
		
		$(document.body).on('mouseover','.b2make-wsoae-prev,.b2make-wsoae-next',function () {
			var pai = $(this).parent().parent();
			
			var color_caixa_hover = pai.myAttr('data-seta-cor-2-ahex');
			var color_seta_hover = pai.myAttr('data-caixa-cor-ahex');
			
			if(!color_caixa_hover) color_caixa_hover = '#333333'; else color_caixa_hover = $.jpicker_ahex_2_rgba(color_caixa_hover);
			if(!color_seta_hover) color_seta_hover = '#ECEDEF'; else color_seta_hover = $.jpicker_ahex_2_rgba(color_seta_hover);
			
			$(this).css('background-color',color_caixa_hover);
			$(this).find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',color_seta_hover);
		});
		
		$(document.body).on('mouseout','.b2make-wsoae-prev,.b2make-wsoae-next',function () {
			var pai = $(this).parent().parent();
			
			var color_caixa_hover = pai.myAttr('data-caixa-cor-ahex');
			var color_seta_hover = pai.myAttr('data-seta-cor-1-ahex');
			
			if(!color_caixa_hover) color_caixa_hover = '#ECEDEF'; else color_caixa_hover = $.jpicker_ahex_2_rgba(color_caixa_hover);
			if(!color_seta_hover) color_seta_hover = '#333333'; else color_seta_hover = $.jpicker_ahex_2_rgba(color_seta_hover);
			
			$(this).css('background-color',color_caixa_hover);
			$(this).find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color',color_seta_hover);
		});
		
		$(document.body).on('mouseover','.b2make-player-prev,.b2make-player-play,.b2make-player-next',function (){
			var album_musicas = $(this).parent().hasClass('b2make-albummusicas-widget-controls');
			
			if(album_musicas){
				var pai = $(this).parent().parent().parent().parent().parent();
				var color = '#DBDBDB';
			} else {
				var pai = $(this).parent().parent().parent().parent();
				var color = '#726B6D';
			}
			
			if(pai.myAttr('data-botoes-color-2-ahex')){
				var bg = $.jpicker_ahex_2_rgba(pai.myAttr('data-botoes-color-2-ahex'));
				
				$(this).find('svg').find('polygon').css('fill',bg);
				$(this).find('svg').find('rect').css('fill',bg);
				$(this).find('svg').find('path').css('fill',bg);
			} else {
				$(this).find('svg').find('polygon').css('fill',color);
				$(this).find('svg').find('rect').css('fill',color);
				$(this).find('svg').find('path').css('fill',color);
			}
		});
		
		$(document.body).on('mouseout','.b2make-player-prev,.b2make-player-play,.b2make-player-next',function (){
			var album_musicas = $(this).parent().hasClass('b2make-albummusicas-widget-controls');
			
			if(album_musicas){
				var pai = $(this).parent().parent().parent().parent().parent();
				var color = '#FFFFFF';
			} else {
				var pai = $(this).parent().parent().parent().parent();
				var color = '#413E3F';
			}
			
			if(pai.myAttr('data-botoes-color-1-ahex')){
				var bg = $.jpicker_ahex_2_rgba(pai.myAttr('data-botoes-color-1-ahex'));
				
				$(this).find('svg').find('polygon').css('fill',bg);
				$(this).find('svg').find('rect').css('fill',bg);
				$(this).find('svg').find('path').css('fill',bg);
			} else {
				$(this).find('svg').find('polygon').css('fill',color);
				$(this).find('svg').find('rect').css('fill',color);
				$(this).find('svg').find('path').css('fill',color);
			}
		});
		
		$('.ui-helper-hidden-accessible').remove();
		setTimeout(function(){$('.ui-helper-hidden-accessible').remove();},1000);
		setTimeout(function(){$('.ui-helper-hidden-accessible').remove();},10000);
		
		if(variaveis_js.widget_id){
			$(b2make.widget).each(function(){
				if($(this).myAttr('id') == variaveis_js.widget_id){
					if(!b2make.conteiner_show){
						b2make.conteiner_obj = $(this).parent().get(0);
						
						if($(b2make.conteiner_obj).myAttr('data-type') == 'conteiner-area') b2make.conteiner_obj = $(b2make.conteiner_obj).parent().get(0);
						conteiner_open();
					}
					
					b2make.conteiner_child_obj = $(this).get(0);
					
					switch($(this).myAttr('data-type')){
						case 'services':
							b2make.widget_add_sub_options_open = true;
						break;
					}
					
					$(this).css('cursor','move');
					$.conteiner_child_open({select:true,widget_type:$(this).myAttr('data-type')});
					
					return;
				}
			});
		}
		
		$('.jPicker .Icon').css('width','20px');
		$('.jPicker .Icon').css('height','20px');
		
		$(b2make.widget).each(function(){
			switch($(this).myAttr('data-type')){
				case 'conteiner':
					if($(this).myAttr('data-banners-id')){
						$(this).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').each(function(){
							if($(this).myAttr('data-google-font-titulo')){
								$.google_fonts_wot_load({
									family : $(this).myAttr('data-titulo-font-family')
								});
							}
							if($(this).myAttr('data-google-font-sub-titulo')){
								$.google_fonts_wot_load({
									family : $(this).myAttr('data-sub-titulo-font-family')
								});
							}
						});
					}
				break;
				case 'youtube':
					if($(this).myAttr('data-layout-tipo') == 'imagem'){
						$.widgets_read_google_font({
							tipo : 2,
							types : new Array('caixa-texto'),
							obj : $(this)
						});
					}
				break;
			}
		});
	}
	
	operacoes_finais();

});