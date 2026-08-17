---
name: c2f-modelo-templates
description: Use ao manipular modelos HTML/texto e células repetitivas no Conn2Flow: modelo_var_troca, extração e iteração de células com tags de comentários, modelo_var_in e modelo_tag_del.
user-invocable: false
---

# Manipulação de Modelos e Células (`modelo.php`)

Consulte e aplique as seguintes convenções ao utilizar as funções de modelos e processamento de templates no Conn2Flow (`gestor/bibliotecas/modelo.php`):

1. **Substituição de Variáveis (`modelo_var_troca` / `modelo_var_troca_tudo`)**:
   - Substituir primeira ocorrência de array de marcadores: `$modelo = modelo_var_troca($modelo, ['#nome#' => 'João', '#local#' => 'Conn2Flow'])`.
   - Substituir primeira ocorrência de marcador único: `$modelo = modelo_var_troca($modelo, '#nome#', 'Maria')`.
   - Substituir **todas** as ocorrências: `$modelo = modelo_var_troca_tudo($modelo, $variaveis)`.

2. **Extração e Preparação de Célula Repetitiva com Tags de Comentário**:
   - Padrão de delimitação no modelo: `<!-- cel < --> CONTEÚDO DA CÉLULA <!-- cel > -->`.
   - Extração da célula, armazenagem em `$cel['cel']` e troca no modelo original por um marcador neutro `<!-- cel -->`:
     ```php
     $cel_nome = 'cel';
     $cel[$cel_nome] = modelo_tag_val($modelo_texto, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
     $modelo_texto = modelo_tag_troca_val($modelo_texto, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->', '<!-- '.$cel_nome.' -->');
     ```

3. **Iteração e Injeção Progressiva de Células Processadas (`modelo_var_in`)**:
   - Iterar sobre a coleção de dados e injetar cada item processado no marcador da célula:
     ```php
     foreach ($itens as $item) {
         $cel_nome = 'cel';
         $cel_aux = $cel[$cel_nome];
         $cel_aux = modelo_var_troca($cel_aux, $item);
         $modelo_processado = modelo_var_in($modelo_processado, '<!-- '.$cel_nome.' -->', $cel_aux);
     }
     // Limpar o marcador residual ao final da iteração
     $modelo_processado = modelo_var_troca($modelo_processado, '<!-- '.$cel_nome.' -->', '');
     ```

4. **Deleção Condicional de Célula/Bloco (`modelo_tag_del`)**:
   - Se a lista estiver vazia ou o bloco condicional não dever ser exibido:
     ```php
     $cel_nome = 'cel';
     $modelo_texto = modelo_tag_del($modelo_texto, '<!-- '.$cel_nome.' < -->', '<!-- '.$cel_nome.' > -->');
     ```
