# Biblioteca: formato.php

> 📝 Funções de formatação de dados (datas, números, texto)

## Visão Geral

A biblioteca `formato.php` fornece um conjunto abrangente de funções para formatação e conversão de dados, incluindo:
- Formatação de datas e horas
- Conversão entre formatos de números (float, int)
- Formatação de texto com padrões específicos
- Conversão bidirecional entre formatos brasileiros e internacionais

**Localização**: `gestor/bibliotecas/formato.php`  
**Versão**: 1.1.0  
**Total de Funções**: 12

## Dependências

- Nenhuma dependência direta com outras bibliotecas
- Utiliza variável global `$_GESTOR`

## Variáveis Globais

```php
$_GESTOR['biblioteca-formato'] = Array(
    'versao' => '1.1.0',
);
```

## Funções Principais

### formato_dado()

Formata dados de acordo com o tipo especificado usando array de parâmetros.

**Assinatura:**
```php
function formato_dado($params = false)
```

**Parâmetros:**
- `valor` (string) - **Obrigatório** - Valor do dado a ser formatado
- `tipo` (string) - **Obrigatório** - Tipo de formatação a ser aplicada

**Tipos de Formatação Disponíveis:**
- `'float-para-texto'` - Converte float para formato brasileiro (00.000,00)
- `'texto-para-float'` - Converte texto brasileiro para float
- `'int-para-texto'` - Converte inteiro para formato brasileiro (00.000.000)
- `'texto-para-int'` - Converte texto brasileiro para inteiro
- `'data'` - Converte datetime para data (DD/MM/AAAA)
- `'dataHora'` - Converte datetime para data e hora (DD/MM/AAAA HH:MM)
- `'datetime'` - Converte data brasileira para datetime SQL
- `'date'` - Converte data brasileira para date SQL

**Retorno:**
- (string) - Valor formatado ou string vazia se parâmetros inválidos

**Exemplo de Uso:**
```php
// Formatar float para texto brasileiro
$valor_formatado = formato_dado(Array(
    'valor' => 1234.56,
    'tipo' => 'float-para-texto'
));
// Retorna: "1.234,56"

// Formatar datetime para data
$data_formatada = formato_dado(Array(
    'valor' => '2025-10-27 14:30:00',
    'tipo' => 'data'
));
// Retorna: "27/10/2025"

// Converter data brasileira para datetime SQL
$datetime_sql = formato_dado(Array(
    'valor' => '27/10/2025 14:30',
    'tipo' => 'datetime'
));
// Retorna: "2025-10-27 14:30:00"
```

---

### formato_dado_para()

Versão simplificada de `formato_dado()` com parâmetros diretos.

**Assinatura:**
```php
function formato_dado_para($tipo, $valor)
```

**Parâmetros:**
- `tipo` (string) - **Obrigatório** - Tipo de formatação
- `valor` (string) - **Obrigatório** - Valor a ser formatado

**Retorno:**
- (string) - Valor formatado ou string vazia

**Exemplo de Uso:**
```php
$valor = formato_dado_para('float-para-texto', 1500.75);
// Retorna: "1.500,75"

$data = formato_dado_para('data', '2025-10-27 14:30:00');
// Retorna: "27/10/2025"
```

---

## Funções Auxiliares

### formato_data_hora_array()

Converte string de data/hora para array associativo.

**Assinatura:**
```php
function formato_data_hora_array($data_hora_padrao_datetime_ou_padrao_date)
```

**Parâmetros:**
- `$data_hora_padrao_datetime_ou_padrao_date` (string) - Data em formato SQL (YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS)

**Retorno:**
- (array) - Array associativo com componentes da data/hora

**Formato do Retorno:**
```php
// Para datetime completo:
Array(
    'dia' => '27',
    'mes' => '10',
    'ano' => '2025',
    'hora' => '14',
    'min' => '30',
    'seg' => '00'
)

// Para apenas data:
Array(
    'dia' => '27',
    'mes' => '10',
    'ano' => '2025'
)
```

**Exemplo de Uso:**
```php
$data_array = formato_data_hora_array('2025-10-27 14:30:00');
echo $data_array['dia']; // "27"
echo $data_array['mes']; // "10"
echo $data_array['ano']; // "2025"
echo $data_array['hora']; // "14"
```

---

### formato_data_hora_padrao_datetime()

Converte data brasileira para formato datetime SQL.

**Assinatura:**
```php
function formato_data_hora_padrao_datetime($dataHora, $semHora = false)
```

**Parâmetros:**
- `$dataHora` (string) - Data no formato brasileiro (DD/MM/AAAA HH:MM)
- `$semHora` (bool) - Opcional - Se true, retorna apenas a data sem hora

**Retorno:**
- (string) - Data no formato SQL (YYYY-MM-DD HH:MM:SS ou YYYY-MM-DD)

**Exemplo de Uso:**
```php
// Com hora
$datetime = formato_data_hora_padrao_datetime('27/10/2025 14:30');
// Retorna: "2025-10-27 14:30:00"

// Sem hora
$date = formato_data_hora_padrao_datetime('27/10/2025 14:30', true);
// Retorna: "2025-10-27"
```

---

### formato_data_hora_from_datetime_to_text()

Converte datetime SQL para formato de texto personalizado ou padrão.

**Assinatura:**
```php
function formato_data_hora_from_datetime_to_text($data_hora, $format = false)
```

**Parâmetros:**
- `$data_hora` (string) - Data em formato SQL (YYYY-MM-DD HH:MM:SS)
- `$format` (string) - Opcional - Formato personalizado usando placeholders

**Placeholders Disponíveis:**
- `D` - Dia
- `ME` - Mês
- `A` - Ano
- `H` - Hora
- `MI` - Minuto
- `S` - Segundo

**Retorno:**
- (string) - Data formatada ou string vazia se data inválida

**Exemplo de Uso:**
```php
// Formato padrão (D/ME/A HhMI)
$data = formato_data_hora_from_datetime_to_text('2025-10-27 14:30:45');
// Retorna: "27/10/2025 14h30"

// Formato personalizado
$data = formato_data_hora_from_datetime_to_text('2025-10-27 14:30:45', 'D-ME-A às H:MI');
// Retorna: "27-10-2025 às 14:30"

// Outro exemplo
$data = formato_data_hora_from_datetime_to_text('2025-10-27 14:30:45', 'A/ME/D H:MI:S');
// Retorna: "2025/10/27 14:30:45"
```

---

### formato_data_from_datetime_to_text()

Converte datetime SQL para data no formato brasileiro (apenas data, sem hora).

**Assinatura:**
```php
function formato_data_from_datetime_to_text($data_hora)
```

**Parâmetros:**
- `$data_hora` (string) - Data em formato SQL (YYYY-MM-DD HH:MM:SS)

**Retorno:**
- (string) - Data no formato DD/MM/AAAA

**Exemplo de Uso:**
```php
$data = formato_data_from_datetime_to_text('2025-10-27 14:30:00');
// Retorna: "27/10/2025"
```

---

### formato_float_para_texto()

Formata número float para o padrão brasileiro.

**Assinatura:**
```php
function formato_float_para_texto($float, $sem_descimal = false)
```

**Parâmetros:**
- `$float` (float) - Número a ser formatado
- `$sem_descimal` (bool) - Opcional - Atualmente não utilizado

**Retorno:**
- (string) - Número formatado no padrão brasileiro (00.000,00)

**Exemplo de Uso:**
```php
$valor = formato_float_para_texto(1234.56);
// Retorna: "1.234,56"

$valor = formato_float_para_texto(1000000.99);
// Retorna: "1.000.000,99"
```

---

### formato_texto_para_float()

Converte texto no formato brasileiro para float.

**Assinatura:**
```php
function formato_texto_para_float($texto)
```

**Parâmetros:**
- `$texto` (string) - Número no formato brasileiro (00.000,00)

**Retorno:**
- (string) - Número no formato float (0000.00)

**Exemplo de Uso:**
```php
$float = formato_texto_para_float('1.234,56');
// Retorna: "1234.56"

$float = formato_texto_para_float('1.000.000,99');
// Retorna: "1000000.99"
```

---

### formato_int_para_texto()

Formata número inteiro para o padrão brasileiro com separadores de milhar.

**Assinatura:**
```php
function formato_int_para_texto($int)
```

**Parâmetros:**
- `$int` (int) - Número inteiro a ser formatado

**Retorno:**
- (string) - Número formatado no padrão brasileiro (00.000.000)

**Exemplo de Uso:**
```php
$valor = formato_int_para_texto(1234567);
// Retorna: "1.234.567"

$valor = formato_int_para_texto(1000);
// Retorna: "1.000"
```

---

### formato_texto_para_int()

Remove pontos de separador de milhar do formato brasileiro.

**Assinatura:**
```php
function formato_texto_para_int($texto)
```

**Parâmetros:**
- `$texto` (string) - Número no formato brasileiro (00.000.000)

**Retorno:**
- (string) - Número sem separadores

**Exemplo de Uso:**
```php
$int = formato_texto_para_int('1.234.567');
// Retorna: "1234567"
```

---

### formato_zero_a_esquerda()

Adiciona zeros à esquerda para completar o número de dígitos especificado.

**Assinatura:**
```php
function formato_zero_a_esquerda($num, $dig)
```

**Parâmetros:**
- `$num` (int|string) - Número a ser formatado
- `$dig` (int) - Número total de dígitos desejado

**Retorno:**
- (string) - Número com zeros à esquerda

**Exemplo de Uso:**
```php
$num = formato_zero_a_esquerda(42, 5);
// Retorna: "00042"

$num = formato_zero_a_esquerda(7, 3);
// Retorna: "007"

$num = formato_zero_a_esquerda(12345, 3);
// Retorna: "12345" (não altera se já tem mais dígitos)
```

---

### formato_colocar_char_meio_numero()

Insere um caractere no meio de um número.

**Assinatura:**
```php
function formato_colocar_char_meio_numero($num, $char = '-')
```

**Parâmetros:**
- `$num` (int|string) - Número a ser formatado
- `$char` (string) - Opcional - Caractere a inserir (padrão: '-')

**Retorno:**
- (string) - Número com caractere inserido no meio

**Exemplo de Uso:**
```php
$num = formato_colocar_char_meio_numero(123456);
// Retorna: "123-456"

$num = formato_colocar_char_meio_numero(123456, '/');
// Retorna: "123/456"

$num = formato_colocar_char_meio_numero(12345678, ' ');
// Retorna: "1234 5678"
```

---

## Casos de Uso Comuns

### 1. Formatação de Valores Monetários
```php
// Exibir preço em formato brasileiro
$preco_db = 1599.90; // Do banco de dados
$preco_exibir = formato_float_para_texto($preco_db);
echo "R$ " . $preco_exibir; // "R$ 1.599,90"

// Salvar preço digitado pelo usuário
$preco_digitado = "1.599,90";
$preco_salvar = formato_texto_para_float($preco_digitado);
// Salva no banco: 1599.90
```

### 2. Formatação de Datas para Exibição
```php
// Data vinda do banco de dados
$data_db = '2025-10-27 14:30:00';

// Exibir apenas a data
$data = formato_dado_para('data', $data_db);
echo $data; // "27/10/2025"

// Exibir data e hora
$data_hora = formato_dado_para('dataHora', $data_db);
echo $data_hora; // "27/10/2025 14h30"

// Formato personalizado
$data_custom = formato_data_hora_from_datetime_to_text($data_db, 'D/ME/A às H:MI');
echo $data_custom; // "27/10/2025 às 14:30"
```

### 3. Processamento de Formulários
```php
// Salvar data de formulário no banco
$data_form = "27/10/2025 14:30"; // Vem do formulário
$data_salvar = formato_dado_para('datetime', $data_form);
// INSERT: "2025-10-27 14:30:00"

// Exibir data do banco no formulário
$data_db = "2025-10-27 14:30:00";
$data_form = formato_dado_para('dataHora', $data_db);
// Formulário exibe: "27/10/2025 14h30"
```

### 4. Formatação de Números de Identificação
```php
// Formatar código de produto
$codigo = 42;
$codigo_formatado = formato_zero_a_esquerda($codigo, 6);
echo "Produto: " . $codigo_formatado; // "Produto: 000042"

// Formatar número de protocolo
$protocolo = 123456;
$protocolo_formatado = formato_colocar_char_meio_numero($protocolo);
echo "Protocolo: " . $protocolo_formatado; // "Protocolo: 123-456"
```

### 5. Manipulação de Componentes de Data
```php
// Extrair componentes de uma data
$data_db = '2025-10-27 14:30:00';
$componentes = formato_data_hora_array($data_db);

echo "Dia: " . $componentes['dia'];     // "27"
echo "Mês: " . $componentes['mes'];     // "10"
echo "Ano: " . $componentes['ano'];     // "2025"
echo "Hora: " . $componentes['hora'];   // "14"
echo "Min: " . $componentes['min'];     // "30"

// Útil para dropdowns de data
```

## Padrões e Convenções

### Formato Brasileiro vs Internacional

| Tipo | Formato Brasileiro | Formato Internacional/SQL |
|------|-------------------|--------------------------|
| Data | DD/MM/AAAA | YYYY-MM-DD |
| Data e Hora | DD/MM/AAAA HH:MM | YYYY-MM-DD HH:MM:SS |
| Float | 1.234,56 | 1234.56 |
| Inteiro | 1.234.567 | 1234567 |

### Fluxo de Conversão

```
┌─────────────────────────────────────────────────────────┐
│ Entrada do Usuário (Formato Brasileiro)                │
│ • Data: 27/10/2025                                      │
│ • Valor: 1.234,56                                       │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Conversão para Formato SQL/Internacional               │
│ • formato_data_hora_padrao_datetime()                  │
│ • formato_texto_para_float()                           │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Armazenamento no Banco de Dados                        │
│ • Data: 2025-10-27                                      │
│ • Valor: 1234.56                                        │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Conversão para Exibição (Formato Brasileiro)           │
│ • formato_data_from_datetime_to_text()                 │
│ • formato_float_para_texto()                           │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│ Exibição ao Usuário (Formato Brasileiro)               │
│ • Data: 27/10/2025                                      │
│ • Valor: 1.234,56                                       │
└─────────────────────────────────────────────────────────┘
```

## Notas Importantes

1. **Validação de Entrada**: As funções não fazem validação extensiva de entrada. Certifique-se de validar dados antes de formatar.

2. **Fuso Horário**: As funções de data/hora não lidam com fusos horários. Use funções PHP nativas se precisar de conversões de timezone.

3. **Precisão de Float**: A conversão de float usa 2 casas decimais por padrão. Para precisão diferente, use `number_format()` diretamente.

4. **Performance**: Para formatação em massa, considere cache ou pré-processamento.

5. **Compatibilidade**: Todas as funções são compatíveis com PHP 7.0+.

## Histórico de Versões

- **v1.1.0** - Versão atual com todas as funções documentadas
- **v1.0.0** - Versão inicial da biblioteca

## Veja Também

- [BIBLIOTECA-BANCO.md](./BIBLIOTECA-BANCO.md) - Para operações de banco de dados
- [BIBLIOTECA-FORMULARIO.md](./BIBLIOTECA-FORMULARIO.md) - Para validação de formulários
- [BIBLIOTECA-INTERFACE.md](./BIBLIOTECA-INTERFACE.md) - Para componentes de interface

---

**Última Atualização**: Outubro 2025  
**Documentado por**: Equipe Conn2Flow
