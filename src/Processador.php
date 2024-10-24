<?php
namespace App;

use PDO;
use PhpOffice\PhpWord\IOFactory;

class Processador {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function processarDocumento($caminhoArquivo) {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($caminhoArquivo);
        $dados = [];
        $perguntaAtual = null;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $texto = trim($element->getText());

                    // Verifica se o texto não está vazio
                    if (!empty($texto)) {
                        // Verifica se o texto representa uma pergunta (começa com número seguido de "-")
                        if (preg_match('/^\d+\-/', $texto)) {
                            // Se tivermos uma pergunta atual, devemos armazená-la antes de iniciar uma nova
                            if ($perguntaAtual) {
                                $dados[] = $perguntaAtual;
                            }

                            // Inicia uma nova pergunta
                            $perguntaAtual = [
                                'title' => $texto,
                                'alternatives' => [] // Inicia um array de alternativas
                            ];
                        } elseif ($perguntaAtual) {
                            // Se já temos uma pergunta atual, adicionamos alternativas
                            $perguntaAtual['alternatives'][] = $texto; // Adiciona resposta à pergunta atual
                        }
                    }
                }
            }

            // Após terminar a seção, adicionamos a última pergunta ao array de dados, se existir
            if ($perguntaAtual) {
                $dados[] = $perguntaAtual;
                $perguntaAtual = null; // Resetar para evitar duplicações
            }
        }

        // Retorna os dados capturados
        return $dados;
    }

    public function contarPerguntas($caminhoArquivo) {
        // Processa o documento para obter os dados
        $dados = $this->processarDocumento($caminhoArquivo);

        // Conta o número de perguntas detectadas
        $numeroPerguntas = count($dados);
        echo "Número de perguntas detectadas: $numeroPerguntas\n";

        // Opcional: Exibir as perguntas para verificação
        foreach ($dados as $pergunta) {
            echo "Pergunta: " . $pergunta['title'] . "\n";
        }

        return $numeroPerguntas;
    }

    public function salvarDados($dados) {
        $quantidadeInserida = 0;

        foreach ($dados as $dado) {
            // Insere a pergunta
            $stmt = $this->conn->prepare("INSERT INTO questions (title, type, id_checklist) VALUES (:title, :type, :id_checklist)");
            $stmt->bindParam(':title', $dado['title']);
            $type = 0; // Defina o tipo conforme sua lógica
            $stmt->bindParam(':type', $type);
            $id_checklist = 1; // Defina o ID do checklist conforme sua lógica
            $stmt->bindParam(':id_checklist', $id_checklist);
            
            if ($stmt->execute()) {
                $quantidadeInserida++;

                // Aqui você pode inserir alternativas se necessário
                $questionId = $this->conn->lastInsertId();
                foreach ($dado['alternatives'] as $alternative) {
                    $stmtAlt = $this->conn->prepare("INSERT INTO alternatives (title, id_question) VALUES (:title, :id_question)");
                    $stmtAlt->bindParam(':title', $alternative);
                    $stmtAlt->bindParam(':id_question', $questionId);
                    $stmtAlt->execute();
                }
            }
        }

        return $quantidadeInserida;
    }

    public function contarColunas($tabela) {
        $stmt = $this->conn->prepare("DESCRIBE $tabela");
        $stmt->execute();
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return count($colunas); // Retorna o número de colunas
    }
}
