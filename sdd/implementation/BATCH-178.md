# BATCH-178: Preservação de Query Strings em Redirecionamentos 301 e Contenção de Blocos-Fragmento no Widget de Formulários

Execução da [req-173](../human-requests/req-173.md).

---

## Atividades e Checklist

### 1. [ ] Roteamento: Preservação de Query String em `paginas_301`
- [ ] Em `gestor/gestor.php` (`gestor_roteador_301_ou_404()`):
  - Passar `'querystring' => true` na chamada de `gestor_roteador_erro()` para redirecionamentos 301.
- [ ] Em `gestor/bibliotecas/gestor.php` (`gestor_redirecionar()`):
  - Tratar a concatenação de `$queryString` quando `$local` já contiver `?`, usando `&` para evitar duplicação de `?`.
  - Garantir integridade de status HTTP (301) e codificação de URL.

### 2. [ ] Widget Forms: Expurgamento de Blocos-Fragmento e Sanitização de Marcadores
- [ ] Em `gestor/modulos/forms/forms.widget.php` (`forms_widget_render_inline()` ou helper):
  - Expurgar os blocos-fragmento do HTML retornado:
    - `<!-- option-choice < -->...<!-- option-choice > -->`
    - `<!-- option-select < -->...<!-- option-select > -->`
    - `<!-- password-toggle < -->...<!-- password-toggle > -->`
  - Aplicar limpeza de salvaguarda contra marcadores `@[[option#*]]@` e `@[[password#*]]@` residuais.
  - Assegurar que selects, radios, checkboxes e botões de alternância de senha funcionem normalmente.

### 3. [ ] Validação e Testes
- [ ] Adicionar testes unitários no PHPUnit cobrindo:
  - Redirecionamento 301 com query string simples e composta (UTMs, múltiplos parâmetros).
  - Redirecionamento 301 sem query string (sem `?` órfão).
  - Renderização do widget `forms` com múltiplos tipos de campo verificando ausência de marcadores crus e integridade do markup funcional gerado.
- [ ] Executar suítes locais:
  - `composer test` (PHPUnit completo).
  - `npx vitest run` (Vitest completo).
  - `git diff --check`.

---

## Critérios de Aceite e Validação

1. **Query String em 301**: Requisições para caminhos com 301 preservam integralmente os parâmetros de busca no destino.
2. **HTML Limpo no Widget Forms**: Nenhum marcador `<!-- option-` ou `@[[...]]@` vaza na saída pública renderizada.
3. **Compatibilidade Total**: Nenhuma quebra em formulários existentes ou em rotas com 301 ativo.
4. **Suíte 100% Aprovada**: PHPUnit e Vitest sem falhas ou regressões.

---

## Evidências de Execução

(A preencher pelo executor ao concluir)

## Estado

`ready-for-intake`
