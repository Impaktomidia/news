<?php
class PontoModelOptimized extends BaseModel {
    protected $table = 'pontos';
    protected $cache;
    protected $logger;
    
    public function __construct() {
        parent::__construct();
        $this->cache = new CacheService();
        $this->logger = new LogService();
    }
    
    public function listarPaginadoComCache(array $filtros = [], int $pagina = 1, int $limite = 10) {
        $cacheKey = 'pontos_' . md5(serialize($filtros) . $pagina . $limite);
        
        return $this->cache->remember($cacheKey, function() use ($filtros, $pagina, $limite) {
            return $this->listarPaginado($filtros, $pagina, $limite);
        }, 300); // Cache por 5 minutos
    }
    
    public function obterClientesAtivosComCache() {
        return $this->cache->remember('clientes_ativos', function() {
            return $this->obterClientesAtivos();
        }, 1800); // Cache por 30 minutos
    }
    
    public function obterEstatisticasComCache() {
        return $this->cache->remember('estatisticas_dashboard', function() {
            return $this->obterEstatisticas();
        }, 600); // Cache por 10 minutos
    }
    
    // Método otimizado com índices
    public function buscarPorTexto($texto, $limite = 10) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE ativo = 1 
                AND (MATCH(logradouro, descricao, cliente) AGAINST(:texto IN BOOLEAN MODE)
                     OR numero LIKE :numero)
                ORDER BY 
                    CASE 
                        WHEN numero = :texto_exato THEN 1
                        WHEN numero LIKE :numero_like THEN 2
                        ELSE 3
                    END,
                    MATCH(logradouro, descricao, cliente) AGAINST(:texto IN BOOLEAN MODE) DESC
                LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':texto', $texto);
        $stmt->bindValue(':numero', "%$texto%");
        $stmt->bindValue(':texto_exato', $texto);
        $stmt->bindValue(':numero_like', "$texto%");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Invalidar cache quando dados são alterados
    public function create(array $data) {
        $result = parent::create($data);
        $this->invalidateCache();
        return $result;
    }
    
    public function update($id, array $data) {
        $result = parent::update($id, $data);
        $this->invalidateCache();
        return $result;
    }
    
    private function invalidateCache() {
        $this->cache->delete('clientes_ativos');
        $this->cache->delete('estatisticas_dashboard');
        
        // Invalidar caches de listagem (aproximação)
        $this->cache->flush();
    }
}