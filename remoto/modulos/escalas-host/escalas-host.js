$(document).ready(function(){
	
	function start(){
		
		$('.ui.selection.dropdown')
			.dropdown({
			})
		;
		
		$('.datepicker').datepicker({
			format: "dd/mm/yy",
			language: "pt-BR",
			maxViewMode: 0,
			datesDisabled: "17/08/2022",
			multidate: true,
			todayHighlight: true,
			startDate: "01/08/2022",
			endDate: "31/08/2022",
			beforeShowDay: function(date) {
				var hilightedDays = [1,3,8,20,21,16,26,30];
				
				if(~hilightedDays.indexOf(date.getDate())) {
					return {classes: 'highlighted', tooltip: 'Title'};
				}
			}
		});

	}
	
	start();
	
});