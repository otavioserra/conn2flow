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

### Fase 3: Implementação do Backend IA
- [ ] Criar biblioteca IA em `gestor/bibliotecas/ia.php`
- [ ] Implementar função `enviarPrompt()` com união pré-prompt + input
- [ ] Criar métodos para pré-prompts estáticos por tipo de conteúdo
- [ ] Implementar função `processarRetorno()` com lógica dinâmica por módulo
- [ ] Criar controladores para processamento de prompts IA
- [ ] Implementar envio para servidores IA (HTTP requests com autenticação)
- [ ] Criar controlador para webhook
- [ ] Implementar validação e processamento de respostas IA

### Fase 4: Integração no Admin-Páginas
- [ ] Analisar estrutura atual do admin-paginas (formulários adicionar/editar)
- [ ] Adicionar campo de chat IA no formulário de páginas via @[[componente#chat-ia]]@
- [ ] Implementar componente JS para interação do chat (envio via AJAX para controlador)
- [ ] Integrar geração de código HTML/CSS via biblioteca IA no salvamento da página
- [ ] Adicionar preview da página gerada antes de salvar
- [ ] Testar fluxo completo: descrição → IA → código → preview → salvar

### Fase 5: Expansão e Testes
- [ ] Expandir para outros módulos (layouts, componentes)
- [ ] Implementar múltiplos modelos de pré-prompt dinâmicos
- [ ] Testar comunicação com servidores IA (usar mocks inicialmente)
- [ ] Implementar tratamento de erros e logs
- [ ] Testar webhook com simulações
- [ ] Validar segurança e performance
- [ ] Documentação de uso por módulo

## Modelos de Pré-Prompt

### Página (Inicial)
```
Informações Principais dessa comunicação. Você irá criar uma página usando o estilizador [Fomantic-UI|TailwindCSS]. Esta página não precisa dos dados da head do HTML. Apenas o que vai no body. Use classes responsivas e acessíveis. Foque em semântica HTML5. Evite JavaScript inline, prefira integração com frameworks do sistema. À seguir o usuário descreveu a seguinte necessidade dele: [INPUT_USUARIO]
```

### Layout (Futuro)
```
Você é um especialista em layouts responsivos. Crie um layout usando [Fomantic-UI|TailwindCSS] com as seguintes características: header fixo, navegação lateral, área de conteúdo principal, footer. Integre com o sistema de variáveis dinâmicas @[[...]]@ do Conn2Flow. Necessidade do usuário: [INPUT_USUARIO]
```

### Componente (Futuro)
```
Crie um componente reutilizável em HTML/CSS/JS usando [Fomantic-UI|TailwindCSS]. Deve ser modular e integrar com o sistema de componentes do Conn2Flow. Foco em acessibilidade e performance. Necessidade do usuário: [INPUT_USUARIO]
```

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

## Considerações Técnicas
- **Integração**: Seguir padrões do Conn2Flow (variáveis dinâmicas @[[...]]@, estrutura de módulos)
- **Performance**: Cache de respostas, otimização de queries
- **Escalabilidade**: Suporte a múltiplos provedores de IA (OpenAI, Anthropic, etc.)
- **Segurança**: Encriptação de tokens, validação de inputs, rate limiting

## Riscos e Mitigações
- **Dependência de Terceiros**: Implementar fallbacks e circuit breakers
- **Custos**: Monitorar uso de APIs pagas
- **Privacidade**: Conformidade com LGPD/GDPR para dados de conversas
- **Performance**: Otimizar para alta concorrência

## Próximos Passos
- Criar módulo admin-ia via script
- Implementar suporte para Gemini (primeiro servidor IA)
- Criar CRUD de integrações com teste de conexão
- Depois, criar biblioteca IA e componente chat
- Integrar no admin-paginas
