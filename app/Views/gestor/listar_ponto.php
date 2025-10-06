<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../public/index.php?erro=nao_logado");
    exit;
}

$paginaAtual = 'pontos';

// Conectar ao banco
try {
    require_once __DIR__ . '/../../../config/database.php';
    $pdo = getDatabase();
} catch (Exception $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Parâmetros de filtro e paginação
$busca = $_GET['busca'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$regiao = $_GET['regiao'] ?? '';
$situacao = $_GET['situacao'] ?? '';
$cliente = $_GET['cliente'] ?? '';
$pagina = (int)($_GET['pagina'] ?? 1);
$itensPorPagina = 5;
$offset = ($pagina - 1) * $itensPorPagina;
$status = $_GET['status'] ?? 'ativo';

// Buscar clientes para filtro
$sqlClientes = "SELECT DISTINCT cliente FROM pontos WHERE cliente IS NOT NULL AND cliente != '' ORDER BY cliente";
$stmtClientes = $pdo->prepare($sqlClientes);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_COLUMN);

// Construir filtros
$where = [];
$params = [];

if ($status === 'ativo') {
    $where[] = "(ativo = 1 OR ativo IS NULL)";
} elseif ($status === 'inativo') {
    $where[] = "ativo = 0";
}

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
    error_log("Erro na consulta de pontos: " . $e->getMessage());
    
    $sqlFallback = "SELECT numero, logradouro, descricao, cidade, regiao, cliente, agencia, tipo, situacao,
                           fim_contrato as fim_contrato_clean
                      FROM pontos $whereSql 
                      ORDER BY numero ASC 
                      LIMIT $itensPorPagina OFFSET $offset";
    
    $stmt = $pdo->prepare($sqlFallback);
    $stmt->execute($params);
    $pontos = $stmt->fetchAll();
}

function formatarData($data) {
    if (!$data || $data === '0000-00-00') {
        return '<span>-</span>';
    }
    try {
        $date = new DateTime($data);
        $mesIngles = $date->format('M');
        $ano = $date->format('Y');
        $mesPortugues = traduzirMes($mesIngles);
        return '<span>' . $mesPortugues . '/' . $ano . '</span>';
    } catch (Exception $e) {
        return '<span class="text-danger">Inválida</span>';
    }
}

function calcularStatus($data) {
    if (!$data || $data === '0000-00-00') {
        return '<small class="text-muted">Sem prazo</small>';
    }
    try {
        $fim = new DateTime($data);
        $hoje = new DateTime();
        $diff = $hoje->diff($fim);
        $meses = ($diff->y * 12) + $diff->m;
        
        if ($fim < $hoje) {
            return '<small class="badge-danger">Vencido</small>';
        } elseif ($meses <= 1) {
            return '<small class="badge-warning">Urgente</small>';
        } elseif ($meses <= 3) {
            return '<small class="badge-info">Atenção</small>';
        } else {
            return '<small class="badge-success">OK (' . $meses . 'm)</small>';
        }
    } catch (Exception $e) {
        return '<small class="badge-danger">Erro</small>';
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

function traduzirMes($mes) {
    $meses = [
        'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 'Apr' => 'Abr',
        'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
        'Sep' => 'Set', 'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
    ];
    return $meses[$mes] ?? $mes;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/impaktonew/public/img/favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="/impaktonew/public/assets/css/gestor.css"> 
    <title>Lista de Pontos - Impakto Mídia</title>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo">
            <img src="/impaktonew/public/assets/img/logo.png" alt="Impakto Mídia" class="logo-img">
        </div>

        <form method="get" class="busca-row">
            <span class="busca-icon">🔍</span>
            <input type="text" name="busca" class="busca-input" 
                   placeholder="Buscar por número, logradouro, cliente ou descrição..." 
                   value="<?= htmlspecialchars($busca) ?>">
        </form>
        
        <nav class="main-nav">
            <a href="/impaktonew/gestor/index.php" class="nav-link">Dashboard</a>
            <a href="/impaktonew/app/Views/gestor/listar_ponto.php" class="nav-link active">Pontos</a>
            <a href="/impaktonew/app/Views/gestor/relatorios/pre_selecao.php" class="nav-link">Pré-Seleção</a>
            <a href="/impaktonew/app/Views/gestor/relatorios/pre_selecao.php" class="nav-link">Relatórios</a>
        </nav>
        
        <div class="user-info">
            <a href="?logout=1" class="btn-logout" onclick="return confirm('Tem certeza que deseja sair?')">Sair</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="controls">       
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
                        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--color-text-muted);">
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
                                <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px;">
                                    <?= htmlspecialchars(substr($ponto['descricao'], 0, 60)) ?><?= strlen($ponto['descricao']) > 60 ? '...' : '' ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($ponto['cidade'] ?? '-') ?></div>
                            <?php if ($ponto['regiao']): ?>
                                <div style="font-size: 0.8rem; color: var(--color-text-muted);"><?= htmlspecialchars($ponto['regiao']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($ponto['cliente'] ?? '-') ?></div>
                            <?php if ($ponto['agencia']): ?>
                                <div style="font-size: 0.8rem; color: var(--color-text-muted);"><?= htmlspecialchars($ponto['agencia']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ponto['tipo'] ?? '-') ?></td>
                        <td><?= badgeSituacao($ponto['situacao'] ?? 'N/A') ?></td>
                        <td>
                            <div class="text-dark"><?= formatarData($ponto['fim_contrato_clean']) ?></div>
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
    const buscaInput = document.querySelector('.busca-input');
    if (buscaInput && !buscaInput.value) {
        buscaInput.focus();
    }
    
    const selects = document.querySelectorAll('select[onchange]');
    selects.forEach(select => {
        select.addEventListener('
        change', function() {
            document.body.classList.add('loading');
        });
    });
    
    window.addEventListener('pageshow', function() {
        document.body.classList.remove('loading');
    });
});
</script>

</body>
</html>