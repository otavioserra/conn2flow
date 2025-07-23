<?php

class Installer
{
    private $data;

    public function __construct(array $postData)
    {
        $this->data = $postData;
    }

    public function runStep(string $step)
    {
        if (method_exists($this, $step)) {
            return $this->$step();
        }
        throw new Exception(__('error_invalid_step', "Etapa de instalação inválida."));
    }

    private function validate_input()
    {
        // Validação básica do lado do servidor
        $required = ['db_host', 'db_name', 'db_user', 'admin_name', 'admin_email', 'admin_pass'];
        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                throw new Exception(__('error_field_required', "Todos os campos são obrigatórios."));
            }
        }

        if ($this->data['admin_pass'] !== $this->data['admin_pass_confirm']) {
            throw new Exception(__('error_passwords_mismatch_server'));
        }

        sleep(1); // Simula a verificação da conexão com o BD

        return [
            'status' => 'success',
            'message' => __('progress_validating'),
            'next_step' => 'download_files'
        ];
    }

    private function download_files() {
        sleep(2); // Simula o download
        return [
            'status' => 'success',
            'message' => __('progress_downloading'),
            'next_step' => 'unzip_files'
        ];
    }

    private function unzip_files() {
        sleep(3); // Simula a descompactação e configuração
        return [
            'status' => 'finished',
            'message' => __('progress_unzipping'),
            'redirect_url' => './?success=true&lang=' . ($this->data['lang'] ?? 'pt-br')
        ];
    }
}