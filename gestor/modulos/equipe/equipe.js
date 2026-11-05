document.addEventListener('DOMContentLoaded', function () {
	var raiz = document.getElementById('equipe-painel');
	if (!raiz) return;

	var caixaMensagem = document.getElementById('equipe-msg');
	var formPerfil = document.getElementById('equipe-perfil-form');
	var formMembro = document.getElementById('equipe-membro-form');

	function mensagem(tipo, texto) {
		caixaMensagem.textContent = texto;
		caixaMensagem.className = 'rounded-lg border px-4 py-3 text-sm ' + (tipo === 'erro'
			? 'border-red-200 bg-red-50 text-red-800'
			: 'border-emerald-200 bg-emerald-50 text-emerald-800');
		caixaMensagem.scrollIntoView({ block: 'nearest' });
	}

	function confirmar(texto) {
		if (window.c2fControles) return window.c2fControles.dialogo.confirmar(texto, { perigo: true });
		return Promise.resolve(window.confirm(texto));
	}

	// O token CSRF entra sozinho pelo envelope de `window.fetch` do global.js.
	function enviar(dados, listas) {
		var corpo = new URLSearchParams();
		corpo.set('ajax', 'sim');
		Object.keys(dados).forEach(function (chave) { corpo.set(chave, dados[chave]); });
		Object.keys(listas || {}).forEach(function (chave) {
			listas[chave].forEach(function (valor) { corpo.append(chave + '[]', valor); });
		});

		return window.fetch(window.location.href, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: corpo.toString(),
			credentials: 'same-origin'
		}).then(function (resposta) {
			if (resposta.status === 401) {
				window.location.href = ((window.gestor && gestor.raiz) ? gestor.raiz : '/') + 'signin/';
				return null;
			}
			return resposta.json();
		}).then(function (json) {
			if (!json) return null;
			if (json.status !== 'success') {
				mensagem('erro', json.message || raiz.dataset.erro);
				return null;
			}
			return json;
		}).catch(function () {
			mensagem('erro', raiz.dataset.erro);
			return null;
		});
	}

	// Assentos e listas saem do servidor: recarregar na mesma aba mantém tudo em dia.
	function recarregar(aba) {
		window.location.hash = aba;
		window.location.reload();
	}

	function ativarAba(nome) {
		Array.prototype.forEach.call(raiz.querySelectorAll('[data-equipe-aba]'), function (aba) {
			var ativa = aba.getAttribute('data-equipe-aba') === nome;
			aba.classList.toggle('active', ativa);
			aba.setAttribute('aria-selected', ativa ? 'true' : 'false');
		});
		Array.prototype.forEach.call(raiz.querySelectorAll('[data-equipe-painel]'), function (painel) {
			painel.classList.toggle('hidden', painel.getAttribute('data-equipe-painel') !== nome);
		});
	}

	function abrirMembro(acao, botao) {
		formMembro.dataset.acao = acao;
		formMembro.dataset.id = botao ? botao.dataset.id : '0';
		formMembro.querySelector('h2').textContent = acao === 'editar' ? raiz.dataset.editar : (acao === 'senha' ? raiz.dataset.senha : raiz.dataset.adicionar);
		document.getElementById('equipe-confirmar-rotulo').textContent = acao === 'criar' ? raiz.dataset.confirmar : raiz.dataset.salvar;
		formMembro.querySelector('label[for="equipe-senha"]').textContent = acao === 'senha' ? raiz.dataset.novaSenha : raiz.dataset.senhaInicial;
		document.getElementById('equipe-nome').value = botao ? botao.dataset.nome || '' : '';
		document.getElementById('equipe-email').value = botao ? botao.dataset.email || '' : '';
		document.getElementById('equipe-senha').value = '';
		var perfil = document.getElementById('equipe-perfil');
		perfil.value = botao ? botao.dataset.perfil || '' : (perfil.options[0] ? perfil.options[0].value : '');
		perfil.dispatchEvent(new Event('change', { bubbles: true }));
		['equipe-nome', 'equipe-email', 'equipe-perfil', 'equipe-senha'].forEach(function (id) {
			var campo = document.getElementById(id);
			campo.closest('.c2fc-campo').classList.toggle('hidden!', id === 'equipe-senha' ? acao === 'editar' : acao === 'senha');
		});
		formMembro.classList.remove('hidden');
		document.getElementById(acao === 'senha' ? 'equipe-senha' : 'equipe-nome').focus();
	}

	Array.prototype.forEach.call(raiz.querySelectorAll('[data-equipe-aba]'), function (aba) {
		aba.addEventListener('click', function () { ativarAba(aba.getAttribute('data-equipe-aba')); });
	});

	ativarAba(window.location.hash === '#perfis' ? 'perfis' : 'membros');

	function marcados(seletor) {
		return Array.prototype.slice.call(formPerfil.querySelectorAll(seletor + ':checked')).map(function (campo) { return campo.value; });
	}

	function abrirFormPerfil(id, nome, modulos, operacoes) {
		document.getElementById('equipe-perfil-id').value = id;
		document.getElementById('equipe-perfil-nome').value = nome;
		Array.prototype.forEach.call(formPerfil.querySelectorAll('.equipe-opcao-modulo'), function (campo) {
			campo.checked = modulos.indexOf(campo.value) !== -1;
		});
		Array.prototype.forEach.call(formPerfil.querySelectorAll('.equipe-opcao-operacao'), function (campo) {
			campo.checked = operacoes.indexOf(campo.value) !== -1;
		});
		sincronizarOperacoes();
		formPerfil.classList.remove('hidden');
		document.getElementById('equipe-perfil-nome').focus();
	}

	// REQ-298: operação só vale com o módulo ligado; desligar o módulo limpa as operações dele.
	function sincronizarOperacoes() {
		Array.prototype.forEach.call(formPerfil.querySelectorAll('.equipe-opcao-modulo'), function (modulo) {
			var cartao = modulo.closest('.equipe-modulo-cartao');
			if (!cartao) return;
			cartao.classList.toggle('border-(--c2fc-acao-realce)', modulo.checked);
			cartao.classList.toggle('bg-(--c2fc-acao-claro)', modulo.checked);
			Array.prototype.forEach.call(cartao.querySelectorAll('.equipe-opcao-operacao'), function (operacao) {
				if (!modulo.checked) operacao.checked = false;
				operacao.disabled = !modulo.checked;
			});
		});
	}

	formPerfil.addEventListener('change', function (evento) {
		if (evento.target.classList.contains('equipe-opcao-modulo')) sincronizarOperacoes();
	});

	raiz.addEventListener('click', async function (evento) {
		var botao = evento.target.closest('button');
		if (!botao) return;

		if (botao.classList.contains('equipe-marcar-grupo')) {
			var marcar = botao.getAttribute('data-marcar') === '1';
			Array.prototype.forEach.call(botao.closest('.equipe-grupo').querySelectorAll('.equipe-opcao-modulo, .equipe-opcao-operacao'), function (campo) {
				campo.checked = marcar;
			});
			sincronizarOperacoes();
		} else if (botao.id === 'btn-equipe-novo') {
			abrirMembro('criar');
		} else if (botao.classList.contains('btn-equipe-editar') || botao.classList.contains('btn-equipe-senha')) {
			abrirMembro(botao.classList.contains('btn-equipe-editar') ? 'editar' : 'senha', botao);
		} else if (botao.classList.contains('btn-equipe-excluir')) {
			if (!(await confirmar(raiz.dataset.confirmarExcluirMembro))) return;
			botao.disabled = true;
			var removido = await enviar({ ajaxOpcao: 'membro-excluir', id: botao.dataset.id });
			if (removido) recarregar('membros');
			else botao.disabled = false;
		} else if (botao.id === 'btn-equipe-cancelar') {
			document.getElementById('equipe-senha').value = '';
			formMembro.classList.add('hidden');
		} else if (botao.id === 'btn-equipe-criar') {
			botao.disabled = true;
			var criado = await enviar({
				ajaxOpcao: 'membro-' + (formMembro.dataset.acao || 'criar'),
				id: formMembro.dataset.id || '0',
				nome: document.getElementById('equipe-nome').value,
				email: document.getElementById('equipe-email').value,
				senha: document.getElementById('equipe-senha').value,
				id_perfil: document.getElementById('equipe-perfil').value
			});
			document.getElementById('equipe-senha').value = '';
			if (criado) recarregar('membros');
			else botao.disabled = false;
		} else if (botao.classList.contains('btn-equipe-status')) {
			var status = botao.getAttribute('data-status');
			if (status === 'I' && !(await confirmar(raiz.dataset.confirmarDesativar))) return;
			var alterado = await enviar({ ajaxOpcao: 'membro-status', id: botao.getAttribute('data-id'), status: status });
			if (alterado) recarregar('membros');
		} else if (botao.id === 'btn-perfil-novo') {
			abrirFormPerfil('0', '', [], []);
		} else if (botao.id === 'btn-perfil-cancelar') {
			formPerfil.classList.add('hidden');
		} else if (botao.id === 'btn-perfil-salvar') {
			var salvo = await enviar({
				ajaxOpcao: 'perfil-salvar',
				id_perfil: document.getElementById('equipe-perfil-id').value,
				nome: document.getElementById('equipe-perfil-nome').value
			}, { modulos: marcados('.equipe-opcao-modulo'), operacoes: marcados('.equipe-opcao-operacao') });
			if (salvo) recarregar('perfis');
		} else if (botao.classList.contains('btn-perfil-editar')) {
			abrirFormPerfil(botao.dataset.id, botao.dataset.nome, JSON.parse(botao.dataset.modulos), JSON.parse(botao.dataset.operacoes));
		} else if (botao.classList.contains('btn-perfil-excluir')) {
			if (!(await confirmar(raiz.dataset.confirmarExcluir))) return;
			var excluido = await enviar({ ajaxOpcao: 'perfil-excluir', id_perfil: botao.dataset.id });
			if (excluido) recarregar('perfis');
		}
	});

	raiz.addEventListener('change', async function (evento) {
		if (!evento.target.classList.contains('equipe-membro-perfil') || !evento.target.value) return;

		var json = await enviar({ ajaxOpcao: 'membro-perfil', id: evento.target.getAttribute('data-id'), id_perfil: evento.target.value });
		if (json) mensagem('sucesso', json.message || '');
	});
});
