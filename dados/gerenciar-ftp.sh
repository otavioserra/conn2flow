#!/bin/bash
# Script para gerenciar usuários FTP no ambiente Docker
# Uso: bash ./gerenciar-ftp.sh [acao] [usuario] [senha]
# Ações: criar, listar, remover, resetar-senha, testar

ACAO=${1:-listar}
USUARIO=$2
SENHA=$3

case "$ACAO" in
  criar)
    if [ -z "$USUARIO" ] || [ -z "$SENHA" ]; then
      echo "❌ Usuário e senha são obrigatórios para criar"
      echo "Uso: bash ./gerenciar-ftp.sh criar nome-usuario senha123"
      exit 1
    fi
    
    echo "👤 Criando usuário FTP: $USUARIO"
    docker exec conn2flow-ftp bash -c "
      if ! id '$USUARIO' &>/dev/null; then
        useradd -m -d '/home/ftp-data/$USUARIO' -s /bin/bash '$USUARIO'
        echo '$USUARIO:$SENHA' | chpasswd
        mkdir -p '/home/ftp-data/$USUARIO'
        chown '$USUARIO:$USUARIO' '/home/ftp-data/$USUARIO'
        chmod 755 '/home/ftp-data/$USUARIO'
        echo '✅ Usuário $USUARIO criado com sucesso!'
      else
        echo '⚠️ Usuário $USUARIO já existe'
      fi
    "
    ;;
    
  listar)
    echo "📋 Usuários FTP configurados:"
    docker exec conn2flow-ftp bash -c "
      echo '  🔧 Usuários do sistema:'
      cat /etc/passwd | grep '/home/ftp-data' | cut -d: -f1 | while read user; do
        home_dir=\$(getent passwd \$user | cut -d: -f6)
        echo '    • \$user → \$home_dir'
      done
      echo ''
      echo '  📁 Diretórios disponíveis:'
      ls -la /home/ftp-data/ | grep '^d' | awk '{print \"    • \" \$9}' | grep -v '^\.$\|^\.\.$'
    "
    ;;
    
  remover)
    if [ -z "$USUARIO" ]; then
      echo "❌ Nome do usuário é obrigatório para remover"
      echo "Uso: bash ./gerenciar-ftp.sh remover nome-usuario"
      exit 1
    fi
    
    echo "🗑️ Removendo usuário FTP: $USUARIO"
    docker exec conn2flow-ftp bash -c "
      if id '$USUARIO' &>/dev/null; then
        userdel -r '$USUARIO' 2>/dev/null || userdel '$USUARIO'
        echo '✅ Usuário $USUARIO removido com sucesso!'
      else
        echo '❌ Usuário $USUARIO não encontrado'
      fi
    "
    ;;
    
  resetar-senha)
    if [ -z "$USUARIO" ] || [ -z "$SENHA" ]; then
      echo "❌ Usuário e nova senha são obrigatórios"
      echo "Uso: bash ./gerenciar-ftp.sh resetar-senha nome-usuario nova-senha"
      exit 1
    fi
    
    echo "🔐 Alterando senha do usuário: $USUARIO"
    docker exec conn2flow-ftp bash -c "
      if id '$USUARIO' &>/dev/null; then
        echo '$USUARIO:$SENHA' | chpasswd
        echo '✅ Senha do usuário $USUARIO alterada com sucesso!'
      else
        echo '❌ Usuário $USUARIO não encontrado'
      fi
    "
    ;;
    
  testar)
    if [ -z "$USUARIO" ]; then
      echo "❌ Nome do usuário é obrigatório para testar"
      echo "Uso: bash ./gerenciar-ftp.sh testar nome-usuario"
      exit 1
    fi
    
    echo "🧪 Testando acesso FTP para usuário: $USUARIO"
    docker exec conn2flow-ftp bash -c "
      if id '$USUARIO' &>/dev/null; then
        echo '✅ Usuário $USUARIO existe no sistema'
        home_dir=\$(getent passwd '$USUARIO' | cut -d: -f6)
        echo '📁 Diretório home: \$home_dir'
        if [ -d \"\$home_dir\" ]; then
          echo '✅ Diretório home existe'
          ls -la \"\$home_dir\"
        else
          echo '❌ Diretório home não existe'
        fi
      else
        echo '❌ Usuário $USUARIO não encontrado'
      fi
    "
    
    echo ""
    echo "🔗 Para testar via FTP cliente:"
    echo "   Host: localhost"
    echo "   Porta: 21"
    echo "   Usuário: $USUARIO"
    echo "   Modo: Ativo ou Passivo (PASV)"
    ;;
    
  info)
    echo "ℹ️ Informações do servidor FTP:"
    docker exec conn2flow-ftp bash -c "
      echo '🌐 Status do vsftpd:'
      ps aux | grep vsftpd | grep -v grep
      echo ''
      echo '📊 Portas utilizadas:'
      netstat -tlnp | grep :21
      echo ''
      echo '📝 Últimas linhas do log:'
      tail -n 5 /var/log/vsftpd.log 2>/dev/null || echo 'Log ainda não disponível'
    "
    ;;
    
  *)
    echo "❌ Ação inválida: $ACAO"
    echo ""
    echo "Uso: bash ./gerenciar-ftp.sh [acao] [usuario] [senha]"
    echo ""
    echo "Ações disponíveis:"
    echo "  listar                         - Lista usuários FTP"
    echo "  criar [usuario] [senha]        - Cria novo usuário FTP"
    echo "  remover [usuario]              - Remove usuário FTP"
    echo "  resetar-senha [usuario] [nova] - Altera senha do usuário"
    echo "  testar [usuario]               - Testa configuração do usuário"
    echo "  info                           - Informações do servidor FTP"
    echo ""
    echo "Exemplos:"
    echo "  bash ./gerenciar-ftp.sh listar"
    echo "  bash ./gerenciar-ftp.sh criar site1_user senha123"
    echo "  bash ./gerenciar-ftp.sh testar site1_user"
    ;;
esac
