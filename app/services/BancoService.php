<?php
namespace app\services;

use app\database\ConnectionFactory;
use app\repositories\BancoRepository;

class BancoService{
    private ?BancoRepository $banco_repository = null;

    public function __construct(){
    }

    private function repo(): BancoRepository {
        if ($this->banco_repository === null) {
            $this->banco_repository = new BancoRepository();
        }
        return $this->banco_repository;
    }

    /**
     * Conecta à instância MySQL local da máquina do usuário (SHOW DATABASES via PDO)
     * e retorna a lista de esquemas disponíveis filtrando bancos internos do SGBD.
     * Não exige autenticação nem persiste dados no DevStudio.
     */
    public static function listarBancosMysqlLocal(
        string $host = 'localhost',
        string $porta = '3306',
        string $user = 'root',
        string $pass = ''
    ): array {
        $host  = self::validarHostMysqlLocal($host);
        $porta = trim($porta) !== '' ? trim($porta) : '3306';
        self::validarPortaMysqlLocal($porta);
        $user  = trim($user) !== '' ? trim($user) : (defined('DB_USER') ? DB_USER : 'root');

        $dsn = "mysql:host={$host};port={$porta}";
        $pdo = ConnectionFactory::specialConn($dsn, $user, $pass);

        $stm = $pdo->query("SHOW DATABASES");
        $databases = $stm->fetchAll(\PDO::FETCH_COLUMN);

        $esquemasSistema = ['information_schema', 'performance_schema', 'mysql', 'sys'];
        return array_values(array_filter($databases, function ($db) use ($esquemasSistema) {
            return !in_array(strtolower((string) $db), $esquemasSistema, true);
        }));
    }

    /**
     * Resolve o host para onde o MySQL deve se conectar.
     * Se o usuário estiver acessando de outra máquina na rede local e o host informado
     * for vazio, 'localhost', '127.0.0.1' ou '::1', redireciona para a máquina do cliente (REMOTE_ADDR),
     * garantindo que os bancos sejam buscados da máquina do desenvolvedor e não do host servidor.
     */
    public static function resolverHostCliente(?string $host = null): string {
        $host = trim((string) $host);
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $isLoopback = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);

        if (!$isLoopback && ($host === '' || in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true))) {
            return $clientIp;
        }

        return $host !== '' ? $host : 'localhost';
    }

    public static function validarHostMysqlLocal(string $host): string {
        $hostResolvido = self::resolverHostCliente($host);
        $hostLower = strtolower($hostResolvido);

        // Permite loopback, IPs válidos (IPv4 / IPv6) e hostnames locais válidos
        if (in_array($hostLower, ['localhost', '127.0.0.1', '::1'], true)
            || filter_var($hostResolvido, FILTER_VALIDATE_IP)
            || preg_match('/^[a-zA-Z0-9_\-\.]+$/', $hostResolvido)
        ) {
            return $hostResolvido;
        }

        throw new \InvalidArgumentException('Informe um endereço de host válido para o MySQL.');
    }

    public static function validarPortaMysqlLocal(string $porta): string {
        if (!ctype_digit($porta) || (int) $porta < 1 || (int) $porta > 65535) {
            throw new \InvalidArgumentException('Informe uma porta MySQL válida.');
        }

        return $porta;
    }



    public function insert($fk_usuario, $nome_banco, $usuario_banco, $senha_banco, $host, $porta){
        // Se este usuário específico já possui este banco cadastrado, recupera o registro existente
        $bancoExistente = $this->getBancoEspecifico($nome_banco, $usuario_banco, $fk_usuario);
        if ($bancoExistente) {
            return $bancoExistente['id_banco'] ?? true;
        }

        return $this->repo()->insert($fk_usuario, $nome_banco, $usuario_banco, $senha_banco, $host, $porta);
    }

    public function getBancoById($value){
        $result = $this->repo()->getBancoById($value);

        return $result;
    }
    public function getBanco($value,$param = 'id_banco'){
        $result = $this->repo()->getBanco($value,$param);

        return $result;
    }

    public function getBancoEspecifico($nome_banco,$usuario_banco,$fk_usuario){
        $result = $this->repo()->getBancoEspecifico($nome_banco,$usuario_banco,$fk_usuario);

        return $result;
    }

    public function getBancoByUsuario($fk_usuario,$opt = "",$sel = null){
        $result = $this->repo()->getBancoByUsuario($fk_usuario,$opt,$sel);

        return $result;
    }

    public function getAllBancos(){
        $result = $this->repo()->getAllBancos();

        return $result;
    }

    public function updateConfig($id_banco, $nome_banco, $usuario_banco, $senha_banco, $host, $porta){
        return $this->repo()->updateConfig($id_banco, $nome_banco, $usuario_banco, $senha_banco, $host, $porta);
    }

    public function delete($id_banco){
        return $this->repo()->delete($id_banco);
    }
}