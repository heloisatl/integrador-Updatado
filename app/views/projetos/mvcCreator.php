<?php
include_once(__DIR__ . "/../include/head.php");
include_once(__DIR__ . "/../include/navigation.php");
?>

    <link rel="stylesheet" href="<?= URL_BASE_CSS ?>/pagina-mvc.css">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <?php
    $step = $_GET['step'] ?? 'configurar';

    $steps = [
        'configurar' => [
            'title' => 'Configurar Projeto',
            'number' => '1',
            'file' => __DIR__ . '/mvcCreator/configurar.php',
        ],
        'tabelas' => [
            'title' => 'Tabelas Detectadas',
            'number' => '2',
            'file' => __DIR__ . '/mvcCreator/tabelas.php',
        ],
        'opcoes' => [
            'title' => 'Opções de Geração',
            'number' => '3',
            'file' => __DIR__ . '/mvcCreator/opcoes.php',
        ],
        'estrutura' => [
            'title' => 'Estrutura de Arquivos que Será Criada',
            'number' => '4',
            'file' => __DIR__ . '/mvcCreator/estrutura.php',
        ],
        'gerar' => [
            'title' => 'Gerar Sistema',
            'number' => '5',
            'file' => __DIR__ . '/mvcCreator/gerar.php',
        ],
    ];

    if (!isset($steps[$step])) {
        $step = 'configurar';
    }
    $modoVisitante = isset($modoVisitante) ? (bool) $modoVisitante : empty($_SESSION['usuario_logado']);
    ?>

    <div class="mvc-etapa-wrapper" id="mvcCreatorWrapper" data-visitante="<?= $modoVisitante ? '1' : '0' ?>">
        <header class="page-header mvc-page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-braces"></i> MVC Creator
                </h1>
            </div>
        </header>

        <div class="mvc-etapa-cabecalho">
            <span class="mvc-etapa-selo"><?= htmlspecialchars($steps[$step]['number']) ?></span>
            <h2><?= htmlspecialchars($steps[$step]['title']) ?></h2>
        </div>

        <div id="alerta-erro" class="alerta alerta-erro oculto" role="alert">
            <span id="alerta-mensagem"></span>
            <button type="button" class="alerta-fechar" onclick="fecharAlerta()">&times;</button>
        </div>

        <?php require $steps[$step]['file']; ?>
    </div>

    <?php require_once __DIR__ . '/../include/footer.php'; ?>
    </main>
    <?php
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $isLoopback = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
    $hostPadrao = $isLoopback ? 'localhost' : $clientIp;
    ?>
    <script>
        window.MODO_VISITANTE = <?= $modoVisitante ? 'true' : 'false' ?>;
        window.MVC_DEFAULT_HOST = <?= json_encode($hostPadrao) ?>;
    </script>
    <script src="<?= URL_BASE ?>/assets/js/arvoreArquivos.js"></script>
    <script src="<?= URL_BASE ?>/assets/js/mvcLoad.js"></script>
    <!-- <script src="<?= URL_BASE ?>/assets/js/desabilitado.js"></script> -->
</div>
</body>

</html>