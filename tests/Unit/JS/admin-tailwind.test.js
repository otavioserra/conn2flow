import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';

/**
 * req-118 (BATCH-119) — runtime do layout administrativo Tailwind.
 *
 * Carrega o arquivo REAL (IIFE que expõe `window.gestorAdminTailwind`). O legado `admin.js`/bloco de
 * menu do `global.js` continua intocado: `gestor_pagina_menu()` injeta um OU outro conforme o
 * framework resolvido, e os dois nunca convivem na mesma página.
 */
function carregarRuntime() {
  const code = readFileSync(resolve(process.cwd(), 'gestor/assets/global/admin-tailwind.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'admin-tailwind.js' });
  return window.gestorAdminTailwind;
}

// Espelha a marcação real: layout-administrativo-tailwind + componente menu-principal-sistema-tailwind.
function montarShell() {
  document.body.innerHTML = `
    <div id="c2f-admin-shell" data-menu-largura-min="220" data-menu-largura-max="450" data-menu-largura-padrao="260">
      <div data-admin-overlay class="hidden"></div>
      <aside data-admin-sidebar class="-translate-x-full lg:translate-x-0">
        <button type="button" data-admin-fechar></button>
        <button type="button" data-admin-dashboard3d></button>
        <div class="flex h-full flex-col">
          <div>
            <input type="text" id="gestor-menu-filtro">
            <p data-menu-vazio class="hidden">Nenhum módulo encontrado.</p>
          </div>
          <nav>
            <div data-menu-grupo>
              <div>
                <a data-menu-item href="/dashboard/"><span data-menu-item-nome>Dashboard</span></a>
              </div>
            </div>
            <div data-menu-grupo>
              <p data-menu-grupo-titulo>Conteúdo</p>
              <div>
                <a data-menu-item href="/admin-paginas/"><span data-menu-item-nome>Páginas</span></a>
                <a data-menu-item href="/publisher/"><span data-menu-item-nome>Publicações</span></a>
              </div>
            </div>
            <div data-menu-grupo>
              <p data-menu-grupo-titulo>Sistema</p>
              <div>
                <a data-menu-item href="/usuarios/"><span data-menu-item-nome>Usuários</span></a>
              </div>
            </div>
          </nav>
        </div>
        <div data-admin-resize></div>
      </aside>
      <div data-admin-conteudo class="lg:ml-[260px]">
        <header>
          <button type="button" data-admin-abrir></button>
        </header>
        <main data-admin-main class="mx-auto w-full max-w-7xl"></main>
      </div>
    </div>
  `;
}

function larguraDaTela(px) {
  Object.defineProperty(window, 'innerWidth', { value: px, writable: true, configurable: true });
}

function filtrar(termo) {
  const campo = document.getElementById('gestor-menu-filtro');
  campo.value = termo;
  campo.dispatchEvent(new window.Event('input', { bubbles: true }));
}

function itensVisiveis() {
  return Array.from(document.querySelectorAll('[data-menu-item]')).filter(
    (el) => !el.classList.contains('hidden')
  );
}

describe('Layout administrativo Tailwind — runtime do menu (req-118)', () => {
  let T;

  beforeEach(() => {
    document.body.innerHTML = '';
    window.localStorage.clear();
    larguraDaTela(1440);
    T = carregarRuntime();
  });

  describe('abrir e fechar', () => {
    it('no desktop nasce aberto e empurra o conteúdo pela largura da barra', () => {
      montarShell();
      T.iniciar();

      const sidebar = document.querySelector('[data-admin-sidebar]');
      const conteudo = document.querySelector('[data-admin-conteudo]');

      expect(sidebar.classList.contains('-translate-x-full')).toBe(false);
      expect(conteudo.style.marginLeft).toBe('260px');
    });

    it('remove a utility responsiva ao assumir o controle do estado', () => {
      // `lg:translate-x-0` existe só para a primeira pintura não dar salto; mantê-la faria a classe
      // do CSS brigar com o JS a cada fechamento no desktop.
      montarShell();
      T.iniciar();

      expect(document.querySelector('[data-admin-sidebar]').classList.contains('lg:translate-x-0')).toBe(false);
    });

    it('o botão fechar recolhe a barra e devolve a largura ao conteúdo', () => {
      montarShell();
      T.iniciar();

      document.querySelector('[data-admin-fechar]').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(document.querySelector('[data-admin-sidebar]').classList.contains('-translate-x-full')).toBe(true);
      expect(document.querySelector('[data-admin-conteudo]').style.marginLeft).toBe('0px');
    });

    it('o botão do topo alterna entre abrir e fechar', () => {
      montarShell();
      document.querySelector('[data-admin-conteudo]').insertAdjacentHTML(
        'beforebegin',
        '<button type="button" data-admin-abrir></button>'
      );
      T.iniciar();

      const botao = document.querySelector('[data-admin-abrir]');
      const sidebar = document.querySelector('[data-admin-sidebar]');

      botao.dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(sidebar.classList.contains('-translate-x-full')).toBe(true);

      botao.dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(sidebar.classList.contains('-translate-x-full')).toBe(false);
    });

    it('lembra que o menu estava fechado na visita anterior', () => {
      montarShell();
      T.iniciar();
      document.querySelector('[data-admin-fechar]').dispatchEvent(new window.Event('click', { bubbles: true }));

      montarShell();
      T.iniciar();

      expect(document.querySelector('[data-admin-sidebar]').classList.contains('-translate-x-full')).toBe(true);
    });

    it('em mobile nasce fechado e nunca empurra o conteúdo', () => {
      // Empurrar o conteúdo em telas estreitas colocaria a página inteira em rolagem horizontal.
      larguraDaTela(390);
      montarShell();
      T.iniciar();

      expect(document.querySelector('[data-admin-sidebar]').classList.contains('-translate-x-full')).toBe(true);
      expect(document.querySelector('[data-admin-conteudo]').style.marginLeft).toBe('0px');
    });

    it('em mobile o overlay aparece ao abrir e fecha o menu ao ser clicado', () => {
      larguraDaTela(390);
      montarShell();
      document.querySelector('[data-admin-conteudo]').insertAdjacentHTML(
        'beforebegin',
        '<button type="button" data-admin-abrir></button>'
      );
      T.iniciar();

      const overlay = document.querySelector('[data-admin-overlay]');

      document.querySelector('[data-admin-abrir]').dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(overlay.classList.contains('hidden')).toBe(false);

      overlay.dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(overlay.classList.contains('hidden')).toBe(true);
      expect(document.querySelector('[data-admin-sidebar]').classList.contains('-translate-x-full')).toBe(true);
    });

    it('em mobile o menu aberto não empurra o conteúdo', () => {
      larguraDaTela(390);
      montarShell();
      document.querySelector('[data-admin-conteudo]').insertAdjacentHTML(
        'beforebegin',
        '<button type="button" data-admin-abrir></button>'
      );
      T.iniciar();

      document.querySelector('[data-admin-abrir]').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(document.querySelector('[data-admin-conteudo]').style.marginLeft).toBe('0px');
    });

    // req-124 F3 (regressão): recolher o menu deixava uma faixa vazia da largura da barra. O runtime
    // limpava só o inline style, e a utility `lg:ml-[260px]` do layout reassumia o recuo — em
    // desktop o conteúdo nunca alcançava a borda esquerda.
    it('recolher o menu zera o recuo e libera a largura de leitura do conteúdo', () => {
      montarShell();
      document.querySelector('[data-admin-conteudo]').insertAdjacentHTML(
        'beforebegin',
        '<button type="button" data-admin-abrir></button>'
      );
      T.iniciar();

      const conteudo = document.querySelector('[data-admin-conteudo]');
      const principal = document.querySelector('[data-admin-main]');

      expect(conteudo.style.marginLeft).toBe('260px');
      expect(principal.style.maxWidth).toBe('');

      document.querySelector('[data-admin-abrir]').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(conteudo.style.marginLeft).toBe('0px');
      expect(principal.style.maxWidth).toBe('none');

      document.querySelector('[data-admin-abrir]').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(conteudo.style.marginLeft).toBe('260px');
      expect(principal.style.maxWidth).toBe('');
    });
  });

  describe('redimensionamento por arraste', () => {
    it('arrastar altera a largura da barra e do conteúdo', () => {
      montarShell();
      T.iniciar();

      const sidebar = document.querySelector('[data-admin-sidebar]');
      Object.defineProperty(sidebar, 'offsetWidth', { value: 260, configurable: true });

      document.querySelector('[data-admin-resize]').dispatchEvent(
        new window.MouseEvent('mousedown', { bubbles: true, cancelable: true, clientX: 260 })
      );
      document.dispatchEvent(
        new window.MouseEvent('mousemove', { bubbles: true, cancelable: true, clientX: 320 })
      );

      expect(sidebar.style.width).toBe('320px');
      expect(document.querySelector('[data-admin-conteudo]').style.marginLeft).toBe('320px');
    });

    it('respeita o mínimo e o máximo declarados no shell', () => {
      montarShell();
      T.iniciar();

      const sidebar = document.querySelector('[data-admin-sidebar]');
      Object.defineProperty(sidebar, 'offsetWidth', { value: 260, configurable: true });
      const handle = document.querySelector('[data-admin-resize]');

      handle.dispatchEvent(new window.MouseEvent('mousedown', { bubbles: true, cancelable: true, clientX: 260 }));
      document.dispatchEvent(new window.MouseEvent('mousemove', { bubbles: true, cancelable: true, clientX: 20 }));
      expect(sidebar.style.width).toBe('220px');

      document.dispatchEvent(new window.MouseEvent('mousemove', { bubbles: true, cancelable: true, clientX: 2000 }));
      expect(sidebar.style.width).toBe('450px');
    });

    it('persiste a largura ao soltar o mouse', () => {
      montarShell();
      T.iniciar();

      const sidebar = document.querySelector('[data-admin-sidebar]');
      Object.defineProperty(sidebar, 'offsetWidth', { value: 340, configurable: true });

      document.querySelector('[data-admin-resize]').dispatchEvent(
        new window.MouseEvent('mousedown', { bubbles: true, cancelable: true, clientX: 260 })
      );
      document.dispatchEvent(new window.MouseEvent('mouseup', { bubbles: true }));

      expect(window.localStorage.getItem('gestor-menu-width')).toBe('340');
    });

    it('marca o body durante o arraste e limpa ao soltar', () => {
      // Sem a marca, o navegador seleciona o texto sob o cursor e o arraste "engasga".
      montarShell();
      T.iniciar();

      const handle = document.querySelector('[data-admin-resize]');

      handle.dispatchEvent(new window.MouseEvent('mousedown', { bubbles: true, cancelable: true, clientX: 260 }));
      expect(document.body.classList.contains('c2f-admin-redimensionando')).toBe(true);

      document.dispatchEvent(new window.MouseEvent('mouseup', { bubbles: true }));
      expect(document.body.classList.contains('c2f-admin-redimensionando')).toBe(false);
    });

    it('mover sem ter iniciado o arraste não altera nada', () => {
      montarShell();
      T.iniciar();

      const sidebar = document.querySelector('[data-admin-sidebar]');
      const antes = sidebar.style.width;

      document.dispatchEvent(new window.MouseEvent('mousemove', { bubbles: true, cancelable: true, clientX: 900 }));

      expect(sidebar.style.width).toBe(antes);
    });

    it('não redimensiona em mobile, onde a barra é overlay', () => {
      larguraDaTela(390);
      montarShell();
      T.iniciar();

      const sidebar = document.querySelector('[data-admin-sidebar]');
      const antes = sidebar.style.width;

      document.querySelector('[data-admin-resize]').dispatchEvent(
        new window.MouseEvent('mousedown', { bubbles: true, cancelable: true, clientX: 260 })
      );
      document.dispatchEvent(new window.MouseEvent('mousemove', { bubbles: true, cancelable: true, clientX: 400 }));

      expect(sidebar.style.width).toBe(antes);
    });

    it('duplo clique devolve a largura de fábrica', () => {
      montarShell();
      T.iniciar();

      const sidebar = document.querySelector('[data-admin-sidebar]');
      Object.defineProperty(sidebar, 'offsetWidth', { value: 260, configurable: true });

      const handle = document.querySelector('[data-admin-resize]');
      handle.dispatchEvent(new window.MouseEvent('mousedown', { bubbles: true, cancelable: true, clientX: 260 }));
      document.dispatchEvent(new window.MouseEvent('mousemove', { bubbles: true, cancelable: true, clientX: 400 }));
      document.dispatchEvent(new window.MouseEvent('mouseup', { bubbles: true }));

      handle.dispatchEvent(new window.Event('dblclick', { bubbles: true }));

      expect(sidebar.style.width).toBe('260px');
      expect(window.localStorage.getItem('gestor-menu-width')).toBe('260');
    });
  });

  describe('filtro de módulos', () => {
    it('esconde o que não casa com o termo', () => {
      montarShell();
      T.iniciar();

      filtrar('pag');

      expect(itensVisiveis().map((el) => el.textContent.trim())).toEqual(['Páginas']);
    });

    it('ignora acento e caixa', () => {
      // Regressão do BATCH-103: `indexOf` cru falhava no segundo caractere acentuado.
      montarShell();
      T.iniciar();

      filtrar('PAGINAS');
      expect(itensVisiveis().map((el) => el.textContent.trim())).toEqual(['Páginas']);

      filtrar('usuarios');
      expect(itensVisiveis().map((el) => el.textContent.trim())).toEqual(['Usuários']);
    });

    it('esconde o grupo inteiro quando ele fica sem itens', () => {
      // Título de categoria sozinho na tela parece grupo vazio, não resultado filtrado.
      montarShell();
      T.iniciar();

      filtrar('usuarios');

      const grupos = Array.from(document.querySelectorAll('[data-menu-grupo]'));
      expect(grupos.filter((g) => !g.classList.contains('hidden')).length).toBe(1);
    });

    it('avisa quando nada é encontrado e some com o aviso ao limpar', () => {
      montarShell();
      T.iniciar();

      const vazio = document.querySelector('[data-menu-vazio]');

      filtrar('zzzzz');
      expect(vazio.classList.contains('hidden')).toBe(false);
      expect(itensVisiveis().length).toBe(0);

      filtrar('');
      expect(vazio.classList.contains('hidden')).toBe(true);
      expect(itensVisiveis().length).toBe(4);
    });

    it('Esc limpa o filtro', () => {
      montarShell();
      T.iniciar();

      filtrar('usuarios');
      const campo = document.getElementById('gestor-menu-filtro');
      campo.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

      expect(campo.value).toBe('');
      expect(itensVisiveis().length).toBe(4);
    });

    it('seta para baixo entra na lista e percorre só os visíveis', () => {
      montarShell();
      T.iniciar();

      filtrar('pa');
      const campo = document.getElementById('gestor-menu-filtro');
      campo.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));

      expect(document.activeElement.textContent.trim()).toBe('Páginas');
    });

    it('seta para cima no primeiro item devolve o foco ao campo', () => {
      montarShell();
      T.iniciar();

      const campo = document.getElementById('gestor-menu-filtro');
      campo.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
      document.activeElement.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'ArrowUp', bubbles: true }));

      expect(document.activeElement).toBe(campo);
    });

    it('seta para baixo no último item não cria ciclo', () => {
      montarShell();
      T.iniciar();

      filtrar('usuarios');
      const campo = document.getElementById('gestor-menu-filtro');
      campo.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));

      const ultimo = document.activeElement;
      ultimo.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));

      expect(document.activeElement).toBe(ultimo);
    });
  });

  describe('alternância de visibilidade dos botões abrir/fechar (req-125 F3)', () => {
    it('menu aberto esconde o botão abrir e mostra o botão fechar', () => {
      montarShell();
      T.iniciar();

      const btnAbrir = document.querySelector('[data-admin-abrir]');
      const btnFechar = document.querySelector('[data-admin-fechar]');

      expect(btnAbrir.classList.contains('hidden')).toBe(true);
      expect(btnFechar.classList.contains('hidden')).toBe(false);
    });

    // A classe `hidden` sozinha NÃO esconde estes botões: ambos são `inline-flex`, e no bundle do
    // layout `.inline-flex` é emitida depois de `.hidden` — mesma especificidade, mesma camada,
    // ganha a última. Quem realmente apaga o botão é o atributo booleano `hidden`, servido pelo
    // preflight como `display:none!important`. Se um dia a classe voltar a ser o único mecanismo, é
    // aqui que o teste avisa.
    it('esconde pelo atributo hidden, não só pela classe', () => {
      montarShell();
      T.iniciar();

      const btnAbrir = document.querySelector('[data-admin-abrir]');
      const btnFechar = document.querySelector('[data-admin-fechar]');

      expect(btnAbrir.hasAttribute('hidden')).toBe(true);
      expect(btnFechar.hasAttribute('hidden')).toBe(false);

      btnFechar.dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(btnAbrir.hasAttribute('hidden')).toBe(false);
      expect(btnFechar.hasAttribute('hidden')).toBe(true);
    });

    // O botão nasce com `lg:hidden` para não piscar ao lado do "fechar" na primeira pintura do
    // desktop. A utility mora numa media query emitida depois de `.inline-flex`, então ela venceria
    // o runtime: sem removê-la no boot, recolher o menu no desktop deixaria os DOIS botões sumidos.
    it('libera a utility lg:hidden do botão abrir no boot', () => {
      montarShell();
      document.querySelector('[data-admin-abrir]').classList.add('lg:hidden');

      T.iniciar();

      expect(document.querySelector('[data-admin-abrir]').classList.contains('lg:hidden')).toBe(false);
    });

    it('menu fechado mostra o botão abrir e esconde o botão fechar', () => {
      montarShell();
      T.iniciar();

      const btnAbrir = document.querySelector('[data-admin-abrir]');
      const btnFechar = document.querySelector('[data-admin-fechar]');

      btnFechar.dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(btnAbrir.classList.contains('hidden')).toBe(false);
      expect(btnFechar.classList.contains('hidden')).toBe(true);
    });

    it('no mobile nasce fechado com botão abrir visível e fechar escondido', () => {
      larguraDaTela(390);
      montarShell();
      T.iniciar();

      const btnAbrir = document.querySelector('[data-admin-abrir]');
      const btnFechar = document.querySelector('[data-admin-fechar]');

      expect(btnAbrir.classList.contains('hidden')).toBe(false);
      expect(btnFechar.classList.contains('hidden')).toBe(true);

      // Clicar em abrir alterna o estado
      btnAbrir.dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(btnAbrir.classList.contains('hidden')).toBe(true);
      expect(btnFechar.classList.contains('hidden')).toBe(false);
    });
  });

  describe('eliminação de warnings do Lucide (req-125 F4)', () => {
    it('remove data-lucide de elementos com valores compostos Fomantic antes de criar ícones', () => {
      document.body.innerHTML = `
        <div id="c2f-admin-shell">
          <aside data-admin-sidebar class="lg:translate-x-0">
            <i id="icon-valido" data-lucide="box" class="box icon"></i>
            <i id="icon-composto" data-lucide="comments outline" class="comments outline icon"></i>
            <i id="icon-vazio" data-lucide="" class="icon"></i>
          </aside>
          <div data-admin-conteudo></div>
        </div>
      `;

      let chamadaCreateIcons = false;
      window.lucide = {
        createIcons: () => {
          chamadaCreateIcons = true;
        },
      };

      T.iniciar();

      expect(chamadaCreateIcons).toBe(true);
      expect(document.getElementById('icon-valido').getAttribute('data-lucide')).toBe('box');
      expect(document.getElementById('icon-composto').hasAttribute('data-lucide')).toBe(false);
      expect(document.getElementById('icon-vazio').hasAttribute('data-lucide')).toBe(false);
    });
  });

  it('fora do layout Tailwind o runtime não faz nada', () => {
    // A página do layout legado tem `.menuComputerCont`, não `#c2f-admin-shell`.
    document.body.innerHTML = '<div class="menuComputerCont"></div>';
    expect(() => T.iniciar()).not.toThrow();
  });
});

