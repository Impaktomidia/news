<?php

class PontoModel {
    private $pdo;

    public function __construct() {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erro conexão DB: " . $e->getMessage());
        }
    }

    /**
     * Lista pontos com filtros e paginação (limit/offset)
     */
    public function listarPaginado(array $filtros = [], int $pagina = 1, int $limite = 5): array {
        $offset = ($pagina - 1) * $limite;

        $sql = "SELECT * FROM pontos WHERE 1=1";
        $params = $this->montarFiltros($sql, $filtros);

        $sql .= " ORDER BY id DESC LIMIT :limite OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        // Bind params dinâmicos
        foreach ($params as $chave => $valor) {
            $stmt->bindValue(":$chave", $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta total de registros com os mesmos filtros
     */
    public function contar(array $filtros = []): int {
        $sql = "SELECT COUNT(*) as total FROM pontos WHERE 1=1";
        $params = $this->montarFiltros($sql, $filtros);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($resultado['total'] ?? 0);
    }

    /**
     * Função auxiliar para adicionar filtros ao SQL e retornar os parâmetros
     */
    private function montarFiltros(&$sql, array $filtros): array {
        $params = [];

        if (!empty($filtros['situacao'])) {
            $sql .= " AND situacao = :situacao";
            $params['situacao'] = $filtros['situacao'];
        }

        if (!empty($filtros['regiao'])) {
            $sql .= " AND regiao = :regiao";
            $params['regiao'] = $filtros['regiao'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND tipo = :tipo";
            $params['tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['cidade'])) {
            $sql .= " AND cidade = :cidade";
            $params['cidade'] = $filtros['cidade'];
        }

        if (!empty($filtros['busca'])) {
            // Busca em 'descricao' e 'cliente' com LIKE
            $sql .= " AND (descricao LIKE :busca OR cliente LIKE :busca)";
            $params['busca'] = '%' . $filtros['busca'] . '%';
        }

        return $params;
    }

    public function buscarPorNumeros($numeros) {
    if (empty($numeros)) return [];

    $placeholders = implode(',', array_fill(0, count($numeros), '?'));
    $sql = "SELECT * FROM pontos WHERE numero IN ($placeholders)";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($numeros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


}
