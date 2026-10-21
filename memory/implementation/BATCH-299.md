---
id: "BATCH-299"
title: "Hardening de Infraestrutura Pós-PHP2Shell e Alerta de Segurança no Painel (REQ-291)"
status: "ready-for-intake"
date: "2026-10-21"
author: "arquiteto"
target_repo: "conn2flow"
summary_short: "Tratamento de pendências de infraestrutura pós-PHP2Shell: persistência de ip6tables, remoção do PHP 8.1 EOL, atualização do hestia-php, SSL Cloudflare no webmail e aviso de segurança no admin-environment."
---

# BATCH-299 — REQ-291

- **Branch Sugerida:** `feat/req-291` (no repositório `conn2flow`)
- **Requisição:** [req-291.md](../human-requests/req-291.md)

---

## 📋 Live Todo List

- [ ] **1. Persistência de Regras de Firewall no Prod**
  - [ ] Persistir regra de bloqueio IPv6 da porta 8083 (`ip6tables-save` / `netfilter-persistent`)
  - [ ] Testar sobrevivência após reinício ou recarga do firewall

- [ ] **2. Remoção do PHP 8.1 EOL nos Servidores**
  - [ ] Verificar pools em uso no Lab e Prod (`find /etc/php -name "*.conf"`)
  - [ ] Desinstalar pacotes `php8.1*` no Lab e no Prod
  - [ ] Validar que nenhum site foi desestabilizado

- [ ] **3. Acompanhamento do `hestia-php`**
  - [ ] Checar publicação de release do HestiaCP com PHP corrigido
  - [ ] Aplicar atualização do pacote `hestia-php` no Lab e Prod

- [ ] **4. Ajuste do SSL da Cloudflare para Webmail**
  - [ ] Ajustar modo SSL da Cloudflare para Full (strict)
  - [ ] Ativar proxy Cloudflare para `webmail.*` e validar resposta HTTP 200 do Roundcube

- [ ] **5. Diagnóstico de Segurança no Painel (`admin-environment`)**
  - [ ] Adicionar checagem de versões vulneráveis conhecidas em `admin-environment`
  - [ ] Exibir banner informativo instruindo upgrade caso a versão do PHP seja vulnerável
  - [ ] Testar renderização no painel
