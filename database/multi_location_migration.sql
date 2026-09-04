-- Multi-branch inventory support: locations table, per-location stock,
-- and location attribution on orders / stock entries / store sales / staff.

CREATE TABLE IF NOT EXISTS locations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  address VARCHAR(255) NULL,
  phone VARCHAR(60) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_locations_is_active (is_active)
) ENGINE=InnoDB;

INSERT INTO locations (name, code, address, phone, is_active)
SELECT 'Main Branch', 'MAIN', address, phone, 1
FROM invoice_settings WHERE id = 1
ON DUPLICATE KEY UPDATE name = name;

-- inventory: move from one row per product to one row per (product, location)
ALTER TABLE inventory ADD COLUMN location_id INT UNSIGNED NULL AFTER product_id;

UPDATE inventory SET location_id = (SELECT id FROM locations WHERE code = 'MAIN' LIMIT 1)
WHERE location_id IS NULL;

ALTER TABLE inventory MODIFY COLUMN location_id INT UNSIGNED NOT NULL;

ALTER TABLE inventory
  DROP INDEX product_id,
  DROP INDEX sku,
  ADD INDEX idx_inventory_sku (sku),
  ADD CONSTRAINT fk_inventory_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE RESTRICT,
  ADD UNIQUE KEY uniq_inventory_product_location (product_id, location_id);

-- orders: which branch fulfilled it
ALTER TABLE orders ADD COLUMN location_id INT UNSIGNED NULL AFTER user_id;
UPDATE orders SET location_id = (SELECT id FROM locations WHERE code = 'MAIN' LIMIT 1)
WHERE location_id IS NULL;
ALTER TABLE orders
  ADD CONSTRAINT fk_orders_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  ADD INDEX idx_orders_location (location_id);

-- stock_entries: which branch received the stock
ALTER TABLE stock_entries ADD COLUMN location_id INT UNSIGNED NULL AFTER product_id;
UPDATE stock_entries SET location_id = (SELECT id FROM locations WHERE code = 'MAIN' LIMIT 1)
WHERE location_id IS NULL;
ALTER TABLE stock_entries
  ADD CONSTRAINT fk_stock_entries_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE RESTRICT,
  ADD INDEX idx_stock_entries_location (location_id);

-- store_sales: which branch sold it
ALTER TABLE store_sales ADD COLUMN location_id INT UNSIGNED NULL AFTER id;
UPDATE store_sales SET location_id = (SELECT id FROM locations WHERE code = 'MAIN' LIMIT 1)
WHERE location_id IS NULL;
ALTER TABLE store_sales
  ADD CONSTRAINT fk_store_sales_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  ADD INDEX idx_store_sales_location (location_id);

-- invoices: which branch's stock was adjusted
ALTER TABLE invoices ADD COLUMN location_id INT UNSIGNED NULL AFTER order_id;
UPDATE invoices SET location_id = (SELECT id FROM locations WHERE code = 'MAIN' LIMIT 1)
WHERE location_id IS NULL;
ALTER TABLE invoices
  ADD CONSTRAINT fk_invoices_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  ADD INDEX idx_invoices_location (location_id);

-- users: optional home branch for staff
ALTER TABLE users ADD COLUMN location_id INT UNSIGNED NULL AFTER role;
ALTER TABLE users
  ADD CONSTRAINT fk_users_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  ADD INDEX idx_users_location (location_id);

-- invoice_settings: default fulfillment branch for public storefront orders
ALTER TABLE invoice_settings ADD COLUMN default_location_id INT UNSIGNED NULL AFTER id;
UPDATE invoice_settings SET default_location_id = (SELECT id FROM locations WHERE code = 'MAIN' LIMIT 1)
WHERE id = 1 AND default_location_id IS NULL;
ALTER TABLE invoice_settings
  ADD CONSTRAINT fk_invoice_settings_location
    FOREIGN KEY (default_location_id) REFERENCES locations(id)
    ON DELETE SET NULL;
