(function (global) {
  'use strict';

  /**
   * Wrapper Conn2Flow para o Stripe.js v3 + Payment Element.
   * Espelha o contrato do assets/paypal/paypal.js: carregamento único do SDK,
   * inicialização dos campos e submit normalizado para o backend.
   */

  var sdkPromise = null;
  var contexto = null; // { stripe, elements, clientSecret, secretType }

  function objectAssign(target) {
    for (var i = 1; i < arguments.length; i += 1) {
      var source = arguments[i] || {};
      Object.keys(source).forEach(function (key) { target[key] = source[key]; });
    }
    return target;
  }

  function requireDocument() {
    if (!global.document || !global.document.createElement) {
      throw new Error('O carregamento do SDK do Stripe exige um documento HTML.');
    }
    return global.document;
  }

  /** Carrega https://js.stripe.com/v3/ uma única vez. */
  function stripeCarregamentoSDK(options) {
    options = options || {};

    if (global.Stripe && !options.forceReload) {
      return Promise.resolve(global.Stripe);
    }
    if (sdkPromise && !options.forceReload) {
      return sdkPromise;
    }

    var doc;
    try { doc = requireDocument(); } catch (error) { return Promise.reject(error); }

    var scriptId = options.scriptId || 'conn2flow-stripe-sdk';
    var oldScript = doc.getElementById(scriptId);

    sdkPromise = new Promise(function (resolve, reject) {
      function loaded() {
        if (global.Stripe) { resolve(global.Stripe); return; }
        sdkPromise = null;
        reject(new Error('O SDK foi carregado, mas window.Stripe não está disponível.'));
      }
      function failed() {
        sdkPromise = null;
        reject(new Error('Não foi possível carregar o SDK do Stripe.'));
      }

      if (oldScript) {
        if (global.Stripe) { loaded(); return; }
        oldScript.addEventListener('load', loaded, { once: true });
        oldScript.addEventListener('error', failed, { once: true });
        return;
      }

      var script = doc.createElement('script');
      script.id = scriptId;
      script.src = options.sdkUrl || 'https://js.stripe.com/v3/';
      script.async = true;
      script.addEventListener('load', loaded, { once: true });
      script.addEventListener('error', failed, { once: true });
      (doc.head || doc.body || doc.documentElement).appendChild(script);
    });

    return sdkPromise;
  }

  /**
   * Inicializa o Payment Element.
   * @param {Object} options { publishableKey, clientSecret, container: '#seletor', appearance?, locale? }
   */
  function stripeElementsInit(options) {
    options = options || {};
    if (!options.publishableKey) return Promise.reject(new Error('options.publishableKey é obrigatório.'));
    if (!options.clientSecret) return Promise.reject(new Error('options.clientSecret é obrigatório.'));
    if (!options.container) return Promise.reject(new Error('options.container é obrigatório.'));

    return stripeCarregamentoSDK(options).then(function (Stripe) {
      var stripe = Stripe(options.publishableKey, { locale: options.locale || 'auto' });
      var elements = stripe.elements({
        clientSecret: options.clientSecret,
        appearance: options.appearance || { theme: 'night' }
      });

      // `defaultValues` pré-preenche nome/e-mail já coletados antes do pagamento. O endereço de
      // cobrança fica em `fields.billingDetails: 'auto'` (padrão): o Stripe pede apenas o que o
      // método/país exige — para cartão no Brasil normalmente só o essencial. Para exigir endereço
      // completo, passe `paymentElementOptions.fields.billingDetails.address: 'never'` e colete-o
      // no seu formulário, ou use o Address Element.
      var elementOptions = objectAssign({}, options.paymentElementOptions || {});
      if (options.customerName || options.customerEmail) {
        var billing = {};
        if (options.customerName) billing.name = options.customerName;
        if (options.customerEmail) billing.email = options.customerEmail;
        elementOptions.defaultValues = objectAssign({}, elementOptions.defaultValues || {}, {
          billingDetails: objectAssign({}, (elementOptions.defaultValues || {}).billingDetails || {}, billing)
        });
      }

      var paymentElement = elements.create('payment', elementOptions);
      paymentElement.mount(options.container);

      contexto = {
        stripe: stripe,
        elements: elements,
        clientSecret: options.clientSecret,
        // SetupIntent (seti_) para assinaturas com trial/valor zero; PaymentIntent nos demais.
        secretType: options.secretType || (String(options.clientSecret).indexOf('seti_') === 0 ? 'setup' : 'payment')
      };

      return new Promise(function (resolve, reject) {
        paymentElement.on('ready', function () { resolve(contexto); });
        paymentElement.on('loaderror', function (event) {
          reject(new Error((event && event.error && event.error.message) ? event.error.message : 'Falha ao carregar o formulário de pagamento.'));
        });
      });
    });
  }

  /**
   * Confirma o pagamento/setup sem sair da página (redirect apenas quando o método exige).
   * @param {Object} options { returnUrl }
   * @returns {Promise<{status, intent_id, intent_type}>}
   */
  function stripeElementsSubmit(options) {
    options = options || {};
    if (!contexto) return Promise.reject(new Error('Inicialize o Payment Element antes do submit.'));

    var params = {
      elements: contexto.elements,
      confirmParams: { return_url: options.returnUrl || global.location.href },
      redirect: 'if_required'
    };

    var confirmar = (contexto.secretType === 'setup')
      ? contexto.stripe.confirmSetup(params)
      : contexto.stripe.confirmPayment(params);

    return confirmar.then(function (result) {
      if (result.error) {
        var error = new Error(result.error.message || 'Pagamento não autorizado.');
        error.code = result.error.code || null;
        error.type = result.error.type || null;
        throw error;
      }
      var intent = result.paymentIntent || result.setupIntent || null;
      return {
        status: intent ? intent.status : null,
        intent_id: intent ? intent.id : null,
        intent_type: result.paymentIntent ? 'payment_intent' : (result.setupIntent ? 'setup_intent' : null)
      };
    });
  }

  var api = {
    carregarSDK: stripeCarregamentoSDK,
    elementsInit: stripeElementsInit,
    elementsSubmit: stripeElementsSubmit,
    getContexto: function () { return contexto; }
  };

  global.stripeCarregamentoSDK = stripeCarregamentoSDK;
  global.stripeElementsInit = stripeElementsInit;
  global.stripeElementsSubmit = stripeElementsSubmit;
  global.conn2flowStripe = api;
}(typeof window !== 'undefined' ? window : globalThis));
