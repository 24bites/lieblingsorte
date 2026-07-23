<?php

namespace Tests\Feature;

use App\Support\PinImageComposer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PinImageComposerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function fakeSourcePhoto(string $relativePath, int $width = 1600, int $height = 900): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 90, 130, 110));
        ob_start();
        imagejpeg($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($relativePath, $contents);
    }

    public function test_compose_produces_a_1000_by_1500_pin_image(): void
    {
        $this->fakeSourcePhoto('regions/test/cover.jpg');

        $path = PinImageComposer::compose(
            'regions/test/cover.jpg',
            'Kalterer See: Der schönste Badesee Südtirols',
            'Geheimtipp für Familien mit Kindern',
            'pins/test',
            'kalterer-see'
        );

        $this->assertNotNull($path);
        $this->assertTrue(Storage::disk('public')->exists($path));

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(1000, $width);
        $this->assertSame(1500, $height);
    }

    public function test_compose_works_without_a_subline(): void
    {
        $this->fakeSourcePhoto('regions/test/cover.jpg', 900, 1600);

        $path = PinImageComposer::compose(
            'regions/test/cover.jpg',
            'Ein sehr langer Titel, der über mehrere Zeilen umbrechen sollte',
            null,
            'pins/test',
            'langer-titel'
        );

        $this->assertNotNull($path);
        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(1000, $width);
        $this->assertSame(1500, $height);
    }

    public function test_compose_returns_null_when_source_is_missing(): void
    {
        $path = PinImageComposer::compose('regions/does-not-exist.jpg', 'Titel', null, 'pins/test', 'missing');

        $this->assertNull($path);
    }
}
