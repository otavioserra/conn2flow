#!/bin/bash
TOKEN=$(cat dev-environment/data/sites/localhost/conn2flow-gestor/.envAITestsToken)
curl -s --cookie "_C2FCID=$TOKEN" "http://localhost/instalador/admin-ia/listar/" | head -20
