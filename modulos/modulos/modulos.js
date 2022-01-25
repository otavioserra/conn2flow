$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		$('.ui.dropdown')
		  .dropdown()
		;
		
		$(document.body).on('mouseup tap','.campoDel',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().parent().remove();
			
			$.formReiniciar();
		});
		
		$('.campoAdd').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var numCampos = parseInt($('#num-campos').val());
			var item = $('.campoModelo').html();
			
			item = item.replace(/#id-num#/gi,numCampos);
			item = item.replace(/#id-valor#/gi,'');
			item = item.replace(/#value-num#/gi,numCampos);
			item = item.replace(/#value-valor#/gi,'');
			item = item.replace(/#ref-num#/gi,numCampos);
			item = item.replace(/#ref-valor#/gi,'');
			
			$('#num-campos').val((1+numCampos));
			$('.ui.items').append($(item));
			
			$.formReiniciar();
		});
		
		function formatar_id(id){
			id = id.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			id = id.replace(/[^a-zA-Z0-9 \-]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço.
			id = id.toLowerCase(); // Passar para letras minúsculas
			id = id.trim(); // Remover espaço do início e fim.
			id = id.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			id = id.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			
			return id;
		}
		
		// ===== Campo Identificador
		
		$(document.body).on('keyup','.identificador',function(e){
			var value = $(this).val();
			
			gestor.input_delay_params = {
				obj:this
			};
			
			$.input_delay_to_change({
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
		
	}
	
});