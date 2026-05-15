<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getAllRecords() {
    $user   = currentUser();
    $userId = $user['id'];

    $stmt = db()->prepare("
        SELECT
            'expense' AS type,
            e.id,
            e.amount,
            e.date,
            e.description,
            ec.name AS category,
            a.name  AS account
        FROM expenses e
        JOIN expense_categories ec ON e.category_id = ec.id
        JOIN accounts a            ON e.account_id  = a.id
        WHERE e.user_id = ?

        UNION ALL

        SELECT
            'income' AS type,
            i.id,
            i.amount,
            i.date,
            i.description,
            ic.name AS category,
            a.name  AS account
        FROM income i
        JOIN income_categories ic ON i.category_id = ic.id
        JOIN accounts a           ON i.account_id  = a.id
        WHERE i.user_id = ?

        UNION ALL

        SELECT
            'transfer' AS type,
            t.id,
            t.amount,
            t.date,
            t.description,
            NULL        AS category,
            a.name      AS account
        FROM transfers t
        JOIN accounts a ON t.from_account_id = a.id
        WHERE t.user_id = ?

        ORDER BY date DESC
    ");

    $stmt->execute([$userId, $userId, $userId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json([
        'status' => 'success',
        'data'   => $records
    ]);
}