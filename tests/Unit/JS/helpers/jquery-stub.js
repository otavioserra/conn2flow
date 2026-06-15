import { vi } from 'vitest';

export function installJQueryStub({ ajax } = {}) {
  const ajaxMock = ajax || vi.fn();
  const delegated = [];

  class JQueryCollection {
    constructor(elements) {
      this.elements = elements.filter(Boolean);
      this.length = this.elements.length;
      this.elements.forEach((el, index) => {
        this[index] = el;
      });
    }

    ready(callback) {
      callback();
      return this;
    }

    each(callback) {
      this.elements.forEach((el, index) => callback.call(el, index, el));
      return this;
    }

    first() {
      return new JQueryCollection(this.elements.slice(0, 1));
    }

    find(selector) {
      return new JQueryCollection(this.elements.flatMap((el) => Array.from(el.querySelectorAll(selector))));
    }

    children(selector) {
      const kids = this.elements.flatMap((el) => Array.from(el.children));
      return new JQueryCollection(selector ? kids.filter((el) => el.matches(selector)) : kids);
    }

    not(selector) {
      return new JQueryCollection(this.elements.filter((el) => !(el.nodeType === 1 && el.matches(selector))));
    }

    empty() {
      this.elements.forEach((el) => { el.innerHTML = ''; });
      return this;
    }

    append(content) {
      this.elements.forEach((el) => {
        if (content instanceof JQueryCollection) {
          content.elements.forEach((child) => el.appendChild(child));
        } else if (content instanceof Node) {
          el.appendChild(content);
        } else {
          el.insertAdjacentHTML('beforeend', String(content));
        }
      });
      return this;
    }

    html(value) {
      if (value === undefined) return this.elements[0]?.innerHTML || '';
      this.elements.forEach((el) => { el.innerHTML = String(value); });
      return this;
    }

    text(value) {
      if (value === undefined) return this.elements[0]?.textContent || '';
      this.elements.forEach((el) => { el.textContent = String(value); });
      return this;
    }

    val(value) {
      if (value === undefined) return this.elements[0]?.value || '';
      this.elements.forEach((el) => { el.value = value; });
      return this;
    }

    attr(name, value) {
      if (value === undefined) return this.elements[0]?.getAttribute(name);
      this.elements.forEach((el) => el.setAttribute(name, value));
      return this;
    }

    data(name, value) {
      if (!this.elements[0]) return value === undefined ? undefined : this;
      const key = `c2f_${name}`;
      if (value === undefined) {
        return this.elements[0][key] ?? this.elements[0].dataset?.[name];
      }
      this.elements.forEach((el) => { el[key] = value; });
      return this;
    }

    on(eventName, selectorOrHandler, maybeHandler) {
      const delegatedSelector = typeof selectorOrHandler === 'string' ? selectorOrHandler : null;
      const handler = delegatedSelector ? maybeHandler : selectorOrHandler;

      if (delegatedSelector) {
        delegated.push({ eventName, selector: delegatedSelector, handler });
        return this;
      }

      this.elements.forEach((el) => {
        el.addEventListener(eventName, (event) => handler.call(el, event));
      });
      return this;
    }

    trigger(eventName) {
      this.elements.forEach((el) => el.dispatchEvent(new Event(eventName, { bubbles: true })));
      return this;
    }

    addClass(className) {
      this.elements.forEach((el) => el.classList.add(...String(className).split(/\s+/).filter(Boolean)));
      return this;
    }

    removeClass(className) {
      this.elements.forEach((el) => el.classList.remove(...String(className).split(/\s+/).filter(Boolean)));
      return this;
    }

    toggle(show) {
      this.elements.forEach((el) => { el.hidden = !show; });
      return this;
    }

    show() {
      this.elements.forEach((el) => { el.hidden = false; });
      return this;
    }

    hide() {
      this.elements.forEach((el) => { el.hidden = true; });
      return this;
    }

    dropdown() { return this; }
    tab() { return this; }
    sortable() { return this; }
    remove() {
      this.elements.forEach((el) => el.remove());
      return this;
    }
  }

  function $(selector) {
    if (typeof selector === 'function') {
      selector();
      return new JQueryCollection([document]);
    }

    if (selector instanceof JQueryCollection) return selector;
    if (selector === document || selector === window || selector instanceof Node) return new JQueryCollection([selector]);

    if (typeof selector === 'string' && selector.trim().startsWith('<')) {
      const template = document.createElement('template');
      template.innerHTML = selector.trim();
      return new JQueryCollection([template.content.firstElementChild]);
    }

    if (typeof selector === 'string') {
      return new JQueryCollection(Array.from(document.querySelectorAll(selector)));
    }

    return new JQueryCollection([]);
  }

  $.ajax = ajaxMock;
  $.extend = function extend(...args) {
    let deep = false;
    if (typeof args[0] === 'boolean') {
      deep = args.shift();
    }
    const target = args.shift() || {};
    args.forEach((source) => {
      Object.entries(source || {}).forEach(([key, value]) => {
        target[key] = deep && value && typeof value === 'object'
          ? extend(true, Array.isArray(value) ? [] : {}, value)
          : value;
      });
    });
    return target;
  };

  document.addEventListener('click', (event) => {
    delegated
      .filter((item) => item.eventName === 'click')
      .forEach((item) => {
        const target = event.target.closest(item.selector);
        if (target) item.handler.call(target, event);
      });
  });

  globalThis.$ = $;
  globalThis.jQuery = $;

  return { $, ajaxMock };
}
