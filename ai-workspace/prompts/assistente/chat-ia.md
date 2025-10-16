# Documentação de Acompanhamen## Módulo Admin IA
- **Estrutura**: Módulo "admin-ia" criado via script, com pastas controladores, resources, db.
- **Funcionalidades**:
  - **CRUD Integrações**: Adicionar/editar/remover servidores IA (Gemini primeiro, depois ChatGPT, etc.).
  - **Campos**: Nome, Tipo (Google Gemini, OpenAI, etc.), URL API, Chave API, Configurações específicas.
  - **Teste de Conexão**: Botão para validar conexão e chave API.
  - **Status**: Ativo/Inativo, Último teste, Logs de erro.
- **Banco de Dados**:
  - `servidores_ia`: id, nome, tipo, url_api, chave_api, configuracoes, status, data_criacao.
  - `logs_testes_ia`: id_servidor, data_teste, sucesso, mensagem_erro.
- **Páginas**: Lista de integrações, formulário adicionar/editar, página de teste.
- **Segurança**: Chaves API encriptadas, permissões por perfil de usuário.

## Integração Gemini (Prioridade Inicial)
- **Por que Gemini primeiro?**: Tier gratuito generoso (60 RPM), não requer cartão de crédito, ideal para desenvolvimento.
- **Modelo**: gemini-1.5-flash-latest (rápido e capaz).
- **Obtenção da Chave API**:
  - Acesse [Google AI Studio](https://aistudio.google.com)
  - Faça login com conta Google
  - Clique "Get API key"
  - Crie projeto no Google Cloud (se necessário)
  - Gere e copie a chave API
- **Endpoint**: `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=API_KEY`
- **Payload JSON**:
  ```json
  {
    "contents": [{
      "parts": [{
        "text": "Seu prompt aqui"
      }]
    }],
    "generationConfig": {
      "temperature": 0.9,
      "maxOutputTokens": 2048
    }
  }
  ```
- **Exemplo PHP** (baseado na resposta do agente):
  ```php
  function callGeminiAPI(string $prompt, string $apiKey): ?string {
      $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;
      $data = [
          'contents' => [[
              'parts' => [[
                  'text' => $prompt
              ]]
          ]]
      ];
      $jsonData = json_encode($data);
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      
      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      
      if ($httpCode !== 200 || $response === false) {
          return null;
      }
      
      $responseData = json_decode($response);
      return $responseData->candidates[0]->content->parts[0]->text ?? null;
  }
  ```
- **Tier Gratuito**: 60 requisições por minuto, suficiente para desenvolvimento e uso moderado.

## Visão Geral do Projeto
Implementar um sistema de chat integrado com IA como um campo reutilizável em módulos existentes do Conn2Flow, para automatizar criação de conteúdos (páginas, layouts, componentes, etc.). **Iniciar com módulo de administração de integrações IA (priorizando Gemini devido ao tier gratuito), depois integração no admin-paginas para criação assistida de páginas.**

## Requisitos Principais
1. **Módulo Admin Integrações IA**: CRUD para gerenciar conexões com servidores IA (ChatGPT, Gemini, etc.).
2. **Campo de Chat IA**: Campo integrado em formulários de módulos (ex: admin-paginas) para descrição assistida.
3. **Modelos de Pré-prompt**: Templates específicos por tipo de conteúdo (página, layout, componente, etc.).
4. **API de Comunicação**: Interface para enviar prompts compostos (pré-prompt + input usuário) para servidores IA.
5. **Webhook**: Endpoint para respostas assíncronas dos servidores IA.

## Arquitetura Proposta
- **Módulo Admin IA**: Novo módulo "admin-ia" com CRUD completo para integrações.
- **Componente Reutilizável**: Campo de chat IA como componente HTML/JS, integrado via variável global @[[componente#chat-ia]]@.
- **Biblioteca IA**: Nova biblioteca (PHP/JS) com funções para envio de prompts e tratamento dinâmico de retornos por módulo.
- **Modelos de Pré-prompt**: Templates específicos por tipo de conteúdo, armazenados na biblioteca.
- **Integração Modular**: Lógica de retorno varia por módulo (ex: páginas → HTML/CSS; galeria → estrutura de imagens).
- **Backend**: Controladores PHP para processamento de prompts e integração com IA.
- **Banco de Dados**: Tabelas para servidores IA, conversas por módulo/conteúdo.
- **Segurança**: Autenticação JWT, validação de tokens IA.

## Biblioteca IA
- **Estrutura**: Biblioteca PHP em `gestor/bibliotecas/ia.php` com classes para envio e tratamento.
- **Funções Principais**:
  - `enviarPrompt(tipo_conteudo, input_usuario, servidor_ia)`: Monta pré-prompt + input, envia para IA.
  - `processarRetorno(modulo, dados_retorno)`: Trata retorno dinamicamente (ex: páginas → HTML/CSS; galeria → estrutura).
- **Interfaces Dinâmicas**: Uso de switch/case ou estratégia pattern para variar lógica por módulo.
- **Pré-prompts**: Métodos estáticos por tipo (ex: `getPromptPagina()`, `getPromptLayout()`).

## Módulo Admin IA
- **Estrutura**: Módulo "admin-ia" criado via script, com pastas controladores, resources, db.
- **Funcionalidades**:
  - **CRUD Integrações**: Adicionar/editar/remover servidores IA (ChatGPT, Gemini 2.5 Pro, etc.).
  - **Campos**: Nome, Tipo (OpenAI, Google, etc.), URL API, Chave API, Configurações específicas.
  - **Teste de Conexão**: Botão para validar conexão e chave API.
  - **Status**: Ativo/Inativo, Último teste, Logs de erro.
- **Banco de Dados**:
  - `servidores_ia`: id, nome, tipo, url_api, chave_api, configuracoes, status, data_criacao.
  - `logs_testes_ia`: id_servidor, data_teste, sucesso, mensagem_erro.
- **Páginas**: Lista de integrações, formulário adicionar/editar, página de teste.
- **Segurança**: Chaves API encriptadas, permissões por perfil de usuário.

## Tarefas de Planejamento

### Fase 1: Definição de Arquitetura e Estrutura (Atualizada)
- [x] Analisar arquitetura atual do Conn2Flow (layouts, módulos, controladores)
- [x] Projetar modelos de pré-prompt por tipo de conteúdo (página, layout, componente)
- [x] Definir estrutura do componente chat IA (HTML/JS reutilizável)
- [x] Projetar estrutura do módulo admin-ia (CRUD integrações)
- [x] Projetar estrutura do módulo admin-ia (CRUD integrações)
- [x] Projetar esquema de banco de dados (tabelas: servidores_ia, conversas_por_modulo)
- [x] Definir endpoints da API (POST /api/ia/generate-content)
- [x] Definir endpoint do webhook (POST /webhook/ia-response)
- [x] Projetar integração Gemini como primeira implementação (tier gratuito, documentação completa)

### Fase 2: Módulo Admin IA
- [x] Criar módulo admin-ia via script de criação
- [x] Implementar migrações de banco (tabelas servidores_ia, logs_testes_ia)
- [x] Criar controladores para CRUD de integrações
- [x] Implementar suporte específico para Gemini (tipo, campos, validação)
- [x] Implementar páginas: lista, adicionar/editar, teste de conexão
- [x] Adicionar encriptação para chaves API
- [x] Implementar lógica de teste de conexão (inicialmente para Gemini)
- [x] Criar permissões e validações de segurança
- [x] Testar CRUD completo e testes de conexão com Gemini

### Fase 3: Sistema de Prompts Técnicos (Pré-Prompts)
- [x] Criar módulo admin-prompts-ia para gerenciamento de prompts pré-configurados
- [x] Implementar estrutura de banco de dados (tabela prompts_ia com campos: nome, alvo, padrao, prompt)
- [x] Desenvolver interface CRUD completa (adicionar/editar/listar prompts)
- [x] Implementar lógica de prompt padrão por alvo (paginas, layouts, componentes)
- [x] Criar sistema de validação para evitar múltiplos prompts padrão no mesmo alvo
- [x] Desenvolver templates de prompts técnicos específicos por tipo de conteúdo
- [x] Implementar internacionalização completa (português/inglês) para todas as interfaces
- [x] Criar sistema de versionamento e histórico de alterações nos prompts
- [x] Integrar com sistema de permissões do Conn2Flow
- [x] Testar funcionalidades CRUD e validações de negócio

### Fase 4: Implementação do Backend IA
- [x] Criar biblioteca IA em `gestor/bibliotecas/ia.php`
- [x] Implementar função `ia_renderizar_prompt()` que irá pegar o componente de prompt e substituir variáveis
- [x] Criar infraestrutura de API em `gestor/controladores/api/api.php`
- [x] Implementar roteamento de endpoints (_api/ia/*)
- [x] Implementar controle básico de rate limiting
- [x] Testar todos os endpoints da API (status, health, ia/*)
- [x] Verificar autenticação e tratamento de erros
- [ ] Implementar autenticação JWT para endpoints privados
- [ ] Implementar função `ia_enviar_prompt()` com união pré-prompt + input
- [ ] Criar métodos para pré-prompts estáticos por tipo de conteúdo
- [ ] Implementar função `ia_processar_retorno()` com lógica dinâmica por módulo
- [ ] Criar controladores para processamento de prompts IA
- [ ] Implementar envio para servidores IA (HTTP requests com autenticação)
- [ ] Criar controlador para webhook
- [ ] Implementar validação e processamento de respostas IA

### Fase 5: Integração no Admin-Páginas
- [ ] Analisar estrutura atual do admin-paginas (formulários adicionar/editar)
- [ ] Adicionar campo de chat IA no formulário de páginas via @[[componente#chat-ia]]@
- [ ] Implementar componente JS para interação do chat (envio via AJAX para controlador)
- [ ] Integrar geração de código HTML/CSS via biblioteca IA no salvamento da página
- [ ] Adicionar preview da página gerada antes de salvar
- [ ] Testar fluxo completo: descrição → IA → código → preview → salvar

### Fase 6: Expansão e Testes
- [ ] Expandir para outros módulos (layouts, componentes)
- [ ] Implementar múltiplos modelos de pré-prompt dinâmicos
- [ ] Testar comunicação com servidores IA (usar mocks inicialmente)
- [ ] Implementar tratamento de erros e logs
- [ ] Testar webhook com simulações
- [ ] Validar segurança e performance
- [ ] Documentação de uso por módulo

## Modelos de Pré-Prompt Técnicos (Sistema Implementado)

### Sistema de Gestão de Prompts
- **Módulo admin-prompts-ia**: Sistema completo para gerenciamento de prompts pré-configurados
- **Estrutura de Banco**: Tabela `prompts_ia` com campos para nome, alvo, padrão e conteúdo do prompt
- **Validação de Negócio**: Apenas um prompt padrão por alvo (páginas, layouts, componentes)
- **Internacionalização**: Suporte completo para português e inglês
- **Versionamento**: Controle de alterações e histórico de modificações

### Templates de Prompts por Alvo

#### Páginas (Implementado)
```
Você é um especialista em desenvolvimento web e irá criar uma página HTML usando o framework Fomantic-UI. 

IMPORTANTE: Esta página será integrada no sistema Conn2Flow, então:
- Use apenas o conteúdo que vai dentro da tag <body>
- NÃO inclua <html>, <head>, <body> ou qualquer tag estrutural
- Use classes do Fomantic-UI para estilização
- Mantenha a responsividade e acessibilidade
- Foque em semântica HTML5 adequada
- Evite JavaScript inline - prefira integração com frameworks do sistema
- Use variáveis dinâmicas do Conn2Flow quando necessário: @[[variavel#valor]]@

O usuário solicitou: [INPUT_USUARIO]

Gere apenas o código HTML da página, sem explicações adicionais.
```

#### Layouts (Implementado)
```
Você é um especialista em design de layouts responsivos e irá criar um layout usando Fomantic-UI para o sistema Conn2Flow.

REGRAS IMPORTANTES:
- Use apenas o conteúdo que vai dentro da tag <body>
- NÃO inclua tags <html>, <head> ou <body>
- Crie um layout estrutural com header, navegação, conteúdo principal e footer
- Use classes responsivas do Fomantic-UI
- Inclua variáveis dinâmicas do Conn2Flow: @[[pagina#corpo]]@ para conteúdo dinâmico
- Mantenha acessibilidade e usabilidade
- Evite JavaScript inline

O usuário precisa de: [INPUT_USUARIO]

Gere apenas o código HTML do layout, sem explicações.
```

#### Componentes (Implementado)
```
Você é um especialista em componentes reutilizáveis e irá criar um componente usando Fomantic-UI para o Conn2Flow.

DIRETRIZES:
- Crie um componente modular e reutilizável
- Use apenas HTML e classes Fomantic-UI
- Mantenha foco em acessibilidade e performance
- Evite JavaScript inline - use apenas HTML/CSS
- Considere integração com variáveis dinâmicas: @[[componente#parametro]]@
- Mantenha semântica HTML5 adequada

O componente deve: [INPUT_USUARIO]

Gere apenas o código HTML do componente, sem explicações adicionais.
```

### Funcionalidades do Sistema de Prompts

#### Gerenciamento CRUD Completo
- ✅ Adicionar novos prompts com nome descritivo
- ✅ Editar prompts existentes com versionamento
- ✅ Listar todos os prompts com filtros
- ✅ Definir prompt padrão por alvo
- ✅ Validação automática de unicidade de padrão

#### Validações de Negócio
- ✅ Apenas um prompt padrão por alvo
- ✅ Verificação automática de conflitos
- ✅ Alerta visual para usuário sobre limitações
- ✅ Manutenção da integridade dos dados

#### Internacionalização
- ✅ Interface completamente traduzida (PT-BR/EN)
- ✅ Variáveis dinâmicas para todos os textos
- ✅ Suporte a expansão para novos idiomas
- ✅ Consistência terminológica

## Modelos de Pré-Prompt (Conceituais - Futuro)

## Exemplos de Uso da Biblioteca

### Para Páginas
- **Envio**: `IA::enviarPrompt('pagina', $input_usuario, $servidor)`
- **Retorno**: `IA::processarRetorno('paginas', $dados_ia)` → Insere HTML em campo HTML, CSS em campo CSS

### Para Galerias (Futuro)
- **Envio**: `IA::enviarPrompt('galeria', $input_usuario, $servidor)`
- **Retorno**: `IA::processarRetorno('galerias', $dados_ia)` → Cria estrutura de imagens e metadados

### Para Layouts (Futuro)
- **Envio**: `IA::enviarPrompt('layout', $input_usuario, $servidor)`
- **Retorno**: `IA::processarRetorno('layouts', $dados_ia)` → Gera layout com variáveis @[[...]]@

### Infraestrutura da API

#### ✅ Controlador API Implementado e Testado
- **Arquivo**: `gestor/controladores/api/api.php`
- **Endpoint Base**: `/_api/` - Todas as requisições API são roteadas através deste endpoint
- **Acesso**: `http://localhost/instalador/_api/*`
- **Métodos Suportados**: GET, POST, PUT, DELETE, OPTIONS
- **Headers CORS**: Configurados para permitir requisições cross-origin
- **Rate Limiting**: Controle básico de 100 requisições por hora por IP
- **Autenticação**: Suporte a tokens JWT e API keys (placeholder para implementação futura)
- **Respostas**: Padronizadas em JSON com status, message, timestamp e dados

#### ✅ Endpoints Implementados e Testados
- **GET `/_api/status`**: Status geral da API (público) ✅ Testado
- **GET `/_api/health`**: Health check da API (público) ✅ Testado
- **POST `/_api/ia/generate`**: Geração de conteúdo via IA (privado) ✅ Testado
- **GET `/_api/ia/status?id={id}`**: Status de uma requisição IA (privado) ✅ Testado
- **GET `/_api/ia/models`**: Lista de modelos IA disponíveis (privado) ✅ Testado

#### ✅ Funcionalidades Verificadas
- **Roteamento**: Funcionamento correto baseado no caminho da URL
- **Autenticação**: Validação de tokens com erro apropriado quando não fornecido
- **Tratamento de Erros**: Respostas padronizadas para endpoints inexistentes (404)
- **Parse JSON**: Processamento correto de corpos de requisição POST
- **Rate Limiting**: Implementado (não testado em profundidade devido ao limite alto)
- **CORS**: Headers configurados para desenvolvimento

#### Estrutura de Resposta
```json
{
  "status": "success|error",
  "message": "Mensagem descritiva",
  "timestamp": "2025-10-09T12:00:00Z",
  "data": { ... } // opcional
}
```

#### Rate Limiting
- **Limite**: 100 requisições por hora por IP
- **Implementação**: Cache em arquivo (para desenvolvimento)
- **Resposta de Erro**: HTTP 429 com mensagem explicativa

#### Autenticação (Próxima Fase)
- **Tokens JWT**: Para usuários autenticados
- **API Keys**: Para integrações de terceiros
- **Validação**: Verificação de assinatura e expiração
- **Controle de Abusos**: Rate limiting por token + IP
