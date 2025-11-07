# Prompt Interactive Programming - Criar Lançamento - v2.4.0

## 📝 Orientações para o Agente
1. Analise a documentação para você ter o contexto geral do sistema atualmente: `ai-workspace\docs`.
2. Busque por todos os últimos commits no repositório para entender as mudanças recentes para criar o texto principal deste lançamento, bem como seu histórico de mudanças. As versões antes do último lançamento são desde v2.3.1 até a versão atual que será a v2.4.0.
3. Atualize o arquivo principal informativo do projeto para ver a necessidade de atualizar o mesmo: EN - `README.md`, PT-BR - `README.pt-br.md`.
4. Atualize os arquivos principais de changelog do projeto: Padrão - `CHANGELOG.md` e histórico mais detalhado - `ai-workspace\docs\CONN2FLOW-CHANGELOG-HISTORY.md`.
5. Atualize as informações do campo `body` do arquivo de lançamento do GitHub Workflow: `.github\workflows\release-gestor.yml`. Manter o padrão de formatação já existente e adicione as novas informações do lançamento.
6. Crie uma mensagem de tag resumida e uma de commit detalhada para o lançamento para incluir na próxima etapa.
7. Use o script pronto para fazer as operações necessárias no repositório GIT: `ai-workspace\scripts\releases\release.sh` usando o seguinte exemplo: `bash ./ai-workspace/scripts/releases/release.sh minor "Resumo para a Tag" "Mensagem detalhada para o Commit"`.
8. Caso não tenha dúvidas ou sugestões, pode executar as tarefas acima. Senão inclua suas considerações logo na próxima sessão abaixo.

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [x] Analisar documentação em ai-workspace/docs para contexto geral do sistema
- [x] Buscar commits desde v2.3.0 até hoje para entender mudanças
- [x] Atualizar README.md e README-PT-BR.md com informações da v2.4.0
- [x] Atualizar CHANGELOG.md com entrada da v2.4.0
- [x] Atualizar CONN2FLOW-CHANGELOG-HISTORY.md com histórico detalhado
- [x] Atualizar campo body do .github/workflows/release-gestor.yml
- [x] Criar mensagens de tag e commit para o lançamento
- [x] Executar script de release com mensagens criadas
- [x] **RELEASE v2.4.0 CONCLUÍDO COM SUCESSO!** 🎉

---
**Data:** 06/11/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v2.4.0