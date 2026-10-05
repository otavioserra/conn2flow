import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';

function executarVariables({ tailwind, valor = '17' }) {
    const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/variables/variables.js'), 'utf8');
    const document = {};
    const element = {};
    const handlers = {};
    const select = {
        is: () => tailwind,
        on: (event, handler) => { handlers[event] = handler; },
        dropdown: vi.fn()
    };
    const $ = (target) => {
        if (target === document) return { ready: (callback) => callback() };
        if (target === '.gestorModule') return select;
        if (target === element) return { val: () => valor };
        throw new Error('Unexpected selector');
    };
    const window = { open: vi.fn() };

    vm.runInNewContext(code, {
        $, document, window,
        gestor: { raiz: '/gestor/', moduloCaminho: 'variables/' }
    });

    return { document, element, handlers, select, window };
}

function executarModulos({ pluginDisponivel, nativos }) {
    const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/modulos/modulos.js'), 'utf8');
    const document = {};
    const dropdowns = { length: nativos ? 0 : 1 };
    const inicializarDropdown = vi.fn();
    dropdowns.dropdown = () => inicializarDropdown();

    const $ = (selector) => {
        if (selector === document) return { ready: (callback) => callback() };
        if (selector === '#_gestor-interface-edit-dados') return { length: 1 };
        if (selector === '.ui.dropdown') return {
            not: (value) => {
                if (value !== '[data-c2f-select]') throw new Error('Unexpected filter');
                return dropdowns;
            }
        };
        throw new Error('Unexpected selector');
    };
    $.fn = pluginDisponivel ? { dropdown: inicializarDropdown } : {};

    vm.runInNewContext(code, { $, document });
    return inicializarDropdown;
}

describe('seletores dos módulos no Tailwind', () => {
    it('navega com o evento change do select nativo', () => {
        const { element, handlers, select, window } = executarVariables({ tailwind: true });

        expect(handlers.change).toBeTypeOf('function');
        expect(select.dropdown).not.toHaveBeenCalled();
        handlers.change.call(element);
        expect(window.open).toHaveBeenCalledWith('/gestor/variables/?id=17', '_self');
    });

    it('mantém a inicialização do dropdown Fomantic legado', () => {
        const { select, window } = executarVariables({ tailwind: false });

        expect(select.dropdown).toHaveBeenCalledOnce();
        select.dropdown.mock.calls[0][0].onChange('23');
        expect(window.open).toHaveBeenCalledWith('/gestor/variables/?id=23', '_self');
    });

    it('não chama Fomantic indisponível nos selects nativos', () => {
        expect(executarModulos({ pluginDisponivel: false, nativos: true })).not.toHaveBeenCalled();
    });

    it('exclui selects nativos mesmo quando Fomantic está carregado', () => {
        expect(executarModulos({ pluginDisponivel: true, nativos: true })).not.toHaveBeenCalled();
    });

    it('continua inicializando dropdowns clássicos quando Fomantic está presente', () => {
        expect(executarModulos({ pluginDisponivel: true, nativos: false })).toHaveBeenCalledOnce();
    });
});