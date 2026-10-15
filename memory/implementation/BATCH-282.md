---
id: "BATCH-282"
title: "Campo domain nos formulários do Core"
status: "implemented-pending-runtime-validation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Tipo domain no editor, widget e validação client/server dos formulários."
summary_medium: "REQ-273 em autonomia monitorada e topologia dupla.\nNormalização e validação FQDN com mensagens pt-br/en."
---

Projeto: conn2flow. Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.
REQ-273; BATCH-282; topologia dupla; autonomo_monitorado; branch feat/req-273.

- [x] Editor pt-br/en, variáveis e placeholder de domínio.
- [x] Widget com input text e atributos de higiene.
- [x] Sanitização imediata e validação FQDN no cliente.
- [x] Mensagens pt-br/en e normalização/validação PHP.
- [x] Compilação, testes, auto-revisão e evidências.
- [ ] Sincronização SQL e conferência da página publicada (Docker ausente).

Escopo: somente Core, sem alterações no conn2flow-site. Alteração inicial em
gestor/db/data/schema-metadata.json preservada e excluída do commit deste lote.
Outro agente atua no Site; pipelines devem executar sequencialmente.

Validação planejada: testes comportamentais de widget, normalização, limites de
labels/domínio, entradas inválidas e obrigatoriedade; manager:update-all e test:run.
Homologação humana permanece separada da validação técnica.

## Implementação e decisões de execução

Os seis editores aceitam domain; o schema já serializava o tipo selecionado sem
allowlist adicional. O placeholder traduzido é uma sugestão no editor e preserva
qualquer texto customizado. Domain não usa a caixa de opções de listas/limites.
O widget mantém label, nome e required, usando text e os três atributos pedidos.

O cliente normaliza no evento input/change e novamente no submit. O PHP normaliza
em $_POST antes de ler o nome da submissão, validar required e persistir os dados.
Ambos removem HTTP(S) e barras finais, convertem para minúsculas e rejeitam caminhos
internos, portas, caracteres inválidos e labels acima de 63 caracteres. Limite
total adicional: 253 caracteres. Valor não escalar enviado ao domínio é rejeitado.
Campo opcional vazio é aceito; required é verificado após a normalização.
Mensagens vêm de form-ui pt-br/en, sem novo literal de interface em PHP/JS.

O CLI não possuía test:run: foi adicionado como alias de db:test, preservando seu
executor PHPUnit e o filtro existente. JavaScript é conferido separadamente.
Versões forms 1.1.5 e interface 1.4.5; hashes e versões de recursos recalculados
oficialmente pelo pipeline, com os derivados minificados atualizados.

## Evidências em 2026-10-15

| Verificação | Resultado |
| --- | --- |
| PHPUnit focado: FormsDomainReq273Test e FormsWidgetFieldTypesTest | 11 testes, 76 asserções, saída 0 |
| test:run completo | 1.795 testes, 20.910 asserções, saída 0; 5 skips, 4 depreciações e 4 depreciações PHPUnit |
| Vitest completo final | 686 testes aprovados, 61 arquivos, saída 0; zero falhas |
| Vitest domain + construtores req-222 | 33 testes aprovados, incluindo 22 novos de domain |
| Lint PHP e node --check dos minificados alterados | aprovados |
| manager:update-all completo, duas tentativas sequenciais | saída 1: compilação concluída; sincronização de arquivos informa erro e sincronização SQL falha no destino Docker |
| Compilação de recursos | 8 recursos alterados compilados na primeira execução; segunda em cache; Data.json gerados |
| git diff --check | aprovado |

Comando PHP reproduzível em PowerShell:

```powershell
$env:OPENSSL_CONF = 'C:/Program Files/Git/usr/ssl/openssl.cnf'
php cli/c2f.php test:run
.\node_modules\.bin\vitest.cmd run
```

A primeira execução PHPUnit falhou na geração RSA por falta de openssl.cnf;
CoreHelpersTest passou após apontar a variável de processo ao arquivo instalado.
Não houve alteração versionada de configuração. A primeira execução Vitest achou
dependência nova no construtor isolado pelo teste da REQ-222; corrigida usando os
dados do próprio input da linha. A suíte completa posterior e o teste de domínio
restaurado passaram. Happy DOM emite avisos de iframe/script e ECONNREFUSED em
localhost:3000 em testes de outros módulos; o relatório final contém zero falhas.

Pipeline também informa seis avisos de framework_css em recursos não alterados e
uma falha de minificação de admin-categorias, sem diagnóstico detalhado pelo CLI.
O arquivo não foi modificado neste lote; sua minificação direta pelo terser local
e node --check passaram. Os dois scripts deste lote foram minificados pelo pipeline.

## Limites e próximo passo

Get-Command docker e command -v docker não localizaram Docker neste host.
O destino devEnvironment exige conn2flow-app em /var/www/sites/localhost/conn2flow-gestor/.
As etapas de banco, CSS derivado e publicação dist não foram concluídas.
Não foi exercitada a tela HTTP, persistência por UI nem submissão AJAX real;
os testes PHP cobrem funções de domínio, widget, traduções e seis templates;
os testes JS exercitam validateField e restauração do campo no DOM.
Assim, não se declara o DoD integral nem homologação humana.

Handoff ao Macro-Arquiteto: [REQ-273-BATCH-282](../handoffs/REQ-273-BATCH-282.md).
MCP Hub de despacho/recibos indisponível nesta sessão; entrega persistida em arquivo.
