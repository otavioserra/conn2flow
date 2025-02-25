$(document).ready(function(){
	sep = "../../";
	
	b2make.plataforma_nao_design = true;
	b2make.tinymce_mce_height = 400;
	$.b2make_tinymce_start({selector:'textarea.tinymce',sem_filemanager_sem_instalar_fonts:true});
	
	var tab_index = 0;
	
	if(localStorage['b2make-admin.preferencias.tab-index']){
		tab_index = parseInt(localStorage['b2make-admin.preferencias.tab-index']);
	}
	
	$('#tab-container').tabs({
		active: tab_index,
		activate: function( event, ui ) {
			var instance = $(this).tabs( "instance" );
			
			for(var i=0;i<instance.tabs.length;i++){
				if($(instance.tabs[i]).hasClass('ui-state-active')){
					localStorage.setItem('b2make-admin.preferencias.tab-index',i);
				}
			}
	}});
	
	$('input.alphanum').keyup(function() {
		if (this.value.match(/[^a-zA-Z0-9_-]/g)) {
			this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '');
		}
	});
	
	$(".opcao").hover(
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-cinza.png?v=1)');
		},
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-branco.png?v=1)');
		}
	);
	
	$(".cat-excluir-a").click(function() {
		if(!confirm("Tem certeza que deseja excluir essa categoria e as preferências a ela vinculada?")){return false;}
	});
	
	$(".pre-excluir-a").click(function() {
		if(!confirm("Tem certeza que deseja excluir essa preferência?")){return false;}
	});
	
	$("#form").submit(function() {
		//if(!confirm("Será necessário reiniciar o sistema. Tem certeza que deseja GRAVAR as alterações?")){return false;}
	});
	
	$("#form2").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		
		campo = "categoria"; mens = "Preencha a Categoria"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		campo = "descricao"; mens = "Preencha a Descrição"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		
		if(!enviar){
			return false;
		}
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
	
	
});