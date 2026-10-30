---
id: "BL-032"
title: "BL-032 — Hierarquia de Usuários Pai/Filho (Multi-Usuários por Conta) e Cotas de Sub-Usuários por Perfil"
status: "IN-DISCUSSION"
date: "2026-10-29"
author: "architect"
target_repo: "conn2flow"
summary_short: "Estrutura nativa no Core para usuário titular (pai) gerenciar usuários vinculados (filhos) com limite definido por perfil de usuário."
summary_medium: "Permite que um assinante/organização crie e gerencie sub-usuários (ex.: profissionais de uma clínica ou equipe) respeitando o teto de assentos configurado em usuarios_perfis."
---

# BL-032 — Hierarquia de Usuários Pai/Filho (Multi-Usuários por Conta) e Cotas de Sub-Usuários por Perfil

- **Tipo:** Epic / Core Architecture / Identity & Access Management (IAM)
- **Status:** IN-DISCUSSION
- **Prioridade:** Média (Planejado para expansão pós-lançamento inicial do Snap Photon V2.0 e SaaS multi-equipe)
- **Criado em:** 2026-10-29
- **Origem:** Diretriz do Engenheiro Chefe durante o planejamento da arquitetura do Snap Photon V2.0 (`lumix`)

---

## 1. Contexto e Necessidade

Atualmente, a tabela `usuarios` do Core `conn2flow` opera sob o modelo de contas individuais independentes vinculadas a um `id_usuarios_perfis`.
Em produtos SaaS construídos sobre o Conn2Flow (como o **Snap Photon V2.0** para clínicas de fotobiomodulação ou projetos multi-equipe no `conn2flow-site`), surge a necessidade de uma **Conta Titular (Usuário Pai / Organização)** poder cadastrar e gerenciar **Usuários Vinculados (Usuários Filhos)** — por exemplo, outros profissionais de saúde da mesma clínica ou secretárias/recepcionistas — compartilhando o mesmo ambiente/tenant de dados sob permissões controladas.

---

## 2. Escopo Arquitetural Proposto no Core (`conn2flow`)

### 2.1 Evolução da Tabela `usuarios`
- Adicionar coluna opcional `id_usuarios_pai` (`INT NULL`, FK para `usuarios.id_usuarios`, indexada) indicando o titular da organização/conta à qual aquele sub-usuário pertence;
- Adicionar função helper canônica no Core `usuario_organizacao_id($id_usuario)` que retorna `id_usuarios_pai ?? id_usuarios`, permitindo que qualquer módulo ou API externa isole o tenant pela conta-mãe de forma transparente;
- Se o Usuário Pai tiver sua assinatura suspensa ou inativada (`status != 'A'`), o guardião de autenticação do Core propaga a restrição de acesso aos Usuários Filhos vinculados.

### 2.2 Limite de Sub-Usuários por Perfil (`usuarios_perfis`)
- Estender a configuração de **Perfis de Usuário (`usuarios_perfis`)** para suportar a definição de **quantos usuários filhos (`max_sub_users`)** um usuário daquele perfil (plano) pode criar e manter ativos;
- Permitir definir quais perfis filhos são permitidos para cada perfil pai (ex.: um titular com perfil `clinica-pro` pode criar sub-usuários com perfil `profissional-equipe` ou `recepcao`, mas nunca um perfil `admin`).

### 2.3 Painel de Autoatendimento de Equipe ("Meus Usuários / Minha Equipe")
- Criar interface em Tailwind CSS (alinhada ao `/perfil-usuario/`) para que o Usuário Pai possa:
  * Visualizar quantos assentos de sub-usuários estão em uso versus o limite do seu `usuarios_perfis`;
  * Convidar/criar novos usuários filhos, redefinir senha, alternar papel (`profissional` vs `recepção`) e ativar/desativar o acesso de membros da sua equipe;
  * Auditar ações realizadas por cada sub-usuário.

---

## 3. Estratégia de Transição Sem Bloqueio

- Enquanto este item permanece no backlog do Core, os projetos satélites (como o `lumix` V2.0) operam com a estrutura atual do Core (1 usuário por conta, onde `organization_id = user_id`), já mantendo o campo `organization_id` separado de `user_id` nas novas tabelas clínicas para que a ativação futura deste `BL-032` seja 100% retrocompatível e sem necessidade de reescrever queries.
