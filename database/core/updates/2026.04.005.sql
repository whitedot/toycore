SET @toy_has_site_settings_public = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'toy_site_settings'
      AND COLUMN_NAME = 'is_public'
);

SET @toy_sql = IF(
    @toy_has_site_settings_public > 0,
    'ALTER TABLE toy_site_settings DROP COLUMN is_public',
    'DO 0'
);
PREPARE toy_stmt FROM @toy_sql;
EXECUTE toy_stmt;
DEALLOCATE PREPARE toy_stmt;
