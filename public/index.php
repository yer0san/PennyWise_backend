<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/controllers/expenseController.php';
require_once __DIR__ . '/../src/controllers/incomeController.php';
require_once __DIR__ . '/../src/controllers/transferController.php';
require_once __DIR__ . '/../src/controllers/accountController.php';
require_once __DIR__ . '/../src/controllers/expenseCategoryController.php';
require_once __DIR__ . '/../src/controllers/incomeCategoryController.php';
require_once __DIR__ . '/../src/controllers/recordsController.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ── CORS 
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

// ── AUTH ROUTES (no session required)
if ($uri === '/login' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/login.php';
    exit;
}
if ($uri === '/register' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/register.php';
    exit;
}
if ($uri === '/logout' && $method === 'POST') {
    require __DIR__ . '/../src/registrationAndLogging/logout.php';
    exit;
}
if ($uri === '/verify' && $method === 'GET') {
    require __DIR__ . '/../src/registrationAndLogging/verify.php';
    exit;
}
if ($uri === '/check-auth' && $method === 'GET') {
    require __DIR__ . '/../src/registrationAndLogging/check_auth.php';
    exit;
}

// ── All routes below require a valid session 
requireAuth();

// ── RECORDS (unified feed of all transactions)
if ($uri === '/records' && $method === 'GET') {
    getAllRecords();
    exit;
}

// ── ACCOUNT ROUTES
if ($uri === '/accounts' && $method === 'GET') {
    getAccounts();
    exit;
}
if ($uri === '/accounts' && $method === 'POST') {
    createAccount();
    exit;
}
if (preg_match('#^/accounts/(\d+)$#', $uri, $matches)) {
    if ($method === 'GET')                          { getAccount($matches[1]);    exit; }
    if ($method === 'PUT' || $method === 'PATCH')   { updateAccount($matches[1]); exit; }
    if ($method === 'DELETE')                       { deleteAccount($matches[1]); exit; }
}

// ── EXPENSE CATEGORY ROUTES ───────────
if ($uri === '/expense-categories' && $method === 'GET') {
    getExpenseCategories();
    exit;
}
if ($uri === '/expense-categories' && $method === 'POST') {
    createExpenseCategory();
    exit;
}
if (preg_match('#^/expense-categories/(\d+)$#', $uri, $matches)) {
    if ($method === 'PUT' || $method === 'PATCH') { updateExpenseCategory($matches[1]); exit; }
    if ($method === 'DELETE')                     { deleteExpenseCategory($matches[1]); exit; }
}

// ── INCOME CATEGORY ROUTES 
if ($uri === '/income-categories' && $method === 'GET') {
    getIncomeCategories();
    exit;
}
if ($uri === '/income-categories' && $method === 'POST') {
    createIncomeCategory();
    exit;
}
if (preg_match('#^/income-categories/(\d+)$#', $uri, $matches)) {
    if ($method === 'PUT' || $method === 'PATCH') { updateIncomeCategory($matches[1]); exit; }
    if ($method === 'DELETE')                     { deleteIncomeCategory($matches[1]); exit; }
}

// ── EXPENSE ROUTES ────────
if ($uri === '/expenses' && $method === 'GET') {
    getExpenses();
    exit;
}
if ($uri === '/expenses' && $method === 'POST') {
    createExpense();
    exit;
}
if (preg_match('#^/expenses/(\d+)$#', $uri, $matches)) {
    if ($method === 'PUT' || $method === 'PATCH') { updateExpense($matches[1]); exit; }
    if ($method === 'DELETE')                     { deleteExpense($matches[1]); exit; }
}

// ── INCOME ROUTES ─────────
if ($uri === '/income' && $method === 'GET') {
    getIncome();
    exit;
}
if ($uri === '/income' && $method === 'POST') {
    createIncome();
    exit;
}
if (preg_match('#^/income/(\d+)$#', $uri, $matches)) {
    if ($method === 'PUT' || $method === 'PATCH') { updateIncome($matches[1]); exit; }
    if ($method === 'DELETE')                     { deleteIncome($matches[1]); exit; }
}

// ── TRANSFER ROUTES ───────
if ($uri === '/transfers' && $method === 'GET') {
    getTransfers();
    exit;
}
if ($uri === '/transfers' && $method === 'POST') {
    createTransfer();
    exit;
}
if (preg_match('#^/transfers/(\d+)$#', $uri, $matches)) {
    if ($method === 'PUT' || $method === 'PATCH') { updateTransfer($matches[1]); exit; }
    if ($method === 'DELETE')                     { deleteTransfer($matches[1]); exit; }
}

// ── FALLBACK ──
http_response_code(404);
echo json_encode(['status' => 'error', 'message' => 'Not Found']);