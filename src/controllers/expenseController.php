<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getExpenses() {
    $user   = currentUser();
    $userId = $user['id'];

    $stmt = db()->prepare("
        SELECT e.id, e.amount, e.description, e.date,
               ec.name AS category, a.name AS account
        FROM expenses e
        JOIN expense_categories ec ON e.category_id = ec.id
        JOIN accounts a            ON e.account_id  = a.id
        WHERE e.user_id = ?
        ORDER BY e.date DESC
    ");
    $stmt->execute([$userId]);

    json([
        'status' => 'success',
        'data'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function createExpense() {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (!isset($input['amount'], $input['account_id'], $input['category_id'], $input['date'])) {
        json(['status' => 'error', 'message' => 'Missing required fields: amount, account_id, category_id, date'], 400);
    }

    if (!validateAmount($input['amount'])) {
        json(['status' => 'error', 'message' => 'Amount must be a positive number'], 400);
    }

    $pdo = db();

    // Verify account belongs to this user and check balance
    $stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE id = ? AND user_id = ?");
    $stmt->execute([$input['account_id'], $userId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$account) {
        json(['status' => 'error', 'message' => 'Account not found'], 404);
    }

    if ($account['balance'] < $input['amount']) {
        json(['status' => 'error', 'message' => 'Insufficient balance in account'], 400);
    }

    // Verify category belongs to this user
    $stmt = $pdo->prepare("SELECT id FROM expense_categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$input['category_id'], $userId]);
    if (!$stmt->fetch()) {
        json(['status' => 'error', 'message' => 'Category not found'], 404);
    }

    try {
        $pdo->beginTransaction();

        // Deduct from account balance
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['amount'], $input['account_id'], $userId]);

        // Insert expense
        $stmt = $pdo->prepare("
            INSERT INTO expenses (user_id, account_id, category_id, amount, description, date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $input['account_id'],
            $input['category_id'],
            $input['amount'],
            $input['description'] ?? null,
            $input['date']
        ]);

        $pdo->commit();

        json(['status' => 'success', 'message' => 'Expense created'], 201);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Create expense failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Could not create expense. Please try again.'], 500);
    }
}

function updateExpense($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (!$input) {
        json(['status' => 'error', 'message' => 'Invalid input'], 400);
    }

    $pdo = db();

    // Fetch existing expense to verify ownership and get current values for balance diff
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        json(['status' => 'error', 'message' => 'Expense not found'], 404);
    }

    if (isset($input['amount']) && !validateAmount($input['amount'])) {
        json(['status' => 'error', 'message' => 'Amount must be a positive number'], 400);
    }

    $fields = [];
    $values = [];
    $allowed = ['amount', 'account_id', 'category_id', 'description', 'date'];

    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $values[] = $input[$field];
        }
    }

    if (empty($fields)) {
        json(['status' => 'error', 'message' => 'No fields to update'], 400);
    }

    try {
        $pdo->beginTransaction();

        // If amount changed, adjust account balance by the difference
        if (isset($input['amount']) && $input['amount'] != $expense['amount']) {
            $oldAmount = $expense['amount'];
            $newAmount = $input['amount'];
            $accountId = $input['account_id'] ?? $expense['account_id'];

            // If new amount is higher, check account has enough for the extra
            if ($newAmount > $oldAmount) {
                $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
                $stmt->execute([$accountId, $userId]);
                $account    = $stmt->fetch(PDO::FETCH_ASSOC);
                $extraNeeded = $newAmount - $oldAmount;
                if ($account['balance'] < $extraNeeded) {
                    $pdo->rollBack();
                    json(['status' => 'error', 'message' => 'Insufficient balance for this update'], 400);
                }
            }

            // Reverse old, apply new: balance + oldAmount - newAmount
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? - ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$oldAmount, $newAmount, $accountId, $userId]);
        }

        $values[] = $id;
        $values[] = $userId;

        $stmt = $pdo->prepare("UPDATE expenses SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
        $stmt->execute($values);

        $pdo->commit();

        json(['status' => 'success', 'message' => 'Expense updated']);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Update expense failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Could not update expense. Please try again.'], 500);
    }
}

function deleteExpense($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $pdo    = db();

    // Fetch expense to verify ownership and get amount for balance reversal
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        json(['status' => 'error', 'message' => 'Expense not found'], 404);
    }

    try {
        $pdo->beginTransaction();

        // Refund the amount back to the account
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$expense['amount'], $expense['account_id'], $userId]);

        // Delete the expense
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        $pdo->commit();

        json(['status' => 'success', 'message' => 'Expense deleted and balance restored']);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Delete expense failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Could not delete expense. Please try again.'], 500);
    }
}