let translations = {}; // Armazena as traduções do idioma atual
let currentLang = 'pt-br'; // Idioma padrão

// Função para aplicar as traduções na página
const applyTranslations = (langData) => {
    translations = langData; // Guarda para uso posterior (ex: mensagens de validação)
    document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.dataset.translate;
        if (translations[key]) {
            element.innerHTML = translations[key];
        }
    });
    // Caso especial para o título do documento
    document.title = translations.page_title || document.title;
};

// Função para buscar o arquivo de idioma e aplicá-lo
const loadLanguage = async (lang) => {
    currentLang = lang;
    try {
        // Adiciona um parâmetro para evitar cache
        const response = await fetch(`lang/${lang}.json?v=${new Date().getTime()}`);
        if (!response.ok) {
            throw new Error(`Arquivo de idioma não encontrado: ${lang}.json`);
        }
        const langData = await response.json();
        applyTranslations(langData);
    } catch (error) {
        console.error('Falha ao carregar o idioma:', error);
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const languageModal = document.getElementById('language-modal');
    const langButtons = document.querySelectorAll('.lang-select-btn');
    const changeLangButton = document.getElementById('change-lang-btn');

    if (languageModal && langButtons.length > 0) {
        langButtons.forEach(button => {
            button.addEventListener('click', async function () {
                const selectedLang = this.dataset.lang;
                await loadLanguage(selectedLang);
                languageModal.classList.add('hidden');
            });
        });
    }

    if (changeLangButton) {
        changeLangButton.addEventListener('click', function () {
            if (languageModal) {
                languageModal.classList.remove('hidden');
            }
        });
    }

    const form = document.getElementById('installer-form');

    if (form) {
        // ...código do modo normal permanece igual...
        // Função auxiliar para mostrar uma mensagem de erro para um campo específico
        const showError = (fieldId, message) => {
            const field = document.getElementById(fieldId);
            const errorElement = document.getElementById(`${fieldId}-error`);
            if (field && errorElement) {
                field.classList.add('border-red-500');
                field.classList.remove('border-gray-300');
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            }
        };

        // Função auxiliar para esconder a mensagem de erro de um campo
        const hideError = (fieldId) => {
            const field = document.getElementById(fieldId);
            const errorElement = document.getElementById(`${fieldId}-error`);
            if (field && errorElement) {
                field.classList.remove('border-red-500');
                field.classList.add('border-gray-300');
                errorElement.classList.add('hidden');
            }
        };

        // Função para atualizar o caminho completo
        const updateInstallPath = () => {
            const basePath = document.getElementById('install_base_path').value.trim();
            const folderName = document.getElementById('install_folder_name').value.trim();
            const fullPath = basePath && folderName ? basePath + '/' + folderName : '';
            document.getElementById('install_path').value = fullPath;
        };

        // Atualiza o caminho quando os campos mudarem
        document.getElementById('install_base_path').addEventListener('input', updateInstallPath);
        document.getElementById('install_folder_name').addEventListener('input', updateInstallPath);

        // Inicializa o campo oculto
        updateInstallPath();

        // Função para sincronizar o domínio do banco com o domínio do site
        const syncDomainFields = () => {
            const domainField = document.getElementById('domain');
            const dbHostField = document.getElementById('db_host');

            if (domainField && dbHostField) {
                // Sincroniza quando o domínio do site é alterado
                domainField.addEventListener('input', function () {
                    const domainValue = this.value.trim();
                    // Só atualiza se o campo do banco estiver vazio ou igual ao valor anterior
                    if (!dbHostField.value.trim() || dbHostField.dataset.autoFilled === 'true') {
                        dbHostField.value = domainValue;
                        dbHostField.dataset.autoFilled = 'true';
                    }
                });

                // Marca quando o usuário edita manualmente o campo do banco
                dbHostField.addEventListener('input', function () {
                    if (this.value.trim() !== domainField.value.trim()) {
                        this.dataset.autoFilled = 'false';
                    }
                });

                // Inicializa a marcação para os campos já preenchidos
                if (dbHostField.value.trim() === domainField.value.trim()) {
                    dbHostField.dataset.autoFilled = 'true';
                }
            }
        };

        // Inicializa a sincronização dos domínios
        syncDomainFields();

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            // Atualiza o caminho antes de validar
            updateInstallPath();

            let isValid = true;

            // Lista de campos para validar
            const fieldsToValidate = [
                'db_host', 'db_name', 'db_user', 'domain', 'install_path', 'admin_name',
                'admin_email', 'admin_pass', 'admin_pass_confirm'
            ];
            // Limpa erros anteriores
            fieldsToValidate.forEach(hideError);

            // --- Validações ---
            const dbHost = document.getElementById('db_host').value.trim();
            if (!dbHost) { showError('db_host', translations.error_db_host_required || 'O Host do Banco de Dados é obrigatório.'); isValid = false; }

            const dbName = document.getElementById('db_name').value.trim();
            if (!dbName) { showError('db_name', translations.error_db_name_required || 'O Nome do Banco de Dados é obrigatório.'); isValid = false; }

            const dbUser = document.getElementById('db_user').value.trim();
            if (!dbUser) { showError('db_user', translations.error_db_user_required || 'O Usuário do Banco de Dados é obrigatório.'); isValid = false; }

            const domain = document.getElementById('domain').value.trim();
            if (!domain) { showError('domain', translations.error_domain_required || 'O Domínio é obrigatório.'); isValid = false; }

            const installPath = document.getElementById('install_path').value.trim();
            if (!installPath) { showError('install_path', translations.error_install_path_required || 'O caminho de instalação é obrigatório.'); isValid = false; }

            const adminName = document.getElementById('admin_name').value.trim();
            if (!adminName) { showError('admin_name', translations.error_admin_name_required || 'O Nome Completo do administrador é obrigatório.'); isValid = false; }

            const adminEmail = document.getElementById('admin_email').value.trim();
            if (!adminEmail) { showError('admin_email', translations.error_admin_email_required || 'O Email do administrador é obrigatório.'); isValid = false; }

            const adminPass = document.getElementById('admin_pass').value;
            if (!adminPass) { showError('admin_pass', translations.error_admin_pass_required || 'A Senha do administrador é obrigatória.'); isValid = false; }

            const adminPassConfirm = document.getElementById('admin_pass_confirm').value;
            if (adminPass && adminPass !== adminPassConfirm) { showError('admin_pass_confirm', translations.error_passwords_no_match || 'As senhas não coincidem.'); isValid = false; }

            if (isValid) {
                // Mostra o modal e inicia a instalação via AJAX
                const button = form.querySelector('button[type="submit"]');
                const modal = document.getElementById('loading-modal');
                button.disabled = true;
                modal.classList.remove('hidden');

                const formData = new FormData(form);
                runInstallation(formData);
            }
        });
    } else {
        // Modo debug: não há formulário, dispara instalação automática
        const modal = document.getElementById('loading-modal') || document.getElementById('progress-container');
        if (modal) {
            modal.classList.remove('hidden');
        }
        // Cria um FormData mínimo, só com o idioma
        let debugFormData = new FormData();
        debugFormData.append('lang', currentLang);
        // Dispara instalação automática
        runInstallation(debugFormData);
    }
});

function handleInstallationError(errorData) {
    const progressMessage = document.getElementById('progress-message');
    const loadingSpinner = document.getElementById('loading-spinner');
    const errorIcon = document.getElementById('error-icon');
    const logContainer = document.getElementById('error-log-container');
    const logContentEl = document.getElementById('error-log-content');
    const copyLogBtn = document.getElementById('copy-log-btn');
    const retryBtn = document.getElementById('retry-btn');
    const mainInstallBtn = document.querySelector('#installer-form button[type="submit"]');

    // Troca o ícone de carregando pelo de erro
    loadingSpinner.classList.add('hidden');
    errorIcon.classList.remove('hidden');

    // Atualiza a mensagem de erro principal
    progressMessage.textContent = errorData.message || (translations.error_unknown || 'Ocorreu um erro desconhecido.');
    progressMessage.classList.remove('text-gray-700');
    progressMessage.classList.add('text-red-500');

    // Mostra o log se ele existir na resposta
    if (errorData.log_content && errorData.log_content.trim()) {
        logContentEl.textContent = errorData.log_content.trim();
        logContainer.classList.remove('hidden');

        copyLogBtn.onclick = () => {
            navigator.clipboard.writeText(logContentEl.textContent).then(() => {
                copyLogBtn.textContent = translations.log_copied_button || 'Copiado!';
                setTimeout(() => {
                    copyLogBtn.textContent = translations.copy_log_button || 'Copiar Log';
                }, 2000);
            });
        };
    }
}

// Função global para rodar a instalação
async function runInstallation(initialFormData) {
    const progressMessage = document.getElementById('progress-message');
    let nextStep = 'validate_input';
    let isFinished = false;

    // Usamos uma cópia dos dados para adicionar a ação de cada etapa
    let postData = new FormData();
    for (let pair of initialFormData.entries()) {
        postData.append(pair[0], pair[1]);
    }
    postData.append('lang', currentLang);

    while (!isFinished) {
        postData.set('action', nextStep);

        try {
            const response = await fetch('.', {
                method: 'POST',
                body: postData
            });

            if (!response.ok) {
                throw new Error(`Erro do servidor: ${response.statusText}`);
            }

            // Tenta ler a resposta como texto primeiro para detectar erros PHP
            const responseText = await response.text();

            // Verifica se a resposta parece ser um erro PHP/HTML
            if (responseText.includes('<br') || responseText.includes('Fatal error') || responseText.includes('Warning')) {
                console.error('Erro PHP detectado:', responseText);
                throw new Error('Erro interno do servidor. Verifique os logs do PHP ou o arquivo installer.log para mais detalhes.');
            }

            // Tenta fazer parse do JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Resposta não é JSON válido:', responseText);
                throw new Error('Resposta inválida do servidor. Verifique o arquivo installer.log para mais detalhes.');
            }

            if (result.status === 'error') { throw new Error(result.message); }

            progressMessage.textContent = result.message;

            if (result.status === 'finished') {
                isFinished = true;
                window.location.href = result.redirect_url;
            } else {
                nextStep = result.next_step;
            }
        } catch (error) {
            progressMessage.textContent = `Ocorreu um erro: ${error.message}`;
            progressMessage.classList.remove('text-gray-700');
            progressMessage.classList.add('text-red-500');
            isFinished = true; // Para o loop em caso de erro
        }
    }
}