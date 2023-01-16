(function( $ ){

    var methods = {
        init : function(options) {
			// ===== default options.
			
			var defaultOptions = {
				verticalResize : 'auto', // Auto / Value resize of main holder - Accept: 'auto' or 'int value' to fix vertical height
			};
			
			var settings = $.extend(defaultOptions, options);
			
			// ===== .
			
			if(settings.verticalResize === 'auto'){
				$(this).css('height','100%');
			} else {
				$(this).css('height',settings.verticalResize+'px');
			}
			
			// ===== Apply img bg for all items.
			
			$(this).find('.items').find('.item').each(function(){
				if($(this).attr('data-src') !== undefined) {
					$(this).css('backgroundImage','url('+$(this).attr('data-src')+')');
				}
			});
			
			// ===== Controls.
			
			var controlLeft = $('<div class"control-left"><i class="chevron left link big icon"></i></div>');
			var controlRight = $('<div class"control-right"><i class="chevron right link big icon"></i></div>');
			
			$(this).append(controlLeft);
			$(this).append(controlRight);
			
			// ===== Show caroucel conteiner.
			
			$(this).show();
        },
        show : function( ) {    },// IS
        hide : function( ) {  },// GOOD
        update : function( content ) {  }// !!!
    };

    $.fn.caroucel = function(methodOrOptions) {
        if ( methods[methodOrOptions] ) {
            return methods[ methodOrOptions ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof methodOrOptions === 'object' || ! methodOrOptions ) {
            // Default to "init"
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  methodOrOptions + ' does not exist on jQuery.caroucel' );
        }    
    };


})( jQuery );