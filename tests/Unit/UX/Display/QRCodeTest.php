<?php

declare(strict_types=1);

namespace Tests\Unit\UX\Display;

use Tests\TestCase;
use Framework\UX\Display\QRCode;

class QRCodeTest extends TestCase
{
    public function test_basic_structure(): void
    {
        $qr = QRCode::make('https://example.com');

        $html = (string) $qr;
        $this->assertStringContainsString('ux-qrcode', $html);
    }

    public function test_value_in_output(): void
    {
        $qr = QRCode::make('hello world');

        $html = (string) $qr;
        // Value is stored in data-qrcode-value attribute
        $this->assertStringContainsString('data-qrcode-value', $html);
    }

    public function test_size_attribute(): void
    {
        $qr = QRCode::make('test')->size(256);

        $html = (string) $qr;
        $this->assertStringContainsString('256', $html);
    }

    public function test_color_attribute(): void
    {
        $qr = QRCode::make('test')->color('#ff0000');

        $html = (string) $qr;
        $this->assertStringContainsString('#ff0000', $html);
    }

    public function test_background_color_attribute(): void
    {
        $qr = QRCode::make('test')->bgColor('#ffffff');

        $html = (string) $qr;
        $this->assertStringContainsString('#ffffff', $html);
    }

    public function test_renders_canvas_element(): void
    {
        $qr = QRCode::make('test');

        $html = (string) $qr;
        $this->assertStringContainsString('<canvas', $html);
    }

    public function test_icon_with_size(): void
    {
        $qr = QRCode::make('test')->icon('/logo.png', 48);

        $html = (string) $qr;
        $this->assertStringContainsString('/logo.png', $html);
        $this->assertStringContainsString('48', $html);
    }
}
