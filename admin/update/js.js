$(document).ready(function(){
	var modulo_versao = '1.0.0';
	sep = "../../";
	
	function debugar(txt){
		if($("#debugar").length <= 0){
			$("body").append('<div id="debugar" style="width:400px;height:500px;overflow:scroll;position:absolute;bottom:0px;right:0px;background-color:rgb(230,230,230);color:#000000;"></div>');
		}
		
		$("#debugar").prepend('<pre>'+txt+'</pre>');
	}
	
	$(".opcao").hover(
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-cinza.png?v=1)');
		},
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-branco.png?v=1)');
		}
	);
	
	$("#form").submit(function() {
		if(!confirm("O envio do arquivo poderá levar alguns minutos dependendo da velocidade da sua conexão nos quais não poderá sair dessa tela até a confirmação do envio. Tem certeza que deseja continuar?")){return false;}
	});
	
	var params = new Array();
	
	$("#instalar").click(function() {
		$(this).attr("disabled", true);
		$("#instalacao-conteiner").append('<h2>Instalação Iniciada</h2>');
		iniciacao();
	});
	
	if(variaveis_js.atualizar){
		$("#instalar").attr("disabled", true);
		iniciacao();
	}
	
	var sub_passo = new Array();
	
	function update(){
		$.ajax({
			type: 'POST',
			url: 'robo.php',
			data: { passo : params['passo'], sub_passo: sub_passo[params['passo']] ? sub_passo[params['passo']] : '0' },
			beforeSend: function(){
				if($("#cont-update-"+params['passo']).length <= 0){
					sub_passo[params['passo']] = 1;
					$("#instalacao-conteiner").append('<div style="width:800px;float:letf;" class="lista_cel update_cel" id="cont-update-'+params['passo']+'">Executando '+params['passos'][params['passo']].titulo+'</div>');
					$("#cont-update-"+params['passo']).append('<div class="update_carregando" id="cont-update-'+params['passo']+'-carregando"></div>');
				} else {
					$("#cont-update-"+params['passo']).append('<div class="update_carregando" id="cont-update-'+params['passo']+'-carregando"></div><div style="clear:both;" id="cont-update-'+params['passo']+'-carregando-clear"></div>');
					sub_passo[params['passo']]++;
				}
			},
			success: function(txt){
				if(txt[0] != '{'){
					debugar(txt);
					debugar("<h1>Janela do Erro</h1>");
				}
				
				var dados = eval('(' + txt + ')');
				
				$("#cont-update-"+params['passo']+"-carregando").remove();
				if($("#cont-update-"+params['passo']+"-carregando-clear").length > 0)$("#cont-update-"+params['passo']+"-carregando-clear").remove();
				
				if($("#cont-mensagem-"+params['passo']).length <= 0){
					$("#cont-update-"+params['passo']).append('<div style="float:right;color:#093;" id="cont-mensagem-'+params['passo']+'"><div>'+dados.mensagem+'</div></div>');
					$("#cont-update-"+params['passo']).append('<div style="clear:both;"></div>');
				} else {
					$("#cont-mensagem-"+params['passo']).append('<div>'+dados.mensagem+'</div>');
				}
				
				params['passo'] = dados.passo;
				
				if(dados.atualizacao_update){
					$.jStorage.set("instalacao-conteiner", $("#instalacao-conteiner").html());
					window.open("index.php?opcao=atualizar","_self");
				} else {
					
					if(params['passo'] >= params['passos'].length){
						$("#instalacao-conteiner").append('<div style="width:800px;float:letf;color:#093;" class="lista_cel update_cel" id="cont-fim">Atualização Finalizada com Sucesso</div>');
						$("html, body").animate({ scrollTop: $(document).height() }, 0);
					} else {
						$("html, body").animate({ scrollTop: $(document).height() }, 0);
						if(!dados.erro)update();
					}
				}
			},
			error: function(txt){
				
			}
		});
	}
	
	function iniciacao(){
		$.ajax({
			type: 'POST',
			url: 'robo.php',
			data: { opcao : 'iniciacao' },
			beforeSend: function(){
				if(variaveis_js.atualizar){
					$("#instalacao-conteiner").append($.jStorage.get("instalacao-conteiner"));
					$.jStorage.deleteKey("instalacao-conteiner");
				} else {
					$("#instalacao-conteiner").append('<div style="width:800px;float:letf;" class="lista_cel update_cel" id="cont-iniciacao">Iniciando Variáveis</div>');
					$("#cont-iniciacao").append('<div class="update_carregando" id="cont-iniciacao-carregando"></div>');
				}
			},
			success: function(txt){
				var dados = eval('(' + txt + ')');
				
				params['passos'] = dados.passos;
				
				if(variaveis_js.atualizar){
					params['passo'] = 2;
				} else {
					params['passo'] = 0;
					$("#cont-iniciacao-carregando").remove();
					$("#cont-iniciacao").append('<div style="float:right;color:#093;">Ok</div>');
				}
				
				update();
			},
			error: function(txt){
				
			}
		});
	}
	
});