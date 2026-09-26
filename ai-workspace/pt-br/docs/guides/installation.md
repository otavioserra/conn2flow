---
title: "Instalação"
description: "Como baixar sempre a última versão do instalador web, preparar o servidor, instalar o Conn2Flow e deixá-lo pronto para uso."
section: guides
order: 10
sources:
  - gestor-instalador/index.php
  - gestor-instalador/src/Installer.php
  - gestor-instalador/src/InstallerGuard.php
  - gestor/autenticacoes.exemplo/dominio/.env
  - .github/workflows/release-instalador.yml
verified_at: 33ce53d9
---

# Instalação

O Conn2Flow é instalado por um **instalador web**: um pacote pequeno que você coloca na pasta pública do site e abre no navegador. Ele baixa o sistema (o *Gestor*), cria o banco, gera as chaves e o `.env`, cria o usuário administrador e, no fim, se transforma no ponto de entrada público do site e apaga a si mesmo.

## 1. Baixe a última versão do instalador

O instalador é publicado no GitHub como `instalador.zip` (com o `instalador.zip.sha256` ao lado), em releases com tag `instalador-vX.Y.Z`.

**[Baixar a última versão do instalador](https://github.com/otavioserra/conn2flow/releases?q=instalador-v&expanded=true)**

> [!NOTE]
> No site da documentação, o link acima é trocado automaticamente pelo download direto do `instalador.zip` mais recente. Não use `releases/latest`: o repositório publica duas séries (`gestor-v*` e `instalador-v*`), e o "latest" do GitHub costuma ser uma release do Gestor, que não tem o `instalador.zip`.

Pelo terminal, este comando sempre busca a versão mais recente e confere a integridade:

```bash
TAG=$(curl -fsSL "https://api.github.com/repos/otavioserra/conn2flow/releases?per_page=50" \
  | grep -o '"tag_name": *"instalador-v[^"]*"' | head -1 | sed 's/.*"\(instalador-v[^"]*\)"/\1/')
echo "Versão: $TAG"
curl -fsSLO "https://github.com/otavioserra/conn2flow/releases/download/$TAG/instalador.zip"
curl -fsSLO "https://github.com/otavioserra/conn2flow/releases/download/$TAG/instalador.zip.sha256"
echo "$(cat instalador.zip.sha256)  instalador.zip" | sha256sum -c -
```

## 2. Prepare o servidor

O instalador **não confere a versão do PHP nem as extensões**: se faltar algo, o erro aparece no meio de uma etapa. Garanta antes:

| Item | Por quê |
|---|---|
| **PHP 8.3 ou superior** (a linha 2.x é testada até o 8.5) | O Gestor usa sintaxe e funções dessas versões |
| Extensões `mysqli`, `pdo_mysql`, `curl`, `zip`, `openssl`, `mbstring`, `gd` | Banco (Gestor e instalador), download do GitHub, extração do pacote, chaves RSA, texto e imagens |
| **MySQL 8** ou **MariaDB**, com um banco e um usuário já criados | O instalador não cria o banco, só as tabelas |
| **Apache** com `mod_rewrite` e `.htaccess` ativos, ou **Nginx** | Todo o site passa por um front-controller |
| Saída HTTPS para `api.github.com` e `github.com` | O instalador baixa o Gestor da última release `gestor-v*` |
| Permissão de escrita na pasta pública e na **pasta-mãe** do caminho de instalação | O instalador cria a pasta do Gestor e depois reescreve a própria pasta |

> [!TIP]
> Instale o Gestor **fora da pasta pública** (por exemplo, `/home/usuario/conn2flow-gestor`, com o site em `/home/usuario/public_html`). Só o `index.php` e o `.htaccess` ficam acessíveis pela web; código, `.env` e chaves não.

## 3. Envie e abra o instalador

1. Extraia o `instalador.zip` **dentro da pasta pública** do site (ou de uma subpasta, se o site vai morar em `/subpasta/`).
2. Abra o endereço no navegador (`https://seu-dominio/`).
3. **Chave de segurança:** na primeira visita, o instalador grava uma chave aleatória em `install-key.txt`, na própria pasta, e pede essa chave. Abra o arquivo por FTP, SSH ou pelo gerenciador de arquivos da hospedagem e cole o conteúdo. Isso impede que alguém instale o sistema no seu servidor antes de você.

> [!NOTE]
> Só uma sessão por vez conduz a instalação. Outra aba ou pessoa recebe **HTTP 423** ("instalação em andamento") até a primeira terminar, ou até 30 minutos sem atividade.

## 4. Preencha o formulário

| Campo | O que informar |
|---|---|
| Idioma | `pt-br` ou `en`: vira o `LANGUAGE_DEFAULT` do site |
| Banco (host, nome, usuário, senha) | A conexão é testada antes de qualquer arquivo ser baixado |
| Domínio | O domínio do site. Nomeia a pasta de configuração e os cookies |
| Pasta de instalação do Gestor | Caminho **absoluto** onde o Gestor ficará. A pasta-mãe precisa existir e aceitar escrita |
| Servidor web | Apache ou Nginx (vem pré-selecionado pelo que o instalador detectou) |
| SSL/HTTPS configurado? | Com "Não", o redirecionamento forçado para HTTPS sai do `.htaccess` |
| Instalação limpa | **Apaga todas as tabelas** do banco informado antes de instalar |
| Administrador (nome, e-mail, senha) | Vira o usuário de id 1. O login é o e-mail |

> [!CAUTION]
> "Instalação limpa" executa `DROP TABLE` em **todas** as tabelas do banco, inclusive as que não são do Conn2Flow. Use só com um banco dedicado.

## 5. O que o instalador faz

1. **Valida** os campos, o caminho e a conexão com o banco.
2. **Baixa** o `gestor.zip` da release `gestor-v*` mais recente (pela API do GitHub) e confere o `gestor.zip.sha256` antes de extrair. Se a API falhar (por exemplo, por limite de requisições), usa uma versão fixa conhecida (`gestor-v2.10.1`) e registra o aviso no log.
3. **Extrai** o Gestor na pasta de instalação e cria `autenticacoes/<domínio>/` a partir de `autenticacoes.exemplo/dominio/`:
   - `.env`, com o banco, o domínio, o `URL_RAIZ` detectado, o idioma e senhas aleatórias para `OPENSSL_PASSWORD` e `USUARIO_HASH_PASSWORD`;
   - o par de chaves RSA em `chaves/gestor/` (`publica.key`/`privada.key`), protegido por essa senha.
4. **Cria o banco:**
   - executa `controladores/atualizacoes/atualizacoes-banco-de-dados.php` (migrações e dados iniciais) e remove a pasta `db/` do Gestor;
   - cria ou atualiza o administrador (senha em Argon2id, ou bcrypt se indisponível);
   - deixa o administrador **já logado** (cookie persistente).
5. **Publica o site:** troca o `index.php` do instalador pelo front-controller do Gestor, que aponta para a pasta de instalação.
   - No Apache, grava o `.htaccess`, com `RewriteBase` se estiver em subpasta.
   - No Nginx, grava `nginx-conn2flow.conf.example` na pasta de instalação e testa se o *rewrite* chega ao PHP.
6. **Limpa:** apaga `src/`, `views/`, `assets/`, `lang/`, `temp/`, o log, a trava e a chave. Por fim, abre a página de sucesso (`/instalacao-sucesso/`).

Se a pasta de instalação já tiver um Gestor (`gestor.php` e `config.php`), o instalador **se recusa** a continuar (HTTP 409).

### Nginx

O `.htaccess` não vale no Nginx. O arquivo de exemplo gravado na pasta de instalação traz o bloco `location` que manda todas as requisições para o `index.php`, com o parâmetro `_gestor-caminho`. Aplique-o ao *virtual host* e recarregue o Nginx. O instalador avisa se a sonda de *rewrite* falhou.

## 6. Depois de instalar

A configuração do site fica em `<pasta de instalação>/autenticacoes/<domínio>/.env`. Pontos que o instalador deixa para você:

- **E-mail:** vem desligado (`EMAIL_ACTIVE=false`), com `noreply@<domínio>` e sem senha. Configure `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS`, `EMAIL_PORT` e `EMAIL_SECURE` e ligue `EMAIL_ACTIVE`, senão cadastro, recuperação de senha e formulários não enviam mensagens.
- **Captcha:** vem desligado (`CAPTCHA_PROVIDER` vazio e `USUARIO_RECAPTCHA_ACTIVE=false`). Para Cloudflare Turnstile, defina `CAPTCHA_PROVIDER=cloudflare-turnstile`, `TURNSTILE_SITE_KEY` e `TURNSTILE_SECRET_KEY`; para Google reCAPTCHA, `CAPTCHA_PROVIDER=google-recaptcha` com chaves reais. Os valores `USUARIO_RECAPTCHA_*` gravados pelo instalador são **aleatórios** (preenchimento), e não chaves válidas do Google.
- **HTTPS:** se você respondeu "Não" e depois instalou um certificado, restaure o redirecionamento no `.htaccess` ou no servidor.
- **Rotinas automáticas:** o instalador **não** agenda nada. Registre no agendador do servidor um tick por frequência (`php gestor/cron.php frequencia=diario` etc.); veja [biblioteca cron.php](../reference/libraries/cron.md).

A maior parte do `.env` também pode ser editada pelo painel, em *Ambiente*.

## Solução de problemas

| Sintoma | Causa provável |
|---|---|
| Página pedindo "chave de segurança" | É a proteção do passo 3: leia `install-key.txt` |
| HTTP 423 | Outra sessão está instalando. Aguarde, ou espere 30 min sem atividade |
| Erro ao baixar o Gestor | Servidor sem saída HTTPS para o GitHub, ou `curl` ausente |
| "Diretório pai não existe" / "não é possível escrever" | Crie a pasta-mãe do caminho de instalação e dê permissão ao usuário do PHP |
| Site abre mas as rotas dão 404 | *Rewrite* inativo: `mod_rewrite`/`AllowOverride` no Apache, ou o bloco do Nginx não aplicado |

Durante a instalação, os detalhes de cada etapa vão para `installer.log`, na pasta do instalador. Ele é apagado no final, junto com o resto do instalador.

## Veja também

- [Como escrever e publicar a documentação](documentation.md)
