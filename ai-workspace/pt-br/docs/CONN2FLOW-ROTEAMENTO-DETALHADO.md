# Conn2Flow - Roteamento Detalhado

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Como Funciona o Roteamento](#como-funciona-o-roteamento)
- [Campos de Roteamento](#campos-de-roteamento)
- [Vinculação com Módulos](#vinculação-com-módulos)
- [Exemplos Práticos](#exemplos-práticos)
- [Histórico de Decisões](#histórico-de-decisões)

---

## 🎯 Visão Geral

O roteamento do Conn2Flow é centralizado no arquivo `gestor.php`, que resolve URLs, busca páginas, layouts e módulos, e renderiza o HTML final.

---

## 🔄 Como Funciona o Roteamento
1. Requisição chega ao `gestor.php`.
2. O caminho é analisado e busca-se a página correspondente na tabela `paginas`.
3. O layout vinculado é carregado.
4. Se a página tem módulo, o arquivo do módulo é incluído.
5. Variáveis dinâmicas são processadas.
6. Componentes são incluídos conforme necessário.
7. HTML final é enviado ao navegador.

---

## 🏷️ Campos de Roteamento
- `caminho`: campo na tabela `paginas` que define a URL.
- `id_layouts`: layout vinculado à página.
- `id_modulos`: módulo vinculado (opcional).

---

## 🔗 Vinculação com Módulos
- Se `id_modulos` está definido, o roteador inclui `{modulo}.php`.
- Permite lógica específica por página.
- Exemplo: dashboard, host-configuracao.

---

## 💡 Exemplos Práticos
- `/dashboard` → página dashboard, layout 1, módulo dashboard.
- `/instalacao-sucesso` → página de sucesso, layout 23, sem módulo.

---

## 📜 Histórico de Decisões
- Estrutura de roteamento centralizada desde a versão inicial.
- Uso de campo `caminho` para URLs amigáveis.
- Processamento de variáveis dinâmicas padronizado.
