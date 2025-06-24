# Template de Projeto Blender para con2flow

Esta branch (`blender-template`) contém uma estrutura de pastas e arquivos `.blend` modelo para projetos de edição de vídeo e composição no **Blender**, associados ao projeto **con2flow**.

O objetivo é fornecer um ponto de partida organizado para agilizar a criação de novos vídeos.

## Como Usar

1.  Baixe ou clone o conteúdo desta branch para o seu computador.
2.  Renomeie a pasta principal para o nome do seu novo projeto (ex: `video-sobre-gatos`).
3.  Abra o arquivo `.blend` correspondente ao formato que você irá produzir (16:9 ou 9:16).
4.  Comece a trabalhar! Importe seus arquivos de mídia para as pastas correspondentes para manter tudo organizado.

## Estrutura do Projeto

A estrutura de pastas foi pensada para organizar os diferentes tipos de mídia que você usará no seu projeto.

```
/
├── audios/             # Para efeitos sonoros (SFX) e narrações.
├── captures/           # Para gravações de tela ou de jogos (gameplay).
├── images/             # Para logos, imagens de apoio, texturas e outros gráficos.
├── musics/             # Para as trilhas musicais do projeto.
├── outputs/            # Pasta padrão de saída para os vídeos renderizados.
├── videos/             # Para arquivos de vídeo brutos (raw footage) gravados com câmeras.
|
├── projeto_16-9.blend  # Arquivo pré-configurado para formato Widescreen (1920x1080 @ 60fps). Ideal para YouTube.
└── projeto_9-16.blend  # Arquivo pré-configurado para formato Vertical (1080x1920). Ideal para Reels, Shorts, etc.
```

## Configurações dos Arquivos .blend

-   **`projeto_16-9.blend`**: Configurado para renderizar em Full HD (1920x1080) a 60 frames por segundo, com codecs de saída comuns para o YouTube.
-   **`projeto_9-16.blend`**: A resolução e a câmera já estão ajustadas para o formato vertical (1080x1920).
