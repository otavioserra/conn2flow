---
title: "Installation"
description: "How to always download the latest web installer, prepare the server, install Conn2Flow and get it ready to use."
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

# Installation

Conn2Flow is installed by a **web installer**: a small package you place in the site's public folder and open in the browser. It downloads the system (the *Gestor*), creates the database, generates the keys and the `.env`, creates the administrator user and, at the end, turns itself into the site's public entry point and deletes itself.

## 1. Download the latest installer

The installer is published on GitHub as `instalador.zip` (with `instalador.zip.sha256` next to it), in releases tagged `instalador-vX.Y.Z`.

**[Download the latest installer](https://github.com/otavioserra/conn2flow/releases?q=instalador-v&expanded=true)**

> [!NOTE]
> On the documentation website, the link above is automatically replaced by the direct download of the most recent `instalador.zip`. Do not use `releases/latest`: the repository publishes two series (`gestor-v*` and `instalador-v*`), and GitHub's "latest" is usually a Gestor release, which has no `instalador.zip`.

From the terminal, this command always fetches the latest version and checks its integrity:

```bash
TAG=$(curl -fsSL "https://api.github.com/repos/otavioserra/conn2flow/releases?per_page=50" \
  | grep -o '"tag_name": *"instalador-v[^"]*"' | head -1 | sed 's/.*"\(instalador-v[^"]*\)"/\1/')
echo "Version: $TAG"
curl -fsSLO "https://github.com/otavioserra/conn2flow/releases/download/$TAG/instalador.zip"
curl -fsSLO "https://github.com/otavioserra/conn2flow/releases/download/$TAG/instalador.zip.sha256"
echo "$(cat instalador.zip.sha256)  instalador.zip" | sha256sum -c -
```

## 2. Prepare the server

The installer **does not check the PHP version or extensions**: if something is missing, the error shows up in the middle of a step. Make sure beforehand:

| Item | Why |
|---|---|
| **PHP 8.3 or later** (the 2.x line is tested up to 8.5) | The Gestor uses syntax and functions from these versions |
| Extensions `mysqli`, `pdo_mysql`, `curl`, `zip`, `openssl`, `mbstring`, `gd` | Database (Gestor and installer), GitHub download, package extraction, RSA keys, text and images |
| **MySQL 8** or **MariaDB**, with a database and a user already created | The installer does not create the database, only the tables |
| **Apache** with `mod_rewrite` and `.htaccess` enabled, or **Nginx** | The whole site goes through a front controller |
| Outbound HTTPS to `api.github.com` and `github.com` | The installer downloads the Gestor from the latest `gestor-v*` release |
| Write permission in the public folder and in the **parent folder** of the installation path | The installer creates the Gestor folder and then rewrites its own folder |

> [!TIP]
> Install the Gestor **outside the public folder** (for example, `/home/user/conn2flow-gestor`, with the site in `/home/user/public_html`). Only `index.php` and `.htaccess` stay reachable from the web; code, `.env` and keys do not.

## 3. Upload and open the installer

1. Extract `instalador.zip` **inside the site's public folder** (or a subfolder, if the site will live at `/subfolder/`).
2. Open the address in the browser (`https://your-domain/`).
3. **Security key:** on the first visit, the installer writes a random key to `install-key.txt`, in its own folder, and asks for it. Open the file via FTP, SSH or the hosting file manager and paste its content. This prevents anyone from installing the system on your server before you.

> [!NOTE]
> Only one session at a time drives the installation. Another tab or person gets **HTTP 423** ("installation in progress") until the first one finishes, or after 30 minutes without activity.

## 4. Fill in the form

| Field | What to enter |
|---|---|
| Language | `pt-br` or `en`: becomes the site's `LANGUAGE_DEFAULT` |
| Database (host, name, user, password) | The connection is tested before any file is downloaded |
| Domain | The site's domain. It names the configuration folder and the cookies |
| Gestor installation folder | **Absolute** path where the Gestor will live. Its parent folder must exist and be writable |
| Web server | Apache or Nginx (preselected from what the installer detected) |
| SSL/HTTPS configured? | With "No", the forced HTTPS redirect is removed from `.htaccess` |
| Clean installation | **Drops every table** of the given database before installing |
| Administrator (name, e-mail, password) | Becomes user id 1. The login is the e-mail |

> [!CAUTION]
> "Clean installation" runs `DROP TABLE` on **every** table of the database, including those that do not belong to Conn2Flow. Only use it with a dedicated database.

## 5. What the installer does

1. **Validates** the fields, the path and the database connection.
2. **Downloads** `gestor.zip` from the most recent `gestor-v*` release (through the GitHub API) and checks `gestor.zip.sha256` before extracting. If the API fails (for example, because of the rate limit), it uses a fixed known version (`gestor-v2.10.1`) and logs a warning.
3. **Extracts** the Gestor into the installation folder and creates `autenticacoes/<domain>/` from `autenticacoes.exemplo/dominio/`:
   - `.env`, with the database, the domain, the detected `URL_RAIZ`, the language and random passwords for `OPENSSL_PASSWORD` and `USUARIO_HASH_PASSWORD`;
   - the RSA key pair in `chaves/gestor/` (`publica.key`/`privada.key`), protected by that password.
4. **Creates the database:**
   - runs `controladores/atualizacoes/atualizacoes-banco-de-dados.php` (migrations and initial data) and removes the Gestor's `db/` folder;
   - creates or updates the administrator (password in Argon2id, or bcrypt when unavailable);
   - leaves the administrator **already logged in** (persistent cookie).
5. **Publishes the site:** replaces the installer's `index.php` with the Gestor front controller, which points to the installation folder.
   - On Apache, it writes `.htaccess`, with `RewriteBase` when in a subfolder.
   - On Nginx, it writes `nginx-conn2flow.conf.example` into the installation folder and tests whether the rewrite reaches PHP.
6. **Cleans up:** deletes `src/`, `views/`, `assets/`, `lang/`, `temp/`, the log, the lock and the key. Finally, it opens the success page (`/instalacao-sucesso/`).

If the installation folder already contains a Gestor (`gestor.php` and `config.php`), the installer **refuses** to continue (HTTP 409).

### Nginx

`.htaccess` has no effect on Nginx. The sample file written to the installation folder contains the `location` block that sends every request to `index.php` with the `_gestor-caminho` parameter. Apply it to the virtual host and reload Nginx. The installer warns when the rewrite probe failed.

## 6. After installing

The site configuration lives in `<installation folder>/autenticacoes/<domain>/.env`. What the installer leaves to you:

- **E-mail:** disabled (`EMAIL_ACTIVE=false`), with `noreply@<domain>` and no password. Set `EMAIL_HOST`, `EMAIL_USER`, `EMAIL_PASS`, `EMAIL_PORT` and `EMAIL_SECURE` and turn `EMAIL_ACTIVE` on, otherwise sign-up, password recovery and forms send no messages. Only implicit TLS works: use port **465** with `EMAIL_SECURE=true` ([comunicacao.php](../reference/libraries/comunicacao.md)).
- **Captcha:** disabled (`CAPTCHA_PROVIDER` empty and `USUARIO_RECAPTCHA_ACTIVE=false`). For Cloudflare Turnstile, set `CAPTCHA_PROVIDER=cloudflare-turnstile`, `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY`; for Google reCAPTCHA, `CAPTCHA_PROVIDER=google-recaptcha` with real keys. The `USUARIO_RECAPTCHA_*` values written by the installer are **random** (placeholders), not valid Google keys.
- **HTTPS:** if you answered "No" and installed a certificate later, restore the redirect in `.htaccess` or in the server.
- **Scheduled routines:** the installer schedules **nothing**. Register one tick per frequency in the server scheduler (`php gestor/cron.php frequencia=diario` and so on); see the [cron.php library](../reference/libraries/cron.md).

Most of `.env` can also be edited in the admin panel, under *Environment*.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| Page asking for a "security key" | The step 3 protection: read `install-key.txt` |
| HTTP 423 | Another session is installing. Wait, or wait for 30 min without activity |
| Error downloading the Gestor | Server without outbound HTTPS to GitHub, or `curl` missing |
| "Parent directory does not exist" / "not writable" | Create the parent folder of the installation path and grant write permission to the PHP user |
| Site opens but routes return 404 | Rewrite disabled: `mod_rewrite`/`AllowOverride` on Apache, or the Nginx block not applied |

During installation, each step's details go to `installer.log`, in the installer folder. It is deleted at the end, along with the rest of the installer.

## See also

- [How to write and publish documentation](documentation.md)
