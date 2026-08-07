# BL-044 — Eventos, indexação e side effects da publicação na v3

- **Tipo:** Architecture/Reliability/Integration/Observability
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** outbox, rebuilds, cache, feeds e notificações externas após publicação
- **Relacionados:** BL-011, BL-020, BL-029, BL-030, BL-032, BL-037, BL-041, BL-042, BL-043, BL-047, BL-048

## Problema

Publicar uma página pode exigir atualizar sitemap/`llms.txt`, invalidar cache, atualizar feeds, notificar indexadores e produzir métricas. Executar tudo dentro do request administrativo:

- aumenta a latência percebida;
- faz uma indisponibilidade externa quebrar o CRUD;
- cria estados parciais quando o banco confirma e o arquivo falha;
- multiplica chamadas durante importações e atualizações em lote;
- perde eventos se hooks rodarem antes do commit ou o processo terminar abruptamente.

## Decisão proposta

Adotar outbox transacional no domínio do BL-041. O commit grava a página e o evento na mesma transação; um worker/runner idempotente do BL-048 processa efeitos externos depois. A outbox preserva o fato de domínio; a plataforma comum fornece claim, retry, agendamento, dead-letter e operação.

```text
PagePublished/PageChanged/PageUnpublished
  -> outbox persistente
  -> dispatcher com lease/retry
      -> projeção sitemap
      -> projeção llms.txt
      -> invalidação de cache
      -> RSS/Atom/WebSub quando habilitado
      -> IndexNow quando habilitado
      -> métricas/auditoria operacional
  -> dead-letter + alerta após limite
```

O desenho deve funcionar em hospedagem compartilhada sem daemon permanente: cron, execução oportunística limitada e comando CLI são transports possíveis. O contrato não pode depender exclusivamente de queue server externo.

## Requisitos da outbox

- evento versionado, ID único, aggregate ID/version, site/host, data e correlation ID;
- payload mínimo e sem HTML completo ou segredo;
- publicação somente após commit;
- consumidor idempotente com chave por evento/handler;
- retry com backoff e jitter, limite e dead-letter;
- lease/lock com recuperação após processo morto;
- ordenação por agregado quando eventos conflitantes exigirem;
- coalescência de rebuilds para rajadas;
- retenção, limpeza e métricas sem apagar evidência antes do prazo;
- replay administrativo com capability e auditoria.

## Consumidores planejados

### Sitemap e `llms.txt`

Marcar projeções dirty, consolidar e publicar atomicamente conforme BL-042/BL-043. Uma importação de milhares de páginas produz um rebuild consolidado, não milhares de reescritas.

### Cache/CDN

- invalidar a URL antiga e a nova quando o caminho mudar;
- invalidar dependências conhecidas, como índices, menus ou página inicial;
- usar tags/surrogate keys quando a infraestrutura oferecer;
- não transformar purge externo em pré-condição para salvar conteúdo;
- registrar idade máxima aceitável para conteúdo despublicado.

### Feeds

Avaliar RSS/Atom por publisher/coleção e feed geral de novidades. Feeds carregam apenas conteúdo público, respeitam canonical/idioma e podem funcionar como complemento incremental ao sitemap.

### IndexNow opcional

IndexNow permite notificar URLs adicionadas, alteradas ou removidas a mecanismos participantes. Planejar adapter opcional:

- chave por host armazenada como segredo e arquivo de verificação público controlado;
- submissão em lote, limites, retry e observabilidade;
- enviar somente URL canônica do host associado;
- tratar `200/202` como recebimento, não garantia de indexação;
- não apresentar IndexNow como integração universal nem substituto do sitemap;
- nenhum retry infinito dentro do request do usuário.

Google Search Console/Bing Webmaster podem receber integração administrativa separada para submissão e diagnóstico, respeitando credenciais e limites próprios. Não usar APIs de indexação fora dos tipos de conteúdo oficialmente suportados.

## Agendamento e expiração

Uma página agendada precisa mudar de projeção mesmo sem request humano. Planejar scheduler que:

- publique/expire no instante lógico com timezone definido;
- use consulta indexada por próxima transição;
- seja idempotente em execuções repetidas ou atrasadas;
- emita os mesmos eventos dos comandos manuais;
- reconcilie periodicamente estado materializado e relógio para recuperar jobs perdidos.

O roteador continua conferindo a janela em tempo de request como barreira de correção; projeções e cache não podem tornar conteúdo expirado acessível.

## Observabilidade

- fila pendente, idade do evento mais antigo e taxa de processamento;
- sucesso/falha/retry por handler e site;
- duração e tamanho de sitemap/feed;
- submissões externas e códigos de resposta;
- páginas publicadas ausentes da projeção e URLs projetadas não publicáveis;
- correlation ID da ação administrativa até o side effect;
- health check que não exponha URLs privadas, payloads ou chaves.

## Testes mínimos

- crash antes/depois do commit e exatamente os efeitos esperados via idempotência;
- eventos duplicados, fora de ordem e replay;
- dois workers concorrentes;
- indisponibilidade prolongada de filesystem, cache e IndexNow;
- importação em massa coalescida;
- mudança de caminho invalida URL anterior e gera 301 coerente;
- agendamento/expiração com relógio controlado e job atrasado;
- dead-letter e recuperação autorizada;
- operação por cron/CLI em hospedagem compartilhada;
- paridade MySQL/PostgreSQL para outbox/locks.

## Critérios de aceite

- nenhum endpoint externo participa da transação de salvar/publicar;
- nenhum evento confirmado é perdido após crash recuperável;
- handlers são idempotentes e observáveis;
- sitemap/IA/cache/feeds recebem a mesma verdade de publicação;
- importações em lote não causam tempestade de rebuild/requisições;
- falhas externas aparecem no painel/log sem falsamente marcar o CRUD como não salvo;
- a arquitetura funciona sem infraestrutura de fila obrigatória, mas permite adapter futuro.

## Próxima decisão

Promover uma PoC da outbox junto do primeiro caso de uso de publicação do BL-041. Implementar primeiro um consumidor local de sitemap; adicionar IndexNow somente depois de provar retry, segredo, coalescência e observabilidade.

## Referências de pesquisa

- IndexNow — documentação do protocolo: <https://www.indexnow.org/documentation>
- Sitemap Protocol: <https://www.sitemaps.org/protocol.html>
- Google — sitemap, RSS/Atom e WebSub: <https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap>
