<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Sessione scaduta']);
    exit;
}

$linkId = (int) ($_POST['link_id'] ?? 0);
if ($linkId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'ID link non valido']);
    exit;
}

$ok = auto_confirm_link($linkId, (int) $user['id']);
echo json_encode(['success' => $ok]);
