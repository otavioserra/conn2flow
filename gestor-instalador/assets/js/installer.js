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

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            let isValid = true;

            // Lista de campos para validar
            const fieldsToValidate = [
                'db_host', 'db_name', 'db_user', 'domain', 'admin_name',
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
    }
});

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

            const result = await response.json();

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