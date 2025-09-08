# Sistema FTP Multi-Domínio para Hospedagem

## 🎯 Objetivo
Este sistema implementa um servidor FTP que mapeia diretamente cada domínio da hospedagem para um usuário FTP correspondente, permitindo gerenciamento simplificado de arquivos.

## 🏗️ Arquitetura

### Estrutura de Diretórios
```
sites/
├── localhost/          # Domínio principal
│   ├── public_html/     # Arquivos web acessíveis
│   └── home/           # Arquivos privados
├── site1.local/        # Domínio adicional 1
│   ├── public_html/
│   └── home/
└── site2.local/        # Domínio adicional 2
    ├── public_html/
    └── home/
```

### Mapeamento FTP → Domínio
- **Usuário FTP `localhost`** → Pasta raiz: `sites/localhost/`
- **Usuário FTP `site1.local`** → Pasta raiz: `sites/site1.local/`
- **Usuário FTP `site2.local`** → Pasta raiz: `sites/site2.local/`

## 🔐 Credenciais de Acesso

| Domínio | Usuário FTP | Senha | Pasta Raiz |
|---------|-------------|-------|------------|
| localhost | `localhost` | `localhost123` | `/sites/localhost/` |
| site1.local | `site1.local` | `site1.local123` | `/sites/site1.local/` |
| site2.local | `site2.local` | `site2.local123` | `/sites/site2.local/` |

## 🚀 Como Usar

### 1. Conexão via Cliente FTP
```bash
# Exemplo com curl
curl -u localhost:localhost123 ftp://localhost/ --list-only

# Upload de arquivo
curl -u localhost:localhost123 -T arquivo.php ftp://localhost/public_html/
```

### 2. Conexão via FileZilla
- **Servidor:** `localhost` (ou IP do servidor)
- **Porta:** `21`
- **Usuário:** Nome do domínio (ex: `localhost`)
- **Senha:** Nome do domínio + `123` (ex: `localhost123`)

### 3. Estrutura de Pastas no FTP
Quando conectado, você verá:
```
/                       # Raiz do domínio
├── public_html/        # Arquivos acessíveis via web
├── home/              # Arquivos privados
└── README-FTP.txt     # Instruções específicas
```

## 🔧 Gerenciamento de Permissões

### Problema Conhecido
Arquivos enviados via FTP são criados com permissão `600` (apenas proprietário), impedindo acesso web.

### Solução Manual
Após upload via FTP, ajuste as permissões:
```bash
# No container app
docker exec conn2flow-app chmod 644 /var/www/sites/DOMINIO/public_html/arquivo.ext
```

### Script Automatizado
Execute o script de correção de permissões:
```bash
# Corrigir permissões de todos os arquivos web
docker exec conn2flow-app find /var/www/sites -name "*.php" -exec chmod 644 {} \;
docker exec conn2flow-app find /var/www/sites -name "*.html" -exec chmod 644 {} \;
docker exec conn2flow-app find /var/www/sites -name "*.css" -exec chmod 644 {} \;
docker exec conn2flow-app find /var/www/sites -name "*.js" -exec chmod 644 {} \;
```

## 🌐 Integração Web + FTP

### Fluxo de Trabalho
1. **Upload via FTP** → Arquivo vai para `public_html/`
2. **Correção de permissões** → `chmod 644`
3. **Acesso via Web** → `http://localhost/arquivo.ext`

### Teste Completo
```bash
# 1. Upload via FTP
echo "Teste integração" > teste.txt
curl -u localhost:localhost123 -T teste.txt ftp://localhost/public_html/

# 2. Correção de permissões
docker exec conn2flow-app chmod 644 /var/www/sites/localhost/public_html/teste.txt

# 3. Acesso via web
curl http://localhost/teste.txt
```

## 🔍 Diagnóstico

### Verificar Status do FTP
```bash
# Status dos containers
docker-compose ps

# Logs do FTP
docker logs conn2flow-ftp --tail 20

# Testar conexão
curl -u localhost:localhost123 ftp://localhost/ --list-only
```

### Verificar Permissões
```bash
# No container FTP
docker exec conn2flow-ftp ls -la /home/vsftpd/localhost/

# No container Web
docker exec conn2flow-app ls -la /var/www/sites/localhost/public_html/
```

## 📝 Logs e Monitoramento

### Logs do FTP
```bash
# Logs em tempo real
docker logs -f conn2flow-ftp

# Logs do vsftpd
docker exec conn2flow-ftp tail -f /var/log/vsftpd/vsftpd.log
```

## 🛠️ Configurações Avançadas

### Adicionar Novo Domínio
1. Criar estrutura de pastas:
```bash
mkdir -p sites/novodominio.com/{public_html,home}
echo "Site novo funcionando" > sites/novodominio.com/public_html/index.php
```

2. Reiniciar container FTP para detectar automaticamente:
```bash
docker-compose restart ftp
```

3. Novo usuário será criado: `novodominio.com` / `novodominio.com123`

## 🚨 Troubleshooting

### Problema: "Access denied: 530"
- **Causa:** Credenciais incorretas
- **Solução:** Verificar usuário/senha na tabela acima

### Problema: "403 Forbidden" na web
- **Causa:** Permissões incorretas (600 em vez de 644)
- **Solução:** `chmod 644` no arquivo

### Problema: FTP lento
- **Causa:** Modo passivo mal configurado
- **Solução:** Usar modo ativo ou verificar firewall

## 📞 Suporte

Para problemas com o sistema FTP:
1. Verificar logs: `docker logs conn2flow-ftp`
2. Testar conectividade: `curl -u USER:PASS ftp://localhost/`
3. Verificar permissões: `docker exec conn2flow-app ls -la /var/www/sites/`

---
**Data:** 04/08/2025  
**Versão:** 1.0  
**Status:** ✅ Funcional (com correção manual de permissões)
