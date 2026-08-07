# BL-028 — Migração contínua da documentação bilíngue para a v3

- **Tipo:** Epic/Documentation/Migration/I18n
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** documentação técnica e manuais `pt-br`/`en` afetados pela evolução 3.x
- **Dependência:** BL-027 e os contratos promovidos dos BL-011 a BL-026

## Decisão de sequenciamento

Não deixar toda a documentação para o final. A estratégia recomendada é híbrida:

- cada batch altera imediatamente a documentação do contrato, módulo ou procedimento que mudou;
- o batch não precisa revisar documentos não relacionados;
- no fim de cada onda ocorre revisão de paridade e integração;
- antes do release candidate há uma auditoria completa, consolidação e remoção de contradições.

Se a atualização ficar somente para o fim, o contexto se perde e exemplos incorretos se acumulam. Se tentarmos reescrever tudo antes do código, a documentação ficará especulativa e precisará ser refeita. A atualização incremental mantém os documentos utilizáveis durante toda a branch v3.

## Regra por batch

Toda requisição/batch v3 deve declarar:

```text
Documentation impact: none | update | new | deprecate
Affected doc_ids: [...]
Locales required: [pt-BR, en]
User manual impact: yes | no
Migration guide impact: yes | no
```

`none` exige justificativa. Mudança de API, schema, comportamento visível, configuração, segurança, instalação, atualização ou extensão nunca deve ser presumida como `none`.

## Princípios de tradução

- identificadores de código, classes C2F, tabelas v3, campos, eventos e chaves ficam em inglês nos dois idiomas;
- explicação e instrução ficam em português ou inglês conforme a árvore;
- os dois documentos devem ter equivalência semântica, não tradução palavra por palavra;
- exemplos executáveis devem representar o mesmo contrato nos dois idiomas;
- criar/revisar os dois idiomas no mesmo batch quando ambos forem obrigatórios;
- tradução por IA exige validação técnica e revisão humana para documentos críticos;
- não anunciar locale cuja documentação/manual esteja incompleto sem marcar a lacuna.

## Preservação da v2

- não reescrever changelog ou documento histórico para fingir que a v3 sempre existiu;
- marcar conteúdo 2.9.x como `legacy`/`historical` ou aplicável à linha 2.x;
- criar guia explícito de migração 2.9.x → 3.x;
- exemplos atuais apontam para v3, mas aliases/adapters legados permanecem documentados enquanto suportados;
- após a promoção da v3, manter acesso à documentação final da 2.9.x durante a janela LTS.

## Matriz de atualização

| Programa v3 | Documentação mínima |
| --- | --- |
| Banco v2 e schema inglês | arquitetura, conexão, query/bind, transações, `SchemaMap`, migrations, instalador e guia de migração |
| Interface v2 | request/response, AJAX, sessão, CSRF, autorização, renderer e extensão de módulos |
| `C2FI18n` | recursos globais/modulares, chaves, placeholders, fallback, catálogo frontend e tooling |
| Tailwind/Fomantic | design system, componentes, preview, build, compatibilidade e remoção do legado |
| `C2FDataGrid` | contrato backend/frontend, paginação, filtros, autorização, acessibilidade e migração DataTables |
| `C2FUpload`/Uppy 5 | contrato, políticas, segurança, UI, eventos, limites e migração blueimp |
| PHP 8.5/releases | requisitos, preflight, canais, atualização, rollback e troubleshooting |
| Docker v3/legacy | perfis, versões, portas, volumes, wrapper, backup, matriz e troubleshooting |
| Inglês técnico | glossário, aliases, schema, eventos, APIs e guia de extensão |
| Cada módulo | referência técnica, manual administrativo, dados/recursos, testes e diferenças 2.x/3.x |

## Ondas

### Onda 0 — Preparação

- executar catálogo/baseline do BL-027;
- corrigir README, totais e navegação;
- parear semanticamente paths traduzidos;
- criar templates e checklist de documentação;
- registrar gaps atuais, especialmente banco v2, interface v2 e PHP 8.5 em `pt-br`.

### Onda 1 — Fundações

- documentar branch/release PHP 8.5;
- documentar o Docker v3 padrão e o perfil legado 2.9.x no mesmo batch dos BL-029/BL-030;
- glossário/naming C2F e política de inglês técnico;
- banco v2, `SchemaMap` e modularização;
- `C2FI18n` e contrato de documentação bilíngue.

### Onda 2 — Plataforma de interface

- interface v2, Tailwind, componentes interativos;
- `C2FDataGrid` e `C2FUpload`/Uppy;
- atualizar guias de criação de módulos para que novos consumidores nasçam na v3.

### Onda 3 — Piloto e módulos

- `admin-paginas-v2` como primeiro conjunto completo: arquitetura, referência técnica, manual e migração;
- cada onda do core atualiza somente seus módulos/documentos relacionados;
- overlays privados mantêm documentos próprios e aparecem no mapa composto.

### Onda 4 — Consolidação por marco

- ao final de alpha, beta e RC, revisar paridade, links, exemplos e status;
- fechar lacunas cruzadas entre arquitetura, módulo e manual;
- atualizar troubleshooting a partir dos testes de upgrade e feedback.

### Onda 5 — Auditoria final

- validar todos os 234 documentos/catalogados;
- remover instruções atuais contraditórias com a v3 ou marcá-las como legacy/historical;
- testar snippets e caminhos críticos;
- revisar tecnicamente `pt-br`/`en`;
- congelar documentação do RC junto do código e publicar matriz de cobertura.

## Definition of Done documental por módulo

- referência técnica descreve propósito, rotas, permissões, eventos e dependências;
- API/contratos, banco, recursos e migrations refletem o código da mesma revisão;
- manual cobre fluxos administrativos suportados;
- exemplos usam nomes técnicos ingleses e APIs C2F atuais;
- diferenças/compatibilidade 2.x estão explícitas;
- `pt-br` e `en` possuem equivalência e links válidos;
- `last_verified_commit` aponta para o código efetivamente validado;
- testes/fixtures relevantes estão ligados no mapa.

## Critérios de aceite

- todo batch v3 declara impacto documental;
- contratos e módulos são atualizados no mesmo batch funcional;
- documentação nunca fica mais de uma onda atrás do código;
- alpha/beta/RC possuem relatórios de cobertura e lacunas conhecidos;
- release estável não contém documento `current` descrevendo API/schema removido;
- guias de instalação, atualização, rollback e migração são testados ponta a ponta;
- agentes conseguem localizar documentação atual de um módulo pelo catálogo do BL-027.

## Próxima ação

Após o spike do BL-027, escolher `admin-paginas-v2` como piloto documental completo e incorporar o campo `Documentation impact` ao primeiro template de batch da v3.
