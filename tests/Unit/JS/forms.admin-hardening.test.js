import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const formsPath = resolve(process.cwd(), 'gestor/modulos/forms/forms.js');
const formsWidgetPath = resolve(process.cwd(), 'gestor/modulos/forms/forms.widget.js');
const formularioPath = resolve(process.cwd(), 'gestor/assets/interface/formulario.js');
const formsSource = readFileSync(formsPath, 'utf8');
const formsWidgetSource = readFileSync(formsWidgetPath, 'utf8');
const formularioSource = readFileSync(formularioPath, 'utf8');

function extractFunction(source, signature) {
  const start = source.indexOf(signature);
  if (start < 0) throw new Error(`${signature} não encontrada`);
  let cursor = source.indexOf('{', start);
  let depth = 0;
  for (; cursor < source.length; cursor += 1) {
    if (source[cursor] === '{') depth += 1;
    if (source[cursor] === '}') {
      depth -= 1;
      if (depth === 0) return source.slice(start, cursor + 1);
    }
  }
  throw new Error(`Fim de ${signature} não encontrado`);
}

function loadFunction(source, signature, functionName, gestorFake) {
  const functionSource = extractFunction(source, signature);
  // eslint-disable-next-line no-new-func
  return new Function('gestor', functionSource + `\nreturn ${functionName};`)(gestorFake);
}

describe('forms.js — URL administrativa e CSRF (req-130)', () => {
  it('normaliza moduloCaminho sem barra dupla e preserva protocolo absoluto', () => {
    const relative = loadFunction(
      formsSource,
      'function moduloUrl(',
      'moduloUrl',
      { raiz: '/site//', moduloCaminho: '/forms/editar//' }
    );
    const absolute = loadFunction(
      formsSource,
      'function moduloUrl(',
      'moduloUrl',
      { raiz: 'https://site.test/', moduloCaminho: 'forms/editar/' }
    );

    expect(relative()).toBe('/site/forms/editar/');
    expect(absolute()).toBe('https://site.test/forms/editar/');
  });

  it('usa moduloUrl e envia o token explicitamente nas três chamadas AJAX', () => {
    expect(formsSource.match(/url:\s*moduloUrl\(\)/g)).toHaveLength(3);
    expect(formsSource.match(/_csrf_token:\s*\(window\.gestor && gestor\.csrfToken\)/g)).toHaveLength(3);
    expect(formsSource).not.toContain("url: gestor.raiz + gestor.moduloCaminho + '/'");
  });

  it('limpa o componente oculto ao apagar a busca ou clicar em limpar', () => {
    expect(formsSource).toMatch(/q\.length < 1[\s\S]*?#email_message_component'\)\.val\(''\)/);
    expect(formsSource).toMatch(/\.sig-clear'[\s\S]*?#email_message_component'\)\.val\(''\)/);
  });

  it('envia CSRF também ao carregar a configuração no iframe do editor', () => {
    expect(formsWidgetSource).toContain("ajaxOpcao: 'forms-render-editor-html'");
    expect(formsWidgetSource).toContain('_csrf_token: csrfToken()');
    expect(formsWidgetSource).toMatch(/function csrfToken\([\s\S]*?gestor\.csrfToken[\s\S]*?window\.parent\.gestor\.csrfToken/);
  });
});

describe('formulario.js — fallback público da action (req-130)', () => {
  it('resolve o endpoint canônico respeitando a raiz da instalação', () => {
    const rootAction = loadFunction(
      formularioSource,
      'function defaultFormAction(',
      'defaultFormAction',
      { raiz: '/projeto//' }
    );
    const absoluteAction = loadFunction(
      formularioSource,
      'function defaultFormAction(',
      'defaultFormAction',
      { raiz: 'https://site.test/base/' }
    );

    expect(rootAction()).toBe('/projeto/forms-submissions-process/');
    expect(absoluteAction()).toBe('https://site.test/base/forms-submissions-process/');
  });

  it('usa o fallback tanto na normalização quanto imediatamente antes do AJAX', () => {
    expect(formularioSource).toContain('pickScalar(normalized.formAction, defaultFormAction())');
    expect(formularioSource).toContain("data.formAction.trim() : defaultFormAction()");
    expect(formularioSource).toContain("form.attr('action') || defaultFormAction()");
  });
});
