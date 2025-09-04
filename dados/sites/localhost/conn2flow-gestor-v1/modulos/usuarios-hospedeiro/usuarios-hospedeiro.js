$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		// ===== Campo Nome
		
		function formatar_nome(nome,trim=true){
			if(trim) nome = nome.trim(); // Remover espaço do início e fim.
			nome = nome.replace(/\s{2,}/g,' '); // Remover a repetição de espaços para um único espaço.
			
			return nome;
		}
		
		$('input[name="nome"]').on('blur',function(e){
			var nome = $(this).val();
			
			if(nome.length > 0)$(this).val(formatar_nome(nome));
		});
		
		$('input[name="email"]').on('blur',function(e){
			var email = $(this).val();
			var usuario = $('input[name="usuario"]').val();
			
			if(usuario.length == 0)$('input[name="usuario"]').val(email);
		});
		
		$(document.body).on('keyup','input[name="nome"]',function(e){
			if(e.which == 9) return false;
			
			var value = $(this).val();
			
			gestor.input_delay_params = {
				obj:this
			};
			
			$.input_delay_to_change({
				trigger_selector:'#gestor-listener',
				trigger_event:'nome-change',
				value:value
			});
		});
		
		$(document.body).on('nome-change','#gestor-listener',function(e,value,p){
			if(!p) p = {};
			
			value = formatar_nome(value,false);
			$(p.obj).val(value);
			
			value = value.trim();
			
			var names = value.split(' ');
			
			var firstName = '';
			var middleName = '';
			var lastName = '';
			
			if(names.length > 2){
				for(var i=0;i<names.length;i++){
					if(i==0){
						firstName = names[i];
					} else if(i==names.length - 1){
						lastName = names[i];
					} else {
						middleName += (middleName.length > 0 ? ' ':'') + names[i];
					}
				}
			} else if(names.length > 1){
				firstName = names[0];
				lastName = names[1];
			} else {
				firstName = names[0];
			}
			
			$('.first-name').html(firstName);
			$('.middle-name').html(middleName);
			$('.last-name').html(lastName);
		});
		
		// ===== Dados do formulário.
		
		var formSelector = '.interfaceFormPadrao';
		
		// ===== CPF e CNPJ controles.
		
		$('.cpf').mask('000.000.000-00', {clearIfNotMatch: true});
		$('.cnpj').mask('00.000.000/0000-00', {clearIfNotMatch: true});
		
		if(gestor.usuariosHosts.cnpj_ativo == 'sim'){
			mudar_documento('cnpj');
		} else {
			mudar_documento('cpf');
		}
		
		$('.controleDoc').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).attr('data-id');
			
			mudar_documento(id);
		});
		
		function mudar_documento(id){
			switch(id){
				case 'cpf':
					$('.cpf').parent().show();
					$('.cnpj').parent().hide();
					$('.controleDoc[data-id="cpf"]').addClass('active');
					$('.controleDoc[data-id="cnpj"]').removeClass('active');
					$('input[name="cnpj_ativo"]').val('nao');
					$(formSelector).form('remove fields', ['cnpj']);
					$(formSelector).form('add rule', 'cpf',{ rules : gestor.interface.regrasValidacao['cpf'].rules });
				break;
				case 'cnpj':
					$('.cpf').parent().hide();
					$('.cnpj').parent().show();
					$('.controleDoc[data-id="cpf"]').removeClass('active');
					$('.controleDoc[data-id="cnpj"]').addClass('active');
					$('input[name="cnpj_ativo"]').val('sim');
					$(formSelector).form('remove fields', ['cpf']);
					$(formSelector).form('add rule', 'cnpj',{ rules : gestor.interface.regrasValidacao['cnpj'].rules });
				break;
			}
		}
		
		// ===== Telefone controle.
		
		var SPMaskBehavior = function (val) {
			return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
		},
		spOptions = {
			onKeyPress: function(val, e, field, options) {
				field.mask(SPMaskBehavior.apply({}, arguments), options);
			},
			clearIfNotMatch: true
		};

		$('.telefone').mask(SPMaskBehavior, spOptions);
	}

});