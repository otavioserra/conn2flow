# BL-046 — Configuração tipada, segredos e isolamento multi-instância na v3

- **Tipo:** Architecture/Security/Operations/Developer Experience
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** carregamento de configuração, validação, segredos, `.env`, domínios e múltiplas instalações
- **Relacionados:** BL-020, BL-029, BL-031, BL-033, BL-034, BL-036, BL-038

## Evidência e problema

Hoje `gestor/config.php` concentra estado global, defaults e leituras de `$_ENV`. Módulos também consultam variáveis de ambiente diretamente, e o administrador de ambiente lê/escreve `.env`. Credenciais de banco, chaves criptográficas, integrações, e-mail e pagamentos compartilham esse mecanismo.

Os principais riscos são:

- iniciar com segredo obrigatório vazio ou configuração parcialmente inválida;
- divergência de nomes, tipos e defaults entre módulos;
- selecionar configuração pela combinação errada de host/caminho;
- vazar segredos em log, debug, backup ou interface administrativa;
- escrita não atômica do `.env` e perda durante atualização;
- dificultar testes por causa de globais mutáveis e dependências ocultas.

## Decisão proposta

Criar `C2FConfig` modular, tipado e imutável após o bootstrap. Somente adapters de origem leem ambiente/arquivo; serviços e módulos recebem objetos de configuração explícitos.

Separar:

- configuração não secreta da aplicação;
- referências a segredos;
- identidade da instalação/site/tenant;
- configuração de runtime e capacidades disponíveis;
- parâmetros de integração por módulo.

## Componentes planejados

- schema versionado de chaves, tipo, obrigatoriedade, default permitido e sensibilidade;
- loaders para ambiente, arquivo local e provider futuro, com precedência determinística;
- validador/preflight usado por web, CLI, instalador e atualizador;
- objetos menores por contexto, evitando uma classe gigante;
- redator central de valores sensíveis para logs/diagnóstico;
- editor administrativo baseado no schema, sem exibir segredo persistido;
- migração controlada de aliases legados para nomes técnicos ingleses.

## Segredos

- nunca guardar segredo real em Git, fixture, imagem ou documentação;
- falhar fechado quando chave criptográfica obrigatória estiver ausente/fraca;
- armazenar somente hash/HMAC quando o valor em claro não precisar ser recuperado;
- permitir rotação com versão/key id e janela de coexistência quando necessário;
- registrar quem alterou configuração sensível sem registrar seu valor;
- restringir permissões de arquivo e usar escrita temporária + rename atômico.

O `.env` pode continuar como origem suportada em hospedagem simples, mas deixa de ser a API consumida pelo domínio.

## Isolamento de instalações

- não confiar em `Host` sem validação/allowlist;
- vincular explicitamente site, caminho e configuração;
- impedir fallback silencioso para credenciais de outra instância;
- cookies, cache, locks, uploads e logs recebem namespace da instalação;
- tarefas CLI exigem site/ambiente explícito quando houver ambiguidade.

## Migração

1. inventariar `$_ENV`, globais e arquivos de configuração;
2. classificar chave por owner, tipo e sensibilidade;
3. introduzir facade compatível com métricas de uso legado;
4. migrar bootstrap e fronteiras críticas;
5. migrar módulos por ondas;
6. bloquear novas leituras diretas;
7. remover aliases somente após contador zero.

## Testes mínimos

- configuração válida, ausente, mal tipada e desconhecida;
- segredo vazio/fraco, rotação e redaction;
- seleção de site com hosts válidos, inválidos e conflitantes;
- edição concorrente e falha durante escrita atômica;
- merge de configuração em upgrade sem sobrescrever segredo local;
- paridade web/CLI/cron/worker/instalador;
- nenhum segredo em logs, exceções ou artefatos CI.

## Critérios de aceite

- schema único documenta todas as chaves suportadas;
- código novo não lê `$_ENV` diretamente fora dos adapters;
- bootstrap falha com diagnóstico seguro antes de executar parcialmente;
- instalações não compartilham acidentalmente cookies, cache, locks ou segredos;
- rotação e edição preservam disponibilidade e auditoria;
- logs e telas aplicam redaction central.

## Próxima ação

Promover inventário e ADR de fontes/precedência antes de refatorar `config.php`. A primeira implementação deve cobrir identidade da instalação, conexão de banco e segredos de sessão, mantendo facade temporária para a 2.9.x.
