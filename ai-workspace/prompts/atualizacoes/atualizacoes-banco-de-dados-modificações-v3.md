# Prompt Interactive Programming - Atualizações Banco de Dados - Modificações V 3.0

## 🎯 Contexto Inicial
- FUNDAMENTAL: Analise o contexto anterior antes de seguir com as orientações abaixo que foi registrado nos arquivos : `ai-workspace\prompts\atualizacoes\atualizacoes-banco-de-dados-modificações.md` e `ai-workspace\prompts\atualizacoes\atualizacoes-banco-de-dados-modificações-v2.md`.

## 📝 Orientações para o Agente
1. Eu vi que muitos registros que existem nos arquivos .json não estavam sendo colocados corretamente nas tabelas do banco de dados. Dái procurei manualmente um registro e vi que ele não existia que é o registro das `variaveis` de PK: 540 no arquivo `gestor\db\data\VariaveisData.json`. Daí vi que tem 2 registros com o mesmo `id`==`add-button-label`:
```json
    ...
    {
        "id_variaveis": "283",
        "linguagem_codigo": "pt-br",
        "modulo": "admin-arquivos",
        "id": "add-button-label",
        "valor": "Adicionar Arquivos",
        "tipo": "string",
        "grupo": null,
        "descricao": null
    },
    ...
    {
        "id_variaveis": "540",
        "linguagem_codigo": "pt-br",
        "modulo": "arquivos",
        "id": "add-button-label",
        "valor": "Adicionar Arquivos",
        "tipo": "string",
        "grupo": null,
        "descricao": null
    },
```
2. Daí então fiz uma atualização no script para fazer uma busca no registro em si sendo computado no momento de sincronização da tabela na função `sincronizarTabela`. E incluí o seguinte teste:
```php
    ...
    // Linha #165
    foreach ($registros as $row) {
        if($row[$pk] == "540" && $tabela === 'variaveis') {
            echo 'Variáveis PK 540: ' . print_r($row, true);
            $find = true;
        }

        $pkVal = $row[$pk] ?? null; if ($pkVal === null) continue; // ignora sem PK
        $sel->execute([':pk' => $pkVal]);
        $exist = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$exist && $hasAlt) {
            $selAlt->execute([':id'=>$row['id'], ':language'=>$row['language']]);
            $exist = $selAlt->fetch(PDO::FETCH_ASSOC);
            if ($exist) { $pkVal = $exist[$pk]; }
        }
        if (!$exist && $selIdOnly) {
            $selIdOnly->execute([':id'=>$row['id']]);
            $exist = $selIdOnly->fetch(PDO::FETCH_ASSOC);
            if ($exist) { $pkVal = $exist[$pk]; log_disco("ALT_ID_MATCH $tabela id=" . encLog($row['id']) . " -> pk=".$pkVal, $GLOBALS['LOG_FILE']); }
        }

        if(isset($find) && $find) {
            echo ($exist ? 'Valor do Exist: ' . print_r($exist, true) : 'Vazio') . PHP_EOL;
            exit;
        }
    ...
```
3. Sincronizei o script no ambiente de testes: `bash docker/utils/sincroniza-gestor.sh checksum`
4. Executei o script no ambiente de testes e deu esse erro:
```bash
otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
$ docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --debug --log-diff --tables=variaveis --dry-run"
Variáveis PK: 540Array
(
    [id_variaveis] => 540
    [linguagem_codigo] => pt-br
    [modulo] => arquivos
    [id] => add-button-label
    [valor] => Adicionar Arquivos
    [tipo] => string
    [grupo] => 
    [descricao] => 
    [user_modified] => 0
    [system_updated] => 0
    [value_updated] => 
)
Valor do Exist: Array
(
    [id_variaveis] => 283
    [linguagem_codigo] => pt-br
    [modulo] => arquivos
    [id] => add-button-label
    [valor] => Adicionar Arquivos
    [tipo] => string
    [grupo] => 
    [descricao] => 
    [user_modified] => 0
    [system_updated] => 0
    [value_updated] => 
)


otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
```
5. Resolva este problema para entender porque as referências não estão sendo atualizadas corretamente. Não é problema com o .JSON, eles estão todos corretos, analisei um a um.

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