---
id: "BATCH-298"
title: "Atualização de Segurança PHP2Shell no Lab, Prod, Docker e Composer (REQ-290)"
status: "in-progress"
date: "2026-10-21"
author: "executor"
target_repo: "conn2flow"
summary_short: "Mitigação emergencial das vulnerabilidades críticas PHP2Shell (CVE-2026-109618 e CVE-2026-109619). Atualização de pacotes nos servidores SSH (Lab e Prod), rebuild dos containers Docker locais e restrição de versão no composer.json do Core."
---

# BATCH-298 — REQ-290

- **Branch Sugerida:** `feat/req-290` (no repositório `conn2flow`)
- **Requisição:** [req-290.md](../human-requests/req-290.md)

---

## 📋 Live Todo List

- [ ] **1. Diagnóstico Inicial de Versões**
  - [ ] Verificar versão local do PHP (`php -v`)
  - [ ] Verificar versão no Lab via SSH (`ssh lab php -v`)
  - [ ] Verificar versão em Produção via SSH (`ssh prod php -v`)

- [ ] **2. Atualização dos Servidores Remotos (SSH)**
  - [ ] **Lab:** Atualizar pacotes PHP (`apt update && apt upgrade ...`), reiniciar serviços (`systemctl restart php*-fpm apache2`) e validar `php -v`
  - [ ] **Prod:** Atualizar pacotes PHP, reiniciar PHP-FPM / webserver e confirmar estabilidade de `conn2flow.com`

- [ ] **3. Atualização do Ambiente Docker Local (`dev-environment/docker`)**
  - [ ] Inspecionar `Dockerfile` e `Dockerfile.php85`
  - [ ] Recompilar containers com `docker compose build --no-cache`
  - [ ] Testar container e validar versão via `docker compose exec ... php -v`

- [ ] **4. Governança no Core (`conn2flow/composer.json`)**
  - [ ] Inserir restrição segura de versão mínima de PHP no `composer.json`
  - [ ] Executar suíte de testes unitários (`vendor/bin/phpunit`) para garantir regressão zero

- [ ] **5. Evidências e Fechamento**
  - [ ] Anotar versões finais instaladas em Lab e Prod
  - [ ] Commit explícito das alterações de arquivos (sem `git add -A`)

---

## Evidências — Diagnóstico (2026-10-21, ~16:20 UTC, executor)

Branch `feat/req-290` criada a partir de `main` (`c19151bc`).

| Alvo | PHP ativo (`php -v`) | Pacotes fpm instalados | Melhor disponível no repositório |
| --- | --- | --- | --- |
| Local (Windows CLI) | 8.5.8 | — | — |
| Lab (Ubuntu 22.04, HestiaCP, PPA ondrej) | 8.5.10 | 8.1.34 / 8.2.33 / 8.3.33 / 8.4.24 / 8.5.10 (+5.6–8.0) | 8.3.35 / 8.5.11 (`apt-cache madison`) |
| Prod (Ubuntu 22.04, HestiaCP, PPA ondrej) | 8.5.10 | 8.1.34 / 8.2.33 / 8.3.33 / 8.4.25 / 8.5.10 | 8.2.34 / 8.3.35 / 8.4.26 / 8.5.11 (cache apt) |
| Docker `php:8.3-apache` / `php:8.5-apache` | rebuild `--no-cache --pull` → **8.3.35** | — | tags `8.3.36-apache` / `8.5.12-apache` publicadas 15:38 UTC como índice vazio: `docker pull` → `no matching manifest for linux/amd64` |

- php.net confirma os releases de segurança 8.2.35 / 8.3.36 / 8.4.27 / 8.5.12 de 21/10/2026.
- **Bloqueio upstream:** o PPA ondrej ainda não publicou as versões corrigidas (Launchpad respondendo 503 no `apt-get update` do Lab) e as imagens oficiais do Docker ainda não têm build amd64. Rodar `apt upgrade` agora só levaria às versões anteriores ao patch, ainda vulneráveis.
- `conn2flow.com` → HTTP 200, `Server: cloudflare` (a Cloudflare anunciou regras de WAF iniciais para os vetores conhecidos).
- PHP 8.1 (instalado em Lab e Prod): fora de suporte; correção só por backport.
- **Composer:** `">=8.2.35"` não barra 8.3.35 nem 8.5.10 (são numericamente maiores). O `gestor/vendor/composer/platform_check.php` é versionado: uma restrição correta derrubaria com erro fatal toda instalação ainda sem patch, inclusive as nossas hoje. Decisão de arquitetura pendente.
- Nenhuma alteração feita em Lab, Prod ou containers ativos. A imagem de teste local foi removida.

## Evidências — Exposição da origem em Prod (2026-10-21, ~16:40 UTC)

- A origem `179.199.141.207` responde **200 (nginx)** nas portas 80 e 443 com `Host: conn2flow.com`, sem passar pela Cloudflare: o WAF pode ser contornado.
- O painel do Hestia na porta `8083` responde 200 em `0.0.0.0` e `[::]`. Roda `hestia-php` 8.5.9, também vulnerável.
- IPv6 `2a02:4780:6e:3d80::1`: política `INPUT ACCEPT`, sem regras; o firewall do Hestia só gerencia IPv4. Os sites só escutam no IPv4; no IPv6 só o 8083 fica exposto.
- Firewall do Hestia: as regras 2 (8083) e 9 (80,443) aceitam `0.0.0.0/0`. Não há ipsets.
- DNS: os três sites e os subdomínios `ia-parceria`, `dev.snapphoton` e `fsmjd.transformamp` são proxied pela Cloudflare. **Ficam fora do proxy (DNS-only):** `server.conn2flow.com`, `webmail.{conn2flow,snapphoton,transformamp}.com`, `webmail.server.conn2flow.com` e `mail.*`.
- A mitigação no firewall foi autorizada pelo humano, mas o comando de escrita remota foi negado pela política de permissões do agente. Nada foi alterado em Prod.

## Evidências — Mitigação de rede em Prod (2026-10-21, 13:31 hora do servidor, aplicada pelo humano)

O humano executou como root os comandos preparados pelo executor; o agente só verificou, em modo leitura.

- Backup em `/root/req-290/` (`iptables-before.rules`, `firewall-data-before/`).
- ipset `cloudflare` criado a partir de `https://www.cloudflare.com/ips-v4` (15 faixas, `AUTOUPDATE='yes'`).
- Regra 9: `80,443` só de `ipset:cloudflare`. Regra 11: `80,443` do IP do operador `189.123.91.205`. Regra 2: `8083` só de `189.123.91.205`.
- IPv6: `ip6tables -A INPUT -p tcp --dport 8083 -j DROP` (a política continua ACCEPT; a regra **não sobrevive a reboot**).
- Verificação pela Cloudflare: conn2flow.com 200, snapphoton.com 200, transformamp.com 200, ia-parceria 200, dev.snapphoton 302, fsmjd.transformamp 200.
- Efeito colateral esperado: `webmail.*` e `server.conn2flow.com` (DNS-only) passam a responder só para o IP do operador. IMAP, SMTP e POP não foram afetados.
- Pendências: ligar o proxy da Cloudflare em `webmail.*`; atualizar o PHP (sury) e o `hestia-php` quando os pacotes corrigidos saírem; reconstruir o Docker quando as imagens amd64 8.3.36/8.5.12 forem publicadas; decidir a restrição no composer.
