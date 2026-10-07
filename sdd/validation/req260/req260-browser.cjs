// REQ-260 — camada de provedores de IA: o cadastro de servidores aceita os quatro tipos com endereço base e modelos,
// o teste de conexão vale para qualquer tipo e o Assistente IA do editor segue respondendo pelo servidor Gemini.
// Cria um servidor de teste (tipo compatível, apontando para uma porta fechada) e o exclui no fim.
// Faz DOIS pedidos reais ao Gemini com o servidor já cadastrado: o teste de conexão e um pedido do editor.
// Uso: C2F_BASE=https://v3.1-conn2flow.local C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do administrador> node req260-browser.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://v3.1-conn2flow.local';
const saida = path.join(__dirname, 'evidencias');
fs.mkdirSync(saida, {recursive: true});
const ler = arquivo => fs.readFileSync(arquivo, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});
let falhas = 0, total = 0;
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 800))); };
const NOME = 'Roteiro REQ-260 ' + Date.now();
const CHAVE = 'sk-roteiro-req260-chave-falsa-0123456789';
const TIPOS = ['gemini', 'anthropic', 'openai', 'openai-compativel'];

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await ctx.addCookies(ler(process.env.C2F_COOKIES));
  const page = await ctx.newPage();
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  // Mesmo caminho da tela: jQuery do painel, para o módulo da página aberta.
  const AJAX = ([a, d]) => new Promise(resolve => { $.ajax({type: 'POST', url: gestor.raiz + gestor.moduloCaminho + '/', dataType: 'json', data: Object.assign({ajax: 'sim', ajaxOpcao: a}, d), success: r => resolve(r), error: x => resolve({status: 'http-' + x.status, texto: String(x.responseText || '').slice(0, 300)})}); });
  const ajax = (acao, dados) => page.evaluate(AJAX, [acao, dados || {}]);
  let criado = null;

  try {
    // ===== Inclusão
    await page.goto(base + '/admin-ia/adicionar/', {waitUntil: 'networkidle'});
    const tela = await page.evaluate(() => ({
      opcoes: [...document.querySelectorAll('#admin-ia-tipo option')].map(o => [o.value, o.textContent.trim()]),
      campos: ['url_base', 'modelo', 'modelo_imagem'].map(n => { const c = document.querySelector('#form-servidor-ia [name="' + n + '"]'); return c ? c.getAttribute('placeholder') : null; }),
      cru: /@\[\[|\[\[provedores-json\]\]/.test(document.body.innerHTML),
    }));
    conferir('a inclusão traz os quatro tipos de servidor, com nome', tela.opcoes.map(o => o[0]).join() === TIPOS.join() && tela.opcoes.every(o => o[1].length > 3), tela.opcoes);
    conferir('endereço base e modelos mostram o padrão do Gemini como exemplo', /generativelanguage/.test(tela.campos[0] || '') && /gemini/.test(tela.campos[1] || '') && /image/.test(tela.campos[2] || ''), tela.campos);
    conferir('nenhuma variável ou marcador cru na tela', !tela.cru);
    await page.evaluate(() => { const s = document.getElementById('admin-ia-tipo'); s.value = 'anthropic'; s.dispatchEvent(new Event('change', {bubbles: true})); });
    const claude = await page.evaluate(() => ['url_base', 'modelo', 'modelo_imagem'].map(n => document.querySelector('[name="' + n + '"]').getAttribute('placeholder')));
    conferir('trocar o tipo troca os exemplos (Claude não tem modelo de imagem)', /anthropic/.test(claude[0]) && /claude/.test(claude[1]) && claude[2] === '', claude);
    await page.screenshot({path: path.join(saida, '1-inclusao.jpg'), type: 'jpeg', quality: 60, fullPage: true});
    // Largura de celular em página própria: redimensionar a janela aberta deixa o menu lateral no estado de desktop.
    const celular = await ctx.newPage();
    await celular.setViewportSize({width: 390, height: 844});
    await celular.goto(base + '/admin-ia/adicionar/', {waitUntil: 'networkidle'});
    conferir('em 390 px a inclusão não tem rolagem horizontal nem campo para fora da tela', await celular.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1 && [...document.querySelectorAll('#form-servidor-ia input, #form-servidor-ia select, #form-servidor-ia p')].every(e => e.getBoundingClientRect().right <= window.innerWidth + 1)));
    await celular.screenshot({path: path.join(saida, '1-inclusao-390.jpg'), type: 'jpeg', quality: 60, fullPage: true});
    await celular.close();

    // ===== O servidor recusa o que não serve
    const antes = await page.evaluate(() => fetch(gestor.raiz + 'admin-ia/listar/').then(r => r.text()));
    const recusas = {
      'tipo desconhecido': {nome: NOME, tipo: 'outro', chave_api: CHAVE},
      'compatível sem endereço base': {nome: NOME, tipo: 'openai-compativel', chave_api: CHAVE, modelo: 'm'},
      'compatível sem modelo': {nome: NOME, tipo: 'openai-compativel', chave_api: CHAVE, url_base: 'http://127.0.0.1:9/v1'},
      'endereço com parâmetros': {nome: NOME, tipo: 'openai', chave_api: CHAVE, url_base: 'https://exemplo.com/v1?key=abc'},
      'modelo com espaço': {nome: NOME, tipo: 'anthropic', chave_api: CHAVE, modelo: 'claude com espaço'},
    };
    for (const [caso, dados] of Object.entries(recusas)) {
      const r = await ajax('salvar', Object.assign({padrao: 'off'}, dados));
      conferir('salvar recusa: ' + caso, r.status === 'error' && /\S/.test(r.message || '') && !/msg-|@\[\[/.test(r.message), r);
    }
    conferir('nenhuma recusa criou servidor', !(await page.evaluate(() => fetch(gestor.raiz + 'admin-ia/listar/').then(r => r.text()))).includes(NOME) && !antes.includes(NOME));

    // ===== Servidor de teste: tipo compatível, porta fechada
    const salvo = await ajax('salvar', {nome: NOME, tipo: 'openai-compativel', chave_api: CHAVE, padrao: 'off', url_base: 'http://127.0.0.1:9/v1/', modelo: 'modelo-de-roteiro'});
    conferir('salva servidor compatível com endereço e modelo', salvo.status === 'success' && Number(salvo.id) > 0, salvo);
    if (!(Number(salvo.id) > 0)) throw new Error('servidor de teste não foi criado');
    criado = Number(salvo.id);

    const teste = await ajax('testar_conexao', {id: criado});
    conferir('testar conexão de tipo novo responde (falha esperada: porta fechada) sem mostrar a chave', teste.status === 'error' && /\S/.test(teste.message || '') && !JSON.stringify(teste).includes(CHAVE) && !/não suportado/i.test(teste.message), teste);

    await page.goto(base + '/admin-ia/editar/?id=' + criado, {waitUntil: 'networkidle'});
    const edicao = await page.evaluate(() => ({
      tipo: document.getElementById('admin-ia-tipo').value,
      url: document.querySelector('[name="url_base"]').value, modelo: document.querySelector('[name="modelo"]').value, imagem: document.querySelector('[name="modelo_imagem"]').value,
      chave: document.querySelector('[name="chave_api"]').value,
      cru: /\[\[(sel-|url-base|modelo|provedores-json)/.test(document.body.innerHTML),
      historico: document.querySelectorAll('#historico-testes article').length,
    }));
    conferir('a edição abre com o tipo, o endereço (sem a barra final) e o modelo gravados', edicao.tipo === 'openai-compativel' && edicao.url === 'http://127.0.0.1:9/v1' && edicao.modelo === 'modelo-de-roteiro' && edicao.imagem === '', edicao);
    conferir('a chave aparece mascarada e não há marcador cru', /^\*+$/.test(edicao.chave) && !edicao.cru, {chave: edicao.chave.length, cru: edicao.cru});
    conferir('o teste falho ficou no histórico do servidor', edicao.historico >= 1, edicao.historico);
    await page.screenshot({path: path.join(saida, '2-edicao.jpg'), type: 'jpeg', quality: 60, fullPage: true});

    const editado = await ajax('editar', {id: criado, nome: NOME, tipo: 'openai', chave_api: edicao.chave, padrao: 'off', url_base: '', modelo: 'gpt-de-roteiro', modelo_imagem: 'gpt-image-1'});
    await page.goto(base + '/admin-ia/editar/?id=' + criado, {waitUntil: 'networkidle'});
    const depois = await page.evaluate(() => [document.getElementById('admin-ia-tipo').value, document.querySelector('[name="url_base"]').value, document.querySelector('[name="modelo"]').value, document.querySelector('[name="modelo_imagem"]').value, document.querySelector('[name="url_base"]').getAttribute('placeholder')]);
    conferir('editar troca o tipo e os modelos; endereço vazio volta ao padrão do provedor', editado.status === 'success' && depois.slice(0, 4).join('|') === 'openai||gpt-de-roteiro|gpt-image-1' && /api\.openai\.com/.test(depois[4]), {editado, depois});

    // ===== Listagem
    await page.goto(base + '/admin-ia/listar/', {waitUntil: 'networkidle'});
    const linhas = await page.evaluate(() => [...document.querySelectorAll('tbody tr')].map(tr => ({nome: tr.cells[0].textContent.trim(), tipo: tr.cells[1].textContent.trim(), id: (tr.querySelector('.testar-conexao') || {dataset: {}}).dataset.id})));
    const minha = linhas.find(l => l.nome === NOME);
    conferir('a listagem mostra o nome do provedor, não o código nem marcador', !!minha && minha.tipo === 'OpenAI' && linhas.every(l => !/#tipo#/.test(l.tipo)), linhas);
    await page.screenshot({path: path.join(saida, '3-listagem.jpg'), type: 'jpeg', quality: 60});

    // ===== Servidor Gemini que já existe: teste de conexão e pedido do editor (dois pedidos reais)
    const gemini = linhas.find(l => /Gemini/i.test(l.tipo));
    conferir('há um servidor Gemini cadastrado neste ambiente', !!gemini, linhas);
    if (gemini) {
      const real = await ajax('testar_conexao', {id: gemini.id});
      conferir('testar conexão do servidor Gemini existente: sucesso', real.status === 'success', real);

      await page.goto(base + '/admin-paginas/adicionar/', {waitUntil: 'networkidle'});
      const pedido = await ajax('html-editor-ia-requests', {target: 'paginas', server_id: gemini.id, mode: 'Você escreve HTML. Responda apenas com um bloco ```html contendo um único parágrafo.', prompt: 'Escreva um parágrafo com a palavra roteiro.', data: {html: '', css: '', framework_css: 'tailwindcss'}});
      const d = pedido.data || {};
      conferir('o pedido do editor ao Gemini volta com HTML, modelo e tokens', pedido.status === 'Ok' && /<p[\s>]/i.test(d.html_gerado || '') && /gemini/.test(d.modelo_usado || '') && Number(d.tokens_total) > 0, {status: pedido.status, message: pedido.message, modelo: d.modelo_usado, tokens: d.tokens_total, html: (d.html_gerado || '').slice(0, 120)});
      conferir('a resposta do editor não carrega endereço com chave', !/[?&]key=/.test(JSON.stringify(pedido)));
    }
    conferir('nenhum erro de JavaScript nas telas', erros.length === 0, erros);
  } catch (e) {
    conferir('roteiro chegou ao fim', false, e.message);
  } finally {
    if (criado) {
      await page.goto(base + '/admin-ia/listar/', {waitUntil: 'networkidle'}).catch(() => {});
      const r = await ajax('excluir', {id: criado}).catch(e => ({status: 'erro', message: e.message}));
      const resta = await page.evaluate(() => fetch(gestor.raiz + 'admin-ia/listar/').then(x => x.text())).catch(() => '');
      conferir('o servidor de teste foi excluído', r.status === 'success' && !resta.includes(NOME), r);
    }
    await browser.close();
  }
  console.log('\n' + (total - falhas) + '/' + total + ' conferências' + (falhas ? ' — ' + falhas + ' FALHA(S)' : ''));
  process.exit(falhas ? 1 : 0);
})();
