/**
 * Páginas de Lousa (REQ-253 e REQ-254): confortos do formulário e a ligação do modelo com o editor HTML.
 *  - o endereço acompanha o nome enquanto o usuário não mexer nele (só com o endereço vazio);
 *  - o endereço é posto no formato aceito ao sair do campo;
 *  - o texto do título some quando o título da página está desligado;
 *  - escolher um modelo carrega o HTML e o CSS dele no editor (como no publisher-index).
 * O servidor confere tudo de novo ao gravar.
 */
(function () {
	function slug(value) {
		return String(value == null ? '' : value).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
			.split('/').map(function (part) { return part.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); })
			.filter(function (part) { return part !== ''; }).join('/');
	}
	function path(value) { var s = slug(value); return s ? s + '/' : ''; }

	// Modelo escolhido: o editor recebe o HTML e o CSS dele. O framework vai junto para a prévia do editor.
	function loadTemplate(id) {
		if (!id || typeof fetch !== 'function' || typeof gestor === 'undefined') return Promise.resolve(false);
		var params = new URLSearchParams({opcao: gestor.moduloOpcao || '', ajax: 'sim', ajaxOpcao: 'template-load', template_id: id});
		return fetch(gestor.raiz + 'dashboard-pages/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params})
			.then(function (response) { return response.json(); })
			.then(function (data) {
				if (!data || data.status !== 'Ok') return false;
				if (gestor.html_editor) gestor.html_editor.framework_css = data.framework_css || null;
				if (typeof window.html_editor_set_html === 'function') window.html_editor_set_html(data.html || '');
				if (typeof window.html_editor_set_css === 'function') window.html_editor_set_css(data.css || '');
				if (typeof window.html_editor_refresh_preview === 'function') setTimeout(function () { window.html_editor_refresh_preview(); }, 300);
				return true;
			})
			.catch(function () { return false; });
	}

	function start() {
		var name = document.querySelector('input[name="nome"]');
		var address = document.querySelector('[data-dashboard-pages-caminho]');
		if (!name || !address) return;

		// Com endereço já preenchido (edição), ele não segue o nome.
		var follow = address.value === '';
		name.addEventListener('input', function () { if (follow) address.value = path(name.value); });
		address.addEventListener('input', function () { follow = false; });
		address.addEventListener('blur', function () { address.value = path(address.value); });

		var toggle = document.querySelector('[data-dashboard-pages-titulo]');
		var text = document.querySelector('[data-dashboard-pages-titulo-texto]');
		function sync() { if (toggle && text) text.hidden = !toggle.checked; }
		if (toggle) toggle.addEventListener('change', sync);
		sync();

		// Só a troca feita pelo usuário carrega o modelo: ao abrir a edição, vale o HTML guardado.
		var template = document.querySelector('[data-dashboard-pages-modelo]');
		// Salvar logo depois de trocar o modelo espera o HTML dele chegar ao editor; sem isso iria o HTML anterior.
		var pending = 0, resubmit = false, form = name.form;
		if (template) template.addEventListener('change', function () {
			pending++;
			loadTemplate(template.value).then(function () {
				pending--;
				if (pending === 0 && resubmit && form) { resubmit = false; form.requestSubmit(); }
			});
		});
		if (form) form.addEventListener('submit', function (event) {
			if (pending === 0) return;
			event.preventDefault(); event.stopImmediatePropagation();
			resubmit = true;
		}, true);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
