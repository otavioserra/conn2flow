---
name: c2f-interface-v2-architecture
description: "LEIA ANTES de renderizar modais, listas de cards, breadcrumbs ou componentes visuais do painel administrativo. Se não ler: o layout quebra no mobile, perde reatividade e conflita com a esteira de estilos v2."
user-invocable: false
---

# Arquitetura de Interface V2 (`gestor/bibliotecas/interface.php`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Desenvolver ou refatorar interfaces administrativas, formulários complexos, modais de confirmação ou listas dinâmicas do Gestor.
- **SKIP APENAS SE**: Endpoints de API headless (JSON puro) sem renderização de interface visual.
- **CONSEQUÊNCIA DE IGNORAR**: Inconsistência na experiência do usuário, falhas de acessibilidade/responsividade e conflitos entre frameworks CSS na administração.

---

Consulte e aplique as seguintes convenções ao utilizar o construtor de interfaces administrativas no Conn2Flow:

## 1. Padrões de Rotas CRUD V2

Telas gerenciadas por `interface.php` seguem os eventos padronizados:
* `adicionar`: Renderiza formulário de inclusão (GET) / Valida e executa INSERT no banco (POST).
* `editar`: Renderiza formulário de edição pré-preenchido com `banco_select_campos_antes_iniciar` (GET) / Valida e executa UPDATE no banco (POST).
* `listar`: Exibe tabela de registros com paginação e busca Semantic UI.
* `clonar`: Duplica um registro existente (GET/POST).
* `status`: Alterna estado do registro (`A`=Ativo, `I`=Inativo, `E`=Excluído).

---

## 2. Emissão de Alertas e Notificações (`interface_alerta`)

```php
interface_alerta([
    'redirect' => true,
    'msg' => gestor_variaveis(['modulo' => 'interface', 'id' => 'alert-success-saved'])
]);
```

---

## 3. Disparo Automático de Hooks Nativos

A biblioteca de interface dispara automaticamente os seguintes hooks por módulo:
- `{modulo-id}.adicionar.pre-banco` / `{modulo-id}.adicionar.banco`
- `{modulo-id}.editar.pre-banco` / `{modulo-id}.editar.banco`
- `{modulo-id}.excluir.banco`
- `{modulo-id}.status.banco`
