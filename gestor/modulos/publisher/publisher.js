$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {
        // ===== Ajax Default

        var ajaxDefault = {
            type: 'POST',
            url: gestor.raiz + gestor.moduloCaminho + '/',
            ajaxOpcao: 'ajaxOpcao',
            data: {
                opcao: gestor.moduloOpcao,
                ajax: 'sim'
            },
            dataType: 'json',
            beforeSend: function () {
                loadDimmer(true);
                msg_erro_resetar();
            },
            success: function (dados) {
                switch (dados.status) {
                    case 'Ok':
                        this.successCallback(dados);
                        break;
                    default:
                        this.successNotOkCallback(dados);
                        console.log('ERROR - ' + this.ajaxOpcao + ' - ' + dados.status);

                }

                loadDimmer(false);
            },
            error: function (txt) {
                switch (txt.status) {
                    case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"), "_self"); break;
                    default:
                        console.log('ERROR AJAX - ' + this.ajaxOpcao + ' - Dados:');
                        console.log(txt);
                        loadDimmer(false);
                }
            },
            successCallback: function (response) { },
            successNotOkCallback: function (response) { }
        };

        function loadDimmer(show = true) {
            if (show) {
                $('.template-options-wrapper .dimmer').addClass('active');
            } else {
                $('.template-options-wrapper .dimmer').removeClass('active');
            }
        }

        // ===== Dropdown de Templates

        $('.dropdown')
            .dropdown({
                onChange: function (value, text, $choice) {
                    setTimeout(function () {
                        templateLoading();
                    }, 100);
                }
            });

        function templateLoading() {
            loadDimmer(true);

            setTimeout(function () {
                loadDimmer(false);
            }, 500);

            return true;

            const ajax = ajaxDefault;
            ajax.ajaxOpcao = 'html-editor-templates-load';
            ajax.data.ajaxOpcao = ajax.ajaxOpcao;
            ajax.data.params = {
                pagina: modelos_pagina,
                limite: 20,
                alvo: ('alvo' in gestor.html_editor ? gestor.html_editor.alvo : 'paginas'),
                framework_css
            };

            ajax.successCallback = function (response) {
                if (response.data && response.data.modelos) {
                    modelosRenderizar(response.data.modelos, response.data.tem_mais);

                    if (response.data.tem_mais) {
                        $('#modelos-load-more').show();
                    } else {
                        $('#modelos-load-more').hide();
                    }
                }
            };

            ajax.successNotOkCallback = function (response) {
                $('#modelos-loading').hide();

                if (response !== undefined && 'status' in response && response.status === 'error') {
                    msg_erro_mostrar(response.message);
                } else {
                    msg_erro_mostrar('Erro ao carregar modelos de página.');
                }
            };

            $.ajax(ajax);
        }

        // ===== Input delay

        $.input_delay_to_change = function (p) {
            if (!gestor.input_delay) {
                gestor.input_delay = new Array();
                gestor.input_delay_count = 0;
            }

            gestor.input_delay_count++;

            var valor = gestor.input_delay_count;

            gestor.input_delay.push(valor);
            gestor.input_value = p.value;

            setTimeout(function () {
                if (gestor.input_delay[gestor.input_delay.length - 1] == valor) {
                    input_change_after_delay({ value: gestor.input_value, trigger_selector: p.trigger_selector, trigger_event: p.trigger_event });
                }
            }, gestor.input_delay_timeout);
        }

        function input_change_after_delay(p) {
            $(p.trigger_selector).trigger(p.trigger_event, [p.value, gestor.input_delay_params]);

            gestor.input_delay = false;
        }

        function input_delay() {
            if (!gestor.input_delay_timeout) gestor.input_delay_timeout = 400;

        }

        input_delay();

        // ===== Format caminho pré-fixo

        $(document.body).on('opcao-change', '#gestor-listener', function (e, value, p) {
            if (!p) p = {};

            if (value.length > 0) {
                $('input[name="path_prefix"]').val(formatar_url(value));
            } else {
                $('input[name="path_prefix"]').val(formatar_url($('input[name="name"]').val()));
            }
        });

        $(document.body).on('keyup', 'input[name="name"]', function (e) {
            if (e.which == 9) return false;

            var value = $(this).val();

            $.input_delay_to_change({
                trigger_selector: '#gestor-listener',
                trigger_event: 'caminho-change',
                value: value
            });
        });

        $(document.body).on('caminho-change', '#gestor-listener', function (e, value, p) {
            if (!p) p = {};

            $('input[name="path_prefix"]').val(formatar_url(value));
        });

        function formatar_url(url) {
            url = url.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
            url = url.replace(/[^a-zA-Z0-9 \-\/]/g, ''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço ou barra.
            url = url.toLowerCase(); // Passar para letras minúsculas
            url = url.trim(); // Remover espaço do início e fim.
            url = url.replace(/\s/g, '-'); // Trocar todos os espaços por traço.
            url = url.replace(/\-{2,}/g, '-'); // Remover a repetição de traços para um único traço.
            url = url.replace(/\/{2,}/g, '/'); // Remover a repetição de barras para uma única barra.

            // Sempre adicionar uma barra no final, ou retornar apenas "/" se estiver vazio
            return url.length > 0 ? url + '/' : '/';
        }

        // ===== Publisher Fields Schema Management

        var schemaContainer = $('#fields-schema-container');
        var addFieldBtn = $('#add-field-btn');
        var hiddenInput = $('input[name="fields_schema"]');

        function addFieldRow(data = {}) {
            var id = data.id || '';
            var label = data.label || '';
            var type = data.type || 'text';
            var mandatory = data.mandatory ? 'checked' : '';
            var placeholder = data.placeholder || '';

            // Fix for dropdown value retrieval if passed via data
            // Semantic UI dropdown handling is needed after append.

            var rowHtml = `
                <div class="ui segment field-row" style="margin-bottom: 10px;">
                    <div class="fields">
                        <div class="four wide field">
                            <label>Label</label>
                            <input type="text" class="field-label" value="${label}" placeholder="Ex: Título">
                        </div>
                        <div class="four wide field">
                            <label>ID (Slug)</label>
                            <div class="ui right labeled input">
                                <input type="text" class="field-id" value="${id}" placeholder="Ex: titulo">
                                <div class="ui label help-popup" data-content="Use este ID no template: @[[publisher#${id}]]@">?</div>
                            </div>
                        </div>
                        <div class="four wide field">
                            <label>Tipo</label>
                            <select class="ui dropdown field-type">
                                <option value="text">Texto Curto</option>
                                <option value="textarea">Texto Longo</option>
                                <option value="html">HTML (Rich Text)</option>
                                <option value="image">Imagem</option>
                            </select>
                        </div>
                        <div class="three wide field">
                            <label>Obrigatório</label>
                            <div class="ui checkbox">
                                <input type="checkbox" class="field-mandatory" ${mandatory}>
                                <label>Sim</label>
                            </div>
                        </div>
                        <div class="one wide field" style="display: flex; align-items: flex-end;">
                            <button type="button" class="ui icon red button remove-field-btn"><i class="trash icon"></i></button>
                        </div>
                    </div>
                </div>
            `;

            var $row = $(rowHtml);
            schemaContainer.append($row);

            // Set dropdown value
            $row.find('.field-type').val(type);
            $row.find('.ui.dropdown').dropdown();
            $row.find('.ui.checkbox').checkbox();
            $row.find('.help-popup').popup();
        }

        // Auto-slug ID from Label
        $(document).on('keyup', '.field-label', function () {
            var row = $(this).closest('.field-row');
            var idInput = row.find('.field-id');
            var helpLabel = row.find('.help-popup');

            // Only auto-update if ID is empty or was auto-generated (we can track this roughly)
            // Or just check if ID matches a slugified version of previous label... let's keep it simple: if ID is empty, update.
            if (idInput.val() === '') {
                var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
                idInput.val(slug);
                helpLabel.attr('data-content', 'Use este ID no template: @[[publisher#' + slug + ']]@');
            }
        });

        $(document).on('keyup', '.field-id', function () {
            var slug = $(this).val();
            var helpLabel = $(this).siblings('.help-popup');
            helpLabel.attr('data-content', 'Use este ID no template: @[[publisher#' + slug + ']]@');
        });

        // Add Button
        addFieldBtn.on('click', function (e) {
            e.preventDefault();
            addFieldRow();
        });

        // Remove Button
        $(document).on('click', '.remove-field-btn', function () {
            $(this).closest('.field-row').remove();
        });

        // Load Initial Data
        if (typeof publisher_initial_schema !== 'undefined' && Array.isArray(publisher_initial_schema)) {
            // Wait for DOM slightly or just run
            publisher_initial_schema.forEach(function (field) {
                addFieldRow(field);
            });
        }

        // Intercept Form Submit
        // We use a general listener on the form submit
        $('.ui.form').on('submit', function () {
            var schema = [];
            $('.field-row').each(function () {
                var row = $(this);
                // Get dropdown value correctly from select or semantic ui 
                var typeVal = row.find('.field-type').val();

                var field = {
                    id: row.find('.field-id').val(),
                    label: row.find('.field-label').val(),
                    type: typeVal,
                    mandatory: row.find('.field-mandatory').is(':checked')
                };
                if (field.id && field.label) {
                    schema.push(field);
                }
            });
            hiddenInput.val(JSON.stringify(schema));
            return true;
        });
    }
});
