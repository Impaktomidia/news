<?php
// SUBSTITUIR COMPLETAMENTE o arquivo: app/Views/gestor/relatorios/pre_selecao.php

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../../../public/index.php?erro=nao_logado");
    exit;
}

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processar formulário se for POST
$erro = '';
$sucesso = '';
$pontos_encontrados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = trim($_POST['cliente'] ?? '');
    $agencia = trim($_POST['agencia'] ?? '');
    $numeracao = trim($_POST['numeracao'] ?? '');
    
    if (empty($cliente)) {
        $erro = "Cliente é obrigatório";
    } elseif (empty($numeracao)) {
        $erro = "Numeração dos pontos é obrigatória";
    } else {
        // Processar numeração
        $numeros = array_map('trim', explode(',', $numeracao));
        $numeros = array_filter($numeros); // Remove vazios
        $numeros = array_slice($numeros, 0, 100); // Máximo 100
        
        if (empty($numeros)) {
            $erro = "Nenhum número válido foi informado";
        } else {
            // Conectar ao banco
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=ipk2024;charset=utf8", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Buscar pontos
                $placeholders = implode(',', array_fill(0, count($numeros), '?'));
                $sql = "SELECT numero, logradouro, descricao, cidade, regiao, tipo, situacao, 
                               cliente, agencia, inicio_contrato, fim_contrato
                        FROM pontos 
                        WHERE numero IN ($placeholders)
                        ORDER BY FIELD(numero, " . implode(',', array_fill(0, count($numeros), '?')) . ")";
                
                $stmt = $pdo->prepare($sql);
                // Bind os números duas vezes (para IN e para ORDER BY FIELD)
                $params = array_merge($numeros, $numeros);
                $stmt->execute($params);
                $pontos_encontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($pontos_encontrados)) {
                    $erro = "Nenhum ponto foi encontrado com os números informados";
                } else {
                    $sucesso = "Encontrados " . count($pontos_encontrados) . " pontos";
                }
                
            } catch (Exception $e) {
                $erro = "Erro ao buscar pontos: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-Seleção - Impakto</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .card-header {
            border-bottom: 2px solid #667eea;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .card-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-error {
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }
        
        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        /* Tabela de resultados */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-top: 2rem;
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
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }
        
        .badge-disponivel { background: #d4edda; color: #155724; }
        .badge-ocupado { background: #f8d7da; color: #721c24; }
        .badge-reservado { background: #fff3cd; color: #856404; }
        .badge-vencido { background: #e2e3e5; color: #383d41; }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }
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
            <a href="/impaktonew/app/Views/gestor/listar_ponto.php">Lista de Pontos</a>
            <a href="#" class="active">Pré-Seleção</a>
        </div>
        
        <div class="user-info">
            Olá, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong>
            <a href="/impaktonew/logout.php" class="btn-logout">Sair</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>📊 Pré-Seleção de Pontos</h2>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                <span>✅</span>
                <?= htmlspecialchars($sucesso) ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cliente">Cliente *</label>
                    <input type="text" name="cliente" id="cliente" required maxlength="100"
                           value="<?= htmlspecialchars($_POST['cliente'] ?? '') ?>"
                           placeholder="Nome do cliente">
                </div>
                
                <div class="form-group">
                    <label for="agencia">Agência</label>
                    <input type="text" name="agencia" id="agencia" maxlength="100"
                           value="<?= htmlspecialchars($_POST['agencia'] ?? '') ?>"
                           placeholder="Nome da agência (opcional)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="numeracao">Numeração dos Pontos *</label>
                <textarea name="numeracao" id="numeracao" rows="5" required maxlength="1000"
                          placeholder="Digite os números separados por vírgula. Ex: 205, 206, 207, 208"><?= htmlspecialchars($_POST['numeracao'] ?? '') ?></textarea>
                <div class="form-text">
                    Máximo 100 pontos por consulta. Separe os números com vírgula.
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <span>📊</span>
                    Gerar Pré-Seleção
                </button>
                <button type="reset" class="btn btn-secondary">
                    <span>🔄</span>
                    Limpar Campos
                </button>
                <a href="/impaktonew/app/Views/gestor/listar_ponto.php" class="btn btn-outline">
                    <span>←</span>
                    Voltar à Lista
                </a>
            </div>
        </form>
    </div>
    
    <?php if (!empty($pontos_encontrados)): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Logradouro</th>
                    <th>Cidade</th>
                    <th>Tipo</th>
                    <th>Situação</th>
                    <th>Cliente Atual</th>
                    <th>Vencimento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pontos_encontrados as $ponto): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ponto['numero'] ?? '') ?></strong></td>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($ponto['logradouro'] ?? '') ?></div>
                        <?php if ($ponto['descricao']): ?>
                            <small style="color: #6c757d;"><?= htmlspecialchars(substr($ponto['descricao'], 0, 50)) ?><?= strlen($ponto['descricao']) > 50 ? '...' : '' ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($ponto['cidade'] ?? '') ?></div>
                        <?php if ($ponto['regiao']): ?>
                            <small style="color: #6c757d;"><?= htmlspecialchars($ponto['regiao']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($ponto['tipo'] ?? '') ?></td>
                    <td>
                        <?php 
                        $situacao = $ponto['situacao'] ?? '';
                        $badgeClass = 'badge-' . strtolower(str_replace(' ', '', $situacao));
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($situacao) ?></span>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($ponto['cliente'] ?? '-') ?></div>
                        <?php if ($ponto['agencia']): ?>
                            <small style="color: #6c757d;"><?= htmlspecialchars($ponto['agencia']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $fim = $ponto['fim_contrato'] ?? '';
                        if ($fim && $fim !== '0000-00-00') {
                            try {
                                $date = new DateTime($fim);
                                echo $date->format('m/Y');
                            } catch (Exception $e) {
                                echo '-';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card" style="margin-top: 2rem;">
        <h3 style="margin-bottom: 1rem; color: #2c3e50;">📄 Ações com os Resultados</h3>
        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-outline">
                <span>🖨️</span>
                Imprimir
            </button>
            <button onclick="exportToCSV()" class="btn btn-outline">
                <span>📊</span>
                Exportar CSV
            </button>
            <button onclick="copyToClipboard()" class="btn btn-outline">
                <span>📋</span>
                Copiar Lista
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus no primeiro campo
    document.getElementById('cliente').focus();
});

function exportToCSV() {
    const table = document.querySelector('.table');
    if (!table) return;
    
    let csv = '';
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv += rowData.join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'pre_selecao_pontos.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function copyToClipboard() {
    const pontos = document.querySelectorAll('.table tbody tr');
    let texto = 'PONTOS SELECIONADOS:\n\n';
    
    pontos.forEach(ponto => {
        const numero = ponto.querySelector('td:nth-child(1)').textContent.trim();
        const logradouro = ponto.querySelector('td:nth-child(2)').textContent.trim();
        const cidade = ponto.querySelector('td:nth-child(3)').textContent.trim();
        texto += `${numero} - ${logradouro}, ${cidade}\n`;
    });
    
    navigator.clipboard.writeText(texto).then(() => {
        alert('Lista copiada para a área de transferência!');
    });
}
</script>

</body>
</html>