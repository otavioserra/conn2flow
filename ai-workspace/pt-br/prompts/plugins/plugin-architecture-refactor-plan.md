- [x] **Análise Arquitetural Completa**
  - [x] Ler e analisar `atualizacao-plugin.php` (orquestrador)
  - [x] Ler e analisar `plugins-installer.php` (instalador com lógica duplicada)
  - [x] Ler e analisar `atualizacoes-banco-de-dados.php` (sistema robusto de BD)
  - [x] Identificar duplicação de lógica de banco de dados
  - [x] Confirmar que `atualizacoes-banco-de-dados.php` tem suporte completo a chaves naturais

- [x] **Refatoração do plugins-installer.php**
  - [x] Remover funções duplicadas de upsert (`plugin_upsert_*`)
  - [x] Substituir chamadas de upsert por delegação para `atualizacoes-banco-de-dados.php`
  - [x] Manter apenas lógica de processamento de arquivos e migrações
  - [x] Criar função wrapper para chamar o sistema de banco de dados
  - [x] Modificar `plugin_sync_datajson_multi` para usar delegação

- [x] **Integração com atualizacoes-banco-de-dados.php**
  - [x] Modificar `plugin_sync_datajson_multi` para delegar operações de BD
  - [x] Criar interface de comunicação entre os sistemas
  - [x] Garantir que checksums e logs sejam preservados
  - [x] **CORREÇÃO**: Função `plugin_delegate_database_operations` estava ausente - adicionada e testada com sucesso

- [ ] **Testes e Validação**
  - [x] Testar instalação de plugin com chave natural (`usuarios_perfis_modulos`)
  - [x] Verificar que operações de BD funcionam corretamente
  - [x] Validar logs e relatórios de instalação
  - [x] Testar cenários de atualização vs nova instalação

- [x] **Documentação e Cleanup**
  - [x] Atualizar comentários explicando a nova arquitetura
  - [x] Remover código obsoleto e duplicado
  - [x] Documentar interface entre os sistemas

## 🔧 Problemas Identificados e Corrigidos

### ❌ Problema: Função `plugin_delegate_database_operations` Ausente
- **Sintomas**: Processo travava com erro fatal "Call to undefined function"
- **Causa**: Função foi criada mas não foi salva corretamente no arquivo
- **Solução**: Adicionada a função completa com delegação para o sistema robusto
- **Resultado**: Instalação agora funciona corretamente

### ✅ Verificação Final
- **Teste bem-sucedido**: Plugin instalado com código 0 (OK)
- **Delegação funcionando**: Sistema robusto de BD foi chamado corretamente
- **Logs preservados**: Toda a rastreabilidade mantida
- **Tabelas processadas**: componentes, layouts, módulos, páginas, usuários_perfis_modulos, variáveis</content>
<parameter name="filePath">c:\Users\otavi\OneDrive\Documentos\GIT\conn2flow\ai-workspace\docs\plugin-architecture-refactor-plan.md
