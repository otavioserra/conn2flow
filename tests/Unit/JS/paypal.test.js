import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it, vi } from 'vitest';

function loadPaypalJs() {
  const code = readFileSync(resolve(process.cwd(), 'gestor/assets/paypal/paypal.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'paypal.js' });
  return window.conn2flowPaypal;
}

function mountContainers() {
  document.body.innerHTML = `
    <div id="card-number-container"></div>
    <div data-paypal-error-for="number" hidden></div>
    <div id="card-expiry-container"></div>
    <div id="card-cvv-container"></div>
    <div id="card-holder-container"></div>`;
}

function mockCardFields() {
  let config;
  const field = () => ({ render: vi.fn(() => Promise.resolve()) });
  const instance = {
    isEligible: vi.fn(() => true),
    NumberField: vi.fn(field),
    ExpiryField: vi.fn(field),
    CVVField: vi.fn(field),
    NameField: vi.fn(field),
    getState: vi.fn(() => Promise.resolve({ isFormValid: true, fields: {} })),
    submit: vi.fn(() => Promise.resolve())
  };
  window.paypal = {
    CardFields: vi.fn((options) => {
      config = options;
      return instance;
    })
  };
  return { instance, getConfig: () => config };
}

describe('paypal.js — checkout transparente (BATCH-104)', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    delete window.paypal;
    delete window.conn2flowPaypal;
    delete window.paypalCarregamentoSDK;
    delete window.paypalCardFieldsInit;
    delete window.paypalCardFieldsSubmit;
  });

  it('carrega o SDK com query segura e client token em data attribute', async () => {
    const api = loadPaypalJs();
    const appendTarget = document.createDocumentFragment();
    const loading = api.carregarSDK({
      clientId: 'client-123',
      clientToken: 'client-token-secret',
      components: ['buttons', 'card-fields'],
      intent: 'capture',
      vault: true,
      currency: 'BRL',
      appendTarget
    });

    const script = appendTarget.querySelector('#conn2flow-paypal-sdk');
    const url = new URL(script.src);
    expect(url.origin + url.pathname).toBe('https://www.paypal.com/sdk/js');
    expect(url.searchParams.get('client-id')).toBe('client-123');
    expect(url.searchParams.get('components')).toBe('buttons,card-fields');
    expect(url.searchParams.get('intent')).toBe('capture');
    expect(url.searchParams.get('vault')).toBe('true');
    expect(url.searchParams.get('currency')).toBe('BRL');
    expect(url.searchParams.has('data-client-token')).toBe(false);
    expect(script.getAttribute('data-client-token')).toBe('client-token-secret');

    window.paypal = { CardFields: vi.fn() };
    script.dispatchEvent(new window.Event('load'));
    await expect(loading).resolves.toBe(window.paypal);
  });

  it('deduplica chamadas concorrentes ao carregamento do SDK', async () => {
    const api = loadPaypalJs();
    const appendTarget = document.createDocumentFragment();
    const first = api.carregarSDK({ clientId: 'client-123', appendTarget });
    const second = api.carregarSDK({ clientId: 'client-123', appendTarget });
    expect(appendTarget.querySelectorAll('#conn2flow-paypal-sdk')).toHaveLength(1);

    window.paypal = {};
    appendTarget.querySelector('#conn2flow-paypal-sdk').dispatchEvent(new window.Event('load'));
    await expect(Promise.all([first, second])).resolves.toEqual([window.paypal, window.paypal]);
  });

  it('renderiza os quatro Card Fields nos seletores padrão', async () => {
    mountContainers();
    const mocked = mockCardFields();
    const api = loadPaypalJs();
    const createOrder = vi.fn(() => Promise.resolve('ORDER-123'));

    const context = await api.cardFieldsInit({ createOrder });

    expect(context.mode).toBe('card-fields');
    expect(window.paypal.CardFields).toHaveBeenCalledOnce();
    expect(context.fields.number.render).toHaveBeenCalledWith('#card-number-container');
    expect(context.fields.expiry.render).toHaveBeenCalledWith('#card-expiry-container');
    expect(context.fields.cvv.render).toHaveBeenCalledWith('#card-cvv-container');
    expect(context.fields.holder.render).toHaveBeenCalledWith('#card-holder-container');
  });

  it('aplica classes e mensagem de validação em tempo real', async () => {
    mountContainers();
    const mocked = mockCardFields();
    const api = loadPaypalJs();
    await api.cardFieldsInit({ createOrder: () => 'ORDER-123' });

    const data = {
      errors: ['INVALID_NUMBER'],
      fields: {
        cardNumberField: {
          isFocused: false,
          isEmpty: false,
          isValid: false,
          isPotentiallyValid: false
        }
      }
    };
    mocked.getConfig().inputEvents.onChange(data);

    const container = document.getElementById('card-number-container');
    const error = document.querySelector('[data-paypal-error-for="number"]');
    expect(container.classList.contains('paypal-card-field--invalid')).toBe(true);
    expect(container.getAttribute('aria-invalid')).toBe('true');
    expect(error.hidden).toBe(false);
    expect(error.textContent).toContain('Número do cartão');
  });

  it('normaliza payment_source e order_id devolvidos pelo submit', async () => {
    mountContainers();
    const mocked = mockCardFields();
    mocked.instance.submit.mockResolvedValue({
      id: 'ORDER-123',
      payment_source: { token: { id: 'TOKEN-123', type: 'PAYMENT_METHOD_TOKEN' } }
    });
    const api = loadPaypalJs();
    await api.cardFieldsInit({ createOrder: () => 'ORDER-123' });

    await expect(api.cardFieldsSubmit()).resolves.toMatchObject({
      order_id: 'ORDER-123',
      payment_source: { token: { id: 'TOKEN-123' } }
    });
    expect(mocked.instance.submit).toHaveBeenCalledWith({});
  });

  it('retorna order_id do onApprove sem inventar um payment_source', async () => {
    mountContainers();
    const mocked = mockCardFields();
    const api = loadPaypalJs();
    await api.cardFieldsInit({ createOrder: () => 'ORDER-APPROVED' });
    mocked.instance.submit.mockImplementation(async () => {
      await mocked.getConfig().onApprove({ orderID: 'ORDER-APPROVED' }, {});
    });

    await expect(api.cardFieldsSubmit()).resolves.toMatchObject({
      order_id: 'ORDER-APPROVED',
      payment_source: null,
      approval_data: { orderID: 'ORDER-APPROVED' }
    });
  });

  it('bloqueia submit quando o estado do formulário é inválido', async () => {
    mountContainers();
    const mocked = mockCardFields();
    mocked.instance.getState.mockResolvedValue({
      isFormValid: false,
      errors: ['INVALID_CVV'],
      fields: {
        cardCvvField: {
          isFocused: false,
          isEmpty: false,
          isValid: false,
          isPotentiallyValid: false
        }
      }
    });
    const api = loadPaypalJs();
    await api.cardFieldsInit({ createOrder: () => 'ORDER-123' });

    await expect(api.cardFieldsSubmit()).rejects.toMatchObject({
      code: 'PAYPAL_CARD_FIELDS_INVALID'
    });
    expect(mocked.instance.submit).not.toHaveBeenCalled();
    expect(document.getElementById('card-cvv-container').classList.contains('paypal-card-field--invalid')).toBe(true);
  });
});
