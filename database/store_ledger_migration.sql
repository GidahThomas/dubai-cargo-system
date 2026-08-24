USE dubai_computer_fast_cargo;

CREATE TABLE IF NOT EXISTS stock_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  supplier_name VARCHAR(160) NULL,
  received_date DATE NOT NULL,
  received_by INT UNSIGNED NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_stock_entries_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_stock_entries_received_by
    FOREIGN KEY (received_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_stock_entries_product (product_id),
  INDEX idx_stock_entries_received_date (received_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_sales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_number VARCHAR(40) NOT NULL UNIQUE,
  customer_name VARCHAR(160) NULL,
  payment_method ENUM('cash', 'bank_transfer', 'mobile_money', 'card') NOT NULL DEFAULT 'cash',
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  sale_date DATE NOT NULL,
  sold_by INT UNSIGNED NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_store_sales_sold_by
    FOREIGN KEY (sold_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_store_sales_sale_date (sale_date),
  INDEX idx_store_sales_payment_method (payment_method)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_sale_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_store_sale_items_sale
    FOREIGN KEY (sale_id) REFERENCES store_sales(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_store_sale_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE RESTRICT,
  INDEX idx_store_sale_items_sale (sale_id),
  INDEX idx_store_sale_items_product (product_id)
) ENGINE=InnoDB;
