<?php
namespace app\repositories;

use app\database\ConnectionFactory;
use app\models\Projeto;
use PDO;

class ProjetoRepository{
    private PDO $conn;
    
    public function __construct(){
        $this->conn =  ConnectionFactory::getConnection();
    }


    public function insert(Projeto $projeto){
        $sql = "INSERT INTO `mvc_creator`.`projeto`(id_usuario,fk_banco,fk_estilo,nome_projeto,data_criacao,prazo_de_vida,caminho_armazenamento,comentarios,views,ultimo_download)
         VALUES (?,?,?,?,?,?,?,?,?,?);";
        $stm = $this->conn->prepare($sql);
        $stm->execute($this->projetoParams($projeto));
        return $this->conn->lastInsertId() ? $this->conn->lastInsertId() : false;
    }

    /**
     * Mantém o limite máximo de 10 projetos armazenados por usuário autenticado (RN 01),
     * removendo os registros mais antigos caso a cota seja ultrapassada.
     */
    public function limitarCotaPorUsuario(int $idUsuario, int $limite = 10): void {
        if ($idUsuario <= 0 || $limite <= 0) {
            return;
        }
        $sqlIds = "SELECT id_projeto FROM projeto WHERE id_usuario = :id_usuario ORDER BY data_criacao DESC, id_projeto DESC";
        $stm = $this->conn->prepare($sqlIds);
        $stm->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stm->execute();
        $ids = $stm->fetchAll(PDO::FETCH_COLUMN);

        if (count($ids) > $limite) {
            $excedentes = array_slice($ids, $limite);
            $placeholders = implode(',', array_fill(0, count($excedentes), '?'));
            $sqlDelete = "DELETE FROM projeto WHERE id_usuario = ? AND id_projeto IN ({$placeholders})";
            $stmDel = $this->conn->prepare($sqlDelete);
            $stmDel->execute(array_merge([$idUsuario], array_map('intval', $excedentes)));
        }
    }

    /**
     * Remove o projeto mais antigo do usuário caso o limite máximo seja atingido ou superado,
     * liberando espaço para um novo projeto. Tenta usar a stored procedure caso disponível,
     * ou realiza a remoção direta via PDO.
     * Retorna os dados do projeto removido (ou null se nenhum foi removido).
     */
    public function apagarProjetoMaisAntigoSeLimite(int $idUsuario, int $limite = 10): ?array {
        if ($idUsuario <= 0 || $limite <= 0) {
            return null;
        }

        $sqlCount = "SELECT COUNT(*) FROM `projeto` WHERE `id_usuario` = :id_usuario";
        $stmCount = $this->conn->prepare($sqlCount);
        $stmCount->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmCount->execute();
        $total = (int) $stmCount->fetchColumn();

        if ($total >= $limite) {
            $sqlAntigo = "SELECT `id_projeto`, `nome_projeto`, `caminho_armazenamento` 
                          FROM `projeto` 
                          WHERE `id_usuario` = :id_usuario 
                          ORDER BY `data_criacao` ASC, `id_projeto` ASC 
                          LIMIT 1";
            $stmAntigo = $this->conn->prepare($sqlAntigo);
            $stmAntigo->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmAntigo->execute();
            $projetoAntigo = $stmAntigo->fetch(PDO::FETCH_ASSOC);

            if ($projetoAntigo && !empty($projetoAntigo['id_projeto'])) {
                $idAntigo = (int) $projetoAntigo['id_projeto'];

                try {
                    $stmtProc = $this->conn->prepare("CALL sp_apagar_projeto_mais_antigo_se_limite(:id_usuario, :limite)");
                    $stmtProc->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
                    $stmtProc->bindValue(':limite', $limite, PDO::PARAM_INT);
                    $stmtProc->execute();
                    $stmtProc->closeCursor();
                } catch (\Throwable $t) {
                    $sqlDel = "DELETE FROM `projeto` WHERE `id_projeto` = :id_projeto AND `id_usuario` = :id_usuario";
                    $stmDel = $this->conn->prepare($sqlDel);
                    $stmDel->bindValue(':id_projeto', $idAntigo, PDO::PARAM_INT);
                    $stmDel->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
                    $stmDel->execute();
                }

                return $projetoAntigo;
            }
        }

        return null;
    }

    /**
     * Remove automaticamente projetos armazenados há mais de 60 dias (RN de retenção),
     * excluindo os arquivos físicos associados (.zip) e os registros do banco de dados.
     * Retorna a lista dos projetos removidos.
     */
    public function removerProjetosExpirados(int $dias = 60): array {
        if ($dias <= 0) {
            $dias = 60;
        }

        try {
            $sqlSelect = "SELECT `id_projeto`, `id_usuario`, `nome_projeto`, `caminho_armazenamento`, `fk_estilo`, `data_criacao` 
                          FROM `projeto` 
                          WHERE `data_criacao` < DATE_SUB(NOW(), INTERVAL :dias DAY)
                             OR (`prazo_de_vida` > 0 AND DATEDIFF(NOW(), `data_criacao`) >= `prazo_de_vida`)";
            $stmSelect = $this->conn->prepare($sqlSelect);
            $stmSelect->bindValue(':dias', $dias, PDO::PARAM_INT);
            $stmSelect->execute();
            $expirados = $stmSelect->fetchAll(PDO::FETCH_ASSOC);

            if (empty($expirados)) {
                return [];
            }

            $idsParaExcluir = [];
            $estilosParaExcluir = [];
            $dirDownloads = __DIR__ . '/../projetos/downloads';
            $dirPublicDownloads = __DIR__ . '/../../public/downloads';

            foreach ($expirados as $proj) {
                $id = (int)$proj['id_projeto'];
                $nome = trim((string)$proj['nome_projeto']);
                $caminho = trim((string)$proj['caminho_armazenamento']);
                $idsParaExcluir[] = $id;

                if (!empty($proj['fk_estilo'])) {
                    $estilosParaExcluir[] = (int)$proj['fk_estilo'];
                }

                // Remoção física de arquivos compactados .zip
                $candidatos = [];
                if ($nome !== '' && $id > 0) {
                    $candidatos[] = $dirDownloads . '/' . $nome . '-' . $id . '.zip';
                }
                if ($nome !== '') {
                    $candidatos[] = $dirDownloads . '/' . $nome . '.zip';
                    $candidatos[] = $dirPublicDownloads . '/' . $nome . '.zip';
                }
                if ($caminho !== '' && file_exists($caminho)) {
                    $candidatos[] = $caminho;
                }

                foreach ($candidatos as $arquivo) {
                    if (is_file($arquivo)) {
                        @unlink($arquivo);
                    }
                }

                if ($nome !== '') {
                    $limpo = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $nome);
                    foreach ([$dirDownloads, $dirPublicDownloads] as $d) {
                        if (is_dir($d)) {
                            $matches = glob($d . '/' . $limpo . '*.zip');
                            if ($matches) {
                                foreach ($matches as $m) {
                                    if (is_file($m)) @unlink($m);
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($idsParaExcluir)) {
                $placeholders = implode(',', array_fill(0, count($idsParaExcluir), '?'));
                $sqlDelete = "DELETE FROM `projeto` WHERE `id_projeto` IN ({$placeholders})";
                $stmDel = $this->conn->prepare($sqlDelete);
                $stmDel->execute($idsParaExcluir);
            }

            if (!empty($estilosParaExcluir)) {
                $inEstilos = implode(',', array_map('intval', array_unique($estilosParaExcluir)));
                $this->conn->exec("DELETE FROM `estilo` WHERE `id_estilo` IN ({$inEstilos})");
            }

            return $expirados;
        } catch (\Throwable $t) {
            return [];
        }
    }

    public function deleteByIdAndUsuario(int $idProjeto, int $idUsuario): bool {
        if ($idProjeto <= 0 || $idUsuario <= 0) {
            return false;
        }
        $sql = "DELETE FROM `projeto` WHERE `id_projeto` = :id_projeto AND `id_usuario` = :id_usuario";
        $stm = $this->conn->prepare($sql);
        $stm->bindValue(':id_projeto', $idProjeto, PDO::PARAM_INT);
        $stm->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        return $stm->execute();
    }

    public function deleteByNomeAndUsuario(string $nomeProjeto, int $idUsuario): bool {
        if ($nomeProjeto === '' || $idUsuario <= 0) {
            return false;
        }
        $sql = "DELETE FROM `projeto` WHERE `nome_projeto` = :nome_projeto AND `id_usuario` = :id_usuario";
        $stm = $this->conn->prepare($sql);
        $stm->bindValue(':nome_projeto', $nomeProjeto, PDO::PARAM_STR);
        $stm->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        return $stm->execute();
    }

    public function deleteAllByUsuario(int $idUsuario): bool {
        if ($idUsuario <= 0) {
            return false;
        }
        $sql = "DELETE FROM `projeto` WHERE `id_usuario` = :id_usuario";
        $stm = $this->conn->prepare($sql);
        $stm->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        return $stm->execute();
    }

    public function getById(int $id){
        $sql = "SELECT nome_projeto,comentarios,views FROM projeto WHERE id_projeto = ?;";
        $stm = $this->conn->prepare($sql);
        $stm->execute([$id]);

        return $stm->fetch();
    }

    

    private function projetoParams(Projeto $projeto){
        
        return [$projeto->getFk_usuario() ?? 1,
        $projeto->getFk_banco() ?? 1,
        $projeto->getFk_estilo() ?? null,
        $projeto->getNome_projeto() ?? '',
        $projeto->getData_criacao() ?? 'NOW',
        60,
        $projeto->getCaminho_armazenamento() ?? 'isso/Nem/Ta/Definido',
        $projeto->getComentarios() ? 1 : 0 ,
        $projeto->getViews() ? 1 : 0,
        null];
    }

    public function getDatabases($dsn,$user,$pass){
        $specialConn = ProjetoRepository::specialConn($dsn,$user,$pass);
        $sql = "SHOW DATABASES";
        $stm = $specialConn->prepare($sql);
        $stm->execute();
        $databases = $stm->fetchAll(PDO::FETCH_ASSOC);
        $aa = "";
        foreach($databases as $database){
            $aa .= "<option>". $database['Database'] ."</option>\n";
        }
        unset($specialConn);
        // print $aa;
        return $aa;
    }

    //CELSO REVISAR ISSO AQUI - FELIPE
    public function getTabelas($dsn, $user, $pass, $banco) {
        $dsnComBanco = "$dsn;dbname=$banco";
        $specialConn = self::specialConn($dsnComBanco, $user, $pass);
        $sql = "SHOW TABLES";
        $stm = $specialConn->prepare($sql);
        $stm->execute();
        $tabelas = $stm->fetchAll(PDO::FETCH_COLUMN);
        unset($specialConn);
        return $tabelas;
    }

    public function getColunas($dsn, $user, $pass, $banco, $tabela) {
        $dsnComBanco = "$dsn;dbname=$banco";
        $specialConn = self::specialConn($dsnComBanco, $user, $pass);
        $sql = "SHOW COLUMNS FROM `$tabela`";
        $stm = $specialConn->prepare($sql);
        $stm->execute();
        $colunas = $stm->fetchAll(PDO::FETCH_ASSOC);
        unset($specialConn);
        return $colunas;
    }

    private static function specialConn($dsn,$user,$pass){
        // print $dsn;
        $connection = new PDO($dsn, $user, $pass);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $connection;
    }
}