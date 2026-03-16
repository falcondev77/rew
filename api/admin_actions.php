<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user || empty($user['is_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso negato']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'approve_link') {
    $linkId = (int) ($_POST['link_id'] ?? 0);
    if ($linkId <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'ID link non valido']);
        exit;
    }
    $ok = approve_link($linkId);
    echo json_encode(['success' => $ok]);
    exit;
}

if ($action === 'reject_link') {
    $linkId = (int) ($_POST['link_id'] ?? 0);
    if ($linkId <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'ID link non valido']);
        exit;
    }
    $ok = reject_link($linkId);
    echo json_encode(['success' => $ok]);
    exit;
}

if ($action === 'add_reward') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pointsCost = (int) ($_POST['points_cost'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($name === '' || $pointsCost <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Nome e punti necessari sono obbligatori']);
        exit;
    }

    $stmt = db()->prepare('INSERT INTO rewards (name, description, points_cost, image_url) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $description, $pointsCost, $imageUrl]);
    echo json_encode(['success' => true, 'id' => (int) db()->lastInsertId()]);
    exit;
}

if ($action === 'toggle_reward') {
    $rewardId = (int) ($_POST['reward_id'] ?? 0);
    $newActive = (int) ($_POST['new_active'] ?? 0);

    $stmt = db()->prepare('UPDATE rewards SET is_active = ? WHERE id = ?');
    $stmt->execute([$newActive, $rewardId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'fulfill_redemption') {
    $id = (int) ($_POST['redemption_id'] ?? 0);
    $stmt = db()->prepare('UPDATE reward_redemptions SET status = "fulfilled" WHERE id = ? AND status = "pending"');
    $stmt->execute([$id]);
    echo json_encode(['success' => $stmt->rowCount() > 0]);
    exit;
}

if ($action === 'reject_redemption') {
    $id = (int) ($_POST['redemption_id'] ?? 0);

    $stmt = db()->prepare('SELECT user_id, points_spent FROM reward_redemptions WHERE id = ? AND status = "pending" LIMIT 1');
    $stmt->execute([$id]);
    $redemption = $stmt->fetch();

    if (!$redemption) {
        echo json_encode(['success' => false]);
        exit;
    }

    $db = db();
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE reward_redemptions SET status = "rejected" WHERE id = ?')->execute([$id]);
        $db->prepare('UPDATE users SET points_balance = points_balance + ? WHERE id = ?')
           ->execute([$redemption['points_spent'], $redemption['user_id']]);
        $db->commit();
        echo json_encode(['success' => true]);
    } catch (\Throwable $e) {
        $db->rollBack();
        echo json_encode(['success' => false]);
    }
    exit;
}

http_response_code(422);
echo json_encode(['error' => 'Azione non riconosciuta']);
