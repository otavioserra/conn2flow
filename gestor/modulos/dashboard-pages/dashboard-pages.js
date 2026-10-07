/**
 * Páginas de Lousa (REQ-253): pequenos confortos do formulário.
 *  - o endereço acompanha o nome enquanto o usuário não mexer nele (só na inclusão);
 *  - o endereço é posto no formato aceito ao sair do campo;
 *  - o texto do título some quando o título da página está desligado.
 * O servidor confere tudo de novo ao gravar.
 */
(function () {
	function slug(value) {
		return String(value == null ? '' : value).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
			.split('/').map(function (part) { return part.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); })
			.filter(function (part) { return part !== ''; }).join('/');
	}
	function path(value) { var s = slug(value); return s ? s + '/' : ''; }

	function start() {
		var name = document.querySelector('input[name="nome"]');
		var address = document.querySelector('[data-dashboard-pages-caminho]');
		if (!name || !address) return;

		// Na edição o endereço já existe e não segue o nome.
		var follow = address.value === '';
		name.addEventListener('input', function () { if (follow) address.value = path(name.value); });
		address.addEventListener('input', function () { follow = false; });
		address.addEventListener('blur', function () { address.value = path(address.value); });

		var toggle = document.querySelector('[data-dashboard-pages-titulo]');
		var text = document.querySelector('[data-dashboard-pages-titulo-texto]');
		function sync() { if (toggle && text) text.hidden = !toggle.checked; }
		if (toggle) toggle.addEventListener('change', sync);
		sync();
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
