# BATCH-127: Correção de Reload em CSRF, Mapeamento de Ícones de Projetos, Alternância de Botões de Menu e Saneamento do Lucide

Intake: [req-125.md](../../human-requests/archive/req-125.md)
Validação: [VALIDATION-CHECKLIST.md#batch-127](../../validation/VALIDATION-CHECKLIST.md)
Status: `implemented-pending-homologation`

Este lote corrige o laço de sessão expirada no login, mapeia os ícones dos módulos servidos por projetos derivados, sincroniza a visibilidade dos botões abrir/fechar menu e elimina os warnings do Lucide no console.

---

## Atividades Técnicas

1. **[x] Reload limpo em erro de CSRF (`gestor_csrf_resposta_invalida`)**
   - `gestor_csrf_rotas_identidade()` e `gestor_csrf_destino_recarregamento()` em `gestor/bibliotecas/gestor.php`.
   - O caminho da requisição é a fonte PRIMÁRIA (existe mesmo sem referer); o referer é a secundária.
   - Rota de identidade → `location.replace()` para a URL de login. Fora delas → `history.back()`, preservado.
   - `Cache-Control: no-store` na própria tela de erro; destino escrito como `data-c2f-destino`, não interpolado no JS.

2. **[x] Ícones dos módulos servidos por projeto**
   - Pares gravados no `ModulosData.json` do `conn2flow-site`, com os ids REAIS (`3d-catalog`, `social-connections`, `publisher-social-media`, `modulos-grupos-distribuido`…).
   - Removidos do núcleo os 18 registros (9 módulos × 2 idiomas) com ids em português que não existem em lugar nenhum.
   - Migração `20260821100000_alter_modulos_update_icones_projetos` reescrita: ids reais + apelidos, bind de parâmetros, guarda de `hasColumn`.
   - Nomes conferidos contra os catálogos reais (Fomantic 2.9.4 e Lucide 0.544.0), nunca contra memória.

3. **[x] Alternância de visibilidade dos botões de abrir/fechar**
   - `sincronizarBotoes()` no `admin-tailwind.js`, chamada por `abrir()`/`fechar()` — cobre também o estado inicial.
   - O mecanismo é o atributo booleano `hidden`, não só a classe: `.inline-flex` é emitida DEPOIS de `.hidden` no bundle e venceria a classe.
   - O botão "abrir" nasce com `lg:hidden` e o runtime remove a utility no boot, como já se faz com `lg:translate-x-0` na barra lateral.

4. **[x] Saneamento de `data-lucide` e eliminação de warnings**
   - `gestor_pagina_menu_icone_lucide_valido()` / `_atributo()` montam o ATRIBUTO INTEIRO — `data-lucide=""` reclamaria igual, então ele precisa não ser emitido.
   - Template do `menu-principal-sistema-tailwind` passou a `#icon-lucide#` / `#icon-2-lucide#` (versão `1.4` → `1.5`, pt-br e en).
   - `sanearIcones()` no runtime cobre marcação de outras origens (componente sobrescrito por projeto, widget, módulo distribuído).

5. **[x] Testes e validação**
   - `composer test` **630/630**, `npm run test` **337/337**, `php -l` e `node --check` verdes, `c2f resources:sync` com 2652 recursos e 0 erros.
   - Testes novos: `tests/Unit/PHP/CsrfReloadIconesMenuTest.php` (43 casos, comportamentais) e 2 casos em `tests/Unit/JS/admin-tailwind.test.js`.

---

## Decisões de execução

- **As funções puras saíram do bootstrap para a biblioteca.** `gestor/gestor.php` termina em `gestor_start()` e não pode ser incluído por um caso de teste; enquanto as funções morassem lá, o único teste possível era procurar o nome delas no arquivo — o que passa mesmo quando o corpo não faz o que o nome promete. Em `gestor/bibliotecas/gestor.php` (carregada pelo `config.php`, primeira linha do bootstrap) elas são exercitadas de verdade.
- **O cadastro do ícone vive no projeto; a migração vive no núcleo.** São papéis diferentes: o `*Data.json` é o que instala e o que o deploy de recursos leva; a migração é o que alcança um banco onde a linha já existe. Um `UPDATE` sem correspondência é operação bem-sucedida de zero linhas, então a migração pode viver no núcleo sem sujar nada.
- **`icone2_tailwind` foi deixado nulo de propósito** nos módulos do projeto que têm `icone2` Fomantic. O `icone2` guarda modificador de posicionamento (`bottom right corner share`), que não existe no Lucide; preenchê-lo abriria a célula de dois ícones para desenhar um `<i>` sem alvo. Mesma regra do BATCH-126.
