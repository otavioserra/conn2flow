<?php
/*********
	Descrição: tela de desbloqueio do instalador por chave de segurança pré-instalação.
	Renderizada pelo index.php enquanto a sessão não apresentar a chave de `install-key.txt`.
**********/

$unlockLang = isset($lang) ? (string)$lang : 'pt-br';
$unlockErro = isset($unlockError) ? (string)$unlockError : '';
$unlockChaveGerada = !empty($installKeyGerada);
$unlockVersao = isset($_GESTOR_INSTALADOR['versao']) ? (string)$_GESTOR_INSTALADOR['versao'] : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($unlockLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars(__('security_key_title', 'Instalador protegido'), ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-16 px-4">
        <div class="w-full max-w-lg mx-auto bg-white p-8 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">
                <?= htmlspecialchars(__('security_key_title', 'Instalador protegido'), ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="text-center text-xs text-gray-400 mb-6">Conn2Flow Instalador <?= htmlspecialchars($unlockVersao, ENT_QUOTES, 'UTF-8') ?></p>

            <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-sm text-blue-800">
                <?= htmlspecialchars(__('security_key_instructions', "Para continuar a instalação, consulte o arquivo 'install-key.txt' gerado no servidor e digite a chave de segurança."), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <?php if ($unlockChaveGerada): ?>
            <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-sm text-yellow-800">
                <?= htmlspecialchars(__('security_key_generated', 'Uma nova chave de segurança acabou de ser gerada no diretório do instalador com permissão restrita (0600).'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>

            <?php if ($unlockErro !== ''): ?>
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-sm text-red-700">
                <?= htmlspecialchars($unlockErro, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="." autocomplete="off" novalidate>
                <input type="hidden" name="action" value="unlock_installer">
                <input type="hidden" name="lang" value="<?= htmlspecialchars($unlockLang, ENT_QUOTES, 'UTF-8') ?>">

                <label for="install_key" class="block text-gray-700 text-sm font-bold mb-2">
                    <?= htmlspecialchars(__('security_key_label', 'Chave de segurança:'), ENT_QUOTES, 'UTF-8') ?>
                </label>
                <input type="password" id="install_key" name="install_key" required autofocus
                    class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline font-mono"
                    autocomplete="off" spellcheck="false">

                <button type="submit" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <?= htmlspecialchars(__('security_key_button', 'Desbloquear instalador'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>

            <div class="text-center mt-6 text-sm">
                <a href="?lang=pt-br" class="text-blue-600 hover:underline">Português</a>
                <span class="text-gray-300 mx-2">|</span>
                <a href="?lang=en" class="text-blue-600 hover:underline">English</a>
            </div>
        </div>
    </div>
</body>
</html>
