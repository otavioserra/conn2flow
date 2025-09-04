#!/bin/bash

# Script de gerenciamento de usuários FTP para fauria/vsftpd
# Compatível com o container conn2flow-ftp

# Configurações
CONTAINER_NAME="conn2flow-ftp"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para verificar se o container está rodando
verificar_container() {
    if ! docker ps --format "table {{.Names}}" | grep -q "^$CONTAINER_NAME$"; then
        echo -e "${RED}❌ Container $CONTAINER_NAME não está rodando!${NC}"
        echo "Inicie o container com: docker-compose up -d ftp"
        exit 1
    fi
}

# Função para criar usuário FTP
criar_usuario() {
    local usuario=$1
    local senha=$2
    
    if [ -z "$usuario" ] || [ -z "$senha" ]; then
        echo -e "${RED}❌ Erro: Usuário e senha são obrigatórios${NC}"
        exit 1
    fi
    
    echo -e "${BLUE}��� Criando usuário FTP: $usuario${NC}"
    
    # Criar diretório home para o usuário no volume compartilhado
    docker exec $CONTAINER_NAME mkdir -p /home/vsftpd/$usuario
    docker exec $CONTAINER_NAME chown ftp:ftp /home/vsftpd/$usuario
    
    # Adicionar usuário ao arquivo virtual_users.txt
    docker exec $CONTAINER_NAME sh -c "echo '$usuario' >> /etc/vsftpd/virtual_users.txt"
    docker exec $CONTAINER_NAME sh -c "echo '$senha' >> /etc/vsftpd/virtual_users.txt"
    
    # Recriar o database de usuários virtuais
    docker exec $CONTAINER_NAME /usr/bin/db_load -T -t hash -f /etc/vsftpd/virtual_users.txt /etc/vsftpd/virtual_users.db
    
    # Ajustar permissões do database
    docker exec $CONTAINER_NAME chown ftp:ftp /etc/vsftpd/virtual_users.db
    docker exec $CONTAINER_NAME chmod 600 /etc/vsftpd/virtual_users.db
    
    echo -e "${GREEN}✅ Usuário $usuario criado com sucesso!${NC}"
}

# Função para listar usuários
listar_usuarios() {
    echo -e "${BLUE}��� Listando usuários FTP:${NC}"
    
    if docker exec $CONTAINER_NAME test -f /etc/vsftpd/virtual_users.txt; then
        echo -e "${YELLOW}Usuários configurados:${NC}"
        docker exec $CONTAINER_NAME sh -c "awk 'NR%2==1' /etc/vsftpd/virtual_users.txt" | while read usuario; do
            echo -e "  • $usuario"
        done
    else
        echo -e "${YELLOW}Nenhum usuário encontrado.${NC}"
    fi
}

# Função para remover usuário
remover_usuario() {
    local usuario=$1
    
    if [ -z "$usuario" ]; then
        echo -e "${RED}❌ Erro: Nome do usuário é obrigatório${NC}"
        exit 1
    fi
    
    echo -e "${YELLOW}���️  Removendo usuário FTP: $usuario${NC}"
    
    # Criar arquivo temporário sem o usuário
    docker exec $CONTAINER_NAME sh -c "
    if [ -f /etc/vsftpd/virtual_users.txt ]; then
        > /tmp/virtual_users_temp.txt
        while IFS= read -r line; do
            if [ \"\$line\" = \"$usuario\" ]; then
                # Pular o usuário e a próxima linha (senha)
                read -r # pula a senha
            else
                echo \"\$line\" >> /tmp/virtual_users_temp.txt
            fi
        done < /etc/vsftpd/virtual_users.txt
        mv /tmp/virtual_users_temp.txt /etc/vsftpd/virtual_users.txt
    fi
    "
    
    # Recriar o database
    docker exec $CONTAINER_NAME /usr/bin/db_load -T -t hash -f /etc/vsftpd/virtual_users.txt /etc/vsftpd/virtual_users.db
    
    # Remover diretório home
    docker exec $CONTAINER_NAME rm -rf /home/vsftpd/$usuario
    
    echo -e "${GREEN}✅ Usuário $usuario removido com sucesso!${NC}"
}

# Função para resetar senha
resetar_senha() {
    local usuario=$1
    local nova_senha=$2
    
    if [ -z "$usuario" ] || [ -z "$nova_senha" ]; then
        echo -e "${RED}❌ Erro: Usuário e nova senha são obrigatórios${NC}"
        exit 1
    fi
    
    echo -e "${YELLOW}��� Alterando senha do usuário: $usuario${NC}"
    
    # Criar arquivo temporário com nova senha
    docker exec $CONTAINER_NAME sh -c "
    if [ -f /etc/vsftpd/virtual_users.txt ]; then
        > /tmp/virtual_users_temp.txt
        while IFS= read -r line; do
            if [ \"\$line\" = \"$usuario\" ]; then
                echo \"\$line\" >> /tmp/virtual_users_temp.txt
                read -r # pula a senha antiga
                echo \"$nova_senha\" >> /tmp/virtual_users_temp.txt
            else
                echo \"\$line\" >> /tmp/virtual_users_temp.txt
            fi
        done < /etc/vsftpd/virtual_users.txt
        mv /tmp/virtual_users_temp.txt /etc/vsftpd/virtual_users.txt
    fi
    "
    
    # Recriar o database
    docker exec $CONTAINER_NAME /usr/bin/db_load -T -t hash -f /etc/vsftpd/virtual_users.txt /etc/vsftpd/virtual_users.db
    
    echo -e "${GREEN}✅ Senha do usuário $usuario alterada com sucesso!${NC}"
}

# Função para testar conexão
testar_conexao() {
    local usuario=$1
    local senha=$2
    
    if [ -z "$usuario" ] || [ -z "$senha" ]; then
        echo -e "${RED}❌ Erro: Usuário e senha são obrigatórios para o teste${NC}"
        exit 1
    fi
    
    echo -e "${BLUE}��� Testando conexão FTP para usuário: $usuario${NC}"
    
    if curl -s --connect-timeout 5 ftp://$usuario:$senha@localhost/ >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Conexão bem-sucedida!${NC}"
    else
        echo -e "${RED}❌ Falha na conexão. Verifique as credenciais.${NC}"
    fi
}

# Função para exibir informações do sistema
info() {
    echo -e "${BLUE}ℹ️  Informações do sistema FTP:${NC}"
    echo "Container: $CONTAINER_NAME"
    echo "Status: $(docker inspect --format='{{.State.Status}}' $CONTAINER_NAME 2>/dev/null || echo 'não encontrado')"
    echo "Porta FTP: 21"
    echo "Portas PASV: 21100-21110"
    echo ""
    listar_usuarios
}

# Função para mostrar ajuda
mostrar_ajuda() {
    echo -e "${BLUE}��� Gerenciador de FTP - conn2flow${NC}"
    echo ""
    echo -e "${YELLOW}Uso:${NC}"
    echo "  $0 <comando> [argumentos]"
    echo ""
    echo -e "${YELLOW}Comandos disponíveis:${NC}"
    echo "  criar <usuario> <senha>      - Criar novo usuário FTP"
    echo "  listar                       - Listar todos os usuários"
    echo "  remover <usuario>            - Remover usuário FTP"
    echo "  resetar-senha <usuario>      - Resetar senha do usuário"
    echo "         <nova_senha>"
    echo "  testar <usuario> <senha>     - Testar conexão FTP"
    echo "  info                         - Mostrar informações do sistema"
    echo "  ajuda                        - Mostrar esta ajuda"
    echo ""
    echo -e "${YELLOW}Exemplos:${NC}"
    echo "  $0 criar localhost minhasenha123"
    echo "  $0 listar"
    echo "  $0 testar localhost minhasenha123"
    echo "  $0 remover localhost"
}

# Verificar argumentos
if [ $# -eq 0 ]; then
    mostrar_ajuda
    exit 1
fi

# Verificar se o container está rodando (exceto para ajuda)
if [ "$1" != "ajuda" ]; then
    verificar_container
fi

# Processar comando
case "$1" in
    "criar")
        criar_usuario "$2" "$3"
        ;;
    "listar")
        listar_usuarios
        ;;
    "remover")
        remover_usuario "$2"
        ;;
    "resetar-senha")
        resetar_senha "$2" "$3"
        ;;
    "testar")
        testar_conexao "$2" "$3"
        ;;
    "info")
        info
        ;;
    "ajuda")
        mostrar_ajuda
        ;;
    *)
        echo -e "${RED}❌ Comando desconhecido: $1${NC}"
        echo ""
        mostrar_ajuda
        exit 1
        ;;
esac
