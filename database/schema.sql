CREATE DATABASE IF NOT EXISTS dubai_computer_fast_cargo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE dubai_computer_fast_cargo;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS shipments;
DROP TABLE IF EXISTS deliveries;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS quotation_items;
DROP TABLE IF EXISTS quotations;
DROP TABLE IF EXISTS invoice_settings;
DROP TABLE IF EXISTS store_sale_items;
DROP TABLE IF EXISTS store_sales;
DROP TABLE IF EXISTS stock_entries;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS locations;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE locations (
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

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('customer', 'manager', 'admin') NOT NULL DEFAULT 'customer',
  location_id INT UNSIGNED NULL,
  phone VARCHAR(40) NULL,
  address VARCHAR(255) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  INDEX idx_users_role (role),
  INDEX idx_users_status (status),
  INDEX idx_users_location (location_id)
) ENGINE=InnoDB;

CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  customer_code VARCHAR(30) NOT NULL UNIQUE,
  company_name VARCHAR(160) NULL,
  city VARCHAR(80) NULL,
  country VARCHAR(80) NOT NULL DEFAULT 'United Arab Emirates',
  tin VARCHAR(80) NULL,
  vrn VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_customers_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  category VARCHAR(100) NOT NULL,
  brand VARCHAR(100) NOT NULL,
  description TEXT NULL,
  specifications TEXT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_products_search (name, category, brand),
  INDEX idx_products_status (status),
  INDEX idx_products_price (price)
) ENGINE=InnoDB;

CREATE TABLE product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  caption VARCHAR(160) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_images_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE,
  UNIQUE KEY ux_product_images_path (product_id, image_path),
  INDEX idx_product_images_product (product_id, is_primary, sort_order)
) ENGINE=InnoDB;

CREATE TABLE inventory (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NOT NULL,
  sku VARCHAR(80) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  reorder_level INT NOT NULL DEFAULT 5,
  location VARCHAR(120) NULL,
  supplier_name VARCHAR(160) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inventory_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_inventory_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE RESTRICT,
  UNIQUE KEY uniq_inventory_product_location (product_id, location_id),
  INDEX idx_inventory_quantity (quantity),
  INDEX idx_inventory_sku (sku)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(40) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NULL,
  status ENUM('pending', 'confirmed', 'processing', 'ready', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  shipping_address VARCHAR(255) NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_orders_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at),
  INDEX idx_orders_location (location_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE RESTRICT,
  INDEX idx_order_items_order (order_id),
  INDEX idx_order_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE quotations (
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

CREATE TABLE quotation_items (
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

CREATE TABLE invoice_settings (
  id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
  default_location_id INT UNSIGNED NULL,
  company_name VARCHAR(180) NOT NULL DEFAULT 'Dubai Computer Cargo',
  logo_path VARCHAR(255) NULL,
  address VARCHAR(255) NOT NULL DEFAULT 'Deira, Dubai, United Arab Emirates',
  phone VARCHAR(60) NOT NULL DEFAULT '0749006994',
  email VARCHAR(160) NOT NULL DEFAULT 'dubaicomputers14@14gmail.com',
  support_email VARCHAR(160) NULL,
  instagram_url VARCHAR(255) NULL,
  social_handle VARCHAR(100) NULL,
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
    ON DELETE SET NULL,
  CONSTRAINT fk_invoice_settings_location
    FOREIGN KEY (default_location_id) REFERENCES locations(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE invoices (
  invoice_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NULL,
  order_id INT UNSIGNED NULL,
  location_id INT UNSIGNED NULL,
  quotation_id INT UNSIGNED NULL,
  customer_name VARCHAR(160) NOT NULL,
  customer_company VARCHAR(160) NULL,
  customer_phone VARCHAR(60) NULL,
  customer_email VARCHAR(160) NULL,
  customer_address VARCHAR(255) NULL,
  customer_tin VARCHAR(80) NULL,
  customer_vrn VARCHAR(80) NULL,
  delivery_full_name VARCHAR(160) NULL,
  delivery_phone VARCHAR(60) NULL,
  delivery_address VARCHAR(255) NULL,
  delivery_region VARCHAR(120) NULL,
  delivery_district VARCHAR(120) NULL,
  delivery_ward VARCHAR(120) NULL,
  delivery_landmark VARCHAR(160) NULL,
  delivery_method ENUM('office_pickup','home_delivery','courier') NULL,
  preferred_delivery_date DATE NULL,
  preferred_delivery_time VARCHAR(40) NULL,
  delivery_instructions TEXT NULL,
  invoice_date DATE NOT NULL,
  due_date DATE NOT NULL,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  vat DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  grand_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  transport_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  installation_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
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
  CONSTRAINT fk_invoices_quotation
    FOREIGN KEY (quotation_id) REFERENCES quotations(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_invoices_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_invoices_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  INDEX idx_invoices_customer (customer_id),
  INDEX idx_invoices_order (order_id),
  INDEX idx_invoices_quotation (quotation_id),
  INDEX idx_invoices_location (location_id),
  INDEX idx_invoices_status (status),
  INDEX idx_invoices_date (invoice_date),
  INDEX idx_invoices_search (invoice_number, customer_name)
) ENGINE=InnoDB;

CREATE TABLE invoice_items (
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

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NULL,
  invoice_id INT UNSIGNED NULL,
  payment_reference VARCHAR(120) NULL,
  method ENUM('cash', 'bank_transfer', 'mobile_money', 'card') NOT NULL DEFAULT 'bank_transfer',
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending', 'confirmed', 'rejected') NOT NULL DEFAULT 'pending',
  proof_file VARCHAR(255) NULL,
  notes TEXT NULL,
  submitted_at TIMESTAMP NULL,
  confirmed_by INT UNSIGNED NULL,
  confirmed_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_payments_invoice
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
    ON DELETE SET NULL,
  CONSTRAINT fk_payments_confirmed_by
    FOREIGN KEY (confirmed_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_invoice (invoice_id),
  INDEX idx_payments_status (status)
) ENGINE=InnoDB;

CREATE TABLE stock_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NULL,
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
  CONSTRAINT fk_stock_entries_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE RESTRICT,
  INDEX idx_stock_entries_product (product_id),
  INDEX idx_stock_entries_received_date (received_date),
  INDEX idx_stock_entries_location (location_id)
) ENGINE=InnoDB;

CREATE TABLE store_sales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_id INT UNSIGNED NULL,
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
  CONSTRAINT fk_store_sales_location
    FOREIGN KEY (location_id) REFERENCES locations(id)
    ON DELETE SET NULL,
  INDEX idx_store_sales_sale_date (sale_date),
  INDEX idx_store_sales_payment_method (payment_method),
  INDEX idx_store_sales_location (location_id)
) ENGINE=InnoDB;

CREATE TABLE store_sale_items (
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

CREATE TABLE deliveries (
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
  INDEX idx_deliveries_created_at (created_at),
  INDEX idx_deliveries_tracking (tracking_number)
) ENGINE=InnoDB;

CREATE TABLE shipments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL UNIQUE,
  tracking_number VARCHAR(60) NOT NULL UNIQUE,
  status ENUM(
    'order_received',
    'payment_confirmed',
    'ordered_from_supplier',
    'shipped_from_origin',
    'in_transit',
    'arrived_at_port',
    'cleared',
    'ready_for_pickup',
    'delivered'
  ) NOT NULL DEFAULT 'order_received',
  origin VARCHAR(120) NOT NULL DEFAULT 'Dubai',
  destination VARCHAR(160) NOT NULL,
  carrier VARCHAR(120) NULL,
  expected_arrival DATE NULL,
  delivered_at TIMESTAMP NULL,
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shipments_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_shipments_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_shipments_status (status),
  INDEX idx_shipments_tracking (tracking_number)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  INDEX idx_notifications_user_read (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  table_name VARCHAR(80) NULL,
  record_id INT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  details TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_logs_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_audit_logs_user (user_id),
  INDEX idx_audit_logs_action (action),
  INDEX idx_audit_logs_created_at (created_at)
) ENGINE=InnoDB;

INSERT INTO locations (id, name, code, address, phone, is_active) VALUES
(1, 'Main Branch', 'MAIN', 'Dar es Salaam, Tanzania / Deira, Dubai', '0652532646', 1);

INSERT INTO users (id, name, email, password, role, phone, address, status) VALUES
(1, 'Company Owner', 'admin@dubai-fast-cargo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+971500000001', 'Dubai, UAE', 'active'),
(2, 'Store Manager', 'manager@dubai-fast-cargo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '+971500000002', 'Deira, Dubai', 'active'),
(3, 'Demo Customer', 'customer@dubai-fast-cargo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '+255700000003', 'Dar es Salaam, Tanzania', 'active');

INSERT INTO customers (id, user_id, customer_code, company_name, city, country, tin, vrn) VALUES
(1, 3, 'CUS-2026-0001', 'Demo Customer Trading', 'Dar es Salaam', 'Tanzania', 'TIN-000-2026', 'VRN-000-2026');

INSERT INTO products (id, name, category, brand, description, specifications, price, image, status, created_by) VALUES
(1, 'Dell Latitude 5440 Laptop', 'Laptops', 'Dell', 'Business laptop for office, school, and business users.', 'Intel Core i5\n16GB RAM\n512GB SSD\nWindows 11 Pro', 1950000.00, 'uploads/product-1782122339-1673.png', 'active', 2),
(2, 'HP LaserJet Pro Printer', 'Printers', 'HP', 'Fast office printer suitable for retail and cargo offices.', 'Laser print engine\nNetwork ready\nOffice duty cycle', 620000.00, NULL, 'active', 2),
(3, 'Samsung 27 Inch Monitor', 'Monitors', 'Samsung', 'Full HD monitor for office and home use.', '27 inch IPS panel\nFull HD resolution\nHDMI input', 420000.00, NULL, 'active', 2),
(4, 'Logitech MX Master Mouse', 'Accessories', 'Logitech', 'Wireless productivity mouse.', 'Bluetooth wireless\nRechargeable battery\nErgonomic profile', 180000.00, NULL, 'active', 2),
(5, 'HP EliteBook 840 G9 Laptop', 'Laptops', 'HP', 'Premium lightweight business laptop.', 'Intel Core i7\n16GB RAM\n512GB SSD\nWindows 11 Pro\nBacklit keyboard', 2250000.00, NULL, 'active', 2),
(6, 'Lenovo ThinkPad T14 Laptop', 'Laptops', 'Lenovo', 'Durable business laptop with strong keyboard and security.', 'Intel Core i5\n16GB RAM\n512GB SSD\nFingerprint reader\nWindows 11 Pro', 2100000.00, NULL, 'active', 2),
(7, 'Apple MacBook Air M2', 'Laptops', 'Apple', 'Thin MacBook for students, designers, and business users.', 'Apple M2 chip\n8GB RAM\n256GB SSD\n13 inch Retina display\nmacOS', 3250000.00, NULL, 'active', 2),
(8, 'HP ProDesk 400 Desktop', 'Desktops', 'HP', 'Reliable desktop computer for office and shop operations.', 'Intel Core i5\n8GB RAM\n512GB SSD\nWindows 11 Pro\nKeyboard and mouse', 1450000.00, NULL, 'active', 2),
(9, 'Dell OptiPlex 7010 Desktop', 'Desktops', 'Dell', 'Compact desktop for business counters and accounts offices.', 'Intel Core i5\n16GB RAM\n512GB SSD\nWindows 11 Pro', 1650000.00, NULL, 'active', 2),
(10, 'Lenovo ThinkCentre M70q Mini PC', 'Mini PCs', 'Lenovo', 'Small form factor PC for tight desks and reception areas.', 'Intel Core i5\n8GB RAM\n256GB SSD\nWi-Fi\nWindows 11 Pro', 1250000.00, NULL, 'active', 2),
(11, 'HP EliteOne 840 All-in-One', 'All-in-One PCs', 'HP', 'All-in-one computer with screen and CPU in one unit.', '24 inch display\nIntel Core i5\n16GB RAM\n512GB SSD\nWebcam\nWindows 11 Pro', 2800000.00, NULL, 'active', 2),
(12, 'Custom Gaming PC RTX 4060', 'Gaming PCs', 'Custom Build', 'Gaming and graphics computer with dedicated GPU.', 'Intel Core i7\n16GB RAM\n1TB SSD\nNVIDIA RTX 4060\nRGB case', 3950000.00, NULL, 'active', 2),
(13, 'Dell Precision 3660 Workstation', 'Workstations', 'Dell', 'High performance workstation for design, engineering, and heavy business work.', 'Intel Core i7\n32GB RAM\n1TB SSD\nNVIDIA professional graphics\nWindows 11 Pro', 4800000.00, NULL, 'active', 2),
(14, 'Acer Chromebook 314', 'Chromebooks', 'Acer', 'Affordable computer for browsing, online classes, and light office use.', 'Intel Celeron\n4GB RAM\n64GB eMMC\n14 inch display\nChromeOS', 850000.00, NULL, 'active', 2),
(15, 'Dell PowerEdge T150 Server', 'Servers', 'Dell', 'Entry-level business server for files, accounting systems, and backups.', 'Intel Xeon\n16GB ECC RAM\n2TB storage\nTower server', 3600000.00, NULL, 'active', 2);

INSERT INTO inventory (product_id, location_id, sku, quantity, reorder_level, location, supplier_name) VALUES
(1, 1, 'LAP-DELL-5440', 15, 4, 'Dubai Warehouse A', 'Dubai Tech Suppliers LLC'),
(2, 1, 'PRN-HP-LJPRO', 9, 3, 'Dubai Warehouse B', 'Office Machines UAE'),
(3, 1, 'MON-SAM-27FHD', 22, 5, 'Dubai Warehouse A', 'Screen World Trading'),
(4, 1, 'ACC-LOG-MXM', 6, 5, 'Retail Shelf 1', 'Gulf Accessories'),
(5, 1, 'LAP-HP-840G9', 8, 3, 'Dubai Warehouse A', 'Dubai Tech Suppliers LLC'),
(6, 1, 'LAP-LEN-T14', 10, 3, 'Dubai Warehouse A', 'Dubai Tech Suppliers LLC'),
(7, 1, 'LAP-APP-MBA-M2', 4, 2, 'Premium Shelf', 'Apple UAE Distributor'),
(8, 1, 'DESK-HP-PD400', 12, 4, 'Dubai Warehouse B', 'Office Machines UAE'),
(9, 1, 'DESK-DELL-7010', 11, 4, 'Dubai Warehouse B', 'Dubai Tech Suppliers LLC'),
(10, 1, 'MINI-LEN-M70Q', 7, 3, 'Retail Shelf 2', 'Gulf Computer Parts'),
(11, 1, 'AIO-HP-840', 5, 2, 'Premium Shelf', 'Office Machines UAE'),
(12, 1, 'GAME-RTX4060', 3, 2, 'Premium Shelf', 'Gulf Computer Parts'),
(13, 1, 'WORK-DELL-3660', 2, 1, 'Premium Shelf', 'Dubai Tech Suppliers LLC'),
(14, 1, 'CHR-ACER-314', 14, 4, 'Retail Shelf 3', 'Gulf Computer Parts'),
(15, 1, 'SRV-DELL-T150', 2, 1, 'Dubai Warehouse C', 'Server World UAE');

INSERT INTO product_images (product_id, image_path, caption, is_primary, sort_order) VALUES
(1, 'uploads/product-1782122339-1673.png', 'Dell Latitude 5440 Laptop', 1, 0);

INSERT INTO quotations (
  id, customer_name, company_name, phone, email, delivery_full_name, delivery_phone,
  delivery_address, delivery_region, delivery_district, delivery_ward, delivery_landmark,
  delivery_date, delivery_time, delivery_instructions, delivery_method, status,
  subtotal, discount, transport_cost, installation_cost, tax, grand_total, admin_notes
) VALUES
(1, 'Demo Customer', 'Demo Customer Trading', '+255700000003', 'customer@dubai-fast-cargo.test',
 'Demo Customer', '+255700000003', 'Mbezi Beach, Dar es Salaam', 'Dar es Salaam',
 'Kinondoni', 'Mbezi Beach', 'Near main road',
 DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), 'Morning', 'Call before delivery.',
 'home_delivery', 'approved', 1950000.00, 0.00, 50000.00, 0.00, 0.00, 2000000.00,
 'Approved demo quotation for laptop delivery.');

INSERT INTO quotation_items (quotation_id, product_id, product_name, product_image, quantity, unit_price, discount, notes, line_total) VALUES
(1, 1, 'Dell Latitude 5440 Laptop', 'uploads/product-1782122339-1673.png', 1, 1950000.00, 0.00, 'Demo quotation item.', 1950000.00);

INSERT INTO orders (id, order_number, user_id, location_id, status, total_amount, shipping_address, notes) VALUES
(1, 'ORD-20260622-0001', 3, 1, 'confirmed', 1950000.00, 'Dar es Salaam, Tanzania', 'Demo order for laptop shipment.');

INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total) VALUES
(1, 1, 1, 1950000.00, 1950000.00);

INSERT INTO invoice_settings (id, default_location_id, company_name, logo_path, address, phone, email, support_email, instagram_url, social_handle, tin, vrn, vat_rate, currency_code, terms, updated_by) VALUES
(1, 1, 'Dubai Computer Cargo', NULL, 'Dar es Salaam, Tanzania / Deira, Dubai', '0749006994', 'dubaicomputers14@14gmail.com', 'gidamasaudathomas@gmail.com', 'https://www.instagram.com/dubai_computers/', '@dubai_computers', 'TIN-DCF-2026', 'VRN-DCF-2026', 0.00, 'TZS', 'Payment is due on or before the invoice due date. Goods remain company property until full payment is received. Customer support: gidamasaudathomas@gmail.com.', 1);

INSERT INTO invoices (
  invoice_id, invoice_number, customer_id, order_id, location_id, customer_name, customer_company, customer_phone,
  customer_email, customer_address, customer_tin, customer_vrn, invoice_date, due_date,
  subtotal, discount, vat, grand_total, balance_due, status, created_by
) VALUES
(1, 'INV-20260622-0001', 1, 1, 1, 'Demo Customer', 'Demo Customer Trading', '+255700000003',
 'customer@dubai-fast-cargo.test', 'Dar es Salaam, Tanzania', 'TIN-000-2026', 'VRN-000-2026',
 CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 1950000.00, 0.00, 0.00, 1950000.00, 0.00, 'paid', 2);

INSERT INTO invoice_items (invoice_id, product_id, product_name, product_image, description, specifications, quantity, unit_price, line_discount, amount) VALUES
(1, 1, 'Dell Latitude 5440 Laptop', 'uploads/product-1782122339-1673.png', 'Business laptop for office, school, and business users.', 'Intel Core i5\n16GB RAM\n512GB SSD\nWindows 11 Pro', 1, 1950000.00, 0.00, 1950000.00);

INSERT INTO payments (order_id, invoice_id, payment_reference, method, amount, status, submitted_at, confirmed_by, confirmed_at, notes) VALUES
(1, 1, 'BANK-DEMO-001', 'bank_transfer', 1950000.00, 'confirmed', CURRENT_TIMESTAMP, 2, CURRENT_TIMESTAMP, 'Seed payment confirmed for demo invoice.');

INSERT INTO stock_entries (product_id, location_id, quantity, unit_cost, supplier_name, received_date, received_by, notes) VALUES
(1, 1, 5, 1550000.00, 'Dubai Tech Suppliers LLC', CURRENT_DATE, 2, 'Opening stock intake demo.'),
(4, 1, 3, 120000.00, 'Gulf Accessories', CURRENT_DATE, 2, 'Accessories restock demo.');

INSERT INTO store_sales (id, location_id, sale_number, customer_name, payment_method, total_amount, sale_date, sold_by, notes) VALUES
(1, 1, 'SALE-20260622-0001', 'Walk-in Customer', 'cash', 180000.00, CURRENT_DATE, 2, 'Demo counter sale.');

INSERT INTO store_sale_items (sale_id, product_id, quantity, unit_price, line_total) VALUES
(1, 4, 1, 180000.00, 180000.00);

INSERT INTO shipments (order_id, tracking_number, status, origin, destination, carrier, expected_arrival, notes, created_by) VALUES
(1, 'DCF-20260622-0001', 'in_transit', 'Dubai', 'Dar es Salaam', 'Dubai Fast Cargo', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), 'Demo shipment already in transit.', 2);

INSERT INTO deliveries (
  quotation_id, invoice_id, customer_name, phone, address, region, district, ward,
  landmark, delivery_method, preferred_date, preferred_time, special_instructions,
  status, tracking_number
) VALUES
(1, NULL, 'Demo Customer', '+255700000003', 'Mbezi Beach, Dar es Salaam',
 'Dar es Salaam', 'Kinondoni', 'Mbezi Beach', 'Near main road',
 'home_delivery', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY), 'Morning',
 'Call before delivery.', 'preparing', 'DEL-20260622-0001');

INSERT INTO notifications (user_id, title, message, type) VALUES
(3, 'Welcome to Dubai Computer Fast Cargo', 'Your customer account is ready. You can browse products, place orders, and track shipments.', 'success'),
(3, 'Shipment in transit', 'Tracking DCF-20260622-0001 is currently in transit.', 'info');

INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent, details) VALUES
(1, 'database_seeded', 'users', 1, '127.0.0.1', 'schema.sql', 'Initial demo data created. Default password for seed users is password.');
