#!/bin/bash

# Script de inicialização para ProFTPD com mapeamento multi-domínio
# Muito mais simples e rápido que vsftpd!

set -e

echo "=== Iniciando ProFTPD Multi-Domínio ==="

# Criar diretórios necessários
mkdir -p /var/log/proftpd
mkdir -p /etc/proftpd/conf.d
mkdir -p /var/run/proftpd

# Aguardar montagem dos volumes
sleep 3

# Função para criar usuário FTP no ProFTPD
create_ftp_user() {
    local domain=$1
    local password=$2
    local home_dir="/home/ftp/$domain"
    
    echo "Configurando usuário FTP: $domain"
    
    # Verificar se diretório existe
    if [ ! -d "$home_dir" ]; then
        echo "ERRO: Diretório $home_dir não encontrado!"
        return 1
    fi
    
    # Gerar hash da senha (método simples para testes)
    local password_hash=$(openssl passwd -1 "$password")
    
    # Adicionar usuário ao arquivo de senhas do ProFTPD
    echo "$domain:$password_hash:33:33::$home_dir:/bin/false" >> /etc/proftpd/ftpd.passwd
    
    # Criar configuração específica do domínio
    cat > "/etc/proftpd/conf.d/${domain}.conf" << EOF
# Configuração para domínio: $domain
<Directory $home_dir>
  <Limit ALL>
    AllowUser $domain
  </Limit>
  Umask 022 022
</Directory>
EOF
    
    # Ajustar permissões
    chown -R 33:33 "$home_dir"
    chmod -R 755 "$home_dir"
    
    echo "✓ Usuário FTP $domain configurado com sucesso!"
}

# Limpar arquivos de configuração anteriores
> /etc/proftpd/ftpd.passwd
echo "www-data:x:33:" > /etc/proftpd/ftpd.group

# Ajustar permissões dos arquivos de autenticação (IMPORTANTE!)
chmod 600 /etc/proftpd/ftpd.passwd
chmod 644 /etc/proftpd/ftpd.group

# Descobrir domínios automaticamente
echo "Descobrindo domínios em /home/ftp/..."

domain_count=0
for domain_dir in /home/ftp/*/; do
    if [ -d "$domain_dir" ]; then
        domain=$(basename "$domain_dir")
        
        # Validar nome do domínio
        if [[ "$domain" =~ ^[a-zA-Z0-9.-]+$ ]]; then
            create_ftp_user "$domain" "${domain}123"
            domain_count=$((domain_count + 1))
        else
            echo "⚠ Ignorando diretório inválido: $domain"
        fi
    fi
done

if [ $domain_count -eq 0 ]; then
    echo "⚠ AVISO: Nenhum domínio encontrado em /home/ftp/"
    echo "Criando usuário padrão para testes..."
    mkdir -p /home/ftp/localhost
    echo "Teste ProFTPD funcionando!" > /home/ftp/localhost/README.txt
    create_ftp_user "localhost" "localhost123"
fi

echo "=== Configuração Concluída ==="
echo "Total de usuários FTP criados: $domain_count"
echo ""
echo "🚀 INICIANDO ProFTPD (modo rápido)..."

# Executar ProFTPD em primeiro plano
exec proftpd --nodaemon --config /etc/proftpd/proftpd.conf
