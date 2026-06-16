/**
 * html-editor-modules.js — simulações específicas de módulo do Editor HTML (req-044 §5).
 *
 * Extraído de html-editor-interface.js para não inflá-lo. Contém as simulações de preview
 * dos alvos `menus`, `galleries` e `publisher*`. As funções são anexadas ao escopo global
 * (window) e referenciam, por nome nu (resolvido via window em runtime), as instâncias e
 * auxiliares expostas por html-editor-interface.js: CodeMirrorHtml, CodeMirrorHtmlExtraHead, 
 * publisher_fields_schema, frameworkCSS, previewHtml, regexVariaveisGlobal, alvoUsaItemVars.
 *
 * Deve ser carregado ANTES de html-editor-interface.js.
 */

// Estado interno da tabela de variáveis (usado apenas por publisherTableVariables).
let publisher_table_tr_skeleton = null;

// req-019: inclui link-custom com target `_blank` e separadores com/sem rótulo.
var MENUS_SIM_FALLBACK = [
    { type: 'pagina', label: 'Início', url: '/', target: '_self', page_id: 'inicio', children: [] },
    {
        type: 'cabecalho', label: 'Institucional', url: '#', children: [
            { type: 'pagina', label: 'Sobre Nós', url: '/sobre/', target: '_self', page_id: 'sobre', children: [] },
            { type: 'link-custom', label: 'Portal do Parceiro', url: 'https://parceiros.exemplo.com', target: '_blank', children: [] }
        ]
    },
    { type: 'separador', label: 'Mais opções', children: [] },
    { type: 'publicador', label: 'Últimas Notícias', url: '#', publisher_id: 'noticias', publisher_name: 'Notícias', count: 4, order_by: 'date_desc', children: [] },
    { type: 'separador', children: [] },
    { type: 'link-custom', label: 'Loja Online', url: 'https://loja.exemplo.com', target: '_blank', children: [] },
    { type: 'link-action', label: 'Contato Rápido', url: '#', css_classes: 'abrir-modal js-contato', children: [] }
];

function menusGetSimulationTree() {
    var $json = $('.hep-menus-simulation-tree');
    if ($json.length) {
        try {
            var parsed = JSON.parse($json.last().text());
            if (Array.isArray(parsed) && parsed.length) return parsed;
        } catch (e) { }
    }
    return MENUS_SIM_FALLBACK;
}

function menusExtrairBlocos(htmlTemplate) {
    var blocos = { item: null, item_parent: null, item_separator: null, no_item: null };
    var mP = htmlTemplate.match(/<!--\s*item-parent\s*<\s*-->([\s\S]*?)<!--\s*item-parent\s*>\s*-->/i);
    if (mP) blocos.item_parent = mP[1];
    // req-019: bloco dedicado do separador.
    var mS = htmlTemplate.match(/<!--\s*item-separator\s*<\s*-->([\s\S]*?)<!--\s*item-separator\s*>\s*-->/i);
    if (mS) blocos.item_separator = mS[1];
    var mI = htmlTemplate.match(/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i);
    if (mI) blocos.item = mI[1];
    var mN = htmlTemplate.match(/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i);
    if (mN) blocos.no_item = mN[1];
    return blocos;
}

function menusResolverItemVars(item) {
    var type = item.type || 'pagina';
    var label = item.label || '';
    var url = item.url || '';
    var css = item.css_classes || '';
    // req-019: alvo do link (`_self` por padrão; vazio em separadores).
    var target = item.target || '';
    var slug = '';
    switch (type) {
        case 'pagina': slug = item.page_id || ''; if (target === '') target = '_self'; break;
        case 'separador': url = ''; target = ''; break; // mantém o rótulo opcional
        case 'cabecalho':
        case 'publicador': url = (url !== '') ? url : '#'; if (target === '') target = '_self'; break;
        default: if (target === '') target = '_self';
    }
    return { label: label, url: url, target: target, slug: slug, css_classes: css };
}

function menusAplicarVars(bloco, vars) {
    return bloco.replace(/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/g, function (m, name) {
        if (name === 'children') return '';
        return (name in vars) ? String(vars[name]) : '';
    });
}

function menusInjetarChildren(bloco, childrenHtml) {
    return bloco.split('@[[item#children]]@').join(childrenHtml).split('[[item#children]]').join(childrenHtml);
}

function menusRenderLevel(itens, blocos) {
    var out = '';
    (itens || []).forEach(function (item) {
        if (!item || typeof item !== 'object') return;
        var children = Array.isArray(item.children) ? item.children : [];
        var temFilhos = children.length > 0;
        var vars = menusResolverItemVars(item);
        // req-019: separador usa o bloco dedicado item-separator (ou item "vazio" no fallback).
        var type = item.type || 'pagina';
        if (type === 'separador') {
            if (blocos.item_separator !== null) {
                out += menusAplicarVars(blocos.item_separator, vars);
            } else if (blocos.item !== null) {
                out += menusAplicarVars(blocos.item, { label: '', url: '', slug: '', css_classes: vars.css_classes, target: '' });
            }
            return;
        }
        if (temFilhos && blocos.item_parent !== null) {
            var childrenHtml = menusRenderLevel(children, blocos);
            var bloco = menusInjetarChildren(blocos.item_parent, childrenHtml);
            out += menusAplicarVars(bloco, vars);
            return;
        }
        if (blocos.item !== null) out += menusAplicarVars(blocos.item, vars);
        if (temFilhos) out += menusRenderLevel(children, blocos);
    });
    return out;
}

function menusMontarBase(htmlTemplate, itensRendered) {
    var padraoItem = /<!--\s*item\s*<\s*-->[\s\S]*?<!--\s*item\s*>\s*-->/i;
    var padraoItemParent = /<!--\s*item-parent\s*<\s*-->[\s\S]*?<!--\s*item-parent\s*>\s*-->/i;
    var padraoItemSeparator = /<!--\s*item-separator\s*<\s*-->[\s\S]*?<!--\s*item-separator\s*>\s*-->/i;
    var padraoNoItem = /<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i;
    var out = htmlTemplate;
    if (itensRendered === '') {
        out = out.replace(padraoItemParent, '');
        out = out.replace(padraoItemSeparator, '');
        out = out.replace(padraoItem, '');
        var mN = out.match(padraoNoItem);
        if (mN) out = out.replace(padraoNoItem, function () { return mN[1]; });
        return out;
    }
    if (padraoItem.test(out)) {
        out = out.replace(padraoItem, function () { return itensRendered; });
        out = out.replace(padraoItemParent, '');
    } else {
        out = out.replace(padraoItemParent, function () { return itensRendered; });
    }
    // req-019: o bloco-modelo do separador é apenas um template — removê-lo da base.
    out = out.replace(padraoItemSeparator, '');
    out = out.replace(padraoNoItem, '');
    return out;
}

// req-018: gera `count` sub-itens `pagina` simulados para um nó `publicador`, espelhando a
// injeção dinâmica do widget renderer (menus.widget.php). Usa massa fictícia bilíngue.
function menusPublicadorMockPaginas(count) {
    var pt = (typeof gestor !== 'undefined' && gestor.language === 'pt-br');
    var base = pt
        ? ['Notícia em Destaque', 'Cobertura Especial', 'Última Atualização', 'Entrevista Exclusiva', 'Análise Completa', 'Reportagem do Dia', 'Comunicado Oficial', 'Nos Bastidores']
        : ['Featured News', 'Special Coverage', 'Latest Update', 'Exclusive Interview', 'Full Analysis', 'Story of the Day', 'Official Statement', 'Behind the Scenes'];
    var n = (parseInt(count, 10) > 0) ? parseInt(count, 10) : 5;
    var filhos = [];
    for (var i = 0; i < n; i++) {
        var slug = 'pub-' + (i + 1);
        filhos.push({ type: 'pagina', label: base[i % base.length] + ' ' + (i + 1), url: '/' + slug + '/', page_id: slug, children: [] });
    }
    return filhos;
}

// req-018: substitui os filhos de cada nó `publicador` pelas páginas simuladas (recursivo).
function menusExpandirPublicadores(itens) {
    return (itens || []).map(function (item) {
        if (!item || typeof item !== 'object') return item;
        var copy = {};
        for (var k in item) { if (Object.prototype.hasOwnProperty.call(item, k)) copy[k] = item[k]; }
        if ((item.type || 'pagina') === 'publicador') {
            copy.children = menusPublicadorMockPaginas(item.count);
        } else {
            copy.children = menusExpandirPublicadores(Array.isArray(item.children) ? item.children : []);
        }
        return copy;
    });
}

function menusSimularPreview(html) {
    var blocos = menusExtrairBlocos(html);
    if (blocos.item === null && blocos.item_parent === null) return html;
    var arvore = menusExpandirPublicadores(menusGetSimulationTree());
    var itensRendered = menusRenderLevel(arvore, blocos);
    return menusMontarBase(html, itensRendered);
}

var GALLERIES_SIM_FALLBACK = [
    { imgSrc: 'https://picsum.photos/seed/galeria1/600/400', caminho: 'imagens/galeria/foto-1.jpg', nome: 'foto-1.jpg', legenda: 'Abertura do evento' },
    { imgSrc: 'https://picsum.photos/seed/galeria2/600/400', caminho: 'imagens/galeria/foto-2.jpg', nome: 'foto-2.jpg', legenda: 'Palestra principal' },
    { imgSrc: 'https://picsum.photos/seed/galeria3/600/400', caminho: 'imagens/galeria/foto-3.jpg', nome: 'foto-3.jpg', legenda: 'Painel de debates' },
    { imgSrc: 'https://picsum.photos/seed/galeria4/600/400', caminho: 'imagens/galeria/foto-4.jpg', nome: 'foto-4.jpg', legenda: 'Networking' },
    { imgSrc: 'https://picsum.photos/seed/galeria5/600/400', caminho: 'imagens/galeria/foto-5.jpg', nome: 'foto-5.jpg', legenda: 'Encerramento' },
    { imgSrc: 'https://picsum.photos/seed/galeria6/600/400', caminho: 'imagens/galeria/foto-6.jpg', nome: 'foto-6.jpg', legenda: 'Bastidores' }
];

function galleriesGetSimulationList() {
    var $json = $('.hep-galleries-simulation-list');
    if ($json.length) {
        try {
            var parsed = JSON.parse($json.last().text());
            if (Array.isArray(parsed) && parsed.length) return parsed;
        } catch (e) { }
    }
    return GALLERIES_SIM_FALLBACK;
}

function galleriesExtrairBlocos(htmlTemplate) {
    var blocos = { item: null, no_item: null };
    var mI = htmlTemplate.match(/<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i);
    if (mI) blocos.item = mI[1];
    var mN = htmlTemplate.match(/<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i);
    if (mN) blocos.no_item = mN[1];
    return blocos;
}

function galleriesAplicarVars(bloco, item) {
    return bloco.replace(/@?\[\[item#([a-zA-Z0-9_\-]+)\]\]@?/g, function (m, name) {
        if (name === 'img-src') return item.imgSrc || item.caminho || '';
        if (name === 'caminho') return item.caminho || '';
        if (name === 'nome') return item.nome || '';
        if (name === 'legenda') return item.legenda || '';
        return '';
    });
}

function galleriesMontarBase(htmlTemplate, itensRendered) {
    var padraoItem = /<!--\s*item\s*<\s*-->[\s\S]*?<!--\s*item\s*>\s*-->/i;
    var padraoNoItem = /<!--\s*no-item\s*<\s*-->([\s\S]*?)<!--\s*no-item\s*>\s*-->/i;
    var out = htmlTemplate;
    if (itensRendered === '') {
        out = out.replace(padraoItem, '');
        var mN = out.match(padraoNoItem);
        if (mN) out = out.replace(padraoNoItem, function () { return mN[1]; });
        return out;
    }
    out = out.replace(padraoItem, function () { return itensRendered; });
    out = out.replace(padraoNoItem, '');
    return out;
}

// req-019: lê os controles de exibição do formulário (defaults seguros se ausentes).
function galleriesGetControls() {
    function chk(id, def) {
        var $el = $('#' + id);
        if ($el.length === 0) return def;
        return $el.is(':checked');
    }
    var speed = parseInt($('#gallery-autoplay-speed').val(), 10);
    return {
        show_arrows: chk('gallery-show-arrows', true),
        show_dots: chk('gallery-show-dots', true),
        autoplay: chk('gallery-autoplay', false),
        autoplay_speed: (speed >= 500) ? speed : 3000,
        loop: chk('gallery-loop', true)
    };
}

// req-019: repete o bloco dot-item `count` vezes (índice + classe ativa no índice 0).
function galleriesRenderDots(inner, count) {
    var padraoDotItem = /<!--\s*dot-item\s*<\s*-->([\s\S]*?)<!--\s*dot-item\s*>\s*-->/i;
    var m = inner.match(padraoDotItem);
    if (!m) return inner;
    var tpl = m[1];
    var dots = '';
    for (var i = 0; i < count; i++) {
        var active = (i === 0) ? 'gallery-dot-active' : '';
        var d = tpl.split('@[[dot#index]]@').join(String(i)).split('[[dot#index]]').join(String(i));
        d = d.split('@[[dot#active-class]]@').join(active).split('[[dot#active-class]]').join(active);
        dots += d;
    }
    return inner.replace(padraoDotItem, function () { return dots; });
}

// req-019: processa os blocos de controle (setas/pontinhos) espelhando galleries.widget.php.
function galleriesProcessarControles(html, showArrows, showDots, count) {
    var padraoArrows = /<!--\s*controls-arrows\s*<\s*-->([\s\S]*?)<!--\s*controls-arrows\s*>\s*-->/i;
    if (showArrows) html = html.replace(padraoArrows, function (m, inner) { return inner; });
    else html = html.replace(padraoArrows, '');

    var padraoDots = /<!--\s*controls-dots\s*<\s*-->([\s\S]*?)<!--\s*controls-dots\s*>\s*-->/i;
    if (showDots) html = html.replace(padraoDots, function (m, inner) { return galleriesRenderDots(inner, count); });
    else html = html.replace(padraoDots, '');

    return html;
}

// req-019 / DEC-031: resolve as variáveis globais de controle no HTML final (com/sem arrobas).
function galleriesResolverGlobais(html, controls) {
    var map = {
        show_arrows: controls.show_arrows ? 'true' : 'false',
        show_dots: controls.show_dots ? 'true' : 'false',
        autoplay: controls.autoplay ? 'true' : 'false',
        autoplay_speed: String(controls.autoplay_speed),
        loop: controls.loop ? 'true' : 'false'
    };
    return html.replace(/@?\[\[([a-zA-Z0-9_\-]+)\]\]@?/g, function (m, name) {
        return Object.prototype.hasOwnProperty.call(map, name) ? map[name] : m;
    });
}

function galleriesSimularPreview(html) {
    var blocos = galleriesExtrairBlocos(html);
    var controls = galleriesGetControls();
    var lista = galleriesGetSimulationList();

    if (blocos.item === null) {
        // Sem bloco item: ainda processa controles/globais para refletir os data-* e setas/dots.
        var base0 = galleriesProcessarControles(galleriesMontarBase(html, ''), false, false, 0);
        return galleriesResolverGlobais(base0, controls);
    }

    var itensRendered = '';
    lista.forEach(function (item) { if (item && typeof item === 'object') itensRendered += galleriesAplicarVars(blocos.item, item); });

    var out = galleriesMontarBase(html, itensRendered);
    out = galleriesProcessarControles(out, controls.show_arrows, controls.show_dots, lista.length);
    out = galleriesResolverGlobais(out, controls);
    return out;
}

function publisherVariablesOrSimulation(html = '') {
    const alvo = ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas');

    if (alvo === 'menus') {
        const simulacao = $('.publisherVariablesOrSimulation[data-id="simulation"]').hasClass('active');
        if (simulacao) html = menusSimularPreview(html);
        return html;
    }

    if (alvo === 'galleries') {
        const simulacao = $('.publisherVariablesOrSimulation[data-id="simulation"]').hasClass('active');
        if (simulacao) html = galleriesSimularPreview(html);
        return html;
    }

    if (alvo === 'publisher-highlights' || alvo === 'publisher-index') {
        const simulacao = $('.publisherVariablesOrSimulation[data-id="simulation"]').hasClass('active');

        if (simulacao) {
            const schemaStr = $('input[name="fields_schema"]').val() || '{}';
            let schema = {};
            try { schema = JSON.parse(schemaStr); } catch (e) { }

            // req-015 item 1.1 / req-041 §3.2: a simulação replica o item N vezes refletindo
            // instantaneamente o controle do CRUD (antes de salvar). Para publisher-highlights
            // N vem de #count; para publisher-index (sem #count) vem de #items_per_page (fallback 10).
            let count;
            if (alvo === 'publisher-index') {
                const ippVal = $('#items_per_page').length ? $('#items_per_page').val() : null;
                count = Math.max(1, parseInt(ippVal || schema.items_per_page || 10, 10));
            } else {
                const countVal = $('#count').length ? $('#count').val() : null;
                count = Math.max(1, parseInt(countVal || schema.count || 4, 10));
            }
            const variableMapping = schema.variable_mapping || {};

            const itemRegex = /<!--\s*item\s*<\s*-->([\s\S]*?)<!--\s*item\s*>\s*-->/i;
            const itemMatch = html.match(itemRegex);

            if (itemMatch) {
                const itemTemplate = itemMatch[1];
                let replicated = '';

                // req-011 item 4: rastrear offsets por NOME DA VARIÁVEL (não por tipo). Assim
                // duas variáveis do mesmo tipo (`titulo` e `resumo`, ambas texto) recebem
                // dados diferentes no mesmo card. O índice final é `(i + offsets[varName]) %
                // simulItems.length`, garantindo cards vizinhos com valores distintos.
                const offsets = {};

                // req-011 item 4: remover o seletor de estilo de simulação do DOM para evitar
                // conflitos visuais no ambiente highlights.
                $('.publisher-design-mode-simulation').remove();

                for (let i = 0; i < count; i++) {
                    // req-009 item 2: regex sem arrobas (editor exibe [[item#X]] limpo).
                    const itemHtml = itemTemplate.replace(/\[\[item#([a-zA-Z0-9_\-]+)\]\]/g, function (match, varName) {
                        const fieldId = variableMapping[varName] || varName;

                        // Detectar tipo pelo schema do publisher ou por nome
                        let fieldType = 'text';
                        if (publisher_fields_schema.fields) {
                            const fieldDef = publisher_fields_schema.fields.find(f => f.id === fieldId);
                            if (fieldDef && fieldDef.type) fieldType = fieldDef.type;
                        }
                        if (fieldType === 'text') {
                            var hint = (varName + ' ' + fieldId).toLowerCase();
                            if (/imagem|image|thumb|foto|photo|capa/.test(hint)) fieldType = 'image';
                            else if (/^url$|^link$|^href$/i.test(varName) || /\burl\b|\blink\b/.test(hint)) fieldType = 'url';
                            else if (/^data$|^date$|datetime/i.test(varName) || /\bdata\b|\bdate\b/.test(hint)) fieldType = 'date';
                            else if (/resumo|descri|subtitulo|excerpt|summary|content|texto/i.test(hint)) fieldType = 'textarea';
                        }

                        var simulItems = $('.hep-simulation-' + fieldType + ' .item');

                        // Fallbacks robustos (req-008/010): bucket vazio → valor estático ou genérico text.
                        if (simulItems.length === 0) {
                            if (fieldType === 'image') return 'https://picsum.photos/seed/highlights/800/450';
                            if (fieldType === 'url') return '#';
                            if (fieldType === 'date') return '27/05/2026';
                            simulItems = $('.hep-simulation-text .item');
                        }

                        if (simulItems.length > 0) {
                            // req-011 item 4: offset estável por variável, sorteado uma vez por render.
                            if (!(varName in offsets)) {
                                offsets[varName] = Math.floor(Math.random() * simulItems.length);
                            }
                            var idx = (i + offsets[varName]) % simulItems.length;
                            return simulItems.eq(idx).html().trim();
                        }

                        // Último recurso por tipo (req-010).
                        if (fieldType === 'textarea') return 'Resumo simulado curto e direto do bloco de destaques.';
                        return 'Título Simulado de Destaque';
                    });
                    replicated += itemHtml;
                }

                html = html.replace(itemRegex, replicated);
            }
        }
        return html;
    }

    if (alvo == 'publisher') {
        const simulacao = $('.publisherVariablesOrSimulation[data-id="simulation"]').hasClass('active');

        if (simulacao) {
            const framework = frameworkCSS();
            const designMode = $('.publisher-design-mode-simulation').length > 0 ? $('.publisher-design-mode-simulation').dropdown('get value') : 'simple';

            // Regex para encontrar variáveis no formato [[publisher#TIPO#ID]]
            const regex = /\[\[publisher#(.+?)#(.+?)\]\]/g;

            html = html.replace(regex, function (match, tipo, id, offset, fullString) {
                // Check context: Are we inside an HTML tag attribute?
                let isInsideTag = false;

                // Look backwards for the nearest opening '<' or closing '>'
                let i = offset - 1;
                while (i >= 0) {
                    if (fullString[i] === '>') {
                        // We found a closing tag before an opening one, so we are OUTSIDE a tag
                        isInsideTag = false;
                        break;
                    }
                    if (fullString[i] === '<') {
                        // We found an opening tag without a closing one in between, so we are INSIDE a tag
                        isInsideTag = true;
                        break;
                    }
                    i--;
                }

                // Buscar valores de simulação baseados no modo de design
                let simulationItems;

                // Force simple mode if inside a tag (to avoid breaking attributes like alt="", src="")
                const effectiveMode = isInsideTag ? 'simple' : designMode;

                if (effectiveMode === 'sophisticated') {
                    simulationItems = $(`.hep-simulation-${tipo}.hep-sophisticated.${framework} .item`);
                } else {
                    // Modo simples: buscar genéricos explicitamente
                    simulationItems = $(`.hep-simulation-${tipo}.hep-simple .item`);
                }

                // Fallback: Tenta pegar qualquer um do tipo se a busca específica falhar
                if (simulationItems.length === 0) {
                    simulationItems = $(`.hep-simulation-${tipo} .item`);
                }

                if (simulationItems.length > 0) {
                    // Sortear um valor aleatório
                    const randomIndex = Math.floor(Math.random() * simulationItems.length);
                    // Usar html() para pegar o conteúdo exato (incluindo entidades HTML) e inserir de volta no HTML
                    const randomValue = simulationItems.eq(randomIndex).html().trim();
                    return randomValue;
                }
                // Se não encontrar valores, retornar a variável original
                return match;
            });
        }
    }

    return html;
}

function publisherVariablesOrValues(html = '', salvar_html = false) {
    const publisherPage = ('publisherPage' in gestor.html_editor ? true : false);

    if (publisherPage) {
        const values = $('.publisherVariablesOrValues[data-id="values"]').hasClass('active');

        if (values || salvar_html) {
            // Pegar os valores atualizados do Publisher Página.
            let valoresAtualizadosDoPublisherPagina = {};
            if ('pegarValoresAtualizadosDoPublisherPagina' in window) {
                valoresAtualizadosDoPublisherPagina = window.pegarValoresAtualizadosDoPublisherPagina();
            }

            // Regex para encontrar variáveis no formato [[publisher#TIPO#ID]]
            const regex = /\[\[publisher#(.+?)#(.+?)\]\]/g;

            html = html.replace(regex, function (match, tipo, id, offset, fullString) {
                // Tentar obter valor do publisher page
                if (valoresAtualizadosDoPublisherPagina && valoresAtualizadosDoPublisherPagina[id] && valoresAtualizadosDoPublisherPagina[id].fieldValue !== undefined && valoresAtualizadosDoPublisherPagina[id].fieldValue !== '') {
                    return valoresAtualizadosDoPublisherPagina[id].fieldValue;
                }

                if (salvar_html) {
                    return '';
                }
                // Se não encontrar valor, retornar a variável original
                return match;
            });
        }
    }

    return html;
}

function publisherValuesUpdate() {
    const values = $('.publisherVariablesOrValues[data-id="values"]').hasClass('active');

    if (values) {
        previewHtml();
    }
}

function publisherGetAllVariables() {
    let html = CodeMirrorHtml.getDoc().getValue();

    const regex = regexVariaveisGlobal();
    let foundVariables = new Set();
    let match;

    while ((match = regex.exec(html)) !== null) {
        foundVariables.add(match[0]);
    }

    return Array.from(foundVariables);
}

function publisherVariablesSearch() {
    if (!publisher_fields_schema.template_map) return;

    setTimeout(function () {
        let html = CodeMirrorHtml.getDoc().getValue();

        const regex = regexVariaveisGlobal();
        let foundVariables = new Set();
        let match;

        while ((match = regex.exec(html)) !== null) {
            foundVariables.add(match[0]);
        }

        // Mapear dados para a tabela
        const highlights = alvoUsaItemVars();

        let tableData = publisher_fields_schema.template_map.map(item => {
            // Encontrar definição do campo se existir
            let fieldDef = publisher_fields_schema.fields ? publisher_fields_schema.fields.find(f => f.id === item.id) : null;

            // Para publisher-highlights, o tipo não é parte do template — usa 'text' como default
            let type = fieldDef ? fieldDef.type : (item.type || 'text');
            if (!fieldDef && !highlights) {
                let parts = (item.variable || '').split('#');
                if (parts.length >= 2) type = parts[1];
            }

            return {
                id: item.id,
                variable: item.variable,
                type: type,
                label: fieldDef ? fieldDef.label : (item.label || item.id),
                found: foundVariables.has(item.variable)
            };
        });

        publisherTableVariables(tableData);
    }, 100);
}

function publisherTableVariables(data) {
    let table = $('.hep-variables-table');
    let tableBody = table.find('tbody');

    // Guardar skeleton inicial se ainda não tiver
    if (!publisher_table_tr_skeleton) {
        let tr = tableBody.find('tr').first();
        if (tr.length > 0) {
            publisher_table_tr_skeleton = tr.clone();
        }
    }

    if (!publisher_table_tr_skeleton) return;

    tableBody.empty();

    let countFound = 0;
    let countTotal = data.length;

    const highlights = alvoUsaItemVars();

    data.forEach(item => {
        let row = publisher_table_tr_skeleton.clone();

        // Substituir placeholders no HTML do row
        let html = row.html();
        html = html.replace(/#val-label#/g, item.label);
        html = html.replace(/#val-type#/g, item.type);
        html = html.replace(/#val-id#/g, item.id);
        row.html(html);

        if (highlights) {
            // req-004 item 7: rótulo da variável segue `[[item#NOME]]` (sem TIPO#).
            row.find('.copy-to-clipboard').text('[[item#' + item.id + ']]');
        }

        // Controle de visibilidade dos ícones
        if (item.found) {
            countFound++;
            row.find('.hep-val-found-check').removeClass('hep-initially-hidden');
            row.find('.hep-val-found-times').addClass('hep-initially-hidden');

            row.find('.hep-val-options-buttons').addClass('hep-initially-hidden');
            row.find('.hep-val-options-ok').removeClass('hep-initially-hidden');
        } else {
            row.find('.hep-val-found-check').addClass('hep-initially-hidden');
            row.find('.hep-val-found-times').removeClass('hep-initially-hidden');

            row.find('.hep-val-options-buttons').removeClass('hep-initially-hidden');
            row.find('.hep-val-options-ok').addClass('hep-initially-hidden');
        }

        tableBody.append(row);
    });

    // Mensagens de Status
    $('.hep-all-found-msg, .hep-some-missing-msg, .hep-all-missing-msg').addClass('hep-initially-hidden');
    $('.remove-all-variables').addClass('hep-initially-hidden');

    if (countTotal === 0) {
        $('.hep-all-missing-msg').removeClass('hep-initially-hidden');
    } else if (countFound === countTotal) {
        $('.hep-all-found-msg').removeClass('hep-initially-hidden');
        $('.remove-all-variables').removeClass('hep-initially-hidden');
    } else {
        $('.hep-some-missing-msg').removeClass('hep-initially-hidden');
        if (countFound > 0) {
            $('.remove-all-variables').removeClass('hep-initially-hidden');
        }
    }

    // Mostrar Tabela
    if (countTotal > 0) {
        table.removeClass('hep-initially-hidden');
    } else {
        table.addClass('hep-initially-hidden');
    }
}

// req-044 §5.1: anexar as funções de simulação ao escopo global (window).
window.menusGetSimulationTree = menusGetSimulationTree;
window.menusExtrairBlocos = menusExtrairBlocos;
window.menusResolverItemVars = menusResolverItemVars;
window.menusAplicarVars = menusAplicarVars;
window.menusInjetarChildren = menusInjetarChildren;
window.menusRenderLevel = menusRenderLevel;
window.menusMontarBase = menusMontarBase;
window.menusPublicadorMockPaginas = menusPublicadorMockPaginas;
window.menusExpandirPublicadores = menusExpandirPublicadores;
window.menusSimularPreview = menusSimularPreview;
window.galleriesGetSimulationList = galleriesGetSimulationList;
window.galleriesExtrairBlocos = galleriesExtrairBlocos;
window.galleriesAplicarVars = galleriesAplicarVars;
window.galleriesMontarBase = galleriesMontarBase;
window.galleriesGetControls = galleriesGetControls;
window.galleriesRenderDots = galleriesRenderDots;
window.galleriesProcessarControles = galleriesProcessarControles;
window.galleriesResolverGlobais = galleriesResolverGlobais;
window.galleriesSimularPreview = galleriesSimularPreview;
window.publisherVariablesOrSimulation = publisherVariablesOrSimulation;
window.publisherVariablesOrValues = publisherVariablesOrValues;
window.publisherValuesUpdate = publisherValuesUpdate;
window.publisherGetAllVariables = publisherGetAllVariables;
window.publisherVariablesSearch = publisherVariablesSearch;
window.publisherTableVariables = publisherTableVariables;
