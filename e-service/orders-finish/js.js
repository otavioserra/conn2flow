$(document).ready(function(){
	sep = "../../";
	var tempo_animacao = 150;
	
	$("#servico-button,#pedido-button").bind('click touchstart',function(){
		var id = $(this).attr('id');
		var opcao = id.replace(/-button/gi,'');
		var campo1 = opcao+'-codigo';
		var campo2 = opcao+'-senha';
		var mens = "Preencha o Código e a Senha do "+(opcao == "servico" ? "Serviço" : "Pedido");
		
		var codigo = $("#"+campo1).val();
		var senha = $("#"+campo2).val();
		
		if(
			codigo.length > 0 &&
			senha.length > 0 
		){
			$("#"+campo1).removeClass('ui-state-error');$("#"+campo2).removeClass('ui-state-error');
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : opcao , codigo : codigo , senha : senha },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						if(dados.confirmacao){
							$('#cont-baixar-retorno').addClass('confirmacao');
							$('#cont-baixar-retorno').removeClass('baixado');
							$('#cont-baixar-retorno').removeClass('erro');
						} else {
							$('#cont-baixar-retorno').removeClass('confirmacao');
							$('#cont-baixar-retorno').removeClass('baixado');
							$('#cont-baixar-retorno').addClass('erro');
						}
						
						$('#cont-baixar-retorno').html(dados.html);
						
						if(dados.confirmacao){
							//$("button, input:submit, input:button").button();
							if(!confirm("Tem certeza que quer baixar esse pedido/serviço?")){
								window.open('.','_self');
							}
						}
					} else {
						console.log(txt);
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		} else {
			if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo1).addClass('ui-state-error');$("#"+campo2).addClass('ui-state-error');
		}
	});
	
	$("#baixar-button").live('click touchstart',function(){
		var pedido_servico = $('#baixar-pedido-servico').val();
		var senha = $('#baixar-senha').val();
		var codigo = $('#baixar-codigo').val();
		var observacao = $('#baixar-observacao').val();
		
		var opcao = 'baixar';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { ajax : 'sim' , opcao : opcao , pedido_servico : pedido_servico , codigo : codigo , senha : senha , observacao : observacao },
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(tempo_animacao);
			},
			success: function(txt){
				$('#ajax_lendo').fadeOut(tempo_animacao);
				
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					if(dados.baixado){
						$('#cont-baixar-retorno').removeClass('confirmacao');
						$('#cont-baixar-retorno').addClass('baixado');
						$('#cont-baixar-retorno').removeClass('erro');
					} else {
						$('#cont-baixar-retorno').removeClass('confirmacao');
						$('#cont-baixar-retorno').removeClass('baixado');
						$('#cont-baixar-retorno').addClass('erro');
					}
					
					$('#cont-baixar-retorno').html(dados.html);
				} else {
					console.log(txt);
				}
			},
			error: function(txt){
				$('#ajax_lendo').fadeOut(tempo_animacao);
			}
		});
	});
	
});