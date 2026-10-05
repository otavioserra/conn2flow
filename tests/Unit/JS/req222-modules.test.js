import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it, vi } from 'vitest';

const source = (module) => readFileSync(resolve(`gestor/modulos/${module}/${module}.js`), 'utf8');
const resource = (module, option = 'editar') => readFileSync(resolve(`gestor/modulos/${module}/resources/pt-br/pages/${module}-${option}/${module}-${option}.html`), 'utf8');
const flush = async () => { for (let i = 0; i < 10; i++) await Promise.resolve(); };

function submissions({ id = 42, result = { status: 'success', message: 'Updated', form_status_label: 'Responded' }, accepted = true, ok = true } = {}) {
  document.body.innerHTML = `<div id="_gestor-interface-visualizar-dados"><div class="req222-page"
    data-js-id-missing="Missing ID" data-js-status-error="Status error" data-js-communication-error="Network error"
    data-js-email-missing="Missing email" data-js-reply-confirm="Send to {email}?" data-js-reply-error="Reply error" data-js-reply-sent="Sent">
    <table><tbody><tr><td><span data-submission-status class="c2fc-selo c2fc-selo-inativo">New</span>
    <select id="form-status-select"><option value="responded">Responded</option></select><button id="btn-save-status">Save</button></td></tr></tbody></table>
    <input id="reply-to-email" value="test@example.org"><textarea id="reply-message">Reply</textarea><button id="btn-send-reply">Send</button>
  </div></div>`;
  const controls = { dialogo: { alerta: vi.fn().mockResolvedValue(), confirmar: vi.fn().mockResolvedValue(accepted) }, aviso: vi.fn() };
  const fetch = vi.fn().mockResolvedValue({ ok, status: ok ? 200 : 403, json: async () => result });
  const gestor = { formsSubmissions: { idNumerico: id }, moduloOpcao: 'visualizar' };
  const fakeWindow = { gestor, c2fControles: controls, location: { pathname: '/forms-submissions/view/', reload: vi.fn() } };
  const proxyDocument = { querySelector: document.querySelector.bind(document), addEventListener: (_, callback) => callback() };
  new Function('document', 'window', 'gestor', 'fetch', source('forms-submissions'))(proxyDocument, fakeWindow, gestor, fetch);
  return { fetch, controls, fakeWindow };
}

describe('req-222 submissions actions', () => {
  it('sends the AJAX envelope and updates status without submitting a form', async () => {
    const { fetch, controls } = submissions();
    document.querySelector('#btn-save-status').click();
    await flush();
    const body = fetch.mock.calls[0][1].body;
    expect(body.get('ajax')).toBe('sim');
    expect(body.get('opcao')).toBe('visualizar');
    expect(body.get('id_numerico')).toBe('42');
    expect(body.get('form_status')).toBe('responded');
    expect(document.querySelector('[data-submission-status]').textContent).toBe('Responded');
    expect(document.querySelector('[data-submission-status]').classList.contains('c2fc-selo-ativo')).toBe(true);
    expect(controls.aviso).toHaveBeenCalledWith('Updated', 'sucesso');
    expect(document.querySelector('#btn-save-status').disabled).toBe(false);
  });
  it('does not send a reply when the panel confirmation is cancelled', async () => {
    const { fetch, controls } = submissions({ accepted: false });
    document.querySelector('#btn-send-reply').click();
    await flush();
    expect(controls.dialogo.confirmar).toHaveBeenCalledWith('Send to test@example.org?');
    expect(fetch).not.toHaveBeenCalled();
    expect(document.querySelector('#btn-send-reply').disabled).toBe(false);
  });
  it('blocks empty replies and focuses the invalid field', async () => {
    const { fetch, controls } = submissions();
    document.querySelector('#reply-message').value = '  ';
    document.querySelector('#btn-send-reply').click();
    await flush();
    expect(fetch).not.toHaveBeenCalled();
    expect(controls.dialogo.confirmar).not.toHaveBeenCalled();
    expect(document.activeElement.id).toBe('reply-message');
    expect(document.activeElement.getAttribute('aria-invalid')).toBe('true');
  });
  it('reports HTTP failures through panel controls and unlocks the button', async () => {
    const { controls } = submissions({ ok: false });
    document.querySelector('#btn-save-status').click();
    await flush();
    expect(controls.dialogo.alerta).toHaveBeenCalledWith('Network error');
    expect(document.querySelector('#btn-save-status').disabled).toBe(false);
    expect(document.querySelector('#btn-save-status').hasAttribute('aria-busy')).toBe(false);
  });
  it('keeps a server rejection visible without clearing the reply', async () => {
    const { controls, fakeWindow } = submissions({ result: { status: 'error', message: 'Rejected' } });
    document.querySelector('#btn-send-reply').click();
    await flush();
    expect(controls.dialogo.alerta).toHaveBeenCalledWith('Rejected');
    expect(document.querySelector('#reply-message').value).toBe('Reply');
    expect(fakeWindow.location.reload).not.toHaveBeenCalled();
  });
  it('sends a confirmed reply once, clears it and reloads the persisted history', async () => {
    const { fetch, fakeWindow } = submissions();
    document.querySelector('#btn-send-reply').click();
    await flush();
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch.mock.calls[0][1].body.get('reply_message')).toBe('Reply');
    expect(document.querySelector('#reply-message').disabled).toBe(true);
    expect(fakeWindow.location.reload).toHaveBeenCalledOnce();
  });
  it('reports a missing submission ID without any request', async () => {
    const { fetch, controls } = submissions({ id: null });
    document.querySelector('#btn-save-status').click();
    await flush();
    expect(fetch).not.toHaveBeenCalled();
    expect(controls.dialogo.alerta).toHaveBeenCalledWith('Missing ID');
  });
});

describe('req-222 native row builders', () => {
  for (const module of ['forms', 'forms-search']) {
    it(`${module}: preserves schema values, multiline options and required state safely`, () => {
      document.body.innerHTML = resource(module);
      const js = source(module);
      const fn = js.slice(js.indexOf('    function buildFieldRow('), js.indexOf('    function fieldTypeValue('));
      const build = new Function('document', 'optionsPlaceholder', 'typeUsesOptions', fn + '\nreturn buildFieldRow;')(document, () => 'Options', (type) => type === 'select');
      const row = build({ label: '<img src=x onerror=alert(1)>', name: 'a"b', type: 'select', options: ['One', 'Two'], required: true }, 2);
      expect(row.dataset.index).toBe('2');
      expect(row.querySelector(`.${module}-field-label`).value).toBe('<img src=x onerror=alert(1)>');
      expect(row.querySelector('img')).toBeNull();
      expect(row.querySelector(`.${module}-field-options`).value).toBe('One\nTwo');
      expect(row.querySelector(`.${module}-field-required`).checked).toBe(true);
      expect(row.querySelector(`.${module}-field-type`).value).toBe('select');
      expect(row.querySelector(`.${module}-field-options`).style.display).toBe('');
      const text = build({ type: 'text' }, 0);
      expect(text.querySelector(`.${module}-field-options`).style.display).toBe('none');
    });
  }
  it('menu row keeps nesting, selection and text without interpreting user HTML', () => {
    document.body.innerHTML = resource('menus');
    const js = source('menus');
    const fn = js.slice(js.indexOf('    function buildTreeRow('), js.indexOf('    function renderTree('));
    const build = new Function('document', 'STEP', 'selectedRowId', 'typeName', fn + '\nreturn buildTreeRow;')(document, 24, 'one', (type) => type);
    const row = build({ id: 'one', depth: 2, type: 'pagina', label: '<script>bad()</script>' });
    expect(row.style.marginLeft).toBe('48px');
    expect(row.classList.contains('selected')).toBe(true);
    expect(row.querySelector('script')).toBeNull();
    expect(row.querySelector('.menu-tree-label').textContent).toBe('<script>bad()</script>');
    expect(row.querySelector('.menu-tree-handle').tagName).toBe('BUTTON');
  });
  it('gallery row preserves caption, image and link editor in native DOM', () => {
    document.body.innerHTML = resource('galleries');
    const js = source('galleries');
    const fn = js.slice(js.indexOf('    function buildItemRow('), js.indexOf('    function openItemSettings('));
    const links = document.createElement('div'); links.className = 'gallery-item-link-wrap';
    const build = new Function('document', 'galleriesNormalizarImagePosition', 'schema', 'buildLinkPanel', fn + '\nreturn buildItemRow;')(document, () => 'top', {}, () => [links]);
    const row = build({ id: 'photo', nome: '<b>Name</b>', imgSrc: '/image.jpg', legenda: 'Caption' });
    expect(row.dataset.id).toBe('photo');
    expect(row.querySelector('.gallery-item-thumb').getAttribute('src')).toBe('/image.jpg');
    expect(row.querySelector('.gallery-item-thumb').style.objectPosition).toBe('top');
    expect(row.querySelector('.gallery-item-name').querySelector('b')).toBeNull();
    expect(row.querySelector('.gallery-item-caption').value).toBe('Caption');
    expect(row.querySelector('.gallery-item-link-wrap')).toBe(links);
  });
});
