<?php
// Descomente as linhas abaixo apenas em ambiente de desenvolvimento para ver erros:
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once __DIR__ . '/../../Controller/PontoController.php';

$controller = new PontoController();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$ponto = $controller->buscarPorId($id);

if (!$ponto) {
    echo "<p>Ponto não encontrado.</p>";
    exit;
}

// Define baseUrl dinâmico
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $baseUrl = 'http://localhost/impakto';
} else {
    $baseUrl = 'https://impaktomidia.com.br'; // substitua pelo seu domínio
}

function formatarData($data) {
    if (empty($data) || $data === '0000-00-00') {
        return '-';
    }

    $timestamp = strtotime($data);
    if (!$timestamp) {
        return '-';
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Normaliza texto: remove acentos e caracteres não alfanuméricos,
 * resultado em minúsculas sem espaços (ex: "Disponível" -> "disponivel").
 */
function badgeSituacaoClass($situacao) {
    $situacao = trim(mb_strtolower($situacao ?? '', 'UTF-8'));

    // remover acentos
    $situacao = preg_replace(
        [
            '/[áàãâä]/u', '/[éèêë]/u', '/[íìîï]/u',
            '/[óòõôö]/u', '/[úùûü]/u', '/ç/u'
        ],
        ['a', 'e', 'i', 'o', 'u', 'c'],
        $situacao
    );

    $map = [
        'disponivel' => 'disponivel',
        'reservado'  => 'reservado',
        'ocupado'    => 'ocupado',
        'vencido'    => 'vencido',
        'permuta'    => 'permuta',
        'bisemana'   => 'bisemana',
    ];

    $classe = $map[$situacao] ?? 'disponivel';
    return 'badge ' . $classe; // agora sempre vem badge + a classe de cor
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ponto <?= htmlspecialchars($ponto['numero'] ?? '') ?> | Impakto</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/gestor/assets/css/estilo-gestor-ponto.css">
</head>
<body>

<div class="container-ponto">

    <h1 class="titulo-pagina"> <?= htmlspecialchars($ponto['numero'] ?? '') ?></h1>

    <div class="info-box">
        <ul>
            <li><strong>Logradouro:</strong> <?= htmlspecialchars($ponto['logradouro'] ?? '') ?></li>
            <li><strong>Descrição:</strong> <?= htmlspecialchars($ponto['descricao'] ?? '') ?></li>
            <li><strong>Sentido:</strong> <?= htmlspecialchars($ponto['sentido'] ?? '') ?></li>
            <li><strong>Cidade:</strong> <?= htmlspecialchars($ponto['cidade'] ?? '') ?></li>
            <li><strong>Região:</strong> <?= htmlspecialchars($ponto['regiao'] ?? '') ?></li>
            <li><strong>Cliente:</strong> <?= htmlspecialchars($ponto['cliente'] ?? '') ?></li>
            <li><strong>Agência:</strong> <?= htmlspecialchars($ponto['agencia'] ?? '') ?></li>
            <li><strong>Tipo:</strong> <?= htmlspecialchars($ponto['tipo'] ?? '') ?></li>
            <li><strong>Formato:</strong> <?= htmlspecialchars($ponto['formato'] ?? '') ?></li>
            <li>
                <strong>Situação:</strong>
                <span class="<?= badgeSituacaoClass($ponto['situacao'] ?? '') ?>">
                    <?= htmlspecialchars($ponto['situacao'] ?? '') ?>
                </span>
            </li>

            <li> <strong> Inicio: </strong> <?= formatarData($ponto['inicio_contrato'] ?? null) ?></li>
            <li><strong>Fim:</strong> <?= formatarData($ponto['fim_contrato'] ?? null) ?></li>
           
           <li>
    <strong>Observações:</strong><br>
    <div style="
        font-style: italic;
        color: red;
        padding: 5px;
        margin-top: 4px;
        background: #fff5f5;
        border-radius: 4px;
        white-space: pre-wrap;
        overflow-wrap: anywhere; /* Força quebra em qualquer lugar */
        line-height: 1.4; /* Melhor leitura */
        max-height: 150px;
        overflow-y: auto;
    ">
        <?= nl2br(htmlspecialchars($ponto['observacoes'] ?? '')) ?>
    </div>
</li>








        </ul>
    </div>

    <div class="visual-box">
        <img class="imagem-ponto"
             src="<?= $baseUrl . '/gestor/' . htmlspecialchars($ponto['foto'] ?: 'fotos/sem-imagem.jpg') ?>"
             alt="Foto do ponto">

        <?php if (!empty($ponto['latitude']) && !empty($ponto['longitude'])): ?>
            <div class="mapa-embed">
                <iframe
                        width="100%"
                        height="300"
                        style="border:0; border-radius: 12px;"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps?q=<?= htmlspecialchars($ponto['latitude'] ?? '') ?>,<?= htmlspecialchars($ponto['longitude'] ?? '') ?>&hl=pt-BR&z=16&output=embed">
                </iframe>
            </div>
        <?php endif; ?>

        <div class="botoes">
            <a class="btn-voltar" href="#" onclick="voltar()">← Voltar</a>
        </div>
    </div>
</div>

<script>
    function voltar() {
        if (document.referrer && document.referrer !== window.location.href) {
            history.back();
        } else {
            window.location.href = '<?= $baseUrl ?>/gestor/index.php';
        }
    }
</script>

</body>
</html>
