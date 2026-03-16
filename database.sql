CREATE DATABASE IF NOT EXISTS affiliate_rewards CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE affiliate_rewards;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL DEFAULT '',
  last_name VARCHAR(100) NOT NULL DEFAULT '',
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  amazon_tracking_id VARCHAR(64) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  points_balance INT NOT NULL DEFAULT 0,
  total_points_earned INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE affiliate_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_title VARCHAR(255) DEFAULT NULL,
  asin VARCHAR(10) NOT NULL,
  source_url TEXT NOT NULL,
  affiliate_url TEXT NOT NULL,
  category_key VARCHAR(50) NOT NULL,
  category_label VARCHAR(100) NOT NULL,
  amazon_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  share_percent DECIMAL(5,2) NOT NULL DEFAULT 30,
  product_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  points_awarded INT NOT NULL DEFAULT 0,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  auto_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_affiliate_links_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_created (user_id, created_at),
  INDEX idx_asin (asin),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rewards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT NOT NULL DEFAULT (''),
  points_cost INT UNSIGNED NOT NULL DEFAULT 0,
  image_url VARCHAR(500) DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reward_redemptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reward_id INT UNSIGNED NOT NULL,
  points_spent INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending','fulfilled','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_redemption_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_redemption_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
  INDEX idx_redemption_user (user_id),
  INDEX idx_redemption_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, first_name, last_name, email, password_hash, amazon_tracking_id, is_admin, points_balance, total_points_earned)
VALUES ('admin', 'Admin', 'System', 'admin@example.com', '$2y$10$5s6iI9PxK8SGvtQvV4H7n.lL6xJja3TBUnIFVQGryuVKy7jH9Muqu', 'admin-21', 1, 0, 0);

INSERT INTO users (username, first_name, last_name, email, password_hash, amazon_tracking_id, points_balance, total_points_earned)
VALUES ('demo', 'Mario', 'Rossi', 'demo@example.com', '$2y$10$5s6iI9PxK8SGvtQvV4H7n.lL6xJja3TBUnIFVQGryuVKy7jH9Muqu', 'demo-21', 250, 1420);
