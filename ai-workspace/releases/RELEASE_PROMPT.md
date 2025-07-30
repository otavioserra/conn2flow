# RELEASE: Conn2Flow Gestor - Correções Técnicas (Julho 2025)

## RESUMO DAS ALTERAÇÕES

- Corrigido o caminho do arquivo `phinx.php`:
  - O arquivo foi movido da pasta `utilitarios` para a raiz do diretório `gestor`.
  - Todas as referências em `gestor-instalador/src/Installer.php` foram atualizadas para `$gestorPath . '/phinx.php'`.
  - Comentário e paths internos do próprio `gestor/phinx.php` corrigidos para refletir a nova estrutura.

## ARQUIVOS MODIFICADOS

- `gestor-instalador/src/Installer.php` (linhas ~239 e ~334)
- `gestor/phinx.php` (comentário e paths)

## INSTRUÇÕES PARA O AGENTE GIT

1. Gerar novo release zipado do gestor com as correções acima.
2. Publicar a nova versão.
3. Atualizar a documentação técnica se necessário.

**Versão:** 1.4.1
**Data:** Julho 2025
**Criticidade:** Correção técnica de referência de arquivo
**Compatibilidade:** Total
