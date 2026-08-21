# BATCH-128: Mapeamento de Ícones Tailwind/Lucide para os Módulos Restantes do `conn2flow-site`

Intake: [req-126.md](../human-requests/req-126.md)
Validação: [VALIDATION-CHECKLIST.md#batch-128](../validation/VALIDATION-CHECKLIST.md)
Status: `ready-for-intake`

Este lote conclui o mapeamento de ícones Lucide para os 6 módulos restantes do `conn2flow-site` (`pro-manager`, `pedidos`, `produtos`, `checkout`, `host-manager`, `host-user-manager`).

---

## Atividades Técnicas

1. **[ ] Atualização do `ModulosData.json` do `conn2flow-site`:**
   - Adicionar `icone_tailwind` para os 6 módulos em `pt-br` e `en`.

2. **[ ] Atualização dos descritores `.json` de módulos em `conn2flow-site/gestor/modulos/`:**
   - Incluir `icone_tailwind` nos respectivos arquivos de configuração dos módulos.

3. **[ ] Atualização da migração no Core (`conn2flow`):**
   - Garantir que a migração Phinx inclua os 6 módulos com `icone_tailwind`.

4. **[ ] Testes e Validação:**
   - Executar `composer test` e `npm run test`.
