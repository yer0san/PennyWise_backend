<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function normalizeDebtId($id): int {
    $id = (int) $id;
    if ($id <= 0) {
        json(['error' => 'Invalid id'], 400);
    }
    return $id;
}

function normalizeDebtName($value, string $fieldName): string {
    if (!is_string($value)) {
        json(['error' => "$fieldName must be a string"], 400);
    }
    $value = trim($value);
    if ($value === '') {
        json(['error' => "$fieldName cannot be empty"], 400);
    }
    if (mb_strlen($value) > 255) {
        json(['error' => "$fieldName is too long"], 400);
    }
    return $value;
}

function normalizeDebtAmount($value, string $fieldName): float {
    if (!is_numeric($value)) {
        json(['error' => "$fieldName must be a number"], 400);
    }
    $n = (float) $value;
    if (!is_finite($n) || $n < 0) {
        json(['error' => "$fieldName must be >= 0"], 400);
    }
    return round($n, 2);
}

function normalizeDebtDate($value, string $fieldName): ?string {
    if ($value === null) {
        return null;
    }
    if (!is_string($value)) {
        json(['error' => "$fieldName must be a string (YYYY-MM-DD) or null"], 400);
    }
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    $errors = DateTime::getLastErrors();
    if ($dt === false || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
        json(['error' => "$fieldName must be YYYY-MM-DD"], 400);
    }
    return $dt->format('Y-m-d');
}

function normalizeDebtDescription($value): ?string {
    if ($value === null) {
        return null;
    }
    if (!is_string($value)) {
        json(['error' => "description must be a string or null"], 400);
    }
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    return $value;
}

function getDebts() {
    $stmt = db()->query("SELECT * FROM debts ORDER BY due_date IS NULL, due_date ASC, created_at DESC");
    json($stmt->fetchAll());
}

function createDebt() {
    $input = getJsonInput();

    if (!isset($input['creditor_name'], $input['total_amount'])) {
        json(['error' => 'Missing fields'], 400);
    }

    $creditorName = normalizeDebtName($input['creditor_name'], 'creditor_name');
    $total = normalizeDebtAmount($input['total_amount'], 'total_amount');
    $remaining = array_key_exists('remaining_amount', $input)
        ? normalizeDebtAmount($input['remaining_amount'], 'remaining_amount')
        : $total;

    if ($remaining > $total) {
        json(['error' => 'remaining_amount cannot be greater than total_amount'], 400);
    }

    $dueDate = array_key_exists('due_date', $input)
        ? normalizeDebtDate($input['due_date'], 'due_date')
        : null;

    $description = array_key_exists('description', $input)
        ? normalizeDebtDescription($input['description'])
        : null;

    $stmt = db()->prepare("
        INSERT INTO debts (user_id, creditor_name, total_amount, remaining_amount, due_date, description)
        VALUES (1, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $creditorName,
        $total,
        $remaining,
        $dueDate,
        $description
    ]);

    json(['message' => 'Debt created']);
}

function deleteDebt($id) {
    $id = normalizeDebtId($id);
    $stmt = db()->prepare("DELETE FROM debts WHERE id = ?");
    $stmt->execute([$id]);

    json(['message' => 'Deleted']);
}

function updateDebt($id) {
    $id = normalizeDebtId($id);
    $input = getJsonInput();

    if (!$input) {
        json(['error' => 'Invalid input'], 400);
    }

    $fields = [];
    $values = [];

    $allowed = ['creditor_name', 'total_amount', 'remaining_amount', 'due_date', 'description'];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $fields[] = "$field = ?";
            if ($field === 'creditor_name') {
                $values[] = normalizeDebtName($input[$field], 'creditor_name');
                continue;
            }
            if ($field === 'total_amount') {
                $values[] = normalizeDebtAmount($input[$field], 'total_amount');
                continue;
            }
            if ($field === 'remaining_amount') {
                $values[] = normalizeDebtAmount($input[$field], 'remaining_amount');
                continue;
            }
            if ($field === 'due_date') {
                $values[] = normalizeDebtDate($input[$field], 'due_date');
                continue;
            }
            if ($field === 'description') {
                $values[] = normalizeDebtDescription($input[$field]);
                continue;
            }
            $values[] = $input[$field];
        }
    }

    if (empty($fields)) {
        json(['error' => 'No fields to update'], 400);
    }

    // If either amount is changing, enforce remaining_amount <= total_amount using current DB values.
    $changingTotal = array_key_exists('total_amount', $input);
    $changingRemaining = array_key_exists('remaining_amount', $input);
    if ($changingTotal || $changingRemaining) {
        $stmt = db()->prepare("SELECT total_amount, remaining_amount FROM debts WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            json(['error' => 'Debt not found'], 404);
        }
        $newTotal = $changingTotal ? normalizeDebtAmount($input['total_amount'], 'total_amount') : (float) $current['total_amount'];
        $newRemaining = $changingRemaining ? normalizeDebtAmount($input['remaining_amount'], 'remaining_amount') : (float) $current['remaining_amount'];
        if ($newRemaining > $newTotal) {
            json(['error' => 'remaining_amount cannot be greater than total_amount'], 400);
        }
    }

    $values[] = $id;

    $sql = "UPDATE debts SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute($values);

    json(['message' => 'Debt updated']);
}
