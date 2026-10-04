---
name: c2f-module-visual-assets
description: "Diretrizes obrigatórias e prompt canônico para geração de imagens referenciais, capas (covers) e thumbnails de módulos no Conn2Flow e Conn2Flow Site. Use sempre que for criar novos módulos ou gerar assets visuais."
user-invocable: true
---

# 🎨 Geração de Assets Visuais e Capas de Módulos (Design System V3.0)

Esta skill define o padrão oficial para concepção e geração de capas conceituais, ilustrações 3D e thumbnails para qualquer módulo do ecossistema Conn2Flow (Core e Site), garantindo que novos módulos preservem a mesma sofisticação e unidade estética.

---

## 🏛️ 1. Princípios do Design System Visual

Todas as ilustrações de módulos devem obedecer rigidamente aos seguintes pilares:
1. **Linguagem Visual:** Isométrica 3D conceitual, estilizada e limpa.
2. **Materiais:** Vidro fosco (*frosted glass / pearl glass*) e cerâmica arredondada com acabamento suave.
3. **Paleta de Cores Canônica:**
   - **Fundo:** Azul meia-noite profundo e contínuo (*seamless deep midnight blue*).
   - **Acentos Luminosos:** Ciano suave e violeta elétrico nas bordas e reflexos sutis.
   - **Elementos de Apoio:** Tons neutros claros em cerâmica e transparência vítrea.
4. **Iluminação e Câmera:**
   - Câmera ortográfica isométrica (sem distorção de perspectiva extrema).
   - Luz de estúdio suave vindo da parte superior-esquerda com sombra suave de contato no chão.
5. **Composição e Margens:**
   - O grupo principal de objetos deve ocupar os **70% centrais** da imagem.
   - Deixar 15% de margem livre em todas as bordas para permitir corte em miniatura e avatares sem perder o foco.
6. **Regras Negativas Invioláveis (Negative Prompts):**
   - **NENHUM** texto, letra, número, logotipo, marca d'água, pessoa, rosto ou moldura ao redor da imagem.

---

## 🤖 2. Template do Prompt Canônico para Geradores de Imagem (DALL-E / Midjourney / SD)

Ao solicitar a uma IA ou pipeline a criação da imagem de um novo módulo, utilize o seguinte template preenchendo apenas os campos `{MODULO_ID}` e `{DESCRICAO_DO_OBJETO_3D}`:

```text
Use case: stylized-concept. Asset type: Conn2Flow module cover for {MODULO_ID}, square 1024x1024. 
Style Reference: Cohesive design system for Conn2Flow module covers: deep midnight blue seamless background, cyan and violet luminous accents, pearl frosted glass and soft rounded ceramic materials, orthographic 3D isometric camera, soft studio key light from upper left, subtle luminous edges, gentle ground contact shadow, sophisticated polished product render. 
Center the complete object group inside the middle 70% with generous margins for thumbnail cropping, clean restrained composition, readable as a small thumbnail. 
Subject: {DESCRICAO_DO_OBJETO_3D}. 
Strict constraints: No lettering, no text, no numbers, no logos, no watermarks, no people, no borders.
```

---

## 📐 3. Exemplos Práticos de Aplicação

### Exemplo 1: Módulo de Assinaturas (`subscriptions`)
> **Subject**: *"A floating 3D recurring billing sphere surrounded by a glowing cyan orbital ring with subtle calendar nodes and card token placeholders in frosted glass."*

### Exemplo 2: Módulo de Automação / Tarefas (`admin-cron`)
> **Subject**: *"Three interlocking floating precision ceramic clockwork gears with an elegant glowing violet pendulum and timeline indicator."*

### Exemplo 3: Módulo de Catálogo 3D (`3d-catalog`)
> **Subject**: *"A glowing holographic display pedestal showcasing an abstract floating wireframe polygon cube rendered in luminous cyan glass."*

---

## 📦 4. Especificações Técnicas de Entrega dos Arquivos

1. **Formato:** `.webp` (Google WebP).
2. **Resolução:** 1024 × 1024 pixels (proporção 1:1).
3. **Qualidade e Tamanho Máximo:** Qualidade 90, método 6, **tamanho máximo de 120 KB** (alvo: entre 60 KB e 95 KB).
4. **Caminho Canônico de Armazenamento:**
   - No Core: `gestor/assets/modulos/covers/<modulo-id>.webp`
   - No Site: `gestor/assets/modulos/covers/<modulo-id>.webp`
   - Opcional local do módulo: `gestor/modulos/<modulo-id>/cover.webp`
5. **Atualização do Manifesto:**
   - Adicionar a entrada com `id`, `width`, `height`, `bytes` e hash `sha256` no arquivo `gestor/assets/modulos/covers/manifest.json`.
