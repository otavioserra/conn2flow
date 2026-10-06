/**
 * cookie-consent.widget.js — comportamento público do aviso de cookies (req-208).
 *
 * Peças dentro de `[data-c2f-cookie-consent]`:
 *   - aviso `[data-cc-banner]`, com `[data-cc-accept]`, `[data-cc-reject]` e `[data-cc-customize]`;
 *   - cartão de preferências `[data-cc-panel]`, com uma caixa `input[data-cc-category="id"]` por
 *     categoria, `[data-cc-save]` e `[data-cc-close]`;
 *   - botão `[data-cc-open]`, que reabre o cartão depois da decisão.
 * Em qualquer lugar da página, `[data-cookie-consent-open]` ou um link para `#cookie-consent` também
 * abre o cartão.
 *
 * A decisão fica no cookie `c2f_consent` do próprio site: `{ v: versão, t: instante, c: { id: bool } }`.
 * Sem decisão válida para a versão atual, só as categorias obrigatórias valem e o aviso aparece.
 *
 * O que a decisão libera:
 *   - `<script type="text/plain" data-cookie-category="analytics">` vira script de verdade (com
 *     `data-src` no lugar de `src` para arquivo externo);
 *   - `<iframe data-cookie-category="marketing" data-src="…">` e `<img …>` recebem o `src`;
 *   - com `data-consent-mode="true"`, os sinais do Google Consent Mode v2 vão para o `dataLayer`.
 * Retirar uma categoria já concedida recarrega a página: script que já rodou não se desfaz.
 *
 * API: `window.c2fConsent` = { get(), has(id), open(), reset(), onChange(fn) }.
 * Evento: `c2f:consent` no `document`, com `detail = { categories, decided }`.
 */
(function () {
    'use strict';

    var COOKIE = 'c2f_consent';
    var ouvintes = [];
    var estado = { categories: {}, decided: false };
    var raiz = null;
    var cfg = null;
    var focoAnterior = null;

    // Categoria do aviso → sinais do Google Consent Mode v2.
    var SINAIS = {
        analytics: ['analytics_storage'],
        marketing: ['ad_storage', 'ad_user_data', 'ad_personalization'],
        preferences: ['functionality_storage', 'personalization_storage']
    };

    function lerCookie() {
        var partes = document.cookie ? document.cookie.split('; ') : [];
        for (var i = 0; i < partes.length; i++) {
            if (partes[i].indexOf(COOKIE + '=') === 0) {
                try { return JSON.parse(decodeURIComponent(partes[i].substring(COOKIE.length + 1))); } catch (e) { return null; }
            }
        }
        return null;
    }

    function gravarCookie(valor, dias) {
        var texto = COOKIE + '=' + encodeURIComponent(JSON.stringify(valor)) + '; path=/; max-age=' + (dias * 86400) + '; SameSite=Lax';
        if (window.location.protocol === 'https:') texto += '; Secure';
        document.cookie = texto;
    }

    function apagarCookie() {
        document.cookie = COOKIE + '=; path=/; max-age=0; SameSite=Lax';
    }

    function categorias() {
        return Array.prototype.map.call(raiz.querySelectorAll('input[data-cc-category]'), function (caixa) {
            return { id: caixa.getAttribute('data-cc-category'), required: caixa.disabled, caixa: caixa };
        });
    }

    function soObrigatorias() {
        var c = {};
        categorias().forEach(function (cat) { c[cat.id] = !!cat.required; });
        return c;
    }

    function copia(obj) {
        var c = {};
        Object.keys(obj).forEach(function (k) { c[k] = obj[k]; });
        return c;
    }

    // ----- Efeitos da decisão

    function gtag() {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(arguments);
    }

    function sinais(c) {
        var s = { security_storage: 'granted' };
        Object.keys(SINAIS).forEach(function (id) {
            SINAIS[id].forEach(function (sinal) { s[sinal] = c[id] ? 'granted' : 'denied'; });
        });
        return s;
    }

    function liberar(c) {
        Array.prototype.forEach.call(document.querySelectorAll('script[type="text/plain"][data-cookie-category]'), function (antigo) {
            if (!c[antigo.getAttribute('data-cookie-category')]) return;
            var novo = document.createElement('script');
            Array.prototype.forEach.call(antigo.attributes, function (a) {
                if (a.name === 'type' || a.name === 'data-src' || a.name === 'data-cookie-category') return;
                novo.setAttribute(a.name, a.value);
            });
            if (antigo.getAttribute('data-src')) novo.src = antigo.getAttribute('data-src');
            else novo.text = antigo.text;
            antigo.parentNode.replaceChild(novo, antigo);
        });

        Array.prototype.forEach.call(document.querySelectorAll('iframe[data-cookie-category][data-src], img[data-cookie-category][data-src]'), function (el) {
            if (!c[el.getAttribute('data-cookie-category')]) return;
            el.setAttribute('src', el.getAttribute('data-src'));
            el.removeAttribute('data-src');
        });
    }

    function aplicar(inicial) {
        var c = estado.categories;
        if (cfg.consentMode) {
            // Na carga, o padrão é tudo negado; a decisão já tomada (ou a nova) entra como atualização.
            if (inicial) gtag('consent', 'default', sinais({}));
            if (!inicial || estado.decided) gtag('consent', 'update', sinais(c));
            if (!inicial) window.dataLayer.push({ event: 'c2f_consent_update', c2f_consent: copia(c) });
        }
        liberar(c);
        document.documentElement.setAttribute('data-c2f-consent', estado.decided ? 'decided' : 'pending');
        var detalhe = { categories: copia(c), decided: estado.decided };
        document.dispatchEvent(new CustomEvent('c2f:consent', { detail: detalhe }));
        ouvintes.forEach(function (fn) { try { fn(detalhe); } catch (e) { } });
    }

    function decidir(c) {
        var antes = estado.categories;
        var retirou = estado.decided && Object.keys(antes).some(function (id) { return antes[id] && !c[id]; });

        estado = { categories: c, decided: true };
        if (!cfg.preview) gravarCookie({ v: cfg.version, t: Date.now(), c: c }, cfg.days);

        fecharAviso();
        fecharPainel();
        mostrarBotao(true);

        if (retirou && !cfg.preview) {
            window.location.reload();
            return;
        }
        aplicar(false);
    }

    // ----- Peças

    function peca(seletor) { return raiz.querySelector(seletor); }

    function mostrar(el, visivel) {
        if (!el) return;
        if (visivel) el.removeAttribute('hidden'); else el.setAttribute('hidden', '');
    }

    function abrirAviso() { mostrar(peca('[data-cc-banner]'), true); }
    function fecharAviso() { mostrar(peca('[data-cc-banner]'), false); }
    function mostrarBotao(visivel) { mostrar(peca('[data-cc-open]'), visivel); }

    function focaveis(el) {
        return Array.prototype.filter.call(el.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'), function (x) {
            return x.offsetParent !== null;
        });
    }

    function abrirPainel() {
        var painel = peca('[data-cc-panel]');
        if (!painel) return;
        categorias().forEach(function (cat) { cat.caixa.checked = cat.required || !!estado.categories[cat.id]; });
        focoAnterior = document.activeElement;
        fecharAviso();
        mostrar(painel, true);
        var lista = focaveis(painel);
        if (lista.length) lista[0].focus();
    }

    function fecharPainel() {
        var painel = peca('[data-cc-panel]');
        if (!painel || painel.hasAttribute('hidden')) return;
        mostrar(painel, false);
        if (!estado.decided) abrirAviso();
        if (focoAnterior && focoAnterior.focus) { try { focoAnterior.focus(); } catch (e) { } }
    }

    function escolhidas() {
        var c = {};
        categorias().forEach(function (cat) { c[cat.id] = cat.required || cat.caixa.checked; });
        return c;
    }

    function todas(valor) {
        var c = {};
        categorias().forEach(function (cat) { c[cat.id] = cat.required || valor; });
        return c;
    }

    // ----- Início

    function iniciar() {
        raiz = document.querySelector('[data-c2f-cookie-consent]');
        if (!raiz || raiz.c2fConsentReady) return;
        raiz.c2fConsentReady = true;

        cfg = {
            version: raiz.getAttribute('data-version') || '1',
            days: Math.max(1, parseInt(raiz.getAttribute('data-expiry-days'), 10) || 180),
            consentMode: raiz.getAttribute('data-consent-mode') === 'true',
            // O Dashboard é um documento isolado: a decisão ali só vale para a demonstração.
            preview: raiz.getAttribute('data-preview') === 'true' || document.documentElement.hasAttribute('data-c2f-dashboard-widget')
        };

        var salvo = cfg.preview ? null : lerCookie();
        var base = soObrigatorias();
        if (salvo && salvo.v === cfg.version && salvo.c && typeof salvo.c === 'object') {
            // Categoria criada depois da decisão nasce negada; a obrigatória vale sempre.
            Object.keys(base).forEach(function (id) { base[id] = base[id] || salvo.c[id] === true; });
            estado = { categories: base, decided: true };
        } else {
            estado = { categories: base, decided: false };
        }

        raiz.addEventListener('click', function (e) {
            var alvo = e.target.closest('[data-cc-accept],[data-cc-reject],[data-cc-customize],[data-cc-save],[data-cc-close],[data-cc-open]');
            if (!alvo || !raiz.contains(alvo)) return;
            e.preventDefault();
            if (alvo.hasAttribute('data-cc-accept')) decidir(todas(true));
            else if (alvo.hasAttribute('data-cc-reject')) decidir(todas(false));
            else if (alvo.hasAttribute('data-cc-save')) decidir(escolhidas());
            else if (alvo.hasAttribute('data-cc-close')) fecharPainel();
            else abrirPainel();
        });

        document.addEventListener('click', function (e) {
            var alvo = e.target.closest('[data-cookie-consent-open], a[href$="#cookie-consent"]');
            if (!alvo || raiz.contains(alvo)) return;
            e.preventDefault();
            abrirPainel();
        });

        document.addEventListener('keydown', function (e) {
            var painel = peca('[data-cc-panel]');
            if (!painel || painel.hasAttribute('hidden')) return;
            if (e.key === 'Escape') { fecharPainel(); return; }
            if (e.key !== 'Tab') return;
            // O foco fica dentro do cartão enquanto ele está aberto.
            var lista = focaveis(painel);
            if (!lista.length) return;
            var primeiro = lista[0];
            var ultimo = lista[lista.length - 1];
            if (e.shiftKey && document.activeElement === primeiro) { e.preventDefault(); ultimo.focus(); }
            else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primeiro.focus(); }
        });

        raiz.classList.add('is-ready');
        if (estado.decided) {
            mostrarBotao(true);
        } else {
            mostrarBotao(false);
            abrirAviso();
        }
        aplicar(true);
    }

    window.c2fConsent = {
        get: function () { return { categories: copia(estado.categories), decided: estado.decided }; },
        has: function (id) { return estado.categories[id] === true; },
        open: function () { if (raiz) abrirPainel(); },
        reset: function () { apagarCookie(); window.location.reload(); },
        onChange: function (fn) { if (typeof fn === 'function') ouvintes.push(fn); }
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();

    // Aviso que chega depois da carga (prévia do editor de páginas, conteúdo trazido por AJAX).
    if (typeof MutationObserver !== 'undefined') {
        var agendado = null;
        var observar = function () {
            new MutationObserver(function () {
                if (raiz || agendado) return;
                agendado = setTimeout(function () { agendado = null; iniciar(); }, 50);
            }).observe(document.body, { childList: true, subtree: true });
        };
        if (document.body) observar(); else document.addEventListener('DOMContentLoaded', observar);
    }
})();
