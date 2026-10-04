import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { describe, it, expect, vi } from 'vitest';

describe('publisher field template autocomplete in Tailwind', () => {
  it.each([
    [undefined, false], [null, false], [{}, false], ['legacy', false],
    [[null, { id: 'other', linked_template: true }], false],
    [[{ id: 'legacy-title', linked_template: true, variable: '[[publisher#text#legacy-title]]' }], true],
  ])(
    'renders and submits fields when template_map is %j', (templateMap, mapped) => {
      new Function('window', readFileSync('gestor/assets/vendor/jquery/3.7.1/jquery.min.js', 'utf8'))(window);
      const $ = window.jQuery;
      globalThis.$ = globalThis.jQuery = $;
      $.fn.ready = function (callback) { callback(); return this; };
      $.fn.dropdown = function (command, value) {
        if (command === 'get value') return this.val() || '';
        if (command === 'set selected') this.val(value);
        return this;
      };
      $.fn.checkbox = function () { return this; };
      $.fn.form = function () { return this; };
      const html = readFileSync('gestor/modulos/publisher/resources/pt-br/pages/publisher-editar/publisher-editar.html', 'utf8');
      document.body.innerHTML = '<form class="interfaceFormPadrao"><div id="_gestor-interface-edit-dados"></div>' + html + '</form>';
      globalThis.gestor = { raiz: '/', moduloCaminho: 'publisher', moduloOpcao: 'editar',
        formLabelRules: { rules: [{ prompt: '#label#' }, { prompt: '#label#' }, { prompt: '#label#' }] } };
      globalThis.publisher_initial_schema = { fields: [{ id: 'legacy-title', label: 'Old title', type: 'text', mandatory: true }], template_map: templateMap };
      try {
        expect(() => vm.runInThisContext(readFileSync('gestor/modulos/publisher/publisher.js', 'utf8'))).not.toThrow();
        const rows = $('#fields-schema-container .field-row');
        expect(rows.length).toBe(1);
        expect(rows.find('.field-label').val()).toBe('Old title');
        expect(rows.find('.field-id').val()).toBe('legacy-title');
        expect(rows.find('.field-template-id').val()).toBe(mapped ? 'legacy-title' : '');
        expect(rows.find('.field-mandatory').prop('checked')).toBe(true);
        $('.interfaceFormPadrao').triggerHandler('submit');
        const schema = JSON.parse($('input[name="fields_schema"]').val());
        expect(schema.fields[0]).toMatchObject({ id: 'legacy-title', label: 'Old title', mandatory: true });
        expect(schema.template_map[0]).toMatchObject({ id: 'legacy-title', linked_template: mapped });
      } finally {
        document.querySelectorAll('#fields-schema-container .search').forEach(element => element.c2fSearchCleanup?.());
        delete globalThis.publisher_initial_schema;
      }
    }
  );

  it('navigates suggestions in both directions, preserves Tab and closes back to the input', () => {
    const code = readFileSync('gestor/modulos/publisher/publisher.js', 'utf8');
    vm.runInThisContext(code.slice(0, code.indexOf('$(document).ready')));
    document.body.innerHTML = '<div class="search"><input class="field-template"><div class="results"></div></div>';
    const element = document.querySelector('.search');
    const input = element.querySelector('input');
    const results = element.querySelector('.results');
    globalThis.c2fPublisherFieldSearch(element, {
      source: [{ title: 'Title', value: 'title' }, { title: 'Cover', value: 'cover' }],
      error: { noResults: 'Empty' }, onSelect: vi.fn()
    });
    const key = (target, value) => {
      const event = new KeyboardEvent('keydown', { key: value, bubbles: true, cancelable: true });
      target.dispatchEvent(event);
      return event;
    };
    input.focus();
    key(input, 'ArrowDown');
    const [first, second] = results.querySelectorAll('button');
    expect(document.activeElement).toBe(first);
    expect(key(first, 'ArrowDown').defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(second);
    key(second, 'ArrowUp');
    expect(document.activeElement).toBe(first);
    key(first, 'ArrowUp');
    expect(document.activeElement).toBe(second);
    expect(key(second, 'Tab').defaultPrevented).toBe(false);
    key(second, 'Escape');
    expect(document.activeElement).toBe(input);
    expect(results.hidden).toBe(true);
    expect(results.children).toHaveLength(0);
    key(input, 'ArrowUp');
    expect(results.hidden).toBe(false);
    expect(document.activeElement.textContent).toBe('Cover');
    element.c2fSearchCleanup();
  });

  it('filters, selects through the real binding callback and closes with Escape', () => {
    const code = readFileSync('gestor/modulos/publisher/publisher.js', 'utf8');
    vm.runInThisContext(code.slice(0, code.indexOf('$(document).ready')));
    document.body.innerHTML = '<div class="search"><input class="field-template"><div class="results"></div></div>';
    const element = document.querySelector('.search');
    const input = element.querySelector('input');
    const results = element.querySelector('.results');
    const select = vi.fn();
    globalThis.c2fPublisherFieldSearch(element, {
      source: [{title: '[[publisher#text#title]]', value: 'title'}, {title: '[[publisher#image#cover]]', value: 'cover'}],
      error: {noResults: 'Empty'}, onSelect: select
    });
    input.value = 'cover';
    input.dispatchEvent(new Event('input'));
    expect(results.children).toHaveLength(1);
    results.querySelector('button').click();
    expect(input.value).toBe('[[publisher#image#cover]]');
    expect(select).toHaveBeenCalledWith({title: '[[publisher#image#cover]]', value: 'cover'});
    expect(select.mock.instances[0]).toBe(element);
    expect(results.hidden).toBe(true);
    input.value = 'missing'; input.dispatchEvent(new Event('input'));
    expect(results.textContent).toBe('Empty');
    input.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape'}));
    expect(results.hidden).toBe(true);
    element.c2fSearchCleanup();
  });
});
