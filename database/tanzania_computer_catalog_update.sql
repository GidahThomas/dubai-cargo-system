USE dubai_computer_fast_cargo;

CREATE TABLE IF NOT EXISTS product_images (
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

INSERT INTO invoice_settings (
  id, company_name, logo_path, address, phone, email, tin, vrn, vat_rate,
  currency_code, terms, updated_by
) VALUES (
  1, 'Dubai Computer Cargo', NULL, 'Dar es Salaam, Tanzania / Deira, Dubai',
  '0749006994', 'dubaicomputers14@14gmail.com', 'TIN-DCF-2026', 'VRN-DCF-2026',
  0.00, 'TZS',
  'Payment is due on or before the invoice due date. Goods remain company property until full payment is received. Customer support: gidamasaudathomas@gmail.com.',
  1
)
ON DUPLICATE KEY UPDATE
  address = VALUES(address),
  phone = VALUES(phone),
  email = VALUES(email),
  currency_code = VALUES(currency_code),
  terms = VALUES(terms);

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
(15, 'Dell PowerEdge T150 Server', 'Servers', 'Dell', 'Entry-level business server for files, accounting systems, and backups.', 'Intel Xeon\n16GB ECC RAM\n2TB storage\nTower server', 3600000.00, NULL, 'active', 2)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  category = VALUES(category),
  brand = VALUES(brand),
  description = VALUES(description),
  specifications = VALUES(specifications),
  price = VALUES(price),
  image = COALESCE(products.image, VALUES(image)),
  status = VALUES(status);

INSERT INTO inventory (product_id, sku, quantity, reorder_level, location, supplier_name) VALUES
(1, 'LAP-DELL-5440', 15, 4, 'Dubai Warehouse A', 'Dubai Tech Suppliers LLC'),
(2, 'PRN-HP-LJPRO', 9, 3, 'Dubai Warehouse B', 'Office Machines UAE'),
(3, 'MON-SAM-27FHD', 22, 5, 'Dubai Warehouse A', 'Screen World Trading'),
(4, 'ACC-LOG-MXM', 6, 5, 'Retail Shelf 1', 'Gulf Accessories'),
(5, 'LAP-HP-840G9', 8, 3, 'Dubai Warehouse A', 'Dubai Tech Suppliers LLC'),
(6, 'LAP-LEN-T14', 10, 3, 'Dubai Warehouse A', 'Dubai Tech Suppliers LLC'),
(7, 'LAP-APP-MBA-M2', 4, 2, 'Premium Shelf', 'Apple UAE Distributor'),
(8, 'DESK-HP-PD400', 12, 4, 'Dubai Warehouse B', 'Office Machines UAE'),
(9, 'DESK-DELL-7010', 11, 4, 'Dubai Warehouse B', 'Dubai Tech Suppliers LLC'),
(10, 'MINI-LEN-M70Q', 7, 3, 'Retail Shelf 2', 'Gulf Computer Parts'),
(11, 'AIO-HP-840', 5, 2, 'Premium Shelf', 'Office Machines UAE'),
(12, 'GAME-RTX4060', 3, 2, 'Premium Shelf', 'Gulf Computer Parts'),
(13, 'WORK-DELL-3660', 2, 1, 'Premium Shelf', 'Dubai Tech Suppliers LLC'),
(14, 'CHR-ACER-314', 14, 4, 'Retail Shelf 3', 'Gulf Computer Parts'),
(15, 'SRV-DELL-T150', 2, 1, 'Dubai Warehouse C', 'Server World UAE')
ON DUPLICATE KEY UPDATE
  sku = VALUES(sku),
  quantity = GREATEST(inventory.quantity, VALUES(quantity)),
  reorder_level = VALUES(reorder_level),
  location = VALUES(location),
  supplier_name = VALUES(supplier_name);

UPDATE orders SET total_amount = 1950000.00 WHERE id = 1;
UPDATE order_items SET unit_price = 1950000.00, line_total = 1950000.00 WHERE order_id = 1 AND product_id = 1;
UPDATE invoices SET subtotal = 1950000.00, grand_total = 1950000.00, balance_due = 0.00 WHERE invoice_id = 1;
UPDATE invoice_items SET description = 'Business laptop for office, school, and business users.', unit_price = 1950000.00, amount = 1950000.00 WHERE invoice_id = 1 AND product_id = 1;
UPDATE payments SET amount = 1950000.00 WHERE invoice_id = 1 AND order_id = 1;
UPDATE stock_entries SET unit_cost = 1550000.00 WHERE product_id = 1;
UPDATE stock_entries SET unit_cost = 120000.00 WHERE product_id = 4;
UPDATE store_sales SET total_amount = 180000.00 WHERE id = 1;
UPDATE store_sale_items SET unit_price = 180000.00, line_total = 180000.00 WHERE sale_id = 1 AND product_id = 4;

INSERT INTO product_images (product_id, image_path, caption, is_primary, sort_order)
SELECT id, image, name, 1, 0
FROM products
WHERE image IS NOT NULL AND image <> ''
ON DUPLICATE KEY UPDATE
  caption = VALUES(caption),
  is_primary = VALUES(is_primary),
  sort_order = VALUES(sort_order);
