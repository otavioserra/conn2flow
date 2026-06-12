import { afterEach, vi } from 'vitest';

globalThis.gestor = {
  raiz: '/',
  moduloCaminho: 'gestor/modulo',
  moduloOpcao: 'editar',
  moduloId: 'publisher-test',
  widgetsToAjax: 'publisher-index',
  html_editor: {}
};

globalThis.loadDimmer = vi.fn();
globalThis.msg_erro_resetar = vi.fn();
globalThis.msg_erro_mostrar = vi.fn();
globalThis.CodeMirror = {
  fromTextArea: () => ({
    setSize: vi.fn(),
    getDoc: () => ({ setValue: vi.fn() }),
    refresh: vi.fn()
  })
};

afterEach(() => {
  document.body.innerHTML = '';
  document.head.innerHTML = '';
  localStorage.clear();
  vi.useRealTimers();
  delete globalThis.$;
  delete globalThis.jQuery;
});
