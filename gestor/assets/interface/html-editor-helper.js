$(document).ready(function () {
    /**
     * HTML Editor Helper - Sistema auxiliar para o Editor HTML
     * 
     * Responsabilidades:
     * - Preparar o ambiente do editor para uso em iframes/previews
     * - Exportar modal, scripts e variáveis necessárias
     * - Facilitar a integração do editor em diferentes contextos
     */
    class HtmlEditorHelper {
        constructor() {
            // Componentes do editor para exportação
            this.htmlEditorModalHtml = '';
            this.htmlEditorVars = '';
            this.htmlEditorScriptPath = '';

            this.init();
        }

        /**
         * Inicialização do helper
         */
        init() {
            this.configureEnvironment();
        }

        /**
         * Configura o ambiente capturando os componentes necessários do editor
         */
        configureEnvironment() {
            // Clonar o modal de edição para uso externo
            const modalElement = $('#html-editor-modal');
            if (modalElement.length) {
                this.htmlEditorModalHtml = modalElement.clone().wrap('<div/>').parent().html();
            }

            // Configurar variáveis e caminhos do script
            if (typeof gestor !== 'undefined' && gestor.html_editor) {
                const { script, overlay_title, imagepick } = gestor.html_editor;

                if (script) {
                    this.htmlEditorScriptPath = script;
                }

                // Construir objeto de variáveis para o iframe
                let htmlEditorVarsObj = {};

                if (overlay_title) {
                    htmlEditorVarsObj.overlay_title = overlay_title;
                }

                if (imagepick) {
                    htmlEditorVarsObj.imagepick = imagepick;
                }

                // Incluir raiz do gestor para URLs de imagens
                if (typeof gestor !== 'undefined' && gestor.raiz) {
                    htmlEditorVarsObj.raiz = gestor.raiz;
                }

                if (Object.keys(htmlEditorVarsObj).length > 0) {
                    this.htmlEditorVars = `<script>
    const html_editor = ${JSON.stringify(htmlEditorVarsObj)};
</script>`;
                }
            }
        }

        /**
         * Escapa strings para uso seguro em JavaScript inline
         * @param {string} str - String a ser escapada
         * @returns {string}
         */
        escapeString(str) {
            return str
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '\\"')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '\\r');
        }

        /**
         * Retorna todas as variáveis de ambiente necessárias para o editor
         * @returns {Object} Objeto com modal HTML, variáveis JS e caminho do script
         */
        getEnvironment() {
            return {
                htmlEditorModalHtml: this.htmlEditorModalHtml,
                htmlEditorVars: this.htmlEditorVars,
                htmlEditorScriptPath: this.htmlEditorScriptPath
            };
        }

        /**
         * Alias para compatibilidade com código existente
         * @deprecated Use getEnvironment() instead
         */
        variablesEnvironment() {
            return this.getEnvironment();
        }

        /**
         * Gera o HTML completo para injetar o editor em um contexto externo
         * @returns {string} HTML com modal, scripts e variáveis
         */
        generateInjectionHtml() {
            let html = '';

            // Adicionar modal
            if (this.htmlEditorModalHtml) {
                html += this.htmlEditorModalHtml + '\n';
            }

            // Adicionar variáveis
            if (this.htmlEditorVars) {
                html += this.htmlEditorVars + '\n';
            }

            // Adicionar script
            if (this.htmlEditorScriptPath) {
                html += `<script src="${this.htmlEditorScriptPath}"></script>\n`;
            }

            return html;
        }
    }

    // Inicializar e expor globalmente
    window.HtmlEditorHelper = new HtmlEditorHelper();
});