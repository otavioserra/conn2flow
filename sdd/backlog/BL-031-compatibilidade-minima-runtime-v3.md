# BL-031 — Compatibilidade mínima e reversa dos runtimes v3

- **Tipo:** Architecture/Compatibility/Release Engineering
- **Status:** IN-DISCUSSION
- **Data:** 2026-08-07
- **Escopo:** PHP, MySQL e PostgreSQL aceitos pelo Contao Flow 3.x
- **Relacionados:** BL-011, BL-020, BL-021, BL-029, BL-030, BL-032

## Objetivo

Separar três conceitos que não podem ser tratados como equivalentes:

1. versão mais nova usada para desenvolver e detectar problemas cedo;
2. menor versão na qual o produto promete funcionar;
3. versão/formato que criou os dados existentes.

Usar PHP 8.5 ou MySQL 8.4 no Docker não obriga tecnicamente o servidor final a usar essas mesmas versões. A compatibilidade com versões inferiores, porém, só existe quando código, dependências, SQL e migrations respeitam a menor versão e passam na matriz correspondente.

## Resposta objetiva

### PHP 8.5 → PHP anterior

Um arquivo PHP não é compilado pelo PHP 8.5 para depois “rodar para trás”. O servidor final interpreta novamente o código-fonte. Portanto:

- código que usa apenas sintaxe/API disponível na versão mínima pode rodar tanto nela quanto no PHP 8.5;
- qualquer sintaxe nova causa erro de parse antes de fallback, feature flag ou tratamento de exceção;
- funções/classes/extensões novas também falham quando chamadas numa versão anterior;
- Composer e extensões precisam aceitar a mesma versão mínima;
- executar no PHP 8.5 pode revelar incompatibilidades/depreciações do código antigo, logo também requer testes.

O `banco-v2.php` atual usa `|>`, `clone with`, `array_first()`, `array_last()` e `#[NoDiscard]`. No estado atual ele requer PHP 8.5 e não carrega no PHP 8.4. Para a v3 suportar PHP anterior, essas construções precisam ser removidas/substituídas antes do cutover.

### MySQL 8.4 → servidor MySQL 8.0

Uma aplicação desenvolvida contra MySQL 8.4 pode conectar ao 8.0 se todas as consultas, tipos, collations, autenticação e configurações usadas também existirem no 8.0. Isso não é automático.

- SQL ou recursos exclusivos do 8.4 não executam no 8.0;
- o data directory do 8.4 não deve ser simplesmente aberto pelo binário 8.0;
- a Oracle admite rollback 8.4→8.0 por dump/load ou replicação somente quando nenhuma funcionalidade nova do servidor foi aplicada aos dados;
- upgrade 8.0→8.4 é suportado, mas exige upgrade checker e correção de opções, palavras reservadas e estruturas incompatíveis;
- migrations devem ser escritas para o menor servidor declarado, não para o Docker mais novo.

Logo, o Contao Flow pode oferecer compatibilidade MySQL 8.0/8.4, mas precisa testar os dois. “Funciona em 8.4” não prova “funciona em 8.0”.

### PostgreSQL 18 → PostgreSQL anterior

O mesmo princípio vale para PostgreSQL:

- SQL e driver podem mirar uma versão mínima e serem testados até a 18;
- o data directory não é backward-compatible entre majors;
- upgrades de major usam `pg_upgrade` ou dump/restore;
- a versão minor corrente de cada major suportada deve ser usada.

PostgreSQL 18.4 será o ambiente de desenvolvimento mais novo, mas a matriz pode suportar majors anteriores ainda mantidas se o contrato não usar recursos exclusivos do 18.

## Matriz provisória recomendada

| Componente | Mínimo candidato | Referência de desenvolvimento | Observação |
| --- | --- | --- | --- |
| PHP | 8.3 | 8.5 | 8.2 encerra suporte de segurança em 2026-12-31; reavaliar antes do RC |
| MySQL | série 8.0, patch mínimo a homologar | 8.4 LTS | testar ao menos 8.0.43 e 8.4.x aprovado |
| PostgreSQL | 16, sujeito à PoC | 18.4 | testar minors correntes de 16, 17 e 18 |

PHP 8.3 é candidato, não decisão final. Se a data do release aproximar-se do fim de suporte da 8.3 ou o custo de backport for alto, elevar para PHP 8.4. PHP 8.2 não é recomendado como baseline novo da v3 porque resta pouca janela de segurança.

## Política de desenvolvimento

- o código-fonte v3 deve passar lint na menor versão PHP declarada;
- CI também executa a versão PHP mais nova aprovada para detectar incompatibilidades futuras;
- `composer.json` declara `php` e extensões coerentes com a matriz;
- análise estática bloqueia símbolos/sintaxe acima do baseline, salvo pacote isolado com preflight;
- SQL comum deve ficar no subconjunto compartilhado entre os servidores declarados;
- recurso exclusivo deve passar por capability explícita, migration condicionada e fallback aprovado;
- manifesto de release informa mínimos, versões testadas e combinações não suportadas;
- o atualizador recusa a instalação antes do deploy quando o mínimo não é atendido.

## Matriz de CI proposta

| Aplicação | PHP | Banco | Gate |
| --- | --- | --- | --- |
| v3 | mínimo candidato | MySQL 8.0 homologado | bloqueante |
| v3 | PHP 8.5 | MySQL 8.4 LTS | bloqueante |
| v3 | mínimo candidato | PostgreSQL 16 homologado | bloqueante antes do RC |
| v3 | PHP 8.5 | PostgreSQL 18.4 | bloqueante antes do RC |
| 2.9.x | PHP 8.3 | MySQL 8.0.43 | LTS/bloqueante |
| 2.9.x | PHP 8.5 | MySQL 8.4 | informativa até homologação |

Além dos extremos, um job intermediário PHP 8.4/PostgreSQL 17 pode rodar em agenda/release para evitar lacunas.

## Decisões a fechar antes de implementar banco/interface v2

1. escolher PHP 8.3 ou 8.4 como mínimo v3;
2. fixar o menor patch MySQL 8.0 oficialmente homologado;
3. confirmar PostgreSQL 16 como mínimo ou elevar para 17;
4. decidir se MariaDB permanece produto suportado ou compatibilidade não garantida;
5. definir janela de suporte e mecanismo de elevação futura dos mínimos;
6. decidir como módulos privados declaram capacidades/versões adicionais.

## Critérios de aceite

- nenhum documento afirma retrocompatibilidade apenas por usar uma versão mais nova no Docker;
- todo arquivo distribuído passa lint na menor versão PHP;
- instalação limpa, migrations, atualização e módulos passam nos extremos da matriz;
- SQL/migration exclusiva de versão possui capability/fallback ou eleva formalmente o mínimo;
- data directory nunca é reutilizado em downgrade não suportado;
- manifesto/preflight impedem deploy em combinação incompatível;
- matriz é executada também com os overlays privados suportados.

## Referências oficiais

- [versões PHP suportadas](https://www.php.net/supported-versions.php) e [guia de migração PHP 8.5](https://www.php.net/manual/en/migration85.php);
- [upgrade MySQL 8.0→8.4](https://dev.mysql.com/doc/refman/8.4/en/upgrade-paths.html), [pré-requisitos](https://dev.mysql.com/doc/refman/8.4/en/upgrade-prerequisites.html) e [downgrade 8.4→8.0](https://dev.mysql.com/doc/refman/8.4/en/downgrading.html);
- [política de versões PostgreSQL](https://www.postgresql.org/support/versioning/).

## Próxima ação

Promover uma PoC de compatibilidade sem migrar módulos: backportar uma cópia controlada do núcleo banco v2 para PHP 8.3/8.4, executar contratos nos extremos e usar o resultado para fechar a versão mínima antes do primeiro batch funcional.
