# Prompt Interactive Programming - Atualizações Banco de Dados

- Definições de toda a infraestrutura de programação que serão usados pelos agentes de IA para interagir com o usuário e gerar código de forma dinâmica estão definidas abaixo.
- Os agentes usarão este arquivo para poder criar e alterar orientações de forma dinâmica, com base nas interações com o usuário. Podendo alterar qualquer parte a qualquer momento. O usuário ficará atento e modificará esse arquivo para garantir que as mudanças sejam compreendidas e implementadas corretamente.
- Tanto o usuário quanto os agentes de IA poderão modificar as orientações e os elementos de programação definidos neste arquivo a qualquer momento. Sendo assim, o agente sempre deve estar atento às mudanças e adaptar seu comportamento conforme necessário.
- Abaixo serão definidos pelos agentes e usuários comandos usando pseudo-código onde a definição da syntax está no seguinte arquivo: `ai-workspace\templates\pseudo-language-programming.md`.

## 🤖 Agente de IA - Responsabilidades
- **Desenvolvimento**: Responsável por criar e modificar estas orientações e o código-fonte da aplicação.
- **GIT**: Responsável por gerenciar o repositório de código-fonte e as versões do projeto.
- **Docker**: Responsável por gerenciar os contêineres Docker e a infraestrutura relacionada.

## 🧪 Ambiente de Testes
- Existe uma infraestrutura de testes prontas e funcional. As configurações do ambiente estão no arquivo `docker\dados\docker-compose.yml`
- O ambiente de testes está na pasta `docker\dados\sites\localhost\conn2flow-gestor`. Que é executado pelo gestor via navegador assim: `http://localhost/instalador/` . O mesmo está na pasta: `docker\dados\sites\localhost\public_html\instalador`
- Para atualizar o ambiente e refletir as mudanças do repositório, segue o arquivo para sincronização: `docker\utils\sincroniza-gestor.sh checksum`
- Todos os comandos para executar no ambiente de testes estão no arquivo: `docker\utils\comandos-docker.md`

## 🗃️ Repositório GIT
- Existe um script feito com todas as operações necessárias internas para gerenciar o repositório: `./ai-workspace/scripts/commit.sh "MensagemDetalhadaAqui"`
- Dentro desse script é feito o versionamento automático do projeto, commit e push. Portanto, não faça os comandos manualmente. Apenas execute o script quando for alterar o repositório.

## ⚙️ Configurações da Implementação
- Caminho base: $base = `gestor\controladores\atualizacoes\`.
- Nome do arquivo da Implementação: $nomeArquivoImplementacao = $base + `atualizacoes-banco-de-dados.php`.
- Caminho da pasta de backups caso necessário: $backupPath = `backups\atualizacoes`.
- Caminho da pasta de logs: $logsPath = `gestor\logs\atualizacoes`.
- Caminho da pasta de linguagens: $linguagensPath = `gestor\controladores\atualizacoes\lang\`.
- Linguagens suportadas: $linguagensSuportadas = [`pt-br`, `en`].
- Linguagens de dicionário serão armazenadas em arquivo .JSON.
- Todos os textos de informação/logs deverão ter multilinguas. Escapados usando função helper `_()`;
- O código fonte deverá **ser bem comentado (padrão DocBlock), seguir os padrões de design definidos e ser modular.** Todas as orientações deverão constar nos comentários do código.

## 📖 Bibliotecas
- Geração de logs: `gestor\bibliotecas\log.php`: `log_disco($msg, $logFilename = "gestor")` > Pode alterar se necessário.
- Funções de lang: `gestor\bibliotecas\lang.php`: `_()` > Pode alterar se necessário.

## 🎯 Contexto Inicial
- Vamos criar uma rotina de atualização do banco de dados. 
- Nós usamos a biblioteca Phinx do PHP para criar migrações e seeders.
- Localização dos arquivos de migração: `gestor\db\migrations`. Exemplo: `gestor\db\migrations\20250723165530_create_paginas_table.php`:
- Localização dos arquivos de seeder (Sem os registros, os mesmos são incluídos automaticamente): `gestor\db\seeders`. Exemplo: `gestor\db\seeds\PaginasSeeder.php`
```php
declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class PaginasSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/PaginasData.json'), true);

        $table = $this->table('paginas');
        $table->insert($data)->saveData();
    }
}
```
- Localização dos arquivos dos registros de dados: `gestor\db\data`. Exemplo: `gestor\db\data\PaginasData.json`
```json
    ...
    {
        "id_paginas": 79,
        "id_usuarios": 1,
        "id_layouts": 1,
        "nome": "Arquivos - Adicionar",
        "id": "arquivos-adicionar",
        "language": "pt-br",
        "caminho": "arquivos\/adicionar\/",
        "tipo": "system",
        "modulo": "arquivos",
        "opcao": "upload",
        "raiz": null,
        "sem_permissao": null,
        "html": "...",
        "css": "...",
        "status": "A",
        "versao": 1,
        "data_criacao": "2025-08-13 17:12:12",
        "data_modificacao": "2025-08-13 17:12:12",
        "user_modified": 0,
        "file_version": "1.1",
        "checksum": "..."
    },
    ...
```
- Formatação dos nomes dos arquivos em relação a tabela: 
| Recurso     | Formatação            |
|-------------|-----------------------|
| Tabela      | `tabela`              |
| Seeder      | `TabelaSeeder.php`    |
| Data        | `TabelaData.json`     |

## 📝 Orientações para o Agente
1. Precisamos rodar as migrações.
2. Precisamos rodar os seeders.
3. Precisamos comparar os dados de cada registro de cada tabela com o seu correspondente arquivo de dados do `gestor\db\data`. Caso não exista, incluir o dado. Caso o mesmo seja diferente, atualizar o registro.

## 🧭 Estrutura do código-fonte
```
migracoes():
    > Lógica para rodar as migrações

seeders():
    > Lógica para rodar os seeders

comparacaoDados():
    > Lógica para comparar os dados

relatorioFinal():
    > Lógica para gerar o relatório final

main():
    migracoes()
    seeders()
    comparacaoDados()
    relatorioFinal()

main()
```

## 🤔 Dúvidas e 📝 Sugestões
- Adicionar opção `--backup` para criar dump JSON por tabela antes de modificar? (recomendado)
Sim, o ideal é sempre ter um backup antes de realizar alterações significativas para fazer o fallback.
- Necessário suportar múltiplos ambientes (ex: staging) ou apenas `localhost`? Podemos parametrizar `--env-dir=`.
Sim, pode fazer.

## ✅ Progresso da Implementação
- [x] Estrutura inicial do script `atualizacoes-banco-de-dados.php`
- [x] Carregamento multilíngue dedicado (merge dicionário local)
- [x] Execução de migrações (com verificação .env pendente de ambiente de testes)
- [x] Execução de seeders
- [x] Comparação e sincronização (insert/update) baseada nos arquivos *Data.json
- [x] Flags CLI: --skip-migrate, --skip-seed, --dry-run, --tables=lista, --help
- [x] Verificação de ambiente (.env) com instrução de sincronização
- [x] Implementar logging detalhado por registro divergente (delta field-level) (flag --log-diff)
- [x] Implementar backup opcional antes de alterações (--backup)
- [x] Implementar modo reverso (gerar Data.json a partir do banco) (--reverse)

## ☑️ Processo Pós-Implementação
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, subistituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/scripts/commit.sh "MensagemDetalhadaAqui"`

## ♻️ Alterações e Correções v1.10.11
### Novas Flags
- --backup: Cria dump JSON de todas as tabelas alvo antes de sincronizar.
- --env-dir=nome: Permite escolher diretório de autenticação (default localhost).
- --reverse: Exporta dados do banco para arquivos *Data.json (DB -> Data) e encerra.
- --log-diff: Registra no log os campos alterados por registro (limitado a 10 campos).

### Ajustes
- Correção de paths BASE_PATH para apontar corretamente para gestor/.
- Adição de exportação reversa com backup de arquivos antigos (rename *.bak.timestamp).
- Mensagens multilíngues ampliadas (backup, reverse, diffs).
- Sanitização/limitação de valores em logs (encLog).
- Atualização de usage help.

## ✅ Progresso da Implementação das Alterações e Correções
1. Eu fui executar por mim mesmo e deu erro:
```bash
otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
$ docker exec conn2flow-app bash -c "php /var/www/sites/localhost/conn2flow-gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php --dry-run --debug"
Erro: Falha seeders

otavi@Otavio-Trabalho MINGW64 ~/OneDrive/Documentos/GIT/conn2flow (main)
```
2. Eu limpei manualmente completamente o banco de dados, rodei novamente e mesmo assim deu o mesmo erro.
3. Limpei manualmente agora e vc vai poder rodar novamente por vc com o banco limpo.

## ☑️ Processo Pós Alterações e Correções
- [] Executar o script gerado para ver se funciona corretamente.
- [] Gerar mensagem detalhada, subistituir "MensagemDetalhadaAqui" do script e executar o script do GIT à seguir: `./ai-workspace/scripts/commit.sh "MensagemDetalhadaAqui"`

---
**Data:** 13/08/2025
**Desenvolvedor:** Otavio Serra
**Projeto:** Conn2Flow v1.10.10