<?php

/** Serializa a reconciliação de IDs externos na mesma conexão MySQL/MariaDB. */
function pagamentos_com_trava($gateway, $callback){
    gestor_incluir_biblioteca('banco');
    // Gateway inteiro: dois objetos da mesma cobrança podem chegar com IDs diferentes.
    $nome = 'c2f-payment-'.hash('sha256', (string)$gateway);
    $nome = substr($nome, 0, 64);
    $resultado = banco_query("SELECT GET_LOCK('".banco_escape_field($nome)."', 15) AS acquired");
    $linha = $resultado ? banco_fetch_assoc($resultado) : null;
    if ((int)($linha['acquired'] ?? 0) !== 1) throw new RuntimeException('payment-lock-unavailable');
    try {
        if (banco_query('START TRANSACTION') === false) throw new RuntimeException('payment-transaction-unavailable');
        $resultado = $callback();
        if (banco_query('COMMIT') === false) throw new RuntimeException('payment-commit-failed');
        return $resultado;
    } catch (Throwable $erro) {
        banco_query('ROLLBACK');
        throw $erro;
    } finally {
        banco_query("SELECT RELEASE_LOCK('".banco_escape_field($nome)."')");
    }
}
