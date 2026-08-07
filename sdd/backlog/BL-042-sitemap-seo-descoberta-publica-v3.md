# BL-042 — Sitemap, SEO técnico e descoberta pública na v3

- **Tipo:** Epic/Architecture/SEO/Content Discovery
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** geração e entrega de `sitemap.xml`, `robots.txt`, canonical/hreflang e metadados públicos
- **Relacionados:** BL-014, BL-021, BL-023, BL-025, BL-027, BL-028, BL-034, BL-040, BL-041, BL-043, BL-044

## Objetivo

Fazer o Contao Flow anunciar de forma segura e consistente todas as páginas públicas que podem ser indexadas, independentemente de terem sido criadas pelo `admin-paginas`, `publisher-pages`, módulos, plugins ou importações.

O protocolo XML Sitemap é o baseline consolidado. Ele complementa links internos e não garante indexação. O arquivo deve conter URLs canônicas absolutas, ser UTF-8 e respeitar os limites do protocolo.

## Fonte de verdade

O gerador não deve inferir publicação apenas por `status='A'`. A query `ListDiscoverablePages` do BL-041 deve aplicar conjuntamente:

- estado efetivamente publicado;
- janela de publicação vigente;
- visibilidade pública (`sem_permissao` é somente adapter legado);
- política indexável e ausência de `noindex`;
- URL canônica válida no host/site atual;
- resposta esperada `200`, sem página de login, soft-404 ou redirect;
- exclusão de páginas administrativas/sistema não públicas, previews e duplicatas;
- idioma e relacionamento entre traduções;
- remoção imediata da projeção quando a página for despublicada, expirar ou for arquivada.

Não permitir opção capaz de colocar uma página protegida no sitemap. A proteção do conteúdo continua obrigatória mesmo que `robots.txt` esteja configurado incorretamente.

## Artefatos públicos

### Sitemap XML

- expor `sitemap.xml` ou um índice de sitemaps no caminho público controlado pela instalação;
- se a instalação ocupa um subdiretório, declarar claramente o escopo desse caminho e oferecer integração com a raiz do host quando ela for administrada por outro sistema;
- gerar sitemaps separados por host/site e, quando útil, idioma ou tipo de conteúdo;
- dividir acima de 50.000 URLs ou 50 MB descomprimidos;
- usar `<loc>` e `<lastmod>`; não depender de `<priority>`/`<changefreq>`;
- incluir extensões oficiais para imagens, vídeos, notícias e `hreflang` somente quando houver dados confiáveis;
- validar XML, escaping, URLs absolutas e schema em CI/testes.

### `lastmod` confiável

`data_modificacao` atual pode mudar por operações técnicas. Planejar `content_modified_at` ou projeção equivalente, atualizada somente por mudança significativa de conteúdo, structured data, URL ou links relevantes. Um timestamp artificial em toda geração reduz a confiança do crawler.

### `robots.txt`

- referenciar o sitemap por URL absoluta;
- permitir configuração por site/ambiente sem editar arquivo de release manualmente;
- bloquear crawling administrativo como redução de ruído, nunca como autorização;
- não bloquear a leitura de uma página que depende de `noindex`, pois o crawler precisa acessá-la para ver a diretiva;
- tratar políticas de crawlers de IA separadamente conforme BL-043.

### Canonical e idiomas

- emitir `rel="canonical"` autorreferente para a página canônica;
- manter sitemap, redirect e canonical apontando para a mesma URL;
- modelar grupo de traduções e gerar `hreflang`, incluindo retorno recíproco e `x-default` quando aprovado;
- não usar fallback silencioso de idioma para criar conteúdo duplicado indexável;
- caminhos antigos ficam fora do sitemap e respondem com redirect permanente para o destino vigente.

### Metadados e dados estruturados

Adicionar ao modelo editorial, conforme o tipo de conteúdo:

- title e meta description próprios, com preview e validação;
- Open Graph e cartões sociais;
- JSON-LD Schema.org por componentes tipados (`WebPage`, `Article`, `Organization`, breadcrumbs etc.);
- `X-Robots-Tag` para recursos não HTML e respostas que não devem ser indexadas;
- validação que impeça JSON-LD arbitrário de quebrar o documento ou injetar script.

Dados estruturados ajudam máquinas a entender entidades, mas não substituem sitemap, HTML acessível nem política de crawler.

## Estratégia de geração

Não reescrever o arquivo inteiro de forma síncrona dentro de cada `POST` de CRUD.

```text
transação de página
  -> evento/outbox após commit
  -> marcar projeção de descoberta como dirty
  -> worker/job idempotente consolida mudanças
  -> escreve arquivo temporário
  -> valida XML e URLs
  -> troca atômica pelo sitemap vigente
  -> atualiza ETag/Last-Modified e métricas
```

- coalescer rajadas de updates/importações;
- manter último sitemap válido se a nova geração falhar;
- fornecer comando administrativo de rebuild e diagnóstico;
- permitir leitura dinâmica/cacheada como fallback quando o filesystem não for gravável;
- agendamento/expiração deve invalidar a projeção mesmo sem alguém editar a página naquele momento.

## Painel administrativo planejado

- status da última geração, duração, quantidade de URLs e checksum;
- erros por URL e motivo de exclusão;
- preview do sitemap sem publicar;
- rebuild autorizado e protegido contra abuso;
- teste de URL: publicada, pública, indexável, canônica e presente/ausente;
- configuração por site/host, sem colocar segredos em recursos públicos.

## Testes mínimos

- criação, edição significativa, alteração de caminho, publicação, despublicação, exclusão e expiração;
- publisher e página comum produzem o mesmo contrato de descoberta;
- página protegida/noindex/draft nunca aparece;
- canonical, redirect, sitemap e `hreflang` coerentes;
- múltiplos idiomas, hosts e instalação em subdiretório;
- corte 50.000 URLs/50 MB e sitemap index;
- caracteres Unicode/escaping XML;
- rebuild concorrente e falha antes da troca atômica;
- cache/ETag e ausência de query pesada por request público;
- verificação do sitemap por crawler sem cookies, integrada ao BL-040.

## Critérios de aceite

- sitemap deriva exclusivamente do contrato central de páginas publicáveis;
- todas as mutações relevantes invalidam sua projeção por evento confiável;
- somente URLs públicas, indexáveis, canônicas e vigentes são anunciadas;
- `lastmod` reflete mudança significativa real;
- arquivos são válidos, cacheáveis e substituídos atomicamente;
- `robots.txt` referencia o sitemap e não é usado como controle de acesso;
- documentação pt-br/en explica configuração, limites e diagnóstico.

## Próxima decisão

Promover o contrato de elegibilidade e o protótipo de projeção depois do ADR do BL-041. Integrações externas de notificação pertencem ao BL-044.

## Referências de pesquisa

- Sitemap Protocol: <https://www.sitemaps.org/protocol.html>
- Google — Build and submit a sitemap: <https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap>
- Google — robots.txt specification: <https://developers.google.com/crawling/docs/robots-txt/robots-txt-spec>
- Google — canonical URLs: <https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls>
