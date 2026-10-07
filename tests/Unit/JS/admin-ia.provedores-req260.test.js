import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { beforeEach, describe, expect, it } from 'vitest';

// REQ-260: sugestões do provedor nos campos e envio dos campos novos do cadastro de servidor de IA.
function carregarHelpers() {
    const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/admin-ia/admin-ia.js'), 'utf8');
    const modulo = { exports: {} };
    const jqueryFalso = () => ({ ready: () => { }, length: 0 });
    // eslint-disable-next-line no-new-func
    new Function('module', 'exports', '$', 'window', 'document', code)(modulo, modulo.exports, jqueryFalso, globalThis, document);
    return { ...modulo.exports, code };
}

const { adminIaSugestoesProvedor, adminIaAplicarSugestoes, adminIaCamposProvedor, code } = carregarHelpers();

const PROVEDORES = {
    gemini: { url_base: 'https://generativelanguage.googleapis.com/v1beta', modelo: 'models/gemini-3-flash-preview', modelo_imagem: 'models/gemini-2.5-flash-image' },
    anthropic: { url_base: 'https://api.anthropic.com/v1', modelo: 'claude-sonnet-5-5', modelo_imagem: '' },
    'openai-compativel': { url_base: '', modelo: '', modelo_imagem: '' }
};

describe('admin-ia provedores (REQ-260)', () => {
    beforeEach(() => {
        document.body.innerHTML = '<form id="f"><select name="tipo"><option value="gemini" selected>G</option><option value="anthropic">A</option>'
            + '<option value="openai-compativel">C</option></select><input name="url_base"><input name="modelo"><input name="modelo_imagem"></form>';
    });

    it('devolve as sugestões do tipo e vazio para tipo desconhecido', () => {
        expect(adminIaSugestoesProvedor(PROVEDORES, 'anthropic')).toEqual({ url_base: 'https://api.anthropic.com/v1', modelo: 'claude-sonnet-5-5', modelo_imagem: '' });
        expect(adminIaSugestoesProvedor(PROVEDORES, 'toString')).toEqual({ url_base: '', modelo: '', modelo_imagem: '' });
        expect(adminIaSugestoesProvedor(null, 'gemini')).toEqual({ url_base: '', modelo: '', modelo_imagem: '' });
    });

    it('mostra a sugestão como texto de exemplo, sem preencher o campo', () => {
        const form = document.getElementById('f');
        adminIaAplicarSugestoes(form, PROVEDORES);
        expect(form.querySelector('[name="modelo"]').getAttribute('placeholder')).toBe('models/gemini-3-flash-preview');
        expect(form.querySelector('[name="modelo"]').value).toBe('');

        form.querySelector('[name="tipo"]').value = 'openai-compativel';
        adminIaAplicarSugestoes(form, PROVEDORES);
        expect(form.querySelector('[name="url_base"]').getAttribute('placeholder')).toBe('');
        expect(form.querySelector('[name="modelo_imagem"]').getAttribute('placeholder')).toBe('');
    });

    it('lê os campos do provedor sem espaços nas pontas', () => {
        const form = document.getElementById('f');
        form.querySelector('[name="url_base"]').value = '  http://localhost:11434/v1 ';
        form.querySelector('[name="modelo"]').value = 'llama3';
        expect(adminIaCamposProvedor(form)).toEqual({ url_base: 'http://localhost:11434/v1', modelo: 'llama3', modelo_imagem: '' });
        expect(adminIaCamposProvedor(null)).toEqual({ url_base: '', modelo: '', modelo_imagem: '' });
    });

    it('inclusão e edição enviam os campos do provedor', () => {
        expect(code.split('Object.assign(data, adminIaCamposProvedor(this));').length - 1).toBe(2);
    });
});
