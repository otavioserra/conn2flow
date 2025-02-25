var b2make = {};

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

window.addEventListener('message', function(event) { 
	if(~event.origin.indexOf('https://beta.b2make.com') || ~event.origin.indexOf('https://b2make.com')) { 
		var data = event.data;

		switch(data.opcao){
			case 'redirect':
				b2make_redirect(data);
			break;
			case 'google_analytics':
				b2make_ga(data);
			break;
		}
	} else { 
		return; 
	} 
});

function b2make_redirect(p = {}){
	window.open(p.url,'_self');
}

function b2make_ga(p = {}){
	if(typeof gtag != 'function'){
	   return false;
	}
	
	var evento = p.evento;
	var itens = p.itens;
	var checkout_options = p.checkout_options;
	var purchase_options = p.purchase_options;
	var value = p.value;
	
	if(purchase_options){
		gtag('event', evento, purchase_options);
	} else if(checkout_options){
		gtag('event', evento, checkout_options);
	} else if(itens){
		gtag('event', evento, {
			"value": value,
			"currency": 'BRL',
			"items": itens
		});
	} else {
		var item_id = p.item_id;
		var item_dados = p.item_dados;
		var item_quant = p.item_quant;
		
		gtag('event', evento, {
			"value": item_dados.preco,
			"currency": 'BRL',
			"items": [
				{
				"id": item_id,
				"name": item_dados.nome,
				"price": item_dados.preco,
				"quantity": item_quant,
				}
			]
		});
	}
}

function sha1(str) {
  //  discuss at: https://locutus.io/php/sha1/
  // original by: Webtoolkit.info (https://www.webtoolkit.info/)
  // improved by: Michael White (https://getsprink.com)
  // improved by: Kevin van Zonneveld (https://kvz.io)
  //    input by: Brett Zamir (https://brett-zamir.me)
  //      note 1: Keep in mind that in accordance with PHP, the whole string is buffered and then
  //      note 1: hashed. If available, we'd recommend using Node's native crypto modules directly
  //      note 1: in a steaming fashion for faster and more efficient hashing
  //   example 1: sha1('Kevin van Zonneveld')
  //   returns 1: '54916d2e62f65b3afa6e192e6a601cdbe5cb5897'

  var hash
  try {
    var crypto = require('crypto')
    var sha1sum = crypto.createHash('sha1')
    sha1sum.update(str)
    hash = sha1sum.digest('hex')
  } catch (e) {
    hash = undefined
  }

  if (hash !== undefined) {
    return hash
  }

  var _rotLeft = function (n, s) {
    var t4 = (n << s) | (n >>> (32 - s))
    return t4
  }

  var _cvtHex = function (val) {
    var str = ''
    var i
    var v

    for (i = 7; i >= 0; i--) {
      v = (val >>> (i * 4)) & 0x0f
      str += v.toString(16)
    }
    return str
  }

  var blockstart
  var i, j
  var W = new Array(80)
  var H0 = 0x67452301
  var H1 = 0xEFCDAB89
  var H2 = 0x98BADCFE
  var H3 = 0x10325476
  var H4 = 0xC3D2E1F0
  var A, B, C, D, E
  var temp

  // utf8_encode
  str = unescape(encodeURIComponent(str))
  var strLen = str.length

  var wordArray = []
  for (i = 0; i < strLen - 3; i += 4) {
    j = str.charCodeAt(i) << 24 |
      str.charCodeAt(i + 1) << 16 |
      str.charCodeAt(i + 2) << 8 |
      str.charCodeAt(i + 3)
    wordArray.push(j)
  }

  switch (strLen % 4) {
    case 0:
      i = 0x080000000
      break
    case 1:
      i = str.charCodeAt(strLen - 1) << 24 | 0x0800000
      break
    case 2:
      i = str.charCodeAt(strLen - 2) << 24 | str.charCodeAt(strLen - 1) << 16 | 0x08000
      break
    case 3:
      i = str.charCodeAt(strLen - 3) << 24 |
        str.charCodeAt(strLen - 2) << 16 |
        str.charCodeAt(strLen - 1) <<
      8 | 0x80
      break
  }

  wordArray.push(i)

  while ((wordArray.length % 16) !== 14) {
    wordArray.push(0)
  }

  wordArray.push(strLen >>> 29)
  wordArray.push((strLen << 3) & 0x0ffffffff)

  for (blockstart = 0; blockstart < wordArray.length; blockstart += 16) {
    for (i = 0; i < 16; i++) {
      W[i] = wordArray[blockstart + i]
    }
    for (i = 16; i <= 79; i++) {
      W[i] = _rotLeft(W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16], 1)
    }

    A = H0
    B = H1
    C = H2
    D = H3
    E = H4

    for (i = 0; i <= 19; i++) {
      temp = (_rotLeft(A, 5) + ((B & C) | (~B & D)) + E + W[i] + 0x5A827999) & 0x0ffffffff
      E = D
      D = C
      C = _rotLeft(B, 30)
      B = A
      A = temp
    }

    for (i = 20; i <= 39; i++) {
      temp = (_rotLeft(A, 5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff
      E = D
      D = C
      C = _rotLeft(B, 30)
      B = A
      A = temp
    }

    for (i = 40; i <= 59; i++) {
      temp = (_rotLeft(A, 5) + ((B & C) | (B & D) | (C & D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff
      E = D
      D = C
      C = _rotLeft(B, 30)
      B = A
      A = temp
    }

    for (i = 60; i <= 79; i++) {
      temp = (_rotLeft(A, 5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff
      E = D
      D = C
      C = _rotLeft(B, 30)
      B = A
      A = temp
    }

    H0 = (H0 + A) & 0x0ffffffff
    H1 = (H1 + B) & 0x0ffffffff
    H2 = (H2 + C) & 0x0ffffffff
    H3 = (H3 + D) & 0x0ffffffff
    H4 = (H4 + E) & 0x0ffffffff
  }

  temp = _cvtHex(H0) + _cvtHex(H1) + _cvtHex(H2) + _cvtHex(H3) + _cvtHex(H4)
  return temp.toLowerCase()
}

function setLocalStorage(chave, valor, minutos){
    var expirarem = (new Date().getTime()) + (60000 * minutos);

    localStorage.setItem(chave, JSON.stringify({
        "value": valor,
        "expires": expirarem
    }));
}

function getLocalStorage(chave){
    localStorageExpires();//Limpa itens

    var itemValue = localStorage[chave];

    if (itemValue && /^\{(.*?)\}$/.test(itemValue)) {

        //Decodifica de volta para JSON
        var current = JSON.parse(itemValue);

        return current.value;
    }

    return false;
}

function localStorageExpires(){
    var
        toRemove = [],                      //Itens para serem removidos
        currentDate = new Date().getTime(); //Data atual em milissegundos

    for (var i = 0, j = localStorage.length; i < j; i++) {
       var key = localStorage.key(i);
       var current = localStorage.getItem(key);

       //Verifica se o formato do item para evitar conflitar com outras aplicações
       if (current && /^\{(.*?)\}$/.test(current)) {

            //Decodifica de volta para JSON
            current = JSON.parse(current);

            //Checa a chave expires do item especifico se for mais antigo que a data atual ele salva no array
            if (current.expires && current.expires <= currentDate) {
                toRemove.push(key);
            }
       }
    }

    // Remove itens que já passaram do tempo
    // Se remover no primeiro loop isto poderia afetar a ordem,
    // pois quando se remove um item geralmente o objeto ou array são reordenados
    for (var i = toRemove.length - 1; i >= 0; i--) {
        localStorage.removeItem(toRemove[i]);
    }

};

function guidGenerator() {
    var S4 = function() {
       return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
    };
    return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
}

function start_dont_ready(){
	localStorageExpires();//Auto executa a limpeza

	var uid = getLocalStorage('b2make-uid');
	
	if(!uid){
		uid = guidGenerator();
		setLocalStorage('b2make-uid', uid, 180);
	}
	
	b2make.session = encodeURIComponent(sha1(uid));
}

start_dont_ready();

$(document).ready(function(){
	function b2make_opcoes(){
		var opcao = getUrlParameter('opcao');
		var src = $('div[name="b2make-config"]').attr('data-src');
		var url_full = (typeof $('div[name="b2make-config"]').attr('data-url-full') !== typeof undefined && $('div[name="b2make-config"]').attr('data-url-full') !== false ? true : false);
		
		src = src + (url_full ? '?':'&') + '_iframe_session='+b2make.session;
		
		switch(opcao){
			case 'cart':
				var operacao = getUrlParameter('operacao');
				var id = getUrlParameter('id');
				
				src = src + '&operacao='+operacao+'&id='+id;
			break;
			case 'newpass':
				var cod = getUrlParameter('cod');
				var key = getUrlParameter('key');
				
				src = src + '&cod='+cod+'&key='+key;
			break;
		}
		
		var iframe = '<iframe name="b2make" src="'+src+'">Seu navegador não tem suporte para iframes e por isso não será possível vizualizar esta página.</iframe>';
		$(iframe).appendTo('body');
	}

	b2make_opcoes();
});