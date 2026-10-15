import { readFileSync } from 'node:fs';
import { describe, expect, it, vi } from 'vitest';

function load(module, { fallback = false } = {}) {
    document.body.innerHTML = `<form>
        <input id="page-visibility-link" value="https://example.test/page/?acesso=old">
        <input name="regenerar_hash_privado" value="0">
        <input name="token" value="test-token">
        <button type="button" id="page-visibility-regenerate" data-confirm="Gerar outro link?">Gerar</button>
    </form>`;
    const ready = [];
    const handlers = new Map();
    const submit = vi.fn();
    const form = document.querySelector('form');
    form.requestSubmit = fallback ? undefined : submit;
    const $ = (target) => {
        if (target === document) return {
            ready: (callback) => ready.push(callback),
            on: (event, selector, callback) => handlers.set(`${event}:${selector}`, callback),
        };
        const nodes = typeof target === 'string' ? [...document.querySelectorAll(target)] : [target];
        return {
            0: nodes[0], length: nodes.length,
            val(value) {
                if (value === undefined) return nodes[0]?.value;
                nodes.forEach((node) => { node.value = value; });
            },
            attr: (name) => nodes[0]?.getAttribute(name),
            closest: (selector) => $(nodes[0].closest(selector)),
            trigger: submit,
        };
    };
    let resolve;
    const confirmar = vi.fn(() => new Promise((done) => { resolve = done; }));
    const browser = { c2fControles: { dialogo: { confirmar } } };
    new Function('$', 'document', 'window', readFileSync(`gestor/modulos/${module}/${module}.js`, 'utf8'))($, document, browser);
    // Inicialização de visibilidade é o último ready; os editores não fazem parte deste cenário.
    ready.at(-1)();
    const event = { preventDefault: vi.fn() };
    const click = () => handlers.get('click:#page-visibility-regenerate').call(document.querySelector('button'), event);
    return { click, confirmar, event, submit, decide: (accepted) => resolve(accepted) };
}

for (const module of ['admin-paginas', 'publisher-pages']) {
    describe(`${module}: confirmação do link privado (REQ-276)`, () => {
        it('aguarda a decisão; cancelar preserva o link, o campo e o formulário', async () => {
            const test = load(module);
            const pending = test.click();
            expect(test.event.preventDefault).toHaveBeenCalledOnce();
            expect(test.confirmar).toHaveBeenCalledWith('Gerar outro link?');
            expect(test.submit).not.toHaveBeenCalled();
            expect(document.querySelector('[name="regenerar_hash_privado"]').value).toBe('0');
            test.decide(false);
            await pending;
            expect(test.submit).not.toHaveBeenCalled();
            expect(document.querySelector('[name="regenerar_hash_privado"]').value).toBe('0');
            expect(document.querySelector('#page-visibility-link').value).toContain('acesso=old');
        });

        for (const fallback of [false, true]) {
            it(`confirma e salva pelo formulário com token (fallback=${fallback})`, async () => {
                const test = load(module, { fallback });
                const pending = test.click();
                expect(test.submit).not.toHaveBeenCalled();
                test.decide(true);
                await pending;
                expect(document.querySelector('[name="regenerar_hash_privado"]').value).toBe('1');
                expect(document.querySelector('[name="token"]').value).toBe('test-token');
                expect(test.submit).toHaveBeenCalledOnce();
                if (fallback) expect(test.submit).toHaveBeenCalledWith('submit');
            });
        }
    });
}
