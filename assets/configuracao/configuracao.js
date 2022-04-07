$(document).ready(function(){
	
	function configuracao_administracao_variavel_remover(){
		// ===== Atualizar o total de itens.
		
		gestor.configuracao.totalItens--;
		
		// ===== Caso o total de variáveis seja zero, remover o botão adicionar abaixo.
		
		if(gestor.configuracao.totalItens <= 0){
			$('.componenteAdicionarBaixo').addClass('escondido');
		}
		
		// ===== Reiniciar o formulário.
		
		$.formReiniciar();
	}
	
	function configuracao_administracao_variavel_adicionar(abaixo){
		// ===== Pegar o total de variáveis.
		
		var variaveisTotal = parseInt($('#variaveis-total').val());
		var num = variaveisTotal;
		
		// ===== Pegar o modelo do formulário.
		
		var adicionar = gestor.configuracao.modelos['adicionar'].clone();
		
		// ===== Alterar informações do componente.
		
		adicionar.find('.identificador').attr('name','id-'+num);
		adicionar.find('.grupo').attr('name','grupo-'+num);
		adicionar.find('.tipo').attr('name','tipo-'+num);
		adicionar.find('.descricao').attr('name','descricao-'+num);
		
		// ===== Pegar o campo do modelo tipo string.
		
		var campo = $('.camposModelos').find('.campo.string').clone();
		
		// ===== Alterar informações do campo.
		
		campo.attr('name','valor-'+num);
		campo.attr('value','');
		
		// ===== Incluir o campo no componete adicionar.
		
		adicionar.find('.variavelValor').html(campo);
		
		// ===== Incluir o modelo na tela.
		
		if(abaixo){
			adicionar.appendTo('.variaveisCont');
		} else {
			adicionar.prependTo('.variaveisCont');
		}
		
		// ===== Incluir o botão adicionar abaixo caso o mesmo esteja escondido.
		
		if($('.componenteAdicionarBaixo').hasClass('escondido')){
			$('.componenteAdicionarBaixo').removeClass('escondido');
		}
		
		// ===== Atualizar o total de variáveis e itens.
		
		variaveisTotal++;
		gestor.configuracao.totalItens++;
		$('#variaveis-total').val(variaveisTotal);
		
		// ===== Listeners deste componente.
		
		adicionar.find('.adicionarBtnCancelar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this);
			
			obj.parents('.adicionar').remove();
			
			configuracao_administracao_variavel_remover();
		});
		
		adicionar.find('.ui.dropdown').dropdown();
		
		// ===== Reiniciar o formulário.
		
		$.formReiniciar();
	}
	
	function configuracao_administracao_variavel_editar(obj){
		// ===== Pegar os dados do objeto antes de editar.
		
		var variavelCont = obj.parents('.variavelCont');
		
		var variavelReferencia = variavelCont.attr('data-id');
		var variavelTipo = variavelCont.attr('data-tipo');
		var variavelNum = variavelCont.attr('data-num');
		var valorObj = variavelCont.find('.variavelValor').find('.campo');
		
		var variavelNome = variavelCont.find('.variavelNome').html();
		var variavelGrupo = variavelCont.find('.variavelGrupo').html();
		var variavelDescricao = variavelCont.find('.variavelDescricao').html();
		
		// ===== Pegar o valor da variável.
		
		var valor = '';
		
		switch(variavelTipo){
			default:
				valor = valorObj.val();
		}
		
		// ===== Pegar o número da variável.
		
		var num = variavelNum;
		
		// ===== Pegar o modelo do formulário.
		
		var editar = gestor.configuracao.modelos['editar'].clone();
		
		// ===== Alterar informações do componente.
		
		editar.find('.identificador').attr('name','id-'+num);
		editar.find('.grupo').attr('name','grupo-'+num);
		editar.find('.tipo').attr('name','tipo-'+num);
		editar.find('.descricao').attr('name','descricao-'+num);
		
		// ===== Alterar valores dos campos.
		
		editar.find('.identificador').attr('value',variavelNome);
		editar.find('.grupo').attr('value',variavelGrupo);
		editar.find('.descricao').attr('value',variavelDescricao);
		
		// ===== Pegar o campo do modelo tipo string.
		
		var campo = $('.camposModelos').find('.campo.'+variavelTipo).clone();
		
		// ===== Alterar informações do campo.
		
		campo.attr('name','valor-'+num);
		campo.attr('value',valor);
		
		// ===== Alterar a referência da variável.
		
		editar.find('.variavelReferencia').attr('name','ref-'+num);
		editar.find('.variavelReferencia').attr('value',variavelReferencia);
		
		// ===== Incluir o campo no componete editar.
		
		editar.find('.variavelValor').html(campo);
		
		// ===== Incluir o modelo na tela.
		
		variavelCont.after(editar);
		
		// ===== Remover o componente atual e guardar uma cópia para caso haja cancelamento da edição.
		
		var variavelContClone = variavelCont.clone();
		variavelCont.remove();
		
		// ===== Listeners deste componente.
		
		editar.find('.editarBtnCancelar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj2 = $(this);
			
			obj2.parents('.editar').after(variavelContClone);
			obj2.parents('.editar').remove();
		});
		
		editar.find('.ui.dropdown').dropdown();
		editar.find('.ui.dropdown').dropdown('set selected',variavelTipo);
		
		// ===== Reiniciar o formulário.
		
		$.formReiniciar();
	}
	
	function configuracao_administracao_iniciar(){
		// ===== Pegar os modelos.
		
		gestor.configuracao.modelos = new Array();
		gestor.configuracao.modelosRemove = true;
		
		// ===== Pegar o total de itens.
		
		gestor.configuracao.totalItens = parseInt($('#variaveis-total').val());
		
		// ===== Modelos de itens.
		
		var modelos = ['adicionar','mostrar','editar'];
		
		$('.modeloItens .card').each(function(){
			var obj = $(this);
			
			modelos.forEach(function (ele){
				if(obj.hasClass(ele)){
					gestor.configuracao.modelos[ele] = obj.clone();
					if(gestor.configuracao.modelosRemove)obj.remove();
				}
			});
		});
		
		// ===== Listeners principais.
		
		$('.variavelBtnAdicionar,.variavelBtnAdicionarAbaixo').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var abaixo = false;
			var obj = $(this);
			
			if(obj.hasClass('variavelBtnAdicionarAbaixo')){
				abaixo = true;
			}
			
			configuracao_administracao_variavel_adicionar(abaixo);
		});
		
		$(document.body).on('mouseup tap','.variavelBtnExcluir',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this);
			
			$('.ui.modal.confirm').modal({
				onApprove: function() {
					obj.parents('.variavelCont').remove();
					
					configuracao_administracao_variavel_remover();
				}
			});
			
			$('.ui.modal.confirm').modal('show');
		});
		
		$(document.body).on('mouseup tap','.variavelBtnEditar',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this);
			
			configuracao_administracao_variavel_editar(obj);
		});
		
		// ===== Campo Identificador e Grupo
		
		$(document.body).on('keyup','.identificador',function(e){
			if(e.which == 9) return false;
			
			var value = $(this).val();
			
			$.input_delay_to_change({
				obj_ref:this,
				trigger_selector:'#gestor-listener',
				trigger_event:'identificador-change',
				value:value
			});
		});
		
		$(document.body).on('identificador-change','#gestor-listener',function(e,value,p){
			if(!p) p = {};
			
			value = formatar_id(value);
			$(p.obj).val(value);
		});
		
		$(document.body).on('keyup','.grupo',function(e){
			if(e.which == 9) return false;
			
			var value = $(this).val();
			
			$.input_delay_to_change({
				obj_ref:this,
				trigger_selector:'#gestor-listener',
				trigger_event:'grupo-change',
				value:value
			});
		});
		
		$(document.body).on('grupo-change','#gestor-listener',function(e,value,p){
			if(!p) p = {};
			
			value = formatar_id(value);
			$(p.obj).val(value);
		});
		
		// ===== Formatar ID regras.
		
		function formatar_id(id){
			id = id.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			id = id.replace(/[^a-zA-Z0-9 \-]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço.
			id = id.toLowerCase(); // Passar para letras minúsculas
			id = id.trim(); // Remover espaço do início e fim.
			id = id.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			id = id.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			
			return id;
		}
	}
	
	function configuracao(){
		// ===== Identificador da opção de administracao.
		
		if($('#_gestor-configuracao-administracao').length > 0){
			configuracao_administracao_iniciar();
		}
	}
	
	configuracao();
	
});