<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Database;
use App\Processador;

require __DIR__ . '/../vendor/autoload.php';

// Cria a conexão com o banco de dados
$db = (new Database())->getConnection();
$processador = new Processador($db);

use PhpOffice\PhpWord\IOFactory;

function insertQuestionsAndAlternatives($filePath, $dbConnection)
{
    $phpWord = IOFactory::load($filePath);
    $questionCount = 0;

    // Define a regex para identificar perguntas como "1.17", "2. Pergunta", etc.
    $questionPattern = '/^(\d+\.\d+)|(\d+-\w)|(\d\.\s)|(\d-\s)|(\d+\.)|(\d-)/i';
    $questions = []; // Para armazenar as perguntas e alternativas

    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if (get_class($element) === 'PhpOffice\PhpWord\Element\Table') {
                foreach ($element->getRows() as $row) {
                    $currentQuestion = null;
                    $currentAlternatives = [];

                    foreach ($row->getCells() as $cell) {
                        foreach ($cell->getElements() as $cellElement) {
                            if (method_exists($cellElement, 'getText')) {
                                $cellText = $cellElement->getText();

                                // Debug: Exibir texto da célula
                                //echo "Processando: " . $cellText . "\n";

                                // Verifica se é uma pergunta
                                if (preg_match($questionPattern, $cellText)) {
                                    // Armazena a pergunta atual
                                    $currentQuestion = $cellText;
                                } elseif ($currentQuestion) {
                                    // Adiciona alternativas se a pergunta atual estiver definida
                                    $currentAlternatives[] = $cellText;
                                }
                            }
                        }
                    }

                    // Armazena a pergunta e suas alternativas
                    if ($currentQuestion) {
                        $questions[] = ['question' => $currentQuestion, 'alternatives' => $currentAlternatives];
                    }
                }
            }
        }
    }

    // Inserir no banco de dados
    foreach ($questions as $q) {
        // Insere a pergunta na tabela questions
        $stmt = $dbConnection->prepare("INSERT INTO questions (title, type, id_checklist) VALUES (?, ?, ?)");
        $stmt->execute([$q['question'], 0, 1]); // Ajuste o type e id_checklist conforme necessário

        $questionId = $dbConnection->lastInsertId(); // Obtém o ID da última pergunta inserida

        // Insere as alternativas na tabela alternatives
        foreach ($q['alternatives'] as $alternative) {
            $stmt = $dbConnection->prepare("INSERT INTO alternatives (title, id_question) VALUES (?, ?)");
            $stmt->execute([$alternative, $questionId]);
        }
    }

    return count($questions); // Retorna o número de perguntas inseridas
}

// Função para converter .doc para .docx usando unoconv
function convertDocToDocx($filePath)
{
    $outputFile = str_replace('.doc', '.docx', $filePath);
    $command = "unoconv -f docx -o " . escapeshellarg($outputFile) . " " . escapeshellarg($filePath);
    exec($command . ' 2>&1', $output, $returnVar); // Capture stderr

    // Verifica se a conversão foi bem-sucedida
    if ($returnVar === 0) {
        return $outputFile;
    } else {
        throw new Exception("Erro ao converter o arquivo .doc para .docx, mande um novo arquivo em .docx: " . implode("\n", $output));
    }
}
// Caminho para o arquivo .docx que foi carregado via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['docxFile'])) {
    $uploadedFile = $_FILES['docxFile'];

    // Verifica se o arquivo foi enviado corretamente
    if ($uploadedFile['error'] == 0) {
        // Obtém a extensão do arquivo
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

        // Verifica se o arquivo é .doc ou .docx
        if ($fileExtension !== 'docx' && $fileExtension !== 'doc') {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Documento inválido, apenas arquivos .doc e .docx são aceitos']);
            exit; // Encerra a execução do script
        }

        $destination = 'uploads/' . $uploadedFile['name'];
        // move_uploaded_file($uploadedFile['tmp_name'], $destination);

        // Converte .doc para .docx se necessário
        if ($fileExtension === 'doc') {
            try {
                $destination = convertDocToDocx($destination);
            } catch (Exception $e) {
                echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao processar arquivo .doc: ' . $e->getMessage()]);
                exit;
            }
        }

        // Conectar ao banco de dados
        try {
            $dbConnection = new PDO('mysql:host=localhost;dbname=avaliacao_db;charset=utf8', 'username', '85857946'); //CONFIGURAR COM SEU RESPECTIVO BD
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insere as perguntas e alternativas
            $questionCount = insertQuestionsAndAlternatives($destination, $dbConnection);

            // Retorna o número de perguntas inseridas
            echo json_encode(['status' => 'sucesso', 'numero_de_perguntas' => $questionCount]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Erro de conexão: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao processar o arquivo.']);
    }
}
