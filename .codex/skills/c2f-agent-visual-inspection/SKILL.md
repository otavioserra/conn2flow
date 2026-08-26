---
name: c2f-agent-visual-inspection
description: "LEIA OBRIGATORIAMENTE antes de validar visualmente telas, animações CSS, erros de console ou rotas autenticadas do Gestor no ambiente local de testes. Elimina a necessidade de intervenção humana para checagem visual."
user-invocable: false
---

# Inspeção e Validação Visual Automatizada no Ambiente Local Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Validar renderização visual, estilos computados (`getComputedStyle`), animações CSS (`getAnimations`), erros de console JavaScript ou rotas autenticadas do Gestor no ambiente local de testes (Docker).
- **SKIP APENAS SE**: Tarefas puramente de backend CLI ou migrations sem renderização de tela.
- **CONSEQUÊNCIA DE IGNORAR**: Deixar itens pendentes como "aguarda homologação visual do operador", gastando rodadas extras de contexto e mascarando bugs visuais (ex: `@media (prefers-reduced-motion)` ou classes CSS ocultas).

---

## 🔍 1. Como Funciona o Pipeline de Inspeção Autônoma

No ambiente de desenvolvimento local (`$_GESTOR['development-env'] === true`), o framework disponibiliza ferramentas para que o agente inspecione o runtime de forma 100% autônoma:

### Passo 1: Autenticação de Sessão Server-Side (`auth:cookie`)
Para rotas autenticadas do Gestor (ex: `/admin/`, `/dashboard/`, `/chat-intelligence/`):
```bash
./c2f auth:cookie --user=admin --project=meu-projeto
```
* **O que faz**: Gera o token JWT de sessão server-side e grava o cookie jar em `temp/agent-cookies.txt`.

### Passo 2: Inspeção Headless via CLI (`page:inspect`)
Execute o comando nativo de inspeção:
```bash
./c2f page:inspect "http://localhost/modulo-rota/" --selector=".meu-elemento" --computed="display,opacity,transform" --screenshot
```
* **O que retorna**: JSON estruturado contendo:
  - `status`: Código HTTP (ex: 200, 302, 403).
  - `console_errors`: Array de erros capturados no console do navegador.
  - `computed_style`: Estilos computados resolvidos pelo browser em tempo de execução real.
  - `animations`: Estado das animações CSS ativas (`getAnimations()`).
  - `screenshot`: Caminho do screenshot PNG gerado em `temp/`.

---

## ⛔ Regras Invioláveis de Inspeção:
1. **EXCLUSIVO PARA AMBIENTE LOCAL DE TESTES**: NUNCA execute inspeção ou scraping automatizado em URLs de produção.
2. **Registro no SDD**: As evidências de inspeção (JSON do `page:inspect` e screenshots) DEVEM ser registradas diretamente no `VALIDATION-CHECKLIST.md` do repositório em vez de marcar "pendente do operador".
3. **Alternância de Ambiente (`c2f env:set`)**:
   - `c2f env:set development`: Força leitura dos arquivos físicos em `resources/`.
   - `c2f env:set production`: Força leitura dos registros compilados no banco de dados.
