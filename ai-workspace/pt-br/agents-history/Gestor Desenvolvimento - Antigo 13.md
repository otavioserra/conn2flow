# Gestor Desenvolvimento - Antigo 13 (Janeiro 2026)

## Refinamento do Editor HTML e Padronização Arquitetural de Variáveis

## Contexto e Objetivos
Esta sessão de desenvolvimento focou em duas frentes principais: a melhoria da experiência de desenvolvimento (DX) no editor HTML visual personalizado do sistema e uma reestruturação arquitetural na forma como as "Variáveis de Sistema" são tratadas entre Frontend e Backend. O objetivo principal foi eliminar a complexidade visual do caractere de controle `@` para o usuário final, mantendo-o estritamente para segurança e processamento no backend.

## Escopo Detalhado Realizado

### 1. Refatoração do Algoritmo de Indentação (Beautify)
O editor HTML possuía um algoritmo de formatação automática (`cleanCodeString`) que aplicava indentação incorreta em atributos de tags.
- **Problema**: Atributos de tags container (ex: `<div>`) eram indentados um nível a mais, criando uma estrutura visualmente desconexa (estilo "escada").
- **Solução Técnica**: 
  - Reescrevemos a lógica de tokenização no arquivo `gestor/assets/interface/html-editor-interface.js`.
  - Implementamos uma distinção clara entre **Void Tags** (auto-fechamento, ex: `img`, `input`, `br`) e **Container Tags**.
  - **Nova Lógica**:
    - Se a tag é *Container*: Os atributos alinham verticalmente com a tag de abertura.
    - Se a tag é *Void*: Mantém-se a indentação aninhada (+1 tab) para atributos, seguindo o padrão visual do VS Code.

### 2. Ferramentas de Produtividade no Editor
Implementação de funcionalidades de UI para agilizar o fluxo de trabalho dos administradores:
- **Copy to Clipboard (One-Click)**: 
  - Adicionado listener global em `html-editor-interface.js` e `publisher.js`.
  - Ao clicar em qualquer variável nas listas de "Campos Disponíveis" ou "Vinculados", a string da variável (`[[publisher#...]]`) é copiada automaticamente para a área de transferência.
- **Remove All Variables**: 
  - Criado botão "Remover Variáveis" na barra de controle (`html-editor-publisher-controls.html`).
  - Função JS que varre o conteúdo do editor via Regex Global e remove todas as instâncias de variáveis dinâmicas, útil para limpeza rápida de templates.

### 3. Padronização Arquitetural de Sintaxe (Frontend vs Backend)
Esta foi a mudança mais crítica e complexa. Havia uma inconsistência onde o usuário precisava lidar com a sintaxe `@[[...]]@` (formato interno do sistema), o que gerava confusão e erros de digitação.

#### A. Camada de Frontend (Visualização Limpa)
Todas as referências visuais e de manipulação no JavaScript foram migradas para o formato **sem** `@`.
- **Arquivos Alterados**: 
  - `gestor/modulos/publisher/publisher.js`
  - `gestor/assets/interface/html-editor-interface.js`
- **Mudanças Específicas**:
  - Atualização das Regex de detecção: De `/@?\[\[/` (opcional) para `/\[\[/` (estrito sem arroba).
  - Templates Literais JS: Atualizados para gerar strings no formato `[[publisher#tipo#id]]` nas listas de seleção e dropdowns.
  - O usuário agora vê, copia e cola apenas `[[...]]`.

#### B. Camada de Backend (Segurança e Persistência)
Para garantir que o processador de templates do sistema (que depende do `@` para identificar variáveis) continuasse funcionando sem alterações no núcleo, implementamos um **Middleware de Transformação** no controlador do módulo.
- **Arquivo Alterado**: `gestor/modulos/publisher/publisher.php`
- **Lógica Implementada**:
  - Criada função privada `publisher_normalize_array($array, $direction)`.
  - **Direção `to_db` (Salvar)**: Intercepta os dados vindos do POST e aplica `preg_replace` para envolver as variáveis com `@`.
    - Regex: Transforma `[[...]]` em `@[[...]]@` (evitando duplicação se já existir).
  - **Direção `from_db` (Carregar)**: Intercepta os dados vindos do Banco antes de enviar para a View/JSON.
    - Operação: Remove os `@` externos (`str_replace`), entregando o formato limpo para o Javascript.

## Arquivos e Diretórios Alterados

### Backend (PHP)
- `gestor/modulos/publisher/publisher.php`:
  - Adição do método `publisher_normalize_array`.
  - Aplicação do método em `publisher_adicionar` (antes de salvar).
  - Aplicação do método em `publisher_editar` (antes de retornar dados para o formulário).

### Frontend Logic (JS)
- `gestor/assets/interface/html-editor-interface.js`:
  - Lógica `cleanCodeString` (Indentação).
  - Listeners de `copy-to-clipboard` e `remove-all-variables`.
  - Refatoração de Regex para sintaxe limpa.
- `gestor/modulos/publisher/publisher.js`:
  - Funções de renderização de listas (`mountAvailableFieldsList`, etc).
  - Lógica de busca e autocompletar atualizada para ignorar `@`.

### Frontend View (HTML/CSS)
- `gestor/resources/pt-br/components/html-editor-publisher-controls/html-editor-publisher-controls.html`:
  - Adição do botão de remoção em massa.
  - Ajuste de labels da interface para refletir o padrão `[[...]]`.

## Lições Aprendidas e Pontos de Atenção
- **Regex Lookbehind/Lookahead**: O uso de `(?<!@)` e `(?!@)` no PHP foi essencial para garantir que o processo de "salvar" fosse idempotente (não adicionar `@` se já existisse).
- **Separação de Preocupações**: A arquitetura agora separa claramente "Display Format" de "Storage Format", reduzindo carga cognitiva no usuário.

## Próximos Passos Sugeridos
1. **Auditoria de Módulos**: Verificar outros módulos (como `admin-templates` ou `paginas`) que possam estar expondo `@[[...]]@` diretamente e aplicar o mesmo padrão de middleware.
2. **Validação de Renderização**: Garantir que o parser central do sistema (`gestor.php`) continue processando corretamente as variáveis `@[[...]]@` vindas do banco, confirmando que a alteração de frontend não quebrou a renderização final das páginas.
3. **Testes de Integração**: Testar o ciclo completo: Criar variável -> Inserir no Editor (formato limpo) -> Salvar (formato sujo no DB) -> Carregar (formato limpo) -> Renderizar no Site (substituição de valor).

## Estado Atual do Sistema
- ✅ **Indentação HTML**: Padrão VS Code.
- ✅ **UX de Variáveis**: Clean (`[[...]]`) e com ferramentas de produtividade.
- ✅ **Integridade de Dados**: Preservada via middleware (`@[[...]]@`).

_Sessão Detalhada - Referência para Agente Futuro (Antigo 13)_
