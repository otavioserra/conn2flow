# BATCH-128: Mapeamento de Ícones do `conn2flow-site`, Auto-Recolhimento do Menu no Resize, Ícone do Botão Sair e URL de Retorno na Área Restrita

Intake: [req-126.md](../human-requests/req-126.md)
Validação: [VALIDATION-CHECKLIST.md#batch-128](../validation/VALIDATION-CHECKLIST.md)
Status: `ready-for-intake`

Este lote conclui o mapeamento de ícones Lucide dos 6 módulos do `conn2flow-site`, implementa o fechamento responsivo automático no resize da janela, corrige o ícone de logout e torna dinâmica a URL de retorno na Área Restrita.

---

## Atividades Técnicas

1. **[ ] Auto-Recolhimento no Resize (`admin-tailwind.js`):**
   - No listener de `resize`, fechar o menu ao transicionar para $\le 1024\text{px}$ e abrir ao transicionar para desktop quando não fechado por preferência.

2. **[ ] Ícone do Botão "Sair" (`gestor.php`):**
   - Emitir `data-lucide="log-out"` em `#icon-lucide#` na montagem do botão Sair para menus Tailwind.

3. **[ ] URL de Retorno Dinâmica na Área Restrita (`perfil-usuario.php`):**
   - Interpolar `#url-voltar#` com a URL do `$redirect` (ou fallback `/perfil-usuario/`) em vez da raiz `/`.

4. **[ ] Mapeamento dos 6 Módulos do `conn2flow-site`:**
   - Preencher `icone_tailwind` em `ModulosData.json`, nos JSONs de módulos e na migração Phinx.

5. **[ ] Testes e Validação:**
   - Executar `composer test` e `npm run test`.
