<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getIncome() {
    $user   = currentUser();
    $userId = $user['id'];

    $stmt = db()->prepare("
        SELECT i.id, i.amount, i.description, i.date,
               ic.name AS category, a.name AS account
        FROM income i
        JOIN income_categories ic ON i.category_id = ic.id
        JOIN accounts a           ON i.account_id  = a.id
        WHERE i.user_id = ?
        ORDER BY i.date DESC
    ");
    $stmt->execute([$userId]);

    json([
        'status' => 'success',
        'data'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function createIncome() {
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

    // Verify account belongs to this user
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ?");
    $stmt->execute([$input['account_id'], $userId]);
    if (!$stmt->fetch()) {
        json(['status' => 'error', 'message' => 'Account not found'], 404);
    }

    // Verify category belongs to this user
    $stmt = $pdo->prepare("SELECT id FROM income_categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$input['category_id'], $userId]);
    if (!$stmt->fetch()) {
        json(['status' => 'error', 'message' => 'Category not found'], 404);
    }

    try {
        $pdo->beginTransaction();

        // Add to account balance
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['amount'], $input['account_id'], $userId]);

        // Insert income record
        $stmt = $pdo->prepare("
            INSERT INTO income (user_id, account_id, category_id, amount, description, date)
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

        json(['status' => 'success', 'message' => 'Income created'], 201);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Create income failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Could not create income. Please try again.'], 500);
    }
}

function updateIncome($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (!$input) {
        json(['status' => 'error', 'message' => 'Invalid input'], 400);
    }

    $pdo = db();

    // Fetch existing income to verify ownership and get current values for balance diff
    $stmt = $pdo->prepare("SELECT * FROM income WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $income = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$income) {
        json(['status' => 'error', 'message' => 'Income record not found'], 404);
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
        if (isset($input['amount']) && $input['amount'] != $income['amount']) {
            $oldAmount = $income['amount'];
            $newAmount = $input['amount'];
            $accountId = $input['account_id'] ?? $income['account_id'];

            // Reverse old, apply new: balance - oldAmount + newAmount
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? + ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$oldAmount, $newAmount, $accountId, $userId]);
        }

        $values[] = $id;
        $values[] = $userId;

        $stmt = $pdo->prepare("UPDATE income SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
        $stmt->execute($values);

        $pdo->commit();

        json(['status' => 'success', 'message' => 'Income updated']);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Update income failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Could not update income. Please try again.'], 500);
    }
}

function deleteIncome($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $pdo    = db();

    // Fetch income to verify ownership and get amount for balance reversal
    $stmt = $pdo->prepare("SELECT * FROM income WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $income = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$income) {
        json(['status' => 'error', 'message' => 'Income record not found'], 404);
    }

    try {
        $pdo->beginTransaction();

        // Deduct the income amount back from the account
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$income['amount'], $income['account_id'], $userId]);

        // Delete the income record
        $stmt = $pdo->prepare("DELETE FROM income WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        $pdo->commit();

        json(['status' => 'success', 'message' => 'Income deleted and balance adjusted']);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Delete income failed: ' . $e->getMessage());
        json(['status' => 'error', 'message' => 'Could not delete income. Please try again.'], 500);
    }
}