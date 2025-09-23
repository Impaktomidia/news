<?php
// No topo do app/Controller/PontoController.php, adicionar:
require_once __DIR__ . '/../../config/security.php';

class PontoController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDatabase(); // Usar nova função
    }

    public function listar(array $filtros, int $paginaAtual, int $limite)
    {
        // Validar e sanitizar filtros
        $filtrosSeguros = [];
        foreach ($filtros as $key => $value) {
            if (!empty($value)) {
                $filtrosSeguros[$key] = sanitizeString($value);
            }
        }
        
        // Validar paginação
        $paginaAtual = validateId($paginaAtual) ?: 1;
        $limite = validateId($limite) ?: 10;
        $limite = min($limite, 100); // Máximo 100 por página
        
        $offset = ($paginaAtual - 1) * $limite;
        $sql = "SELECT * FROM pontos WHERE ativo = 1";

        $params = [];

        // Usar filtros sanitizados
        if (!empty($filtrosSeguros['situacao'])) {
            $sql .= " AND situacao = :situacao";
            $params[':situacao'] = $filtrosSeguros['situacao'];
        }

        if (!empty($filtrosSeguros['regiao'])) {
            $sql .= " AND regiao = :regiao";
            $params[':regiao'] = $filtrosSeguros['regiao'];
        }

        if (!empty($filtrosSeguros['tipo'])) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $filtrosSeguros['tipo'];
        }

        if (!empty($filtrosSeguros['cidade'])) {
            $sql .= " AND cidade = :cidade";
            $params[':cidade'] = $filtrosSeguros['cidade'];
        }

        // Busca otimizada
        if (!empty($filtrosSeguros['busca'])) {
            $sql .= " AND (logradouro LIKE :busca OR descricao LIKE :busca OR cliente LIKE :busca OR numero LIKE :busca)";
            $params[':busca'] = '%' . $filtrosSeguros['busca'] . '%';
        }

        $sql .= " ORDER BY 
            CASE 
                WHEN fim_contrato IS NULL OR fim_contrato = '0000-00-00' OR fim_contrato = '' THEN 1
                ELSE 0 
            END,
            fim_contrato ASC 
            LIMIT :offset, :limite";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Conta total otimizada
            $sqlCount = "SELECT COUNT(*) FROM pontos WHERE ativo = 1";
            $paramsCount = [];
            
            // Repetir condições do filtro para contagem
            if (!empty($filtrosSeguros['situacao'])) {
                $sqlCount .= " AND situacao = :situacao";
                $paramsCount[':situacao'] = $filtrosSeguros['situacao'];
            }
            
            if (!empty($filtrosSeguros['regiao'])) {
                $sqlCount .= " AND regiao = :regiao";
                $paramsCount[':regiao'] = $filtrosSeguros['regiao'];
            }
            
            if (!empty($filtrosSeguros['tipo'])) {
                $sqlCount .= " AND tipo = :tipo";
                $paramsCount[':tipo'] = $filtrosSeguros['tipo'];
            }
            
            if (!empty($filtrosSeguros['cidade'])) {
                $sqlCount .= " AND cidade = :cidade";
                $paramsCount[':cidade'] = $filtrosSeguros['cidade'];
            }
            
            if (!empty($filtrosSeguros['busca'])) {
                $sqlCount .= " AND (logradouro LIKE :busca OR descricao LIKE :busca OR cliente LIKE :busca OR numero LIKE :busca)";
                $paramsCount[':busca'] = '%' . $filtrosSeguros['busca'] . '%';
            }

            $stmtCount = $this->pdo->prepare($sqlCount);
            $stmtCount->execute($paramsCount);
            $total = $stmtCount->fetchColumn();

            return [
                'pontos' => $pontos,
                'total' => $total,
                'limite' => $limite,
                'pagina' => $paginaAtual
            ];
            
        } catch (PDOException $e) {
            error_log("Erro na consulta de pontos: " . $e->getMessage());
            throw new Exception("Erro ao buscar pontos");
        }
    }

    public function buscarPorId(int $id)
    {
        $id = validateId($id);
        if (!$id) {
            return false;
        }

        $sql = "SELECT * FROM pontos WHERE id = :id AND ativo = 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar ponto ID $id: " . $e->getMessage());
            return false;
        }
    }
}

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
