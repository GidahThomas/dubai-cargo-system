-- Pricing and condition details shown to customers.
-- compare_at_price: optional "was" price, shown crossed out next to the selling price.
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS compare_at_price DECIMAL(12,2) NULL AFTER price,
  ADD COLUMN IF NOT EXISTS item_condition VARCHAR(20) NOT NULL DEFAULT 'new' AFTER compare_at_price,
  ADD COLUMN IF NOT EXISTS warranty VARCHAR(80) NULL AFTER item_condition;
