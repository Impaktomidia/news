<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-Seleção - Outdoor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2 class="text-center">Pré-Seleção</h2>
    <form action="?page=pre_selecao_gerar" method="post">
        <div class="mb-3">
            <label>Cliente:</label>
            <input type="text" name="cliente" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Agência:</label>
            <input type="text" name="agencia" class="form-control">
        </div>

        
     
        <div class="mb-3">
            <label>Numeração:</label>
            <textarea name="numeracao" class="form-control" rows="5" placeholder="Ex: 205, 206"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Gerar</button>
        <button type="reset" class="btn btn-warning">Reset</button>
    </form>
</div>

</body>
</html>
