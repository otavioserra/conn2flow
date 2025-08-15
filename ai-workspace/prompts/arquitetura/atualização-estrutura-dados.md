# Prompt Interactive Programming - Atualização Estrutura Dados

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
- Existe uma infraestrutura de testes prontas e funcional. As configurações do ambiente estão no arquivo `docker\dados\docker-compose.yml`
- O ambiente de testes está na pasta local `docker\dados\sites\localhost\conn2flow-gestor`, o mesmo está na pasta do ambiente de testes: `/var/www/sites/localhost/conn2flow-gestor/`. Que é executado pelo gestor via navegador assim: `http://localhost/instalador/` . O mesmo está na pasta: `docker\dados\sites\localhost\public_html\instalador`
- Para atualizar o ambiente e refletir as mudanças do repositório, segue o arquivo para sincronização: `docker\utils\sincroniza-gestor.sh checksum`
- Todos os comandos para executar no ambiente de testes estão no arquivo: `docker\utils\comandos-docker.md`
- Se precisar executar o PHP lá, exemplo: `docker exec conn2flow-app bash -c "php -v"`

## 🗃️ Repositório GIT
- Existe um script feito com todas as operações necessárias internas para gerenciar o repositório: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`
- Dentro desse script é feito o versionamento automático do projeto, commit e push. Portanto, não faça os comandos manualmente. Apenas execute o script quando for alterar o repositório.

## ⚙️ Configurações da Implementação
- Caminho base: $base = `gestor/controladores/agents/arquitetura`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `atualização-estrutura-dados.php`.
- Caminho da pasta de backups caso necessário: $backupPath = `backups\arquitetura`.
- Caminho da pasta de logs: $logsPath = `gestor\logs\arquitetura`.
- Caminho da pasta de linguagens: $linguagensPath = `gestor\controladores\agents\arquitetura\lang`.
- Linguagens suportadas: $linguagensSuportadas = [`pt-br`, `en`].
- Linguagens de dicionário serão armazenadas em arquivo .JSON.
- Todos os textos de informação/logs deverão ter multilinguas. Escapados usando função helper `_()`;
- O código fonte deverá **ser bem comentado (padrão DocBlock), seguir os padrões de design definidos e ser modular.** Todas as orientações deverão constar nos comentários do código.

## 📖 Bibliotecas

## 📝 Orientações para o Agente

## 🧭 Estrutura do código-fonte
```
main():
    // Lógica principal do script
    

main()
```

## 🤔 Dúvidas e 📝 Sugestões

---
## 🚀 PLANEJAMENTO E REGRAS DE TRABALHO

### Como será o fluxo colaborativo:
- Todas as ideias, decisões, requisitos e alterações serão registradas neste arquivo antes de qualquer implementação.
- Cada tarefa será documentada com: objetivo, contexto, requisitos, plano de ação e checklist.
- O agente de IA só fará alterações no código após o plano ser aprovado/documentado aqui.
- Todo histórico de decisões e mudanças ficará registrado para rastreabilidade.

### Estrutura de cada tarefa:
1. **Contexto**: Descrição do problema ou objetivo.
2. **Requisitos**: Pontos que devem ser atendidos.
3. **Plano de ação**: Passos para resolver.
4. **Checklist**: Itens para marcar progresso.
5. **Decisões**: Justificativas e alternativas consideradas.

---
## 📝 Tarefa 1: Inicialização do Processo Colaborativo

### Contexto
Iniciar o registro de todas as decisões e alterações do projeto de atualização da estrutura de dados, centralizando o fluxo neste arquivo markdown.

### Requisitos
- Definir regras de trabalho colaborativo.
- Criar template de tarefas para futuras interações.
- Garantir que todo novo plano/alteração seja documentado antes de ser implementado.

### Plano de ação
1. Adicionar seção de planejamento e regras ao arquivo.
2. Criar template de tarefa para uso futuro.
3. Registrar esta primeira tarefa como exemplo.

### Checklist
- [x] Seção de planejamento criada
- [x] Regras de trabalho colaborativo definidas
- [x] Template de tarefa adicionado
- [x] Tarefa 1 registrada

---

## ✅ Progresso da Implementação
- [] item do progresso

## ☑️ Processo Pós-Implementação
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções 1.0

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

---
## 📝 Tarefa 2: Padronização de Identificadores nas Migrações e Tabela Páginas

### Contexto
Necessário padronizar o identificador de usuários nas migrações para valor default igual a 1. Na tabela `paginas`, remover o campo `id_layouts` (integer) e adicionar o campo `layout_id` (string), referenciando o campo `id` da tabela `layouts`.

### Requisitos
- Todas as migrações devem definir `id_usuarios` com valor padrão 1.
- Na tabela `paginas`, remover `id_layouts` (integer).
- Adicionar `layout_id` (string) na tabela `paginas`, referenciando o campo `id` da tabela `layouts`.
- Garantir que o relacionamento entre páginas e layouts seja feito pelo identificador alfanumérico.

### Plano de ação
1. Revisar todas as migrações e ajustar o campo `id_usuarios` para default 1.
2. Editar a migração da tabela `paginas`:
    - Remover o campo `id_layouts` (integer).
    - Adicionar o campo `layout_id` (string, limit 255, not null), referenciando o campo `id` da tabela `layouts`.
3. Validar se o relacionamento está correto e compatível com o novo padrão.
4. Testar as migrações em ambiente de desenvolvimento.
5. Documentar decisões e eventuais problemas encontrados.

### Checklist
- [x] Migração `paginas` ajustada (`id_usuarios` default 1, substituição de `id_layouts` por `layout_id` string)
- [x] Migração `layouts` ajustada (`id_usuarios` default 1)
- [x] Migração `componentes` ajustada (`id_usuarios` default 1)
- [x] Migração `arquivos` ajustada (`id_usuarios` default 1)
- [x] Migração `hosts_variaveis` ajustada (`id_usuarios` default 1)
- [x] Demais migrações com `id_usuarios` revisadas e ajustadas
- [ ] Relacionamento entre páginas e layouts validado (execução real de migrações)
- [ ] Testes executados e aprovados
- [ ] Decisões e problemas documentados

### Decisões
- Optou-se por utilizar identificador alfanumérico para layouts, visando maior flexibilidade e compatibilidade futura.
- O campo `id_usuarios` padrão 1 facilita o controle de registros globais e evita inconsistências de ownership.

---
**Data:** 15/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.10.16