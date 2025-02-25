var componentes = {};

$(document).ready(function(){
	$(window).on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		if(componentes.open.length > 0){
			componentes.open.filter(function(ele){
				componentes_select_close({obj:$(ele)});
			});
		}
	});
	
	// ========================== Datepicker ============================
	
	if($(".b2make-componentes-datepicker-input").length > 0){
		$(".b2make-componentes-datepicker-input").mask("99/99/9999",{completed:function(){
			var data = this.val();
			var data_aux = data.split('/');
			var alerta = "Data inválida";
			var bissexto = false;
			var dia_str;
			var mes_str;
			var ano_str;
			var dia_aux = data_aux[0];
			var mes_aux = data_aux[1];
			
			if(dia_aux[0] == '0') dia_str = dia_aux[1]; else dia_str = dia_aux;
			if(mes_aux[0] == '0') mes_str = mes_aux[1]; else mes_str = mes_aux;
			ano_str = data_aux[2];
			
			var dia = parseInt(dia_str);
			var mes = parseInt(mes_str);
			var ano = parseInt(ano_str);
			
			if(mes > 12 || mes == 0){
				this.val('');
				alert(alerta);
				return false;
			}
			
			switch(mes){
				case 1:
				case 3:
				case 5:
				case 7:
				case 8:
				case 10:
				case 12:
					if(dia > 31){
						this.val('');
						alert(alerta);
						return false;
					}
				break;
				case 4:
				case 6:
				case 9:
				case 11:
					if(dia > 30){
						this.val('');
						alert(alerta);
						return false;
					}
				break;
				case 2:
					if(dia > 28){
						if(ano % 4 == 0){
							bissexto = true;
						}
						if(ano % 100 == 0){
							bissexto = false;
						}
						if(ano % 400 == 0){
							bissexto = true;
						}
						
						if(bissexto == true){
							if(dia > 29){
								this.val('');
								alert(alerta);
								return false;
							}
						} else {
							this.val('');
							alert(alerta);
							return false;
						}
					}
				break;
			}
			
			if(ano < 1875 || ano > 2200){
				this.val('');
				alert(alerta);
				return false;
			}
		}});
	}
	
	if($(".b2make-componentes-datepicker-calendar-input").length > 0){
		$(".b2make-componentes-datepicker-calendar-input").datepicker({
			buttonImageOnly: true,
			nextText: 'Próximo',
			prevText: 'Anterior',
			dateFormat: 'dd/mm/yy',
			dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
			dayNamesMin: ['Do', 'Se', 'Te', 'Qa', 'Qi', 'Se', 'Sa'],
			monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
			monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
			onSelect: function(dateText, inst) { 
				componentes_datepicker_change({
					obj:$(inst.input).parent().parent(),
					value:dateText
				});
			}
		});
	}
	
	function componentes_datepicker_change(p = {}){
		var obj = p.obj;
		var value = p.value;
		
		obj.attr('data-value',value);
		obj.find('input.b2make-componentes-datepicker-input').prop('value',value);
		
	}
	
	function componentes_datepicker(){
		$('.b2make-componentes-datepicker-calendar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().find(".b2make-componentes-datepicker-calendar-input").trigger('focus');
		});
	}
	
	componentes_datepicker();
	
	// ========================== Select ============================

	function componentes_select_change(p = {}){
		var obj = p.obj;
		
		obj.find('.b2make-componentes-select-holder').html(p.text).attr('data-value',p.value);
		obj.find('input').prop('value',p.value);
		obj.find('input').trigger('change');
		
		if(obj.attr('data-callback')){
			window[obj.attr('data-callback')]();
		}
	}
	
	function componentes_select_close_all(){
		if(componentes.open){
			componentes.open.forEach(function(obj){
				componentes_select_close({obj:$(obj)});
			});
		}
	}
	
	function componentes_select_open(p = {}){
		var obj = p.obj;
		var height = $('#b2make-gestor-site').height();
		var top = obj.offset().top;
		var top_ajuste = obj.find('.b2make-componentes-select-holder').outerHeight(true);
		
		componentes_select_close_all();
		
		componentes.open.push(obj.get(0));
		
		if(top + componentes.animation_height > height){
			obj.find('.b2make-componentes-select').animate({height:componentes.animation_height,opacity:1},componentes.animation_time);
		} else {
			obj.find('.b2make-componentes-select').animate({height:componentes.animation_height,opacity:1},componentes.animation_time);
		}
	}
	
	function componentes_select_close(p = {}){
		var obj = p.obj;
		
		componentes.open = componentes.open.filter(function(ele){
			return ele != obj.get(0);
		});
		obj.find('.b2make-componentes-select').animate({height:0,opacity:0},componentes.animation_time);
	}
	
	function componentes_select(){
		componentes = {};
		
		componentes.animation_time = 300;
		componentes.animation_height = 300;
		componentes.open = new Array();
		
		$('.b2make-componentes-select-holder').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			componentes_select_open({obj:$(this).parent()});
		});
		
		$('.b2make-componentes-select-options').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
		});
		
		$('.b2make-componentes-select-holder-2,.b2make-componentes-select-option').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if($(this).hasClass('b2make-componentes-select-holder-2')){
				componentes_select_close({obj:$(this).parent().parent()});
				componentes_select_change({
					obj:$(this).parent().parent(),
					value:$(this).attr('data-value'),
					text:$(this).html()
				});
			} else {
				componentes_select_close({obj:$(this).parent().parent().parent()});
				componentes_select_change({
					obj:$(this).parent().parent().parent(),
					value:$(this).attr('data-value'),
					text:$(this).html()
				});
			}
		});
	}
	
	componentes_select();
});