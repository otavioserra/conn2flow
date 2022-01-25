$(document).ready(function(){
	
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