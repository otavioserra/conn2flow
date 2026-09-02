# BATCH-126: Correções no Layout Administrativo Tailwind, Menu do Gestor, Histórico do Perfil e Identidade Visual Azul Conn2Flow no Core

Intake: [req-124.md](../../human-requests/archive/req-124.md)
Validação: [VALIDATION-CHECKLIST.md#batch-126](../../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation`

Este lote executa as correções de layout administrativo, expansão do menu colapsado, completude dos ícones do menu, visibilidade do botão sair, isolamento do histórico no perfil e padronização da paleta azul Conn2Flow nas páginas de autenticação do Core.

---

## Atividades Técnicas

1. **[x] Compensação de Topo do Editbar no `layout-administrativo-tailwind`:**
   - Adicionar suporte a offset/padding-top de ~30px quando `c2f-site-toolbar` estiver ativo.

2. **[x] Preenchimento de Ícones do Menu no Core:**
   - Mapear e atualizar os ícones Fomantic e Lucide para os módulos de IA, Prompts, Formulários, Atualização, `modulos`, `modulos_grupos` e `modulos_operacoes`.

3. **[x] Expansão do Conteúdo ao Recolher Menu Lateral (`data-admin-abrir`):**
   - Ajustar `admin-tailwind.js` e o layout administrativo Tailwind para alternar a classe de recuo do container principal (`#paginaCont` / `<main>`).

4. **[x] Ajuste de Scroll e Padding Inferior no Menu Lateral:**
   - Adicionar padding inferior na sidebar para garantir rolagem completa até o botão "Sair" e versão.

5. **[x] Correção da Tabela de `#historico#` no Perfil de Usuário:**
   - Restringir o bloco de histórico à aba apropriada e tratar o marcador `#historico#`.

6. **[x] Padronização da Paleta Azul Conn2Flow (`rgb(30, 45, 74)`, `rgb(27, 171, 198)`, `rgb(203, 203, 203)`):**
   - Atualizar botões, links, anéis de foco e indicadores de abas nas páginas públicas de autenticação e perfil do Core para os tons de azul do Conn2Flow.

7. **[x] Validação e Testes:**
   - Executar `npm run test` (Vitest), `composer test` (PHPUnit) e `php -l`.

---

## Resultado da Execução (2026-08-21)

### Achado que redirecionou a Frente 2

A req diagnosticava "módulos sem ícone cadastrado". O cadastro estava completo: o defeito era de
**vocabulário**. `gestor_pagina_menu_icone()` entrega a um menu Tailwind o conteúdo de
`modulos.icone_tailwind` (catálogo Lucide), mas o componente `menu-principal-sistema-tailwind`
desenhava `<i class="#icon# icon">` contra a folha de ícones do **Fomantic** que o layout carrega.
Cruzando os 33 módulos do menu com o `icon.min.css` real do Fomantic 2.9.4, **19** ficavam sem glifo
— os 8 relatados entre eles. Os que apareciam eram exatamente os nomes que existem por acaso nos dois
catálogos (`user`, `users`, `home`, `search`, `server`, `plug`, `images`, `list`, `shapes`,
`newspaper`, `wrench`).

A correção honra o desenho que o próprio `gestor.php` já documentava ("os layouts Tailwind desenham
`<i data-lucide="credit-card">`") sem alterar contrato nenhum do req-086, e segue o padrão que o
projeto irmão SnapPhoton já adota (Lucide UMD via CDN em `config-project.php`).

### Degradação graciosa preservada

O gate `banco_campo_existe('icone_tailwind','modulos')` existe para bancos que ainda não rodaram a
migração do req-086 — e nesse caso o resolvedor devolve o nome **Fomantic** legado. Emitir só
`data-lucide` teria transformado isso numa regressão: instalações não migradas perderiam ícones que
hoje aparecem. O item nasce então com os dois vocabulários no mesmo elemento:

```html
<i data-lucide="#icon#" class="#icon# icon"></i>
```

`createIcons()` devolve o `<i>` intacto quando o nome não está no catálogo (verificado no bundle:
`if(!na) return console.warn(...)`), e aí quem desenha é o Fomantic. Todas as regras do
`icon.min.css` são prefixadas com `i.icon` — nenhuma alcança o `<svg>` resultante quando o Lucide
converte. As duas camadas convivem sem interferência.

### Correções de causa-raiz nas demais frentes

| Frente | Sintoma | Causa real |
| --- | --- | --- |
| F1 | Editbar sobre o cabeçalho | `margin-top` no `<html>` não alcança `fixed`/`sticky` |
| F3 | Faixa vazia ao recolher o menu | `marginLeft = ''` devolve o recuo à utility `lg:ml-[260px]`, não o zera |
| F4 | Botão "Sair" fora do alcance | `h-full` + `min-height:auto` impedem o item flex de encolher; o rodapé sai do viewport |
| F5 | `#historico#` cru na tela | A troca casava `<td>#historico#</td>`, marcação que só existe no componente Fomantic |

### Fora do escopo declarado, feito por dependência

- `perfil-usuario.js` entrou em `tailwind_sources`: as classes que o runtime aplica não eram
  escaneadas. `bg-emerald-500` (faixa "Forte" da força de senha) estava ausente do bundle.
- Botão primário dos componentes globais `interface-formulario-edicao-tailwind` e
  `interface-alerta-modal-tailwind` migrado para azul — é o botão de gravar do próprio perfil, mas a
  troca alcança todos os módulos administrativos em Tailwind.

### Validação

`php -l` OK · `composer test` **581/581** · `npm run test` **331/331** · compilador de recursos sem
erros. Detalhamento e pendências de homologação em
[VALIDATION-CHECKLIST.md](../../validation/VALIDATION-CHECKLIST.md#batch-126---layout-administrativo-tailwind-ícones-do-menu-histórico-do-perfil-e-paleta-azul-conn2flow-req-124-2026-08-21).
