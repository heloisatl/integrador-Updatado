# Registro de Mudanças Recentes (Novembro/2026)

Este documento descreve detalhadamente as melhorias, correções de regras de negócio, integrações entre módulos e ajustes visuais realizados no sistema **DevStudio**.

---

## 1. Gestão de Limite de Projetos e Script de Limpeza Automática

### 1.1 Limites Independentes por Módulo
- Foi implementada a separação estrita e independente de cotas: cada usuário autenticado pode manter **até 10 projetos do MVC Creator** e **até 10 páginas do PageMaker** (totalizando até 20 projetos ativos por usuário).
- A verificação de limites agora opera de forma modular (`aplicarLimiteIndependentePorModulo` em `ProjetoController`), impedindo que projetos do PageMaker afetem a cota do MVC Creator e vice-versa.

### 1.2 Script e Stored Procedure de Limpeza Automática
- Criado o script SQL dedicado: [`app/database/scripts/limpar_projetos_antigos.sql`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/database/scripts/limpar_projetos_antigos.sql).
- Adicionada a Stored Procedure `sp_apagar_projeto_mais_antigo_se_limite` ao script principal do banco ([`app/database/scripts/script.sql`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/database/scripts/script.sql)):
  - Ao atingir 10 projetos em um módulo, o sistema apaga automaticamente o registro mais antigo (`ORDER BY data_criacao ASC, id_projeto ASC LIMIT 1`).
  - O método `apagarProjetoMaisAntigoSeLimite` foi incorporado a `ProjetoRepository` e `ProjetoService`, acionando a procedure e realizando a exclusão automática dos arquivos físicos `.zip` correspondentes na pasta `app/projetos/downloads/`.

### 1.3 Aviso Informativo no MVC Creator
- Adicionado aviso explícito na etapa final de geração (`app/views/projetos/mvcCreator/gerar.php`):
  > *"Aviso de Limite de Projetos: Cada usuário pode manter até 10 projetos no sistema. Ao atingir o limite de 10 projetos, o projeto mais antigo será automaticamente apagado para dar lugar ao novo."*
- A mensagem foi mantida na etapa 5 (geração) e removida da etapa 1 (configuração inicial) para evitar sobrecarga visual de alertas no início do formulário.

### 1.4 Limpeza de Arquivos ZIP Antigos
- Todos os arquivos `.zip` antigos e órfãos gerados anteriormente na pasta [`app/projetos/downloads/`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/projetos/downloads/) foram removidos, liberando espaço em disco e garantindo a integridade dos novos pacotes gerados sob demanda.

---

## 2. Integração do PageMaker com o Histórico

- **Salvamento no Histórico**:
  - Implementada a rota e método `salvarPageMakerHistorico` em `ProjetoController`.
  - Adicionado o botão **"Salvar no Histórico"** na interface do PageMaker (`app/views/projetos/pageMaker.php`).
  - Ao realizar o download do pacote ZIP no PageMaker, o projeto agora é registrado automaticamente no histórico do usuário.
- **Download Flexível no Histórico**:
  - No módulo Histórico (`app/views/projetos/historico.php`), projetos do PageMaker agora contam com opções dedicadas para baixar o arquivo **ZIP completo** (HTML + CSS empacotados), o arquivo **HTML** ou a folha de estilo **CSS**.

---

## 3. Exclusão Completa em Cascata de Usuário e Segurança

- **Tratamento de Chaves Estrangeiras (`fk_atributo`)**:
  - Corrigido o erro de violação de integridade referencial (`SQLSTATE[23000]: 1451`) em `UsuarioRepository::deleteUsuario`.
  - Antes da exclusão, as auto-referências de chave estrangeira entre atributos (`fk_atributo`) são anuladas (`SET fk_atributo = NULL`) e a transação é protegida com desativação temporária de constraints (`FOREIGN_KEY_CHECKS = 0`).
- **Remoção Completa em Cascata**:
  - Ao excluir uma conta de usuário, são excluídos todos os dados vinculados:
    1. Projetos gerados no banco de dados e arquivos ZIP físicos.
    2. Estilos personalizados criados no PageMaker associados aos projetos.
    3. Atributos, tabelas e bancos cadastrados no módulo PHPMeuAmigo.
    4. Logs de auditoria gerados pelo usuário.
    5. Arquivos de avatar e histórico individual em JSON (`usuario_{id}.json`).
- **Modal de Exclusão no Perfil e Regras de Negócio**:
  - Criada a seção de exclusão de conta na tela de perfil do usuário com botão discreto e modal de confirmação no padrão visual do sistema.
  - Alerta explícito de que a exclusão é **permanente e irreversível**.
  - Regra de proteção: a conta de administrador conectada não pode ser excluída por si mesma para garantir a integridade de acesso ao sistema.
  - Registro de auditoria: todas as exclusões de usuários e de conta própria geram registro detalhado no módulo de Logs.

---

## 4. PHPMeuAmigo: Importação de Unique Keys (UQ) e Download de SQL

- **Detecção de Chave Única (UQ)**:
  - Verificação e ajuste na extração de metadados de tabelas importadas para assegurar a identificação correta de índices únicos (`UNIQUE` / `UQ`).
- **Download de Script SQL**:
  - Correção na rotina de geração e download de scripts `.sql` no PHPMeuAmigo, garantindo a exportação correta de DDL de bancos, tabelas e atributos.
- **Padronização Visual**:
  - Remoção de degradês (gradientes) dos botões de ação do sistema, adotando cores sólidas e estados `hover` consistentes com a identidade visual do DevStudio.

---

## 5. Retenção de Armazenamento (60 Dias) e Exclusão Segura de Bancos com Senha

### 5.1 Remoção Automática de Projetos após 60 Dias
- **Prevenção de Sobrecarga de Armazenamento**:
  - Implementada a política de retenção automática: todo projeto armazenado no sistema há mais de **60 dias** é expurgado de forma automatizada e transparente para evitar consumo desnecessário de disco no servidor.
- **Camadas de Expurgo**:
  1. **Banco de Dados Relacional**:
     - Procedure `sp_limpar_projetos_expirados` adicionada a [`app/database/scripts/script.sql`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/database/scripts/script.sql) e [`app/database/scripts/limpar_projetos_antigos.sql`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/database/scripts/limpar_projetos_antigos.sql).
     - Métodos `removerProjetosExpirados` integrados a `ProjetoRepository` e `ProjetoService`, removendo registros que excederam o `prazo_de_vida` (60 dias) ou cuja data de criação seja superior a 60 dias.
  2. **Arquivos Físicos Compactados**:
     - Remoção de arquivos `.zip` associados aos projetos expirados em `app/projetos/downloads/` e diretórios públicos.
  3. **Histórico em JSON (`usuario_{id}.json`)**:
     - Método `aplicarRemocaoAutomaticaPorIdade` em `ProjetoController`, que inspeciona carimbos de data/hora (`criado_em`) e remove registros com mais de 60 dias.
  4. **Rotina Global e Executável CLI**:
     - Método `limparProjetosExpiradosSistema` acionado na sincronização periódica de histórico e script dedicado em linha de comando [`app/database/scripts/limpar_projetos_antigos.php`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/database/scripts/limpar_projetos_antigos.php) para execução via agendador de tarefas / crontab.
- **Transparência Visual**:
  - Mensagens informativas de retenção de 60 dias atualizadas nas telas do Histórico ([`app/views/projetos/historico.php`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/views/projetos/historico.php)) e de geração do MVC Creator ([`app/views/projetos/mvcCreator/gerar.php`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/views/projetos/mvcCreator/gerar.php)).

### 5.2 Alerta Visual e Solicitação de Senha na Exclusão de Banco de Dados
- **Identidade Visual Padronizada**:
  - O modal de confirmação de exclusão do banco de dados no PHPMeuAmigo ([`app/views/projetos/phpmeuamigo.php`](file:///c:/xampp/xampp2/htdocs/integrador-Updatado/app/views/projetos/phpmeuamigo.php)) foi alinhado com a identidade visual do DevStudio (`DM Sans`, tons temáticos de perigo com borda avermelhada, ícone `bi-trash3-fill` e aviso de ação crítica irreversível).
  - Integrado o elemento de alerta inline `#phpma-modal-delete-alert` com classes `.alert-danger`, `.alert-warning` e disparo sincronizado com o sistema global de toasts `window.devstudioAlert`.
- **Autenticação Obrigatória por Senha**:
  - Inclusão do campo ergonômico de senha com botão de alternância de visibilidade ("olhinho") no modal de exclusão.
  - Validação estrita no frontend (`public/assets/js/phpmeuamigo.js`), alertando visualmente o usuário caso tente prosseguir sem preencher a senha.
  - Validação criptográfica no backend (`PhpMeuAmigoController::excluirBanco`): valida a senha informada contra o hash do usuário logado via `password_verify` antes de autorizar a remoção do banco de dados. Em caso de senha incorreta ou ausente, a operação é rejeitada com feedback visual amigável e explicativo.
  - Registro de auditoria em `LogService` detalhando a exclusão após autenticação bem-sucedida.
