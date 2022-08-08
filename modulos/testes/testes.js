$(document).ready(function(){
	
	$('.datepicker').datepicker({
		format: "dd/mm/yy",
		language: "pt-BR",
		datesDisabled: gestor.escalas.datasDesabilitadas,
		multidate: true,
		startDate: gestor.escalas.startDate,
		endDate: gestor.escalas.endDate,
		beforeShowDay: function(date) {
			var datasDestacadas = gestor.escalas.datasDestacadas;
			var day = (date.getDate() < 10 ? '0' : '') + date.getDate();
			var month = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
			var year = date.getFullYear();
			var dateFormated = day + '/' + month + '/' + year;
			
			for(var key in datasDestacadas){
				var dataDestacada = datasDestacadas[key];
				
				if(dateFormated == dataDestacada){
					return {classes: 'highlighted', tooltip: gestor.escalas.highlightedTooltip};
				}
			}
		}
	})
	.on('changeDate', function(e) {
		var dates = '';
		
		for(var key in e.dates){
			var date = e.dates[key];
			var i = parseInt(key);
			
			dates += (dates.length > 0 ? ',':'') + e.format(i);
		}
		
		$('input[name="datas"]').val(dates);
    })
	;
	
	
});