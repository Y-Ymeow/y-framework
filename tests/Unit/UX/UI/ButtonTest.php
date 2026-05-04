<?php

declare(strict_types=1);

namespace Tests\Unit\UX\UI;

use Tests\TestCase;
use Framework\UX\UI\Button;

class ButtonTest extends TestCase
{
    public function test_basic_button_renders_with_label(): void
    {
        $btn = Button::make('Click Me');

        $html = (string) $btn;
        // Label is rendered inside ux-btn-label span
        $this->assertStringContainsString('ux-btn', $html);
        // Button should have content (label or icon)
        $this->assertGreaterThan(50, strlen($html));
    }

    public function test_button_type_primary(): void
    {
        $btn = Button::make('Submit')->primary();

        $this->assertStringContainsString('ux-btn-primary', (string) $btn);
    }

    public function test_button_type_success(): void
    {
        $btn = Button::make('Save')->success();

        $this->assertStringContainsString('ux-btn-success', (string) $btn);
    }

    public function test_button_type_warning(): void
    {
        $btn = Button::make('Warning')->warning();

        $this->assertStringContainsString('ux-btn-warning', (string) $btn);
    }

    public function test_button_type_danger(): void
    {
        $btn = Button::make('Delete')->danger();

        $this->assertStringContainsString('ux-btn-danger', (string) $btn);
    }

    public function test_button_size_small(): void
    {
        $btn = Button::make('Small')->sm();

        $this->assertStringContainsString('ux-btn-sm', (string) $btn);
    }

    public function test_button_size_large(): void
    {
        $btn = Button::make('Large')->lg();

        $this->assertStringContainsString('ux-btn-lg', (string) $btn);
    }

    public function test_button_disabled_state(): void
    {
        $btn = Button::make('Disabled')->disabled();

        $html = (string) $btn;
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_button_loading_state(): void
    {
        $btn = Button::make('Loading')->loading();

        $this->assertStringContainsString('ux-btn-loading', (string) $btn);
    }

    public function test_button_outline_variant(): void
    {
        $btn = Button::make('Outline')->outline();

        $this->assertStringContainsString('ux-btn-outline', (string) $btn);
    }

    public function test_button_icon_only(): void
    {
        $btn = Button::make()->icon('bi-plus');

        $this->assertStringContainsString('bi-plus', (string) $btn);
    }

    public function test_button_live_action_binding(): void
    {
        $btn = Button::make('Action')->liveAction('save');

        $this->assertStringContainsString('data-action', (string) $btn);
    }
}
