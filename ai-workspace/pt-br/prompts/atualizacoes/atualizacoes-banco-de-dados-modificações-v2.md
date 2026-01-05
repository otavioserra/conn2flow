# Prompt Interactive Programming - Atualizações Banco de Dados - Modificações V 2.0

## 🎯 Contexto Inicial
- FUNDAMENTAL: Analise o contexto anterior antes de seguir com as orientações abaixo que foi registrado no arquivo : `ai-workspace\prompts\atualizacoes\atualizacoes-banco-de-dados-modificações.md`.

## 📝 Orientações para o Agente

### Atualização apenas origem de dados atualizados
1. Eu criei uma tabela `manager_updates` para registrar metadados de execuções de atualização do gestor. O motivo é que a atualização dos dados do banco de dados está demorando muito, cerca de 14 segundos. Essa tabela irá servir para guardar histórico para podermos ver o que realmente precisa atualizar e em quais tabelas precisam de fato serem atualizadas. E não atualizar todas as tabelas como está sendo feito atualmente sem necessidade.
2. Dentro dessa tabela têm os seguintes campos: `db_checksum`, `backup_path`, `version`, `date`.
3. O campo `db_checksum` irá guardar o conjunto de todos os checksums dos arquivos de dados de cada tabela, que estão armazenadas na seguinte pasta: `gestor\db\data`.
4. Cada conjunto de dados dentro dessa pasta com o nome `TabelaData.json` terá o cálculo do seu checksum individualmente. O conjunto de todos os checksums será guardado como um único valor JSON. Seguindo o seguinte formato:
```json
{
    "TabelaData.json": "checksum1",
    "TabelaData2.json": "checksum2",
    ...
}
```
Por exemplo, para os dois tipo de dados: `PaginasData.json` e `LayoutsData.json`
```json
{
    ...
    "PaginasData.json": "c4ca4238a0b923820dcc509a6f75849b",
    "LayoutsData.json": "098f6bcd4621d373cade4e832627b4f6"
    ...
}
```
Esse JSON será guardado na tabela `manager_updates` no campo `db_checksum`.
5. O algoritmo precisa analisar esse campo antes de iniciar qualquer atualização. Caso não exista nenhuma atualização anterior, ele deve iniciar uma nova atualização completa com todos os dados. Caso exista um registro de uma atualização anterior, ele deve comparar os checksums e determinar quais tabelas precisam ser atualizadas.
6. O campo `backup_path` será utilizado para armazenar o caminho do diretório de backup associado à atualização atual.
7. O campo `version` irá armazenar a versão do gestor no momento da atualização.
8. Toda atualização deve ser registrada na tabela `manager_updates` com os devidos metadados.
9. O campo `date` irá armazenar a data e hora da execução da atualização.

### Atualização apenas de registros modificados.
1. Analisando o log do relatório final gerado depois de executar a atualização, pude ver que mesmo um registro não ter valor modificado, ele está sendo atualizado de qualquer jeito. Ou seja, não mudei nada nos arquivos `TabelaData.json`, mas todos os seus registros estão sendo atualizados. Precisamos alterar a lógica que verifique se realmente houve alteração nos dados antes de realizar a atualização no banco. Como você pode ver pelo relatório gerado. + é inclusão de registro e ~ é de atualização. Logo, fica claro que tah atualizando tudo sem necessidade:
```bash
otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
$ docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff"
📝 Relatório Final Atualização BD
══════════════════════════════════════════════════
📦 categorias => +0 ~41 =0
📦 componentes => +0 ~0 =84
📦 hosts_configuracoes => +0 ~2 =0
📦 layouts => +0 ~0 =14
📦 modulos => +0 ~74 =0
📦 modulos_grupos => +0 ~12 =0
📦 modulos_operacoes => +0 ~1 =0
📦 paginas => +0 ~5 =179
📦 plugins => +0 ~3 =0
📦 templates => +0 ~42 =0
📦 usuarios => +0 ~1 =0
📦 usuarios_perfis => +0 ~1 =0
📦 usuarios_perfis_modulos => +0 ~0 =18
📦 variaveis => +0 ~718 =590
Σ TOTAL => +0 ~900 =885

otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
```

### Executar o script para testes operações necessárias:
1. Sincronizar os dados no ambiente de testes: `bash docker/utils/sincroniza-gestor.sh checksum`
2. Executar o script no ambiente de testes: `docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff"`

### Atualizar o repositório
1. Caso tudo fique resolvido, vamos gerar a versão e as operações do GIT executando o script de commit: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`. Lembrando que vc precisa executar isso e ao mesmo tempo criar a mensagem detalhada.

## 🧭 Estrutura do código-fonte
```
...Demais Funções...

main():
    ... Demais Lógicas ...
    
    ... Demais Lógicas ...

main()
```

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [x] Analisar logs e identificar problema principal
- [x] Corrigir lógica de comparação para detectar mudanças reais
- [x] Implementar verificação mais rigorosa de registros modificados  
- [x] Implementar limpeza automática de espaços em dados JSON
- [ ] Otimizar performance das atualizações por checksum
- [ ] Testar script com --dry-run para verificar melhorias em todas as tabelas
- [ ] Executar testes finais sem --dry-run
- [ ] Commit das alterações

## ☑️ Processo Pós-Implementação
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções 1.0

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** dataAtual()
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow versao()