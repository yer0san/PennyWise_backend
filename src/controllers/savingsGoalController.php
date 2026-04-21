<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function goalMetrics(float $target, float $current): array {
    $remaining = max(0, round($target - $current, 2));
    $percent = $target > 0 ? round(min(100, ($current / $target) * 100), 2) : 0.0;
    return [
        'remaining_amount' => $remaining,
        'progress_percent' => $percent,
        'is_completed' => $current >= $target && $target > 0
    ];
}

function appendGoalMetrics(array $goal): array {
    $target = (float) $goal['target_amount'];
    $current = (float) $goal['current_amount'];
    return array_merge($goal, goalMetrics($target, $current));
}

function getSavingsGoals() {
    $stmt = db()->query("SELECT * FROM savings_goals ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $enriched = array_map('appendGoalMetrics', $rows);
    json($enriched);
}

function createSavingsGoal() {
    $input = getJsonInput();
    if (!isset($input['name'], $input['target_amount'])) {
        json(['error' => 'Missing fields'], 400);
    }

    if (!is_string($input['name']) || trim($input['name']) === '') {
        json(['error' => 'name is required'], 400);
    }
    $name = trim($input['name']);

    if (!is_numeric($input['target_amount'])) {
        json(['error' => 'target_amount must be a number'], 400);
    }
    $target = round((float) $input['target_amount'], 2);
    if ($target <= 0) {
        json(['error' => 'target_amount must be greater than 0'], 400);
    }

    $current = 0.0;
    if (array_key_exists('current_amount', $input)) {
        if (!is_numeric($input['current_amount'])) {
            json(['error' => 'current_amount must be a number'], 400);
        }
        $current = round((float) $input['current_amount'], 2);
        if ($current < 0) {
            json(['error' => 'current_amount must be >= 0'], 400);
        }
    }
    if ($current > $target) {
        json(['error' => 'current_amount cannot be greater than target_amount'], 400);
    }

    $targetDate = null;
    if (array_key_exists('target_date', $input)) {
        if ($input['target_date'] !== null && (!is_string($input['target_date']) || trim($input['target_date']) === '')) {
            json(['error' => 'target_date must be YYYY-MM-DD or null'], 400);
        }
        if (is_string($input['target_date']) && trim($input['target_date']) !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', trim($input['target_date']));
            $errors = DateTime::getLastErrors();
            if ($dt === false || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
                json(['error' => 'target_date must be YYYY-MM-DD'], 400);
            }
            $targetDate = $dt->format('Y-m-d');
        }
    }

    $stmt = db()->prepare("
        INSERT INTO savings_goals (user_id, name, target_amount, current_amount, target_date)
        VALUES (1, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $target, $current, $targetDate]);

    json(['message' => 'Savings goal created']);
}

function updateSavingsGoal($id) {
    $id = (int) $id;
    if ($id <= 0) {
        json(['error' => 'Invalid id'], 400);
    }
    $input = getJsonInput();
    if (!$input) {
        json(['error' => 'Invalid input'], 400);
    }

    $fields = [];
    $values = [];


    if (array_key_exists('name', $input)) {
        if (!is_string($input['name']) || trim($input['name']) === '') {
            json(['error' => 'name cannot be empty'], 400);
        }
        $fields[] = "name = ?";
        $values[] = trim($input['name']);
    }
    if (array_key_exists('target_amount', $input)) {
        if (!is_numeric($input['target_amount'])) {
            json(['error' => 'target_amount must be a number'], 400);
        }
        $fields[] = "target_amount = ?";
        $values[] = round((float) $input['target_amount'], 2);
    }
    if (array_key_exists('current_amount', $input)) {
        if (!is_numeric($input['current_amount'])) {
            json(['error' => 'current_amount must be a number'], 400);
        }
        $fields[] = "current_amount = ?";
        $values[] = round((float) $input['current_amount'], 2);
    }
    if (array_key_exists('target_date', $input)) {
        $dateValue = null;
        if ($input['target_date'] !== null) {
            if (!is_string($input['target_date']) || trim($input['target_date']) === '') {
                json(['error' => 'target_date must be YYYY-MM-DD or null'], 400);
            }
            $dt = DateTime::createFromFormat('Y-m-d', trim($input['target_date']));
            $errors = DateTime::getLastErrors();
            if ($dt === false || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
                json(['error' => 'target_date must be YYYY-MM-DD'], 400);
            }
            $dateValue = $dt->format('Y-m-d');
        }
        $fields[] = "target_date = ?";
        $values[] = $dateValue;
    }

    if (empty($fields)) {
        json(['error' => 'No fields to update'], 400);
    }

    $check = db()->prepare("SELECT target_amount, current_amount FROM savings_goals WHERE id = ?");
    $check->execute([$id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        json(['error' => 'Savings goal not found'], 404);
    }

    $newTarget = array_key_exists('target_amount', $input)
        ? round((float) $input['target_amount'], 2)
        : (float) $existing['target_amount'];
    $newCurrent = array_key_exists('current_amount', $input)
        ? round((float) $input['current_amount'], 2)
        : (float) $existing['current_amount'];

    if ($newTarget <= 0) {
        json(['error' => 'target_amount must be greater than 0'], 400);
    }
    if ($newCurrent < 0) {
        json(['error' => 'current_amount must be >= 0'], 400);
    }
    if ($newCurrent > $newTarget) {
        json(['error' => 'current_amount cannot be greater than target_amount'], 400);
    }

    $values[] = $id;
    $sql = "UPDATE savings_goals SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute($values);

    json(['message' => 'Savings goal updated']);
}

function deleteSavingsGoal($id) {
    $id = (int) $id;
    if ($id <= 0) {
        json(['error' => 'Invalid id'], 400);
    }
    $stmt = db()->prepare("DELETE FROM savings_goals WHERE id = ?");
    $stmt->execute([$id]);
    json(['message' => 'Deleted']);
}

function getGoalContributions($goalId) {
    $goalId = (int) $goalId;
    if ($goalId <= 0) {
        json(['error' => 'Invalid id'], 400);
    }

    $goalStmt = db()->prepare("SELECT * FROM savings_goals WHERE id = ?");
    $goalStmt->execute([$goalId]);
    $goal = $goalStmt->fetch(PDO::FETCH_ASSOC);
    if (!$goal) {
        json(['error' => 'Savings goal not found'], 404);
    }

    $stmt = db()->prepare("SELECT * FROM goal_contributions WHERE goal_id = ? ORDER BY date DESC, id DESC");
    $stmt->execute([$goalId]);
    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json([
        'goal' => appendGoalMetrics($goal),
        'contributions' => $contributions
    ]);
}

function createGoalContribution($goalId) {
    $goalId = (int) $goalId;
    if ($goalId <= 0) {
        json(['error' => 'Invalid id'], 400);
    }
    $input = getJsonInput();


    if (!isset($input['account_id'], $input['amount'], $input['date'])) {
        json(['error' => 'Missing fields'], 400);
    }

    if (!is_numeric($input['account_id']) || (int) $input['account_id'] <= 0) {
        json(['error' => 'account_id must be a positive number'], 400);
    }
    $accountId = (int) $input['account_id'];

    if (!is_numeric($input['amount'])) {
        json(['error' => 'amount must be a number'], 400);
    }
    $amount = round((float) $input['amount'], 2);
    if ($amount <= 0) {
        json(['error' => 'amount must be greater than 0'], 400);
    }

    if (!is_string($input['date']) || trim($input['date']) === '') {
        json(['error' => 'date must be YYYY-MM-DD'], 400);
    }
    $dt = DateTime::createFromFormat('Y-m-d', trim($input['date']));
    $errors = DateTime::getLastErrors();
    if ($dt === false || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
        json(['error' => 'date must be YYYY-MM-DD'], 400);
    }
    $date = $dt->format('Y-m-d');
    $note = null;
    if (array_key_exists('note', $input)) {
        if ($input['note'] === null) {
            $note = null;
        } elseif (is_string($input['note'])) {
            $note = trim($input['note']);
            if ($note === '') {
                $note = null;
            }
        } else {
            json(['error' => 'note must be a string or null'], 400);
        }
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $goalStmt = $pdo->prepare("SELECT id, target_amount, current_amount FROM savings_goals WHERE id = ? FOR UPDATE");
        $goalStmt->execute([$goalId]);
        $goal = $goalStmt->fetch(PDO::FETCH_ASSOC);
        if (!$goal) {
            $pdo->rollBack();
            json(['error' => 'Savings goal not found'], 404);
        }

        $newCurrent = round(((float) $goal['current_amount']) + $amount, 2);
        if ($newCurrent > (float) $goal['target_amount']) {
            $pdo->rollBack();
            json(['error' => 'Contribution exceeds target amount'], 400);
        }

        $insert = $pdo->prepare("
            INSERT INTO goal_contributions (goal_id, account_id, amount, date, note)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insert->execute([$goalId, $accountId, $amount, $date, $note]);

        $update = $pdo->prepare("UPDATE savings_goals SET current_amount = ? WHERE id = ?");
        $update->execute([$newCurrent, $goalId]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json(['error' => 'Failed to add contribution'], 500);
    }

    json(['message' => 'Contribution added']);
}
