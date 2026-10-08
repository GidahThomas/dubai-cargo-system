USE dubai_computer_fast_cargo;

ALTER TABLE customers
  ADD COLUMN company_name VARCHAR(160) NULL AFTER customer_code,
  ADD COLUMN tin VARCHAR(80) NULL AFTER country,
  ADD COLUMN vrn VARCHAR(80) NULL AFTER tin;

ALTER TABLE products
  ADD COLUMN specifications TEXT NULL AFTER description;

CREATE TABLE IF NOT EXISTS invoice_settings (
  id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
  company_name VARCHAR(180) NOT NULL DEFAULT 'Dubai Computer Cargo',
  logo_path VARCHAR(255) NULL,
  address VARCHAR(255) NOT NULL DEFAULT 'Deira, Dubai, United Arab Emirates',
  phone VARCHAR(60) NOT NULL DEFAULT '0749006994',
  email VARCHAR(160) NOT NULL DEFAULT 'dubaicomputers14@14gmail.com',
  tin VARCHAR(80) NULL,
  vrn VARCHAR(80) NULL,
  vat_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  currency_code VARCHAR(12) NOT NULL DEFAULT 'TZS',
  footer_note VARCHAR(255) NOT NULL DEFAULT 'Thank you for your business.',
  terms TEXT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoice_settings_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO invoice_settings (
  id, company_name, logo_path, address, phone, email, tin, vrn, vat_rate, currency_code, terms, updated_by
) VALUES (
  1, 'Dubai Computer Cargo', NULL, 'Dar es Salaam, Tanzania / Deira, Dubai',
  '0749006994', 'dubaicomputers14@14gmail.com', 'TIN-DCF-2026', 'VRN-DCF-2026',
  0.00, 'TZS', 'Payment is due on or before the invoice due date. Goods remain company property until full payment is received.', 1
) ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS invoices (
  invoice_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NULL,
  order_id INT UNSIGNED NULL,
  quotation_id INT UNSIGNED NULL,
  customer_name VARCHAR(160) NOT NULL,
  customer_company VARCHAR(160) NULL,
  customer_phone VARCHAR(60) NULL,
  customer_email VARCHAR(160) NULL,
  customer_address VARCHAR(255) NULL,
  customer_tin VARCHAR(80) NULL,
  customer_vrn VARCHAR(80) NULL,
  invoice_date DATE NOT NULL,
  due_date DATE NOT NULL,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  vat DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  grand_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance_due DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('draft', 'paid', 'unpaid', 'cancelled') NOT NULL DEFAULT 'draft',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoices_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_invoices_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_invoices_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_invoices_customer (customer_id),
  INDEX idx_invoices_order (order_id),
  INDEX idx_invoices_status (status),
  INDEX idx_invoices_date (invoice_date),
  INDEX idx_invoices_search (invoice_number, customer_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoice_items (
  invoice_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(180) NOT NULL,
  product_image VARCHAR(255) NULL,
  description TEXT NULL,
  specifications TEXT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  line_discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_invoice_items_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_invoice_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE SET NULL,
  INDEX idx_invoice_items_invoice (invoice_id),
  INDEX idx_invoice_items_product (product_id)
) ENGINE=InnoDB;

ALTER TABLE payments
  MODIFY order_id INT UNSIGNED NULL,
  ADD COLUMN invoice_id INT UNSIGNED NULL AFTER order_id,
  ADD CONSTRAINT fk_payments_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
    ON DELETE SET NULL,
  ADD INDEX idx_payments_invoice (invoice_id);
