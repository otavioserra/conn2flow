import { describe, expect, it } from 'vitest';
import fs from 'node:fs';

const sourcePath = 'gestor/assets/interface/html-editor-interface.js';
const code = fs.readFileSync(sourcePath, 'utf8');

const CONSTS =
  "const HTML_EDITOR_CHROME_LAYER = 'c2f-editor-chrome';" +
  "const HTML_EDITOR_TAILWIND_LAYERS = ['properties','theme','base','components','utilities'];";

const MAPA = {
  'jquery': { 'jquery.min.js': '/vendor/jquery/3.7.1/jquery.min.js' },
  'fomantic-ui': {
    'semantic.min.css': '/vendor/fomantic-ui/2.9.4/semantic.min.css',
    'semantic.min.js': '/vendor/fomantic-ui/2.9.4/semantic.min.js',
  },
  'codemirror': { 'codemirror.min.css': '/vendor/codemirror/5.65.20/codemirror.min.css' },
  'tailwindcss-browser': { 'dist/index.global.js': '/vendor/tailwindcss-browser/4.3.0/dist/index.global.js' },
};

// `assetsUrls` é publicado UMA vez, no objeto `gestor` de toda página (gestor.php), e alcançado
// por `window.gestorAssets` (global.js). O ambiente reproduz os dois caminhos.
const AMBIENTE =
  CONSTS +
  'const gestor = { assetsUrls: ' + JSON.stringify(MAPA) + ' };' +
  'const window = { gestor };' +
  "function htmlEditorAssetUrl(b, a) { return (gestor.assetsUrls[b] || {})[a] || ''; }" +
  "function htmlEditorBaseScripts() { return '<scr' + 'ipt src=\"' + htmlEditorAssetUrl('jquery', 'jquery.min.js') + '\"></scr' + 'ipt>'; }";

function extrairFuncao(nome, dependencias = '') {
  const start = code.indexOf('function ' + nome + '(');
  if (start < 0) throw new Error(nome + ' não encontrada no arquivo');
  let i = code.indexOf('{', start), depth = 0, end = -1;
  for (; i < code.length; i++) {
    if (code[i] === '{') depth++;
    else if (code[i] === '}') {
      depth--;
      if (depth === 0) { end = i + 1; break; }
    }
  }
  return new Function(dependencias + '\n' + code.slice(start, end) + '\nreturn ' + nome + ';')();
}

// Medido em Chromium sobre a página real, contra o preview: o Fomantic sem camada levava o título
// de 72px para 24px, o peso de 900 para 700 e o texto do CTA de branco para rgb(65,131,196).
describe('html-editor-interface.js — paridade visual do editor visual (req-156)', () => {
  it('não deixa a folha do Fomantic sem camada no iframe Tailwind do editor visual', () => {
    const includes = extrairFuncao('htmlEditorVisualFrameworkIncludes', AMBIENTE);
    const saida = includes('tailwindcss');

    // A folha PRECISA continuar no documento: `#html-editor-modal` é um modal Fomantic, e
    // `html-editor.js` chama `.modal()` sobre ele. O que não pode é ela reger o conteúdo.
    expect(saida).toContain('semantic.min.css');
    expect(saida).toContain('layer(c2f-editor-chrome)');
    expect(saida).not.toMatch(/<link[^>]+semantic\.min\.css/);
  });

  // `html{font-size:14px}` do Fomantic encolhe TODA medida `rem` do Tailwind por 14/16 = 0,875:
  // 72->63px, 128->112px, 48->42px. Nenhuma camada corrige, porque o Tailwind não tem regra
  // concorrendo por `html { font-size }` — a do Fomantic vence por ausência de disputa.
  it('restaura a unidade rem que o Fomantic redefine, fora de camada', () => {
    const includes = extrairFuncao('htmlEditorVisualFrameworkIncludes', AMBIENTE);
    const saida = includes('tailwindcss');

    expect(saida).toContain('html{font-size:16px}');
    expect(saida).not.toMatch(/@layer[^{]*\{[^}]*html\{font-size:16px\}/);
  });

  it('mantém o Fomantic regendo normalmente quando o framework é fomantic-ui', () => {
    const includes = extrairFuncao('htmlEditorVisualFrameworkIncludes', AMBIENTE);
    const saida = includes('fomantic-ui');

    expect(saida).toMatch(/<link[^>]+semantic\.min\.css/);
    expect(saida).not.toContain('layer(c2f-editor-chrome)');
    expect(saida).not.toContain('html{font-size:16px}');
  });

  it('declara a ordem das camadas com o chrome do editor antes das do Tailwind', () => {
    const ordem = extrairFuncao('htmlEditorLayerOrderDeclaration', CONSTS);
    const saida = ordem('tailwindcss');

    expect(saida).toContain('@layer c2f-editor-chrome, properties, theme, base, components, utilities;');
    // Fora do Tailwind não há cascata estratificada para ordenar.
    expect(ordem('fomantic-ui')).toBe('');
  });

  it('usa as funções de isolamento nos dois pontos de montagem do editor visual', () => {
    const start = code.indexOf('function editorHtmlVisualConteudo(');
    const end = code.indexOf('function htmlEditorRenderVars(', start);
    const editor = code.slice(start, end);

    // Ramo de páginas/componentes e ramo de layouts.
    expect(editor.match(/htmlEditorVisualFrameworkIncludes\(framework\)/g)).toHaveLength(2);
    expect(editor.match(/htmlEditorLayerOrderDeclaration\(framework\)/g)).toHaveLength(2);
    // A regressão que este teste trava: o `<link>` cru que o BATCH-156 removeu do preview mas
    // deixou no editor visual, criando o "ambiente intermediário" do relato.
    expect(editor).not.toMatch(/<link[^>]+semantic\.min\.css/);
  });

  it('declara a ordem das camadas também no preview, para a cascata não depender de posição', () => {
    const start = code.indexOf('function previewHtmlConteudo(');
    const end = code.indexOf('function previewHtml()', start);
    const preview = code.slice(start, end);

    expect(preview.match(/htmlEditorLayerOrderDeclaration\(framework\)/g)).toHaveLength(2);
  });
});

// DEC-122: assets de terceiros são servidos do disco; o CDN é fallback do registro, nunca uma URL
// escrita no código. Os iframes do editor escaparam do BATCH-146 porque montam as tags no CLIENTE.
describe('html-editor-interface.js — assets de terceiros pelo registro (req-156)', () => {
  it('não carrega nenhuma biblioteca de CDN escrito no código', () => {
    const cdns = code.match(/https:\/\/(cdn\.jsdelivr\.net|cdnjs\.cloudflare\.com|unpkg\.com|ajax\.googleapis\.com|code\.jquery\.com)[^"'`\s]*/g);
    expect(cdns).toBeNull();
  });

  it('resolve a URL pelo helper global quando ele está disponível', () => {
    const url = extrairFuncao(
      'htmlEditorAssetUrl',
      CONSTS + 'const gestor = {};' +
      "const window = { gestorAssets: { url: (b, a) => '/vendor/' + b + '/' + a } };"
    );

    expect(url('tailwindcss-browser', 'dist/index.global.js')).toBe('/vendor/tailwindcss-browser/dist/index.global.js');
  });

  it('cai no objeto gestor quando o helper global não foi carregado', () => {
    const url = extrairFuncao('htmlEditorAssetUrl', CONSTS + 'const gestor = { assetsUrls: ' + JSON.stringify(MAPA) + ' }; const window = {};');

    expect(url('tailwindcss-browser', 'dist/index.global.js')).toBe('/vendor/tailwindcss-browser/4.3.0/dist/index.global.js');
    expect(url('fomantic-ui', 'semantic.min.css')).toBe('/vendor/fomantic-ui/2.9.4/semantic.min.css');
  });

  // Devolver '' em vez de um CDN embutido: a tag vazia falha de modo visível e rastreável,
  // enquanto uma URL remota de emergência recriaria em silêncio a dependência removida.
  it('devolve string vazia quando o registro não conhece o arquivo', () => {
    const url = extrairFuncao(
      'htmlEditorAssetUrl',
      CONSTS + 'const gestor = { assetsUrls: ' + JSON.stringify(MAPA) + ' }; const window = {}; const console = { warn() {} };'
    );

    expect(url('inexistente', 'x.js')).toBe('');
    expect(url('jquery', 'nao-registrado.js')).toBe('');
  });

  it('monta as tags do CodeMirror pelo registro, com codemirror.min.js à frente dos addons', () => {
    const includes = extrairFuncao(
      'htmlEditorCodemirrorIncludes',
      CONSTS +
      "const HTML_EDITOR_CODEMIRROR_CSS = ['codemirror.min.css'];" +
      "const HTML_EDITOR_CODEMIRROR_JS = ['codemirror.min.js', 'addon/edit/closetag.js'];" +
      "function htmlEditorAssetUrl(b, a) { return '/vendor/codemirror/5.65.20/' + a; }"
    );
    const saida = includes();

    expect(saida.indexOf('codemirror.min.js')).toBeLessThan(saida.indexOf('addon/edit/closetag.js'));
    expect(saida).toContain('/vendor/codemirror/5.65.20/codemirror.min.css');
  });

  it('lista no iframe apenas addons do CodeMirror que existem no registro do backend', () => {
    const registro = fs.readFileSync('gestor/bibliotecas/assets-externos.php', 'utf8');
    const bloco = registro.slice(registro.indexOf("'codemirror' => Array("), registro.indexOf("'fomantic-ui' => Array("));

    const usados = code.slice(code.indexOf('HTML_EDITOR_CODEMIRROR_CSS'), code.indexOf('function htmlEditorCodemirrorIncludes'));
    for (const arquivo of usados.match(/'([a-z0-9/.-]+\.(?:js|css))'/g) || []) {
      // Sem esta guarda, um addon fora do registro cai no CDN em silêncio — que é como
      // `assets_externos_url()` degrada quando o arquivo local não existe.
      expect(bloco, 'addon fora do registro: ' + arquivo).toContain(arquivo);
    }
  });
});

// req-160: o sidecar de um template inserido na sessão não pode entrar no baseline contra o qual a
// captura filtra. Se entrar, as regras do template somem do `css_compiled` — e como o runtime
// público não recebe o sidecar, a página publicada renderiza sem elas. Medido antes da correção:
// 8 utilities aplicadas sem regra na publicada, todas fornecidas por sidecar de template.
describe('html-editor-interface.js — baseline do runtime x overlay de sessão (req-160)', () => {
  function includes(baselineAtual, inicial) {
    return extrairFuncao(
      'tailwindPreviewIncludes',
      CONSTS +
      'const gestor = { html_editor: { cssPrecompiledBase64: "BASE64", assetsUrls: ' + JSON.stringify(MAPA) + ' } };' +
      'const window = { gestor, htmlEditorCssPrecompiled: ' + JSON.stringify(baselineAtual) + ' };' +
      'const htmlEditorDecodeBase64 = () => ' + JSON.stringify(inicial) + ';' +
      "function htmlEditorAssetUrl(b, a) { return (gestor.html_editor.assetsUrls[b] || {})[a] || ''; }"
    )();
  }

  it('sem inserção de template, emite só a folha de baseline', () => {
    const saida = includes('.layout{}', '.layout{}');

    expect(saida).toContain('data-c2f-tailwind-role="baseline"');
    expect(saida).not.toContain('session-overlay');
  });

  it('com template inserido, separa o sidecar do baseline do runtime', () => {
    const saida = includes('.layout{}\n.border-b-2{border-bottom-width:2px}', '.layout{}');

    // O baseline continua sendo APENAS o que o runtime entrega.
    const baseline = saida.slice(saida.indexOf('data-c2f-tailwind-role="baseline"'), saida.indexOf('session-overlay'));
    expect(baseline).toContain('.layout{}');
    expect(baseline).not.toContain('.border-b-2');

    // E o sidecar vai para uma folha própria, fora do filtro da captura.
    expect(saida).toContain('data-c2f-css-role="session-overlay"');
    expect(saida.slice(saida.indexOf('session-overlay'))).toContain('.border-b-2');
  });

  it('o overlay não carrega marca que o coletor de baseline reconheça', () => {
    const saida = includes('.layout{}\n.novo{}', '.layout{}');
    const overlay = saida.slice(saida.indexOf('session-overlay') - 40);

    // `baselineStyles()` seleciona `[data-c2f-tailwind-role="baseline"]` e `[data-tailwind-role]`.
    // Qualquer uma das duas marcas no overlay o devolveria ao filtro.
    const abertura = overlay.slice(0, overlay.indexOf('>') + 1);
    expect(abertura).not.toContain('data-c2f-tailwind-role');
    expect(abertura).not.toContain('data-tailwind-role');
  });
});

// req-160: o CSS AUTORAL do layout (a coluna `css` da tabela `layouts`) é servido pelo runtime e
// não chegava ao editor. `layout-conn2flow-site` traz `body { background: #000; }` — a página
// publicada saía com fundo preto e o editor com fundo claro, e toda seção transparente divergia.
describe('html-editor-interface.js — CSS autoral do layout no iframe (req-160)', () => {
  function includes(extra) {
    return extrairFuncao(
      'tailwindPreviewIncludes',
      CONSTS +
      'const gestor = { html_editor: Object.assign({ cssPrecompiledBase64: "B", assetsUrls: ' + JSON.stringify(MAPA) + ' }, ' + JSON.stringify(extra || {}) + ') };' +
      'const window = { gestor, htmlEditorCssPrecompiled: ".layout{}" };' +
      'const htmlEditorDecodeBase64 = v => (v === "AUTORAL" ? "body{background:#000}" : ".layout{}");' +
      "function htmlEditorAssetUrl(b, a) { return (gestor.html_editor.assetsUrls[b] || {})[a] || ''; }"
    )();
  }

  it('emite o CSS autoral do layout numa folha própria', () => {
    const saida = includes({ layoutCssAutoralBase64: 'AUTORAL' });

    expect(saida).toContain('data-c2f-css-role="layout-authored"');
    expect(saida).toContain('body{background:#000}');
  });

  it('mantém o autoral FORA do baseline que a captura filtra', () => {
    const saida = includes({ layoutCssAutoralBase64: 'AUTORAL' });
    const autoral = saida.slice(saida.indexOf('layout-authored') - 40);
    const abertura = autoral.slice(0, autoral.indexOf('>') + 1);

    // Dentro do baseline, a captura descartaria regras achando que já existem — o mesmo erro que
    // o sidecar de template causava. E o autoral não é derivado: não pertence àquele conjunto.
    expect(abertura).not.toContain('data-c2f-tailwind-role');
    expect(abertura).not.toContain('data-tailwind-role');
  });

  it('não emite a folha quando o layout não tem CSS autoral', () => {
    const saida = extrairFuncao(
      'tailwindPreviewIncludes',
      CONSTS +
      'const gestor = { html_editor: { cssPrecompiledBase64: "B", assetsUrls: ' + JSON.stringify(MAPA) + ' } };' +
      'const window = { gestor, htmlEditorCssPrecompiled: ".layout{}" };' +
      'const htmlEditorDecodeBase64 = (v) => v ? ".layout{}" : "";' +
      "function htmlEditorAssetUrl(b, a) { return (gestor.html_editor.assetsUrls[b] || {})[a] || ''; }"
    )();

    expect(saida).not.toContain('layout-authored');
  });

  // O layout pode ser trocado no select do CRUD a qualquer momento: o baseline entregue na abertura
  // deixa de valer, e salvar sob a cascata antiga gravaria um derivado que não é o da página.
  it('recarrega as duas camadas quando o layout é trocado no formulário', () => {
    const code = fs.readFileSync(sourcePath, 'utf8');
    const inicio = code.indexOf('function htmlEditorObservarTrocaDeLayout(');
    expect(inicio).toBeGreaterThan(-1);
    const bloco = code.slice(inicio, inicio + 2600);

    expect(bloco).toContain("select[name=\"layout\"]");
    expect(bloco).toContain('html-editor-layout-css');
    expect(bloco).toContain('htmlEditorLayoutCssAutoral');
    // O que a sessão acumulou (sidecars de template) tem de sobreviver à troca.
    expect(bloco).toContain('daSessao');
  });
});

// req-160: a captura só rodava no iframe do editor visual, porque `HtmlEditorCssCapture` vive no
// `html-editor.js` e ele não era injetado no preview. Quem montava a página pelos modelos e salvava
// gravava `css_compiled` VAZIO — medido: 360 classes aplicadas, 0 byte.
describe('html-editor-interface.js — motor de captura no preview (req-160)', () => {
  it('injeta o motor no iframe de preview sem ativar a UI de edição', () => {
    const code = fs.readFileSync(sourcePath, 'utf8');
    const inicio = code.indexOf('function previewHtmlConteudo(');
    const fim = code.indexOf('function previewHtml()', inicio);
    const preview = code.slice(inicio, fim);

    expect(preview).toContain('__c2fHtmlEditorNoAutoInit');
    expect(preview).toContain('htmlEditorScriptPath');
    // Página e layout: os dois ramos do preview capturam pelo mesmo caminho.
    expect(preview.match(/capturaScript/g).length).toBeGreaterThanOrEqual(3);
  });

  it('avisa quem espera a captura terminar, para o salvamento não usar tempo fixo', () => {
    const code = fs.readFileSync(sourcePath, 'utf8');
    const inicio = code.indexOf('function updateCSSCompiled(');
    const bloco = code.slice(inicio, inicio + 2400);

    expect(bloco).toContain('aoConcluir');
    expect(bloco).toContain('htmlEditorCssCompiledOrigem');
  });

  it('gera o CSS antes de enviar quando o HTML mudou depois da captura', () => {
    const code = fs.readFileSync(sourcePath, 'utf8');
    const inicio = code.indexOf('function htmlEditorInterceptarSubmitParaGerarCss(');
    expect(inicio).toBeGreaterThan(-1);
    const bloco = code.slice(inicio, inicio + 2600);

    // Capture phase: o handler do `formulario.js` é jQuery (bubble) e não chega a rodar.
    expect(bloco).toContain('stopImmediatePropagation');
    expect(bloco).toContain('visualizacao-pagina');
    expect(bloco).toContain('updateCSSCompiled');
  });

  it('não trava o salvamento de página sem classe Tailwind', () => {
    const code = fs.readFileSync(sourcePath, 'utf8');
    const inicio = code.indexOf('function htmlEditorCssCompiledDesatualizado(');
    const bloco = code.slice(inicio, inicio + 1600);

    // CSS vazio é legítimo em página sem utilities; exigir geração ali travaria um save correto.
    expect(bloco).toContain("frameworkCSS() !== 'tailwindcss'");
    expect(bloco).toContain('class');
  });
});
