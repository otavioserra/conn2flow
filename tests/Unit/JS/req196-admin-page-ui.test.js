import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

describe('REQ-196 admin-paginas', () => {
  it('alterna o repetidor e adiciona/remove pares sem afetar o layout padrão', () => {
    document.body.innerHTML = `
      <select name="layout"><option value="base" selected>Base</option></select>
      <div class="field"><input type="checkbox" name="mapear_layouts_perfis"></div>
      <div class="layout-profile-fields" hidden>
        <div class="layout-profile-rows"></div>
        <button type="button" class="layout-profile-add">Adicionar</button>
        <template class="layout-profile-template">
          <div class="layout-profile-row">
            <select id="layout-profile-layout-template" class="ui search clearable dropdown" name="layout_profile_layout[]"><option value="cliente">Cliente</option></select>
            <select id="layout-profile-profile-template" class="ui search clearable dropdown" name="layout_profile_profile[]"><option value="comprador">Comprador</option></select>
            <button type="button" class="layout-profile-remove">Remover</button>
          </div>
        </template>
      </div>`;
    const { $ } = installJQueryStub();
    const dropdownInit = vi.spyOn($.fn, 'dropdown');
    const script = readFileSync(resolve('gestor/modulos/admin-paginas/admin-paginas.js'), 'utf8');
    vm.runInThisContext(script, { filename: 'admin-paginas.js' });

    const toggle = document.querySelector('[name="mapear_layouts_perfis"]');
    const fields = document.querySelector('.layout-profile-fields');
    expect(fields.hidden).toBe(true);
    toggle.checked = true;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(fields.hidden).toBe(false);

    document.querySelector('.layout-profile-add').click();
    expect(document.querySelectorAll('.layout-profile-rows .layout-profile-row')).toHaveLength(1);
    expect(document.querySelector('.layout-profile-rows [name="layout_profile_profile[]"]').value).toBe('comprador');
    expect(document.querySelector('.layout-profile-rows [name="layout_profile_layout[]"]').id).toBe('layout-profile-layout-0');
    expect(dropdownInit).toHaveBeenCalled();

    document.querySelector('.layout-profile-rows .layout-profile-remove').click();
    expect(document.querySelectorAll('.layout-profile-rows .layout-profile-row')).toHaveLength(0);
    document.querySelector('.layout-profile-add').click();
    expect(document.querySelector('.layout-profile-rows [name="layout_profile_layout[]"]').id).toBe('layout-profile-layout-1');
    toggle.checked = false;
    toggle.dispatchEvent(new Event('change', { bubbles: true }));
    expect(fields.hidden).toBe(true);
    expect(document.querySelector('[name="layout"]').value).toBe('base');
  });
});
