# 03 — Uma Só Frota de Governança, Muitos Repositórios

## O custo de reinventar a disciplina por base de código

Cada nova base de código que um agente toca costuma exigir reexplicar, do
zero, como aquele projeto funciona: suas convenções, suas armadilhas, seu
processo de release. Esse custo é pago de novo a cada vez, em cada
repositório.

## Um catálogo de skills compartilhado, propagado de propósito

O **Conn2Flow AI Workspace** centraliza um catálogo de Core Skills
(conhecimento de produto e infraestrutura: acesso a banco de dados,
compilação de recursos, arquitetura Tailwind, armadilhas de shell/Windows,
entre outras) mais skills de fluxo SDD (como iniciar uma fatia, continuar
um lote, levantar uma mudança de especificação, revisar um lote, podar
memória). O mesmo catálogo — não uma cópia reinventada por projeto — é
instalado em todo repositório que adota o framework, em todas as
ferramentas de IA suportadas (Claude Code, GitHub Copilot, Cursor,
Antigravity/Gemini, OpenAI Codex).

## Memória local, forma compartilhada

Propagação não significa que todo repositório se comporte de forma
idêntica: cada um mantém suas **próprias** memórias locais de Chefia e de
Execução, seu próprio histórico em `sdd/` e seu próprio backlog. O que é
compartilhado é a *forma* da governança — os mesmos papéis da Tríade, os
mesmos portões de intake, a mesma fronteira entre escrita normativa e
operacional — de modo que um agente transitando entre repositórios não
precise reaprender como as decisões devem fluir, apenas o que é específico
daquela base de código.

## Onboarding em um clique

A extensão VS Code **Conn2Flow Dev Tools** transforma a adoção deste
modelo em uma ação de um clique: clonar um repositório e montar um novo
projeto-satélite com a estrutura SDD e o catálogo de skills já conectados,
em vez de um bootstrap manual e sujeito a erro a cada novo projeto.

## Por que isso importa para o modelo de sobreposição privada

Um projeto privado de cliente é tipicamente uma sobreposição fina sobre o
CMS core: seu próprio conteúdo, suas próprias customizações, às vezes suas
próprias skills privadas — mas a mesma governança e o mesmo core. Propagar
o framework, em vez de duplicá-lo manualmente, é o que mantém esse modelo
de sobreposição sustentável conforme o número de projetos cresce.
