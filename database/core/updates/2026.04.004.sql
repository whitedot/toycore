INSERT INTO toy_site_settings (setting_key, setting_value, value_type, is_public, created_at, updated_at)
SELECT 'site.name', name, 'string', 1, NOW(), NOW()
FROM toy_sites
ORDER BY id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    value_type = VALUES(value_type),
    is_public = VALUES(is_public),
    updated_at = VALUES(updated_at);

INSERT INTO toy_site_settings (setting_key, setting_value, value_type, is_public, created_at, updated_at)
SELECT 'site.base_url', base_url, 'string', 1, NOW(), NOW()
FROM toy_sites
ORDER BY id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    value_type = VALUES(value_type),
    is_public = VALUES(is_public),
    updated_at = VALUES(updated_at);

INSERT INTO toy_site_settings (setting_key, setting_value, value_type, is_public, created_at, updated_at)
SELECT 'site.timezone', timezone, 'string', 0, NOW(), NOW()
FROM toy_sites
ORDER BY id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    value_type = VALUES(value_type),
    is_public = VALUES(is_public),
    updated_at = VALUES(updated_at);

INSERT INTO toy_site_settings (setting_key, setting_value, value_type, is_public, created_at, updated_at)
SELECT 'site.default_locale', default_locale, 'string', 1, NOW(), NOW()
FROM toy_sites
ORDER BY id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    value_type = VALUES(value_type),
    is_public = VALUES(is_public),
    updated_at = VALUES(updated_at);

INSERT INTO toy_site_settings (setting_key, setting_value, value_type, is_public, created_at, updated_at)
SELECT 'site.status', status, 'string', 1, NOW(), NOW()
FROM toy_sites
ORDER BY id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    value_type = VALUES(value_type),
    is_public = VALUES(is_public),
    updated_at = VALUES(updated_at);

DROP TABLE IF EXISTS toy_sites;
