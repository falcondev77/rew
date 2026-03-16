CREATE DATABASE IF NOT EXISTS affiliate_rewards CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE affiliate_rewards;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  amazon_tracking_id VARCHAR(64) NOT NULL,
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
  points_awarded INT NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_affiliate_links_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_created (user_id, created_at),
  INDEX idx_asin (asin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password_hash, amazon_tracking_id, points_balance, total_points_earned)
VALUES ('demo', 'demo@example.com', '$2y$10$5s6iI9PxK8SGvtQvV4H7n.lL6xJja3TBUnIFVQGryuVKy7jH9Muqu', 'demo-21', 250, 1420);
