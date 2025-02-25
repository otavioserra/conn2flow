var b2make = {};
if (!b2make.msgs) b2make.msgs = {};

b2make.screen_width = window.screen.width;
b2make.googleapis.key = 'SECRERT_KEY';

function ler_scripts(scripts) {
	if (scripts) {
		for (var i = 0; i < scripts.length; i++) {
			if (!b2make.scripts_loaded[scripts[i]]) {
				$.getScript(scripts[i]);
				b2make.scripts_loaded[scripts[i]] = true;
			}
		}
	}
}

function ler_css(stylesheets) {
	if (stylesheets) {
		for (var i = 0; i < stylesheets.length; i++) {
			if (!b2make.stylesheets_loaded[stylesheets[i]]) {
				$('<link rel="stylesheet" type="text/css" href="' + stylesheets[i] + '" >').appendTo("head");
				b2make.stylesheets_loaded[stylesheets[i]] = true;
			}
		}
	}
}

var getUrlParameter = function getUrlParameter(sParam) {
	var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		sURLVariables = sPageURL.split('&'),
		sParameterName,
		i;

	for (i = 0; i < sURLVariables.length; i++) {
		sParameterName = sURLVariables[i].split('=');

		if (sParameterName[0] === sParam) {
			return sParameterName[1] === undefined ? true : sParameterName[1];
		}
	}
};

b2make.scripts = new Array();
b2make.stylesheets = new Array();
b2make.scripts_loaded = new Array();
b2make.stylesheets_loaded = new Array();

b2make.stylesheets.push('https://b2make.com/design/jpicker/css/jPicker-1.1.6.css');
b2make.stylesheets.push('https://b2make.com/design/jpicker/jPicker.css');

b2make.scripts.push('https://b2make.com/design/jpicker/jpicker-1.1.6.js?v=0.9.1');

ler_scripts(b2make.scripts);
ler_css(b2make.stylesheets);

// ==================================== Mobile =============================

function getParameterByName(name, url) {
	if (!url) url = window.location.href;
	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
	if (!results) return null;
	if (!results[2]) return '';
	return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function extractHostname(url) {
	var hostname;
	//find & remove protocol (http, ftp, etc.) and get hostname

	if (url.indexOf("://") > -1) {
		hostname = url.split('/')[2];
	}
	else {
		hostname = url.split('/')[0];
	}

	//find & remove port number
	hostname = hostname.split(':')[0];
	//find & remove "?"
	hostname = hostname.split('?')[0];

	return hostname;
}

function mobile_sitemap(p) {
	if (!p) p = {};

	if (b2make.device != 'phone') return false;

	if (!b2make.mobile_sitemap) {
		var id_func = 'mobile-sitemaps';
		$.ajax({
			cache: false,
			type: 'GET',
			dataType: 'json',
			url: b2make.hostname + '/files/sitemaps/sitemaps.json',
			beforeSend: function () {
			},
			success: function (txt) {
				b2make.mobile_sitemap = txt.sites;
				mobile_sitemap(p);
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
			}
		});
	} else {
		if (!p.obj) return false;

		var sitemap = b2make.mobile_sitemap;
		var protomatch = /^(https?|ftp):\/\//;
		var obj = p.obj;

		switch ($(obj).attr('data-type')) {
			case 'texto':
			case 'imagem':
				if ($(obj).attr('data-hiperlink')) {
					var url = $(obj).attr('data-hiperlink');

					url = url.replace(protomatch, '');

					if (sitemap)
						for (var i = 0; i < sitemap.length; i++) {
							var url_sitemap = sitemap[i].url.replace(protomatch, '');

							if (url_sitemap == url) {
								if (sitemap[i].url_mobile) {
									$(obj).attr('data-hiperlink', sitemap[i].url_mobile);
									break;
								}
							}
						}
				}
				break;
			case 'services':
				$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').each(function () {
					var url = $(this).find('.b2make-service-comprar').attr('href');

					url = url.replace(protomatch, '');

					if (sitemap)
						for (var i = 0; i < sitemap.length; i++) {
							var url_sitemap = sitemap[i].url.replace(protomatch, '');

							if (url_sitemap == url) {
								if (sitemap[i].url_mobile) {
									$(this).find('.b2make-service-comprar').attr('href', sitemap[i].url_mobile);
									break;
								}
							}
						}
				});
				break;
			case 'contents':
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').each(function () {
					var url = $(this).attr('data-href');
					var href = $(this).attr('href');

					url = url.replace(protomatch, '');

					if (sitemap)
						for (var i = 0; i < sitemap.length; i++) {
							var url_sitemap = sitemap[i].url.replace(protomatch, '');

							if (url_sitemap == url) {
								if (sitemap[i].url_mobile) {
									$(this).find('.b2make-content-acessar').attr('href', sitemap[i].url_mobile);
									if (href) $(this).attr('href', sitemap[i].url_mobile);
									break;
								}
							}
						}
				});
				break;

		}
	}
}

function mobile_change_page(p) {
	if (!p) p = {};

	if (!b2make.mobile_sitemap) {
		var id_func = 'mobile-sitemaps';
		$.ajax({
			cache: false,
			type: 'GET',
			dataType: 'json',
			url: b2make.hostname + '/files/sitemaps/sitemaps.json',
			beforeSend: function () {
			},
			success: function (txt) {
				b2make.mobile_sitemap = txt.sites;
				mobile_change_page(p);
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
			}
		});
	} else {
		var sitemap = b2make.mobile_sitemap;
		var protomatch = /^(https?|ftp):\/\//;

		switch (p.id) {
			case 'b2make-mobile-link-desktop':
				var url = $('link[rel="canonical"]').attr('href');

				url = url.replace(protomatch, '');

				if (sitemap)
					for (var i = 0; i < sitemap.length; i++) {
						var url_sitemap = sitemap[i].url.replace(protomatch, '');

						if (url_sitemap == url) {
							if (sitemap[i].url_mobile) {
								$(obj).attr('data-hiperlink', sitemap[i].url_mobile);
								window.open(sites[i].url_mobile, '_self');
								break;
							}
						}
					}
				break;

		}
	}
}

function mobile() {
	if (!b2make.msgs.mobileLinkDesktop) b2make.msgs.mobileLinkDesktop = 'Acessar vers&atilde;o desktop';
	if (!b2make.msgs.mobileLinkMobile) b2make.msgs.mobileLinkMobile = 'Acessar vers&atilde;o mobile';

	b2make.device = ($('#b2make-pagina-options').attr('data-device') ? $('#b2make-pagina-options').attr('data-device') : 'desktop');

	if (b2make.device == 'phone') {
		b2make.desktop_domain = extractHostname($('link[rel="canonical"]').attr('href'));
		b2make.mobile_domain = extractHostname(location.hostname.toLowerCase());
	}

	var hostname = location.hostname.toLowerCase();

	if (b2make.device == 'phone') {
		b2make.hostname = '//' + b2make.desktop_domain;
	} else {
		b2make.hostname = '//' + extractHostname(hostname);
	}

	b2make.mobile = {};

	b2make.mobile.width = 600;

	if (b2make.screen_width <= b2make.mobile.width) {
		b2make.mobile.active = true;
	} else {
		b2make.mobile.active = false;
	}

	if (getParameterByName('b2make-version-desktop')) {
		localStorage.setItem('b2make.versao_desktop', true);
	}

	if (getParameterByName('b2make-version-mobile')) {
		localStorage.setItem('b2make.versao_mobile', true);
	}

	if (b2make.mobile.active && !localStorage.getItem('b2make.versao_desktop') && !localStorage.getItem('b2make.versao_mobile')) {
		var id_func = 'mobile-sitemaps';
		$.ajax({
			cache: false,
			type: 'GET',
			dataType: 'json',
			url: b2make.hostname + '/files/sitemaps/sitemaps.json',
			beforeSend: function () {
			},
			success: function (txt) {
				var sites = txt.sites;

				var url_page = window.location.href;
				var protomatch = /^(https?|ftp):\/\//;

				url_page = url_page.replace(protomatch, '');

				if (sites)
					for (var i = 0; i < sites.length; i++) {
						var url_sitemap = sites[i].url.replace(protomatch, '');
						url_sitemap = url_sitemap.replace(/^\/\//, '');

						if (url_sitemap == url_page) {
							if (sites[i].url_mobile) {
								window.open(sites[i].url_mobile, '_self');
							}
						}
					}
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
			}
		});
	}

	$(document.body).on('mouseup tap', '.b2make-mobile-link', function (e) {
		if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

		var id = $(this).attr('id');

		switch (id) {
			case 'b2make-mobile-link-desktop':
				var url = $('link[rel="canonical"]').attr('href');
				localStorage.removeItem('b2make.versao_mobile');
				window.open(url + '?b2make-version-desktop=true', '_self');
				break;
			case 'b2make-mobile-link-mobile':
				var url = $('link[rel="alternate"]').attr('href');
				localStorage.removeItem('b2make.versao_desktop');
				window.open(url + '?b2make-version-mobile=true', '_self');
				break;
		}
	});
}

mobile();

$(document).ready(function () {
	function debug_box(p) {
		if (!p) p = {};


		if (!b2make.debug_box) {
			b2make.debug_box = {};
		}

		b2make.debug_box.title = '<b style="font-size:20px;">Debug</b><br><br>';

		if (!b2make.debug_box.cont) {
			b2make.debug_box.cont = $('<div id="b2make-debug-box"></div>');

			b2make.debug_box.cont.css({
				'zIndex': '99999999',
				'padding': '10px',
				'position': 'absolute',
				'top': '20px',
				'left': '20px',
				'width': '400px',
				'height': '300px',
				'overflow-y': 'auto',
				'border': '3px #CCC solid',
				'background-color': '#FFF'
			});

			b2make.debug_box.cont.appendTo('body');
		}

		b2make.debug_box.cont.show();

		b2make.debug_box.cont.html(b2make.debug_box.title + p.msg);
	}

	$(window).resize(function () {
		if ($('#b2make-widget-menu-holder').length > 0) {
			if (widget_menu_holder) {
				var widget_menu_holder = $('#b2make-widget-menu-holder');

				var widget_menu = widget_menu_holder.attr('data-menu-atual');

				widget_menu_holder.css('top', $('#' + widget_menu).offset().top + 'px');
				widget_menu_holder.css('left', $('#' + widget_menu).offset().left + 'px');
			}
		}

		images_conteiners_update();
		dialogbox_position();
	});

	$(window).bind('mouseup tap', function (e) {
		if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
		if (b2make.menu_holder) {
			if (b2make.menu_holder.attr('data-open') == '1') {
				b2make.menu_holder.attr('data-open', '0');
				b2make.menu_holder.hide();
			}
		}
	});

	$.dialogbox_open = function (p) {
		if (!b2make.dialogbox_cont) {

			b2make.dialogbox_cont = $('<div id="b2make-dialogbox"><div id="b2make-dialogbox-head"></div><div id="b2make-dialogbox-msg"></div><div id="b2make-dialogbox-btns"></div></div>');
			b2make.dialogbox_cont.appendTo('body');
		}

		if (!b2make.dialogbox) {
			if (!p) p = {};
			b2make.dialogbox = true;

			if (!b2make.dialbox_default_width) b2make.dialbox_default_width = $("#b2make-dialogbox").width();
			if (!b2make.dialbox_default_height) b2make.dialbox_default_height = $("#b2make-dialogbox").height();

			if (!p.width) if (b2make.dialbox_default_width != $("#b2make-dialogbox").width()) $("#b2make-dialogbox").width(b2make.dialbox_default_width);
			if (!p.height) if (b2make.dialbox_default_height != $("#b2make-dialogbox").height()) $("#b2make-dialogbox").height(b2make.dialbox_default_height);

			if (p.width) $("#b2make-dialogbox").width(p.width);
			if (p.height) $("#b2make-dialogbox").height(p.height);

			$("#b2make-dialogbox-head").html((p.title ? p.title : (p.confirm ? b2make.msgs.confirmTitle : b2make.msgs.alertTitle)));
			if (!p.coneiner) $("#b2make-dialogbox-msg").html((p.msg ? p.msg : (p.confirm ? b2make.msgs.confirmMsg : b2make.msgs.alertMsg)));
			$("#b2make-dialogbox-btns").html('');

			if (p.coneiner) {
				$("#b2make-dialogbox-msg").html('');
				$("#b2make-dialogbox-msg").append($('#' + p.coneiner));
				b2make.dialogbox_conteiner = p.coneiner;
			}

			if (!p.no_btn_default) {
				if (p.message) {
					$('<div class="b2make-dialogbox-btn b2make-dialogbox-btn-click-dont-close' + (p.calback_yes ? ' ' + p.calback_yes : '') + '"' + (p.calback_yes_extra ? ' ' + p.calback_yes_extra : '') + '>' + (p.message_btn_yes_title ? p.message_btn_yes_title : b2make.msgs.messageBtnYes) + '</div>').appendTo("#b2make-dialogbox-btns");

					if (p.more_buttons) {
						var btns = p.more_buttons;

						for (var i = 0; i < btns.length; i++) {
							$('<div class="b2make-dialogbox-btn' + (btns[i].calback ? ' ' + btns[i].calback : '') + '"' + (btns[i].calback_extra ? ' ' + btns[i].calback_extra : '') + '>' + btns[i].title + '</div>').appendTo("#b2make-dialogbox-btns");
						}
					}

					$('<div class="b2make-dialogbox-btn' + (p.calback_no ? ' ' + p.calback_no : '') + '"' + (p.calback_no_extra ? ' ' + p.calback_no_extra : '') + '>' + (p.message_btn_no_title ? p.message_btn_no_title : b2make.msgs.messageBtnNo) + '</div>').appendTo("#b2make-dialogbox-btns");
				} else if (p.confirm) {
					$('<div class="b2make-dialogbox-btn' + (p.calback_no ? ' ' + p.calback_no : '') + '"' + (p.calback_no_extra ? ' ' + p.calback_no_extra : '') + '>' + (p.confirm_btn_no_title ? p.confirm_btn_no_title : b2make.msgs.confirmBtnNo) + '</div>').appendTo("#b2make-dialogbox-btns");
					$('<div class="b2make-dialogbox-btn' + (p.calback_yes ? ' ' + p.calback_yes : '') + '"' + (p.calback_yes_extra ? ' ' + p.calback_yes_extra : '') + '>' + (p.confirm_btn_yes_title ? p.confirm_btn_yes_title : b2make.msgs.confirmBtnYes) + '</div>').appendTo("#b2make-dialogbox-btns");
				} else {
					$('<div class="b2make-dialogbox-btn' + (p.calback_no ? ' ' + p.calback_alert : '') + '"' + (p.calback_alert_extra ? ' ' + p.calback_alert_extra : '') + '>' + (p.alert_btn_title ? p.alert_btn_title : b2make.msgs.alertBtn) + '</div>').appendTo("#b2make-dialogbox-btns");
				}
			}

			b2make.dialogbox_callback_yes = p.calback_yes;

			var top_start = -10 - $("#b2make-dialogbox").height();
			var top_stop = $(window).height() / 2 - $("#b2make-dialogbox").height() / 2;
			var left = $(window).width() / 2 - $("#b2make-dialogbox").width() / 2;

			$("#b2make-dialogbox").css('top', top_start);
			$("#b2make-dialogbox").css('left', left);
			$("#b2make-dialogbox").show();

			$("#b2make-dialogbox").animate({ top: top_stop }, b2make.dialogboxAnimateTime, function () {
				if (p.coneiner) {
					$('#' + p.coneiner).find('input').filter(':visible:first').focus();
					$('#' + p.coneiner).find('input').filter(':visible:first').tooltip("close");
				}
			});
		}
	}

	function dialogbox_shake() {
		$("#b2make-dialogbox").stop().effect("shake");
	}

	function dialogbox_open_after(p) {
		setTimeout(function () {
			$.dialogbox_open(p);
		}, b2make.dialogboxAnimateTime);
	}

	function dialogbox_close() {
		if (b2make.dialogbox) {
			b2make.dialogbox = false;

			var top_stop = -10 - $("#b2make-dialogbox").height();

			$("#b2make-dialogbox").animate({ top: top_stop }, b2make.dialogboxAnimateTime, "swing", function () {
				if (b2make.dialogbox_conteiner) {
					formulario_resetar(b2make.dialogbox_conteiner);
					$('#' + b2make.dialogbox_conteiner).appendTo($('#b2make-formularios'));
					b2make.dialogbox_conteiner = false;
				}
			});
		}
	}

	function dialogbox_position() {
		if (b2make.dialogbox) {
			$("#b2make-dialogbox").stop();
			var top = $(window).height() / 2 - $("#b2make-dialogbox").height() / 2;
			var left = $(window).width() / 2 - $("#b2make-dialogbox").width() / 2;

			$("#b2make-dialogbox").css('top', top);
			$("#b2make-dialogbox").css('left', left);
		} else {
			var top = -10 - $("#b2make-dialogbox").height();;
			var left = $(window).width() / 2 - $("#b2make-dialogbox").width() / 2;

			$("#b2make-dialogbox").css('top', top);
			$("#b2make-dialogbox").css('left', left);
		}
	}

	function dialogbox() {
		b2make.dialogbox = false;
		if (!b2make.dialogboxAnimateTime) b2make.dialogboxAnimateTime = 250;
		if (!b2make.msgs.alertTitle) b2make.msgs.alertTitle = "Alerta";
		if (!b2make.msgs.confirmTitle) b2make.msgs.confirmTitle = "Confirma&ccedil;&atilde;o";
		if (!b2make.msgs.alertMsg) b2make.msgs.alertMsg = "Esta op&ccedil;&atilde;o n&atilde;o est&aacute; ativada";
		if (!b2make.msgs.alertBtn) b2make.msgs.alertBtn = "Ok";
		if (!b2make.msgs.confirmMsg) b2make.msgs.confirmMsg = "Tem certeza que deseja proseguir?";
		if (!b2make.msgs.confirmBtnYes) b2make.msgs.confirmBtnYes = "Sim";
		if (!b2make.msgs.confirmBtnNo) b2make.msgs.confirmBtnNo = "N&atilde;o";
		if (!b2make.msgs.messageBtnNo) b2make.msgs.messageBtnNo = "Cancelar";
		if (!b2make.msgs.messageBtnYes) b2make.msgs.messageBtnYes = "Enviar";

		$(".b2make-dialogbox-btn").live('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if (!$(this).hasClass('b2make-dialogbox-btn-click-dont-close')) dialogbox_close();
		});
	}

	dialogbox();

	function images_conteiners_update() {
		var ww = parseInt($(window).width());

		$('.b2make-widget').each(function () {
			if ($(this).attr('data-type') != 'conteiner-area') {
				switch ($(this).attr('data-type')) {
					case 'conteiner':
						var iw = $(this).attr('data-image-width');
						var ih = $(this).attr('data-image-height');
						var position_y = ($(this).attr('data-background-position-y') ? $(this).attr('data-background-position-y') : 'top');
						var position_x = ($(this).attr('data-background-position-x') ? $(this).attr('data-background-position-x') : 'left');
						var repeat = ($(this).attr('data-background-repeat') ? $(this).attr('data-background-repeat') : 'completar');
						var ch = $(this).height();

						if (
							position_y != 'center' &&
							position_y != 'top' &&
							position_y != 'bottom'
						)
							position_y = position_y + 'px';

						if (
							position_x != 'center' &&
							position_x != 'left' &&
							position_x != 'right'
						)
							position_x = position_x + 'px';


						if (iw && ih) {
							iw = parseInt(iw);
							ih = parseInt(ih);
							ch = parseInt(ch);

							if (repeat == 'completar') {
								if (iw < ih) {
									if (position_x != 'left') position_x = 'left';
								}

								var nw = Math.floor((iw * ch) / ih);
								var dw = (nw - ww) / 2;

								if (nw >= ww) {
									$(this).css('background-size', nw + 'px ' + ch + 'px');
									$(this).css('background-position', '-' + dw + 'px ' + position_y);
								} else {
									var nh = Math.floor((ww * ih) / iw);
									$(this).css('background-size', ww + 'px ' + nh + 'px');
									$(this).css('background-position', position_x + ' ' + position_y);
								}
							} else {
								if (ww < iw) {
									if (position_x == 'center') {
										var dw = (iw - ww) / 2;
										$(this).css('background-position', '-' + dw + 'px ' + position_y);
									}
								} else {
									$(this).css('background-position', position_x + ' ' + position_y);
								}
							}
						}
						break;

				}
			}
		});
	}

	function checkMail(mail) {
		var er = new RegExp(/^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/);
		if (typeof (mail) == "string") {
			if (er.test(mail)) { return true; }
		} else if (typeof (mail) == "object") {
			if (er.test(mail.value)) {
				return true;
			}
		} else {
			return false;
		}
	}

	function player_widget_controls(obj, type) {
		var album = false;

		switch (type) {
			case 'player':
				var obj_id = $(obj).attr('id');
				var player_id = '#b2make-jplayer-' + obj_id;
				var player_control = '#b2make-player-control-' + obj_id + ' ';
				break;
			case 'albummusicas':

				var obj_id = $(obj).parent().parent().parent().attr('id');
				var id_album = $(obj).attr('data-album-musicas-id');
				var player_id = '#b2make-jplayer-player-' + obj_id + '-' + id_album;
				var player_control = '#b2make-player-control-' + obj_id + '-' + id_album + ' ';

				album = true;
				break;

		}

		if (!b2make.player) {
			b2make.player = new Array();
		}

		if (!$(obj).attr('data-music-list')) {
			return;
		}

		b2make.player[player_id] = {};

		b2make.player[player_id].lista_musicas_str = $(obj).attr('data-music-list');
		b2make.player[player_id].lista_musicas = new Array();
		b2make.player[player_id].lista_musicas_tit = new Array();
		b2make.player[player_id].total_musicas = 0;
		b2make.player[player_id].num_musica = 0;
		b2make.player[player_id].mudou_musicas = false;
		b2make.player[player_id].auto_play = ($(obj).attr('data-start-automatico') ? true : false);
		b2make.player[player_id].player_pause = true;
		if (album) b2make.player[player_id].album = true;

		var lm_aux = b2make.player[player_id].lista_musicas_str.split('<;>');
		var i;
		var aux;

		for (i = 0; i < lm_aux.length; i++) {
			if (lm_aux[i]) {
				aux = lm_aux[i].split('<,>');
				b2make.player[player_id].lista_musicas_tit[i] = aux[0];
				b2make.player[player_id].lista_musicas[i] = aux[1];
				b2make.player[player_id].total_musicas++;
			}
		}

		$(player_id).jPlayer({
			swfPath: "jplayer",
			ready: function () {
				$(player_id).jPlayer("setMedia", {
					mp3: b2make.player[player_id].lista_musicas[0] // Defines the mp3 url
				});

				if (b2make.player[player_id].auto_play) {
					b2make.player_playing = player_id;
					$(player_id).jPlayer("play");
					b2make.player[player_id].player_pause = false;
				}

				$(player_control + ".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
				$(player_control + ".b2make-player-time").text($.jPlayer.convertTime(0));
				if (b2make.player[player_id].auto_play) $(player_control + ".b2make-player-play").addClass("b2make-player-pause_css");

				var pai = $(player_id).parent().parent().parent().parent();
				var color_playing_start = (pai.attr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-1-ahex')) : '#A1BC31');
				var color_not_start = (pai.attr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-2-ahex')) : '#000000');

				$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function () {
					var musica_num = parseInt($(this).attr('data-musica-num'));

					if (musica_num == b2make.player[player_id].num_musica) {
						$(this).addClass('b2make-albummusicas-widget-playing');
						$(this).css('color', color_playing_start);
					} else {
						$(this).removeClass('b2make-albummusicas-widget-playing');
						$(this).css('color', color_not_start);
					}
				});

				$(player_control + ".b2make-player-prev").on('mouseup tap', function (e) {
					if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
					if (b2make.player_playing) if (b2make.player_playing != player_id) {
						$(b2make.player_playing).jPlayer("pause");
						b2make.player[b2make.player_playing].player_pause = true;
						$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
					}
					b2make.player_playing = player_id;

					$(player_id).jPlayer("stop");
					b2make.player[player_id].num_musica--;

					if (b2make.player[player_id].num_musica < 0) {
						b2make.player[player_id].num_musica = b2make.player[player_id].total_musicas - 1;
					}

					$(player_id).jPlayer("setMedia", {
						mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
					});
					$(player_id).jPlayer("play");
					b2make.player[player_id].player_pause = false;
					$(player_control + ".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
					$(player_control + ".b2make-player-play").addClass("b2make-player-pause_css");

					if (b2make.player[player_id].album) {
						var color_playing = (pai.attr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.attr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-2-ahex')) : '#000000');

						$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function () {
							var musica_num = parseInt($(this).attr('data-musica-num'));

							if (musica_num == b2make.player[player_id].num_musica) {
								$(this).addClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_playing);
							} else {
								$(this).removeClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_not);
							}
						});
					}
				});

				$(player_control + ".b2make-player-play").on('mouseup tap', function (e) {
					if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
					if (b2make.player_playing) if (b2make.player_playing != player_id) {
						$(b2make.player_playing).jPlayer("pause");
						b2make.player[b2make.player_playing].player_pause = true;
						$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
					}
					b2make.player_playing = player_id;

					if (b2make.player[player_id].player_pause) {
						$(player_id).jPlayer("play");
						b2make.player[player_id].player_pause = false;
						$(player_control + ".b2make-player-play").addClass("b2make-player-pause_css");
					} else {
						$(player_id).jPlayer("pause");
						b2make.player[player_id].player_pause = true;
						$(player_control + ".b2make-player-play").removeClass("b2make-player-pause_css");
					}
				});

				$(player_control + ".b2make-player-stop").on('mouseup tap', function (e) {
					if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
					$(player_id).jPlayer("stop");
					b2make.player[player_id].player_pause = true;
					$(player_control + ".b2make-player-play").addClass("b2make-player-pause_css");
					b2make.player_playing = player_id;
				});

				$(player_control + ".b2make-player-next").on('mouseup tap', function (e) {
					if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
					if (b2make.player_playing) if (b2make.player_playing != player_id) {
						$(b2make.player_playing).jPlayer("pause");
						b2make.player[b2make.player_playing].player_pause = true;
						$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
					}
					b2make.player_playing = player_id;

					$(player_id).jPlayer("stop");
					b2make.player[player_id].num_musica++;

					if (b2make.player[player_id].num_musica >= b2make.player[player_id].total_musicas) {
						b2make.player[player_id].num_musica = 0;
					}

					$(player_id).jPlayer("setMedia", {
						mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
					});
					$(player_id).jPlayer("play");
					b2make.player[player_id].player_pause = false;
					$(player_control + ".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
					$(player_control + ".b2make-player-play").addClass("b2make-player-pause_css");

					if (b2make.player[player_id].album) {
						var color_playing = (pai.attr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.attr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-2-ahex')) : '#000000');

						$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function () {
							var musica_num = parseInt($(this).attr('data-musica-num'));

							if (musica_num == b2make.player[player_id].num_musica) {
								$(this).addClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_playing);
							} else {
								$(this).removeClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_not);
							}
						});
					}
				});

				$(player_id).bind($.jPlayer.event.ended + ".jp-repeat", function (event) { // Using ".jp-repeat" namespace so we can easily remove this event
					b2make.player[player_id].num_musica++;

					if (b2make.player[player_id].num_musica >= b2make.player[player_id].total_musicas) {
						b2make.player[player_id].num_musica = 0;
					}

					$(player_id).jPlayer("setMedia", {
						mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
					});
					$(player_control + ".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
					$(player_id).jPlayer("play");

					if (b2make.player[player_id].album) {
						var color_playing = (pai.attr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.attr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-2-ahex')) : '#000000');

						$(player_id).parent().find('.b2make-albummusicas-widget-list-mp3s').find('.b2make-albummusicas-widget-mp3').each(function () {
							var musica_num = parseInt($(this).attr('data-musica-num'));

							if (musica_num == b2make.player[player_id].num_musica) {
								$(this).addClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_playing);
							} else {
								$(this).removeClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_not);
							}
						});
					}
				});

				$(player_id).bind($.jPlayer.event.timeupdate, function (event) {
					$(player_control + ".b2make-player-time").text($.jPlayer.convertTime(event.jPlayer.status.currentTime));

					if (b2make.player[player_id].mudou_musicas) {
						b2make.player[player_id].mudou_musicas = false;
						$(player_control + ".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
						$(player_control + ".b2make-player-play").addClass("b2make-player-pause_css");
					}
				});

				if (b2make.player[player_id].album) {
					var parent_id = $(player_id).parent().attr('id');
					$('#' + parent_id + ' .b2make-albummusicas-widget-list-mp3s .b2make-albummusicas-widget-mp3').live('mouseup tap', function (e) {
						if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
						b2make.player[player_id].num_musica = parseInt($(this).attr('data-musica-num'));

						var color_playing = (pai.attr('data-lista-color-1-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-1-ahex')) : '#A1BC31');
						var color_not = (pai.attr('data-lista-color-2-ahex') ? jpicker_ahex_2_rgba(pai.attr('data-lista-color-2-ahex')) : '#000000');

						$('#' + parent_id + ' .b2make-albummusicas-widget-list-mp3s .b2make-albummusicas-widget-mp3').each(function () {
							var musica_num = parseInt($(this).attr('data-musica-num'));

							if (musica_num == b2make.player[player_id].num_musica) {
								$(this).addClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_playing);

								if (b2make.player_playing) if (b2make.player_playing != player_id) {
									$(b2make.player_playing).jPlayer("pause");
									b2make.player[b2make.player_playing].player_pause = true;
									$(b2make.player_playing).parent().find(".b2make-player-controls").find(".b2make-player-play").removeClass("b2make-player-pause_css");
								}
								b2make.player_playing = player_id;

								$(player_id).jPlayer("stop");
								$(player_id).jPlayer("setMedia", {
									mp3: b2make.player[player_id].lista_musicas[b2make.player[player_id].num_musica]
								});
								$(player_id).jPlayer("play");
								b2make.player[player_id].player_pause = false;
								$(player_control + ".b2make-player-tit").html(b2make.player[player_id].lista_musicas_tit[b2make.player[player_id].num_musica]);
								$(player_control + ".b2make-player-play").addClass("b2make-player-pause_css");
							} else {
								$(this).removeClass('b2make-albummusicas-widget-playing');
								$(this).css('color', color_not);
							}
						});
					});
				}
			}
		});
	}

	function slideshow_animation_start(obj) {
		if (b2make.slideshow_start[$(obj).attr('id')]) {
			var width = $(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image').width();
			var direction = 'left';
			var tempo = 3000;

			if (!$(obj).attr('data-animation')) {
				$(obj).attr('data-animation', true);
			}
			if ($(obj).attr('data-direction')) {
				direction = $(obj).attr('data-direction');
			}
			if ($(obj).attr('data-tempo')) {
				tempo = parseInt($(obj).attr('data-tempo'));
			}

			if (direction == 'left') {
				$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').stop().animate({
					left: -width
				}, tempo, 'linear', function () {
					$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image:first-child').appendTo($(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder'));
					$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').css('left', 0);
					slideshow_animation_start(obj);
				});
			} else {
				$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').find('div.b2make-slideshow-widget-image:last-child').prependTo($(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder'));
				$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').css('left', -width);
				$(obj).find('div.b2make-widget-out').find('div.b2make-slideshow-widget-holder').stop().animate({
					left: 0
				}, tempo, 'linear', function () {
					slideshow_animation_start(obj);
				});
			}
		}
	}

	function start_classes() {
		images_conteiners_update();
		if (localStorage.getItem('b2make.page_reload') == 1) {
			localStorage.setItem('b2make.page_reload', null);
			location.reload(true);
		}

		if ($('#b2make-pagina-options').attr('instagram_token')) {
			b2make.instagram_token = $('#b2make-pagina-options').attr('instagram_token');
		}

		b2make.conteiners_height = 0;

		if (!b2make.formularioObrigatorioText) b2make.formularioObrigatorioText = "* Campos obrigat&oacute;rios!";
		if (!b2make.formularioEmailInvalido) b2make.formularioEmailInvalido = "* Email inv&aacute;lido!";
		if (!b2make.formularioEmailEnviado) b2make.formularioEmailEnviado = "* Contato enviado!";

		$('.b2make-widget[data-type="conteiner-area"]').css('cursor', "");
		$('.b2make-widget[data-type="conteiner-area"]').removeAttr('cursor');
		$('.b2make-widget[data-type="conteiner"]').css('cursor', "");
		$('.b2make-widget[data-type="conteiner"]').removeAttr('cursor');

		var addthis_exec;

		b2make.widget_start = new Array();

		$('.b2make-widget').each(function () {
			$(this).css('cursor', 'default');
			if ($(this).attr('data-type') != 'conteiner-area') {
				switch ($(this).attr('data-type')) {
					case 'conteiner':
						b2make.conteiners_height = b2make.conteiners_height + parseInt($(this).height());
						$(this).css('width', '100%');

						if (b2make.device != 'phone') {
							if ($(this).attr('data-area-largura')) {
								$(this).css('min-width', $(this).attr('data-area-largura') + 'px');
							}
						}
						break;
					case 'player':
						player_widget_controls($(this), 'player');
						break;
					case 'slideshow':
						var obj = $(this).get(0);

						if (!b2make.slideshow_start) b2make.slideshow_start = new Array();

						b2make.slideshow_start[$(obj).attr('id')] = true;
						slideshow_animation_start(obj);
						break;
					case 'albummusicas':
						$(this).find('.b2make-widget-out').find('.b2make-albummusicas-widget-holder').find('.b2make-albummusicas-widget-album').each(function () {
							player_widget_controls($(this), 'albummusicas');
						});
						break;
					case 'imagem':
						$(this).find('img').css('cursor', 'default');
						if ($(this).attr('data-hiperlink')) {
							$(this).css('cursor', 'pointer');
							$(this).find('img').css('cursor', 'pointer');
							$(this).on('mouseup tap', function (e) {
								if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
								window.open($(this).attr('data-hiperlink'), ($(this).attr('data-hiperlink-target') ? $(this).attr('data-hiperlink-target') : '_self'));
							});
						}

						if ($(this).attr('data-marcador') == '@e-services#imagem') {
							var src = $(this).find('img').attr('src');

							$(this).css('background-image', 'url(' + src + ')');
							$(this).css('background-size', 'cover');

							$(this).find('img').remove();
						}

						mobile_sitemap({ obj: this });
						break;
					case 'texto':
						$(this).find('.b2make-texto-table').css('cursor', 'default');
						$(this).find('.b2make-texto-table').find('.b2make-texto-cel').css('cursor', 'default');
						if ($(this).attr('data-hiperlink')) {
							$(this).css('cursor', 'pointer');
							$(this).find('.b2make-texto-table').css('cursor', 'pointer');
							$(this).find('.b2make-texto-table').find('.b2make-texto-cel').css('cursor', 'pointer');
							$(this).on('mouseup tap', function (e) {
								if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
								window.open($(this).attr('data-hiperlink'), ($(this).attr('data-hiperlink-target') ? $(this).attr('data-hiperlink-target') : '_self'));
							});
						}

						mobile_sitemap({ obj: this });
						break;
					case 'instagram':
						var obj = $(this).get(0);

						instagram_verificar_recentes({
							obj: obj
						});
						break;
					case 'addthis':
						addthis_exec = true;
						break;
					case 'services':
						servicos_widget_update({ obj: this });
						break;
					case 'contents':
						b2make.widget_start['contents'] = true;
						break;
					case 'form_contato':
						$(this).attr('data-type', 'formularios');
						break;
					case 'google-maps':
						if (!b2make.google_maps_load) {
							b2make.google_maps_load = true;
							google_maps_load_api();
						}
						break;
					case 'redessociais':
						var rede = $(this).find('.b2make-widget-out').find('.b2make-redessociais-widget-holder').find('.b2make-redessociais-rede');
						var height = parseInt(rede.height());
						var width = Math.floor((height * 1200) / 100);

						rede.find('div').find('svg').css({ 'width': '' });
						rede.find('div').find('svg').attr('viewBox', '0 0 ' + width + ' ' + height);
						rede.find('div').find('svg').attr('width', width + 'px');
						rede.find('div').find('svg').attr('height', height + 'px');
						break;
					default:
						$(this).find('.b2make-widget-out').find('.b2make-widget-loading').show();

				}
			}
		});

		if (addthis_exec) {
			var script = document.location.protocol + '//s7.addthis.com/js/300/addthis_widget.js?async=1&domready=1&pubid=ra-4dc8b14029ceaa85';

			$.getScript(script, function () {
				window.addthis.update('config', 'data_track_clickback', true);
				window.addthis.update('share', 'url', location.href);
				window.addthis.update('share', 'title', document.title);
				window.addthis.toolbox(".addthis_toolbox");
			});
		}

		$('.b2make-widget-menu').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			var area = $(this).parent().parent().parent();
			var this_obj = this;
			var menu_holder_append = false;
			var position = 'absolute';

			if (area.attr('data-type') == 'conteiner-area') {
				area = area.parent();
			}

			if (area.attr('data-position') == 'fixed') {
				position = 'fixed';
			}

			if ($('#b2make-widget-menu-holder').length == 0) {
				menu_holder_append = true;
				b2make.menu_holder = $('<div id="b2make-widget-menu-holder"></div>');
			} else {
				b2make.menu_holder = $('#b2make-widget-menu-holder');
				b2make.menu_holder.html('');
			}

			if (b2make.menu_holder.attr('data-open') == '1') {
				b2make.menu_holder.attr('data-open', '0');
				b2make.menu_holder.hide();
				return;
			}

			var areas_ocultas = $(this).parent().parent().attr('data-areas');
			var areas_ocultas_arr = new Array();

			if (areas_ocultas)
				areas_ocultas_arr = areas_ocultas.split(',');


			if (menu_holder_append) b2make.menu_holder.appendTo('body');

			$('.b2make-widget').each(function () {
				if ($(this).attr('data-type') == 'conteiner') {
					var found = false;

					for (var j = 0; j < areas_ocultas_arr.length; j++) {
						var area_oculta = areas_ocultas_arr[j];
						if (area_oculta == $(this).attr('id')) {
							found = true;
							break;
						}
					}

					if (!found) {
						var caixa_color = '';
						var font_color = '';
						var hover_color = '';
						var font_family = '';
						var font_size = '';
						var font_align = '';
						var font_italico = '';
						var font_negrito = '';
						var espacamento = '';
						var largura = '';

						if ($(this_obj).attr('data-caixa-color-ahex')) {
							caixa_color = jpicker_ahex_2_rgba($(this_obj).attr('data-caixa-color-ahex'));
						}
						if ($(this_obj).attr('data-font-color-ahex')) {
							font_color = jpicker_ahex_2_rgba($(this_obj).attr('data-font-color-ahex'));
						}
						if ($(this_obj).attr('data-hover-color-ahex')) {
							hover_color = jpicker_ahex_2_rgba($(this_obj).attr('data-hover-color-ahex'));
						}
						if ($(this_obj).attr('data-font-family')) {
							font_family = $(this_obj).attr('data-font-family');
						}
						if ($(this_obj).attr('data-font-size')) {
							font_size = $(this_obj).attr('data-font-size');
						}
						if ($(this_obj).attr('data-font-align')) {
							font_align = $(this_obj).attr('data-font-align');
						}
						if ($(this_obj).attr('data-font-italico')) {
							if ($(this_obj).attr('data-font-italico') == 'sim')
								font_italico = $(this_obj).attr('data-font-italico');
						}
						if ($(this_obj).attr('data-font-negrito')) {
							if ($(this_obj).attr('data-font-negrito') == 'sim')
								font_negrito = $(this_obj).attr('data-font-negrito');
						}
						if ($(this_obj).attr('data-espacamento')) {
							espacamento = $(this_obj).attr('data-espacamento');
						}
						if ($(this_obj).attr('data-largura')) {
							largura = $(this_obj).attr('data-largura');
						}

						var link = $('<div data-id="' + $(this).attr('id') + '">' + ($(this).attr('data-name') ? $(this).attr('data-name') : $(this).attr('id')) + '</div>');
						link.appendTo('#b2make-widget-menu-holder');

						if (caixa_color) {
							link.css('background-color', caixa_color);
						}

						if (font_color || font_family || font_size) {
							link.html('<span>' + link.html() + '</span>');
						}

						if (hover_color) {
							link.hover(
								function () {
									$(this).css('background-color', hover_color);
								},
								function () {
									$(this).css('background-color', caixa_color);
								}
							);
						}

						if (font_color) {
							link.find('span').css('color', font_color);
						}

						if (font_family) {
							link.find('span').css('fontFamily', font_family);
						}

						if (font_size) {
							link.find('span').css('fontSize', font_size + 'px');
							link.css('line-height', font_size + 'px');
						}

						if (font_align) {
							link.css('textAlign', font_align);
						}

						if (font_italico) {
							link.css('fontStyle', 'italic');
						}

						if (font_negrito) {
							link.css('fontWeight', 'bold');
						}

						if (espacamento) {
							link.css('padding', espacamento + 'px');
							link.css('margin-bottom', espacamento + 'px');
						}

						if (largura) {
							link.css('width', largura + 'px');
						}

					}
				}
			});

			b2make.menu_holder_position = position;
			b2make.menu_holder_obj = $(this);
			b2make.menu_holder.attr('data-menu-atual', $(this).attr('id'));
			b2make.menu_holder.css('position', position);

			if (position == 'fixed') {
				b2make.menu_holder.css('top', ($(this).parent().parent().position().top + $(this).outerHeight()) + 'px');
				b2make.menu_holder.css('left', $(this).offset().left + 'px');
			} else {
				b2make.menu_holder.css('top', ($(this).offset().top + $(this).outerHeight()) + 'px');
				b2make.menu_holder.css('left', $(this).offset().left + 'px');
			}
			b2make.menu_holder.attr('data-open', '1');
			b2make.menu_holder.show();

			$('#b2make-widget-menu-holder div').on('mouseup tap', function (e) {
				if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
				parallax_animar_scroll({ obj: $(this) });
			});
		});

		if ($("a[rel^='prettyPhoto']").length) {
			var prettyphoto_var = { animation_speed: 'fast', slideshow: 3000, hideflash: true, deeplinking: false, twitter: true, facebook: true };
			setTimeout(function () { $("a[rel^='prettyPhoto']").prettyPhoto(prettyphoto_var); }, 100);
		}

		$('div.b2make-widget[data-type="agenda"]').each(function () {
			var excluir_eventos = $(this).attr('data-excluir-eventos');

			if (!excluir_eventos) excluir_eventos = 's';

			if (excluir_eventos == 's')
				$(this).find('div.b2make-widget-out').find('div.b2make-eventos-widget-holder').find('div.b2make-widget-eventos').each(function () {
					var data = $(this).find('div.b2make-widget-eventos-data').attr('data-date');
					var hora = $(this).find('div.b2make-widget-eventos-hora').html().split(':');

					var time1 = parseInt($.datepicker.formatDate('@', $.datepicker.parseDate("dd/mm/yy", data)));
					var time2 = parseInt(hora[0]) * 1000 * 60 * 60 + parseInt(hora[1]) * 1000 * 60;
					var time_now = parseInt($.now());

					if (time1 + time2 < time_now) {
						$(this).remove();
					}
				});
		});

		$('.b2make-gwi-prev').bind('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			var holder = $(this).parent().find('div.b2make-galeria-widget-holder');
			var img = holder.find('div:last-child');

			img.prependTo(holder);
		});

		$('.b2make-gwi-next').bind('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			var holder = $(this).parent().find('div.b2make-galeria-widget-holder');
			var img = holder.find('div:first-child');

			img.appendTo(holder);
		});

		$('.b2make-wsoae-prev').bind('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			var holder = $(this).parent().find('div.b2make-eventos-widget-holder');
			var img = holder.find('div.b2make-widget-eventos:last-child');

			img.prependTo(holder);
		});

		$('.b2make-wsoae-next').bind('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			e.stopPropagation();

			var holder = $(this).parent().find('div.b2make-eventos-widget-holder');
			var img = holder.find('div.b2make-widget-eventos:first-child');

			img.appendTo(holder);
		});

		$('.b2make-albumfotos-widget-image').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if ($(this).attr('data-imagens-urls')) {
				var imgs_arr = $(this).attr('data-imagens-urls').split(',');
				var imgs;

				if (imgs_arr.length > 0) {
					imgs = new Array();
					for (var i = 0; i < imgs_arr.length; i++) {
						imgs.push(imgs_arr[i]);
					}
					if (!b2make.start_pretty_photo) {
						$.fn.prettyPhoto({ animation_speed: 'fast', slideshow: 3000, hideflash: true, deeplinking: false });
						b2make.start_pretty_photo = true;
					}
					$.prettyPhoto.open(imgs);
				}
			}
		});

		$('.b2make-slideshow-widget-image').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			if (!b2make.widget_child_move) {
				var imagens = $(this).parent().parent().parent().attr('data-imagens-urls');
				var imagem = $(this).attr('data-image-url');

				if (imagens) {
					var imgs_arr = imagens.split(',');
					var imgs;
					var indice = 0;

					if (imgs_arr.length > 0) {
						imgs = new Array();
						for (var i = 0; i < imgs_arr.length; i++) {
							if (imagem == imgs_arr[i]) {
								indice = i;
								break;
							}
						}
						for (var i = indice; i < imgs_arr.length; i++) {
							imgs.push(imgs_arr[i]);
						}
						for (var i = 0; i < indice; i++) {
							imgs.push(imgs_arr[i]);
						}
						if (!b2make.start_pretty_photo) {
							$.fn.prettyPhoto({ animation_speed: 'fast', slideshow: 3000, hideflash: true, deeplinking: false });
							b2make.start_pretty_photo = true;
						}
						$.prettyPhoto.open(imgs);
					}
				}
			}
		});

		$('.b2make-galeria-imagens-widget-image,.b2make-galeria-imagens-widget-image-2').live('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var img = $(this).attr('data-image-url');
			var pai = $(this).parent().parent().parent();

			if (pai.attr('data-imagens-urls')) {
				var imgs_arr = pai.attr('data-imagens-urls').split(',');
				var imgs;

				if (imgs_arr.length > 0) {
					imgs = new Array();
					imgs.push(img);
					for (var i = 0; i < imgs_arr.length; i++) {
						if (imgs_arr[i] != img) {
							imgs.push(imgs_arr[i]);
						}
					}
					if (!b2make.start_pretty_photo) {
						$.fn.prettyPhoto({ animation_speed: 'fast', slideshow: 3000, hideflash: true, deeplinking: false });
						b2make.start_pretty_photo = true;
					}
					$.prettyPhoto.open(imgs);
				}
			}
		});
	}

	start_classes();

	// ==================================== Menu Páginas =============================

	function menu_pagina_submenu_posicao(p) {
		if (!p) p = {};

		var obj = p.obj;
		var id = obj.attr('data-id');

		switch (obj.attr('data-tipo')) {
			case 'esquerda':
				obj.css('width', ($('#' + id).outerWidth(true) + $('#' + id).offset().left) + 'px');
				obj.find('ul').find('li').find('a').css('width', ($('#' + id).outerWidth(true) + $('#' + id).offset().left - 30) + 'px');
				obj.css('left', '0px');
				break;
			case 'direita':
				obj.css('width', ($(window).outerWidth(true) - $('#' + id).offset().left) + 'px');
				obj.find('ul').find('li').find('a').css('width', ($(window).outerWidth(true) - $('#' + id).offset().left - 30) + 'px');
				obj.css('left', $('#' + id).offset().left + 'px');
				break;
		}
	}

	function menu_pagina_html_urls(p) {
		if (!p) p = {};

		var urls = p.urls;
		var sites = b2make.menu_paginas.sitemaps.sites;
		var found_pai;
		var found_pai_2;
		var obj = $('#' + p.id);
		var escolha_pontual = false;
		var num_paginas = 0;

		if (obj.attr('data-paginas-opcao') && obj.attr('data-paginas-opcao') == 'escolha-pontual') {
			escolha_pontual = true;
		}

		if (escolha_pontual) {
			if (obj.attr('data-ids-paginas')) {
				var ids_paginas = obj.attr('data-ids-paginas');
				var paginas_ids = new Array();
				var ids_paginas_arr = (ids_paginas ? ids_paginas.split(',') : false);
			}
		}


		if (escolha_pontual) {
			var entra_menu = false;
			var faz_parte_menu = new Array();

			if (ids_paginas_arr)
				for (var k = 0; k < ids_paginas_arr.length; k++) {
					for (var i = 0; i < sites.length; i++) {
						if (ids_paginas_arr[k] == sites[i].id_site) {
							found_pai = false;
							found_pai_2 = false;
							for (var j = 0; j < sites.length; j++) {
								if (sites[i].id_site_pai == sites[j].id_site && !sites[j].raiz) {
									found_pai = true;
								}
							}

							if (faz_parte_menu)
								for (var j = 0; j < faz_parte_menu.length; j++) {
									if (sites[i].id_site_pai == faz_parte_menu[j]) {
										found_pai_2 = true;
									}
								}

							var li = $('<li><a href="' + sites[i].url + '">' + (found_pai && found_pai_2 ? '&nbsp;&nbsp;&nbsp;' : '') + (sites[i].raiz ? 'In&iacute;cio' : sites[i].nome) + '</a></li>');

							faz_parte_menu.push(sites[i].id_site);

							if (sites[i].raiz) {
								urls.prepend(li);
							} else {
								urls.append(li);
							}

							num_paginas++;
						}
					}
				}
		} else {
			for (var i = 0; i < sites.length; i++) {
				found_pai = false;
				for (var j = 0; j < sites.length; j++) {
					if (sites[i].id_site_pai == sites[j].id_site && !sites[j].raiz) {
						found_pai = true;
					}
				}

				var li = $('<li><a href="' + sites[i].url + '">' + (found_pai ? '&nbsp;&nbsp;&nbsp;' : '') + (sites[i].raiz ? 'In&iacute;cio' : sites[i].nome) + '</a></li>');

				if (sites[i].raiz) {
					urls.prepend(li);
				} else {
					urls.append(li);
				}

				num_paginas++;
			}
		}

		return { urls: urls, num_paginas: num_paginas };
	}

	function menu_paginas_html(p) {
		if (!p) p = {};

		var tipo = $('#' + p.id).attr('data-tipo-menu');
		var left = $('#' + p.id).offset().left;
		var top = parseInt($('#' + p.id).offset().top) + parseInt($('#' + p.id).outerHeight(true));

		var cont_principal = $('<div class="b2make-menu-paginas-submenu" data-id="' + p.id + '"' + (tipo ? ' data-tipo="' + tipo + '"' : '') + '></div>');
		cont_principal.hide();
		cont_principal.css('left', left + 'px');
		cont_principal.css('top', top + 'px');
		cont_principal.appendTo('body');

		var urls = $('<ul></ul>');

		var ret = menu_pagina_html_urls({ urls: urls, id: p.id });

		urls = ret.urls;

		cont_principal.append(urls);

		cont_principal.css('height', ((b2make.menu_paginas.height + 1) * ret.num_paginas) + 'px');

		b2make.menu_paginas.submenu[p.id] = cont_principal;

		if (tipo == 'direita' || tipo == 'esquerda') {
			menu_pagina_submenu_posicao({ obj: cont_principal });
		}
	}

	function menu_paginas_close(p) {
		if (!p) p = {};

		if (b2make.menu_paginas.submenu[p.id]) {
			b2make.menu_paginas.submenu[p.id].stop().fadeToggle(b2make.menu_paginas.transicao, function () {
				var left = $('#' + p.id).offset().left;
				var tipo = $(this).attr('data-tipo');

				if (tipo != 'direita' && tipo != 'esquerda') {
					var top = parseInt($('#' + p.id).offset().top) + parseInt($('#' + p.id).outerHeight(true));

					$(this).css('left', left + 'px');
					$(this).css('top', top + 'px');
				}
			});
		}

		b2make.menu_paginas.open = false;
	}

	function menu_paginas_open(p) {
		if (!p) p = {};

		if (!b2make.menu_paginas.submenu) {
			b2make.menu_paginas.submenu = new Array();
		}

		if (!b2make.menu_paginas.submenu[p.id]) {
			menu_paginas_html({ id: p.id });
		}

		b2make.menu_paginas.submenu[p.id].stop().fadeToggle(b2make.menu_paginas.transicao, function () {
			$(this).css('opacity', '1');
			var tipo = $(this).attr('data-tipo');

			if (tipo != 'direita' && tipo != 'esquerda') {
				var left = $('#' + p.id).offset().left;
				var top = parseInt($('#' + p.id).offset().top) + parseInt($('#' + p.id).outerHeight(true));

				$(this).css('left', left + 'px');
				$(this).css('top', top + 'px');
			} else {
				menu_pagina_submenu_posicao({ obj: $(this) });
			}
		});

		b2make.menu_paginas.open = true;
	}

	function menu_paginas() {
		b2make.menu_paginas = {};

		b2make.menu_paginas.transicao = 300;
		b2make.menu_paginas.height = 60;

		if ($('.b2make-menu-paginas').length > 0) {
			var id_func = 'menu-paginas-sitemaps';

			$.ajax({
				cache: false,
				type: 'GET',
				dataType: 'json',
				crossDomain: true,
				url: b2make.hostname + '/files/sitemaps/sitemaps.json',
				beforeSend: function () {
				},
				success: function (txt) {
					var sitemaps = txt;

					b2make.menu_paginas.sitemaps_loaded = true;
					b2make.menu_paginas.sitemaps = sitemaps;

					if (b2make.menu_paginas.menu_clicked) {
						menu_paginas_open({ id: b2make.menu_paginas.menu_clicked });
					}
				},
				error: function (txt) {
					console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
				}
			});

			$(document.body).on('mouseup tap', '.b2make-menu-paginas', function (e) {
				if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

				var id = $(this).parent().parent().attr('id');

				if (b2make.menu_paginas.sitemaps_loaded) {
					if (!b2make.menu_paginas.open) {
						menu_paginas_open({ id: id });
					} else {
						menu_paginas_close({ id: id });
					}
				} else {
					b2make.menu_paginas.menu_clicked = id;
				}
			});

			$(window).resize(function () {
				$('.b2make-menu-paginas-submenu').each(function () {
					var id = $(this).attr('data-id');
					var tipo = $(this).attr('data-tipo');

					if (tipo != 'direita' && tipo != 'esquerda') {
						var left = $('#' + id).offset().left;
						var top = parseInt($('#' + id).offset().top) + parseInt($('#' + id).outerHeight(true));

						$(this).css('left', left + 'px');
						$(this).css('top', top + 'px');
					} else {
						menu_pagina_submenu_posicao({ obj: $(this) });
					}
				});
			});
		}
	}

	menu_paginas();

	// ==================================== Breadcrumbs =============================

	function breadcrumbs_montar_menu(p = {}) {
		var pagina_arvore = p.pagina_arvore;
		var inicio = 'In&iacute;cio';

		var url = '<a href="' + (b2make.mobile.active && pagina_arvore.url_mobile ? pagina_arvore.url_mobile : pagina_arvore.url) + '" class="b2make-breadcrumbs-link' + (p.start ? ' b2make-breadcrumbs-link-2' : '') + '">' + (pagina_arvore.pai ? pagina_arvore.nome : inicio) + '</a>';

		if (pagina_arvore.pai) {
			return breadcrumbs_montar_menu({ pagina_arvore: pagina_arvore.pai }) + '<span class="b2make-breadcrumbs-link-sep"> / </span>' + url
		} else {
			return url;
		}
	}

	function breadcrumbs_montar_arvore(p = {}) {
		if (p.sitemaps) {
			var sites_unpublished = p.sitemaps.sites_unpublished;
			var sites = p.sitemaps.sites;
			var atual_id = p.atual_id;
			var pagina_arvore = false;

			if (sites)
				for (var i = 0; i < sites.length; i++) {
					if (atual_id == sites[i].id_site) {
						if (sites[i].id_site_pai) {
							return {
								nome: sites[i].nome,
								url: sites[i].url,
								url_mobile: sites[i].url_mobile,
								pai: breadcrumbs_montar_arvore({ sitemaps: p.sitemaps, atual_id: sites[i].id_site_pai })
							};
						} else {
							return {
								nome: sites[i].nome,
								url: sites[i].url,
								url_mobile: sites[i].url_mobile,
								pai: false
							};;
						}
					}
				}

			if (sites_unpublished)
				for (var i = 0; i < sites_unpublished.length; i++) {
					if (atual_id == sites_unpublished[i].id_site) {
						if (sites_unpublished[i].id_site_pai) {
							return {
								nome: sites_unpublished[i].nome,
								url: sites_unpublished[i].url,
								url_mobile: sites_unpublished[i].url_mobile,
								pai: breadcrumbs_montar_arvore({ sitemaps: p.sitemaps, atual_id: sites_unpublished[i].id_site_pai })
							};
						} else {
							return {
								nome: sites_unpublished[i].nome,
								url: sites_unpublished[i].url,
								url_mobile: sites_unpublished[i].url_mobile,
								pai: false
							};
						}
					}
				}

			return false;
		}
	}

	function breadcrumbs_html_update(p = {}) {
		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
		var cont_principal = $(obj).find('.b2make-widget-out').find('.b2make-breadcrumbs');
		var atual_id = $(obj).attr('data-id');
		var plugin_id = 'breadcrumbs';
		var id_func = 'pagina-arvore';

		cont_principal.html('');

		$.ajax({
			cache: false,
			type: 'GET',
			dataType: 'json',
			crossDomain: true,
			url: b2make.hostname + '/files/sitemaps/sitemaps.json',
			beforeSend: function () {
			},
			success: function (txt) {
				var sitemaps = txt;

				var pagina_arvore = breadcrumbs_montar_arvore({ sitemaps: sitemaps, atual_id: atual_id });
				var breadcrumbs = breadcrumbs_montar_menu({ pagina_arvore: pagina_arvore, start: true });
				cont_principal.html(breadcrumbs);
				$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
			}
		});
	}

	function breadcrumbs() {
		$('.b2make-widget').each(function () {
			if ($(this).attr('data-type') != 'conteiner-area') {
				switch ($(this).attr('data-type')) {
					case 'breadcrumbs':
						breadcrumbs_html_update({ obj: this });
						break;
				}
			}
		});
	}

	breadcrumbs();

	// ==================================== Google Maps =============================

	function google_maps_create(p) {
		if (!p) p = {};
		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
		var id_pai = $(obj).attr('id');
		var id_map = $(obj).attr('id') + '-map';
		var obj_pai = obj;

		if ($(obj).attr('data-area')) {
			obj = $(obj).find('.b2make-widget[data-type="conteiner-area"]');
		} else {
			obj = $(obj);
		}

		obj = obj.find('.b2make-widget-out').find('.b2make-google-maps');

		obj.html('');

		var div_map = $('<div id="' + id_map + '" class="b2make-google-maps-map"></div>');
		div_map.appendTo(obj);

		div_map.height($(obj_pai).height());

		var gmaps = false;

		gmaps = {};
		gmaps.map_id = id_map;

		gmaps.geocoder = new google.maps.Geocoder();
		gmaps.bounds = new google.maps.LatLngBounds();
		gmaps.markersArray = [];

		var lat = -23.5428164;
		var lng = -46.6416659;

		if ($(obj_pai).attr('data-latlong')) {
			var latlong_txt = $(obj_pai).attr('data-latlong');
			var latlong_arr = latlong_txt.split(':');
			lat = parseFloat(latlong_arr[0]);
			lng = parseFloat(latlong_arr[1]);
		}

		var zoom = b2make.googleMaps.zoom;

		if ($(obj_pai).attr('data-zoom')) {
			zoom = parseInt($(obj_pai).attr('data-zoom'));
		}

		var styleJson = [];

		if ($(obj_pai).attr('data-style')) {
			styleJson = JSON.parse($(obj_pai).attr('data-style-json'));
			styleJson = styleJson.styles;
		}

		var styledMapType = new google.maps.StyledMapType(styleJson, { name: b2make.msgs.googleMapsStyledButton });

		gmaps.opts = {
			center: new google.maps.LatLng(lat, lng),
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
			position: { lat: lat, lng: lng },
			map: gmaps.map,
			title: b2make.msgs.googleMapsTitleMarker
		}));

		b2make.googleMaps.gmaps[id_pai] = gmaps;
	}

	function google_maps_load_api() {
		$.getScript('https://maps.googleapis.com/maps/api/js?key=' + b2make.googleapis.key, function () {
			google_maps_start();
		});
	}

	function google_maps_start() {
		b2make.googleMaps = {};

		b2make.googleMaps.gmaps = new Array();
		b2make.googleMaps.zoom = 12;
		b2make.googleMaps.maxZoom = 20;

		if (!b2make.msgs.googleMapsTitleMarker) b2make.msgs.googleMapsTitleMarker = 'Local do Estabelecimento';
		if (!b2make.msgs.googleMapsStyledButton) b2make.msgs.googleMapsStyledButton = 'Mapa';

		$('.b2make-widget').each(function () {
			if ($(this).attr('data-type') != 'conteiner-area') {
				switch ($(this).attr('data-type')) {
					case 'google-maps':
						google_maps_create({ obj: this });
						break;
				}
			}
		});
	}

	// ==================================== E-Service =============================

	function escapeHtml(unsafe) {
		return unsafe
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	function formatMoney(n, c, d, t) {
		c = isNaN(c = Math.abs(c)) ? 2 : c,
			d = d == undefined ? "." : d,
			t = t == undefined ? "," : t,
			s = n < 0 ? "-" : "",
			i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
			j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}

	function services_total_update() {
		var total = 0;

		$('#b2make-services-carrinho-lista').find('.b2make-services-carrinho-lista-cont').each(function () {
			var preco = parseFloat($(this).attr('data-preco'));
			var quant = parseInt($(this).find('.b2make-sclt-quant').find('input').val());

			total = total + (preco * (quant ? quant : 0));
		});

		$('#b2make-services-carrinho-total').find('span').html(services_format_money(total, 2, ',', '.'));
	}

	function services_carregamento_abrir() {
		$('#b2make-services-carrinho-carregamento').show();
	}

	function services_carregamento_fechar() {
		$('#b2make-services-carrinho-carregamento').hide();
	}

	function services_quantidade_delay_to_change(value) {
		if (!b2make.services_quantidade_delay) {
			b2make.services_quantidade_delay = new Array();
			b2make.services_quantidade_delay_count = 0;
		}

		b2make.services_quantidade_delay_count++;

		var valor = b2make.services_quantidade_delay_count;

		b2make.services_quantidade_delay.push(valor);
		b2make.services_quantidade_value = value;

		setTimeout(function () {
			if (b2make.services_quantidade_delay[b2make.services_quantidade_delay.length - 1] == valor) {
				services_quantidade_change(b2make.services_quantidade_value);
			}
		}, b2make.services_quantidade_delay_timeout);
	}

	function services_quantidade_change(value) {
		if (!b2make.services_list) {
			var id_func = 'quantidade-change';
			$.ajax({
				cache: false,
				type: 'GET',
				dataType: 'json',
				url: b2make.hostname + '/servicos/services-list.json',
				beforeSend: function () {
					services_carregamento_abrir();
				},
				success: function (txt) {
					b2make.services_list = txt;
					services_carrinho_mudar_quantidade(value);
					services_carregamento_fechar();
				},
				error: function (txt) {
					console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
					services_carregamento_fechar();
				}
			});
		} else {
			services_carrinho_mudar_quantidade(value);
		}

		b2make.services_quantidade_delay = false;
	}

	function services_carrinho_mudar_quantidade(value) {
		var obj = b2make.services.carrinho_quantidade;
		var cont = $(obj).parent().parent();

		cont.find('.b2make-sclt-quant').find('input').attr('value', value);
		if (value > 0) {
			var id = cont.attr('data-id');

			for (var i = 0; i < b2make.services_list.length; i++) {
				if (b2make.services_list[i].id == id) {
					var quantidade = parseInt(b2make.services_list[i].quantidade);
					var preco = parseFloat(b2make.services_list[i].preco);

					if (quantidade > 0) {
						if (quantidade >= value) {
							cont.find('.b2make-sclt-preco-unitario').html('R$ ' + formatMoney(preco, 2, ',', '.'));
							cont.find('.b2make-sclt-sub-total').html('R$ ' + formatMoney((value * preco), 2, ',', '.'));
						} else {
							$.dialogbox_open({
								msg: b2make.msgs.servicosSemQuantidadeSuficiente
							});
						}
					} else {
						$.dialogbox_open({
							msg: b2make.msgs.servicosSemQuantidade
						});
					}

					break;
				}
			}
		} else {
			cont.find('.b2make-sclt-preco-unitario').html('R$ 0,00');
			cont.find('.b2make-sclt-sub-total').html('R$ 0,00');
		}

		var carrinho_lista = $('#b2make-services-carrinho-lista');
		localStorage.setItem('b2make.carrinho-lista', carrinho_lista.html());

		services_total_update();
	}

	function formatMoney2(n) {
		n = parseFloat(n);
		var c = isNaN(c = Math.abs(c)) ? 2 : c,
			d = d == undefined ? "," : d,
			t = t == undefined ? "." : t,
			s = n < 0 ? "-" : "",
			i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
			j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}

	function servicos_widget_html(p) {
		if (!p) p = {};

		if (p.link_area) {
			var service_cont = $('<a href="' + p.service.url + '" class="b2make-service-cont" data-id="' + p.service.id + '" data-quantidade="' + p.service.quantidade + '" data-validade="' + p.service.validade + '" data-validade_data="' + p.service.validade_data + '" data-validade_hora="' + p.service.validade_hora + '" data-validade_tipo="' + p.service.validade_tipo + '" data-preco="' + p.service.preco + '" data-href="' + p.service.url + '"></a>');
		} else {
			var service_cont = $('<div class="b2make-service-cont" data-id="' + p.service.id + '" data-quantidade="' + p.service.quantidade + '" data-validade="' + p.service.validade + '" data-validade_data="' + p.service.validade_data + '" data-validade_hora="' + p.service.validade_hora + '" data-validade_tipo="' + p.service.validade_tipo + '" data-preco="' + p.service.preco + '" data-href="' + p.service.url + '"></div>');
		}

		var imagem = $('<div class="b2make-service-imagem" style="background-image:url(' + (p.service.url_imagem ? p.service.url_imagem : '//b2make.com/site/images/b2make-album-sem-imagem.png') + ');"></div>');
		var name = $('<div class="b2make-service-name">' + p.service.nome + '</div>');
		var descricao = $('<div class="b2make-service-descricao">' + p.service.descricao + '</div>');
		var comprar = $('<a class="b2make-service-comprar" href="' + p.service.url + '">' + (parseInt(p.service.quantidade) > 0 ? b2make.msgs.servicosComprar : b2make.msgs.servicosIndisponivel) + '</a>');
		var preco = $('<div class="b2make-service-preco">R$ ' + formatMoney2(p.service.preco) + '</div>');

		imagem.appendTo(service_cont);
		name.appendTo(service_cont);
		descricao.appendTo(service_cont);
		comprar.appendTo(service_cont);
		preco.appendTo(service_cont);

		if (p.largura_cont) {
			service_cont.width(p.largura_cont);
		}

		if (p.altura_cont) {
			service_cont.height(p.altura_cont);
		}

		if (p.altura_img) {
			imagem.height(p.altura_img);
		}

		return service_cont;
	}

	function servicos_widget_update(p) {
		if (!p) p = {};

		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
		var id_func = 'servicos-html-list';

		$.ajax({
			cache: false,
			type: 'GET',
			dataType: 'json',
			url: b2make.hostname + '/servicos/services-list.json',
			beforeSend: function () {
			},
			success: function (txt) {
				$(obj).find('.b2make-widget-out').find('.b2make-services-list').html('');
				var services_ids = $(obj).attr('data-services-ids');
				var found_service;
				var link_area = false;

				if (services_ids) services_ids = services_ids.split(',');

				if ($(obj).attr('data-acao-click') == 'area') {
					link_area = true;
				}

				if ($(obj).attr('data-tamanho-cont')) {
					var largura_cont = $(obj).attr('data-tamanho-cont');
				} else {
					var largura_cont = 160;
				}

				if ($(obj).attr('data-tamanho-cont-2')) {
					var altura_cont = $(obj).attr('data-tamanho-cont-2');
				} else {
					var altura_cont = 280;
				}

				if ($(obj).attr('data-altura-imagem')) {
					var altura_img = $(obj).attr('data-altura-imagem');
				} else {
					var altura_img = 160;
				}

				for (var i = 0; i < txt.length; i++) {
					found_service = false;
					if (services_ids)
						for (var j = 0; j < services_ids.length; j++) {
							if (services_ids[j] == txt[i].id) {
								found_service = true;
							}
						}

					if (!found_service) continue;

					$(obj).find('.b2make-widget-out').find('.b2make-services-list').append(servicos_widget_html({ service: txt[i], link_area: link_area, largura_cont: largura_cont, altura_cont: altura_cont, altura_img: altura_img }));
				}

				mobile_sitemap({ obj: obj });

				if ($(obj).attr('data-widget-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-services-list').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-widget-color-ahex')));
				if ($(obj).attr('data-caixa-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex')));
				if ($(obj).attr('data-botao-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-botao-color-ahex')));
				if ($(obj).attr('data-titulo-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-name').css('color', jpicker_ahex_2_rgba($(obj).attr('data-titulo-text-color-ahex')));
				if ($(obj).attr('data-descricao-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-descricao').css('color', jpicker_ahex_2_rgba($(obj).attr('data-descricao-text-color-ahex')));
				if ($(obj).attr('data-preco-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-preco').css('color', jpicker_ahex_2_rgba($(obj).attr('data-preco-text-color-ahex')));
				if ($(obj).attr('data-botao-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar').css('color', jpicker_ahex_2_rgba($(obj).attr('data-botao-text-color-ahex')));

				var ids = new Array('titulo', 'descricao', 'preco', 'botao');
				var mudar_height = false;
				var target;

				for (var i = 0; i < ids.length; i++) {
					var id = ids[i];

					switch (id) {
						case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-name'); mudar_height = true; break;
						case 'descricao': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-descricao'); mudar_height = true; break;
						case 'preco': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-preco'); break;
						case 'botao': target = $(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').find('.b2make-service-comprar'); break;
					}

					if ($(obj).attr('data-' + id + '-font-family')) target.css('fontFamily', $(obj).attr('data-' + id + '-font-family'));
					if ($(obj).attr('data-' + id + '-font-size')) {
						target.css('fontSize', $(obj).attr('data-' + id + '-font-size') + 'px');

						var height = b2make.services.conteiner_height_lines * ($(obj).attr('data-titulo-font-size') ? parseInt($(obj).attr('data-titulo-font-size')) : b2make.services.conteiner_height_name) + b2make.services.conteiner_height_lines * ($(obj).attr('data-descricao-font-size') ? parseInt($(obj).attr('data-descricao-font-size')) : b2make.services.conteiner_height_descricao);
						height = height + b2make.services.conteiner_height_default;

						$(obj).find('.b2make-widget-out').find('.b2make-services-list').find('.b2make-service-cont').css('height', height + 'px');

						var line_height = parseInt($(obj).attr('data-' + id + '-font-size')) + b2make.services.conteiner_height_margin;
						target.css('line-height', line_height + 'px');

						if (mudar_height) {
							target.css('max-height', (line_height * b2make.services.conteiner_height_lines) + 'px');
						}
					}
					if ($(obj).attr('data-' + id + '-font-align')) target.css('textAlign', $(obj).attr('data-' + id + '-font-align'));
					if ($(obj).attr('data-' + id + '-font-italico')) target.css('fontStyle', ($(obj).attr('data-' + id + '-font-italico') == 'sim' ? 'italic' : 'normal'));
					if ($(obj).attr('data-' + id + '-font-negrito')) target.css('fontWeight', ($(obj).attr('data-' + id + '-font-negrito') == 'sim' ? 'bold' : 'normal'));
				}

				$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
			}
		});
	}

	function services_format_money(n, c, d, t) {
		c = isNaN(c = Math.abs(c)) ? 2 : c,
			d = d == undefined ? "." : d,
			t = t == undefined ? "," : t,
			s = n < 0 ? "-" : "",
			i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
			j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	};

	function services_carrinho_add(p) {
		if (!p) p = {};

		var carrinho;
		var found = false;

		if (localStorage.getItem('b2make.carrinho')) {
			carrinho = JSON.parse(localStorage.getItem('b2make.carrinho'));
		} else {
			carrinho = new Array();
		}

		for (var i = 0; i < carrinho.length; i++) {
			if (carrinho[i].id == p.id) {
				found = true;
			}
		}

		if (!found) {
			carrinho.push({
				preco: p.preco,
				validade: p.validade,
				id: p.id
			});

			var carrinho_lista = $('#b2make-services-carrinho-lista');

			var lista_cont = $('<div class="b2make-services-carrinho-lista-cont" data-nome="' + escapeHtml(p.nome) + '" data-descricao="' + escapeHtml(p.descricao) + '" data-id="' + p.id + '" data-preco="' + p.preco + '" data-validade="' + p.validade + '"></div>');

			var nome = $('<div class="b2make-services-carrinho-lista b2make-sclt-nome b2make-sclt-nome-ajuste">' + escapeHtml(p.nome) + '</div>');
			var quant = $('<div class="b2make-services-carrinho-lista b2make-sclt-quant"><input type="text" value="1" class="b2make-sclt-quant-value" maxlength="2"></div>');
			var excluir = $('<div class="b2make-services-carrinho-lista b2make-sclt-excluir"><div class="b2make-sclt-excluir-btn"></div></div>');
			var preco_unitario = $('<div class="b2make-services-carrinho-lista b2make-sclt-preco-unitario">R$ ' + services_format_money(p.preco, 2, ',', '.') + '</div>');
			var sub_total = $('<div class="b2make-services-carrinho-lista b2make-sclt-sub-total">R$ ' + services_format_money(p.preco, 2, ',', '.') + '</div>');

			nome.appendTo(lista_cont);
			quant.appendTo(lista_cont);
			excluir.appendTo(lista_cont);
			preco_unitario.appendTo(lista_cont);
			sub_total.appendTo(lista_cont);

			lista_cont.appendTo(carrinho_lista);

			services_total_update();

			localStorage.setItem('b2make.carrinho-lista', carrinho_lista.html());
		}

		localStorage.setItem('b2make.carrinho', JSON.stringify(carrinho));
	}

	function services_carrinho_del(p) {
		if (!p) p = {};

		var carrinho;
		var found = false;

		if (localStorage.getItem('b2make.carrinho')) {
			carrinho = JSON.parse(localStorage.getItem('b2make.carrinho'));
		} else {
			carrinho = new Array();
		}

		var carrinho_novo = new Array();

		for (var i = 0; i < carrinho.length; i++) {
			if (carrinho[i].id == p.id) {
				found = true;
			} else {
				carrinho_novo.push(carrinho[i]);
			}
		}

		carrinho = carrinho_novo;

		if (found) {
			var carrinho_lista = $('#b2make-services-carrinho-lista');

			$('.b2make-services-carrinho-lista-cont[data-id="' + p.id + '"]').remove();
			localStorage.setItem('b2make.carrinho-lista', carrinho_lista.html());
		}

		localStorage.setItem('b2make.carrinho', JSON.stringify(carrinho));
		services_total_update();
	}

	function services_carrinho_fechar() {
		b2make.services_carrinho_cont.fadeOut(b2make.services.fade_time);
		b2make.services_carrinho_open = false;
	}

	function services_carrinho_abrir() {
		if (!b2make.services_carrinho_open) {
			b2make.services_carrinho_open = true;

			var carrinho;
			if (!b2make.services_carrinho_cont) {
				carrinho = $('<div id="b2make-services-carrinho"></div>');

				var carrinho_carregamento = $('<div id="b2make-services-carrinho-carregamento"></div>');
				var carrinho_tit = $('<div id="b2make-services-carrinho-tit">Carrinho</div>');
				var carrinho_fechar = $('<div id="b2make-services-carrinho-fechar"></div>');
				var carrinho_lista_tit = $('<div id="b2make-services-carrinho-lista-tit"></div>');
				var carrinho_lista = $('<div id="b2make-services-carrinho-lista"></div>');
				var carrinho_total = $('<div id="b2make-services-carrinho-total">Total: R$ <span>0,00</span></div>');
				var carrinho_continuar = $('<div id="b2make-services-carrinho-continuar">Continuar Comprando</div>');
				var carrinho_finalizar = $('<div id="b2make-services-carrinho-finalizar">Finalizar Compra</div>');

				var lista_tit_nome = $('<div class="b2make-services-carrinho-lista-tit b2make-sclt-nome">Servi&ccedil;os</div>');
				var lista_tit_quant = $('<div class="b2make-services-carrinho-lista-tit b2make-sclt-quant">Quant.</div>');
				var lista_tit_excluir = $('<div class="b2make-services-carrinho-lista-tit b2make-sclt-excluir">Excluir</div>');
				var lista_tit_preco_unitario = $('<div class="b2make-services-carrinho-lista-tit b2make-sclt-preco-unitario">Pre&ccedil;o unit.</div>');
				var lista_tit_sub_total = $('<div class="b2make-services-carrinho-lista-tit b2make-sclt-sub-total">Sub Total</div>');

				lista_tit_nome.appendTo(carrinho_lista_tit);
				lista_tit_quant.appendTo(carrinho_lista_tit);
				lista_tit_excluir.appendTo(carrinho_lista_tit);
				lista_tit_preco_unitario.appendTo(carrinho_lista_tit);
				lista_tit_sub_total.appendTo(carrinho_lista_tit);

				carrinho_carregamento.appendTo(carrinho);
				carrinho_tit.appendTo(carrinho);
				carrinho_fechar.appendTo(carrinho);
				carrinho_lista_tit.appendTo(carrinho);
				carrinho_lista.appendTo(carrinho);
				carrinho_total.appendTo(carrinho);
				carrinho_continuar.appendTo(carrinho);
				carrinho_finalizar.appendTo(carrinho);

				carrinho.appendTo('body');
				carrinho.hide();

				b2make.services_carrinho_cont = carrinho;

				if (localStorage.getItem('b2make.carrinho-lista')) {
					carrinho_lista.html(localStorage.getItem('b2make.carrinho-lista'));
					services_total_update();
				}

				$('#b2make-services-carrinho-fechar').on('mouseup tap', function (e) {
					if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

					services_carrinho_fechar();
				});

				$('#b2make-services-carrinho-finalizar').on('mouseup tap', function (e) {
					if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

					var id_func = 'carrinho-finalizar';
					$.ajax({
						cache: false,
						type: 'GET',
						dataType: 'json',
						url: b2make.hostname + '/servicos/config.json',
						beforeSend: function () {
						},
						success: function (txt) {
							var pub_id = txt.pub_id;
							var data = '';

							$('#b2make-services-carrinho-lista').find('.b2make-services-carrinho-lista-cont').each(function () {
								var id = parseInt($(this).attr('data-id'));
								var quant = parseInt($(this).find('.b2make-sclt-quant').find('input').val());

								data = data + (data ? '_' : '') + id + '-' + quant;
							});

							var carrinho = new Array();

							localStorage.setItem('b2make.carrinho-lista', '');
							localStorage.setItem('b2make.carrinho', JSON.stringify(carrinho));

							var debug = getUrlParameter('debug');

							window.open('https://b2make.com/' + (debug ? 'teste/' : '') + 'e-services/pre-checkout/' + pub_id + '/' + data, '_self');
						},
						error: function (txt) {
							console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
						}
					});
				});

				$('#b2make-services-carrinho-continuar').on('mouseup tap', function (e) {
					if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

					var id_func = 'carrinho-continuar';
					$.ajax({
						cache: false,
						type: 'GET',
						dataType: 'json',
						url: b2make.hostname + '/servicos/config.json',
						beforeSend: function () {
						},
						success: function (txt) {
							var url_continuar_comprando = txt.url_continuar_comprando;

							if (url_continuar_comprando) {
								window.open(url_continuar_comprando, '_self');
							} else {
								window.open('../../', '_self');
							}
						},
						error: function (txt) {
							console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
						}
					});
				});

			} else {
				carrinho = b2make.services_carrinho_cont;
			}

			carrinho.fadeIn(b2make.services.fade_time);
		}
	}

	function services() {
		b2make.services = {};

		if (!b2make.msgs.servicosComprar) b2make.msgs.servicosComprar = 'Comprar';
		if (!b2make.msgs.servicosIndisponivel) b2make.msgs.servicosIndisponivel = 'Servi&ccedil;o Indispon&iacute;vel';
		if (!b2make.msgs.servicosExcluirCarrinho) b2make.msgs.servicosExcluirCarrinho = 'Tem certeza que voc&ecirc; quer excluir este servi&ccedil;o do carrinho?';
		if (!b2make.msgs.servicosSemQuantidade) b2make.msgs.servicosSemQuantidade = 'Infelizmente o servi&ccedil;o em quest&atilde;o n&atilde;o est&aacute; dispon&iacute;vel.';
		if (!b2make.msgs.servicosSemQuantidadeSuficiente) b2make.msgs.servicosSemQuantidadeSuficiente = 'Infelizmente n&atilde;o h&aacute; quantidade suficiente em estoque para esse servi&ccedil;o.';
		if (!b2make.services_quantidade_delay_timeout) b2make.services_quantidade_delay_timeout = 400;

		//localStorage.clear();
		b2make.services.fade_time = 200;

		$('.b2make-service-inserir-carrinho').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var id = $(this).attr('data-id');
			var validade = $(this).attr('data-validade');
			var validade_data = $(this).attr('data-validade_data');
			var validade_hora = $(this).attr('data-validade_hora');
			var validade_tipo = $(this).attr('data-validade_tipo');
			var preco = $(this).attr('data-preco');
			var nome = $(this).attr('data-nome');
			var descricao = $(this).attr('data-descricao');
			var quantidade = $(this).attr('data-quantidade');
			var sem_quantidade = false;

			if (quantidade) {
				if (parseInt(quantidade) > 0) {
					services_carrinho_abrir();

					services_carrinho_add({
						nome: nome,
						descricao: descricao,
						preco: preco,
						validade: validade,
						validade_data: validade_data,
						validade_hora: validade_hora,
						validade_tipo: validade_tipo,
						id: id
					});
				} else {
					sem_quantidade = true;
				}
			} else {
				sem_quantidade = true;
			}

			if (sem_quantidade) {
				$.dialogbox_open({
					msg: b2make.msgs.servicosSemQuantidade
				});
			}
		});

		$('.b2make-sclt-excluir-btn').live('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			b2make.services.carrinho_delete_id = $(this).parent().parent().attr('data-id');
			$.dialogbox_open({
				confirm: true,
				calback_yes: 'b2make-sclt-excluir-btn-yes',
				msg: b2make.msgs.servicosExcluirCarrinho
			});
		});

		$('.b2make-sclt-excluir-btn-yes').live('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			services_carrinho_del({
				id: b2make.services.carrinho_delete_id
			});
		});

		$('.b2make-sclt-quant-value').live('keydown', function (e) {
			// Allow: backspace, delete, tab, escape, enter and
			if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
				// Allow: Ctrl+A
				(e.keyCode == 65 && e.ctrlKey === true) ||
				// Allow: home, end, left, right, down, up
				(e.keyCode >= 35 && e.keyCode <= 40)) {
				// let it happen, don't do anything
				return;
			}
			// Ensure that it is a number and stop the keypress
			if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
				e.preventDefault();
			}
		});

		$('.b2make-sclt-quant-value').live('keyup', function (e) {
			var value = parseInt((this.value ? this.value : '0'));

			if (value > 10) {
				value = 10;
				$(this).val(value);
			}

			if (!value) this.value = '';

			b2make.services.carrinho_quantidade = this;
			services_quantidade_delay_to_change(value);
		});
	}

	services();

	// ==================================== Youtube =============================

	function youtube() {
		$('.b2make-youtube-cont').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var url = $(this).parent().parent().attr('data-url');
			var titulo = $(this).parent().parent().attr('data-titulo');

			if (!b2make.start_pretty_photo) {
				$.fn.prettyPhoto({ animation_speed: 'fast', slideshow: 3000, hideflash: true, deeplinking: false });
				b2make.start_pretty_photo = true;
			}
			$.prettyPhoto.open(url, titulo);
		});
	}

	youtube();

	// ==================================== Contents =============================

	function contents_widget_setinha_altura(p) {
		if (!p) p = {};

		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);

		var next = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next');
		var previous = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous');
		var tamanho = ($(obj).attr('data-tamanho-seta') ? $(obj).attr('data-tamanho-seta') : 15);

		var height = $(obj).find('.b2make-widget-out').find('.b2make-contents').height();
		var top = Math.floor(parseInt(height) / 2) - Math.floor(parseInt(tamanho) / 2);

		next.css('top', top + 'px');
		previous.css('top', top + 'px');
	}

	function contents_widget_setinha_update(p) {
		if (!p) p = {};

		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);

		var next = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next');
		var previous = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous');
		var tamanho = ($(obj).attr('data-tamanho-seta') ? $(obj).attr('data-tamanho-seta') : 15);

		next.css('width', tamanho + 'px');
		next.css('height', tamanho + 'px');
		next.css('line-height', tamanho + 'px');
		next.css('font-size', tamanho + 'px');

		previous.css('width', tamanho + 'px');
		previous.css('height', tamanho + 'px');
		previous.css('line-height', tamanho + 'px');
		previous.css('font-size', tamanho + 'px');

		contents_widget_setinha_altura(p);
	}

	function contents_widget_html(p) {
		if (!p) p = {};

		var content_cont;
		var mais_opcoes = true;

		switch (p.layout_tipo) {
			case 'padrao':
				if (p.link_area) {
					content_cont = $('<a href="' + p.content.url + '" class="b2make-content-cont" data-id="' + p.content.id + '" data-href="' + p.content.url + '"></a>');
				} else {
					content_cont = $('<div class="b2make-content-cont" data-id="' + p.content.id + '" data-href="' + p.content.url + '"></div>');
				}

				var imagem = $('<div class="b2make-content-imagem" style="background-image:url(' + (p.content.url_imagem ? p.content.url_imagem : '//b2make.com/site/images/b2make-album-sem-imagem.png') + ');"></div>');
				var name = $('<div class="b2make-content-name">' + p.content.nome + '</div>');
				var texto = $('<div class="b2make-content-texto">' + p.content.texto + '</div>');
				var acessar = $('<a class="b2make-content-acessar" href="' + p.content.url + '">' + p.botao_texto + '</a>');

				imagem.appendTo(content_cont);
				name.appendTo(content_cont);
				texto.appendTo(content_cont);
				acessar.appendTo(content_cont);

				if (p.altura_img) {
					imagem.height(p.altura_img);
				}
				break;
			case 'imagem':
				content_cont = $('<a href="' + p.content.url + '" class="b2make-content-cont-2" data-id="' + p.content.id + '" data-href="' + p.content.url + '"></a>');

				var imagem = $('<div class="b2make-content-imagem-mask"><div class="b2make-content-imagem-2" style="background-image:url(' + (p.content.url_imagem_2 ? p.content.url_imagem_2 : '//b2make.com/site/images/b2make-album-sem-imagem.png') + ');"></div></div>');
				var name = $('<div class="b2make-content-name-2">' + p.content.nome + '</div>');
				var texto = $('<div class="b2make-content-texto-2"' + (p.linhas_descricao ? ' style="max-height:' + (p.linhas_descricao * 15) + 'px;"' : '') + '>' + p.content.texto + '</div>');
				var texto_cont = $('<div class="b2make-content-texto-cont"></div>');

				imagem.appendTo(content_cont);
				name.appendTo(texto_cont);
				texto.appendTo(texto_cont);

				if (p.content.tags) {
					var tags = p.content.tags;
					var principal;

					for (var i = 0; i < tags.length; i++) {
						if (!principal) principal = tags[0];

						if (tags[i].principal) {
							principal = tags[i];
							break;
						}
					}

					var tag_cont = $('<div class="b2make-content-tag-cont" style="border-bottom:12px solid #' + principal.cor + '; color: #' + principal.cor + ';">' + principal.nome + '</div>');

					tag_cont.appendTo(texto_cont);
				}

				texto_cont.appendTo(content_cont);

				if (p.altura_textos) {
					texto_cont.height(p.altura_textos);
				}
				break;
			case 'menu':
				content_cont = $('<a href="' + p.content.url + '" class="b2make-content-cont-2" data-id="' + p.content.id + '" data-href="' + p.content.url + '"></a>');

				var imagem = $('<div class="b2make-content-imagem-mask"><div class="b2make-content-imagem-2" style="background-image:url(' + (p.content.url_imagem_2 ? p.content.url_imagem_2 : '//b2make.com/site/images/b2make-album-sem-imagem.png') + ');"></div></div>');
				var name = $('<div class="b2make-content-name-2">' + p.content.nome + '</div>');
				var texto = $('<div class="b2make-content-texto-2"' + (p.linhas_descricao ? ' style="max-height:' + (p.linhas_descricao * 15) + 'px;"' : '') + '>' + p.content.texto + '</div>');
				var texto_cont = $('<div class="b2make-content-texto-cont"></div>');
				var acessar = $('<div class="b2make-content-acessar-mask"><a class="b2make-content-acessar-2" href="' + p.content.url + '">' + p.botao_texto + '</a></div>');

				imagem.appendTo(content_cont);
				name.appendTo(texto_cont);
				texto.appendTo(texto_cont);
				acessar.appendTo(content_cont);

				texto_cont.appendTo(content_cont);

				if (p.altura_textos) {
					texto_cont.height(p.altura_textos);
				}
				break;
			case 'mosaico':
				mais_opcoes = false;

				if (p.num > 3) break;

				content_cont = $('<a href="' + p.content.url + '" class="b2make-content-cont-3" data-pos="' + (p.num + 1) + '" data-id="' + p.content.id + '" data-href="' + p.content.url + '"></a>');

				var imagem = $('<div class="b2make-content-imagem-mask"><div class="b2make-content-imagem-2" style="background-image:url(' + (p.content.url_imagem_2 ? p.content.url_imagem_2 : '//b2make.com/site/images/b2make-album-sem-imagem.png') + ');"></div></div>');
				var name = $('<div class="b2make-content-name-2">' + p.content.nome + '</div>');
				var texto = $('<div class="b2make-content-texto-2"' + (p.linhas_descricao ? ' style="max-height:' + (p.linhas_descricao * 15) + 'px;"' : '') + '>' + p.content.texto + '</div>');
				var texto_cont = $('<div class="b2make-content-texto-cont"></div>');

				imagem.appendTo(content_cont);
				name.appendTo(texto_cont);
				texto.appendTo(texto_cont);
				texto_cont.appendTo(content_cont);

				if (p.altura_textos) {
					texto_cont.height(p.altura_textos);
				}

				break;
			case 'lista-texto':
				mais_opcoes = false;

				content_cont = $('<a class="b2make-content-cont-4" href="' + p.content.url + '">' + p.content.nome + '</a>');

				break;

		}

		if (mais_opcoes) {
			if (p.largura_cont) {
				content_cont.width(p.largura_cont);
			}

			if (p.altura_cont) {
				content_cont.height(p.altura_cont);
			}

			if (p.margem) {
				content_cont.css('margin', p.margem + 'px');
			}
		}

		return content_cont;
	}

	function contents_widget_update_resize(p = {}) {
		var obj = p.obj;

		if ($(obj).attr('data-layout-tipo') == 'mosaico') {
			var width = $(obj).width();
			var height = $(obj).height();
			var margem = ($(obj).attr('data-margem') ? parseInt($(obj).attr('data-margem')) : 10);

			$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').each(function () {
				var pos = $(this).attr('data-pos');
				var col_width = Math.ceil((width - 2 * margem) / 3);
				var line_height = Math.ceil((height - margem) / 2);

				switch (pos) {
					case '1':
						$(this).css('width', col_width + 'px');
						$(this).css('height', height + 'px');
						$(this).css('top', '0px');
						$(this).css('left', '0px');
						break;
					case '2':
						$(this).css('width', col_width + 'px');
						$(this).css('height', line_height + 'px');
						$(this).css('top', '0px');
						$(this).css('left', (col_width + margem) + 'px');
						break;
					case '3':
						$(this).css('width', col_width + 'px');
						$(this).css('height', line_height + 'px');
						$(this).css('top', '0px');
						$(this).css('left', (2 * (col_width + margem)) + 'px');
						break;
					case '4':
						$(this).css('width', ((2 * col_width) + margem) + 'px');
						$(this).css('height', line_height + 'px');
						$(this).css('top', (line_height + margem) + 'px');
						$(this).css('left', (col_width + margem) + 'px');
						break;

				}
			});
		}
	}

	function contents_widget_update(p) {
		if (!p) p = {};

		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
		var id_func = 'contents-html-list';

		$.ajax({
			cache: false,
			type: 'GET',
			dataType: 'json',
			crossDomain: true,
			url: b2make.hostname + '/files/conteudos-list.json',
			beforeSend: function () {
			},
			success: function (txt) {
				var conteudos_list = txt;
				var link_area;

				$(obj).find('.b2make-widget-out').find('.b2make-contents').html('');
				var contents_ids = $(obj).attr('data-contents-ids');
				var found_content;

				$(obj).find('.b2make-widget-out').find('.b2make-contents').append('<div class="b2make-content-next">&#10095;</div>');
				$(obj).find('.b2make-widget-out').find('.b2make-contents').append('<div class="b2make-content-previous">&#10094;</div>');

				if (contents_ids) contents_ids = contents_ids.split(',');

				if ($(obj).attr('data-acao-click') == 'area') {
					link_area = true;
				}

				if ($(obj).attr('data-tamanho-cont')) {
					var largura_cont = $(obj).attr('data-tamanho-cont');
				} else {
					var largura_cont = 160;
				}

				if ($(obj).attr('data-tamanho-cont-2')) {
					var altura_cont = $(obj).attr('data-tamanho-cont-2');
				} else {
					var altura_cont = 280;
				}

				if ($(obj).attr('data-altura-imagem')) {
					var altura_img = $(obj).attr('data-altura-imagem');
				} else {
					var altura_img = 160;
				}

				if ($(obj).attr('data-margem')) {
					var margem = $(obj).attr('data-margem');
				} else {
					var margem = 10;
				}

				if ($(obj).attr('data-margem-seta')) {
					var margem_seta = $(obj).attr('data-margem-seta');
				} else {
					var margem_seta = 15;
				}

				if ($(obj).attr('data-botao-texto')) {
					var botao_texto = $(obj).attr('data-botao-texto');
				} else {
					var botao_texto = b2make.msgs.contentsBotaoTexto;
				}

				if ($(obj).attr('data-layout-tipo')) {
					var layout_tipo = $(obj).attr('data-layout-tipo');
				} else {
					var layout_tipo = 'padrao';
				}

				if ($(obj).attr('data-altura-textos-cont')) {
					var altura_textos = $(obj).attr('data-altura-textos-cont');
				} else {
					var altura_textos = false;
				}

				if ($(obj).attr('data-linhas-descricao')) {
					var linhas_descricao = parseInt($(obj).attr('data-linhas-descricao'));
				} else {
					var linhas_descricao = 3;
				}

				if ($(obj).attr('data-conteudo-tipo') == 'todos-posts') {
					if (conteudos_list) {
						var ordem = ($(obj).attr('data-ordem') ? $(obj).attr('data-ordem') : 'data-desc');
						switch (ordem) {
							case 'alfabetica-asc':
								conteudos_list.sort(function (a, b) {
									if (a.nome < b.nome) return -1;
									if (a.nome > b.nome) return 1;
									return 0;
								});
								break;
							case 'alfabetica-desc':
								conteudos_list.sort(function (a, b) {
									if (a.nome > b.nome) return -1;
									if (a.nome < b.nome) return 1;
									return 0;
								});
								break;
							case 'data-asc':
								conteudos_list.sort(function (a, b) {
									if (!a.data_modificacao) a.data_modificacao = 0;
									if (!b.data_modificacao) b.data_modificacao = 0;

									if (a.data_modificacao < b.data_modificacao) return -1;
									if (a.data_modificacao > b.data_modificacao) return 1;
									return 0;
								});
								break;
							case 'data-desc':
								conteudos_list.sort(function (a, b) {
									if (!a.data_modificacao) a.data_modificacao = 0;
									if (!b.data_modificacao) b.data_modificacao = 0;

									if (a.data_modificacao > b.data_modificacao) return -1;
									if (a.data_modificacao < b.data_modificacao) return 1;
									return 0;
								});
								break;
						}

						var num = 0;
						for (var i = 0; i < conteudos_list.length; i++) {
							$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
								linhas_descricao: linhas_descricao,
								num: num,
								altura_textos: altura_textos,
								layout_tipo: layout_tipo,
								margem: margem,
								botao_texto: botao_texto,
								content: conteudos_list[i],
								largura_cont: largura_cont,
								altura_cont: altura_cont,
								altura_img: altura_img
							}));
							num++;
						}
					}
				} else {
					var num = 0;
					if (conteudos_list)
						for (var i = 0; i < conteudos_list.length; i++) {
							found_content = false;
							if (contents_ids)
								for (var j = 0; j < contents_ids.length; j++) {
									if (contents_ids[j] == conteudos_list[i].id) {
										found_content = true;
									}
								}

							if (!found_content) continue;

							$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
								linhas_descricao: linhas_descricao,
								num: num,
								altura_textos: altura_textos,
								layout_tipo: layout_tipo,
								link_area: link_area,
								margem: margem,
								botao_texto: botao_texto,
								content: conteudos_list[i],
								largura_cont: largura_cont,
								altura_cont: altura_cont,
								altura_img: altura_img
							}));
							num++;
						}
				}

				mobile_sitemap({ obj: obj });

				if (layout_tipo == 'menu') {
					if ($(obj).find('.b2make-widget-out').find('.b2make-menu-tags').length == 0) $(obj).find('.b2make-widget-out').prepend('<div class="b2make-menu-tags"></div>');

					$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').html('');

					var menu_tags = $(obj).find('.b2make-widget-out').find('.b2make-menu-tags');
					var contents_tags = ($(obj).attr('data-contents-tags-ids') ? $(obj).attr('data-contents-tags-ids') : '');
					var contents_tags_arr = (contents_tags ? contents_tags.split(',') : new Array());
					var tag_id = '-1';

					if ($(obj).attr('data-menu-largura-cont')) {
						var menu_largura_cont = $(obj).attr('data-menu-largura-cont');
					} else {
						var menu_largura_cont = 200;
					}

					if ($(obj).attr('data-menu-altura-cont')) {
						var menu_altura_cont = $(obj).attr('data-menu-altura-cont');
					} else {
						var menu_altura_cont = 80;
					}

					if ($(obj).attr('data-menu-margem-cont')) {
						var menu_margem_cont = $(obj).attr('data-menu-margem-cont');
					} else {
						var menu_margem_cont = 5;
					}

					if ($(obj).attr('data-menu-botao-largura-cont')) {
						var menu_botao_largura_cont = $(obj).attr('data-menu-botao-largura-cont');
					} else {
						var menu_botao_largura_cont = 100;
					}

					if ($(obj).attr('data-menu-botao-altura-cont')) {
						var menu_botao_altura_cont = $(obj).attr('data-menu-botao-altura-cont');
					} else {
						var menu_botao_altura_cont = 40;
					}

					if ($(obj).attr('data-menu-botao-margem-cont')) {
						var menu_botao_margem_cont = $(obj).attr('data-menu-botao-margem-cont');
					} else {
						var menu_botao_margem_cont = 30;
					}

					if (contents_tags_arr) {
						var conteudos_tags_lista = b2make.conteudos_tags_lista;
						var cor_atual = '';

						for (var i = 0; i < contents_tags_arr.length; i++) {
							for (var j = 0; j < conteudos_tags_lista.length; j++) {
								if (conteudos_tags_lista[j].id == contents_tags_arr[i]) {
									var cor = conteudos_tags_lista[j].cor;
									var nome = conteudos_tags_lista[j].nome;

									if (i == (b2make.contents.tag_id_atual ? b2make.contents.tag_id_atual : 0)) {
										tag_id = contents_tags_arr[i];
										b2make.contents.cor_atual = cor_atual = cor;
										menu_tags.append($('<div class="b2make-menu-tag" style="border-bottom: solid 5px #' + cor + '; color: #' + cor + '; width: ' + menu_largura_cont + 'px; line-height: ' + menu_altura_cont + 'px; margin: ' + menu_margem_cont + 'px;" data-id="' + contents_tags_arr[i] + '" data-cor="' + cor + '" data-atual="sim">' + nome + '</div>'));
									} else {
										menu_tags.append($('<div class="b2make-menu-tag" style="width: ' + menu_largura_cont + 'px; line-height: ' + menu_altura_cont + 'px; margin: ' + menu_margem_cont + 'px;" data-id="' + contents_tags_arr[i] + '" data-cor="' + cor + '">' + nome + '</div>'));
									}
								}
							}
						}

						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').each(function () {
							var id = $(this).attr('data-id');

							if (conteudos_list)
								for (var i = 0; i < conteudos_list.length; i++) {
									var conteudo_list = conteudos_list[i];
									var found = false;

									if (id == conteudo_list.id) {
										if (conteudo_list.tags) {
											var tags = conteudo_list.tags;

											for (var j = 0; j < tags.length; j++) {
												if (tags[j].id == tag_id) {
													found = true;
													break;
												}
											}
										}

										if (found) {
											$(this).show();
											$(this).attr('data-show', true);
											$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('background-color', '#' + cor_atual);
											$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').width(menu_botao_largura_cont);
											$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('line-height', menu_botao_altura_cont + 'px');
											$(this).find('.b2make-content-acessar-mask').css('bottom', '-' + menu_botao_margem_cont + 'px');
											$(this).css('marginBottom', (parseInt(menu_botao_margem_cont) + parseInt(menu_botao_altura_cont)) + 'px');
										} else {
											$(this).hide();
										}
									}
								}
						});
					}
				} else {
					$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').remove();
				}

				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').css('right', margem_seta + 'px');
				$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').css('left', margem_seta + 'px');

				contents_widget_setinha_update({ obj: obj });

				if ($(obj).attr('data-widget-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-widget-color-ahex')));
				if ($(obj).attr('data-seta-color-ahex')) {
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').css('color', jpicker_ahex_2_rgba($(obj).attr('data-seta-color-ahex')));
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').css('color', jpicker_ahex_2_rgba($(obj).attr('data-seta-color-ahex')));
				}

				switch (layout_tipo) {
					case 'padrao':
						if ($(obj).attr('data-caixa-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex')));
						if ($(obj).attr('data-botao-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-botao-color-ahex')));
						if ($(obj).attr('data-titulo-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name').css('color', jpicker_ahex_2_rgba($(obj).attr('data-titulo-text-color-ahex')));
						if ($(obj).attr('data-texto-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto').css('color', jpicker_ahex_2_rgba($(obj).attr('data-texto-text-color-ahex')));
						if ($(obj).attr('data-botao-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('color', jpicker_ahex_2_rgba($(obj).attr('data-botao-text-color-ahex')));
						break;
					case 'imagem':
						if ($(obj).attr('data-caixa-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex')));
						if ($(obj).attr('data-titulo-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color', jpicker_ahex_2_rgba($(obj).attr('data-titulo-text-color-ahex')));
						if ($(obj).attr('data-texto-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color', jpicker_ahex_2_rgba($(obj).attr('data-texto-text-color-ahex')));
						if ($(obj).attr('data-texto-textos-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-texto-textos-color-ahex')));
						break;
					case 'menu':
						if ($(obj).attr('data-caixa-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex')));
						if ($(obj).attr('data-titulo-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color', jpicker_ahex_2_rgba($(obj).attr('data-titulo-text-color-ahex')));
						if ($(obj).attr('data-texto-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color', jpicker_ahex_2_rgba($(obj).attr('data-texto-text-color-ahex')));
						if ($(obj).attr('data-texto-textos-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-texto-textos-color-ahex')));
						if ($(obj).attr('data-menu-color-ahex')) {
							$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag').css('background-color', jpicker_ahex_2_rgba($(obj).attr('data-menu-color-ahex')));
							$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('border-bottom', 'solid 5px ' + jpicker_ahex_2_rgba($(obj).attr('data-menu-color-ahex')));
						}
						if ($(obj).attr('data-menu-text-color-ahex')) {
							$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('color', jpicker_ahex_2_rgba($(obj).attr('data-menu-text-color-ahex')));
							b2make.contents.cor_padrao = jpicker_ahex_2_rgba($(obj).attr('data-menu-text-color-ahex'));
						} else {
							b2make.contents.cor_padrao = '#606060';
						}
						if ($(obj).attr('data-menu-botao-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('color', jpicker_ahex_2_rgba($(obj).attr('data-menu-botao-color-ahex')));

						break;
					case 'mosaico':
						if ($(obj).attr('data-caixa-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').css('background-color', $.jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex')));
						if ($(obj).attr('data-titulo-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color', $.jpicker_ahex_2_rgba($(obj).attr('data-titulo-text-color-ahex')));
						if ($(obj).attr('data-texto-text-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color', $.jpicker_ahex_2_rgba($(obj).attr('data-texto-text-color-ahex')));
						if ($(obj).attr('data-texto-textos-color-ahex')) $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').css('background-color', $.jpicker_ahex_2_rgba($(obj).attr('data-texto-textos-color-ahex')));

						contents_widget_update_resize({ obj: obj });
						break;

				}

				var ids = new Array('titulo', 'texto', 'botao', 'tags', 'menu', 'menu-botao');
				var mudar_height = false;
				var target;

				for (var i = 0; i < ids.length; i++) {
					var id = ids[i];

					mudar_height = false;

					switch (layout_tipo) {
						case 'padrao':
							switch (id) {
								case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name'); mudar_height = true; break;
								case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto'); mudar_height = true; break;
								case 'botao': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar'); break;
							}
							break;
						case 'imagem':
							switch (id) {
								case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
								case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
								case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
							}
							break;
						case 'menu':
							switch (id) {
								case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
								case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
								case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
								case 'menu': target = $(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag'); break;
								case 'menu-botao': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2'); break;
							}
							break;
						case 'mosaico':
							switch (id) {
								case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
								case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
								case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
							}
							break;

					}

					if ($(obj).attr('data-' + id + '-font-family')) target.css('fontFamily', $(obj).attr('data-' + id + '-font-family'));
					if ($(obj).attr('data-' + id + '-font-size')) {
						target.css('fontSize', $(obj).attr('data-' + id + '-font-size') + 'px');

						var height = b2make.contents.conteiner_height_lines * ($(obj).attr('data-titulo-font-size') ? parseInt($(obj).attr('data-titulo-font-size')) : b2make.contents.conteiner_height_name) + b2make.contents.conteiner_height_lines * ($(obj).attr('data-texto-font-size') ? parseInt($(obj).attr('data-texto-font-size')) : b2make.contents.conteiner_height_texto);
						height = height + b2make.contents.conteiner_height_default;

						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('height', height + 'px');

						if (mudar_height) {
							var line_height = parseInt($(obj).attr('data-' + id + '-font-size')) + b2make.contents.conteiner_height_margin;
							target.css('line-height', line_height + 'px');

							target.css('max-height', (line_height * b2make.contents.conteiner_height_lines) + 'px');
						}
					}
					if ($(obj).attr('data-' + id + '-font-align')) target.css('textAlign', $(obj).attr('data-' + id + '-font-align'));
					if ($(obj).attr('data-' + id + '-font-italico')) target.css('fontStyle', ($(obj).attr('data-' + id + '-font-italico') == 'sim' ? 'italic' : 'normal'));
					if ($(obj).attr('data-' + id + '-font-negrito')) target.css('fontWeight', ($(obj).attr('data-' + id + '-font-negrito') == 'sim' ? 'bold' : 'normal'));
				}

				if (layout_tipo == 'mosaico' || layout_tipo == 'lista-texto') {
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').hide();
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').hide();
				} else {
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').show();
					$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').show();
				}

				$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
			}
		});
	}

	function contents() {
		b2make.contents = {};

		if (!b2make.msgs.contentsBotaoTexto) b2make.msgs.contentsBotaoTexto = 'Leia Mais';

		b2make.contents.conteiner_height_default = 220;
		b2make.contents.conteiner_height_lines = 3;
		b2make.contents.conteiner_height_margin = 2;
		b2make.contents.conteiner_height_name = 18;
		b2make.contents.conteiner_height_texto = 13;

		if (b2make.widget_start['contents']) {
			$.ajax({
				cache: false,
				type: 'GET',
				dataType: 'json',
				crossDomain: true,
				url: b2make.hostname + '/files/library/posts-filter.json',
				beforeSend: function () {
				},
				success: function (txt) {
					var conteudos_tags_lista = new Array();
					var tags = txt.tags;

					if (tags) {
						for (var i = 0; i < tags.length; i++) {
							conteudos_tags_lista.push({
								nome: tags[i].nome,
								cor: tags[i].cor,
								id: tags[i].id_site_conteudos_tags,
								id_pai: tags[i].id_site_conteudos_tags_pai
							});
						}
					}

					b2make.conteudos_tags_lista = conteudos_tags_lista;

					$('.b2make-widget[data-type="contents"]').each(function () {
						$(this).find('.b2make-widget-out').find('.b2make-widget-loading').show();
						$(this).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').html('&#10095;');
						$(this).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').html('&#10094;');
						contents_widget_update({ obj: this });
					});
				},
				error: function (txt) {
					console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
				}
			});

			$(document.body).on('mouseup tap', '.b2make-content-next', function (e) {
				if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

				var obj = $(this).parent();

				obj.find('.b2make-content-cont').first().appendTo(obj);
				obj.find('.b2make-content-cont-2').first().appendTo(obj);
			});

			$(document.body).on('mouseup tap', '.b2make-content-previous', function (e) {
				if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

				var obj = $(this).parent();

				obj.find('.b2make-content-cont').last().prependTo(obj);
				obj.find('.b2make-content-cont-2').last().prependTo(obj);
			});

			$(document.body).on('mouseenter', '.b2make-content-imagem-2', function (e) {
				$(this).addClass('b2make-img-zoom-transition');
			});

			$(document.body).on('mouseleave', '.b2make-content-imagem-2', function (e) {
				$(this).removeClass('b2make-img-zoom-transition');
			});

			$(document.body).on('mouseenter', '.b2make-menu-tag', function (e) {
				if (!$(this).attr('data-atual')) {
					var cor_padrao = b2make.contents.cor_padrao;
					var cor_atual = b2make.contents.cor_atual;
					var cor = ($(this).attr('data-cor') ? '#' + $(this).attr('data-cor') : cor_padrao);

					$(this).css('color', cor);
					$(this).css('border-bottom', 'solid 5px ' + cor);
				}
			});

			$(document.body).on('mouseleave', '.b2make-menu-tag', function (e) {
				if (!$(this).attr('data-atual')) {
					var cor_padrao = b2make.contents.cor_padrao;

					$(this).css('color', cor_padrao);
					$(this).css('border-bottom', 'solid 5px #e1e1e1');
				}
			});

			$(document.body).on('mouseup tap', '.b2make-menu-tag', function (e) {
				if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

				var obj = $(this).parent().parent().parent().get(0);

				var contents_tags = ($(obj).attr('data-contents-tags-ids') ? $(obj).attr('data-contents-tags-ids') : '');
				var contents_tags_arr = (contents_tags ? contents_tags.split(',') : new Array());

				var id = $(this).attr('data-id');

				for (var i = 0; i < contents_tags_arr.length; i++) {
					if (id == contents_tags_arr[i]) {
						b2make.contents.tag_id_atual = i;

						$(this).parent().find('.b2make-menu-tag').each(function () {
							$(this).removeAttr('data-atual');
						});
						$(this).attr('data-atual', 'sim');

						contents_widget_update({ obj: obj });
						break;
					}
				}
			});
		}
	}

	contents();

	// ==================================== Formulários =============================

	$.bordas_update = function (p) {
		var borda_name = 'data-bordas-todas';

		if (p.borda_name) {
			borda_name = p.borda_name;
		} else {
			if ($(p.obj).attr('data-borda-name')) {
				borda_name = $(p.obj).attr('data-borda-name');
			}
		}

		var todas = $(p.obj).attr(borda_name);

		if (!todas) todas = '0;solid;rgb(0,0,0);0;000000ff';

		var todas_arr = todas.split(';');

		p.target.css('border', todas_arr[0] + 'px ' + todas_arr[1] + ' ' + todas_arr[2]);
		p.target.css('-webkit-border-radius', todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px');
		p.target.css('border-radius', todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px');
	}

	function formularios_widgets_update(p) {
		if (!p) p = {};

		var plugin_id = 'formularios';

		$('.b2make-widget').each(function () {
			switch ($(this).attr('data-type')) {
				case plugin_id:
					var obj = this;
					var widget = $(obj).find('.b2make-widget-out').find('.b2make-widget-formularios');
					var id = $(obj).attr('id');
					var id_referencia = $(obj).attr('data-referencia-id');

					if (p.id) {
						if (p.id != id) {
							return;
						}
					}

					switch (p.type) {
						case 'del':
							if (p.id_referencia == id_referencia) {
								widget.html('');
								widget.parent().find('.b2make-library-loading').show();
							}
							break;
						default:
							widget.html('');

							if (id_referencia == '0' || !id_referencia) {
								$(obj).find('.b2make-widget-out').find('.b2make-library-loading').show();
								return true;
							}

							var campos = b2make.formularios.campos[id_referencia];
							var label, input;

							if (campos)
								for (var i = 0; i < campos.length; i++) {
									label = $('<label for="' + campos[i].campo + '">' + campos[i].title + '</label>');

									switch (campos[i].tipo) {
										case 'text': input = $('<input type="text" id="' + campos[i].campo + '" name="' + campos[i].campo + '">'); break;
										case 'textarea': input = $('<textarea id="' + campos[i].campo + '" name="' + campos[i].campo + '"></textarea>'); break;
									}

									widget.append(label);
									widget.append(input);

									if (campos[i].obrigatorio) $('#' + campos[i].campo).attr('data-obrigatorio', true);
								}

							input = $('<input type="hidden" id="_b2make-form-id" name="_b2make-form-id" value="' + id_referencia + '">');

							widget.append(input);

							var button = $('<input type="button" class="b2make-formularios-button" value="' + b2make.msgs.formulariosButtonValue + '">');
							widget.append(button);

							$(obj).find('.b2make-widget-out').find('.b2make-library-loading').hide();

							if (p.widget_add) {
								return;
							}

							// Colors

							if ($(obj).attr('data-caixa-color-ahex')) {
								var bg = jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-ahex'));

								$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('background-color', bg);
								$(obj).find('.b2make-widget-out').find('form').find('textarea').css('background-color', bg);
							}

							if ($(obj).attr('data-caixa-color-2-ahex')) {
								var bg = jpicker_ahex_2_rgba($(obj).attr('data-caixa-color-2-ahex'));

								$(obj).find('.b2make-widget-out').find('form').find('input[type="button"]').css('background-color', bg);
							}

							if ($(obj).attr('data-rotulo-color-ahex')) {
								var bg = jpicker_ahex_2_rgba($(obj).attr('data-rotulo-color-ahex'));

								$(obj).find('.b2make-widget-out').find('form').find('label').css('color', bg);
							}

							if ($(obj).attr('data-preenchimento-color-ahex')) {
								var bg = jpicker_ahex_2_rgba($(obj).attr('data-preenchimento-color-ahex'));

								$(obj).find('.b2make-widget-out').find('form').find('input[type="text"]').css('color', bg);
								$(obj).find('.b2make-widget-out').find('form').find('textarea').css('color', bg);
							}

							if ($(obj).attr('data-botao-color-ahex')) {
								var bg = jpicker_ahex_2_rgba($(obj).attr('data-botao-color-ahex'));

								$(obj).find('.b2make-widget-out').find('form').find('input[type="button"]').css('color', bg);
							}

							// Bordas

							var target;
							var target2;

							target = $(obj).find('.b2make-widget-out').find('form').find('input[type="text"]');
							target2 = $(obj).find('.b2make-widget-out').find('form').find('textarea');

							$.bordas_update({
								borda_name: 'data-borda-caixa',
								obj: obj,
								target: target
							});
							$.bordas_update({
								borda_name: 'data-borda-caixa',
								obj: obj,
								target: target2
							});

							var todas = $(obj).attr('data-borda-caixa');
							var todas_saida = '';

							if (!todas) todas = '0;solid;rgb(0,0,0);0;000000ff';

							var todas_arr = todas.split(';');

							var p2 = parseInt(todas_arr[3]);
							var w = parseInt(todas_arr[0]) * 2 + 20 + p2;

							target.css('width', 'calc(100% - ' + w + 'px)');
							target2.css('width', 'calc(100% - ' + w + 'px)');
							target.css('padding', Math.floor(p2 / 2) + 'px');
							target2.css('padding', Math.floor(p2 / 2) + 'px');
							target2.css('height', 'calc(70px + ' + w + 'px)');

							target = $(obj).find('.b2make-widget-out').find('form').find('input[type="button"]');

							$.bordas_update({
								borda_name: 'data-borda-botao',
								obj: obj,
								target: target
							});

							// Font

							var types = new Array('rotulo', 'preenchimento', 'botao');
							var modifications = new Array('changeFontFamily', 'changeFontSize', 'changeFontAlign', 'changeFontItalico', 'changeFontNegrito');

							for (var i = 0; i < types.length; i++) {
								var target;
								var cssVar = '';
								var noSize = false;
								var type = types[i];

								switch (type) {
									case 'rotulo': target = $(obj).find('.b2make-widget-out').find('form').find('label'); break;
									case 'preenchimento': target = $(obj).find('.b2make-widget-out').find('form').find('input[type="text"],textarea'); break;
									case 'botao': target = $(obj).find('.b2make-widget-out').find('form').find('input[type="button"]'); noSize = true; break;

								}

								for (var j = 0; j < modifications.length; j++) {
									switch (modifications[j]) {
										case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar, $(obj).attr('data-' + type + '-font-family')); break;
										case 'changeFontSize': cssVar = 'fontSize'; target.css(cssVar, $(obj).attr('data-' + type + '-font-size') + 'px'); target.css('line-height', $(obj).attr('data-' + type + '-font-size') + 'px');
											var size = parseInt($(obj).attr('data-' + type + '-font-size')); target.css('padding', Math.floor(size / 2.5) + 'px ' + Math.floor(size / 3) + 'px');
											if (!noSize) {
												target.css('width', 'calc(100% - ' + (Math.floor(size / 2.5)) + 'px)');
											}
											break;
										case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar, $(obj).attr('data-' + type + '-font-align')); break;
										case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar, ($(obj).attr('data-' + type + '-font-italico') == 'sim' ? 'italic' : 'normal')); break;
										case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar, ($(obj).attr('data-' + type + '-font-negrito') == 'sim' ? 'bold' : 'normal')); break;
									}
								}
							}

					}

					break;
			}
		});
	}

	function formularios_widgets_start(p) {
		if (!p) p = {};

		var id_func = 'formularios';

		$.ajax({
			cache: false,
			type: 'GET',
			dataType: 'json',
			url: b2make.hostname + '/files/library/' + id_func + '.json',
			beforeSend: function () {
			},
			success: function (txt) {
				var json = txt;

				if (json)
					for (var i = 0; i < json.length; i++) {
						b2make.formularios.campos[json[i].id] = json[i].campos;
					}

				formularios_widgets_update(null);
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + id_func + ' - ' + txt);
			}
		});
	}

	function formularios() {
		b2make.formularios = {};
		b2make.formularios.campos = new Array();

		if (!b2make.msgs.formulariosButtonValue) b2make.msgs.formulariosButtonValue = 'Enviar';

		if ($('.b2make-widget-formularios').length > 0) {
			formularios_widgets_start(null);
		}

		$('.b2make-widget-form_contato input[type="button"],.b2make-widget-formularios input[type="button"]').live('mouseover', function () {
			var pai = $(this).parent().parent().parent();
			var color = '#FFF';
			var bg = '#464E56';

			if (pai.attr('data-botao-color-ahex')) {
				bg = jpicker_ahex_2_rgba(pai.attr('data-botao-color-ahex'));
			}
			if (pai.attr('data-caixa-color-2-ahex')) {
				color = jpicker_ahex_2_rgba(pai.attr('data-caixa-color-2-ahex'));
			}

			$(this).css('color', color);
			$(this).css('background-color', bg);
		});

		$('.b2make-widget-form_contato input[type="button"],.b2make-widget-formularios input[type="button"]').live('mouseout', function () {
			var pai = $(this).parent().parent().parent();
			var color = '#58585B';
			var bg = '#D7D9DD';

			if (pai.attr('data-botao-color-ahex')) {
				color = jpicker_ahex_2_rgba(pai.attr('data-botao-color-ahex'));
			}
			if (pai.attr('data-caixa-color-2-ahex')) {
				bg = jpicker_ahex_2_rgba(pai.attr('data-caixa-color-2-ahex'));
			}

			$(this).css('color', color);
			$(this).css('background-color', bg);
		});

		$('.b2make-widget-form_contato input[type="button"]').bind('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var pai = $(this).parent().parent().parent();
			var opcao = 'FormContato';
			var enviar = true;
			var campo_obrigatorio = false;
			var bg_caixa;
			var color_caixa;
			var bg_erro = '#FF0000';
			var color_erro = '#FFFFFF';

			if (pai.attr('data-caixa-color-ahex')) {
				bg_caixa = jpicker_ahex_2_rgba(pai.attr('data-caixa-color-ahex'));
			}
			if (pai.attr('data-preenchimento-color-ahex')) {
				color_caixa = jpicker_ahex_2_rgba(pai.attr('data-preenchimento-color-ahex'));
			}

			$(this).parent().find('.b2make-formulario-obrigatorio').remove();
			$(this).parent().find('.b2make-formulario-enviado').remove();
			$(this).parent().find('input[type="text"],textarea').each(function () {
				if (bg_caixa) {
					$(this).css('background-color', bg_caixa);
				}
				if (color_caixa) {
					$(this).css('color', color_caixa);
				}

				$(this).removeClass('b2make-formulario-erro');
				if (!$(this).val()) {
					enviar = false;
					$(this).addClass('b2make-formulario-erro');
					campo_obrigatorio = true;

					if (bg_caixa) {
						$(this).css('background-color', bg_erro);
					}
					if (color_caixa) {
						$(this).css('color', color_erro);
					}
				}

				if ($(this).attr('name') == 'form_contato-email') {
					if (!checkMail($(this).val())) {
						enviar = false;
						$(this).addClass('b2make-formulario-erro');

						if (bg_caixa) {
							$(this).css('background-color', bg_erro);
						}
						if (color_caixa) {
							$(this).css('color', color_erro);
						}
					}
				}
			});

			if (!enviar) {
				$(this).parent().parent().parent().stop().effect("shake");
				$(this).before($('<div class="b2make-formulario-obrigatorio">' + (campo_obrigatorio ? b2make.formularioObrigatorioText : b2make.formularioEmailInvalido) + '</div>'));
			} else {
				var pub_id = $(this).parent().attr('data-pub-id');
				var url = 'https://b2make.com/webservices/formulario-contato/';
				var params = 'pub_id=' + pub_id + '&' + $(this).parent().serialize();
				var obj = $(this).parent();

				obj.append('<div class="b2make-carregando"></div>');

				$.ajax({
					type: 'POST',
					url: url + '?' + params,
					data: params,
					dataType: "json",
					beforeSend: function () {

					},
					success: function (txt) {
						var dados = txt;

						switch (dados.status) {
							case 'Ok':
								obj.find('input[type="button"]').before($('<div class="b2make-formulario-enviado">' + b2make.formularioEmailEnviado + '</div>'));
								obj.get(0).reset();
								break;
							default:
								console.log('ERROR - ' + opcao + ' - ' + dados.status);

						}

						obj.find('.b2make-carregando').remove();
					},
					error: function (txt) {
						console.log('ERROR AJAX - ' + opcao + ' - ' + txt);
						obj.find('.b2make-carregando').remove();
					}
				});
			}
		});

		$('.b2make-widget-formularios input[type="button"]').live('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var pai = $(this).parent().parent().parent();
			var opcao = 'FormContato';
			var enviar = true;
			var campo_obrigatorio = false;
			var bg_caixa;
			var color_caixa;
			var bg_erro = '#FF0000';
			var color_erro = '#FFFFFF';

			if (pai.attr('data-caixa-color-ahex')) {
				bg_caixa = jpicker_ahex_2_rgba(pai.attr('data-caixa-color-ahex'));
			}
			if (pai.attr('data-preenchimento-color-ahex')) {
				color_caixa = jpicker_ahex_2_rgba(pai.attr('data-preenchimento-color-ahex'));
			}

			$(this).parent().find('.b2make-formulario-obrigatorio').remove();
			$(this).parent().find('.b2make-formulario-enviado').remove();
			$(this).parent().find('input[type="text"],textarea').each(function () {
				if (bg_caixa) {
					$(this).css('background-color', bg_caixa);
				}
				if (color_caixa) {
					$(this).css('color', color_caixa);
				}

				$(this).removeClass('b2make-formulario-erro');
				if (!$(this).val() && $(this).attr('data-obrigatorio')) {
					enviar = false;
					$(this).addClass('b2make-formulario-erro');
					campo_obrigatorio = true;

					if (bg_caixa) {
						$(this).css('background-color', bg_erro);
					}
					if (color_caixa) {
						$(this).css('color', color_erro);
					}
				}
			});

			if (!enviar) {
				$(this).parent().parent().parent().stop().effect("shake");
				$(this).before($('<div class="b2make-formulario-obrigatorio">' + (campo_obrigatorio ? b2make.formularioObrigatorioText : b2make.formularioEmailInvalido) + '</div>'));
			} else {
				var pub_id = $(this).parent().attr('data-pub-id');
				var url = 'https://b2make.com/webservices/formulario-contato/';
				var params = 'pub_id=' + pub_id + '&' + $(this).parent().serialize();
				var obj = $(this).parent();

				obj.append('<div class="b2make-carregando"></div>');

				$.ajax({
					type: 'POST',
					url: url + '?' + params,
					data: params,
					dataType: "json",
					beforeSend: function () {

					},
					success: function (txt) {
						var dados = txt;

						switch (dados.status) {
							case 'Ok':
								obj.find('input[type="button"]').before($('<div class="b2make-formulario-enviado">' + b2make.formularioEmailEnviado + '</div>'));
								obj.get(0).reset();
								break;
							default:
								console.log('ERROR - ' + opcao + ' - ' + dados.status);

						}

						obj.find('.b2make-carregando').remove();
					},
					error: function (txt) {
						console.log('ERROR AJAX - ' + opcao + ' - ' + txt);
						console.log(txt);
						obj.find('.b2make-carregando').remove();
					}
				});
			}
		});
	}

	formularios();

	// ==================================== Album de Fotos =============================

	function album_fotos_mudar_foto_mini(p) {

	}

	function album_fotos_mudar_foto(p) {
		if (b2make.album_fotos.animating) return false;

		var foto_id = p.id;
		var obj = p.obj;
		var imagem_cont = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont');
		var imagem_url_1 = imagem_cont.find('.b2make-albumfotos-widget-image-2').attr('data-imagem');
		var imagem_url_2 = imagem_cont.find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini[data-id="' + foto_id + '"]').attr('data-imagem');
		var album_id = imagem_cont.find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini[data-id="' + foto_id + '"]').attr('data-album-fotos-id');
		var holder;
		var width = imagem_cont.find('.b2make-albumfotos-widget-image-2').width();
		var tempo_transicao = b2make.album_fotos.tempo_transicao;
		var efeito = b2make.album_fotos.efeito;
		var descricao = '';

		if (imagem_url_1 == imagem_url_2) return false;

		$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry').each(function () {
			$(this).removeAttr('data-status');
		});

		$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-menu').find('.b2make-albumfotos-widget-menu-entry[data-album-fotos-id="' + album_id + '"]').attr('data-status', 'selected');

		$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini').each(function () {
			if ($(this).attr('data-id') == foto_id) {
				if ($(this).attr('data-descricao')) {
					descricao = $(this).attr('data-descricao');
				}
				return false;
			}

			$(this).appendTo($(this).parent());
		});

		if (!b2make.album_fotos.animation_holder[obj]) {
			var height = imagem_cont.find('.b2make-albumfotos-widget-image-2').height();
			var left = imagem_cont.find('.b2make-albumfotos-widget-image-2').css('left');
			var top = imagem_cont.find('.b2make-albumfotos-widget-image-2').css('top');

			holder = b2make.album_fotos.animation_holder[obj] = $('<div class="b2make-albumfotos-animation-holder"></div>');

			var imagem1 = $('<div class="b2make-albumfotos-imagem1"></div>');
			var imagem2 = $('<div class="b2make-albumfotos-imagem2"></div>');

			imagem1.css('width', width + 'px');
			imagem1.css('height', height + 'px');
			imagem2.css('width', width + 'px');
			imagem2.css('height', height + 'px');
			imagem2.css('left', width + 'px');

			imagem1.appendTo(holder);
			imagem2.appendTo(holder);

			holder.css('width', (2 * width) + 'px');
			holder.css('height', height + 'px');
			holder.css('top', '0px');
			holder.css('left', '0px');

			holder.appendTo(imagem_cont);
			holder.hide();
		} else {
			holder = b2make.album_fotos.animation_holder[obj];
		}

		if (p.animation_option) {
			b2make.album_fotos.animation_option = p.animation_option;
		}

		var animation_option = b2make.album_fotos.animation_option;

		b2make.album_fotos.animating = true;

		switch (animation_option) {
			case 'slide-left':
				holder.find('.b2make-albumfotos-imagem1').css('background-image', 'url(' + imagem_url_1 + ')');
				holder.find('.b2make-albumfotos-imagem2').css('background-image', 'url(' + imagem_url_2 + ')');
				holder.show();
				imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
				holder.css('left', '0px');

				holder.stop().animate({
					left: (-width) + 'px'
				}, tempo_transicao, efeito, function () {
					imagem_cont.find('.b2make-albumfotos-widget-image-2').css('background-image', 'url(' + imagem_url_2 + ')');
					imagem_cont.find('.b2make-albumfotos-widget-image-2').attr('data-imagem', imagem_url_2);
					imagem_cont.find('.b2make-albumfotos-widget-images-descricao').html(descricao);
					if (descricao.length > 0) {
						imagem_cont.find('.b2make-albumfotos-widget-images-descricao').show();
					} else {
						imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
					}
					b2make.album_fotos.animating = false;
					holder.hide();
				});
				break;
			case 'slide-right':
				holder.find('.b2make-albumfotos-imagem1').css('background-image', 'url(' + imagem_url_2 + ')');
				holder.find('.b2make-albumfotos-imagem2').css('background-image', 'url(' + imagem_url_1 + ')');
				holder.show();
				imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
				holder.css('left', (-width) + 'px');

				holder.stop().animate({
					left: '0px'
				}, tempo_transicao, efeito, function () {
					imagem_cont.find('.b2make-albumfotos-widget-image-2').css('background-image', 'url(' + imagem_url_2 + ')');
					imagem_cont.find('.b2make-albumfotos-widget-image-2').attr('data-imagem', imagem_url_2);
					imagem_cont.find('.b2make-albumfotos-widget-images-descricao').html(descricao);
					if (descricao.length > 0) {
						imagem_cont.find('.b2make-albumfotos-widget-images-descricao').show();
					} else {
						imagem_cont.find('.b2make-albumfotos-widget-images-descricao').hide();
					}
					b2make.album_fotos.animating = false;
					holder.hide();
				});
				break;
		}
	}

	function album_fotos() {
		b2make.album_fotos = {};

		b2make.album_fotos.animating = false;
		b2make.album_fotos.animation_holder = new Array();
		b2make.album_fotos.tempo_transicao = 500;
		b2make.album_fotos.efeito = 'linear';
		b2make.album_fotos.animation_option = 'slide-left';

		$(document.body).on('mouseup tap', '.b2make-albumfotos-widget-image-mini', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var id = $(this).attr('data-id');
			var obj = $(this).parent().parent().parent().parent().parent().parent().get(0);

			album_fotos_mudar_foto({ id: id, obj: obj });
		});

		$(document.body).on('mouseup tap', '.b2make-albumfotos-widget-right-arrow', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var obj = $(this).parent().parent().parent().parent().parent().get(0);
			var imagem = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini:nth-child(2)');

			var id = imagem.attr('data-id');

			album_fotos_mudar_foto({ id: id, obj: obj, animation_option: 'slide-left' });
		});

		$(document.body).on('mouseup tap', '.b2make-albumfotos-widget-left-arrow', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var obj = $(this).parent().parent().parent().parent().parent().get(0);
			var imagem = $(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini:last-child');

			var id = imagem.attr('data-id');

			album_fotos_mudar_foto({ id: id, obj: obj, animation_option: 'slide-right' });
		});

		$(document.body).on('mouseup tap', '.b2make-albumfotos-widget-menu-entry', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var album_id = $(this).attr('data-album-fotos-id');
			var obj = $(this).parent().parent().parent().parent().get(0);
			var id = '';

			$(obj).find('.b2make-widget-out').find('.b2make-albumfotos-widget-holder-2').find('.b2make-albumfotos-widget-content').find('.b2make-albumfotos-widget-images-cont').find('.b2make-albumfotos-widget-images-mini').find('.b2make-albumfotos-widget-image-mini').each(function () {
				if ($(this).attr('data-album-fotos-id') == album_id) {
					id = $(this).attr('data-id');
					return false;
				}
			});

			album_fotos_mudar_foto({ id: id, obj: obj });
		});

	}

	album_fotos();

	// ==================================== Conteiner Banners =============================

	function conteiner_banners_caixa_posicao_atualizar(p) {
		var obj = p.obj;
		var imagem;

		if (p.proximo) {
			imagem = $(p.proximo);
		} else {
			$(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').each(function () {
				imagem = $(this);
				return false;
			});
		}

		var margem_seta = parseInt(($(obj).attr('data-seta-margem') ? $(obj).attr('data-seta-margem') : '100'));
		var tamanho_seta = parseInt(($(obj).attr('data-seta-tamanho') ? $(obj).attr('data-seta-tamanho') : '100'));

		var seta_left = $(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left');
		var seta_right = $(obj).find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right');

		seta_left.css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');
		seta_right.css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');
		seta_left.find('svg').css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');
		seta_right.find('svg').css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');

		seta_left.css('left', margem_seta + 'px');
		seta_right.css('right', margem_seta + 'px');
	}

	function conteiner_banners_animation_proximo(p) {
		var obj = p.obj;
		var inverso = p.inverso;
		var interacao = b2make.conteiner_animation_interacao[$(obj).attr('id')];
		var tempo_exposicao = ($(obj).attr('data-tempo-exposicao') ? parseInt($(obj).attr('data-tempo-exposicao')) : 5000);

		setTimeout(function () {
			if (interacao == b2make.conteiner_animation_interacao[$(obj).attr('id')])
				conteiner_banners_animation_start({ obj: obj, inverso: inverso });
		}, tempo_exposicao);
	}

	function conteiner_banners_animation_start(p) {
		var obj = p.obj;
		var inverso = p.inverso;
		var atual;
		var proximo;
		var found_flag = false;
		var proxima_flag = false;
		var count = 0;
		var width = $(obj).outerWidth();
		var height = $(obj).outerHeight();
		var tempo_transicao = ($(obj).attr('data-tempo-transicao') ? parseInt($(obj).attr('data-tempo-transicao')) : 500);
		var tipo = ($(obj).attr('data-animation-type') ? $(obj).attr('data-animation-type') : 'slideRight');
		var efeito = ($(obj).attr('data-ease-type') ? $(obj).attr('data-ease-type') : 'linear');
		var cont_hide = '#b2make-conteiner-banners-lista-images-hide';
		var holder = $(obj).find('.b2make-conteiner-banners-holder');

		if (inverso) {
			holder.find('.b2make-conteiner-banners-image').each(function () {
				if (!atual) atual = this;
				proximo = this;

				count++;
			});

			$(cont_hide).append($(proximo));
			$(holder).prepend($(proximo));

			switch (tipo) {
				case 'slideLeft': tipo = 'slideRight'; break;
				case 'slideRight': tipo = 'slideLeft'; break;
				case 'slideTop': tipo = 'slideDown'; break;
				case 'slideDown': tipo = 'slideTop'; break;
			}
		} else {
			holder.find('.b2make-conteiner-banners-image').each(function () {
				if (atual && !proximo) proximo = this;
				if (!atual) atual = this;

				count++;

				if (atual && proximo) return false;
			});
		}

		if (count < 2) return;

		$(proximo).css('position', 'absolute');
		$(proximo).css('zIndex', '1');

		switch (tipo) {
			case 'slideLeft':
				$(proximo).css('top', '0px');
				$(proximo).css('left', width + 'px');

				$(proximo).stop().animate({
					left: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					conteiner_banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'slideRight':
				$(proximo).css('top', '0px');
				$(proximo).css('left', (-width) + 'px');

				$(proximo).stop().animate({
					left: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					conteiner_banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'slideTop':
				$(proximo).css('top', height + 'px');
				$(proximo).css('left', '0px');

				$(proximo).stop().animate({
					top: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					conteiner_banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'slideDown':
				$(proximo).css('top', (-height) + 'px');
				$(proximo).css('left', '0px');

				$(proximo).stop().animate({
					top: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					conteiner_banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'fade':
				$(proximo).css('top', '0px');
				$(proximo).css('left', '0px');
				$(proximo).css('opacity', 0);

				$(proximo).stop().animate({
					opacity: 1
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					conteiner_banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;

		}
	}

	function conteiner_banners() {
		b2make.conteiner_animation_interacao = new Array();

		$('.b2make-conteiner-banners-image').each(function () {
			$(this).attr('data-animation-imagem-atual', 'nao');
		});

		$('.b2make-widget[data-type="conteiner"]').each(function () {
			var obj = this;

			if ($(obj).attr('data-banners-id')) {
				b2make.conteiner_animation_interacao[$(obj).attr('id')] = 0;
				conteiner_banners_animation_proximo({ obj: obj, inverso: true });
			}
		});

		$('.b2make-conteiner-banners-seta-right').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var obj = $(this).parent().parent().get(0);

			b2make.conteiner_animation_interacao[$(obj).attr('id')]++;
			conteiner_banners_animation_start({ obj: obj, inverso: true });
		});

		$('.b2make-conteiner-banners-seta-left').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			var obj = $(this).parent().parent().get(0);

			b2make.conteiner_animation_interacao[$(obj).attr('id')]++;
			conteiner_banners_animation_start({ obj: obj });
		});
	}

	conteiner_banners();

	// ==================================== Banners =============================

	function banners_caixa_posicao_atualizar(p) {
		var obj = p.obj;
		var imagem;

		if (p.proximo) {
			imagem = $(p.proximo);
		} else {
			$(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').each(function () {
				imagem = $(this);
				return false;
			});
		}

		var padding = ($(obj).attr('data-titulo-padding') ? $(obj).attr('data-titulo-padding') : '10');
		var topo = ($(obj).attr('data-titulo-topo') ? $(obj).attr('data-titulo-topo') : '290');
		var margem = ($(obj).attr('data-titulo-margem') ? $(obj).attr('data-titulo-margem') : '20');
		var wv = parseInt((parseInt(imagem.attr('data-image-width')) * parseInt($(obj).outerHeight())) / parseInt(imagem.attr('data-image-height')));
		var tamanho = Math.floor(wv - 2 * parseInt(margem));
		var left = Math.floor((parseInt($(obj).outerWidth()) - tamanho) / 2 - parseInt(padding));

		imagem.find('.b2make-conteiner-banners-image-cont').each(function () {
			$(this).css('top', topo + 'px');
			$(this).css('padding', padding + 'px');
			$(this).css('left', left + 'px');
			$(this).css('width', tamanho + 'px');
		});

		if (p.criar) {
			$(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-image').find('.b2make-conteiner-banners-image-cont').each(function () {
				$(this).css('top', topo + 'px');
				$(this).css('padding', padding + 'px');
				$(this).css('left', left + 'px');
				$(this).css('width', tamanho + 'px');
			});
		} else {
			imagem.find('.b2make-conteiner-banners-image-cont').each(function () {
				$(this).css('top', topo + 'px');
				$(this).css('padding', padding + 'px');
				$(this).css('left', left + 'px');
				$(this).css('width', tamanho + 'px');
			});
		}

		var topo_seta = ($(obj).attr('data-seta-topo') ? $(obj).attr('data-seta-topo') : '150');
		var margem_seta = parseInt(($(obj).attr('data-seta-margem') ? $(obj).attr('data-seta-margem') : '20'));
		var tamanho_seta = parseInt(($(obj).attr('data-seta-tamanho') ? $(obj).attr('data-seta-tamanho') : '30'));

		var seta_left = $(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-left');
		var seta_right = $(obj).find('.b2make-out').find('.b2make-conteiner-banners-holder').find('.b2make-conteiner-banners-seta-right');

		seta_left.css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');
		seta_right.css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');
		seta_left.find('svg').css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');
		seta_right.find('svg').css('width', tamanho_seta + 'px').css('height', tamanho_seta + 'px');

		seta_left.css('top', topo_seta + 'px');
		seta_right.css('top', topo_seta + 'px');

		var lateral = (parseInt($(obj).outerWidth()) - wv) / 2 + margem_seta;

		seta_left.css('left', lateral + 'px');
		seta_right.css('right', lateral + 'px');
	}

	function banners_animation_proximo(p) {
		var obj = p.obj;
		var inverso = p.inverso;
		var interacao = b2make.animation_interacao[$(obj).attr('id')];
		var tempo_exposicao = ($(obj).attr('data-tempo-exposicao') ? parseInt($(obj).attr('data-tempo-exposicao')) : 3000);

		setTimeout(function () {
			if (interacao == b2make.animation_interacao[$(obj).attr('id')])
				banners_animation_start({ obj: obj, inverso: inverso });
		}, tempo_exposicao);
	}

	function banners_animation_start(p) {
		var obj = p.obj;
		var inverso = p.inverso;
		var atual;
		var proximo;
		var found_flag = false;
		var proxima_flag = false;
		var count = 0;
		var width = $(obj).outerWidth();
		var height = $(obj).outerHeight();
		var tempo_transicao = ($(obj).attr('data-tempo-transicao') ? parseInt($(obj).attr('data-tempo-transicao')) : 500);
		var tipo = ($(obj).attr('data-animation-type') ? $(obj).attr('data-animation-type') : 'slideRight');
		var efeito = ($(obj).attr('data-ease-type') ? $(obj).attr('data-ease-type') : 'linear');
		var cont_hide = '#b2make-conteiner-banners-lista-images-hide';
		var holder = $(obj).find('.b2make-widget-out').find('.b2make-banners-widget-holder');

		if (inverso) {
			holder.find('.b2make-banners-widget-image').each(function () {
				if (!atual) atual = this;
				proximo = this;

				count++;
			});

			$(cont_hide).append($(proximo));
			$(holder).prepend($(proximo));

			switch (tipo) {
				case 'slideLeft': tipo = 'slideRight'; break;
				case 'slideRight': tipo = 'slideLeft'; break;
				case 'slideTop': tipo = 'slideDown'; break;
				case 'slideDown': tipo = 'slideTop'; break;
			}
		} else {
			holder.find('.b2make-banners-widget-image').each(function () {
				if (atual && !proximo) proximo = this;
				if (!atual) atual = this;

				count++;

				if (atual && proximo) return false;
			});
		}

		if (count < 2) return;

		$(proximo).css('position', 'absolute');
		$(proximo).css('zIndex', '1');

		switch (tipo) {
			case 'slideLeft':
				$(proximo).css('top', '0px');
				$(proximo).css('left', width + 'px');

				$(proximo).stop().animate({
					left: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					banners_caixa_posicao_atualizar({ proximo: proximo, obj: obj });
					banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'slideRight':
				$(proximo).css('top', '0px');
				$(proximo).css('left', (-width) + 'px');

				$(proximo).stop().animate({
					left: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					banners_caixa_posicao_atualizar({ proximo: proximo, obj: obj });
					banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'slideTop':
				$(proximo).css('top', height + 'px');
				$(proximo).css('left', '0px');

				$(proximo).stop().animate({
					top: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					banners_caixa_posicao_atualizar({ proximo: proximo, obj: obj });
					banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'slideDown':
				$(proximo).css('top', (-height) + 'px');
				$(proximo).css('left', '0px');

				$(proximo).stop().animate({
					top: 0
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					banners_caixa_posicao_atualizar({ proximo: proximo, obj: obj });
					banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;
			case 'fade':
				$(proximo).css('top', '0px');
				$(proximo).css('left', '0px');
				$(proximo).css('opacity', 0);

				$(proximo).stop().animate({
					opacity: 1
				}, tempo_transicao, efeito, function () {

					$(proximo).css('position', 'relative');
					$(proximo).css('top', 'auto');
					$(proximo).css('left', 'auto');
					$(proximo).css('zIndex', 'auto');

					if (!inverso) {
						$(cont_hide).append($(atual));
						$(holder).append($(atual));
					}

					banners_caixa_posicao_atualizar({ proximo: proximo, obj: obj });
					banners_animation_proximo({ obj: obj, inverso: inverso });
				});
				break;

		}
	}

	function banners() {
		b2make.animation_interacao = new Array();

		$('.b2make-banners-widget-image').each(function () {
			$(this).attr('data-animation-imagem-atual', 'nao');
		});

		$('.b2make-widget[data-type="banners"]').each(function () {
			var obj = this;

			b2make.animation_interacao[$(obj).attr('id')] = 0;
			banners_animation_proximo({ obj: obj });
		});

		$('.b2make-banners-widget-seta-right,.b2make-banners-widget-seta-2-right').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			if ($(this).hasClass('b2make-banners-widget-seta-2-right')) {
				var obj = $(this).parent().parent().parent().parent().get(0);
			} else {
				var obj = $(this).parent().parent().parent().get(0);
			}

			b2make.animation_interacao[$(obj).attr('id')]++;
			banners_animation_start({ obj: obj });
		});

		$('.b2make-banners-widget-seta-left,.b2make-banners-widget-seta-2-left').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

			if ($(this).hasClass('b2make-banners-widget-seta-2-left')) {
				var obj = $(this).parent().parent().parent().parent().get(0);
			} else {
				var obj = $(this).parent().parent().parent().get(0);
			}

			b2make.animation_interacao[$(obj).attr('id')]++;
			banners_animation_start({ obj: obj, inverso: true });
		});
	}

	banners();

	// ==================================== Instagram =============================

	function instagram_widget_update(p) {
		if (!p) p = {};

		var opcao = 'instagram_widget_update';

		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
		var count = parseInt(($(obj).attr('data-numero-posts') ? $(obj).attr('data-numero-posts') : 1));

		if (count < 1) count = 1;
		if (count > 16) count = 16;

		if (!$(obj).attr('data-numero-posts')) $(obj).attr('data-numero-posts', count);

		var params = 'access_token=' + b2make.instagram_token + '&count=' + count;

		$.ajax({
			type: 'POST',
			url: 'https://api.instagram.com/v1/users/self/media/recent/' + '?' + params,
			data: params,
			dataType: "jsonp",
			beforeSend: function () {

			},
			success: function (txt) {
				var dados = txt;
				var first = true;

				if (dados.data) {
					if (count == 1) {
						instagram_post({
							url: dados.data[0].link,
							id: dados.data[0].id,
							obj: obj
						});
					} else {
						for (var i = 0; i < dados.data.length; i++) {
							instagram_images({
								url: dados.data[i].link,
								image: dados.data[i].images.standard_resolution.url,
								id: dados.data[i].id,
								obj: obj,
								first: first
							});

							first = false;

							if (count <= i) {
								break;
							}
						}

						var numero = $(obj).attr('data-tamanho-imagens');

						if (numero) {
							$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').find('.b2make-instagram-posts').each(function () {
								$(this).css('margin', Math.floor(((15 * numero) / 220)) + 'px');
								$(this).css('width', numero + 'px');
								$(this).css('height', numero + 'px');
							});
						}
					}
				} else {
					console.log('ERROR - ' + opcao + ' - Erro data');
				}
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + opcao + ' - ' + txt);
			}
		});
	}

	function instagram_verificar_recentes(p) {
		if (!p) p = {};

		var opcao = 'instagram_verificar_recentes';

		if (p.instagram_token) {
			b2make.instagram_token = p.instagram_token;
		}

		var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
		var count = parseInt(($(obj).attr('data-numero-posts') ? $(obj).attr('data-numero-posts') : 1));

		if (count < 1) count = 1;
		if (count > 16) count = 16;

		if (!$(obj).attr('data-numero-posts')) $(obj).attr('data-numero-posts', count);

		var params = 'access_token=' + b2make.instagram_token + '&count=' + count;

		$.ajax({
			type: 'POST',
			url: 'https://api.instagram.com/v1/users/self/media/recent/' + '?' + params,
			data: params,
			dataType: "jsonp",
			beforeSend: function () {

			},
			success: function (txt) {
				var dados = txt;
				var first = true;

				if (dados.data) {
					var id_aux = $(obj).attr('data-instagram-id');
					var id_arr = id_aux.split(',');
					var id = id_arr[0];

					if (dados.data[0].id != id) {
						instagram_widget_update({
							obj: obj
						});
					}
				} else {
					console.log('ERROR - ' + opcao + ' - Erro data');
				}
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + opcao + ' - ' + txt);
			}
		});
	}

	function instagram_post(p) {
		if (!p) p = {};

		var opcao = 'instagram_post';
		var obj = p.obj;

		var params = 'url=' + p.url;

		$.ajax({
			type: 'POST',
			url: 'https://api.instagram.com/oembed/' + '?' + params,
			data: params,
			dataType: "jsonp",
			beforeSend: function () {

			},
			success: function (txt) {
				var dados = txt;

				if (dados.html) {
					$(obj).attr('data-instagram-id', p.id);
					$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').html(dados.html);
				} else {
					console.log('ERROR - ' + opcao + ' - Erro data');
				}
			},
			error: function (txt) {
				console.log('ERROR AJAX - ' + opcao + ' - ' + txt);
			}
		});
	}

	function instagram_images(p) {
		if (!p) p = {};
		var obj = p.obj;

		if (p.first == true) {
			$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').html('');
			$(obj).attr('data-instagram-id', '');
		}

		var ids = $(obj).attr('data-instagram-id');

		if (ids != '') ids = ids + ',';

		$(obj).attr('data-instagram-id', ids + p.id);
		$(obj).find('.b2make-widget-out').find('.b2make-instagram-widget-holder').append('<a class="b2make-instagram-posts" style="background-image:url(' + p.image + ')" href="' + p.url + '" target="_blank"></a>');
	}


	// ==================================== Parallax =============================

	b2make.parallax = {
		parallax_ativo: true,
		frames: false,
		//easin : 'out_elastic',
		posicao_inicial: 0,
		parallax_layer: '.b2make-widget[data-type="conteiner"]',
		parallax_layer_mask: '.layer-mask',
		parallax_layers: '#parallax-layers',
		nav_left: 50,
		nav_btn_w: 25,
		nav_btn_h: 25,
		nav_btn_top: 28,
		velocidade_menu_top: 3,
		velocidade_menu_top_mobile: 3,
		voltar_topo_start_height: 300,
		velocidade: 1
	};

	// ================= Parallax Iniciar ===================

	$.browser.device = (/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent.toLowerCase()));
	//$.browser.device = true;

	if ($.browser.device) {
		parallax_mobile();
	}

	if ($('#b2make-pagina-options').attr('data-pagina-parallax')) {
		if ($('#b2make-pagina-options').attr('data-pagina-parallax') == 'n') {
			b2make.parallax.parallax_ativo = false;
		}
	}

	b2make.parallax_ativo_padrao = b2make.parallax.parallax_ativo;

	parallax_posicionar();
	navegador_posicionar();

	// ==================================== Parallax Mobile =============================

	function parallax_mobile() {
		b2make.parallax.parallax_ativo = false;
	}

	function parallax_mobile_menu(obj) {
		var cont = parseInt(obj.attr('data-cont'));
		var top = $('#parallax-layer-' + cont).offset().top;
		var body = $("html, body");
		var scrollTop = $(window).scrollTop();
		var espaco = top - scrollTop;
		var tempo = 0;

		if (espaco < 0) espaco = espaco * (-1);

		tempo = espaco / b2make.parallax.velocidade_menu_top_mobile;

		body.stop().animate({ scrollTop: top }, tempo, 'swing', function () {

		});
	}

	// ==================================== Parallax Desktop =============================

	function parallax_mover() {
		var pos = parseInt($(window).scrollTop());
		var frames = b2make.parallax.frames;
		var frames_height = b2make.parallax.frames_height;
		var cont_scroll = $('#b2make-parallax-scroll');
		var perc = pos / (b2make.parallax.cont_height);
		var top_scroll = Math.floor(perc * (frames_height - b2make.parallax.window_height));
		var frame_atual = 0;

		cont_scroll.css('top', -top_scroll);

		for (var i = 0; i < frames.length; i++) {
			if (frames[i].pos_top <= top_scroll) {
				if (frames[i].height > b2make.parallax.window_height) {
					frames[i].obj.css('top', frames[i].pos_top);
				} else {
					frames[i].obj.css('top', top_scroll);
				}
			} else {
				frames[i].obj.css('top', frames[i].pos_top);
			}
		}
	}

	function parallax_posicionar() {
		b2make.parallax.window_height = $(window).height();
		b2make.parallax.cont_height = 0;
		b2make.parallax.frames_height = 0;
		var frames_height = 0;

		$(b2make.parallax.parallax_layer).each(function (index) {
			var flag = false;

			if ($(this).attr('data-position')) {
				if ($(this).attr('data-position') == 'fixed') {
					flag = true;
				}
			}

			if (!flag) {
				frames_height = frames_height + parseInt($(this).height());
			}
		});

		if (frames_height <= b2make.parallax.window_height) {
			b2make.parallax.parallax_ativo = false;
		} else {
			b2make.parallax.parallax_ativo = b2make.parallax_ativo_padrao;
		}

		if (b2make.parallax.parallax_ativo) {
			if ($('#b2make-parallax').length == 0) {
				$('<div id="b2make-parallax"></div>').appendTo('body');
				$('<div id="b2make-parallax-scroll"></div>').appendTo('body');

				$('#b2make-parallax-scroll').css('position', 'fixed');
				$('#b2make-parallax-scroll').css('width', '100%');
			}
		}

		var pos_top = (b2make.parallax.posicao_inicial ? b2make.parallax.posicao_inicial : 0);
		var index_novo = 0;

		$(b2make.parallax.parallax_layer).each(function (index) {
			var flag = false;

			if ($(this).attr('data-position')) {
				if ($(this).attr('data-position') == 'fixed') {
					var scrollTop = $(window).scrollTop();
					var top = parseInt($(this).offset().top) - 110 - scrollTop;
					$(this).css('top', top);
					flag = true;
				}
			}

			if (!flag) {
				b2make.parallax.cont_height = b2make.parallax.cont_height + b2make.parallax.velocidade * parseInt($(this).height());
				if (b2make.parallax.parallax_ativo) $(this).appendTo('#b2make-parallax-scroll');
				$(this).css('zIndex', index_novo + 1);
				$(this).css('position', 'absolute');
				$(this).css('top', pos_top);

				if (!b2make.parallax.frames) {
					b2make.parallax.frames = new Array();
				}

				b2make.parallax.frames[index_novo] = {
					obj: $(this),
					pos_top: pos_top,
					height: parseInt($(this).height())
				};

				pos_top = pos_top + parseInt($(this).height());

				index_novo++;
			}
		});

		b2make.parallax.frames_height = pos_top;

		$('#b2make-parallax').height(b2make.parallax.cont_height + b2make.parallax.velocidade * b2make.parallax.window_height);

		if (b2make.parallax.parallax_ativo) {
			parallax_mover();
		}
	}

	// ===================== Menus

	function navegador_layout() {
		var layout = $('#b2make-pagina-options').attr('data-pagina-menu-bolinhas-layout');
		var layout_vars = layout.split('|');
		var w = parseInt(layout_vars[0]);

		var focus = $('._parallax-nav-btn-1');
		var normal = $('._parallax-nav-btn-2');

		focus.css('width', w + 'px');
		normal.css('width', w + 'px');
		focus.css('height', w + 'px');
		normal.css('height', w + 'px');
		focus.css('margin', (w / 2) + 'px');
		normal.css('margin', (w / 2) + 'px');


		if (!b2make.navegador_layout_first) {
			setTimeout(function () { normal.css('background-color', jpicker_ahex_2_rgba(layout_vars[2])); }, 100);
			setTimeout(function () { focus.css('background-color', jpicker_ahex_2_rgba(layout_vars[4])); }, 100);
		} else {
			normal.css('background-color', jpicker_ahex_2_rgba(layout_vars[2]));
			focus.css('background-color', jpicker_ahex_2_rgba(layout_vars[4]));
		}

		b2make.navegador_layout_first = true;

		var todas = layout_vars[1];

		var todas_arr = todas.split(';');

		$('._parallax-nav-btn-2').css('border', todas_arr[0] + 'px ' + todas_arr[1] + ' ' + todas_arr[2]);
		$('._parallax-nav-btn-2').css('-webkit-border-radius', todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px');
		$('._parallax-nav-btn-2').css('border-radius', todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px');

		var todas = layout_vars[3];

		var todas_arr = todas.split(';');

		$('._parallax-nav-btn-1').css('border', todas_arr[0] + 'px ' + todas_arr[1] + ' ' + todas_arr[2]);
		$('._parallax-nav-btn-1').css('-webkit-border-radius', todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px');
		$('._parallax-nav-btn-1').css('border-radius', todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px ' + todas_arr[3] + 'px');
	}

	function navegador_posicionar() {
		var frames = b2make.parallax.frames;
		var frames_height = b2make.parallax.frames_height;
		var areas_desativadas = '';

		if ($('#b2make-pagina-options').attr('data-pagina-menu-bolinha')) {
			if ($('#b2make-pagina-options').attr('data-pagina-menu-bolinha') == 'n') {
				return;
			}
		}

		if ($('#b2make-pagina-options').attr('data-pagina-menu-bolinhas-areas')) {
			areas_desativadas = $('#b2make-pagina-options').attr('data-pagina-menu-bolinhas-areas');
		}

		var areas_desativadas_arr = areas_desativadas.split(',');

		if (!b2make.parallax.nav) {
			b2make.parallax.nav = $('<div id="_parallax-nav"></div>');

			b2make.parallax.nav.css('zIndex', '999');
			b2make.parallax.nav.css('position', 'fixed');

			b2make.parallax.nav_buttons = new Array();

			var height_frame = 0;
			var flag_1_nav = false;
			var indice_max = 0;

			for (var i = frames.length - 1; i >= 0; i--) {
				if (height_frame > b2make.parallax.window_height) {
					break;
				}

				indice_max = i;
				height_frame = height_frame + frames[i].height;
			}

			height_frame = 0;

			for (var i = 0; i < frames.length; i++) {
				if (indice_max < i) {
					break;
				}

				var area_desativada = false;
				for (var j = 0; j < areas_desativadas_arr.length; j++) {
					if (areas_desativadas_arr[j] == frames[i].obj.attr('id')) {
						area_desativada = true;
						break;
					}
				}

				if (area_desativada) {
					continue;
				}

				height_frame = height_frame + frames[i].height;

				b2make.parallax.nav_buttons[i] = $('<div id="_parallax-nav-' + (i + 1) + '" class="_parallax-nav-btn _parallax-nav-btn-2" data-id="' + (frames[i].obj.attr('id')) + '"></div>');

				b2make.parallax.nav_buttons[i].appendTo(b2make.parallax.nav);

				b2make.parallax.nav_buttons[i].bind('click tap', function (e) {

					parallax_animar_scroll({ obj: $(this) });
				});
			}

			if (i == 1) {
				flag_1_nav = true;
			}
		}

		var layout = $('#b2make-pagina-options').attr('data-pagina-menu-bolinhas-layout');
		var layout_vars = layout.split('|');
		var w = parseInt(layout_vars[0]);

		var nav_left = b2make.parallax.nav_left;
		var nav_top = (b2make.parallax.window_height / 2) - (2 * w * frames.length) / 2 + b2make.parallax.posicao_inicial;

		b2make.parallax.nav.css('left', nav_left);
		b2make.parallax.nav.css('top', nav_top);

		b2make.parallax.nav.appendTo('body');

		var pos = $(window).scrollTop();
		var perc = pos / (b2make.parallax.cont_height);
		var top_scroll = Math.floor(perc * (frames_height - b2make.parallax.window_height));

		for (var i = 0; i < frames.length; i++) {
			var top = frames[i].pos_top;
			var height = top + frames[i].height;

			if (top_scroll >= top - 1 && top_scroll < height - 1) {
				$('#_parallax-nav-' + (i + 1)).removeClass('_parallax-nav-btn-2');
				$('#_parallax-nav-' + (i + 1)).addClass('_parallax-nav-btn-1');
			} else {
				$('#_parallax-nav-' + (i + 1)).addClass('_parallax-nav-btn-2');
				$('#_parallax-nav-' + (i + 1)).removeClass('_parallax-nav-btn-1');
			}
		}

		if (flag_1_nav) {
			$('#_parallax-nav-1').hide();
		}

		navegador_layout();
	}

	function parallax_animar_scroll(p) {
		if (!p) p = {};

		var id = (p.obj ? p.obj.attr('data-id') : p.id);
		var frames = b2make.parallax.frames;
		var top = 0;
		var body = $("html, body");
		var scrollTop = $(window).scrollTop();
		var espaco = 0;
		var tempo = 0;
		var frames_height = b2make.parallax.frames_height;

		for (var i = 0; i < frames.length; i++) {
			if (frames[i].obj.attr('id') == id) {
				top = frames[i].pos_top;
				break;
			}
		}

		if (b2make.parallax.parallax_ativo) {
			var perc = top / (frames_height - b2make.parallax.window_height);
			var top_scroll = Math.floor(perc * (b2make.parallax.cont_height));
		} else {
			var top_scroll = top;
		}

		espaco = scrollTop - top_scroll
		if (espaco < 0) espaco = espaco * (-1);

		tempo = espaco / b2make.parallax.velocidade_menu_top;

		body.stop().animate({ scrollTop: top_scroll }, tempo, 'swing', function () {

		});
	}

	function voltar_topo_close() {
		var voltar_topo = $('#b2make-pagina-voltar-topo');

		voltar_topo.hide();
	}

	function voltar_topo_open() {
		var voltar_topo = $('#b2make-pagina-voltar-topo');

		voltar_topo.show();
	}

	function voltar_topo() {
		$('<div id="b2make-pagina-voltar-topo"></div>').appendTo('body');
		$('#b2make-pagina-voltar-topo').hide();

		$('#b2make-pagina-voltar-topo').on('mouseup tap', function (e) {
			if (e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var top = 0;
			var body = $("html, body");
			var scrollTop = $(window).scrollTop();
			var espaco = 0;
			var tempo = 0;

			espaco = top - scrollTop
			if (espaco < 0) espaco = espaco * (-1);

			tempo = espaco / b2make.parallax.velocidade_menu_top;

			body.stop().animate({ scrollTop: top }, tempo, 'swing', function () {

			});
		});
	}

	voltar_topo();

	$(window).resize(function () {
		if ($.browser.device) {
			parallax_mobile();
		} else {
			parallax_posicionar();
			navegador_posicionar();
		}

		tecnologia_posicionar();

		$('.b2make-widget').each(function () {
			$(this).css('cursor', 'default');
			if ($(this).attr('data-type') != 'conteiner-area') {
				switch ($(this).attr('data-type')) {
					case 'conteiner':
						if ($(this).attr('data-area-fixed') && $(this).attr('data-area-fixed') == 'b') {
							var abaixo = parseInt($(this).attr('data-area-fixed-baixo'));
							$(this).css('top', 'auto');
							$(this).css('zIndex', '900');
							$(this).css('bottom', abaixo + 'px');
						}
						break;
				}
			}
		});
	});

	$(window).bind('scroll', function () {
		window.requestAnimationFrame(scrollHandler);
	});

	function scrollHandler() {
		if (b2make.parallax.parallax_ativo) {
			parallax_mover();
		}

		var scrollTop = $(window).scrollTop();
		if (scrollTop > b2make.parallax.voltar_topo_start_height) {
			voltar_topo_open();
		} else {
			voltar_topo_close();
		}

		var frames = b2make.parallax.frames;
		var frames_height = b2make.parallax.frames_height;
		var pos = $(window).scrollTop();

		if (b2make.parallax.parallax_ativo) {
			var perc = pos / (b2make.parallax.cont_height);
			var top_scroll = Math.floor(perc * (frames_height - b2make.parallax.window_height));
		} else {
			var top_scroll = pos;
		}

		for (var i = 0; i < frames.length; i++) {
			var top = frames[i].pos_top;
			var height = top + frames[i].height;

			if (top_scroll >= top - 1 && top_scroll < height - 1) {
				$('#_parallax-nav-' + (i + 1)).removeClass('_parallax-nav-btn-2');
				$('#_parallax-nav-' + (i + 1)).addClass('_parallax-nav-btn-1');
			} else {
				$('#_parallax-nav-' + (i + 1)).addClass('_parallax-nav-btn-2');
				$('#_parallax-nav-' + (i + 1)).removeClass('_parallax-nav-btn-1');
			}
		}

		if (b2make.menu_holder) {
			if (b2make.menu_holder.attr('data-open') == '1') {
				if (b2make.menu_holder_position == 'absolute') {
					b2make.menu_holder.attr('data-open', '0');
					b2make.menu_holder.hide();
				}
			}
		}

		navegador_layout();
	}

	function jpicker_ahex_2_rgba(ahex) {
		var rgba = $.jPicker.ColorMethods.hexToRgba(ahex);

		return 'rgba(' + rgba.r + ',' + rgba.g + ',' + rgba.b + ',' + (rgba.a / 255).toFixed(1) + ')';
	}

	$(window).bind('mousewheel', function (e) {
		$("html, body").stop();
	});

	function tecnologia_posicionar() {
		if (!b2make.technology) b2make.technology = $('<div id="b2make-technology-cont">' + (b2make.device == 'phone' || localStorage.getItem('b2make.versao_desktop') ? (b2make.device == 'phone' ? '<div id="b2make-mobile-link-desktop" class="b2make-mobile-link">' + b2make.msgs.mobileLinkDesktop + '</div>' : '<div id="b2make-mobile-link-mobile" class="b2make-mobile-link">' + b2make.msgs.mobileLinkMobile + '</div>') : '') + '<a href="https://b2make.com" target="_blank" id="b2make-technology-link"></a></div>');

		if (b2make.parallax.parallax_ativo) {
			var top = parseInt($('#b2make-parallax').height()) - parseInt(b2make.technology.height());

			b2make.technology.css('position', 'absolute');
			b2make.technology.css('top', top + 'px');

			b2make.technology.appendTo($('#b2make-parallax'));
		} else {
			var top = b2make.parallax.cont_height;

			b2make.technology.css('position', 'absolute');
			b2make.technology.css('top', top + 'px');
			b2make.technology.appendTo('body');
		}
	}

	function hash_animation_sem_parallax(id) {
		var tempo;
		var top_scroll = $('#' + id).offset().top;
		var scrollTop = $(window).scrollTop();
		var espaco;
		var body = $("html, body");

		espaco = scrollTop - top_scroll
		if (espaco < 0) espaco = espaco * (-1);

		tempo = espaco / b2make.parallax.velocidade_menu_top;

		body.stop().animate({ scrollTop: top_scroll }, tempo, 'swing', function () {

		});
	}

	function hash_animation() {
		var hash = location.hash.replace(/^#/, '');

		if (!hash) {
			return;
		}

		if ($('.b2make-widget[data-name="' + hash + '"]').length > 0) {
			var obj = $('.b2make-widget[data-name="' + hash + '"]');
			var type = obj.attr('data-type');

			if (b2make.parallax.parallax_ativo) {
				if (type == 'conteiner') {
					parallax_animar_scroll({ id: obj.attr('id') });
				} else {
					var pai = obj.parent();

					if (pai.attr('data-type') == 'conteiner-area') {
						pai = pai.parent();
					}

					parallax_animar_scroll({ id: pai.attr('id') });
				}
			} else {
				hash_animation_sem_parallax(obj.attr('id'));
			}
		}
	}

	function hash_navagation() {
		$(window).on('hashchange', function () {

			hash_animation();

			return false;
		});

		hash_animation();
	}

	hash_navagation();

	function operacoes_finais() {
		tecnologia_posicionar();

		$('.b2make-wsoae-prev,.b2make-wsoae-next').on('mouseover', function () {
			var pai = $(this).parent().parent();

			var color_caixa_hover = pai.attr('data-seta-cor-2-ahex');
			var color_seta_hover = pai.attr('data-caixa-cor-ahex');

			if (!color_caixa_hover) color_caixa_hover = '#333333'; else color_caixa_hover = jpicker_ahex_2_rgba(color_caixa_hover);
			if (!color_seta_hover) color_seta_hover = '#ECEDEF'; else color_seta_hover = jpicker_ahex_2_rgba(color_seta_hover);

			$(this).css('background-color', color_caixa_hover);
			$(this).find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color', color_seta_hover);
		});

		$('.b2make-wsoae-prev,.b2make-wsoae-next').on('mouseout', function () {
			var pai = $(this).parent().parent();

			var color_caixa_hover = pai.attr('data-caixa-cor-ahex');
			var color_seta_hover = pai.attr('data-seta-cor-1-ahex');

			if (!color_caixa_hover) color_caixa_hover = '#ECEDEF'; else color_caixa_hover = jpicker_ahex_2_rgba(color_caixa_hover);
			if (!color_seta_hover) color_seta_hover = '#333333'; else color_seta_hover = jpicker_ahex_2_rgba(color_seta_hover);

			$(this).css('background-color', color_caixa_hover);
			$(this).find('.b2make-wsoae-table').find('.b2make-wsoae-cel').css('color', color_seta_hover);
		});

		$('.b2make-player-prev,.b2make-player-play,.b2make-player-next').on('mouseover', function () {
			var album_musicas = $(this).parent().hasClass('b2make-albummusicas-widget-controls');

			if (album_musicas) {
				var pai = $(this).parent().parent().parent().parent().parent();
				var color = '#DBDBDB';
			} else {
				var pai = $(this).parent().parent().parent().parent();
				var color = '#726B6D';
			}

			if (pai.attr('data-botoes-color-2-ahex')) {
				var bg = jpicker_ahex_2_rgba(pai.attr('data-botoes-color-2-ahex'));

				$(this).find('svg').find('polygon').css('fill', bg);
				$(this).find('svg').find('rect').css('fill', bg);
				$(this).find('svg').find('path').css('fill', bg);
			} else {
				$(this).find('svg').find('polygon').css('fill', color);
				$(this).find('svg').find('rect').css('fill', color);
				$(this).find('svg').find('path').css('fill', color);
			}
		});

		$('.b2make-player-prev,.b2make-player-play,.b2make-player-next').on('mouseout', function () {
			var album_musicas = $(this).parent().hasClass('b2make-albummusicas-widget-controls');

			if (album_musicas) {
				var pai = $(this).parent().parent().parent().parent().parent();
				var color = '#FFFFFF';
			} else {
				var pai = $(this).parent().parent().parent().parent();
				var color = '#413E3F';
			}

			if (pai.attr('data-botoes-color-1-ahex')) {
				var bg = jpicker_ahex_2_rgba(pai.attr('data-botoes-color-1-ahex'));

				$(this).find('svg').find('polygon').css('fill', bg);
				$(this).find('svg').find('rect').css('fill', bg);
				$(this).find('svg').find('path').css('fill', bg);
			} else {
				$(this).find('svg').find('polygon').css('fill', color);
				$(this).find('svg').find('rect').css('fill', color);
				$(this).find('svg').find('path').css('fill', color);
			}
		});

		$('.b2make-widget').each(function () {
			$(this).css('cursor', 'default');
			if ($(this).attr('data-type') != 'conteiner-area') {
				switch ($(this).attr('data-type')) {
					case 'conteiner':
						if ($(this).attr('data-area-fixed') && $(this).attr('data-area-fixed') == 'b') {
							var abaixo = parseInt($(this).attr('data-area-fixed-baixo'));
							$(this).css('top', 'auto');
							$(this).css('zIndex', '900');
							$(this).css('bottom', abaixo + 'px');
						}
						break;
				}
			}
		});
	}

	operacoes_finais();
});