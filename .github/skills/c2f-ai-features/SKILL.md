---
name: c2f-ai-features
description: "Implemente e revise recursos de IA no Conn2Flow Pro: provedores unificados, créditos, modos, isolamento de dados, renderização segura e validação de respostas. Use ao integrar IA em módulos do produto."
---

# Recursos de IA no Conn2Flow Pro

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Implementar ou revisar recursos de IA em módulos do Conn2Flow Pro.
- **SKIP APENAS SE**: A tarefa não integra nem altera recursos de IA do produto.
- **CONSEQUÊNCIA DE IGNORAR**: Cobrança duplicada, exposição de dados sensíveis e renderização insegura de respostas.

Origem: BL-028, REQ-067 / BATCH-069 (2026-10-09). Antes de implementar, confira a biblioteca `conn2flow/gestor/bibliotecas/ia-provedores.php`, os hooks e os recursos do módulo real. Exemplos vivos: `conn2flow-site/gestor/modulos/ai-assistant/`, `ai-agents/` e `ai-writer/`. Nomes de caminhos partem das respectivas raízes; não confunda a matriz com o core.

## Oito pilares

1. **Provedor unificado.** Chame `ia_provedor_gerar_texto($servidor, $pedido, $tempo = 120)` ou `ia_provedor_gerar_imagem($servidor, $pedido, $tempo = 180)`. Informe `recurso` e `referencia` no pedido. Módulos não fazem HTTP direto à LLM nem duplicam o adaptador. Trate `status`, `message` e `bloqueado` conforme a biblioteca vigente.
2. **Créditos pelos hooks.** O ponto `ia-provedores` usa o filtro `pedido.autorizar` antes da chamada e a ação `pedido.concluido` depois, com status/uso. O módulo não debita novamente. Confira que o controle de créditos do Pro está registrado: o core pode executar sem hooks e o wrapper registra exceções sem bloquear por si só; o callback que exige recusa em falha deve implementá-la. Nunca suponha cobrança automática em instalação sem a integração.
3. **Modos editáveis, segurança fixa.** A regra de escrita reside em `ai_modes` com alvo do módulo editável no painel. Permissões, limites, sanitização e privacidade continuam no código e não podem ser removidos por modo editável. Use o sistema de recursos e variáveis para instruções/rótulos configuráveis.
4. **Isolamento de dados.** Envolva o contexto da tela entre `<dados>` e `</dados>`, com instrução fixa para nunca obedecer comandos encontrados nesse bloco. Trate delimitadores vindos do usuário para impedir fechamento antecipado. O envelope não substitui autorização, seleção mínima de campos e validação de saída.
5. **Renderização segura.** Resposta nunca entra como HTML cru. Crie nós de texto no DOM; se o produto permitir formatação, aplique whitelist de tags/atributos no servidor e cliente, com teste de paridade e conteúdo adversarial. Não use `innerHTML` com resposta não sanitizada.
6. **Telemetria sem conteúdo bruto.** Registre usuário/contexto permitido, recurso, referência, provedor/modelo, tokens e status. Não persista prompt, texto gerado, chave ou `resposta_completa` em logs de uso. Inspeções de validação usam dados mascarados e retenção restrita.
7. **Degradação graciosa.** Exercite falta de servidor/provedor, falta de créditos, recusa, timeout e erro. A tela comunica a condição usando variáveis e mantém as ações independentes de IA; verificações locais do guarda continuam. Não invente uma resposta bem-sucedida.
8. **Dados sensíveis e banco.** Não leia senha/hash para o contexto; masque e-mail e nome de usuário, que também pode ser e-mail. Libere a conexão MySQL antes da espera com `ia_provedor_banco_soltar()`; o HTTP unificado já chama essa função. Aplique a mesma disciplina a outro pedido HTTP longo e confira persistência após reconexão.

## Validação antes da entrega

Siga `project-validation`: inspecione pedido mascarado e resposta real, com critérios sustentados pela fonte; use tela com menu, usuário com nome em formato de e-mail e componente criado depois do hook. Verifique permissões, conteúdo hostil, créditos e ausência de texto bruto na telemetria. Fixtures são removidas no `finally`, com confirmação da limpeza. Sem provedor acessível, registre testes com dublê e o caminho real não exercitado, sem atribuir homologação real.
