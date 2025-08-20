<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <title>Instalação via Modo Debug</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-20">
        <div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-md text-center">
            <div class="flex justify-center mb-6">
                <img src="assets/images/conn2flow-logomarca.png" alt="Logomarca Conn2Flow" class="max-w-xs">
            </div>
            <h1 class="text-2xl font-bold mb-4 text-blue-600">Instalação via Modo Debug</h1>
            <p class="text-gray-700 mb-6 text-lg">O instalador detectou o arquivo <span class="font-mono text-red-500">.env.debug</span> na raiz do projeto.</p>
            <div class="mb-6">
                <span class="inline-block bg-blue-100 text-blue-800 text-sm px-4 py-2 rounded-full font-semibold">A instalação será realizada automaticamente com os dados de debug.</span>
            </div>
        <div id="progress-container" class="mt-8">
            <svg id="loading-spinner" class="mx-auto h-12 w-12 text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p id="progress-message" class="mt-4 text-blue-500 font-semibold">Iniciando instalação Conn2Flow Gestor...</p>
            <p id="progress-details" class="text-sm text-gray-500">Aguarde, o processo pode levar alguns minutos.</p>
            <div id="error-log-container" class="hidden text-left mt-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-md font-semibold text-gray-800">Log de Erros</h3>
                    <button id="copy-log-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold py-1 px-3 rounded">Copiar Log</button>
                </div>
                <div class="mt-2 bg-gray-100 p-3 rounded-md max-h-40 overflow-y-auto border border-gray-300">
                    <pre id="error-log-content" class="text-xs text-gray-600 whitespace-pre-wrap break-all"></pre>
                </div>
            </div>
            <button id="retry-btn" class="hidden mt-6 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Tentar novamente</button>
        </div>
        <p class="text-gray-600 mt-8">Para voltar ao modo normal, remova ou renomeie o arquivo <span class="font-mono">.env.debug</span> e recarregue a página.</p>
    </div>
    <script src="assets/js/installer.js" defer></script>
        </div>
    </div>
</body>
</html>
