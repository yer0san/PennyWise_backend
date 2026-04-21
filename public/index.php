<?php
require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/controllers/expenseController.php';
require_once __DIR__ . '/../src/controllers/incomeController.php';
require_once __DIR__ . '/../src/controllers/transferController.php';
require_once __DIR__ . '/../src/controllers/debtController.php';
require_once __DIR__ . '/../src/controllers/budgetController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// EXPENSE ROUTES
if ($uri === '/expenses' && $method === 'GET') {
    getExpenses();
    exit;
}

if ($uri === '/expenses' && $method === 'POST') {
    createExpense();
    exit;
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteExpense($matches[1]);
    exit;
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    updateExpense($matches[1]);
    exit;
}

// INCOME ROUTES
if ($uri === '/income' && $method === 'GET') {
    getIncome();
    exit;
}

if ($uri === '/income' && $method === 'POST') {
    createIncome();
    exit;
}

if (preg_match('#^/income/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteIncome($matches[1]);
    exit;
}
if (preg_match('#^/income/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    updateIncome($matches[1]);
    exit;
}

if (preg_match('#^/expenses/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    updateExpense($matches[1]);
    exit;
}

// DEBT ROUTES
if ($uri === '/debts' && $method === 'GET') {
    getDebts();
    exit;
}

if ($uri === '/debts' && $method === 'POST') {
    createDebt();
    exit;
}

if (preg_match('#^/debts/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteDebt($matches[1]);
    exit;
}

if (preg_match('#^/debts/(\d+)$#', $uri, $matches) &&
    ($method === 'PUT' || $method === 'PATCH')) {
    updateDebt($matches[1]);
    exit;
}

// TRANSFER ROUTES
if ($uri === '/transfers' && $method === 'GET') {
    getTransfers();
    exit;
}

if ($uri === '/transfers' && $method === 'POST') {
    createTransfer();
    exit;
}

if (preg_match('#^/transfers/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteTransfer($matches[1]);
    exit;
}

if (preg_match('#^/transfers/(\d+)$#', $uri, $matches) && 
    ($method === 'PUT' || $method === 'PATCH')) {
    updateTransfer($matches[1]);
    exit;
}   
// BUDGET ROUTES
if ($uri === '/budgets' && $method === 'GET') {
    getBudgets();
    exit;
}

if ($uri === '/budgets' && $method === 'POST') {
    createBudget();
    exit;
}

if (preg_match('#^/budgets/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteBudget($matches[1]);
    exit;
}

if (preg_match('#^/budgets/(\d+)$#', $uri, $matches) &&
    ($method === 'PUT' || $method === 'PATCH')) {
    updateBudget($matches[1]);
    exit;
}

// SAVINGS GOAL ROUTES
if ($uri === '/goals' && $method === 'GET') {
    getSavingsGoals();
    exit;
}

if ($uri === '/goals' && $method === 'POST') {
    createSavingsGoal();
    exit;
}

if (preg_match('#^/goals/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    deleteSavingsGoal($matches[1]);
    exit;
}

if (preg_match('#^/goals/(\d+)$#', $uri, $matches) &&
    ($method === 'PUT' || $method === 'PATCH')) {
    updateSavingsGoal($matches[1]);
    exit;
}

if (preg_match('#^/goals/(\d+)/contributions$#', $uri, $matches) && $method === 'GET') {
    getGoalContributions($matches[1]);
    exit;
}

if (preg_match('#^/goals/(\d+)/contributions$#', $uri, $matches) && $method === 'POST') {
    createGoalContribution($matches[1]);
    exit;
}

// FALLBACK
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
