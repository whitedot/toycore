CREATE TABLE IF NOT EXISTS toy_site_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    value_type VARCHAR(20) NOT NULL DEFAULT 'string',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_site_settings_key (setting_key)
);

CREATE TABLE IF NOT EXISTS toy_modules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    module_key VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    version VARCHAR(40) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'disabled',
    is_bundled TINYINT(1) NOT NULL DEFAULT 0,
    installed_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_modules_key (module_key)
);

CREATE TABLE IF NOT EXISTS toy_module_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    module_id BIGINT UNSIGNED NOT NULL,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    value_type VARCHAR(20) NOT NULL DEFAULT 'string',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_module_settings_key (module_id, setting_key)
);

CREATE TABLE IF NOT EXISTS toy_schema_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scope VARCHAR(20) NOT NULL,
    module_key VARCHAR(60) NOT NULL DEFAULT '',
    version VARCHAR(40) NOT NULL,
    applied_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_schema_versions (scope, module_key, version)
);

CREATE TABLE IF NOT EXISTS toy_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_account_id BIGINT UNSIGNED NULL,
    actor_type VARCHAR(40) NOT NULL DEFAULT 'system',
    event_type VARCHAR(80) NOT NULL,
    target_type VARCHAR(60) NOT NULL DEFAULT '',
    target_id VARCHAR(120) NOT NULL DEFAULT '',
    result VARCHAR(30) NOT NULL DEFAULT 'success',
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent TEXT NULL,
    message VARCHAR(255) NOT NULL DEFAULT '',
    metadata_json TEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_toy_audit_logs_actor (actor_account_id),
    KEY idx_toy_audit_logs_event (event_type),
    KEY idx_toy_audit_logs_target (target_type, target_id),
    KEY idx_toy_audit_logs_created (created_at)
);

CREATE TABLE IF NOT EXISTS toy_privacy_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NULL,
    request_type VARCHAR(40) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'requested',
    requester_email_hash CHAR(64) NOT NULL DEFAULT '',
    requester_snapshot VARCHAR(255) NOT NULL DEFAULT '',
    request_message TEXT NULL,
    admin_note TEXT NULL,
    handled_by_account_id BIGINT UNSIGNED NULL,
    handled_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_toy_privacy_requests_account (account_id),
    KEY idx_toy_privacy_requests_status (status),
    KEY idx_toy_privacy_requests_type (request_type),
    KEY idx_toy_privacy_requests_created (created_at)
);
