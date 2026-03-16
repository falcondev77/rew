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

    $stmt = db()->prepare('SELECT id, username, email, amazon_tracking_id, points_balance, total_points_earned, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
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
        'sports' => ['sport', 'fitness', 'outdoor'],
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
        'sports' => ['label' => 'Sport', 'amazon_rate' => 5.0, 'high_commission' => 0],
    ];

    return $defaults[$category] ?? $defaults['generic'];
}

function calculate_points(array $categoryRule): array {
    $sharePercent = DEFAULT_SHARE_PERCENT + (!empty($categoryRule['high_commission']) ? BONUS_SHARE_PERCENT : 0);
    $points = (int) round(($categoryRule['amazon_rate'] * $sharePercent) * 10);

    return [
        'share_percent' => $sharePercent,
        'estimated_points' => max($points, 10),
    ];
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

function build_affiliate_url(string $asin, string $username): string {
    $mainTag = 'pato666-21';
    $subtag = build_subtag($username);

    return 'https://www.amazon.it/dp/' . rawurlencode($asin)
        . '/?tag=' . rawurlencode($mainTag)
        . '&ascsubtag=' . rawurlencode($subtag);
}

function get_dashboard_activity(int $userId): array {
    $stmt = db()->prepare('SELECT product_title, asin, source_url, affiliate_url, category_label, points_awarded, status, created_at FROM affiliate_links WHERE user_id = ? ORDER BY id DESC LIMIT 10');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
