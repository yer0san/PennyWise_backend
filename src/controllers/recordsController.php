<?php

    require_once __DIR__ . '/../core/db.php';
    require_once __DIR__ . '/../utils.php';

    function getAllRecords() {
        $stmt = db()->query("
            SELECT 'expense' AS type, e.id, e.amount, e.date, c.name AS category, a.name AS account
            FROM expenses e
            JOIN categories c ON e.category_id = c.id
            JOIN accounts a ON e.account_id = a.id
            UNION ALL
            SELECT 'income' AS type, i.id, i.amount, i.date, c.name AS category, a.name AS account
            FROM income i
            JOIN categories c ON i.category_id = c.id
            JOIN accounts a ON i.account_id = a.id
            SELECT 'transfer' AS type, t.id, t.amount, t.date, NULL AS category, a.name AS account
            FROM transfers t
            JOIN accounts a ON t.from_account_id = a.id
            SELECT 'debt' AS type, d.id, d.amount, d.date, NULL AS category, a.name AS account
            FROM debts d
            JOIN accounts a ON d.account_id = a.id
            SELECT 'savings_goal' AS type, s.id, s.amount, s.target_date AS date, NULL AS category, NULL AS account
            FROM savings_goals s
            JOIN accounts a ON s.account_id = a.id
            SELECT 'budget' AS type, b.id, b.amount, b.due_date AS date, NULL AS category, NULL AS account
            FROM budgets b
            JOIN accounts a ON b.account_id = a.id
            ORDER BY date DESC
        ");
        json($stmt->fetchAll(PDO::FETCH_ASSOC));
        // export to csv
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $csv = "type,id,amount,date,category,account\n";
        foreach ($records as $record) {
            $csv .= "{$record['type']},{$record['id']},{$record['amount']},{$record['date']},{$record['category']},{$record['account']}\n";
        }
        file_put_contents(__DIR__ . '/../../exports/records.csv', $csv);
    }

?>