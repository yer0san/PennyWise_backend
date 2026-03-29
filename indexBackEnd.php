<?php


header('Content-Type: application/json');

// Simple router
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Example: handle GET /api/expenses and POST /api/expenses
$dsn = 'mysql:host=localhost;dbname=pennywise;charset=utf8mb4';
$username = 'your_db_user';
$password = 'your_db_password';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($requestMethod === 'GET') {
    $stmt = $pdo->query('SELECT id, description, amount, date FROM expenses ORDER BY date DESC');
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($expenses);
    exit;
} elseif ($requestMethod === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['description'], $input['amount'], $input['date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO expenses (description, amount, date) VALUES (?, ?, ?)');
    $stmt->execute([
        $input['description'],
        $input['amount'],
        $input['date']
    ]);
    $input['id'] = $pdo->lastInsertId();
    echo json_encode($input);
    exit;
}
if ($requestUri === '/api/expenses') {
    if ($requestMethod === 'GET') {
        // Fetch expenses (dummy data for example)
        $expenses = [
            ['id' => 1, 'description' => 'Coffee', 'amount' => 3.5, 'date' => '2024-06-01'],
            ['id' => 2, 'description' => 'Groceries', 'amount' => 45.0, 'date' => '2024-06-02'],
        ];
        echo json_encode($expenses);
        exit;
    } elseif ($requestMethod === 'POST') {
        // Add a new expense (dummy implementation)
        $input = json_decode(file_get_contents('php://input'), true);
        // Normally, you would validate and save to a database
        $input['id'] = rand(100, 999); // Simulate DB ID
        echo json_encode($input);
        exit;
    }
}

// 404 Not Found for other routes
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
exit;