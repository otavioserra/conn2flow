(function (global) {
  'use strict';

  var sdkPromise = null;
  var cardFieldsContext = null;
  var lastApproval = null;

  var DEFAULT_SELECTORS = {
    number: '#card-number-container',
    expiry: '#card-expiry-container',
    cvv: '#card-cvv-container',
    holder: '#card-holder-container'
  };

  var DEFAULT_MESSAGES = {
    number: 'Número do cartão inválido.',
    expiry: 'Data de validade inválida ou expirada.',
    cvv: 'Código de segurança inválido.',
    holder: 'Nome do titular inválido.'
  };

  var FIELD_STATE_KEYS = {
    number: ['cardNumberField', 'number'],
    expiry: ['cardExpiryField', 'expirationDate', 'expiry'],
    cvv: ['cardCvvField', 'cvv'],
    holder: ['cardNameField', 'name', 'holder']
  };

  var FIELD_ERROR_KEYS = {
    number: 'INVALID_NUMBER',
    expiry: 'INVALID_EXPIRY',
    cvv: 'INVALID_CVV',
    holder: 'INVALID_NAME'
  };

  function objectAssign(target) {
    for (var i = 1; i < arguments.length; i += 1) {
      var source = arguments[i] || {};
      Object.keys(source).forEach(function (key) {
        target[key] = source[key];
      });
    }
    return target;
  }

  function requireDocument() {
    if (!global.document || !global.document.createElement) {
      throw new Error('O carregamento do SDK do PayPal exige um documento HTML.');
    }
    return global.document;
  }

  function normalizeComponents(value) {
    var components = value || 'buttons,card-fields';
    if (Array.isArray(components)) {
      components = components.join(',');
    }
    return String(components)
      .split(',')
      .map(function (component) { return component.trim(); })
      .filter(Boolean)
      .join(',');
  }

  function addQuery(params, name, value) {
    if (value === undefined || value === null || value === '') return;
    if (typeof value === 'boolean') value = value ? 'true' : 'false';
    params.set(name, String(value));
  }

  /**
   * Carrega o SDK oficial uma única vez. O client token fica deliberadamente no
   * atributo data-client-token, conforme o contrato do SDK, e nunca na URL.
   */
  function paypalCarregamentoSDK(options) {
    options = options || {};

    if (global.paypal && !options.forceReload) {
      return Promise.resolve(global.paypal);
    }

    if (sdkPromise && !options.forceReload) {
      return sdkPromise;
    }

    var clientId = options.clientId || options['client-id'];
    if (!clientId || typeof clientId !== 'string') {
      return Promise.reject(new Error('options.clientId é obrigatório para carregar o SDK do PayPal.'));
    }

    var doc;
    try {
      doc = requireDocument();
    } catch (error) {
      return Promise.reject(error);
    }

    var scriptId = options.scriptId || 'conn2flow-paypal-sdk';
    var oldScript = doc.getElementById(scriptId);
    if (options.forceReload && oldScript && oldScript.parentNode) {
      oldScript.parentNode.removeChild(oldScript);
      oldScript = null;
      sdkPromise = null;
    }

    var query = new URLSearchParams();
    addQuery(query, 'client-id', clientId);
    addQuery(query, 'components', normalizeComponents(options.components));
    addQuery(query, 'currency', options.currency);
    addQuery(query, 'intent', options.intent);
    addQuery(query, 'vault', options.vault);
    addQuery(query, 'locale', options.locale);
    addQuery(query, 'buyer-country', options.buyerCountry);
    addQuery(query, 'commit', options.commit);
    addQuery(query, 'debug', options.debug);
    addQuery(query, 'enable-funding', options.enableFunding);
    addQuery(query, 'disable-funding', options.disableFunding);

    var sdkBaseUrl = options.sdkBaseUrl || 'https://www.paypal.com/sdk/js';
    var sdkUrl = sdkBaseUrl + (sdkBaseUrl.indexOf('?') === -1 ? '?' : '&') + query.toString();

    sdkPromise = new Promise(function (resolve, reject) {
      var script = oldScript || doc.createElement('script');

      function loaded() {
        if (global.paypal) {
          script.setAttribute('data-conn2flow-loaded', '1');
          resolve(global.paypal);
          return;
        }
        sdkPromise = null;
        reject(new Error('O SDK foi carregado, mas window.paypal não está disponível.'));
      }

      function failed() {
        sdkPromise = null;
        if (script.parentNode && !oldScript) script.parentNode.removeChild(script);
        reject(new Error('Não foi possível carregar o SDK do PayPal.'));
      }

      if (oldScript) {
        if (oldScript.getAttribute('data-conn2flow-loaded') === '1') {
          loaded();
        } else {
          oldScript.addEventListener('load', loaded, { once: true });
          oldScript.addEventListener('error', failed, { once: true });
        }
        return;
      }

      script.id = scriptId;
      script.src = sdkUrl;
      script.async = true;
      script.setAttribute('data-sdk-integration-source', options.integrationSource || 'conn2flow');

      var clientToken = options.clientToken || options['data-client-token'];
      if (clientToken) script.setAttribute('data-client-token', String(clientToken));
      if (options.partnerAttributionId) {
        script.setAttribute('data-partner-attribution-id', String(options.partnerAttributionId));
      }
      if (options.userIdToken) script.setAttribute('data-user-id-token', String(options.userIdToken));
      if (options.cspNonce) script.setAttribute('nonce', String(options.cspNonce));

      script.addEventListener('load', loaded, { once: true });
      script.addEventListener('error', failed, { once: true });
      (options.appendTarget || doc.head || doc.body || doc.documentElement).appendChild(script);
    });

    return sdkPromise;
  }

  function findElement(selector) {
    if (!selector) return null;
    if (typeof selector !== 'string') return selector;
    return global.document ? global.document.querySelector(selector) : null;
  }

  function fieldState(data, fieldName) {
    var fields = data && data.fields ? data.fields : {};
    var keys = FIELD_STATE_KEYS[fieldName];
    for (var i = 0; i < keys.length; i += 1) {
      if (fields[keys[i]]) return fields[keys[i]];
    }
    return null;
  }

  function setErrorMessage(options, fieldName, message) {
    var errorSelectors = options.errorContainers || {};
    var errorElement = findElement(errorSelectors[fieldName]);
    if (!errorElement && global.document) {
      errorElement = global.document.querySelector('[data-paypal-error-for="' + fieldName + '"]');
    }
    if (!errorElement) return;
    errorElement.textContent = message || '';
    errorElement.hidden = !message;
  }

  function updateContainer(options, fieldName, data, eventName) {
    var selectors = options.selectors;
    var container = findElement(selectors[fieldName]);
    var state = fieldState(data, fieldName);
    if (!container || !state) return;

    var classes = options.classes;
    var errors = Array.isArray(data.errors) ? data.errors : [];
    var isFocused = state.isFocused === true || eventName === 'focus';
    if (eventName === 'blur') isFocused = false;

    var isInvalid = errors.indexOf(FIELD_ERROR_KEYS[fieldName]) !== -1;
    if (!isInvalid && state.isEmpty !== true) {
      isInvalid = state.isPotentiallyValid === false || (eventName === 'blur' && state.isValid !== true);
    }

    container.classList.toggle(classes.focused, isFocused);
    container.classList.toggle(classes.valid, state.isValid === true);
    container.classList.toggle(classes.invalid, isInvalid);
    container.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');

    var messages = options.messages;
    setErrorMessage(options, fieldName, isInvalid ? messages[fieldName] : '');
  }

  function updateValidation(options, data, eventName) {
    Object.keys(DEFAULT_SELECTORS).forEach(function (fieldName) {
      updateContainer(options, fieldName, data || {}, eventName);
    });
    if (typeof options.onValidation === 'function') {
      options.onValidation(data || {}, eventName);
    }
  }

  function mergeInputEvents(options, customEvents) {
    customEvents = customEvents || {};

    function handler(eventName) {
      var customName = {
        change: 'onChange',
        focus: 'onFocus',
        blur: 'onBlur',
        submit: 'onInputSubmitRequest'
      }[eventName];

      return function (data) {
        updateValidation(options, data, eventName);
        if (typeof customEvents[customName] === 'function') {
          return customEvents[customName](data);
        }
      };
    }

    return {
      onChange: handler('change'),
      onFocus: handler('focus'),
      onBlur: handler('blur'),
      onInputSubmitRequest: handler('submit')
    };
  }

  function defaultStyle() {
    return {
      input: {
        'font-family': 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'font-size': '16px',
        color: '#1f2937'
      },
      '.valid': { color: '#166534' },
      '.invalid': { color: '#b91c1c' },
      ':focus': { color: '#111827' }
    };
  }

  function normalizedCardOptions(options) {
    var selectors = {
      number: options.numberContainer || DEFAULT_SELECTORS.number,
      expiry: options.expiryContainer || DEFAULT_SELECTORS.expiry,
      cvv: options.cvvContainer || DEFAULT_SELECTORS.cvv,
      holder: options.holderContainer || DEFAULT_SELECTORS.holder
    };

    return objectAssign({}, options, {
      selectors: selectors,
      classes: objectAssign({
        focused: 'paypal-card-field--focused',
        valid: 'paypal-card-field--valid',
        invalid: 'paypal-card-field--invalid'
      }, options.classes || {}),
      messages: objectAssign({}, DEFAULT_MESSAGES, options.messages || {}),
      style: objectAssign({}, defaultStyle(), options.style || {})
    });
  }

  function approvalHandler(options) {
    return function (data, actions) {
      var approval = { data: data || null, result: undefined };
      lastApproval = approval;
      var result = typeof options.onApprove === 'function'
        ? options.onApprove(data, actions)
        : undefined;

      return Promise.resolve(result).then(function (value) {
        approval.result = value;
        return value;
      });
    };
  }

  function renderCurrentCardFields(paypal, options) {
    if (typeof options.createOrder !== 'function') {
      return Promise.reject(new Error('options.createOrder é obrigatório para inicializar Card Fields.'));
    }

    var config = {
      createOrder: options.createOrder,
      onApprove: approvalHandler(options),
      onError: options.onError,
      onCancel: options.onCancel,
      style: options.style,
      inputEvents: mergeInputEvents(options, options.inputEvents)
    };
    var instance = paypal.CardFields(config);

    if (!instance || (typeof instance.isEligible === 'function' && !instance.isEligible())) {
      return Promise.reject(new Error('Card Fields não está elegível para esta conta ou navegador.'));
    }

    var fieldDefinitions = [
      ['number', 'NumberField', options.numberOptions],
      ['expiry', 'ExpiryField', options.expiryOptions],
      ['cvv', 'CVVField', options.cvvOptions],
      ['holder', 'NameField', options.holderOptions]
    ];
    var fields = {};
    var renders = [];

    fieldDefinitions.forEach(function (definition) {
      var fieldName = definition[0];
      var factoryName = definition[1];
      var container = findElement(options.selectors[fieldName]);
      var required = fieldName !== 'holder';

      if (!container) {
        if (required) throw new Error('Contêiner PayPal não encontrado: ' + options.selectors[fieldName]);
        return;
      }
      if (typeof instance[factoryName] !== 'function') {
        if (required) throw new Error('O SDK do PayPal não expõe ' + factoryName + '().');
        return;
      }

      var fieldOptions = objectAssign({ style: options.style }, definition[2] || {});
      fields[fieldName] = instance[factoryName](fieldOptions);
      renders.push(Promise.resolve(fields[fieldName].render(options.selectors[fieldName])));
    });

    return Promise.all(renders).then(function () {
      return { mode: 'card-fields', instance: instance, fields: fields };
    });
  }

  function renderLegacyHostedFields(paypal, options) {
    if (typeof options.createOrder !== 'function') {
      return Promise.reject(new Error('options.createOrder é obrigatório para inicializar Hosted Fields.'));
    }
    if (typeof paypal.HostedFields.isEligible === 'function' && !paypal.HostedFields.isEligible()) {
      return Promise.reject(new Error('Hosted Fields não está elegível para esta conta ou navegador.'));
    }

    ['number', 'expiry', 'cvv'].forEach(function (fieldName) {
      if (!findElement(options.selectors[fieldName])) {
        throw new Error('Contêiner PayPal não encontrado: ' + options.selectors[fieldName]);
      }
    });

    return Promise.resolve(paypal.HostedFields.render({
      createOrder: options.createOrder,
      styles: options.style,
      fields: {
        number: objectAssign({ selector: options.selectors.number }, options.numberOptions || {}),
        expirationDate: objectAssign({ selector: options.selectors.expiry }, options.expiryOptions || {}),
        cvv: objectAssign({ selector: options.selectors.cvv }, options.cvvOptions || {})
      }
    })).then(function (instance) {
      ['focus', 'blur', 'validityChange', 'cardTypeChange'].forEach(function (eventName) {
        if (instance && typeof instance.on === 'function') {
          instance.on(eventName, function (data) {
            updateValidation(options, data, eventName === 'validityChange' ? 'change' : eventName);
          });
        }
      });
      return { mode: 'hosted-fields', instance: instance, fields: {} };
    });
  }

  /** Inicializa e renderiza Card Fields (ou Hosted Fields legado como fallback). */
  function paypalCardFieldsInit(options) {
    options = normalizedCardOptions(options || {});

    var sdkReady = options.sdkOptions
      ? paypalCarregamentoSDK(options.sdkOptions)
      : Promise.resolve(global.paypal);

    return sdkReady.then(function (paypal) {
      if (!paypal) throw new Error('Carregue o SDK do PayPal antes de inicializar Card Fields.');

      if (!options.useHostedFields && typeof paypal.CardFields === 'function') {
        return renderCurrentCardFields(paypal, options);
      }
      if (paypal.HostedFields && typeof paypal.HostedFields.render === 'function') {
        return renderLegacyHostedFields(paypal, options);
      }
      throw new Error('O componente card-fields não está disponível no SDK carregado.');
    }).then(function (context) {
      context.options = options;
      cardFieldsContext = context;
      return context;
    });
  }

  function firstPaymentSource(values) {
    for (var i = 0; i < values.length; i += 1) {
      var value = values[i];
      if (!value || typeof value !== 'object') continue;
      if (value.payment_source && typeof value.payment_source === 'object') return value.payment_source;
      if (value.paymentSource && typeof value.paymentSource === 'object') return value.paymentSource;
    }
    return null;
  }

  function firstOrderId(values) {
    var keys = ['order_id', 'orderID', 'orderId', 'id'];
    for (var i = 0; i < values.length; i += 1) {
      var value = values[i];
      if (!value || typeof value !== 'object') continue;
      for (var j = 0; j < keys.length; j += 1) {
        if (typeof value[keys[j]] === 'string' && value[keys[j]]) return value[keys[j]];
      }
    }
    return null;
  }

  /**
   * Submete os campos e normaliza o retorno para o backend Conn2Flow.
   * CardFields normalmente retorna order_id; payment_source só é preenchido
   * quando o SDK ou um callback da integração realmente fornece essa estrutura.
   */
  function paypalCardFieldsSubmit(options) {
    options = options || {};
    var context = options.cardFields || cardFieldsContext;
    if (!context) return Promise.reject(new Error('Inicialize os Card Fields antes do submit.'));

    var instance = context.instance || context;
    if (!instance || typeof instance.submit !== 'function') {
      return Promise.reject(new Error('A instância de Card Fields não suporta submit().'));
    }

    var statePromise = typeof instance.getState === 'function' && options.validate !== false
      ? Promise.resolve(instance.getState())
      : Promise.resolve(null);

    lastApproval = null;
    return statePromise.then(function (state) {
      if (state && state.isFormValid === false) {
        updateValidation(context.options || normalizedCardOptions({}), state, 'blur');
        var error = new Error('Revise os dados do cartão antes de continuar.');
        error.code = 'PAYPAL_CARD_FIELDS_INVALID';
        error.state = state;
        throw error;
      }

      var submitData = options.submitData || {};
      if (options.billingAddress) {
        submitData = objectAssign({}, submitData, { billingAddress: options.billingAddress });
      }
      return Promise.resolve(instance.submit(submitData));
    }).then(function (sdkResult) {
      var approvalData = lastApproval ? lastApproval.data : null;
      var approvalResult = lastApproval ? lastApproval.result : null;
      var explicitSource = typeof options.getPaymentSource === 'function'
        ? options.getPaymentSource(sdkResult, approvalData, approvalResult)
        : options.paymentSource;

      return Promise.resolve(explicitSource).then(function (resolvedSource) {
        var explicitEnvelope = resolvedSource && (resolvedSource.payment_source || resolvedSource.paymentSource)
          ? resolvedSource
          : (resolvedSource ? { payment_source: resolvedSource } : null);
        var paymentSource = firstPaymentSource([
          explicitEnvelope,
          sdkResult,
          approvalData,
          approvalResult
        ]);
        var orderId = firstOrderId([sdkResult, approvalData, approvalResult]);

        if (options.requirePaymentSource && !paymentSource) {
          throw new Error('O submit foi concluído sem um payment_source retornado pelo provedor.');
        }

        return {
          payment_source: paymentSource,
          order_id: orderId,
          approval_data: approvalData,
          sdk_result: sdkResult === undefined ? null : sdkResult
        };
      });
    });
  }

  var api = {
    carregarSDK: paypalCarregamentoSDK,
    cardFieldsInit: paypalCardFieldsInit,
    cardFieldsSubmit: paypalCardFieldsSubmit,
    getCardFields: function () { return cardFieldsContext; }
  };

  global.paypalCarregamentoSDK = paypalCarregamentoSDK;
  global.paypalCardFieldsInit = paypalCardFieldsInit;
  global.paypalCardFieldsSubmit = paypalCardFieldsSubmit;
  global.conn2flowPaypal = api;
}(typeof window !== 'undefined' ? window : globalThis));
