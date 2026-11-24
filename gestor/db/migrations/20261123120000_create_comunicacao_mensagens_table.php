<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela `comunicacao_mensagens` (REQ-304 / BATCH-313).
 *
 * Fila e histórico unificados de TODOS os canais de comunicação (`email` hoje; `whatsapp`,
 * `telegram`, `push` preparados pelo contrato `comunicacao.drivers`). Uma linha por mensagem:
 * nasce `pendente` (fila) ou `enviando` (envio imediato), termina `enviado`, `falhou` ou
 * `cancelado`. O worker `comunicacao_fila_processar()` reserva cada linha com UPDATE condicional
 * (`WHERE status='pendente'`) e confere as linhas afetadas (D-040).
 *
 * `status`, `canal` são VARCHAR e não ENUM (D-037: a suíte roda em SQLite). `prioridade` é
 * numérica (1 alta, 2 normal, 3 baixa) para o worker ordenar com ORDER BY simples.
 *
 * `payload_json` guarda o conteúdo já montado (HTML final, destinatários, remetente, anexos) e
 * NUNCA credenciais: a senha SMTP é resolvida no momento do envio.
 *
 * Não é tabela de recurso sincronizado (sem `user_modified`/`project`): os dados nascem em
 * runtime e não vêm de `db/data`.
 */
final class CreateComunicacaoMensagensTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('comunicacao_mensagens')) {
            return;
        }

        $opcoes = ['id' => 'id_comunicacao_mensagens'];
        if ($this->getAdapter()->getAdapterType() === 'mysql') {
            // Assunto e HTML aceitam emoji.
            $opcoes['encoding'] = 'utf8mb4';
            $opcoes['collation'] = 'utf8mb4_unicode_ci';
        }

        $table = $this->table('comunicacao_mensagens', $opcoes);
        $table->addColumn('id_hosts', 'integer', ['null' => true])
            // email | whatsapp | telegram | push (canais futuros chegam por driver)
            ->addColumn('canal', 'string', ['limit' => 32, 'null' => false, 'default' => 'email'])
            // pendente | enviando | enviado | falhou | cancelado
            ->addColumn('status', 'string', ['limit' => 24, 'null' => false, 'default' => 'pendente'])
            // 1 alta | 2 normal | 3 baixa
            ->addColumn('prioridade', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => false, 'default' => 2])
            // Destinatário efetivo (a caixa de teste quando MAIL_REDIRECT_TO estava ativo).
            ->addColumn('destinatario', 'string', ['limit' => 255, 'null' => true])
            // Destinatário(s) pedido(s) pelo chamador, preenchido só quando houve redirecionamento.
            ->addColumn('destinatario_original', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('assunto', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('payload_json', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG, 'null' => true])
            ->addColumn('chave_idempotencia', 'string', ['limit' => 128, 'null' => true])
            ->addColumn('tentativas', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('max_tentativas', 'integer', ['null' => false, 'default' => 4])
            ->addColumn('proxima_tentativa_em', 'datetime', ['null' => true])
            // Início da última tentativa: base do rate limit por minuto e da recuperação de travados.
            ->addColumn('reservado_em', 'datetime', ['null' => true])
            ->addColumn('erro_ultimo', 'text', ['null' => true])
            ->addColumn('enviado_em', 'datetime', ['null' => true])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            // Deduplicação: NULL repetido é permitido, a mesma chave não.
            ->addIndex(['chave_idempotencia'], ['unique' => true, 'name' => 'idx_comunicacao_mensagens_idempotencia'])
            // Consulta do worker.
            ->addIndex(['status', 'proxima_tentativa_em', 'prioridade'], ['name' => 'idx_comunicacao_mensagens_fila'])
            // Listagem do painel.
            ->addIndex(['canal', 'status', 'data_criacao'], ['name' => 'idx_comunicacao_mensagens_painel'])
            // Retenção (enviado/cancelado mais antigos que N dias).
            ->addIndex(['status', 'data_criacao'], ['name' => 'idx_comunicacao_mensagens_retencao'])
            // Rate limit (tentativas no minuto corrente).
            ->addIndex(['reservado_em'], ['name' => 'idx_comunicacao_mensagens_reserva'])
            ->addIndex(['id_hosts'], ['name' => 'idx_comunicacao_mensagens_hosts'])
            ->create();
    }
}
