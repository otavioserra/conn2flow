# BL-051 — Backup, restore e recuperação de desastre da v3

- **Tipo:** Reliability/Data Protection/Operations/Release Engineering
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** backup integral da instalação, restauração, rollback, continuidade e ensaios
- **Relacionados:** BL-011, BL-020, BL-024, BL-029, BL-030, BL-032, BL-045, BL-046, BL-047, BL-050

## Problema

Existem snapshots e rotinas pontuais ligados a atualização/conteúdo, mas a v3 precisa tratar a instalação como conjunto consistente: banco, arquivos enviados, configuração recuperável, chaves necessárias, versão do código e migrations. Um backup que nunca foi restaurado é apenas uma hipótese.

## Decisão proposta

Definir um contrato de backup/restore por instalação e ensaiá-lo automaticamente em ambientes descartáveis. Separar:

- rollback transacional ou de deploy imediato;
- backup operacional recorrente;
- exportação administrativa de conteúdo;
- disaster recovery completo.

## Conteúdo e manifesto

Cada conjunto de recuperação deve identificar:

- installation/site id, versão do app e schema;
- banco/dialeto e checksums;
- uploads/arquivos necessários e permissões relevantes;
- configuração não secreta;
- referências às chaves/segredos que precisam existir no destino;
- instante consistente, método, tamanho e política de retenção;
- resultado da verificação/restauração mais recente.

Segredos não devem ser copiados em claro para um pacote comum. O plano precisa explicar quais chaves são recuperadas por cofre/processo separado e o impacto de sua perda.

## Estratégia

- snapshots/dumps consistentes com escrita coordenada;
- criptografia, integridade e acesso mínimo;
- cópia fora do host/volume principal em produção;
- retenção e expiração alinhadas ao BL-050;
- restore em caminho/banco novo antes de qualquer substituição;
- runbook para perda parcial, corrupção, release defeituoso e credencial comprometida;
- RPO/RTO definidos por perfil de instalação, não presumidos pelo software.

## Upgrade e rollback

- preflight verifica espaço, compatibilidade e backup restaurável;
- migrations destrutivas exigem checkpoint e estratégia aprovada;
- rollback de código não é prometido quando schema/dados são incompatíveis;
- upgrade MySQL/PostgreSQL e 2.9→3 usa cópia, valida invariantes e mantém origem intacta até aceite;
- arquivos e banco devem voltar ao mesmo ponto lógico.

## Testes mínimos

- backup + restore em instalação vazia com checksum/invariantes;
- banco + uploads consistentes durante escrita concorrente;
- backup truncado, chave ausente e manifesto adulterado falham fechado;
- restore entre patches suportados e ensaio 2.9→3;
- MySQL e PostgreSQL conforme matriz;
- core + overlay privado e módulos opcionais;
- dados eliminados/retidos conforme BL-050 após restore.

## Critérios de aceite

- todo release candidate executa ao menos um restore automatizado;
- backup possui manifesto, integridade, retenção e owner;
- recuperação inclui banco, arquivos e configuração necessária de forma consistente;
- RPO/RTO e limitações são documentados por cenário;
- runbooks e evidências são bilíngues e não contêm segredos;
- promoção da v3 exige ensaio humano de recuperação em ambiente isolado.

## Próxima ação

Promover inventário dos dados recuperáveis e um primeiro ensaio automatizado da 2.9.x em ambiente descartável. Usar o resultado como baseline antes de autorizar migrations destrutivas da v3.
