<?php
require '../vendor/autoload.php';

// Habilita a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Database;
use App\Processador;

// Cria a conexão com o banco de dados
$db = (new Database())->getConnection();
$processador = new Processador($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $arquivo = $_FILES['file'];

    // Verifica se o arquivo é um .docx
    if ($arquivo['type'] == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        $caminhoArquivo = __DIR__ . '/../uploads/' . basename($arquivo['name']);
        
        // Tenta mover o arquivo enviado para o diretório de uploads
        if (move_uploaded_file($arquivo['tmp_name'], $caminhoArquivo)) {
            // Processar o documento
            $dados = $processador->processarDocumento($caminhoArquivo);
            $quantidadeInserida = $processador->salvarDados($dados);

            // Retornar resposta JSON
            echo json_encode(['success' => true, 'count' => $quantidadeInserida]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao mover o arquivo.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Arquivo inválido.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
