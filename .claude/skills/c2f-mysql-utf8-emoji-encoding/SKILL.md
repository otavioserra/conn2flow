---
name: c2f-mysql-utf8-emoji-encoding
description: Use ao persistir JSON com texto livre, conteúdo de IA ou emojis em conexões MySQL Conn2Flow configuradas como utf8 de 3 bytes.
user-invocable: false
---

# JSON seguro para MySQL utf8

- Com `mysqli_set_charset(..., "utf8")`, caracteres de 4 bytes não podem ser gravados diretamente.
- Use `json_encode($dados, JSON_UNESCAPED_SLASHES)`.
- Não use `JSON_UNESCAPED_UNICODE`; escapes `\uXXXX` são ASCII-safe e `json_decode` recompõe o texto.
- Verifique erro e linhas afetadas; helpers legados podem falhar silenciosamente.
- Coluna nula após migration bem-sucedida é sinal para investigar charset.
