import { readFileSync } from 'node:fs';
import { describe, it, expect, vi } from 'vitest';

const source = (module) => readFileSync(`gestor/modulos/${module}/${module}.js`, 'utf8');

describe('REQ-221 publisher page submission', () => {
    it('writes the latest rendered HTML to the form on Tailwind submit', () => {
        const script = source('publisher-pages');
        const match = script.match(/\$\('form'\)\.on\('submit', function \(\) \{([\s\S]*?)\n\t\t\}\);/);
        expect(match).not.toBeNull();
        const append = vi.fn();
        const val = vi.fn();
        const form = {};
        const $ = vi.fn((selector) => selector === form ? { append } : { length: 0, val });
        const browser = { getUpdatedHtmlWithValues: () => '<section>updated</section>' };
        const handler = new Function('$', 'window', `return function() {${match[1]}}`)($, browser);
        expect(handler.call(form)).toBe(true);
        expect(append).toHaveBeenCalledWith(expect.stringContaining('name="htmlWithValues"'));
        expect(val).toHaveBeenCalledWith('<section>updated</section>');
    });

    it('binds all curators to native forms and keeps dynamic actions visible', () => {
        for (const module of ['publisher-index', 'pages-index']) {
            const script = source(module);
            expect(script).toContain("$('form').on('submit'");
            expect(script).not.toContain("$('.ui.form')");
            expect(script).not.toMatch(/class="ui\s/);
            expect(script).toContain('remove-tag-btn');
            expect(script).toContain('>×</button>');
            expect(script).toContain('c2fc-botao-primario');
        }
    });

    it('reports transfer errors through translated control dialogs', () => {
        const script = source('publisher-pages');
        expect(script).not.toMatch(/\b(?:alert|confirm|prompt)\(/);
        expect(script).toContain("window.c2fControles.dialogo.alerta($('.mover-publicador-modal').attr('data-msg-connection'))");
        expect(script).toContain("closest('.field, .c2fc-campo')");
        expect(script).toContain("e.key === 'Escape' && activeQuill");
    });
});
