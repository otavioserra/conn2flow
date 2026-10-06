import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it } from 'vitest';

/**
 * req-242 (BATCH-251) — refinamentos do painel Tailwind:
 * select que flutua fora de container que recorta, bandeja de miniaturas do seletor de arquivos e
 * as regras de folha que sustentam os demais ajustes (cursor em rótulos, abas, capa ou ícone).
 */
const ler = (caminho) => readFileSync(resolve(process.cwd(), caminho), 'utf8');

function controles() {
  delete window.c2fControles;
  delete window.jQuery;
  vm.runInThisContext(ler('gestor/assets/interface/controles.js'), { filename: 'controles.js' });
  return window.c2fControles;
}

describe('select em container que recorta (req-242)', () => {
  it('fora de container com overflow o painel continua absoluto, sem medidas inline', () => {
    const c = controles();
    document.body.innerHTML = '<div id="livre"><select><option value="a">Alfa</option><option value="b">Beta</option></select></div>';
    c.select(document.querySelector('select'));
    document.querySelector('.c2fc-select-gatilho').click();
    const painel = document.querySelector('.c2fc-select-painel');
    expect(painel.classList.contains('c2fc-oculto')).toBe(false);
    expect(painel.classList.contains('c2fc-flutuante')).toBe(false);
    expect(painel.style.left).toBe('');
  });

  it('dentro de tabela com rolagem o painel vira fixo na medida do gatilho e volta ao fechar', () => {
    const c = controles();
    document.body.innerHTML = '<div style="overflow-x:auto"><table><tbody><tr><td><select><option value="a">Alfa</option><option value="b">Beta</option></select></td></tr></tbody></table></div>';
    const s = c.select(document.querySelector('select'));
    const raiz = document.querySelector('.c2fc-select');
    raiz.getBoundingClientRect = () => ({ left: 40, top: 100, bottom: 138, right: 240, width: 200, height: 38 });
    document.querySelector('.c2fc-select-gatilho').click();
    const painel = document.querySelector('.c2fc-select-painel');
    expect(painel.classList.contains('c2fc-flutuante')).toBe(true);
    expect(painel.style.left).toBe('40px');
    expect(painel.style.width).toBe('200px');
    expect(painel.style.top).toBe('142px');
    // a rolagem da página reposiciona enquanto está aberto
    raiz.getBoundingClientRect = () => ({ left: 40, top: 60, bottom: 98, right: 240, width: 200, height: 38 });
    window.dispatchEvent(new Event('scroll'));
    expect(painel.style.top).toBe('102px');
    s.fechar(false);
    expect(painel.classList.contains('c2fc-oculto')).toBe(true);
    raiz.getBoundingClientRect = () => ({ left: 40, top: 10, bottom: 48, right: 240, width: 200, height: 38 });
    window.dispatchEvent(new Event('scroll'));
    expect(painel.style.top).toBe('102px');
  });

  it('sem espaço embaixo o painel flutuante abre para cima', () => {
    const c = controles();
    document.body.innerHTML = '<div style="overflow:hidden"><select><option value="a">Alfa</option></select></div>';
    c.select(document.querySelector('select'));
    const raiz = document.querySelector('.c2fc-select');
    const altura = window.innerHeight;
    raiz.getBoundingClientRect = () => ({ left: 0, top: altura - 60, bottom: altura - 22, right: 200, width: 200, height: 38 });
    document.querySelector('.c2fc-select-gatilho').click();
    const painel = document.querySelector('.c2fc-select-painel');
    expect(painel.classList.contains('c2fc-acima')).toBe(true);
    expect(painel.style.top).toBe('auto');
    expect(painel.style.bottom).toBe('64px');
  });
});

describe('bandeja do seletor de arquivos (req-242)', () => {
  function helpers() {
    const modulo = { exports: {} };
    const jqueryFalso = () => ({ ready: () => {}, length: 0 });
    // eslint-disable-next-line no-new-func
    new Function('module', 'exports', '$', 'window', ler('gestor/modulos/admin-arquivos/admin-arquivos.js'))(modulo, modulo.exports, jqueryFalso, globalThis);
    return modulo.exports;
  }

  it('uma miniatura por arquivo, com o caminho no botão de remover e o nome escapado', () => {
    const { adminArquivosBandejaHtml, adminArquivosArquivosSelecionados } = helpers();
    const selecionados = {
      'fotos/a.png': { caminho: 'fotos/a.png', tipo: 'arquivo', nome: 'a.png', imgSrc: '/mini/a.png' },
      'fotos': { caminho: 'fotos', tipo: 'pasta', nome: 'fotos' },
      'fotos/<b>.png': { caminho: 'fotos/<b>.png', tipo: 'arquivo', nome: '<b>.png', imgSrc: '/mini/b.png' },
    };
    const html = adminArquivosBandejaHtml(adminArquivosArquivosSelecionados(selecionados), 'Remover');
    const caixa = document.createElement('div');
    caixa.innerHTML = html;
    const miniaturas = caixa.querySelectorAll('.c2f-pick-thumb');
    expect(miniaturas).toHaveLength(2);
    expect(miniaturas[0].querySelector('img').getAttribute('src')).toBe('/mini/a.png');
    expect(miniaturas[0].querySelector('.c2f-pick-thumb-remove').getAttribute('data-caminho')).toBe('fotos/a.png');
    // o nome do arquivo nunca vira marcação
    expect(html).toContain('title="&lt;b&gt;.png"');
    expect(html).toContain('aria-label="Remover: &lt;b&gt;.png"');
    expect(html).not.toContain('<b>');
    expect(caixa.querySelector('b')).toBeNull();
  });

  it('lista vazia não desenha nada', () => {
    expect(helpers().adminArquivosBandejaHtml([], 'Remover')).toBe('');
  });
});

describe('folhas e marcação (req-242)', () => {
  const folha = ler('gestor/assets/interface/controles.css');

  it('rótulo de checkbox e rádio mostra cursor de clique', () => {
    expect(folha).toMatch(/label:has\(input\[type="checkbox"\]:not\(:disabled\)\)[^{]*\{ cursor: pointer; \}/);
    expect(folha).toContain('input[type="radio"]:not(:disabled) + label');
  });

  it('modal de tela cheia com iframe não rola por fora', () => {
    expect(folha).toContain('.c2fc-ponte-modal.fullscreen { overflow: hidden; }');
    // REQ-243: a regra vale só para o iframe dentro de `.iframe-container` (o palco do editor visual fica fora).
    expect(folha).toContain('.c2fc-ponte-modal.fullscreen > .content .iframe-container iframe { flex: 1;');
    expect(folha).not.toContain('.c2fc-ponte-modal.fullscreen > .content iframe {');
  });

  it('nenhuma página pinta de azul a aba ativa', () => {
    for (const modulo of ['forms', 'forms-search', 'forms-submissions', 'galleries', 'menus', 'cookie-consent']) {
      for (const idioma of ['pt-br', 'en']) {
        const pagina = modulo === 'forms-submissions' ? 'forms-submissions-view' : (modulo === 'cookie-consent' ? 'cookie-consent-editar' : modulo + '-editar');
        const css = ler(`gestor/modulos/${modulo}/resources/${idioma}/pages/${pagina}/${pagina}.css`);
        expect(css, `${modulo} ${idioma}`).not.toMatch(/\.c2fc-aba\.active\s*\{[^}]*background/);
      }
    }
  });

  it('as duas abas do dashboard têm a mesma marcação de estado e o ícone do card não força o próprio display', () => {
    for (const idioma of ['pt-br', 'en']) {
      const html = ler(`gestor/modulos/dashboard/resources/${idioma}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html`);
      const abas = html.match(/<button type="button" id="dashboard-tab-btn-(?:modulos|widgets)" class="([^"]*)"/g);
      expect(abas).toHaveLength(2);
      const classes = abas.map((a) => a.replace(/.*class="/, '').replace('"', '').replace(' active', ''));
      expect(classes[0]).toBe(classes[1]);
      expect(html).not.toMatch(/dashboard-card-svg[^>]*display:\s*flex\s*!important/);
      const css = ler(`gestor/modulos/dashboard/resources/${idioma}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.css`);
      expect(css).toContain('.dashboard-module-card.has-cover .dashboard-card-svg { display: none !important; }');
      expect(css).toContain('.dashboard-tab-trigger:hover:not(.active)');
    }
  });

  it('favoritos da topbar ficam à direita sem depender de breakpoint', () => {
    for (const idioma of ['pt-br', 'en']) {
      const topbar = ler(`gestor/resources/${idioma}/components/admin-topbar-tailwind/admin-topbar-tailwind.html`);
      expect(topbar).not.toContain('sm:justify-start');
      expect(ler(`gestor/resources/${idioma}/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.css`)).toContain('[data-admin-topbar] > nav { justify-content: flex-end; }');
    }
  });
});
