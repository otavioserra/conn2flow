$(document).ready(function(){
	sep = "../../";
	var tempo_animacao = 150;
	
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
	
	$(document.body).on('click','.cont-conteudos-excluir',function(){
		var id = this.id;
		var id_arr = id.split('_');
		
		id = id_arr[1];
		
		$('#conteudos_'+id).remove();
		var txt = $('#conteudos').val();
		var txt_arr = txt.split(',');
		
		txt = '';
		
		for(var i=0;i<txt_arr.length;i++){
			if(id != txt_arr[i]){
				txt = txt + (txt.length > 0 ? ',':'') + txt_arr[i];
			}
		}
		
		$('#conteudos').val(txt);
	});
	
	$('.conteudo_select').click(function(){
		var id_arr = this.id.split('_');
		var id_newsletter = id_arr[1];
		var id_conteudo = id_arr[2];
		var checked = $(this).is(':checked');
		var tempo_animacao = 150;
		
		$.ajax({
			type: 'POST',
			url: url_name(),
			data: { ajax : 'sim' , conteudo_select : 'sim', checked : (checked ?'':'sim') , id_newsletter : id_newsletter , id_conteudo : id_conteudo },
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(tempo_animacao);
			},
			success: function(txt){
				var dados = eval('(' + txt + ')');
				$('#ajax_lendo').fadeOut(tempo_animacao);
			},
			error: function(txt){
				$('#ajax_lendo').fadeOut(tempo_animacao);
			}
		});
	});
	
	//we will be using this to cache the responses from the server
	var ajaxCache = {};
	
	$("#conteudos_txt").autocomplete({
		source: function(request, response) {
			//what are we searching for
			var query_id = $(this).attr('element').attr('id');
			//the cacheterm that we use to save it in the cache
			var cachedTerm = (request.term + '' + query_id) . toLowerCase();
			//if the data is in the cache and the data is not too long, use it
			
			$.ajax({
				url: url_name(),
				dataType: "json",
				data: {
					ajax: 1,
					query_id: query_id,
					query: request.term
				},
				success: function(data) {
					if(data){
						//cache the data for later
						ajaxCache[cachedTerm] = data;
						//map the data into a response that will be understood by the autocomplete widget
						response($.map(data, function(item) {
							return {
								label: item.value,
								value: item.value,
								id: item.id
							}
						}));
					}
				}
			});
		},
		//start looking at 3 characters because mysql's limit is 4
		minLength: 1,
		//when you have selected something
		select: function(event, ui) {
			//close the drop down
			
			$('#cont-conteudos').append('<div class="cont-conteudos-entry" id="conteudos_'+ui.item.id+'"><img src="'+sep+'images/icons/excluir.png" id="excluir_'+ui.item.id+'" class="cont-conteudos-excluir"> '+ui.item.value+'</div>');
			
			if($('#conteudos').val().length > 0){
				$('#conteudos').val($('#conteudos').val()+','+ui.item.id);
			} else {		
				$('#conteudos').val(ui.item.id);
			}
			
			this.close;
		},
		//show the drop down
		open: function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		//close the drop down
		close: function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
			$("#conteudos_txt").val('');
		}
	});
	
	$(document.body).on('change','#id_newsletter_layout',function(){
		var id = $(this).val();
		if(id == '1'){
			$('#layout_cont').hide();
		} else {
			$.ajax({
				type: 'POST',
				url: url_name(),
				data: { ajax : 'sim' , layout_change : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					$('#layout').val(dados.layout);
					tinyMCE.activeEditor.setContent(dados.layout);
					$('#layout_cont').show();
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		}
		
		layout_del_visibilidade();
	});
	
	$('#layout_add_txt').keyup(function(e) {
		if(e.keyCode == 13) {
			layout_add();
			e.preventDefault();
			return false;
		}
		
		if(e.keyCode == 27) {
			layout_add_visibilidade('hide');
			return false;
		}
	});
	
	$('#layout_add_txt').keydown(function(e) {
		if(e.keyCode == 13) {
			e.preventDefault();
			return false;
		}
	});
	
	$(document.body).on('click','#layout_add_btn',function(){
		layout_add();
	});
	
	$(document.body).on('click','#layout_add',function(){
		layout_add_visibilidade('visible');
	});
	
	function layout_add(){
		var lay_txt = $('#layout_add_txt').val();
		
		$('#layout_add_txt').val('');
		
		layout_add_visibilidade('hide');
		
		$.ajax({
			type: 'POST',
			url: url_name(),
			data: { ajax : 'sim' , layout_add : lay_txt },
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(tempo_animacao);
			},
			success: function(txt){
				var dados = eval('(' + txt + ')');
				$('#ajax_lendo').fadeOut(tempo_animacao);
				
				$('#id_newsletter_layout').append('<option value="'+dados.id+'" selected="selected">'+lay_txt+'</option>');
				$('#layout').val(dados.layout);
				tinyMCE.activeEditor.setContent(dados.layout);
				$('#layout_cont').show();
				layout_del_visibilidade();
			},
			error: function(txt){
				$('#ajax_lendo').fadeOut(tempo_animacao);
			}
		});
	}
	
	// ================= Remove Layout
	
	$(document.body).on('click','#layout_del',function(){
		var id_layout = $('#id_newsletter_layout option:selected').val();
		
		if(id_layout == '1'){
			return false;
		} else {
			$.ajax({
				type: 'POST',
				url: url_name(),
				data: { ajax : 'sim' , layout_del : id_layout },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					$('#id_newsletter_layout option:selected').remove();
					$('#id_newsletter_layout option[value="1"]').attr('selected', 'selected');
					$('#layout_cont').hide();
					layout_del_visibilidade();
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		}
	});
	
	function layout_add_visibilidade(status){
		if(status == 'visible'){
			$('#layout_add').hide();
			$('#layout_del').hide();
			$('#layout_add_txt').show();
			$('#layout_add_btn').show();
			$('#layout_add_txt').focus();
		} else {
			$('#layout_add').show();
			$('#layout_del').show();
			$('#layout_add_btn').hide();
			$('#layout_add_txt').hide();
			layout_del_visibilidade();
		}
	}
	
	function layout_del_visibilidade(){
		if($('#id_newsletter_layout option:selected').val() == '1'){
			$('#layout_del').hide();
		} else {
			$('#layout_del').show();
		}
	}
	
	layout_del_visibilidade();
	
});