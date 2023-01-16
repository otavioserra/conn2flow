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