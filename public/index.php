<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/controllers/expenseController.php';
require_once __DIR__ . '/../src/controllers/incomeController.php';
require_once __DIR__ . '/../src/controllers/transferController.php';
require_once __DIR__ . '/../src/controllers/debtController.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if ($uri === '/login' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/login.php';
    exit;
}
// Register
if ($uri === '/register' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/register.php';
    exit;
}

// LOGOUT
if ($uri === '/logout' && $method === 'GET') {
    require __DIR__ . '/../src/registrationAndLogging/logout.php';
    exit;
}

// EMAIL VERIFICATION
if ($uri === '/verify' && $method === 'GET') {
    require __DIR__ . '/../src/registrationAndLogging/verify.php';
    exit;
}

// EXPENSE ROUTES
if ($uri === '/expenses' && $method === 'GET') {
    requireAuth();
    getExpenses();
    exit;
}

if ($uri === '/expenses' && $method === 'POST') {
    requireAuth();
    createExpense();
    exit;
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    requireAuth();
    deleteExpense($matches[1]);
    exit;
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    requireAuth();
    updateExpense($matches[1]);
    exit;
}

// INCOME ROUTES
if ($uri === '/income' && $method === 'GET') {
    requireAuth();
    getIncome();
    exit;
}

if ($uri === '/income' && $method === 'POST') {
    requireAuth();
    createIncome();
    exit;
}

if (preg_match('#^/income/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    requireAuth();
    deleteIncome($matches[1]);
    exit;
}

if (preg_match('#^/income/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    requireAuth();
    updateIncome($matches[1]);
    exit;
}

// DEBT ROUTES
if ($uri === '/debts' && $method === 'GET') {
    requireAuth();
    getDebts();
    exit;
}

if ($uri === '/debts' && $method === 'POST') {
    requireAuth();
    createDebt();
    exit;
}

if (preg_match('#^/debts/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    requireAuth();
    deleteDebt($matches[1]);
    exit;
}

if (preg_match('#^/debts/(\d+)$#', $uri, $matches) &&
    ($method === 'PUT' || $method === 'PATCH')) {
    requireAuth();
    updateDebt($matches[1]);
    exit;
}

// TRANSFER ROUTES
if ($uri === '/transfers' && $method === 'GET') {
    requireAuth();
    getTransfers();
    exit;
}

if ($uri === '/transfers' && $method === 'POST') {
    requireAuth();
    createTransfer();
    exit;
}

if (preg_match('#^/transfers/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    requireAuth();
    deleteTransfer($matches[1]);
    exit;
}

if (preg_match('#^/transfers/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    requireAuth();
    updateTransfer($matches[1]);
    exit;
}   
// BUDGET ROUTES
if ($uri === '/budgets' && $method === 'GET') {
    requireAuth();
    getBudgets();
    exit;
}

if ($uri === '/budgets' && $method === 'POST') {
    requireAuth();
    createBudget();
    exit;
}

if (preg_match('#^/budgets/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    requireAuth();
    deleteBudget($matches[1]);
    exit;
}

if (preg_match('#^/budgets/(\d+)$#', $uri, $matches) &&
    ($method === 'PUT' || $method === 'PATCH')) {
    requireAuth();
    updateBudget($matches[1]);
    exit;
}

// FALLBACK
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
