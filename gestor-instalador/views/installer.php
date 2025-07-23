<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16x16.png">
    <link rel="manifest" href="assets/favicons/site.webmanifest">
    <title data-translate="page_title"><?= __('page_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Language Selection Modal -->
    <div id="language-modal" class="fixed inset-0 bg-gray-900 bg-opacity-80 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl text-center">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Select Your Language / Selecione seu Idioma</h2>
            <div class="flex justify-center space-x-4">
                <button data-lang="pt-br" class="lang-select-btn bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Português (Brasil)
                </button>
                <button data-lang="en-us" class="lang-select-btn bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                    English (US)
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto mt-10">
        <div class="w-full max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <div class="flex justify-center mb-4">
                <img src="assets/images/conn2flow-logomarca.png" alt="Logomarca Conn2Flow" class="max-w-xs">
            </div>
            <h1 data-translate="installer_title" class="text-2xl font-bold mb-6 text-center text-gray-700"><?= __('installer_title') ?></h1>
            <p data-translate="welcome_message" class="text-gray-600 mb-6">
                <?= __('welcome_message') ?>
            </p>
            <form id="installer-form" action="." method="POST" autocomplete="off" novalidate>
                <input type="hidden" name="install" value="1">
                <!-- Database Configuration -->
                <div class="mb-6">
                    <h2 data-translate="db_config_title" class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4"><?= __('db_config_title') ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="db_host" data-translate="db_host_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('db_host_label') ?></label>
                            <input type="text" id="db_host" name="db_host" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="off">
                            <p id="db_host-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                        <div>
                            <label for="db_name" data-translate="db_name_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('db_name_label') ?></label>
                            <input type="text" id="db_name" name="db_name" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="off">
                            <p id="db_name-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                        <div>
                            <label for="db_user" data-translate="db_user_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('db_user_label') ?></label>
                            <input type="text" id="db_user" name="db_user" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="off">
                            <p id="db_user-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                        <div>
                            <label for="db_pass" data-translate="db_pass_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('db_pass_label') ?></label>
                            <input type="password" id="db_pass" name="db_pass" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="new-password">
                            <p id="db_pass-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                    </div>
                </div>

                <!-- Admin User Configuration -->
                <div class="mb-6">
                    <h2 data-translate="admin_config_title" class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4"><?= __('admin_config_title') ?></h2>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="admin_name" data-translate="admin_name_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('admin_name_label') ?></label>
                            <input type="text" id="admin_name" name="admin_name" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="off">
                            <p id="admin_name-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                        <div>
                            <label for="admin_email" data-translate="admin_email_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('admin_email_label') ?></label>
                            <input type="email" id="admin_email" name="admin_email" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="off">
                            <p id="admin_email-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                        <div>
                            <label for="admin_pass" data-translate="admin_pass_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('admin_pass_label') ?></label>
                            <input type="password" id="admin_pass" name="admin_pass" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="new-password">
                            <p id="admin_pass-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                         <div>
                            <label for="admin_pass_confirm" data-translate="admin_pass_confirm_label" class="block text-gray-700 text-sm font-bold mb-2"><?= __('admin_pass_confirm_label') ?></label>
                            <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" class="shadow appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" autocomplete="new-password">
                            <p id="admin_pass_confirm-error" class="text-red-500 text-xs italic mt-2 hidden"></p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center">
                    <button type="submit" data-translate="install_button" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-48 disabled:opacity-50">
                        <?= __('install_button') ?>
                    </button>
                </div>
            </form>
            <div class="text-center mt-6">
                <button id="change-lang-btn" data-translate="change_language_button" class="text-sm text-blue-500 hover:underline"><?= __('change_language_button') ?></button>
            </div>
        </div>
    </div>

    <!-- Modal de Carregamento -->
    <div id="loading-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl text-center">
            <svg class="animate-spin h-12 w-12 text-blue-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p id="progress-message" class="text-lg font-semibold text-gray-700">Iniciando a instalação, por favor aguarde...</p>
            <p class="text-sm text-gray-500">Isso pode levar alguns minutos. Não feche esta janela.</p>
        </div>
    </div>

    <script src="assets/js/installer.js" defer></script>
</body>
</html>