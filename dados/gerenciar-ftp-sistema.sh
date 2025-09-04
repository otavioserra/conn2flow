#!/bin/bash

# Script de Gerenciamento do Sistema FTP Multi-Domínio
# Autor: Assistente AI
# Data: 04/08/2025

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para imprimir mensagens coloridas
print_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Função para mostrar o menu
show_menu() {
    echo -e "\n${BLUE}=== Sistema FTP Multi-Domínio - Gerenciador ===${NC}"
    echo "1. Listar usuários FTP ativos"
    echo "2. Testar conexão FTP"
    echo "3. Corrigir permissões de arquivos web"
    echo "4. Adicionar novo domínio"
    echo "5. Ver logs do FTP"
    echo "6. Status dos containers"
    echo "7. Backup de configurações"
    echo "8. Restaurar configurações"
    echo "9. Limpar arquivos temporários"
    echo "0. Sair"
    echo -n "Escolha uma opção: "
}

# Função para listar usuários FTP
list_ftp_users() {
    print_info "Listando usuários FTP configurados..."
    
    if docker ps -q --filter "name=conn2flow-ftp" | grep -q .; then
        echo -e "\n${GREEN}Usuários FTP Ativos:${NC}"
        docker exec conn2flow-ftp sh -c "cat /etc/vsftpd/virtual_users.txt" 2>/dev/null | while read -r line; do
            if [[ $line =~ ^[a-zA-Z0-9.-]+$ ]]; then
                echo "  • Usuário: $line"
                read -r password
                echo "    Senha: $password"
                echo ""
            fi
        done
    else
        print_error "Container FTP não está executando!"
    fi
}

# Função para testar conexão FTP
test_ftp_connection() {
    echo -n "Digite o usuário FTP (ex: localhost): "
    read -r ftp_user
    echo -n "Digite a senha (ex: localhost123): "
    read -rs ftp_pass
    echo ""
    
    print_info "Testando conexão FTP para usuário: $ftp_user"
    
    if curl -u "$ftp_user:$ftp_pass" ftp://localhost/ --list-only --connect-timeout 10 > /tmp/ftp_test.txt 2>&1; then
        print_success "Conexão FTP bem-sucedida!"
        echo -e "\n${GREEN}Conteúdo do diretório:${NC}"
        cat /tmp/ftp_test.txt
        rm -f /tmp/ftp_test.txt
    else
        print_error "Falha na conexão FTP!"
        echo "Detalhes do erro:"
        cat /tmp/ftp_test.txt
        rm -f /tmp/ftp_test.txt
    fi
}

# Função para corrigir permissões
fix_permissions() {
    print_info "Corrigindo permissões de arquivos web..."
    
    if docker ps -q --filter "name=conn2flow-app" | grep -q .; then
        # Corrigir permissões dos arquivos comuns
        docker exec conn2flow-app find /var/www/sites -type f -name "*.php" -exec chmod 644 {} \; 2>/dev/null || true
        docker exec conn2flow-app find /var/www/sites -type f -name "*.html" -exec chmod 644 {} \; 2>/dev/null || true
        docker exec conn2flow-app find /var/www/sites -type f -name "*.htm" -exec chmod 644 {} \; 2>/dev/null || true
        docker exec conn2flow-app find /var/www/sites -type f -name "*.css" -exec chmod 644 {} \; 2>/dev/null || true
        docker exec conn2flow-app find /var/www/sites -type f -name "*.js" -exec chmod 644 {} \; 2>/dev/null || true
        docker exec conn2flow-app find /var/www/sites -type f -name "*.txt" -exec chmod 644 {} \; 2>/dev/null || true
        docker exec conn2flow-app find /var/www/sites -type f -name "*.json" -exec chmod 644 {} \; 2>/dev/null || true
        docker exec conn2flow-app find /var/www/sites -type f -name "*.xml" -exec chmod 644 {} \; 2>/dev/null || true
        
        # Corrigir permissões de diretórios
        docker exec conn2flow-app find /var/www/sites -type d -exec chmod 755 {} \; 2>/dev/null || true
        
        print_success "Permissões corrigidas com sucesso!"
        
        # Mostrar estatísticas
        total_files=$(docker exec conn2flow-app find /var/www/sites -type f | wc -l)
        print_info "Total de arquivos processados: $total_files"
    else
        print_error "Container APP não está executando!"
    fi
}

# Função para adicionar novo domínio
add_new_domain() {
    echo -n "Digite o nome do novo domínio (ex: novosite.com): "
    read -r domain_name
    
    if [[ ! $domain_name =~ ^[a-zA-Z0-9.-]+$ ]]; then
        print_error "Nome de domínio inválido! Use apenas letras, números, pontos e hífens."
        return 1
    fi
    
    print_info "Criando estrutura para domínio: $domain_name"
    
    # Criar diretórios
    mkdir -p "sites/$domain_name/public_html"
    mkdir -p "sites/$domain_name/home"
    
    # Criar arquivo index padrão
    cat > "sites/$domain_name/public_html/index.php" << EOF
<?php
echo "<h1>$domain_name está funcionando!</h1>";
echo "<p>Via FTP e Web: " . date('Y-m-d H:i:s') . "</p>";
?>
EOF
    
    # Criar README
    cat > "sites/$domain_name/README-FTP.txt" << EOF
=== Acesso FTP para $domain_name ===

Servidor: localhost (ou IP do servidor)
Porta: 21
Usuário: $domain_name
Senha: ${domain_name}123

Esta pasta é a raiz do seu domínio $domain_name
- public_html/: Arquivos acessíveis via web
- home/: Arquivos privados

Após enviar arquivos via FTP, execute:
docker exec conn2flow-app chmod 644 /var/www/sites/$domain_name/public_html/arquivo.ext

Criado em: $(date)
EOF
    
    # Definir permissões
    chmod -R 755 "sites/$domain_name"
    
    print_success "Domínio $domain_name criado com sucesso!"
    print_info "Reiniciando container FTP para detectar novo domínio..."
    
    docker-compose restart ftp > /dev/null 2>&1
    
    print_success "Sistema atualizado!"
    print_info "Credenciais FTP:"
    echo "  Usuário: $domain_name"
    echo "  Senha: ${domain_name}123"
}

# Função para ver logs
view_logs() {
    print_info "Mostrando logs do container FTP (últimas 50 linhas)..."
    echo -e "\n${GREEN}=== Logs do FTP ===${NC}"
    docker logs conn2flow-ftp --tail 50
}

# Função para ver status
show_status() {
    print_info "Status dos containers do sistema..."
    echo -e "\n${GREEN}=== Status dos Containers ===${NC}"
    docker-compose ps
    
    echo -e "\n${GREEN}=== Uso de Recursos ===${NC}"
    docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" $(docker-compose ps -q)
}

# Função para backup
backup_config() {
    print_info "Criando backup das configurações..."
    
    backup_dir="backup_ftp_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    # Backup dos arquivos de configuração
    cp -r sites/ "$backup_dir/" 2>/dev/null || true
    cp docker-compose.yml "$backup_dir/" 2>/dev/null || true
    cp Dockerfile.ftp "$backup_dir/" 2>/dev/null || true
    cp entrypoint-custom-ftp.sh "$backup_dir/" 2>/dev/null || true
    
    # Backup das configurações do container
    if docker ps -q --filter "name=conn2flow-ftp" | grep -q .; then
        docker exec conn2flow-ftp tar -czf - /etc/vsftpd/ > "$backup_dir/vsftpd_config.tar.gz" 2>/dev/null || true
    fi
    
    print_success "Backup criado em: $backup_dir"
}

# Função para restaurar
restore_config() {
    echo -n "Digite o nome do diretório de backup: "
    read -r backup_dir
    
    if [[ ! -d "$backup_dir" ]]; then
        print_error "Diretório de backup não encontrado: $backup_dir"
        return 1
    fi
    
    print_warning "Esta operação irá sobrescrever as configurações atuais!"
    echo -n "Continuar? (y/N): "
    read -r confirm
    
    if [[ $confirm =~ ^[Yy]$ ]]; then
        print_info "Restaurando backup..."
        
        # Parar containers
        docker-compose down > /dev/null 2>&1
        
        # Restaurar arquivos
        cp -r "$backup_dir/sites/" . 2>/dev/null || true
        cp "$backup_dir/docker-compose.yml" . 2>/dev/null || true
        cp "$backup_dir/Dockerfile.ftp" . 2>/dev/null || true
        cp "$backup_dir/entrypoint-custom-ftp.sh" . 2>/dev/null || true
        
        # Reiniciar sistema
        docker-compose up -d > /dev/null 2>&1
        
        print_success "Backup restaurado com sucesso!"
    else
        print_info "Operação cancelada."
    fi
}

# Função para limpeza
cleanup() {
    print_info "Limpando arquivos temporários..."
    
    # Limpar logs antigos
    docker exec conn2flow-ftp sh -c "find /var/log -name '*.log' -mtime +7 -delete" 2>/dev/null || true
    
    # Limpar arquivos temporários locais
    rm -f teste-*.txt
    rm -f /tmp/ftp_*.txt
    
    # Limpar imagens Docker não utilizadas
    docker image prune -f > /dev/null 2>&1
    
    print_success "Limpeza concluída!"
}

# Função principal
main() {
    while true; do
        show_menu
        read -r choice
        
        case $choice in
            1) list_ftp_users ;;
            2) test_ftp_connection ;;
            3) fix_permissions ;;
            4) add_new_domain ;;
            5) view_logs ;;
            6) show_status ;;
            7) backup_config ;;
            8) restore_config ;;
            9) cleanup ;;
            0) 
                print_info "Saindo..."
                exit 0
                ;;
            *)
                print_error "Opção inválida!"
                ;;
        esac
        
        echo -e "\nPressione Enter para continuar..."
        read -r
    done
}

# Verificar se Docker está disponível
if ! command -v docker &> /dev/null; then
    print_error "Docker não está instalado ou não está no PATH!"
    exit 1
fi

# Verificar se docker-compose está disponível
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose não está instalado ou não está no PATH!"
    exit 1
fi

# Executar programa principal
main
