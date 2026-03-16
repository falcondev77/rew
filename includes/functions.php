<?php
require_once __DIR__ . '/config.php';

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    try {
        $stmt = db()->prepare('SELECT id, username, first_name, last_name, email, amazon_tracking_id, is_admin, points_balance, total_points_earned, created_at FROM users WHERE id = ? LIMIT 1');
    } catch (PDOException $e) {
        $stmt = db()->prepare('SELECT id, username, email, amazon_tracking_id, points_balance, total_points_earned, created_at FROM users WHERE id = ? LIMIT 1');
    }
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        $user['first_name'] = $user['first_name'] ?? '';
        $user['last_name']  = $user['last_name'] ?? '';
        $user['is_admin']   = $user['is_admin'] ?? 0;
    }

    return $user ?: null;
}

function is_admin(): bool {
    $user = current_user();
    return $user && !empty($user['is_admin']);
}

function require_admin(): array {
    $user = require_auth();
    if (empty($user['is_admin'])) {
        redirect('dashboard.php');
    }
    return $user;
}

function require_auth(): array {
    $user = current_user();
    if (!$user) {
        redirect('index.php');
    }
    return $user;
}

function normalize_username_to_tracking_id(string $username): string {
    $base = strtolower(trim($username));
    $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'member';
    $base = substr($base, 0, 45);
    return $base . DEFAULT_TRACKING_SUFFIX;
}

function build_subtag(string $username): string {
    $username = strtolower(trim($username));
    $username = preg_replace('/[^a-z0-9_-]/', '-', $username);
    $username = preg_replace('/-+/', '-', $username);
    $username = trim($username, '-_');

    if ($username === '') {
        $username = 'user';
    }

    return substr($username, 0, 64);
}

function get_main_amazon_tag(): string {
    return 'pato666-21';
}

function extract_asin(string $input): ?string {
    $input = trim($input);

    if (preg_match('/^[A-Z0-9]{10}$/i', $input)) {
        return strtoupper($input);
    }

    $patterns = [
        '/\/(?:dp|gp\/product|gp\/aw\/d|product)\/([A-Z0-9]{10})(?:[\/?]|$)/i',
        '/[\?&]asin=([A-Z0-9]{10})(?:&|$)/i'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input, $matches)) {
            return strtoupper($matches[1]);
        }
    }

    return null;
}

function resolve_amzn_short_url(string $input): ?string {
    if (!preg_match('/^https?:\/\/amzn\.(to|eu)\//i', trim($input))) {
        return null;
    }

    $headers = @get_headers($input, true);
    if ($headers === false || !isset($headers['Location'])) {
        return null;
    }

    $location = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
    return is_string($location) ? $location : null;
}

function clean_amazon_url(string $url): string {
    $url = preg_replace('/(\?|\&)(ref|psc|smid|th|qid|sr|dib|dib_tag|pd_rd_[^=]*)=[^&]*/', '', $url);
    return rtrim($url, '?&');
}

function infer_category_from_url(string $url): array {
    $normalized = strtolower($url);
    $rules = [
        'electronics' => ['electronics', 'informatica', 'computer', 'pc', 'tablet', 'smartphone', 'audio', 'videogiochi'],
        'home' => ['casa', 'kitchen', 'home', 'arredamento', 'fai-da-te'],
        'beauty' => ['beauty', 'bellezza', 'profumi', 'skincare'],
        'fashion' => ['fashion', 'moda', 'scarpe', 'abbigliamento', 'jewelry'],
        'books' => ['libri', 'books', 'audible'],
        'pet' => ['pet', 'animali'],
        'sports' => ['sport', 'fitness', 'outdoor', 'hobby', 'camping', 'trekking'],
    ];

    foreach ($rules as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($normalized, $keyword) !== false) {
                return [$category, 'Riconosciuta da URL/keyword'];
            }
        }
    }

    return ['generic', 'Categoria stimata in modo generico'];
}

function get_category_rule(string $category): array {
    $defaults = [
        'generic' => ['label' => 'Generico', 'amazon_rate' => 3.0, 'high_commission' => 0],
        'electronics' => ['label' => 'Elettronica', 'amazon_rate' => 2.5, 'high_commission' => 0],
        'home' => ['label' => 'Casa', 'amazon_rate' => 4.0, 'high_commission' => 1],
        'beauty' => ['label' => 'Bellezza', 'amazon_rate' => 10.0, 'high_commission' => 1],
        'fashion' => ['label' => 'Moda', 'amazon_rate' => 10.0, 'high_commission' => 1],
        'books' => ['label' => 'Libri', 'amazon_rate' => 7.0, 'high_commission' => 1],
        'pet' => ['label' => 'Animali', 'amazon_rate' => 8.0, 'high_commission' => 1],
        'sports' => ['label' => 'Sport / Outdoor', 'amazon_rate' => 5.0, 'high_commission' => 0],
    ];

    return $defaults[$category] ?? $defaults['generic'];
}

function fetch_remote_html(string $url): ?string {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ],
    ]);

    $html = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($html === false || $httpCode >= 400 || !is_string($html)) {
        return null;
    }

    if (stripos($html, 'captcha') !== false || stripos($html, 'robot check') !== false) {
        return null;
    }

    return $html;
}

function normalize_price_string(string $value): ?float {
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = trim($value);
    $value = preg_replace('/[^0-9,\.\s]/u', '', $value);
    $value = str_replace(' ', '', $value);

    if ($value === '') {
        return null;
    }

    if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $value)) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } else {
        $value = str_replace(',', '.', $value);
    }

    if (!is_numeric($value)) {
        return null;
    }

    return (float) $value;
}

function extract_amazon_price_from_html(string $html): ?float {
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    if (!$dom->loadHTML($html)) {
        return null;
    }

    $xpath = new DOMXPath($dom);

    $primaryXPath = '/html/body/div[1]/div[2]/div[2]/div[5]/div[4]/div[17]/div/div/div[6]/div[1]/span[2]/span[2]';
    $nodes = $xpath->query($primaryXPath);
    if ($nodes instanceof DOMNodeList && $nodes->length > 0) {
        $price = normalize_price_string(trim($nodes->item(0)->textContent));
        if ($price !== null) {
            return $price;
        }
    }

    $fallbackXPaths = [
        '//*[@id="priceblock_ourprice"]',
        '//*[@id="priceblock_dealprice"]',
        '//*[@id="priceblock_saleprice"]',
        '//*[@id="corePrice_feature_div"]//span[contains(@class,"a-offscreen")]',
        '//*[@id="corePriceDisplay_desktop_feature_div"]//span[contains(@class,"a-offscreen")]',
        '//*[contains(@class,"a-price")]//span[contains(@class,"a-offscreen")]',
    ];

    foreach ($fallbackXPaths as $expr) {
        $nodes = $xpath->query($expr);
        if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
            continue;
        }

        foreach ($nodes as $node) {
            $price = normalize_price_string(trim($node->textContent));
            if ($price !== null) {
                return $price;
            }
        }
    }

    if (preg_match('/"priceAmount"\s*:\s*"?(\d+[\.,]\d{2})"?/i', $html, $matches)) {
        $price = normalize_price_string($matches[1]);
        if ($price !== null) {
            return $price;
        }
    }

    return null;
}

function get_amazon_product_price(string $url): ?float {
    $html = fetch_remote_html($url);
    if ($html === null) {
        return null;
    }

    return extract_amazon_price_from_html($html);
}

function calculate_points_from_price(float $price, array $categoryRule): array {
    $amazonRate = (float) ($categoryRule['amazon_rate'] ?? 0);
    $sharePercent = DEFAULT_SHARE_PERCENT + (!empty($categoryRule['high_commission']) ? BONUS_SHARE_PERCENT : 0);

    $amazonCommission = $price * ($amazonRate / 100);
    $userValue = $amazonCommission * ($sharePercent / 100);
    $points = (int) round($userValue * 100);

    return [
        'product_price' => round($price, 2),
        'amazon_rate' => $amazonRate,
        'share_percent' => $sharePercent,
        'estimated_points' => max(0, $points),
    ];
}

function build_affiliate_url(string $asin, string $username): string {
    $mainTag = get_main_amazon_tag();
    $subtag = build_subtag($username);

    return 'https://www.amazon.it/dp/' . rawurlencode($asin)
        . '/?tag=' . rawurlencode($mainTag)
        . '&ascsubtag=' . rawurlencode($subtag);
}

function get_dashboard_activity(int $userId): array {
    $stmt = db()->prepare('SELECT product_title, asin, source_url, affiliate_url, category_label, points_awarded, status, auto_confirmed, created_at FROM affiliate_links WHERE user_id = ? ORDER BY id DESC LIMIT 10');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function get_all_users(): array {
    return db()->query('SELECT id, username, first_name, last_name, email, is_admin, points_balance, total_points_earned, created_at FROM users ORDER BY created_at DESC')->fetchAll();
}

function get_all_links(): array {
    $sql = 'SELECT al.*, u.username, u.email AS user_email
            FROM affiliate_links al
            JOIN users u ON u.id = al.user_id
            ORDER BY al.created_at DESC';
    return db()->query($sql)->fetchAll();
}

function get_pending_links(): array {
    $sql = 'SELECT al.*, u.username, u.email AS user_email
            FROM affiliate_links al
            JOIN users u ON u.id = al.user_id
            WHERE al.status = "pending"
            ORDER BY al.created_at DESC';
    return db()->query($sql)->fetchAll();
}

function approve_link(int $linkId): bool {
    $db = db();
    $stmt = $db->prepare('SELECT id, user_id, points_awarded, status FROM affiliate_links WHERE id = ? LIMIT 1');
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();

    if (!$link || $link['status'] !== 'pending') {
        return false;
    }

    $db->beginTransaction();
    try {
        $db->prepare('UPDATE affiliate_links SET status = "approved" WHERE id = ?')->execute([$linkId]);
        $db->prepare('UPDATE users SET points_balance = points_balance + ?, total_points_earned = total_points_earned + ? WHERE id = ?')
           ->execute([$link['points_awarded'], $link['points_awarded'], $link['user_id']]);
        $db->commit();
        return true;
    } catch (\Throwable $e) {
        $db->rollBack();
        return false;
    }
}

function reject_link(int $linkId): bool {
    $stmt = db()->prepare('UPDATE affiliate_links SET status = "rejected" WHERE id = ? AND status = "pending"');
    $stmt->execute([$linkId]);
    return $stmt->rowCount() > 0;
}

function auto_confirm_link(int $linkId, int $userId): bool {
    $db = db();
    $stmt = $db->prepare('SELECT id, user_id, points_awarded, status FROM affiliate_links WHERE id = ? AND user_id = ? AND status = "pending" LIMIT 1');
    $stmt->execute([$linkId, $userId]);
    $link = $stmt->fetch();

    if (!$link) {
        return false;
    }

    $db->beginTransaction();
    try {
        $db->prepare('UPDATE affiliate_links SET status = "approved", auto_confirmed = 1 WHERE id = ?')->execute([$linkId]);
        $db->prepare('UPDATE users SET points_balance = points_balance + ?, total_points_earned = total_points_earned + ? WHERE id = ?')
           ->execute([$link['points_awarded'], $link['points_awarded'], $link['user_id']]);
        $db->commit();
        return true;
    } catch (\Throwable $e) {
        $db->rollBack();
        return false;
    }
}

function get_all_rewards(): array {
    return db()->query('SELECT * FROM rewards ORDER BY created_at DESC')->fetchAll();
}

function get_active_rewards(): array {
    return db()->query('SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_cost ASC')->fetchAll();
}

function get_all_redemptions(): array {
    $sql = 'SELECT rr.*, u.username, r.name AS reward_name, r.points_cost
            FROM reward_redemptions rr
            JOIN users u ON u.id = rr.user_id
            JOIN rewards r ON r.id = rr.reward_id
            ORDER BY rr.created_at DESC';
    return db()->query($sql)->fetchAll();
}
