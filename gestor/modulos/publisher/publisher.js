$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {
        // Inicializar gestor.template se não existir
        if (!gestor.template) {
            gestor.template = {
                fieldSets: { available: [], missing: [], linked: [] },
                currentPublisherFields: [],
                currentTemplateFields: []
            };
        }
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

        function msg_erro_resetar() {
            $('#error-message').hide();
        }

        function msg_erro_mostrar(mensagem) {
            $('#error-message-content').text(mensagem);
            $('#error-message').show();
        }

        // ===== Dropdown de Templates

        $('.templateDropdown')
            .dropdown({
                onChange: function (value, text, $choice) {
                    setTimeout(function () {
                        templateLoading(value);
                    }, 100);
                }
            });

        // Carregar template inicial se houver
        var initialValue = $('.templateDropdown').dropdown('get value');
        if (initialValue) {
            templateLoading(initialValue);
        }

        $('#add-new-template').on('click', function () {
            $('.template-options-wrapper').show();
            var defaultName = $('#template-skeletons .msg-novo-template').text();
            $('#template-name').val(defaultName);
            // Carregar template padrão para mostrar campos disponíveis
            templateLoading('noticias-simples');
        });

        function templateLoading(template_id) {
            // Mostrar/esconder template-options-wrapper baseado na seleção
            if (template_id) {
                $('.template-options-wrapper').show();
            } else {
                $('.template-options-wrapper').hide();
                return;
            }

            const ajax = ajaxDefault;

            ajax.ajaxOpcao = 'template-load';
            ajax.data.ajaxOpcao = ajax.ajaxOpcao;
            ajax.data.params = {
                template_id,
                fields_schema: $('input[name="fields_schema"]').val() || '[]'
            };

            ajax.successCallback = function (response) {
                if (response.modelo) {
                    // Atualizar nome do template
                    $('#template-name').val(response.modelo.name || '');

                    // Atualizar variáveis globais - apenas campos do template
                    gestor.template.currentTemplateFields = response.campos || [];

                    // Atualizar listas e searches
                    recalculateFieldSets();
                }
            };

            ajax.successNotOkCallback = function (response) {
                var msgPrefix = $('#template-skeletons .msg-erro-carregar-template').text();
                var msgUnknown = $('#template-skeletons .msg-erro-desconhecido').text();
                msg_erro_mostrar(msgPrefix + (response.message || msgUnknown));
            };

            $.ajax(ajax);
        }

        function checkFieldStatus(publisherFields, templateFields) {
            if (!publisherFields || !templateFields) {
                $('#field-status-message').hide();
                $('#field-status-ok').hide();
                return;
            }

            const templateFieldIds = templateFields.map(f => f.id);
            const allMapped = publisherFields.every(pf => {
                const selected = $(`.field-mapping[data-publisher-field="${pf.id}"]`).val();
                return selected && templateFieldIds.includes(selected);
            });

            if (allMapped) {
                $('#field-status-ok').show();
                $('#field-status-message').hide();
            } else {
                $('#field-status-message').show();
                $('#field-status-ok').hide();
            }
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

        // ===== Format slug ID

        $(document.body).on('keyup', '.field-label', function (e) {
            if (e.which == 9) return false;

            var parentRow = $(this).closest('.field-row');
            var value = $(this).val();

            $.input_delay_to_change({
                trigger_selector: '#gestor-listener',
                trigger_event: 'slug-change',
                value: { value, parentRow }
            });
        });

        $(document.body).on('slug-change', '#gestor-listener', function (e, value, p) {
            if (!p) p = {};

            var slug = formatar_slug(value.value);
            value.parentRow.find('.field-id').val(slug);
            value.parentRow.find('.field-id-display').text(slug);

            recalculateFieldSets();
        });

        function formatar_slug(slug) {
            slug = slug.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
            slug = slug.replace(/[^a-zA-Z0-9 \-]/g, ''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço.
            slug = slug.toLowerCase(); // Passar para letras minúsculas
            slug = slug.trim(); // Remover espaço do início e fim.
            slug = slug.replace(/\s/g, '-'); // Trocar todos os espaços por traço.
            slug = slug.replace(/\-{2,}/g, '-'); // Remover a repetição de traços para um único traço.

            return slug.length > 0 ? slug : '';
        }

        // ===== Publisher Fields Schema Management

        var schemaContainer = $('#fields-schema-container');
        var addFieldBtn = $('#add-field-btn');
        var hiddenInput = $('input[name="fields_schema"]');

        function addFieldRow(data = {}) {
            var id = data.id || '';
            var name = data.name || data.label || '';
            var type = data.type || 'text';
            var mandatory = data.mandatory ? true : false;
            var template_field_id = data.template_field_id || '';

            const rowClone = $('#template-skeletons .field-row-skeleton').clone();
            rowClone.find('.field-label').val(name);
            rowClone.find('.field-id').val(id);
            rowClone.find('.field-type').val(type);
            if (mandatory) {
                rowClone.find('.field-mandatory').prop('checked', true);
            }
            rowClone.find('.field-template-id').val(template_field_id);
            rowClone.find('.field-id-display').text(formatar_slug(name));

            schemaContainer.append(rowClone);

            // Inicializar dropdowns, search e checkbox.
            rowClone.find('.dropdownTemplate').removeClass('dropdownTemplate').addClass('ui dropdown').dropdown({
                onChange: function (value, text, $choice) {
                    recalculateFieldSets();
                }
            });
            rowClone.find('.field-template').closest('.ui.search').search({
                highlightMatches: true,
                source: gestor.template.currentTemplateFields.map(f => ({ title: `@[[publisher#${f.type}#${f.id}]]@`, value: f.id })),
                onSelect: function (result, response) {
                    $(this).closest('.field-row').find('.field-template-id').val(result.value);
                    $(this).val(result.title);
                    recalculateFieldSets();
                }
            });
            rowClone.find('.field-template').closest('.ui.search').find('.remove.icon').on('mouseup tap', function (e) {
                if (e.which != 1 && e.which != 0 && e.which != undefined) return false;

                $(this).closest('.field-row').find('.field-template-id').val('');
                recalculateFieldSets();
            });
            rowClone.find('.ui.checkbox').checkbox();

            // Atualizar searches e recalcular
            recalculateFieldSets();
        }

        function updateFieldLists() {
            // Campos Disponíveis: mostrar nome do campo do template
            $('#available-fields-list').html(gestor.template.fieldSets.available.map(f => `<div class="item"><kbd class="ui label">@[[publisher#${f.type}#${f.id}]]@</kbd></div>`).join('') || `<div class="item" style="color:#999">${$('#template-skeletons .msg-nenhum-campo-template').text()}</div>`);
            // Campos Ausentes: mostrar variável do publisher sem vinculação
            $('#missing-fields-list').html(gestor.template.fieldSets.missing.map(pf => `<div class="item"><kbd class="ui label">@[[publisher#${pf.type}#${pf.id}]]@</kbd></div>`).join('') || `<div class="item" style="color:#999">${$('#template-skeletons .msg-nenhum-campo-publisher').text()}</div>`);
            // Campos Vinculados: mostrar variável do publisher com vinculação
            $('#linked-fields-list').html(gestor.template.fieldSets.linked.map(pf => `<div class="item"><kbd class="ui label">@[[publisher#${pf.type}#${pf.id}]]@</kbd><kbd class="ui teal icon label"><i class="exchange alternate icon"></i></kbd><kbd class="ui label">@[[publisher#${pf.type}#${pf.id}]]@</kbd></div>`).join('') || `<div class="item" style="color:#999">${$('#template-skeletons .msg-nenhum-campo-vinculado').text()}</div>`);
        }

        function recalculateFieldSets() {
            const container = $('#fields-schema-container');
            // Ler campos do publisher diretamente do DOM
            var publisherFields = [];
            container.find('.field-row').each(function () {
                var id = $(this).find('.field-id').val();
                var type = $(this).find('.field-type').dropdown('get value');
                var template_field_id = $(this).find('.field-template-id').val();
                if (id) {
                    publisherFields.push({ id: id, type: type, template_field_id: template_field_id });
                }
            });

            // Campos vinculados: campos do publisher que têm template_field_id
            var linkedPublisherFields = publisherFields.filter(pf => pf.template_field_id);
            var linkedTemplateIds = linkedPublisherFields.map(pf => pf.template_field_id);

            // Campos Disponíveis: campos do template que NÃO estão vinculados
            gestor.template.fieldSets.available = gestor.template.currentTemplateFields.filter(f => !linkedTemplateIds.includes(f.id));

            // Campos Ausentes: campos do publisher que NÃO têm vinculação
            gestor.template.fieldSets.missing = publisherFields.filter(pf => !pf.template_field_id);

            // Campos Vinculados: campos do publisher que TÊM vinculação
            gestor.template.fieldSets.linked = linkedPublisherFields;

            updateFieldLists();
        }

        // Add Button
        addFieldBtn.on('click', function (e) {
            e.preventDefault();
            addFieldRow();
        });

        // Remove Button
        $(document).on('click', '.remove-field-btn', function () {
            $(this).closest('.field-row').remove();
            recalculateFieldSets();
        });

        // Load Initial Data
        if (typeof publisher_initial_schema !== 'undefined' && Array.isArray(publisher_initial_schema)) {
            // Wait for DOM slightly or just run
            publisher_initial_schema.forEach(function (field) {
                addFieldRow(field);
            });
            recalculateFieldSets();
        }

        // Intercept Form Submit
        // We use a general listener on the form submit
        $('.ui.form').on('submit', function () {
            var schema = [];
            $('.field-row').each(function () {
                var row = $(this);
                // Get dropdown value correctly from select or semantic ui 
                var typeVal = row.find('.field-type').dropdown('get value');

                var field = {
                    id: row.find('.field-id').val(),
                    label: row.find('.field-label').val(),
                    type: typeVal,
                    mandatory: row.find('.field-mandatory').is(':checked'),
                    template_field_id: row.find('.field-template-id').val()
                };
                if (field.id && field.label) {
                    schema.push(field);
                }
            });
            hiddenInput.val(JSON.stringify(schema));
            return true;
        });

        // ===== Event listeners para botões do template
        $('#save-template').on('click', function () {
            alert('Funcionalidade de salvar template ainda não implementada.');
        });

        $('#edit-template').on('click', function () {
            alert('Funcionalidade de editar template ainda não implementada.');
        });

        $('#duplicate-template').on('click', function () {
            alert('Funcionalidade de duplicar template ainda não implementada.');
        });

        $('#delete-template').on('click', function () {
            if (confirm('Tem certeza que deseja excluir este template?')) {
                alert('Funcionalidade de excluir template ainda não implementada.');
            }
        });

        // Atualizar status dos campos quando mapeamento muda
        $(document).on('change', '.field-mapping', function () {
            checkFieldStatus(currentPublisherFields, currentTemplateFields);
        });
    }
});
