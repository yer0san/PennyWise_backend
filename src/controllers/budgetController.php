<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

const BUDGET_USER_ID = 1;

function budgetCategoryKey(?int $expenseCategoryId): int {
    return $expenseCategoryId === null ? 0 : $expenseCategoryId;
}

function getBudgets() {
    $year = isset($_GET['year']) ? (int) $_GET['year'] : null;
    $month = isset($_GET['month']) ? (int) $_GET['month'] : null;

    if ($month !== null && ($month < 1 || $month > 12)) {
        json(['error' => 'month must be 1–12'], 400);
    }

    $sql = "
        SELECT
            b.id,
            b.user_id,
            b.year,
            b.month,
            b.expense_category_id,
            b.category_key,
            b.amount_limit,
            b.created_at,
            COALESCE((
                SELECT SUM(e.amount)
                FROM expenses e
                WHERE e.user_id = b.user_id
                  AND YEAR(e.date) = b.year
                  AND MONTH(e.date) = b.month
                  AND (
                      b.category_key = 0
                      OR e.category_id = b.expense_category_id
                  )
            ), 0) AS spent
        FROM budgets b
        WHERE b.user_id = ?
    ";
    $params = [BUDGET_USER_ID];

    if ($year !== null) {
        $sql .= " AND b.year = ?";
        $params[] = $year;
    }
    if ($month !== null) {
        $sql .= " AND b.month = ?";
        $params[] = $month;
    }

    $sql .= " ORDER BY b.year DESC, b.month DESC, b.category_key ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $spent = (float) $row['spent'];
        $limit = (float) $row['amount_limit'];
        $row['spent'] = round($spent, 2);
        $row['remaining'] = round($limit - $spent, 2);
        $row['over_budget'] = $spent > $limit;
    }
    unset($row);

    json($rows);
}

function createBudget() {
    $input = getJsonInput();

    if (!$input) {
        json(['error' => 'Invalid JSON input'], 400);
    }

    // Required fields
    if (!isset($input['year'], $input['month'], $input['amount_limit'])) {
        json(['error' => 'Missing required fields'], 400);
    }

    // Sanitize and Validate
    $year = (int) sanitize($input['year']);
    $month = (int) sanitize($input['month']);
    $amountLimit = sanitize($input['amount_limit']);

    if ($year < 2000) {
        json(['error' => 'Invalid year'], 400);
    }
    if ($month < 1 || $month > 12) {
        json(['error' => 'month must be 1–12'], 400);
    }
    //Optional Category
    $categoryId = null;
    if (array_key_exists('expense_category_id', $input) && $input['expense_category_id'] !== null) {
        $categoryId = (int) $input['expense_category_id'];
    }

    $categoryKey = budgetCategoryKey($categoryId);

    try {
        $stmt = db()->prepare("
            INSERT INTO budgets (user_id, year, month, expense_category_id, category_key, amount_limit)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            BUDGET_USER_ID,
            $year,
            $month,
            $categoryId,
            $categoryKey,
            $amountLimit
        ]);
    } catch (PDOException $e) {
        if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
            json(['error' => 'Budget already exists for this month and category scope'], 409);
        }
        throw $e;
    }

    json(['message' => 'Budget created']);
}

function deleteBudget($id) {
    $stmt = db()->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, BUDGET_USER_ID]);

    json(['message' => 'Deleted']);
}

function updateBudget($id) {
    $input = getJsonInput();

    if (!$input) {
        json(['error' => 'Invalid input'], 400);
    }

    $fields = [];
    $values = [];


    if (isset($input['amount_limit'])) {
        $fields[] = 'amount_limit = ?';
        $values[] = $input['amount_limit'];
    }
    if (isset($input['year'])) {
        $fields[] = 'year = ?';
        $values[] = (int) $input['year'];
    }
    if (isset($input['month'])) {
        $m = (int) $input['month'];
        if ($m < 1 || $m > 12) {
            json(['error' => 'month must be 1–12'], 400);
        }
        $fields[] = 'month = ?';
        $values[] = $m;
    }
    if (array_key_exists('expense_category_id', $input)) {
        $cat = $input['expense_category_id'];
        $categoryId = $cat === null ? null : (int) $cat;
        $fields[] = 'expense_category_id = ?';
        $values[] = $categoryId;
        $fields[] = 'category_key = ?';
        $values[] = budgetCategoryKey($categoryId);
    }

    if (empty($fields)) {
        json(['error' => 'No fields to update'], 400);
    }

    $values[] = $id;
    $values[] = BUDGET_USER_ID;

    $sql = 'UPDATE budgets SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($values);
    } catch (PDOException $e) {
        if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
            json(['error' => 'Budget already exists for this month and category scope'], 409);
        }
        throw $e;
    }

    json(['message' => 'Budget updated']);
}
