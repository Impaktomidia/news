<?php

/**
 * Classe base para todos os Models
 * Centraliza conexão PDO e métodos comuns
 */
abstract class BaseModel
{
    protected $pdo;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    
    public function __construct()
    {
        $this->connect();
    }
    
    /**
     * Estabelece conexão com banco de dados
     */
    private function connect(): void
    {
        static $pdo = null;
        
        if ($pdo === null) {
            $config = require __DIR__ . '/../../config/database.php';
            
            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
            
            try {
                $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                error_log("Erro de conexão: " . $e->getMessage());
                throw new Exception("Falha na conexão com banco de dados");
            }
        }
        
        $this->pdo = $pdo;
    }
    
    /**
     * Buscar todos os registros com filtros e paginação
     */
    public function findAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM {$this->table}";
        $whereClause = $this->buildWhereClause($filters);
        
        if ($whereClause['sql']) {
            $sql .= " WHERE " . $whereClause['sql'];
        }
        
        $sql .= " ORDER BY {$this->primaryKey} DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Bind filtros
        foreach ($whereClause['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind paginação
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Contar registros com filtros
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $whereClause = $this->buildWhereClause($filters);
        
        if ($whereClause['sql']) {
            $sql .= " WHERE " . $whereClause['sql'];
        }
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($whereClause['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return (int) ($result['total'] ?? 0);
    }
    
    /**
     * Buscar por ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Criar novo registro
     */
    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        
        if (empty($data)) {
            throw new InvalidArgumentException("Nenhum dado válido fornecido");
        }
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Atualizar registro
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        
        if (empty($data)) {
            return false;
        }
        
        $setPairs = [];
        foreach (array_keys($data) as $key) {
            $setPairs[] = "{$key} = :{$key}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setPairs) . 
               " WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Deletar registro (soft delete se houver campo 'ativo')
     */
    public function delete(int $id): bool
    {
        // Verifica se a tabela tem campo 'ativo' para soft delete
        if ($this->hasColumn('ativo')) {
            return $this->update($id, ['ativo' => 0]);
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Executar query customizada
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Construir cláusula WHERE dinâmica
     */
    protected function buildWhereClause(array $filters): array
    {
        $conditions = [];
        $params = [];
        
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                // Busca com LIKE para campos de texto
                if (in_array($field, $this->getSearchableFields())) {
                    $conditions[] = "{$field} LIKE :{$field}";
                    $params[":{$field}"] = "%{$value}%";
                } else {
                    $conditions[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $value;
                }
            }
        }
        
        return [
            'sql' => implode(' AND ', $conditions),
            'params' => $params
        ];
    }
    
    /**
     * Filtrar dados apenas para campos permitidos
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Verificar se tabela tem uma coluna específica
     */
    private function hasColumn(string $column): bool
    {
        static $cache = [];
        
        $cacheKey = "{$this->table}.{$column}";
        
        if (!isset($cache[$cacheKey])) {
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE :column";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':column', $column);
            $stmt->execute();
            
            $cache[$cacheKey] = $stmt->rowCount() > 0;
        }
        
        return $cache[$cacheKey];
    }
    
    /**
     * Campos que permitem busca com LIKE
     * Sobrescrever nas classes filhas conforme necessário
     */
    protected function getSearchableFields(): array
    {
        return ['descricao', 'logradouro', 'cliente', 'agencia'];
    }
    
    /**
     * Iniciar transação
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }
    
    /**
     * Confirmar transação
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }
    
    /**
     * Reverter transação
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
    
    /**
     * Verificar se está em transação
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}