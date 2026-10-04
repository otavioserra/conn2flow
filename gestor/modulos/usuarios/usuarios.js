$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		// req-224: na página Tailwind não há o interface.js legado, que definia este atraso de digitação.
		if(typeof $.input_delay_to_change !== 'function'){
			$.input_delay_to_change = function(p){
				clearTimeout(gestor.input_delay_timer);
				gestor.input_delay_timer = setTimeout(function(){
					$(p.trigger_selector).trigger(p.trigger_event, [p.value, gestor.input_delay_params]);
				}, gestor.input_delay_timeout || 400);
			};
		}

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
		
	}

});