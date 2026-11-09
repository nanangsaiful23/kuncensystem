<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_number_and_money_helpers(): void
    {
        $this->assertSame('1,234,567', formatNumber(1234567));
        $this->assertSame('Rp1,500.00', showRupiah('1,500'));
        $this->assertSame('', checkNullString(null));
        $this->assertSame(25.0, calculateProfit(100, 125));
    }

    public function test_resize_image_scales_down_to_512px_jpeg(): void
    {
        Storage::fake();

        $path = resizeImage('good_photos', UploadedFile::fake()->image('big.png', 1024, 600));

        $this->assertMatchesRegularExpression('#^good_photos/[0-9a-f]{32}\.jpg$#', $path);
        Storage::assertExists($path);
        [$w, $h, $type] = getimagesizefromstring(Storage::get($path));
        $this->assertSame([512, 300, IMAGETYPE_JPEG], [$w, $h, $type]);
    }

    public function test_resize_image_never_upscales(): void
    {
        Storage::fake();

        $path = resizeImage('good_photos', UploadedFile::fake()->image('small.png', 200, 100));

        [$w, $h] = getimagesizefromstring(Storage::get($path));
        $this->assertSame([200, 100], [$w, $h]);
    }

    public function test_qr_code_generates_svg(): void
    {
        $svg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate('123');

        $this->assertStringContainsString('<svg', $svg);
    }
}
