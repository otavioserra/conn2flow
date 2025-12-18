````markdown
# Prompt for Programming Agent - OAuth 2.0 Consumption Script

Hello! I am working on an OAuth 2.0 project for the Conn2Flow Manager system, which implements OAuth 2.0 support for secure authentication of external applications. The project uses PHP, with libraries like [`gestor/bibliotecas/oauth2.php`](gestor/libraries/oauth2.php) for generating and validating JWT tokens via RSA keys, and endpoints like `/oauth-authenticate/` that return JSON with tokens or redirect via `url_redirect`.

**Problem Context:**
- The user wants to avoid complex automation (like Selenium) for browser interaction because it requires Google's reCAPTCHA to combat spam, and this is already integrated into the system.
- The approach is: Manually open a browser, the user enters credentials, solves reCAPTCHA, submits the form, and captures the return (JSON with access_token, refresh_token, etc.) or redirection.
- Afterward, use cURL for subsequent requests with the captured tokens, without reCAPTCHA (since they are already authorized).
- Reason: Simplicity, security (reCAPTCHA in the browser), and avoiding extra layers of automation.

**My Suggestion - Option 2: Local Server (Less Manual, Still Simple)**
- **Justification:** It combines manual browser interaction with automatic capture via a local Python server (to receive the callback/redirection). This reduces manual copy/pasting but maintains simplicity (no Selenium). The browser opens with `url_redirect` pointing to localhost, the user interacts, and the server captures the tokens in a JSON file. Then, a shell script uses cURL with the tokens.
- **Advantages:** Automates capture, uses reCAPTCHA natively, compatible with the project (JSON return or redirection).
- **Disadvantages:** Requires Python/Node.js for the server, but it's lightweight.

**Provided Example Codes:**
1. **Callback Server in Python (callback_server.py):**
   ```python
   # filepath: callback_server.py
   from http.server import BaseHTTPRequestHandler, HTTPServer
   import urllib.parse
   import json

   class CallbackHandler(BaseHTTPRequestHandler):
       def do_GET(self):
           # Parses the return URL (e.g., ?access_token=abc&...)
           query = urllib.parse.urlparse(self.path).query
           params = urllib.parse.parse_qs(query)
           
           # Saves tokens to a file or prints to the shell
           if 'access_token' in params:
               with open('tokens.json', 'w') as f:
                   json.dump(params, f)
               self.send_response(200)
               self.end_headers()
               self.wfile.write(b"Authentication complete! Close this tab.")
           else:
               self.send_response(400)
               self.end_headers()
               self.wfile.write(b"Error: Token not found.")

   if __name__ == "__main__":
       server = HTTPServer(('localhost', 8080), CallbackHandler)
       print("Callback server running at http://localhost:8080")
       server.serve_forever()
   ```

2. **Shell Script (Bash) for Full Flow (oauth_flow_simple.sh):**
   ```bash
   # filepath: oauth_flow_simple.sh
   #!/bin/bash

   # Starts callback server in the background
   python callback_server.py &

   # Opens browser with url_redirect to localhost
   start chrome "https://your-domain.com/oauth-authenticate/?url_redirect=http://localhost:8080"

   # Waits for manual user interaction
   echo "Waiting for user interaction in the browser..."

   # Checks if tokens.json has been created (simple polling)
   while [ ! -f tokens.json ]; do
       sleep 2
   done

   # Reads tokens and continues the script
   tokens=$(cat tokens.json)
   echo "Tokens captured: $tokens"
   # Here, use the tokens for the next request (e.g., curl with Bearer)

   # Kills the server
   pkill -f callback_server.py
   ```

**How to Use:**
- Run `python callback_server.py` in the background.
- Run `bash oauth_flow_simple.sh` to open the browser and wait.
- The user interacts manually (credentials + reCAPTCHA).
- After success, tokens are saved in `tokens.json`.
- Use cURL: `curl -H "Authorization: Bearer $ACCESS_TOKEN" https://api.com/endpoint`.

**Request for You (Agent):**
Based on this context and Option 2, generate a complete and functional implementation for the Conn2Flow project. Include:
- Adjustments to the Python/Bash code for compatibility (e.g., Windows via WSL, error handling).
- Integration with the project's `/oauth-authenticate/` endpoint (assuming JSON return or redirection).
- Example of using cURL for a subsequent API request.
- Improvements: Token validation, security (local HTTPS), and documentation.
- If possible, adapt to Node.js instead of Python, or suggest simple alternatives.

Ensure the implementation is secure, concise, and follows best practices. Thank you!

````