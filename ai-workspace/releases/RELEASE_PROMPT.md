



# RELEASE: Conn2Flow Instalador - Hotfix Página de Sucesso (Julho 2025)

## RESUMO DA ALTERAÇÃO

Correção no instalador para garantir que o update da página `instalacao-sucesso` seja feito pelo campo `id` (sem barra), evitando duplicidade e conflito com o seeder. Também foi removida a lógica de insert manual dessa página, pois o CID das migrações já garante sua existência.

## ARQUIVO MODIFICADO

- `gestor-instalador/src/Installer.php` (função createSuccessPage)

## INSTRUÇÕES PARA PUBLICAÇÃO

1. Gerar nova tag do instalador com o hotfix.
2. Zipar o diretório `gestor-instalador` para release.
3. Publicar a nova versão para testes/homologação.

**Versão sugerida:** 1.0.26
**Data:** Julho 2025
**Criticidade:** Alta (bloqueio de instalação)
**Compatibilidade:** Total

---

# RELEASE: Conn2Flow Gestor - Seeder Ajustado (Julho 2025)

## RESUMO DA ALTERAÇÃO

No seeder de páginas, o layout da página `instalacao-sucesso` foi alterado para usar o ID 23, garantindo consistência visual. Também foi removido o registro duplicado/inútil dessa página, já que o CID das migrações já garante sua existência, evitando conflitos e duplicidade no banco.

## ARQUIVO MODIFICADO

- `gestor/db/seeds/PaginasSeeder.php` (layout 23, remoção de registro duplicado)

## INSTRUÇÕES PARA PUBLICAÇÃO

1. Gerar nova tag do gestor com o ajuste do seeder.
2. Zipar o diretório `gestor` para release.
3. Publicar a nova versão para testes/homologação.

**Versão sugerida:** 1.8.7
**Data:** Julho 2025
**Criticidade:** Média (consistência visual e banco)
**Compatibilidade:** Total
