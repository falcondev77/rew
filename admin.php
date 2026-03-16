<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_admin();

$tab = $_GET['tab'] ?? 'overview';
$users = get_all_users();
$pendingLinks = get_pending_links();
$allLinks = get_all_links();
$rewards = get_all_rewards();
$redemptions = get_all_redemptions();

$approvedLinks = array_filter($allLinks, fn($l) => $l['status'] === 'approved');
$rejectedLinks = array_filter($allLinks, fn($l) => $l['status'] === 'rejected');
$pendingRedemptions = array_filter($redemptions, fn($r) => $r['status'] === 'pending');

$totalPoints = array_sum(array_column($users, 'points_balance'));
$totalEarned = array_sum(array_column($users, 'total_points_earned'));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_NAME) ?> - Admin Panel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="admin-page">
    <header class="topbar topbar-admin">
        <div class="brand-row compact">
            <div class="brand-icon admin-icon">&#9881;</div>
            <strong>Admin Panel</strong>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Vai al Sito</a>
            <a href="logout.php" class="avatar-link"><?= strtoupper(e(substr($user['username'], 0, 1))) ?></a>
        </nav>
    </header>

    <main class="admin-shell">
        <div class="admin-tabs">
            <a href="admin.php?tab=overview" class="tab-link <?= $tab === 'overview' ? 'active' : '' ?>">Panoramica</a>
            <a href="admin.php?tab=links" class="tab-link <?= $tab === 'links' ? 'active' : '' ?>">
                Link & Punti
                <?php if (count($pendingLinks) > 0): ?>
                    <span class="tab-badge"><?= count($pendingLinks) ?></span>
                <?php endif; ?>
            </a>
            <a href="admin.php?tab=redemptions" class="tab-link <?= $tab === 'redemptions' ? 'active' : '' ?>">
                Riscatti
                <?php if (count($pendingRedemptions) > 0): ?>
                    <span class="tab-badge"><?= count($pendingRedemptions) ?></span>
                <?php endif; ?>
            </a>
            <a href="admin.php?tab=rewards" class="tab-link <?= $tab === 'rewards' ? 'active' : '' ?>">Premi</a>
            <a href="admin.php?tab=users" class="tab-link <?= $tab === 'users' ? 'active' : '' ?>">Utenti</a>
        </div>

        <?php if ($tab === 'overview'): ?>

        <div class="overview-stats">
            <div class="ov-card">
                <div class="ov-icon ov-icon-blue">&#128101;</div>
                <div class="ov-data">
                    <span class="ov-value"><?= count($users) ?></span>
                    <span class="ov-label">Utenti registrati</span>
                </div>
            </div>
            <div class="ov-card">
                <div class="ov-icon ov-icon-orange"><?= count($pendingLinks) > 0 ? '&#9888;' : '&#10003;' ?></div>
                <div class="ov-data">
                    <span class="ov-value <?= count($pendingLinks) > 0 ? 'text-warning' : 'text-success' ?>"><?= count($pendingLinks) ?></span>
                    <span class="ov-label">Link in attesa</span>
                </div>
            </div>
            <div class="ov-card">
                <div class="ov-icon ov-icon-purple">&#127873;</div>
                <div class="ov-data">
                    <span class="ov-value <?= count($pendingRedemptions) > 0 ? 'text-warning' : 'text-success' ?>"><?= count($pendingRedemptions) ?></span>
                    <span class="ov-label">Riscatti in attesa</span>
                </div>
            </div>
            <div class="ov-card">
                <div class="ov-icon ov-icon-green">&#11088;</div>
                <div class="ov-data">
                    <span class="ov-value"><?= number_format($totalEarned) ?></span>
                    <span class="ov-label">Punti totali assegnati</span>
                </div>
            </div>
            <div class="ov-card">
                <div class="ov-icon ov-icon-blue">&#128179;</div>
                <div class="ov-data">
                    <span class="ov-value"><?= number_format($totalPoints) ?></span>
                    <span class="ov-label">Punti ancora disponibili</span>
                </div>
            </div>
            <div class="ov-card">
                <div class="ov-icon ov-icon-green">&#10003;</div>
                <div class="ov-data">
                    <span class="ov-value"><?= count($approvedLinks) ?></span>
                    <span class="ov-label">Link approvati</span>
                </div>
            </div>
        </div>

        <?php if (count($pendingLinks) > 0): ?>
        <section class="admin-section">
            <div class="section-header-action">
                <h2>&#9888; Link da approvare <span class="badge badge-pending"><?= count($pendingLinks) ?></span></h2>
                <a href="admin.php?tab=links" class="btn-sm btn-primary-outline">Vedi tutti</a>
            </div>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Prodotto</th>
                            <th>Categoria</th>
                            <th>Prezzo</th>
                            <th>Punti</th>
                            <th>Data</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($pendingLinks, 0, 5) as $l): ?>
                        <tr id="link-<?= (int) $l['id'] ?>">
                            <td><strong><?= e($l['username']) ?></strong></td>
                            <td>
                                <a href="<?= e($l['source_url']) ?>" target="_blank" rel="noopener" class="product-link">
                                    <?= e($l['product_title'] ?: $l['asin']) ?>
                                </a>
                            </td>
                            <td><?= e($l['category_label']) ?></td>
                            <td>&euro;<?= number_format((float) $l['product_price'], 2) ?></td>
                            <td class="text-center"><strong><?= (int) $l['points_awarded'] ?></strong> pts</td>
                            <td class="muted-text"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                            <td class="actions-cell">
                                <button class="btn-xs btn-success approve-btn" data-id="<?= (int) $l['id'] ?>">&#10003; Approva</button>
                                <button class="btn-xs btn-danger reject-btn" data-id="<?= (int) $l['id'] ?>">&#10007; Rifiuta</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php else: ?>
        <div class="all-clear-box">
            <div class="all-clear-icon">&#10003;</div>
            <div>
                <strong>Nessun link in attesa</strong>
                <p>Tutti i link sono stati processati.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($pendingRedemptions) > 0): ?>
        <section class="admin-section">
            <div class="section-header-action">
                <h2>&#127873; Riscatti da evadere <span class="badge badge-pending"><?= count($pendingRedemptions) ?></span></h2>
                <a href="admin.php?tab=redemptions" class="btn-sm btn-primary-outline">Vedi tutti</a>
            </div>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Premio</th>
                            <th>Punti Spesi</th>
                            <th>Data</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRedemptions as $rd): ?>
                        <tr>
                            <td><strong><?= e($rd['username']) ?></strong></td>
                            <td><?= e($rd['reward_name']) ?></td>
                            <td class="text-center"><?= (int) $rd['points_spent'] ?> pts</td>
                            <td class="muted-text"><?= date('d/m/Y H:i', strtotime($rd['created_at'])) ?></td>
                            <td class="actions-cell">
                                <button class="btn-xs btn-success fulfill-btn" data-id="<?= (int) $rd['id'] ?>">&#10003; Consegnato</button>
                                <button class="btn-xs btn-danger reject-redeem-btn" data-id="<?= (int) $rd['id'] ?>">&#10007; Rifiuta</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <?php elseif ($tab === 'links'): ?>
        <section class="admin-section">
            <div class="section-header-action">
                <h2>Link In Attesa <span class="badge badge-pending"><?= count($pendingLinks) ?></span></h2>
            </div>
            <?php if (!$pendingLinks): ?>
                <div class="all-clear-box">
                    <div class="all-clear-icon">&#10003;</div>
                    <div><strong>Nessun link in attesa</strong><p>Tutti processati!</p></div>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Prodotto</th>
                                <th>ASIN</th>
                                <th>Link</th>
                                <th>Categoria</th>
                                <th>Prezzo</th>
                                <th>Punti</th>
                                <th>Data</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingLinks as $l): ?>
                            <tr id="link-<?= (int) $l['id'] ?>">
                                <td><strong><?= e($l['username']) ?></strong></td>
                                <td><a href="<?= e($l['source_url']) ?>" target="_blank" rel="noopener" class="product-link"><?= e($l['product_title'] ?: $l['asin']) ?></a></td>
                                <td><code><?= e($l['asin']) ?></code></td>
                                <td class="link-cell"><a href="<?= e($l['source_url']) ?>" target="_blank" rel="noopener"><?= e(substr($l['source_url'], 0, 45)) ?>...</a></td>
                                <td><?= e($l['category_label']) ?></td>
                                <td>&euro;<?= number_format((float) $l['product_price'], 2) ?></td>
                                <td class="text-center"><strong><?= (int) $l['points_awarded'] ?></strong> pts</td>
                                <td class="muted-text"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                                <td class="actions-cell">
                                    <button class="btn-xs btn-success approve-btn" data-id="<?= (int) $l['id'] ?>">&#10003; Approva</button>
                                    <button class="btn-xs btn-danger reject-btn" data-id="<?= (int) $l['id'] ?>">&#10007; Rifiuta</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="admin-section">
            <h2>Storico Link <span class="badge"><?= count($allLinks) - count($pendingLinks) ?></span></h2>
            <?php $processedLinks = array_filter($allLinks, fn($l) => $l['status'] !== 'pending'); ?>
            <?php if (!$processedLinks): ?>
                <p class="empty-state">Nessun link nello storico.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Prodotto</th>
                                <th>ASIN</th>
                                <th>Categoria</th>
                                <th>Prezzo</th>
                                <th>Punti</th>
                                <th>Stato</th>
                                <th>Auto</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processedLinks as $l): ?>
                            <tr>
                                <td><strong><?= e($l['username']) ?></strong></td>
                                <td><?= e($l['product_title'] ?: $l['asin']) ?></td>
                                <td><code><?= e($l['asin']) ?></code></td>
                                <td><?= e($l['category_label']) ?></td>
                                <td>&euro;<?= number_format((float) $l['product_price'], 2) ?></td>
                                <td class="text-center"><?= (int) $l['points_awarded'] ?> pts</td>
                                <td><span class="status-pill status-<?= e($l['status']) ?>"><?php
                                    $labels = ['approved' => 'Approvato', 'rejected' => 'Rifiutato'];
                                    echo e($labels[$l['status']] ?? $l['status']);
                                ?></span></td>
                                <td><?= $l['auto_confirmed'] ? '<span class="badge badge-auto">Auto</span>' : '-' ?></td>
                                <td class="muted-text"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <?php elseif ($tab === 'redemptions'): ?>
        <section class="admin-section">
            <h2>Riscatti in Attesa <span class="badge badge-pending"><?= count($pendingRedemptions) ?></span></h2>
            <?php if (!$pendingRedemptions): ?>
                <div class="all-clear-box">
                    <div class="all-clear-icon">&#10003;</div>
                    <div><strong>Nessun riscatto in attesa</strong><p>Tutti evasi!</p></div>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Premio</th>
                                <th>Punti Spesi</th>
                                <th>Data</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRedemptions as $rd): ?>
                            <tr>
                                <td><strong><?= e($rd['username']) ?></strong></td>
                                <td><?= e($rd['reward_name']) ?></td>
                                <td class="text-center"><?= (int) $rd['points_spent'] ?> pts</td>
                                <td class="muted-text"><?= date('d/m/Y H:i', strtotime($rd['created_at'])) ?></td>
                                <td class="actions-cell">
                                    <button class="btn-xs btn-success fulfill-btn" data-id="<?= (int) $rd['id'] ?>">&#10003; Consegnato</button>
                                    <button class="btn-xs btn-danger reject-redeem-btn" data-id="<?= (int) $rd['id'] ?>">&#10007; Rifiuta</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="admin-section">
            <h2>Storico Riscatti <span class="badge"><?= count($redemptions) ?></span></h2>
            <?php $processedRed = array_filter($redemptions, fn($r) => $r['status'] !== 'pending'); ?>
            <?php if (!$processedRed): ?>
                <p class="empty-state">Nessun riscatto processato.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Premio</th>
                                <th>Punti Spesi</th>
                                <th>Stato</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processedRed as $rd): ?>
                            <tr>
                                <td><strong><?= e($rd['username']) ?></strong></td>
                                <td><?= e($rd['reward_name']) ?></td>
                                <td class="text-center"><?= (int) $rd['points_spent'] ?> pts</td>
                                <td><span class="status-pill status-<?= e($rd['status']) ?>"><?php
                                    $rdLabels = ['fulfilled' => 'Consegnato', 'rejected' => 'Rifiutato'];
                                    echo e($rdLabels[$rd['status']] ?? $rd['status']);
                                ?></span></td>
                                <td class="muted-text"><?= date('d/m/Y H:i', strtotime($rd['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <?php elseif ($tab === 'rewards'): ?>
        <section class="admin-section">
            <h2>Gestione Premi <span class="badge"><?= count($rewards) ?></span></h2>

            <div class="reward-form-card">
                <h3>Aggiungi Nuovo Premio</h3>
                <form id="addRewardForm" class="reward-form">
                    <div class="form-row-inline">
                        <div>
                            <label>Nome Premio</label>
                            <input type="text" name="name" required placeholder="es. Gift Card Amazon 10EUR">
                        </div>
                        <div>
                            <label>Punti Necessari</label>
                            <input type="number" name="points_cost" required min="1" placeholder="500">
                        </div>
                    </div>
                    <label>Descrizione</label>
                    <textarea name="description" rows="2" placeholder="Descrizione del premio..."></textarea>
                    <label>URL Immagine (opzionale)</label>
                    <input type="url" name="image_url" placeholder="https://...">
                    <button type="submit" class="btn-primary">Aggiungi Premio</button>
                    <div id="rewardFormMsg" class="form-msg"></div>
                </form>
            </div>

            <?php if ($rewards): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrizione</th>
                                <th>Punti</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewards as $r): ?>
                            <tr>
                                <td><strong><?= e($r['name']) ?></strong></td>
                                <td><?= e($r['description'] ?: '-') ?></td>
                                <td class="text-center"><strong><?= (int) $r['points_cost'] ?></strong> pts</td>
                                <td>
                                    <span class="status-pill <?= $r['is_active'] ? 'status-approved' : 'status-rejected' ?>">
                                        <?= $r['is_active'] ? 'Attivo' : 'Disattivato' ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn-xs <?= $r['is_active'] ? 'btn-warning' : 'btn-success' ?> toggle-reward-btn"
                                        data-id="<?= (int) $r['id'] ?>" data-active="<?= $r['is_active'] ?>">
                                        <?= $r['is_active'] ? 'Disattiva' : 'Attiva' ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Nessun premio creato ancora.</p>
            <?php endif; ?>
        </section>

        <?php elseif ($tab === 'users'): ?>
        <section class="admin-section">
            <h2>Utenti Registrati <span class="badge"><?= count($users) ?></span></h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Punti Disponibili</th>
                            <th>Punti Totali</th>
                            <th>Registrato il</th>
                            <th>Ruolo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= e($u['username']) ?></strong></td>
                            <td><?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '-') ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td class="text-center"><strong><?= (int) $u['points_balance'] ?></strong></td>
                            <td class="text-center"><?= (int) $u['total_points_earned'] ?></td>
                            <td class="muted-text"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['is_admin']): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">Utente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <script>
        window.appConfig = { approveEndpoint: 'api/admin_actions.php' };
    </script>
    <script src="assets/admin.js"></script>
</body>
</html>
