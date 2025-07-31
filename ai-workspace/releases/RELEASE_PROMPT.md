


# RELEASE: Conn2Flow Instalador - Hotfix Página de Sucesso (Julho 2025)

## RESUMO DA ALTERAÇÃO

Correção pontual no instalador para garantir que o HTML e CSS da página `instalacao-sucesso` sejam sempre sobrescritos pelo instalador, resolvendo o conflito com o conteúdo antigo do seeder.

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

# RELEASE: Conn2Flow Gestor

## RESUMO DA ALTERAÇÃO

Nenhuma alteração realizada no gestor nesta release. Todas as correções foram aplicadas exclusivamente ao instalador (`gestor-instalador`).
