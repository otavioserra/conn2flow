$(document).ready(function(){
	sep = "../../";
	
	$("#form").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!enviar){
			alerta.html("É obrigatório preencher os campos marcados em vermelho!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
			alerta.dialog("open");
			return false;
		}
	});
	
	if($('#_voucher-cont').length > 0){
		$("#_voucher-imprimir").bind('click touchstart',function(){
			window.open(variaveis_js.site_raiz+"includes/ecommerce/print.php","Imprimir","menubar=0,location=0,height=700,width=700");
		});
		
		$("#_voucher-alterar-campos").bind('click touchstart',function(){
			$('#_voucher-cont').hide();
			$('#_voucher-form-presente').show();
		});
		
		$("#_voucher-visulizar").bind('click touchstart',function(){
			$('#_voucher-cont').show();
			$('#_voucher-form-presente').hide();
		});
		
		$("#_voucher-lista-pedidos").bind('change',function(){
			var id = $(this).val();

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-pedidos' , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$.link_trigger('voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		var presente_outro = 'Para Presente';
		var presente_voce = 'Para Você';
		var tempo_animacao = 150;
		
		$("#_voucher-presente").bind('click touchstart',function(){
			var flag = $(this).attr('data-flag');
			
			if(flag == '1'){
				$(this).attr('data-flag','2');
				$(this).val(presente_voce);
				$('#_voucher-cont').hide();
				$('#_voucher-form-presente').show();
			} else {
				$(this).attr('data-flag','1');
				$(this).val(presente_outro);
				$('#_voucher-cont').show();
				$('#_voucher-form-presente').hide();
			}
			
			voucher_mudar_campos();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-presente' , flag : flag },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					if(flag == '2'){
						//$.link_trigger('voucher');
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_voucher-form-presente").bind('submit',function() {
			var enviar = true;
			var campo;
			var mens;
			
			campo = "_voucher-form-presente-de"; mens = "Preencha o campo De"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "_voucher-form-presente-para"; mens = "Preencha o campo Para"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "_voucher-form-presente-mensagem"; mens = "Preencha o campo Mensagem"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			if(enviar){
				$('#_voucher-form-presente').attr('action','.');
				$('#_voucher-form-presente').attr('method','post');
			} else {
				return false;
			}
		});
		
		function voucher_mudar_campos(){
			var flag = $("#_voucher-presente").attr('data-flag');
			
			if(flag == '2'){
				$('#_voucher-lay-de').show();
				$('#_voucher-lay-para').show();
				$('#_voucher-lay-mens').show();
				$('#_voucher-alterar-campos').show();
			} else {
				$('#_voucher-lay-de').hide();
				$('#_voucher-lay-para').hide();
				$('#_voucher-lay-mens').hide();
				$('#_voucher-alterar-campos').hide();
			}
		}
		
		voucher_mudar_campos();
		
	}
	
	
	
});