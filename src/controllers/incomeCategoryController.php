<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../utils.php';

function getIncomeCategories() {
    $user   = currentUser();
    $userId = $user['id'];

    $stmt = db()->prepare("
        SELECT id, name
        FROM income_categories
        WHERE user_id = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$userId]);

    json([
        'status' => 'success',
        'data'   => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function createIncomeCategory() {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    if (isEmpty($input['name'] ?? '')) {
        json(['status' => 'error', 'message' => 'Category name is required'], 400);
    }

    if (!validateText($input['name'], 1, 100)) {
        json(['status' => 'error', 'message' => 'Category name must be between 1 and 100 characters'], 400);
    }

    // Prevent duplicate category names per user
    $stmt = db()->prepare("SELECT id FROM income_categories WHERE user_id = ? AND name = ?");
    $stmt->execute([$userId, trim($input['name'])]);
    if ($stmt->fetch()) {
        json(['status' => 'error', 'message' => 'A category with that name already exists'], 409);
    }

    $stmt = db()->prepare("INSERT INTO income_categories (user_id, name) VALUES (?, ?)");
    $stmt->execute([$userId, sanitize($input['name'])]);

    $categoryId = db()->lastInsertId();

    json([
        'status'  => 'success',
        'message' => 'Category created',
        'data'    => ['id' => (int) $categoryId, 'name' => sanitize($input['name'])]
    ], 201);
}

function updateIncomeCategory($id) {
    $user   = currentUser();
    $userId = $user['id'];
    $input  = getJsonInput();

    // Verify category belongs to this user
    $stmt = db()->prepare("SELECT id FROM income_categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        json(['status' => 'error', 'message' => 'Category not found'], 404);
    }

    if (isEmpty($input['name'] ?? '')) {
        json(['status' => 'error', 'message' => 'Category name is required'], 400);
    }

    if (!validateText($input['name'], 1, 100)) {
        json(['status' => 'error', 'message' => 'Category name must be between 1 and 100 characters'], 400);
    }

    // Check for duplicate name excluding current category
    $stmt = db()->prepare("SELECT id FROM income_categories WHERE user_id = ? AND name = ? AND id != ?");
    $stmt->execute([$userId, trim($input['name']), $id]);
    if ($stmt->fetch()) {
        json(['status' => 'error', 'message' => 'A category with that name already exists'], 409);
    }

    $stmt = db()->prepare("UPDATE income_categories SET name = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([sanitize($input['name']), $id, $userId]);

    json(['status' => 'success', 'message' => 'Category updated']);
}

function deleteIncomeCategory($id) {
    $user   = currentUser();
    $userId = $user['id'];

    // Verify category belongs to this user
    $stmt = db()->prepare("SELECT id FROM income_categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        json(['status' => 'error', 'message' => 'Category not found'], 404);
    }

    // Block deletion if category is in use
    $stmt = db()->prepare("SELECT COUNT(*) FROM income WHERE category_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        json([
            'status'  => 'error',
            'message' => 'Cannot delete a category that has income records linked to it'
        ], 409);
    }

    $stmt = db()->prepare("DELETE FROM income_categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    json(['status' => 'success', 'message' => 'Category deleted']);
}