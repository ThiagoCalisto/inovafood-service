<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Database;

require __DIR__ . '/../vendor/autoload.php';

$db = (new Database())->getConnection();

use PhpOffice\PhpWord\IOFactory;

function getOrCreateChecklist($title, $dbConnection)
{
    $stmt = $dbConnection->prepare("SELECT id FROM checklists WHERE title = ?");
    $stmt->execute([$title]);
    $checklist = $stmt->fetch();

    if (!$checklist) {
        $stmt = $dbConnection->prepare("INSERT INTO checklists (title) VALUES (?)");
        $stmt->execute([$title]);
        return $dbConnection->lastInsertId();
    }

    return $checklist['id'];
}

function insertQuestionsAndAlternatives($filePath, $dbConnection)
{
    $phpWord = IOFactory::load($filePath);
    $questionPattern = '/^(\d+\.\d+)|(\d+-\w)|(\d\.\s)|(\d-\s)|(\d+\.)|(\d-)/i';
    $questions = [];
    $firstSentence = "";

      foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            if (get_class($element) === 'PhpOffice\PhpWord\Element\Table') {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        foreach ($cell->getElements() as $cellElement) {
                            if (method_exists($cellElement, 'getText') && !empty(trim($cellElement->getText()))) {
                                $firstSentence = trim($cellElement->getText());
                                break 3;
                            }
                        }
                    }
                }
            }
        }
    }

    if (empty($firstSentence)) {
        throw new Exception("Erro: Não foi possível identificar a primeira frase no arquivo.");
    }

    $checklistId = getOrCreateChecklist($firstSentence, $dbConnection);

    // Processa perguntas e alternativas
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

                                if (preg_match($questionPattern, $cellText)) {
                                    $currentQuestion = $cellText;
                                } elseif ($currentQuestion) {
                                    $currentAlternatives[] = $cellText;
                                }
                            }
                        }
                    }
                    if ($currentQuestion) {
                        $questions[] = ['question' => $currentQuestion, 'alternatives' => $currentAlternatives];
                    }
                }
            }
        }
    }

    foreach ($questions as $q) {
        $stmt = $dbConnection->prepare("INSERT INTO questions (title, type, id_checklist) VALUES (?, ?, ?)");
        $stmt->execute([$q['question'], 0, $checklistId]);

        $questionId = $dbConnection->lastInsertId();

        foreach ($q['alternatives'] as $alternative) {
            $stmt = $dbConnection->prepare("INSERT INTO alternatives (title, id_question) VALUES (?, ?)");
            $stmt->execute([$alternative, $questionId]);
        }
    }

    return count($questions);
}

function convertDocToDocx($filePath)
{
    $outputFile = str_replace('.doc', '.docx', $filePath);
    $command = "unoconv -f docx -o " . escapeshellarg($outputFile) . " " . escapeshellarg($filePath);
    exec($command . ' 2>&1', $output, $returnVar);

    if ($returnVar === 0) {
        return $outputFile;
    } else {
        throw new Exception("Erro ao converter o arquivo .doc para .docx, mande um novo arquivo em .docx: " . implode("\n", $output));
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['docxFile'])) {
    $uploadedFile = $_FILES['docxFile'];

    if ($uploadedFile['error'] == 0) {
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

        if ($fileExtension !== 'docx' && $fileExtension !== 'doc') {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Documento inválido, apenas arquivos .doc e .docx são aceitos']);
            exit;
        }

        $destination = 'uploads/' . $uploadedFile['name'];

        if ($fileExtension === 'doc') {
            try {
                $destination = convertDocToDocx($destination);
            } catch (Exception $e) {
                echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao processar arquivo .doc: ' . $e->getMessage()]);
                exit;
            }
        }

        try {
            $dbConnection = new PDO('mysql:host=localhost;dbname=avaliacao_db;charset=utf8', 'username', '85857946'); //CONFIGURAR COM SEU RESPECTIVO BD
            $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $questionCount = insertQuestionsAndAlternatives($destination, $dbConnection);

            echo json_encode(['status' => 'sucesso', 'numero_de_perguntas' => $questionCount]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Erro de conexão: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao processar o arquivo.']);
    }
}
