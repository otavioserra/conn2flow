// REQ-256 — miniaturas dos modelos do cadastro de modelos.
// Para cada modelo sem miniatura: preenche o HTML com dados de exemplo (repete o bloco de item, tira os blocos de
// estado vazio, troca os marcadores por texto e imagem de amostra), renderiza no quadro de prévia do editor de modelos
// (que já carrega o framework CSS certo) e fotografa a área de 1024 x 768 px. A conversão para WebP é do `montar.py`.
//
// Entrada: MODELOS=<arquivo .jsonl com id, language, target, framework, html, css por linha>
// Uso: C2F_BASE=<painel> C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do administrador> MODELOS=<jsonl> SAIDA=<pasta> [SO=id1,id2] node gerar-miniaturas.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE;
const saida = process.env.SAIDA;
// 4:3, a proporção da área de imagem do cartão de modelo no painel (`aspect-4/3` com recorte para preencher):
// uma foto mais larga que isso perde as laterais no cartão.
const LARGURA = 1024, ALTURA = 768;
const ler = arquivo => fs.readFileSync(arquivo, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});

const TEXTOS = {
  'pt-br': {titulos: ['Como começar em poucos passos', 'Novidades desta semana', 'Guia rápido para a equipe', 'O que mudou na plataforma', 'Dicas para o dia a dia', 'Perguntas frequentes'],
    frase: 'Um resumo curto do conteúdo, com duas linhas de texto para mostrar como o modelo fica preenchido.', data: ['07/10/2026', '02/10/2026', '28/09/2026', '21/09/2026', '15/09/2026', '08/09/2026'],
    menu: ['Início', 'Produtos', 'Novidades', 'Sobre', 'Contato'], preco: 'R$ 99,00', precoDe: 'R$ 129,00', botao: 'Saiba mais', comprar: 'Comprar', carrinho: 'Adicionar ao carrinho', selo: 'Novo',
    recurso: ['Suporte por e-mail', 'Atualizações incluídas', 'Uso em um site', 'Modelos prontos'], categoria: ['Geral', 'Notícias', 'Tutoriais'], opcao: ['Primeira opção', 'Segunda opção', 'Terceira opção'],
    rotulo: ['Nome', 'E-mail', 'Mensagem', 'Assunto', 'Preferência', 'Aceite'], buscar: 'Buscar...', nome: 'Nome de exemplo', plano: ['Básico', 'Profissional', 'Empresa'], frete: 'Frete grátis',
    cookies: {titulo: 'Sua privacidade', mensagem: 'Usamos cookies necessários para o site funcionar e, com a sua permissão, cookies para medir o uso e melhorar a sua experiência.', politica: 'Política de Privacidade', termos: 'Termos de Uso', aceitar: 'Aceitar todos', recusar: 'Recusar opcionais', personalizar: 'Personalizar', salvar: 'Salvar preferências'},
    corpo: '<main style="max-width:960px;margin:0 auto;padding:48px 24px"><h1 style="font-size:2.2rem;font-weight:700;margin:0 0 16px">Título da página</h1><p style="font-size:1.05rem;line-height:1.6;opacity:.8">Aqui entra o conteúdo da página. O layout cuida do que fica em volta: cabeçalho, navegação e rodapé.</p></main>'},
  en: {titulos: ['How to get started in a few steps', 'What is new this week', 'Quick guide for the team', 'What changed on the platform', 'Everyday tips', 'Frequently asked questions'],
    frase: 'A short summary of the content, with two lines of text to show how the template looks when filled in.', data: ['Oct 7, 2026', 'Oct 2, 2026', 'Sep 28, 2026', 'Sep 21, 2026', 'Sep 15, 2026', 'Sep 8, 2026'],
    menu: ['Home', 'Products', 'News', 'About', 'Contact'], preco: '$99.00', precoDe: '$129.00', botao: 'Learn more', comprar: 'Buy', carrinho: 'Add to cart', selo: 'New',
    recurso: ['Email support', 'Updates included', 'Use on one site', 'Ready-made templates'], categoria: ['General', 'News', 'Tutorials'], opcao: ['First option', 'Second option', 'Third option'],
    rotulo: ['Name', 'Email', 'Message', 'Subject', 'Preference', 'Agreement'], buscar: 'Search...', nome: 'Sample name', plano: ['Basic', 'Professional', 'Business'], frete: 'Free shipping',
    cookies: {titulo: 'Your privacy', mensagem: 'We use cookies that are necessary for the site to work and, with your permission, cookies to measure usage and improve your experience.', politica: 'Privacy Policy', termos: 'Terms of Use', aceitar: 'Accept all', recusar: 'Reject optional', personalizar: 'Customize', salvar: 'Save preferences'},
    corpo: '<main style="max-width:960px;margin:0 auto;padding:48px 24px"><h1 style="font-size:2.2rem;font-weight:700;margin:0 0 16px">Page title</h1><p style="font-size:1.05rem;line-height:1.6;opacity:.8">The page content goes here. The layout takes care of what surrounds it: header, navigation and footer.</p></main>'},
};
const CORES = [['#0ea5e9', '#6366f1'], ['#f97316', '#ec4899'], ['#10b981', '#0ea5e9'], ['#8b5cf6', '#ec4899'], ['#f59e0b', '#ef4444'], ['#14b8a6', '#6366f1']];
const imagem = n => { const [a, b] = CORES[n % CORES.length];
  return 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 520" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' + a + '"/><stop offset="1" stop-color="' + b + '"/></linearGradient></defs><rect width="800" height="520" fill="url(#g)"/><circle cx="590" cy="150" r="56" fill="#fff" fill-opacity=".35"/><path d="M0 520 L250 250 L400 400 L520 300 L800 520 Z" fill="#fff" fill-opacity=".28"/></svg>'); };

// Blocos que só aparecem em estado vazio, de erro ou desligado saem; os de item se repetem.
const SAI = /^(no-|sem-|empty|erro|error|loading|link-disabled|guest|category-required|results-box)/;
const SUBMOLDE = /^(option-choice|option-select|password-toggle)$/;
const REPETE = {item: null, 'dot-item': 5, feature: 4, 'category-item': 3, 'option-choice': 3, 'option-select': 3};
const CAMPOS = ['type-input', 'type-input', 'type-select', 'type-textarea'];
const ITENS = {forms: 4, 'forms-search': 2, menus: 5, galleries: 6, 'galleries-estados': 6, 'publisher-index': 6, 'publisher-highlights': 4, 'pages-index': 6, 'products-index': 6, 'subscriptions-plans': 3, 'presentations': 5};

function valor(chave, n, t, alvo) {
  const k = chave.toLowerCase(), fim = k.split('#').pop();
  if (/children/.test(fim)) return '';
  if (alvo === 'cookie-consent') {
    if (/policy_label/.test(fim)) return t.cookies.politica;
    if (/terms_label/.test(fim)) return t.cookies.termos;
    if (/accept|aceit/.test(fim)) return t.cookies.aceitar;
    if (/reject|recus|deny/.test(fim)) return t.cookies.recusar;
    if (/custom|personal|prefer|manage|settings/.test(fim)) return t.cookies.personalizar;
    if (/save|salvar/.test(fim)) return t.cookies.salvar;
    if (/title|titulo/.test(fim)) return t.cookies.titulo;
    if (/message|mensagem|description|descri/.test(fim)) return t.cookies.mensagem;
    if (/position/.test(fim)) return 'bottom-left';
  }
  if (/^pagina#/.test(k)) return /corpo|body|conteudo/.test(fim) ? t.corpo : (/titulo|title/.test(fim) ? t.titulos[0] : (/url-raiz|url/.test(fim) ? '/' : ''));
  if (/(^|[_#-])(url|href|link|action|checkout|cart_url|policy_url|terms_url)|_url$|^url/.test(fim)) return '#';
  if (/imag|img|thumb|foto|photo|capa|cover|logo|avatar|src|banner/.test(fim) && !/position|alt/.test(fim)) return imagem(n);
  if (/position/.test(fim)) return 'center';
  if (/height|altura/.test(fim)) return '420';
  if (/page_count|count/.test(fim)) return '6';
  if (/page_total|total/.test(fim)) return '24';
  if (/items_per_page/.test(fim)) return '6';
  if (/original|price_from|de$/.test(fim) && /pric|prec/.test(fim)) return t.precoDe;
  if (/pric|prec|valor|amount/.test(fim)) return t.preco;
  if (/data|date|publicad|criad/.test(fim) && !/metadata/.test(fim)) return t.data[n % t.data.length];
  if (/resumo|descri|excerpt|summary|subtit|texto|conteudo|content|chamada|lead/.test(fim)) return t.frase;
  if (/cart_label/.test(fim)) return t.carrinho;
  if (/button|botao|cta|submit|label_botao/.test(fim)) return alvo.startsWith('product') || alvo.startsWith('subscr') ? t.comprar : t.botao;
  if (/badge|selo|tag/.test(fim)) return t.selo;
  if (/feature|recurso|beneficio/.test(fim)) return t.recurso[n % t.recurso.length];
  if (/categor/.test(fim)) return t.categoria[n % t.categoria.length];
  if (/shipping|frete/.test(fim)) return t.frete;
  if (/placeholder|search|busca/.test(fim)) return alvo === 'forms' ? '' : t.buscar;
  if (/^option#|opcao/.test(k)) return /value|valor/.test(fim) ? 'op' + n : t.opcao[n % t.opcao.length];
  if (/label|rotulo/.test(fim)) return alvo === 'menus' ? t.menu[n % t.menu.length] : (alvo === 'forms' ? t.rotulo[n % t.rotulo.length] : t.titulos[n % t.titulos.length]);
  if (/titulo|title|headline|heading/.test(fim)) return alvo === 'subscriptions-plans' ? t.plano[n % t.plano.length] : t.titulos[n % t.titulos.length];
  if (/^name$|nome|autor|author/.test(fim)) return alvo === 'subscriptions-plans' ? t.plano[n % t.plano.length] : (alvo === 'forms' ? 'campo' + n : t.nome);
  if (/text$|^text/.test(fim)) return alvo === 'forms' ? t.rotulo[n % t.rotulo.length] : t.titulos[n % t.titulos.length];
  if (/id$|slug|form_id|grupo|ordenacao|css|class|type|tipo|required|checked|selected|disabled|active|index|numero|number|dot/.test(fim)) return /index|numero|number/.test(fim) ? String(n + 1) : '';
  return '';
}

// Lousa de amostra (modelos de lousa e de página de lousa): desenha o arranjo em grade de 12 colunas. Objeto de texto
// e botão saem com o próprio texto; widget vira um cartão com o título e linhas de conteúdo.
function lousaAmostra(itens) {
  const esc = v => String(v == null ? '' : v).replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));
  const caixas = (itens || []).map((item, n) => {
    const largura = Math.max(2, Math.min(12, Number(item.width) || 4)), alto = Math.max(60, Math.min(260, (Number(item.height_px) || 220) * 0.5));
    const o = item.object || {};
    let dentro;
    if (item.id === 'objeto' && o.type === 'button') dentro = '<div style="display:flex;height:100%;align-items:center;justify-content:center"><span style="padding:10px 26px;border-radius:999px;background:' + esc(o.fill || '#0284c7') + ';color:' + esc(o.color || '#fff') + ';font-weight:700;font-size:18px">' + esc(o.text) + '</span></div>';
    else if (item.id === 'objeto') dentro = '<div style="display:flex;height:100%;align-items:center;justify-content:center;text-align:center;color:' + esc(o.color || '#0f172a') + ';font-weight:' + (Number(o.weight) === 400 ? 400 : 800) + ';font-size:' + Math.max(15, Math.min(40, (Number(o.size) || 28) * 0.8)) + 'px;line-height:1.15">' + esc(o.text) + '</div>';
    else {
      const [a, b] = CORES[n % CORES.length];
      dentro = '<div style="height:100%;border:1px solid #e2e8f0;border-radius:12px;background:#fff;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.06)"><div style="padding:9px 12px;border-bottom:1px solid #e2e8f0;font-weight:600;font-size:14px;color:#0f172a">' + esc((item.options && item.options.title) || item.name || item.id)
        + '</div><div style="padding:12px"><div style="height:' + Math.round(alto * 0.34) + 'px;border-radius:8px;background:linear-gradient(135deg,' + a + ',' + b + ');opacity:.85"></div><div style="height:8px;margin-top:10px;border-radius:4px;background:#e2e8f0;width:82%"></div><div style="height:8px;margin-top:7px;border-radius:4px;background:#e2e8f0;width:58%"></div></div></div>';
    }
    return '<div style="grid-column:span ' + largura + ';height:' + alto + 'px;min-width:0">' + dentro + '</div>';
  }).join('');
  return '<div style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:14px;font-family:system-ui,sans-serif">' + caixas + '</div>';
}
const LOUSA_PADRAO = [{id: 'menus', name: 'Menus', width: 4, height_px: 300}, {id: 'pages-index', name: 'Índice', width: 8, height_px: 300}, {id: 'galleries', name: 'Galeria', width: 6, height_px: 260}, {id: 'forms', name: 'Formulário', width: 6, height_px: 260}];

function amostra(html, lingua, alvo) {
  const t = TEXTOS[lingua] || TEXTOS['pt-br'];
  // Modelo de lousa: o conteúdo é o arranjo em JSON. Modelo de página de lousa: a moldura recebe uma lousa de amostra.
  if (alvo === 'dashboard-boards') {
    let arranjo = null;
    try { arranjo = JSON.parse(html); } catch (e) { /* conteúdo que não é arranjo fica em branco */ }
    return '<div style="max-width:940px;margin:0 auto;padding:8px">' + lousaAmostra(arranjo && arranjo.widgets) + '</div>';
  }
  if (alvo === 'dashboard-pages') html = html.split('[[lousa#widget]]').join(lousaAmostra(LOUSA_PADRAO)).split('[[lousa#titulo]]').join(t.titulos[0]);
  let contador = 0;
  const preencher = (trecho, n) => trecho.replace(/@?\[\[([^\[\]]+)\]\]@?/g, (_, chave) => valor(chave, n, t, alvo));
  // Vários passes: bloco dentro de bloco.
  const bloco = /<!--\s*([\w#-]+)\s*<\s*-->([\s\S]*?)<!--\s*\1\s*>\s*-->/;
  for (let passe = 0; passe < 400 && bloco.test(html); passe++) {
    html = html.replace(bloco, (_, nome, dentro) => {
      if (SAI.test(nome) || ((alvo === 'forms' || alvo === 'forms-search') && SUBMOLDE.test(nome))) return '';
      if (nome in REPETE) {
        const vezes = REPETE[nome] || ITENS[alvo] || 4;
        let saida = '';
        for (let i = 0; i < vezes; i++) {
          let um = dentro;
          // Formulário: cada campo do modelo traz um bloco por tipo; na amostra, um tipo por campo.
          if (alvo === 'forms' || alvo === 'forms-search') { const fica = CAMPOS[i % CAMPOS.length]; um = um.replace(/<!--\s*(type-[\w-]+)\s*<\s*-->([\s\S]*?)<!--\s*\1\s*>\s*-->/g, (m, tipo, corpo) => tipo === fica ? corpo : ''); }
          saida += preencher(um, contador++);
        }
        return saida;
      }
      return dentro;
    });
  }
  return preencher(html, 0).replace(/<script[\s\S]*?<\/script>/gi, '').replace(/\ssrc=""/g, ' src="' + imagem(0) + '"');
}

(async () => {
  const modelos = fs.readFileSync(process.env.MODELOS, 'utf8').split(/\r?\n/).filter(l => l.trim().startsWith('{')).map(l => JSON.parse(l));
  const so = process.env.SO ? process.env.SO.split(',') : null;
  const lista = modelos.filter(m => m.target !== 'galleries-estados' && (!so || so.includes(m.id) || so.includes(m.target)));
  fs.mkdirSync(saida, {recursive: true});
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1600, height: 1100}});
  await ctx.addCookies(ler(process.env.C2F_COOKIES));
  const page = await ctx.newPage();
  // Um quadro de prévia por framework: o editor de um modelo daquele framework já traz os estilos de base.
  let aberto = null;
  const relatorio = [];
  for (const framework of ['tailwindcss', 'fomantic-ui']) {
    const grupo = lista.filter(m => (m.framework || 'fomantic-ui') === framework);
    if (!grupo.length) continue;
    const porta = modelos.find(m => (m.framework || 'fomantic-ui') === framework && m.language === 'pt-br');
    await page.goto(base + '/admin-templates/editar/?id=' + encodeURIComponent(porta.id), {waitUntil: 'networkidle'});
    await page.waitForSelector('#iframe-visualizacao-pagina', {timeout: 20000});
    await page.waitForTimeout(3000);
    await page.evaluate(([w, h]) => { const f = document.getElementById('iframe-visualizacao-pagina'); f.style.cssText += ';width:' + w + 'px !important;height:' + h + 'px !important;max-width:none !important;border:0 !important;border-radius:0 !important;position:fixed !important;left:0;top:0;z-index:2147483647;background:#fff'; }, [LARGURA, ALTURA]);
    const quadro = page.frames().find(f => f.name() === 'iframe-visualizacao-pagina') || await (await page.$('#iframe-visualizacao-pagina')).contentFrame();
    for (const m of grupo) {
      let html = amostra(m.html || '', m.language, m.target);
      // O aviso de cookies nasce escondido e é mostrado pelo script do widget; na amostra ele fica à mostra.
      if (m.target === 'cookie-consent') html = html.replace(/(<section[^>]*data-cc-banner[^>]*?)\shidden(?=[\s>])/, '$1') + '<style>[data-cc-banner]{position:static !important;margin:0 !important;max-width:420px}</style>';
      const medida = await quadro.evaluate(([h, css, cheio, escuro]) => {
        document.querySelectorAll('style[data-amostra]').forEach(s => s.remove());
        const estilo = document.createElement('style'); estilo.setAttribute('data-amostra', '1'); estilo.textContent = css || ''; document.head.appendChild(estilo);
        document.body.innerHTML = '<div id="amostra-palco">' + h + '</div>';
        document.documentElement.scrollTop = 0; document.body.scrollTop = 0;
        const palco = document.getElementById('amostra-palco');
        document.body.style.cssText = 'margin:0;overflow:hidden' + (escuro ? ';background:#0f1c34' : '');
        return new Promise(r => setTimeout(() => {
          // Enquadramento: mede a caixa do que está pintado (texto, imagem, campo, fundo, borda), amplia até caber com
          // margem e centra no quadro. A largura do layout não muda, então nada quebra de linha por causa da ampliação.
          const W = window.innerWidth, H = window.innerHeight, MARGEM = 40, MAXIMO = 1.6;
          let x0, y0, x1, y1;
          const pintado = el => {
            if (/^(IMG|SVG|INPUT|TEXTAREA|SELECT|BUTTON|VIDEO|CANVAS|HR)$/i.test(el.tagName)) return true;
            for (const n of el.childNodes) if (n.nodeType === 3 && n.textContent.trim()) return true;
            const e = getComputedStyle(el);
            if (e.backgroundImage !== 'none') return true;
            const cor = e.backgroundColor.match(/[\d.]+/g) || [];
            if (cor.length >= 3 && (cor.length < 4 || Number(cor[3]) > 0.02) && !(escuro ? false : cor.slice(0, 3).every(v => Number(v) >= 250))) return true;
            return ['Top', 'Right', 'Bottom', 'Left'].some(l => parseFloat(e['border' + l + 'Width']) > 0 && e['border' + l + 'Style'] !== 'none');
          };
          const medir = () => {
            x0 = Infinity; y0 = Infinity; x1 = -Infinity; y1 = -Infinity;
            palco.querySelectorAll('*').forEach(el => {
              const e = getComputedStyle(el);
              if (e.display === 'none' || e.visibility === 'hidden' || Number(e.opacity) === 0 || !pintado(el)) return;
              const c = el.getBoundingClientRect();
              if (c.width < 2 || c.height < 2) return;
              x0 = Math.min(x0, c.left); y0 = Math.min(y0, c.top); x1 = Math.max(x1, c.right); y1 = Math.max(y1, c.bottom);
            });
            x0 = Math.max(0, x0); y0 = Math.max(0, y0); x1 = Math.min(W, x1);
            return x1 > x0 && y1 > y0;
          };
          if (escuro) palco.style.cssText = 'padding:32px;box-sizing:border-box';
          let tem = medir();
          // Faixa de largura total e baixa (barra de navegação, rodapé, barra lateral): o palco estreita para a ampliação
          // ter o que ampliar. As consultas de mídia continuam vendo a janela inteira.
          if (!cheio && tem && x1 - x0 >= W * 0.9 && y1 - y0 < H * 0.5) { palco.style.cssText += ';width:' + Math.round(W / MAXIMO) + 'px'; tem = medir(); }
          const alto = palco.getBoundingClientRect().height;
          if (!cheio && tem) {
            const largo = x1 - x0, altura = y1 - y0;
            // Mostra a área útil inteira: amplia o que é pequeno e reduz o que passa do quadro, sempre centrado e com
            // margem. Conteúdo muito mais alto que o quadro (lista longa) fica no tamanho natural, ancorado no topo.
            const longo = altura > H * 1.9;
            const cabe = Math.min((W - 2 * MARGEM) / largo, (H - 2 * MARGEM) / altura);
            // Na largura, tudo cabe com margem, inclusive a lista longa: cortar embaixo é aceito, cortar dos lados não.
            const z = longo ? Math.max(0.5, Math.min(MAXIMO, (W - 2 * MARGEM) / largo)) : Math.max(0.5, Math.min(MAXIMO, cabe));
            {
              const tx = (W - largo * z) / 2 - x0 * z;
              const ty = longo ? MARGEM - y0 * z : (H - altura * z) / 2 - y0 * z;
              palco.style.cssText += ';transform-origin:0 0;transform:translate(' + tx + 'px,' + ty + 'px) scale(' + z + ')';
            }
          }
          setTimeout(() => r({alto: Math.round(alto), texto: document.body.innerText.trim().length, cru: (document.body.innerText.match(/\[\[[^\]]+\]\]/g) || []).slice(0, 5)}), 700);
        }, 900));
      }, [html, m.css || '', m.target === 'layouts', /^conn2flow-checkout|^presentations-slide-(closing|opening|three-points)$/.test(m.id)]);
      const arquivo = path.join(saida, m.language, m.id + '.png');
      fs.mkdirSync(path.dirname(arquivo), {recursive: true});
      await page.screenshot({path: arquivo, clip: {x: 0, y: 0, width: LARGURA, height: ALTURA}});
      relatorio.push({id: m.id, language: m.language, target: m.target, framework, project: m.project || null, alto: medida.alto, texto: medida.texto, cru: medida.cru});
      process.stdout.write('.');
    }
  }
  fs.writeFileSync(path.join(saida, 'relatorio.json'), JSON.stringify(relatorio, null, 1));
  console.log('\n' + relatorio.length + ' modelos fotografados; com marcador sobrando: ' + relatorio.filter(r => r.cru.length).length + '; com pouco texto: ' + relatorio.filter(r => r.texto < 20).map(r => r.id + ':' + r.language).join(' '));
  await browser.close();
})().catch(e => { console.error(e); process.exit(2); });
