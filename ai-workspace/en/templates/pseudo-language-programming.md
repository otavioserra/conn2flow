````markdown
# Pseudo Language Programming
Definitions of programming elements that will be used by AI agents to be able to translate pseudo code to any programming language.

## Variables
1. A variable in this context is defined using an alphanumeric word preceded by '$'.
- $variableName 
2. Variable types are controlled by AI agents. But the developer can define the type for better assertiveness using '<TYPE>'
- $variable<STRING>
3. A variable can be an array|list|object, for that just put '[]'
- $variableName[]
4. To reference an index of a variable, just use '.'. Applicable to multidimensions as well.
- $variableName.childIndex.grandchildIndex

## Functions
1. A function is defined using the word functionName followed by parentheses '()' and ':'.
functionName():
2. The function body is defined below the name definition, all internal commands must be indented.
functionName():
    $variable = 10
    <$variable
3. Functions can receive parameters, separated by space inside the parentheses.
functionName($parameter1 $parameter2)
4. The return of a function is done with the symbol '<'.
<$returnVariable
5. The execution of a function is done by calling its name followed by parentheses.
functionName()

## Control Structures and Loops
1. Structures are defined using the '@' symbol followed by the element name '@if', '@elseif', '@else', '@while', '@for', '@foreach'.
@if condition
2. The code block belonging to the element is defined as an indentation below the element.
@if condition
    $variable = 10
@else
    $variable = 20
3. Loops are defined with 'for', 'foreach' and 'while'.
- @for $i = 0 $i < 10 $i++
- @foreach $array as $item
- @while condition

## Comments
1. Single line comments are done with '//'.
// This is a single line comment
2. Multi-line comments are done with '/* ... */'.
/* This is a 
   multi-line comment */

## Guidelines for the agent
1. Guidelines for the agent are done with '>' and must be followed by the guideline description.
> This is a guideline for the agent
2. If the guideline is indented, the agent must follow the same indentation rule defined above. Because a guideline can be part of a function or control elements and loops.
3. Guidelines can include actions like storing data, creating indexes, etc. Or even a complex guideline like creating functions, files, etc.

## Examples
1. Example of variable usage:
```
$name = "John"
$age = 30
```
2. Example of function usage:
```
randomNumber($a $b):
    $result = $a + $b
    > Create a random number between $a and $b, divide by $result and assign to $result
    <$result
```
3. Example of usage of control structures and guideline for the agent to store data in the database:
```
@if $age > 18
    $message = "Of legal age"
@else
    $message = "Underage"
> Store $message in the database, in the users table
> Create an index for the message column
```
````