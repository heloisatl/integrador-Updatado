<?php $isVisitante = empty($_SESSION['usuario_logado']); ?>
<div class="mvc-etapa">
    <?php if ($isVisitante): ?>
        <div class="mvc-aviso-info" role="status">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span><strong>Aviso de Sessão (Modo Visitante):</strong> Os dados e arquivos gerados nesta sessão não serão salvos no servidor nem registrados no histórico.</span>
        </div>
    <?php else: ?>
        <div class="mvc-aviso-info" role="status" style="margin-bottom: 16px;">
            <i class="bi bi-info-circle" aria-hidden="true" style="font-size: 16px; flex-shrink: 0;"></i>
            <span><strong>Aviso de Armazenamento e Limite:</strong> Projetos armazenados no sistema são removidos automaticamente após 60 dias para evitar sobrecarga no servidor. Além disso, cada usuário pode manter até 10 projetos no sistema (ao atingir o limite, o mais antigo será <strong>automaticamente apagado</strong> para dar lugar ao novo).</span>
        </div>
    <?php endif; ?>

    <p class="mvc-subtitulo">Clique no botão abaixo para gerar o código MVC e baixar o arquivo .ZIP completo:</p>

    <div class="mvc-acoes" style="flex-wrap: wrap;">
        <button type="button" id="btn-gerar-final" onclick="executarGeracaoMvc();" class="mvc-etapa-botao" style="font-size: 16px; padding: 12px 24px;">
            <?= $isVisitante ? 'Baixar Projeto (.ZIP)' : 'Gerar Todo o Sistema (.ZIP)' ?>
        </button>
        <a href="?step=estrutura" id="btn-visualizar-estrutura" class="mvc-etapa-botao mvc-etapa-botao-secundario">
            Visualizar Estrutura
        </a>
        <?php if (!$isVisitante): ?>
            <button type="button" id="btn-salvar-historico-mvc" onclick="salvarMvcNoHistorico();" class="mvc-etapa-botao mvc-etapa-botao-secundario">
                <i class="bi bi-bookmark-check"></i> Salvar no Histórico
            </button>
        <?php endif; ?>
    </div>

    <!-- <div id="mvc-conclusao-geracao" style="display: none; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border);">
        <h3 style="font-size: 16px; margin-bottom: 8px; color: var(--text);">Projeto compilado com sucesso</h3>
        <p id="mvc-conclusao-resumo" class="mvc-subtitulo" style="margin-bottom: 10px;"></p>

        <?php if ($isVisitante): ?>
            <p class="mvc-subtitulo" style="font-size: 12px; opacity: 0.85; margin-bottom: 14px;">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Seus arquivos temporários são removidos automaticamente do servidor imediatamente após o download.
            </p>
        <?php endif; ?>

        <div class="mvc-acoes" style="flex-wrap: wrap;">
            <button type="button" id="btn-baixar-novamente" onclick="baixarNovamenteZip();" class="mvc-etapa-botao">
                Baixar Projeto (.ZIP)
            </button>
            <a href="?step=estrutura" class="mvc-etapa-botao mvc-etapa-botao-secundario">
                Visualizar Estrutura
            </a>
            <?php if (!$isVisitante): ?>
                <a href="<?= URL_BASE ?>/projetos/historico" class="mvc-etapa-botao mvc-etapa-botao-secundario">
                    Salvar no Histórico
                </a>
            <?php endif; ?>
        </div>
    </div> -->
</div>
