(function (window, $) {
    'use strict';

    function PublisherHighlightsCustomDropdown(options) {
        this.options = $.extend(true, {
            selectSelector: '#selected_items',
            generatedAttr: 'data-dropdown-generated-manual',
            language: function () { return 'en'; },
            getPublisherId: function () { return ''; },
            getSelectedIds: function () { return []; },
            setSelectedIds: function () { },
            fetchSearchResults: function (publisherId, query, done) { done([]); },
            fetchHydratedResults: function (publisherId, ids, done) { done([]); },
            onDropdownShown: function () { }
        }, options || {});

        this.ready = false;
        // req-011 item 1: array interno na ordem cronológica de cliques.
        // Fomantic UI reordena os <option> conforme o DOM ao adicionar/remover,
        // perdendo a ordem cronológica original. Mantemos aqui o estado correto.
        this.selectedIds = [];
        // req-013 item 1: flag para suprimir callbacks durante mutações programáticas
        // (change values / clear / set exactly em setValues). Esses comandos disparam
        // onAdd/onRemove em cascata, corrompendo `selectedIds` e o preview.
        this.ignoreCallbacks = false;
    }

    PublisherHighlightsCustomDropdown.prototype.refs = function () {
        var $sel = $(this.options.selectSelector);
        var $dd = $sel.parent('.ui.dropdown');

        if ($dd.length === 0) $dd = $sel;

        return { $sel: $sel, $dd: $dd };
    };

    PublisherHighlightsCustomDropdown.prototype.noResultsMessage = function () {
        return this.options.language() === 'pt-br' ? 'Nenhum item encontrado' : 'No results found.';
    };

    PublisherHighlightsCustomDropdown.prototype.settings = function () {
        var self = this;
        return {
            forceSelection: false,
            allowAdditions: false,
            fullTextSearch: true,
            preserveHTML: false,
            message: {
                noResults: this.noResultsMessage()
            },
            // req-011 item 1: callbacks de adição/remoção para manter a ordem cronológica
            // dos cliques em `self.selectedIds`. A ordem do <select> reflete o DOM (estrutural)
            // e não a sequência de seleção do usuário.
            // req-013 item 1: a flag `ignoreCallbacks` suprime as ações enquanto setValues
            // está mutando o dropdown programaticamente — sem ela, change values/clear/set
            // exactly disparam onAdd/onRemove em cascata e corrompem o estado.
            onAdd: function (addedValue) {
                if (self.ignoreCallbacks) return;
                if (!addedValue) return;
                var idx = self.selectedIds.indexOf(addedValue);
                if (idx !== -1) self.selectedIds.splice(idx, 1);
                self.selectedIds.push(addedValue);
                self.options.setSelectedIds(self.selectedIds.slice());
            },
            onRemove: function (removedValue) {
                if (self.ignoreCallbacks) return;
                if (!removedValue) return;
                var idx = self.selectedIds.indexOf(removedValue);
                if (idx !== -1) self.selectedIds.splice(idx, 1);
                self.options.setSelectedIds(self.selectedIds.slice());
            }
        };
    };

    PublisherHighlightsCustomDropdown.prototype.applyMessages = function () {
        var currentRefs = this.refs();

        if (!currentRefs.$dd.length || currentRefs.$dd[0] === currentRefs.$sel[0]) return;

        currentRefs.$dd.dropdown('setting', 'message', {
            noResults: this.noResultsMessage()
        });
    };

    PublisherHighlightsCustomDropdown.prototype.readSelection = function () {
        var currentRefs = this.refs();
        var value;

        if (currentRefs.$sel.length === 0) return [];

        value = currentRefs.$sel.val();

        if (Array.isArray(value)) return value.filter(function (item) { return item !== ''; });
        if (typeof value === 'string' && value.length > 0) {
            return value.split(',').map(function (item) { return item.trim(); }).filter(Boolean);
        }

        return [];
    };

    PublisherHighlightsCustomDropdown.prototype.syncSelection = function () {
        // req-013 item 2: confiar SEMPRE em `this.selectedIds` (mantido pelos callbacks
        // onAdd/onRemove em ordem cronológica). O fallback antigo para `readSelection()`
        // quando o array estava vazio reidratava itens removidos a partir do DOM cru,
        // travando a pré-visualização ao desmarcar a última tag.
        // A flag `ignoreCallbacks` impede propagação durante mutações programáticas.
        if (this.ignoreCallbacks) return;
        var selectedIds = (this.selectedIds || []).slice();
        this.options.setSelectedIds(selectedIds);
    };

    PublisherHighlightsCustomDropdown.prototype.bindEvents = function () {
        var self = this;
        var currentRefs = self.refs();
        var $sel = currentRefs.$sel;
        var $dd = currentRefs.$dd;

        if ($sel.length === 0) return;

        $sel.off('change.hepManualItems').on('change.hepManualItems', function () {
            self.syncSelection();
        });

        $dd.off('click.hepManualItemsOpen').on('click.hepManualItemsOpen', function (event) {
            if ($(event.target).closest('.menu .item, .label .delete.icon, input.search').length === 0) {
                self.options.onDropdownShown();
            }
        });

        $dd.off('click.hepManualItemsSync').on('click.hepManualItemsSync', '.menu .item, .label .delete.icon', function () {
            setTimeout(function () {
                self.syncSelection();
            }, 0);
        });
    };

    PublisherHighlightsCustomDropdown.prototype.setValues = function (values, selectedIds) {
        var currentRefs = this.refs();
        var $sel = currentRefs.$sel;
        var $dd = currentRefs.$dd;

        if ($sel.length === 0) return;

        if (!this.ready) {
            $sel.dropdown(this.settings());
            currentRefs = this.refs();
            currentRefs.$dd.attr(this.options.generatedAttr, 'true');
            this.applyMessages();
            this.bindEvents();
            this.ready = true;
            $dd = currentRefs.$dd;
        }

        this.applyMessages();

        // req-013 item 1: suprimir callbacks durante a sequência programática de mutações.
        // change values / clear / set exactly disparam onAdd/onRemove em cascata; manter os
        // callbacks ativos aqui causa loops que corrompem `selectedIds` e a pré-visualização.
        this.ignoreCallbacks = true;
        try {
            $dd.dropdown('change values', values || []);
            $dd.dropdown('refresh');
            $dd.dropdown('clear', true);

            // req-011 item 1: sincronizar `this.selectedIds` ANTES de set exactly — a ordem
            // recebida no parâmetro é a ordem cronológica de referência que queremos preservar.
            this.selectedIds = (selectedIds || []).slice();

            if (selectedIds && selectedIds.length > 0) {
                $dd.dropdown('set exactly', selectedIds, true);
            }
        } finally {
            this.ignoreCallbacks = false;
        }
    };

    PublisherHighlightsCustomDropdown.prototype.search = function (query) {
        var self = this;
        var currentRefs = self.refs();
        var publisherId = self.options.getPublisherId();

        if (currentRefs.$sel.length === 0 || !publisherId) return;

        self.options.fetchSearchResults(publisherId, query, function (results) {
            var keepIds = (self.options.getSelectedIds() || []).slice();
            var values = [];
            var present = {};

            currentRefs.$sel.find('option').each(function () {
                var value = $(this).attr('value');
                var name;

                if (!value || keepIds.indexOf(value) === -1 || present[value]) return;

                name = $(this).attr('data-text') || $(this).text() || value;
                present[value] = true;
                values.push({ value: value, text: name, name: name });
            });

            (results || []).forEach(function (result) {
                var name;

                if (!result || !result.value || present[result.value]) return;

                name = result.name || result.text || result.value;
                present[result.value] = true;
                values.push({ value: result.value, text: name, name: name });
            });

            self.setValues(values, keepIds);
        });
    };

    PublisherHighlightsCustomDropdown.prototype.hydrate = function (publisherId, ids) {
        var self = this;

        self.options.fetchHydratedResults(publisherId, ids, function (results) {
            var byValue = {};
            var values;

            (results || []).forEach(function (result) {
                byValue[result.value] = result.name || result.value;
            });

            values = (ids || []).map(function (slug) {
                var name = byValue[slug] || slug;
                return { value: slug, text: name, name: name };
            });

            self.setValues(values, ids || []);
        });
    };

    PublisherHighlightsCustomDropdown.prototype.init = function (publisherId) {
        var selectedIds = this.options.getSelectedIds() || [];
        var currentRefs = this.refs();

        if (currentRefs.$sel.length === 0 || this.ready) return;

        this.setValues([], selectedIds);

        if (publisherId) {
            if (selectedIds.length > 0) {
                this.hydrate(publisherId, selectedIds);
            }
            this.search('');
        }
    };

    PublisherHighlightsCustomDropdown.prototype.reset = function (publisherId) {
        if (!this.refs().$sel.length) return;

        if (!this.ready) {
            this.init(publisherId || '');
            return;
        }

        this.setValues([], []);
        if (publisherId) this.search('');
    };

    PublisherHighlightsCustomDropdown.prototype.refresh = function () {
        var currentRefs = this.refs();

        if (currentRefs.$dd.length && currentRefs.$dd[0] !== currentRefs.$sel[0]) {
            this.applyMessages();
            currentRefs.$dd.dropdown('refresh');
        }
    };

    window.PublisherHighlightsCustomDropdown = PublisherHighlightsCustomDropdown;
})(window, jQuery);