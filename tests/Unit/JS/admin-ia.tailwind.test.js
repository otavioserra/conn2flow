import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { beforeEach, describe, expect, it } from 'vitest';

function carregarHelpers() {
    const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/admin-ia/admin-ia.js'), 'utf8');
    const modulo = { exports: {} };
    const jqueryFalso = () => ({ ready: () => { }, length: 0 });
    // eslint-disable-next-line no-new-func
    new Function('module', 'exports', '$', 'window', 'document', code)(modulo, modulo.exports, jqueryFalso, globalThis, document);
    return modulo.exports;
}

const { adminIaHistoricoRenderizar } = carregarHelpers();

describe('admin-ia Tailwind', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="historico"></div><template id="item"><article><span data-history-success class="hidden">Success</span><span data-history-error class="hidden">Error</span><time data-history-date></time><strong data-history-response></strong><p data-history-error-detail class="hidden"><span data-history-error-message></span></p></article></template>';
    });

    it('shows localized empty state when there is no history', () => {
        const list = document.getElementById('historico');
        const template = document.getElementById('item');

        adminIaHistoricoRenderizar(list, template, [], 'No tests yet');

        expect(list.textContent).toBe('No tests yet');
        expect(list.children).toHaveLength(0);
    });

    it('renders successful and failed entries with the matching status', () => {
        const list = document.getElementById('historico');
        const template = document.getElementById('item');

        adminIaHistoricoRenderizar(list, template, [
            { sucesso: 1, data: '2026-10-04', tempo_resposta: '120 ms' },
            { sucesso: 0, data: '2026-10-03', tempo_resposta: '2 s', mensagem_erro: '<img src=x onerror=alert(1)>' }
        ], 'No tests yet');

        expect(list.children).toHaveLength(2);
        expect(list.children[0].querySelector('[data-history-success]').classList.contains('hidden')).toBe(false);
        expect(list.children[1].querySelector('[data-history-error]').classList.contains('hidden')).toBe(false);
        expect(list.children[1].querySelector('img')).toBeNull();
        expect(list.children[1].textContent).toContain('<img src=x onerror=alert(1)>');
    });
});