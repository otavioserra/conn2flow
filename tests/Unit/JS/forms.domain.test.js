import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const source = readFileSync('gestor/assets/interface/formulario.js', 'utf8');
const start = source.indexOf('function normalizeDomain(');
const end = source.indexOf('function updateFieldUI(', start);
const validate = new Function('parseFieldLimits', 'pickPrompt', 'fillPrompt', 'updateFieldUI',
  source.slice(start, end) + '\nreturn validateField;')(
  () => ({}), (prompts, key, fallback) => prompts[key] || fallback,
  (tpl, field) => tpl.replace('#label#', field.label),
  (input, valid, message) => { input.valid = valid; input.message = message; }
);

function run(value, required = true) {
  const input = { value, val(next) { if (arguments.length) this.value = next; return this.value; } };
  const result = validate(input, { type: 'domain', name: 'host', label: 'Host', required },
    { domain: 'Invalid #label#', empty: 'Required #label#' }, 'tailwindcss', {});
  return { input, result };
}

describe('REQ-273 domain', () => {
  it.each(['https://MEUSITE.com.br/', ' HTTP://MEUSITE.COM.BR/// ', 'meusite.com.br'])('normalizes %s before validation', value => {
    const { input, result } = run(value);
    expect(result).toBe(true);
    expect(input.value).toBe('meusite.com.br');
  });
  it.each(['localhost', 'foo..com', '-foo.com', 'foo-.com', 'foo_bar.com', 'a b.com', 'olá.com',
    'foo.com/path', 'foo.com:80', 'ftp://foo.com', 'foo@bar.com', 'foo.c', 'foo.123',
    'a'.repeat(64) + '.com', ['a'.repeat(63), 'b'.repeat(63), 'c'.repeat(63), 'd'.repeat(62)].join('.')])('rejects %s', value => {
    const { input, result } = run(value);
    expect(result).toBe(false);
    expect(input.message).toBe('Invalid Host');
  });
  it('checks required after normalization and allows optional empty', () => {
    expect(run('https:///').result).toBe(false);
    expect(run('  ', false).result).toBe(true);
  });
  it('accepts label and total length boundaries', () => {
    expect(run('a'.repeat(63) + '.com').result).toBe(true);
    expect(run([63, 63, 63, 61].map(n => 'a'.repeat(n)).join('.')).result).toBe(true);
  });
  it('wires validation to real-time input events', () => {
    expect(source).toMatch(/field\.type === 'domain'[\s\S]*?input\.on\('input change blur',[\s\S]*?validateField\(/);
  });
  // REQ-130 (site): sem `prompt-domain` no banco, a mensagem não pode sair vazia.
  it.each([['pt-br', 'O campo Host precisa de um domínio válido (ex: seudominio.com.br).'],
    ['en', 'The Host field requires a valid domain (e.g. yourdomain.com).']])('falls back to a readable %s message', (language, expected) => {
    globalThis.gestor = { language };
    try {
      const input = { value: 'foo..com', val(next) { if (arguments.length) this.value = next; return this.value; } };
      expect(validate(input, { type: 'domain', name: 'host', label: 'Host', required: true }, {}, 'tailwindcss', {})).toBe(false);
      expect(input.message).toBe(expected);
    } finally { delete globalThis.gestor; }
  });
  it('finds the error container without a Fomantic .field ancestor', () => {
    const ui = source.slice(source.indexOf('function updateFieldUI('), source.indexOf('function getCharCounter('));
    expect(ui).toMatch(/closest\('\.field, \.conn2flow-form-field'\)/);
    expect(ui).toMatch(/if \(!fieldContainer\.length\) fieldContainer = input\.parent\(\);/);
    expect(ui).not.toMatch(/=\s*input\.closest\('\.field'\)\s*\|\|/);
  });
  it('restores domain selection and placeholder without losing custom text', () => {
    document.body.innerHTML = readFileSync('gestor/modulos/forms/resources/pt-br/pages/forms-editar/forms-editar.html', 'utf8');
    const templateInput = document.querySelector('#field-row-template').content.querySelector('.forms-field-placeholder');
    templateInput.dataset.domainPlaceholder = 'exemplo.com.br';
    const admin = readFileSync('gestor/modulos/forms/forms.js', 'utf8');
    const builder = admin.slice(admin.indexOf('    function buildFieldRow('), admin.indexOf('    function fieldTypeValue('));
    const build = new Function('document', 'optionsPlaceholder', 'typeUsesOptions', builder + '\nreturn buildFieldRow;')(
      document, () => '', () => false);
    const row = build({ type: 'domain', name: 'host', placeholder: 'custom.example', required: true }, 0);
    expect(row.querySelector('.forms-field-type').value).toBe('domain');
    expect(row.querySelector('.forms-field-placeholder').value).toBe('custom.example');
    expect(row.querySelector('.forms-field-placeholder').placeholder).toBe('exemplo.com.br');
    expect(row.querySelector('.forms-field-required').checked).toBe(true);
    expect(row.querySelector('.forms-field-options').style.display).toBe('none');
  });
});
