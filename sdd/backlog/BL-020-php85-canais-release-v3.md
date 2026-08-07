# BL-020 — PHP 8.5, canais de release e atualização segura para v3

- **Tipo:** Architecture/Release Engineering/Compatibility
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** instalador, atualizador, artefatos e bootstrap do Gestor 3.x
- **Relacionados:** BL-011, BL-012, BL-013, BL-029, BL-030, BL-031, BL-032

## Diagnóstico

Banco v2, interface v2 e `admin-paginas-v2` já usam sintaxe/recursos de PHP 8.5 e falham no parse do PHP 8.4. Isso descreve o código experimental atual, não necessariamente o requisito final do produto. O BL-031 decidirá se essas construções serão backportadas para PHP 8.3/8.4. Os workflows atuais ainda testam PHP 8.4 e parte do empacotamento usa PHP 8.2. Não existe preflight completo de requisito PHP antes de aplicar um release.

Um fallback executado dentro de código PHP 8.5 não protege um servidor antigo: o parser falha antes de qualquer condição. A decisão precisa acontecer antes do download/deploy, em código compatível com a linha 2.x.

## Política proposta

- Gestor 3.x declara uma versão mínima única após a PoC do BL-031 e extensões explicitamente declaradas;
- PHP 8.5 permanece referência superior de desenvolvimento, sem implicar sozinho requisito mínimo 8.5;
- Gestor 2.x torna-se canal LTS por uma janela definida;
- atualizador escolhe a versão mais recente compatível, não simplesmente a tag mais recente;
- ambiente incompatível permanece integralmente na 2.x e mostra aviso acionável ao administrador;
- nunca instalar mistura parcial de arquivos 2.x/3.x.

## Manifesto de release

Publicar ao lado do ZIP e checksum um manifesto assinado/versionado contendo:

- versão/tag e canal;
- faixa PHP e extensões;
- requisitos de banco/sistema quando aplicável;
- versão mínima do atualizador/instalador;
- formato de dados/migrações;
- tamanho, checksum e assinatura;
- compatibilidade de downgrade/rollback.

## Fluxo do atualizador

1. obter e validar manifesto antes do ZIP;
2. comparar requisitos com o ambiente e o estado instalado;
3. se incompatível, não criar sessão de deploy nem alterar arquivos/banco;
4. informar versão disponível, PHP atual, PHP necessário e caminho de upgrade;
5. oferecer canal 2.x LTS quando houver release compatível;
6. em ambiente compatível, fazer staging, lint, verificação de extensões e espaço;
7. aplicar release atomicamente, executar migrações e confirmar health check;
8. reverter o slot/release completo se falhar antes da confirmação.

## Instalador e bootstrap

- instalador deve checar requisitos antes de baixar/extrair;
- o stub de compatibilidade precisa ser interpretável pela menor versão PHP suportada pelo instalador;
- se uma instalação v3 sofrer downgrade externo do PHP, um front controller mínimo compatível deve exibir diagnóstico estático ou selecionar um slot válido; não incluir arquivos v3 antes da verificação;
- nunca tentar downgrade automático de dados sem uma migração reversa declarada.

## CI/release

- lint/testes da v3 na menor versão declarada e no PHP 8.5;
- executar a v3 no ambiente Docker isolado PHP 8.5 + MySQL 8.4 LTS do BL-029;
- preservar PHP 8.3/MySQL 8.0 como baseline legado da 2.9.x, sem compartilhar volumes/sites com a v3;
- usar a matriz do BL-030 para testar a 2.9.x no runtime novo, sem presumir retrocompatibilidade;
- teste negativo: atualizador 2.x em PHP 8.4 recusa v3 antes do deploy;
- testes de upgrade 2.x→3.x, falha de migração e rollback atômico;
- build reproduzível e verificação de que o artefato não contém `.git` aninhado;
- matriz explícita de sistema operacional, servidor, banco e extensões;
- documentação e aviso antecipado no painel 2.x.

## Critérios de aceite para futura implementação

- PHP incompatível nunca recebe arquivos v3;
- o administrador recebe orientação clara, sem loop AJAX ou erro de parse;
- release interrompido deixa uma versão completa e inicializável;
- manifesto, ZIP e checksum referem-se exatamente ao mesmo artefato;
- canal LTS e fim de suporte têm datas/política aprovadas;
- compatibilidade é testada na composição core + overlay privado.

## Próxima decisão

Definir a versão mínima da v3 pelo BL-031, a versão mínima do stub/atualizador, duração do canal 2.x LTS e estratégia de deploy atômico (slots, diretórios versionados ou equivalente) antes da primeira release v3.
