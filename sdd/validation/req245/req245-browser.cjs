// REQ-245 — roteiro de navegador no Lab local: abas Manual e Automático do admin-atualizacoes e a rotina no admin-cron.
// Uso: node sdd/validation/req245/req245-browser.cjs [--saida=<pasta de evidências>]
// C2F_PLAYWRIGHT aponta o pacote do Playwright; C2F_COOKIES, o arquivo de `c2f auth:cookie --project=conn2flow-site-local`.
// Grava e desfaz a configuração da atualização automática (termina desligada, como começou). Não dispara atualização:
// "Verificar agora" só consulta a versão publicada.
const {chromium} = require(process.env.C2F_PLAYWRIGHT || '../../../node_modules/playwright');
const fs = require('node:fs'), path = require('node:path');
const core = path.resolve(__dirname, '../../..');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const opcao = nome => (process.argv.find(a => a.startsWith('--' + nome + '=')) || '').split('=').slice(1).join('=');
const saida = opcao('saida') ? path.resolve(opcao('saida')) : path.join(__dirname, 'evidencias');
fs.mkdirSync(saida, {recursive: true});
const jar = fs.readFileSync(process.env.C2F_COOKIES || path.join(core, 'temp/agent-cookies.txt'), 'utf8').split(/\r?\n/)
  .filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
    const p = l.replace(/^#HttpOnly_/, '').split('\t');
    return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
  });
const resultados = [];
function conferir(nome, ok, detalhe) {
  resultados.push({nome, ok: !!ok, detalhe: detalhe === undefined ? '' : detalhe});
  console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok ? '' : ' -> ' + String(JSON.stringify(detalhe === undefined ? '' : detalhe)).slice(0, 500)));
}

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const novo = async largura => { const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: largura, height: 900}}); await ctx.addCookies(jar); const p = await ctx.newPage(); p._erros = []; p.on('pageerror', e => p._erros.push(e.message.slice(0, 160))); return p; };
  const abrir = async (page, rota) => { const r = await page.goto(base + '/' + rota, {waitUntil: 'networkidle', timeout: 60000}); await page.waitForTimeout(500); return r.status(); };
  const foto = (page, nome, inteira) => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60, fullPage: !!inteira}).catch(() => {});
  const visiveis = page => page.evaluate(() => ({manual: !document.querySelector('[data-c2f-painel="manual"]').hidden, auto: !document.querySelector('[data-c2f-painel="automatico"]').hidden, aba: (document.querySelector('#atualizacoes-modos [data-c2f-aba][aria-selected="true"]') || {getAttribute: () => null}).getAttribute('data-c2f-aba')}));
  const esperarResposta = page => page.waitForResponse(r => r.request().method() === 'POST' && /admin-atualizacoes/.test(r.url()), {timeout: 60000}).then(() => page.waitForTimeout(400));
  const estadoTela = page => page.evaluate(() => ({ativo: document.getElementById('auto-ativo').checked, periodo: (document.querySelector('[data-auto-periodo][data-selected="true"]') || {getAttribute: () => null}).getAttribute('data-auto-periodo'), hora: document.getElementById('auto-hora').value, backup: document.getElementById('auto-backup').checked, selo: (document.querySelector('#auto-estado .c2fc-rotulo') || {}).textContent, mensagem: document.getElementById('auto-mensagem').textContent, estado: document.getElementById('auto-estado').innerText.replace(/\s+/g, ' ').slice(0, 400)}));
  const salvar = async (page, cfg) => {
    await page.evaluate(c => { const a = document.getElementById('auto-ativo'); if (a.checked !== c.ativo) a.click(); const b = document.getElementById('auto-backup'); if (b.checked !== c.backup) b.click(); document.querySelector('[data-auto-periodo="' + c.periodo + '"]').click(); window.c2fControles.de(document.getElementById('auto-hora')).definir(String(c.hora)); }, cfg);
    await Promise.all([esperarResposta(page), page.click('#auto-salvar')]);
  };

  const page = await novo(1366);
  try { await page.evaluate(() => 0); } catch (e) { /* página em branco */ }
  conferir('admin-atualizacoes abre (HTTP 200)', await abrir(page, 'admin-atualizacoes/') === 200);
  await page.evaluate(() => { try { localStorage.removeItem('c2f-admin-atualizacoes-modo'); } catch (e) {} });
  await abrir(page, 'admin-atualizacoes/');

  // ---- abas
  const inicio = await visiveis(page);
  conferir('abre na aba Manual, com a Automático escondida', inicio.manual && !inicio.auto && inicio.aba === 'manual', inicio);
  const manual = await page.evaluate(() => { const p = document.querySelector('[data-c2f-painel="manual"]'); return {modos: p.querySelectorAll('.upd-mode-btn').length, iniciar: !!p.querySelector('#atualizacoes-start-btn'), opcoes: p.querySelectorAll('.upd-flag').length, resumo: !!document.querySelector('[data-atualizacoes-resumo]') && !document.querySelector('[data-atualizacoes-resumo]').closest('[data-c2f-painel]'), registros: !document.querySelector('[data-atualizacoes-registros]').closest('[data-c2f-painel]')}; });
  conferir('Manual guarda os três modos, as opções e o botão de executar; resumo e registros ficam fora das abas', manual.modos === 3 && manual.iniciar && manual.opcoes >= 10 && manual.resumo && manual.registros, manual);
  await page.click('.upd-mode-btn[data-modo="only-files"]'); await page.waitForTimeout(200);
  const escolhido = await page.evaluate(() => ({sel: document.querySelector('.upd-mode-btn[data-modo="only-files"]').getAttribute('data-selected'), botao: !document.getElementById('atualizacoes-start-btn').disabled}));
  conferir('o modo manual continua funcionando (escolher um modo libera o botão)', escolhido.sel === 'true' && escolhido.botao, escolhido);
  await foto(page, '01-manual', true);
  await page.click('[data-c2f-aba="automatico"]'); await page.waitForTimeout(300);
  const auto = await visiveis(page);
  conferir('a aba Automático troca o painel', !auto.manual && auto.auto && auto.aba === 'automatico', auto);
  const desenho = await page.evaluate(() => { const p = document.getElementById('atualizacoes-auto'); const cartoes = [...p.querySelectorAll('[data-auto-periodo]')].map(c => c.getBoundingClientRect()); const cru = (p.innerText.match(/@?\[\[[^\]]+\]\]@?|#[a-z-]+#/g) || []); return {blocos: p.querySelectorAll(':scope > section').length, numeros: [...p.querySelectorAll(':scope > section h2 > span')].map(s => s.textContent.trim()), periodos: cartoes.length, mesmaLinha: cartoes.every(c => Math.abs(c.top - cartoes[0].top) < 2), horaControle: !!document.querySelector('.c2fc-select > #auto-hora'), horas: document.getElementById('auto-hora').options.length, cru, dicas: [...p.querySelectorAll('#auto-salvar, #auto-verificar, a[href*="admin-cron"]')].map(b => (b.getAttribute('data-c2f-dica') || '').length > 5)}; });
  conferir('três blocos numerados, três períodos lado a lado e hora com o controle do painel', desenho.blocos === 3 && desenho.numeros.join('') === '123' && desenho.periodos === 3 && desenho.mesmaLinha && desenho.horaControle && desenho.horas === 24, desenho);
  conferir('nenhuma variável ou marcador cru na aba; botões com dica', desenho.cru.length === 0 && desenho.dicas.length === 3 && desenho.dicas.every(Boolean), desenho);
  const original = await estadoTela(page);
  conferir('a automação nasce desligada nesta instalação (ou já foi ligada por alguém: registrado)', true, original);
  await foto(page, '02-automatico', true);

  // ---- salvar e reler
  await salvar(page, {ativo: true, periodo: 'mensal', hora: 22, backup: false});
  const salvo = await estadoTela(page);
  conferir('salvar grava e a tela responde (selo Ligada e mensagem)', salvo.mensagem.length > 3 && /Ligada|On/.test(salvo.selo || ''), salvo);
  await abrir(page, 'admin-atualizacoes/');
  const relido = await estadoTela(page);
  const abaLembrada = await visiveis(page);
  conferir('depois de recarregar: aba Automático lembrada e configuração mantida', abaLembrada.auto && relido.ativo && relido.periodo === 'mensal' && relido.hora === '22' && !relido.backup, {abaLembrada, relido});
  conferir('próxima checagem aparece às 22:00', /22:00/.test(relido.estado), relido.estado);
  await foto(page, '03-automatico-ligada', true);

  // ---- rotina no admin-cron acompanha o interruptor
  const cron = await novo(1366);
  await abrir(cron, 'admin-cron/'); await cron.waitForTimeout(800);
  const tarefa = () => cron.evaluate(() => { const l = [...document.querySelectorAll('tbody tr')].find(t => /admin-atualizacoes-automatica/.test(t.textContent)); return l ? {texto: l.innerText.replace(/\s+/g, ' ').slice(0, 220), pausada: /Pausada|Paused/.test(l.innerText), alternar: (l.querySelector('[data-acao="toggle"]') || {}).textContent} : null; });
  const ligada = await tarefa();
  conferir('a rotina aparece no admin-cron como tarefa do módulo, de hora em hora, ativa', ligada && /admin-atualizacoes/.test(ligada.texto) && /0 \* \* \* \*/.test(ligada.texto) && !ligada.pausada, ligada);
  await foto(cron, '04-admin-cron');

  // ---- verificar agora (só consulta)
  await Promise.all([esperarResposta(page), page.click('#auto-verificar')]);
  const verificado = await estadoTela(page);
  const consultou = !/Nunca conferido|Never checked/.test(verificado.estado);
  conferir('Verificar agora registra a checagem e mostra a versão publicada ou o motivo', consultou && verificado.mensagem.length > 3, verificado);
  const semExecucao = await page.evaluate(() => !/em andamento|in progress/i.test(document.getElementById('auto-estado').innerText));
  conferir('Verificar agora não dispara atualização', semExecucao, verificado.estado);
  await foto(page, '05-verificado', true);

  // ---- desligar e conferir a rotina
  await salvar(page, {ativo: false, periodo: 'semanal', hora: 3, backup: true});
  const desligado = await estadoTela(page);
  conferir('desligar volta ao padrão (selo Desligada, sem próxima checagem)', /Desligada|Off/.test(desligado.selo || '') && !desligado.ativo && desligado.periodo === 'semanal' && desligado.hora === '3' && desligado.backup, desligado);
  await abrir(cron, 'admin-cron/'); await cron.waitForTimeout(800);
  const pausada = await tarefa();
  conferir('a rotina fica pausada no admin-cron quando a automação é desligada', pausada && pausada.pausada, pausada);
  await cron.context().close();

  // ---- 390 px
  const celular = await novo(390);
  await abrir(celular, 'admin-atualizacoes/');
  await celular.click('[data-c2f-aba="automatico"]').catch(() => {}); await celular.waitForTimeout(300);
  conferir('aba Automático sem rolagem lateral em 390 px', await celular.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), await celular.evaluate(() => document.documentElement.scrollWidth));
  await foto(celular, '06-automatico-390', true);
  await celular.click('[data-c2f-aba="manual"]'); await celular.waitForTimeout(300);
  conferir('aba Manual sem rolagem lateral em 390 px', await celular.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
  await celular.evaluate(() => { try { localStorage.removeItem('c2f-admin-atualizacoes-modo'); } catch (e) {} });
  await celular.context().close();
  await page.evaluate(() => { try { localStorage.removeItem('c2f-admin-atualizacoes-modo'); } catch (e) {} });
  conferir('nenhum erro de script na tela', page._erros.length === 0, page._erros);

  await browser.close();
  const falhas = resultados.filter(r => !r.ok);
  fs.writeFileSync(path.join(saida, 'resultado.json'), JSON.stringify({base, quando: new Date().toISOString(), total: resultados.length, aprovadas: resultados.length - falhas.length, falhas, resultados}, null, 1));
  console.log(`\n${resultados.length - falhas.length} de ${resultados.length} conferências`);
  process.exit(falhas.length ? 1 : 0);
})();
