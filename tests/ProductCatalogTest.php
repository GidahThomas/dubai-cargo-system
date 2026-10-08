<?php

class ProductCatalogTest extends TestCase
{
    public function testPagingReturnsDistinctPagesThatAddUpToTheTotal(): void
    {
        $model = new Product();
        $total = $model->countAll([], true);
        $this->assertTrue($total >= 10, 'Seed data should have at least 10 active products.');

        $first = array_column($model->all([], true, null, 4, 0), 'id');
        $second = array_column($model->all([], true, null, 4, 4), 'id');
        $everything = array_column($model->all([], true), 'id');

        $this->assertEquals(4, count($first));
        $this->assertEquals([], array_intersect($first, $second), 'Pages must not repeat products.');
        $this->assertEquals(array_slice($everything, 0, 8), array_merge($first, $second), 'Pages follow the same order as the full list.');
        $this->assertEquals(count($everything), $total);
    }

    public function testCountRespectsFilters(): void
    {
        $model = new Product();
        $this->assertEquals(count($model->all(['brand' => 'Dell'], true)), $model->countAll(['brand' => 'Dell'], true));
        $this->assertEquals(0, $model->countAll(['search' => 'no-such-product-xyz'], true));
    }

    public function testCatalogGroupsFeatureLargeCategoriesAndMergeSmallOnes(): void
    {
        $products = array_merge(
            $this->make('Laptops', 9),
            $this->make('Desktops', 2),
            $this->make('Monitors', 1)
        );
        $categories = [['name' => 'Laptops'], ['name' => 'Desktops'], ['name' => 'Monitors']];

        $sections = Product::catalogGroups($products, $categories, 3, 7, 12);

        $this->assertEquals(2, count($sections));
        $this->assertEquals('Laptops', $sections[0]['name']);
        $this->assertTrue($sections[0]['featured']);
        $this->assertEquals(7, count($sections[0]['products']), 'Capped at the section size.');
        $this->assertEquals(9, $sections[0]['total'], 'Total still reports every laptop.');
        $this->assertEquals('More Products', $sections[1]['name']);
        $this->assertTrue($sections[1]['mixed']);
        $this->assertEquals(3, $sections[1]['total']);
    }

    public function testSingleSmallCategoryKeepsItsName(): void
    {
        $sections = Product::catalogGroups($this->make('Printers', 2), [['name' => 'Printers']]);

        $this->assertEquals(1, count($sections));
        $this->assertEquals('Printers', $sections[0]['name']);
        $this->assertFalse($sections[0]['mixed']);
    }

    private function make(string $category, int $count): array
    {
        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $products[] = ['id' => crc32($category) + $i, 'category' => $category, 'name' => "{$category} {$i}"];
        }

        return $products;
    }
}
