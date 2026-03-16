<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_auth();
$activity = get_dashboard_activity((int) $user['id']);
$rewards = get_active_rewards();
$approvedPoints = (int) $user['points_balance'];
$totalEarned = (int) $user['total_points_earned'];
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
            <div class="brand-icon">&#9733;</div>
            <strong><?= e(SITE_NAME) ?></strong>
        </div>
        <nav class="nav-links">
            <?php if (!empty($user['is_admin'])): ?>
                <a href="admin.php" class="nav-admin-link">Admin Panel</a>
            <?php endif; ?>
            <a href="#rewards-section">Premi</a>
            <a href="#activity">Storico</a>
            <a href="logout.php" class="avatar-link"><?= strtoupper(e(substr($user['username'], 0, 1))) ?></a>
        </nav>
    </header>

    <main class="dashboard-shell">
        <section class="hero-dashboard">
            <div class="hero-image"></div>
            <div class="hero-copy">
                <h1>Guadagna Punti dai tuoi Link Amazon</h1>
                <p>Incolla un link Amazon, il sistema genera il tuo link affiliato e stima i punti. I punti restano <strong>in attesa</strong> fino alla conferma.</p>
                <div class="search-bar">
                    <input type="text" id="productUrl" placeholder="Incolla qui il link Amazon...">
                    <button id="convertBtn">Converti</button>
                </div>
                <div id="converterResult" class="converter-result"></div>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Punti Confermati</span>
                <strong><?= $approvedPoints ?> <small>pts</small></strong>
            </article>
            <article class="stat-card">
                <span class="stat-label">Totale Guadagnato</span>
                <strong><?= $totalEarned ?> <small>pts</small></strong>
            </article>
            <article class="stat-card accent-card">
                <span class="stat-label">Il tuo Tag</span>
                <strong class="tag-display"><?= e($user['amazon_tracking_id']) ?></strong>
            </article>
        </section>

        <?php if ($rewards): ?>
        <section class="rewards-section" id="rewards-section">
            <h2>Premi Disponibili</h2>
            <div class="rewards-grid">
                <?php foreach ($rewards as $reward): ?>
                    <div class="reward-item">
                        <?php if ($reward['image_url']): ?>
                            <img src="<?= e($reward['image_url']) ?>" alt="<?= e($reward['name']) ?>" class="reward-thumb">
                        <?php else: ?>
                            <div class="reward-thumb-placeholder"></div>
                        <?php endif; ?>
                        <h3><?= e($reward['name']) ?></h3>
                        <?php if ($reward['description']): ?>
                            <p class="reward-desc"><?= e($reward['description']) ?></p>
                        <?php endif; ?>
                        <div class="reward-bottom">
                            <span class="reward-price"><?= (int) $reward['points_cost'] ?> pts</span>
                            <?php if ($approvedPoints >= (int) $reward['points_cost']): ?>
                                <button class="btn-sm btn-success redeem-btn" data-reward-id="<?= (int) $reward['id'] ?>">Riscatta</button>
                            <?php else: ?>
                                <span class="reward-locked">Punti insufficienti</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="activity-card" id="activity">
            <div class="activity-header">
                <h2>Attivita Recente</h2>
            </div>
            <div class="activity-list">
                <?php if (!$activity): ?>
                    <div class="activity-item empty-state">Ancora nessuna conversione effettuata.</div>
                <?php else: ?>
                    <?php foreach ($activity as $item): ?>
                        <div class="activity-item">
                            <div>
                                <div class="activity-title"><?= e($item['product_title'] ?: ('Prodotto ' . $item['asin'])) ?></div>
                                <div class="activity-subtitle"><?= e($item['category_label']) ?> &bull; <?= e($item['created_at']) ?></div>
                            </div>
                            <div class="points-cell">
                                <span class="points-value <?= $item['status'] === 'approved' ? 'plus' : ($item['status'] === 'rejected' ? 'minus' : 'pending-pts') ?>">
                                    <?= $item['status'] === 'approved' ? '+' : '' ?><?= (int) $item['points_awarded'] ?> pts
                                </span>
                                <span class="status-pill status-<?= e($item['status']) ?>">
                                    <?php
                                    $labels = ['pending' => 'In attesa', 'approved' => 'Approvato', 'rejected' => 'Rifiutato'];
                                    echo e($labels[$item['status']] ?? $item['status']);
                                    ?>
                                    <?php if (!empty($item['auto_confirmed'])): ?>
                                        <small>(auto)</small>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        window.appConfig = {
            convertEndpoint: 'api/convert_link.php',
            confirmEndpoint: 'api/auto_confirm.php',
            redeemEndpoint: 'api/redeem_reward.php'
        };
    </script>
    <script src="assets/app.js"></script>
</body>
</html>
