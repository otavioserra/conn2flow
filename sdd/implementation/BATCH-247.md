# BATCH-247 — REQ-238

- Projeto: conn2flow, `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`.
- Branch: `feat/req-238`; autonomia: `autonomo_monitorado`.
- Status: implemented-pending-homologation; validação técnica em 2026-10-05.
- Escopo: [REQ-238](../human-requests/req-238.md), blocos A–D; Site coordenado no [BATCH-098](../../../conn2flow-site/sdd/implementation/BATCH-098.md).

## Live Todo List

- [x] A: avatar 32px e nome visível; dropdown 320px, alinhado e contido no viewport; atalhos somente ícone, rótulo acessível e tooltip.
- [x] B: sidebar compartilhada, links 500, ativo 600 e cabeçalhos 12px/600; conferida nos 22 módulos.
- [x] C1: badge sky do grupo em pt-br/en.
- [x] C2/C3: alça única inferior direita; altura 180–780px em passos de 60px; blueprint durante drag/resize, cancelamento sem persistência.
- [x] C4: busca instantânea; tipo/registro atuais selecionados, inclusive registro fora da primeira página; reset e navegação por teclado.
- [x] C5: iframe sandbox allow-scripts, origem opaca; estilos SQL da fundação, template/registro e renderizador transportados com o widget.
- [x] C6: switch com role/aria-checked e textos traduzidos para ativado/desativado.
- [x] C7: manual/docs de módulos privados apontam para documentation/<id>/; identidade Core explícita, independente da árvore pública no destino.
- [x] D1: cards brancos, títulos e ícones consistentes; espaçamento das ações IA.
- [x] D2: abas canônicas em Core/Site; submissões com Dados, Respostas e JSON, três painéis funcionais.
- [x] D3: inspeção autenticada dos 22 módulos, 38 telas em desktop/390px, sem overflow nem erros de console.
- [x] Vitest/PHPUnit Core e Site aprovados.
- [x] Pipeline oficial Lab, manutenção desligada e conferência do conteúdo.
- [x] Revisão dos diffs, versões dos recursos, evidências e preparação dos commits locais.
- [ ] Homologação humana e integração em main.

## Implementação e correções verificadas

O CSS compartilhado controla perfil, sidebar, títulos e abas; os templates administrativos envolvem formulários/listas em cards brancos. Ícones dos títulos derivam do item atual da sidebar quando o título não possui ícone. O upload de arquivos teve o input invisível contido para eliminar overflow a 390px. No Site, o Host Manager mantém um único ícone no título; subscriptions-config deixou de solicitar um JS inexistente. As classes visuais precedem a ponte JS legada sem remover seus hooks.

Os widgets preservam larguras 4/6/8/12 e preferências legadas; altura em pixels é adicional. O renderizador recupera CSS do próprio conteúdo e do template nativo, incluindo estilos coletados durante renderização, sem copiar CSS do painel. O iframe de origem opaca impede acesso dos scripts ao documento pai. Preferências de widgets e favoritos utilizados nos testes foram restaurados.

O Site fornece 58 rotas de guias internos (29 módulos, pt-br/en), com nome, descrição e links das telas nativas. São guias de acesso derivados dos recursos existentes; não representam novos manuais completos escritos para cada módulo privado.

O primeiro pipeline revelou manifestos/partes de sementes do Site sobrevivendo à etapa Core e tomando precedência sobre PaginasData.json. A sincronização oficial Core usa agora --delete exclusivamente no diretório gerado db/data; teste com rsync real comprova remoção das sementes antigas e preservação de contents. Nenhuma cópia manual foi usada.

## Evidências e resultados

| Verificação | Resultado |
| --- | --- |
| Core Vitest | 45 arquivos, 537 testes aprovados |
| Core PHPUnit | 1.597 testes, 15.849 asserções; zero falhas/erros |
| Site Vitest | 2 arquivos, 13 testes aprovados |
| Site PHPUnit | 7 testes, 1.370 asserções; zero falhas/erros |
| Navegador | 239 verificações de módulos + 14 widgets + 8 topbar = 261 aprovadas |
| Prova negativa JS | versão anterior falha em 3 verificações novas; implementação atual passa |
| Prova negativa PHP | versão anterior falha em estilos e substituição das sementes; implementação atual passa |
| Pipeline Lab | project:update-all conn2flow-site-local --confirmar-remoto: saída 0, css:rebuild executado, manutenção desligada |
| Conteúdo do Lab | 775 arquivos de código comparados, nenhuma diferença nem sobra após normalização bilateral CRLF/LF |

Inventário: [req238-validation.json](../validation/req238-validation.json). Scripts reproduzíveis e screenshots estão em [validation](../validation/req238-browser.cjs) e [evidence-req238](../validation/evidence-req238/modules.json). Inspeção visual incluiu todas as capturas desktop/mobile e os estados reais de widgets, perfil e abas.

Avisos não bloqueantes: PHPUnit registra 4 depreciações, 3 depreciações PHPUnit e 4 testes pulados da suíte existente. O pipeline avisa sobre JS vazio de admin-categorias, páginas públicas sem bundle e ausência de ssh_public_path opcional. project:verify acusa 295 diferenças por comparar CRLF remoto com LF local sem normalizar ambos: [comparação complementar](../validation/req238-code-hashes.php) demonstra conteúdo idêntico nos 775 arquivos. O comando de produção não foi alterado para ampliar o escopo desta requisição.

## Governança

A rotina oficial ai:archive-sdd arquivou 14 requisições e 14 lotes históricos (28 arquivos), ajustou 71 links e manteve dez arquivos ativos por raiz. Seis links históricos apontavam para relatórios inexistentes: os rótulos foram preservados com anotação de ausência. Não houve poda de memória nem mudança em SPEC.md. Req-239 e demais intakes permaneceram fora deste lote. Compilados, Data.json, metadados de versão e assets acompanham as fontes. Commits são locais; homologação, merge e push permanecem pendentes.
