<?php

class ProductDetailsTest extends TestCase
{
    public function testSpecRowsReadLabelledAndPlainLines(): void
    {
        $rows = product_spec_rows("Processor: Intel Core i7-1355U\nRAM: 16GB DDR5\n\n- Backlit keyboard\nRatio: 16:9");

        $this->assertEquals(4, count($rows));
        $this->assertEquals(['label' => 'Processor', 'value' => 'Intel Core i7-1355U'], $rows[0]);
        $this->assertEquals(['label' => '', 'value' => 'Backlit keyboard'], $rows[2]);
        $this->assertEquals(['label' => 'Ratio', 'value' => '16:9'], $rows[3], 'Only the first colon splits label and value.');
    }

    public function testSpecsFromFormRowsSkipEmptyRows(): void
    {
        $text = product_specs_from_rows(['Processor', 'RAM', '', 'Storage', 'Bad:Label'], ['Intel Core i5', '', 'Fingerprint reader', '512GB  SSD', 'x']);

        $this->assertEquals("Processor: Intel Core i5\nFingerprint reader\nStorage: 512GB SSD\nBadLabel: x", $text);
    }

    public function testKeySpecsPreferProcessorRamStorage(): void
    {
        $specs = "Operating system: Windows 11 Pro\nStorage: 512GB SSD\nProcessor: Intel Core i7\nRAM: 16GB";

        $this->assertEquals('Intel Core i7 · 16GB · 512GB SSD', product_key_specs($specs));
        $this->assertEquals('Intel Core i5 · 8GB RAM', product_key_specs("Intel Core i5\n8GB RAM"), 'Old plain-line specs still work.');
        $this->assertEquals('', product_key_specs(''));
    }

    public function testDiscountPercent(): void
    {
        $this->assertEquals(10, discount_percent(1800000, 2000000));
        $this->assertEquals(0, discount_percent(2000000, 1800000), 'A lower "was" price is not a discount.');
        $this->assertEquals(0, discount_percent(1000, null));
    }

    public function testCreateStoresPricingDetailsSafely(): void
    {
        $db = Database::connect();
        $adminId = (int) $db->query('SELECT id FROM users WHERE role = "admin" LIMIT 1')->fetchColumn();
        $model = new Product();

        $id = $model->create([
            'name' => 'Test Laptop', 'category' => 'Laptops', 'brand' => 'Dell', 'price' => 1800000,
            'compare_at_price' => 2000000, 'item_condition' => 'refurbished', 'warranty' => '1 year',
            'specifications' => "Processor: Intel Core i7\nRAM: 16GB", 'status' => 'inactive',
        ], $adminId);
        $row = $db->query("SELECT compare_at_price, item_condition, warranty, specifications FROM products WHERE id = {$id}")->fetch();
        $this->assertEquals(2000000.0, (float) $row['compare_at_price']);
        $this->assertEquals('refurbished', $row['item_condition']);
        $this->assertEquals('1 year', $row['warranty']);

        $id2 = $model->create([
            'name' => 'Odd Input', 'category' => 'Laptops', 'brand' => 'HP', 'price' => 500000,
            'compare_at_price' => 400000, 'item_condition' => 'stolen', 'status' => 'inactive',
        ], $adminId);
        $row2 = $db->query("SELECT compare_at_price, item_condition, warranty FROM products WHERE id = {$id2}")->fetch();
        $this->assertNull($row2['compare_at_price'], 'A "was" price below the selling price is dropped.');
        $this->assertEquals('new', $row2['item_condition'], 'Unknown conditions fall back to new.');
        $this->assertNull($row2['warranty']);
    }
}
