USE dubai_computer_fast_cargo;

ALTER TABLE invoices
  ADD COLUMN IF NOT EXISTS delivery_full_name VARCHAR(160) NULL AFTER customer_vrn,
  ADD COLUMN IF NOT EXISTS delivery_phone VARCHAR(60) NULL AFTER delivery_full_name,
  ADD COLUMN IF NOT EXISTS delivery_address VARCHAR(255) NULL AFTER delivery_phone,
  ADD COLUMN IF NOT EXISTS delivery_region VARCHAR(120) NULL AFTER delivery_address,
  ADD COLUMN IF NOT EXISTS delivery_district VARCHAR(120) NULL AFTER delivery_region,
  ADD COLUMN IF NOT EXISTS delivery_ward VARCHAR(120) NULL AFTER delivery_district,
  ADD COLUMN IF NOT EXISTS delivery_landmark VARCHAR(160) NULL AFTER delivery_ward,
  ADD COLUMN IF NOT EXISTS delivery_method ENUM('office_pickup','home_delivery','courier') NULL AFTER delivery_landmark,
  ADD COLUMN IF NOT EXISTS preferred_delivery_date DATE NULL AFTER delivery_method,
  ADD COLUMN IF NOT EXISTS preferred_delivery_time VARCHAR(40) NULL AFTER preferred_delivery_date,
  ADD COLUMN IF NOT EXISTS delivery_instructions TEXT NULL AFTER preferred_delivery_time,
  ADD COLUMN IF NOT EXISTS transport_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER grand_total,
  ADD COLUMN IF NOT EXISTS installation_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER transport_cost;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'invoices'
    AND INDEX_NAME = 'idx_invoices_quotation'
);

SET @idx_sql := IF(
  @idx_exists = 0,
  'ALTER TABLE invoices ADD INDEX idx_invoices_quotation (quotation_id)',
  'SELECT ''idx_invoices_quotation already exists'' AS status'
);

PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;
