CREATE TABLE IF NOT EXISTS toy_site_menus (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    menu_key VARCHAR(60) NOT NULL,
    label VARCHAR(120) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'enabled',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_site_menus_key (menu_key),
    KEY idx_toy_site_menus_status (status)
);

CREATE TABLE IF NOT EXISTS toy_site_menu_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    menu_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    label VARCHAR(120) NOT NULL,
    url VARCHAR(255) NOT NULL,
    target VARCHAR(20) NOT NULL DEFAULT 'self',
    status VARCHAR(30) NOT NULL DEFAULT 'enabled',
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_toy_site_menu_items_menu (menu_id, status, sort_order, id),
    KEY idx_toy_site_menu_items_parent (parent_id)
);
