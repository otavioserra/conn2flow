# Prompt Interactive Programming - Alterar Migrações - Criar Campos V 2.0

## 🎯 Contexto Inicial
- FUNDAMENTAL: Analise o contexto anterior antes de seguir com as orientações abaixo que foi registrado no arquivo : `ai-workspace\prompts\arquitetura\alterar-migrações-criar-campos.md`.

## 📝 Orientações para o Agente
1. Criar uma nova migração para criar uma nova tabela `manager_updates` com os seguintes campos:
```php
$table = $this->table('manager_updates', ['id' => 'id_manager_updates']);
    $table->addColumn('db_checksum', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
    $table->addColumn('backup_path', 'text', ['null' => true, 'default' => null]);
    $table->addColumn('version', 'text', ['null' => true, 'default' => null]);
    $table->addColumn('date', 'timestamp', ['null' => true, 'default' => null]);
    $table->create();
```

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
**Data:** dataAtual()
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow versao()