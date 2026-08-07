# BL-022 — Modernização de uploads com C2FUpload e Uppy 5

- **Tipo:** Architecture/Migration/Security/UX
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Decisão aprovada pelo usuário em 2026-08-07:** Uppy 5 como motor frontend interno; protocolo, UI e backend próprios do Conn2Flow
- **Escopo inicial:** `gestor/modulos/admin-arquivos`, assets compartilhados e consumidores privados
- **Relacionados:** BL-011, BL-012, BL-013, BL-016, BL-017, BL-019, BL-021

## Regra de governança

Este documento registra análise e proposta. Não autoriza substituir a biblioteca, instalar pacotes ou alterar o módulo. A implementação depende de promoção explícita para requisição humana e batches.

## Diagnóstico

O módulo `admin-arquivos` usa `blueimp/jQuery File Upload 10.32.0` e carrega:

- `jquery.ui.widget.js`;
- `jquery.iframe-transport.js`;
- `jquery.fileupload.js`;
- CSS do plugin.

A versão 10.32.0 foi publicada em 2021 e o repositório oficial foi arquivado em abril de 2026, tornando-se read-only. Diferentemente do DataTables, não existe uma nova major moderna do mesmo projeto que retire jQuery e continue sua evolução.

O uso efetivo do core é limitado a:

- seleção múltipla e drag-and-drop;
- fila sequencial;
- envio multipart/XHR;
- progresso individual e agregado;
- cancelar individual/todos;
- preview local de imagens;
- metadados de destino e categorias;
- tratamento de sucesso, falha e sessão expirada.

Não há uso atual de upload em chunks ou retomada após recarregar a página. O manifesto limita o core a 20 MB por arquivo, embora textos de interface ainda informem 10 MB.

## Consumidores e impacto de composição

- Core: `gestor/modulos/admin-arquivos` é o único carregador direto encontrado.
- `conn2flow-site`: `gestor/modulos/arquivos` também carrega o asset fornecido pelo core e possui fluxo mais complexo, com limite de 50 MB, cotas, banco, arquivos 3D, MIME/magic bytes e miniaturas.
- Lumix/Transformamp: nenhum carregamento direto do blueimp encontrado; uploads próprios devem apenas ser avaliados como futuros consumidores do contrato comum.

O asset antigo não pode ser removido enquanto o overlay `conn2flow-site` ainda depender dele.

## Comparação de alternativas

### Manter blueimp 10.32.0

**Vantagem:** nenhuma migração imediata.

**Desvantagens:** projeto arquivado, jQuery/jQuery UI/iframe transport, ausência de evolução e incompatibilidade com a direção Tailwind/JavaScript moderno. Não recomendado para a v3.

### Uppy 5 — aprovado

Uppy é modular, MIT, ativo e permite usar apenas `@uppy/core` como orquestrador e `@uppy/xhr-upload` como transporte multipart. Possui restrições, fila, progresso, cancelamento, retry, eventos, internacionalização e opção futura de uploads resumíveis por tus.

**Estratégia Conn2Flow:** não usar o Dashboard visual no primeiro momento. `C2FUpload` renderiza toda a UI Tailwind e traduz estado/eventos do Uppy. Isso reduz acoplamento visual e bundle.

## Decisões fechadas

- Uppy 5 será o motor frontend interno do `C2FUpload`;
- os módulos consumirão somente o contrato público do Conn2Flow e não importarão Uppy diretamente;
- a interface será própria, construída com Tailwind, sem adotar o Dashboard visual do Uppy no primeiro release;
- o pacote inicial será `@uppy/core` + `@uppy/xhr-upload`;
- o primeiro protocolo será multipart/XHR, um arquivo por request;
- `@uppy/tus`, chunks e retomada ficam condicionados a requisito posterior de arquivos grandes ou redes instáveis;
- a versão exata será fixada no lockfile e os assets serão locais/reproduzíveis.

Continuam em discussão o detalhamento do contrato backend, políticas por contexto, armazenamento, compensação, métricas e divisão dos batches. Por isso o item ainda não foi promovido para implementação.

### FilePond

Possui UI polida, responsiva/acessível, previews, plugins e chunks. Entretanto, é mais opinativo sobre markup/CSS e seu protocolo de chunks exige endpoints próprios de process/revert/restore. É boa alternativa de contingência, porém menos alinhada ao design system Tailwind controlado pelo Conn2Flow.

### Dropzone

Oferece drag-and-drop, previews, progresso, cancelamento e chunks, mas a documentação pública e evolução recente são menos convincentes que Uppy para uma nova fundação. Não é a primeira escolha.

### Motor totalmente próprio

O escopo atual poderia ser implementado com `input[type=file]`, Drag and Drop API, `XMLHttpRequest.upload`, `FormData` e cancelamento. O bundle seria mínimo, mas fila, retry, concorrência, acessibilidade e estados passariam a ser mantidos integralmente pelo projeto. Uppy Core fornece essa máquina de estados sem impor a interface.

## Arquitetura alvo

```text
Módulo
  -> C2FUploadConfig (políticas, destino lógico e metadados permitidos)
  -> componente C2FUpload (Tailwind, acessibilidade e lifecycle)
  -> adapter Uppy (fila/estado/progresso/cancelamento)
  -> transporte XHR multipart inicialmente
  -> endpoint C2FUpload próprio
  -> UploadService (validação, quota, armazenamento e pós-processamento)
```

Módulos não devem importar Uppy, construir `$_FILES` esperado ou conhecer detalhes de tus/XHR. A troca futura de transporte ou motor deve ocorrer dentro dos adapters.

## Frontend proposto

### Pacotes iniciais

- `@uppy/core`;
- `@uppy/xhr-upload`.

Adicionar `@uppy/tus` apenas quando houver requisito aprovado de retomada/grandes arquivos. Não usar o bundle completo nem CDN.

### Responsabilidades de C2FUpload

- input nativo com progressive enhancement;
- dropzone acessível e navegável por teclado;
- fila com nome, tamanho, tipo declarado, preview e estado;
- iniciar/cancelar/repetir individual e em lote;
- progresso individual/agregado;
- limite de quantidade, tamanho individual/total e tipos como orientação de UX;
- lifecycle `mount/unmount` idempotente em páginas/AJAX/iframe;
- CSRF e sessão integrados ao cliente HTTP comum;
- erro tipado: validação, quota, autenticação, rede, servidor e pós-processamento;
- revoke de object URLs de preview para não vazar memória;
- mensagens pt-BR/en e componentes Tailwind/`data-c2-*`.

Validação do navegador melhora UX, mas nunca é autoridade de segurança.

## Contrato backend proposto

### Request multipart v1

- campo de arquivo canônico, um arquivo por request inicialmente;
- `requestId` e `uploadId` opaco emitido/validado pelo servidor quando necessário;
- destino lógico, nunca caminho absoluto;
- metadados em allowlist, como categorias/pasta;
- CSRF, sessão e permissão da ação.

### Response

- `status`, `requestId` e `fileId`/identificador opaco;
- nome original e nome final;
- tamanho, MIME detectado, hash e URL autorizada;
- preview/miniatura quando pronto;
- warnings de pós-processamento;
- erro estável com código, mensagem segura e campo afetado.

Não devolver `$_FILES`, caminhos físicos, stack trace ou detalhes internos.

## Hardening obrigatório do servidor

O backend atual melhorou traversal e extensões perigosas, mas ainda precisa de uma política comum:

1. normalizar a estrutura de `$_FILES` e validar `UPLOAD_ERR_*` antes de usar campos;
2. cruzar limite do módulo com `upload_max_filesize`, `post_max_size` e `max_file_uploads`;
3. validar tamanho real e reservar quota antes do processamento;
4. preferir allowlist por contexto; denylist de extensões não é suficiente;
5. detectar MIME com `finfo`/magic bytes e comparar com extensão/política;
6. tratar SVG/HTML/XML e outros conteúdos ativos: sanitizar, bloquear ou servir como attachment/origem isolada;
7. guardar arquivos em staging não executável antes de movê-los ao destino;
8. gerar nome interno/ID opaco e resolver colisão de modo atômico;
9. calcular hash e, quando aplicável, deduplicar apenas sem vazar existência entre usuários;
10. aplicar transação/compensação entre arquivo, miniatura, categorias e banco;
11. limitar taxa, concorrência, quantidade e tempo de processamento;
12. limpar temporários/staging abandonados;
13. considerar varredura antimalware por adapter/hook para ambientes que exigirem;
14. servir conteúdo com `nosniff`, Content-Disposition e CSP apropriados;
15. registrar auditoria sem nome/caminho sensível desnecessário.

## Upload resumível/chunks

Não implementar no primeiro lote somente porque Uppy suporta. Com 20–50 MB, multipart XHR reduz risco e permite paridade rápida.

Quando houver requisito de arquivos grandes ou redes instáveis, avaliar `@uppy/tus` e servidor tus compatível. A adoção requer:

- upload IDs vinculados a usuário/sessão/política;
- offset e tamanho verificados pelo servidor;
- reserva/liberação de quota;
- expiração e coleta de chunks;
- checksum/integridade;
- finalização idempotente;
- proteção contra enumeração e apropriação de upload alheio;
- proxy/webserver preparado para PATCH/HEAD e limites adequados.

## Sequenciamento da migração

1. caracterizar core e overlay privado com fixtures reais;
2. definir contratos `C2FUpload`, `UploadPolicy`, `UploadResult`, armazenamento e pós-processamento;
3. extrair/hardenizar `UploadService` no backend;
4. criar PoC Uppy Core + XHRUpload + UI Tailwind própria;
5. migrar `admin-arquivos` mantendo o endpoint antigo por adapter temporário;
6. validar picker/iframe, categorias, pastas, preview, miniaturas e sessão expirada;
7. migrar `conn2flow-site/modulos/arquivos`, preservando 3D, cotas, hashes e banco;
8. inventariar outros uploads para possível adoção do contrato;
9. remover blueimp, jQuery UI Widget, iframe transport e CSS somente após zerar consumidores;
10. manter testes de contrato para permitir trocar Uppy.

## PoC e métricas

- múltiplos arquivos, retry, cancelamento e remoção durante envio;
- 0 byte, limite exato, acima do limite e request excedendo `post_max_size`;
- nomes Unicode, duplicados, múltiplas extensões e paths maliciosos;
- MIME/extensão divergentes e formatos ativos;
- 401/403/CSRF, timeout, offline e resposta fora de ordem;
- mobile, teclado, leitor de tela e iframe picker;
- uso de memória com previews e lote grande;
- tempo de upload, processamento/miniatura e número de requests;
- core 20 MB e overlay 50 MB.

## Complexidade estimada

Assumindo uma pessoa experiente e sem tus no primeiro release:

- PoC Uppy/Tailwind: 3–5 dias;
- contrato e backend seguro compartilhado: 3–6 semanas;
- migração/validação de `admin-arquivos`: 1–2 semanas;
- migração do overlay privado complexo: 2–4 semanas;
- estabilização, acessibilidade e remoção do legado: 1–2 semanas.

Total indicativo: **7–14 semanas**, divisível em batches independentes. Upload resumível acrescentaria uma frente própria.

## Critérios de aceite para futura implementação

- nenhuma dependência blueimp, jQuery UI Widget ou iframe transport;
- UI 100% Tailwind e API pública `C2FUpload` sem tipos Uppy;
- arquivos e metadados são validados pelo servidor, não pelo browser;
- caminhos físicos e `$_FILES` não aparecem na resposta;
- core e overlay privado possuem paridade e testes de composição;
- falhas não deixam arquivo, miniatura, quota ou banco inconsistentes;
- assets Uppy são pinados, locais e reproduzíveis;
- motor/transporte podem ser substituídos atrás dos contratos.

## Referências

- blueimp arquivado: <https://github.com/blueimp/jQuery-File-Upload>
- Uppy Core: <https://uppy.io/docs/uppy/>
- Uppy XHRUpload: <https://uppy.io/docs/xhr-upload/>
- Uppy tus: <https://uppy.io/docs/tus/>
- FilePond: <https://pqina.nl/filepond/>
- FilePond server/chunks: <https://pqina.nl/filepond/docs/api/server/>

## Próxima ação

Promover primeiro um batch de caracterização + contrato/backend. O PoC Uppy/Tailwind pode avançar em paralelo quando as primitives do BL-016/BL-017 estiverem disponíveis.
