<?php

class InvoicePdfGenerator
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const MARGIN = 36.0;

    private NativePdfDocument $pdf;
    private string $content = '';
    private array $pageXObjects = [];
    private array $pageExtGStates = [];
    private array $imageCache = [];
    private array $grayscaleImageCache = [];
    private ?int $watermarkGStateId = null;
    private float $y = self::MARGIN;
    private string $currency = 'TZS';

    public function generate(array $invoice, array $items, array $settings): string
    {
        $this->pdf = new NativePdfDocument(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        $this->content = '';
        $this->pageXObjects = [];
        $this->pageExtGStates = [];
        $this->imageCache = [];
        $this->currency = strtoupper((string) ($settings['currency_code'] ?? default_currency_code()));
        $this->y = self::MARGIN;

        $this->drawHeader($invoice, $settings);
        $this->drawInvoiceParties($invoice);
        $this->drawWatermark((string) ($settings['logo_path'] ?? ''), $this->y, 650.0);
        $this->drawTableHeader();

        foreach ($items as $item) {
            $this->drawItemRow($item);
        }

        $this->drawTotalsAndFooter($invoice, $settings);
        $this->finishPage();

        return $this->pdf->output();
    }

    private function drawHeader(array $invoice, array $settings): void
    {
        $logoPath = (string) ($settings['logo_path'] ?? '');
        $left = self::MARGIN;

        $this->text($left, $this->y, (string) $settings['company_name'], 15, 'F2', [15, 23, 42]);

        $logoSize = 92.0;
        $logoY = $this->y + 14;

        if (!$this->drawImage($logoPath, $left, $logoY, $logoSize, $logoSize)) {
            $this->fillRect($left, $logoY, $logoSize, $logoSize, [8, 80, 65]);
            $this->text($left + 32, $logoY + 48, 'DCF', 13, 'F2', [255, 255, 255]);
        }

        $textY = $logoY + $logoSize + 15;
        $this->text($left, $textY, (string) $settings['address'], 8.5, 'F1', [71, 85, 105]);
        $textY += 13;
        $this->text($left, $textY, 'Phone: ' . (string) $settings['phone'], 8.5, 'F1', [71, 85, 105]);
        $textY += 13;
        $this->text($left, $textY, 'Email: ' . (string) $settings['email'], 8.5, 'F1', [71, 85, 105]);
        $textY += 13;

        $taxLine = trim('TIN: ' . (string) ($settings['tin'] ?? '') . '   VRN: ' . (string) ($settings['vrn'] ?? ''));
        if ($taxLine !== 'TIN:    VRN:') {
            $this->text($left, $textY, $taxLine, 8.5, 'F1', [71, 85, 105]);
            $textY += 13;
        }

        $right = 398;
        $this->text($right, $this->y, 'INVOICE', 27, 'F2', [15, 23, 42]);
        $this->text($right, $this->y + 35, (string) $invoice['invoice_number'], 10.5, 'F2', [15, 118, 110]);
        $this->statusPill($right, $this->y + 53, (string) $invoice['status']);

        $headerBottom = max($textY, $this->y + 89);
        $this->line(self::MARGIN, $headerBottom + 12, self::PAGE_WIDTH - self::MARGIN, $headerBottom + 12, [217, 226, 236]);
        $this->y = $headerBottom + 32;
    }

    private function drawInvoiceParties(array $invoice): void
    {
        $boxW = (self::PAGE_WIDTH - (self::MARGIN * 2) - 18) / 2;
        $boxH = 122;
        $leftX = self::MARGIN;
        $rightX = self::MARGIN + $boxW + 18;

        $this->strokeRect($leftX, $this->y, $boxW, $boxH, [217, 226, 236]);
        $this->fillRect($leftX, $this->y, $boxW, 28, [248, 250, 252]);
        $this->text($leftX + 12, $this->y + 9, 'Bill To', 10, 'F2', [15, 23, 42]);

        $lines = [
            (string) $invoice['customer_name'],
            (string) ($invoice['customer_company'] ?? ''),
            'Phone: ' . (string) ($invoice['customer_phone'] ?? ''),
            (string) ($invoice['customer_email'] ?? ''),
            (string) ($invoice['customer_address'] ?? ''),
            trim('TIN: ' . (string) ($invoice['customer_tin'] ?? '') . '   VRN: ' . (string) ($invoice['customer_vrn'] ?? '')),
        ];
        $lineY = $this->y + 39;

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '' || $line === 'Phone:' || $line === 'TIN:    VRN:') {
                continue;
            }

            $this->text($leftX + 12, $lineY, $line, $index === 0 ? 10 : 8.4, $index === 0 ? 'F2' : 'F1', [30, 41, 59]);
            $lineY += 13;
        }

        $this->strokeRect($rightX, $this->y, $boxW, $boxH, [217, 226, 236]);
        $this->fillRect($rightX, $this->y, $boxW, 28, [248, 250, 252]);
        $this->text($rightX + 12, $this->y + 9, 'Invoice Information', 10, 'F2', [15, 23, 42]);

        $info = [
            ['Invoice Date', $this->dateLabel($invoice['invoice_date'] ?? '')],
            ['Due Date', $this->dateLabel($invoice['due_date'] ?? '')],
            ['Payment Status', readable_status($invoice['status'] ?? '')],
            ['Prepared By', $invoice['created_by_name'] ?? 'System'],
            ['Order Ref', $invoice['order_number'] ?? 'Direct invoice'],
        ];
        $lineY = $this->y + 39;

        foreach ($info as [$label, $value]) {
            $this->text($rightX + 12, $lineY, $label, 8.2, 'F2', [100, 116, 139]);
            $this->text($rightX + 112, $lineY, (string) $value, 8.8, 'F1', [30, 41, 59]);
            $lineY += 15;
        }

        $this->y += $boxH + 24;
    }

    private function columnBoundaries(): array
    {
        return [self::MARGIN, 92.0, 350.0, 398.0, 498.0, self::PAGE_WIDTH - self::MARGIN];
    }

    private function drawColumnDividers(float $top, float $bottom, array $rgb): void
    {
        $boundaries = $this->columnBoundaries();

        foreach (array_slice($boundaries, 1, -1) as $x) {
            $this->line($x, $top, $x, $bottom, $rgb);
        }
    }

    private function drawTableHeader(): void
    {
        $headerHeight = 30.0;
        $this->fillRect(self::MARGIN, $this->y, self::PAGE_WIDTH - (self::MARGIN * 2), $headerHeight, [15, 23, 42]);
        $this->drawColumnDividers($this->y, $this->y + $headerHeight, [71, 85, 105]);
        $this->text(44, $this->y + 10, 'Image', 8.4, 'F2', [255, 255, 255]);
        $this->text(96, $this->y + 10, 'Item Name / Description / Specifications', 8.4, 'F2', [255, 255, 255]);
        $this->text(356, $this->y + 10, 'Qty', 8.4, 'F2', [255, 255, 255]);
        $this->text(404, $this->y + 10, 'Rate Price (TZS)', 8.4, 'F2', [255, 255, 255]);
        $this->text(504, $this->y + 10, 'Amount', 8.4, 'F2', [255, 255, 255]);
        $this->y += $headerHeight;
        $this->line(self::MARGIN, $this->y, self::PAGE_WIDTH - self::MARGIN, $this->y, [15, 23, 42]);
    }

    private function drawItemRow(array $item): void
    {
        $descLines = $this->wrapText((string) ($item['description'] ?? ''), 67, 3);
        $specLines = $this->wrapText((string) ($item['specifications'] ?? ''), 67, 5);
        $rowHeight = max(68, 32 + (count($descLines) + count($specLines)) * 10);

        if ($this->y + $rowHeight > 650) {
            $this->finishPage();
            $this->content = '';
            $this->pageXObjects = [];
            $this->pageExtGStates = [];
            $this->y = self::MARGIN;
            $this->text(self::MARGIN, $this->y, 'Invoice Items Continued', 13, 'F2', [15, 23, 42]);
            $this->y += 24;
            $this->drawTableHeader();
        }

        $this->strokeRect(self::MARGIN, $this->y, self::PAGE_WIDTH - (self::MARGIN * 2), $rowHeight, [203, 213, 225]);
        $this->drawColumnDividers($this->y, $this->y + $rowHeight, [203, 213, 225]);

        if (!$this->drawImageCover((string) ($item['product_image'] ?? ''), 44, $this->y + 12, 42, 42)) {
            $this->strokeRect(44, $this->y + 12, 42, 42, [203, 213, 225]);
            $this->text(51, $this->y + 29, 'IMG', 8, 'F2', [100, 116, 139]);
        }

        $textY = $this->y + 10;
        $this->text(96, $textY, (string) ($item['product_name'] ?? 'Invoice item'), 9.2, 'F2', [15, 23, 42]);
        $textY += 13;

        foreach ($descLines as $line) {
            $this->text(96, $textY, $line, 7.8, 'F1', [51, 65, 85]);
            $textY += 10;
        }

        foreach ($specLines as $line) {
            $this->text(96, $textY, $line, 7.6, 'F1', [71, 85, 105]);
            $textY += 10;
        }

        if ((float) ($item['line_discount'] ?? 0) > 0) {
            $this->text(96, $textY, 'Line discount: ' . $this->money($item['line_discount']), 7.6, 'F1', [185, 28, 28]);
        }

        $this->text(360, $this->y + 16, (string) (int) ($item['quantity'] ?? 0), 8.8, 'F2', [15, 23, 42]);
        $this->text(404, $this->y + 16, $this->money($item['unit_price'] ?? 0), 8.3, 'F1', [15, 23, 42]);
        $this->text(504, $this->y + 16, $this->money($item['amount'] ?? 0), 8.3, 'F2', [15, 23, 42]);

        $this->y += $rowHeight;
    }

    private function drawTotalsAndFooter(array $invoice, array $settings): void
    {
        if ($this->y + 205 > 804) {
            $this->finishPage();
            $this->content = '';
            $this->pageXObjects = [];
            $this->pageExtGStates = [];
            $this->y = self::MARGIN;
        }

        $totalsX = 350;
        $labelX = $totalsX;
        $valueX = 492;
        $this->y += 20;

        $this->totalLine($labelX, $valueX, 'Subtotal', $invoice['subtotal']);
        $this->totalLine($labelX, $valueX, 'Discount', $invoice['discount']);
        $this->totalLine($labelX, $valueX, 'VAT', $invoice['vat']);
        $this->line($totalsX, $this->y + 2, self::PAGE_WIDTH - self::MARGIN, $this->y + 2, [203, 213, 225]);
        $this->y += 11;
        $this->totalLine($labelX, $valueX, 'Grand Total', $invoice['grand_total'], true);
        $this->totalLine($labelX, $valueX, 'Balance Due', $invoice['balance_due'], true, [15, 118, 110]);

        $footerY = max($this->y + 20, 650);
        $this->text(self::MARGIN, $footerY, (string) ($settings['footer_note'] ?? 'Thank you for your business.'), 10.5, 'F2', [15, 118, 110]);
        $this->text(self::MARGIN, $footerY + 25, 'Prepared By', 8.6, 'F2', [100, 116, 139]);
        $this->line(self::MARGIN, $footerY + 57, 176, $footerY + 57, [148, 163, 184]);
        $this->text(224, $footerY + 25, 'Authorized Signature', 8.6, 'F2', [100, 116, 139]);
        $this->line(224, $footerY + 57, 380, $footerY + 57, [148, 163, 184]);
        $this->text(420, $footerY + 25, 'Company Stamp', 8.6, 'F2', [100, 116, 139]);
        $this->strokeRect(420, $footerY + 38, 108, 58, [148, 163, 184]);

        $terms = trim((string) ($settings['terms'] ?? ''));
        if ($terms !== '') {
            $this->text(self::MARGIN, $footerY + 88, 'Terms & Conditions', 8.5, 'F2', [15, 23, 42]);
            $termY = $footerY + 101;
            foreach ($this->wrapText($terms, 118, 4) as $line) {
                $this->text(self::MARGIN, $termY, $line, 7.4, 'F1', [71, 85, 105]);
                $termY += 9;
            }
        }

        $contact = 'Contact: ' . (string) $settings['phone'] . ' / ' . (string) $settings['email'];
        $this->text(self::MARGIN, 813, $contact, 7.8, 'F1', [100, 116, 139]);
    }

    private function totalLine(float $labelX, float $valueX, string $label, mixed $value, bool $strong = false, array $color = [15, 23, 42]): void
    {
        $this->text($labelX, $this->y, $label, $strong ? 9.8 : 8.8, $strong ? 'F2' : 'F1', $color);
        $this->text($valueX, $this->y, $this->money($value), $strong ? 9.8 : 8.8, $strong ? 'F2' : 'F1', $color);
        $this->y += $strong ? 17 : 14;
    }

    private function statusPill(float $x, float $y, string $status): void
    {
        $colors = match ($status) {
            'paid' => [[22, 163, 74], [255, 255, 255]],
            'unpaid' => [[217, 119, 6], [255, 255, 255]],
            'cancelled' => [[220, 38, 38], [255, 255, 255]],
            default => [[100, 116, 139], [255, 255, 255]],
        };

        $this->fillRect($x, $y, 88, 22, $colors[0]);
        $this->text($x + 12, $y + 7, strtoupper(readable_status($status)), 8.2, 'F2', $colors[1]);
    }

    private function finishPage(): void
    {
        if ($this->content === '') {
            return;
        }

        $this->pdf->addPage($this->content, $this->pageXObjects, $this->pageExtGStates);
    }

    private function loadCachedImage(string $path): ?array
    {
        $resolved = $this->resolveImagePath($path);

        if ($resolved === null) {
            return null;
        }

        if (!isset($this->imageCache[$resolved])) {
            $image = $this->loadImage($resolved);

            if ($image === null) {
                return null;
            }

            $this->imageCache[$resolved] = $image;
        }

        return $this->imageCache[$resolved];
    }

    private function drawImage(string $path, float $x, float $y, float $w, float $h): bool
    {
        $image = $this->loadCachedImage($path);

        if ($image === null) {
            return false;
        }

        $imgW = max(1, (float) $image['width']);
        $imgH = max(1, (float) $image['height']);
        $scale = min($w / $imgW, $h / $imgH);
        $scaledW = $imgW * $scale;
        $scaledH = $imgH * $scale;
        $offsetX = ($w - $scaledW) / 2;
        $offsetY = ($h - $scaledH) / 2;

        $name = 'Im' . $image['object_id'];
        $this->pageXObjects[$name] = $image['object_id'];
        $pdfY = self::PAGE_HEIGHT - $y - $h;
        $this->content .= sprintf(
            "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
            $scaledW,
            $scaledH,
            $x + $offsetX,
            $pdfY + $offsetY,
            $name
        );

        return true;
    }

    private function drawImageCover(string $path, float $x, float $y, float $w, float $h): bool
    {
        $image = $this->loadCachedImage($path);

        if ($image === null) {
            return false;
        }

        $imgW = max(1, (float) $image['width']);
        $imgH = max(1, (float) $image['height']);
        $scale = max($w / $imgW, $h / $imgH);
        $scaledW = $imgW * $scale;
        $scaledH = $imgH * $scale;
        $offsetX = ($w - $scaledW) / 2;
        $offsetY = ($h - $scaledH) / 2;

        $name = 'Im' . $image['object_id'];
        $this->pageXObjects[$name] = $image['object_id'];
        $pdfY = self::PAGE_HEIGHT - $y - $h;
        $this->content .= sprintf(
            "q %.2F %.2F %.2F %.2F re W n %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
            $x,
            $pdfY,
            $w,
            $h,
            $scaledW,
            $scaledH,
            $x + $offsetX,
            $pdfY + $offsetY,
            $name
        );

        return true;
    }

    private function drawWatermark(string $logoPath, float $zoneTop, float $zoneBottom): void
    {
        $zoneHeight = $zoneBottom - $zoneTop;

        if ($zoneHeight < 60) {
            return;
        }

        $maxWidth = self::PAGE_WIDTH - (self::MARGIN * 2);
        $size = min(340.0, $zoneHeight * 0.85, $maxWidth);
        $x = (self::PAGE_WIDTH - $size) / 2;
        $y = $zoneTop + ($zoneHeight - $size) / 2;

        $this->drawGrayscaleImage($logoPath, $x, $y, $size, $size, 0.1);
    }

    private function drawGrayscaleImage(string $path, float $x, float $y, float $w, float $h, float $opacity): bool
    {
        $resolved = $this->resolveImagePath($path);

        if ($resolved === null) {
            return false;
        }

        if (!isset($this->grayscaleImageCache[$resolved])) {
            $image = $this->loadGrayscaleImage($resolved);

            if ($image === null) {
                return false;
            }

            $this->grayscaleImageCache[$resolved] = $image;
        }

        $image = $this->grayscaleImageCache[$resolved];
        $imageName = 'Im' . $image['object_id'];
        $this->pageXObjects[$imageName] = $image['object_id'];

        if ($this->watermarkGStateId === null) {
            $this->watermarkGStateId = $this->pdf->addDictionaryObject(
                '/Type /ExtGState /ca ' . $this->formatNumber($opacity) . ' /CA ' . $this->formatNumber($opacity)
            );
        }

        $gStateName = 'GSWatermark';
        $this->pageExtGStates[$gStateName] = $this->watermarkGStateId;

        $pdfY = self::PAGE_HEIGHT - $y - $h;
        $this->content .= sprintf(
            "q /%s gs %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
            $gStateName,
            $w,
            $h,
            $x,
            $pdfY,
            $imageName
        );

        return true;
    }

    private function loadGrayscaleImage(string $path): ?array
    {
        $bytes = file_get_contents($path);

        if ($bytes === false || !str_starts_with($bytes, "\x89PNG")) {
            return null;
        }

        $png = $this->parsePng($bytes);

        if ($png === null) {
            return null;
        }

        $channels = $png['color_space'] === 'DeviceGray' ? 1 : 3;
        $raw = $png['raw'];

        if ($channels === 1) {
            $grayBytes = $raw;
        } else {
            $grayBytes = '';
            $length = strlen($raw);

            for ($i = 0; $i + 2 < $length; $i += 3) {
                $r = ord($raw[$i]);
                $g = ord($raw[$i + 1]);
                $b = ord($raw[$i + 2]);
                $grayBytes .= chr((int) round(0.299 * $r + 0.587 * $g + 0.114 * $b));
            }
        }

        $smask = '';

        if (!empty($png['alpha'])) {
            $smaskId = $this->pdf->addStreamObject(
                $png['alpha'],
                '/Type /XObject /Subtype /Image /Width ' . $png['width'] .
                ' /Height ' . $png['height'] .
                ' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode'
            );
            $smask = ' /SMask ' . $smaskId . ' 0 R';
        }

        $objectId = $this->pdf->addStreamObject(
            gzcompress($grayBytes),
            '/Type /XObject /Subtype /Image /Width ' . $png['width'] .
            ' /Height ' . $png['height'] .
            ' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode' . $smask
        );

        return ['object_id' => $objectId, 'width' => $png['width'], 'height' => $png['height']];
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') ?: '0';
    }

    private function resolveImagePath(string $path): ?string
    {
        $path = trim($path);

        if (MediaStorage::isCloudinaryUrl($path)) {
            return $this->downloadCloudinaryImage($path);
        }

        if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
            return null;
        }

        $candidate = str_starts_with($path, ROOT_PATH)
            ? $path
            : ROOT_PATH . '/public/' . ltrim($path, '/\\');

        $real = realpath($candidate);

        if ($real === false || !is_file($real)) {
            return null;
        }

        return $real;
    }

    /**
     * Fetches a Cloudinary image as a JPEG (the only formats the PDF writer embeds are JPEG/PNG)
     * into the temp folder, reusing it for later PDFs. Only res.cloudinary.com is ever fetched.
     */
    private function downloadCloudinaryImage(string $url): ?string
    {
        $jpegUrl = MediaStorage::cloudinaryVariant($url, 'f_jpg,q_85,c_limit,w_800');
        $cacheFile = sys_get_temp_dir() . '/dcf-pdf-' . sha1($jpegUrl) . '.jpg';

        if (is_file($cacheFile) && filesize($cacheFile) > 0) {
            return $cacheFile;
        }

        $curl = curl_init($jpegUrl);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false]);
        $bytes = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($status !== 200 || !is_string($bytes) || !str_starts_with($bytes, "\xFF\xD8")) {
            return null;
        }

        return @file_put_contents($cacheFile, $bytes) !== false ? $cacheFile : null;
    }

    private function loadImage(string $path): ?array
    {
        $bytes = file_get_contents($path);

        if ($bytes === false) {
            return null;
        }

        if (str_starts_with($bytes, "\xFF\xD8")) {
            $info = @getimagesize($path);

            if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') {
                return null;
            }

            $objectId = $this->pdf->addStreamObject(
                $bytes,
                '/Type /XObject /Subtype /Image /Width ' . (int) $info[0] .
                ' /Height ' . (int) $info[1] .
                ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode'
            );

            return ['object_id' => $objectId, 'width' => (int) $info[0], 'height' => (int) $info[1]];
        }

        if (str_starts_with($bytes, "\x89PNG")) {
            $png = $this->parsePng($bytes);

            if ($png === null) {
                return null;
            }

            $smask = '';

            if (!empty($png['alpha'])) {
                $smaskId = $this->pdf->addStreamObject(
                    $png['alpha'],
                    '/Type /XObject /Subtype /Image /Width ' . $png['width'] .
                    ' /Height ' . $png['height'] .
                    ' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode'
                );
                $smask = ' /SMask ' . $smaskId . ' 0 R';
            }

            $objectId = $this->pdf->addStreamObject(
                $png['data'],
                '/Type /XObject /Subtype /Image /Width ' . $png['width'] .
                ' /Height ' . $png['height'] .
                ' /ColorSpace /' . $png['color_space'] .
                ' /BitsPerComponent 8 /Filter /FlateDecode' . $smask
            );

            return ['object_id' => $objectId, 'width' => $png['width'], 'height' => $png['height']];
        }

        return null;
    }

    private function parsePng(string $bytes): ?array
    {
        $offset = 8;
        $width = 0;
        $height = 0;
        $bitDepth = 0;
        $colorType = 0;
        $interlace = 0;
        $idat = '';
        $length = strlen($bytes);

        while ($offset + 8 <= $length) {
            $chunkLength = unpack('N', substr($bytes, $offset, 4))[1];
            $type = substr($bytes, $offset + 4, 4);
            $data = substr($bytes, $offset + 8, $chunkLength);
            $offset += $chunkLength + 12;

            if ($type === 'IHDR') {
                $header = unpack('Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace', $data);
                $width = (int) $header['width'];
                $height = (int) $header['height'];
                $bitDepth = (int) $header['bitDepth'];
                $colorType = (int) $header['colorType'];
                $interlace = (int) $header['interlace'];
            } elseif ($type === 'IDAT') {
                $idat .= $data;
            } elseif ($type === 'IEND') {
                break;
            }
        }

        if ($width < 1 || $height < 1 || $bitDepth !== 8 || $interlace !== 0 || $idat === '') {
            return null;
        }

        $channels = match ($colorType) {
            0 => 1,
            2 => 3,
            6 => 4,
            default => 0,
        };

        if ($channels === 0) {
            return null;
        }

        $decoded = @zlib_decode($idat);

        if (!is_string($decoded)) {
            return null;
        }

        $stride = $width * $channels;
        $pos = 0;
        $previous = str_repeat("\0", $stride);
        $imageData = '';
        $alphaData = '';

        for ($row = 0; $row < $height; $row++) {
            if ($pos >= strlen($decoded)) {
                return null;
            }

            $filter = ord($decoded[$pos]);
            $pos++;
            $scanline = substr($decoded, $pos, $stride);
            $pos += $stride;
            $unfiltered = $this->unfilterPngLine($scanline, $previous, $filter, $channels);
            $previous = $unfiltered;

            if ($colorType === 6) {
                for ($i = 0; $i < $stride; $i += 4) {
                    $imageData .= $unfiltered[$i] . $unfiltered[$i + 1] . $unfiltered[$i + 2];
                    $alphaData .= $unfiltered[$i + 3];
                }
            } else {
                $imageData .= $unfiltered;
            }
        }

        return [
            'width' => $width,
            'height' => $height,
            'color_space' => $colorType === 0 ? 'DeviceGray' : 'DeviceRGB',
            'data' => gzcompress($imageData),
            'alpha' => $alphaData !== '' ? gzcompress($alphaData) : null,
            'raw' => $imageData,
        ];
    }

    private function unfilterPngLine(string $scanline, string $previous, int $filter, int $bpp): string
    {
        $length = strlen($scanline);
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $x = ord($scanline[$i]);
            $a = $i >= $bpp ? ord($out[$i - $bpp]) : 0;
            $b = $previous !== '' ? ord($previous[$i]) : 0;
            $c = ($i >= $bpp && $previous !== '') ? ord($previous[$i - $bpp]) : 0;

            $predictor = match ($filter) {
                1 => $a,
                2 => $b,
                3 => intdiv($a + $b, 2),
                4 => $this->paeth($a, $b, $c),
                default => 0,
            };

            $out .= chr(($x + $predictor) & 0xFF);
        }

        return $out;
    }

    private function paeth(int $a, int $b, int $c): int
    {
        $p = $a + $b - $c;
        $pa = abs($p - $a);
        $pb = abs($p - $b);
        $pc = abs($p - $c);

        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        }

        return $pb <= $pc ? $b : $c;
    }

    private function text(float $x, float $y, string $text, float $size = 9, string $font = 'F1', array $rgb = [15, 23, 42]): void
    {
        $pdfY = self::PAGE_HEIGHT - $y - $size;
        $this->content .= sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $font,
            $size,
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $x,
            $pdfY,
            $this->escapePdfText($text)
        );
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $rgb = [0, 0, 0]): void
    {
        $this->content .= sprintf(
            "%.3F %.3F %.3F RG %.2F %.2F m %.2F %.2F l S\n",
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $x1,
            self::PAGE_HEIGHT - $y1,
            $x2,
            self::PAGE_HEIGHT - $y2
        );
    }

    private function fillRect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->content .= sprintf(
            "%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n",
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $x,
            self::PAGE_HEIGHT - $y - $h,
            $w,
            $h
        );
    }

    private function strokeRect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->content .= sprintf(
            "%.3F %.3F %.3F RG %.2F %.2F %.2F %.2F re S\n",
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $x,
            self::PAGE_HEIGHT - $y - $h,
            $w,
            $h
        );
    }

    private function wrapText(string $text, int $maxChars, int $maxLines): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return [];
        }

        $lines = [];

        foreach (explode("\n", $text) as $segment) {
            foreach (explode("\n", wordwrap(trim($segment), $maxChars, "\n", true)) as $line) {
                $line = trim($line);

                if ($line !== '') {
                    $lines[] = $line;
                }

                if (count($lines) >= $maxLines) {
                    return $lines;
                }
            }
        }

        return $lines;
    }

    private function money(mixed $amount): string
    {
        $decimals = in_array($this->currency, ['TZS', 'UGX', 'RWF'], true) ? 0 : 2;

        return $this->currency . ' ' . number_format((float) $amount, $decimals);
    }

    private function dateLabel(mixed $date): string
    {
        $timestamp = strtotime((string) $date);

        return $timestamp ? date('M d, Y', $timestamp) : (string) $date;
    }

    private function escapePdfText(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        if (is_string($converted)) {
            $text = $converted;
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}

class NativePdfDocument
{
    private array $objects = [null];
    private array $pages = [];
    private int $catalogObject;
    private int $pagesObject;
    private int $fontRegularObject;
    private int $fontBoldObject;

    public function __construct(private float $width, private float $height)
    {
        $this->catalogObject = $this->reserveObject();
        $this->pagesObject = $this->reserveObject();
        $this->fontRegularObject = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $this->fontBoldObject = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
    }

    public function addPage(string $content, array $xobjects = [], array $extGStates = []): void
    {
        $contentObject = $this->addStreamObject($content, '');
        $resource = '/Font << /F1 ' . $this->fontRegularObject . ' 0 R /F2 ' . $this->fontBoldObject . ' 0 R >>';

        if ($xobjects) {
            $resource .= ' /XObject <<';

            foreach ($xobjects as $name => $objectId) {
                $resource .= ' /' . $name . ' ' . (int) $objectId . ' 0 R';
            }

            $resource .= ' >>';
        }

        if ($extGStates) {
            $resource .= ' /ExtGState <<';

            foreach ($extGStates as $name => $objectId) {
                $resource .= ' /' . $name . ' ' . (int) $objectId . ' 0 R';
            }

            $resource .= ' >>';
        }

        $pageObject = $this->addObject(
            '<< /Type /Page /Parent ' . $this->pagesObject . ' 0 R' .
            ' /MediaBox [0 0 ' . $this->format($this->width) . ' ' . $this->format($this->height) . ']' .
            ' /Resources << ' . $resource . ' >>' .
            ' /Contents ' . $contentObject . ' 0 R >>'
        );

        $this->pages[] = $pageObject;
    }

    public function addStreamObject(string $data, string $dictionaryEntries): int
    {
        $dictionary = '<< /Length ' . strlen($data);

        if (trim($dictionaryEntries) !== '') {
            $dictionary .= ' ' . trim($dictionaryEntries);
        }

        $dictionary .= " >>\nstream\n" . $data . "\nendstream";

        return $this->addObject($dictionary);
    }

    public function addDictionaryObject(string $dictionaryEntries): int
    {
        return $this->addObject('<< ' . trim($dictionaryEntries) . ' >>');
    }

    public function output(): string
    {
        $kids = implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $this->pages));
        $this->setObject($this->pagesObject, '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($this->pages) . ' >>');
        $this->setObject($this->catalogObject, '<< /Type /Catalog /Pages ' . $this->pagesObject . ' 0 R >>');

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        for ($index = 1; $index < count($this->objects); $index++) {
            $object = $this->objects[$index];
            $offsets[$index] = strlen($pdf);
            $pdf .= $index . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . count($this->objects) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < count($this->objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . count($this->objects) . ' /Root ' . $this->catalogObject . " 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function reserveObject(): int
    {
        $id = count($this->objects);
        $this->objects[$id] = '';

        return $id;
    }

    private function addObject(string $content): int
    {
        $id = count($this->objects);
        $this->objects[$id] = $content;

        return $id;
    }

    private function setObject(int $id, string $content): void
    {
        $this->objects[$id] = $content;
    }

    private function format(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
