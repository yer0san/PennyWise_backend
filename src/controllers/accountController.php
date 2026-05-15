<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getAccounts() {
    $user   = currentUser();
    $userId = $user['id'];

    $stmt = db()->prepare("
        SELECT id, name, balance, created_at
        FROM accounts
        WHERE user_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId]);

    json([
        'status' => 'success',
        'data'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function getAccount($id) {
    $user   = currentUser();
    $userId = $user['id'];

    $stmt = db()->prepare("
        SELECT id, name, balance, created_at
        FROM accounts
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$id, $userId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        json(['status' => 'error', 'message' => 'Account not found'], 404);
    }

    json(['status' => 'success', 'data' => $account]);
}

function createAccount() {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (isEmpty($input['name'] ?? '')) {
        json(['status' => 'error', 'message' => 'Account name is required'], 400);
    }

    if (!validateText($input['name'], 1, 100)) {
        json(['status' => 'error', 'message' => 'Account name must be between 1 and 100 characters'], 400);
    }

    // Initial balance is optional, defaults to 0
    $balance = $input['balance'] ?? 0;
    if (!is_numeric($balance) || $balance < 0) {
        json(['status' => 'error', 'message' => 'Balance must be a non-negative number'], 400);
    }

    // Prevent duplicate account names per user
    $stmt = db()->prepare("SELECT id FROM accounts WHERE user_id = ? AND name = ?");
    $stmt->execute([$userId, trim($input['name'])]);
    if ($stmt->fetch()) {
        json(['status' => 'error', 'message' => 'An account with that name already exists'], 409);
    }

    $stmt = db()->prepare("
        INSERT INTO accounts (user_id, name, balance)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, sanitize($input['name']), $balance]);

    $accountId = db()->lastInsertId();

    json([
        'status'  => 'success',
        'message' => 'Account created',
        'data'    => ['id' => (int) $accountId, 'name' => sanitize($input['name']), 'balance' => (float) $balance]
    ], 201);
}

function updateAccount($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (!$input) {
        json(['status' => 'error', 'message' => 'Invalid input'], 400);
    }

    // Verify account belongs to this user
    $stmt = db()->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        json(['status' => 'error', 'message' => 'Account not found'], 404);
    }

    $fields = [];
    $values = [];

    if (isset($input['name'])) {
        if (!validateText($input['name'], 1, 100)) {
            json(['status' => 'error', 'message' => 'Account name must be between 1 and 100 characters'], 400);
        }
        // Check for duplicate name (excluding current account)
        $stmt = db()->prepare("SELECT id FROM accounts WHERE user_id = ? AND name = ? AND id != ?");
        $stmt->execute([$userId, trim($input['name']), $id]);
        if ($stmt->fetch()) {
            json(['status' => 'error', 'message' => 'An account with that name already exists'], 409);
        }
        $fields[] = 'name = ?';
        $values[] = sanitize($input['name']);
    }

    if (isset($input['balance'])) {
        if (!is_numeric($input['balance']) || $input['balance'] < 0) {
            json(['status' => 'error', 'message' => 'Balance must be a non-negative number'], 400);
        }
        $fields[] = 'balance = ?';
        $values[] = $input['balance'];
    }

    if (empty($fields)) {
        json(['status' => 'error', 'message' => 'No fields to update'], 400);
    }

    $values[] = $id;
    $values[] = $userId;

    $stmt = db()->prepare("UPDATE accounts SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
    $stmt->execute($values);

    json(['status' => 'success', 'message' => 'Account updated']);
}

function deleteAccount($id) {
    $user   = currentUser();
    $userId = $user['id'];

    // Verify account belongs to this user
    $stmt = db()->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        json(['status' => 'error', 'message' => 'Account not found'], 404);
    }

    // Block deletion if account has linked expenses or income
    // (schema uses ON DELETE CASCADE but we warn the user explicitly)
    $stmt = db()->prepare("SELECT COUNT(*) FROM expenses WHERE account_id = ?");
    $stmt->execute([$id]);
    $expenseCount = $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT COUNT(*) FROM income WHERE account_id = ?");
    $stmt->execute([$id]);
    $incomeCount = $stmt->fetchColumn();

    if ($expenseCount > 0 || $incomeCount > 0) {
        json([
            'status'  => 'error',
            'message' => 'Cannot delete account with existing transactions. Reassign or delete them first.'
        ], 409);
    }

    $stmt = db()->prepare("DELETE FROM accounts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json(['status' => 'success', 'message' => 'Account deleted']);
}