$(document).ready(function(){
	// ===== localStorage < =====
	
	function localStorageExpires(){
		/**
		* Função para limpar itens no localStorage
		*/

		var toRemove = [],                      //Itens para serem removidos
			currentDate = new Date().getTime(); //Data atual em milissegundos

		for (var i = 0, j = localStorage.length; i < j; i++) {
		   var key = localStorage.key(i),
			   itemValue = localStorage.getItem(key);

		   //Verifica se o formato do item para evitar conflitar com outras aplicações
		   if (itemValue && /^\{(.*?)\}$/.test(itemValue)) {

				//Decodifica de volta para JSON
				var current = JSON.parse(itemValue);

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
	}

	function setLocalStorage(chave, valor, minutos){
		/**
		* Função para adicionar itens no localStorage
		* @param {string} chave Chave que será usada para obter o valor posteriormente
		* @param {*} valor Quase qualquer tipo de valor pode ser adicionado, desde que não falhe no JSON.stringify
		* @param {number} minutos Tempo de vida do item
		*/

		var expirarem = new Date().getTime() + (60000 * minutos);

		localStorage.setItem(chave, JSON.stringify({
			"value": valor,
			"expires": expirarem
		}));
	}

	function getLocalStorage(chave){
		/**
		* Função para obter itens do localStorage que ainda não expiraram
		* @param {string} chave Chave para obter o valor associado
		* @return {*} Retorna qualquer valor, se o item tiver expirado irá retorna undefined
		*/

		localStorageExpires(); //Limpa itens

		var itemValue = localStorage.getItem(chave);

		if (itemValue && /^\{(.*?)\}$/.test(itemValue)) {

			//Decodifica de volta para JSON
			var current = JSON.parse(itemValue);

			return current.value;
		}
	}
	
	// ===== localStorage > =====
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		
	}
	
	if(typeof gestor.toasts !== typeof undefined && gestor.toasts !== false){
		var toasts = gestor.toasts;
		var toasts_options = gestor.toasts_options;
		var opcoes_padroes = toasts_options.opcoes_padroes;
		var transition = 0;
		
		for(toast in toasts){
			// ===== Verifica se há regra específica, caso haja disparar regra.
			
			var regra = false;
			
			if(typeof toasts[toast].regra !== typeof undefined && toasts[toast].regra !== false){
				regra = toasts[toast].regra;
			}
			
			var toastObj = {};
			var toastObjAux = {};
			
			// ===== Alterar opções padrões.
			
			if(typeof opcoes_padroes !== typeof undefined && opcoes_padroes !== false){
				for(opcaoPadrao in opcoes_padroes){
					toastObj[opcaoPadrao] = opcoes_padroes[opcaoPadrao];
				}
			}
			
			// ===== Popular objeto do toast com todas as opções definidas no servidor.
			
			if(typeof toasts[toast].opcoes !== typeof undefined && toasts[toast].opcoes !== false){
				for(opcao in toasts[toast].opcoes){
					toastObj[opcao] = toasts[toast].opcoes[opcao];
				}
			}
			
			// ===== Popular objeto do toastObjAux com todos os botões definidos no servidor.
			
			if(typeof toasts[toast].botoes !== typeof undefined && toasts[toast].botoes !== false){
				for(botao in toasts[toast].botoes){
					toastObjAux[botao] = toasts[toast].botoes[botao];
				}
			}
			
			// ===== Mostrar este toast.
			
			var showToast = true;
			
			switch(regra){
				case 'update':
					var updateNotShowToast = getLocalStorage('updateNotShowToast');
					
					if(typeof updateNotShowToast !== typeof undefined && updateNotShowToast !== false){
						showToast = false;
					}
				break;
			}
			
			if(showToast){
				toast_show(toastObj,toastObjAux,regra);
			}
			
			// ===== Próximo toast que seja disparado com um período entre eles definido por "troca_time" em milisegundos.
			
			transition = transition + parseInt(toasts_options.troca_time);
		}
		
		// ===== Toast Click Functions
		
		var botaoObjClick = {};
		
		function toastClickUpdatePositivo(){
			var timeLimit = 2000;
			
			if(typeof botaoObjClick['update-positivo']['displayTime'] !== typeof undefined && botaoObjClick['update-positivo']['displayTime'] !== false){
				timeLimit = botaoObjClick['update-positivo']['displayTime'];
			}
			
			$('body').toast(botaoObjClick['update-positivo']);
			setTimeout(function(){
				window.open(gestor.raiz+'host-update/','_self');
			},timeLimit);
		}
		
		function toastClickUpdateNegativo(){
			setLocalStorage('updateNotShowToast', 'yes', parseInt(toasts_options.updateNotShowToastTime));
			$('body').toast(botaoObjClick['update-negativo']);
		}
		
		// ===== Toast Show
		
		function toast_show(obj = {},objExtra = {},rule = false){
			setTimeout(function(){
				if(Object.keys(objExtra).length !== 0){
					var toastActionObj = [];
					
					for(id in objExtra){
						var botaoObj = {};
						
						if(typeof objExtra[id] !== typeof undefined && objExtra[id] !== false){
							for(botao in objExtra[id]){
								if(botao == 'click'){
									botaoObjClick[id] = objExtra[id]['click'];
									
									switch(id){
										case 'update-positivo':
											botaoObj[botao] = toastClickUpdatePositivo;
										break;
										case 'update-negativo':
											botaoObj[botao] = toastClickUpdateNegativo;
										break;
									}
									
									if(typeof objExtra[id]['click']['displayTime'] !== typeof undefined && objExtra[id]['click']['displayTime'] !== false){
										objExtra[id]['click']['displayTime'] = parseInt(objExtra[id]['click']['displayTime']);
									}
									
									botaoObjClick[id] = objExtra[id]['click'];
								} else {
									botaoObj[botao] = objExtra[id][botao];
								}
							}
						}
						
						toastActionObj.push(botaoObj);
					}
					
					toastObj['actions'] = toastActionObj;
				}
				
				$('body')
					.toast(toastObj);
			},transition);
		}
		
		
	}
	
	
});