<?php

/**
 * Script para traduzir automaticamente as variáveis dos dicionários
 */

// Dicionário de traduções português -> inglês
$translations = [
    // Formulários
    "Confirme o Email" => "Confirm Email",
    "Nome da Conta" => "Account Name",
    "Nome do Usuário" => "User Name",
    "Confirme a Senha" => "Confirm Password",
    "Mudar Senha" => "Change Password",
    "Usuário Perfil" => "User Profile",
    "Selecione um perfil" => "Select a profile",
    "Último Nome" => "Last Name",
    "Nome do Meio" => "Middle Name",
    "Favor informar a senha logo abaixo e depois clicar no botão <b>Enviar</b> para autorizar provisoriamente a modificação de algum dado sensível da sua conta." => "Please enter the password below and then click the <b>Send</b> button to authorize the provisional modification of sensitive data in your account.",
    "Autorização Provisória de Alteração" => "Provisional Change Authorization",
    "letras, números, '.' e apenas um '@'" => "letters, numbers, '.' and only one '@'",
    "Entrar" => "Login",
    "aqui" => "here",
    "Esqueceu sua senha? Clique" => "Forgot your password? Click",
    "Manter minha conta ativa neste navegador" => "Keep my account active in this browser",
    "Faça seu cadastro" => "Create your account",
    "Não possui uma conta?" => "Don't have an account?",

    // Botões de mudança
    "Mudar Email" => "Change Email",
    "Mudar Nome" => "Change Name",
    "Mudar Senha" => "Change Password",
    "Mudar Usuário" => "Change User",
    "Alterações" => "Changes",

    // Esqueceu senha
    "Esqueceu a Senha" => "Forgot Password",
    "Confirme o Email" => "Confirm Email",
    "Enviar" => "Send",
    "Já tem cadastro? Clique" => "Already have an account? Click",
    "Você Esqueceu Sua Senha?" => "Did You Forget Your Password?",
    "Se sim, preencha o campo <b>Email</b> e depois o campo <b>Confirme o Email</b>. Em seguida o sistema irá enviar uma mensagem no endereço de email informado. Siga as orientações desta mensagem afim de criar uma nova senha." => "If yes, fill in the <b>Email</b> field and then the <b>Confirm Email</b> field. Then the system will send a message to the email address provided. Follow the instructions in this message to create a new password.",
    "Não existe nenhum cadastro no sistema com o email informado: <b>#email#</b> . Verifique se o email enviado foi informado corretamente e tente denovo." => "There is no registration in the system with the email provided: <b>#email#</b>. Please check if the email sent was entered correctly and try again.",
    "Esqueceu a Senha Email Enviado" => "Forgot Password Email Sent",
    "Email de Confirmação Enviado com Sucesso!" => "Confirmation Email Sent Successfully!",
    "Foi enviado uma mensagem com instruções para alterar a sua senha no seguinte email: <b>#email#</b> . Favor acessar o seu cliente de emails e seguir as orientações enviadas." => "A message with instructions to change your password was sent to the following email: <b>#email#</b>. Please access your email client and follow the instructions sent.",
    "Redefinição da sua Senha da Conta Conn2Flow nº #numero#" => "Reset Your Conn2Flow Account Password nº #numero#",
    "O token enviado perdeu o prazo de validade. É necessário refazer o processo e tentar novamente." => "The token sent has expired. It is necessary to redo the process and try again.",

    // Redefinir senha
    "Você Quer Redefinir sua Senha?" => "Do You Want to Reset Your Password?",
    "Se sim, preencha o campo <b>Senha</b> e depois o campo <b>Confirme a Senha</b>. Então, clique no botão <b>Redefinir</b> para alterar a sua senha." => "If yes, fill in the <b>Password</b> field and then the <b>Confirm Password</b> field. Then, click the <b>Reset</b> button to change your password.",
    "Redefinir" => "Reset",
    "Redefinir Senha Confirmação" => "Reset Password Confirmation",
    "Redefinição de Senha Concluída com Sucesso!" => "Password Reset Completed Successfully!",
    "Agora você pode acessar o sistema com o seu usuário e senha. Para isso acesse clicando #url# ." => "Now you can access the system with your username and password. To do this, click #url#.",

    // Cadastro
    "Já tem cadastro? Clique" => "Already have an account? Click",
    "Cadastrar" => "Register",
    "CADASTRO" => "REGISTRATION",
    "Confirmação de email" => "Email confirmation",
    "Confirmação de senha" => "Password confirmation",
    "A sua requisição <b>não</b> foi aceita pois nosso sistema automático de segurança considerou a mesma sendo feita por um <b>robô</b>. Favor tentar novamente, mas caso o problema persista, favor entrar em contato clicando <b>#url#</b>." => "Your request <b>was not</b> accepted because our automatic security system considered it to be made by a <b>robot</b>. Please try again, but if the problem persists, please contact us by clicking <b>#url#</b>.",
    "Nova Conta Conn2Flow nº #numero#" => "New Conn2Flow Account nº #numero#",

    // Alertas
    "O email informado já está em uso! Escolha outro e tente novamente." => "The email provided is already in use! Choose another one and try again.",
    "Houve um problema e não foi possível enviar o email. Tente novamente, mas caso o problema persista, entre em contato com o suporte técnico." => "There was a problem and it was not possible to send the email. Try again, but if the problem persists, contact technical support.",
    "senha redefinida com sucesso! <b>IP: #ip# | USER_AGENT: #user-agent#</b>" => "password reset successfully! <b>IP: #ip# | USER_AGENT: #user-agent#</b>",
    "Senha da Conta Conn2Flow redefinida com sucesso nº #numero#" => "Conn2Flow Account Password Reset Successfully nº #numero#",

    // Outros módulos
    "Selecionar Todos" => "Select All",
    "Deselecionar Todos" => "Unselect All",
    "Operações" => "Operations",
    "[operação(ões): #operacoes#]" => "[operation(s): #operacoes#]",
    "Módulo(s): #modulos# ; Módulo(s) Operação(ões): #modulos-operacoes#" => "Module(s): #modulos# ; Module(s) Operation(s): #modulos-operacoes#",
    "Padrão" => "Default",
];

// Arquivos a processar
$files = [
    'perfil-usuario_en.json',
    'usuarios_en.json',
    'usuarios-perfis_en.json',
    // Adicionar outros conforme necessário
];

$dictionariesPath = __DIR__ . '/dictionaries/';

foreach ($files as $file) {
    $filePath = $dictionariesPath . $file;

    if (file_exists($filePath)) {
        echo "Processando: $file\n";

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if ($data && isset($data['variables'])) {
            $translated = false;

            foreach ($data['variables'] as $key => $value) {
                if (isset($translations[$value])) {
                    $data['variables'][$key] = $translations[$value];
                    $translated = true;
                    echo "  Traduzido: $key\n";
                }
            }

            if ($translated) {
                file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                echo "  Arquivo atualizado: $file\n";
            }
        }
    } else {
        echo "Arquivo não encontrado: $file\n";
    }
}

echo "\n✅ Tradução automática concluída!\n";

?>