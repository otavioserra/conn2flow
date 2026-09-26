---
title: "Módulo admin-ia"
description: "Cadastro de provedores de IA e testes de conexão."
section: reference
module: admin-ia
sources:
  - gestor/modulos/admin-ia/admin-ia.php
  - gestor/modulos/admin-ia/admin-ia.js
  - gestor/modulos/admin-ia/admin-ia.json
  - gestor/modulos/admin-ia/gemini/pt-br/data.json
  - gestor/modulos/admin-ia/gemini/en/data.json
  - gestor/db/migrations/20250930155415_create_servidores_ia_table.php
  - gestor/db/migrations/20250930155416_create_logs_testes_ia_table.php
verified_at: 98ac881d
---

# Módulo admin-ia

Cadastra servidores/provedores de IA, protege suas chaves de API no banco, testa conexões e exibe o histórico dos testes. A configuração de modelos Gemini disponíveis globalmente aparece na edição de servidor.

## Como usar

Abra admin-ia/listar/, crie em admin-ia/adicionar/ ou edite em admin-ia/editar/?id=<número>. Informe nome, tipo e chave; escolha se será o padrão do tipo. Na edição é possível testar a conexão, ver os últimos testes, ativar/desativar e excluir. As três rotas existem em pt-br e en; uma opção raiz, quando alcançada, redireciona à lista.

## Referência técnica

O switch de página trata raiz, listar-servidores, adicionar-servidor e editar-servidor. AJAX trata salvar, editar, testar_conexao, historico_testes, excluir, ativar, desativar e salvar_modelos_globais. A chave é cifrada com as chaves OpenSSL configuradas e fica mascarada no editor; o teste de tipo gemini decifra a chave, chama generateContent via cURL e registra resultado, erro e duração. O JSON define a URL/modelo Gemini; arquivos gemini/<idioma>/data.json alimentam a lista de modelos. Não há widget, template, hook ou hooks.api do módulo.

servidores_ia tem id_servidores_ia, nome, tipo, padrao, chave_api, status e datas. logs_testes_ia tem id_logs_testes_ia, id_servidores_ia, data_teste, sucesso, mensagem_erro e tempo_resposta. O código também grava ia_user_models para id_usuarios=0; não há migration dessa tabela entre as migrations do core.

## Limitações confirmadas

> [!WARNING]
> Várias buscas por id numérico em editar/testar/excluir/histórico concatenam o valor da requisição na SQL sem escape. O teste Gemini é o único tipo implementado no switch, embora o esquema aceite outros tipos. salvar_modelos_globais apaga as linhas globais antes de reinseri-las, sem transação explícita.

> [!CAUTION]
> A rotina de edição no JS acessa o valor do checkbox padrao sem guarda quando ele está ausente; isso pode impedir salvar com o checkbox desmarcado.

## Veja também

- [Modos de IA](admin-modos-ia.md)
- [Prompts de IA](admin-prompts-ia.md)
