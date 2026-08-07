# BL-016 — Design system Tailwind e desacoplamento do Fomantic UI

- **Tipo:** Epic/Architecture/UX
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** interface administrativa, componentes compartilhados e build CSS
- **Relacionados:** BL-013, BL-017, BL-018, BL-019

## Diagnóstico

O core contém centenas de arquivos com marcadores Semantic/Fomantic, distribuídos por módulos, recursos, assets, banco e bibliotecas. O bootstrap em `gestor.php` ainda usa Fomantic 2.9.4 por CDN quando `framework_css` é `fomantic-ui` ou quando o framework está vazio, tornando-o o fallback real do sistema administrativo.

Tailwind 4 já existe no projeto, mas o build atual procura principalmente HTML/CSS/JS em módulos e recursos. Classes geradas em PHP, JSON, dados do banco e overlays privados podem não entrar no CSS final. Apenas trocar classes tela a tela produziria regressões e acoplamento novo.

## Resultado desejado

Criar um design system Conn2Flow baseado em Tailwind, acessível e independente da biblioteca de componentes escolhida. Módulos consomem componentes e tokens do sistema; Flowbite, Preline ou outra solução ficam atrás de adaptadores substituíveis.

## Decisões a validar em ADR/PoC

1. **Camada própria mínima:** componentes server-rendered e primitives nativas, com JavaScript vanilla.
2. **Flowbite:** ampla coleção Tailwind e API vanilla/data attributes.
3. **Preline:** integração Tailwind v4, componentes JS e reinicialização de DOM dinâmico.

Headless UI não é candidato natural para este stack porque sua implementação oficial atende React/Vue. A escolha deve considerar acessibilidade real, licença, CSP, bundle, manutenção, inicialização após AJAX e capacidade de customização — não apenas quantidade de componentes.

## Frentes de trabalho

### Fundação visual

- tokens de cor, tipografia, espaçamento, elevação, borda, estado e motion;
- temas claro/escuro e contraste;
- política de ícones sem classes Semantic;
- layouts responsivos e shell administrativo;
- catálogo vivo/Storybook equivalente para PHP.

### API estável

- helpers/componentes Conn2Flow para botão, campo, mensagem, badge, card, menu, tabela e navegação;
- classes de fornecedor proibidas na API pública dos módulos;
- atributos `data-c2-*` para comportamentos;
- lifecycle único de montar/desmontar/reinicializar após AJAX.

### Build Tailwind

- incluir PHP, JSON, templates, dados gerados e overlays privados nas fontes relevantes;
- evitar concatenação arbitrária de classes; usar mapas/safelist controlada;
- gerar artefato reproduzível e versionado por release;
- medir CSS não utilizado e orçamento de bundle;
- impedir dependência de CDN no runtime administrativo.

### Acessibilidade e qualidade

- WCAG 2.2 AA como alvo;
- foco visível, teclado, leitor de tela, redução de movimento e touch;
- testes de contraste e auditoria automatizada complementada por teste manual;
- estados de loading, vazio, erro, sucesso, bloqueio e sessão expirada padronizados.

## Critérios de aceite para futura implementação

- módulo novo não referencia `ui`, `semantic`, `fomantic` ou APIs diretas do fornecedor escolhido;
- design tokens e componentes possuem documentação e testes;
- Tailwind compila todas as classes usadas na composição core + overlay;
- nenhuma dependência administrativa crítica vem de CDN;
- shell, navegação e formulários funcionam com teclado e em viewport móvel;
- existe caminho de coexistência temporária sem fazer Fomantic ser o fallback padrão da v3.

## Referências para a decisão

- Flowbite: <https://flowbite.com/docs/getting-started/introduction/>
- Preline: <https://preline.co/docs/>
- Preline JavaScript: <https://preline.co/docs/preline-javascript.html>
- Headless UI: <https://headlessui.com/>

## Próxima decisão

Executar PoC pequeno do shell, formulário, modal e dropdown com as três estratégias e registrar ADR antes da migração em massa.
