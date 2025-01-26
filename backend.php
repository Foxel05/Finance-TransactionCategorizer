<?php
ini_set('memory_limit', '256M');
require 'vendor/autoload.php';

use Niiknow\Bayes;

header('Content-Type: application/json');

class TransactionSystem {
    private $dataFile = 'data.json';
    private $data;
    private $userId;
    private $classifier;

    public function __construct() {
        $this->loadData();
        $this->userId = $this->getUserId();
        $this->initializeUser();

        // Ensure the models directory exists
        if (!is_dir('models')) {
            mkdir('models', 0777, true);
        }

        // Initialize the classifier with a custom tokenizer
        $this->classifier = new Bayes(['tokenizer' => $this->getCustomTokenizer()]);
        $this->loadClassifier();
    }

    private function getCustomTokenizer() {
        return function ($text) {
            // Convert text to lowercase
            $text = mb_strtolower($text);

            // Use a regex to split text into words and numbers
            preg_match_all('/\b\w+\b/', $text, $matches);

            // Return the first match (list of words and numbers)
            return $matches[0];
        };
    }

    private function getUserId() {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input['userId'] ?? 'default';
    }

    private function loadData() {
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode(['users' => []]));
        }
        $this->data = json_decode(file_get_contents($this->dataFile), true);
    }

    private function initializeUser() {
        if (!isset($this->data['users'][$this->userId])) {
            $this->data['users'][$this->userId] = [
                'transactions' => [],
                'categories' => []
            ];
            $this->saveData();
        }
    }

    private function getModelFilePath($userId) {
        return "models/model_{$userId}.json";
    }

    private function saveClassifier() {
        $modelFilePath = $this->getModelFilePath($this->userId);
        $modelDir = dirname($modelFilePath);

        // Ensure the models directory exists
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0777, true);
        }

        // Save the classifier state to the file with pretty JSON formatting
        $jsonState = $this->classifier->toJson();
        $prettyJsonState = json_encode(json_decode($jsonState), JSON_PRETTY_PRINT);
        file_put_contents($modelFilePath, $prettyJsonState);
    }

    private function loadClassifier() {
        $modelFilePath = $this->getModelFilePath($this->userId);
        if (file_exists($modelFilePath)) {
            $jsonState = file_get_contents($modelFilePath);
            $this->classifier->fromJson($jsonState);
        }
    }

    public function handleRequest() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Only POST requests are supported');
            }

            if (!isset($input['action'])) {
                throw new Exception('Action parameter is required');
            }

            $this->handleAction($input);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    private function handleAction($input) {
        switch ($input['action']) {
            case 'getTransactions':
                $this->getTransactions();
                break;
                
            case 'predict':
                if (empty($input['description'])) {
                    throw new Exception('Description is required for prediction');
                }
                $this->predictCategory($input['description']);
                break;
                
            case 'train':
                if (empty($input['description']) || empty($input['category'])) {
                    throw new Exception('Description and category are required for training');
                }
                $this->trainModel($input['description'], $input['category']);
                break;
                
            case 'savePrediction':
                if (empty($input['description']) || empty($input['category'])) {
                    throw new Exception('Description and category are required for saving prediction');
                }
                $this->savePrediction($input['description'], $input['category']);
                break;
                
            case 'bulkUpdate':
                if (empty($input['updates']) || !is_array($input['updates'])) {
                    throw new Exception('Updates array is required for bulk update');
                }
                $this->bulkUpdate($input['updates']);
                break;
                
            case 'retrain':
                $this->retrainModel();
                break;
                
            case 'getProbabilities': // New action for probabilities
                if (empty($input['description'])) {
                    throw new Exception('Description is required for getting probabilities');
                }
                $this->getProbabilities($input['description']);
                break;
                
            default:
                throw new Exception('Invalid action specified');
        }
    }

    private function getTransactions() {
        $transactions = $this->data['users'][$this->userId]['transactions'];
        echo json_encode(['transactions' => $transactions]);
    }

    private function predictCategory($description) {
        $category = $this->classifier->categorize($description);
        $this->addTransaction($description, $category);
        echo json_encode(['category' => $category]);
    }

    private function trainModel($description, $category) {
        $this->classifier->learn($description, $category);
        $this->saveClassifier();
        $this->addTransaction($description, $category);
        echo json_encode(['status' => 'success']);
    }

    private function savePrediction($description, $category) {
        $this->addTransaction($description, $category);
        echo json_encode(['status' => 'success']);
    }

    private function bulkUpdate($updates) {
        foreach ($updates as $update) {
            if (!isset($this->data['users'][$this->userId]['transactions'][$update['transactionIndex']])) {
                throw new Exception("Invalid transaction index: {$update['transactionIndex']}");
            }
            
            $transaction = &$this->data['users'][$this->userId]['transactions'][$update['transactionIndex']];
            $transaction['category'] = $update['newCategory'];
            
            if (!in_array($update['newCategory'], $this->data['users'][$this->userId]['categories'])) {
                $this->data['users'][$this->userId]['categories'][] = $update['newCategory'];
            }
        }
        
        $this->saveData();
        $this->retrainModel();
        echo json_encode(['status' => 'success']);
    }

    private function retrainModel() {
        $transactions = $this->data['users'][$this->userId]['transactions'];
        if (empty($transactions)) {
            throw new Exception('No transactions available for training');
        }

        $this->classifier = new Bayes(['tokenizer' => $this->getCustomTokenizer()]);
        foreach ($transactions as $transaction) {
            $this->classifier->learn($transaction['description'], $transaction['category']);
        }

        $this->saveClassifier();
    }

    private function getProbabilities($description) {
        // Get the probabilities for the given description
        $probabilities = $this->classifier->probabilities($description);

        // Return the probabilities as a JSON response
        echo json_encode(['probabilities' => $probabilities]);
    }

    private function addTransaction($description, $category) {
        $this->data['users'][$this->userId]['transactions'][] = [
            'description' => $description,
            'category' => $category
        ];
        
        if (!in_array($category, $this->data['users'][$this->userId]['categories'])) {
            $this->data['users'][$this->userId]['categories'][] = $category;
        }
        
        $this->saveData();
    }

    private function saveData() {
        file_put_contents($this->dataFile, json_encode($this->data, JSON_PRETTY_PRINT));
    }
}

// Initialize and run the system
(new TransactionSystem())->handleRequest();