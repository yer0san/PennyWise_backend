<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getExpenses() {
    $stmt = db()->query("SELECT * FROM expenses ORDER BY date DESC");
    json($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createExpense() {
    $input = getJsonInput();

    if (!isset($input['amount'], $input['account_id'], $input['category_id'], $input['date'])) {
        json(['error' => 'Missing fields'], 400);
    }

    $stmt = db()->prepare("
        INSERT INTO expenses (user_id, account_id, category_id, amount, description, date)
        VALUES (1, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $input['account_id'],
        $input['category_id'],
        $input['amount'],
        $input['description'] ?? null,
        $input['date']
    ]);

    json(['message' => 'Expense created']);
}

function deleteExpense($id) {
    $stmt = db()->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);

    json(['message' => 'Deleted']);
}

function updateExpense($id) {
    $input = getJsonInput();

    if (!$input) {
        json(['error' => 'Invalid input'], 400);
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
        json(['error' => 'No fields to update'], 400);
    }

    $values[] = $id;

    $sql = "UPDATE expenses SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute($values);

    json(['message' => 'Expense updated']);
}