<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../public/index.php?erro=nao_logado");
    exit;
}

// Configuração do banco
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ipk2024;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET sql_mode = ''"); // Modo permissivo
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Parâmetros de filtro e paginação
$busca = $_GET['busca'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$regiao = $_GET['regiao'] ?? '';
$situacao = $_GET['situacao'] ?? '';
$cliente = $_GET['cliente'] ?? '';
$pagina = (int)($_GET['pagina'] ?? 1);
$itensPorPagina = 12;
$offset = ($pagina - 1) * $itensPorPagina;

// Buscar clientes para filtro
$sqlClientes = "SELECT DISTINCT cliente FROM pontos WHERE cliente IS NOT NULL AND cliente != '' ORDER BY cliente";
$stmtClientes = $pdo->prepare($sqlClientes);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_COLUMN);

// Construir filtros
$where = [];
$params = [];

if ($busca) {
    $where[] = "(numero LIKE :busca OR logradouro LIKE :busca OR cliente LIKE :busca OR descricao LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

if ($tipo) {
    $where[] = "tipo = :tipo";
    $params[':tipo'] = $tipo;
}

if ($regiao) {
    $where[] = "regiao = :regiao";
    $params[':regiao'] = $regiao;
}

if ($situacao) {
    $where[] = "situacao = :situacao";
    $params[':situacao'] = $situacao;
}

if ($cliente) {
    $where[] = "cliente = :cliente";
    $params[':cliente'] = $cliente;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Contar total
$sqlCount = "SELECT COUNT(*) FROM pontos $whereSql";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPaginas = ceil($total / $itensPorPagina);

// Buscar pontos
$sql = "SELECT numero, logradouro, descricao, cidade, regiao, cliente, agencia, tipo, situacao,
               CASE 
                 WHEN fim_contrato IS NULL OR fim_contrato = '0000-00-00' OR fim_contrato = '' 
                 THEN NULL
                 ELSE DATE(fim_contrato)
               END as fim_contrato_clean
        FROM pontos $whereSql 
        ORDER BY 
            CASE WHEN numero REGEXP '^[0-9]+$' THEN CAST(numero AS UNSIGNED) ELSE 999999 END,
            numero ASC 
        LIMIT $itensPorPagina OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pontos = $stmt->fetchAll();
} catch (PDOException $e) {
    // Log do erro para debug
    error_log("Erro na consulta de pontos: " . $e->getMessage());
    
    // Fallback: buscar sem formatação de data
    $sqlFallback = "SELECT numero, logradouro, descricao, cidade, regiao, cliente, agencia, tipo, situacao,
                           fim_contrato as fim_contrato_clean
                    FROM pontos $whereSql 
                    ORDER BY numero ASC 
                    LIMIT $itensPorPagina OFFSET $offset";
    
    $stmt = $pdo->prepare($sqlFallback);
    $stmt->execute($params);
    $pontos = $stmt->fetchAll();
}


// Funções auxiliares seguras
function formatarData($data) {
    if (!$data || $data === '0000-00-00') {
        return '<span style="color: #6c757d;">-</span>';
    }
    try {
        $date = new DateTime($data);
        return '<span style="color: #495057;">' . $date->format('M/Y') . '</span>';
    } catch (Exception $e) {
        return '<span style="color: #dc3545;">Inválida</span>';
    }
}

function calcularStatus($data) {
    if (!$data || $data === '0000-00-00') {
        return '<small style="color: #6c757d;">Sem prazo</small>';
    }
    try {
        $fim = new DateTime($data);
        $hoje = new DateTime();
        $diff = $hoje->diff($fim);
        $meses = ($diff->y * 12) + $diff->m;
        
        if ($fim < $hoje) {
            return '<small style="color: #dc3545; font-weight: bold;">Vencido</small>';
        } elseif ($meses <= 1) {
            return '<small style="color: #fd7e14; font-weight: bold;">Urgente</small>';
        } elseif ($meses <= 3) {
            return '<small style="color: #ffc107;">Atenção</small>';
        } else {
            return '<small style="color: #198754;">OK (' . $meses . 'm)</small>';
        }
    } catch (Exception $e) {
        return '<small style="color: #dc3545;">Erro</small>';
    }
}

function badgeSituacao($situacao) {
    $cores = [
        'Disponível' => ['bg' => '#198754', 'text' => 'white'],
        'Ocupado' => ['bg' => '#dc3545', 'text' => 'white'],
        'Reservado' => ['bg' => '#fd7e14', 'text' => 'white'],
        'Vencido' => ['bg' => '#6f42c1', 'text' => 'white'],
        'Permuta' => ['bg' => '#0dcaf0', 'text' => 'black'],
    ];
    
    $cor = $cores[$situacao] ?? ['bg' => '#6c757d', 'text' => 'white'];
    return "<span style='background: {$cor['bg']}; color: {$cor['text']}; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 500;'>$situacao</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Pontos - Impakto</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #f8f9fa; 
            line-height: 1.6;
            color: #495057;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }
        
        .logo h1 { 
            color: white; 
            font-size: 1.8rem; 
            font-weight: 300;
        }
        .logo .red { color: #ffeb3b; font-weight: 700; }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .user-info {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            text-decoration: none;
            margin-left: 1rem;
            font-weight: 600;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .controls {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .busca-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        
        .busca-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .busca-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-buscar {
            background: #667eea;
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-buscar:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .filtro-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
        }
        
        .filtro-group select {
            width: 100%;
            padding: 0.7rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }
        
        .filtro-group select:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            padding: 0.7rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-reset:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        .stats {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stats-text {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.9rem;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .numero-col {
            font-weight: 700;
            color: #495057;
            min-width: 80px;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .paginacao {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .paginacao a {
            padding: 0.8rem 1.2rem;
            background: white;
            border: 2px solid #e9ecef;
            text-decoration: none;
            color: #495057;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .paginacao a:hover {
            background: #f8f9fa;
            border-color: #667eea;
            transform: translateY(-1px);
        }
        
        .paginacao a.ativo {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 0 1rem;
            }
            
            .busca-row {
                flex-direction: column;
            }
            
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                min-width: 800px;
            }
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo">
            <h1>impa<span class="red">k</span>to</h1>
        </div>
        
   <div class="nav-links">
    <a href="/impaktonew/gestor/index.php">Dashboard</a>
    <a href="#" class="active">Lista de Pontos</a>
    <a href="/impaktonew/app/Views/gestor/relatorios/pre_selecao.php">Pré-Seleção</a>
    </div>

        
        <div class="user-info">
            Olá, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong>
            <a href="/impaktonew/logout.php" class="btn-logout">Sair</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="controls">
        <form method="get" class="busca-row">
            <input type="text" name="busca" class="busca-input" 
                   placeholder="Buscar por número, logradouro, cliente ou descrição..." 
                   value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" class="btn-buscar">Buscar</button>
        </form>
        
        <form method="get" class="filtros-grid">
            <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
            
            <div class="filtro-group">
                <label>Tipo</label>
                <select name="tipo" onchange="this.form.submit()">
                    <option value="">Todos os tipos</option>
                    <option value="Outdoor" <?= $tipo === 'Outdoor' ? 'selected' : '' ?>>Outdoor</option>
                    <option value="Painel" <?= $tipo === 'Painel' ? 'selected' : '' ?>>Painel</option>
                </select>
            </div>
            
            <div class="filtro-group">
                <label>Região</label>
                <select name="regiao" onchange="this.form.submit()">
                    <option value="">Todas as regiões</option>
                    <option value="Metropolitana" <?= $regiao === 'Metropolitana' ? 'selected' : '' ?>>Metropolitana</option>
                    <option value="Agreste" <?= $regiao === 'Agreste' ? 'selected' : '' ?>>Agreste</option>
                    <option value="Mata Norte" <?= $regiao === 'Mata Norte' ? 'selected' : '' ?>>Mata Norte</option>
                    <option value="Sertão" <?= $regiao === 'Sertão' ? 'selected' : '' ?>>Sertão</option>
                </select>
            </div>
            
            <div class="filtro-group">
                <label>Situação</label>
                <select name="situacao" onchange="this.form.submit()">
                    <option value="">Todas as situações</option>
                    <option value="Disponível" <?= $situacao === 'Disponível' ? 'selected' : '' ?>>Disponível</option>
                    <option value="Ocupado" <?= $situacao === 'Ocupado' ? 'selected' : '' ?>>Ocupado</option>
                    <option value="Reservado" <?= $situacao === 'Reservado' ? 'selected' : '' ?>>Reservado</option>
                    <option value="Vencido" <?= $situacao === 'Vencido' ? 'selected' : '' ?>>Vencido</option>
                </select>
            </div>
            
            <div class="filtro-group">
                <label>Cliente</label>
                <select name="cliente" onchange="this.form.submit()">
                    <option value="">Todos os clientes</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?= htmlspecialchars($cli) ?>" <?= $cliente === $cli ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cli) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <a href="?" class="btn-reset">Limpar Filtros</a>
        </form>
    </div>
    
    <div class="stats">
        <div class="stats-number"><?= number_format($total) ?></div>
        <div class="stats-text">
            <?= $total === 1 ? 'ponto encontrado' : 'pontos encontrados' ?>
            <?= $busca ? " para \"$busca\"" : '' ?>
        </div>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Logradouro</th>
                    <th>Cidade</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Situação</th>
                    <th>Vencimento</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pontos)): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <div>Nenhum ponto encontrado com os filtros selecionados</div>
                        <div style="margin-top: 0.5rem; font-size: 0.8rem;">
                            Tente ajustar os filtros ou fazer uma nova busca
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($pontos as $ponto): ?>
                    <tr>
                        <td class="numero-col"><?= htmlspecialchars($ponto['numero'] ?? '-') ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($ponto['logradouro'] ?? '-') ?></div>
                            <?php if ($ponto['descricao']): ?>
                                <div style="font-size: 0.8rem; color: #6c757d; margin-top: 2px;">
                                    <?= htmlspecialchars(substr($ponto['descricao'], 0, 60)) ?><?= strlen($ponto['descricao']) > 60 ? '...' : '' ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($ponto['cidade'] ?? '-') ?></div>
                            <?php if ($ponto['regiao']): ?>
                                <div style="font-size: 0.8rem; color: #6c757d;"><?= htmlspecialchars($ponto['regiao']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($ponto['cliente'] ?? '-') ?></div>
                            <?php if ($ponto['agencia']): ?>
                                <div style="font-size: 0.8rem; color: #6c757d;"><?= htmlspecialchars($ponto['agencia']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ponto['tipo'] ?? '-') ?></td>
                        <td><?= badgeSituacao($ponto['situacao'] ?? 'N/A') ?></td>
                        <td>
                            <?= formatarData($ponto['fim_contrato_clean']) ?><br>
                            <?= calcularStatus($ponto['fim_contrato_clean']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPaginas > 1): ?>
    <div class="paginacao">
        <?php if ($pagina > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">« Anterior</a>
        <?php endif; ?>
        
        <?php
        $inicio = max(1, $pagina - 2);
        $fim = min($totalPaginas, $pagina + 2);
        
        for ($i = $inicio; $i <= $fim; $i++):
        ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"
               class="<?= $i == $pagina ? 'ativo' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($pagina < $totalPaginas): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">Próximo »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus na busca
    const buscaInput = document.querySelector('.busca-input');
    if (buscaInput && !buscaInput.value) {
        buscaInput.focus();
    }
    
    // Loading nos selects
    const selects = document.querySelectorAll('select[onchange]');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            document.body.classList.add('loading');
        });
    });
    
    // Remover loading após carregar
    window.addEventListener('pageshow', function() {
        document.body.classList.remove('loading');
    });
});
</script>

</body>
</html>