# BL-043 — Descoberta de conteúdo para IA e `llms.txt` na v3

- **Tipo:** Spike/Architecture/AI Readiness/Content Discovery
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** conteúdo público legível por LLMs, políticas de crawlers de IA e avaliação de `llms.txt`
- **Relacionados:** BL-025, BL-026, BL-027, BL-028, BL-034, BL-037, BL-040, BL-041, BL-042, BL-044

## Conclusão atual

Não há hoje um “sitemap XML para IA” universal separado do sitemap web. Crawlers e mecanismos de busca com IA podem consumir o sitemap XML normal e as páginas públicas. A proposta mais próxima da ideia citada é `/llms.txt`, publicada em 2024, mas ela é Markdown, curada e ainda deve ser tratada como experimental.

Portanto, a v3 deve adotar duas camadas complementares:

1. `sitemap.xml` padrão como catálogo completo de URLs públicas indexáveis;
2. `llms.txt` opcional como mapa editorial conciso para conteúdos especialmente úteis a LLMs em tempo de inferência.

Nenhuma das duas autoriza acesso a conteúdo protegido nem garante que um provedor de IA use ou cite o material.

## O que o `llms.txt` deve ser no Contao Flow

- arquivo Markdown na raiz pública da instalação/site, quando habilitado;
- H1 com nome do site, resumo curto e seções de links importantes;
- curadoria por coleção/categoria, não espelho de todas as páginas;
- links para URLs canônicas públicas, com descrições breves;
- gerado de uma projeção própria do BL-041, com opt-in editorial por página/coleção;
- versionado por um perfil de formato para permitir alterar a implementação se a proposta evoluir;
- validado para nunca listar preview, administração, autenticação, dados pessoais ou páginas protegidas.

Não gerar automaticamente um `llms-full.txt` com todo o conteúdo. Um corpus integral pode aumentar exposição de material licenciado, dados pessoais, segredos acidentais e custo de crawl. Qualquer exportação ampla exige decisão separada, limites e revisão jurídica/editorial.

## Representações legíveis por máquina

Antes de inventar um formato proprietário:

- garantir HTML semântico, server-rendered e acessível sem JavaScript para o conteúdo essencial;
- manter canonical, idioma, headings e dados estruturados coerentes;
- avaliar uma representação Markdown limpa somente para páginas explicitamente elegíveis;
- se houver variante Markdown, apontar para a mesma identidade/canonical e aplicar a mesma política de acesso/indexação;
- usar `Content-Type`, charset, cache e limites corretos;
- impedir que remoção de navegação transforme conteúdo não confiável em instruções privilegiadas para agentes.

O conteúdo publicado deve ser tratado como dados não confiáveis por qualquer agente que o consuma; `llms.txt` não é um canal de comando.

## Política para crawlers de IA

Criar configuração por site e finalidade, porque descoberta em busca e treinamento não são equivalentes:

- política geral para crawlers cooperativos;
- allow/deny explícito para crawlers de busca/inferência conhecidos;
- política separada para crawlers de treinamento quando o provedor distingue os agentes;
- geração do bloco correspondente em `robots.txt` sem hardcode espalhado;
- revisão periódica da lista de user-agents e documentação da data/fonte;
- firewall/CDN configurável quando um provedor publicar faixas verificáveis;
- nenhuma autorização de dados privados baseada em User-Agent ou IP de crawler.

Exemplo conceitual: para aparecer no ChatGPT Search, a documentação da OpenAI orienta permitir `OAI-SearchBot`. Isso não implica automaticamente permitir todo crawler de treinamento. A política precisa ser uma escolha do administrador do site.

## CMS versus AMS

Separar dois problemas:

- **descoberta de conteúdo:** sitemap, HTML/Markdown, JSON-LD, feeds e `llms.txt` públicos;
- **descoberta de capacidades do AMS:** APIs e ferramentas autenticadas para agentes criarem, revisarem ou publicarem conteúdo.

O segundo não deve ser resolvido por `llms.txt`. Futuras capacidades de agente devem reutilizar os casos de uso do BL-041, reference monitor, scopes, aprovação humana, idempotência e auditoria. OpenAPI/MCP ou outro protocolo só deve ser escolhido em ADR próprio e com threat model.

## Painel e governança editorial

- habilitar/desabilitar `llms.txt` por site;
- selecionar coleções/páginas e ordenar links;
- preview e diff antes de publicar;
- indicar por que uma página não é elegível;
- política de crawler por finalidade com texto explicativo;
- registro de última geração, checksum e histórico;
- alerta quando um item selecionado se tornar privado, noindex, expirado ou removido.

## Métricas e experimento

Como o formato ainda é uma proposta, sua adoção deve ser mensurável:

- acessos a `llms.txt` e variantes por user-agent, sem confiar nele para autorização;
- hits de crawlers nas URLs indicadas;
- referências/citações observadas quando houver fonte verificável;
- impacto de cache, banda e erros;
- comparação entre sites/ambientes com e sem o recurso;
- decisão posterior de manter, adaptar ou retirar sem afetar `sitemap.xml`.

## Testes mínimos

- arquivo válido conforme o perfil adotado e conteúdo determinístico;
- somente links absolutos/canônicos aprovados;
- remoção automática de página privada, noindex, expirada ou arquivada;
- escaping/normalização de títulos e descrições não confiáveis;
- múltiplos idiomas e sites sem mistura de host;
- crawler sem cookies recebe o mesmo conteúdo público essencial;
- `robots.txt`, sitemap e `llms.txt` não apresentam políticas contraditórias;
- desligar a feature remove o artefato/rota sem afetar SEO tradicional.

## Critérios de aceite do Spike

- a documentação chama `llms.txt` de proposta experimental, não de padrão oficial;
- sitemap XML continua sendo a infraestrutura primária de descoberta;
- administrador controla separadamente busca/inferência e treinamento;
- nenhuma informação privada é publicada por inferência de bot;
- formatos públicos de IA são adapters descartáveis sobre o domínio, não dependência do CRUD;
- existe métrica e estratégia de saída.

## Próxima decisão

Depois do sitemap e do modelo de indexabilidade estarem estáveis, executar PoC pequeno com `llms.txt` curado e revisar adoção real antes de torná-lo default.

## Referências de pesquisa

- Proposta `llms.txt`: <https://llmstxt.org/>
- OpenAI — disponibilidade no ChatGPT Search e `OAI-SearchBot`: <https://help.openai.com/en/articles/9237897-chatgpt-search>
- Robots Exclusion Protocol (RFC 9309): <https://www.rfc-editor.org/rfc/rfc9309.html>
