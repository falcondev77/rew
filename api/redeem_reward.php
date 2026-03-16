<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Sessione scaduta']);
    exit;
}

$rewardId = (int) ($_POST['reward_id'] ?? 0);
if ($rewardId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'ID premio non valido']);
    exit;
}

$stmt = db()->prepare('SELECT id, points_cost, is_active FROM rewards WHERE id = ? LIMIT 1');
$stmt->execute([$rewardId]);
$reward = $stmt->fetch();

if (!$reward || !$reward['is_active']) {
    http_response_code(422);
    echo json_encode(['error' => 'Premio non disponibile']);
    exit;
}

$cost = (int) $reward['points_cost'];
$balance = (int) $user['points_balance'];

if ($balance < $cost) {
    http_response_code(422);
    echo json_encode(['error' => 'Punti insufficienti']);
    exit;
}

$db = db();
$db->beginTransaction();
try {
    $db->prepare('INSERT INTO reward_redemptions (user_id, reward_id, points_spent) VALUES (?, ?, ?)')
       ->execute([$user['id'], $rewardId, $cost]);

    $db->prepare('UPDATE users SET points_balance = points_balance - ? WHERE id = ?')
       ->execute([$cost, $user['id']]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (\Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Errore nel riscatto']);
}
