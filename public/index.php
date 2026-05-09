<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/controllers/expenseController.php';
require_once __DIR__ . '/../src/controllers/incomeController.php';
require_once __DIR__ . '/../src/controllers/transferController.php';
require_once __DIR__ . '/../src/controllers/debtController.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ── CORS ─────────────────────────────────────────────────────
// Allow requests from the frontend (Live Server)
$allowedOrigins = ['http://localhost:5500', 'http://127.0.0.1:5500'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// AUTH ROUTES
if ($uri === '/login' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/login.php';
    exit;
}

if ($uri === '/register' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/register.php';
    exit;
}

// Changed from GET to POST — GET-based logout is vulnerable to CSRF
if ($uri === '/logout' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/logout.php';
    exit;
}

if ($uri === '/verify' && $method === 'GET') {
    require __DIR__ . '/../src/registrationAndLogging/verify.php';
    exit;
}

// Lightweight session check — called by the frontend on every protected page load
if ($uri === '/check-auth' && $method === 'GET') {
    require __DIR__ . '/../src/registrationAndLogging/check_auth.php';
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
