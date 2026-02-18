# CONN2FLOW - Cloudflare Tunnel para Desenvolvimento Local

## 📋 Visão Geral

O serviço **Cloudflare Tunnel (cloudflared)** permite expor o ambiente de desenvolvimento Docker local para a internet pública através de um domínio seguro, sem necessidade de port forwarding ou IP público. É utilizado principalmente para **testes de webhooks** (PayPal, Stripe, etc.) que exigem um endpoint HTTPS acessível externamente.

**URL pública:** `https://dev.conn2flow.com`

## 🏗️ Arquitetura

```
Internet
   │
   ▼
Cloudflare Edge (dev.conn2flow.com)
   │
   ▼ (QUIC/H2 — conexão reversa, sem abrir portas)
Docker: conn2flow-cloudflared
   │
   ▼ http://app:80  (rede interna Docker)
Docker: conn2flow-app  (Apache/PHP)
```

### Componentes

| Componente | Localização | Descrição |
|---|---|---|
| **Serviço Docker** | `dev-environment/docker/docker-compose.yml` | Serviço `cloudflared` com profile `tunnel` |
| **Config do Tunnel** | `dev-environment/data/cloudflared/config.yml` | Configuração de rotas (ingress rules) |
| **Credenciais** | `dev-environment/data/cloudflared/conn2flow-dev.json` | Credenciais do tunnel (⚠️ gitignored) |
| **DNS** | Cloudflare Dashboard | CNAME `dev.conn2flow.com` → tunnel UUID |

## 📁 Arquivos

### config.yml

Localizado em `dev-environment/data/cloudflared/config.yml`:

```yaml
tunnel: 33f0e9e4-8333-4966-82f7-6832450ed381
credentials-file: /etc/cloudflared/conn2flow-dev.json

ingress:
  - hostname: dev.conn2flow.com
    service: http://app:80
  - service: http_status:404
```

- **tunnel**: UUID do tunnel criado na Cloudflare
- **credentials-file**: Caminho dentro do container (montado via volume)
- **ingress**: Regras de roteamento — todo tráfego de `dev.conn2flow.com` vai para o container `app` na porta 80. O catch-all final retorna 404 para qualquer outro hostname.

### conn2flow-dev.json (Credenciais)

```json
{
  "AccountTag": "...",
  "TunnelSecret": "...",
  "TunnelID": "33f0e9e4-8333-4966-82f7-6832450ed381"
}
```

> ⚠️ Este arquivo está na pasta `dev-environment/data/` que é **gitignored** (`.gitignore` → `dev-environment/data/`). Nunca commite credenciais no repositório.

### docker-compose.yml (Serviço cloudflared)

```yaml
cloudflared:
  image: cloudflare/cloudflared:latest
  container_name: conn2flow-cloudflared
  restart: unless-stopped
  profiles:
    - tunnel
  command: tunnel --config /etc/cloudflared/config.yml --no-autoupdate run
  depends_on:
    - app
  networks:
    - conn2flow-network
  volumes:
    - ../data/cloudflared:/etc/cloudflared:ro
```

**Pontos importantes:**
- **`profiles: [tunnel]`** — O serviço **NÃO inicia automaticamente** com `docker compose up -d`. Requer `--profile tunnel`.
- **`command`** — Passa argumentos diretamente ao binário `cloudflared` (a imagem é distroless, sem shell `sh`/`bash`).
- **`depends_on: [app]`** — Garante que o Apache esteja rodando antes do tunnel.
- **`volumes`** — Monta a pasta com `config.yml` e credenciais em modo somente leitura.

## 🚀 Uso

### Iniciar o Tunnel

```bash
cd dev-environment/docker
docker compose --profile tunnel up -d
```

### Parar o Tunnel

```bash
cd dev-environment/docker
docker compose --profile tunnel stop
```

### Ver Logs

```bash
docker compose logs -f cloudflared
# ou
docker logs conn2flow-cloudflared -f
```

### Verificar Status

```bash
# Ver se o container está rodando
docker ps --filter name=conn2flow-cloudflared

# Testar acesso externo
curl -I https://dev.conn2flow.com
```

### Comportamento com `docker compose up -d`

O serviço cloudflared **NÃO** é incluído num `docker compose up -d` normal:

```bash
# Estes comandos NÃO iniciam o cloudflared:
docker compose up -d
docker compose restart

# SOMENTE estes comandos iniciam o cloudflared:
docker compose --profile tunnel up -d
```

Isso garante que ao reiniciar a máquina/Docker, o tunnel permanece **desativado**.

## 🔧 Troubleshooting

### Container sai imediatamente (exit code 1)

**Causa mais comum:** Credenciais ou config inválidos.

```bash
# Ver o erro
docker logs conn2flow-cloudflared

# Verificar que os arquivos existem
ls dev-environment/data/cloudflared/
# Deve conter: config.yml, conn2flow-dev.json
```

### HTTP 530 ao acessar dev.conn2flow.com

Significa que o tunnel não está conectado:

```bash
# Verificar status do container
docker ps -a --filter name=conn2flow-cloudflared

# Reiniciar
docker compose --profile tunnel restart cloudflared
```

### HTTP 502 — Bad Gateway

O Apache (`app`) pode não estar respondendo:

```bash
# Verificar se o app está rodando
docker ps --filter name=conn2flow-app

# Testar internamente
docker exec conn2flow-cloudflared wget -qO- http://app:80 2>&1 || echo "Falha"
```

### A imagem cloudflared é distroless

A imagem oficial `cloudflare/cloudflared` **não possui shell** (`sh`, `bash`) nem utilitários básicos (`ls`, `cat`, `wget`). Isso significa:

- ❌ `command: sh -c '...'` → **NÃO funciona**
- ❌ `docker exec -it conn2flow-cloudflared sh` → **NÃO funciona**
- ✅ `command: tunnel --config /etc/cloudflared/config.yml run` → **Funciona** (argumentos ao entrypoint `cloudflared`)

## 🔐 Segurança

- **Credenciais gitignored:** Pasta `dev-environment/data/` está no `.gitignore`
- **Somente leitura:** O volume é montado `:ro` (read-only)
- **Sem portas expostas:** O tunnel usa conexão reversa (outbound), não requer portas abertas no host
- **Uso temporário:** O profile garante que o tunnel só roda quando explicitamente solicitado

## 📝 Dados do Tunnel

| Propriedade | Valor |
|---|---|
| **Nome** | `conn2flow-dev` |
| **ID (UUID)** | `33f0e9e4-8333-4966-82f7-6832450ed381` |
| **Hostname** | `dev.conn2flow.com` |
| **Protocolo** | QUIC (automático) |
| **Team (Zero Trust)** | `conn2flow` |
| **Cloudflared versão** | `2026.2.0` (via Docker `latest`) |

## 🔗 Referências

- [Cloudflare Tunnel - Criar tunnel local](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/get-started/create-local-tunnel/)
- [Cloudflare Tunnel - Arquivo de configuração](https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/do-more-with-tunnels/local-management/configuration-file/)
- [Docker Compose Profiles](https://docs.docker.com/compose/profiles/)
- [Cloudflare Zero Trust Dashboard](https://one.dash.cloudflare.com/)

## 📅 Histórico

| Data | Ação |
|---|---|
| 2026-02-18 | Tunnel criado (`conn2flow-dev`) via `cloudflared tunnel create` no Windows |
| 2026-02-18 | DNS `dev.conn2flow.com` configurado via `cloudflared tunnel route dns` |
| 2026-02-18 | Config.yml criado, docker-compose corrigido (sem shell, com profiles) |
