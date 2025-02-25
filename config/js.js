if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

$(document).ready(function(){
	sep = "../";
	
	b2makeAdmin.stop_enter_preventDefaults = true;
	
	$(".telefone").mask("(99) 9999-9999?9");
	$(".cnpj").mask("99.999.999/9999-99");
	$(".cpf").mask("999.999.999/99");
	
	$(".money").maskMoney({symbol:'R$',decimal:",",thousands:"."});
	
	$(document.body).on('keyup','.parcelamento_maximo_parcelas',function(e){
		var value = $(this).val();
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-gestor-listener',
			trigger_event:'parcelamento_maximo_parcelas-change',
			value:value
		});
	});
	
	$(document.body).on('parcelamento_maximo_parcelas-change','#b2make-gestor-listener',function(e,value,p){
		if(!p) p = {};
		
		var min = parseInt($('.parcelamento_maximo_parcelas').attr('min'));
		var max = parseInt($('.parcelamento_maximo_parcelas').attr('max'));
		value = parseInt(value);
		
		var valor_mudar;
		
		if(value > max){
			valor_mudar = max;
		}
		
		if(value < min){
			valor_mudar = min;
		}
		
		if(!value){
			valor_mudar = min;
		}
		
		if(typeof valor_mudar !== typeof undefined && valor_mudar !== false){
			$('.parcelamento_maximo_parcelas').val(valor_mudar);
		}
	});
	
	$('input.alphanum').keyup(function() {
		if (this.value.match(/[^a-zA-Z0-9_-]/g)) {
			this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '');
		}
	});
	
	$(".opcao").hover(
		function(){
			//$(this).css('background-image', 'url(../images/admin/box-cinza.png?v=1)');
		},
		function(){
			//$(this).css('background-image', 'url(../images/admin/box-branco.png?v=1)');
		}
	);
	
	tinymce.init({
		menubar: false,
		selector: 'textarea.tinymce',
		toolbar: 'undo redo | styleselect | bold italic underline | link image | alignleft aligncenter alignright alignjustify',
		plugins: "image imagetools link",
		directionality: 'pt_BR',
		resize: "both",
		language_url: sep + 'includes/js/tinyMce/pt_BR.js',
		branding: false
	});
	
	$(".pre-excluir-a").click(function() {
		if(!confirm("Tem certeza que deseja excluir essa preferência?")){return false;}
	});
	
	$("#form").submit(function() {
		//if(!confirm("Será necessário reiniciar o sistema. Tem certeza que deseja GRAVAR as alterações?")){return false;}
	});
	
	$("#form3").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		
		campo = "variavel"; mens = "Preencha a Variável"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		campo = "tipo"; mens = "Selecione um tipo"; if(!!$("input[id="+campo+"]").is(':checked')){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		campo = "descricao"; mens = "Preencha a Descrição"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		
		if(!enviar){
			return false;
		}
	});
	
	$('#b2make-store-pagseguro-auth').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		window.open('?opcao=pagseguro-autorizar','_self');
	});
	
	$('#paypal-app-install-btn').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		$('#paypal-app-install-cont').toggle();
		$('#paypal-app-sandbox-code').focus();
	});

	$('#paypal-app-desinstall-cont').on('b2make-check-box-clicked',function(e){
		if($(this).find('div').attr('data-checked')){
			$('#paypal-app-live-cont').hide();
			$('#paypal-app-active-cont').hide();
			$('#paypal-app-inactive-cont').hide();
		} else {
			$('#paypal-app-live-cont').show();
			$('#paypal-app-active-cont').show();
			$('#paypal-app-inactive-cont').show();
		}
	});

	// ================================================== Codemirror ==============================================
	
	var codemirrors_instances = new Array();
	
	var codemirror_js_mini = document.getElementsByClassName("codemirror-js-mini");
	
	if(codemirror_js_mini.length > 0){
		for(var i=0;i<codemirror_js_mini.length;i++){
			var myCodeMirror = CodeMirror.fromTextArea(codemirror_js_mini[i],{
				lineNumbers: true,
				styleActiveLine: true,
				matchBrackets: true,
				mode: "javascript",
				htmlMode: true,
				theme: "abcdef"
			});
			
			myCodeMirror.setSize(477,400);
			
			codemirrors_instances.push(myCodeMirror);
		}
	}
	
	var codemirror_js = document.getElementsByClassName("codemirror-js");
	
	if(codemirror_js.length > 0){
		for(var i=0;i<codemirror_js.length;i++){
			var myCodeMirror = CodeMirror.fromTextArea(codemirror_js[i],{
				lineNumbers: true,
				styleActiveLine: true,
				matchBrackets: true,
				mode: "javascript",
				htmlMode: true,
				theme: "abcdef"
			});
			
			myCodeMirror.setSize(477,400);
			
			codemirrors_instances.push(myCodeMirror);
		}
	}
	
	var codemirror_html = document.getElementsByClassName("codemirror-html");
	
	if(codemirror_html.length > 0){
		for(var i=0;i<codemirror_html.length;i++){
			var myCodeMirror = CodeMirror.fromTextArea(codemirror_html[i],{
				lineNumbers: true,
				styleActiveLine: true,
				matchBrackets: true,
				mode: "htmlmixed",
				htmlMode: true,
				theme: "abcdef"
			});
			
			myCodeMirror.setSize(477,400);
			
			codemirrors_instances.push(myCodeMirror);
		}
	}
	
	var codemirror_css = document.getElementsByClassName("codemirror-css");
	
	if(codemirror_css.length > 0){
		for(var i=0;i<codemirror_css.length;i++){
			var myCodeMirror = CodeMirror.fromTextArea(codemirror_css[i],{
				lineNumbers: true,
				styleActiveLine: true,
				matchBrackets: true,
				mode: "css",
				htmlMode: true,
				theme: "abcdef"
			});
			
			myCodeMirror.setSize(477,400);
			
			codemirrors_instances.push(myCodeMirror);
		}
	}
	
	// ================================================== JPicker ==============================================
	
	$.jpicker_ahex_2_rgba = function(ahex){
		var rgba = $.jPicker.ColorMethods.hexToRgba(ahex);
		
		return 'rgba('+rgba.r+','+rgba.g+','+rgba.b+','+(rgba.a/255).toFixed(1)+')';
	}
	
	function jpicker_open(obj){
		var position = $(obj).attr('data-position');
		var obj_target = $(obj).attr('data-obj-target');
		var obj_holder = $(obj).attr('data-obj-holder');
		var obj_callback = $(obj).attr('data-obj-callback');
		var obj_parent_callback = $(obj).attr('data-parent-callback');
		var css_property = $(obj).attr('data-css-property');
		var position_specific = $(obj).attr('data-position-specific');
		var ahex = $(obj).attr('data-ahex');
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
			$.jPicker.List[0].color.active.val('ahex',ahex);
		} else {
			$.jPicker.List[0].color.active.val('ahex','000000ff');
		}
		
		$('div.jPicker').css('top',($(obj).offset().top ));
		$('div.jPicker').css('left',($(obj).offset().left));
		$('#b2make-jpicker-conteiner').find('span.jPicker').find('span.Icon').find('span.Image:first').trigger('click');
	}
	
	$.jpicker_load = function(p){
		$(p.obj).find('.b2make-jpicker').each(function(){
			$(this).addClass('b2make-tooltip');
			if(!$(this).attr('title'))$(this).attr('title',b2make.msgs.jpickerTitle);
		});
	}
	
	function jpicker(){
		if(!b2make.msgs) b2make.msgs = {};
		if(!b2make.msgs.jpickerTitle)b2make.msgs.jpickerTitle = 'Clique para mudar a cor do objeto desejado';
		if(!b2make.msgs.jpickerWindowTitle)b2make.msgs.jpickerWindowTitle = 'Selecione a Cor Desejada';
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
		if(!b2make.fade_time)b2make.fade_time = 200;
		
		b2make.jpicker = {};
		
		$('.b2make-jpicker').each(function(){
			$(this).addClass('b2make-tooltip');
			if(!$(this).attr('title'))$(this).attr('title',b2make.msgs.jpickerTitle);
		});
		
		if($('#b2make-jpicker-conteiner').length > 0){
			var left_jpicker = $('#b2make-jpicker-conteiner').offset().left+'px';
		} else {
			var left_jpicker = 0;
		}
		
		b2make.msgs.jpickerLocalization.tooltips.picker_open = b2make.msgs.jpickerTitle;
		
		$.fn.jPicker.defaults.images.clientPath='jpicker/images/';
		
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
				all = $.jPicker.List[0].color.active.val('all');
				
				if(all){
					$(b2make.jpicker.obj).css({'background-color' : 'rgba('+all.r+','+all.g+','+all.b+','+(all.a/255).toFixed(1)+')'});
				} else {
					$(b2make.jpicker.obj).css({'background-color' : 'rgb(0,0,0)'});
				}
				
				if(all){
					$(b2make.jpicker.obj).attr('data-ahex',all.ahex);
				} else {
					$(b2make.jpicker.obj).attr('data-ahex','000000ff');
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
						$(b2make.jpicker.obj_holder).attr('data-color-ahex',all.ahex);
					} else {
						$(b2make.jpicker.obj_holder).attr('data-color-ahex',false);
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
	
	// ================================================== Fonts ==============================================
	
	$(window).bind('mouseup touchend',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;

		if(b2make.fonts_open){
			if(!$(e.target).parent().is('.b2make-fonts-count') && !$(e.target).parent().is('.b2make-wot-google-fontes') && !$(e.target).is('.b2make-fonts-google-fontes') && !$(e.target).is('.b2make-fonts-count-teste') && !$(e.target).is('.b2make-fonts-fontes')){
				fonts_close();
			}
			if($(e.target).is('.b2make-fonts-count-ok')){
				fonts_close();
			}
		}
		
	});
	
	$.google_fonts_wot_load = function(p){
		var found = false;
		
		switch(p.family){
			case 'Arial':
			case 'Helvetica':
			case 'Times New Roman':
			case 'Times':
			case 'Courier New':
			case 'Courier':
			case 'Palatino':
			case 'Garamond':
			case 'Avant Garde':
			case 'Verdana':
			case 'Tahoma':
			case 'Georgia':
			case 'Comic Sans MS':
			case 'Trebuchet MS':
			case 'Arial Black':
			case 'Impact':
				return false;
			break;
		}
		
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
					alert(b2make.msgs.googleFontsInative);
					
					if(!p.nao_carregamento)$.carregamento_close();
				},
				fontloading: function(familyName, fvd) {},
				fontactive: function(familyName, fvd) {},
				fontinactive: function(familyName, fvd) {}
			});
		}
	}
	
	function google_fonts(){
		if(!b2make.google_fonts){
			$.ajax({
				dataType: "json",
				url: 'webfonts/webfonts.js?v=3',
				data: { 
					
				},
				beforeSend: function(){
					$.carregamento_open();
				},
				success: function(txt){
					var ul = $('<ul class="b2make-wot-google-fontes"></ul>');
					var value_por_coluna = Math.floor(txt.length/3);
					
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
			
			var options = $(this).attr('data-options');
			
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
			var options = $(this).attr('data-options');
			
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
			
			if(pai.attr('id') != b2make.fonts_pai_atual){
				b2make.fonts_open = false;
				$('.b2make-fonts-count').hide();
			}
			
			b2make.fonts_pai_atual = pai.attr('id');
			
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
			
			obj.attr('data-font-family',$(this).css('fontFamily').replace(/'/gi,''));
			obj.attr('data-google-font','nao');
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
				'fontFamily': $(this).attr('data-font-family')
			});
			obj.find('.b2make-fonts-count').find('.b2make-fonts-count-teste').css({
				'fontFamily': $(this).attr('data-font-family')
			});
			obj.find('.b2make-fonts-holder').html($(this).attr('data-font-family').replace(/'/gi,''));
			
			obj.attr('data-font-family',$(this).attr('data-font-family').replace(/'/gi,''));
			obj.attr('data-google-font','sim');
			obj.trigger('changeFontFamily');
			
			$.google_fonts_wot_load({
				family : $(this).attr('data-font-family')
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
			
			obj.attr('data-font-size',value);
			obj.trigger('changeFontSize');
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-negrito',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = $(this).parent();
			
			if(obj.attr('data-font-negrito') == 'sim'){
				obj.attr('data-font-negrito','nao');
			} else {
				obj.attr('data-font-negrito','sim');
			}
			
			obj.trigger('changeFontNegrito');
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-italico',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = $(this).parent();
			
			if(obj.attr('data-font-italico') == 'sim'){
				obj.attr('data-font-italico','nao');
			} else {
				obj.attr('data-font-italico','sim');
			}
			
			obj.trigger('changeFontItalico');
		});
		
		$(document.body).on('mouseup tap','.b2make-fonts-align',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var obj = $(this).parent();
			var pos = $(this).attr('data-id');
			
			obj.attr('data-font-align',pos);
			obj.trigger('changeFontAlign');
		});
		
	}
	
	fonts();
	
	// ================================================== Carregamento ==============================================
	
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
	
	// ================================================== Loja ==============================================
	
	function loja(){
		b2make.font = 'Open Sans';
		
		var ahex_padrao_1 = '434142ff';
		var ahex_padrao_2 = 'ffffffff';
		var ahex_padrao_3 = 'd28d00ff';
		
		var cor_padrao_1 = 'rgb(67,65,66)';
		var cor_padrao_2 = 'rgb(255,255,255)';
		var cor_padrao_3 = 'rgb(210,141,0)';
		
		var esquema_cores = $('#b2make-esquema-cores-input').val();
		var esquema_cores_arr = new Array();
		var esquema_cores_txt = '';
		
		if(esquema_cores){
			esquema_cores_arr = esquema_cores.split(';');
		} else {
			esquema_cores_arr[0] = ahex_padrao_1+'|'+cor_padrao_1;
			esquema_cores_arr[1] = ahex_padrao_2+'|'+cor_padrao_2;
			esquema_cores_arr[2] = ahex_padrao_3+'|'+cor_padrao_3;
			
			for(var i=0;i<esquema_cores_arr.length;i++){
				esquema_cores_txt = esquema_cores_txt + (esquema_cores_txt.length > 0 ?';':'')+esquema_cores_arr[i];
			}
			
			$('#b2make-esquema-cores-input').val(esquema_cores_txt);
		}
		
		for(var i=0;i<esquema_cores_arr.length;i++){
			var cor_arr = esquema_cores_arr[i].split('|');
			
			$('#b2make-esquema-cor-'+(i+1)).attr('data-ahex',cor_arr[0]);
			$('#b2make-esquema-cor-'+(i+1)).css('background-color',cor_arr[1]);
		}
		
		$('#b2make-esquema-cor-1,#b2make-esquema-cor-2,#b2make-esquema-cor-3').on('changeColor',function(){
			var id = $(this).attr('id');
			var bg = $(b2make.jpicker.obj).css('background-color');
			var ahex = $(b2make.jpicker.obj).attr('data-ahex');
			
			id = parseInt(id.replace(/b2make-esquema-cor-/gi,''));
			
			esquema_cores_txt = '';
			for(var i=0;i<esquema_cores_arr.length;i++){
				if(i == id-1){
					esquema_cores_arr[i] = ahex + '|' + bg;
				}
				
				esquema_cores_txt = esquema_cores_txt + (esquema_cores_txt.length > 0 ?';':'')+esquema_cores_arr[i];
			}
			
			$('#b2make-esquema-cores-input').val(esquema_cores_txt);
		});
		
		$('#b2make-fontes-select').on('changeFontFamily',function(e){
			var font_family = $(this).attr('data-font-family');
			
			font_family = font_family.replace(/\"/gi,'');
			$('#b2make-fontes-input').val(font_family);
		});
		
		if($('#b2make-fontes-input').val()){
			$('#b2make-fontes-select').find('.b2make-fonts-holder').css({
				'fontFamily': $('#b2make-fontes-input').val()
			});
			$('#b2make-fontes-select').find('.b2make-fonts-holder').html($('#b2make-fontes-input').val());
			
			$.google_fonts_wot_load({
				family : $('#b2make-fontes-input').val()
			});
		} else {
			$('#b2make-fontes-select').find('.b2make-fonts-holder').css({
				'fontFamily': b2make.font
			});
			$('#b2make-fontes-select').find('.b2make-fonts-holder').html(b2make.font);
		}
	}
	
	loja();
});