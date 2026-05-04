<?php

declare(strict_types=1);

namespace Tests\Unit\UX\Form;

use Tests\TestCase;
use Framework\UX\Form\DateRangePicker;

class DateRangePickerTest extends TestCase
{
    public function test_basic_structure(): void
    {
        $picker = DateRangePicker::make();

        $html = (string) $picker;
        $this->assertStringContainsString('ux-date-range-picker', $html);
    }

    public function test_start_value_attribute(): void
    {
        $picker = DateRangePicker::make()->startValue('2024-01-01');

        $html = (string) $picker;
        $this->assertStringContainsString('2024-01-01', $html);
    }

    public function test_end_value_attribute(): void
    {
        $picker = DateRangePicker::make()->endValue('2024-12-31');

        $html = (string) $picker;
        $this->assertStringContainsString('2024-12-31', $html);
    }

    public function test_separator_attribute(): void
    {
        $picker = DateRangePicker::make()->separator('to');

        $html = (string) $picker;
        $this->assertStringContainsString('to', $html);
    }

    public function test_placeholder(): void
    {
        $picker = DateRangePicker::make()->placeholder('Pick range');

        $html = (string) $picker;
        $this->assertStringContainsString('Pick range', $html);
    }

    public function test_show_time_mode(): void
    {
        $picker = DateRangePicker::make()->showTime(true);

        $html = (string) $picker;
        $this->assertStringContainsString('show-time', $html);
    }

    public function test_disabled_state(): void
    {
        $picker = DateRangePicker::make()->disabled();

        $this->assertStringContainsString('ux-date-range-picker-disabled', (string) $picker);
    }

    public function test_has_input_wrapper(): void
    {
        $picker = DateRangePicker::make();

        $html = (string) $picker;
        $this->assertStringContainsString('ux-date-range-picker-input-wrapper', $html);
    }
}
