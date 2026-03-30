<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getTransfers() {
    $stmt = db()->query("SELECT * FROM transfers ORDER BY date DESC");
    json($stmt->fetchAll());
}

function createTransfer() {
    $input = getJsonInput();

    if (!isset(
        $input['from_account_id'],
        $input['to_account_id'],
        $input['amount'],
        $input['date']
    )) {
        json(['error' => 'Missing fields'], 400);
    }

    if ($input['from_account_id'] == $input['to_account_id']) {
        json(['error' => 'Cannot transfer to the same account'], 400);
    }

    $pdo = db();

    try {
        $pdo->beginTransaction();

        // Deduct from source account
        $stmt = $pdo->prepare("
            UPDATE accounts SET balance = balance - ?
            WHERE id = ?
        ");
        $stmt->execute([
            $input['amount'],
            $input['from_account_id']
        ]);

        // Add to destination account
        $stmt = $pdo->prepare("
            UPDATE accounts SET balance = balance + ?
            WHERE id = ?
        ");
        $stmt->execute([
            $input['amount'],
            $input['to_account_id']
        ]);

        // Insert transfer record
        $stmt = $pdo->prepare("
            INSERT INTO transfers 
            (user_id, from_account_id, to_account_id, amount, description, date)
            VALUES (1, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $input['from_account_id'],
            $input['to_account_id'],
            $input['amount'],
            $input['description'] ?? null,
            $input['date']
        ]);

        $pdo->commit();

        json(['message' => 'Transfer successful']);

    } catch (Exception $e) {
        $pdo->rollBack();
        json(['error' => 'Transfer failed'], 500);
    }
}

function deleteTransfer($id) {
    $stmt = db()->prepare("DELETE FROM transfers WHERE id = ?");
    $stmt->execute([$id]);

    json(['message' => 'Deleted']);
}

function updateTransfer($id) {
    $input = getJsonInput();

    if (!$input) {
        json(['error' => 'Invalid input'], 400);
    }

    $fields = [];
    $values = [];

    $allowed = [
        'from_account_id',
        'to_account_id',
        'amount',
        'description',
        'date'
    ];

    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $values[] = $input[$field];
        }
    }

    if (empty($fields)) {
        json(['error' => 'No fields to update'], 400);
    }

    $values[] = $id;

    $sql = "UPDATE transfers SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute($values);

    json(['message' => 'Transfer updated']);
}