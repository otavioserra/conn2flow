var b2make = {};
if(!b2make.msgs)b2make.msgs = {};

b2make.screen_width = window.screen.width;
b2make.mobile = {};
b2make.mobile.width = 600;

var getScript = function(nm) {
	var s = document.createElement('script');
	s.type='text/javascript';
	s.src=nm;
	document.getElementsByTagName('body')[0].appendChild(s);
}

function plataform_pre_load(){
	var b2make_config = document.head.querySelector("[name~=b2make-config][content]").content;
	
	if(b2make_config){
		var arr = b2make_config.split(';');
		
		if(arr[2]){
			b2make.config_data = JSON.stringify(arr[2]);
			
			if(b2make.config_data.mobile_url){
				if(b2make.screen_width <= b2make.mobile.width){
					var url = document.head.querySelector("[rel~=alternate][href]").content;
					b2make.plataform_not_start = true;
					window.open(url,'_self');
				}
			}
		}
	}
}

plataform_pre_load();
