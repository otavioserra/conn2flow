# Prompt Interactive Programming - Modo Debug

## 🎯 Contexto Inicial
- O objetivo é criar um modo de debug para o instalador do Conn2Flow, permitindo testes locais sem preencher manualmente o formulário e sem baixar o gestor.zip do repositório toda vez.
- O instalador está na pasta `gestor-instalador`.

## 📝 Orientações para o Agente

- [x] 1. Definir o formato e local do arquivo de configuração de debug (`.env.debug` na raiz do projeto).
- [x] 2. Listar todos os campos obrigatórios e opcionais do instalador para preenchimento automático:
    - db_host, db_name, db_user, db_pass, domain, install_path, admin_name, admin_email, admin_pass, admin_pass_confirm, ssl_enabled, clean_install, lang.
- [x] 3. Planejar como o instalador irá detectar e usar esse arquivo para preencher os dados automaticamente (detecção automática do `.env.debug` em ambiente de desenvolvimento).
- [x] 4. Adicionar opção para pular o download do gestor.zip e usar arquivos locais (variável SKIP_DOWNLOAD no `.env.debug`).
- [x] 5. Documentar como ativar/desativar o modo debug e como customizar os dados de instalação.
    - O modo debug é ativado automaticamente se existir o arquivo `.env.debug` na raiz do projeto.
    - Para desativar, basta remover ou renomear o arquivo `.env.debug`.
    - No processo de release, o workflow remove automaticamente o `.env.debug` antes de criar o `instalador.zip`, garantindo que nunca será distribuído em produção.
- [ ] 6. Sugerir melhorias para logs e mensagens de erro no modo debug (exibir detalhes extras, stacktrace, variáveis de ambiente).
- [ ] 7. Validar se há impacto em segurança ao expor dados sensíveis no modo debug e sugerir proteções.
- [ ] 8. Implementar testes automatizados para validar o modo debug e garantir que o instalador funcione corretamente com dados pré-preenchidos.

## 🤔 Dúvidas e 📝 Sugestões
- Qual formato prefere para o arquivo de configuração? (JSON, .env, PHP array)
.env.debug na raiz é a melhor opção. E crie uma opção no index.php para caso exista esse arquivo, ele será usado ao invés do modo normal. Quando for criar o release usando GitHub workflow, este arquivo deverá ser removido: `.github\workflows\release-instalador.yml`
- O modo debug deve ser ativado por variável de ambiente, parâmetro GET/POST ou detecção automática?
Como disse acima, a detecção automática em ambiente de desenvolvimento é a melhor abordagem.
- Alguma restrição quanto à sincronização dos arquivos do gestor para ambiente de teste?
Eu vou usar um script que fiz para isso. Para os dados do instalador eu uso este para sincronizar: `docker\utils\sincroniza-gestor-instalador.sh`, para os dados do gestor eu uso: `docker\utils\sincroniza-gestor.sh`. Então quando modificar ou fizer alguma correção no gestor ou instalador, é só rodar o script em cada caso, que irá pegar a última cópia.
- Alguma preferência para logs detalhados (ex: salvar em arquivo separado, exibir na tela)?
Na verdade já tem todas as saídas de log.

## ✅ Progresso da Implementação
- [ ] item do progresso

---

**Data:** 19/08/2025  
**Desenvolvedor:** Otavio Serra  
**Projeto:** Conn2Flow v1.2.0