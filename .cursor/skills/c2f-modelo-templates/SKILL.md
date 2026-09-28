---
name: c2f-modelo-templates
description: "LEIA ANTES de manipular templates HTML com marcadores <!-- cel < -->, <!-- tag < --> ou modelo_var_troca(). Se nÃ£o ler: blocos inteiros somem silenciosamente da tela ou geram tags residuais nÃ£o substituÃ­das."
user-invocable: false
---

# ManipulaÃ§Ã£o de Modelos e CÃ©lulas (`modelo.php`)

# âš¡ Gatilho ObrigatÃ³rio
- **TRIGGER**: Manipular estruturas de repetiÃ§Ã£o, cÃ©lulas condicionais ou troca de marcadores em templates HTML via biblioteca `modelo.php`.
- **SKIP APENAS SE**: RenderizaÃ§Ã£o direta de dados sem uso do motor de templates do Gestor.
- **CONSEQUÃŠNCIA DE IGNORAR**: `modelo_tag_val()` devolvendo vazio e apagando seÃ§Ãµes da tela sem erro explÃ­cito, marcadores crus `#[id]#` expostos ao usuÃ¡rio final ou duplicaÃ§Ã£o de blocos.

---

Consulte e aplique as seguintes convenÃ§Ãµes ao utilizar as funÃ§Ãµes de modelos e processamento de templates no Conn2Flow (`gestor/bibliotecas/modelo.php`):

1. **SubstituiÃ§Ã£o de VariÃ¡veis (`modelo_var_troca` / `modelo_var_troca_tudo`)**:
   - Substituir primeira ocorrÃªncia de array de marcadores: `$modelo = modelo_var_troca($modelo, ['#nome#' => 'JoÃ£o', '#local#' => 'Conn2Flow'])`.
   - Substituir primeira ocorrÃªncia de marcador Ãºnico: `$modelo = modelo_var_troca($modelo, '#nome#', 'Maria')`.
   - Substituir **todas** as ocorrÃªncias: `$modelo = modelo_var_troca_tudo($modelo, $variaveis)`.

2. **ExtraÃ§Ã£o e PreparaÃ§Ã£o de CÃ©lula Repetitiva com Tags de ComentÃ¡rio**:
   - PadrÃ£o de delimitaÃ§Ã£o no modelo: `<!-- cel < --> CONTEÃšDO DA CÃ‰LULA <!-- cel > -->`.
   - ExtraÃ§Ã£o da cÃ©lula, armazenagem em `$cel['cel']` e troca no modelo original por um marcador neutro `<!-- cel -->`:
     ```php
     $cel_nome = 'cel';
     $cel[$cel_nome] = modelo_tag_val($modelo_texto, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
     $modelo_texto = modelo_tag_troca_val($modelo_texto, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->', '<!-- '.$cel_nome.' -->');
     ```

3. **IteraÃ§Ã£o e InjeÃ§Ã£o Progressiva de CÃ©lulas Processadas (`modelo_var_in`)**:
   - Iterar sobre a coleÃ§Ã£o de dados e injetar cada item processado no marcador da cÃ©lula:
     ```php
     foreach ($itens as $item) {
         $cel_nome = 'cel';
         $cel_aux = $cel[$cel_nome];
         $cel_aux = modelo_var_troca($cel_aux, $item);
         $modelo_processado = modelo_var_in($modelo_processado, '<!-- '.$cel_nome.' -->', $cel_aux);
     }
     // Limpar o marcador residual ao final da iteraÃ§Ã£o
     $modelo_processado = modelo_var_troca($modelo_processado, '<!-- '.$cel_nome.' -->', '');
     ```

4. **DeleÃ§Ã£o Condicional de CÃ©lula/Bloco (`modelo_tag_del`)**:
   - Se a lista estiver vazia ou o bloco condicional nÃ£o dever ser exibido:
     ```php
     $cel_nome = 'cel';
     $modelo_texto = modelo_tag_del($modelo_texto, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
     ```


---

## 5. Mockup Defensivo de Widgets (`<!-- widgets#SIG < -->`)

- Quando um widget pode retornar conteúdo vazio (ex: widget de listagem sem itens, widget condicional), use o **padrão de mockup defensivo** para evitar telas em branco e preservar as classes Tailwind no build:
```html
<!-- widgets#formulario-contato < -->
<div class="p-6 bg-white rounded-lg shadow-md">
  <h2 class="text-xl font-bold mb-4">Formulário de Contato</h2>
  <p class="text-gray-500">Carregando formulário...</p>
</div>
<!-- widgets#formulario-contato > -->
```

- **Funcionamento**: O motor de widgets substitui o conteúdo entre os marcadores `<!-- widgets#ID < -->` e `<!-- widgets#ID > -->` pelo output real do widget. Se o widget devolver vazio, o mockup original é preservado.
- **Benefício Duplo**:
  1. **UX**: O usuário nunca vê uma tela em branco — o mockup serve como placeholder visual.
  2. **Tailwind Build**: As classes CSS do mockup (`p-6`, `bg-white`, `rounded-lg`, etc.) são detectadas pelo scanner do Tailwind e incluídas no build, evitando a perda de classes que só existem no output dinâmico do widget.

> [!WARNING]
> Sem o mockup defensivo, classes Tailwind usadas exclusivamente pelo widget podem ser eliminadas do CSS compilado, causando quebra visual quando o widget finalmente renderiza.

---

## 6. Isolamento de Fragmentos de Formulário com `<template>`

- Fragmentos de HTML que servem como templates client-side (ex: opções de select, toggles de senha, blocos clonáveis) devem ser envolvidos em `<template>` para **não vazarem no DOM vivo**:
```html
<!-- ❌ ERRADO — fragmento visível no DOM, pode causar estilos indesejados -->
<div id="opcoes-template" style="display:none">
  <option value="#[valor]#">#[label]#</option>
</div>

<!-- ✅ CORRETO — <template> não renderiza no DOM, conteúdo inerte -->
<template id="opcoes-template">
  <option value="#[valor]#">#[label]#</option>
</template>
```

- **Razão Técnica**: O elemento `<template>` é inerte — seu conteúdo não é renderizado, scripts internos não executam, estilos não se aplicam e formulários internos não são submetidos. Isso previne:
  - Marcadores `#[...]#` não substituídos aparecendo na tela.
  - Elementos `<option>` fantasmas poluindo selects.
  - Event listeners sendo acionados prematuramente.
  - Classes CSS órfãs afetando o layout.

- **Acesso via JavaScript**:
```javascript
const template = document.getElementById('opcoes-template');
const clone = template.content.cloneNode(true);
// Processar marcadores no clone antes de inserir no DOM
select.appendChild(clone);
```