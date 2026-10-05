import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { beforeEach, describe, expect, it } from 'vitest';

function carregarHelpers() {
    const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/admin-plugins/admin-plugins.js'), 'utf8');
    const modulo = { exports: {} };
    const jqueryFalso = () => ({ ready: () => { }, length: 0 });
    // eslint-disable-next-line no-new-func
    new Function('module', 'exports', '$', 'window', 'document', code)(modulo, modulo.exports, jqueryFalso, globalThis, document);
    return modulo.exports;
}

const { adminPluginsOrigemInicializar } = carregarHelpers();

describe('admin-plugins Tailwind source tabs', () => {
    beforeEach(() => {
        document.body.innerHTML = `
      <div data-admin-plugins-source data-plugin-source-initial="github_privado">
        <input id="origem_selecionada" value="github_privado">
        <button type="button" data-plugin-source-tab="arquivo"></button>
        <button type="button" data-plugin-source-tab="publico"></button>
        <button type="button" data-plugin-source-tab="privado"></button>
        <section data-plugin-source-panel="arquivo"></section>
        <section data-plugin-source-panel="publico" hidden></section>
        <section data-plugin-source-panel="privado" hidden></section>
      </div>`;
    });

    it('restores the saved origin and submits the canonical source value', () => {
        const source = document.querySelector('[data-admin-plugins-source]');
        adminPluginsOrigemInicializar(source);

        expect(source.querySelector('#origem_selecionada').value).toBe('privado');
        expect(source.querySelector('[data-plugin-source-tab="privado"]').getAttribute('aria-selected')).toBe('true');
        expect(source.querySelector('[data-plugin-source-panel="privado"]').hidden).toBe(false);
        expect(source.querySelector('[data-plugin-source-panel="arquivo"]').hidden).toBe(true);
    });

    it('switches panels and the hidden form value when a tab is activated', () => {
        const source = document.querySelector('[data-admin-plugins-source]');
        adminPluginsOrigemInicializar(source);
        source.querySelector('[data-plugin-source-tab="publico"]').click();

        expect(source.querySelector('#origem_selecionada').value).toBe('publico');
        expect(source.querySelector('[data-plugin-source-tab="publico"]').getAttribute('aria-selected')).toBe('true');
        expect(source.querySelector('[data-plugin-source-panel="publico"]').hidden).toBe(false);
        expect(source.querySelector('[data-plugin-source-panel="privado"]').hidden).toBe(true);
    });
});