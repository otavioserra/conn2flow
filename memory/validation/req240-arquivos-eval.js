// REQ-240 (segundo adendo) — exercita o admin-arquivos pela própria página, com a sessão corrente.
// Usado por req240-probe.cjs --eval. Cria e remove só o que ele mesmo criou (pasta e arquivo `req240-*`).
//
// SEGURANÇA DO ROTEIRO: as sondagens "fora do escopo" miram FORA, um caminho que não existe. Com escopo,
// a resposta esperada é `Invalid` (a regra barra antes de olhar o disco); sem escopo, `NotFound`. Em
// nenhum dos dois casos algo real pode ser movido, renomeado ou apagado. A primeira versão mirava a
// pasta `favicon` e, rodada como administrador (que não tem escopo), apagou a pasta do Lab.
const FORA = 'req240-fora-do-escopo-inexistente';
(async () => {
  const url = gestor.raiz + gestor.moduloId + '/';
  const chamar = (ajaxOpcao, dados) => new Promise(resolve => {
    $.ajax({type: 'POST', url, dataType: 'json', data: Object.assign({opcao: 'listar-arquivos', ajax: 'sim', ajaxOpcao}, dados)})
      .done(r => resolve(r)).fail(x => resolve({falha: x.status}));
  });
  const enviar = (nome, bytes, dir) => new Promise(resolve => {
    const fd = new FormData();
    fd.append('opcao', 'upload'); fd.append('ajax', 'sim'); fd.append('ajaxOpcao', 'uploadFile'); fd.append('dir', dir);
    fd.append('files[]', new File([bytes], nome));
    $.ajax({type: 'POST', url, data: fd, processData: false, contentType: false, dataType: 'json'})
      .done(r => resolve(r)).fail(x => resolve({falha: x.status, corpo: (x.responseText || '').slice(0, 120)}));
  });
  const r = {};

  const raiz = await chamar('navegar', {dir: '', pagina: 0, filtros: '{}'});
  r.raiz = {dir: raiz.dir, trilha: (raiz.breadcrumb || []).map(b => b.caminho), uso: raiz.uso, pastas: (raiz.pastas || []).length, arquivos: raiz.total};
  r.escopoJs = (gestor.adminArquivos || {}).escopo;

  r.fugas = {};
  // Só leitura: pedir uma pasta alheia nunca altera nada.
  for (const dir of ['../', '../../etc', '/etc/passwd', 'favicon', 'plugins', 'outro-usuario/files', raiz.dir + '/../favicon', 'mini']) {
    const resp = await chamar('navegar', {dir, pagina: 0, filtros: '{}'});
    r.fugas[dir] = resp.dir;
  }

  const pasta = await chamar('pasta-criar', {dir: raiz.dir, nome: 'req240-teste'});
  r.pastaCriada = pasta.status + ' ' + ((pasta.pasta || {}).caminho || '');
  const destino = (pasta.pasta || {}).caminho || '';

  // Conteúdo que contradiz a extensão, SVG com script e PNG de verdade (1x1).
  const png = Uint8Array.from(atob('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='), c => c.charCodeAt(0));
  const falso = await enviar('req240-falso.png', '<?php echo 1; ?>', destino);
  r.uploadFalso = falso.status + ' | ' + (falso.error || falso.falha || '');
  const php = await enviar('req240-shell.php', '<?php echo 1; ?>', destino);
  r.uploadPhp = php.status + ' | ' + (php.error || php.falha || '');
  const bom = await enviar('req240-ok.png', png, destino);
  r.uploadBom = bom.status + ' | ' + (bom.caminho || bom.error || bom.falha || '');
  const svg = await enviar('req240-icone.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script><rect width="4" height="4"/></svg>', destino);
  r.uploadSvg = svg.status + ' | ' + (svg.caminho || svg.error || '');
  if (svg.url) {
    const texto = await fetch(svg.url + '?t=' + Date.now()).then(x => x.text()).catch(() => '');
    r.svgNoDisco = {temScript: /<script/i.test(texto), temOnload: /onload/i.test(texto), temRect: /<rect/.test(texto)};
  }

  // Mover: o PNG sai da pasta de teste para a raiz do escopo e volta.
  if (bom.caminho) {
    const ida = await chamar('mover', {destino: raiz.dir, itens: JSON.stringify([{caminho: bom.caminho, tipo: 'arquivo'}])});
    r.moverIda = (ida.resultados || []).map(x => x.status + ' -> ' + (x.novo || '')).join(', ');
    const novo = ((ida.resultados || [])[0] || {}).novo || '';
    const volta = await chamar('mover', {destino, itens: JSON.stringify([{caminho: novo, tipo: 'arquivo'}])});
    r.moverVolta = (volta.resultados || []).map(x => x.status + ' -> ' + (x.novo || '')).join(', ');
  }
  // Destino fora do escopo: com escopo, o servidor traz o destino para a raiz do usuário; sem escopo,
  // a pasta não existe e nada se move.
  const paraFora = await chamar('mover', {destino: FORA, itens: JSON.stringify([{caminho: destino, tipo: 'pasta'}])});
  r.moverParaFora = (paraFora.status || '') + ' | destino=' + (paraFora.destino === undefined ? '-' : paraFora.destino) + ' | ' + (paraFora.resultados || []).map(x => x.status).join(',');
  const deFora = await chamar('mover', {destino, itens: JSON.stringify([{caminho: FORA, tipo: 'pasta'}])});
  r.moverDeFora = (deFora.resultados || []).map(x => x.status).join(',');

  // Ações sobre o que está fora do escopo (caminho inexistente) e, só quando há escopo, sobre a raiz dele
  // (sem `recursivo`: se a regra falhasse, uma raiz com conteúdo responderia NotEmpty em vez de sumir).
  const alvos = [{caminho: FORA, tipo: 'pasta'}];
  if (raiz.dir !== '') alvos.push({caminho: raiz.dir, tipo: 'pasta'});
  const forasteiro = await chamar('excluir', {itens: JSON.stringify(alvos), recursivo: 'false'});
  r.excluirFora = (forasteiro.resultados || []).map(x => x.caminho + '=' + x.status).join(', ');
  const renomear = await chamar('renomear', {caminho: FORA, nome: 'req240-invadido'});
  r.renomearFora = renomear.status;

  // Limpeza do que este roteiro criou.
  const limpeza = await chamar('excluir', {itens: JSON.stringify([{caminho: destino, tipo: 'pasta'}]), recursivo: 'true'});
  r.limpeza = (limpeza.resultados || []).map(x => x.status).join(',');
  const fim = await chamar('navegar', {dir: raiz.dir, pagina: 0, filtros: '{}'});
  r.sobrou = (fim.pastas || []).filter(p => /req240/.test(p.nome)).length + (fim.arquivos || []).filter(a => /req240/.test(a.nome)).length;

  r.tela = {
    dicas: [...document.querySelectorAll('.c2f-view-btn')].filter(b => b.getAttribute('data-c2f-dica')).length,
    circulos: document.querySelectorAll('[data-admin-main] svg.lucide-circle').length,
    naoDesenhados: document.querySelectorAll('[data-admin-main] i[data-lucide], [data-admin-main] i.icon').length,
    usoVisivel: !!document.getElementById('c2f-usage') && !document.getElementById('c2f-usage').classList.contains('hidden'),
    usoTexto: (document.getElementById('c2f-usage-text') || {}).textContent,
    trilha: (document.getElementById('c2f-breadcrumb') || {}).innerText,
    recortar: !!document.getElementById('c2f-cut-selected'),
    overflow: document.documentElement.scrollWidth - innerWidth,
  };
  return r;
})()
