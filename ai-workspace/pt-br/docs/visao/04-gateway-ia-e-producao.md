# 04 — Gateway de IA & Prova em Produção

## Um gateway de IA emergente e independente de fornecedor

O **Conn2Flow Nexus** é um microsserviço em estágio inicial, projetado
para ficar entre a plataforma core e múltiplos provedores de IA: um
endpoint FastAPI recebe uma tarefa, enfileira no Kafka, um worker a
roteia através do LiteLLM para o modelo configurado (OpenAI, Claude,
Gemini, Groq e outros), e um worker de entrega posta o resultado de volta
ao Conn2Flow via webhook. O ponto é o desacoplamento — o core nunca
depende do SDK ou da disponibilidade de um único fornecedor, e o trabalho
do agente se torna assíncrono e observável em vez de uma chamada HTTP
bloqueante. Este serviço ainda está evoluindo e deve ser lido como uma
direção, não como um produto acabado.

## Além do navegador: uma arquitetura de agente mobile

O **aplicativo mobile complementar do Conn2Flow** é construído em torno do
mesmo padrão aplicado a uma superfície diferente: uma arquitetura de agente
full-stack que espelha dinamicamente o RBAC do core e clona módulos
administrativos web existentes (HTML, JS, Tailwind) para telas nativas,
consumindo os mesmos endpoints de autenticação que o painel administrativo
web usa. É evidência de que "conteúdo como API, governado da mesma forma
para humanos e agentes" não é específico do navegador.

## Rodando em produção hoje

Isto não é um exercício de laboratório. Múltiplos projetos de cliente já
em produção — cada um uma sobreposição privada sobre o mesmo core
Conn2Flow, seguindo o mesmo modelo de governança descrito nesta visão — já
rodam em produção. Os projetos específicos são deliberadamente não
nomeados aqui; o que importa para este documento é que o padrão já
sobreviveu ao contato com implantações reais e pagantes, não apenas com a
suíte de testes do próprio repositório core.
