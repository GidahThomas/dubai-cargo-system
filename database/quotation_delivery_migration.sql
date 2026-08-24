CREATE TABLE IF NOT EXISTS quotations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(160) NOT NULL,
  company_name VARCHAR(160) NULL,
  phone VARCHAR(60) NOT NULL,
  email VARCHAR(160) NULL,
  delivery_full_name VARCHAR(160) NULL,
  delivery_phone VARCHAR(60) NULL,
  delivery_address VARCHAR(255) NULL,
  delivery_region VARCHAR(120) NULL,
  delivery_district VARCHAR(120) NULL,
  delivery_ward VARCHAR(120) NULL,
  delivery_landmark VARCHAR(160) NULL,
  delivery_date DATE NULL,
  delivery_time VARCHAR(40) NULL,
  delivery_instructions TEXT NULL,
  delivery_method ENUM('office_pickup','home_delivery','courier') NOT NULL DEFAULT 'home_delivery',
  status ENUM('pending','reviewed','approved','rejected','converted_to_invoice') NOT NULL DEFAULT 'pending',
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  transport_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  installation_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  tax DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  grand_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  admin_notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_quotations_status (status),
  INDEX idx_quotations_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotation_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(180) NOT NULL,
  product_image VARCHAR(255) NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  notes TEXT NULL,
  line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_quotation_items_quotation
    FOREIGN KEY (quotation_id) REFERENCES quotations(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_quotation_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE SET NULL,
  INDEX idx_quotation_items_quotation (quotation_id),
  INDEX idx_quotation_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS deliveries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT UNSIGNED NULL,
  invoice_id INT UNSIGNED NULL,
  customer_name VARCHAR(160) NOT NULL,
  phone VARCHAR(60) NULL,
  address VARCHAR(255) NULL,
  region VARCHAR(120) NULL,
  district VARCHAR(120) NULL,
  ward VARCHAR(120) NULL,
  landmark VARCHAR(160) NULL,
  delivery_method ENUM('office_pickup','home_delivery','courier') NOT NULL DEFAULT 'home_delivery',
  preferred_date DATE NULL,
  preferred_time VARCHAR(40) NULL,
  special_instructions TEXT NULL,
  status ENUM('pending','preparing','packed','out_for_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending',
  tracking_number VARCHAR(50) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_deliveries_quotation
    FOREIGN KEY (quotation_id) REFERENCES quotations(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_deliveries_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
    ON DELETE SET NULL,
  INDEX idx_deliveries_status (status),
  INDEX idx_deliveries_created_at (created_at)
) ENGINE=InnoDB;
