---
name: c2f-mysql-utf8-emoji-encoding
description: "LEIA ANTES de salvar textos, posts, JSONs ou strings que possam conter Emojis ou caracteres Unicode especiais no MySQL. Se não ler: o banco rejeita o UPDATE silenciosamente e o registro fica NULL/não persiste."
user-invocable: false
---

# JSON seguro para MySQL utf8 de 3 bytes

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Gravar dados textuais ricos, posts de redes sociais, feedbacks de usuários ou JSONs arbitrários em colunas MySQL.
- **SKIP APENAS SE**: Colunas estritamente numéricas, booleanas ou slugs ASCII controlados.
- **CONSEQUÊNCIA DE IGNORAR**: Falha silenciosa de gravação no MySQL (`Incorrect string value`), perda irrecuperável de conteúdo digitado pelo usuário e campos persistidos como NULL.

---

- Enquanto a conexão usar `mysqli_set_charset(..., "utf8")`, trate caracteres de 4 bytes como incompatíveis com gravação direta.
- Codifique payloads JSON com `json_encode($dados, JSON_UNESCAPED_SLASHES)`.
- Não adicione `JSON_UNESCAPED_UNICODE`: os escapes `\uXXXX` mantêm o valor ASCII-safe e `json_decode` recompõe Unicode/emoji na leitura.
- Verifique erro/linhas afetadas após INSERT/UPDATE; helpers legados podem falhar silenciosamente.
- Se a migration existe mas a coluna continua nula, investigue charset antes de concluir que o caminho de persistência não executou.
- Se o projeto migrar integralmente para `utf8mb4`, reavalie esta compatibilidade antes de remover o padrão.
