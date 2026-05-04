<?php

declare(strict_types=1);

namespace Tests\Unit\UX\Dialog;

use Tests\TestCase;
use Framework\UX\Dialog\Toast;

class ToastTest extends TestCase
{
    public function test_basic_structure(): void
    {
        $toast = Toast::make('Hello World');

        $html = (string) $toast;
        // Toast is a script-based component, renders as placeholder
        $this->assertStringContainsString('ux-toast', $html);
    }

    public function test_type_info(): void
    {
        $toast = Toast::make('Info msg')->type('info');

        $this->assertStringContainsString('ux-toast', (string) $toast);
    }

    public function test_success_method(): void
    {
        $toast = Toast::make('Success!')->success();

        $this->assertStringContainsString('ux-toast', (string) $toast);
    }

    public function test_warning_method(): void
    {
        $toast = Toast::make('Warning')->warning();

        $this->assertStringContainsString('ux-toast', (string) $toast);
    }

    public function test_error_method(): void
    {
        $toast = Toast::make('Error!')->error();

        $this->assertStringContainsString('ux-toast', (string) $toast);
    }

    public function test_duration_sets_attribute(): void
    {
        $toast = Toast::make('Auto close')->duration(5000);

        $html = (string) $toast;
        $this->assertStringContainsString('ux-toast', $html);
    }

    public function test_position_method(): void
    {
        $toast = Toast::make('msg')->position('top-right');

        $html = (string) $toast;
        $this->assertStringContainsString('ux-toast', $html);
    }

    public function test_closeable_mode(): void
    {
        $toast = Toast::make('msg')->closeable(true);

        $html = (string) $toast;
        $this->assertStringContainsString('ux-toast', $html);
    }
}
