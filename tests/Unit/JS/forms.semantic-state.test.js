// REQ-131 (site): o validador só aplica classes semânticas de estado; o visual vem do `form-ui`.
import { readFileSync } from 'node:fs';
import { beforeEach, describe, expect, it } from 'vitest';

const source = readFileSync('gestor/assets/interface/formulario.js', 'utf8');
const jquerySource = readFileSync('gestor/assets/vendor/jquery/3.7.1/jquery.min.js', 'utf8');
const $ = new Function('module', 'exports', jquerySource + '\nreturn module.exports;')({ exports: {} }, {});

const slice = (from, to) => source.slice(source.indexOf(from), source.indexOf(to, source.indexOf(from)));
const ui = new Function('$', 'jQuery',
  slice('function getUiComponent(', 'function parseFieldLimits(') +
  slice('function updateFieldUI(', 'function submitForm(') +
  '\nreturn { updateFieldUI, updateCharCounter };')($, $);

describe('REQ-131 semantic form state', () => {
  beforeEach(() => {
    document.body.innerHTML = '<form><div class="conn2flow-form-field"><input name="host"><span class="char-counter"></span></div></form>';
  });

  it('marks container, field and message with semantic classes only', () => {
    const input = $('input[name="host"]');
    ui.updateFieldUI(input, false, 'Domínio inválido', 'tailwindcss', {});
    const field = $('.conn2flow-form-field');
    const msg = field.find('.c2fc-campo-msg-erro');
    expect(field.hasClass('has-error')).toBe(true);
    expect(input.hasClass('c2fc-campo-invalido')).toBe(true);
    expect(msg.length).toBe(1);
    expect(msg.hasClass('error-msg')).toBe(true);
    expect(msg.text()).toBe('Domínio inválido');
    expect(document.body.innerHTML).not.toMatch(/style=|border-red|text-red|border-green/);
  });

  it('applies the classes to a project template and clears them on success', () => {
    const input = $('input[name="host"]');
    const data = { ui: { components: { errorElementTailwind: '\n  <small class="component-error-element-tailwind">#message#</small>' } } };
    ui.updateFieldUI(input, false, 'x', 'tailwindcss', data);
    ui.updateFieldUI(input, false, 'y', 'tailwindcss', data);
    expect($('.c2fc-campo-msg-erro').length).toBe(1);
    expect($('.c2fc-campo-msg-erro').hasClass('component-error-element-tailwind')).toBe(true);
    ui.updateFieldUI(input, true, '', 'tailwindcss', data);
    expect($('.conn2flow-form-field').hasClass('has-error')).toBe(false);
    expect(input.hasClass('c2fc-campo-invalido')).toBe(false);
    expect($('.c2fc-campo-msg-erro').length).toBe(0);
    expect($('.error-msg').length).toBe(0);
  });

  it('keeps the Fomantic error state class', () => {
    const input = $('input[name="host"]');
    ui.updateFieldUI(input, false, 'x', 'fomantic-ui', {});
    expect(input.hasClass('error')).toBe(true);
    ui.updateFieldUI(input, true, '', 'fomantic-ui', {});
    expect(input.hasClass('error')).toBe(false);
    expect(input.hasClass('valid')).toBe(true);
  });

  it('flags the character counter with a class instead of an inline color', () => {
    const input = $('input[name="host"]').val('abcdef');
    ui.updateCharCounter(input, 3);
    expect($('.char-counter').hasClass('c2fc-contador-excedido')).toBe(true);
    expect($('.char-counter').attr('style')).toBeUndefined();
    ui.updateCharCounter(input.val('ab'), 3);
    expect($('.char-counter').hasClass('c2fc-contador-excedido')).toBe(false);
  });

  it('injects no Tailwind utility or inline style anywhere in the validator', () => {
    expect(source).not.toMatch(/\b(?:border|text|bg)-(?:red|green)-\d{2,3}\b/);
    expect(source).not.toMatch(/\.css\(/);
    expect(source).not.toMatch(/style=\\?["']/);
  });
});
