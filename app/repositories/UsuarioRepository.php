<?php

namespace app\repositories;

use app\database\ConnectionFactory;
use app\models\Usuario;
use PDO;

class UsuarioRepository {
    private PDO $connection;

    public function __construct() {
        $this->connection = ConnectionFactory::getConnection();
    }

    public function getUsuarios(): array {
        $sql  = "SELECT id_usuario, nome, email, tipo_perfil FROM usuario ORDER BY id_usuario ASC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getUsuarioById(int $id): array|false {
        $sql  = "SELECT id_usuario, nome, email, tipo_perfil FROM usuario WHERE id_usuario = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getUsuarioByEmail(string $email): Usuario|false {
        $sql  = "SELECT * FROM usuario WHERE email = :email";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $dados = $stmt->fetch();

        if (!$dados) {
            return false;
        }

        return Usuario::arrayParaObjeto($dados);
    }

    public function deleteUsuario(int $id): bool {
        try {
            $this->connection->beginTransaction();
            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0");

            // 1. Obter projetos vinculados ao usuário para limpeza de arquivos e estilos
            $stmtProj = $this->connection->prepare("SELECT id_projeto, fk_estilo, caminho_armazenamento FROM projeto WHERE id_usuario = :id");
            $stmtProj->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtProj->execute();
            $projetos = $stmtProj->fetchAll(PDO::FETCH_ASSOC);

            $estilosParaExcluir = [];
            foreach ($projetos as $proj) {
                if (!empty($proj['fk_estilo'])) {
                    $estilosParaExcluir[] = (int) $proj['fk_estilo'];
                }
                if (!empty($proj['caminho_armazenamento']) && file_exists($proj['caminho_armazenamento'])) {
                    if (is_dir($proj['caminho_armazenamento'])) {
                        $this->removerDiretorioRecursivo($proj['caminho_armazenamento']);
                    } else {
                        @unlink($proj['caminho_armazenamento']);
                    }
                }
            }

            // Desvincular qualquer projeto que tenha ultimo_download referenciando logs deste usuário
            $stmtCleanDown = $this->connection->prepare("UPDATE projeto SET ultimo_download = NULL WHERE ultimo_download IN (SELECT id_log FROM log WHERE fk_usuario = :id)");
            $stmtCleanDown->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtCleanDown->execute();

            // Deletar os projetos do usuário
            $stmtDelProj = $this->connection->prepare("DELETE FROM projeto WHERE id_usuario = :id");
            $stmtDelProj->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDelProj->execute();

            // Deletar estilos associados aos projetos excluídos
            if (!empty($estilosParaExcluir)) {
                $inQuery = implode(',', array_map('intval', array_unique($estilosParaExcluir)));
                $this->connection->exec("DELETE FROM estilo WHERE id_estilo IN ($inQuery)");
            }

            // 2. Desvincular auto-referências de Foreign Keys entre atributos do usuário no PHPMeuAmigo
            $stmtNullAttr = $this->connection->prepare("UPDATE atributo a 
                INNER JOIN tabela t ON a.fk_tabela = t.id_tabela 
                INNER JOIN banco b ON t.fk_banco = b.id_banco 
                SET a.fk_atributo = NULL 
                WHERE b.fk_usuario = :id");
            $stmtNullAttr->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtNullAttr->execute();

            // Deletar atributos, tabelas e bancos do usuário no PHPMeuAmigo
            $stmtDelAttr = $this->connection->prepare("DELETE a FROM atributo a 
                INNER JOIN tabela t ON a.fk_tabela = t.id_tabela 
                INNER JOIN banco b ON t.fk_banco = b.id_banco 
                WHERE b.fk_usuario = :id");
            $stmtDelAttr->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDelAttr->execute();

            $stmtDelTab = $this->connection->prepare("DELETE t FROM tabela t 
                INNER JOIN banco b ON t.fk_banco = b.id_banco 
                WHERE b.fk_usuario = :id");
            $stmtDelTab->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDelTab->execute();

            $stmtDelBanco = $this->connection->prepare("DELETE FROM banco WHERE fk_usuario = :id");
            $stmtDelBanco->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDelBanco->execute();

            // 3. Deletar logs gerados por este usuário
            $stmtDelLogs = $this->connection->prepare("DELETE FROM log WHERE fk_usuario = :id");
            $stmtDelLogs->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDelLogs->execute();

            // 4. Deletar o registro do usuário
            $stmtDelUser = $this->connection->prepare("DELETE FROM usuario WHERE id_usuario = :id");
            $stmtDelUser->bindValue(':id', $id, PDO::PARAM_INT);
            $sucesso = $stmtDelUser->execute();

            $this->connection->commit();
            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");

            // 5. Limpeza de arquivos físicos (avatares, histórico JSON e diretórios)
            $this->limparArquivosFisicosUsuario($id);

            return $sucesso;
        } catch (\Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
            error_log("Erro ao excluir usuário e dados vinculados: " . $e->getMessage());
            throw $e;
        }
    }

    private function limparArquivosFisicosUsuario(int $id): void {
        // Avatares
        $avatarDir = dirname(__DIR__, 2) . '/projetos/avatars';
        foreach (glob($avatarDir . '/' . $id . '_*.*') ?: [] as $arquivo) {
            @unlink($arquivo);
        }

        // Histórico JSON
        $historicoFile = dirname(__DIR__, 2) . '/projetos/historicos/usuario_' . $id . '.json';
        if (file_exists($historicoFile)) {
            @unlink($historicoFile);
        }

        // Diretório de arquivos de projetos do usuário
        $userDir = dirname(__DIR__, 2) . '/projetos/arquivos/' . $id;
        if (is_dir($userDir)) {
            $this->removerDiretorioRecursivo($userDir);
        }
    }

    private function removerDiretorioRecursivo(string $dir): void {
        if (!file_exists($dir)) return;
        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removerDiretorioRecursivo($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function emailExiste(string $email, ?int $idExcluido = null): bool {
        $sql = 'SELECT id_usuario FROM usuario WHERE LOWER(email) = LOWER(:email)';

        if ($idExcluido !== null) {
            $sql .= ' AND id_usuario != :id';
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':email', $email);

        if ($idExcluido !== null) {
            $stmt->bindValue(':id', $idExcluido, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function createUsuario(array $dados): bool {
        $sql = "INSERT INTO usuario (nome, email, senha_usuario, tipo_perfil) 
                VALUES (:nome, :email, :senha, :tipo_perfil)";
        $stmt = $this->connection->prepare($sql);
        
        $stmt->bindValue(':nome', $dados['nome']);
        $stmt->bindValue(':email', $dados['email']);
        $stmt->bindValue(':senha', password_hash($dados['senha'], PASSWORD_DEFAULT));
        $stmt->bindValue(':tipo_perfil', $dados['tipo_perfil']);

        return $stmt->execute();
    }

    public function updateSenhaPorEmail(string $email, string $senha): bool {
        $sql = 'UPDATE usuario SET senha_usuario = :senha WHERE LOWER(email) = LOWER(:email)';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':senha', password_hash($senha, PASSWORD_DEFAULT));
        $stmt->bindValue(':email', $email);

        return $stmt->execute();
    }

    public function updatePerfil(int $id, string $nome, string $email, ?string $senha = null): bool {
        $sql = 'UPDATE usuario SET nome = :nome, email = :email';
        if ($senha !== null) {
            $sql .= ', senha_usuario = :senha';
        }
        $sql .= ' WHERE id_usuario = :id';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($senha !== null) {
            $stmt->bindValue(':senha', password_hash($senha, PASSWORD_DEFAULT));
        }

        return $stmt->execute();
    }

    public function updateUsuario(int $id, array $dados): bool {
        $sql = "UPDATE usuario SET 
                nome = :nome, 
                email = :email, 
                tipo_perfil = :tipo_perfil";
        
        if (!empty($dados['senha'])) {
            $sql .= ", senha_usuario = :senha";
        }
        
        $sql .= " WHERE id_usuario = :id";
        
        $stmt = $this->connection->prepare($sql);
        
        $stmt->bindValue(':nome', $dados['nome']);
        $stmt->bindValue(':email', $dados['email']);
        $stmt->bindValue(':tipo_perfil', $dados['tipo_perfil']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        if (!empty($dados['senha'])) {
            $stmt->bindValue(':senha', password_hash($dados['senha'], PASSWORD_DEFAULT));
        }

        return $stmt->execute();
    }
} 