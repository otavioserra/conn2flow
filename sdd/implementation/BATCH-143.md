# BATCH-143 — 404 em imagens estáticas com hífen/espaço e colisão de upload sem espaço

- **Status**: implemented-pending-homologation
- **Intake**: `sdd/human-requests/req-140.md`
- **Data de abertura**: 2026-08-28
- **Classificação**: bugfix / controlador estático, módulo `admin-arquivos` e biblioteca `arquivo.php`

## Objetivo

Fazer o controlador de arquivos estáticos servir os arquivos legados cujo nome em disco tem espaço
mas cuja URL publicada já vem sanitizada com hífen, e cortar a origem do problema: o desempate de
colisão do upload deixa de gravar espaço no nome.

## Diagnóstico confirmado no código

O defeito tem duas metades que se encaixam:

1. `admin_arquivos_ajax_upload_file()` montava o desempate como `$nomeBase . ' (' . $i . ')'`, com um
   espaço deliberado. O arquivo nascia `Ela (1).webp`.
2. Todo consumidor que publica a URL (`interface.php`, `publisher-pages`, widgets) passa o caminho
   por `arquivo_caminho_relativo_seguro()` → `arquivo_nome_sanitizar()`, que troca espaço por hífen
   (regra do BATCH-100). A página passa a apontar para `Ela-(1).webp`.

O controlador resolvia o caminho só por `realpath()` exato: o nome pedido não existia e a resposta
era 404 para um arquivo que estava no disco. Medido em produção no intake e reproduzido aqui no
runtime local antes da correção (ver Evidências).

## Slice aprovado

1. **Módulo 1 — controlador estático**: fallback no ramo de `contents-path`, sem tocar no ramo de
   `assets/` e `modulos/`, que não recebem nome de usuário.
2. **Módulo 2 — `admin-arquivos`**: desempate de colisão passa a nascer já em conformidade com a
   sanitização.
3. **Módulo 3 — testes**: cobertura unitária das duas metades, incluindo as recusas do fallback.

## Fora do escopo

- Renomear em massa os arquivos legados que já estão no disco com espaço.
- Alterar `arquivo_nome_sanitizar()` ou a regra do BATCH-100.
- Estender o fallback aos ramos de `assets/` e `modulos/`.
- Commit, push ou deploy remoto (Nível 1).

## Decisões de implementação

- **A correspondência é pelo resultado da sanitização, não por troca adivinhada de hífen por
  espaço.** O intake sugeria `preg_replace('/-(?=\(\d+\))/', ' ', ...)` ou "substituição controlada
  de hífens". Duas razões levaram ao mecanismo mais forte: um nome com hífen real *e* espaço
  (`Foto-Final de Praia.webp`) não é alcançável por nenhuma das duas trocas sugeridas; e tentar as
  combinações de hífen custa 2^n testes de disco numa string que o atacante controla. Comparar
  `arquivo_nome_sanitizar($entradaFísica) === $segmentoPedido` acha o arquivo exatamente quando ele
  é o que geraria aquela URL — nem mais, nem menos. Registrado como DEC-119.
- **A resolução é segmento a segmento**, então `mini/Ela-(1).webp` e diretórios com espaço
  (`Minha Pasta/`) caem no mesmo mecanismo, sem código dedicado à miniatura.
- **Duas guardas antes de listar diretório**: o fallback inteiro só entra se o caminho tiver hífen
  (a sanitização só *produz* hífen), e cada segmento repete a checagem antes do `scandir`. As
  varreduras automáticas de 404 (`/wp-login.php`, `/.env`) param sem custo de I/O.
- **O fallback descobre o nome; não autoriza o envio.** O retorno continua passando por
  `arquivo_estatico_resolver_autorizado()`, que é quem garante o containment sob a base.
- **Retorna `false` quando não houve divergência**, para não duplicar o trabalho da busca direta.
- **`rawurldecode` entra como variante, não como substituto.** A reescrita do gestor usa a flag `[B]`
  e o PHP já recebe o caminho decodificado — a Tentativa 1 do intake é inócua neste ambiente, mas
  cobre servidor de terceiro sem essa flag. A variante decodificada só entra na lista depois de
  passar pela mesma guarda de traversal, então `%2e%2e%2f` continua recusado.
- **O desempate mora na biblioteca, não no módulo** (`arquivo_nome_colisao()`), ao lado de
  `arquivo_nome_sanitizar()` — mesmo precedente do `arquivo_mime_por_extensao()` no BATCH-141. O
  resultado passa pela própria sanitização para ficar idempotente: um nome-base terminado em hífen
  produziria `base--(1)`, que a sanitização colapsaria de volta para `base-(1)`, reabrindo a
  divergência pela porta dos fundos.

## Arquivos tocados

| Arquivo | Mudança |
| --- | --- |
| `gestor/bibliotecas/arquivo.php` | `arquivo_nome_colisao()` |
| `gestor/modulos/admin-arquivos/admin-arquivos.php` | loop de colisão usa a função da biblioteca |
| `gestor/controladores/arquivo-estatico/arquivo-estatico.php` | `arquivo_estatico_caminho_variantes()`, `arquivo_estatico_entrada_por_nome_sanitizado()`, `arquivo_estatico_resolver_nome_sanitizado()` e o fallback no ramo de `contents` |
| `tests/Unit/PHP/AdminArquivosSegurancaTest.php` | 4 casos do desempate + 1 de blindagem ponta a ponta |
| `tests/Unit/PHP/ArquivoEstaticoNomeSanitizadoTest.php` | novo, 14 casos |

## Contrato de validação

- `php -l` nos três PHP de produção e nos dois de teste.
- Testes focados novos falhando antes e passando depois.
- Suítes completas sem regressão (PHPUnit e Vitest).
- Runtime HTTP local provando 404 **antes** e 200 **depois** nas mesmas URLs.
- Upload real autenticado provando a sequência de nomes gravada em disco.

## Evidências

Registradas em `sdd/validation/VALIDATION-CHECKLIST.md#batch-143`.

## Limitações conhecidas

A guarda por hífen deixa de fora um caso: nome físico cuja única divergência é a apara das pontas
feita pela sanitização (`-foto.webp` no disco, publicado como `foto.webp`). Ele não nasce do
upload — que já grava o nome sanitizado — e cobri-lo custaria uma listagem de diretório em todo
404 do site. Registrado no comentário da função; fora do escopo deste lote, que trata o espaço.


Arquivos legados continuam no disco com espaço no nome; o fallback os serve, mas cada requisição
deles paga uma listagem do diretório. Uma normalização em massa dos nomes já gravados encerraria o
custo — é mudança de dados, não de código, e fica para intake próprio se a chefia quiser.
