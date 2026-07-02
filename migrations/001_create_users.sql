CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `username`          VARCHAR(50) NOT NULL UNIQUE,
    `password_hash`     VARCHAR(255) NOT NULL,
    `persona`           TEXT NULL,
    `role`              ENUM('user','admin','inactive') NOT NULL DEFAULT 'inactive',
    `tokens_sent`       INT NOT NULL DEFAULT 0,
    `tokens_generated`  INT NOT NULL DEFAULT 0,
    `tokens_cost`       DECIMAL(10, 6) NOT NULL DEFAULT 0.00,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
