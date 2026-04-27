CREATE TABLE IF NOT EXISTS toy_member_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_identifier_hash CHAR(64) NOT NULL,
    login_id_hash CHAR(64) NULL,
    email VARCHAR(255) NOT NULL,
    email_hash CHAR(64) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(120) NOT NULL,
    locale VARCHAR(20) NOT NULL DEFAULT 'ko',
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_member_identifier (account_identifier_hash),
    UNIQUE KEY uq_toy_member_email_hash (email_hash)
);

CREATE TABLE IF NOT EXISTS toy_member_auth_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(60) NOT NULL,
    result VARCHAR(30) NOT NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_toy_member_auth_logs_account (account_id),
    KEY idx_toy_member_auth_logs_created (created_at)
);
