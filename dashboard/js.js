if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

$(document).ready(function(){
	if($('#conteiner-principal').length != 0){
		function grafico(){
			if(variaveis_js.dark_mode){
				var scales = {
					yAxes: [{
						gridLines: {
							zeroLineColor: 'rgba(255,255,255,0.15)',
							color: 'rgba(255,255,255,0.05)'
						},
						ticks: {
							beginAtZero: true,
							fontColor: 'rgba(255,255,255,0.6)'
						}
					}],
					xAxes: [{
						gridLines: {
							zeroLineColor: 'rgba(255,255,255,0.15)',
							color: 'rgba(255,255,255,0.05)'
						},
						ticks: {
							beginAtZero: true,
							fontColor: 'rgba(255,255,255,0.6)'
						}
					}]
				};
			} else {
				var scales = {
					yAxes: [{
						ticks: {
							beginAtZero: true
						}
					}]
				};
			}
			
			var ctx = document.getElementById('monthly-summary').getContext('2d');
			var myChart = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: variaveis_js.monthly_summary_labels,
					datasets: [{
						label: variaveis_js.monthly_summary_label,
						data: variaveis_js.monthly_summary_data,
						backgroundColor: variaveis_js.monthly_summary_colors,
						borderColor: variaveis_js.monthly_summary_colors,
						borderWidth: 0
					}]
				},
				options: {
					scales: scales,
					legend:{
						position: 'right',
						display: false
					},
					aspectRatio: 2.7
				}
			});
			
			var arr = [0,0,0];
			
			if(JSON.stringify(variaveis_js.recent_requests_data) == JSON.stringify(arr)){
				variaveis_js.recent_requests_data = [0.1,0,0];
			}
			
			var ctx2 = document.getElementById('recent-requests').getContext('2d');
			var myChart2 = new Chart(ctx2, {
				type: 'doughnut',
				data: {
					labels: variaveis_js.recent_requests_labels,
					datasets: [{
						data: variaveis_js.recent_requests_data,
						backgroundColor: variaveis_js.recent_requests_colors,
						borderColor: variaveis_js.recent_requests_colors,
						borderWidth: 0
					}]
				},
				options: {
					cutoutPercentage: 30,
					legend:{
						position: 'right',
						display: false
					},
					scales: {
						
					}
				}
			});
			
			var requests_data = variaveis_js.recent_requests_data;
			var requests_colors = variaveis_js.recent_requests_colors;
			var total_index = 3;
			var total = 0;
			var animacao_tempo = 800;
			
			for(var i=0;i<requests_data.length;i++){
				total += parseInt(requests_data[i]);
			}
			
			for(var i=0;i<requests_data.length;i++){
				var percent = Math.floor((parseInt(requests_data[i]) / total) * 100);
				var obj = $('.recent-requests-bars-cont[data-id="'+(i+1)+'"] .recent-requests-bars-holder .recent-requests-bars-percent');
				var obj2 = $('.recent-requests-bars-cont[data-id="'+(i+1)+'"] .recent-requests-bars-value');
				
				obj.css('backgroundColor',requests_colors[i]);
				obj.animate({width:percent+'%'},animacao_tempo);
				obj2.html(requests_data[i] == '0.1' ? '0' : requests_data[i].toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
			}
			
			var obj = $('.recent-requests-bars-cont[data-id="'+(total_index+1)+'"] .recent-requests-bars-holder .recent-requests-bars-percent');
			var obj2 = $('.recent-requests-bars-cont[data-id="'+(total_index+1)+'"] .recent-requests-bars-value');
			
			obj.css('backgroundColor',requests_colors[total_index]);
			if(total > 0)obj.animate({width:'100%'},animacao_tempo);
			obj2.html(total.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."));
		}
		
		function filtros(){
			window.data_filtro = function(){
				window.open('./?filtro-data='+$('input[name="filtro-data"]').val(),'_self');
			}
		}
		
		if(!variaveis_js.financeiro_off){
			grafico();
			filtros();
		}
	}
	
	if($('#b2make-instalacao').length != 0){
		setTimeout(function(){
		   window.location.reload(1);
		}, 5000);
	}
	
});	