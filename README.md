# VSE Random Video Gallery - Add-on para Blender

Este é um add-on para o Blender que agiliza a criação de sequências de vídeo, permitindo ao usuário adicionar múltiplos clipes de vídeo aleatórios de uma pasta especificada diretamente na timeline do Video Sequence Editor (VSE).

O add-on foi projetado para ser flexível, oferecendo controle sobre transições, escala, organização de canais e muito mais.

## Funcionalidades

- **Seleção de Pasta Recursiva:** Escaneia uma pasta principal e todas as suas subpastas em busca de arquivos de vídeo.
- **Filtro Inteligente:** Ignora automaticamente as pastas de proxy do Blender (`BL_proxy`) para evitar a inclusão de vídeos de baixa resolução.
- **Adição Aleatória:** Seleciona um número especificado de vídeos aleatoriamente da lista encontrada para adicionar à timeline.
- **Controle de Transições:** Adiciona automaticamente transições de "Crossfade" entre os clipes, com duração e canal configuráveis.
- **Ajuste de Escala Automático:** Ajusta automaticamente a escala dos vídeos para se adequarem à resolução da cena, com as opções "Contain" (Conter) ou "Cover" (Cobrir).
- **Organização de Canais:** Alterna os vídeos entre dois canais configuráveis (A e B) para permitir transições limpas.
- **Anexar ao Final:** Permite continuar um trabalho, adicionando novos vídeos ao final da sequência já existente na timeline.
- **Feedback Visual:** Mostra um cursor de "espera" e uma barra de progresso durante operações demoradas.

## Instalação

Existem duas maneiras de instalar o add-on:

### Método 1: Instalando pelo Arquivo .zip (Recomendado)

Este método é o mais fácil se você baixou o projeto do GitHub.

1.  Vá para a página principal do repositório no GitHub.
2.  Clique no botão verde **`< > Code`** e depois em **`Download ZIP`**.
3.  Salve o arquivo `.zip` no seu computador (não precisa descompactá-lo!).
4.  Abra o Blender e vá para `Edit > Preferences...`.
5.  Na janela de Preferências, vá para a aba `Add-ons`.
6.  Clique no botão `Install...` no canto superior direito.
7.  Navegue até o local onde você salvou o arquivo **`.zip`** e selecione dentro dele o seguinte arquivo: `galeria-videos.py` ali dentro. Clique em "Install Add-on".
8.  Após a instalação, encontre o add-on na lista (você pode pesquisar por "VSE Random Video Gallery") e marque a caixa de seleção ao lado dele para ativá-lo.

### Método 2: Instalando pelo Arquivo .py

Se você tem apenas o arquivo `galeria-videos.py`.

1.  Baixe ou salve o arquivo `galeria-videos.py` no seu computador.
2.  Siga os passos 4, 5 e 6 do Método 1, mas na etapa 7, selecione o arquivo `.py` em vez do `.zip`.

## Como Usar

1.  Abra o workspace **Video Editing** no Blender.
2.  Abra a barra lateral direita no Sequencer (atalho: tecla `N`).
3.  Você encontrará uma nova aba chamada **"GaleriaVSE"**. Clique nela para abrir o painel do add-on.

### Fluxo de Trabalho

1.  **Configurações da Galeria:**
    - **Pasta de Vídeos:** Clique no ícone de pasta e selecione o diretório principal que contém seus vídeos.
    - **Extensões:** Mantenha as extensões de vídeo padrão ou adicione/remova conforme necessário (separadas por vírgula).
    - **Importar Sem Áudio:** Marque esta opção se não quiser que as faixas de áudio dos vídeos sejam importadas.
    - **Nº de Vídeos na Galeria:** Defina quantos vídeos aleatórios você quer adicionar por vez.

2.  **Posicionamento:**
    - **Anexar ao Final da Timeline:** Marque esta caixa se você já tem uma sequência na timeline e quer adicionar mais vídeos ao final dela.
    - **Frame Inicial:** Se "Anexar ao Final" estiver desmarcado, defina em qual frame a nova sequência deve começar (o padrão é 0).

3.  **Organização de Canais e Efeitos:**
    - Configure os canais para os vídeos (A e B) e para as transições. Os padrões (2, 3, 4) são recomendados.
    - Defina a duração da transição em frames.
    - Escolha o **Modo de Escala**: "Contain" (para ver o vídeo inteiro) ou "Cover" (para preencher a tela).

4.  **Executando as Ações:**
    - **Passo 1:** Clique no botão **"1. Construir/Atualizar Lista"**. O add-on irá varrer suas pastas (mostrando uma barra de progresso) e informará quantos vídeos encontrou.
    - **Passo 2:** Clique no botão **"2. Adicionar Galeria à Timeline"**. Os vídeos selecionados aleatoriamente serão adicionados à sua timeline do VSE com todas as configurações aplicadas.

## Licença

Este projeto é de código aberto e pode ser modificado e distribuído livremente.