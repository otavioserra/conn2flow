# BATCH-269 — Camada de provedores de IA: Gemini, Claude, OpenAI e compatíveis

- **Requisição:** [REQ-260](../human-requests/req-260.md)
- **Status:** `implemented-pending-homologation`
- **Linha:** `3.0` (branch `feat/req-260`, entregue em `main`, `3.0` e `3.1`, que estão no mesmo commit desde a DEC-134).
- **Data:** 2026-10-07
- **Ambiente de teste:** `https://v3.1-conn2flow.local/` (projeto `conn2flow-v31-local`, que tem um servidor Gemini cadastrado)

## Live Todo List

- [x] Biblioteca de provedores (`ia-provedores.php`): texto nos quatro tipos, imagem no Gemini e na OpenAI
- [x] Colunas `url_base`, `modelo` e `modelo_imagem` em `servidores_ia`
- [x] Cadastro de servidores com os quatro tipos e teste de conexão para qualquer um
- [x] Envio do editor (`ia_enviar_prompt`) pela biblioteca, com o mesmo retorno
- [x] Chave em cabeçalho, fora do endereço e das mensagens de erro
- [x] Testes sem rede, suítes, publicação e roteiro de navegador
- [ ] Conferência com chave real de Claude, OpenAI e de um serviço compatível
- [ ] Homologação humana

## O que mudou

**Uma entrada e uma saída para todos os provedores.** `gestor/bibliotecas/ia-provedores.php` conhece quatro tipos de servidor:

| Tipo | Serviço | Texto | Imagem | Endereço base |
|---|---|---|---|---|
| `gemini` | Google Gemini | sim | sim | padrão do provedor |
| `anthropic` | Anthropic Claude | sim | não | padrão do provedor |
| `openai` | OpenAI | sim | sim | padrão do provedor |
| `openai-compativel` | qualquer serviço que fale o formato da OpenAI, inclusive modelo local | sim | só com modelo de imagem informado | obrigatório |

Quem chama não conhece o formato de ninguém:

```php
gestor_incluir_biblioteca('ia-provedores');
$servidor = ia_provedor_servidor_do_banco($linha_de_servidores_ia);   // decifra a chave
$r = ia_provedor_gerar_texto($servidor, ['sistema' => '...', 'mensagens' => [['papel' => 'user', 'texto' => '...']]]);
$i = ia_provedor_gerar_imagem($servidor, ['prompt' => '...', 'tamanho' => '1536x1024']);
```

- Texto devolve `status`, `texto`, `modelo` e os tokens de entrada, saída e total. Imagem devolve `imagens`, lista de `mime` e `base64`.
- Montar o pedido e ler a resposta são funções puras (`ia_provedor_pedido_texto`, `ia_provedor_resposta_texto` e as de imagem); só `ia_provedor_http()` fala com fora. É o que permite testar tudo sem rede.
- A conversa já aceita várias mensagens e instrução de sistema, que as frentes seguintes (criador de imagens, IA nos campos, assistente) vão usar.

**Chave fora do endereço.** Antes a chave do Gemini ia em `?key=` no endereço, que é o que costuma ficar em registro de acesso e de proxy. Agora vai em cabeçalho em todos os provedores, e a mensagem de erro que volta do provedor passa por um filtro que tira o que parece chave.

**Cadastro de servidores (`admin-ia`).** Inclusão e edição trazem os quatro tipos e três campos opcionais: endereço base, modelo de texto e modelo de imagem. Vazio usa o padrão do provedor, mostrado como exemplo no campo e trocado quando o tipo muda. O servidor confere tudo de novo ao gravar: tipo conhecido, endereço só `http(s)` sem usuário, senha nem parâmetros, nome de modelo sem espaço, e endereço e modelo obrigatórios no tipo compatível. O teste de conexão deixou de ser escrito para o Gemini e usa o provedor do servidor. A listagem mostra o nome do provedor.

**Envio do editor.** `ia_enviar_prompt()` usa a biblioteca e devolve o mesmo que devolvia (`texto_gerado`, `modelo_usado`, tokens, `resposta_completa`). A lista de modelos do Assistente IA é do Gemini: quando o servidor escolhido é de outro provedor, vale o modelo do cadastro do servidor.

**Consultas com identificador numérico.** Quatro consultas do `admin-ia` juntavam o `id` do pedido direto no SQL; passaram a converter para inteiro.

## Arquivos

- `gestor/bibliotecas/ia-provedores.php` (novo) e registro em `gestor/config.php`
- `gestor/bibliotecas/ia.php` (`ia_enviar_prompt`)
- `gestor/db/migrations/20261007130000_add_provider_fields_to_servidores_ia.php` (novo)
- `gestor/modulos/admin-ia/`: `admin-ia.php`, `admin-ia.js`, `admin-ia.json` (15 variáveis novas por idioma, versão 1.3.0), páginas `admin-ia-adicionar` e `admin-ia-editar` em pt-br e en
- Testes: `tests/Unit/PHP/IaProvedoresReq260Test.php`, `tests/Unit/PHP/AdminIaProvedoresReq260Test.php`, `tests/Unit/JS/admin-ia.provedores-req260.test.js`
- Roteiro: `sdd/validation/req260/req260-browser.cjs`

## Validação

| Checagem | Resultado |
|---|---|
| PHPUnit (suíte inteira) | 1.746 testes, 20.479 asserções, 5 pulados — OK |
| Vitest (suíte inteira) | 664 testes em 60 arquivos — OK |
| Testes da requisição | 24 de PHP (pedido e resposta de cada provedor, chave fora do endereço e do erro, conferência dos campos do cadastro) e 4 de JavaScript |
| Roteiro de navegador em `v3.1-conn2flow.local` | 24/24 |
| Roteiro de navegador em `conn2flow.local` (linha 3.0) | 24/24 |
| Memória de execução | podada no fecho do lote: de 48.212 bytes e 328 linhas para 35.425 bytes e 231 linhas, 23 seções preservadas; 13 seções de 2026-10-02 a 04 movidas na íntegra para `sdd/archive/MEMORIA-ENGENHARIA-EXECUCAO-2026-10-02-04.md` |

O roteiro cria um servidor de teste do tipo compatível apontando para uma porta fechada, confere inclusão, recusas, edição, histórico e listagem, e o exclui no fim. Com o servidor Gemini que já existia, faz dois pedidos reais: o teste de conexão e um pedido do editor, que voltou com HTML, modelo e contagem de tokens.

## O que não foi exercitado

- **Claude, OpenAI e serviço compatível com chave real.** O pedido e a leitura da resposta de cada um estão cobertos por teste sem rede, escritos a partir do formato documentado. Nenhum pedido real foi feito a eles: não há chave cadastrada. O tipo compatível foi exercitado só até a falha de conexão.
- **Geração de imagem** em qualquer provedor: a biblioteca monta o pedido e lê a resposta, mas ninguém a chama ainda. A primeira chamada real vem com o criador de imagens, no site.
- **Assistente IA pela tela do editor** (clicar e ver o HTML entrar): o roteiro faz o mesmo pedido que a tela faz, pelo mesmo endereço, e confere a resposta; não clica nos botões.
- Nada a mais na instalação da linha 3.0 (`conn2flow.local`): ela também tem um servidor Gemini cadastrado e o mesmo roteiro passou lá, 24/24, depois do fecho do lote.

## Limites conhecidos

- Os nomes de modelo padrão (`gemini-3-flash-preview`, `gemini-2.5-flash-image`, `claude-sonnet-5-5`, `gpt-4.1-mini`, `gpt-image-1`) envelhecem. O campo de modelo do servidor existe para isso; trocar o padrão é uma linha em `ia_provedores()`.
- O endereço base aceita rede interna (é o caso de uso do modelo local). O cadastro é restrito a quem administra servidores de IA.
- As mensagens de erro da biblioteca estão em português no código, como as de `ia.php`. As do cadastro estão no sistema de variáveis.
- A seção "Modelos disponíveis globalmente" da edição continua listando só modelos do Gemini.
