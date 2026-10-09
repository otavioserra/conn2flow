// Prepara a área de widgets de uma instalação nova para os roteiros: um widget de cada tipo que tiver registro.
// Só age quando o usuário ainda não tem widgets; não mexe em layout existente.
// Uso: C2F_BASE=https://v3.1-conn2flow.local:8443 C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies> node preparar-area-de-widgets.cjs
const fs = require('node:fs');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const jar = fs.readFileSync(process.env.C2F_COOKIES, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});
(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true});
  await ctx.addCookies(jar);
  const page = await ctx.newPage();
  await page.goto(base + '/dashboard/', {waitUntil: 'networkidle', timeout: 90000});
  const resultado = await page.evaluate(async () => {
    const ajax = async (acao, dados) => { const p = new URLSearchParams(Object.assign({opcao: 'inicio', ajax: 'sim', ajaxOpcao: acao}, dados || {})); return (await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p})).json(); };
    const atual = gestor.dashboard_user_prefs.widgets_layout || [];
    if (atual.length) return {mantido: atual.map(w => w.id)};
    const tipos = (await ajax('widgets-catalogo')).data || [];
    const layout = [], semRegistro = [];
    for (const tipo of tipos) {
      const registros = ((await ajax('widgets-registros', {widget_id: tipo.id, pagina: 1})).data || {}).items || [];
      if (!registros.length) { semRegistro.push(tipo.id); continue; }
      const r = registros[0];
      layout.push({id: tipo.id, name: (tipo.name || tipo.id) + ' / ' + (r.nome || r.id), registro_id: r.id, params: {grupo_slug: r.id}, instance_id: 'roteiro-' + tipo.id, width: 6, height: 2, height_px: 460});
    }
    const gravado = await ajax('salvar-preferencias', {chave: 'dashboard_widgets_layout', valor: JSON.stringify(layout)});
    return {criado: layout.map(w => w.id + ':' + w.registro_id), semRegistro, status: gravado.status};
  });
  console.log(JSON.stringify(resultado, null, 1));
  await browser.close();
})().catch(e => { console.error(e.message.split('\n')[0]); process.exit(2); });
