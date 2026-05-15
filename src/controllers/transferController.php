<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getTransfers() {
    $user   = currentUser();
    $userId = $user['id'];

    $stmt = db()->prepare("
        SELECT
            t.id,
            t.amount,
            t.description,
            t.date,
            fa.name AS from_account,
            ta.name AS to_account
        FROM transfers t
        JOIN accounts fa ON t.from_account_id = fa.id
        JOIN accounts ta ON t.to_account_id   = ta.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC
    ");
    $stmt->execute([$userId]);

    json([
        'status' => 'success',
        'data'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function createTransfer() {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (!isset($input['from_account_id'], $input['to_account_id'], $input['amount'], $input['date'])) {
        json(['status' => 'error', 'message' => 'Missing required fields: from_account_id, to_account_id, amount, date'], 400);
    }

    if ($input['from_account_id'] == $input['to_account_id']) {
        json(['status' => 'error', 'message' => 'Cannot transfer to the same account'], 400);
    }

    if (!validateAmount($input['amount'])) {
        json(['status' => 'error', 'message' => 'Amount must be a positive number'], 400);
    }

    $pdo = db();

    // Verify both accounts belong to this user
    $stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE id = ? AND user_id = ?");

    $stmt->execute([$input['from_account_id'], $userId]);
    $fromAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$fromAccount) {
        json(['status' => 'error', 'message' => 'Source account not found'], 404);
    }

    $stmt->execute([$input['to_account_id'], $userId]);
    $toAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$toAccount) {
        json(['status' => 'error', 'message' => 'Destination account not found'], 404);
    }

    // Check sufficient balance in source account
    if ($fromAccount['balance'] < $input['amount']) {
        json(['status' => 'error', 'message' => 'Insufficient balance in source account'], 400);
    }

    try {
        $pdo->beginTransaction();

        // Deduct from source account
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['amount'], $input['from_account_id'], $userId]);

        // Add to destination account
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['amount'], $input['to_account_id'], $userId]);

        // Insert transfer record
        $stmt = $pdo->prepare("
            INSERT INTO transfers (user_id, from_account_id, to_account_id, amount, description, date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $input['from_account_id'],
            $input['to_account_id'],
            $input['amount'],
            $input['description'] ?? null,
            $input['date']
        ]);

        $transferId = $pdo->lastInsertId();

        $pdo->commit();

        json([
            'status'  => 'success',
            'message' => 'Transfer successful',
            'data'    => ['id' => (int) $transferId]
        ], 201);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Transfer failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Transfer failed. Please try again.'], 500);
    }
}

function updateTransfer($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (!$input) {
        json(['status' => 'error', 'message' => 'Invalid input'], 400);
    }

    // Fetch the existing transfer to verify ownership and get current values
    $stmt = db()->prepare("SELECT * FROM transfers WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $transfer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transfer) {
        json(['status' => 'error', 'message' => 'Transfer not found'], 404);
    }

    // Only allow updating description and date — changing accounts or amount
    // would require reversing and reapplying balance changes which risks
    // inconsistency. For those cases, delete and recreate the transfer.
    $fields = [];
    $values = [];
    $allowed = ['description', 'date'];

    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $values[] = $input[$field];
        }
    }

    if (empty($fields)) {
        json([
            'status'  => 'error',
            'message' => 'Only description and date can be updated. To change amount or accounts, delete and recreate the transfer.'
        ], 400);
    }

    $values[] = $id;
    $values[] = $userId;

    $stmt = db()->prepare("UPDATE transfers SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
    $stmt->execute($values);

    json(['status' => 'success', 'message' => 'Transfer updated']);
}

function deleteTransfer($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $pdo    = db();

    // Fetch the transfer to verify ownership and get amounts for balance reversal
    $stmt = $pdo->prepare("SELECT * FROM transfers WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $transfer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transfer) {
        json(['status' => 'error', 'message' => 'Transfer not found'], 404);
    }

    try {
        $pdo->beginTransaction();

        // Reverse the balance changes — add back to source, deduct from destination
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$transfer['amount'], $transfer['from_account_id'], $userId]);

        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$transfer['amount'], $transfer['to_account_id'], $userId]);

        // Delete the transfer record
        $stmt = $pdo->prepare("DELETE FROM transfers WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        $pdo->commit();

        json(['status' => 'success', 'message' => 'Transfer deleted and balances reversed']);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Transfer deletion failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Could not delete transfer. Please try again.'], 500);
    }
}