# Limpeza e Padronização de Estrutura HTML/CSS

## Objetivo

Padronizar e organizar todos os recursos visuais do sistema (layouts, páginas e componentes) em arquivos físicos, facilitando o versionamento, manutenção e desenvolvimento, sem perder a flexibilidade de edição pelo usuário via plataforma.


## Estrutura Adotada

- **gestor/**
    - **resources/**
        - **layouts/**: Layouts globais do sistema.
            - `id-do-layout/`
                - `id-do-layout.html`
                - `id-do-layout.css`
        - **paginas/**: Páginas não vinculadas a módulos.
            - `id-da-pagina/`
                - `id-da-pagina.html`
                - `id-da-pagina.css`
        - **componentes/**: Componentes reutilizáveis.
            - `id-do-componente/`
                - `id-do-componente.html`
                - `id-do-componente.css`

    - **modulos/**
        - `nome-do-modulo/`
            - `id-da-pagina/`
                - `id-da-pagina.html`
                - `id-da-pagina.css`
            - ...

- **gestor-cliente/**
    - **modulos/**
        - `nome-do-modulo/`
            - `id-da-pagina/`
                - `id-da-pagina.html`
                - `id-da-pagina.css`
            - ...

- **gestor-plugins/**
    - `nome-do-plugin/`
        - **local/**
            - **modulos/**
                - `nome-do-modulo/`
                    - `id-da-pagina.html`
                    - `id-da-pagina.css`
        - **remoto/**
            - **modulos/**
                - `nome-do-modulo/`
                    - `id-da-pagina.html`
                    - `id-da-pagina.css`

### Exemplo de estrutura de plugin:

gestor-plugins/
    agendamentos/
        local/
            modulos/
                agendamentos/
        remoto/
            modulos/
                agendamentos-host/


## Regras

- Todos os arquivos exportados devem ser salvos como filhos da pasta `gestor/` (ou seja, `gestor/resources/layouts/`, `gestor/resources/paginas/`, `gestor/resources/componentes/`, `gestor/modulos/` etc.), nunca na raiz do repositório.
- Para páginas de módulos, só criar arquivos se a pasta do módulo já existir em um destes caminhos:
    - `gestor/modulos/{modulo}/`
-    - `gestor-plugins/{plugin}/local/modulos/{modulo}/`
-    - `gestor-plugins/{plugin}/remoto/modulos/{modulo}/`
-    - `gestor-cliente/modulos/{modulo}/`
- Caso não exista a pasta do módulo, não criar nada e registrar como órfão para informar ao final.
- O nome da pasta e dos arquivos sempre será o `id` do recurso.
- Cada recurso tem seu HTML e CSS separados.
- Para recursos globais, ficam em `gestor/resources/`.
- O banco de dados continua sendo a fonte para edição pelo usuário, mas os arquivos físicos são a referência para desenvolvedores e versionamento.
- Futuramente, será criada rotina de sincronização e diff entre banco e arquivos.



## Limpeza automática de módulos

Foi criado um script de limpeza que remove arquivos e pastas que não pertencem ao módulo correto em todas as estruturas:
- `gestor/modulos/`
- `gestor-plugins/*/local/modulos/`
- `gestor-plugins/*/remoto/modulos/`
- `gestor-cliente/modulos/`

O script mantém apenas subpastas e arquivos que realmente pertencem ao módulo (seguindo o padrão `{modulo}/{id}/{id}.html` e `.css`). Remove arquivos soltos, subpastas de outros módulos e qualquer lixo gerado por exportações erradas.

## Observações importantes

- Existem recursos (páginas, layouts, componentes) que podem ser criados por outros fluxos, como "emissão" ou hosts distribuídos, e não aparecem nos seeders principais. Esses casos devem ser tratados separadamente.
- O campo `modulo` no seeder deve bater exatamente com o nome da pasta do módulo para que a exportação funcione corretamente.
- Recursos órfãos (sem pasta de módulo correspondente) são exportados como páginas globais em `gestor/resources/paginas/{id}/{id}.html` e `.css`.

## Histórico

- **2025-08-04**: Remoção de layouts órfãos dos seeders.
- **2025-08-05**: Ajuste no upload para criação de diretórios herdando permissão do pai.
- **2025-08-05**: Definição e validação da estrutura de arquivos HTML/CSS para layouts, páginas e componentes.
- **2025-08-05**: Planejamento para exportação dos seeders para arquivos físicos.
- **2025-08-05**: Implementação de script de limpeza automática de módulos e recursos órfãos.
- **2025-08-05**: Inclusão do suporte a gestor-cliente/modulos/ e regras para recursos criados por outros fluxos.

---

> Documentação gerada automaticamente pelo agente GitHub Copilot IA.
