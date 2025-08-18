# Prompt Interactive Programming - NOME

## 🎯 Contexto Inicial
- FUNDAMENTAL: Analise o contexto anterior antes de seguir com as orientações abaixo que foi registrado no arquivo : `ai-workspace\prompts\arquitetura\atualização-dados-recursos-v2.md`.

## 📖 Bibliotecas

## 📝 Orientações para o Agente
1. Encontrei um problema na geração das versões e do checksum dos recursos: `paginas`, `layouts`, e `componentes`.
2. Verifiquei que quando eu mudo o valor do HTML de um recurso (provavelmente o problema pode ter também no CSS), daí executo o script ` php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`, o checksum no arquivo `TabelaData.json` muda corretamente, mas o campo `file_version` não está sendo incrementado neste arquivo. Os valores do `checksum` estão mudando corretamente. Por outro lado, o campo `file_version` não está sendo atualizado no arquivo da origem do recurso, o mesmo ocorre com o `checksum`. Não guardando o histórico conforme esperado. Exemplo:
- Mudei o arquivo `gestor\resources\pt-br\components\botao-superior-interface\botao-superior-interface.html`, e incluí isso no mesmo (mas qualquer mudança dá problema):
```html
<p class="ui #cor# text">
    #texto# as
</p>
```
- Executei o script `php ./gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php`.
- O registro no arquivo de origem `gestor\resources\pt-br\components.json` não foi modificado:
```json
    {
        "name": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "version": "1.2",
        "checksum": {
            "html": "17898f8cf079921914072cfa54971eb7",
            "css": "",
            "combined": "17898f8cf079921914072cfa54971eb7"
        }
    },
```
- O registro no arquivo de destino `gestor\db\data\ComponentesData.json` modificou só o `checksum` (os demais campos aparentemente estão mudando certo):
```json
    {
        "nome": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "language": "pt-br",
        "modulo": null,
        "html": "<a class=\"ui button #cor#\" href=\"#url#\" data-content=\"#tooltip#\" data-id=\"adicionar\">\n    <i class=\"#icon# icon\"><\/i>\n    #label#\n<\/a>\n<p class=\"ui #cor# text\">\n    #texto# as\n<\/p>",
        "css": null,
        "status": "A",
        "versao": 6,
        "file_version": "1.2",
        "checksum": "{\"html\":\"f4aa7f9cb54d699431bf771a5f12f442\",\"css\":\"\",\"combined\":\"f4aa7f9cb54d699431bf771a5f12f442\"}"
    },
```
- O que era esperado no registro no arquivo de origem `gestor\resources\pt-br\components.json`:
```json
    {
        "name": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "version": "1.3",
        "checksum": {
            "html": "f4aa7f9cb54d699431bf771a5f12f442",
            "css": "",
            "combined": "f4aa7f9cb54d699431bf771a5f12f442"
        }
    },
```
- O que era esperado no registro no arquivo de destino `gestor\db\data\ComponentesData.json`:
```json
    {
        "nome": "Botão Superior Interface",
        "id": "botao-superior-interface",
        "language": "pt-br",
        "modulo": null,
        "html": "<a class=\"ui button #cor#\" href=\"#url#\" data-content=\"#tooltip#\" data-id=\"adicionar\">\n    <i class=\"#icon# icon\"><\/i>\n    #label#\n<\/a>\n<p class=\"ui #cor# text\">\n    #texto# as\n<\/p>",
        "css": null,
        "status": "A",
        "versao": 6,
        "file_version": "1.3",
        "checksum": "{\"html\":\"f4aa7f9cb54d699431bf771a5f12f442\",\"css\":\"\",\"combined\":\"f4aa7f9cb54d699431bf771a5f12f442\"}"
    },
```


## 🧭 Estrutura do código-fonte
```
main():
    // Lógica principal do script
    

main()
```

## 🤔 Dúvidas e 📝 Sugestões

## ✅ Progresso da Implementação
- [] item do progresso

## ☑️ Processo Pós-Implementação
- [x] Executar o script gerado para ver se funciona corretamente. (Executado em 2025-08-18: versões e checksums atualizados e idempotência validada.)
- [x] Gerar mensagem detalhada e executar commit:

```bash
./ai-workspace/git/scripts/commit.sh "Recursos: atualização automática de version/checksum em origem (layouts, pages, components)

- Adiciona fase 'atualizarArquivosOrigem' ao script atualizacao-dados-recursos.
- Recalcula checksums (html, css, combined) lendo arquivos físicos antes da coleta.
- Incrementa 'version' em origem apenas quando checksum muda (mantendo histórico coerente).
- Sincroniza 'file_version' nos *Data.json com a versão de origem atualizada.
- Mantém incremento de 'versao' interno (contador de alterações de conteúdo) sem alterar lógica prévia.
- Adiciona flag --no-origin-update para pular atualização de origem quando necessário.
- Garante idempotência: segunda execução sem modificações não altera versões.
- Validação manual realizada para layouts, páginas e componentes (exemplo: botao-superior-interface 1.2 -> 1.3).
"
```

## ♻️ Alterações e Correções 1.0

## ✅ Progresso da Implementação das Alterações e Correções

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, substituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/git/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** dataAtual()
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow versao()