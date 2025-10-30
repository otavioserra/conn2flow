$(document).ready(function () {
    // HTML Editor Helper - Sistema helper para o Editor HTML
    class HtmlEditorHelper {
        constructor() {
            this.htmlEditorModalHtml = '';
            this.htmlEditorVars = '';
            this.htmlEditorScriptPath = '';
            this.init();
        }

        init() {
            this.configureEnvironment();
        }

        configureEnvironment() {
            // Clonar o modal de edição
            this.htmlEditorModalHtml = $('#html-editor-modal').clone().wrap('<div/>').parent().html();

            // Incluir o script e variáveis do editor HTML
            if ('html_editor' in gestor) {
                if ('script' in gestor.html_editor) {
                    this.htmlEditorScriptPath = gestor.html_editor.script;
                }
                if ('overlay_title' in gestor.html_editor) {
                    this.htmlEditorVars += '<script>\n';
                    this.htmlEditorVars += `	const html_editor = { overlay_title: '${gestor.html_editor.overlay_title}' };\n`;
                    this.htmlEditorVars += '</script>\n';
                }
            }
        }

        variablesEnvironment() {
            return {
                htmlEditorModalHtml: this.htmlEditorModalHtml,
                htmlEditorVars: this.htmlEditorVars,
                htmlEditorScriptPath: this.htmlEditorScriptPath
            };
        }
    }

    // Inicializar o editor HTML
    window.HtmlEditorHelper = new HtmlEditorHelper();
});