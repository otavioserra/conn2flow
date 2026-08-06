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
  // Espelha a API do CodeMirror 5 real: setValue/getValue diretos (proxy do doc) + getDoc + on.
  fromTextArea: () => {
    let _val = '';
    const _handlers = {};
    return {
      setSize: vi.fn(),
      getDoc: () => ({ setValue: (v) => { _val = v == null ? '' : String(v); }, getValue: () => _val }),
      setValue: (v) => { _val = v == null ? '' : String(v); },
      getValue: () => _val,
      refresh: vi.fn(),
      on: (evento, cb) => { (_handlers[evento] = _handlers[evento] || []).push(cb); },
      // Auxiliar de teste (não existe no CodeMirror real): dispara os handlers registrados.
      __emit: (evento) => { (_handlers[evento] || []).forEach((cb) => cb()); }
    };
  }
};

afterEach(() => {
  document.body.innerHTML = '';
  document.head.innerHTML = '';
  localStorage.clear();
  vi.useRealTimers();
  delete globalThis.$;
  delete globalThis.jQuery;
});
