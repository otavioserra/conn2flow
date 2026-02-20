/**
 * Interface V2 — Frontend OOP Module
 * 
 * Reescrita moderna da interface.js usando classes ES6+.
 * Mantém compatibilidade com jQuery e Fomantic-UI (Semantic UI).
 * 
 * Namespace: gestor['interface-v2']
 * 
 * @version 2.0.0
 * @requires jQuery, Fomantic-UI, DataTables (para listagem)
 */
$(document).ready(function () {

    'use strict';

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                    UTILITY HELPERS                           ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Input debounce — espera o usuário parar de digitar antes de disparar.
     */
    class InputDebounce {
        #timers = new Map();
        #globalDelay;

        constructor(delay = 600) {
            this.#globalDelay = delay;
        }

        /**
         * @param {string} key Identificador único do debounce
         * @param {Function} callback Função a executar após delay
         * @param {number} [delay] Delay em ms (usa global se omitido)
         */
        run(key, callback, delay) {
            if (this.#timers.has(key)) clearTimeout(this.#timers.get(key));
            this.#timers.set(key, setTimeout(() => {
                this.#timers.delete(key);
                callback();
            }, delay ?? this.#globalDelay));
        }

        cancel(key) {
            if (this.#timers.has(key)) {
                clearTimeout(this.#timers.get(key));
                this.#timers.delete(key);
            }
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                      LOADING MODAL                          ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Gerencia o modal de carregamento com proteção contra timeout.
     */
    class LoadingManager {
        #counter = 0;
        #isLoading = false;
        #openTime = 0;
        #timeoutMs;
        #minDisplayMs;

        constructor(timeoutMs = 5000, minDisplayMs = 200) {
            this.#timeoutMs = timeoutMs;
            this.#minDisplayMs = minDisplayMs;
        }

        open() {
            this.#counter++;
            this.#isLoading = true;
            this.#openTime = Date.now();

            if (this.#counter === 1) {
                const num = this.#counter;

                $('.ui.modal.carregando').modal({
                    closable: false,
                    onShow: () => {
                        setTimeout(() => {
                            if (num === this.#counter && this.#isLoading) {
                                this.close();
                                AlertManager.show(gestor.componentes?.ajaxTimeoutMessage || 'Timeout');
                            }
                        }, this.#timeoutMs);
                    }
                }).modal('setting', 'duration', '0');
            }

            $('.ui.modal.carregando').modal('show');
        }

        close() {
            this.#isLoading = false;
            const elapsed = Date.now() - this.#openTime;
            const remaining = this.#minDisplayMs - elapsed;

            if (remaining > 0) {
                setTimeout(() => $('.ui.modal.carregando').modal('hide'), remaining);
            } else {
                $('.ui.modal.carregando').modal('hide');
            }
        }

        get isLoading() { return this.#isLoading; }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                       ALERT MANAGER                         ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Gerencia exibição de alertas via modal.
     */
    class AlertManager {
        static show(msg) {
            if (msg) {
                $('.ui.modal.alerta .content p').html(msg);
            }
            $('.ui.modal.alerta').modal('show');
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                     DELETE CONFIRMATION                      ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Gerencia confirmação de exclusão.
     */
    class DeleteConfirm {
        #url = '';

        setUrl(url) {
            this.#url = url;
        }

        show() {
            $('.ui.modal.confirm._interfaceDelecaoModal').modal({
                onApprove: () => {
                    window.open(this.#url, '_self');
                    return false;
                }
            }).modal('show');
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                      FORM MANAGER                           ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Gerencia formulários com validação client-side e server-side.
     */
    class FormManager {
        #optionsToValidate = [];
        #dontAutoSubmit = false;
        #iv2Config;

        constructor(iv2Config) {
            this.#iv2Config = iv2Config;
        }

        /**
         * Inicializa o formulário Fomantic-UI com regras de validação.
         * @param {Object} [opts] Opções extras
         * @param {Function} [opts.formOnSuccessCalbackFunc] Callback de sucesso
         */
        init(opts = {}) {
            const validarCampos = this.#iv2Config?.validarCampos || gestor.interface?.validarCampos || false;
            const regras = this.#iv2Config?.regrasValidacao || gestor.interface?.regrasValidacao || {};

            // Server-side field validation functions
            const verificarCamposFinalizar = () => {
                let invalido = false;
                const errObjs = {};

                for (const campo in validarCampos) {
                    const vc = validarCampos[campo];
                    const campoId = vc.campo || campo;

                    if (!vc.valido) {
                        invalido = true;
                        const erroKey = campoId + '_erro';
                        errObjs[erroKey] = vc.prompt;
                        $('.ui.form.interfaceFormPadrao').form('add prompt', campoId, [erroKey]);
                    } else {
                        $('.ui.form.interfaceFormPadrao').form('validate field', campoId);
                    }
                }

                loading.close();

                if (invalido) {
                    $('.ui.form.interfaceFormPadrao').form('add errors', errObjs);
                } else {
                    $('.ui.form.interfaceFormPadrao').form('remove errors');

                    if (this.#dontAutoSubmit) {
                        this.submit('validarCampos');
                    } else {
                        $('.ui.form.interfaceFormPadrao').unbind('submit').submit();
                    }
                }
            };

            const verificarCampoCallback = (campo, status) => {
                validarCampos[campo].valido = status;
                validarCampos[campo].verificado = true;

                const todosVerificados = Object.values(validarCampos)
                    .every(vc => vc.verificado);

                if (todosVerificados) verificarCamposFinalizar();
            };

            const verificarCampo = (language, value, id, campo) => {
                $.ajax({
                    type: 'POST',
                    url: gestor.raiz + gestor.moduloCaminho,
                    data: {
                        opcao: gestor.moduloOpcao,
                        ajax: 'sim',
                        ajaxOpcao: 'verificar-campo',
                        ajaxRegistroId: gestor.moduloRegistroId,
                        language: language !== false,
                        campo: campo || id,
                        valor: value,
                    },
                    dataType: 'json',
                    success(dados) {
                        if (dados.status === 'Ok') {
                            verificarCampoCallback(id, !dados.campoExiste);
                        } else {
                            console.error('interface-v2: verificar-campo error', dados);
                        }
                    },
                    error(txt) {
                        if (txt.status === 401) {
                            window.open(gestor.raiz + (txt.responseJSON?.redirect || 'signin/'), '_self');
                        } else {
                            console.error('interface-v2: verificar-campo AJAX error', txt);
                        }
                    },
                });
            };

            // Init Fomantic-UI form
            $('.ui.form.interfaceFormPadrao').form({
                fields: regras,
                onSuccess: (event, fields) => {
                    let retorno = true;

                    // Server-side field validation
                    if (validarCampos) {
                        this.#optionsToValidate.push({ id: 'validarCampos', valido: false });
                        let needLoading = false;

                        for (const field in fields) {
                            if (!validarCampos[field]) continue;

                            const vc = validarCampos[field];
                            let validar = false;

                            if (vc.valor !== undefined) {
                                validar = vc.valor !== fields[field] || !vc.valido;
                            } else {
                                validar = true;
                            }

                            if (validar) {
                                if (!needLoading) { needLoading = true; loading.open(); }
                                vc.verificado = false;
                                vc.valor = fields[field];
                                verificarCampo(
                                    vc.language ?? false,
                                    vc.valor,
                                    field,
                                    vc.campo || false
                                );
                            }
                        }

                        retorno = false;
                    }

                    // Custom success callback
                    if (opts.formOnSuccessCalbackFunc) {
                        this.#optionsToValidate.push({ id: 'formOnSuccessCalback', valido: false });
                        this.#dontAutoSubmit = true;
                        opts.formOnSuccessCalbackFunc();
                        retorno = false;
                    }

                    return retorno;
                },
            });
        }

        /**
         * Submete o formulário se todos os validadores passaram.
         */
        submit(validatorId) {
            for (const opt of this.#optionsToValidate) {
                if (opt.id === validatorId) opt.valido = true;
            }

            const allValid = this.#optionsToValidate.every(o => o.valido);
            if (allValid) {
                $('.ui.form.interfaceFormPadrao').unbind('submit').submit();
            }
        }

        /**
         * Reinicializa o formulário.
         */
        reinit(opts = {}) {
            this.#optionsToValidate = [];
            this.#dontAutoSubmit = false;
            this.init(opts);
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                    HISTORY LOADER                           ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Gerencia carregamento paginado do histórico.
     */
    class HistoryLoader {
        #currentPage = 0;
        #buttonSelector;
        #semId;

        constructor(buttonSelector, semId = false) {
            this.#buttonSelector = buttonSelector;
            this.#semId = semId;
            this.#bind();
        }

        #bind() {
            $(this.#buttonSelector).on('mouseup tap', (e) => {
                if (e.which !== 1 && e.which !== 0 && e.which !== undefined) return false;
                this.#loadMore();
            });

            // V2 button
            $('#_iv2-historico-mais').on('mouseup tap', (e) => {
                if (e.which !== 1 && e.which !== 0 && e.which !== undefined) return false;
                this.#loadMore();
            });
        }

        #loadMore() {
            this.#currentPage++;

            const iv2 = gestor['interface-v2'] || {};
            const id = gestor.interface?.id || iv2?.historico?.id || '';

            const data = {
                opcao: gestor.moduloOpcao,
                ajax: 'sim',
                ajaxOpcao: 'historico-mais-resultados',
                ajaxRegistroId: gestor.moduloRegistroId,
                pagina: this.#currentPage,
                id,
            };

            if (this.#semId) data.sem_id = 'sim';

            $.ajax({
                type: 'POST',
                url: gestor.raiz + gestor.moduloCaminho,
                data,
                dataType: 'json',
                beforeSend: () => loading.open(),
                success: (dados) => {
                    if (dados.status === 'Ok') {
                        const $btn = $(this.#buttonSelector).add('#_iv2-historico-mais');
                        $btn.parent().parent().before(dados.pagina);

                        const totalPaginas = iv2?.historico?.totalPaginas
                            || gestor.interface?.totalPaginas || 1;

                        if (this.#currentPage >= parseInt(totalPaginas) - 1) {
                            $btn.hide();
                        }
                    } else {
                        console.error('interface-v2: historico error', dados);
                    }
                    loading.close();
                },
                error: (txt) => {
                    if (txt.status === 401) {
                        window.open(gestor.raiz + (txt.responseJSON?.redirect || 'signin/'), '_self');
                    } else {
                        console.error('interface-v2: historico AJAX error', txt);
                        loading.close();
                    }
                },
            });
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                    WIDGET: ImagePick                        ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Widget de seleção de imagem via iframe modal.
     */
    class ImagePickWidget {
        #config;
        #objPai = null;
        #started = false;

        constructor(config) {
            this.#config = config;
        }

        init() {
            if (!$('._gestor-widgetImage-cont').length) return;

            // Add button
            $('._gestor-widgetImage-btn-add').on('mouseup tap', (e) => {
                if (e.which !== 1 && e.which !== 0 && e.which !== undefined) return false;

                if (!this.#started) {
                    this.#started = true;
                    $('.iframePagina .header').html(this.#config.modal.head);
                    $('.iframePagina .cancel.button').html(this.#config.modal.cancel);
                }

                this.#objPai = $(e.currentTarget).closest('._gestor-widgetImage-cont');

                const $modal = $('.ui.modal.iframePagina');
                $modal.find('iframe').get(0).contentWindow.document.write('<body></body>');
                $modal.find('iframe').attr('src', this.#config.modal.url);
                $modal.find('iframe').on('load', () => $modal.dimmer('hide'));
                $modal.dimmer('show').modal('show');
            });

            // Delete button
            $('._gestor-widgetImage-btn-del').on('mouseup tap', (e) => {
                if (e.which !== 1 && e.which !== 0 && e.which !== undefined) return false;

                const $pai = $(e.currentTarget).closest('._gestor-widgetImage-cont');
                const def = this.#config.padroes;

                $pai.find('input._gestor-widgetImage-file-id').val(def.fileId);
                $pai.find('input._gestor-widgetImage-file-caminho').val(def.caminho);
                $pai.find('.widgetImage-image').attr('src', def.imgSrc);
                $pai.find('.widgetImage-nome').html(def.nome);
                $pai.find('.widgetImage-data .icon').get(0).nextSibling?.remove();
                $pai.find('.widgetImage-data').append(def.data);
                $pai.find('.widgetImage-tipo .icon').get(0).nextSibling?.remove();
                $pai.find('.widgetImage-tipo').append(def.tipo);
            });

            // iframe postMessage listener
            window.addEventListener('message', (e) => {
                try {
                    const data = JSON.parse(e.data);
                    if (!['admin-arquivos', 'arquivos'].includes(data.moduloId)) return;

                    const dados = JSON.parse(decodeURI(data.data));

                    if (dados.tipo?.match(/image\//) === 'image/') {
                        const $pai = this.#objPai;
                        $pai.find('input._gestor-widgetImage-file-id').val(dados.id);
                        $pai.find('input._gestor-widgetImage-file-caminho').val(dados.caminho);
                        $pai.find('.widgetImage-image').attr('src', dados.imgSrc);
                        $pai.find('.widgetImage-nome').html(dados.nome);
                        $pai.find('.widgetImage-data .icon').get(0).nextSibling?.remove();
                        $pai.find('.widgetImage-data').append(dados.data);
                        $pai.find('.widgetImage-tipo .icon').get(0).nextSibling?.remove();
                        $pai.find('.widgetImage-tipo').append(dados.tipo);
                        $('.ui.modal.iframePagina').modal('hide');
                    } else {
                        AlertManager.show(this.#config.alertas?.naoImagem || 'Not an image');
                    }
                } catch { /* ignore non-JSON messages */ }
            });
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                   WIDGET: TemplatePick                      ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Widget de seleção de templates via iframe modal.
     */
    class TemplatePickWidget {
        #config;
        #objPai = null;
        #started = false;

        constructor(config) {
            this.#config = config;
        }

        init() {
            if (!$('._gestor-widgetTemplate-cont').length) return;

            $('._gestor-widgetTemplate-btn-change').on('mouseup tap', (e) => {
                if (e.which !== 1 && e.which !== 0 && e.which !== undefined) return false;

                if (!this.#started) {
                    this.#started = true;
                    $('.iframePagina .header').html(this.#config.modal.head);
                    $('.iframePagina .cancel.button').html(this.#config.modal.cancel);
                }

                this.#objPai = $(e.currentTarget).closest('._gestor-widgetTemplate-cont');

                const $modal = $('.ui.modal.iframePagina');
                $modal.find('iframe').get(0).contentWindow.document.write('<body></body>');
                $modal.find('iframe').attr('src', this.#config.modal.url);
                $modal.find('iframe').on('load', () => $modal.dimmer('hide'));
                $modal.dimmer('show').modal('show');
            });

            window.addEventListener('message', (e) => {
                try {
                    const data = JSON.parse(e.data);
                    if (data.moduloId !== 'templates') return;

                    const dados = JSON.parse(decodeURI(data.data));
                    const $pai = this.#objPai;

                    $pai.find('input.widgetTemplate-templateId').val(dados.templateId);
                    $pai.find('input.widgetTemplate-templateTipo').val(dados.templateTipo);
                    $pai.find('.widgetTemplate-image').attr('src', dados.imgSrc);
                    $pai.find('.widgetTemplate-nome').html(dados.nome);
                    $pai.find('.widgetTemplate-data .icon').get(0).nextSibling?.remove();
                    $pai.find('.widgetTemplate-data').append(dados.data);
                    $pai.find('.widgetTemplate-tipo .icon').get(0).nextSibling?.remove();
                    $pai.find('.widgetTemplate-tipo').append(dados.tipo);
                    $('.ui.modal.iframePagina').modal('hide');
                } catch { /* ignore */ }
            });
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                    DATATABLE LISTING                        ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Gerencia tabela DataTable com server-side processing.
     */
    class DataTableManager {
        #tableId;
        #instance = null;
        #deleteConfirm;

        constructor(tableId, deleteConfirm) {
            this.#tableId = tableId;
            this.#deleteConfirm = deleteConfirm;
        }

        /**
         * Inicializa o DataTable como server-side.
         * @param {Object} lista Configuração da lista (vem do PHP via gestor.*)
         */
        init(lista) {
            if (!$('#' + this.#tableId).length) return;

            // Language pack
            let language = null;
            if (gestor.language === 'pt-br') {
                language = { url: gestor.raiz + 'datatables/1.10.23/pt_br.json' };
            }

            this.#instance = $('#' + this.#tableId).DataTable({
                processing: true,
                serverSide: true,
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: (row) => {
                                const data = row.data();
                                return 'Detalhes do registro: ' + data[lista.id];
                            },
                        }),
                        renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                            tableClass: 'ui celled table responsive nowrap unstackable',
                        }),
                    },
                },
                deferLoading: parseInt(lista.deferLoading),
                pageLength: parseInt(lista.pageLength),
                displayStart: parseInt(lista.displayStart),
                columns: lista.columns,
                order: lista.order,
                ajax: {
                    url: gestor.raiz + lista.url,
                    type: 'POST',
                    data: (d) => {
                        d.opcao = 'listar';
                        d.ajax = 'true';
                        d.ajaxOpcao = 'listar';
                        d.columnsExtraSearch = lista.columnsExtraSearch;
                    },
                },
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    {
                        targets: -1,
                        responsivePriority: 2,
                        className: 'dt-head-center',
                        render: (data, type, row) => this.#renderActions(data, row, lista),
                    },
                ],
                language,
                initComplete: function () {
                    const api = this.api();
                    let searchInterval;

                    $('.dataTables_filter input')
                        .unbind()
                        .bind('input', function (e) {
                            const $input = $(this);
                            if (searchInterval) clearInterval(searchInterval);

                            searchInterval = setInterval(() => {
                                const term = $input.val();
                                if (term.length === 0 || term.length >= 3 || e.keyCode === 13) {
                                    clearInterval(searchInterval);
                                    searchInterval = null;
                                    api.search(term).draw();
                                }
                            }, 750);
                        });

                    // Ocultar coluna opções se não há ações
                    if (!lista.opcoes) {
                        const dtInstance = $('#' + this.#tableId).DataTable();
                        dtInstance.column(-1).visible(false);
                    }

                    // Largura 100%
                    $('#' + this.#tableId).css('width', '100%');
                },
                drawCallback: () => {
                    $('.buttons .button').popup({
                        delay: { show: 150, hide: 0 },
                        position: 'top center',
                        variation: 'inverted',
                    });
                },
            });

            // Error handler  
            $.fn.dataTable.ext.errMode = (settings) => {
                if (settings.jqXHR?.status === 401) {
                    window.open(gestor.raiz + (settings.jqXHR.responseJSON?.redirect || 'signin/'), '_self');
                }
            };

            // Delete handler
            $(document.body).on('mouseup tap', '.excluir', (e) => {
                if (e.which !== 1 && e.which !== 0 && e.which !== undefined) return false;
                this.#deleteConfirm.setUrl($(e.currentTarget).attr('data-href'));
                this.#deleteConfirm.show();
            });

            // CTRL+F → search focus
            $(document).keydown((event) => {
                if ((event.ctrlKey || event.metaKey) && event.which === 70) {
                    $('input[type="search"]').focus();
                    event.preventDefault();
                    return false;
                }
            });
        }

        /**
         * Renderiza ações (botões) por registro.
         */
        #renderActions(data, row, lista) {
            if (!lista.opcoes) return '';

            let botoes = '';
            const status = lista.status ? row[lista.status] : null;

            for (const id in lista.opcoes) {
                const opc = lista.opcoes[id];

                if (opc.opcao === 'status') {
                    if (opc.status_atual === status) {
                        botoes += `<a class="ui button ${opc.cor}" href="?opcao=${opc.opcao}&status=${opc.status_mudar}&id=${data}" data-content="${opc.tooltip}" data-id="${id}"><i class="${opc.icon} icon"></i></a>`;
                    }
                } else if (opc.url) {
                    botoes += `<a class="ui button ${opc.cor}" href="${opc.url}?id=${data}" data-content="${opc.tooltip}" data-id="${id}"><i class="${opc.icon} icon"></i></a>`;
                } else if (id === 'excluir') {
                    botoes += `<div class="ui button ${opc.cor} excluir" data-href="?opcao=${opc.opcao}&id=${data}" data-content="${opc.tooltip}" data-id="${id}"><i class="${opc.icon} icon"></i></div>`;
                } else {
                    botoes += `<a class="ui button ${opc.cor}" href="?opcao=${opc.opcao}&id=${data}" data-content="${opc.tooltip}" data-id="${id}"><i class="${opc.icon} icon"></i></a>`;
                }
            }

            return `<div class="ui icon buttons">${botoes}</div>`;
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                   COMMON UI BEHAVIORS                       ║
    // ╚══════════════════════════════════════════════════════════════╝

    /**
     * Comportamentos comuns de UI reutilizáveis.
     */
    class CommonUI {
        /**
         * Ativa atalho CTRL+S para submeter formulário.
         */
        static ctrlSave() {
            $(document).keydown((event) => {
                if ((event.ctrlKey || event.metaKey) && event.which === 83) {
                    $('.interfaceFormPadrao').form('submit');
                    event.preventDefault();
                    return false;
                }
            });
        }

        /**
         * Inicializa tooltips nos botões.
         */
        static tooltips(selector = '.segment .button') {
            $(selector).popup({
                delay: { show: 150, hide: 0 },
                position: 'top right',
                variation: 'inverted',
            });
        }

        /**
         * Inicializa dropdowns do Fomantic-UI.
         */
        static dropdowns() {
            $('.ui.dropdown').dropdown();
        }

        /**
         * Hack: sincroniza checkboxes com data-checked attribute.
         */
        static checkboxes() {
            $('.checkbox').checkbox();

            $('input[type="checkbox"]').each(function () {
                const checked = $(this).attr('data-checked');
                if (checked === 'checked') {
                    $(this).prop('checked', true);
                }
            });

            gestor.checkboxesReady = true;
        }

        /**
         * Inicializa backup dropdown com AJAX onChange.
         */
        static backupDropdown() {
            $('.backupDropdown').dropdown({
                onChange: function (value) {
                    const $this = $(this);
                    $.ajax({
                        type: 'POST',
                        url: gestor.raiz + gestor.moduloId + '/',
                        data: {
                            opcao: 'editar',
                            ajax: 'sim',
                            ajaxOpcao: 'backup-campos-mudou',
                            ajaxRegistroId: gestor.moduloRegistroId,
                            campo: $this.attr('data-campo'),
                            id_numerico: $this.attr('data-id'),
                            id: value,
                        },
                        dataType: 'json',
                        beforeSend: () => loading.open(),
                        success: (dados) => {
                            if (dados.status === 'Ok') {
                                $('#gestor-listener').trigger($this.attr('data-callback'), {
                                    valor: dados.valor,
                                    campo: $this.attr('data-campo-form'),
                                });
                            } else {
                                console.error('interface-v2: backup error', dados);
                            }
                            loading.close();
                        },
                        error: (txt) => {
                            if (txt.status === 401) {
                                window.open(gestor.raiz + (txt.responseJSON?.redirect || 'signin/'), '_self');
                            } else {
                                console.error('interface-v2: backup AJAX error', txt);
                                loading.close();
                            }
                        },
                    });
                },
            });
        }
    }

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                    INSTANCES GLOBAIS                        ║
    // ╚══════════════════════════════════════════════════════════════╝

    const debounce = new InputDebounce(gestor.input_delay_timeout || 600);
    const loading = new LoadingManager();
    const deleteConfirm = new DeleteConfirm();

    // Configuração v2 (do PHP)
    const iv2 = gestor['interface-v2'] || {};

    const formManager = new FormManager(iv2);

    // Expor no jQuery para compatibilidade
    $.dropdown = CommonUI.dropdowns;
    $.formReiniciar = (p) => formManager.reinit(p);
    $.formSubmit = (p) => formManager.submit(p?.id);
    $.formSubmitNormal = () => $('.ui.form.interfaceFormPadrao').submit();

    $.input_delay_to_change = function (p) {
        debounce.run('input_change', () => {
            $(p.trigger_selector).trigger(p.trigger_event, [
                p.value,
                p.obj_ref !== undefined ? { obj: p.obj_ref } : gestor.input_delay_params,
            ]);
        });
    };

    // ╔══════════════════════════════════════════════════════════════╗
    // ║                     MAIN INITIALIZATION                     ║
    // ╚══════════════════════════════════════════════════════════════╝

    function interfaceV2Start() {

        // ===== Widgets =====

        if (gestor.interface?.imagepick) {
            new ImagePickWidget(gestor.interface.imagepick).init();
        }

        if (gestor.interface?.templates) {
            new TemplatePickWidget(gestor.interface.templates).init();
        }

        // ===== Autorização Provisória =====

        if ($('.autorizacaoProvisoria').length) {
            $('.ui.modal.autorizacaoProvisoria').modal({
                closable: false,
                onApprove: () => false,
                onDeny: () => false,
            }).modal('show');
        }

        // ===== ADICIONAR =====

        if ($('#_gestor-interface-insert-dados').length) {
            CommonUI.ctrlSave();
            CommonUI.dropdowns();
            formManager.init();
            CommonUI.tooltips();
            CommonUI.checkboxes();
        }

        // ===== VISUALIZAR =====

        if ($('#_gestor-interface-visualizar-dados').length) {
            new HistoryLoader('#_gestor-interface-edit-historico-mais');
        }

        // ===== CONFIG / SIMPLES =====

        if ($('#_gestor-interface-config-dados').length || $('#_gestor-interface-simples').length) {
            CommonUI.ctrlSave();
            new HistoryLoader('#_gestor-interface-edit-historico-mais', true);
            CommonUI.tooltips();
        }

        // ===== EDITAR =====

        if ($('#_gestor-interface-edit-dados').length) {
            CommonUI.ctrlSave();
            CommonUI.dropdowns();
            CommonUI.backupDropdown();
            formManager.init();
            CommonUI.tooltips();
            CommonUI.checkboxes();

            // Delete handler
            $(document.body).on('mouseup tap', '.excluir', (e) => {
                if (e.which !== 1 && e.which !== 0 && e.which !== undefined) return false;
                deleteConfirm.setUrl($(e.currentTarget).attr('data-href'));
                deleteConfirm.show();
            });

            new HistoryLoader('#_gestor-interface-edit-historico-mais');
        }

        // ===== LISTAR =====

        if ($('#_gestor-interface-lista-tabela').length) {
            const lista = iv2?.lista || gestor.interface?.lista;

            if (lista) {
                const dtManager = new DataTableManager('_gestor-interface-lista-tabela', deleteConfirm);
                dtManager.init(lista);
            }
        }

        // ===== Popup de botões na listagem =====

        if ($('#_gestor-interface-listar').length) {
            CommonUI.tooltips();
        }

        // ===== Alerta automático =====

        const alertaMsg = iv2?.alerta || gestor.interface?.alerta || gestor.interface?.alert;
        if (alertaMsg) {
            AlertManager.show(alertaMsg.msg || alertaMsg);
        }

        // ===== Triggers globais =====

        $('#gestor-listener')
            .on('carregar_abrir', () => loading.open())
            .on('carregar_fechar', () => loading.close())
            .on('alerta', (e, p) => AlertManager.show(p?.msg || p));
    }

    interfaceV2Start();
});
