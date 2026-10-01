$(document).ready(function(){
	$('.profile-tabs .item').tab();

	var homeSearchTimer = null;
	var homeSearchRequest = null;
	var homeSearchSequence = 0;
	var $homeInput = $('#pagina-inicial-busca');
	var $homeValue = $('input[name="pagina_inicial"]');
	var $homeSuggestions = $('#pagina-inicial-suggestions');
	var $homeClear = $('.home-page-clear');

	// O `display` do Fomantic (`.ui.menu`, `.ui.button`) vence o atributo `hidden`; a regra
	// `[hidden]{display:none!important}` do componente devolve o efeito a ele.
	function setHomeHidden($element, hidden){
		$element.prop('hidden', hidden);
	}

	function hideHomeSuggestions(){
		setHomeHidden($homeSuggestions, true);
		$homeSuggestions.empty();
		$homeInput.attr('aria-expanded', 'false');
	}

	function showHomeSuggestions(results){
		$homeSuggestions.empty();
		if(!results || results.length === 0){
			var empty = document.createElement('div');
			empty.className = 'item disabled';
			empty.textContent = $homeSuggestions.attr('data-no-results') || '';
			$homeSuggestions.append(empty);
		} else {
			results.forEach(function(result){
				if(!result || !result.value) return;
				var item = document.createElement('div');
				item.className = 'item';
				item.setAttribute('data-value', result.value);
				item.textContent = result.name || result.value;
				$homeSuggestions.append(item);
			});
		}
		setHomeHidden($homeSuggestions, false);
		$homeInput.attr('aria-expanded', 'true');
	}

	$(document).on('input', '#pagina-inicial-busca', function(){
		var query = ($(this).val() || '').trim();
		$homeValue.val('');
		setHomeHidden($homeClear, true);
		homeSearchSequence++;
		if(homeSearchTimer) clearTimeout(homeSearchTimer);
		if(homeSearchRequest) homeSearchRequest.abort();
		if(query.length < 2){ hideHomeSuggestions(); return; }
		var sequence = homeSearchSequence;
		homeSearchTimer = setTimeout(function(){
			homeSearchRequest = $.ajax({
				type: 'POST',
				url: gestor.raiz + gestor.moduloCaminho + '/',
				dataType: 'json',
				data: { opcao: gestor.moduloOpcao, ajax: 'sim', ajaxOpcao: 'buscar-pagina-inicial', q: query },
				success: function(response){
					if(sequence !== homeSearchSequence) return;
					showHomeSuggestions(response && response.status === 'Ok' ? response.results : []);
				},
				error: function(xhr, status){
					if(status === 'abort' || sequence !== homeSearchSequence) return;
					if(xhr && xhr.status === 401){
						window.open(gestor.raiz + (xhr.responseJSON && xhr.responseJSON.redirect ? xhr.responseJSON.redirect : 'signin/'), '_self');
						return;
					}
					hideHomeSuggestions();
				}
			});
		}, 300);
	});

	$(document).on('click', '#pagina-inicial-suggestions .item[data-value]', function(){
		$homeValue.val($(this).attr('data-value'));
		$homeInput.val($(this).text());
		setHomeHidden($homeClear, false);
		hideHomeSuggestions();
	});
	$(document).on('click', '.home-page-clear', function(){
		$homeValue.val('');
		$homeInput.val('');
		setHomeHidden($homeClear, true);
		hideHomeSuggestions();
	});
	$(document).on('click', function(event){
		if($(event.target).closest('.home-page-search').length === 0) hideHomeSuggestions();
	});
	$(document).on('keydown', function(event){
		if(event.key === 'Escape') hideHomeSuggestions();
	});
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		$('.selectAll').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var pai = $(this).parent().parent().parent();
			
			pai.find('input[type="checkbox"]').prop( "checked", true );
		});
		
		$('.unselectAll').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var pai = $(this).parent().parent().parent();
			
			pai.find('input[type="checkbox"]').prop( "checked", false );
		});
	}
	
});
