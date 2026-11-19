<?php
namespace app\services;

use app\models\Projeto;
use app\repositories\ProjetoRepository;

class ProjetoService{
    private ProjetoRepository $projetoRepository;


    public function __construct(){
        $this->projetoRepository = new ProjetoRepository();
    }


    public function getDatabases($dsn,$user,$pass){
        return $this->projetoRepository->getDatabases($dsn,$user,$pass);
    }

    public function getTabelas($dsn, $user, $pass, $banco) {
        return $this->projetoRepository->getTabelas($dsn, $user, $pass, $banco);
    }

    public function getColunas($dsn, $user, $pass, $banco, $tabela) {
        return $this->projetoRepository->getColunas($dsn, $user, $pass, $banco, $tabela);
    }

    public function getById(int $id){   
        return $this->projetoRepository->getById($id);
    }

    public function insert(Projeto $projeto){
        $idUsuario = (int) $projeto->getFk_usuario();
        if ($idUsuario > 0) {
            // Caso o usuário alcance o limite de 10 projetos, apaga o mais antigo do banco e remove o ZIP físico
            $antigo = $this->projetoRepository->apagarProjetoMaisAntigoSeLimite($idUsuario, 10);
            if ($antigo) {
                $this->removerArquivoZipProjeto($antigo['nome_projeto'] ?? '', (int) ($antigo['id_projeto'] ?? 0));
            }
        }

        $idInserido = $this->projetoRepository->insert($projeto);
        if ($idInserido && $idUsuario > 0) {
            $this->projetoRepository->limitarCotaPorUsuario($idUsuario, 10);
        }
        return $idInserido;
    }

    public function deleteByIdAndUsuario(int $idProjeto, int $idUsuario): bool {
        return $this->projetoRepository->deleteByIdAndUsuario($idProjeto, $idUsuario);
    }

    public function deleteByNomeAndUsuario(string $nomeProjeto, int $idUsuario): bool {
        return $this->projetoRepository->deleteByNomeAndUsuario($nomeProjeto, $idUsuario);
    }

    public function deleteAllByUsuario(int $idUsuario): bool {
        return $this->projetoRepository->deleteAllByUsuario($idUsuario);
    }

    public function contarProjetosMvc(int $idUsuario): int {
        return $this->projetoRepository->contarProjetosMvc($idUsuario);
    }

    public function contarProjetosPageMaker(int $idUsuario): int {
        return $this->projetoRepository->contarProjetosPageMaker($idUsuario);
    }

    public function apagarMaisAntigoMvcSeLimite(int $idUsuario, int $limite = 10): ?array {
        return $this->projetoRepository->apagarMaisAntigoMvcSeLimite($idUsuario, $limite);
    }

    public function apagarMaisAntigoPageMakerSeLimite(int $idUsuario, int $limite = 10): ?array {
        return $this->projetoRepository->apagarMaisAntigoPageMakerSeLimite($idUsuario, $limite);
    }

    public function apagarProjetoMaisAntigoSeLimite(int $idUsuario, int $limite = 10): ?array {
        return $this->projetoRepository->apagarProjetoMaisAntigoSeLimite($idUsuario, $limite);
    }

    public function removerProjetosExpirados(int $dias = 60): array {
        return $this->projetoRepository->removerProjetosExpirados($dias);
    }

    private function removerArquivoZipProjeto(string $nomeProjeto, int $idProjeto): void {
        $dirDownloads = __DIR__ . '/../projetos/downloads';
        if (!is_dir($dirDownloads)) {
            return;
        }

        $candidatos = [];
        if ($nomeProjeto !== '' && $idProjeto > 0) {
            $candidatos[] = $dirDownloads . '/' . $nomeProjeto . '-' . $idProjeto . '.zip';
        }
        if ($nomeProjeto !== '') {
            $candidatos[] = $dirDownloads . '/' . $nomeProjeto . '.zip';
        }

        foreach ($candidatos as $arq) {
            if (is_file($arq)) {
                @unlink($arq);
            }
        }
    }


}



