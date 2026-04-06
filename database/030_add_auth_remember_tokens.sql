CREATE TABLE IF NOT EXISTS auth_remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    selector CHAR(18) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    user_agent VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_remember_selector (selector),
    KEY idx_auth_remember_user (user_id),
    KEY idx_auth_remember_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM auth_remember_tokens
WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
