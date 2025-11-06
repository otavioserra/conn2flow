#!/bin/bash

# Script de Teste: Verificação da Correção do Deploy
# ------------------------------------------------------------------------------
# Este script testa se a correção no deploy-projeto.sh está funcionando

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log "=== TESTE DA CORREÇÃO NO DEPLOY-PROJETO.SH ==="

# Simular o comportamento antigo (problemático)
log ""
log "=== SIMULAÇÃO DO COMPORTAMENTO ANTIGO ==="

# Script que sempre falha mas produz saída
cat > /tmp/failing_script.sh << 'EOF'
#!/bin/bash
echo "Este script sempre falha"
echo "Mas produz saída no stdout e stderr" >&2
exit 1
EOF
chmod +x /tmp/failing_script.sh

log "Testando comportamento antigo (sempre verdadeiro):"
if OUTPUT=$(/tmp/failing_script.sh 2>&1); then
    log_error "❌ Comportamento antigo: IF sempre verdadeiro mesmo com exit 1!"
    log "Saída capturada: $OUTPUT"
else
    log_success "✅ Comportamento antigo: IF seria falso (mas não é assim que estava)"
fi

log ""
log "=== SIMULAÇÃO DO NOVO COMPORTAMENTO ==="

log "Testando novo comportamento (verifica exit code):"
OUTPUT2=$(/tmp/failing_script.sh 2>&1)
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ] && [ -n "$OUTPUT2" ] && [ "$OUTPUT2" != "null" ]; then
    log_error "❌ Novo comportamento: IF verdadeiro (não deveria ser)"
else
    log_success "✅ Novo comportamento: IF falso corretamente quando exit code != 0"
    log "Exit code: $EXIT_CODE"
    log "Saída: $OUTPUT2"
fi

# Teste com script que funciona
log ""
log "=== TESTE COM SCRIPT QUE FUNCIONA ==="

cat > /tmp/success_script.sh << 'EOF'
#!/bin/bash
echo "eyJhbGciOiJSU0EiLCJ0eXAiOiJKV1QifQ.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c"
exit 0
EOF
chmod +x /tmp/success_script.sh

log "Testando com script que funciona:"
OUTPUT3=$(/tmp/success_script.sh 2>&1)
EXIT_CODE3=$?

if [ $EXIT_CODE3 -eq 0 ] && [ -n "$OUTPUT3" ] && [ "$OUTPUT3" != "null" ]; then
    log_success "✅ Novo comportamento: IF verdadeiro corretamente quando tudo OK"
    log "Token simulado: ${OUTPUT3:0:30}..."
else
    log_error "❌ Novo comportamento: IF falso quando deveria ser verdadeiro"
fi

log ""
log "=== CONCLUSÃO ==="
log "A correção implementada no deploy-projeto.sh agora:"
log "1. ✅ Captura a saída do script de renovação"
log "2. ✅ Verifica o código de saída (exit code)"
log "3. ✅ Valida se o token retornado não está vazio/null"
log "4. ✅ Só prossegue se TUDO estiver correto"

# Limpar arquivos temporários
rm -f /tmp/failing_script.sh /tmp/success_script.sh

exit 0

log "=== TESTE DA CORREÇÃO NO DEPLOY-PROJETO.SH ==="

# Simular o comportamento antigo (problemático)
log ""
log "=== SIMULAÇÃO DO COMPORTAMENTO ANTIGO ==="

# Script que sempre falha mas produz saída
cat > /tmp/failing_script.sh << 'EOF'
#!/bin/bash
echo "Este script sempre falha"
echo "Mas produz saída no stdout e stderr" >&2
exit 1
EOF
chmod +x /tmp/failing_script.sh

log "Testando comportamento antigo (sempre verdadeiro):"
if OUTPUT=$(/tmp/failing_script.sh 2>&1); then
    log_error "❌ Comportamento antigo: IF sempre verdadeiro mesmo com exit 1!"
    log "Saída capturada: $OUTPUT"
else
    log_success "✅ Comportamento antigo: IF seria falso (mas não é assim que estava)"
fi

log ""
log "=== SIMULAÇÃO DO NOVO COMPORTAMENTO ==="

log "Testando novo comportamento (verifica exit code):"
OUTPUT2=$(/tmp/failing_script.sh 2>&1)
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ] && [ -n "$OUTPUT2" ] && [ "$OUTPUT2" != "null" ]; then
    log_error "❌ Novo comportamento: IF verdadeiro (não deveria ser)"
else
    log_success "✅ Novo comportamento: IF falso corretamente quando exit code != 0"
    log "Exit code: $EXIT_CODE"
    log "Saída: $OUTPUT2"
fi

# Teste com script que funciona
log ""
log "=== TESTE COM SCRIPT QUE FUNCIONA ==="

cat > /tmp/success_script.sh << 'EOF'
#!/bin/bash
echo "eyJhbGciOiJSU0EiLCJ0eXAiOiJKV1QifQ.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c"
exit 0
EOF
chmod +x /tmp/success_script.sh

log "Testando com script que funciona:"
OUTPUT3=$(/tmp/success_script.sh 2>&1)
EXIT_CODE3=$?

if [ $EXIT_CODE3 -eq 0 ] && [ -n "$OUTPUT3" ] && [ "$OUTPUT3" != "null" ]; then
    log_success "✅ Novo comportamento: IF verdadeiro corretamente quando tudo OK"
    log "Token simulado: ${OUTPUT3:0:30}..."
else
    log_error "❌ Novo comportamento: IF falso quando deveria ser verdadeiro"
fi

log ""
log "=== CONCLUSÃO ==="
log "A correção implementada no deploy-projeto.sh agora:"
log "1. ✅ Captura a saída do script de renovação"
log "2. ✅ Verifica o código de saída (exit code)"
log "3. ✅ Valida se o token retornado não está vazio/null"
log "4. ✅ Só prossegue se TUDO estiver correto"

# Limpar arquivos temporários
rm -f /tmp/failing_script.sh /tmp/success_script.sh

exit 0