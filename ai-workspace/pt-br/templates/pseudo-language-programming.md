# Pseudo Language Programming
Definições de elementos de programação que serão usados pelos agentes de IA para poderem traduzir o pseudo código para uma linguagem de programação qualquer.

## Variáveis
1. Uma variável neste contexto é definida usando uma palavra alphanumérica precedida por '$'.
- $variavelNome 
2. Os tipos da variáveis são controlados pelos agentes de IA. Mas o desenvolvedor pode definir o tipo para melhor assertividade usando '<TIPO>'
- $variavel<STRING>
3. Uma variável pode ser um array|lista|objeto, para isso basta colocar o '[]'
- $variavelNome[]
4. Para referenciar um índice de uma variável, basta usar o '.'. Aplicável a multidimensões também.
- $variavelNome.indiceFilho.indiceNeto

## Funções
1. Uma função é definida usando a palavra nomeDaFuncao seguida pelo parênteses '()' e ':'.
nomeDaFuncao():
2. O corpo da função é definido abaixo da definição do nome, todos os comandos internos devem estar identados.
nomeDaFuncao():
    $variavel = 10
    <$variavel
3. As funções podem receber parâmetros, separados por espaço dentro dos parênteses.
nomeDaFuncao($parametro1 $parametro2)
4. O retorno de uma função é feito com o símbolo '<'.
<$variavelRetorno
5. A execução de uma função é feita chamando seu nome seguido por parênteses.
nomeDaFuncao()

## Estruturas de Controle e Loops
1. Estruturas são definidas usando o símbolo do '@' seguido do nome do elemento '@if', '@elseif', '@else', '@while', '@for', '@foreach'.
@if condicao
2. O bloco de código pertencente ao elemento é definido como uma identação abaixo do elemento.
@if condicao
    $variavel = 10
@else
    $variavel = 20
3. Laços de repetição são definidos com 'for', 'foreach' e 'while'.
- @for $i = 0 $i < 10 $i++
- @foreach $array as $item
- @while condicao

## Comentários
1. Comentários de linha única são feitos com '//'.
// Este é um comentário de linha única
2. Comentários de múltiplas linhas são feitos com '/* ... */'.
/* Este é um comentário
   de múltiplas linhas */

## Orientações para o agente
1. Orientações para o agente são feitas com '>' e devem ser seguidas pela descrição da orientação.
> Esta é uma orientação para o agente
2. Se a orientação estive identada, o agente deve seguir a mesma regra de identação definida acima. Pois uma orientação pode fazer parte de uma função ou elementos de controle e loops.
3. Orientações podem incluir ações como armazenar dados, criar índices, etc. Ou mesmo uma orientação complexa como criar funções, arquivos e etc.

## Exemplos
1. Exemplo de uso de variáveis:
```
$nome = "João"
$idade = 30
```
2. Exemplo de uso de funções:
```
numeroAleatorio($a $b):
    $resultado = $a + $b
    > Crie um número aleatório entre $a e $b, divida pelo $resultado e atribua a $resultado
    <$resultado
```
3. Exemplo de uso de estruturas de controle e e orientação para o agente guardar dados no banco:
```
@if $idade > 18
    $mensagem = "Maior de idade"
@else
    $mensagem = "Menor de idade"
> Armazene $mensagem no banco de dados, na tabela usuarios
> Crie um índice para a coluna mensagem
```