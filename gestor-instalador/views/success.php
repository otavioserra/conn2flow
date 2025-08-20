<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <title data-translate="success_page_title"><?= __('success_page_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-20">
        <div class="w-full max-w-lg mx-auto bg-white p-8 rounded-lg shadow-md text-center">
            <h1 data-translate="success_title" class="text-2xl font-bold mb-4 text-green-600"><?= __('success_title') ?></h1>
            <p data-translate="success_message" class="text-gray-700 mb-6"><?= __('success_message') ?></p>
            <a href="../gestor/" data-translate="success_button" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= __('success_button') ?></a>
        </div>
    </div>
</body>
</html>