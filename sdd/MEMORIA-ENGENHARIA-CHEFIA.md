# Memória de Engenharia — Chefia

> **Propósito**: Este diário de bordo é reservado ao registro de diretrizes, decisões de design, preferências e restrições técnicas estabelecidas pelo **Engenheiro Chefe Humano** durante o desenvolvimento do projeto Conn2Flow. Ele serve como canal de persistência de contexto e memória de pair programming entre chats.

---

## Preferências de Design & Estilo

- **Consistência de Editores (CodeMirror)**:
  - Editores CodeMirror adicionados em abas ou containers extras (como a aba "Código do Widget") devem herdar a mesma identidade do Editor HTML principal.
  - **Especificações de Layout**: Tema `'tomorrow-night-bright'`, modo `'htmlmixed'` (com `htmlMode: true`), indentação de `4` espaços, e altura fixada em `800px` (`setSize('100%', 800)`).
  - **Dedup de Editores**: Sempre verificar a existência de um contêiner `.CodeMirror` irmão adjacente no DOM antes de re-inicializar a biblioteca para prevenir duplicação de instâncias ao alternar abas.

---

## Convenções de Código

- **Fluxo de Versionamento (Commits & Pushes)**:
  - **REGRA CRÍTICA**: Nunca realizar commits (`git commit`) ou envios (`git push`) de forma autônoma ou automática sob nenhuma circunstância.
  - Sempre aguardar a autorização/solicitação explícita do Engenheiro Chefe Humano antes de executar comandos que alterem o histórico remoto do Git.

---

## Restrições Técnicas

- **Placeholder e Notação de Variáveis**:
  - A formatação oficial de placeholders de itens no template do módulo de destaques é `[[item#NOME_DA_VARIAVEL]]` (sem arrobas nas pontas).
  - As expressões regulares (regex) no backend (PHP) e frontend (JS) de renderização ou extração de variáveis devem sempre refletir esta notação para que os valores reais das publicações sejam preenchidos em runtime.

---

## Regras de Negócio e Gestão do SDD

- **Tratamento de Brainstorms & Ideias Futuras**:
  - Funcionalidades, ideias e conceitos de segurança discutidos em brainstorms (como o planejamento de 2FA e Social Login) **não** devem ser colocados em arquivos de requisições de intake ativos (`human-requests`).
  - Devem ser mantidos apenas como tópicos/linhas marcados em status `blocked` (ou `planejado`) no **[BATCH-INDEX.md](file:///c:/Users/otavi/OneDrive/Documentos/GIT/conn2flow/sdd/implementation/BATCH-INDEX.md)**, evitando desvios ou ambiguidades em relação ao escopo das tarefas ativas.

---

## Notas Gerais

- As notas deste arquivo foram registradas pelo agente IA sob autorização expressa do Engenheiro Chefe Humano na sessão de 04/06/2026 para reter aprendizados de relacionamento de pair programming.
