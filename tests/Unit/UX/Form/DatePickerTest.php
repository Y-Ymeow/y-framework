<?php

declare(strict_types=1);

namespace Tests\Unit\UX\Form;

use Tests\TestCase;
use Framework\UX\Form\DatePicker;

class DatePickerTest extends TestCase
{
    public function test_basic_structure(): void
    {
        $picker = DatePicker::make();

        $html = (string) $picker;
        $this->assertStringContainsString('ux-date-picker', $html);
    }

    public function test_value_sets_data_attribute(): void
    {
        $picker = DatePicker::make()->value('2024-06-15');

        $html = (string) $picker;
        $this->assertStringContainsString('2024-06-15', $html);
    }

    public function test_format_attribute(): void
    {
        $picker = DatePicker::make()->format('YYYY-MM-DD');

        $html = (string) $picker;
        $this->assertStringContainsString('YYYY-MM-DD', $html);
    }

    public function test_min_date_attribute(): void
    {
        $picker = DatePicker::make()->minDate('2024-01-01');

        $html = (string) $picker;
        $this->assertStringContainsString('2024-01-01', $html);
    }

    public function test_max_date_attribute(): void
    {
        $picker = DatePicker::make()->maxDate('2025-12-31');

        $html = (string) $picker;
        $this->assertStringContainsString('2025-12-31', $html);
    }

    public function test_placeholder(): void
    {
        $picker = DatePicker::make()->placeholder('Select date');

        $html = (string) $picker;
        $this->assertStringContainsString('Select date', $html);
    }

    public function test_disabled_state(): void
    {
        $picker = DatePicker::make()->disabled();

        $this->assertStringContainsString('ux-date-picker-disabled', (string) $picker);
    }

    public function test_allow_clear_mode(): void
    {
        $picker = DatePicker::make()->allowClear(true);

        // Component should render without error
        $html = (string) $picker;
        $this->assertStringContainsString('ux-date-picker', $html);
    }
}
