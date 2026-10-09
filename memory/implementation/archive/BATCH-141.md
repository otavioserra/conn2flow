# BATCH-141 — MIME real no admin-arquivos e correção da guarda de imagem no interface-v2

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-138.md`
- **Data de abertura**: 2026-08-26
- **Classificação**: implementação incremental / correção de defeitos pré-existentes

## Objetivo

Fazer o gerenciador de arquivos devolver MIME type de verdade em vez de um rótulo interno
concatenado, e restaurar a aceitação de imagens no widget do `interface-v2`, cuja guarda era uma
comparação sempre falsa.

## Slice aprovado

1. `arquivo_mime_por_extensao()` na biblioteca `arquivo.php`, ao lado de
   `arquivo_tipo_por_extensao()`, resolvendo MIME só pela extensão.
2. `admin-arquivos.php:183` passa a chamar a nova função no lugar da concatenação.
3. `interface-v2.js` troca a comparação com o retorno de `match()` pelo teste de prefixo já usado
   pelos demais consumidores do canal.

## Fora do escopo

- Alterar `arquivo_tipo_por_extensao()` ou a família exibida na coluna "Tipo" da listagem.
- Alterar qualquer consumidor do canal além da guarda quebrada do `interface-v2`.
- Detectar MIME por conteúdo (`finfo`): a resolução segue por extensão, como o resto do módulo.
- Fazer commit, push ou deploy remoto.

## Decisões de implementação

- **A função nasceu na biblioteca, não no módulo.** `arquivo.php` já abriga
  `arquivo_tipo_por_extensao()`, com o mesmo contrato (resolve por extensão, não toca no disco).
  Deixar o mapa dentro do `admin-arquivos` obrigaria a duplicá-lo no primeiro outro consumidor.
- **Resolução por extensão, não por `finfo`.** `mime_content_type()` exige o arquivo em disco e a
  extensão `fileinfo` ativa; o módulo inteiro resolve por extensão e a listagem monta itens sem
  abrir os arquivos. Trocar o critério aqui seria mudança de arquitetura, não correção de defeito.
- **Fallback `application/octet-stream`**, e não o antigo `file/<ext>`. É o valor neutro padrão para
  binário desconhecido; nenhum consumidor testa o prefixo `file/` (verificado por varredura).
- **A invariante de prefixo virou teste, não comentário.** Os seis consumidores decidem se aceitam
  o arquivo com `/^image\//`. O teste percorre a lista real de extensões de
  `arquivo_tipo_por_extensao()` e exige que cada família devolva o prefixo correspondente — assim,
  adicionar uma extensão lá e esquecer do mapa quebra o build em vez de quebrar o picker em silêncio.
- **A guarda estática ficou sem exceção para comentários.** A primeira versão do comentário no
  `interface-v2.js` citava o código defeituoso literalmente e disparava o próprio teste de
  regressão. O comentário foi reescrito em prosa: um guarda que precisa de exceção não protege.

## Contrato de validação

- `php -l` nos dois PHP e `node --check` no `interface-v2.js`.
- Teste PHP cobrindo o mapa, o fallback, a normalização de caixa e a invariante de prefixo.
- Teste JS demonstrando o defeito da guarda antiga, validando a nova e impedindo a reintrodução do
  padrão em todos os consumidores do canal.
- Suítes PHPUnit e Vitest sem regressão.
- Runtime local: MIME real na listagem e no payload despachado, com o BATCH-140 ainda funcionando.

## Evidências

- Lint: `php -l` **2/2**, `node --check` **1/1**.
- `ArquivoMimePorExtensaoTest`: **8/8** (97 asserções), incluindo a invariante sobre **29 extensões**.
- `interface-v2.imagepick-mime.test.js`: **6/6**.
- Suítes: PHPUnit **784/784** (3.423 asserções) e Vitest **363/363** (25 arquivos), sem regressão.
- Runtime local (Playwright + `c2f auth:cookie`), com o ambiente em modo desenvolvimento:
  - listagem: `asset-version.json` agora sai como **`application/json`** (antes `file/json`);
  - nenhum item com prefixo `file/` nem com `image/jpg` na listagem;
  - despacho em lote do BATCH-140 segue funcionando, com `tipo: "application/json"` no payload;
  - console **sem erros**.
- `git diff --check`: limpo.
- Nível 1 respeitado: nenhum commit, push ou deploy remoto.

## Notas

- O defeito do `interface-v2` era **silencioso e total**: o ramo de sucesso nunca executava, então o
  widget de imagem daquele arquivo recusava 100% das seleções. Não havia como o valor `'image/jpeg'`
  passar — o problema nunca foi o dado, e sim a comparação.
- Ambos os defeitos são anteriores ao BATCH-140; foram encontrados ao rastrear os consumidores do
  canal `postMessage` durante aquele lote e promovidos a intake próprio por decisão da chefia.
