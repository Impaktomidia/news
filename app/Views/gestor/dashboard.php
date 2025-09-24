<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Impakto Mídia</title>
    <link rel="stylesheet" href="/public/assets/css/gestor.css">
    <style>
        .dashboard { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: 700; color: #2c3e50; margin-bottom: 10px; }
        .stat-label { color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card.disponivel .stat-number { color: #27AE60; }
        .stat-card.ocupado .stat-number { color: #E74C3C; }
        .stat-card.reservado .stat-number { color: #F39C12; }
        .stat-card.vencido .stat-number { color: #8E44AD; }
        
        .recent-section { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .section-title { font-size: 1.4rem; margin-bottom: 20px; color: #2c3e50; border-bottom: 3px solid #C0392B; padding-bottom: 10px; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .action-btn { background: linear-gradient(45deg, #C0392B, #E74C3C); color: white; padding: 20px; border-radius: 8px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.3s; }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(192,57,43,0.3); color: white; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../layouts/headers.php'; ?>

<div class="dashboard">
    <h1>📊 Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $estatisticas['total'] ?? 0 ?></div>
            <div class="stat-label">Total de Pontos</div>
        </div>
        
        <div class="stat-card disponivel">
            <div class="stat-number"><?= $estatisticas['disponiveis'] ?? 0 ?></div>
            <div class="stat-label">Disponíveis</div>
        </div>
        
        <div class="stat-card ocupado">
            <div class="stat-number"><?= $estatisticas['ocupados'] ?? 0 ?></div>
            <div class="stat-label">Ocupados</div>
        </div>
        
        <div class="stat-card reservado">
            <div class="stat-number"><?= $estatisticas['reservados'] ?? 0 ?></div>
            <div class="stat-label">Reservados</div>
        </div>
    </div>
    
    <div class="quick-actions">
        <a href="?page=pontos" class="action-btn">📋 Ver Todos os Pontos</a>
        <a href="?page=pre_selecao" class="action-btn">📊 Fazer Pré-Seleção</a>
        <a href="?page=pontos&situacao=Disponível" class="action-btn">✅ Pontos Disponíveis</a>
        <a href="?page=pontos&vencimento_proximo=1" class="action-btn">⚠️ Próximos Vencimentos</a>
    </div>
    
    <?php if (!empty($proximosVencimento)): ?>
    <div class="recent-section">
        <h2 class="section-title">⚠️ Contratos Próximos ao Vencimento</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ponto</th>
                        <th>Cliente</th>
                        <th>Cidade</th>
                        <th>Vencimento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proximosVencimento as $ponto): ?>
                    <tr>
                        <td><?= htmlspecialchars($ponto['numero'] ?? '') ?></td>
                        <td><?= htmlspecialchars($ponto['cliente'] ?? '') ?></td>
                        <td><?= htmlspecialchars($ponto['cidade'] ?? '') ?></td>
                        <td><?= htmlspecialchars($ponto['fim_contrato'] ?? '') ?></td>
                        <td>
                            <a href="?page=ponto&id=<?= $ponto['id'] ?>" target="_blank">Ver Detalhes</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

</body>
</html>