CREATE TABLE IF NOT EXISTS toy_admin_account_roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    role_key VARCHAR(40) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_admin_account_roles (account_id, role_key)
);
