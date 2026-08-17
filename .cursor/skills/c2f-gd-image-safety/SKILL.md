---
name: c2f-gd-image-safety
description: Use ao ler, converter, redimensionar ou gerar miniaturas com GD, SimpleImage, WebP, AVIF ou outros formatos opcionais.
user-invocable: false
---

# Segurança de imagens GD

- Antes de processar um formato, confirme `function_exists` tanto para a função de leitura quanto para a de escrita.
- Não presuma suporte WebP/AVIF a partir da presença da extensão GD; valide as funções específicas ou `gd_info()`.
- Capture `\Throwable`, não apenas `\Exception`: função GD ausente lança `\Error` e pode causar HTTP 500.
- Se o servidor não puder gerar miniatura, degrade com segurança para o arquivo original quando o navegador suportá-lo.
- Valide entrada, saída e destruição de recursos; nunca grave arquivo parcial sobre o original.
- Teste explicitamente um runtime sem suporte ao formato opcional.
