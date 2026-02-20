# PHP 8.5 — Instalação Local (Windows) + VS Code

## 1. Download do PHP 8.5.1

Versão recomendada: **VS17 x64 Non Thread Safe** (NTS) — ideal para CLI e VS Code.

**Link direto:**
```
https://windows.php.net/downloads/releases/php-8.5.1-nts-Win32-vs17-x64.zip
```

> **Pré-requisito:** [Visual C++ Redistributable for VS 2015-2022 (x64)](https://aka.ms/vs/17/release/vc_redist.x64.exe)

---

## 2. Instalação

```powershell
# 1. Criar pasta
mkdir C:\tools\php85

# 2. Extrair o ZIP para C:\tools\php85

# 3. Copiar php.ini base
copy C:\tools\php85\php.ini-development C:\tools\php85\php.ini

# 4. Ativar extensões no php.ini (descomentar/adicionar):
#    extension_dir = "ext"
#    extension=pdo_mysql
#    extension=pdo_pgsql
#    extension=mysqli
#    extension=pgsql
#    extension=mbstring
#    extension=gd
#    extension=zip
#    extension=curl
#    extension=openssl
#    extension=fileinfo
#    extension=exif

# 5. Adicionar ao PATH do sistema (PowerShell Admin)
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\tools\php85", "Machine")

# 6. Verificar
php -v
# PHP 8.5.1 (cli) (built: Dec 17 2025 ...)
```

---

## 3. Configuração do VS Code

### 3.1 settings.json (Workspace)

Adicionar ao `.vscode/settings.json` do workspace:

```jsonc
{
    // PHP 8.5 CLI
    "php.validate.executablePath": "C:\\tools\\php85\\php.exe",
    
    // Intelephense — apontar para PHP 8.5
    "intelephense.environment.phpVersion": "8.5.1",
    "intelephense.runtime": "C:\\tools\\php85\\php.exe",
    
    // PHP Debug (Xdebug) — opcional
    "php.debug.executablePath": "C:\\tools\\php85\\php.exe"
}
```

### 3.2 Múltiplas versões (PHP 8.4 + 8.5)

Se quiser manter ambas versões:

```jsonc
{
    // Usar 8.5 como padrão no projeto
    "php.validate.executablePath": "C:\\tools\\php85\\php.exe",
    "intelephense.environment.phpVersion": "8.5.1",
    
    // Terminal integrado — alternar via profile
    "terminal.integrated.profiles.windows": {
        "PHP 8.5": {
            "path": "powershell.exe",
            "env": { "PATH": "C:\\tools\\php85;${env:PATH}" }
        },
        "PHP 8.4": {
            "path": "powershell.exe",
            "env": { "PATH": "C:\\tools\\php84;${env:PATH}" }
        }
    }
}
```

---

## 4. Verificação de funcionalidades PHP 8.5

```php
<?php
// Testar que PHP 8.5 está funcionando

// 1. Pipe operator
$resultado = "  Hello World  " |> trim(...) |> strtolower(...);
echo $resultado; // "hello world"

// 2. Clone with (readonly classes)
readonly class Ponto {
    public function __construct(
        public float $x,
        public float $y,
    ) {}
}
$p1 = new Ponto(1.0, 2.0);
$p2 = clone $p1 with { y: 3.0 };
echo $p2->y; // 3.0

// 3. array_first / array_last
$arr = [10, 20, 30];
echo array_first($arr); // 10
echo array_last($arr);  // 30

// 4. #[NoDiscard]
#[\NoDiscard("O retorno não deve ser ignorado")]
function calcular(): int { return 42; }

// 5. Enums com match
echo PHP_VERSION; // 8.5.1
```

Salvar como `test-php85.php` e executar:

```powershell
C:\tools\php85\php.exe test-php85.php
```

---

## 5. Extensões VS Code recomendadas

| Extensão | ID | Notas |
|---|---|---|
| PHP Intelephense | `bmewburn.vscode-intelephense-client` | IntelliSense PHP |
| PHP Debug | `xdebug.php-debug` | Xdebug integration |
| PHP DocBlocker | `neilbrayfield.php-docblocker` | PHPDoc |

---

## Notas

- **PHP 8.4.8** continua em `C:\tools\php84\php.exe` (não remover)
- **Docker** usa `php:8.5-apache` (Dockerfile.php85) — independente do local
- O **banco-v2.php** usa features do PHP 8.5 (pipe operator, clone with, #[NoDiscard])
- Se necessário usar temporariamente PHP 8.4 localmente, alterar apenas o `php.validate.executablePath`
