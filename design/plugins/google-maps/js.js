function google_maps_style_models_start(p){
	$.ajax({
		type: 'POST',
		dataType: "json",
		url: 'plugins/google-maps/styles.json',
		beforeSend: function(){
		},
		success: function(json){
			b2make.googleMaps.jsonStyles = json;
			google_maps_create(p);
		},
		error: function(txt){
			console.log('ERROR AJAX - google-maps - styles-json - '+txt);
		}
	});
}

function google_maps_geocoder(p){
	if(!p) p = {};
	
	if(!p.gmaps) return false;
	
	var gmaps = p.gmaps;
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	
	$(obj).attr('data-address',p.address);
	
	gmaps.geocoder.geocode({
		'address': p.address
	}, function(results, status){
		if (status == google.maps.GeocoderStatus.OK) {
			var latlong = results[0].geometry.location.lat() + ':' + results[0].geometry.location.lng();
			
			$(obj).attr('data-latlong',latlong);
			
			var lat = -23.5428164;
			var lng = -46.6416659;
			
			if(latlong){
				var latlong_txt = latlong;
				var latlong_arr = latlong_txt.split(':');
				lat = parseFloat(latlong_arr[0]);
				lng = parseFloat(latlong_arr[1]);
			}
			
			gmaps.map.setCenter(new google.maps.LatLng(lat, lng));
			
			google_maps_deleteOverlays(gmaps);
			
			gmaps.markersArray.push(new google.maps.Marker({
				position: {lat: lat, lng: lng},
				map: gmaps.map,
				title: b2make.msgs.googleMapsTitleMarker
			}));
		} else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
			$.dialogbox_open({
				msg: '<p>Você pode modificar no máximo 25 vezes por minuto de endereço!</p><p>Aguarde 1 minuto e tente novamente mudar de endereço.</p>'
			});
		} else {
			console.log('Google Maps: Geolocalização não obteve sucesso por causa dessa razão: '+ status);
		}
	});
}

function google_maps_deleteOverlays(gmaps){
	if (gmaps.markersArray) {
		for (i in gmaps.markersArray) {
			gmaps.markersArray[i].setMap(null);
		}
		gmaps.markersArray.length = 0;
	}
}

function google_maps_create(p){
	if(!p) p = {};
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	var id_pai = $(obj).attr('id');
	var id_map = $(obj).attr('id')+'-map';
	var obj_pai = obj;
	
	//if($(obj_pai).attr('data-style') && !b2make.googleMaps.jsonStyles){
	if(!b2make.googleMaps.jsonStyles){
		google_maps_style_models_start(p);
		return false;
	}
	
	if($(obj).attr('data-area')){
		obj = $(obj).find('.b2make-widget[data-type="conteiner-area"]');
	} else {
		obj = $(obj);
	}
	
	obj = obj.find('.b2make-widget-out').find('.b2make-google-maps');
	
	obj.html('');
	
	var div_map = $('<div id="'+id_map+'" class="b2make-google-maps-map"></div>');
	div_map.appendTo(obj);
	
	div_map.height($(obj_pai).height());
	
	var gmaps = false;
	
	gmaps = {};
	gmaps.map_id = id_map;
	
	gmaps.geocoder = new google.maps.Geocoder();
	gmaps.bounds = new google.maps.LatLngBounds();
	gmaps.markersArray = [];
	
	google_maps_deleteOverlays(gmaps);
	
	var lat = -23.5428164;
	var lng = -46.6416659;
	
	if($(obj_pai).attr('data-latlong')){
		var latlong_txt = $(obj_pai).attr('data-latlong');
		var latlong_arr = latlong_txt.split(':');
		lat = parseFloat(latlong_arr[0]);
		lng = parseFloat(latlong_arr[1]);
	}
	
	var zoom = b2make.googleMaps.zoom;
	
	if($(obj_pai).attr('data-zoom')){
		zoom = parseInt($(obj_pai).attr('data-zoom'));
	}
	
	var styleJson = [];
	
	if($(obj_pai).attr('data-style')){
		styleJson = b2make.googleMaps.jsonStyles[$(obj_pai).attr('data-style')];
	}
	
	var styledMapType = new google.maps.StyledMapType(styleJson,{name: b2make.msgs.googleMapsStyledButton});
	$(obj_pai).attr('data-style-json',JSON.stringify(styledMapType));
	
	gmaps.opts = {
		center: new google.maps.LatLng(lat,lng),
		zoom: zoom,
		maxZoom: b2make.googleMaps.maxZoom,
		scrollwheel: false,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		disableDoubleClickZoom: true,
		mapTypeControlOptions: {
			mapTypeIds: ['styled_map', 'satellite', 'hybrid', 'terrain']
		}
	};
	
	gmaps.map = new google.maps.Map(document.getElementById(gmaps.map_id), gmaps.opts);
	
	gmaps.map.mapTypes.set('styled_map', styledMapType);
    gmaps.map.setMapTypeId('styled_map');
	
	gmaps.markersArray.push(new google.maps.Marker({
		position: {lat: lat, lng: lng},
		map: gmaps.map,
		title: b2make.msgs.googleMapsTitleMarker
	}));
	
	b2make.googleMaps.gmaps[id_pai] = gmaps;
}

function google_maps_start(){
	b2make.googleMaps = {};
	
	b2make.googleMaps.gmaps = new Array();
	b2make.googleMaps.zoom = 12;
	b2make.googleMaps.maxZoom = 20;
	
	if(!b2make.msgs.googleMapsTitleMarker)b2make.msgs.googleMapsTitleMarker = 'Local do Estabelecimento';
	if(!b2make.msgs.googleMapsStyledButton)b2make.msgs.googleMapsStyledButton = 'Mapa';
	
	b2make.google_maps_widget_loaded = true;
	if(b2make.google_maps_widget_added) google_maps_create(null);
	
	$(b2make.widget).each(function(){
		if($(this).attr('data-type') != 'conteiner-area'){
			switch($(this).attr('data-type')){
				case 'google-maps':
					google_maps_create({obj:this});
				break;
			}
		}
	});
	
	$('#b2make-wogm-endereco-input').on('keyup',function(e){
		var value = $(this).val();
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-google-maps-change',
			value:value
		});
	});
	
	$('#b2make-listener').on('b2make-google-maps-change',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		var id = $(obj).attr('id');
		
		google_maps_geocoder({
			gmaps : b2make.googleMaps.gmaps[id],
			address : value
		});
	});
	
	$('#b2make-listener').on('widgets-resize',function(e){
		switch(b2make.conteiner_child_type){
			case 'google-maps':
				var obj = b2make.conteiner_child_obj;
				var obj_pai = b2make.conteiner_child_obj;
				
				if($(obj).attr('data-area')){
					obj = $(obj).find('.b2make-widget[data-type="conteiner-area"]');
				} else {
					obj = $(obj);
				}
				
				obj = obj.find('.b2make-widget-out').find('.b2make-google-maps').find('.b2make-google-maps-map').height($(obj_pai).height());
			break;
		}
	});
	
	$('#b2make-listener').on('widgets-resize-finish widgets-change-width widgets-change-height',function(e){
		switch(b2make.conteiner_child_type){
			case 'google-maps':
				if(b2make.google_maps_widget_loaded) google_maps_create(null);
			break;
		}
	});
	
	$('#b2make-wogm-zoom-input').on('keyup',function(e){
		var value = $(this).val();
		
		value = parseInt(value);
		
		if(!value)value = 0;
		if(value > b2make.googleMaps.maxZoom){
			value = b2make.googleMaps.maxZoom;
		} else if(value < 0){
			value = 0;
		}
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-google-maps-zoom-change',
			value:value
		});
	});
	
	$('#b2make-listener').on('b2make-google-maps-zoom-change',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		var id = $(obj).attr('id');
		var gmaps = b2make.googleMaps.gmaps[id];
		
		gmaps.map.setZoom(value);
		$(obj).attr('data-zoom',value);
	});
	
	$('#b2make-wogm-style-input').on('change',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		$(obj).attr('data-style',value);
		google_maps_create(null);
	});
	
}

var _plugin_id = 'google-maps';

window[_plugin_id] = function(){
	var plugin_id = 'google-maps';
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/html.html',
		beforeSend: function(){
		},
		success: function(txt){
			var html = $('<div>'+txt+'</div>');
			
			var options = html.find('#b2make-widget-options-'+plugin_id).clone();
			options.appendTo('#b2make-widget-options-hide');
			
			$.widget_specific_options_open();
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			$.getScript('https://maps.googleapis.com/maps/api/js?key='+variaveis_js.b2make_gpk, function(){
				google_maps_start();
			});
		},
		error: function(txt){
			console.log('ERROR AJAX - '+plugin_id+' - html - '+txt);
		}
	});
	
	// =========
	
	$('#b2make-'+plugin_id+'-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:plugin_id});
	});
	
	$('#b2make-'+plugin_id+'-callback').on('widget_added',function(e){
		if(b2make.google_maps_widget_added && b2make.google_maps_widget_loaded) google_maps_create(null);
		b2make.google_maps_widget_added = true;

	});
	
	$('#b2make-'+plugin_id+'-callback').on('conteiner_child_open',function(e){
		var obj = b2make.conteiner_child_obj;
		
		if($(obj).attr('data-address')){
			$('#b2make-wogm-endereco-input').val($(obj).attr('data-address'));
		} else {
			$('#b2make-wogm-endereco-input').val('');
		}
		
		
		if($(obj).attr('data-zoom')){
			$('#b2make-wogm-zoom-input').val($(obj).attr('data-zoom'));
		} else {
			$('#b2make-wogm-zoom-input').val('12');
		}
		
		if($(obj).attr('data-style')){
			var option = $('#b2make-wogm-style-input').find("[value='" + $(obj).attr('data-style') + "']");
			option.attr('selected', 'selected');
		} else {
			var option = $('#b2make-wogm-style-input').find(":first");
			option.attr('selected', 'selected');
		}
	});
	
}

var fn = window[_plugin_id];fn();