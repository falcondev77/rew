<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Sessione scaduta']);
    exit;
}

$inputUrl = trim($_POST['product_url'] ?? '');
if ($inputUrl === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Inserisci un link Amazon o un ASIN']);
    exit;
}

$resolvedUrl = resolve_amzn_short_url($inputUrl);
$sourceUrl = clean_amazon_url($resolvedUrl ?: $inputUrl);
$asin = extract_asin($sourceUrl);

if (!$asin) {
    http_response_code(422);
    echo json_encode(['error' => 'ASIN non trovato nel link inserito']);
    exit;
}

[$categoryKey, $categoryReason] = infer_category_from_url($sourceUrl);
$categoryRule = get_category_rule($categoryKey);
$pointsInfo = calculate_points($categoryRule);

$username = $user['username'];
$subtag = build_subtag($username);
$mainTag = 'pato666-21';
$affiliateUrl = build_affiliate_url($asin, $username);

$title = 'Amazon product ' . $asin;
if (preg_match('#/([A-Za-z0-9\-\_]+?)/dp/#', $sourceUrl, $matches)) {
    $title = ucwords(str_replace('-', ' ', $matches[1]));
}

$db = db();
$db->beginTransaction();

try {
    $stmt = $db->prepare('
        INSERT INTO affiliate_links (
            user_id,
            product_title,
            asin,
            source_url,
            affiliate_url,
            category_key,
            category_label,
            amazon_rate,
            share_percent,
            points_awarded,
            status,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $user['id'],
        $title,
        $asin,
        $sourceUrl,
        $affiliateUrl,
        $categoryKey,
        $categoryRule['label'],
        $categoryRule['amazon_rate'],
        $pointsInfo['share_percent'],
        $pointsInfo['estimated_points'],
        'Pending',
        $categoryReason . ' | tag=' . $mainTag . ' | subtag=' . $subtag
    ]);

    $update = $db->prepare('
        UPDATE users
        SET points_balance = points_balance + ?, total_points_earned = total_points_earned + ?
        WHERE id = ?
    ');
    $update->execute([
        $pointsInfo['estimated_points'],
        $pointsInfo['estimated_points'],
        $user['id']
    ]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Errore durante il salvataggio della conversione',
        'details' => $e->getMessage()
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'asin' => $asin,
    'title' => $title,
    'category' => $categoryRule['label'],
    'category_reason' => $categoryReason,
    'amazon_rate' => $categoryRule['amazon_rate'],
    'share_percent' => $pointsInfo['share_percent'],
    'points' => $pointsInfo['estimated_points'],
    'affiliate_url' => $affiliateUrl,
    'tag' => $mainTag,
    'subtag' => $subtag,
    'username' => $username
]);