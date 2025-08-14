# Prompt Interactive Programming - Alterar Migrações Criar Campos

## 🤖 Agente de IA - Responsabilidades
- **Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação.
- **GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto.
- **Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada.

## 🎯 Contexto Inicial
- Definições de toda a infraestrutura de programação que serão usados pelo agente de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- O agente usará este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar este arquivo qualquer parte a qualquer momento. O usuário ficará atento a este arquivo e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto o agente de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelo agente e/ou usuário comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🧪 Ambiente de Testes
- Os testes serão feitos localmente no ambiente de desenvolvimento.

## 🗃️ Repositório GIT
- Existe um script feito com todas as operações necessárias internas para gerenciar o repositório: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`
- Dentro desse script é feito o versionamento automático do projeto, commit e push. Portanto, não faça os comandos manualmente. Apenas execute o script quando for alterar o repositório.

## ⚙️ Configurações da Implementação
- Caminho base: $base = `ai-workspace\scripts\arquitetura`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `alterar-migracoes-criar-campos.php`.
- Caminho da pasta de backups caso necessário: $backupPath = `backups\arquitetura`.
- Caminho da pasta de logs: $logsPath = `logs\arquitetura`.
- O código fonte deverá **ser bem comentado (padrão DocBlock), seguir os padrões de design definidos e ser modular.** Todas as orientações deverão constar nos comentários do código.

## 📖 Bibliotecas
- Geração de logs: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Pode alterar se necessário.
- Geração de migrações: Phinx. Já completamente configurada no repositório: `gestor\phinx.php`

## 📝 Orientações para o Agente
1. Vamos criar novas migrações para alterar campos do banco de dados. A pasta com todas as migrações é a seguinte: `gestor\db\migrations`.
2. Vamos alterar os campos das tabelas: `paginas`, `layouts` e `componentes`. Vamos incluir os seguintes novos campos após o campo `user_modified`: `system_updated`, `html_updated` e `css_updated`. Use o seguinte tipo de dados:
```php
    ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
    ->addColumn('html_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
    ->addColumn('css_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
```
3. Vamos alterar os campos da tabela: `variaveis`. Vamos incluir os seguintes novos campos após o campo `descricao`: `user_modified`, `system_updated` e `value_updated`. Use o seguinte tipo de dados:

```php
    ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
    ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
    ->addColumn('value_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
```
4. Executar o script gerado e verificar se há erros nos arquivos de migrações. IMPORTANTE: Não executar as migrações, farei isso em outro contexto.
5. Caso tudo fique resolvido, vamos gerar a versão e as operações do GIT executando o script de commit: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## 🧭 Estrutura do código-fonte
```
gerar_migracoes():
    > Lógica para gerar as migrações

gerar_relatorio():
    > Lógica para gerar o relatório

main():
    gerar_migracoes()
    gerar_relatorio()

main()
```

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [x] Criar migração adicionar campos paginas
- [x] Criar migração adicionar campos layouts
- [x] Criar migração adicionar campos componentes
- [x] Criar migração adicionar campos variaveis
- [x] Validar sintaxe/phinx (não executar up) 
- [ ] Gerar commit e versão

## ☑️ Processo Pós-Implementação
- [x] Executar o script gerado para ver se funciona corretamente.
- [ ] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções 1.0

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** 14/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.10.14