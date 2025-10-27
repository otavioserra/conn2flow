# Biblioteca: geral.php

> 🛠️ Funções gerais e utilitários diversos

## Visão Geral

A biblioteca `geral.php` fornece funções utilitárias de propósito geral que não se encaixam em categorias específicas. Atualmente, contém funções para manipulação básica de texto.

**Localização**: `gestor/bibliotecas/geral.php`  
**Versão**: 1.0.0  
**Total de Funções**: 1

## Dependências

- Nenhuma dependência direta com outras bibliotecas
- Utiliza variável global `$_GESTOR`
- Depende da função `existe()` (presumivelmente de outra biblioteca)

## Variáveis Globais

```php
$_GESTOR['biblioteca-geral'] = Array(
    'versao' => '1.0.0',
);
```

## Funções Principais

### geral_nl2br()

Converte quebras de linha para tags HTML `<br>`.

**Assinatura:**
```php
function geral_nl2br($string = '')
```

**Descrição:**
Esta função é um wrapper para a função nativa `nl2br()` do PHP, com verificação adicional de existência da string usando a função `existe()`.

**Parâmetros:**
- `$string` (string) - Opcional - String a ser processada (padrão: string vazia)

**Retorno:**
- (string) - String com quebras de linha convertidas para `<br>` tags HTML, ou a string original se vazia

**Exemplo de Uso:**
```php
// Texto com quebras de linha
$texto = "Primeira linha
Segunda linha
Terceira linha";

$texto_html = geral_nl2br($texto);
echo $texto_html;

// Saída:
// Primeira linha<br />
// Segunda linha<br />
// Terceira linha
```

**Exemplo em Contexto de Exibição:**
```php
// Armazenar texto do usuário
$descricao = $_POST['descricao'];
// "Este é um produto
// com múltiplas linhas
// de descrição"

// Exibir em HTML preservando quebras de linha
echo '<div class="descricao">' . geral_nl2br($descricao) . '</div>';

// HTML gerado:
// <div class="descricao">
// Este é um produto<br />
// com múltiplas linhas<br />
// de descrição
// </div>
```

**Exemplo com String Vazia:**
```php
$texto = '';
$resultado = geral_nl2br($texto);
// Retorna: '' (string vazia)

$texto = null;
$resultado = geral_nl2br($texto);
// Retorna: null ou '' dependendo da implementação de existe()
```

---

## Casos de Uso Comuns

### 1. Exibição de Comentários/Descrições
```php
// Comentário do usuário vindo do banco de dados
$comentario = banco_select_one("SELECT texto FROM comentarios WHERE id = 1");

// Exibir preservando formatação
echo '<div class="comentario">';
echo geral_nl2br($comentario['texto']);
echo '</div>';
```

### 2. Formatação de Endereços
```php
// Endereço em múltiplas linhas
$endereco = "Rua das Flores, 123
Bairro Centro
São Paulo - SP
CEP: 01234-567";

echo '<address>' . geral_nl2br($endereco) . '</address>';
```

### 3. Exibição de Mensagens do Sistema
```php
// Mensagem com formatação
$mensagem = "Bem-vindo ao sistema!

Você tem 3 novas notificações.
Clique aqui para ver.";

echo '<div class="alert">' . geral_nl2br($mensagem) . '</div>';
```

### 4. Preview de Conteúdo com Quebras de Linha
```php
// Preview de artigo
$preview = substr($artigo['conteudo'], 0, 200);

if(strpos($preview, "\n") !== false) {
    echo geral_nl2br($preview) . '...';
} else {
    echo $preview . '...';
}
```

---

## Comparação com nl2br() Nativa

### Diferenças

| Aspecto | `geral_nl2br()` | `nl2br()` nativa |
|---------|-----------------|------------------|
| Verificação de existência | Sim (usa `existe()`) | Não |
| Retorno para string vazia | String original | String vazia com warning potencial |
| Uso de variáveis globais | Não (apenas declaração) | Não |

### Quando Usar geral_nl2br()

Use `geral_nl2br()` quando:
- Você quer garantir que strings vazias/null sejam tratadas graciosamente
- Você está trabalhando dentro do ecossistema Conn2Flow
- Você quer consistência com outras funções do sistema

### Quando Usar nl2br() Nativa

Use `nl2br()` nativa quando:
- Você tem certeza de que a string é válida
- Você está em contexto fora do sistema Conn2Flow
- Você precisa de controle fino sobre o parâmetro `is_xhtml`

---

## Detalhes de Implementação

### Função existe()

A função `geral_nl2br()` depende de `existe()`, que presumivelmente verifica se uma variável existe e não está vazia. Implementação típica:

```php
// Implementação presumida (não documentada neste arquivo)
function existe($var) {
    return isset($var) && $var != '' && $var !== null;
}
```

### Processamento de nl2br()

A função nativa `nl2br()` do PHP:
- Insere `<br />` antes de `\n`, `\r\n`, e `\r`
- Não remove as quebras de linha originais
- É segura para uso em HTML

---

## Notas Importantes

1. **Sanitização de HTML**: `geral_nl2br()` não faz escape de HTML. Se a string contém conteúdo do usuário, considere usar `htmlspecialchars()` primeiro:
   ```php
   $texto_seguro = htmlspecialchars($texto_usuario);
   $texto_formatado = geral_nl2br($texto_seguro);
   ```

2. **Performance**: Para grandes volumes de texto, considere armazenar a versão com `<br>` no banco de dados se o texto não muda frequentemente.

3. **Compatibilidade**: A função depende de `existe()` que deve estar disponível no sistema.

4. **XHTML vs HTML5**: A função usa `nl2br()` que por padrão gera tags XHTML (`<br />`). No HTML5, `<br>` também é válido.

---

## Exemplos Avançados

### Combinação com Outras Funções de Formatação

```php
// Processar texto do usuário de forma segura
$texto_raw = $_POST['descricao'];

// 1. Limpar HTML potencialmente perigoso
$texto_limpo = htmlspecialchars($texto_raw, ENT_QUOTES, 'UTF-8');

// 2. Converter quebras de linha para HTML
$texto_formatado = geral_nl2br($texto_limpo);

// 3. Limitar tamanho (se necessário)
$texto_final = substr($texto_formatado, 0, 500);

echo $texto_final;
```

### Uso em Templates

```php
// Em um sistema de templates
$template = '
<div class="post">
    <h2>{titulo}</h2>
    <div class="conteudo">
        {conteudo}
    </div>
</div>
';

// Preparar dados
$dados = Array(
    'titulo' => $post['titulo'],
    'conteudo' => geral_nl2br($post['descricao'])
);

// Renderizar
foreach($dados as $chave => $valor) {
    $template = str_replace('{'.$chave.'}', $valor, $template);
}

echo $template;
```

### Processamento Condicional

```php
// Aplicar nl2br apenas se houver quebras de linha
function formatar_texto_inteligente($texto) {
    if(existe($texto)) {
        // Verificar se há quebras de linha
        if(preg_match('/[\r\n]/', $texto)) {
            return geral_nl2br($texto);
        }
        return $texto;
    }
    return '';
}

$texto1 = "Texto em uma linha";
$texto2 = "Texto com\nmúltiplas\nlinhas";

echo formatar_texto_inteligente($texto1); // Sem <br>
echo formatar_texto_inteligente($texto2); // Com <br>
```

---

## Futuras Expansões

A biblioteca `geral.php` está posicionada para receber funções utilitárias adicionais que não se encaixam em outras categorias, tais como:

- Funções de manipulação de strings
- Utilitários de array
- Helpers de validação genérica
- Funções de conversão de tipos
- Utilitários de debug

---

## Veja Também

- [BIBLIOTECA-FORMATO.md](./BIBLIOTECA-FORMATO.md) - Formatação de dados específicos
- [BIBLIOTECA-HTML.md](./BIBLIOTECA-HTML.md) - Geração de HTML
- [BIBLIOTECA-INTERFACE.md](./BIBLIOTECA-INTERFACE.md) - Componentes de interface

---

**Última Atualização**: Outubro 2025  
**Documentado por**: Equipe Conn2Flow
