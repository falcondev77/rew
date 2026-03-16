<?php
require_once __DIR__ . '/includes/config.php';

$db = db();
$errors = [];
$done = [];

function run(PDO $db, string $label, string $sql, array &$done, array &$errors): void {
    try {
        $db->exec($sql);
        $done[] = $label;
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'already exists')) {
            $done[] = $label . ' (gia presente)';
        } else {
            $errors[] = $label . ': ' . $e->getMessage();
        }
    }
}

run($db, 'users.first_name', "ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER username", $done, $errors);
run($db, 'users.last_name', "ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name", $done, $errors);
run($db, 'users.is_admin', "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER amazon_tracking_id", $done, $errors);

run($db, 'affiliate_links.product_price', "ALTER TABLE affiliate_links ADD COLUMN product_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER share_percent", $done, $errors);
run($db, 'affiliate_links.auto_confirmed', "ALTER TABLE affiliate_links ADD COLUMN auto_confirmed TINYINT(1) NOT NULL DEFAULT 0 AFTER status", $done, $errors);
run($db, 'affiliate_links.status ENUM', "ALTER TABLE affiliate_links MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'", $done, $errors);
run($db, 'affiliate_links.status index', "ALTER TABLE affiliate_links ADD INDEX idx_status (status)", $done, $errors);

run($db, 'TABLE rewards', "CREATE TABLE IF NOT EXISTS rewards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  points_cost INT UNSIGNED NOT NULL DEFAULT 0,
  image_url VARCHAR(500) DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $done, $errors);

run($db, 'TABLE reward_redemptions', "CREATE TABLE IF NOT EXISTS reward_redemptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reward_id INT UNSIGNED NOT NULL,
  points_spent INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending','fulfilled','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_red_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_red_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
  INDEX idx_redemption_user (user_id),
  INDEX idx_redemption_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $done, $errors);

?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Migrazione Database</title>
    <style>
        body { font-family: Inter, sans-serif; max-width: 700px; margin: 60px auto; padding: 0 20px; background: #f3f5fb; }
        h1 { font-size: 24px; margin-bottom: 24px; }
        .ok { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px; color: #065f46; }
        .err { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 16px 20px; margin-bottom: 12px; color: #991b1b; }
        .item { padding: 6px 0; font-size: 14px; }
        a { display: inline-block; margin-top: 24px; padding: 12px 24px; background: #3f6de0; color: #fff; border-radius: 12px; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <h1>Migrazione Database</h1>
    <?php if ($done): ?>
    <div class="ok">
        <strong>Completate (<?= count($done) ?>):</strong>
        <?php foreach ($done as $d): ?>
            <div class="item">&#10003; <?= htmlspecialchars($d) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="err">
        <strong>Errori (<?= count($errors) ?>):</strong>
        <?php foreach ($errors as $e): ?>
            <div class="item">&#10007; <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (!$errors): ?>
        <p>Migrazione completata con successo. Puoi eliminare questo file dopo averlo eseguito.</p>
        <a href="index.php">Vai al Login</a>
    <?php endif; ?>
</body>
</html>
