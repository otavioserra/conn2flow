import { readFileSync } from 'node:fs';
import { describe, it, expect, beforeEach } from 'vitest';

describe('Campo monetário compartilhado', () => {
    beforeEach(() => {
        document.body.innerHTML = '<form><input name="price" value="1234.56" data-c2f-mascara="moeda"><input name="sale_price" value="" data-c2f-mascara="moeda"></form>';
        window.eval(readFileSync('gestor/assets/interface/campo-moeda.js', 'utf8'));
        window.C2FCampoMoeda.iniciar(document);
    });
    it('formata milhares e centavos e conserva um promocional vazio', () => {
        expect(document.querySelector('[name=price]').value.replace(/\s/g, ' ')).toBe('R$ 1.234,56');
        expect(document.querySelector('[name=sale_price]').value).toBe('');
    });
    it('digitação em centavos, limpeza e letras não geram NaN', () => {
        const input = document.querySelector('[name=price]');
        input.value = '123456'; input.dispatchEvent(new Event('input'));
        expect(window.C2FCampoMoeda.decimal(input.value)).toBe('1234.56');
        input.value = 'abc'; input.dispatchEvent(new Event('input'));
        expect(input.value).toBe('');
    });
    it('normaliza prefixos e rejeita decimais inválidos', () => {
        const parse = window.C2FCampoMoeda.decimal;
        expect(parse('R$ 1.234,56')).toBe('1234.56');
        expect(parse('US$ 1.234,56')).toBe('1234.56');
        expect(parse('1234.56')).toBe('1234.56');
        expect(parse('abc')).toBe('');
        expect(parse('-10')).toBe('');
        expect(parse('10.123')).toBe('');
    });
    it('envia decimal sem alterar a exibição e inclui campos inseridos depois', () => {
        const form = document.querySelector('form');
        const formData = new FormData();
        const event = new Event('formdata');
        Object.defineProperty(event, 'formData', { value: formData });
        form.dispatchEvent(event);
        expect(formData.get('price')).toBe('1234.56');
        expect(formData.get('sale_price')).toBe('');
        const input = document.createElement('input');
        input.name = 'variant'; input.dataset.c2fMascara = 'moeda'; input.value = '10.50';
        form.appendChild(input); window.C2FCampoMoeda.iniciar(form);
        form.dispatchEvent(event);
        expect(formData.get('variant')).toBe('10.50');
    });
});
