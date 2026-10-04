import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const html = readFileSync('gestor/resources/pt-br/components/interface-listar-tailwind/interface-listar-tailwind.html', 'utf8');
const code = readFileSync('gestor/assets/interface/interface-listar-tailwind.js', 'utf8');
const config = () => ({ url: 'piloto/', id: 'id', acoesId: '_gestor_acoes_id', status: 'status', pageLength: 10,
  order: [[1, 'asc']], columns: [{ data: '_gestor_acoes_id', orderable: false }, { data: 'nome', name: 'Nome' }, { data: 'formatado', name: 'Host', html: true }],
  opcoes: {
    editar: { url: 'editar/', tooltip: 'Editar', lucide: 'pencil' },
    ativar: { opcao: 'status', status_atual: 'I', status_mudar: 'A', tooltip: 'Ativar', lucide: 'eye' },
    desativar: { opcao: 'status', status_atual: 'A', status_mudar: 'I', tooltip: 'Desativar', lucide: 'eye-off' },
    excluir: { opcao: 'excluir', tooltip: 'Excluir', lucide: 'trash-2' }
  }
});
const rows = [{ _gestor_acoes_id: 'a&?b', nome: '<img src=x onerror=alert(1)>', formatado: '<b>Sim</b>', status: 'A' }];
const response = (data = rows, total = 21) => ({ ok: true, status: 200, json: async () => ({ data, recordsFiltered: total, recordsTotal: total }) });
let raiz;
function montar() { return window.c2fListaTailwind.iniciar(raiz, config()); }
async function pronto() { await vi.waitFor(() => expect(raiz.getAttribute('aria-busy')).toBe('false')); }

beforeEach(() => {
  document.body.innerHTML = html;
  raiz = document.querySelector('[data-c2f-listar]');
  window.gestor = { raiz: '/', csrfToken: 'teste' };
  window.fetch = vi.fn().mockResolvedValue(response());
  window.c2fControles = { dialogo: { confirmar: vi.fn().mockResolvedValue(false) } };
  vm.runInThisContext(code);
});

describe('Listagem Tailwind (req-220)', () => {
  it('envia contrato AJAX, índices de coluna e nenhum nome SQL do navegador', async () => {
    montar(); await pronto();
    const [url, opts] = window.fetch.mock.calls[0];
    expect(url).toContain('/piloto/');
    expect(opts.body.get('ajax')).toBe('sim');
    expect(opts.body.get('opcao')).toBe('listar');
    expect(opts.body.get('ajaxOpcao')).toBe('listar');
    expect(opts.body.get('order[0][column]')).toBe('1');
    expect(opts.body.get('columns[1][data]')).toBeNull();
    expect(raiz.querySelector('tbody').textContent).toContain('<img');
    expect(raiz.querySelector('tbody img')).toBeNull();
    expect(raiz.querySelector('tbody').textContent).toContain('Sim');
  });

  it('pagina, altera quantidade e ordena no servidor com indicação acessível', async () => {
    const lista = montar(); await pronto();
    raiz.querySelector('[data-lista-proxima]').click(); await pronto();
    expect(window.fetch.mock.lastCall[1].body.get('start')).toBe('10');
    raiz.querySelector('[data-lista-anterior]').click(); await pronto();
    expect(lista.estado.inicio).toBe(0);
    raiz.querySelectorAll('th button')[1].click(); await pronto();
    expect(window.fetch.mock.lastCall[1].body.get('order[0][dir]')).toBe('desc');
    expect(raiz.querySelectorAll('th')[1].getAttribute('aria-sort')).toBe('descending');
    const select = raiz.querySelector('select'); select.value = '25'; select.dispatchEvent(new Event('change')); await pronto();
    expect(window.fetch.mock.lastCall[1].body.get('length')).toBe('25');
  });

  it('busca com debounce e Enter imediato, incluindo consulta vazia', async () => {
    montar(); await pronto();
    const input = raiz.querySelector('input'); input.value = 'abc'; input.dispatchEvent(new Event('input'));
    expect(window.fetch).toHaveBeenCalledTimes(1);
    await vi.waitFor(() => expect(window.fetch).toHaveBeenCalledTimes(2)); await pronto();
    expect(window.fetch.mock.lastCall[1].body.get('search[value]')).toBe('abc');
    input.value = ''; input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' })); await pronto();
    expect(window.fetch.mock.lastCall[1].body.get('search[value]')).toBe('');
  });

  it('ações usam id cru codificado, CSRF e confirmação perigosa sem navegação na recusa', async () => {
    montar(); await pronto();
    const edit = raiz.querySelector('[data-lista-acao="editar"]');
    expect(new URL(edit.href).searchParams.get('id')).toBe('a&?b');
    expect(raiz.querySelector('[data-lista-acao="ativar"]')).toBeNull();
    const status = raiz.querySelector('[data-lista-acao="desativar"]');
    expect(new URL(status.href).searchParams.get('_csrf_token')).toBe('teste');
    expect(new URL(status.href).searchParams.get('status')).toBe('I');
    const button = raiz.querySelector('[data-lista-acao="excluir"]'); button.click();
    await vi.waitFor(() => expect(button.disabled).toBe(false));
    expect(window.c2fControles.dialogo.confirmar).toHaveBeenCalledWith('#mensagem-excluir#', expect.objectContaining({ perigo: true }));
    expect(button.classList.contains('excluir')).toBe(false);
  });

  it('confirmação aceita inclui token atual e não permite duas confirmações simultâneas', async () => {
    const assign = vi.spyOn(window.location, 'assign').mockImplementation(() => {});
    let confirmar; window.c2fControles.dialogo.confirmar.mockImplementation(() => new Promise(resolve => { confirmar = resolve; }));
    montar(); await pronto();
    const button = raiz.querySelector('[data-lista-acao="excluir"]'); button.click(); button.click();
    expect(window.c2fControles.dialogo.confirmar).toHaveBeenCalledTimes(1);
    window.gestor.csrfToken = 'renovado'; confirmar(true);
    await vi.waitFor(() => expect(assign).toHaveBeenCalled());
    const url = new URL(assign.mock.lastCall[0]);
    expect(url.searchParams.get('opcao')).toBe('excluir');
    expect(url.searchParams.get('_csrf_token')).toBe('renovado');
  });

  it('resposta vazia tem mensagem e navegação desabilitada', async () => {
    window.fetch.mockResolvedValue(response([], 0)); montar(); await pronto();
    expect(raiz.querySelector('[data-lista-mensagem]').textContent).toBe('#vazia#');
    expect(raiz.querySelector('[data-lista-anterior]').disabled).toBe(true);
    expect(raiz.querySelector('[data-lista-proxima]').disabled).toBe(true);
  });

  it('falha e JSON inválido liberam carregamento e mostram texto da variável', async () => {
    window.fetch.mockResolvedValue({ ok: false, status: 403 }); const lista = montar(); await pronto();
    expect(raiz.querySelector('[data-lista-mensagem]').textContent).toBe('#erro#');
    window.fetch.mockResolvedValue({ ok: true, status: 200, json: async () => ({ status: 'Erro' }) });
    await lista.carregar();
    expect(raiz.querySelector('[data-lista-mensagem]').textContent).toBe('#erro#');
  });

  it('sessão expirada redireciona e boot repetido não duplica cabeçalho nem pedidos', async () => {
    const assign = vi.spyOn(window.location, 'assign').mockImplementation(() => {});
    window.fetch.mockResolvedValue({ ok: false, status: 401 });
    montar(); await pronto(); montar();
    expect(assign).toHaveBeenCalledWith(expect.stringContaining('/signin/'));
    expect(window.fetch).toHaveBeenCalledTimes(1);
    expect(raiz.querySelectorAll('th')).toHaveLength(3);
  });

  it('resposta antiga não substitui busca mais recente mesmo sem respeitar AbortSignal', async () => {
    let liberar; window.fetch.mockImplementationOnce(() => new Promise(resolve => { liberar = resolve; }));
    const lista = montar();
    await lista.carregar(); liberar(response([{ nome: 'antiga' }], 1));
    await pronto();
    expect(raiz.querySelector('tbody').textContent).not.toContain('antiga');
  });

  it('volta à primeira página quando a contagem diminui', async () => {
    const lista = montar(); await pronto();
    lista.estado.inicio = 20; window.fetch.mockResolvedValue(response(rows, 1)); await lista.carregar(); await pronto();
    expect(lista.estado.inicio).toBe(0);
    expect(window.fetch.mock.lastCall[1].body.get('start')).toBe('0');
  });
});
