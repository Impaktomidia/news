<?php
$data_emissao = date('d/m/Y');

// Define baseUrl dinâmico
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $baseUrl = 'http://localhost/impakto';
} else {
    $baseUrl = 'https://impaktomidia.com.br'; // substitua pelo seu domínio
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pré-Seleção - Impakto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 60px;
        }
        h2 {
            margin: 0;
            text-align: center;
        }
        .info p {
            margin: 4px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th {
            background: #f2f2f2;
            text-align: left;
        }
        th, td {
            padding: 8px;
        }
        @media print {
          
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <img src="<?= $baseUrl ?>/gestor/assets/img/logo_gestor.png" alt="Logo">
    </div>
    <div>
        <strong>Data de emissão:</strong> <?= $data_emissao ?>
    </div>
</div>

<h2>Pré-Seleção - <?= htmlspecialchars($cliente) ?></h2>

<div class="info">
    <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente) ?></p>
    <p><strong>Agência:</strong> <?= htmlspecialchars($agencia) ?></p>
    <p><strong>Quantidade:</strong> <?= count($pontos) ?></p>
</div>

<table>
    <thead>
        <tr>
            <th>Número</th>
            <th>Logradouro</th>
            <th>Descrição</th>
            <th>Cidade</th>
            <th>Mais Info</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($pontos as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['numero']) ?></td>
            <td><?= htmlspecialchars($p['logradouro']) ?></td>
            <td><?= htmlspecialchars($p['descricao']) ?></td>
            <td><?= htmlspecialchars($p['cidade']) ?></td>
            <td>
                <a href="?page=ponto&id=<?= urlencode($p['id']) ?>" target="_blank">+Info</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
