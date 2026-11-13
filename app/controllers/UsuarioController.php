<?php

namespace app\controllers;

use app\core\Controller;
use app\services\UsuarioService;

class UsuarioController extends Controller {
    private UsuarioService $service;

    public function __construct() {
        $this->service = new UsuarioService();
    }

    public function index(): void {
        $this->adminRequired();

        $data['usuarios'] = $this->service->getUsuarios();
        $data['flash'] = $_SESSION['usuario_flash'] ?? null;
        unset($_SESSION['usuario_flash']);

        $this->view('usuarios/usuario_list', $data);
    }

    public function excluir(): void {
        $this->adminRequired();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);

        if ($id <= 0) {
            $_SESSION['usuario_flash'] = [
                'tipo' => 'erro',
                'mensagem' => 'Identificador de usuário inválido.'
            ];
            $this->redirect(URL_BASE . '/usuarios');
        }

        $usuarioLogado = $_SESSION['usuario_logado'] ?? null;
        if ($usuarioLogado && (int) $usuarioLogado->getIdUsuario() === $id) {
            $_SESSION['usuario_flash'] = [
                'tipo' => 'erro',
                'mensagem' => 'Não é permitido excluir o próprio usuário atualmente conectado.'
            ];
            $this->redirect(URL_BASE . '/usuarios');
        }

        $usuarioAlvo = $this->service->getUsuarioById($id);
        if (!$usuarioAlvo) {
            $_SESSION['usuario_flash'] = [
                'tipo' => 'erro',
                'mensagem' => 'O usuário selecionado não foi encontrado ou já foi excluído.'
            ];
            $this->redirect(URL_BASE . '/usuarios');
        }

        try {
            $sucesso = $this->service->deleteUsuario($id);
            if ($sucesso) {
                \app\services\LogService::registrar("Usuário '{$usuarioAlvo['nome']}' (ID: {$id}) e todos os dados vinculados foram excluídos pelo Administrador.");
                $_SESSION['usuario_flash'] = [
                    'tipo' => 'sucesso',
                    'mensagem' => "Usuário '{$usuarioAlvo['nome']}' e todos os dados vinculados foram excluídos permanentemente com sucesso."
                ];
            } else {
                $_SESSION['usuario_flash'] = [
                    'tipo' => 'erro',
                    'mensagem' => "Não foi possível excluir o usuário '{$usuarioAlvo['nome']}'."
                ];
            }
        } catch (\Throwable $e) {
            $_SESSION['usuario_flash'] = [
                'tipo' => 'erro',
                'mensagem' => "Erro ao excluir o usuário: " . $e->getMessage()
            ];
        }

        $this->redirect(URL_BASE . '/usuarios');
    }

    public function cadastrar(): void {
        $this->adminRequired();
        $this->view('usuarios/usuario_create');
    }

    public function salvar(): void {
        $this->adminRequired();

        $dados = [
            'nome' => $_POST['nome'] ?? '',
            'email' => $_POST['email'] ?? '',
            'senha' => $_POST['senha'] ?? '',
            'usuario_banco' => $_POST['usuario_banco'] ?? '',
            'servidor' => $_POST['servidor'] ?? '',
            'tipo_perfil' => $_POST['tipo_perfil'] ?? '',
        ];

        if ($this->service->createUsuario($dados)) {
            \app\services\LogService::registrar("Novo usuário cadastrado pelo Administrador: " . $dados['nome']);
            $this->redirect(URL_BASE . '/usuarios');
        } else {
            $dados['erro'] = 'Não foi possível cadastrar o usuário. Verifique os dados informados.';
            $this->view('usuarios/usuario_create', $dados);
        }
    }

    public function editar(): void {
        $this->adminRequired();

        if (!isset($_GET['id'])) {
            $this->redirect(URL_BASE . '/usuarios');
        }

        $id = (int) $_GET['id'];
        $data['usuario'] = $this->service->getUsuarioById($id);

        if (!$data['usuario']) {
            $this->redirect(URL_BASE . '/usuarios');
        }

        $this->view('usuarios/usuario_edit', $data);
    }

    public function atualizar(): void {
        $this->adminRequired();

        if (!isset($_POST['id'])) {
            $this->redirect(URL_BASE . '/usuarios');
        }

        $id = (int) $_POST['id'];

        $dados = [
            'nome' => $_POST['nome'] ?? '',
            'email' => $_POST['email'] ?? '',
            'senha' => $_POST['senha'] ?? '',
            'usuario_banco' => $_POST['usuario_banco'] ?? '',
            'servidor' => $_POST['servidor'] ?? '',
            'tipo_perfil' => $_POST['tipo_perfil'] ?? '',
        ];

        if ($this->service->updateUsuario($id, $dados)) {
            \app\services\LogService::registrar("Dados de usuário atualizados pelo Administrador (ID: " . $id . ")");
            $this->redirect(URL_BASE . '/usuarios');
        } else {
            $this->redirect(URL_BASE . '/usuarios/editar?id=' . $id);
        }
    }

    public function perfil(): void {
        if (!isset($_SESSION['usuario_logado'])) {
            $this->redirect(URL_BASE . '/projetos');
        }

        $sessao = $_SESSION['usuario_logado'];
        $usuario = $this->service->getUsuarioPorEmail($sessao->getEmail());
        if (!$usuario) {
            $this->redirect(URL_BASE . '/logout');
        }

        if (empty($_SESSION['perfil_csrf'])) {
            $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
        }

        $id = $usuario->getIdUsuario();
        $avatarPath = $this->obterCaminhoAvatar($id);
        $flash = $_SESSION['perfil_flash'] ?? null;
        unset($_SESSION['perfil_flash']);
        $dadosAntigos = $_SESSION['perfil_dados_antigos'] ?? null;
        unset($_SESSION['perfil_dados_antigos']);

        $nomePadrao = is_array($dadosAntigos) && isset($dadosAntigos['nome']) ? (string) $dadosAntigos['nome'] : $usuario->getNome();
        $emailPadrao = is_array($dadosAntigos) && isset($dadosAntigos['email']) ? (string) $dadosAntigos['email'] : $usuario->getEmail();

        $dados = [
            'nomeExibicao' => strtoupper($usuario->getNome()),
            'nomeUsuario' => $nomePadrao,
            'emailUsuario' => $emailPadrao,
            'avatarUrl' => $avatarPath ? URL_BASE . '/perfil/avatar?v=' . filemtime($avatarPath) : null,
            'mensagemPerfil' => is_array($flash) ? ($flash['mensagem'] ?? '') : '',
            'tipoMensagemPerfil' => is_array($flash) ? ($flash['tipo'] ?? 'sucesso') : 'sucesso',
            'csrfPerfil' => $_SESSION['perfil_csrf'],
            'ehAdmin' => (method_exists($usuario, 'ehAdmin') ? $usuario->ehAdmin() : (strtolower(trim((string) $usuario->getTipoPerfil())) === 'admin')),
        ];
        $this->view('usuarios/perfil', $dados);
    }

    public function atualizarPerfil(): void {
        $this->autenticacaoRequired();
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['perfil_csrf'] ?? '', (string) $_POST['csrf_token'])) {
            $_SESSION['perfil_flash'] = ['tipo' => 'erro', 'mensagem' => 'A sessão do formulário expirou. Atualize a página e tente novamente.'];
            $this->redirect(URL_BASE . '/perfil');
        }

        $sessao = $_SESSION['usuario_logado'];
        $usuarioAtual = $this->service->getUsuarioPorEmail($sessao->getEmail());
        if (!$usuarioAtual) {
            $this->redirect(URL_BASE . '/logout');
        }

        $arquivoEnviado = isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $avatarNovo = null;
        if ($arquivoEnviado) {
            $resultadoAvatar = $this->salvarAvatarEnviado($usuarioAtual->getIdUsuario(), $_FILES['avatar']);
            if (!$resultadoAvatar['sucesso']) {
                $_SESSION['perfil_flash'] = ['tipo' => 'erro', 'mensagem' => $resultadoAvatar['mensagem']];
                $_SESSION['perfil_dados_antigos'] = ['nome' => $_POST['nome'] ?? '', 'email' => $_POST['email'] ?? ''];
                $this->redirect(URL_BASE . '/perfil');
            }
            $avatarNovo = $resultadoAvatar['caminho'];
        }

        $resultado = $this->service->atualizarPerfil($usuarioAtual->getIdUsuario(), $usuarioAtual->getEmail(), $_POST);
        if (!$resultado['sucesso']) {
            if ($avatarNovo && is_file($avatarNovo)) {
                unlink($avatarNovo);
            }
            $_SESSION['perfil_flash'] = ['tipo' => 'erro', 'mensagem' => $resultado['mensagem']];
            $_SESSION['perfil_dados_antigos'] = ['nome' => $_POST['nome'] ?? '', 'email' => $_POST['email'] ?? ''];
            $this->redirect(URL_BASE . '/perfil');
        }

        if ($avatarNovo || !empty($_POST['remover_avatar'])) {
            $this->removerAvataresAntigos($usuarioAtual->getIdUsuario(), $avatarNovo);
        }

        $usuarioAtualizado = $this->service->getUsuarioPorEmail(strtolower(trim((string) $_POST['email'])));
        if ($usuarioAtualizado) {
            $_SESSION['usuario_logado'] = $usuarioAtualizado;
        }

        $_SESSION['perfil_csrf'] = bin2hex(random_bytes(32));
        \app\services\LogService::registrar('Perfil de usuário atualizado com sucesso.');
        $_SESSION['perfil_flash'] = ['tipo' => 'sucesso', 'mensagem' => $resultado['mensagem']];
        $this->redirect(URL_BASE . '/perfil');
    }

    public function excluirContaPropria(): void {
        $this->autenticacaoRequired();

        $csrf = (string) ($_POST['csrf_token'] ?? '');
        if ($csrf === '' || !hash_equals((string) ($_SESSION['perfil_csrf'] ?? ''), $csrf)) {
            $_SESSION['perfil_flash'] = [
                'tipo' => 'erro',
                'mensagem' => 'Sessão expirada. Recarregue a página e tente novamente.'
            ];
            $this->redirect(URL_BASE . '/perfil');
            return;
        }

        $usuarioAtual = $_SESSION['usuario_logado'] ?? null;
        if (!$usuarioAtual || !method_exists($usuarioAtual, 'getIdUsuario')) {
            $this->redirect(URL_BASE . '/login');
            return;
        }

        $id = (int) $usuarioAtual->getIdUsuario();
        $nome = method_exists($usuarioAtual, 'getNome') ? $usuarioAtual->getNome() : 'Usuário';

        // Regra de segurança: Administrador conectado não pode apagar a própria conta conectada
        $tipoPerfil = method_exists($usuarioAtual, 'getTipoPerfil') ? strtolower(trim((string) $usuarioAtual->getTipoPerfil())) : '';
        if ($tipoPerfil === 'admin' || (method_exists($usuarioAtual, 'ehAdmin') && $usuarioAtual->ehAdmin())) {
            $_SESSION['perfil_flash'] = [
                'tipo' => 'erro',
                'mensagem' => 'A conta de administrador conectada não pode ser excluída por questões de segurança do sistema.'
            ];
            $this->redirect(URL_BASE . '/perfil');
            return;
        }

        try {
            // Registrar log de auditoria permanente da autoexclusão atrelado ao sistema (ID 1)
            \app\services\LogService::registrar("O usuário '{$nome}' (ID: {$id}) excluiu permanentemente sua própria conta e todos os dados vinculados.", 1);

            $sucesso = $this->service->deleteUsuario($id);

            if ($sucesso) {
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                session_start();
                $_SESSION['login_flash'] = [
                    'tipo' => 'sucesso',
                    'mensagem' => 'Sua conta e todos os dados vinculados foram excluídos permanentemente com sucesso.'
                ];
                $this->redirect(URL_BASE . '/login');
                return;
            } else {
                $_SESSION['perfil_flash'] = [
                    'tipo' => 'erro',
                    'mensagem' => 'Não foi possível concluir a exclusão da conta.'
                ];
                $this->redirect(URL_BASE . '/perfil');
                return;
            }
        } catch (\Throwable $e) {
            $_SESSION['perfil_flash'] = [
                'tipo' => 'erro',
                'mensagem' => 'Erro ao excluir conta: ' . $e->getMessage()
            ];
            $this->redirect(URL_BASE . '/perfil');
        }
    }

    public function avatar(): void {
        $this->autenticacaoRequired();
        $usuario = $this->service->getUsuarioPorEmail($_SESSION['usuario_logado']->getEmail());
        $caminho = $usuario ? $this->obterCaminhoAvatar($usuario->getIdUsuario()) : null;
        if (!$caminho || !is_file($caminho)) {
            http_response_code(404);
            exit();
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($caminho);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            http_response_code(404);
            exit();
        }

        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($caminho);
        exit();
    }

    private function obterDiretorioAvatares(): string {
        return __DIR__ . '/../projetos/avatars';
    }

    private function obterCaminhoAvatar(int $id): ?string {
        $arquivos = glob($this->obterDiretorioAvatares() . '/' . $id . '_*.*');
        return $arquivos ? $arquivos[0] : null;
    }

    private function salvarAvatarEnviado(int $id, array $arquivo): array {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['sucesso' => false, 'mensagem' => 'O upload da foto falhou. Tente novamente.'];
        }
        if (($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['sucesso' => false, 'mensagem' => 'A foto deve ter no máximo 2 MB.'];
        }

        $tmp = $arquivo['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            return ['sucesso' => false, 'mensagem' => 'O arquivo enviado não é válido.'];
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensoes[$mime]) || @getimagesize($tmp) === false) {
            return ['sucesso' => false, 'mensagem' => 'Formato inválido. Envie uma imagem JPG, PNG ou WEBP.'];
        }

        $diretorio = $this->obterDiretorioAvatares();
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            return ['sucesso' => false, 'mensagem' => 'Não foi possível preparar o armazenamento da foto.'];
        }

        $destino = $diretorio . '/' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $extensoes[$mime];
        if (!move_uploaded_file($tmp, $destino)) {
            return ['sucesso' => false, 'mensagem' => 'Não foi possível salvar a foto. Tente novamente.'];
        }

        return ['sucesso' => true, 'caminho' => $destino];
    }

    private function removerAvataresAntigos(int $id, ?string $preservar = null): void {
        foreach (glob($this->obterDiretorioAvatares() . '/' . $id . '_*.*') ?: [] as $arquivo) {
            if ($preservar === null || $arquivo !== $preservar) {
                @unlink($arquivo);
            }
        }
    }
}
