# Prompt para Agente de Programação - OAuth 2.0 Script de Consumo

Olá! Estou trabalhando em um projeto OAuth 2.0 para o sistema Conn2Flow Gestor, que implementa suporte a OAuth 2.0 para autenticação segura de aplicações externas. O projeto usa PHP, com bibliotecas como [`gestor/bibliotecas/oauth2.php`](gestor/bibliotecas/oauth2.php ) para geração e validação de tokens JWT via chaves RSA, e endpoints como `/oauth-authenticate/` que retornam JSON com tokens ou redirecionam via `url_redirect`.

**Contexto do Problema:**
- O usuário quer evitar automação complexa (como Selenium) para interação via navegador, pois precisa de reCAPTCHA do Google para combater spam, e isso já está integrado no sistema.
- A abordagem é: Abrir navegador manualmente, usuário insere credenciais, resolve reCAPTCHA, envia formulário, e capturar o retorno (JSON com access_token, refresh_token, etc.) ou redirecionamento.
- Depois, usar cURL para requisições subsequentes com os tokens capturados, sem reCAPTCHA (já autorizados).
- Motivo: Simplicidade, segurança (reCAPTCHA no navegador), e evitar camadas extras de automação.

**Minha Sugestão - Opção 2: Servidor Local (Menos Manual, Ainda Simples)**
- **Justificativa:** Combina interação manual no navegador com captura automática via servidor local em Python (para receber o callback/redirecionamento). Isso reduz copiar/colar manual, mas mantém simplicidade (sem Selenium). O navegador abre com `url_redirect` apontando para localhost, o usuário interage, e o servidor captura os tokens em um arquivo JSON. Depois, um script shell usa cURL com os tokens.
- **Vantagens:** Automatiza captura, usa reCAPTCHA nativamente, compatível com o projeto (retorno JSON ou redirecionamento).
- **Desvantagens:** Requer Python/Node.js para o servidor, mas é leve.

**Códigos de Exemplo Fornecidos:**
1. **Servidor Callback em Python (callback_server.py):**
   ```python
   # filepath: callback_server.py
   from http.server import BaseHTTPRequestHandler, HTTPServer
   import urllib.parse
   import json

   class CallbackHandler(BaseHTTPRequestHandler):
       def do_GET(self):
           # Parseia a URL de retorno (ex.: ?access_token=abc&...)
           query = urllib.parse.urlparse(self.path).query
           params = urllib.parse.parse_qs(query)
           
           # Salva tokens em arquivo ou imprime para o shell
           if 'access_token' in params:
               with open('tokens.json', 'w') as f:
                   json.dump(params, f)
               self.send_response(200)
               self.end_headers()
               self.wfile.write(b"Autenticacao concluida! Feche esta aba.")
           else:
               self.send_response(400)
               self.end_headers()
               self.wfile.write(b"Erro: Token nao encontrado.")

   if __name__ == "__main__":
       server = HTTPServer(('localhost', 8080), CallbackHandler)
       print("Servidor callback rodando em http://localhost:8080")
       server.serve_forever()
   ```

2. **Script Shell (Bash) para Fluxo Completo (oauth_flow_simple.sh):**
   ```bash
   # filepath: oauth_flow_simple.sh
   #!/bin/bash

   # Inicia servidor callback em background
   python callback_server.py &

   # Abre navegador com url_redirect para localhost
   start chrome "https://seu-dominio.com/oauth-authenticate/?url_redirect=http://localhost:8080"

   # Aguarda interação manual do usuario
   echo "Aguarde o usuario interagir no navegador..."

   # Verifica se tokens.json foi criado (polling simples)
   while [ ! -f tokens.json ]; do
       sleep 2
   done

   # Le tokens e continua o script
   tokens=$(cat tokens.json)
   echo "Tokens capturados: $tokens"
   # Aqui, use os tokens para proxima requisicao (ex.: curl com Bearer)

   # Mata o servidor
   pkill -f callback_server.py
   ```

**Como Usar:**
- Execute `python callback_server.py` em background.
- Rode `bash oauth_flow_simple.sh` para abrir navegador e aguardar.
- Usuário interage manualmente (credenciais + reCAPTCHA).
- Após sucesso, tokens são salvos em `tokens.json`.
- Use cURL: `curl -H "Authorization: Bearer $ACCESS_TOKEN" https://api.com/endpoint`.

**Pedido para Você (Agente):**
Com base nesse contexto e na Opção 2, gere uma implementação completa e funcional para o projeto Conn2Flow. Inclua:
- Ajustes no código Python/Bash para compatibilidade (ex.: Windows via WSL, tratamento de erros).
- Integração com o endpoint `/oauth-authenticate/` do projeto (supondo retorno JSON ou redirecionamento).
- Exemplo de uso com cURL para uma requisição API subsequente.
- Melhorias: Validação de tokens, segurança (HTTPS local), e documentação.
- Se possível, adapte para Node.js em vez de Python, ou sugira alternativas simples.

Garanta que a implementação seja segura, curta e siga boas práticas. Obrigado!
