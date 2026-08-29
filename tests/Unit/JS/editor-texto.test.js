import { describe, it, expect, beforeEach, vi } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';

/**
 * req-142 / BATCH-145 — runtime do editor de texto (Quill).
 *
 * O editor anterior era o TinyMCE, licenciado. A troca só é segura se a PONTE com o formulário for
 * confiável: o Quill edita uma `<div>`, mas quem o servidor recebe é o `<textarea>`. Se a
 * sincronização falhar, o operador escreve e o texto não chega — perda de trabalho silenciosa.
 */

const fonte = fs.readFileSync(
    path.resolve(process.cwd(), 'gestor/assets/interface/editor-texto.js'),
    'utf8'
);

/** Quill mínimo: só o que a ponte usa. */
function QuillFake(container) {
    this.container = container;
    this.root = { innerHTML: '<p><br></p>' };
    this.handlers = {};

    this.on = (evento, fn) => { this.handlers[evento] = fn; };
    this.setText = (texto) => { this.root.innerHTML = texto === '' ? '<p><br></p>' : texto; };
    this.clipboard = {
        dangerouslyPasteHTML: (html) => {
            this.root.innerHTML = html;
            if (this.handlers['text-change']) this.handlers['text-change']();
        },
    };
    // Simula digitação do operador.
    this.digitar = (html) => {
        this.root.innerHTML = html;
        if (this.handlers['text-change']) this.handlers['text-change']();
    };
}

function carregarRuntime() {
    delete window.EditorTexto;
    // eslint-disable-next-line no-new-func
    new Function('window', 'document', fonte)(window, document);
    return window.EditorTexto;
}

function montarCampo(valorInicial = '') {
    document.body.innerHTML = '<form id="f"><textarea class="tinymce"></textarea></form>';
    const textarea = document.querySelector('textarea');
    // O valor entra por `.value`: escrever HTML dentro da tag faz o DOM parsear e devolver só o
    // texto, e o teste passaria a medir outra coisa.
    textarea.value = valorInicial;
    return textarea;
}

describe('EditorTexto — ponte entre o Quill e o formulário', () => {
    beforeEach(() => {
        window.Quill = QuillFake;
        document.body.innerHTML = '';
    });

    it('preserva o textarea, que continua sendo o campo enviado', () => {
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('<p>inicial</p>');

        EditorTexto.criar(textarea);

        // O textarea NÃO pode ser removido: é ele que o servidor recebe.
        expect(document.querySelector('textarea')).not.toBeNull();
        expect(textarea.style.display).toBe('none');
        expect(document.querySelector('.editor-texto-container')).not.toBeNull();
    });

    it('carrega o conteúdo existente no editor', () => {
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('<p>conteudo salvo</p>');

        const quill = EditorTexto.criar(textarea);

        expect(quill.root.innerHTML).toBe('<p>conteudo salvo</p>');
    });

    it('escreve no textarea a cada alteração do editor', () => {
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('');
        const quill = EditorTexto.criar(textarea);

        quill.digitar('<p>texto novo</p>');

        expect(textarea.value).toBe('<p>texto novo</p>');
    });

    it('trata o editor vazio como vazio de verdade', () => {
        // `<p><br></p>` é como o Quill representa o vazio. Gravar isso encheria o banco de conteúdo
        // falso e faria checagens de "campo preenchido" mentirem.
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('<p>algo</p>');
        const quill = EditorTexto.criar(textarea);

        quill.digitar('<p><br></p>');

        expect(textarea.value).toBe('');
    });

    it('sincroniza no submit, sem depender do evento de digitação', () => {
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('');
        const quill = EditorTexto.criar(textarea);

        // Altera sem disparar text-change (o caso que perderia conteúdo).
        quill.root.innerHTML = '<p>direto no dom</p>';
        expect(textarea.value).toBe('');

        document.getElementById('f').dispatchEvent(new Event('submit'));

        expect(textarea.value).toBe('<p>direto no dom</p>');
    });

    it('não cria dois editores sobre o mesmo campo', () => {
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('');

        expect(EditorTexto.criar(textarea)).not.toBeNull();
        expect(EditorTexto.criar(textarea)).toBeNull();
        expect(document.querySelectorAll('.editor-texto-container').length).toBe(1);
    });

    it('sem o Quill carregado, o campo segue utilizável como textarea', () => {
        // Degradação: o operador perde a barra de formatação, não o conteúdo nem o salvamento.
        delete window.Quill;
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('<p>preservado</p>');
        const aviso = vi.spyOn(console, 'warn').mockImplementation(() => {});

        expect(EditorTexto.criar(textarea)).toBeNull();
        expect(textarea.style.display).not.toBe('none');
        expect(textarea.value).toBe('<p>preservado</p>');

        aviso.mockRestore();
    });

    it('obterValor e definirValor funcionam com e sem editor', () => {
        const EditorTexto = carregarRuntime();
        const textarea = montarCampo('<p>a</p>');

        // Sem editor: opera direto no textarea.
        expect(EditorTexto.obterValor(textarea)).toBe('<p>a</p>');
        EditorTexto.definirValor(textarea, '<p>b</p>');
        expect(textarea.value).toBe('<p>b</p>');

        // Com editor: os dois lados acompanham.
        const quill = EditorTexto.criar(textarea);
        EditorTexto.definirValor(textarea, '<p>c</p>');
        expect(textarea.value).toBe('<p>c</p>');
        expect(quill.root.innerHTML).toBe('<p>c</p>');
    });

    it('iniciar cria um editor por campo do contexto informado', () => {
        const EditorTexto = carregarRuntime();
        document.body.innerHTML = `
            <form id="f">
                <div id="dentro"><textarea class="tinymce"></textarea><textarea class="tinymce"></textarea></div>
                <textarea class="tinymce"></textarea>
            </form>`;

        const criados = EditorTexto.iniciar('textarea.tinymce', document.getElementById('dentro'));

        expect(criados).toBe(2);
        expect(document.querySelectorAll('.editor-texto-container').length).toBe(2);
    });
});
