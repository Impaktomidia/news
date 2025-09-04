<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php?erro=nao_logado");
    exit;
}

// Importa config do banco
$config = require __DIR__ . '/../../../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['db']};charset=utf8",
        $config['user'],
        $config['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// DEBUGGING: Adicione este bloco temporário para ver o que está acontecendo
$debug = isset($_GET['debug']) ? true : false;

if ($debug) {
    echo "<h3>DEBUG MODE</h3>";
    echo "<p><strong>Parâmetros recebidos:</strong></p>";
    echo "<pre>";
    print_r($_GET);
    echo "</pre>";
}

// Recebe filtros da URL
$busca = $_GET['busca'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$regiao = $_GET['regiao'] ?? '';
$situacao = $_GET['situacao'] ?? '';
$cliente = $_GET['cliente'] ?? '';
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itensPorPagina = 10;
$offset = ($pagina - 1) * $itensPorPagina;

// Debug: Verificar se há registros na tabela
if ($debug) {
    $sqlTotal = "SELECT COUNT(*) as total, 
                        COUNT(CASE WHEN ativo = 1 THEN 1 END) as ativos,
                        COUNT(CASE WHEN ativo = 0 THEN 1 END) as inativos
                 FROM pontos";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute();
    $totais = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Registros na tabela:</strong></p>";
    echo "<ul>";
    echo "<li>Total geral: " . $totais['total'] . "</li>";
    echo "<li>Ativos: " . $totais['ativos'] . "</li>";
    echo "<li>Inativos: " . $totais['inativos'] . "</li>";
    echo "</ul>";
}

// Busca lista de clientes para o select
$sqlClientes = "SELECT DISTINCT cliente FROM pontos WHERE ativo = 1 AND cliente IS NOT NULL AND cliente != '' ORDER BY cliente";
$stmtClientes = $pdo->prepare($sqlClientes);
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_COLUMN);

if ($debug) {
    echo "<p><strong>Clientes encontrados:</strong> " . count($clientes) . "</p>";
    if (count($clientes) > 0) {
        echo "<ul>";
        foreach (array_slice($clientes, 0, 5) as $cli) {
            echo "<li>" . htmlspecialchars($cli) . "</li>";
        }
        if (count($clientes) > 5) {
            echo "<li>... e mais " . (count($clientes) - 5) . " clientes</li>";
        }
        echo "</ul>";
    }
}

// Monta consulta dinâmica
$where = [];
$params = [];

// Sempre trazer só ativos
$where[] = "ativo = 1";

if ($busca !== '') {
    $where[] = "(logradouro LIKE :busca OR descricao LIKE :busca OR cliente LIKE :busca OR agencia LIKE :busca OR cidade LIKE :busca OR numero LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

if ($tipo !== '') {
    $where[] = "tipo = :tipo";
    $params[':tipo'] = $tipo;
}

if ($regiao !== '') {
    $where[] = "regiao = :regiao";
    $params[':regiao'] = $regiao;
}

if ($situacao !== '') {
    $where[] = "situacao = :situacao";
    $params[':situacao'] = $situacao;
}

if ($cliente !== '') {
    $where[] = "cliente = :cliente";
    $params[':cliente'] = $cliente;
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Debug: Mostrar a consulta que será executada
if ($debug) {
    echo "<p><strong>Consulta WHERE:</strong></p>";
    echo "<pre>$whereSql</pre>";
    echo "<p><strong>Parâmetros:</strong></p>";
    echo "<pre>";
    print_r($params);
    echo "</pre>";
}

// Conta total de registros para paginação
$sqlCount = "SELECT COUNT(*) FROM pontos $whereSql";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPaginas = ceil($total / $itensPorPagina);

if ($debug) {
    echo "<p><strong>Total encontrado com filtros:</strong> $total</p>";
    echo "<p><strong>Total de páginas:</strong> $totalPaginas</p>";
    echo "<p><strong>Página atual:</strong> $pagina</p>";
    echo "<p><strong>Offset:</strong> $offset</p>";
    echo "<p><strong>Itens por página:</strong> $itensPorPagina</p>";
}

// Consulta principal com limite


$sql = "SELECT * FROM pontos $whereSql 
        ORDER BY 
            CASE 
                WHEN fim_contrato IS NULL OR fim_contrato = '0000-00-00' OR fim_contrato = '' THEN 1
                ELSE 0 
            END,
            fim_contrato ASC 
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// Bind dos parâmetros dinâmicos
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

// Bind dos limites, que devem ser inteiros
$stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

if ($debug) {
    echo "<p><strong>Consulta final:</strong></p>";
    echo "<pre>$sql</pre>";
    echo "<p><strong>LIMIT:</strong> $itensPorPagina, <strong>OFFSET:</strong> $offset</p>";
}

$stmt->execute();
$pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($debug) {
    echo "<p><strong>Registros retornados:</strong> " . count($pontos) . "</p>";
    if (count($pontos) > 0) {
        echo "<p><strong>Primeiro registro:</strong></p>";
        echo "<pre>";
        print_r($pontos[0]);
        echo "</pre>";
    }
    echo "<hr>";
}

// Funções auxiliares
// Versão melhorada das funções auxiliares para datas
// Funções auxiliares para formatação de datas em mês/ano


// Função para exibir data com status visual (mês/ano)
function formatarDataComStatus(?string $data, string $tipo = ''): string {
    if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
        return '<span class="data-vazia">-</span>';
    }

    try {
        $date = new DateTime($data);
        $hoje = new DateTime();
        $dataFormatada = $date->format('F/Y');
        
        // Para contratos com data de fim
        if ($tipo === 'fim') {
            if ($date < $hoje) {
                return '<span class="data-vencida" title="Contrato vencido">' . $dataFormatada . '</span>';
            } elseif ($date->diff($hoje)->m <= 2 && $date > $hoje) {
                return '<span class="data-proximo-vencimento" title="Vence em breve">' . $dataFormatada . '</span>';
            } else {
                return '<span class="data-valida">' . $dataFormatada . '</span>';
            }
        }
        
        // Para contratos com data de início
        if ($tipo === 'inicio') {
            if ($date > $hoje) {
                return '<span class="data-futura" title="Contrato futuro">' . $dataFormatada . '</span>';
            } else {
                return '<span class="data-valida">' . $dataFormatada . '</span>';
            }
        }
        
        return '<span class="data-valida">' . $dataFormatada . '</span>';
        
    } catch (Exception $e) {
        return '<span class="data-erro" title="Formato de data inválido">Data inválida</span>';
    }
}

// Função para calcular meses restantes
function mesesRestantes(?string $dataFim): string {
    if (empty($dataFim) || $dataFim === '0000-00-00') {
        return '';
    }

    try {
        $fim = new DateTime($dataFim);
        $hoje = new DateTime();
        
        // Calcular diferença em meses
        $interval = $hoje->diff($fim);
        $mesesTotal = ($interval->y * 12) + $interval->m;
        
        if ($fim < $hoje) {
            // Contrato vencido
            return '<small class="meses-vencido">Vencido há ' . $mesesTotal . ' mês(es)</small>';
        } else {
            // Contrato ativo
            if ($mesesTotal == 0) {
                // Menos de 1 mês
                $dias = $interval->d;
                if ($dias <= 7) {
                    return '<small class="meses-critico">Vence esta semana</small>';
                } else {
                    return '<small class="meses-critico">Vence este mês</small>';
                }
            } elseif ($mesesTotal == 1) {
                return '<small class="meses-atencao">Resta 1 mês</small>';
            } elseif ($mesesTotal <= 3) {
                return '<small class="meses-atencao">Restam ' . $mesesTotal . ' meses</small>';
            } else {
                return '<small class="meses-normal">Restam ' . $mesesTotal . ' meses</small>';
            }
        }
    } catch (Exception $e) {
        return '';
    }
}

// Função alternativa mais precisa para cálculo de meses
function mesesRestantesPreciso(?string $dataFim): string {
    if (empty($dataFim) || $dataFim === '0000-00-00') {
        return '';
    }

    try {
        $fim = new DateTime($dataFim);
        $hoje = new DateTime();
        
        if ($fim < $hoje) {
            // Contrato vencido - calcular há quantos meses
            $anosDiff = $hoje->format('Y') - $fim->format('Y');
            $mesesDiff = $hoje->format('m') - $fim->format('m');
            $mesesTotal = ($anosDiff * 12) + $mesesDiff;
            
            if ($mesesTotal == 0) {
                return '<small class="meses-vencido">Vencido este mês</small>';
            } else {
                return '<small class="meses-vencido">Vencido há ' . $mesesTotal . ' mês(es)</small>';
            }
        } else {
            // Contrato futuro - calcular quantos meses restam
            $anosDiff = $fim->format('Y') - $hoje->format('Y');
            $mesesDiff = $fim->format('m') - $hoje->format('m');
            $mesesTotal = ($anosDiff * 12) + $mesesDiff;
            
            if ($mesesTotal == 0) {
                return '<small class="meses-critico">Vence este mês</small>';
            } elseif ($mesesTotal == 1) {
                return '<small class="meses-atencao">Resta 1 mês</small>';
            } elseif ($mesesTotal <= 3) {
                return '<small class="meses-atencao">Restam ' . $mesesTotal . ' meses</small>';
            } else {
                return '<small class="meses-normal">Restam ' . $mesesTotal . ' meses</small>';
            }
        }
    } catch (Exception $e) {
        return '';
    }
}

// Função para exibir período do contrato (mês/ano)
function exibirPeriodoContratoMensalY(?string $inicio, ?string $fim): string {
    $inicioFormatado = formatarData($inicio);
    $fimFormatado = formatarDataComStatus($fim, 'fim');
    $mesesInfo = mesesRestantesPreciso($fim);
    
    $html = '<div class="periodo-contrato">';
    $html .= '<div class="datas-mensais">';
    $html .= '<span class="inicio">' . $inicioFormatado . '</span>';
    $html .= ' <span class="separador">até</span> ';
    $html .= '<span class="fim">' . $fimFormatado . '</span>';
    $html .= '</div>';
    
    if ($mesesInfo) {
        $html .= '<div class="meses-info">' . $mesesInfo . '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// Versão simplificada para usar na tabela
function formatarMesAno(?string $data): string {
    if (empty($data) || $data === '0000-00-00' || $data === '0000-00-00 00:00:00') {
        return '-';
    }

    try {
        $date = new DateTime($data);

        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];

        $mes = $meses[(int)$date->format('n')];
        return $mes . '/' . $date->format('Y');
    } catch (Exception $e) {
        return 'Data inválida';
    }
}


// Versão simplificada para meses restantes
function tempoRestanteMeses(?string $dataFim): string {
    if (empty($dataFim) || $dataFim === '0000-00-00') {
        return '';
    }

    try {
        $fim = new DateTime($dataFim);
        $hoje = new DateTime();
        
        $anosDiff = $fim->format('Y') - $hoje->format('Y');
        $mesesDiff = $fim->format('m') - $hoje->format('m');
        $mesesTotal = ($anosDiff * 12) + $mesesDiff;
        
        if ($mesesTotal < 0) {
            return 'Vencido há ' . abs($mesesTotal) . 'm';
        } elseif ($mesesTotal == 0) {
            return 'Vence este mês';
        } else {
            return 'Restam ' . $mesesTotal . 'm';
        }
    } catch (Exception $e) {
        return '';
    }
}


function badgeSituacaoClass($situacao) {
    $situacao = trim(mb_strtolower($situacao ?? '', 'UTF-8'));
    $situacao = normalizarTexto($situacao);
    $map = [
        'disponivel' => 'disponivel',
        'reservado' => 'reservado',
        'ocupado' => 'ocupado',
        'vencido' => 'vencido',
        'permuta' => 'permuta',
        'bisemana' => 'bisemana',
    ];
    return $map[$situacao] ?? 'disponivel';
}

function normalizarTexto($str) {
    $comAcento = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç'];
    $semAcento  = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'];
    return str_replace($comAcento, $semAcento, $str);
}

// Define baseUrl dinâmico
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $baseUrl = 'http://localhost/impakto';
} else {
    $baseUrl = 'https://impaktomidia.com.br'; // substitua pelo seu domínio
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Impakto - Painel Gestor</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/gestor/assets/css/estilo-gestor.css">
</head>
<body>

<header class="topo">
    <div class="header-container">
        <div class="logo">
            <img src="<?= $baseUrl ?>/gestor/assets/img/logo_gestor.png" alt="Logo">
        </div>

        <div class="busca-google-central">
            <form method="get">
                <!-- Preservar parâmetros de debug -->
                <?php if ($debug): ?>
                    <input type="hidden" name="debug" value="1">
                <?php endif; ?>
                
                <input
                        type="text"
                        name="busca"
                        placeholder="Buscar pontos..."
                        value="<?= htmlspecialchars($busca) ?>"
                        onkeypress="if(event.key === 'Enter') this.form.submit();"
                />
                <button type="submit" class="icone-lupa">&#128269;</button>
            </form>
        </div>

        <div class="acoes">
            <nav class="links-topo">
                <a href="index.php?page=pre_selecao">Pré-Seleção</a>              
            </nav>

            <!-- Boas-vindas + Logout -->
            <div class="usuario-menu">
                <span>👋 Bem-vindo, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></span>
                <a href="<?= $baseUrl ?>/gestor/logout.php" class="btn-logout">Sair</a>
            </div>
        </div>
    </div>
</header>

<div class="filtros-container">
    <div class="pontos-contagem">
        <?php if ($total > 0): ?>
            <span><?= $total ?> ponto(s) encontrado(s)</span>
        <?php else: ?>
            <span>Nenhum ponto encontrado.</span>
        <?php endif; ?>
    </div>

    <form method="get" class="filtros-form">
        <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>" />
        <?php if ($debug): ?>
            <input type="hidden" name="debug" value="1">
        <?php endif; ?>

        <label for="tipo">
            <select name="tipo" id="tipo" onchange="this.form.submit()">
                <option value="">Tipo</option>
                <option value="Outdoor" <?= $tipo === 'Outdoor' ? 'selected' : '' ?>>Outdoor</option>
                <option value="Painel" <?= $tipo === 'Painel' ? 'selected' : '' ?>>Painel</option>
            </select>
        </label>

        <label for="regiao">
            <select name="regiao" id="regiao" onchange="this.form.submit()">
                <option value="">Região</option>
                <option value="Metropolitana" <?= $regiao === 'Metropolitana' ? 'selected' : '' ?>>Metropolitana</option>
                <option value="Agreste" <?= $regiao === 'Agreste' ? 'selected' : '' ?>>Agreste</option>
                <option value="Mata Norte" <?= $regiao === 'Mata Norte' ? 'selected' : '' ?>>Mata Norte</option>
                <option value="Mata Sul" <?= $regiao === 'Mata Sul' ? 'selected' : '' ?>>Mata Sul</option>
                <option value="Litoral Norte" <?= $regiao === 'Litoral Norte' ? 'selected' : '' ?>>Litoral Norte</option>
                <option value="Litoral Sul" <?= $regiao === 'Litoral Sul' ? 'selected' : '' ?>>Litoral Sul</option>
                <option value="Sertão" <?= $regiao === 'Sertão' ? 'selected' : '' ?>>Sertão</option>
            </select>
        </label>

        <label for="situacao">
            <select name="situacao" id="situacao" onchange="this.form.submit()">
                <option value="">Situação</option>
                <?php
                $situacoes = ['Disponível', 'Reservado', 'Ocupado', 'Vencido', 'Permuta', 'Bisemana'];
                foreach ($situacoes as $sit) {
                    $selected = $situacao === $sit ? 'selected' : '';
                    echo "<option value=\"$sit\" $selected>$sit</option>";
                }
                ?>
            </select>
        </label>

        <label for="cliente">
            <select name="cliente" id="cliente" onchange="this.form.submit()">
                <option value="">Cliente</option>
                <?php foreach ($clientes as $cli): ?>
                    <option value="<?= htmlspecialchars($cli) ?>" <?= $cliente === $cli ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cli) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <a href="?reset=1<?= $debug ? '&debug=1' : '' ?>" class="btn-reset">Limpar</a>
    </form>
</div>

<div class="tabela-scroll">
    <table class="tabela-compacta">
        <thead>
        <tr>
            <th>Nº</th>
            <th>Logradouro</th>
            <th>Descrição</th>
            <th>Cidade</th>
            <th>Cliente</th>
            <th>Status</th>
             <th class="tabela-data-fim">Fim do Contrato</th>
            
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pontos as $ponto): ?>
            <tr>
                <td data-label="Ponto"><?= htmlspecialchars($ponto['numero'] ?? '') ?></td>
                <td data-label="Logradouro"><?= htmlspecialchars($ponto['logradouro'] ?? '') ?></td>
                <td data-label="Descrição"><?= htmlspecialchars($ponto['descricao'] ?? '') ?></td>
                <td data-label="Cidade"><?= htmlspecialchars($ponto['cidade'] ?? '') ?></td>
                
                <td data-label="Cliente"><?= htmlspecialchars($ponto['cliente'] ?? '') ?></td>

                <td data-label="Situação">
                    <span class="badge <?= badgeSituacaoClass($ponto['situacao'] ?? '') ?>">
                        <?= htmlspecialchars($ponto['situacao'] ?? '') ?>
                    </span>
                </td>
                            
                <!-- OPÇÃO 1: Formato simples -->
            
                <td data-label="Fim" class="tabela-data-fim">
                    <span class="data-compacta"><?= formatarMesAno($ponto['fim_contrato'] ?? null) ?></span>
                    <div class="tempo-restante"><?= tempoRestanteMeses($ponto['fim_contrato'] ?? null) ?></div>
                </td>

                <!-- OPÇÃO 2: Formato com status colorido (substitua as células acima por estas) -->
                <!--
                <td data-label="Início" class="tabela-data-inicio">
                    <?= formatarDataComStatus($ponto['inicio_contrato'] ?? null, 'inicio') ?>
                </td>
                <td data-label="Fim" class="tabela-data-fim">
                    <?= formatarDataComStatus($ponto['fim_contrato'] ?? null, 'fim') ?>
                    <?= mesesRestantesPreciso($ponto['fim_contrato'] ?? null) ?>
                </td>
                -->

                <!-- OPÇÃO 3: Formato com badges (substitua as células acima por estas) -->
                <!--
                <td data-label="Início" class="tabela-data-inicio">
                    <span class="data-compacta"><?= formatarMesAno($ponto['inicio_contrato'] ?? null) ?></span>
                </td>
                <td data-label="Fim" class="tabela-data-fim">
                    <span class="data-compacta"><?= formatarMesAno($ponto['fim_contrato'] ?? null) ?></span>
                    <?php 
                    $tempoRestante = tempoRestanteMeses($ponto['fim_contrato'] ?? null);
                    if ($tempoRestante && $tempoRestante !== '') {
                        $classeBadge = 'normal';
                        if (strpos($tempoRestante, 'Vencido') !== false) {
                            $classeBadge = 'vencido';
                        } elseif (strpos($tempoRestante, 'este mês') !== false) {
                            $classeBadge = 'critico';
                        } elseif (strpos($tempoRestante, 'Restam') !== false && 
                                  (strpos($tempoRestante, '1m') !== false || strpos($tempoRestante, '2m') !== false || strpos($tempoRestante, '3m') !== false)) {
                            $classeBadge = 'atencao';
                        }
                        echo '<span class="badge-tempo ' . $classeBadge . '">' . $tempoRestante . '</span>';
                    }
                    ?>
                </td>
                -->
                
                
                <td data-label="Detalhes">
                    <a href="?page=ponto&id=<?= urlencode($ponto['id']) ?>" target="_blank"> +Info</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPaginas > 1): ?>
    <div class="paginacao">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"
               class="<?= $i == $pagina ? 'ativo' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<script>
    document.querySelectorAll('.ver-detalhes').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;

            document.getElementById('conteudoDetalhes').innerHTML = 'Carregando...';

            fetch('ponto.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('conteudoDetalhes').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
                })
                .catch(() => {
                    document.getElementById('conteudoDetalhes').innerHTML = '<p>Erro ao carregar detalhes.</p>';
                });
        });
    });
</script>

</body>
</html>