# Prompt Interactive Programming - Criar Lançamento - v2.7.6

## 📝 Orientações para o Agente
1. Analise a documentação para você ter o contexto geral do sistema atualmente: `ai-workspace\docs`.
2. Busque por todos os últimos commits no repositório para entender as mudanças recentes para criar o texto principal deste lançamento, bem como seu histórico de mudanças. As versões antes do último lançamento são desde v2.7.0 até a versão atual que será a v2.7.6.
3. Atualize o arquivo principal informativo do projeto para ver a necessidade de atualizar o mesmo: EN - `README.md`, PT-BR - `README.pt-br.md`.
4. Atualize os arquivos principais de changelog do projeto: EN - `CHANGELOG.md` e PT-BR - `CHANGELOG.pt-br.md`, e histórico mais detalhado: EN - `ai-workspace\en\docs\CONN2FLOW-CHANGELOG-HISTORY.md` e PT-BR - `ai-workspace\pt-br\docs\CONN2FLOW-ATUALIZACOES-SISTEMA.md`.
5. Atualize as informações do campo `body` do arquivo de lançamento do GitHub Workflow: `.github\workflows\release-gestor.yml`. Analise tudo como está e mantenha o padrão de formatação já existente, adicionando as novas informações do lançamento e removendo as antigas.
6. Crie uma mensagem de tag resumida e uma de commit detalhada para o lançamento para incluir na próxima etapa.
7. Use o script pronto para fazer as operações necessárias no repositório GIT: `ai-workspace\pt-br\scripts\releases\release.sh` usando o seguinte exemplo: `bash ./ai-workspace/pt-br/scripts/releases/release.sh minor "Resumo para a Tag" "Mensagem detalhada para o Commit"`.
8. Caso tenha alguma dúvida ou sugestão, pode executar as tarefas acima. E no final inclua suas considerações logo na próxima sessão abaixo. E numa próxima interação, podemos discutir as dúvidas e sugestões que você tiver. O importante é seguir o passo a passo acima para garantir um lançamento organizado e informativo para a comunidade.

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [ ] Analisar documentação em ai-workspace/docs para contexto geral do sistema
- [ ] Buscar commits desde v2.7.0 até hoje para entender mudanças recentes
- [ ] Atualizar README.md e README-PT-BR.md com informações da v2.7.6
- [ ] Atualizar CHANGELOG.md e CHANGELOG-PT-BR.md com entrada da v2.7.6
- [ ] Atualizar CONN2FLOW-CHANGELOG-HISTORY.md e CONN2FLOW-ATUALIZACOES-SISTEMA.md com histórico detalhado
- [ ] Atualizar campo body do .github/workflows/release-gestor.yml
- [ ] Criar mensagens de tag e commit para o lançamento
- [ ] Executar script de release com mensagens criadas
- [ ] **RELEASE v2.7.6 CONCLUÍDO COM SUCESSO!** 🎉

---
**Data:** 19/02/2026
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v2.7.6