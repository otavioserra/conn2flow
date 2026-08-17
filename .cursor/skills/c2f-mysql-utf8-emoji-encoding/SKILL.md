---
name: c2f-mysql-utf8-emoji-encoding
description: Use ao persistir JSON com texto livre, conteúdo de IA ou emojis em conexões MySQL Conn2Flow configuradas como utf8 de 3 bytes.
user-invocable: false
---

# JSON seguro para MySQL utf8 de 3 bytes

- Enquanto a conexão usar `mysqli_set_charset(..., "utf8")`, trate caracteres de 4 bytes como incompatíveis com gravação direta.
- Codifique payloads JSON com `json_encode($dados, JSON_UNESCAPED_SLASHES)`.
- Não adicione `JSON_UNESCAPED_UNICODE`: os escapes `\uXXXX` mantêm o valor ASCII-safe e `json_decode` recompõe Unicode/emoji na leitura.
- Verifique erro/linhas afetadas após INSERT/UPDATE; helpers legados podem falhar silenciosamente.
- Se a migration existe mas a coluna continua nula, investigue charset antes de concluir que o caminho de persistência não executou.
- Se o projeto migrar integralmente para `utf8mb4`, reavalie esta compatibilidade antes de remover o padrão.
