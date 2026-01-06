$(document).ready(function () {
    if ($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0) {

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
