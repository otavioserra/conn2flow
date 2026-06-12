import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

function runScript(relativePath) {
  const code = readFileSync(resolve(process.cwd(), relativePath), 'utf8');
  vm.runInThisContext(code, { filename: relativePath });
}

describe('publisher-highlights.js', () => {
  it('debounces local editor changes before scheduling the widget preview refresh', () => {
    vi.useFakeTimers();

    const ajax = vi.fn((options) => {
      options.success?.({ status: 'Ok', html: '<section>Destaques</section>' });
      options.complete?.();
    });
    installJQueryStub({ ajax });

    globalThis.publisher_highlights_initial_schema = {
      rule: 'latest',
      count: 4,
      order_by: 'date_desc',
      selected_items: [],
      variable_mapping: {}
    };
    window.html_editor_get_html = vi.fn(() => '<div>[[item#titulo]]</div>');
    window.html_editor_get_css = vi.fn(() => '.highlight{display:block}');

    document.body.innerHTML = `
      <form id="_gestor-interface-edit-dados" class="ui form"></form>
      <select name="publisher_id"></select>
      <select name="template_id" id="template_id"></select>
      <select id="rule"><option value="latest" selected>Latest</option></select>
      <input id="count" value="4">
      <select id="order_by"><option value="date_desc" selected>Date</option></select>
      <input name="fields_schema">
      <textarea class="codemirror-html"></textarea>
      <textarea class="codemirror-css"></textarea>
      <iframe id="iframe-publisher-highlights-preview"></iframe>
      <div id="available-fields-list"></div>
      <div id="missing-fields-list"></div>
      <div id="linked-fields-list"></div>
      <div id="manual-items-wrapper"></div>
      <div id="order-by-wrapper"></div>
      <div class="template-options-wrapper"><div class="dimmer"></div></div>
      <div class="hep-preview-dimmer"></div>
      <div id="template-skeletons"></div>
      <div id="error-message" class="hidden"><span id="error-message-content"></span></div>
    `;

    runScript('gestor/modulos/publisher-highlights/publisher-highlights.js');
    ajax.mockClear();
    vi.clearAllTimers();

    window.updatedCodeMirrorHtml();
    expect(vi.getTimerCount()).toBe(1);

    window.updatedCodeMirrorHtml();
    expect(vi.getTimerCount()).toBe(1);

    vi.advanceTimersByTime(599);
    expect(ajax).not.toHaveBeenCalled();
  });
});
