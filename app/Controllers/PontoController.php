<?php

class PontoController
{
    private $pdo;

    public function __construct()
    {
        // Carrega as configurações do banco
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erro ao conectar ao banco de dados: " . $e->getMessage());
        }
    }

    public function listar(array $filtros, int $paginaAtual, int $limite)
    {
        $offset = ($paginaAtual - 1) * $limite;
        $sql = "SELECT * FROM pontos WHERE ativo = 1"; // Só ativos

        $params = [];

        if (!empty($filtros['situacao'])) {
            $sql .= " AND situacao = :situacao";
            $params[':situacao'] = $filtros['situacao'];
        }

        if (!empty($filtros['regiao'])) {
            $sql .= " AND regiao = :regiao";
            $params[':regiao'] = $filtros['regiao'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['cidade'])) {
            $sql .= " AND cidade = :cidade";
            $params[':cidade'] = $filtros['cidade'];
        }

        $sql .= " LIMIT :offset, :limite";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);

        $stmt->execute();
        $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Conta total para paginação
        $sqlCount = "SELECT COUNT(*) FROM pontos WHERE ativo = 1";
        $stmtCount = $this->pdo->query($sqlCount);
        $total = $stmtCount->fetchColumn();

        return [
            'pontos' => $pontos,
            'total' => $total,
            'limite' => $limite,
            'pagina' => $paginaAtual
        ];
    }

    public function buscarPorId(int $id)
    {
        $sql = "SELECT * FROM pontos WHERE id = :id AND ativo = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
