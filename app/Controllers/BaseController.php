<?php
// app/Controllers/BaseController.php
abstract class BaseController {
    protected function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    protected function redirect($url, $message = null, $type = 'success') {
        if ($message) {
            $_SESSION["flash_{$type}"] = $message;
        }
        header("Location: $url");
        exit;
    }
    
    protected function view($viewPath, $data = []) {
        extract($data);
        
        ob_start();
        include __DIR__ . "/../Views/{$viewPath}.php";
        $content = ob_get_clean();
        
        // Se não for uma requisição AJAX, incluir layout
        if (!$this->isAjax()) {
            include __DIR__ . '/../Views/layouts/app.php';
        } else {
            echo $content;
        }
    }
    
    protected function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    protected function validateCSRF() {
        $token = $_POST['_token'] ?? $_GET['_token'] ?? null;
        if (!$token || !AuthMiddleware::checkCSRF($token)) {
            $this->jsonResponse(['error' => 'Token CSRF inválido'], 403);
        }
    }
}

// app/Controllers/PontoController.php
class PontoController extends BaseController {
    private $pontoModel;
    
    public function __construct() {
        $this->pontoModel = new PontoModel();
    }
    
    public function index() {
        try {
            // Validação e sanitização de inputs
            $filtros = ValidationService::validateFilters($_GET);
            $pagination = ValidationService::validatePagination(
                $_GET['pagina'] ?? 1,
                $_GET['limite'] ?? 10
            );
            
            // Busca dados
            $pontos = $this->pontoModel->listarPaginado(
                $filtros,
                $pagination['page'],
                $pagination['limit']
            );
            
            $total = $this->pontoModel->contar($filtros);
            $totalPaginas = ceil($total / $pagination['limit']);
            
            // Dados extras para a view
            $clientes = $this->pontoModel->obterClientesAtivos();
            $estatisticas = $this->pontoModel->obterEstatisticas();
            
            $data = [
                'pontos' => $pontos,
                'total' => $total,
                'totalPaginas' => $totalPaginas,
                'paginaAtual' => $pagination['page'],
                'limite' => $pagination['limit'],
                'filtros' => $filtros,
                'clientes' => $clientes,
                'estatisticas' => $estatisticas
            ];
            
            // Se for AJAX, retorna apenas os dados
            if ($this->isAjax()) {
                $this->jsonResponse($data);
            }
            
            $this->view('gestor/pontos/index', $data);
            
        } catch (Exception $e) {
            error_log("Erro em PontoController::index: " . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->jsonResponse(['error' => 'Erro interno do servidor'], 500);
            }
            
            $this->view('errors/500', ['message' => 'Erro ao carregar pontos']);
        }
    }
    
    public function show($id) {
        try {
            $id = ValidationService::validateId($id);
            if (!$id) {
                throw new Exception("ID inválido");
            }
            
            $ponto = $this->pontoModel->findOrFail($id);
            
            if ($this->isAjax()) {
                $this->jsonResponse(['ponto' => $ponto]);
            }
            
            $this->view('gestor/pontos/show', ['ponto' => $ponto]);
            
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->jsonResponse(['error' => $e->getMessage()], 404);
            }
            
            $this->view('errors/404', ['message' => $e->getMessage()]);
        }
    }
    
    public function buscarPorNumeros() {
        try {
            $this->validateCSRF();
            
            $numeracao = $_POST['numeracao'] ?? '';
            $numeros = array_filter(
                array_map('trim', explode(',', $numeracao))
            );
            
            if (empty($numeros)) {
                throw new Exception("Nenhum número fornecido");
            }
            
            $pontos = $this->pontoModel->buscarPorNumeros($numeros);
            
            $data = [
                'pontos' => $pontos,
                'cliente' => ValidationService::sanitizeString($_POST['cliente'] ?? ''),
                'agencia' => ValidationService::sanitizeString($_POST['agencia'] ?? ''),
                'numerosNaoEncontrados' => array_diff($numeros, array_column($pontos, 'numero'))
            ];
            
            $this->view('gestor/pre_selecao_resultado', $data);
            
        } catch (Exception $e) {
            $this->redirect('?page=pre_selecao', $e->getMessage(), 'error');
        }
    }
    
    public function dashboard() {
        try {
            $estatisticas = $this->pontoModel->obterEstatisticas();
            
            // Pontos próximos ao vencimento (próximos 30 dias)
            $proximosVencimento = $this->pontoModel->listarPaginado([
                'vencimento_proximo' => true
            ], 1, 10);
            
            $data = [
                'estatisticas' => $estatisticas,
                'proximosVencimento' => $proximosVencimento
            ];
            
            $this->view('gestor/dashboard', $data);
            
        } catch (Exception $e) {
            error_log("Erro no dashboard: " . $e->getMessage());
            $this->view('gestor/dashboard', ['error' => 'Erro ao carregar dashboard']);
        }
    }
    
    public function exportar() {
        try {
            $filtros = ValidationService::validateFilters($_GET);
            $pontos = $this->pontoModel->listarPaginado($filtros, 1, 1000); // Máximo para exportação
            
            $filename = 'pontos_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeçalhos
            fputcsv($output, [
                'Número', 'Logradouro', 'Descrição', 'Cidade', 'Região',
                'Cliente', 'Agência', 'Tipo', 'Situação', 'Início Contrato', 'Fim Contrato'
            ]);
            
            // Dados
            foreach ($pontos as $ponto) {
                fputcsv($output, [
                    $ponto['numero'],
                    $ponto['logradouro'],
                    $ponto['descricao'],
                    $ponto['cidade'],
                    $ponto['regiao'],
                    $ponto['cliente'],
                    $ponto['agencia'],
                    $ponto['tipo'],
                    $ponto['situacao'],
                    $ponto['inicio_contrato'],
                    $ponto['fim_contrato']
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log("Erro na exportação: " . $e->getMessage());
            $this->redirect('?page=pontos', 'Erro ao exportar dados', 'error');
        }
    }
}
