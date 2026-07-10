/**
 * _smoke_batch079.mjs — Smoke do motor de mapeamento in-place do Live Editor (BATCH-079 / rev.2).
 *
 * Modelo NOVO (ideia do Engenheiro Chefe): no modo de edição o `gestor_pagina_widgets` PRESERVA os
 * comentários `<!-- widgets#SIG < --> … <!-- widgets#SIG > -->` ao redor do render, então o DOM vivo
 * carrega a FRONTEIRA EXATA de cada widget. O `mapTree` marca cada widget pelos próprios comentários
 * (sem heurística de alinhamento). Valida:
 *   - Widget que é o ÚNICO conteúdo de um contêiner (`<nav>`) → marca o PAI; save reconstrói o
 *     marcador no innerHTML do pai (preserva a tag externa).
 *   - Dois widgets idênticos consecutivos no mesmo `<nav>` → widgets SEPARADOS (ids distintos),
 *     ambos por-elemento (nenhum "preenche" o nav sozinho).
 *   - Widget + nó estático irmão → widget por-elemento; o estático é preservado.
 *
 * Carrega o ARQUIVO REAL `dashboard.toolbar.js` via hook de teste injetado antes do fecho da IIFE.
 * Uso: node _smoke_batch079.mjs
 */
import { Window } from 'happy-dom';
import fs from 'node:fs';

const window = new Window();
const document = window.document;
globalThis.window = window;
globalThis.document = document;
if (!window.btoa) window.btoa = (s) => Buffer.from(s, 'binary').toString('base64');
if (!window.atob) window.atob = (s) => Buffer.from(s, 'base64').toString('binary');

let code = fs.readFileSync('gestor/modulos/dashboard/dashboard.toolbar.js', 'utf8');
const idx = code.lastIndexOf('})();');
if (idx < 0) { console.error('IIFE close não encontrado'); process.exit(1); }
const hook = 'window.__c2fToolbar={runMap:function(root,backup){varMap={};varSeq=0;mapRoot=root;' +
    'mapTree(root,backup);return varMap;},reconstruct:function(c){return reconstructOriginal(c);}};\n';
code = code.slice(0, idx) + hook + code.slice(idx);
(0, eval)(code);

const T = window.__c2fToolbar;
if (!T || typeof T.runMap !== 'function') { console.error('hook de teste não exposto'); process.exit(1); }

let pass = 0, fail = 0;
function check(name, cond) {
    if (cond) { pass++; console.log('  ok  - ' + name); }
    else { fail++; console.log('  FAIL- ' + name); }
}
function el(html, id) {
    const d = document.createElement('div');
    if (id) d.id = id;
    d.innerHTML = html;
    return d;
}
function commentCount(node) {
    let n = 0;
    node.childNodes.forEach((c) => { if (c.nodeType === 8) n++; });
    return n;
}

const OPEN = (s) => '<!-- widgets#' + s + ' < -->';
const CLOSE = (s) => '<!-- widgets#' + s + ' > -->';

// ===== Cenário A — widget é o único conteúdo do <nav> → mapeamento no PAI.
(function scenarioA() {
    const sig = 'menus->render({"grupo_slug":"main"})';
    // DOM VIVO (edit mode): comentários preservados ao redor do render (3 links).
    const root = el('<nav id="mainnav">' + OPEN(sig) + '<a href="/a">A</a><a href="/b">B</a><a href="/c">C</a>' + CLOSE(sig) + '</nav>', 'c2f-page-content');
    // CRU (banco): comentários ao redor do mockup.
    const backup = el('<nav id="mainnav">' + OPEN(sig) + '<a>mock1</a><a>mock2</a>' + CLOSE(sig) + '</nav>');

    T.runMap(root, backup);
    const nav = root.querySelector('#mainnav');
    const anchors = root.querySelectorAll('#mainnav > a');

    console.log('Cenário A — widget único preenche o <nav> → PAI:');
    check('nav marcado como widget-parent', nav.getAttribute('data-c2f-widget-parent') === '1');
    check('nav é widget-root (borda/label)', nav.getAttribute('data-c2f-widget-root') === '1');
    check('nav tem data-widget-slug=main', nav.getAttribute('data-widget-slug') === 'main');
    check('os <a> filhos NÃO recebem widget-id', Array.prototype.every.call(anchors, (a) => !a.getAttribute('data-c2f-widget-id')));
    check('comentários do widget removidos do DOM vivo', commentCount(nav) === 0);

    const out = T.reconstruct(root);
    check('reconstrução restaura o marcador cru (com mockup)', out.indexOf('<a>mock1</a>') !== -1);
    check('reconstrução preserva a tag externa <nav id="mainnav">', out.indexOf('<nav id="mainnav">') !== -1);
    check('reconstrução NÃO mantém o render vivo (>A</a>)', out.indexOf('>A</a>') === -1);
    check('reconstrução limpa as anotações data-c2f-*', out.indexOf('data-c2f-widget') === -1);
})();

// ===== Cenário B — dois widgets idênticos consecutivos no mesmo <nav> → SEPARADOS.
(function scenarioB() {
    const sig = 'menus->render({"grupo_slug":"padrao"})';
    const root = el('<nav id="nav2">' +
        OPEN(sig) + '<a>A1</a><a>A2</a>' + CLOSE(sig) +
        OPEN(sig) + '<a>B1</a><a>B2</a>' + CLOSE(sig) + '</nav>', 'c2f-page-content');
    const backup = el('<nav id="nav2">' +
        OPEN(sig) + '<a>m</a>' + CLOSE(sig) +
        OPEN(sig) + '<a>m</a>' + CLOSE(sig) + '</nav>');

    T.runMap(root, backup);
    const links = root.querySelectorAll('#nav2 > a');
    const ids = Array.prototype.map.call(links, (a) => a.getAttribute('data-c2f-widget-id'));
    const distintos = new Set(ids.filter(Boolean));

    console.log('Cenário B — dois widgets idênticos consecutivos no <nav>:');
    check('todos os 4 links marcados com widget-id', ids.every(Boolean) && ids.length === 4);
    check('DOIS ids de widget distintos (não fundidos)', distintos.size === 2);
    check('nav NÃO virou widget-parent (tem 2 widgets)', root.querySelector('#nav2').getAttribute('data-c2f-widget-parent') !== '1');
    check('primeiro link de cada widget é raiz', links[0].getAttribute('data-c2f-widget-root') === '1' && links[2].getAttribute('data-c2f-widget-root') === '1');

    const out = T.reconstruct(root);
    check('reconstrução gera 2 marcadores de abertura', (out.match(/ < -->/g) || []).length === 2);
    check('reconstrução gera 2 marcadores de fechamento', (out.match(/ > -->/g) || []).length === 2);
})();

// ===== Cenário C — widget + nó estático irmão → widget por-elemento; estático preservado.
(function scenarioC() {
    const sig = 'menus->render({"grupo_slug":"padrao"})';
    const root = el('<div id="wrap">' + OPEN(sig) + '<a>A</a><a>B</a>' + CLOSE(sig) + '<footer id="ft">F</footer></div>', 'c2f-page-content');
    const backup = el('<div id="wrap">' + OPEN(sig) + '<a>m</a>' + CLOSE(sig) + '<footer id="ft">F</footer></div>');

    T.runMap(root, backup);
    const wrap = root.querySelector('#wrap');
    const footer = root.querySelector('#ft');
    const links = root.querySelectorAll('#wrap > a');

    console.log('Cenário C — widget + rodapé estático (mesmo contêiner):');
    check('wrap NÃO virou widget-parent (há conteúdo estático)', wrap.getAttribute('data-c2f-widget-parent') !== '1');
    check('links do widget marcados', links.length === 2 && links[0].getAttribute('data-c2f-widget-root') === '1');
    check('rodapé estático NÃO marcado', !footer.getAttribute('data-c2f-widget-id') && !footer.getAttribute('data-c2f-marker'));

    const out = T.reconstruct(root);
    check('reconstrução mantém o rodapé intacto', out.indexOf('<footer id="ft">F</footer>') !== -1);
    check('reconstrução tem 1 marcador de widget', (out.match(/ < -->/g) || []).length === 1);
})();

console.log('\nResultado: ' + pass + ' ok, ' + fail + ' fail (' + (pass + fail) + ' checks)');
process.exit(fail === 0 ? 0 : 1);
