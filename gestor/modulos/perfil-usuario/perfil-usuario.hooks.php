<?php
function perfil_usuario_distribuido_login_hook($id_usuarios) {
    gestor_incluir_biblioteca('modulo-distribuido');
    modulo_distribuido_login_sucesso((int)$id_usuarios);
}
