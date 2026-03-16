<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_auth();
$activity = get_dashboard_activity((int) $user['id']);
$remaining = max(NEXT_REWARD_TARGET - (int) $user['points_balance'], 0);
$progress = min(((int) $user['points_balance'] / NEXT_REWARD_TARGET) * 100, 100);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_NAME) ?> - Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dashboard-page">
    <header class="topbar">
        <div class="brand-row compact">
            <div class="brand-icon">💳</div>
            <strong><?= e(SITE_NAME) ?></strong>
        </div>
        <nav class="nav-links">
            <a href="#">Rewards</a>
            <a href="#activity">History</a>
            <a href="#profile">Profile</a>
            <a href="logout.php" class="avatar-link"><?= strtoupper(e(substr($user['username'], 0, 1))) ?></a>
        </nav>
    </header>

    <main class="dashboard-shell">
        <section class="hero-dashboard">
            <div class="hero-image"></div>
            <div class="hero-copy">
                <h1>Earn Rewards from your Amazon Links</h1>
                <p>Incolla un link Amazon, il sistema aggiunge il tracking ID dell'utente, stima la categoria e accredita i punti.</p>
                <div class="search-bar">
                    <input type="text" id="productUrl" placeholder="Paste Amazon product URL here...">
                    <button id="convertBtn">Convert</button>
                </div>
                <div id="converterResult" class="converter-result"></div>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Current balance</span>
                <strong><?= (int) $user['points_balance'] ?> <small>pts</small></strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Total earned</span>
                <strong><?= (int) $user['total_points_earned'] ?> <small>pts</small></strong>
            </article>
            <article class="stat-card reward-card">
                <span class="stat-label">Next reward</span>
                <strong><?= e(NEXT_REWARD_LABEL) ?></strong>
                <div class="progress"><span style="width: <?= $progress ?>%"></span></div>
                <small><?= $remaining ?> pts remaining</small>
            </article>
        </section>

        <section class="activity-card" id="activity">
            <div class="activity-header">
                <h2>Recent Activity</h2>
            </div>
            <div class="activity-list">
                <?php if (!$activity): ?>
                    <div class="activity-item empty-state">Ancora nessuna conversione effettuata.</div>
                <?php else: ?>
                    <?php foreach ($activity as $item): ?>
                        <div class="activity-item">
                            <div>
                                <div class="activity-title"><?= e($item['product_title'] ?: ('Prodotto ' . $item['asin'])) ?></div>
                                <div class="activity-subtitle"><?= e($item['category_label']) ?> • <?= e($item['created_at']) ?></div>
                            </div>
                            <div class="points <?= $item['points_awarded'] >= 0 ? 'plus' : 'minus' ?>">
                                <?= $item['points_awarded'] >= 0 ? '+' : '' ?><?= (int) $item['points_awarded'] ?> pts
                                <small><?= e($item['status']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="info-note" id="profile">
            <p><strong>Tracking ID utente:</strong> <?= e($user['amazon_tracking_id']) ?></p>
            <p><strong>Nota tecnica:</strong> in Amazon Associates il tag deve esistere davvero come tracking ID del tuo account. Questo campo può essere mappato 1:1 con gli utenti solo se li crei in Amazon Associates.</p>
        </section>
    </main>

    <script>
        window.appConfig = {
            convertEndpoint: 'api/convert_link.php'
        };
    </script>
    <script src="assets/app.js"></script>
</body>
</html>
