$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		$('.ui.dropdown')
		  .dropdown()
		;
		
		
	}
	
	if($('#_gestor-interface-edit-dados').length > 0){
	
		$('.mini.button').popup({
			delay: {
				show: 150,
				hide: 0
			},
			position:'top left',
			variation:'inverted'
		});
	}
	
});