<?php

require_once __DIR__ . '/../src/controllers/expenseController.php';
require_once __DIR__ . '/../src/controllers/incomeController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// EXPENSE ROUTES
if ($uri === '/expenses' && $method === 'GET') {
    getExpenses();
}

if ($uri === '/expenses' && $method === 'POST') {
    createExpense();
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteExpense($matches[1]);
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    updateExpense($matches[1]);
}

// INCOME ROUTES
if ($uri === '/income' && $method === 'GET') {
    getIncome();
}

if ($uri === '/income' && $method === 'POST') {
    createIncome();
}

if (preg_match('#^/income/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteIncome($matches[1]);
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    updateExpense($matches[1]);
}

// FALLBACK
http_response_code(404);
echo json_encode(['error' => 'Not Found']);