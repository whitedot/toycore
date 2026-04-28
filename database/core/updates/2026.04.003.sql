CREATE TABLE IF NOT EXISTS toy_popup_layers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(120) NOT NULL,
    body_text TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    dismiss_cookie_days INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_toy_popup_layers_status_dates (status, starts_at, ends_at),
    KEY idx_toy_popup_layers_updated (updated_at)
);

CREATE TABLE IF NOT EXISTS toy_popup_layer_targets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    popup_layer_id BIGINT UNSIGNED NOT NULL,
    module_key VARCHAR(60) NOT NULL,
    point_key VARCHAR(120) NOT NULL,
    slot_key VARCHAR(80) NOT NULL,
    subject_id VARCHAR(80) NOT NULL DEFAULT '',
    match_type VARCHAR(20) NOT NULL DEFAULT 'all',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_toy_popup_layer_targets_popup (popup_layer_id),
    KEY idx_toy_popup_layer_targets_lookup (module_key, point_key, slot_key, match_type, subject_id, popup_layer_id)
);

INSERT INTO toy_modules (module_key, name, version, status, is_bundled, installed_at, updated_at)
VALUES ('popup_layer', 'Popup Layer', '2026.04.001', 'enabled', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    version = VALUES(version),
    is_bundled = VALUES(is_bundled),
    updated_at = VALUES(updated_at);

INSERT IGNORE INTO toy_schema_versions (scope, module_key, version, applied_at)
VALUES ('module', 'popup_layer', '2026.04.001', NOW());
