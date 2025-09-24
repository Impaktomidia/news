<?php
require_once __DIR__ . '/../../../config/security.php';

// Verificar se usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php?erro=nao_logado");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-Seleção - Impakto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 800px; margin-top: 30px; }
        .card { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .form-control:focus { border-color: #C0392B; box-shadow: 0 0 0 0.2rem rgba(192, 57, 43, 0.25); }
        .btn-primary { background-color: #C0392B; border-color: #C0392B; }
        .btn-primary:hover { background-color: #a12a20; border-color: #a12a20; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">📊 Pré-Seleção de Pontos</h4>
                </div>
                <div class="card-body">
                    
                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['erro']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form action="?page=pre_selecao_gerar" method="post">
                        <!-- Token CSRF -->
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="cliente" class="form-label">Cliente *</label>
                                    <input type="text" name="cliente" id="cliente" class="form-control" 
                                           required maxlength="100"
                                           value="<?= htmlspecialchars($_POST['cliente'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="agencia" class="form-label">Agência</label>
                                    <input type="text" name="agencia" id="agencia" class="form-control"
                                           maxlength="100"
                                           value="<?= htmlspecialchars($_POST['agencia'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="numeracao" class="form-label">Numeração dos Pontos *</label>
                            <textarea name="numeracao" id="numeracao" class="form-control" rows="5" 
                                      required maxlength="1000"
                                      placeholder="Digite os números separados por vírgula. Ex: 205, 206, 207, 208"><?= htmlspecialchars($_POST['numeracao'] ?? '') ?></textarea>
                            <div class="form-text">
                                Máximo 100 pontos por consulta. Separe os números com vírgula.
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                📊 Gerar Pré-Seleção
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                🔄 Limpar Campos
                            </button>
                            <a href="?" class="btn btn-outline-secondary">
                                ← Voltar à Lista
                            </a>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus no primeiro campo
    document.getElementById('cliente').focus();
});
</script>

</body>
</html>
