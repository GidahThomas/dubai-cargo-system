<?php

class InstagramImporterTest extends TestCase
{
    private InstagramImporter $importer;

    protected function setUp(): void
    {
        $this->importer = new InstagramImporter(false);
    }

    public function testFixEncodingRepairsInstagramMojibake(): void
    {
        // Instagram exports "🔥 Bei" as escaped single bytes, which json_decode turns into Latin-1 characters.
        $mojibake = json_decode('"ð\u009f\u0094¥ Bei"');
        $this->assertEquals('🔥 Bei', $this->importer->fixEncoding($mojibake));
    }

    public function testFixEncodingLeavesCorrectTextAlone(): void
    {
        $this->assertEquals('Karibu sana 🔥', $this->importer->fixEncoding('Karibu sana 🔥'));
        $this->assertEquals('Café', $this->importer->fixEncoding('Café'));
    }

    public function testGuessPriceReadsCommonFormats(): void
    {
        $this->assertEquals(1250000.0, $this->importer->guessPrice('Dell Latitude - Tsh 1,250,000'));
        $this->assertEquals(1200000.0, $this->importer->guessPrice("HP 840 G8\nBei: 1.2M"));
        $this->assertEquals(850000.0, $this->importer->guessPrice('Price 850k only'));
        $this->assertEquals(900000.0, $this->importer->guessPrice('Offer 900,000/= today'));
        $this->assertEquals(0.0, $this->importer->guessPrice('Core i7 16GB RAM 512GB SSD'));
    }

    public function testProductNameUsesFirstLineWithoutTagsOrPrice(): void
    {
        $this->assertEquals('Dell Latitude 5420', $this->importer->productName("💻 Dell Latitude 5420 — Tsh 1,250,000\nKaribu"));
        $this->assertEquals('HP EliteBook 840 G8', $this->importer->productName("HP EliteBook 840 G8 🔥 #dubaicomputers\nCore i7"));
    }

    public function testProductNameFallsBackToDate(): void
    {
        $this->assertEquals('Instagram post 2026-10-04', $this->importer->productName('🔥🔥 #sale', strtotime('2026-10-04 12:00:00')));
    }
}
