# Decisões 116 a 122

## DEC-116 - 2026-08-21 - accepted

Atualização Global da Documentação, Readmes, Changelogs e Workflows de Release (Gestor v2.9.39 e Instalador v1.5.6) (BATCH-127). Decisões desta rodada:

1. **Sincronização Integrada da Linha de Lançamento**: Todas as notas de versão em `CHANGELOG.md` e `CHANGELOG-PT-BR.md` serão consolidadas na versão `[2.9.39] - 2026-08-21`, categorizando as grandes entregas dos lotes BATCH-049 a BATCH-126 (Live Editor, Layouts Tailwind v4, Pull System & Sync Declarativo, Suporte Híbrido a PDF e Streaming, PAT, PayPal & Stripe, CLI c2f).
2. **READMEs Alinhados ao Release v2.9.39 e Instalador v1.5.6**: A seção "Última Versão" dos arquivos `README.md` e `README-PT-BR.md` passa a refletir a versão `v2.9.39` com seus destaques executivos, e os links e comandos de download passam a apontar para `instalador-v1.5.6/instalador.zip`.
3. **Descritivos Estruturados de Release nos Workflows do GitHub Actions**: Os workflows `release-gestor.yml` e `release-instalador.yml` recebem corpos de release (`body: |`) estruturados e ricos em detalhes, permitindo geração automática de notas no GitHub Releases compatíveis com os runners Node 24.

---

## DEC-117 - 2026-08-21 - accepted

Correção de Reload em Erro de CSRF/Sessão, Mapeamento de Ícones de Projetos, Alternância de Botões de Menu e Saneamento do Lucide (req-125 / BATCH-127). Decisões desta rodada:

1. **Reload Limpo e Fim do Loop de Sessão no Login**: Na tela de erro de CSRF inválido/sessão expirada (`gestor_csrf_resposta_invalida()`), o botão "Voltar" passa a verificar a rota de origem (`/signin/`) e disparar recarregamento limpo / `location.replace`, garantindo a obtenção de um novo token CSRF e novo cookie de sessão no servidor, sem reciclar formulários expirados do histórico (bfcache) do navegador.
2. **Mapeamento Canônico de Ícones de Módulos de Projetos Derivados**: Todos os módulos de extensões e projetos irmãos (`catalogo-3d`, `catalogo-3d-grupos`, `catalogo-3d-itens`, `conexoes-sociais`, `gateways-pagamentos`, `publicador-midias-sociais`, `social-apps`, `arquivos`, `admin-arquivos`, `modulos-grupos-distribuidos`) passam a declarar o par oficial de ícones Fomantic e Lucide (em formato kebab-case estrito) tanto em `ModulosData.json` quanto na migração Phinx `20260821100000_alter_modulos_update_icones_projetos.php`.
3. **Alternância Contextual de Botões no Layout Administrativo Tailwind**: O botão `[data-admin-abrir]` recebe `lg:hidden` no HTML inicial para evitar visualização concorrente com o menu aberto no desktop; o runtime `admin-tailwind.js` alterna as classes `hidden` nos métodos `abrir()` e `fechar()`, mantendo visibilidade contextual exclusiva em mobile e desktop.
4. **Saneamento em Duas Camadas do Lucide para Eliminar Warnings de Console**: Implementada validação de identificadores Lucide válidos (`/^[a-z0-9]+(?:-[a-z0-9]+)*$/`) no backend (`gestor_pagina_menu_icone_lucide_atributo`) e no frontend (`desenharIcones` em `admin-tailwind.js`), omitindo `data-lucide` para nomes compostos legados do Fomantic e mantendo o console do desenvolvedor 100% limpo.

---

## DEC-118 - 2026-08-24 - accepted

Extrator Semântico de Tokens do Tailwind para o Assistente de IA no Editor HTML (req-127 / BATCH-129).
Decisões desta rodada:

1. **Contexto de marca por extração, não por anexo**: o Assistente passa a receber a paleta e as
   classes do projeto pela tag `{{theme_tokens}}`, alimentada por
   `html_editor_ia_extrair_tokens_tema()`. O `browser-contract.css` bruto **não** é enviado: o do
   `transformamp` tem 78.485 bytes (~20 mil tokens) e o extraído tem 1.482 (98,11% de corte).

2. **O corte de valor é por FORMA, não por nome**: descarta-se qualquer declaração com `data:`,
   `url()` acima de 80 bytes ou valor acima de 120 bytes. É o que remove os SVGs embutidos das
   `--art-*-mask` sem precisar de allowlist por nome, e vale para contrato futuro sem manutenção.

3. **O corte por orçamento é round-robin entre namespaces**: na varredura sequencial as 63 cores do
   `transformamp` consomem o teto inteiro e o namespace pequeno do contrato desaparece. Uma rodada
   por namespace garante representação de cada família; a saída volta à ordem natural, porque o
   round-robin decide quem entra, nunca em que ordem sai.

4. **A allowlist cobre todo namespace do v4 que vira utility com nome próprio**: além dos
   `--color-*`, `--font-*` e `--spacing-*` citados no intake, entram `--text-`, `--radius-`,
   `--shadow-`, `--breakpoint-`, `--container-`, `--leading-` e `--tracking-`. Sem eles a IA escreve
   valor arbitrário (`rounded-[12px]`) em vez do token da marca, e o custo medido é marginal.

5. **A diretriz é bloco condicional com o par de marcadores da convenção do core**: projeto sem
   contrato, ou fora do Tailwind, tem a seção inteira removida por `modelo_tag_del()`. Prompt que
   manda "usar prioritariamente" uma lista em branco é pior do que prompt sem a seção.

6. **A frase-guia vive no `.md` do modo, o bloco injetado é CSS puro**: a instrução que ensina a
   derivar a utility do token (`--color-mp-red` → `bg-mp-red`) é texto de produto e pertence ao
   artefato multi-idioma; o valor injetado tem de ser idêntico nos dois idiomas.

7. **`{{css_compiled}}` é opt-in e resumido a nomes de classe**: o valor cru é o output inteiro do
   Tailwind e devolveria o payload à casa dos 20 mil tokens. Nenhum modo do core declara a tag.

8. **A fonte dos modos é `resources/<lang>/ai_modes/<id>/<id>.md`**: `gestor/db/data/ModosIaData.json`
   é artefato compilado por `atualizacao-dados-recursos.php` e nunca deve ser editado à mão.

## DEC-119 - 2026-08-28 - accepted

404 em imagens estáticas com hífen/espaço e colisão de upload sem espaço (req-140 / BATCH-143).
Decisões desta rodada:

1. **A correspondência é pelo RESULTADO da sanitização, não por troca adivinhada de hífen por
   espaço**: o intake sugeria `preg_replace('/-(?=\(\d+\))/', ' ', ...)` ou substituição controlada
   de hífens. Nenhuma das duas alcança um nome que tenha hífen real E espaço
   (`Foto-Final de Praia.webp` publicado como `Foto-Final-de-Praia.webp`), e testar as combinações
   de hífen custa 2^n acessos a disco numa string que o requisitante controla — um DoS barato.
   Comparar `arquivo_nome_sanitizar($entradaFisica) === $segmentoPedido` acha o arquivo exatamente
   quando ele é o que geraria aquela URL. O critério de aceite é o comportamento, e o mecanismo
   escolhido cobre estritamente mais casos do que o sugerido.

2. **A resolução é segmento a segmento**: miniatura (`mini/Ela-(1).webp`) e diretório com espaço
   (`Minha Pasta/`) caem no mesmo mecanismo, sem código dedicado a cada caso.

3. **Duas guardas por hífen antes de listar diretório**: a sanitização só PRODUZ hífen, então um
   caminho sem hífen nenhum não pode divergir de um nome físico. O fallback inteiro fica atrás dessa
   checagem (nem carrega a biblioteca) e cada segmento a repete antes do `scandir`. As varreduras
   automáticas de 404 param sem custo de I/O.

4. **O fallback descobre o nome; não autoriza o envio**: o caminho encontrado continua passando por
   `arquivo_estatico_resolver_autorizado()`. A garantia de containment permanece num único lugar.

5. **O fallback fica restrito ao ramo de `contents-path`**: `assets/` e `modulos/` não recebem nome
   escolhido por usuário e não têm o problema; estender o mecanismo a eles só ampliaria superfície.

6. **`rawurldecode` é variante, não substituto**: a reescrita do gestor usa a flag `[B]` e o PHP já
   recebe o caminho decodificado, então a Tentativa 1 do intake é inócua neste ambiente — mas cobre
   servidor sem essa flag. A variante só entra depois de passar pela mesma guarda de traversal.

7. **O desempate de colisão mora na biblioteca e sanitiza o próprio resultado**:
   `arquivo_nome_colisao()` nasce ao lado de `arquivo_nome_sanitizar()`, mesmo precedente do
   `arquivo_mime_por_extensao()` no BATCH-141. Trocar apenas o espaço por hífen não bastaria: um
   nome-base terminado em hífen produziria `base--(1)`, que a sanitização colapsa para `base-(1)`,
   reabrindo a mesma divergência. A invariante do contrato é `sanitizar(n) === n`, e ela virou teste.

8. **Os nomes legados no disco não são renomeados por este lote**: renomear em massa é mudança de
   DADOS, com risco de quebrar referências já gravadas em páginas publicadas. O fallback os mantém
   servidos; a normalização fica para intake próprio se a chefia quiser encerrar o custo de I/O.

## DEC-120 - 2026-08-29 - accepted

**Aparência de estado no `galleries` resolvida por cadeia de recursos, com o CSS acompanhando as
classes (BATCH-147).**

O BATCH-144 acrescentou `pointer-events-none cursor-default` aos quatro templates do core e a home
continuou publicando imagens sem link com cursor de mão. Duas razões medidas:

1. A galeria **não renderiza a partir do template**. Ela guarda em `galleries.html` uma cópia,
   tirada quando o operador escolheu o modelo, que congela com `user_modified = 1` — a flag que por
   design bloqueia o sync de recursos.
2. O template em uso na home do `transformamp` é do **projeto** (`galeria-home`), fora do alcance de
   qualquer correção feita no core.

**Decisões:**

1. **A resolução é uma cadeia de RECURSOS, não um default em PHP**: HTML da própria galeria →
   template de origem (`fields_schema.template_id`) → `galleries-estados`, novo recurso do core. O
   segundo degrau é o que alcança as cópias congeladas, permitindo que corrigir o recurso baste, sem
   exigir que o operador reescolha o modelo em cada galeria já publicada. Nenhum degrau escreve
   classe em PHP: os três são recursos e o compilador Tailwind os enxerga.

2. **O recurso que declara as classes declara também o CSS delas**: com a cadeia no lugar, as seis
   âncoras receberam as classes e `.cursor-default` ficou **sem regra nenhuma** na página — o HTML do
   widget só existe em runtime e nunca chega ao compilador. Emitir as classes sem a regra é a mesma
   falha silenciosa de antes, um degrau adiante.

3. **`galleries-estados` usa `target` próprio**: o dropdown de modelos filtra por
   `target='galleries'`; um recurso interno com o mesmo alvo apareceria como opção de galeria.

4. **A resolução acontece uma vez, fora do laço de itens**: ela é constante para todos os itens, e o
   degrau 2 consulta o banco.

## DEC-121 - 2026-08-29 - accepted

**A tela `variables` não oferece ações sobre a tabela `modulos`; o tipo de campo passa a ser
`editor-texto` (BATCH-147).**

O relato foi de 404 em `variables/adicionar/` e `variables/editar/`. As páginas de fato nunca
existiram, mas os quatro botões herdados do scaffold CRUD apontavam **todos** para a tabela
`modulos`: `status` desativava e `excluir` aplicava `status='D'` no módulo inteiro. Um clique em
"Excluir" na tela de variáveis do `usuarios-perfis` apagava o módulo `usuarios-perfis`.

**Decisões:**

1. **Remover os quatro botões em vez de criar as páginas faltantes**: criar `adicionar/` e `editar/`
   atenderia o pedido literal e deixaria de pé duas ações destrutivas. Esta tela edita as VARIÁVEIS
   de um módulo, já é a tela de edição (`interface-opcao = alteracoes`) e já inclui variável pelo
   card `adicionar` de `configuracao_administracao()`.

2. **O tipo de campo descreve o que o campo é, não quem o fabrica**: `tinymce` → `editor-texto`.
   Enquanto o nome do fornecedor for a chave, cada troca de editor vira ou uma mentira na interface
   ou uma migração de dados.

3. **O alias de leitura não é preguiça, é a janela do deploy**: `configuracao_campo_tipo()` trata
   `tinymce` como `editor-texto`. Entre o deploy do código e a aplicação da migração o banco ainda
   diz `tinymce`, e sem o alias `$campo[$tipo]` erra a chave e o campo **some da tela** — pior que um
   erro visível. A migração encerra a dívida; o alias cobre o intervalo.

## DEC-122 - 2026-08-29 - accepted

**Assets de terceiros versionados em `gestor/assets/vendor/`, com verificação de certificado
inegociável no download (BATCH-146).**

`assets-externos.php` já resolvia "local primeiro, CDN como fallback", mas `assets/vendor/` nunca
existiu: o fallback era o único caminho e o sistema seguia 100% dependente de CDN, com a aparência
de já ter migrado.

**Decisões:**

1. **Verificação de certificado nunca é desligada**: o PHP CLI do Windows não traz CA bundle e as 28
   baixas falharam de uma vez com `unable to get local issuer certificate`. `CURLOPT_SSL_VERIFYPEER
   => false` está descartado: são arquivos servidos como biblioteca em toda tela do gestor, e
   baixá-los por canal não verificado é pior do que continuar no CDN. A cadeia cai para o binário
   `curl` do sistema, que valida contra o repositório de certificados do SO.

2. **Só HTTP 200 vira arquivo**: um 404 do CDN devolve corpo HTML. Gravá-lo com nome de biblioteca
   faria o resolvedor servi-lo como válido, e a tela quebraria sem nenhuma pista de rede.

3. **Os assets são versionados**: o `vendor/` do Composer no `.gitignore` também os engolia, o que
   faria a migração valer só na máquina de quem rodou o comando — em produção o resolvedor cairia no
   CDN em silêncio. São 2,9 MB em troca de o que roda em produção ser o que foi revisado.

4. **Versão é versão, não faixa**: `quill@2` permitia qualquer publicação 2.x entrar sem revisão. E
   como `quill-content.css` é gerado a partir dessa versão, a faixa permitia o editor e a página
   publicada divergirem sem ninguém ter mexido em nada. A versão passa a sair do registro.

5. **A ordem de carga é propriedade do registro**: `codemirror.min.js` define o objeto que todo
   addon estende. Eram 161 tags em 7 arquivos, cada cópia com sua própria lista e ordem.

