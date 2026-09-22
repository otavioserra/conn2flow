import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

function carregarModulo() {
  installJQueryStub();
  const code = readFileSync(
    resolve(process.cwd(), 'gestor/modulos/perfil-usuario/perfil-usuario.js'),
    'utf8'
  );
  vm.runInThisContext(code, { filename: 'perfil-usuario.js' });
}

function montarFormulario(id = '_gestor-form-logar') {
  document.body.innerHTML = `<form id="${id}"><button type="submit">Enviar</button></form>`;
  return document.querySelector('form');
}

describe('Perfil do Usuário — CAPTCHA agnóstico ao Fomantic (req-172)', () => {
  let submitNativo;

  beforeEach(() => {
    document.body.innerHTML = '';
    window.gestor = { interface: {} };
    submitNativo = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {});
  });

  afterEach(() => {
    vi.useRealTimers();
    delete window.grecaptcha;
    delete window.turnstile;
  });

  it('executa v3 no submit Tailwind, injeta token e action e envia nativamente', async () => {
    const form = montarFormulario('_gestor-form-signup');
    window.gestor.googleRecaptchaActive = true;
    window.gestor.googleRecaptchaSite = 'site-v3';
    window.grecaptcha = {
      ready: (callback) => callback(),
      execute: vi.fn(() => Promise.resolve('token-v3'))
    };

    carregarModulo();
    const evento = new Event('submit', { bubbles: true, cancelable: true });
    form.dispatchEvent(evento);
    await Promise.resolve();
    await Promise.resolve();

    expect(evento.defaultPrevented).toBe(true);
    expect(window.grecaptcha.execute).toHaveBeenCalledWith('site-v3', { action: 'signup' });
    expect(form.querySelector('[name="token"]').value).toBe('token-v3');
    expect(form.querySelector('[name="action"]').value).toBe('signup');
    expect(submitNativo).toHaveBeenCalledTimes(1);
  });

  it('aguarda o token do Turnstile antes de continuar o submit nativo', () => {
    vi.useFakeTimers();
    const form = montarFormulario('_gestor-form-signup');
    let callback;
    window.gestor.turnstileSiteKey = 'site-turnstile';
    window.gestor.turnstileMode = 'managed';
    window.turnstile = {
      render: vi.fn((widget, options) => { callback = options.callback; return 'widget-1'; }),
      execute: vi.fn()
    };

    carregarModulo();
    vi.advanceTimersByTime(100);
    const evento = new Event('submit', { bubbles: true, cancelable: true });
    form.dispatchEvent(evento);

    expect(evento.defaultPrevented).toBe(true);
    expect(submitNativo).not.toHaveBeenCalled();
    callback('token-turnstile');
    expect(form.querySelector('[name="cf-turnstile-response"]').value).toBe('token-turnstile');
    expect(submitNativo).toHaveBeenCalledTimes(1);
  });

  it('renderiza o checkbox v2 e deixa o envio sob controle do formulário', () => {
    vi.useFakeTimers();
    const form = montarFormulario('_gestor-form-signup');
    window.gestor.googleRecaptchaV2Required = true;
    window.gestor.googleRecaptchaV2Site = 'site-v2';
    window.grecaptcha = { render: vi.fn(() => 7) };

    carregarModulo();
    vi.advanceTimersByTime(100);
    const evento = new Event('submit', { bubbles: true, cancelable: true });
    form.dispatchEvent(evento);

    expect(window.grecaptcha.render).toHaveBeenCalledWith(
      form.querySelector('.g-recaptcha-v2'),
      { sitekey: 'site-v2' }
    );
    expect(evento.defaultPrevented).toBe(false);
    expect(submitNativo).not.toHaveBeenCalled();
  });
});
