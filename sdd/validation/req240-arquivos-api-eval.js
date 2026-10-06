// REQ-240 / REQ-106 — exercita a API do módulo-biblioteca `arquivos-api` com um token temporário.
// Usado por req240-probe.cjs --eval, na página perfil-usuario/. O token é gerado, usado e revogado
// aqui dentro e nunca sai na resposta. Cria e apaga só o arquivo `req240-api-*` que ele mesmo envia.
(async () => {
  const r = {};
  const painel = async dados => {
    const corpo = new URLSearchParams();
    corpo.set('ajax', 'sim');
    for (const [k, v] of Object.entries(dados)) {
      if (Array.isArray(v)) v.forEach(x => corpo.append(k + '[]', x)); else corpo.set(k, v);
    }
    const resp = await fetch(location.href, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: corpo.toString(), credentials: 'same-origin'});
    return resp.json().catch(() => ({}));
  };

  const escopos = [...document.querySelectorAll('.api-token-escopo')].map(c => c.value);
  const gerado = await painel({ajaxOpcao: 'api-token-gerar', nome: 'req240-arquivos-api', expiracao: '0', escopos});
  const token = gerado.token || gerado.access_token || (gerado.data || {}).token || '';
  r.tokenGerado = !!token;
  r.chavesDaResposta = Object.keys(gerado).filter(k => !/token/i.test(k));
  if (!token) return r;

  const base = (gestor.raiz || '/') + 'pt-br/_api/arquivos-api/';
  const api = async (acao, opcoes = {}, comToken = true) => {
    const headers = Object.assign({Accept: 'application/json'}, opcoes.headers || {});
    if (comToken) headers.Authorization = 'Bearer ' + token;
    const resp = await fetch(base + acao, Object.assign({}, opcoes, {headers, credentials: 'omit'}));
    const texto = await resp.text();
    let json = null;
    try { json = JSON.parse(texto); } catch (e) { /* resposta não JSON */ }
    return {status: resp.status, json, texto: json ? '' : texto.slice(0, 120)};
  };
  const resumo = x => x.status + ' ' + (x.json ? JSON.stringify(x.json).slice(0, 170) : x.texto);

  const semToken = await api('list', {}, false);
  r.semToken = semToken.status;

  const lista = await api('list');
  r.listar = lista.status + ' total=' + (((lista.json || {}).data || {}).total);

  const png = Uint8Array.from(atob('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='), c => c.charCodeAt(0));
  const corpoFalso = new FormData();
  corpoFalso.append('file', new File(['<?php echo 1; ?>'], 'req240-api-falso.png', {type: 'image/png'}));
  r.envioFalso = resumo(await api('upload', {method: 'POST', body: corpoFalso}));

  const corpoPhp = new FormData();
  corpoPhp.append('file', new File(['<?php echo 1; ?>'], 'req240-api-shell.php', {type: 'text/plain'}));
  r.envioPhp = resumo(await api('upload', {method: 'POST', body: corpoPhp}));

  const corpo = new FormData();
  corpo.append('file', new File([png], 'req240-api-ok.png', {type: 'image/png'}));
  const envio = await api('upload', {method: 'POST', body: corpo});
  r.envio = resumo(envio);
  const dados = (envio.json || {}).data || {};
  const id = dados.id || '';
  r.caminho = dados.caminho || '';

  if (id) {
    r.detalhe = resumo(await api('get?id=' + encodeURIComponent(id))).slice(0, 120);
    r.excluir = resumo(await api('delete', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id})})).slice(0, 120);
    r.detalheDepois = (await api('get?id=' + encodeURIComponent(id))).status;
  }

  // Sobras de execuções anteriores deste roteiro: apaga pelo id fixo do arquivo de teste.
  for (const resto of ['req240-api-ok', 'req240-api-ok-1']) { if (resto !== id) await api('delete', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: resto})}); }

  // Revoga todo token temporário deste roteiro: o id de cada um está na tabela da tela, que é relida.
  const html = await fetch(location.href, {credentials: 'same-origin'}).then(x => x.text());
  const doc = new DOMParser().parseFromString(html, 'text/html');
  const linhas = [...doc.querySelectorAll('tr[data-token-id]')].filter(tr => tr.textContent.includes('req240-arquivos-api') && tr.querySelector('.btn-api-token-revogar'));
  let revogados = 0;
  for (const tr of linhas) { const rev = await painel({ajaxOpcao: 'api-token-revogar', id: tr.getAttribute('data-token-id')}); if (rev && !rev.error) revogados++; }
  r.tokensRevogados = revogados + ' de ' + linhas.length;
  r.aposRevogar = (await api('list')).status;
  return r;
})()
