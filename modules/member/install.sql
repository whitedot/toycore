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

CREATE TABLE IF NOT EXISTS toy_member_profiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    nickname VARCHAR(80) NOT NULL DEFAULT '',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    birth_date DATE NULL,
    avatar_path VARCHAR(255) NOT NULL DEFAULT '',
    profile_text TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_member_profiles_account (account_id)
);

CREATE TABLE IF NOT EXISTS toy_member_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    session_token_hash CHAR(64) NOT NULL,
    remember_token_hash CHAR(64) NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent TEXT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_member_sessions_token (session_token_hash),
    KEY idx_toy_member_sessions_account (account_id),
    KEY idx_toy_member_sessions_expires (expires_at),
    KEY idx_toy_member_sessions_revoked (revoked_at)
);

CREATE TABLE IF NOT EXISTS toy_member_password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    reset_token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_member_password_resets_token (reset_token_hash),
    KEY idx_toy_member_password_resets_account (account_id),
    KEY idx_toy_member_password_resets_expires (expires_at)
);

CREATE TABLE IF NOT EXISTS toy_member_email_verifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    verification_token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_member_email_verifications_token (verification_token_hash),
    KEY idx_toy_member_email_verifications_account (account_id),
    KEY idx_toy_member_email_verifications_expires (expires_at)
);

CREATE TABLE IF NOT EXISTS toy_member_consents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    consent_key VARCHAR(80) NOT NULL,
    consent_version VARCHAR(40) NOT NULL,
    consented TINYINT(1) NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_toy_member_consents_account (account_id),
    KEY idx_toy_member_consents_key (consent_key, consent_version)
);
