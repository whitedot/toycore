INSERT INTO toy_modules (module_key, name, version, status, is_bundled, installed_at, updated_at)
VALUES ('seo', 'SEO', '2026.04.001', 'enabled', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    version = VALUES(version),
    is_bundled = VALUES(is_bundled),
    updated_at = VALUES(updated_at);

INSERT IGNORE INTO toy_schema_versions (scope, module_key, version, applied_at)
VALUES ('module', 'seo', '2026.04.001', NOW());
