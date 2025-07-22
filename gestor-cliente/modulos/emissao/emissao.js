$(document).ready(function(){
	
	function start(){
		var identidadeVoucher = {};
		
		// ===== Forms Alterar Identificação.
		
		var formId = 'formAlterarIdentificacao';
		var formSelector = '.formAlterarIdentificacao';
		
		$(formSelector)
			.form({
				fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
				onSuccess(event, fields){
					var obj = $(event.target);
					
					// ===== Pegar os campos do formulário.
					
					var nome = obj.find('input[name="nome"]').val();
					var documento = obj.find('input[name="documento"]').val();
					var telefone = obj.find('input[name="telefone"]').val();
					
					// ===== Colocar eles na identificação do voucher.
					
					obj.parents('.voucherCell').find('.campoNome').html(nome);
					obj.parents('.voucherCell').find('.campoDocumento').html(documento);
					obj.parents('.voucherCell').find('.campoTelefone').html(telefone);
					
					// ===== Adicionar no vetor de identidades.
					
					var voucherID = obj.parents('.voucherCell').attr('data-id');
					
					identidadeVoucher[voucherID] = {
						nome,
						documento,
						telefone,
					};
					
					// ===== Esconder o formulário.
					
					obj.parents('.contAlteracaoIdentidade').hide();
					
					return false;
				}
			});
		
		// ===== Máscara do campo telefone.
		
		var SPMaskBehavior = function (val) {
			return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
		},
		spOptions = {
			onKeyPress: function(val, e, field, options) {
				field.mask(SPMaskBehavior.apply({}, arguments), options);
			},
			clearIfNotMatch: true
		};

		$('.tel').mask(SPMaskBehavior, spOptions);
		
		// ===== Alterar Identidade.
		
		$('.botaoAlterarIdentidade').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parents('.voucherCell').find('.contAlteracaoIdentidade').toggle();
		});
		
		$('.botaoCancelar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parents('.contAlteracaoIdentidade').hide();
		});
		
		// ===== Botão Próximo.
		
		$('.botaoProximo,.botaoProximoGratuito').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			// ===== Criar o formulário.
			
			var form = document.createElement("form");
			
			form.setAttribute("action", gestor.emissao.formUrl);
			form.setAttribute("method", "post");
			
			// ===== Campo opcao.
			
			var opcao = document.createElement("input");
			opcao.setAttribute("type", "hidden");
			opcao.setAttribute("value", "sim");
			opcao.setAttribute("name", "emissao-salvar");
			form.appendChild(opcao);
			
			// ===== Campo código do pedido.
			
			var pedido = document.createElement("input");
			pedido.setAttribute("type", "hidden");
			pedido.setAttribute("value", gestor.emissao.codigo);
			pedido.setAttribute("name", "pedido");
			form.appendChild(pedido);
			
			// ===== Verificar se existe modificação de identidade. Se houver, incluir cada voucher com sua nova identidade.
			
			var identidadeMudou = false;
			var vouchersStr = '';
			
			for(var voucherID in identidadeVoucher) {
				identidadeMudou = true;
				
				// ===== Campo nome.
				
				var nome = document.createElement("input");
				nome.setAttribute("type", "hidden");
				nome.setAttribute("value", identidadeVoucher[voucherID].nome);
				nome.setAttribute("name", voucherID + '_nome');
				form.appendChild(nome);
				
				// ===== Campo documento.
				
				var documento = document.createElement("input");
				documento.setAttribute("type", "hidden");
				documento.setAttribute("value", identidadeVoucher[voucherID].documento);
				documento.setAttribute("name", voucherID + '_documento');
				form.appendChild(documento);
				
				// ===== Campo documento.
				
				var telefone = document.createElement("input");
				telefone.setAttribute("type", "hidden");
				telefone.setAttribute("value", identidadeVoucher[voucherID].telefone);
				telefone.setAttribute("name", voucherID + '_telefone');
				form.appendChild(telefone);
				
				// ===== Adicionar os IDs dos vouchers que mudaram.
				
				vouchersStr += (vouchersStr.length > 0 ? ',' : '') + voucherID;
			}
			
			// ===== Se houver mudança de identidade, criar o campo vouchers com eles.
			
			if(identidadeMudou){
				var vouchers = document.createElement("input");
				vouchers.setAttribute("type", "hidden");
				vouchers.setAttribute("name", "vouchers");
				vouchers.setAttribute("value", vouchersStr);
				form.appendChild(vouchers);
			}
			
			// ===== Se for pedido gratuito.
			
			if($(this).hasClass('botaoProximoGratuito')){
				var pedidoGratuito = document.createElement("input");
				pedidoGratuito.setAttribute("type", "hidden");
				pedidoGratuito.setAttribute("name", "pedidoGratuito");
				pedidoGratuito.setAttribute("value", '1');
				form.appendChild(pedidoGratuito);
			}
			
			// ===== Incluir o formulário na página.
			
			document.body.appendChild(form);
			
			form.submit();
		});
	}
	
	start();
	
});