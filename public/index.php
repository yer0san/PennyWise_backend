<?php

require_once __DIR__ . '/../src/controllers/expenseController.php';
require_once __DIR__ . '/../src/controllers/incomeController.php';

session_start();
    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = session_id();
        $mysqli = new mysqli("localhost", "root", "", "pennywise_db");
        if ($mysqli->connect_errno) {
            die("Failed to connect to MySQL: " . $mysqli->connect_error);
        }
        $stmt = $mysqli->prepare("INSERT INTO sessions (session_id, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $_SESSION['session_id']);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();
    }
if (!isset($_SESSION['accounts'])) $_SESSION['accounts'] = [];
if (!isset($_SESSION['categories'])) $_SESSION['categories'] = [];
if (!isset($_SESSION['records'])) $_SESSION['records'] = [];

// Helper: get POST data
function getPostData() {
    return json_decode(file_get_contents('php://input'), true);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_accounts':
        echo json_encode($_SESSION['accounts']);
        break;
    case 'add_account':
        $data = getPostData();
        $account = [
        'id' => uniqid(),
        'name' => $data['name'],
        'balance' => floatval($data['balance'])
        ];
        $_SESSION['accounts'][] = $account;
        echo json_encode($account);
        break;
    case 'get_categories':
        echo json_encode($_SESSION['categories']);
        break;
    case 'add_category':
        $data = getPostData();
        $category = [
        'id' => uniqid(),
        'name' => $data['name'],
        'type' => $data['type']
        ];
        $_SESSION['categories'][] = $category;
        echo json_encode($category);
        break;
    case 'get_records':
        echo json_encode($_SESSION['records']);
        break;
    case 'add_record':
        $data = getPostData();
        $record = [
        'id' => uniqid(),
        'type' => $data['type'],
        'from' => $data['from'],
        'to' => $data['to'],
        'amount' => floatval($data['amount']),
        'date' => date('Y-m-d H:i:s')
        ];
        $_SESSION['records'][] = $record;
        echo json_encode($record);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

// FALLBACK
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
?>