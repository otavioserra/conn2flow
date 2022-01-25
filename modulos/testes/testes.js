$(document).ready(function(){
	
	$('.ui.toggle.button').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		if($(this).hasClass('active')){
			$(this).removeClass('active');
			$(this).html('<i class="'+$(this).attr('data-icon-inactive')+' icon"></i>'+$(this).attr('data-text-inactive'));
		} else {
			$(this).addClass('active');
			$(this).html('<i class="'+$(this).attr('data-icon-active')+' icon"></i>'+$(this).attr('data-text-active'));
		}
		
	});
	
	
});