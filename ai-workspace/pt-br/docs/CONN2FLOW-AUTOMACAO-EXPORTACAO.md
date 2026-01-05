# Conn2Flow - Automação de Exportação

## 📋 Índice
- [Visão Geral](#visão-geral)
- [Motivação](#motivação)
- [Fluxo de Exportação](#fluxo-de-exportação)
- [Validação de Módulos](#validação-de-módulos)
- [Estrutura de Arquivos Exportados](#estrutura-de-arquivos-exportados)
- [Boas Práticas](#boas-práticas)
- [Exemplos de Uso](#exemplos-de-uso)
- [Histórico de Decisões](#histórico-de-decisões)

---

## 🎯 Visão Geral

A automação de exportação permite transformar os dados dos seeders (layouts, páginas, componentes) em arquivos versionáveis, espelhando a estrutura do gestor para o gestor-cliente.

---

## 💡 Motivação
- Facilitar versionamento e manutenção.
- Garantir consistência entre ambientes.
- Evitar erros manuais na exportação de recursos visuais.

---

## 🔄 Fluxo de Exportação
1. Listagem dos módulos reais.
2. Leitura dos seeders.
3. Exportação de layouts/componentes para resources globais.
4. Exportação de páginas para módulos válidos ou global.
5. Limpeza de módulos inválidos.
6. Validação da estrutura final.

---

## ✅ Validação de Módulos
- Apenas módulos com `{modulo}.php` ou `{modulo}.js` são considerados reais.
- Exportação de páginas para módulos inválidos é bloqueada.

---

## 🗂️ Estrutura de Arquivos Exportados
- `gestor-cliente/resources/layouts/{id}/`
- `gestor-cliente/resources/paginas/{id}/`
- `gestor-cliente/resources/componentes/{id}/`
- `gestor-cliente/modulos/{modulo}/{id}/` (apenas módulos reais)

---

## 📝 Boas Práticas
- Rodar o script de exportação sempre após alterações nos seeders.
- Validar a estrutura gerada antes de deploy.
- Manter logs de exportação para auditoria.

---

## 💡 Exemplos de Uso
- Exportação completa após atualização de layouts.
- Limpeza de módulos inválidos antes de deploy.

---

## 📜 Histórico de Decisões
- Automação de exportação implementada em agosto/2025.
- Estrutura espelhada do gestor para o gestor-cliente.
- Validação de módulos reais obrigatória.
