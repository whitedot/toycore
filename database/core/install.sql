CREATE TABLE IF NOT EXISTS toy_sites (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    site_key VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    base_url VARCHAR(255) NOT NULL DEFAULT '',
    timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Seoul',
    default_locale VARCHAR(20) NOT NULL DEFAULT 'ko',
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_sites_site_key (site_key)
);

CREATE TABLE IF NOT EXISTS toy_site_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    value_type VARCHAR(20) NOT NULL DEFAULT 'string',
    is_public TINYINT(1) NOT NULL DEFAULT 0,
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
