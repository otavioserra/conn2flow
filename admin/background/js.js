$(document).ready(function(){
	sep = "../../";
	
	var player_id = '#jquery_jplayer_1';
	var video_w = 580;
	var video_h = 400;
	
	if($(player_id).length){
		$(player_id).jPlayer( {
			swfPath: sep+"includes/js/jPlayer/jquery.jplayer",
			supplied: "webm, ogv, m4v",
			errorAlerts: true,
			loop : true,
			preload : "auto",
			ready: function () {
				switch($('#video-tipo').val()){
					case 'webm':
						$(player_id).jPlayer("setMedia", {
							webm: sep+$('#video-url').val()
						});
					break;
					case 'ogv':
						$(player_id).jPlayer("setMedia", {
							ogv: sep+$('#video-url').val()
						});
					break;
					case 'm4v':
						$(player_id).jPlayer("setMedia", {
							m4v: sep+$('#video-url').val()
						});
					break;
					
				}
				
				$(player_id).jPlayer("option","size",{
					width: video_w,
					height: video_h
				});
				
				$(player_id).jPlayer("play");
			},
		});
	}
	
	$('input[type="password"],input[type="text"]').live('click touchstart',function(){
		this.select();
	});
	
	if($("a[rel^='prettyPhoto']").length){
		var prettyphoto_var = {animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true};
		setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto(prettyphoto_var); }, 100);
	}
	
	$('#swfupload-control').swfupload({
		upload_url: "uploadimg.php",
		file_post_name: 'uploadfile',
		file_size_limit : "10000",
		file_types : "*.jpg;*.png;*.gif;*.webm;*.ogv;*.m4v",
		file_types_description : "Image/Video files",
		file_upload_limit : 100,
		flash_url : sep+"includes/js/swfupload/swfupload/swfupload.swf",
		button_image_url : sep+'includes/js/swfupload/swfupload/wdp_buttons_upload_114x29.png',
		button_width : 114,
		button_height : 29,
		button_placeholder : $('#swfupload-button')[0],
		debug: false,
		post_params: {
			'usuario':$("#usuario").val(),
			'sessao':$("#sessao").val(),
		}
	})
	.bind('fileQueued', function(event, file){
		var listitem='<li id="'+file.id+'" >'+
			'Arquivo: <em>'+file.name+'</em> ('+Math.round(file.size/1024)+' KB) <span class="progressvalue" ></span>'+
			'<div class="progressbar" ><div class="progress" ></div></div>'+
			'<p class="status" >Aguardando</p>'+
			'<span class="cancel" >&nbsp;</span>'+
			'</li>';
		$('#log').append(listitem);
		$('li#'+file.id+' .cancel').bind('click', function(){
			var swfu = $.swfupload.getInstance('#swfupload-control');
			swfu.cancelUpload(file.id);
			$('li#'+file.id).slideUp('fast');
		});
		// start the upload since it's queued
		$(this).swfupload('startUpload');
	})
	.bind('fileQueueError', function(event, file, errorCode, message){
		alert('O tamanho do arquivo '+file.name+' é maior que o limite máximo');
	})
	.bind('fileDialogComplete', function(event, numFilesSelected, numFilesQueued){
		$('#queuestatus').text('Arquivos selecionados: '+numFilesSelected+' / Arquivos na Fila: '+numFilesQueued);
	})
	.bind('uploadStart', function(event, file){
		$('#log li#'+file.id).find('p.status').text('Enviando...');
		$('#log li#'+file.id).find('span.progressvalue').text('0%');
		$('#log li#'+file.id).find('span.cancel').hide();
	})
	.bind('uploadProgress', function(event, file, bytesLoaded){
		//Show Progress
		var percentage=Math.round((bytesLoaded/file.size)*100);
		$('#log li#'+file.id).find('div.progress').css('width', percentage+'%');
		$('#log li#'+file.id).find('span.progressvalue').text(percentage+'%');
	})
	.bind('uploadSuccess', function(event, file, serverData){
		var item=$('#log li#'+file.id);
		item.find('div.progress').css('width', '100%');
		item.find('span.progressvalue').text('100%');
		var pathtofile= /*'<a href="uploads/'+file.name+'" target="_blank" >view &raquo;</a>'*/'';
		item.addClass('success').find('p.status').html('Terminou!!!'+pathtofile);
	})
	.bind('uploadComplete', function(event, file){
		// upload has completed, try the next one in the queue
		$(this).swfupload('startUpload');
	});
	
	
});