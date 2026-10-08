-- Low-stock alerts: remembers that managers were already told, until the item is restocked.
ALTER TABLE inventory
  ADD COLUMN IF NOT EXISTS low_stock_notified TINYINT(1) NOT NULL DEFAULT 0;

-- Products: where the item was sourced from.
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS country_of_origin VARCHAR(80) NULL AFTER brand;
