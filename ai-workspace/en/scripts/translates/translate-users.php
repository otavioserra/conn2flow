<?php

// Script to translate usuarios_en.json
$file = __DIR__ . '/dictionaries/usuarios_en.json';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    die("Error decoding JSON\n");
}

$translations = [
    "Name" => "Name",
    "Name" => "Name",
    "Middle Name" => "Middle Name",
    "First Name" => "First Name",
    "Last Name" => "Last Name",
    "Email" => "Email",
    "Email" => "Email",
    "Confirm Email" => "Confirm Email",
    "Confirm Email" => "Confirm Email",
    "User" => "User",
    "User" => "User",
    "Password" => "Password",
    "Password" => "Password",
    "Confirm Password" => "Confirm Password",
    "Confirm Password" => "Confirm Password",
    "User Profile" => "User Profile",
    "Select a profile..." => "Select a profile...",
    "LOGIN" => "LOGIN",
    "User" => "User",
    "Password" => "Password",
    "Login" => "Login",
    "Forgot your password? Click" => "Forgot your password? Click",
    "here" => "here",
    "Keep my account active in this browser" => "Keep my account active in this browser",
    "Don't have an account?" => "Don't have an account?",
    "Create your account" => "Create your account",
    "Usuário e/ou Senha inválidos. Favor tentar novamente." => "Invalid username and/or password. Please try again.",
    "Usuário inativo no sistema, favor entrar em contato com o suporte para restabelecer sua conta." => "User inactive in the system, please contact support to restore your account.",
    "Você não tem permissão para acessar este local." => "You do not have permission to access this location.",
    "There is already a registration for the field <b>#label#</b> with the same value: <b>#value#</b>. Please choose another <b>#label#</b> and try again." => "There is already a registration for the field <b>#label#</b> with the same value: <b>#value#</b>. Please choose another <b>#label#</b> and try again.",
    "Change Password" => "Change Password",
    "letters, numbers, '.' and only one '@'" => "letters, numbers, '.' and only one '@'",
    "Account Name" => "Account Name",
    "User Name" => "User Name",
    "Account Name" => "Account Name",
    "User Name" => "User Name",
    "Não é permitido alterar nenhum dado do usuário sem autorização provisória." => "It is not allowed to change any user data without provisional authorization.",
    "Favor informar a senha logo abaixo e depois clicar no botão <b>Enviar</b> para autorizar provisoriamente a modificação de algum dado sensível da sua conta. " => "Please enter your password below and then click the <b>Send</b> button to provisionally authorize the modification of any sensitive data in your account.",
    "Provisional Change Authorization" => "Provisional Change Authorization",
    "Senha incorreta! Você tem mais <b>#tentativas#</b> tentativas, senão será desconectado automaticamente!" => "Incorrect password! You have <b>#tentativas#</b> more attempts, otherwise you will be automatically disconnected!",
    "Incorrect password! You have been automatically disconnected from the system due to excessive incorrect password entries. You will need to enter username and password to access again." => "Incorrect password! You have been automatically disconnected from the system due to excessive incorrect password entries. You will need to enter username and password to access again.",
    "User Plan" => "User Plan",
    "User Manager Profile" => "User Manager Profile",
    "Select a profile..." => "Select a profile...",
    "Host" => "Host",
    "Parent User" => "Parent User"
];

// Translate variables
foreach ($data['variables'] as $key => $value) {
    if (isset($translations[$value])) {
        $data['variables'][$key] = $translations[$value];
        echo "Translated variable: $key\n";
    }
}

$newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($file, $newContent);

echo "✅ Translation completed!\n";

?>
