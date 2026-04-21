<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getIncome() {
    $stmt = db()->query("SELECT * FROM income ORDER BY date DESC");
    json($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createIncome() {
    $input = getJsonInput();

    if (!isset($input['amount'], $input['account_id'], $input['category_id'], $input['date'])) {
        json(['error' => 'Missing fields'], 400);
    }

    $stmt = db()->prepare("
        INSERT INTO income (user_id, account_id, category_id, amount, date)
        VALUES (1, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $input['account_id'],
        $input['category_id'],
        $input['amount'],
        $input['date']
    ]);

    json(['message' => 'Income created']);
}

function deleteIncome($id) {
    $stmt = db()->prepare("DELETE FROM income WHERE id = ?");
    $stmt->execute([$id]);

    json(['message' => 'Deleted']);
}

function updateIncome($id) {
    $input = getJsonInput();

    if (!$input) {
        json(['error' => 'Invalid input'], 400);
    }

    $fields = [];
    $values = [];

    $allowed = ['amount', 'account_id', 'category_id', 'date'];

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

    $sql = "UPDATE income SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute($values);

    json(['message' => 'Income updated']);
}
?>