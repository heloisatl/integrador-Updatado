<?php
include_once(__DIR__ . "/../include/head.php");
include_once(__DIR__ . "/../include/navigation.php");

$nomeExibicao = $nomeExibicao ?? 'PERFIL';
$nomeUsuario = $nomeUsuario ?? '';
$emailUsuario = $emailUsuario ?? '';
$avatarUrl = $avatarUrl ?? null;
$mensagemPerfil = $mensagemPerfil ?? '';
$tipoMensagemPerfil = $tipoMensagemPerfil ?? 'sucesso';
$csrfPerfil = $csrfPerfil ?? '';
$ehAdmin = $ehAdmin ?? false;
?>

<link rel="stylesheet" href="<?= URL_BASE ?>/assets/css/perfil.css?v=<?= time() ?>">

<div class="perfil-container">
        <header class="perfil-header">
            <h1 class="perfil-nome-titulo">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($nomeExibicao, ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="perfil-subtitulo">Gerencie seus dados pessoais, sua foto e a segurança da conta.</p>
        </header>

        <?php if ($mensagemPerfil !== ''): ?>
            <div class="perfil-feedback perfil-feedback-<?= htmlspecialchars($tipoMensagemPerfil, ENT_QUOTES, 'UTF-8') ?>" role="status">
                <i class="bi <?= $tipoMensagemPerfil === 'sucesso' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>" aria-hidden="true"></i>
                <?= htmlspecialchars($mensagemPerfil, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="perfil-formulario" action="<?= URL_BASE ?>/perfil/atualizar" method="post" enctype="multipart/form-data" id="perfilForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfPerfil, ENT_QUOTES, 'UTF-8') ?>">

            <section class="perfil-card">
                <div class="perfil-secao-cabecalho">
                    <span class="perfil-secao-icone"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                    <div>
                        <h2>Dados pessoais</h2>
                        <p>Atualize como suas informações aparecem na plataforma.</p>
                    </div>
                </div>

                <div class="perfil-avatar-editor">
                    <div class="perfil-avatar-wrapper">
                        <div class="perfil-avatar">
                            <?php if ($avatarUrl): ?>
                                <img id="perfilAvatarPreview" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <img id="perfilAvatarPreview" src="" alt="Prévia da foto de perfil" hidden>
                                <i class="bi bi-person-fill perfil-avatar-icon" id="perfilAvatarPlaceholder" aria-hidden="true"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="perfil-avatar-controles">
                        <strong>Foto de perfil</strong>
                        <span>JPG, PNG ou WEBP · máximo 2 MB</span>
                        <div class="perfil-avatar-botoes">
                            <label class="perfil-btn-foto" for="perfilAvatarInput"><i class="bi bi-camera" aria-hidden="true"></i> Escolher foto</label>
                            <input class="perfil-input-arquivo" type="file" id="perfilAvatarInput" name="avatar" accept="image/jpeg,image/png,image/webp">
                            <input type="hidden" name="remover_avatar" id="perfilRemoverAvatar" value="0">
                            <button type="button" class="perfil-btn-remover" id="perfilBtnRemover" <?= !$avatarUrl ? 'style="display: none;"' : '' ?>>
                                <i class="bi bi-trash" id="perfilBtnRemoverIcone" aria-hidden="true"></i>
                                <span id="perfilBtnRemoverTexto">Remover foto</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="perfil-grade-campos">
                    <div class="perfil-campo">
                        <label for="perfil-nome">Nome completo</label>
                        <input type="text" id="perfil-nome" name="nome" value="<?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>" maxlength="60" autocomplete="name" required>
                    </div>
                    <div class="perfil-campo">
                        <label for="perfil-email">E-mail</label>
                        <input type="email" id="perfil-email" name="email" value="<?= htmlspecialchars($emailUsuario, ENT_QUOTES, 'UTF-8') ?>" maxlength="60" autocomplete="email" required>
                    </div>
                </div>
            </section>

            <section class="perfil-card perfil-card-seguranca">
                <div class="perfil-secao-cabecalho">
                    <span class="perfil-secao-icone"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                    <div>
                        <h2>Alterar senha</h2>
                        <p>Deixe os campos vazios se não deseja trocar sua senha.</p>
                    </div>
                </div>

                <div class="perfil-campo">
                    <label for="perfil-senha-atual">Senha atual</label>
                    <div class="perfil-senha-wrapper">
                        <input type="password" id="perfil-senha-atual" name="senha_atual" autocomplete="current-password">
                        <button type="button" class="perfil-btn-toggle-senha" data-target="perfil-senha-atual" title="Mostrar/ocultar senha" aria-label="Mostrar/ocultar senha">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="perfil-grade-campos">
                    <div class="perfil-campo">
                        <label for="perfil-senha-nova">Nova senha</label>
                        <div class="perfil-senha-wrapper">
                            <input type="password" id="perfil-senha-nova" name="senha_nova" autocomplete="new-password" minlength="10" aria-describedby="perfilRegraSenha">
                            <button type="button" class="perfil-btn-toggle-senha" data-target="perfil-senha-nova" title="Mostrar/ocultar senha" aria-label="Mostrar/ocultar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="perfil-campo">
                        <label for="perfil-confirmar-senha">Confirmar nova senha</label>
                        <div class="perfil-senha-wrapper">
                            <input type="password" id="perfil-confirmar-senha" name="confirmar_senha" autocomplete="new-password" minlength="10">
                            <button type="button" class="perfil-btn-toggle-senha" data-target="perfil-confirmar-senha" title="Mostrar/ocultar senha" aria-label="Mostrar/ocultar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <p class="perfil-ajuda-senha" id="perfilRegraSenha">Use pelo menos 10 caracteres, com letras maiúsculas e minúsculas, número e símbolo.</p>
            </section>

            <div class="perfil-acoes">
                <a href="<?= URL_BASE ?>/projetos" class="perfil-btn-voltar"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar ao início</a>
                <button type="submit" class="perfil-btn-salvar"><i class="bi bi-check2-circle" aria-hidden="true"></i> Salvar alterações</button>
            </div>
        </form>

        <!-- Zona de Perigo: Exclusão de Conta -->
        <section class="perfil-card perfil-card-perigo" style="border: 1px solid rgba(239, 68, 68, 0.35); margin-top: 28px; background: rgba(239, 68, 68, 0.04);">
            <div class="perfil-secao-cabecalho">
                <span class="perfil-secao-icone" style="color: #ef4444; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.25);">
                    <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 style="color: #ef4444;">Excluir Conta</h2>
                    <p>Encerre sua conta e remova permanentemente seus dados do DevStudio.</p>
                </div>
            </div>

            <?php if ($ehAdmin): ?>
                <div style="margin-top: 14px; padding: 12px 14px; background: rgba(255, 255, 255, 0.04); border: 1px solid var(--border); border-radius: 8px; font-size: 13.5px; color: var(--muted); display: flex; align-items: flex-start; gap: 10px;">
                    <i class="bi bi-shield-lock-fill" style="color: var(--accent); font-size: 16px; flex-shrink: 0; margin-top: 2px;"></i>
                    <span><strong>Conta de Administrador Conectada:</strong> A conta de administrador atualmente conectada não pode ser excluída por questões de integridade do sistema.</span>
                </div>
            <?php else: ?>
                <p style="color: var(--muted); font-size: 13.5px; margin: 12px 0 16px; line-height: 1.5;">
                    Ao excluir sua conta, todos os seus dados vinculados (projetos gerados, modelagens no PHPMeuAmigo, arquivos e histórico) serão <strong>permanentemente apagados</strong>. <strong>Esta ação é irreversível.</strong>
                </p>
                <button type="button" class="btn btn-danger" id="btnAbrirModalExcluirConta" style="background: #ef4444; border-color: #dc2626; color: #fff; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-trash3-fill"></i> Excluir Minha Conta
                </button>
            <?php endif; ?>
        </section>
    </div>

    <!-- Modal de Confirmação de Exclusão de Conta Própria DevStudio -->
    <?php if (!$ehAdmin): ?>
    <div id="modalExcluirContaPropria" class="perfil-modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="modalExcluirContaTitulo">
        <div class="perfil-modal-dialog">
            <div class="perfil-modal-header" style="border-bottom-color: rgba(239, 68, 68, 0.3);">
                <h3 id="modalExcluirContaTitulo" class="perfil-modal-title" style="color: #ef4444;">
                    <i class="bi bi-person-x-fill"></i> Confirmar Exclusão Definitiva
                </h3>
                <button type="button" class="perfil-modal-close" id="btnFecharModalExcluirConta" aria-label="Fechar modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="perfil-modal-body">
                <div class="perfil-modal-warning" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.35); color: #f87171;">
                    <i class="bi bi-exclamation-triangle-fill" style="color: #ef4444; font-size: 20px;"></i>
                    <div style="color: var(--text);">
                        <strong style="color: #ef4444;">Ação Estritamente Irreversível:</strong> Tem certeza que deseja encerrar e excluir sua conta no DevStudio? Todos os seus projetos, bancos de dados modelados, atributos, arquivos e histórico de atividades serão permanentemente apagados.
                    </div>
                </div>
                <p style="font-size: 13.5px; color: var(--muted); margin-top: 14px; line-height: 1.5;">
                    Uma vez confirmada, a sua sessão será imediatamente encerrada e você não poderá recuperar nenhuma informação vinculada à conta.
                </p>
            </div>

            <div class="perfil-modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelarModalExcluirConta">
                    Cancelar
                </button>
                <form method="POST" action="<?= URL_BASE ?>/perfil/excluir" style="display: inline-flex; margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfPerfil, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-danger" style="background: #ef4444; border-color: #dc2626; color: #fff;">
                        <i class="bi bi-trash3-fill"></i> Sim, Excluir Minha Conta Permanentemente
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal de Confirmação de Alterações Sensíveis no Perfil DevStudio -->
    <div id="modalConfirmarPerfil" class="perfil-modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="modalConfirmarPerfilTitulo">
        <div class="perfil-modal-dialog">
            <div class="perfil-modal-header">
                <h3 id="modalConfirmarPerfilTitulo" class="perfil-modal-title">
                    <i class="bi bi-shield-exclamation"></i> Confirmar Alteração
                </h3>
                <button type="button" class="perfil-modal-close" id="btnFecharModalPerfil" aria-label="Fechar modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="perfil-modal-body">
                <p style="color: var(--muted); font-size: 13.5px; margin: 0 0 4px; line-height: 1.5;">
                    As seguintes modificações importantes estão prestes a ser aplicadas à sua conta:
                </p>

                <div class="perfil-modal-lista-acoes" id="perfilModalListaAcoes">
                    <!-- Preenchido dinamicamente via JS -->
                </div>

                <div class="perfil-modal-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Atenção:</strong> Tem certeza que deseja prosseguir e salvar essas alterações?
                    </div>
                </div>
            </div>

            <div class="perfil-modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelarModalPerfil">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmarModalPerfil">
                    <i class="bi bi-check2"></i> Sim, Continuar
                </button>
            </div>
        </div>
    </div>

<script>
    (() => {
        const form = document.getElementById('perfilForm');
        const inputFoto = document.getElementById('perfilAvatarInput');
        const preview = document.getElementById('perfilAvatarPreview');
        const placeholder = document.getElementById('perfilAvatarPlaceholder');
        const inputRemover = document.getElementById('perfilRemoverAvatar');
        const btnRemover = document.getElementById('perfilBtnRemover');
        const btnRemoverIcone = document.getElementById('perfilBtnRemoverIcone');
        const btnRemoverTexto = document.getElementById('perfilBtnRemoverTexto');
        const senhaAtual = document.getElementById('perfil-senha-atual');
        const senhaNova = document.getElementById('perfil-senha-nova');
        const confirmarSenha = document.getElementById('perfil-confirmar-senha');
        let urlPreview = null;
        const temAvatarOriginal = preview && preview.getAttribute('src') !== '';

        if (temAvatarOriginal) {
            preview.dataset.avatarOriginalSrc = preview.getAttribute('src');
        }

        inputFoto?.addEventListener('change', () => {
            const arquivo = inputFoto.files?.[0];
            if (!arquivo) return;
            if (arquivo.size > 2 * 1024 * 1024) {
                devstudioAlert('A foto deve ter no máximo 2 MB.', 'erro');
                inputFoto.value = '';
                return;
            }
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(arquivo.type)) {
                devstudioAlert('Selecione uma imagem JPG, PNG ou WEBP.', 'erro');
                inputFoto.value = '';
                return;
            }
            if (urlPreview) URL.revokeObjectURL(urlPreview);
            urlPreview = URL.createObjectURL(arquivo);
            preview.src = urlPreview;
            preview.hidden = false;
            if (placeholder) placeholder.hidden = true;
            if (inputRemover) inputRemover.value = '0';
            if (btnRemover) {
                btnRemover.style.display = 'inline-flex';
                btnRemover.classList.remove('ativo');
                if (btnRemoverIcone) btnRemoverIcone.className = 'bi bi-trash';
                if (btnRemoverTexto) btnRemoverTexto.textContent = 'Remover foto selecionada';
            }
        });

        btnRemover?.addEventListener('click', () => {
            // Se havia um arquivo recém-selecionado mas não salvo, cancela a seleção
            if (inputFoto?.files?.length > 0) {
                inputFoto.value = '';
                if (urlPreview) {
                    URL.revokeObjectURL(urlPreview);
                    urlPreview = null;
                }
                if (temAvatarOriginal) {
                    preview.src = preview.dataset.avatarOriginalSrc;
                    preview.hidden = false;
                    if (placeholder) placeholder.hidden = true;
                    inputRemover.value = '0';
                    btnRemover.classList.remove('ativo');
                    btnRemoverIcone.className = 'bi bi-trash';
                    btnRemoverTexto.textContent = 'Remover foto';
                } else {
                    preview.hidden = true;
                    if (placeholder) placeholder.hidden = false;
                    btnRemover.style.display = 'none';
                }
                return;
            }

            // Alternar estado de remoção do avatar já existente
            if (inputRemover.value === '1') {
                inputRemover.value = '0';
                preview.hidden = false;
                if (placeholder) placeholder.hidden = true;
                btnRemover.classList.remove('ativo');
                btnRemoverIcone.className = 'bi bi-trash';
                btnRemoverTexto.textContent = 'Remover foto';
            } else {
                inputRemover.value = '1';
                preview.hidden = true;
                if (placeholder) placeholder.hidden = false;
                btnRemover.classList.add('ativo');
                btnRemoverIcone.className = 'bi bi-arrow-counterclockwise';
                btnRemoverTexto.textContent = 'Desfazer remoção';
            }
        });

        // Controle do Modal Customizado de Confirmação no Perfil
        let modalConfirmado = false;
        const modal = document.getElementById('modalConfirmarPerfil');
        const modalListaAcoes = document.getElementById('perfilModalListaAcoes');
        const btnConfirmarModal = document.getElementById('btnConfirmarModalPerfil');
        const btnCancelarModal = document.getElementById('btnCancelarModalPerfil');
        const btnFecharModal = document.getElementById('btnFecharModalPerfil');

        function fecharModal() {
            if (!modal) return;
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function abrirModal(acoes) {
            if (!modal || !modalListaAcoes) return;
            modalListaAcoes.innerHTML = '';

            acoes.forEach((acao) => {
                const item = document.createElement('div');
                item.className = 'perfil-modal-item';
                if (acao === 'senha') {
                    item.innerHTML = `
                        <i class="bi bi-key-fill" style="color: var(--accent);"></i>
                        <div><strong>Alteração de Senha:</strong> Uma nova senha de acesso será definida para a sua conta.</div>
                    `;
                } else if (acao === 'foto') {
                    item.innerHTML = `
                        <i class="bi bi-image" style="color: #ef4444;"></i>
                        <div><strong>Remoção de Foto:</strong> A sua foto de perfil atual será removida do sistema.</div>
                    `;
                }
                modalListaAcoes.appendChild(item);
            });

            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        form?.addEventListener('submit', (evento) => {
            if (modalConfirmado) {
                return;
            }

            const vaiAlterarSenha = senhaAtual.value !== '' || senhaNova.value !== '' || confirmarSenha.value !== '';
            const vaiRemoverFoto = inputRemover?.value === '1';
            const acoesCriticas = [];

            if (vaiAlterarSenha) acoesCriticas.push('senha');
            if (vaiRemoverFoto) acoesCriticas.push('foto');

            if (acoesCriticas.length > 0) {
                evento.preventDefault();
                abrirModal(acoesCriticas);
            }
        });

        btnConfirmarModal?.addEventListener('click', () => {
            modalConfirmado = true;
            fecharModal();
            form?.submit();
        });

        if (btnCancelarModal) btnCancelarModal.addEventListener('click', fecharModal);
        if (btnFecharModal) btnFecharModal.addEventListener('click', fecharModal);

        modal?.addEventListener('click', (e) => {
            if (e.target === modal) fecharModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal?.classList.contains('open')) {
                fecharModal();
            }
        });

        // Modal de Exclusão de Conta Própria
        const modalExcluirConta = document.getElementById('modalExcluirContaPropria');
        const btnAbrirModalExcluir = document.getElementById('btnAbrirModalExcluirConta');
        const btnFecharModalExcluir = document.getElementById('btnFecharModalExcluirConta');
        const btnCancelarModalExcluir = document.getElementById('btnCancelarModalExcluirConta');

        function fecharModalExcluirConta() {
            if (!modalExcluirConta) return;
            modalExcluirConta.classList.remove('open');
            modalExcluirConta.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function abrirModalExcluirConta() {
            if (!modalExcluirConta) return;
            modalExcluirConta.classList.add('open');
            modalExcluirConta.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        if (btnAbrirModalExcluir) btnAbrirModalExcluir.addEventListener('click', abrirModalExcluirConta);
        if (btnFecharModalExcluir) btnFecharModalExcluir.addEventListener('click', fecharModalExcluirConta);
        if (btnCancelarModalExcluir) btnCancelarModalExcluir.addEventListener('click', fecharModalExcluirConta);

        modalExcluirConta?.addEventListener('click', (e) => {
            if (e.target === modalExcluirConta) fecharModalExcluirConta();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalExcluirConta?.classList.contains('open')) {
                fecharModalExcluirConta();
            }
        });

        // Alternar visibilidade das senhas (mesmo padrão do login/cadastro)
        document.querySelectorAll('.perfil-btn-toggle-senha').forEach((btn) => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.dataset.target;
                const input = targetId ? document.getElementById(targetId) : this.previousElementSibling;
                const icone = this.querySelector('i');
                if (input && icone) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icone.classList.remove('bi-eye');
                        icone.classList.add('bi-eye-slash');
                        this.setAttribute('aria-label', 'Ocultar senha');
                    } else {
                        input.type = 'password';
                        icone.classList.remove('bi-eye-slash');
                        icone.classList.add('bi-eye');
                        this.setAttribute('aria-label', 'Mostrar senha');
                    }
                }
            });
        });
    })();
</script>

<?php require_once __DIR__ . '/../include/footer.php'; ?>
</main>
</div>
</body>
</html>
