---
name: c2f-gd-image-safety
description: Use ao ler, converter, redimensionar ou gerar miniaturas com GD, SimpleImage, WebP, AVIF ou outros formatos opcionais.
user-invocable: false
---

# Segurança de imagens GD

- Confirme `function_exists` para as funções de leitura e escrita do formato.
- Não presuma suporte WebP/AVIF apenas porque GD está carregado.
- Capture `\Throwable`, não só `\Exception`: função GD ausente lança `\Error`.
- Sem suporte a miniatura, degrade para o arquivo original quando o navegador puder renderizá-lo.
- Nunca grave saída parcial sobre o original e teste um runtime sem o formato opcional.
