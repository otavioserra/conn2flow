/**
 * Editor de Texto (Quill) — runtime compartilhado do Gestor.
 *
 * Par JavaScript de `bibliotecas/editor-texto.php`: a biblioteca PHP inclui os assets e decide a
 * versão; este arquivo cria e sincroniza os editores. Quem precisar de um editor de texto rico numa
 * tela do gestor chama `EditorTexto.iniciar(...)` e não repete configuração.
 *
 * Por que existe (req-142): o editor anterior era o TinyMCE, licenciado e carregado de
 * `cdn.tiny.cloud` com a chave de API versionada no repositório. Cada tela que precisava de um
 * editor repetia as opções, e a troca de versão exigia caçar as ocorrências uma a uma.
 *
 * Ponte com o formulário: o Quill edita uma `<div>`, não o `<textarea>`. O textarea original é
 * PRESERVADO e escondido — ele continua sendo o campo que o formulário envia. Cada alteração no
 * editor escreve o HTML de volta nele, e o submit também sincroniza, para não depender do evento de
 * digitação ter disparado por último.
 */
(function (window, document) {
    'use strict';

    var EditorTexto = {
        /** Instâncias criadas, para que a tela possa lê-las se precisar. */
        instancias: [],

        /** Barra de ferramentas equivalente à que o TinyMCE oferecia nas variáveis. */
        toolbarPadrao: [
            [{ header: [1, 2, 3, 4, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            ['blockquote', 'code-block'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image'],
            [{ align: [] }],
            [{ indent: '-1' }, { indent: '+1' }],
            ['clean']
        ],

        /**
         * Cria um editor sobre um `<textarea>`.
         *
         * @param {HTMLTextAreaElement} textarea Campo que o formulário envia.
         * @param {Object} [opcoes] `toolbar` e `altura` sobrescrevem os padrões.
         * @returns {Object|null} Instância do Quill, ou null quando não foi possível criar.
         */
        criar: function (textarea, opcoes) {
            if (!textarea || textarea.getAttribute('data-editor-texto') === 'pronto') {
                return null;
            }

            if (typeof window.Quill === 'undefined') {
                // Sem a biblioteca carregada o campo continua sendo um textarea editável: o operador
                // perde a formatação visual, mas não perde o conteúdo nem a capacidade de salvar.
                console.warn('EditorTexto: Quill não carregado; o textarea segue em uso.');
                return null;
            }

            opcoes = opcoes || {};

            var container = document.createElement('div');
            container.className = 'editor-texto-container';
            if (opcoes.altura) {
                container.style.minHeight = opcoes.altura;
            }

            textarea.parentNode.insertBefore(container, textarea.nextSibling);

            // O textarea sai da vista mas continua no formulário: é ele que o servidor recebe.
            textarea.style.display = 'none';
            textarea.setAttribute('data-editor-texto', 'pronto');

            var quill = new window.Quill(container, {
                theme: 'snow',
                modules: { toolbar: opcoes.toolbar || EditorTexto.toolbarPadrao }
            });

            if (textarea.value) {
                quill.clipboard.dangerouslyPasteHTML(textarea.value);
            }

            var sincronizar = function () {
                // `ql-editor` vazio ainda contém `<p><br></p>`; gravar isso encheria o banco de
                // conteúdo falso e faria checagens de "campo preenchido" mentirem.
                var html = quill.root.innerHTML;
                textarea.value = (html === '<p><br></p>') ? '' : html;
            };

            quill.on('text-change', sincronizar);

            var formulario = textarea.form;
            if (formulario && !formulario.hasAttribute('data-editor-texto-submit')) {
                formulario.setAttribute('data-editor-texto-submit', '1');
                formulario.addEventListener('submit', function () {
                    EditorTexto.sincronizarTodos();
                });
            }

            EditorTexto.instancias.push({ quill: quill, textarea: textarea, sincronizar: sincronizar });

            return quill;
        },

        /**
         * Cria editores para todos os textareas que casarem com o seletor.
         *
         * @param {string} seletor Seletor CSS dos campos (ex.: `textarea.editor-texto`).
         * @param {Element|Document} [contexto] Raiz da busca; o documento inteiro por padrão.
         * @param {Object} [opcoes] Repassadas para `criar()`.
         * @returns {number} Quantidade de editores criados.
         */
        iniciar: function (seletor, contexto, opcoes) {
            var raiz = contexto || document;
            var campos = raiz.querySelectorAll(seletor);
            var criados = 0;

            for (var i = 0; i < campos.length; i++) {
                if (EditorTexto.criar(campos[i], opcoes)) {
                    criados++;
                }
            }

            return criados;
        },

        /**
         * Instância associada a um textarea (ou null quando o campo não virou editor).
         *
         * @param {HTMLTextAreaElement} textarea
         * @returns {Object|null}
         */
        instanciaDe: function (textarea) {
            for (var i = 0; i < EditorTexto.instancias.length; i++) {
                if (EditorTexto.instancias[i].textarea === textarea) {
                    return EditorTexto.instancias[i];
                }
            }
            return null;
        },

        /**
         * Conteúdo HTML atual do campo.
         *
         * Lê do textarea, que é a fonte que o formulário envia e que o editor mantém sincronizado.
         * Assim a leitura funciona igual com ou sem o editor carregado.
         *
         * @param {HTMLTextAreaElement} textarea
         * @returns {string}
         */
        obterValor: function (textarea) {
            if (!textarea) {
                return '';
            }

            var instancia = EditorTexto.instanciaDe(textarea);
            if (instancia) {
                instancia.sincronizar();
            }

            return textarea.value || '';
        },

        /**
         * Define o conteúdo do campo, atualizando editor e textarea.
         *
         * @param {HTMLTextAreaElement} textarea
         * @param {string} html
         */
        definirValor: function (textarea, html) {
            if (!textarea) {
                return;
            }

            html = (html === null || html === undefined) ? '' : String(html);
            textarea.value = html;

            var instancia = EditorTexto.instanciaDe(textarea);
            if (!instancia) {
                return;  // campo ainda é textarea puro: o valor acima já basta
            }

            instancia.quill.setText('');
            if (html !== '') {
                instancia.quill.clipboard.dangerouslyPasteHTML(html);
            }
        },

        /** Escreve o conteúdo de todos os editores nos respectivos textareas. */
        sincronizarTodos: function () {
            for (var i = 0; i < EditorTexto.instancias.length; i++) {
                EditorTexto.instancias[i].sincronizar();
            }
        }
    };

    window.EditorTexto = EditorTexto;
})(window, document);
