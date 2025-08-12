feat: Implementa script para migrar variáveis de seed para arquivos de recursos

Este commit introduz um novo script PHP para automatizar a migração de variáveis de um arquivo de seed JSON (`VariaveisData.json`) para uma estrutura de arquivos de recursos distribuída.

O script realiza as seguintes ações:
- **Lê as variáveis** do arquivo de seed.
- **Identifica e mapeia** os módulos principais e os módulos de plugins existentes na aplicação.
- **Formata e categoriza** cada variável como global, de módulo ou de plugin, com base na sua associação.
- **Salva as variáveis** formatadas nos arquivos de destino corretos:
    - Globais: `gestor/resources/{lang}/variables.json`
    - Módulos: `gestor/modulos/{module}/{module}.json`
    - Plugins: `gestor-plugins/{plugin}/local/modulos/{module}/{module}.json`
- **Internacionalização (i18n):** Adiciona suporte a múltiplos idiomas para todos os logs e mensagens de relatório, utilizando uma nova biblioteca `lang.php`.
- **Logs e Relatórios:** Gera um log detalhado do processo e exibe um relatório de resumo na conclusão.

Essa automação simplifica a manutenção e a organização das variáveis do sistema, movendo-as de um local centralizado para os contextos onde são utilizadas.
