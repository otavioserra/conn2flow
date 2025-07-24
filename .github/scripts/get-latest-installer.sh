#!/bin/bash
# Script para obter a URL do instalador mais recente
# Filtra apenas releases que começam com "instalador-v"

LATEST_INSTALLER=$(gh release list --limit 20 | grep "instalador-v" | head -n 1 | awk '{print $3}')

if [ -z "$LATEST_INSTALLER" ]; then
    echo "Erro: Nenhum release do instalador encontrado"
    exit 1
fi

echo "https://github.com/otavioserra/conn2flow/releases/download/${LATEST_INSTALLER}/instalador.zip"
