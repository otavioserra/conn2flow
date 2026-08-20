---
name: c2f-gd-image-safety
description: "LEIA ANTES de manipular imagens com PHP GD (redimensionamento, conversão, thumbnails). Se não ler: o servidor entra em Fatal Error por falta de suporte a formatos (WebP/AVIF), esgota memória ou corrompe uploads."
user-invocable: false
---

# Segurança de imagens GD

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Manipular upload, redimensionamento, recorte ou conversão de imagens usando a extensão GD do PHP.
- **SKIP APENAS SE**: Upload de arquivos binários não-imagem (PDFs, vídeos, áudios) tratados como arquivos estáticos puros.
- **CONSEQUÊNCIA DE IGNORAR**: Fatal Errors não capturados (`\Throwable`) em servidores sem suporte nativo a formatos específicos (WebP/AVIF) e corrupção de imagens de usuários.

---

- Antes de processar um formato, confirme `function_exists` tanto para a função de leitura quanto para a de escrita.
- Não presuma suporte WebP/AVIF a partir da presença da extensão GD; valide as funções específicas ou `gd_info()`.
- Capture `\Throwable`, não apenas `\Exception`: função GD ausente lança `\Error` e pode causar HTTP 500.
- Se o servidor não puder gerar miniatura, degrade com segurança para o arquivo original quando o navegador suportá-lo.
- Valide entrada, saída e destruição de recursos; nunca grave arquivo parcial sobre o original.
- Teste explicitamente um runtime sem suporte ao formato opcional.
