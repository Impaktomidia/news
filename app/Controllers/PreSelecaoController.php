<?php
require_once __DIR__ . '/../Model/PontoModel.php';

class PreSelecaoController {
    public function gerar() {
        $cliente    = $_POST['cliente'] ?? '';
        $agencia    = $_POST['agencia'] ?? '';
        $bisemana   = $_POST['bisemana'] ?? '';
        $prazoNum   = $_POST['prazo_num'] ?? '';
        $prazoMes   = $_POST['prazo_mes'] ?? '';
        $prazoAno   = $_POST['prazo_ano'] ?? '';
        $numeracao  = $_POST['numeracao'] ?? '';

        $numeros = array_map('trim', explode(',', $numeracao));

        $model = new PontoModel();
        $pontos = $model->buscarPorNumeros($numeros);

        require __DIR__ . '/../View/gestor/pre_selecao_resultado.php';
    }
}
